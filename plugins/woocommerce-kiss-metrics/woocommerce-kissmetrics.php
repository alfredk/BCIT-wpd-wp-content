<?php
/**
 * Plugin Name: WooCommerce KISSmetrics
 * Plugin URI: http://www.woothemes.com/products/kiss-metrics/
 * Description: Adds KISSmetrics tracking to WooCommerce with one click!
 * Author: SkyVerge
 * Author URI: http://www.skyverge.com
 * Version: 1.3
 * Text Domain: woocommerce-kiss-metrics
 * Domain Path: /i18n/languages
 *
 * Copyright: (c) 2012-2014 SkyVerge, Inc. (info@skyverge.com)
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WC-KISSmetrics
 * @author    SkyVerge
 * @category  Integration
 * @copyright Copyright (c) 2013-2014, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Required functions
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

// Plugin updates
woothemes_queue_update( plugin_basename( __FILE__ ), 'd4e3376922b693659e176e8ebc834104', '27146' );

// WC active check
if ( ! is_woocommerce_active() ) {
	return;
}

// Required library class
if ( ! class_exists( 'SV_WC_Framework_Bootstrap' ) ) {
	require_once( 'lib/skyverge/woocommerce/class-sv-wc-framework-bootstrap.php' );
}

SV_WC_Framework_Bootstrap::instance()->register_plugin( '2.0', __( 'WooCommerce KISSmetrics', 'woocommerce-kiss-metrics' ), __FILE__, 'init_woocommerce_kiss_metrics' );

function init_woocommerce_kiss_metrics() {


/**
 * # WooCommerce KISSmetrics Main Plugin Class
 *
 * ## Plugin Overview
 *
 * This plugin adds KISSmetrics tracking to many different WooCommerce events, like adding a product to the cart or completing
 * a purchase. Admins can control the name of the events and properties sent to KISSmetrics in the integration settings section.
 *
 * ## Admin Considerations
 *
 * The plugin is added as an integration, so all settings exist inside the integrations section (WooCommerce > Settings > Integrations)
 *
 * ## Frontend Considerations
 *
 * The KISSmetrics tracking javascript is added to the <head> of every page load
 *
 * ## Database
 *
 * ### Global Settings
 *
 * + `wc_kissmetrics_settings` - a serialized array of KISSmetrics integration settings, include API credentials and event/property names
 *
 * ### Options table
 *
 * + `wc_kissmetrics_version` - the current plugin version, set on install/upgrade
 *
 */
class WC_KISSmetrics extends SV_WC_Plugin {


	/** plugin version number */
	const VERSION = '1.3';

	/** plugin id */
	const PLUGIN_ID = 'kiss_metrics';

	/** plugin text domain */
	const TEXT_DOMAIN = 'woocommerce-kiss-metrics';


	/**
	 * Initializes the plugin
	 *
	 * @since 1.2
	 */
	public function __construct() {

		parent::__construct(
		  self::PLUGIN_ID,
		  self::VERSION,
		  self::TEXT_DOMAIN
		);

		// load integration
		add_action( 'sv_wc_framework_plugins_loaded', array( $this, 'includes' ) );
	}


	/**
	 * Include required files
	 *
	 * @since 1.2
	 */
	public function includes() {

		require( 'includes/class-wc-kissmetrics-integration.php' );

		add_filter( 'woocommerce_integrations', array( $this, 'load_integration' ) );
	}


	/**
	 * Add KISSmetrics to the list of integrations WooCommerce loads
	 *
	 * @since 1.2
	 */
	public function load_integration( $integrations ) {

		$integrations[] = 'WC_KISSmetrics_Integration';

		return $integrations;
	}


	/**
	 * Handle localization, WPML compatible
	 *
	 * @since 1.2
	 */
	public function load_translation() {

		load_plugin_textdomain( 'woocommerce-kiss-metrics', false, dirname( plugin_basename( $this->get_file() ) ) . '/i18n/languages' );
	}


	/** Helper methods ******************************************************/


	/**
	 * Returns the plugin name, localized
	 *
	 * @since 1.3
	 * @see SV_WC_Plugin::get_plugin_name()
	 * @return string the plugin name
	 */
	public function get_plugin_name() {

		return __( 'WooCommerce KISSmetrics', self::TEXT_DOMAIN );
	}


	/**
	 * Returns __FILE__
	 *
	 * @since 1.3
	 * @see SV_WC_Plugin::get_file()
	 * @return string the full path and filename of the plugin file
	 */
	protected function get_file() {

		return __FILE__;
	}


	/**
	 * Gets the plugin documentation url, which for Customer/Order CSV Export is non-standard
	 *
	 * @since 1.3
	 * @see SV_WC_Plugin::get_documentation_url()
	 * @return string documentation URL
	 */
	public function get_documentation_url() {

		return 'http://docs.woothemes.com/document/kiss-metrics/';
	}


	/**
	 * Gets the URL to the settings page
	 *
	 * @since 1.3
	 * @see SV_WC_Plugin::is_plugin_settings()
	 * @param string $_ unused
	 * @return string URL to the settings page
	 */
	public function get_settings_url( $_ = '' ) {

		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_2_1() ) {

			return admin_url( 'admin.php?page=wc-settings&tab=integration&section=kissmetrics');

		} else {

			return admin_url( 'admin.php?page=woocommerce_settings&tab=integration&section=kissmetrics' );
		}

	}


	/**
	 * Returns true if on the gateway settings page
	 *
	 * @since 1.3
	 * @see SV_WC_Plugin::is_plugin_settings()
	 * @return boolean true if on the settings page
	 */
	public function is_plugin_settings() {

		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_2_1() ) {

			return isset( $_GET['page'] ) && 'wc-settings' == $_GET['page'] &&
			isset( $_GET['tab'] ) && 'integration' == $_GET['tab'] &&
			isset( $_GET['section'] ) && 'kissmetrics' == $_GET['section'];

		} else {

			return isset( $_GET['page'] ) && 'woocommerce_settings' == $_GET['page'] &&
			isset( $_GET['tab'] ) && 'integration' == $_GET['tab'] &&
			isset( $_GET['section'] ) && 'kissmetrics' == $_GET['section'];
		}
	}


} // end \WC_KISSmetrics class


/**
 * The WC_KISSmetrics global object
 * @name $wc_kissmetrics
 * @global WC_KISSmetrics $GLOBALS['wc_kissmetrics']
 */
$GLOBALS['wc_kissmetrics'] = new WC_KISSmetrics();

} // init_woocommerce_kiss_metrics()
