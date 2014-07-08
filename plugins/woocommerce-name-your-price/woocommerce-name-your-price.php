<?php
/*
Plugin Name: WooCommerce Name Your Price
Plugin URI: http://www.woothemes.com/products/name-your-price/
Description: WooCommerce Name Your Price allows customers to set their own price for products or donations.
Version: 2.0.3
Author: Kathy Darling
Author URI: http://kathyisawesome.com
Requires at least: 3.8
Tested up to: 3.9.1

Copyright: © 2012 Kathy Darling.
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

*/

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '31b4e11696cd99a3c0572975a84f1c08', '18738' );

// Quit right now if WooCommerce is not active
if ( ! is_woocommerce_active() )
	return;


/**
 * The Main WC_Name_Your_Price class
 **/
if ( ! class_exists( 'WC_Name_Your_Price' ) ) :

class WC_Name_Your_Price {

	/**
	 * @var WC_Name_Your_Price - the single instance of the class
	 * @since 2.0
	 */
	protected static $_instance = null;           

	/**
	 * @var plugin version
	 * @since 2.0
	 */
	public $version = '2.0.2';   

	/**
	 * Main WC_Name_Your_Price Instance
	 *
	 * Ensures only one instance of WC_Name_Your_Price is loaded or can be loaded.
	 *
	 * @static
	 * @see WC_Name_Your_Price()
	 * @return WC_Name_Your_Price - Main instance
	 * @since 2.0
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 2.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '2.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 2.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '2.0' );
	}
  
	/**
	 * WC_Name_Your_Price Constructor
	 *
	 * @access public
     * @return WC_Name_Your_Price
	 * @since 1.0
	 */

	public function __construct() { 

		// Load translation files
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

		// Include required files
		add_action( 'plugins_loaded', array( $this, 'includes' ) );

		// Settings Link for Plugin page
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_action_link' ), 10, 2 );

    }

	/*-----------------------------------------------------------------------------------*/
	/* Helper Functions */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Get the plugin url.
	 *
	 * @return string
	 * @since  2.0
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 * @since  2.0
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/*-----------------------------------------------------------------------------------*/
	/* Required Files */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Include required core files used in admin and on the frontend.
	 *
	 * @return void
	 * @since  1.0
	 */
	public function includes(){

		// include all helper functions
		include_once( 'includes/class-wc-name-your-price-helpers.php' );

		// include admin class to handle all backend functions
		if( is_admin() )
			include_once( 'includes/admin/class-name-your-price-admin.php' );

		// include the front-end functions
		if ( ! is_admin() || defined('DOING_AJAX') ) {
			include_once( 'includes/class-wc-name-your-price-display.php' );
			$this->display = new WC_Name_Your_Price_Display();

			include_once( 'includes/class-wc-name-your-price-cart.php' );
			$this->cart = new WC_Name_Your_Price_Cart();
		}

		if ( WC_Name_Your_Price_Helpers::is_woocommerce_2_1() ) {
			include_once( 'includes/wc-21-functions.php' );
		} else {
			include_once( 'includes/wc-20-functions.php' );
		}

	}

	/*-----------------------------------------------------------------------------------*/
	/* Localization */
	/*-----------------------------------------------------------------------------------*/


	/**
	 * Make the plugin translation ready
	 *
	 * @return void
	 * @since  1.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'wc_name_your_price' , false , dirname( plugin_basename( __FILE__ ) ) .  '/languages/' );
	}

	/*-----------------------------------------------------------------------------------*/
	/* Plugins Page */
	/*-----------------------------------------------------------------------------------*/

	/*
	 * 'Settings' link on plugin page
	 *
	 * @param array $links
	 * @return array
	 * @since 1.0
	 */

	public function add_action_link( $links ) {

		if ( WC_Name_Your_Price_Helpers::is_woocommerce_2_1() ) {
			$settings_link = '<a href="'.admin_url('admin.php?page=wc-settings&tab=nyp').'" title="'.__('Go to the settings page', 'wc_name_your_price').'">'.__( 'Settings', 'wc_name_your_price' ).'</a>';
		} else {
			$settings_link = '<a href="'.admin_url('admin.php?page=woocommerce&tab=nyp').'" title="'.__('Go to the settings page', 'wc_name_your_price').'">'.__( 'Settings', 'wc_name_your_price' ).'</a>';
		}

		return array_merge( (array) $settings_link, $links );

	}


	/*-----------------------------------------------------------------------------------*/
	/* Deprecated Functions */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * display_minimum_price function
	 *
	 * @deprecated As of 2.0, function is now in display class and global replaced with instance
	 * @access public
	 */
	public function display_minimum_price() {
		_deprecated_function( 'display_minimum_price', '2.0', 'WC_Name_Your_Price()->display->display_minimum_price' );
		return $this->display->display_minimum_price();
	}

	/**
	 * display_price_input function
	 *
	 * @deprecated As of 2.0, function is now in display class and global replaced with instance
	 * @access public
	 */
	public function display_price_input() { 
		_deprecated_function( 'display_price_input', '2.0', 'WC_Name_Your_Price()->display->display_price_input' );
		return $this->display->display_price_input();
	}

	/**
	 * nyp_style function
	 *
	 * @deprecated As of 2.0, function is now in display class and global replaced with instance
	 * @access public
	 */
	public function nyp_style() {
		_deprecated_function( 'nyp_style', '2.0', 'WC_Name_Your_Price()->display->nyp_style' );
		return $this->display->nyp_style();
	}

} //end class: do not remove or there will be no more guacamole for you

endif; // end class_exists check


/**
 * Returns the main instance of WC_Name_Your_Price to prevent the need to use globals.
 *
 * @since  2.0
 * @return WooCommerce
 */
function WC_Name_Your_Price() {
  return WC_Name_Your_Price::instance();
}

// Launch the whole plugin w/ a little backcompat
$GLOBALS['wc_name_your_price'] = WC_Name_Your_Price();