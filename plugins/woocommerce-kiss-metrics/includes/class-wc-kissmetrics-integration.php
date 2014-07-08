<?php
/**
* WooCommerce KISSmetrics
*
* This source file is subject to the GNU General Public License v3.0
* that is bundled with this package in the file license.txt.
* It is also available through the world-wide-web at this URL:
* http://www.gnu.org/licenses/gpl-3.0.html
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@skyverge.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade WooCommerce KISSmetrics to newer
* versions in the future. If you wish to customize WooCommerce KISSmetrics for your
* needs please refer to http://docs.woothemes.com/document/kiss-metrics/ for more information.
*
* @package     WC-KISSmetrics/Classes
* @author      SkyVerge
* @copyright   Copyright (c) 2013-2014, SkyVerge, Inc.
* @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
* KISSmetrics Integration class
*
* Handles settings and tracking functionality
*
* @since 1.0
* @extends \WC_Integration
*/
class WC_KISSMetrics_Integration extends WC_Integration {


	/** @var string KM API Key */
	public $api_key;

	/** @var string how to identify visitors, either WP username or email */
	public $identity_pref;

	/** @var array of event names */
	public $event_name = array();

	/** @var array of property names */
	public $property_name = array();

	/** @var \WC_KISSmetrics_API instance */
	protected $api;

	/** @var array API options */
	protected $api_options;


