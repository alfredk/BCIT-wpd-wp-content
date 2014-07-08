<?php
/*
Plugin Name: BCIT WPD Corporate Bulk Purchasers
Plugin URI: http://localhost/woo-wpd
Description: Limit Products to a User and provide custom discounts to that user
Version: 1.0
Author: Alfred Kolowrat
Author URI: http://alfredk.com
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.
*/

// adds the meta-box.php file once
require_once('meta-box.php');

class WOO_BCIT_custom_discounts{

	function __construct(){
		add_action( 'pre_get_posts', array( $this, 'is_user_the_real_deal' ) );
		add_action( 'admin_notices', array( $this, 'check_required_plugins' ) );
		add_action( 'pre_get_posts', array( $this, 'limit_our_posts' ) );



		// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );
	} // construct

		/**
	 * @uses    function_exists     Checks for the function given string
	 * @uses    deactivate_plugins  Deactivates plugins given string or array of plugins
	 * @action  admin_notices       Provides WordPress admin notices
	 * @since   1.0
	 * @author  Alfred Kolowrat
	 */
	public function check_required_plugins(){

		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	?>
			<div id="message" class="error">
				<p>Corporate Bulk Purchasers expects WooCommerce to be active. This plugin has been deactivated.</p>
			</div>
	<?php
			deactivate_plugins( '/corporate-bulk-purchasers/corporate-bulk-purchasers.php' );
		} // corporate-bulk-purchasers
	} // check_required_plugins

	/**
	 * Fired when plugin is activated
	 *
	 * @param   bool    $network_wide   TRUE if WPMU 'super admin' uses Network Activate option
	 */
	public function activate( $network_wide ){
 		add_role( 'bulk_purchaser', 'Bulk Purchaser', array( 'read' => true, 'level_0' => true ) );
	} // activate

	/**
	 * Fired when plugin is deactivated
	 *
	 * @param   bool    $network_wide   TRUE if WPMU 'super admin' uses Network Activate option
	 */
	public function deactivate( $network_wide ){

	} // deactivate

	/**
	 * Fired when plugin is uninstalled
	 *
	 * @param   bool    $network_wide   TRUE if WPMU 'super admin' uses Network Activate option
	 */
	public function uninstall( $network_wide ){

	} // uninstall


/**
 *
 * @return boolean [description]
 */
	public function is_user_the_real_deal($query){

	if ( ! is_admin() && is_user_logged_in() && is_post_type_archive( 'product' ) && $query->is_main_query() ){

      $meta_query = $query->get('meta_query');
      $meta_query[] = array(
				                'key' => '_bcit_wpd_show_product',
				              'value' => ' ',
				            'compare' => 'NOT EXISTS',
          );
      $query->set('meta_query', $meta_query);
	  }
	  wp_reset_query();
	} // is_user_the_real_deal

	/**
	 * Changes our query so that we don't show our posts where we don't want them
	 *
	 * @since 1.0
	 * @author SFNdesign, Curtis McHale
	 *
	 * @param array     $query$         required        The mayn WP_Query object on the page
	 * any further docs go here
	 * @uses is_user_logged_in()                        Returns true if user is logged in
	 */
	public function limit_our_posts( $query ){

		// make sure you write you pre_get_posts stuff to remove any post with a key set (where you saved your user_id)
		// then my filter below should include them back in for a user_id if it matches the current user_id

		if ( is_user_logged_in() ) {
			add_filter( 'posts_where', array( $this, 'custom_where' ) );
		}

	} // limit_our_posts

	/**
	 * Make sure that if a user is logged in we include posts that have their user_id assigned in addition to the
	 * posts that have no key assigned.
	 *
	 * @since 1.0
	 * @author SFNdesign, Curtis McHale
	 *
	 * @param string        $where          required            existing SQL WHERE clauses in our query
	 * @global $wpdb                                            Biddy daddy WordPress dabatase global
	 * @uses get_current_user_id()                              Returns current user_id
	 * @return string       $where                              Our modified WP WHERE SQL clause
	 */
	public function custom_where( $where = '' ){


	// make sure to change your meta_key to whatever you saved your user_id as here mine is _restricted_to

		global $wpdb;
		$user_id = get_current_user_id();

		$where .= " OR (( $wpdb->postmeta.meta_key = '_bcit_wpd_show_product' and $wpdb->postmeta.meta_value = $user_id ))";

		remove_filter( 'posts_where', array( $this, 'custom_where' ) );

		return $where;

	} // custom_where


} //WOO_BCIT_custom_discounts
new WOO_BCIT_custom_discounts();