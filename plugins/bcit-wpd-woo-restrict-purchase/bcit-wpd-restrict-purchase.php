<?php
/**
 * Plugin Name: Alfred Custom BCIT Restrict plugin purchase
 * Plugin URI: http://localhost/woo-wpd
 * Description: A brief description of the Plugin.
 * Version: The 1.0
 * Author: Alfredk.com
 * Author URI: http://alfredk.com
 * License: A "Slug" license name e.g. GPL2
 */
/*This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA. */

// ADD meta.php to this file
require_once( 'meta.php' );

class BCIT_WPD_HideProduct{

	function __construct(){

		add_action('admin_notices', array( $this, 'check_required_plugins'));

		// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );

	} // __construct
/**
* check for woo commerce and deactivate if we do not find it
* @since 1.0
* @author Alfred
*
* @uses    function_exists     Checks for the function given string
* @uses    deactivate_plugins  Deactivates plugins given string or array of plugins
*
*/
	public function check_required_plugins(){

		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) { ?>

			<div id="message" class="error">
				<p>BCIT WPD Restrict Purchase expects WooCommerce to be active. This plugin has been deactivated.</p>
			</div>

			<?php
			deactivate_plugins( '/bcit-wpd-woo-restrict-purchase/bcit-wpd-restrict-purchase.php' );
		} // compmany team if

	} // check_required_plugins
		/**
	 * Fired when plugin is activated
	 *
	 * @param   bool    $network_wide   TRUE if WPMU 'super admin' uses Network Activate option
	 */
	public function activate( $network_wide ){

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

} // BCIT_WPD_HideProduct

new BCIT_WPD_HideProduct();