<?php
/*
	Plugin Name: WooCommerce Software Add-On
	Plugin URI: http://woothemes.com/woocommerce
	Description: Extends WooCommerce to a full-blown software shop, including license activation, license retrieval, activation e-mails and more. Requires WooCommerce 1.5.6+
	Version: 1.2.3
	Author: WooThemes
	Author URI: http://www.woothemes.com

	Copyright: © 2009-2012 WooThemes.
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html

	Partly based on the software addon by Joachim Kudish.
*/

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '79f6dbfe1f1d3a56a86f0509b6d6b04b', '18683' );

if ( is_woocommerce_active() ) {

	if ( class_exists( 'WC_Software' ) ) return;

    /**
     * Localisation
     **/
    load_plugin_textdomain( 'wc_software', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	/**
	 * WC_Software class.
	 */
	class WC_Software {

		var $api_url;
		var $plugin_url;
		var $plugin_path;
		var $messages = array();

		/**
		* class constructor
		* plugin activation, hooks & filters, etc..
		*
		* @since 1.0
		* @return void
		*/
		function __construct() {

			// API
			$this->api_url = add_query_arg( 'wc-api', 'software-api', site_url() );

			// Include requires functions and classes
			$this->includes();

			// API hook
			add_action( 'woocommerce_api_software-api', array( $this, 'handle_api_request' ) );

			// Hooks
			add_action( 'woocommerce_admin_css', array( $this, 'admin_styles' ) );
			add_action( 'woocommerce_order_status_completed', array( $this, 'order_complete' ) );
			add_action( 'woocommerce_email_before_order_table', array( $this, 'email_keys' ) );
			add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'upgrade_form' ) );

			// Filters for checkout actions
			add_action( 'woocommerce_checkout_order_processed', array( $this, 'save_update_key' ) );

			// Filters for cart actions
			add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), 10, 2 );
			add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session' ), 10, 2 );
			add_filter( 'woocommerce_get_item_data', array( $this, 'get_item_data' ), 10, 2 );
			add_filter( 'woocommerce_add_cart_item', array( $this, 'add_cart_item' ), 10, 1 );
			add_action( 'woocommerce_order_item_meta', array( $this, 'order_item_meta' ), 10, 2 );
			add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_add_cart_item' ), 10, 3 );

			// AJAX
			add_action( 'wp_ajax_woocommerce_lost_licence', array( $this, 'lost_licence_ajax' ) );
			add_action( 'wp_ajax_nopriv_woocommerce_lost_licence', array( $this, 'lost_licence_ajax' ) );

			// Shortcodes
			add_shortcode( 'woocommerce_software_lost_license', array( $this, 'lost_license_page' ) );

	        // Add menu
	        add_action( 'admin_menu', array( $this, 'menu' ));
	    }

	    function menu() {
	    	add_submenu_page( 'woocommerce', __( 'Licence Keys', 'wc_software' ),  __( 'Licence Keys', 'wc_software' ), 'manage_woocommerce', 'wc_software_keys', array( $this, 'licence_key_page' ) );
	    }

		function licence_key_page() {
			global $woocommerce;

		    $WC_Software_Key_Admin = new WC_Software_Key_Admin();
		    $WC_Software_Key_Admin->prepare_items();

		    ?>
		    <div class="wrap">

		        <div id="icon-woocommerce" class="icon32 icon32-posts-product"><br/></div>
		        <h2><?php _e( 'Licence Keys', 'wc_software' ); ?></h2>
		        <form id="stock-management" method="post">

		        	<?php
		        		if ( $this->messages ) {

		        			echo '<div class="updated">';

		        			foreach ( $this->messages as $message ) {
		        				echo '<p>' . $message . '</p>';
		        			}

		        			echo '</div>';

		        		}

		        		wp_nonce_field( 'save', 'wc-stock-management' );
		        	?>
		            <input type="hidden" name="page" value="wc_software" />
		            <?php $WC_Software_Key_Admin->display() ?>
		        </form>

		    </div>
		    <?php
		}


		/**
		 * includes function.
		 */
		function includes() {
			if ( is_admin() ) {
				include_once( 'classes/class-wc-software-key-admin.php' );
				include_once( 'classes/class-wc-software-reports.php' );
				include_once( 'classes/class-wc-software-order-admin.php' );
				include_once( 'classes/class-wc-software-product-admin.php' );
			}
		}

		/**
		 * handle_api_request function.
		 *
		 * @access public
		 * @return void
		 */
		function handle_api_request() {
			include_once( 'classes/class-wc-software-api.php' );
			die;
		}

		/**
		 * admin_scripts function.
		 */
		function admin_styles() {
			wp_enqueue_style( 'woocommerce_software_admin_styles', $this->plugin_url() . '/assets/css/admin.css' );
		}

		/**
		 * runs various functions when the plugin first activates
		 *
		 * @see register_activation_hook()
		 * @link http://codex.wordpress.org/Function_Reference/register_activation_hook
		 * @since 1.0
		 * @return void
		 */
		function activation() {
			global $wpdb, $woocommerce;

			$lost_license_page_id = get_option( 'woocommerce_lost_license_page_id' );

			// Creates the lost license page with the right shortcode in it
			$slug = 'lost-licence';
			$found = $wpdb->get_var( "SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '$slug' LIMIT 1;" );

			if ( empty( $lost_license_page_id ) || ! $found ) {
				$lost_license_page = array(
					'post_title' 	=> _x( 'Lost License', 'Title of a page', 'wc_software' ),
					'post_content' 	=> '[woocommerce_software_lost_license]',
					'post_status' 	=> 'publish',
					'post_type' 	=> 'page',
					'post_name' 	=> $slug,
				);
				$lost_license_page_id = (int) wp_insert_post( $lost_license_page );
				update_option( 'woocommerce_lost_license_page_id', $lost_license_page_id );
			}

			// Create database tables
			$wpdb->hide_errors();

			$collate = '';
		    if( $wpdb->has_cap( 'collation' ) ) {
				if( ! empty($wpdb->charset ) ) $collate .= "DEFAULT CHARACTER SET $wpdb->charset";
				if( ! empty($wpdb->collate ) ) $collate .= " COLLATE $wpdb->collate";
		    }

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php' );

		    // Table for storing licence keys for purchases
		    $sql = "
CREATE TABLE ". $wpdb->prefix . "woocommerce_software_licences (
  key_id bigint(20) NOT NULL auto_increment,
  order_id bigint(20) NOT NULL DEFAULT 0,
  activation_email varchar(200) NOT NULL,
  licence_key varchar(200) NOT NULL,
  software_product_id varchar(200) NOT NULL,
  software_version varchar(200) NOT NULL,
  activations_limit varchar(9) NULL,
  created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
  PRIMARY KEY  (key_id)
) $collate;
			";
			dbDelta($sql);

		    // Table for tracking licence key activations
		    $sql = "
CREATE TABLE ". $wpdb->prefix . "woocommerce_software_activations (
  activation_id bigint(20) NOT NULL auto_increment,
  key_id bigint(20) NOT NULL,
  instance varchar(200) NOT NULL,
  activation_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
  activation_active int(1) NOT NULL DEFAULT 1,
  activation_platform varchar(200) NULL,
  PRIMARY KEY  (activation_id)
) $collate;
			";
			dbDelta($sql);

		}

		/**
		 * order_complete function.
		 *
		 * Order is complete - give out any licence codes!
		 */
		function order_complete( $order_id ) {
			global $wpdb;

			if ( get_post_meta( $order_id, 'software_processed', true ) == 1 ) return; // Only do this once

			$order = new WC_Order( $order_id );

			if ( sizeof( $order->get_items() ) > 0 ) {

				foreach ( $order->get_items() as $item ) {

					$item_product_id = ( isset( $item['product_id'] ) ) ? $item['product_id'] : $item['id'];

					if ( $item_product_id > 0 ) {

						$meta = get_post_custom( $item_product_id );

						if ( $meta['_is_software'][0] == 'yes' ) {

							$quantity = isset( $item['item_meta']['_qty'][0] ) ? absint( $item['item_meta']['_qty'][0] ) : 1;

							// FOUND SOME SOFTWARE - Lets make those licences!
							for ( $i = 0; $i < $quantity; $i++ ) {
				                $data = array(
									'order_id' 				=> $order_id,
									'activation_email'		=> $order->billing_email,
									'prefix'				=> empty( $meta['_software_license_key_prefix'][0] ) ? '' : $meta['_software_license_key_prefix'][0],
									'software_product_id'	=> empty( $meta['_software_product_id'][0] ) ? '' : $meta['_software_product_id'][0],
									'software_version'		=> empty( $meta['_software_version'][0] ) ? '' : $meta['_software_version'][0],
									'activations_limit'		=> empty( $meta['_software_activations'][0] ) ? '' : (int) $meta['_software_activations'][0],
						        );

								$key_id = $this->save_licence_key( $data );
							}
						}

					}

				}

			}

			update_post_meta( $order_id,  'software_processed', 1);

		}

		/**
		 * email_keys function.
		 *
		 * @access public
		 * @return void
		 */
		function email_keys( $order ) {
			global $wpdb;

			$licence_keys = $wpdb->get_results( "
				SELECT * FROM {$wpdb->prefix}woocommerce_software_licences
				WHERE order_id = {$order->id}
			" );

			woocommerce_get_template( 'email-keys.php', array(
				'keys'	=> $licence_keys
			), 'woocommerce-software', $this->plugin_path() . '/templates/' );

		}

		/**
		 * upgrade_form function.
		 *
		 * @access public
		 * @return void
		 */
		function upgrade_form() {
			global $product, $post, $woocommerce;

			$is_software 			= get_post_meta( $post->ID, '_is_software', true );
			$software_product_id 	= get_post_meta( $post->ID, '_software_product_id', true );
			$price					= get_post_meta( $post->ID, '_software_upgrade_price', true );
			$upgradable_product		= get_post_meta( $post->ID, '_software_upgradable_product', true );

			if ( $is_software == 'yes' && $software_product_id && $price && $upgradable_product ) {

				woocommerce_get_template( 'upgrade-form.php', array(
					'software_product_id' 	=> $software_product_id,
					'prefix'				=> get_post_meta( $post->ID, '_software_license_key_prefix', true ),
					'price'					=> $price,
					'upgradable_product'	=> $upgradable_product
				), 'woocommerce-software', $this->plugin_path() . '/templates/' );

			}
		}

		/** Checkout actions ************************************************************/

		/**
		 * save_update_key function.
		 *
		 * @access public
		 * @return void
		 */
		function save_update_key( $order_id ) {
			global $woocommerce;

			foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {

				if ( isset( $values['software_upgrade'] ) && $values['software_upgrade'] ) {

					$used_keys 		= array_filter( array_map( 'trim', explode( ',', get_post_meta( $values['product_id'], '_software_used_license_keys', true ) ) ) );
					$license_keys 	= array_filter( array_map( 'trim', explode( ',', get_post_meta( $values['product_id'], '_software_upgrade_license_keys', true ) ) ) );

					$used_keys[] = $values['software_upgrade_key'];
					unset( $license_keys[ array_search( $values['software_upgrade_key'], $license_keys ) ] );

					update_post_meta( $values['product_id'], '_software_used_license_keys', implode( ', ', $used_keys ) );
					update_post_meta( $values['product_id'], '_software_upgrade_license_keys', implode( ', ', $license_keys ) );

				}

			}
		}

		/** Add to cart actions ************************************************************/

		function validate_add_cart_item( $passed, $product_id, $qty ) {
			global $woocommerce;

			$is_software = get_post_meta( $product_id, '_is_software', true );

            if ( ! empty( $_POST['activation_email'] ) && ! empty( $_POST['licence_key'] ) && $is_software == 'yes' ) {
				// Check the posted key
				if ( empty( $_POST['licence_key'] ) ) {
					$woocommerce->add_error( __( 'Please enter your upgrade key!', 'wc_software' ) );
					return false;
				}
				if ( empty( $_POST['activation_email'] ) ) {
					$woocommerce->add_error( __( 'Please enter your activation email address!', 'wc_software' ) );
					return false;
				}

				$licence_key 	= esc_attr( stripslashes( trim( $_POST['licence_key'] ) ) );
				$email_address 	= esc_attr( stripslashes( trim( $_POST['activation_email'] ) ) );

				if ( ! is_email( $email_address ) ) {
					$woocommerce->add_error( __( 'Please enter a valid activation email address.', 'wc_software' ) );
					return false;
				}

				// CHECK VALID!
				if ( $this->is_used_upgrade_key( $licence_key, $product_id ) ) {
					$woocommerce->add_error( __( 'This upgrade key has been used already. If you need assistance please contact us.', 'wc_software' ) );
					return false;
				}

				if ( ! $this->is_valid_upgrade_key( $licence_key, $product_id ) ) {
					$woocommerce->add_error( __( 'This upgrade key is not valid. If you need assistance please contact us.', 'wc_software' ) );
					return false;
				}

			}

			return $passed;
		}

		function add_cart_item_data( $cart_item_meta, $product_id ) {
			global $woocommerce;

			$is_software = get_post_meta( $product_id, '_is_software', true );

			if ( ! empty( $_POST['activation_email'] ) && ! empty( $_POST['licence_key'] ) && $is_software == 'yes' ) {

				$cart_item_meta['software_upgrade'] = true;

				$licence_key 	= esc_attr( stripslashes( trim( $_POST['licence_key'] ) ) );
				$email_address 	= esc_attr( stripslashes( trim( $_POST['activation_email'] ) ) );

				$cart_item_meta['software_upgrade_key'] = $licence_key;
				$cart_item_meta['software_activation_email'] = $email_address;

			}

			return $cart_item_meta;
		}

		function get_cart_item_from_session( $cart_item, $values ) {

			if ( isset( $values['software_upgrade'] ) ) {
				$cart_item['software_upgrade'] = true;
				$cart_item['software_upgrade_key'] = $values['software_upgrade_key'];
				$cart_item['software_activation_email'] = $values['software_activation_email'];

				$software_upgrade_price = get_post_meta( $cart_item['product_id'], '_software_upgrade_price', true );

				$cart_item['data']->price = $software_upgrade_price;
			}


			return $cart_item;

		}

		function get_item_data( $other_data, $cart_item ) {

			if ( isset( $cart_item['software_upgrade'] ) ) {

				$software_upgradable_product = get_post_meta( $cart_item['product_id'], '_software_upgradable_product', true );

				$other_data[] = array(
					'name' 		=> __( 'Upgrading from', 'wc_software' ),
					'value' 	=> $software_upgradable_product,
					'display' 	=> ''
				);

				$other_data[] = array(
					'name' 		=> __( 'Upgrade key', 'wc_software' ),
					'value' 	=> $cart_item['software_upgrade_key'],
					'display' 	=> ''
				);

				$other_data[] = array(
					'name' 		=> __( 'Upgrade email', 'wc_software' ),
					'value' 	=> $cart_item['software_activation_email'],
					'display' 	=> ''
				);

			}

			return $other_data;
		}

		function add_cart_item( $cart_item ) {

			// Adjust price if addons are set
			if ( isset( $cart_item['software_upgrade'] ) ) {

				$software_upgrade_price = get_post_meta( $cart_item['product_id'], '_software_upgrade_price', true );

				if ( $software_upgrade_price !== '' ) {

					// @TODO Add set_price method to core
					$cart_item['data']->price = $software_upgrade_price;

				}

			}

			return $cart_item;
		}

		function order_item_meta( $item_meta, $cart_item ) {

			// Add the fields
			if ( isset( $cart_item['software_upgrade'] ) ) {

				$software_upgradable_product = get_post_meta( $cart_item['product_id'], '_software_upgradable_product', true );

				$item_meta->add( __( 'Upgrading from', 'wc_software' ), $software_upgradable_product );
				$item_meta->add( __( 'Upgrade key', 'wc_software' ), $cart_item['software_upgrade_key'] );
				$item_meta->add( __( 'Upgrade email', 'wc_software' ), $cart_item['software_activation_email'] );

			}
		}

		/** AJAX ************************************************************/

		function lost_licence_ajax() {
			global $woocommerce, $wpdb;

			check_ajax_referer( 'wc-lost-licence', 'security' );

			$email = esc_attr( trim( $_POST['email'] ) );

			if ( ! is_email( $email ) )
				die( json_encode( array(
					'success' 	=> false,
					'message'	=> __( 'Invalid Email Address', 'wc_software' )
				) ) );

			$licence_keys = $wpdb->get_results( "
				SELECT * FROM {$wpdb->prefix}woocommerce_software_licences
				WHERE activation_email = '{$email}'
			" );

			if ( sizeof( $licence_keys ) > 0 ) {

				ob_start();

				$mailer = $woocommerce->mailer();

				woocommerce_get_template( 'email-lost-keys.php', array(
					'keys'	=> $licence_keys,
					'email_heading' => __( 'Your licence keys', 'wc_software' )
				), 'woocommerce-software', $this->plugin_path() . '/templates/' );

				$message = ob_get_clean();

				woocommerce_mail( $email, __( 'Your licence keys', 'wc_software' ), $message );

				die( json_encode( array(
					'success' 	=> true,
					'message'	=> __( 'Your licence keys have been emailed', 'wc_software' )
				) ) );

			} else {

				die( json_encode( array(
					'success' 	=> false,
					'message'	=> __( 'No licence keys were found for your email address', 'wc_software' )
				) ) );

			}

		}

		/** Shortcodes ************************************************************/

		/**
		 * lost_license_page function.
		 *
		 * @access public
		 */
		function lost_license_page() {

			woocommerce_get_template( 'lost-license.php', '', 'woocommerce-software', $this->plugin_path() . '/templates/' );

		}

		/** Helper functions ******************************************************/

		/**
		 * Get the plugin url
		 */
		function plugin_url() {
			if ( $this->plugin_url ) return $this->plugin_url;
			return $this->plugin_url = plugins_url( basename( plugin_dir_path(__FILE__) ), basename( __FILE__ ) );
		}

		/**
		 * Get the plugin path
		 */
		function plugin_path() {
			if ( $this->plugin_path ) return $this->plugin_path;

			return $this->plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) );
		}

		/**
		 * activations_remaining function.
		 *
		 * @access public
		 * @param mixed $key
		 * @return int
		 */
		function activations_remaining( $key ) {
			global $wpdb;

			$key = (int) $key;

			if ( ! $key ) return 0;

			$activations_limit = $wpdb->get_var( "SELECT activations_limit FROM {$wpdb->prefix}woocommerce_software_licences WHERE key_id = {$key};" );

			if ( NULL == $activations_limit || 0 == $activations_limit ) {
				return 999999999;
			}

			$active_activations = $wpdb->get_var( "SELECT COUNT(activation_id) FROM {$wpdb->prefix}woocommerce_software_activations WHERE key_id = {$key} AND activation_active = 1;" );

			$remaining =  $activations_limit - $active_activations;

			if ( $remaining < 0 ) $remaining = 0;

			return $remaining;
		}

		/** 
		* Return stored platform for activations
		* 
		* @access public 
		* @param int $key_id
		* @return string value stored as platform for the activation 
		*/ 
		function get_platform( $key_id ) { 
			global $wpdb;

			$key_id = absint( $key_id );

			if ( ! $key_id ) {
				return 0;
			}

			return $wpdb->get_var( "SELECT activation_platform FROM {$wpdb->prefix}woocommerce_software_activations WHERE key_id = {$key_id};" ); 
		}

        /**
         * Resets the platform for all activations of a specific key
         *
         * @param $key_id Integer id of the key
         * @return int Number of rows affected
         */
        function reset_platform_for_key( $key_id ) {
            global $wpdb;

            $key_id = absint( $key_id );

            if ( ! $key_id ) {
                return 0;
            }

            return $wpdb->update(
                $wpdb->prefix . 'woocommerce_software_activations',
                array(
                    'activation_platform' => ''
                ),
                array( 'key_id' => $key_id ),
                array( '%s' ),
                array( '%d' )
            );
        }

		/**
		 * checks if a key is a valid upgrade key for a particular product
		 *
		 * @since 1.0
		 * @param string $key the key to validate
		 * @param int $item_id the product to validate for
		 * @return bool valid key or not
		 */
		function is_valid_upgrade_key( $key = null, $item_id = null ) {
			if ( $key && $item_id ) {
				$_software_upgrade_license_keys = array_filter( array_map( 'trim', explode( ',', get_post_meta( $item_id, '_software_upgrade_license_keys', true ) ) ) );

				if ( in_array( $key, $_software_upgrade_license_keys ) )
					return true;
			}
			return false;
		}

		/**
		 * checks if a key is a used upgrade key for a particular product
		 *
		 * @since 1.0
		 * @param string $key the key to validate
		 * @param int $item_id the product to validate for
		 * @return bool valid key or not
		 */
		function is_used_upgrade_key( $key = null, $item_id = null ) {
			if ( $key && $item_id ) {
				$_software_used_license_keys = array_filter( array_map( 'trim', explode( ',', get_post_meta( $item_id, '_software_used_license_keys', true ) ) ) );

				if ( in_array( $key, $_software_used_license_keys ) )
					return true;
			}
			return false;
		}

		/**
		 * save_licence_key function.
		 *
		 * @access public
		 * @return void
		 */
		function save_licence_key( $data ) {
			global $wpdb;

			$defaults = array(
				'order_id' 				=> '',
				'activation_email' 		=> '',
				'prefix'				=> '',
				'licence_key' 			=> $this->generate_licence_key(),
				'software_product_id' 	=> '',
				'software_version'		=> '',
				'activations_limit'		=> '',
				'created'				=> current_time( 'mysql' )
			);

			$data = wp_parse_args( $data, $defaults  );

			$insert = array(
				'order_id' 				=> $data['order_id'],
				'activation_email'		=> $data['activation_email'],
				'licence_key'			=> $data['prefix'] . $data['licence_key'],
				'software_product_id'	=> $data['software_product_id'],
				'software_version'		=> $data['software_version'],
				'activations_limit'		=> $data['activations_limit'],
				'created'				=> $data['created']
	        );

	        $format = array(
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s'
	        );

	        $wpdb->insert( $wpdb->prefix . 'woocommerce_software_licences',
	            $insert,
	            $format
	        );

			return $wpdb->insert_id;
		}

		/**
		 * generates a unique id that is used as the license code
		 *
		 * @since 1.0
		 * @return string the unique ID
		 */
		function generate_licence_key() {

			return sprintf(
				'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
				mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
				mt_rand( 0, 0x0fff ) | 0x4000,
				mt_rand( 0, 0x3fff ) | 0x8000,
				mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
			);
		}

		/**
		 * check_product_secret function.
		 *
		 * @access public
		 * @param mixed $software_product_id
		 * @param mixed $secret_key
		 * @return void
		 */
		public function check_product_secret( $software_product_id, $secret_key ) {
			global $wpdb;

			$product_id = $wpdb->get_var( $wpdb->prepare( "
				SELECT post_id FROM {$wpdb->postmeta}
				WHERE meta_key = '_software_product_id'
				AND meta_value = %s LIMIT 1
			", $software_product_id ) );

			if ( ! $product_id ) return false;

			$product_secret_key = get_post_meta( $product_id, '_software_secret_product_key', true );

			if ( $product_secret_key == $secret_key )
				return true;

			return false;
		}

		/**
		 * get_licence_key function.
		 *
		 * @access public
		 * @param mixed $licence_key
		 * @param mixed $software_product_id
		 * @param mixed $email
		 * @return bool
		 */
		public function get_licence_key( $licence_key, $software_product_id, $email ) {
			global $wpdb;

			$key = $wpdb->get_row( $wpdb->prepare( "
				SELECT * FROM {$wpdb->prefix}woocommerce_software_licences
				WHERE licence_key = %s
				AND software_product_id = %s
				AND activation_email = %s
				LIMIT 1
			", $licence_key, $software_product_id, $email ) );

			return $key;
		}

		/**
		 * get_licence_activations function.
		 *
		 * @access public
		 * @param mixed $licence_key
		 * @param mixed $activation_id
		 * @return void
		 */
		public function get_licence_activations( $licence_key ) {
			global $wpdb;

			$licenses = $wpdb->get_results( $wpdb->prepare( "
				SELECT * FROM {$wpdb->prefix}woocommerce_software_activations as activations
				LEFT JOIN {$wpdb->prefix}woocommerce_software_licences as licences ON activations.key_id = licences.key_id
				WHERE licences.licence_key = %s
			", $licence_key ) );

			return $licenses;
		}

		/**
		 * deactivate_licence_key function.
		 *
		 * @access public
		 * @param mixed $key_id
		 * @param string $instance (default: '' )
		 * @return bool
		 */
		public function deactivate_licence_key( $key_id, $instance = '' ) {
			global $wpdb;

			$activation_ids = array();

			if ( ! $instance ) {
				$activation_ids = $wpdb->get_col( $wpdb->prepare( "
					SELECT activation_id
					FROM {$wpdb->prefix}woocommerce_software_activations
					WHERE key_id = %s
				", $key_id ) );
			} else {
				$activation_id = $wpdb->get_var( $wpdb->prepare( "
					SELECT activation_id
					FROM {$wpdb->prefix}woocommerce_software_activations
					WHERE key_id = %s
					AND instance = %s
				", $key_id, $instance ) );

				if ( $activation_id ) $activation_ids[] = $activation_id;
			}

			if ( $activation_ids ) {
				foreach ( $activation_ids as $activation_id ) {

					// UPDATE ACTIVATION
					$wpdb->update(
						$wpdb->prefix . 'woocommerce_software_activations',
						array(
							'activation_active' => '0'
						),
						array( 'activation_id' => $activation_id ),
						array( '%d' ),
						array( '%d' )
					);

				}
				return true;
			}
			return false;
		}

		/**
		 * activate_licence_key function.
		 *
		 * @access public
		 * @param mixed $key_id
		 * @param string $instance (default: '' )
		 * @param string $platform (default: '' )
		 * @return bool
		 */
		public function activate_licence_key( $key_id, $instance = '', $platform = '' ) {
			global $wpdb;

			// Find instance for licence key
			$activation_id = $wpdb->get_var( $wpdb->prepare( "
				SELECT activation_id
				FROM {$wpdb->prefix}woocommerce_software_activations
				WHERE key_id = %s
				AND instance = %s
			", $key_id, $instance ) );

			if ( $activation_id > 0 ) {

				// UPDATE ACTIVATION
				$wpdb->update(
					$wpdb->prefix . 'woocommerce_software_activations',
					array(
						'activation_active' => '1'
					),
					array( 'activation_id' => $activation_id ),
					array( '%d' ),
					array( '%d' )
				);

				return true;

	        } else {

				// NEW ACTIVATION
		        $insert = array(
					'key_id' 				=> $key_id,
					'instance'				=> $instance,
					'activation_time'		=> current_time( 'mysql' ),
					'activation_active'		=> 1,
					'activation_platform'	=> $platform
		        );

		        $format = array(
					'%d',
					'%s',
					'%s',
					'%d',
					'%s'
		        );

		        $wpdb->insert( $wpdb->prefix . 'woocommerce_software_activations',
		            $insert,
		            $format
		        );

		        return $wpdb->insert_id;

	        }

	        return false;

		}

	} // end class

	$GLOBALS['wc_software'] = new WC_Software(); // Init the main class

	// Hook into activation
	register_activation_hook( __FILE__, array( $GLOBALS['wc_software'], 'activation' ) );

}
