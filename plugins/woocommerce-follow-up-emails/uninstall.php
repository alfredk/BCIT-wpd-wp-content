<?php

if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') ) {
    exit;
}
global $wpdb;

$tables = array();

$results = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}followup_%'", 'ARRAY_N');

foreach ($results as $row) {
    $tables[] = $row[0];
}

foreach ($tables as $tbl) {
    $wpdb->query("DROP TABLE `$tbl`");
}

// delete the unsubcribe page
if ( function_exists('woocommerce_get_page_id') ) {
    if ($page_id) {
        wp_delete_post($page_id, true);
    }

    $page_id = woocommerce_get_page_id('followup_unsubscribe');
}

delete_option('woocommerce_followup_unsubscribe_page_id');
