<?php
/*
Plugin Name: Follow-Up Emails
Plugin URI: http://www.woothemes.com/products/follow-up-emails/
Description: The most comprehensive email marketing tool for WooCommerce. Automate your email marketing to drive your customer engagement to a new level pre and post sale. Learn more at <a href="http://www.75nineteen.com/woocommerce/follow-up-email-autoresponder/?utm_source=WooCommerce&utm_medium=Link&utm_campaign=FUE" target="_blank">Follow-up Emails</a>.
Version: 3.3.4
Author: 75nineteen Media
Author URI: http://www.75nineteen.com/woocommerce/follow-up-email-autoresponder/

Copyright: © 2014 75nineteen Media.

*/

/** Path and URL constants **/
define( 'FUE_VERSION', '3.3.4' );
define( 'FUE_KEY', 'Y3VjLnJocy96YnAuYXJyZ3JhdmE1Ny8vOmNnZ3U' );
define( 'FUE_FILE', __FILE__ );
define( 'FUE_URL', plugins_url('', __FILE__) );
define( 'FUE_DIR', dirname(__FILE__) );
define( 'FUE_INC_DIR', FUE_DIR .'/includes' );
define( 'FUE_INC_URL', FUE_URL .'/includes' );
define( 'FUE_ADDONS_DIR', FUE_DIR .'/addons' );
define( 'FUE_ADDONS_URL', FUE_URL .'/addons' );
define( 'FUE_TEMPLATES_DIR', FUE_DIR .'/templates' );
define( 'FUE_TEMPLATES_URL', FUE_URL .'/templates' );

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
    require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '05ece68fe94558e65278fe54d9ec84d2', '18686' );


load_plugin_textdomain( 'follow_up_emails', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

$active_plugins         = get_option('active_plugins', array());
$woocommerce_installed  = in_array('woocommerce/woocommerce.php', $active_plugins);
$sensei_installed       = in_array('woothemes-sensei/woothemes-sensei.php', $active_plugins);

if ( !$woocommerce_installed && !$sensei_installed ) {

    add_action('admin_notices', 'fue_admin_notice');
    function fue_admin_notice() {
        $wc_url = 'http://www.woothemes.com/woocommerce/';
        $sensei_url = 'http://www.woothemes.com/products/sensei/';
        printf('<div class="updated"><p>'. __('Follow-Up Emails requires <a href="%s">WooCommerce</a> or <a href="%s">WooThemes Sensei</a> to be installed', 'follow_up_emails') .'</div>', $wc_url, $sensei_url);
    }

} else {

    require_once FUE_INC_DIR .'/class.followup_emails.php';
    $GLOBALS['fue'] = new FollowUpEmails();

}
