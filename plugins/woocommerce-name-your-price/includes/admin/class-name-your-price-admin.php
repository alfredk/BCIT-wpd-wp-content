<?php
/**
 * Name Your Price Admin Class
 *
 * Adds a name your price setting tab and saves name your price meta data.
 *
 * @package		WooCommerce Name Your Price
 * @subpackage	WC_Name_Your_Price_Admin
 * @category	Class
 * @author		Kathy Darling
 * @since		1.0
 */
class WC_Name_Your_Price_Admin {

	static $simple_supported_types = array( 'simple', 'subscription', 'bundle', 'bto' );

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 *
	 * @since 1.0
	 */
	public static function init() {

		// admin notices
		if ( ! WC_Name_Your_Price_Helpers::is_woocommerce_2_1() ) {
			add_action( 'admin_notices', array( __CLASS__, 'admin_notice' ) );
			add_action( 'admin_init', array( __CLASS__, 'nag_ignore' ) );
		}

		// Product Meta boxes
		add_filter( 'product_type_options', array( __CLASS__, 'product_type_options' ) );
		add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'add_to_metabox' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_product_meta' ), 20, 2 );

		// Variable Product
		if ( WC_Name_Your_Price_Helpers::is_woocommerce_2_1() ) {
			add_action( 'woocommerce_variation_options', array( __CLASS__, 'product_variations_options' ), 10, 2 );
			add_action( 'woocommerce_product_after_variable_attributes', array( __CLASS__, 'add_to_variations_metabox'), 10, 2 );
			
			// regular variable products
			add_action( 'woocommerce_process_product_meta_variable', array( __CLASS__, 'save_variable_product_meta' ) );
			add_action( 'woocommerce_save_product_variation', array( __CLASS__, 'save_product_variation' ), 10, 2 );

			// variable subscription products
			add_action( 'woocommerce_process_product_meta_variable-subscription', array( __CLASS__, 'save_variable_product_meta' ) );
			add_action( 'woocommerce_save_product_variation-subscription', array( __CLASS__, 'save_product_variation' ), 10, 2 );

			// Variable Bulk Edit
			add_action( 'woocommerce_variable_product_bulk_edit_actions', array( __CLASS__, 'bulk_edit_actions' ) );
		}

		// Admin Scripts
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'meta_box_script'), 20 );

		// Add Help Tab
		add_action( 'admin_print_styles', array( __CLASS__, 'add_help_tab' ), 20 );

		// Edit Products screen
		add_filter( 'woocommerce_get_price_html', array( __CLASS__, 'admin_price_html' ), 20, 2 );

		// Product Filters
		add_filter( 'woocommerce_product_filters', array( __CLASS__, 'product_filters' ) );
		add_filter( 'parse_query', array( __CLASS__, 'product_filters_query' ) );

		// Quick Edit - changing to only work with WC2.1
		add_action( 'manage_product_posts_custom_column', array( __CLASS__, 'column_display'), 10, 2 );
		add_action( 'woocommerce_product_quick_edit_end',  array( __CLASS__, 'quick_edit') ); 
		if ( WC_Name_Your_Price_Helpers::is_woocommerce_2_1() )
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'quick_edit_scripts'), 20 );
		add_action( 'woocommerce_product_quick_edit_save', array( __CLASS__, 'quick_edit_save') );

		// Admin Settings
		if ( WC_Name_Your_Price_Helpers::is_woocommerce_2_1() ) {
			// new settings API
			add_filter( 'woocommerce_get_settings_pages', array( __CLASS__, 'add_settings_page' ) ); 
		} else {
			add_filter( 'woocommerce_settings_tabs_array', array( __CLASS__, 'admin_tabs' ), 30 );
			add_action( 'woocommerce_settings_tabs_nyp', array( __CLASS__, 'admin_panel') );
			add_action( 'woocommerce_update_options_nyp' , array( __CLASS__, 'process_admin_options' ) );
		}

	}

    /*-----------------------------------------------------------------------------------*/
	/* Admin Notice */
	/*-----------------------------------------------------------------------------------*/

	/*
	 * Display a notice that can be dismissed if less than WooCommerce 2.1
	 *
	 * @return print HTML
	 * @since 1.0
	 */
	public static function admin_notice() {
		global $current_user ;
		$user_id = $current_user->ID;
		/* Check that the user hasn't already clicked to ignore the message */
		if ( current_user_can( 'update_plugins' ) && ! get_user_meta( $user_id, 'nyp_2_0_ignore') ) {
			echo '<div class="updated woocommerce-message"><div class="squeezer"><h4>';
			printf( __( 'WooCommerce <strong>Name Your Price</strong> now supports variable products!' ), 'wc_name_your_price' );
			echo '<p>';
			printf( __( 'Please upgrade WooCommerce to version 2.1 to take advantage of this feature. %sUpgrade Now%s %sHide Notice%s', 'wc_name_your_price' ), '<a href="'. admin_url( "update-core.php" ) . '" class="wc-update-now button-primary" >', '</a>', '<a href="'. add_query_arg( 'nyp_2_0_nag_ignore', 0 ) . '" class="skip button-primary">', '</a>' );

			echo '</p></div></div>';
		}
	}

	/*
	 * Add notice status to user meta
	 *
	 * @param array $options
	 * @return array
	 * @since 1.0
	 */
	public static function nag_ignore() {
		global $current_user;
		$user_id = $current_user->ID;
		/* If user clicks to ignore the notice, add that to their user meta */
		if ( isset( $_GET['nyp_2_0_nag_ignore'] ) && '0' == $_GET['nyp_2_0_nag_ignore'] ) {
			add_user_meta( $user_id, 'nyp_2_0_ignore', 'true', true );
		}
	}

    /*-----------------------------------------------------------------------------------*/
	/* Write Panel / metabox */
	/*-----------------------------------------------------------------------------------*/

	/*
	 * Add checkbox to product data metabox title
	 *
	 * @param array $options
	 * @return array
	 * @since 1.0
	 */
	public static function product_type_options( $options ){

	  $options['nyp'] = array(
	      'id' => '_nyp',
	      'wrapper_class' => 'show_if_simple',
	      'label' => __( 'Name Your Price', 'wc_name_your_price'),
	      'description' => __( 'Customers are allowed to determine their own price.', 'wc_name_your_price'),
	      'default' => 'no'
	    );

	  return $options;

	}

	/*
	 * Add text inputs to product metabox
	 *
	 * @return print HTML
	 * @since 1.0
	 */
	public static function add_to_metabox(){
		global $post;

		echo '<div class="options_group show_if_nyp">';

			if( class_exists( 'WC_Subscriptions' ) ) {

				// make billing period variable
				woocommerce_wp_checkbox( array(
						'id' => '_variable_billing',
						'wrapper_class' => 'show_if_subscription',
						'label' => __('Variable Billing Period', 'wc_name_your_price'),
						'description' => __('Allow the customer to set the billing period.', 'woocommerce', 'wc_name_your_price' ) ) );
			}
		
			// Suggested Price
			woocommerce_wp_text_input( array(
				'id' => '_suggested_price',
				'class' => 'wc_input_price short',
				'label' => __( 'Suggested Price', 'wc_name_your_price') . ' ('.get_woocommerce_currency_symbol().')' ,
				'desc_tip' => 'true',
				'description' => __( 'Price to pre-fill for customers.  Leave blank to not suggest a price.', 'wc_name_your_price' ),
				'data_type' => 'price'
			) );

			if( class_exists( 'WC_Subscriptions' ) ) {

				// Suggested Billing Period
				woocommerce_wp_select( array(
					'id'          => '_suggested_billing_period',
					'label'       => __( 'per', WC_Subscriptions::$text_domain ),
					'options'     => WC_Subscriptions_Manager::get_subscription_period_strings()
					)
				);
			}

			// Minimum Price
			woocommerce_wp_text_input( array(
				'id' => '_min_price',
				'class' => 'wc_input_price short',
				'label' => __( 'Minimum Price', 'wc_name_your_price') . ' ('.get_woocommerce_currency_symbol().')',
				'desc_tip' => 'true',
				'description' =>  __( 'Lowest acceptable price for product. Leave blank to not enforce a minimum. Must be less than or equal to the set suggested price.', 'wc_name_your_price' ),
				'data_type' => 'price'
			) );

			if( class_exists( 'WC_Subscriptions' ) ) {
				// Minimum Billing Period
				woocommerce_wp_select( array(
					'id'          => '_minimum_billing_period',
					'label'       => __( 'per', WC_Subscriptions::$text_domain ),
					'options'     => WC_Subscriptions_Manager::get_subscription_period_strings()
					)
				);
			}

			do_action( 'woocommerce_name_your_price_options_pricing' );

		echo '</div>';

	  }


	/*
	 * Save extra meta info
	 *
	 * @param int $post_id
	 * @param object $post
	 * @return void
	 * @since 1.0 (renamed in 2.0)
	 */
	public static function save_product_meta( $post_id, $post ) {

	   	$product_type 	= empty( $_POST['product-type'] ) ? 'simple' : sanitize_title( stripslashes( $_POST['product-type'] ) );
	   	$suggested = '';

	   	if ( isset( $_POST['_nyp'] ) && in_array( $product_type, self::$simple_supported_types) ) {
			update_post_meta( $post_id, '_nyp', 'yes' );
			// removing the sale price removes NYP items from Sale shortcodes
			update_post_meta( $post_id, '_sale_price', '' );
		} else {
			update_post_meta( $post_id, '_nyp', 'no' );
		}

		if ( isset( $_POST['_suggested_price'] ) ) {
			$suggested = ( trim( $_POST['_suggested_price'] ) === '' ) ? '' : wc_nyp_format_decimal( $_POST['_suggested_price'] );
			update_post_meta( $post_id, '_suggested_price', $suggested );
		}

		if ( isset( $_POST['_min_price'] ) ) {
			$minimum = ( trim( $_POST['_min_price'] ) === '' ) ? '' : wc_nyp_format_decimal( $_POST['_min_price'] );
			update_post_meta( $post_id, '_min_price', $minimum );
		}

		// Variable Billing Periods

		// save whether subscription is variable billing or not
		if ( isset( $_POST['_variable_billing'] ) ) {
			update_post_meta( $post_id, '_variable_billing', 'yes' );
		} else {
			update_post_meta( $post_id, '_variable_billing', 'no' );
		}

		// suggested period - don't save if no suggested price
		if ( class_exists( 'WC_Subscriptions_Manager' ) && $suggested && isset( $_POST['_suggested_billing_period'] ) && in_array( $_POST['_suggested_billing_period'], WC_Subscriptions_Manager::get_subscription_period_strings() ) ){
			
			$suggested_period = wc_nyp_clean( $_POST['_suggested_billing_period'] );
			
			update_post_meta( $post_id, '_suggested_billing_period', $suggested_period );
		} 

		// minimum period - don't save if no minimum price
		if ( class_exists( 'WC_Subscriptions_Manager' ) && isset( $_POST['_min_price'] ) && isset( $_POST['_minimum_billing_period'] ) && in_array( $_POST['_minimum_billing_period'], WC_Subscriptions_Manager::get_subscription_period_strings() ) ){
			
			$minimum_period = wc_nyp_clean( $_POST['_minimum_billing_period'] );
			
			update_post_meta( $post_id, '_minimum_billing_period', $minimum_period );
		}

	}


	/*
	 * Add NYP checkbox to each variation
	 *
	 * @param string $loop
	 * @param array $variation_data
	 * return print HTML
	 * @since 2.0
	 */
	public static function product_variations_options( $loop, $variation_data ){ ?>

		<label><input type="checkbox" class="checkbox variation_is_nyp" name="variation_is_nyp[<?php echo $loop; ?>]"

			<?php checked( isset( $variation_data['_nyp'][0] ) ? $variation_data['_nyp'][0] : '', 'yes' ); ?> /> <?php _e( 'Name Your Price', 'wc_name_your_price'); ?> <a class="tips" data-tip="<?php _e( 'Customers are allowed to determine their own price.', 'wc_name_your_price'); ?>" href="#">[?]</a></label>

		<?php

	}

	/*
	 * Add NYP price inputs to each variation
	 *
	 * @param string $loop
	 * @param array $variation_data
	 * @return print HTML
	 * @since 2.0
	 */
	public static function add_to_variations_metabox( $loop, $variation_data ){ ?>

		<tr class="variable_nyp_pricing">
			<td>
				<label><?php echo __( 'Suggested Price:', 'wc_name_your_price' ) . ' ('.get_woocommerce_currency_symbol().')'; ?></label>
				<input size="5" name="variation_suggested_price[<?php echo $loop; ?>]" value="<?php if ( isset( $variation_data['_suggested_price'][0] ) ) echo esc_attr( $variation_data['_suggested_price'][0] ); ?>" step="any" min="0"  />
			</td>
			<td>
				<label><?php echo __( 'Minimum Price:', 'wc_name_your_price' ) . ' ('.get_woocommerce_currency_symbol().')'; ?></label>
				<input size="5" name="variation_min_price[<?php echo $loop; ?>]" value="<?php if ( isset( $variation_data['_min_price'][0] ) ) echo esc_attr( $variation_data['_min_price'][0] ); ?>" step="any" min="0" />
			</td>
		</tr>
	<?php

	}

	/*
	 * Save extra meta info for variable products
	 *
	 * @param int $variation_id
	 * @param int $i
	 * return void
	 * @since 2.0
	 */
	public static function save_product_variation( $variation_id, $i ){

		// set NYP status
		$variation_is_nyp = isset( $_POST['variation_is_nyp'][$i] ) ? 'yes' : 'no';
		update_post_meta( $variation_id, '_nyp', $variation_is_nyp );

		// save suggested price
		if ( isset( $_POST['variation_suggested_price'][$i] ) ) {
			$variation_suggested_price = ( trim( $_POST['variation_suggested_price'][$i]  ) === '' ) ? '' : wc_nyp_format_decimal( $_POST['variation_suggested_price'][$i] );
			update_post_meta( $variation_id, '_suggested_price', $variation_suggested_price );
		}

		// save minimum price
		if ( isset( $_POST['variation_min_price'][$i] ) ) {
			$variation_min_price = ( trim( $_POST['variation_min_price'][$i]  ) === '' ) ? '' : wc_nyp_format_decimal( $_POST['variation_min_price'][$i] );
			update_post_meta( $variation_id, '_min_price', $variation_min_price );
		}

		// remove lingering sale price if switched to nyp
		if( $variation_is_nyp == 'yes' )
			update_post_meta( $variation_id, '_sale_price', '' );

	}

	/*
	 * Save extra meta info for product variations
	 *
	 * @param int $post_id
	 * @return void
	 * @since 2.0
	 */
	public static function save_variable_product_meta( $post_id ){

		$has_nyp = false;

		if ( isset( $_POST['variable_sku'] ) ) :
			$variable_sku = $_POST['variable_sku'];

			$variation_is_nyp = $_POST['variation_is_nyp'];

			for ( $i = 0; $i < sizeof( $variable_sku ); $i++ ) :

				$is_nyp = isset( $variation_is_nyp[ $i ] ) ? true : false;

				if( $is_nyp ){
					$has_nyp = true;
					break; // if we find 1 NYP we can quit early
				}

			endfor;

		endif;

		$has_nyp = $has_nyp ? 'yes' : 'no';
		update_post_meta( $post_id, '_has_nyp', $has_nyp );
	}


	/*
	 * Add options to variations bulk edit
	 *
	 * @return print HTML
	 * @since 2.0
	 */
	public static function bulk_edit_actions(){ ?>
		<option value="toggle_nyp"><?php _e( 'Toggle &quot;Name Your Price&quot;', 'wc_name_your_price' ); ?></option>
		<option value="variation_suggested_price"><?php _e( 'Suggested prices', 'wc_name_your_price' ); ?></option>
		<option value="variation_suggested_price_increase"><?php _e( 'Suggested prices increase by (fixed amount or %)', 'wc_name_your_price' ); ?></option>
		<option value="variation_suggested_price_decrease"><?php _e( 'Suggested prices decrease by (fixed amount or %)', 'wc_name_your_price' ); ?></option>
		<option value="variation_min_price"><?php _e( 'Minimum prices', 'wc_name_your_price' ); ?></option>
		<option value="variation_min_price_increase"><?php _e( 'Minimum prices increase by (fixed amount or %)', 'wc_name_your_price' ); ?></option>
		<option value="variation_min_price_decrease"><?php _e( 'Minimum prices decrease by (fixed amount or %)', 'wc_name_your_price' ); ?></option>
		<?php
	}


	/*
	 * Javascript to handle the NYP metabox options
	 *
	 * @param string $hook
	 * @return void
	 * @since 1.0
	 */
    public static function meta_box_script( $hook ){

		// check if on Edit-Post page (post.php or new-post.php).
		if( ! in_array( $hook, array( 'post-new.php', 'post.php' ) ) )
					return;
				
		// now check to see if the $post type is 'product'
		global $post;
		if ( ! isset( $post ) || 'product' != $post->post_type )
			return;

		// enqueue and localize
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
 
		wp_enqueue_script( 'woocommerce_nyp_metabox',WC_Name_Your_Price()->plugin_url() . '/includes/admin/js/nyp-metabox'. $suffix . '.js', array( 'jquery' ), WC_Name_Your_Price()->version, true );

		$strings = array ( 'enter_value' => __( 'Enter a value', 'wc-name-your-price' ),
										'price_adjust' => __( 'Enter a value (fixed or %)', 'woocommerce' ) );

		wp_localize_script( 'woocommerce_nyp_metabox', 'woocommerce_nyp_metabox', $strings );

	}

	/*
	 * Add help tab for product meta
	 *
	 * @return print html
	 * @since 1.0
	 */
    public static function add_help_tab(){

    	if ( ! function_exists( 'get_current_screen' ) )
    		return;

		$screen = get_current_screen();

		// Product/Coupon/Orders
		if ( ! in_array( $screen->id, array( 'product', 'edit-product' ) ) ) return;

		$screen->add_help_tab( array(
	    'id'	=> 'woocommerce_nyp_tab',
	    'title'	=> __('Name Your Price', 'wc_name_your_price'),
	    'content'	=>

	    	'<h4>' . __( 'Name Your Price', 'wc_name_your_price' ) . '</h4>' .

	    	'<p>' . __( 'In the "Product Meta" metabox, check the Name Your Price checkbox to allow your customers to enter their own price.', 'wc_name_your_price' ) . '</p>' .

	    	'<p>' . __( 'As of Name Your Price version 2.0, this ability is available for "Simple", "Subscription", "Bundled", "Variable" and "Variable Subscriptions" Products.', 'wc_name_your_price' ) . '</p>' .

	    	'<h4>' . __( 'Suggested Price', 'wc_name_your_price' ) . '</h4>' .

	    	'<p>' . __( 'This is the price you\'d like to suggest to your customers.  The Name Your Price input will be prefilled with this value.  To not suggest a price at all, you may leave this field blank.', 'wc_name_your_price' ) . '</p>' .

	    	'<p>' . __( 'This value must be a positive number.', 'wc_name_your_price' ) . '</p>' .

	    	'<h4>' . __( 'Minimum Price', 'wc_name_your_price' ) . '</h4>' .

	    	'<p>' . __( 'This is the lowest price you are willing to accept for this product.  To not enforce a minimum (ie: to accept any price, including zero), you may leave this field blank.', 'wc_name_your_price' ) . '</p>' .

	    	'<p>' . __( 'This value must be a positive number that is less than or equal to the set suggested price.', 'wc_name_your_price' ) . '</p>' .

	    	'<h4>' . __( 'Subscriptions', 'wc_name_your_price' ) . '</h4>' .

	    	'<p>' . __( 'If you have a name your price subscription product, the subscription time period fields are still needed, but the price will be disabled in lieu of the Name Your Price suggested and minimum prices.', 'wc_name_your_price' ) . '</p>' .

	    	'<p>' . __( 'As of Name Your Price version 2.0, you can now allow variable billing periods.', 'wc_name_your_price' ) . '</p>'

	    ) );

	}

    /*-----------------------------------------------------------------------------------*/
	/* Product Overview - edit columns */
	/*-----------------------------------------------------------------------------------*/


	/*
	 * Change price in edit screen to NYP
	 *
	 * @param string $price
	 * @param object $product
	 * @return string
	 * @since 1.0
	 */
	public static function admin_price_html( $price, $product ){

	   if( WC_Name_Your_Price_Helpers::is_nyp( $product ) && ! isset( $product->is_filtered_price_html ) ){
	   		$minimum = WC_Name_Your_Price_Helpers::get_minimum_price( $product );
	   		$period = WC_Name_Your_Price_Helpers::is_billing_period_variable( $product ) ? WC_Name_Your_Price_Helpers::get_minimum_billing_period( $product ) : false;
			$price = _x( 'From:', 'minimum price', 'wc_name_your_price' ) . ' ' . WC_Name_Your_Price_Helpers::get_price_string( $product->id, array ( 'price' => $minimum, 'period' => $period ) );
	   }
	   
		return $price;

	}

	public static function product_filters( $output ){
		global $wp_query;

		$pos = strpos ( $output, "</select>" );

		if ( $pos ) {

			$nyp_option = "<option value='name-your-price' ";

				if ( isset( $wp_query->query['product_type'] ) )
					$nyp_option .= selected( 'name-your-price', $wp_query->query['product_type'], false );

				$nyp_option .= "> &#42; " . __( 'Name Your Price', 'wc_name_your_price' ) . "</option>";
			
			$output = substr_replace( $output, $nyp_option, $pos, 0);

		}

		return $output;

	}

	/**
	 * Filter the products in admin based on options
	 *
	 * @param mixed $query
	 */
	public static function product_filters_query( $query ) {
		global $typenow, $wp_query;

	    if ( $typenow == 'product' ) {

	    	if ( isset( $query->query_vars['product_type'] ) ) {
		    	// Subtypes
		    	if ( $query->query_vars['product_type'] == 'name-your-price' ) {
			    	$query->query_vars['product_type'] = '';
			    	$meta_query = array(
			    		'relation' => 'OR',
						array(
							'key' => '_nyp',
							'value' => 'yes',
							'compare' => '=',
						),
						array(
							'key' => '_has_nyp',
							'value' => 'yes',
							'compare' => '='
						)
					);
					$query->query_vars['meta_query'] = $meta_query;
			    } 
		    }
		}
	}


    /*-----------------------------------------------------------------------------------*/
	/* Quick Edit */
	/*-----------------------------------------------------------------------------------*/

	/*
	 * Display the column content
	 *
	 * @param string $column_name
	 * @param int $post_id
	 * @return print HTML
	 * @since 1.0
	 */
	public static function column_display( $column_name, $post_id ) {

		switch ( $column_name ) {

			case 'price' :

				/* Custom inline data for nyp */
				$nyp = get_post_meta( $post_id, '_nyp', true );
				
				$suggested = WC_Name_Your_Price_Helpers::get_suggested_price( $post_id );
				$suggested = wc_nyp_format_localized_price( $suggested );

				$min = WC_Name_Your_Price_Helpers::get_minimum_price( $post_id );
				$min = wc_nyp_format_localized_price( $min );

				$is_nyp_allowed = has_term( array( 'simple' ), 'product_type', $post_id ) ? 'yes' : 'no';

				echo '
					<div class="hidden" id="nyp_inline_' . $post_id . '">
						<div class="nyp">' . $nyp . '</div>
						<div class="suggested_price">' . $suggested . '</div>
						<div class="min_price">' . $min . '</div>
						<div class="is_nyp_allowed">' . $is_nyp_allowed . '</div>
					</div>
				';

			break;


		}

	}

	/*
	 * Add quick edit fields
	 *
	 * @return print HTML
	 * @since 1.0
	 */

	public static function quick_edit() {  ?>

		<style>
			.inline-edit-row fieldset .nyp_prices span.title { line-height: 1; }
			.inline-edit-row fieldset .nyp_prices label { overflow: hidden; }
		</style>
		    <div id="nyp-fields" class="inline-edit-col-left">

		    	<br class="clear" />

			   	<h4><?php _e( 'Name Your Price', 'wc_name_your_price' ); ?>  <input type="checkbox" name="_nyp" class="nyp" value="1" /></h4>

			    <div class="nyp_prices">
			    	<label>
			            <span class="title"><?php _e( 'Suggested Price', 'wc_name_your_price' ); ?></span>
			            <span class="input-text-wrap">
			            	<input type="text" name="_suggested_price" class="text suggested_price" placeholder="<?php _e( 'Suggested Price', 'wc_name_your_price' ); ?>" value="">
			            </span>
			        </label>
			        <label>
			            <span class="title"><?php _e( 'Minimum Price', 'wc_name_your_price' ); ?></span>
			            <span class="input-text-wrap">
			            	<input type="text" name="_min_price" class="text min_price" placeholder="<?php _e( 'Minimum price', 'wc_name_your_price' ); ?>" value="">
			        	</span>
			        </label>
			    </div>

			</div>

	  <?php
	}

	/*
	 * Load the scripts for dealing with the quick edit
	 *
	 * @param string $hook
	 * @return void
	 * @since 1.0
	 */
	public static function quick_edit_scripts( $hook ) {
		global $post_type;

		if ( $hook == 'edit.php' && $post_type == 'product' ){
			$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
 			wp_enqueue_script( 'nyp-quick-edit', WC_Name_Your_Price()->plugin_url() . '/includes/admin/js/nyp-quick-edit'. $suffix .'.js', array( 'jquery' ), WC_Name_Your_Price()->version, true );
		}
	
	}

	/*
	 * Save quick edit changes
	 *
	 * @param object $product
	 * @return void
	 * @since 1.0
	 * @since 2.0 modified to only work in WC 2.1
	 * 
	 */
	public static function quick_edit_save( $product ) {
		global $woocommerce, $wpdb;

		if( isset( $product->id ) ){
			$product_id = $product->id;
		} else {
			return $product;
		}

		// Save fields

		if( isset( $_REQUEST['_nyp'] ) ) {
			update_post_meta( $product_id, '_nyp', 'yes' ); 
		} else {
			update_post_meta( $product_id, '_nyp', 'no' );
		}

		if ( isset( $_REQUEST['_suggested_price'] ) ) {
			$suggested = ( trim( $_REQUEST['_suggested_price'] ) === '' ) ? '' : wc_nyp_format_decimal( $_REQUEST['_suggested_price'] );
			update_post_meta( $product_id, '_suggested_price', $suggested );
		} 
		
		if ( isset( $_REQUEST['_min_price'] ) ) {
			$min = ( trim( $_REQUEST['_min_price'] ) === '' ) ? '' : wc_nyp_format_decimal( $_REQUEST['_min_price'] );
			update_post_meta( $product_id, '_min_price', $min );
		} 

	}


	/*-----------------------------------------------------------------------------------*/
	/* Admin Settings */
	/*-----------------------------------------------------------------------------------*/

	/*
	 * Add tab to settings
	 *
	 * @param array $tabs
	 * @return array
	 * @since 1.0
	 */

	public static function admin_tabs( $tabs ) {
		$tabs['nyp'] = __( 'Name Your Price', 'wc_name_your_price' );
		return $tabs;
	}

	 /**
	  * add_settings_fields
	  *
	  * Add settings fields for the nyp tab.
	  *
	  * @return void
	  * @since 1.0
	  */
	public static function add_settings_fields () {
	  	global $woocommerce_settings;

	  	$woocommerce_settings['nyp'] = apply_filters('woocommerce_nyp_settings', array(

			array( 'title' => __( 'Name Your Price Setup', 'wc_name_your_price' ), 'type' => 'title', 'desc' =>  __( 'Modify the text strings used by the Name Your Own Price extension.', 'wc_name_your_price' ), 'id' => 'woocommerce_nyp_options' ),

			array(
				'title' => __( 'Suggested Price Text', 'wc_name_your_price' ),
				'desc' 		=> __( 'This is the text to display before the suggested price.', 'wc_name_your_price' ),
				'id' 		=> 'woocommerce_nyp_suggested_text',
				'type' 		=> 'text',
				'css' 		=> 'min-width:300px;',
				'default'	=> _x( 'Suggested Price:', 'suggested price', 'wc_name_your_price' ),
				'desc_tip'	=>  true,
			),

			array(
				'title' => __( 'Minimum Price Text', 'wc_name_your_price' ),
				'desc' 		=> __( 'This is the text to display before the minimum accepted price.', 'wc_name_your_price' ),
				'id' 		=> 'woocommerce_nyp_minimum_text',
				'type' 		=> 'text',
				'css' 		=> 'min-width:300px;',
				'default'	=> _x( 'Minimum Price:', 'minimum price', 'wc_name_your_price' ),
				'desc_tip'	=>  true,
			),

			array(
				'title' => __( 'Name Your Price Text', 'wc_name_your_price' ),
				'desc' 		=> __( 'This is the text that appears above the Name Your Price input field.', 'wc_name_your_price' ),
				'id' 		=> 'woocommerce_nyp_label_text',
				'type' 		=> 'text',
				'css' 		=> 'min-width:300px;',
				'default'	=> __( 'Name Your Price', 'wc_name_your_price' ),
				'desc_tip'	=>  true,
			),

			array(
				'title' => __( 'Add to Cart Button Text for Shop', 'wc_name_your_price' ),
				'desc' 		=> __( 'This is the text that appears on the Add to Cart buttons on the Shop Pages.', 'wc_name_your_price' ),
				'id' 		=> 'woocommerce_nyp_button_text',
				'type' 		=> 'text',
				'css' 		=> 'min-width:300px;',
				'default'		=> __( 'Set Price', 'wc_name_your_price' ),
				'desc_tip'	=>  true,
			),

			array(
				'title' => __( 'Add to Cart Button Text for Single Product', 'wc_name_your_price' ),
				'desc' 		=> __( 'This is the text that appears on the Add to Cart buttons on the Single Product Pages.', 'wc_name_your_price' ),
				'id' 		=> 'woocommerce_nyp_button_text_single',
				'type' 		=> 'text',
				'css' 		=> 'min-width:300px;',
				'default'		=> __( 'Add to Cart', 'wc_name_your_price' ),
				'desc_tip'	=>  true,
			),

			array( 'type' => 'sectionend', 'id' => 'woocommerce_nyp_options' ),

			array( 'title' => __( 'Name Your Price Style', 'wc_name_your_price' ), 'type' => 'title', 'wc_name_your_price', 'id' => 'woocommerce_nyp_style_options' ),

			array(
				'title' => __( 'Disable Name Your Price Stylesheet', 'wc_name_your_price' ),
				'id' 		=> 'woocommerce_nyp_disable_css',
				'type' 		=> 'checkbox',
				'default'		=> 'no'
			),

			array( 'type' => 'sectionend', 'id' => 'woocommerce_nyp_style_options' ),

		)); // End nyp settings

	} // End add_settings_fields()


	/*
	 * Display the plugin's options
	 *
	 * @return print HTML
	 * @since 1.0
	 */
	public static function admin_panel() {

		if( ! current_user_can( 'manage_options' ) ){

			echo '<p>'. __( 'You do not have sufficient permissions to access this page.', 'wc_name_your_price') . '</p>';

		} else {

			global $woocommerce_settings;

			self::add_settings_fields();

			woocommerce_admin_fields( $woocommerce_settings['nyp'] );

		}
	}

	/*
	 * Save the plugin's options
	 *
	 * @return void
	 * @since 1.0
	 */
	public static function process_admin_options(){
		global $woocommerce_settings;

		// Make sure our settings fields are recognised.
		self::add_settings_fields();

		// Save settings
		woocommerce_update_options( $woocommerce_settings['nyp'] );

	}

	/*
	 * Include the settings page class
	 * compatible with WooCommerce 2.1
	 *
	 * @param array $settings ( the included settings pages )
	 * @return array
	 * @since 2.0
	 */
	public static function add_settings_page( $settings ) {

		$settings[] = include( 'class-wc-settings-nyp.php' );
		
		return $settings;
	}

}
WC_Name_Your_Price_Admin::init();