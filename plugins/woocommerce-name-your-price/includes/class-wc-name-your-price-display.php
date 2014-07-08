<?php

/**
 * WC_Name_Your_Price_Display class.
 */
class WC_Name_Your_Price_Display {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		// Single Product Display
		add_action( 'wp_enqueue_scripts', array( $this, 'nyp_style' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'display_price_input' ) );
		add_action( 'woocommerce_nyp_after_price_input', array( $this, 'display_minimum_price' ) );
		add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'single_add_to_cart_text' ), 10, 2 );

		// Display NYP Prices
		add_filter( 'woocommerce_get_price_html', array( $this, 'nyp_price_html'), 10, 2 );

		// Loop Display
		add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'add_to_cart_text' ) );
		add_filter( 'woocommerce_product_add_to_cart_url', array( $this, 'add_to_cart_url' ), 10, 2 );

		// if quick-view is enabled then we need the style and scripts everywhere
		add_action( 'wc_quick_view_enqueue_scripts', array( $this, 'nyp_scripts' ) );
		add_action( 'wc_quick_view_enqueue_scripts', array( $this, 'nyp_style' ) );

		// post class
		add_filter( 'post_class', array( $this, 'add_post_class' ), 30, 3 );

		// variable products
		if ( WC_Name_Your_Price_Helpers::is_woocommerce_2_1() ) {
			add_filter( 'woocommerce_variation_is_visible', array( $this, 'variation_is_visible' ), 10, 3 );
			add_filter( 'woocommerce_available_variation', array( $this, 'available_variation' ), 10, 3 );
			add_filter( 'woocommerce_get_variation_price', array( $this, 'get_variation_price' ), 10, 4 );
			add_filter( 'woocommerce_get_variation_regular_price', array( $this, 'get_variation_price' ), 10, 4 );
		}

		// backcompat for WC2.0.20
		add_filter( 'single_add_to_cart_text', array( $this, 'single_add_to_cart_text' ) );
		add_filter( 'add_to_cart_text', array( $this, 'add_to_cart_text' ) );
		add_filter( 'woocommerce_add_to_cart_url', array( $this, 'add_to_cart_url' ) );
	
	}



	/*-----------------------------------------------------------------------------------*/
	/* Single Product Display Functions */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Load a little stylesheet
	 *
	 * @return void
	 * @since 1.0
	 */
	public function nyp_style(){

		if ( get_option( 'woocommerce_nyp_disable_css', 'no' ) == 'no' )
			wp_enqueue_style( 'woocommerce-nyp', WC_Name_Your_Price()->plugin_url() . '/assets/css/name-your-price.css', false, WC_Name_Your_Price()->version );

	}


	/**
	 * Register the price input script
	 *
	 * @return void
	 */
	function register_scripts() {
		wp_register_script( 'accounting', WC_Name_Your_Price()->plugin_url() . '/assets/js/accounting.js', '', '0.3.2', true );
		
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		wp_register_script( 'woocommerce-nyp', WC_Name_Your_Price()->plugin_url() . '/assets/js/name-your-price'. $suffix . '.js', array( 'jquery', 'accounting' ), WC_Name_Your_Price()->version, true );
	}


	/**
	 * Load price input script
	 *
	 * @return void
	 */
	function nyp_scripts() {

		wp_enqueue_script( 'accounting' );
		wp_enqueue_script( 'woocommerce-nyp' );

		$params = array(
			'currency_format_num_decimals' => absint( get_option( 'woocommerce_price_num_decimals' ) ),
			'currency_format_decimal_sep'  => esc_attr( stripslashes( get_option( 'woocommerce_price_decimal_sep' ) ) ),
			'currency_format_thousand_sep' => esc_attr( stripslashes( get_option( 'woocommerce_price_thousand_sep' ) ) ),
			'currency_symbol' => esc_attr( get_woocommerce_currency_symbol() ),
			'annual_price_factors' =>  WC_Name_Your_Price_Helpers::annual_price_factors(),
			'add_to_cart_text'	=> get_option( 'woocommerce_nyp_button_text_single', __( 'Add to Cart', 'wc_name_your_price' ) ),
			'minimum_error' => __( 'Please enter at least %s', 'wc_name_your_price' )
		);

		wp_localize_script( 'woocommerce-nyp', 'woocommerce_nyp_params', $params );
		
	}

	
	/**
	 * Call the Price Input Template
	 *
	 * @param int $product_id
	 * @param string $prefix - prefix is key to integration with Bundles
	 * @return  void
	 * @since 1.0
	 */
	public function display_price_input( $product_id = false, $prefix = false ){

		if( ! $product_id ){
			global $product;
			$product_id = $product->id;
		}

		// If not NYP quit right now
		if( ! WC_Name_Your_Price_Helpers::is_nyp( $product_id ) && ! WC_Name_Your_Price_Helpers::has_nyp( $product_id ) )
			return;

		// load up the NYP scripts
		$this->nyp_scripts();

		// If the product is a subscription add some items to the price input
		if( WC_Name_Your_Price_Helpers::is_subscription( $product_id ) ){

			// add the billing period input
			if( WC_Name_Your_Price_Helpers::is_billing_period_variable( $product_id ) )
				add_filter( 'woocommerce_get_price_input', array('WC_Name_Your_Price_Helpers', 'get_subscription_period_input' ), 10, 3 );

			// add the price terms
			add_filter( 'woocommerce_get_price_input', array('WC_Name_Your_Price_Helpers', 'get_subscription_terms' ), 20, 3 );

		}

		// get the price input template
		wc_nyp_get_template(
			'single-product/price-input.php',
			array( 'product_id' => $product_id,
					'prefix' 	=> $prefix ),
			FALSE,
			WC_Name_Your_Price()->plugin_path() . '/templates/' );

	}

	/**
	 * Call the Minimum Price Template
	 *
	 * @param int $product_id
	 * @param string $prefix - prefix is key to integration with Bundles
	 * @return  void
	 * @since 1.0
	 */
	public function display_minimum_price( $product_id ){

		if( ! $product_id ){
			global $product;
			$product_id = $product->id;
		}

		// If not NYP quit right now
		if( ! WC_Name_Your_Price_Helpers::is_nyp( $product_id ) && ! WC_Name_Your_Price_Helpers::has_nyp( $product_id ) )
			return;

		// get the minimum price
		$minimum = WC_Name_Your_Price_Helpers::get_minimum_price( $product_id ); 

		if( $minimum > 0 || WC_Name_Your_Price_Helpers::has_nyp( $product_id )){

			// get the minimum price template
			wc_nyp_get_template(
				'single-product/minimum-price.php',
				array( 'product_id' => $product_id ),
				FALSE,
				WC_Name_Your_Price()->plugin_path() . '/templates/' );

		}

	}


	/*
	 * if NYP change the single item's add to cart button text
	 * don't include on variations as you can't be sure all the variations are NYP
	 * variations will be handled via JS
	 *
	 * @param string $text
	 * @return string
	 * @since 2.0
	 */
	public function single_add_to_cart_text( $text, $product = null ) {
		
		if( ! is_object( $product ) )
			global $product; // remove when WC2.0.20 support dropped

		if( WC_Name_Your_Price_Helpers::is_nyp( $product ) )
			$text = get_option( 'woocommerce_nyp_button_text_single', __( 'Add to Cart', 'wc_name_your_price' ) );

		return $text;

	}

	/*-----------------------------------------------------------------------------------*/
	/* Display NYP Price HTML */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Filter the Price HTML
	 *
	 * @param string $price
	 * @param object $product
	 * @return string
	 * @since 1.0
	 * @renamed in 2.0
	 */
	function nyp_price_html( $price, $product ){

		if( WC_Name_Your_Price_Helpers::is_nyp( $product ) )
			$price = WC_Name_Your_Price_Helpers::get_price_html( $product );

		return $price;

	}

	/*-----------------------------------------------------------------------------------*/
	/* Loop Display Functions */
	/*-----------------------------------------------------------------------------------*/

	/*
	 * if NYP change the loop's add to cart button text
	 *
	 * @param string $text
	 * @return string
	 * @since 1.0
	 */
	public function add_to_cart_text( $text, $product = null ) {

		if( ! is_object( $product ) )
			global $product;
		
		if ( WC_Name_Your_Price_Helpers::is_nyp( $product ) )
			$text = get_option( 'woocommerce_nyp_button_text', __( 'Set Price', 'wc_name_your_price' ) );

		return $text;

	}

	/*
	 * if NYP change the loop's add to cart button URL
	 * disable ajax add to cart and redirect to product page
	 *
	 * @param string $url
	 * @return string
	 * @since 1.0
	 */
	public function add_to_cart_url( $url, $product = null ) {

		if( ! is_object( $product ) )
			global $product;

		if ( WC_Name_Your_Price_Helpers::is_nyp( $product ) ) {
			$url = get_permalink( $product->id );
			$product->product_type = 'nyp'; // disables the ajax add to cart
		}

		return $url;

	}

	/*-----------------------------------------------------------------------------------*/
	/* Post Class */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Add nyp to post class
	 * 
	 * @param  array $classes - post classes
	 * @param  string $class
	 * @param  int $post_id
	 * @return array
	 * @since 2.0
	 */
	public function add_post_class( $classes, $class = '', $post_id = '' ) {
		if ( ! $post_id || get_post_type( $post_id ) !== 'product' )
			return $classes;

		if ( WC_Name_Your_Price_Helpers::is_nyp( $post_id ) || WC_Name_Your_Price_Helpers::has_nyp( $post_id ) )
			$classes[] = 'nyp-product';

		return $classes;

	}

	/*-----------------------------------------------------------------------------------*/
	/* Variable Product Display Functions */
	/*-----------------------------------------------------------------------------------*/

	/*
	 * Add nyp data to json encoded variation form
	 * 
	 * @param  boolean $visible - whether to display this variation or not
	 * @param  int $variation_id
	 * @param  int $product_id
	 * @return boolean
	 * @since 2.0
	 */
	public function variation_is_visible( $visible, $variation_id, $product_id ){

		if( WC_Name_Your_Price_Helpers::is_nyp( $variation_id ) )
			$visible = TRUE;

		return $visible;
	}

	/*
	 * Add nyp data to json encoded variation form
	 * 
	 * @param  array $data - this is the variation's json data
	 * @param  object $product
	 * @param  object $variation
	 * @return array
	 * @since 2.0
	 */
	public function available_variation( $data, $product, $variation ){

		$is_nyp = WC_Name_Your_Price_Helpers::is_nyp( $variation );

		$nyp_data = array ( 'is_nyp' => $is_nyp );

		if( $is_nyp ){
			$nyp_data['minimum_price'] = WC_Name_Your_Price_Helpers::get_minimum_price( $variation->variation_id );
			$nyp_data['posted_price'] =  WC_Name_Your_Price_Helpers::get_posted_price( $variation->variation_id );
			$nyp_data['price_html'] = '<span class="price">' . WC_Name_Your_Price_Helpers::get_suggested_price_html( $variation ) . '</span>';
			$nyp_data['minimum_price_html'] = WC_Name_Your_Price_Helpers::get_minimum_price_html( $variation );
		}

		return array_merge ( $data, $nyp_data );

	}

	/**
	 * Get the min or max variation (active) price.
	 * 
	 * @param  string $price
	 * @param  string $min_or_max - min or max
	 * @param  boolean  $display Whether the value is going to be displayed
	 * @return string
	 * @since  2.0
	 */
	public function get_variation_price( $price, $product, $min_or_max, $display ) {

		if ( WC_Name_Your_Price_Helpers::has_nyp( $product ) )
			$price = get_post_meta( $product->id, '_' . $min_or_max . '_variation_price', true );

		return $price;
	}

} //end class