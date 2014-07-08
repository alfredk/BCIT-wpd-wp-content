<?php

/**
 * WC_Name_Your_Price_Helpers class.
 */
class WC_Name_Your_Price_Helpers {

	// the nyp product type is how the ajax add to cart functionality is disabled
	static $supported_types = array( 'simple', 'subscription', 'bundle', 'bto', 'variation', 'subscription_variation', 'nyp' );
	static $supported_variable_types = array( 'variable', 'variable-subscription' );

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 * Needed variable_product_sync() to be available to admin and front-end
	 *
	 * @since 2.0
	 */
	public static function init(){
		if ( self::is_woocommerce_2_1() )
			add_action( 'woocommerce_variable_product_sync', array( __CLASS__, 'variable_product_sync' ), 10, 2 );
	}


	/**
	 * Check is the installed version of WooCommerce is 2.1 or newer.
	 * props to Brent Shepard
	 *
	 * @return	boolean
	 * @access 	public
	 * @since 2.0
	 */
	public static function is_woocommerce_2_1() {

		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.1b' ) >= 0 ) {

			$woocommerce_is_2_1 = true;

		} else {

			$woocommerce_is_2_1 = false;

		}

		return $woocommerce_is_2_1;
	}


	/*
	 *  Get the product ID or variation ID if object is a variation
	 *
	 * @param   object $product product/variation object 
	 * @return	integer
	 * @access 	public
	 * @since 	2.0
	 */
	public static function get_id( $product ){
		if ( is_object( $product ) ){
			$product_id = isset( $product->variation_id ) ? (int) $product->variation_id : (int) $product->id;
		} else {
			$product_id = $product;
		}
		return $product_id;
	}


	/*
	 * Verify this is a Name Your Price product
	 *
	 * @param 	mixed $the_product product/variation object or product/variation ID
	 * @return 	return boolean
	 * @access 	public
	 * @since 	1.0
	 */
	public static function is_nyp( $the_product ){

		// get the product object
		if ( is_numeric( $the_product ) ) {
			$the_product = get_product( $the_product );
		}

		if ( ! $the_product )
			return FALSE;

		// the product ID
		$product_id = isset( $the_product->variation_id ) ? (int) $the_product->variation_id : (int) $the_product->id;

		if ( $the_product->is_type( self::$supported_types ) && get_post_meta( $product_id , '_nyp', true ) == 'yes' ) {
			$is_nyp = TRUE;
		} else {
			$is_nyp = FALSE;
		}

		return apply_filters ( 'woocommerce_is_nyp', $is_nyp, $product_id, $the_product );

	}


	/*
	 * Get the suggested price
	 *
	 * @param 	mixed $product_id product/variation object or product/variation ID
	 * @return 	return number or FALSE
	 * @access 	public
	 * @since 2.0
	 */
	public static function get_suggested_price( $product_id ) {

		$product_id = self::get_id( $product_id );

		$suggested = get_post_meta( $product_id , '_suggested_price', true ); 

		// filter the raw suggested price @since 1.2
		return apply_filters ( 'woocommerce_raw_suggested_price', $suggested, $product_id );

	}


	/*
	 * Get the minimum price
	 *
	 * @param int|WC_Product $product_id Either a product object or product's post ID.
	 * @return 	return string
	 * @access 	public
	 * @since 	2.0
	 */
	public static function get_minimum_price( $product_id ){

		$product_id = self::get_id( $product_id );

		$minimum = get_post_meta( $product_id , '_min_price', true );

		// filter the raw minimum price @since 1.2
		return apply_filters ( 'woocommerce_raw_minimum_price', $minimum, $product_id );

	}


	/*
	 * Check if Subscriptions plugin is installed and this is a subscription product
	 *
	 * @param int|WC_Product $product_id Either a product object or product's post ID.
	 * @access 	public
	 * @return 	return boolean
	 * @since 	2.0
	 */
	public static function is_subscription( $product_id ){

		$product_id = self::get_id( $product_id );

		if( class_exists( 'WC_Subscriptions_Product' ) && WC_Subscriptions_Product::is_subscription( $product_id ) ) {
			return TRUE;
		} else {
			return FALSE;
		}

	}


	/*
	 * Is the billing period variable
	 *
	 * @param int|WC_Product $product_id Either a product object or product's post ID.
	 * @return 	return string
	 * @access 	public
	 * @since 	2.0
	 */
	public static function is_billing_period_variable( $product_id ) {

		$product_id = self::get_id( $product_id );	

		if ( get_post_meta( $product_id , '_variable_billing', true ) == 'yes' ) {
			$variable = TRUE;
		} else {
			$variable = FALSE;
		}

		return apply_filters ( 'woocommerce_is_billing_period_variable', $variable, $product_id );
	}


	/*
	 * Get the Suggested Billing Period for subscriptsion
	 *
	 * @param int|WC_Product $product_id Either a product object or product's post ID.
	 * @return 	return string
	 * @access 	public
	 * @since 	2.0
	 */
	public static function get_suggested_billing_period( $product_id ) {

		$product_id = self::get_id( $product_id );	

		// Set month as the default billing period
		if ( ! ( $period = get_post_meta( $product_id, '_suggested_billing_period', true ) ) )
		 	$period = 'month';

		// filter the raw minimum price @since 1.2
		return apply_filters ( 'woocommerce_suggested_billing_period', $period, $product_id );

	}


	/*
	 * Get the Minimum Billing Period for subscriptsion
	 *
	 * @param int|WC_Product $product_id Either a product object or product's post ID.
	 * @return 	return string
	 * @access 	public
	 * @since 	2.0
	 */
	public static function get_minimum_billing_period( $product_id ) {

		$product_id = self::get_id( $product_id );	

		// Set month as the default billing period
		if ( ! ( $period = get_post_meta( $product_id, '_minimum_billing_period', true ) ) )
		 	$period = 'month';

		// filter the raw minimum price @since 1.2
		return apply_filters ( 'woocommerce_minimum_billing_period', $period, $product_id );

	}


	/*
	 * Determine if variable has NYP variations
	 *
	 * @param int|WC_Product $product_id Either a product object or product's post ID.
	 * @return 	return string
	 * @access 	public
	 * @since 	2.0
	 */
	public static function has_nyp( $the_product ) {

		// get the product object
		if ( is_numeric( $the_product ) ) {
			$the_product = get_product( $the_product );
		}

		if ( ! $the_product )
			return FALSE;

		// the product ID
		$product_id = $the_product->id;

		if ( $the_product->is_type( self::$supported_variable_types ) && get_post_meta( $product_id , '_has_nyp', true ) == 'yes' ) {
			$has_nyp = TRUE;
		} else {
			$has_nyp = FALSE;
		}

		return apply_filters ( 'woocommerce_has_nyp_variations', $has_nyp, $the_product );

	}


	/*
	 * Standardize number
	 *
	 * Switch the configured decimal and thousands separators to PHP default
	 *
	 * @return 	return string
	 * @access 	public
	 * @since 	1.2.2
	 */
	public static function standardize_number( $value ){

		$value = str_replace( get_option( 'woocommerce_price_thousand_sep' ), '', $value );

		return wc_nyp_format_decimal( $value );

	}


	/*
	 * Annualize Subscription Price
	 * convert price to "per year" so that prices with different billing periods can be compared
	 *
	 * @return 	woo formatted number
	 * @access 	public
	 * @since 	2.0
	 */
	public static function annualize_price( $price = false, $period = null ){

		$factors = self::annual_price_factors();

		if( isset( $factors[$period] ) )
			$price = $factors[$period] * self::standardize_number( $price );

		return wc_nyp_format_decimal( $price );

	}


	/*
	 * Annualize Subscription Price
	 * convert price to "per year" so that prices with different billing periods can be compared
	 *
	 * @return 	woo formatted number
	 * @access 	public
	 * @since 	2.0
	 */
	public static function annual_price_factors(){

		return array_map( 'esc_attr', apply_filters( 'woocommerce_nyp_annual_factors' ,
							array ( 'day' => 365,
										'week' => 52,
										'month' => 12,
										'year' => 1 ) ) );

	}


	/*
	 * Get the price HTML
	 *
	 * @param	object $product 
	 * @return 	string
	 * @access 	public
	 * @since 	2.0
	 */

	public static function get_price_html( $product ) { 

		$html = '';

		if( WC_Name_Your_Price_Helpers::is_nyp( $product ) )
			$html = self::get_suggested_price_html( $product );

		return apply_filters( 'woocommerce_nyp_html', $html, $product );

	}


	/*
	 * Get the "Minimum Price: $10" minimum string
	 *
	 * @param string $price ( formatted price string )
	 * @return 	string
	 * @access 	public
	 * @since 	2.0
	 */
	public static function get_minimum_price_html( $product ) {

		// start the price string
		$html = '';

		// if not nyp quit early
		if ( ! self::is_nyp( $product ) )
			return $html;

		// get the minimum price
		$minimum = self::get_minimum_price( $product ); 

		if( $minimum > 0 ){

			// get the minimum: text option
			$minimum_text = get_option( 'woocommerce_nyp_minimum_text', _x( 'Minimum Price:', 'minimum price', 'wc_name_your_price' ) );

			// get minimum billing period
			$minimum_period = self::is_billing_period_variable( $product ) ? self::get_minimum_billing_period( $product ) : false;

			// formulate a price string
			$price_string = self::get_price_string( $product, array( 'price' => $minimum, 'period' => $minimum_period ) );

			$html .= sprintf( '<span class="minimum-text">%s</span><span class="amount">%s</span>', $minimum_text, $price_string );


		} 

		return apply_filters( 'woocommerce_nyp_minimum_price_html', $html, $product );

	}


	/*
	 * Get the "Suggested Price: $10" price string
	 *
	 * @param	string $price ( formatted price string )
	 * @param	boolean $show_parens ( whether or not to show parentheses )
	 * @return 	string
	 * @access 	public
	 * @since 	2.0
	 */
	public static function get_suggested_price_html( $product ) {

		// start the price string
		$html = '';

		// if not nyp quit early
		if ( ! self::is_nyp( $product ) )
			return $html;

		// get suggested price
		$suggested = self::get_suggested_price( $product ); 

		if ( $suggested > 0 ) {

			// get the suggested: text option
			$suggested_text = get_option( 'woocommerce_nyp_suggested_text', _x( 'Suggested Price:', 'suggested price', 'wc_name_your_price' ) );

			// get suggested billing period
			$suggested_period = self::is_billing_period_variable( $product ) ? self::get_suggested_billing_period( $product ) : false;

			// formulate a price string
			$price_string = self::get_price_string( $product, array( 'price' => $suggested, 'period' => $suggested_period ) );

			// put it all together
			$html .= sprintf( '<span class="suggested-text">%s</span><span class="amount">%s</span>', $suggested_text, $price_string );

		} 

		return $html;

	}


	/*
	 * Format a price string
	 *
	 * @since 	2.0
	 * @param	object $product
	 * @param	array $args ( price, and billing period )
	 * @return	string
	 * @access	public
	 * @since	2.0
	 */
	public static function get_price_string( $product, $args = array() ) {

		$defaults = array( 'price' => false, 'period' => false );

		extract( wp_parse_args( $args, $defaults ) );

		// get subscription price string
		if( class_exists( 'WC_Subscriptions_Product' ) && WC_Subscriptions_Product::is_subscription( $product ) ) {

			// if a billing period was passed ( then it is a variable billing product and need to create our own string )
			if( $period ) {

				$html = sprintf( _x( ' %s / %s', 'Variable subscription price, ex: $10 / day', 'wc_name_your_price' ), wc_nyp_price( $price ), WC_Subscriptions_Manager::get_subscription_period_strings( 1, $period ) );

			} else {

				$include = array(
								'price' => wc_nyp_price( $price ),
								'subscription_length' => false,
								'sign_up_fee'         => false,
								'trial_length'        => false
				);

				$html = WC_Subscriptions_Product::get_price_string( $product, $include );
			}

		// simple products
		} else {
			$html = wc_nyp_price( $price );
		}

		return $html;

	}


	/**
	 * Get Posted Price
	 * 
	 * @param	string $product_id
	 * @param	string $prefix - needed for composites and bundles
	 * @return	string
	 * @access	public
	 * @since	2.0
	 */
	public static function get_posted_price( $product_id, $prefix = false ) {

		// go through a few options to find the $price we should display in the input (typically will be the suggested price)
		$posted = isset( $_POST['nyp' . $prefix] ) ?  ( self::standardize_number( $_POST['nyp' . $prefix] ) ) : '';

		// get suggested price
		$suggested = self::get_suggested_price( $product_id );

		// get minimum price
		$minimum = self::get_minimum_price( $product_id );

		if ( $posted && $posted >= 0 ) {
			$price = $posted;
		} elseif ( $suggested && $suggested > 0 ) {
			$price = $suggested;
		} elseif ( $minimum && $minimum > 0 ) {
			$price =  $minimum;
		} else {
			$price = '';
		}

		return $price;
	}


	/**
	 * Get Posted Billing Period
	 * 
	 * @param	string $product_id
	 * @param	string $prefix - needed for composites and bundles
	 * @return	string
	 * @access	public
	 * @since	2.0
	 */
	public static function get_posted_period( $product_id, $prefix = false ) {

		// go through a few options to find the $period we should display
		$posted_period = isset( $_POST['nyp-period' . $prefix] ) ?  $_POST['nyp-period' . $prefix] : '';

		// get suggested billing_period
		$suggested_period = self::get_suggested_billing_period( $product_id );

		// get minimum billing_period
		$minimum_period = self::get_minimum_billing_period( $product_id );

		if ( $posted_period ) {
			$period = $posted_period;
		} elseif ( $suggested_period ) {
			$period = $suggested_period;
		} elseif ( $minimum_period ) {
			$period = $minimum_period;
		} else {
			$period = 'month';
		}
		return $period;
	}


	/*
	 * Generate markup for NYP Price input
	 * similar to wc_price() but returns a text input instead with formatted number
	 * 
	 * @param	string $product_id
	 * @param	string $prefix - needed for composites and bundles
	 * @return	string
	 * @access	public
	 * @since	2.0
	 */
	public static function get_price_input( $product_id, $prefix = null ) {

		$price = self::get_posted_price( $product_id, $prefix );

		$num_decimals = absint( get_option( 'woocommerce_price_num_decimals' ) );
		$currency_pos = get_option( 'woocommerce_currency_pos' );
		$currency_symbol = get_woocommerce_currency_symbol();
		$decimal_sep     = wp_specialchars_decode( stripslashes( get_option( 'woocommerce_price_decimal_sep' ) ), ENT_QUOTES );
		$thousands_sep   = wp_specialchars_decode( stripslashes( get_option( 'woocommerce_price_thousand_sep' ) ), ENT_QUOTES );

		$price           = apply_filters( 'raw_woocommerce_price', floatval( $price ) );
		$price           = apply_filters( 'formatted_woocommerce_price', number_format( $price, $num_decimals, $decimal_sep, $thousands_sep ), $price, $num_decimals, $decimal_sep, $thousands_sep );

		if ( apply_filters( 'woocommerce_price_trim_zeros', true ) && $num_decimals > 0 ) {
			$price = wc_nyp_trim_zeros( $price );
		}

		$return = sprintf( '<input id="nyp%s" name="nyp%s" value="%s" size="6" title="nyp" class="input-text amount nyp-input text" />', $prefix, $prefix, $price );

		return apply_filters ( 'woocommerce_get_price_input', $return, $product_id, $prefix );

	}


	/**
	 * Generate Markup for Subscription Periods
	 * 
	 * @param	string $input
	 * @param	string $product_id
	 * @param	string $prefix - needed for composites and bundles
	 * @return	string
	 * @access	public
	 * @since	2.0
	 */
	public static function get_subscription_terms( $input, $product_id, $prefix ) {

		$terms = '';

		if( self::is_billing_period_variable( $product_id ) ) {
			// don't display the subscription price, period or length
			$include = array(
				'subscription_price'  => false,
				'subscription_period' => false,
				'subscription_length' => false,
			);

		} else {
			$include = array(
					'subscription_price'  => false
				);
			// if we don't show the price we don't get the "per" backslash so add it back
			if( WC_Subscriptions_Product::get_interval( $product_id ) == 1 )
	   		$terms .= '<span class="per">/ </span>';
		}

	   $terms .= WC_Subscriptions_Product::get_price_string( $product_id, $include );

	   // piece it all together
	   $terms = '<span class="subscription-terms"> ' . $terms . '</span>';

		return $input . $terms;

	}


	/**
	 * Generate Markup for Subscription Period Input
	 * 
	 * @param	string $input
	 * @param	string $product_id
	 * @param	string $prefix - needed for composites and bundles
	 * @return	string
	 * @access	public
	 * @since	2.0
	 */
	public static function get_subscription_period_input( $input, $product_id, $prefix ) {

		// create the dropdown select element
		$period = self::get_posted_period( $product_id, $prefix );

		// the pre-selected value
		$selected = $period ? $period : 'month';

		// get list of available periods from Subscriptions plugin
		$periods = WC_Subscriptions_Manager::get_subscription_period_strings();

		if( $periods ) :

			$period_input = sprintf( '<span class="per">/ </span><select id="nyp-period%s" name="nyp-period%s" class="nyp-period" />', $prefix, $prefix );

			foreach ( $periods as $i => $period ) :
				$period_input .= sprintf( '<option value="%s" %s>%s</option>', $i, selected( $i, $selected, false ), $period );
			endforeach;

			$period_input .= '</select>';

			$period_input = '<span class="nyp-billing-period"> ' . $period_input . '</span>';

		endif;

    	return $input . $period_input;

	}


	/*
	 * Get data attributes for use in nyp.js
	 *
	 * @param	string $product_id
	 * @param	string $prefix - needed for composites and bundles
	 * @return	string
	 * @access	public
	 * @since	2.0
	 */
	public static function get_data_attributes( $product_id, $prefix  = null ) {

		$price = self::get_posted_price( $product_id, $prefix );
		$minimum = self::get_minimum_price( $product_id ); 

		$data_string = sprintf( 'data-price="%s"', (double) $price );

		if( self::is_subscription( $product_id ) && self::is_billing_period_variable( $product_id ) ){

				$period = self::get_posted_period( $product_id, $prefix );
				$minimum_period = self::get_minimum_billing_period( $product_id );

				$annualized_minimum = self::annualize_price( $minimum, $minimum_period );

				$data_string .= sprintf( ' data-period="%s"', ( esc_attr( $period ) ) ? esc_attr( $period ) : 'month' );
				$data_string .= sprintf( ' data-annual-minimum="%s"', $annualized_minimum > 0  ? (double) $annualized_minimum : 0 );

		} else {

			$data_string .= sprintf( ' data-min-price="%s"', ( $minimum && $minimum > 0 ) ? (double) $minimum : 0 );

		}

		return $data_string;

	}


	/*
	 * Sync variable product prices against NYP minimum prices
	 * @param	string $product_id
	 * @param	array $children - the ids of the variations
	 * @return	void
	 * @access	public
	 * @since	2.0
	 */
	public static function variable_product_sync( $product_id, $children ){ 

		if ( $children ) { 

			$min_price    = null;
			$max_price    = null;
			$min_price_id = null;
			$max_price_id = null;

			foreach ( $children as $child )  {

				// if NYP 
				if( WC_Name_Your_Price_Helpers::is_nyp( $child ) ) {

					// get the nyp prices for this variation
					$nyp_suggested_price 	= get_post_meta( $child, '_suggested_price', true );
					$nyp_min_price 		= get_post_meta( $child, '_min_price', true );

					// if there is no set minimum, technically the min is 0
					$nyp_min_price = $nyp_min_price ? $nyp_min_price : 0;

					// if either suggested or min is larger than then use that
					$nyp_max = max( $nyp_suggested_price, $nyp_min_price );

					if ( $nyp_max > $max_price ) {
						$max_price    = $nyp_max;
						$max_price_id = $child;
					}

					// suggested cannot (or should not) be lower than min price so only compare nyp minimum against lowest price
					if ( is_null( $min_price ) || $nyp_min_price < $min_price ) {
						$min_price    = $nyp_min_price;
						$min_price_id = $child;
					}

				} else {

					$child_price = get_post_meta( $child, '_price', true );
					
					if ( $child_price === '' )
						continue;

					if ( $child_price > $max_price ) {
						$max_price    = $child_price;
						$max_price_id = $child;
					}

					if ( is_null( $min_price ) || $child_price < $min_price ) {
						$min_price    = $child_price;
						$min_price_id = $child;
					}
				}

			}

			// Store prices
			update_post_meta( $product_id, '_price', $min_price );
			update_post_meta( $product_id, '_min_variation_price', $min_price );
			update_post_meta( $product_id, '_max_variation_price', $max_price );

			// Store IDS
			update_post_meta( $product_id, '_min_price_variation_id', $min_price_id );
			update_post_meta( $product_id, '_max_price_variation_id', $max_price_id );

		}

	}
} //end class

WC_Name_Your_Price_Helpers::init();