	/**
	 * load settings and setup hooks
	 *
	 * @since 1.0
	 * @return \WC_KISSMetrics_Integration
	 */
	public function __construct() {

		// Setup plugin
		$this->id                 = 'kissmetrics';
		$this->method_title       = __( 'KISS Metrics', WC_KISSmetrics::TEXT_DOMAIN );
		$this->method_description = __( 'Web analytics tool that tracks visitors to your site as people, not pageviews. Visualize your online sales funnels and find out which ones are driving revenue and which are not.', WC_KISSmetrics::TEXT_DOMAIN );

		// Load admin form
		$this->init_form_fields();

		// Load settings
		$this->init_settings();

		// Set API Key / Identity Preference
		$this->api_key       = $this->settings['api_key'];
		$this->identity_pref = $this->settings['identity_pref'];

		// Load event / property names
		foreach ( $this->settings as $key => $value ) {

			if ( strpos( $key, 'event_name' ) !== false ) {

				// event name setting, remove '_event_name' and use as key
				$key = str_replace( '_event_name', '', $key );
				$this->event_name[ $key ] = $value;

			} elseif ( strpos( $key, 'property_name' ) !== false ) {

				// property name setting, remove '_property_name' and use as key
				$key = str_replace( '_property_name', '', $key );
				$this->property_name[ $key ] = $value;
			}
		}

		// Setup API options
		$this->api_options = array();

		// Logging Preference
		switch ( $this->settings['logging'] ) {

			case 'queries':
				$this->api_options = array_merge( $this->api_options, array( 'log_queries' => true ) );
				break;

			case 'errors':
				$this->api_options = array_merge( $this->api_options, array( 'log_errors' => true ) );
				break;

			case 'queries_and_errors':
				$this->api_options = array_merge( $this->api_options, array( 'log_queries' => true, 'log_errors' => true ) );
				break;

			default:
				break;
		}

		// Add hooks to record events - only add hook if event name is populated

		// Header Javascript Code, only add is API key is populated
		if ( $this->api_key ) {
			add_action( 'wp_head',    array( $this, 'output_head' ) );
			add_action( 'login_head', array( $this, 'output_head' ) );
		}

		// Signed in
		if ( $this->event_name['signed_in'] ) {
			add_action( 'wp_login', array( $this, 'signed_in' ), 10, 2 );
		}

		// Signed out
		if ( $this->event_name['signed_out'] ) {
			add_action( 'wp_logout', array( $this, 'signed_out' ) );
		}

		// Viewed Signup page (on my account page, if enabled)
		if ( $this->event_name['viewed_signup'] ) {
			add_action( 'register_form', array( $this, 'viewed_signup' ) );
		}

		// Signed up for new account (on my account page if enabled OR during checkout)
		if ( $this->event_name['signed_up'] ) {
			add_action( 'user_register', array( $this, 'signed_up' ) );
		}

		// Viewed Product (Properties: Name)
		if ( $this->event_name['viewed_product'] ) {
			add_action( 'woocommerce_after_single_product', array( $this, 'viewed_product' ) );
		}

		// Added Product to Cart (Properties: Product Name, Quantity)
		if ( $this->event_name['added_to_cart'] ) {
			// single product add to cart button
			add_action( 'woocommerce_add_to_cart', array( $this, 'added_to_cart' ), 10, 6 );
			// AJAX add to cart
			add_action( 'woocommerce_ajax_added_to_cart', array( $this, 'ajax_added_to_cart' ) );
		}

		// Removed Product from Cart (Properties: Product Name)
		if ( $this->event_name['removed_from_cart'] ) {
			add_action( 'woocommerce_before_cart_item_quantity_zero', array( $this, 'removed_from_cart' ) );
		}

		// Changed Quantity of Product in Cart (Properties: Product Name, Quantity )
		if ( $this->event_name['changed_cart_quantity'] ) {
			add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'changed_cart_quantity' ), 10, 2 );
		}

		// Viewed Cart
		if ( $this->event_name['viewed_cart'] ) {
			add_action( 'woocommerce_after_cart_contents', array( $this, 'viewed_cart' ) );
			add_action( 'woocommerce_cart_is_empty', array( $this, 'viewed_cart' ) );
		}

		// Started Checkout
		if ( $this->event_name['started_checkout'] ) {
			add_action( 'woocommerce_after_checkout_form', array( $this, 'started_checkout' ) );
		}

		// Started Payment (for gateways that direct post from payment page, eg: Braintree TR, Authorize.net AIM, etc
		if ( $this->event_name['started_payment'] ) {
			add_action( 'after_woocommerce_pay', array( $this, 'started_payment' ) );
		}

		// Completed Purchase
		if ( $this->event_name['completed_purchase'] ) {
			add_action( 'woocommerce_thankyou', array( $this, 'completed_purchase' ) );
		}

		// Wrote Review or Commented (Properties: Product Name if review, Post Title if blog post)
		if ( $this->event_name['wrote_review'] || $this->event_name['commented'] ) {
			add_action( 'comment_post', array( $this, 'wrote_review_or_commented' ) );
		}

		// Viewed Account
		if ( $this->event_name['viewed_account'] ) {
			add_action( 'woocommerce_after_my_account', array( $this, 'viewed_account' ) );
		}

		// Viewed Order
		if ( $this->event_name['viewed_order'] ) {
			add_action( 'woocommerce_view_order', array( $this, 'viewed_order' ) );
		}

		// Updated Address
		if ( $this->event_name['updated_address'] ) {
			add_action( 'woocommerce_customer_save_address', array( $this, 'updated_address' ) );
		}

		// Changed Password
		if ( $this->event_name['changed_password'] ) {
			add_action( 'woocommerce_customer_change_password', array( $this, 'changed_password' ) );
		}

		// Applied Coupon
		if ( $this->event_name['applied_coupon'] ) {
			add_action( 'woocommerce_applied_coupon', array( $this, 'applied_coupon' ) );
		}

		// Tracked Order
		if ( $this->event_name['tracked_order'] ) {
			add_action( 'woocommerce_track_order', array( $this, 'tracked_order' ) );
		}

		// Estimated Shipping
		if ( $this->event_name['estimated_shipping'] ) {
			add_action( 'woocommerce_calculated_shipping', array( $this, 'estimated_shipping' ) );
		}

		// Cancelled Order
		if ( $this->event_name['cancelled_order'] ) {
			add_action( 'woocommerce_cancelled_order', array( $this, 'cancelled_order' ) );
		}

		// Reordered Previous Order
		if ( $this->event_name['reordered'] ) {
			add_action( 'woocommerce_ordered_again', array( $this, 'reordered' ) );
		}

		// WooCommerce Subscription support, since 1.1

		if ( $this->is_subscriptions_active() ) {

			// Subscription Activation
			if ( ! empty( $this->event_name['activated_subscription'] ) ) {
				add_action( 'subscriptions_activated_for_order', array( $this, 'activated_subscription' ) );
			}

			// Free Trial End
			if ( ! empty( $this->event_name['subscription_trial_ended'] ) ) {
				add_action( 'subscription_trial_end', array( $this, 'subscription_trial_ended' ), 10, 2 );
			}

			// Subscription Expiration
			if ( ! empty( $this->event_name['subscription_expired'] ) ) {
				add_action( 'subscription_expired', array( $this, 'subscription_expired' ), 10, 2 );
			}

			// Subscription Suspension
			if ( ! empty( $this->event_name['suspended_subscription'] ) ) {
				add_action( 'subscription_put_on-hold', array( $this, 'suspended_subscription' ), 10, 2 );
			}

			// Subscription Reactivation
			if ( ! empty( $this->event_name['reactivated_subscription'] ) ) {
				add_action( 'reactivated_subscription', array( $this, 'reactivated_subscription' ), 10, 2 );
			}

			// Subscription Cancellation
			if ( ! empty( $this->event_name['cancelled_subscription'] ) ) {
				add_action( 'cancelled_subscription', array( $this, 'cancelled_subscription' ), 10, 2 );
			}

			// Subscription Renewed
			if ( ! empty( $this->event_name['renewed_subscription'] ) ) {
				add_action( 'woocommerce_subscriptions_renewal_order_created', array( $this, 'renewed_subscription' ), 10, 4 );
			}
		}

		// Save admin options
		if ( is_admin() ) {
			add_action( 'woocommerce_update_options_integration_kissmetrics', array( $this, 'process_admin_options' ) );
		}
	}


	/**
	 * Track login event
	 *
	 * @since 1.0
	 * @param string $user_login
	 * @param object $user WP_User instance
	 */
	public function signed_in( $user_login, $user ) {

		if ( in_array( $user->roles[0], apply_filters( 'wc_kissmetrics_signed_in_user_roles', array( 'subscriber', 'customer' ) ) ) ) {
			$this->api_record_event( $this->event_name['signed_in'], array(), $this->get_identity( $user ) );
		}
	}


	/**
	 * Track sign out
	 *
	 * @since 1.0
	 */
	public function signed_out() {

		$this->api_record_event( $this->event_name['signed_out'] );

	}


	/**
	 * Track sign up
	 *
	 * @since 1.0
	 */
	public function signed_up() {
		$this->api_record_event( $this->event_name['signed_up'] );
	}


	/**
	 * Track sign up view
	 *
	 * @since 1.0
	 */
	public function viewed_signup() {
		if ( $this->not_page_reload() ) {
			$this->js_record_event( $this->event_name['viewed_signup'] );
		}
	}


	/**
	 * Track product view
	 *
	 * @since 1.0
	 */
	public function viewed_product() {
		if ( $this->not_page_reload() ) {
			$this->js_record_event( $this->event_name['viewed_product'], array( $this->property_name['product_name'] => get_the_title() ) );
		}
	}


	/**
	 * Track add to cart
	 *
	 * @since 1.0
	 * @param string $cart_item_key
	 * @param int $product_id
	 * @param int $quantity
	 * @param int $variation_id
	 * @param array $variation
	 * @param array $cart_item_data
	 */
	public function added_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {

		// don't track add to cart from AJAX POST here
		if ( isset( $_POST['action'] ) ) {
			return;
		}

		$product = get_product( $product_id );

		$properties = array(
			$this->property_name['product_name'] => htmlentities( $product->get_title(), ENT_QUOTES, 'UTF-8' ),
			$this->property_name['quantity']     => $quantity,
			$this->property_name['category']     => strip_tags( $product->get_categories() )
		);

		if ( ! empty( $variation ) ) {
			// Added a variable product to cart, set attributes as properties
			// Remove 'pa_' from keys to keep property names consistent
			// note: `pa_` was replaced with `attribute_` in 2.1, so `pa_` can be removed once 2.1+ is required
			$variation = array_flip( str_replace( array( 'pa_', 'attribute_' ), '', array_flip( $variation ) ) );

			$properties = array_merge( $properties, $variation );
		}

		$this->api_record_event( $this->event_name['added_to_cart'], $properties );
	}


	/**
	 * Track AJAX add to cart
	 *
	 * @since 1.0
	 * @param int $product_id
	 */
	public function ajax_added_to_cart( $product_id ) {

		$product = get_product( $product_id );

		$properties = array(
			$this->property_name['product_name'] => htmlentities( $product->get_title(), ENT_QUOTES, 'UTF-8' ),
			$this->property_name['quantity']     => 1,
			$this->property_name['category']     => trim( strip_tags( $product->get_categories() ) )
		);

		$this->api_record_event( $this->event_name['added_to_cart'], $properties );
	}


	/**
	 * Track remove from cart
	 *
	 * @since 1.0
	 * @param string $cart_item_key
	 */
	public function removed_from_cart( $cart_item_key ) {

		if ( isset( SV_WC_Plugin_Compatibility::WC()->cart->cart_contents[ $cart_item_key ] ) ) {

			$item = SV_WC_Plugin_Compatibility::WC()->cart->cart_contents[ $cart_item_key ];

			$this->api_record_event( $this->event_name['removed_from_cart'], array( 'product name' => get_the_title( $item['product_id'] ) ) );
		}
	}


	/**
	 * Track quantity change in cart
	 *
	 * @since 1.0
	 * @param string $cart_item_key
	 * @param int $quantity
	 */
	public function changed_cart_quantity( $cart_item_key, $quantity ) {;

		if ( isset( SV_WC_Plugin_Compatibility::WC()->cart->cart_contents[ $cart_item_key ] ) ) {

			$item = SV_WC_Plugin_Compatibility::WC()->cart->cart_contents[ $cart_item_key ];

			$this->api_record_event( $this->event_name['changed_cart_quantity'], array( $this->property_name['product_name'] => get_the_title( $item['product_id'] ), 'quantity' => $quantity ) );
		}
	}


	/**
	 * Track cart view
	 *
	 * @since 1.0
	 */
	public function viewed_cart() {

		if ( $this->not_page_reload() ) {
			$this->js_record_event( $this->event_name['viewed_cart'] );
		}
	}


	/**
	 * Track checkout start
	 *
	 * @since 1.0
	 */
	public function started_checkout() {

		if ( $this->not_page_reload() ) {
			$this->js_record_event( $this->event_name['started_checkout'] );
		}
	}

	/**
	 * Track payment start
	 *
	 * @since 1.0
	 */
	public function started_payment() {

		if ( $this->not_page_reload() ) {
			$this->js_record_event( $this->event_name['started_payment'] );
		}
	}


	/**
	 * Track commenting (either post or product review)
	 *
	 * @since 1.0
	 */
	public function wrote_review_or_commented() {

		$type = get_post_type();

		if ( $type == 'product' ) {
			if ( $this->event_name['wrote_review'] ) {
				$this->api_record_event( $this->event_name['wrote_review'], array( $this->property_name['product_name'] => get_the_title() ) );
			}
		} elseif ( $type == 'post' ) {
			if ( $this->event_name['commented'] ) {
				$this->api_record_event( $this->event_name['commented'], array( $this->property_name['post_title'] => get_the_title() ) );
			}
		}
	}


	/**
	 * Track completed purchase
	 *
	 * @since 1.0
	 * @param int $order_id
	 */
	public function completed_purchase( $order_id ) {

		if ( metadata_exists( 'post', $order_id, '_wc_kiss_metrics_tracked', 1 ) ) {
			return;
		}

		$order = new WC_Order( $order_id );

		$properties = apply_filters( 'wc_kissmetrics_completed_purchase_properties',
			array(
				'order_id'       => $order_id,
				'order_total'    => $order->get_total(),
				'shipping_total' => SV_WC_Plugin_Compatibility::get_total_shipping( $order ),
				'total_quantity' => $order->get_item_count(),
				'payment_method' => $order->payment_method_title
			), $order, $this
		);

		// record purchase
		$this->js_record_event( $this->event_name['completed_purchase'], array(
				$this->property_name['order_id']       => $properties['order_id'],
				$this->property_name['order_total']    => $properties['order_total'],
				$this->property_name['shipping_total'] => $properties['shipping_total'],
				$this->property_name['total_quantity'] => $properties['total_quantity'],
				$this->property_name['payment_method'] => $properties['payment_method'],
			)
		);

		// get logged in user to include in properties, otherwise identify guest user by their billing email
		if ( is_user_logged_in() ) {
			$user = get_user_by( 'id', $order->user_id );
		} else {
			SV_WC_Plugin_Compatibility::wc_enqueue_js( sprintf( "_kmq.push(['identify', '%s']);", $order->billing_email ) );
		}

		$properties = apply_filters( 'wc_kissmetrics_completed_purchase_user_properties', array(
			'created'    => date( 'r' ),
			'email'      => $order->billing_email,
			'first name' => $order->billing_first_name,
			'last name'  => $order->billing_last_name,
		), $order, $this );

		if ( isset( $user->user_login ) ) {
			$properties['username'] = $user->user_login;
		}

		// set properties on user
		$this->js_set_properties( $properties );

		// mark order as tracked
		update_post_meta( $order->id, '_wc_kiss_metrics_tracked', 1 );
	}


	/**
	 * Track account view
	 *
	 * @since 1.0
	 */
	public function viewed_account() {

		$this->js_record_event( $this->event_name['viewed_account'] );
	}


	/**
	 * Track order view
	 *
	 * @since 1.0
	 */
	public function viewed_order() {

		$this->api_record_event( $this->event_name['viewed_order'] );
	}


	/**
	 * Track address update
	 *
	 * @since 1.0
	 */
	public function updated_address() {

		$this->api_record_event( $this->event_name['updated_address'] );
	}


	/**
	 * Track password change
	 *
	 * @since  1.0
	 */
	public function changed_password() {

		$this->api_record_event( $this->event_name['changed_password'] );
	}


	/**
	 * Track successful coupon apply
	 *
	 * @since 1.0
	 * @param string $coupon_code
	 */
	public function applied_coupon( $coupon_code ) {

		$this->api_record_event( $this->event_name['applied_coupon'], array( $this->property_name['coupon_code'] => $coupon_code ) );
	}


	/**
	 * Track order track
	 *
	 * @since 1.0
	 * @param int $order_id
	 */
	public function tracked_order( $order_id ) {

		$this->api_record_event( $this->event_name['tracked_order'], array( $this->property_name['order_id'] => $order_id ) );
	}


	/**
	 * Track shipping estimate on cart page
	 *
	 * @since 1.0
	 */
	public function estimated_shipping() {

		$this->api_record_event( $this->event_name['estimated_shipping'] );
	}


	/**
	 * Track order cancel from My Account area
	 *
	 * @since 1.0
	 * @param int $order_id
	 */
	public function cancelled_order( $order_id ) {

		$this->api_record_event( $this->event_name['cancelled_order'], array( $this->property_name['order_id'] => $order_id ) );
	}


	/**
	 * Track re-order from My Account area
	 *
	 * @since 1.0
	 * @param int $order_id
	 */
	public function reordered( $order_id ) {

		$this->api_record_event( $this->event_name['reordered'], array( $this->property_name['order_id'] => $order_id ) );
	}


	// WooCommerce Subscriptions support, since 1.1


	/**
	 * Track subscription activations (only after successful payment for subscription)
	 *
	 * @since 1.1
	 * @param \WC_Order $order
	 */
	public function activated_subscription( $order ) {

		// set properties
		$properties = apply_filters( 'wc_kissmetrics_activated_subscription_properties',
			array(
				'subscription_name'         => WC_Subscriptions_Order::get_item_name( $order ),
				'total_initial_payment'     => WC_Subscriptions_Order::get_total_initial_payment( $order ),
				'initial_sign_up_fee'       => WC_Subscriptions_Order::get_sign_up_fee( $order ),
				'subscription_period'       => WC_Subscriptions_Order::get_subscription_period( $order ),
				'subscription_interval'     => WC_Subscriptions_Order::get_subscription_interval( $order ),
				'subscription_length'       => WC_Subscriptions_Order::get_subscription_length( $order ),
				'subscription_trial_period' => WC_Subscriptions_Order::get_subscription_trial_period( $order ),
				'subscription_trial_length' => WC_Subscriptions_Order::get_subscription_trial_length( $order )
			), $order, $this
		);

		// record event
		$this->api_record_event( $this->event_name['activated_subscription'],
			array(
				$this->property_name['subscription_name']         => $properties['subscription_name'],
				$this->property_name['total_initial_payment']     => $properties['total_initial_payment'],
				$this->property_name['initial_sign_up_fee']       => $properties['initial_sign_up_fee'],
				$this->property_name['subscription_period']       => $properties['subscription_period'],
				$this->property_name['subscription_interval']     => $properties['subscription_period'],
				$this->property_name['subscription_length']       => $properties['subscription_interval'],
				$this->property_name['subscription_trial_period'] => $properties['subscription_trial_period'],
				$this->property_name['subscription_trial_length'] => $properties['subscription_trial_length'],
			),
			$this->get_identity( $order->user_id )
		);

	}


	/**
	 * Track subscription trial end
	 *
	 * @since  1.1
	 * @param int $user_id
	 * @param string $subscription_key
	 */
	public function subscription_trial_ended( $user_id, $subscription_key ) {

		$subscription = WC_Subscriptions_Manager::get_users_subscription( $user_id, $subscription_key );

		// bail if order id isn't available
		if( ! isset( $subscription['order_id'] ) ) {
			return;
		}

		// Set properties
		$properties = array(
			$this->property_name['subscription_name'] => WC_Subscriptions_Order::get_item_name( $subscription['order_id'] )
		);

		$this->api_record_event( $this->event_name['subscription_trial_ended'], $properties, $this->get_identity( $user_id ) );

		// extra event for handling trial conversions to paying customers, check if subscription is active when the trial ends
		// and assume the trial converted since the customer didn't cancel
		if ( isset( $subscription['status'] ) && 'active' === $subscription['status'] ) {
			$this->api_record_event( 'subscription trial converted', $properties, $this->get_identity( $user_id ) );
		} else {
			$this->api_record_event( 'subscription trial cancelled', $properties, $this->get_identity( $user_id ) );
		}
	}


	/**
	 * Track subscription expiration
	 *
	 * @since 1.1
	 * @param int $user_id
	 * @param string $subscription_key
	 */
	public function subscription_expired( $user_id, $subscription_key ) {

		$subscription = WC_Subscriptions_Manager::get_users_subscription( $user_id, $subscription_key );

		// bail if order id isn't available
		if( ! isset( $subscription['order_id'] ) ) {
			return;
		}

		// Set properties
		$properties = array(
			$this->property_name['subscription_name'] => WC_Subscriptions_Order::get_item_name( $subscription['order_id'] )
		);

		$this->api_record_event( $this->event_name['subscription_expired'], $properties, $this->get_identity( $user_id ) );
	}


	/**
	 * Track subscription suspension
	 *
	 * @since 1.1
	 * @param int $user_id
	 * @param string $subscription_key
	 */
	public function suspended_subscription( $user_id, $subscription_key ) {

		$subscription = WC_Subscriptions_Manager::get_users_subscription( $user_id, $subscription_key );

		// bail if order id isn't available
		if( ! isset( $subscription['order_id'] ) ) {
			return;
		}

		// Set properties
		$properties = array(
			$this->property_name['subscription_name'] => WC_Subscriptions_Order::get_item_name( $subscription['order_id'] )
		);

		$this->api_record_event( $this->event_name['suspended_subscription'], $properties, $this->get_identity( $user_id ) );
	}


	/**
	 * Track subscription reactivation
	 *
	 * @since 1.1
	 * @param int $user_id
	 * @param string $subscription_key
	 */
	public function reactivated_subscription( $user_id, $subscription_key ) {

		$subscription = WC_Subscriptions_Manager::get_users_subscription( $user_id, $subscription_key );

		// bail if order id isn't available
		if( ! isset( $subscription['order_id'] ) ) {
			return;
		}

		// Set properties
		$properties = array(
			$this->property_name['subscription_name'] => WC_Subscriptions_Order::get_item_name( $subscription['order_id'] )
		);

		$this->api_record_event( $this->event_name['reactivated_subscription'], $properties, $this->get_identity( $user_id ) );
	}


	/**
	 * Track subscription cancellation
	 *
	 * @since 1.1
	 * @param int $user_id
	 * @param string $subscription_key
	 */
	public function cancelled_subscription( $user_id, $subscription_key ) {

		$subscription = WC_Subscriptions_Manager::get_users_subscription( $user_id, $subscription_key );

		// bail if order id isn't available
		if( ! isset( $subscription['order_id'] ) ) {
			return;
		}

		// Set properties
		$properties = array(
			$this->property_name['subscription_name'] => WC_Subscriptions_Order::get_item_name( $subscription['order_id'] )
		);

		$this->api_record_event( $this->event_name['cancelled_subscription'], $properties, $this->get_identity( $user_id ) );
	}


	/**
	 * Track renewal order generated from active subscription (either automatically or manually from customer payment)
	 *
	 * @since 1.1
	 * @param \WC_Order $renewal_order
	 * @param \WC_Order $original_order
	 * @param int $product_id
	 * @param string $new_order_role
	 */
	public function renewed_subscription( $renewal_order, $original_order, $product_id, $new_order_role ) {

		// set properties
		$properties = array(
			$this->property_name['billing_amount']      => $renewal_order->get_total(),
			$this->property_name['billing_description'] => WC_Subscriptions_Order::get_item_name( $renewal_order, $product_id ),

		);

		$this->api_record_event( $this->event_name['renewed_subscription'], $properties, $this->get_identity( $renewal_order->user_id ) );
	}


	/**
	 * Track custom event
	 *
	 * Contains excess checks to account for any kind of user input
	 *
	 * @since 1.0
	 * @param bool $event_name
	 * @param bool $properties
	 */
	public function custom_event( $event_name = false, $properties = false ) {

		if ( isset( $event_name ) && $event_name != '' && strlen( $event_name ) > 0 ) {

			//Sanitize property names and values
			$prop_array = false;
			$props      = false;
			if ( isset( $properties ) && is_array( $properties ) && count( $properties ) > 0 ) {
				foreach ( $properties as $k => $v ) {
					$key   = $this->sanitize_event_string( $k );
					$value = $this->sanitize_event_string( $v );
					if ( $key && $value ) {
						$prop_array[$key] = $value;
					}
				}

				$props = false;
				if ( $prop_array && is_array( $prop_array ) && count( $prop_array ) > 0 ) {
					$props = $prop_array;
				}
			}

			//Sanitize event name
			$event = $this->sanitize_event_string( $event_name );

			//If everything checks out then trigger event
			if ( $event ) {
				$this->js_record_event( $event, $props );
			}
		}
	}


	/**
	 * Sanitize string for custom events
	 *
	 * Contains excess checks to account for any kind of user input
	 *
	 * @since 1.0
	 * @param bool $str
	 * @return string|bool
	 */
	private function sanitize_event_string( $str = false ) {

		if ( isset( $str ) ) {
			//Remove excess spaces
			$str = trim( $str );

			//Remove characters that could break JSON or JS trigger
			$str = str_replace( array( '\'', '"', ',', ';', ':', '.', '{', '}' ), '', $str );

			//URL encode for safety
			$str = urlencode( $str );

			return $str;
		}

		return false;
	}


	/**
	 * Output tracking javascript in <head>
	 *
	 * @since 1.0
	 */
	public function output_head() {
		// Verify tracking status
		if ( $this->disable_tracking() ) return;

		// no indentation on purpose
		?>
<!-- Start WooCommerce KISSmetrics -->
<script type="text/javascript">
	var _kmq = _kmq || [];
	function _kms(u) {
		setTimeout(function () {
			var s = document.createElement('script');
			var f = document.getElementsByTagName('script')[0];
			s.type = 'text/javascript';
			s.async = true;
			s.src = u;
			f.parentNode.insertBefore(s, f);
		}, 1);
	}
	_kms('//i.kissmetrics.com/i.js');
	_kms('//doug1izaerwt3.cloudfront.net/<?php echo $this->api_key; ?>.1.js');
	_kmq.push(['identify', '<?php echo $this->get_identity(); ?>']);
	<?php if ( is_front_page() && $this->event_name['viewed_homepage'] ) echo "_kmq.push(['record', '" . $this->event_name['viewed_homepage'] . "' ]);\n"; ?>
</script>
<!-- end WooCommerce KISSmetrics -->
		<?php
	}


	/**
	 * Output event tracking javascript
	 *
	 * @param string $event_name Name of Event to be set
	 * @param array|string $properties Properties to be set with event.
	 * @since 1.0
	 */
	private function js_record_event( $event_name, $properties = '' ) {

		// Verify tracking status
		if ( $this->disable_tracking() ) {
			return;
		}

		// json encode properties if they exist
		if ( is_array( $properties ) ) {

			// remove blank properties
			if( isset( $properties[''] ) )
				unset( $properties[''] );

			$properties = ", " . json_encode( $properties );
		}

		echo '<script type="text/javascript">' . "_kmq.push(['record', '{$event_name}'{$properties}]);" . "</script>";
	}


	/**
	 * Output user property setting javascript
	 *
	 * @param array|string $properties Properties to be set with event.
	 * @since 1.0
	 */
	private function js_set_properties( $properties = array() ) {

		// Verify tracking status
		if ( $this->disable_tracking() ) {
			return;
		}

		// remove blank properties
		if( isset( $properties[''] ) ) {
			unset( $properties[''] );
		}

		$properties = json_encode( $properties );

		echo '<script type="text/javascript">' . "_kmq.push(['set', {$properties}]);" . "</script>";
	}


	/**
	 * Record event via HTTP API
	 *
	 * @since 1.0
	 * @param string $event_name Name of Event to be set
	 * @param array $properties Properties to be set with event.
	 * @param string $identity KM identity for visitor
	 */
	private function api_record_event( $event_name, $properties = array(), $identity = null ) {

		// Verify tracking status
		if ( $this->disable_tracking() ) {
			return;
		}

		// identify user first
		$this->set_named_identity( $identity );

		// remove blank properties
		if( isset( $properties[''] ) ) {
			unset( $properties[''] );
		}

		// record the event
		$this->get_api()->record( $event_name, $properties );
	}


	/**
	 * Set properties for user via HTTP API
	 *
	 * @since 1.0
	 * @param array $properties Properties to be set on user
	 */
	private function api_set_properties( $properties ) {

		// Verify tracking status
		if ( $this->disable_tracking() ) {
			return;
		}

		// identify user first
		$this->set_named_identity( null );

		// remove blank properties
		if( isset( $properties[''] ) ) {
			unset( $properties[''] );
		}

		// record the properties
		$this->get_api()->set( $properties );
	}


	/**
	 * Lazy load the API object
	 *
	 * @since 1.1.1
	 */
	private function get_api() {

		if ( is_object( $this->api ) ) {
			return $this->api;
		}

		// Load KISS Metrics API wrapper class
		require_once( $GLOBALS['wc_kissmetrics']->get_plugin_path() . '/includes/class-wc-kissmetrics-api.php' );

		// Init KM API
		return $this->api = new WC_KISSmetrics_API( $this->api_key, $this->api_options );
	}


	/**
	 * Verify that tracking cookie is set and get preferred identity
	 * When logging events via API, prefer named identity first, then anonymous
	 *
	 * @since 1.0
	 * @param string $identity KM identity for visitor
	 */
	private function set_named_identity( $identity ) {

		if ( isset( $identity ) ) {

			// Use passed identity
			$this->get_api()->identify( $identity );

		} elseif ( isset ( $_COOKIE['km_ni'] ) ) {

			// Use named identity
			$this->get_api()->identify( $_COOKIE['km_ni'] );

		} elseif ( isset ( $_COOKIE['km_ai'] ) ) {

			// Use anonymous identity
			$this->get_api()->identify( $_COOKIE['km_ai'] );
		} else {
			// Neither cookie set and named identity not passed, don't track request and log error
			// Cookies are probably disabled for visitor
			if ( $this->logging == 'errors' || $this->logging == 'queries_and_errors' ) {
				$GLOBALS['wc_kissmetrics']->log( "No identity found! Cannot send event via API" );
			}
		}
	}


	/**
	 * Disable tracking if admin, privileged user, or API key is blank
	 *
	 * @since 1.0
	 * @return bool true if tracking should be disabled, otherwise false
	 */
	private function disable_tracking() {

		// don't disable tracking on AJAX requests
		if ( is_admin() && defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return false;
		}

		// disable tracking if admin, shop manager, or API key is blank
		if ( is_admin() || current_user_can( 'manage_options' ) || ( ! $this->api_key ) ) {
			return true;
		} else {
			return false;
		}
	}


	/**
	 * Get named identity of user
	 *
	 * @since 1.0
	 * @param mixed $user
	 * @return string|null visitor email or username
	 */
	private function get_identity( $user = null ) {

		// WP_User or user_id
		if ( isset ( $user ) ) {

			// instantiate new user if not WP_User object
			if ( ! is_object( $user ) ) {
				$user = new WP_User( $user );
			}

			return ( $this->identity_pref == 'email' ? $user->user_email : $user->user_login );
		}

		// user is logged in
		if ( is_user_logged_in() ) {

			$user = get_user_by( 'id', get_current_user_id() );
			return ( $this->identity_pref == 'email' ? $user->user_email : $user->user_login );

		} else {

			//nothing to identify on
			return 'null';
		}
	}


	/**
	 * Checks HTTP referer to see if request was a page reload
	 * Prevents duplication of tracking events when user reloads page or submits a form
	 * e.g applying a coupon on the cart page
	 *
	 * @since 1.0
	 * @return bool true if not a page reload, false if page reload
	 */
	private function not_page_reload() {

		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {

			// return portion before query string
			$request_uri = str_replace( strstr( $_SERVER['REQUEST_URI'], '?' ), '', $_SERVER['REQUEST_URI'] );

			if ( stripos( $_SERVER['HTTP_REFERER'], $request_uri ) === false )
				return true;
		}

		return true;
	}

	/** Admin Methods **********************************************/

	/**
	 * Initializes form fields in the format required by WC_Integration
	 *
	 * @since 1.0
	 */
	public function init_form_fields() {

		$this->form_fields = array(

			'api_settings_section' => array(
				'title'       => __( 'API Settings', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Enter your API key to start tracking.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'section',
				'default'     => ''
			),

			'api_key' => array(
				'title'       => __( 'API Key', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Log into your account and go to Site Settings. Leave blank to disable tracking.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => ''
			),

			'identity_pref' => array(
				'title'       => __( 'Identity Preference', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Select how to identify logged in users.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'select',
				'default'     => '',
				'options'     => array(
					'email'    => __( 'Email Address', WC_KISSmetrics::TEXT_DOMAIN ),
					'username' => __( 'Wordpress Username', WC_KISSmetrics::TEXT_DOMAIN )
				)
			),

			'logging' => array(
				'title'       => __( 'Logging', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Select whether to log nothing, queries, errors, or both queries and errors to the WooCommerce log. Careful, this can fill up log files very quickly on a busy site.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'select',
				'default'     => '',
				'options'     => array(
					'off'                => __( 'Off', WC_KISSmetrics::TEXT_DOMAIN ),
					'queries'            => __( 'Queries', WC_KISSmetrics::TEXT_DOMAIN ),
					'errors'             => __( 'Errors', WC_KISSmetrics::TEXT_DOMAIN ),
					'queries_and_errors' => __( 'Queries & Errors', WC_KISSmetrics::TEXT_DOMAIN )
				)
			),

			'event_names_section' => array(
				'title'       => __( 'Event Names', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Customize the event names. Leave a field blank to disable tracking of that event.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'section',
				'default'     => ''
			),

			'signed_in_event_name' => array(
				'title'       => __( 'Signed In', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Triggered when a customer signs in.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'signed in'
			),

			'signed_out_event_name' => array(
				'title'       => __( 'Signed Out', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Triggered when a customer signs out.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'signed out'
			),

			'viewed_signup_event_name' => array(
				'title'       => __( 'Viewed Signup', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Triggered when a customer views the registration form.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'viewed signup'
			),

			'signed_up_event_name' => array(
				'title'       => __( 'Signed Up', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Triggered when a customer registers a new account.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'signed up'
			),

			'viewed_homepage_event_name' => array(
				'title'       => __( 'Viewed Homepage', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Triggered when a customer views the homepage.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'viewed homepage'
			),

			'viewed_product_event_name' => array(
				'title'       => __( 'Viewed Product', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Triggered when a customer views a single product', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'viewed product'
			),

			'added_to_cart_event_name' => array(
				'title'       => __( 'Added to Cart', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Triggered when a customer adds an item to the cart.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'added to cart'
			),

			'removed_from_cart_event_name' => array(
				'title'       => __( 'Removed from Cart', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Triggered when a customer removes an item from the cart.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'removed from cart'
			),

			'changed_cart_quantity_event_name' => array(
				'title'       => __( 'Changed Cart Quantity', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Triggered when a customer changes the quantity of an item in the cart.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'changed cart quantity'
			),

			'viewed_cart_event_name' => array(
				'title'       => __( 'Viewed Cart', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Triggered when a customer views the cart.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'viewed cart'
			),

			'applied_coupon_event_name' => array(
				'title'       => __( 'Applied Coupon', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Triggered when a customer applies a coupon', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'applied coupon'
			),

			'started_checkout_event_name' => array(
				'title'       => __( 'Started Checkout', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Triggered when a customer starts the checkout.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'started checkout'
			),

			'started_payment_event_name' => array(
				'title'       => __( 'Started Payment', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Triggered when a customer views the payment page (used with direct post payment gateways)', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'started payment'
			),

			'completed_purchase_event_name' => array(
				'title'       => __( 'Completed Purchase', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Triggered when a customer completes a purchase.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'completed purchase'
			),

			'wrote_review_event_name' => array(
				'title'       => __( 'Wrote Review', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Triggered when a customer writes a review.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'wrote review'
			),

			'commented_event_name' => array(
				'title'       => __( 'Commented', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Triggered when a customer write a comment.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'commented'
			),

			'viewed_account_event_name' => array(
				'title'       => __( 'Viewed Account', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Triggered when a customer views the My Account page.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'viewed account'
			),

			'viewed_order_event_name' => array(
				'title'       => __( 'Viewed Order', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Triggered when a customer views an order', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'viewed order'
			),

			'updated_address_event_name' => array(
				'title'       => __( 'Updated Address', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Triggered when a customer updates their address.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'updated address'
			),

			'changed_password_event_name' => array(
				'title'       => __( 'Changed Password', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Triggered when a customer changes their password.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'changed password'
			),

			'estimated_shipping_event_name' => array(
				'title'       => __( 'Estimated Shipping', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Triggered when a customer estimates shipping.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'estimated shipping'
			),

			'tracked_order_event_name' => array(
				'title'       => __( 'Tracked Order', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Triggered when a customer tracks an order.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'tracked order'
			),

			'cancelled_order_event_name' => array(
				'title'       => __( 'Cancelled Order', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Triggered when a customer cancels an order.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'cancelled order'
			),

			'reordered_event_name' => array(
				'title'       => __( 'Reordered', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Triggered when a customer reorders a previous order.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'reordered'
			),

			'property_names_section' => array(
				'title'       => __( 'Property Names', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Customize the property names. Leave a field blank to disable tracking of that property.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'section',
				'default'     => ''
			),

			'product_name_property_name' => array(
				'title'       => __( 'Product Name', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Tracked when a customer views a product, adds / removes / changes quantities in the cart, or writes a review.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'product name'
			),

			'quantity_property_name' => array(
				'title'       => __( 'Product Quantity', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Tracked when a customer adds a product to their cart or changes the quantity in their cart.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'quantity'
			),

			'category_property_name' => array(
				'title'       => __( 'Product Category', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Tracked when a customer adds a product to their cart.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'category'
			),

			'coupon_code_property_name' => array(
				'title'       => __( 'Coupon Code', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Tracked when a customer applies a coupon.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'coupon code'
			),

			'order_id_property_name' => array(
				'title'       => __( 'Order ID', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Tracked when a customer completes their purchase.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'order id'
			),

			'order_total_property_name' => array(
				'title'       => __( 'Order Total', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Tracked when a customer completes their purchase.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'order total'
			),

			'shipping_total_property_name' => array(
				'title'       => __( 'Shipping Total', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Tracked when a customer completes their purchase.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'shipping total'
			),

			'total_quantity_property_name' => array(
				'title'       => __( 'Total Quantity', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Tracked when a customer completes their purchase.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'total quantity'
			),

			'payment_method_property_name' => array(
				'title'       => __( 'Payment Method', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Tracked when a customer completes their purchase.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'payment method'
			),

			'post_title_property_name' => array(
				'title'       => __( 'Post Title', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Tracked when a customer leaves a comment on a post.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'post title'
			),

			'country_property_name' => array(
				'title'       => __( 'Shipping Country', WC_KISSmetrics::TEXT_DOMAIN ),
				'description' => __( 'Tracked when a customer estimates shipping.', WC_KISSmetrics::TEXT_DOMAIN ),
				'type'        => 'text',
				'default'     => 'country'
			),
		);

		// WooCommerce Subscriptions support, since 1.1

		if( $this->is_subscriptions_active() ) :

			$this->form_fields = array_merge( $this->form_fields, array(

				'subscription_event_names_section' => array(
					'title'       => __( 'Subscription Event Names', WC_KISSmetrics::TEXT_DOMAIN ),
					'description' => __( 'Customize the event names for Subscription events. Leave a field blank to disable tracking of that event.', WC_KISSmetrics::TEXT_DOMAIN ),
					'type'        => 'section',
				),

				'activated_subscription_event_name' => array(
					'title'       => __( 'Activated Subscription', WC_KISSmetrics::TEXT_DOMAIN ),
					'description' => __( 'Triggered when a customer activates their subscription.', WC_KISSmetrics::TEXT_DOMAIN ),
					'type'        => 'text',
					'default'     => 'activated subscription',
				),

				'subscription_trial_ended_event_name' => array(
					'title'       => __( 'Subscription Free Trial Ended', WC_KISSmetrics::TEXT_DOMAIN ),
					'description' => __( 'Triggered when a the free trial ends for a subscription.', WC_KISSmetrics::TEXT_DOMAIN ),
					'type'        => 'text',
					'default'     => 'subscription trial ended',
				),

				'subscription_expired_event_name' => array(
					'title'       => __( 'Subscription Expired', WC_KISSmetrics::TEXT_DOMAIN ),
					'description' => __( 'Triggered when a subscription expires.', WC_KISSmetrics::TEXT_DOMAIN ),
					'type'        => 'text',
					'default'     => 'subscription expired',
				),

				'suspended_subscription_event_name' => array(
					'title'       => __( 'Suspended Subscription', WC_KISSmetrics::TEXT_DOMAIN ),
					'description' => __( 'Triggered when a customer suspends their subscription.', WC_KISSmetrics::TEXT_DOMAIN ),
					'type'        => 'text',
					'default'     => 'suspended subscription',
				),

				'reactivated_subscription_event_name' => array(
					'title'       => __( 'Reactivated Subscription', WC_KISSmetrics::TEXT_DOMAIN ),
					'description' => __( 'Triggered when a customer reactivates their subscription.', WC_KISSmetrics::TEXT_DOMAIN ),
					'type'        => 'text',
					'default'     => 'reactivated subscription',
				),

				'cancelled_subscription_event_name' => array(
					'title'       => __( 'Cancelled Subscription', WC_KISSmetrics::TEXT_DOMAIN ),
					'description' => __( 'Triggered when a customer cancels their subscription.', WC_KISSmetrics::TEXT_DOMAIN ),
					'type'        => 'text',
					'default'     => 'cancelled subscription',
				),

				'renewed_subscription_event_name' => array(
					'title'       => __( 'Renewed Subscription', WC_KISSmetrics::TEXT_DOMAIN ),
					'description' => __( 'Triggered when a customer is automatically billed for a subscription renewal.', WC_KISSmetrics::TEXT_DOMAIN ),
					'type'        => 'text',
					'default'     => 'subscription billed',
				),

				'subscription_property_names_section' => array(
					'title'       => __( 'Subscription Property Names', WC_KISSmetrics::TEXT_DOMAIN ),
					'description' => __( 'Customize the property names for Subscription events. Leave a field blank to disable tracking of that property.', WC_KISSmetrics::TEXT_DOMAIN ),
					'type'        => 'section',
				),

				'subscription_name_property_name' => array(
					'title'       => __( 'Subscription Name', WC_KISSmetrics::TEXT_DOMAIN ),
					'description' => __( 'Tracked anytime a subscription event occurs.', WC_KISSmetrics::TEXT_DOMAIN ),
					'type'        => 'text',
					'default'     => 'subscription name'
				),

				'total_initial_payment_property_name' => array(
					'title'       => __( 'Total Initial Payment', WC_KISSmetrics::TEXT_DOMAIN ),
					'description' => __( 'Tracked for subscription activations. Includes the Recurring amount and Sign Up Fee.', WC_KISSmetrics::TEXT_DOMAIN ),
					'type'        => 'text',
					'default'     => 'subscription name'
				),

				'initial_sign_up_fee_property_name' => array(
					'title'       => __( 'Initial Sign Up Fee', WC_KISSmetrics::TEXT_DOMAIN ),
					'description' => __( 'Tracked for subscription activations. This will be zero if the subscription has no sign up fee.', WC_KISSmetrics::TEXT_DOMAIN ),
					'type'        => 'text',
					'default'     => 'initial sign up fee'
				),

				'subscription_period_property_name' => array(
					'title'       => __( 'Subscription Period', WC_KISSmetrics::TEXT_DOMAIN ),
					'description' => __( 'Tracks the period (e.g. Day, Month, Year) for subscription activations.', WC_KISSmetrics::TEXT_DOMAIN ),
					'type'        => 'text',
					'default'     => 'subscription period'
				),

				'subscription_interval_property_name' => array(
					'title'       => __( 'Subscription Interval', WC_KISSmetrics::TEXT_DOMAIN ),
					'description' => __( 'Tracks the interval (e.g. every 1st, 2nd, 3rd, etc.) for subscription activations.', WC_KISSmetrics::TEXT_DOMAIN ),
					'type'        => 'text',
					'default'     => 'subscription interval'
				),

				'subscription_length_property_name' => array(
					'title'       => __( 'Subscription Length', WC_KISSmetrics::TEXT_DOMAIN ),
					'description' => __( 'Tracks the length (e.g. infinite, 12 months, 2 years, etc.) for subscription activations.', WC_KISSmetrics::TEXT_DOMAIN ),
					'type'        => 'text',
					'default'     => 'subscription length'
				),

				'subscription_trial_period_property_name' => array(
					'title'       => __( 'Subscription Trial Period', WC_KISSmetrics::TEXT_DOMAIN ),
					'description' => __( 'Tracks the trial period (e.g. Day, Month, Year) for subscription activations with a free trial.', WC_KISSmetrics::TEXT_DOMAIN ),
					'type'        => 'text',
					'default'     => 'subscription trial period'
				),

				'subscription_trial_length_property_name' => array(
					'title'       => __( 'Subscription Trial Length', WC_KISSmetrics::TEXT_DOMAIN ),
					'description' => __( 'Tracks the trial length (e.g. 1-90 periods) for subscription activations with a free trial.', WC_KISSmetrics::TEXT_DOMAIN ),
					'type'        => 'text',
					'default'     => 'subscription trial length'
				),

				'billing_amount_property_name' => array(
					'title'       => __( 'Billing Amount for Subscription Renewal', WC_KISSmetrics::TEXT_DOMAIN ),
					'description' => __( 'Tracks the amount billed to the customer when their subscription automatically renews.', WC_KISSmetrics::TEXT_DOMAIN ),
					'type'        => 'text',
					'default'     => 'subscription billing amount'
				),

				'billing_description_property_name' => array(
					'title'       => __( 'Billing Description for Subscription Renewal', WC_KISSmetrics::TEXT_DOMAIN ),
					'description' => __( 'Tracks the name of the subscription billed to the customer when the subscription automatically renews.', WC_KISSmetrics::TEXT_DOMAIN ),
					'type'        => 'text',
					'default'     => 'subscription billing description'
				),

			) );

		endif;
	}


	/**
	 * Checks is WooCommerce Subscriptions is active
	 *
	 * @since 1.1
	 * @return bool true if WCS is active, false if not active
	 */
	public function is_subscriptions_active() {

		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}

		return in_array( 'woocommerce-subscriptions/woocommerce-subscriptions.php', $active_plugins ) || array_key_exists( 'woocommerce-subscriptions/woocommerce-subscriptions.php', $active_plugins );
	}


	/**
	 * Generate Section HTML so we can divide the settings page up into sections
	 *
	 * @since 1.0
	 * @param string $key
	 * @param string $data
	 * @return string section HTML
	 */
	public function generate_section_html( $key, $data ) {
		$html = '';

		if ( isset( $data['title'] ) && $data['title'] != '' ) $title = $data['title']; else $title = '';
		$data['class'] = ( isset( $data['class'] ) ) ? $data['class'] : '';
		$data['css']   = ( isset( $data['css'] ) ) ? $data['css'] : '';

		$html .= '<tr valign="top">' . "\n";
		$html .= '<th scope="row" colspan="2">';
		$html .= '<h3 style="margin:0;">' . $data['title'] . '</h3>';
		if ( $data['description'] ) $html .= '<p>' . $data['description'] . '</p>';
		$html .= '</th>' . "\n";
		$html .= '</tr>' . "\n";

		return $html;
	}


	/**
	 * Remove section field from $fields so it's not saved in the db
	 *
	 * @since 1.0
	 */
	public function validate_settings_fields( $_ = false ) {
		parent::validate_settings_fields();

		// remove our section 'field' so it doesn't get saved to the database
		foreach ( $this->form_fields as $k => $v ) {
			if ( isset( $v['type'] ) && $v['type'] == 'section' ) {

				unset( $this->sanitized_fields[$k] );
			}
		}
	}


} // end \WC_KISSmetrics_Integration class
