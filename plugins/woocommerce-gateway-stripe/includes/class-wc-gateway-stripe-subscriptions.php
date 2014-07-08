<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Gateway_Stripe_Subscriptions class.
 *
 * @extends WC_Gateway_Stripe
 */
class WC_Gateway_Stripe_Subscriptions extends WC_Gateway_Stripe {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'scheduled_subscription_payment_' . $this->id, array( $this, 'scheduled_subscription_payment' ), 10, 3 );
		add_filter( 'woocommerce_subscriptions_renewal_order_meta_query', array( $this, 'remove_renewal_order_meta' ), 10, 4 );
		add_action( 'woocommerce_subscriptions_changed_failing_payment_method_stripe', array( $this, 'update_failing_payment_method' ), 10, 3 );
		// display the current payment method used for a subscription in the "My Subscriptions" table
		add_filter( 'woocommerce_my_subscriptions_recurring_payment_method', array( $this, 'maybe_render_subscription_payment_method' ), 10, 3 );
	}

	/**
     * Process the payment
     */
	function process_payment( $order_id ) {
		if ( class_exists( 'WC_Subscriptions_Order' ) && WC_Subscriptions_Order::order_contains_subscription( $order_id ) ) {

			$order        = new WC_Order( $order_id );
			$stripe_token = isset( $_POST['stripe_token'] ) ? wc_clean( $_POST['stripe_token'] ) : '';

			// Use Stripe CURL API for payment
			try {

				$post_data   = array();
				$customer_id = 0;

				// Check if paying via customer ID
				if ( isset( $_POST['stripe_customer_id'] ) && $_POST['stripe_customer_id'] !== 'new' && is_user_logged_in() ) {
					$customer_ids = get_user_meta( get_current_user_id(), '_stripe_customer_id', false );

					if ( isset( $customer_ids[ $_POST['stripe_customer_id'] ]['customer_id'] ) ) {
						$customer_id = $customer_ids[ $_POST['stripe_customer_id'] ]['customer_id'];
					} else {
						throw new Exception( __( 'Invalid card.', 'woocommerce-gateway-stripe' ) );
					}
				}

				// Else, Check token
				elseif ( empty( $stripe_token ) ) {
					throw new Exception( __( 'Please make sure your card details have been entered correctly and that your browser supports JavaScript.', 'woocommerce-gateway-stripe' ) );
				}

				if ( $customer_id ) {

					// Store the ID in the order
					update_post_meta( $order->id, '_stripe_customer_id', $customer_id );

				} else {

					// Store token/add customer
					$customer_id = $this->add_customer( $order, $stripe_token );

					if ( is_wp_error( $customer_id ) ) {
						throw new Exception( $customer_id->get_error_message() );
					}
				}

				$initial_payment = WC_Subscriptions_Order::get_total_initial_payment( $order );

				if ( $initial_payment > 0 ) {
					$payment_response = $this->process_subscription_payment( $order, $initial_payment );
				}

				if ( isset( $payment_response ) && is_wp_error( $payment_response ) ) {

					throw new Exception( $payment_response->get_error_message() );

				} else {

					if ( isset( $payment_response->balance_transaction ) && isset( $payment_response->balance_transaction->fee ) ) {
						$fee = number_format( $payment_response->balance_transaction->fee / 100, 2, '.', '' );
						update_post_meta( $order->id, 'Stripe Fee', $fee );
						update_post_meta( $order->id, 'Net Revenue From Stripe', $order->order_total - $fee );
					}

					// Payment complete
					$order->payment_complete();

					// Remove cart
					WC()->cart->empty_cart();

					// Activate subscriptions
					WC_Subscriptions_Manager::activate_subscriptions_for_order( $order );

					// Store token
					if ( $stripe_token ) {
						update_post_meta( $order->id, '_stripe_token', $stripe_token );
					}

					// Return thank you page redirect
					return array(
						'result' 	=> 'success',
						'redirect'	=> $this->get_return_url( $order )
					);
				}

			} catch( Exception $e ) {
				wc_add_notice( __('Error:', 'woocommerce-gateway-stripe') . ' "' . $e->getMessage() . '"', 'error' );
				return;
			}

		} else {
			return parent::process_payment( $order_id );
		}
	}

	/**
	 * scheduled_subscription_payment function.
	 *
	 * @param $amount_to_charge float The amount to charge.
	 * @param $order WC_Order The WC_Order object of the order which the subscription was purchased in.
	 * @param $product_id int The ID of the subscription product for which this payment relates.
	 * @access public
	 * @return void
	 */
	public function scheduled_subscription_payment( $amount_to_charge, $order, $product_id ) {
		$result = $this->process_subscription_payment( $order, $amount_to_charge );

		if ( is_wp_error( $result ) ) {
			WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order, $product_id );
		} else {
			WC_Subscriptions_Manager::process_subscription_payments_on_order( $order );
		}
	}

	/**
	 * process_subscription_payment function.
	 *
	 * @access public
	 * @param mixed $order
	 * @param int $amount (default: 0)
	 * @param string $stripe_token (default: '')
	 * @return void
	 */
	public function process_subscription_payment( $order = '', $amount = 0 ) {
		$order_items       = $order->get_items();
		$order_item        = array_shift( $order_items );
		$subscription_name = sprintf( __( 'Subscription for "%s"', 'woocommerce-gateway-stripe' ), $order_item['name'] ) . ' ' . sprintf( __( '(Order %s)', 'wp_stripe' ), $order->get_order_number() );

		if ( $amount * 100 < 50 ) {
			return new WP_Error( 'stripe_error', __( 'Sorry, the minimum allowed order total is 0.50 to use this payment method.', 'woocommerce-gateway-stripe' ) );
		}

		$stripe_customer = get_post_meta( $order->id, '_stripe_customer_id', true );

		if ( ! $stripe_customer ) {
			return new WP_Error( 'stripe_error', __( 'Customer not found', 'woocommerce-gateway-stripe' ) );
		}

		// Charge the customer
		$response = $this->stripe_request( array(
			'amount'      => $amount * 100, // In cents, minimum amount = 50
			'currency'    => strtolower( get_woocommerce_currency() ),
			'description' => $subscription_name,
			'customer'    => $stripe_customer,
			'expand[]'    => 'balance_transaction'
		), 'charges' );

		if ( is_wp_error( $response ) ) {
			return $response;
		} else {
			$order->add_order_note( sprintf( __( 'Stripe subscription payment completed (Charge ID: %s)', 'woocommerce-gateway-stripe' ), $response->id ) );

			return true;
		}
	}

	/**
	 * Don't transfer Stripe customer/token meta when creating a parent renewal order.
	 *
	 * @access public
	 * @param array $order_meta_query MySQL query for pulling the metadata
	 * @param int $original_order_id Post ID of the order being used to purchased the subscription being renewed
	 * @param int $renewal_order_id Post ID of the order created for renewing the subscription
	 * @param string $new_order_role The role the renewal order is taking, one of 'parent' or 'child'
	 * @return void
	 */
	public function remove_renewal_order_meta( $order_meta_query, $original_order_id, $renewal_order_id, $new_order_role ) {
		if ( 'parent' == $new_order_role ) {
			$order_meta_query .= " AND `meta_key` NOT LIKE '_stripe_customer_id' "
							  .  " AND `meta_key` NOT LIKE '_stripe_token' ";
		}
		return $order_meta_query;
	}

	/**
	 * Update the customer_id for a subscription after using Stripe to complete a payment to make up for
	 * an automatic renewal payment which previously failed.
	 *
	 * @access public
	 * @param WC_Order $original_order The original order in which the subscription was purchased.
	 * @param WC_Order $renewal_order The order which recorded the successful payment (to make up for the failed automatic payment).
	 * @param string $subscription_key A subscription key of the form created by @see WC_Subscriptions_Manager::get_subscription_key()
	 * @return void
	 */
	function update_failing_payment_method( $original_order, $renewal_order, $subscription_key ) {		
		$new_customer_id = get_post_meta( $renewal_order->id, '_stripe_customer_id', true );
		update_post_meta( $original_order->id, '_stripe_customer_id', $new_customer_id );
	}

	/**
	 * Render the payment method used for a subscription in the "My Subscriptions" table
	 *
	 * @since 1.7.5
	 * @param string $payment_method_to_display the default payment method text to display
	 * @param array $subscription_details the subscription details
	 * @param WC_Order $order the order containing the subscription
	 * @return string the subscription payment method
	 */
	public function maybe_render_subscription_payment_method( $payment_method_to_display, $subscription_details, WC_Order $order ) {

		// bail for other payment methods
		if ( $this->id !== $order->recurring_payment_method || ! $order->customer_user ) {
			return $payment_method_to_display;
		}

		$stripe_customer = get_post_meta( $order->id, '_stripe_customer_id', true );
		$customer_ids    = get_user_meta( $order->customer_user, '_stripe_customer_id', false );

		foreach ( $customer_ids as $customer_id ) {
			if ( $customer_id['customer_id'] == $stripe_customer ) {
				$payment_method_to_display = sprintf( 'Via card ending in %s', $customer_id['active_card'] );
				break;
			}
		}

		return $payment_method_to_display;
	}
}