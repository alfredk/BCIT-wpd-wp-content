<?php

// install
register_activation_hook(basename(FUE_DIR).'/'. basename(FUE_DIR) .'.php', 'FollowUpEmails::install');

// uninstall
register_deactivation_hook(basename(FUE_DIR).'/'. basename(FUE_DIR) .'.php', 'FollowUpEmails::uninstall');

// Upgrade
add_action( 'plugins_loaded', array( 'FollowUpEmails', 'update_db' ) );
add_action( 'plugins_loaded', array( 'FollowUpEmails', 'add_capabilities' ) );

// menu
add_action('admin_menu', 'FUE_Admin::add_menu', 20);

// settings styles and scripts
add_action('admin_enqueue_scripts', 'FUE_Admin::settings_scripts');

// new cron event
add_filter('cron_schedules', 'FollowUpEmails::cron_schedules');

// cron: optimize tables
add_action('sfn_optimize_tables', 'FollowUpEmails::optimize_tables');

// load addons
add_action('plugins_loaded', 'FollowUpEmails::load_addons');

// after user signs up
add_action('user_register', 'FollowUpEmails::user_register');

// catch unsubscribe request
add_action('template_redirect', 'FollowUpEmails::unsubscribe_request');
add_action('template_redirect', 'FollowUpEmails::update_my_account');

// account form
add_shortcode( 'fue_followup_optout', 'FollowUpEmails::opt_out_form' );
add_shortcode( 'woocommerce_followup_optout', 'FollowUpEmails::opt_out_form' );

// shortcode for the unsubscribe page
add_shortcode('fue_followup_unsubscribe', 'FollowUpEmails::unsubscribe_form');
add_shortcode('woocommerce_followup_unsubscribe', 'FollowUpEmails::unsubscribe_form');

// AJAX email search for manual send
add_action('wp_ajax_fue_email_query', 'FollowUpEmails::email_query');

// get post custom fields
add_action('wp_ajax_fue_get_custom_fields', 'FollowUpEmails::get_custom_fields');

// find dupes/similar follow-ups
add_action('wp_ajax_sfn_fe_find_dupes', 'FollowUpEmails::find_dupes');

// cron action
add_action('sfn_followup_emails', 'FUE::send_emails');

// usage report
add_action('sfn_send_usage_report', 'FUE::send_usage_data');

// test email
add_action('wp_ajax_sfn_test_email', array('FUE', 'send_test_email'));

// send manual emails
add_action('admin_post_sfn_followup_send_manual', 'FollowUpEmails::send_manual');

// settings actions
add_action( 'admin_post_sfn_followup_form', 'FollowUpEmails::process_email_form' );

add_action( 'admin_post_sfn_followup_delete', 'FollowUpEmails::delete_email');
add_action( 'admin_post_sfn_followup_save_priorities', 'FollowUpEmails::update_priorities');
add_action( 'admin_post_sfn_followup_save_settings', 'FollowUpEmails::update_settings');

// Restore optout email
add_action( 'admin_post_sfn_optout_manage', 'FollowUpEmails::manage_optout' );

// reset report data
add_action('admin_post_fue_reset_reports', array('FollowUpEmails', 'reset_reports') );

// backup and restore
add_action('admin_post_fue_backup_emails', 'FollowUpEmails::backup_emails');
add_action('admin_post_fue_backup_settings', 'FollowUpEmails::backup_settings');

// Clone Email
add_action('wp_ajax_fue_email_clone', 'FollowUpEmails::ajax_clone_email');

// Update status
add_action('wp_ajax_fue_email_toggle_status', 'FollowUpEmails::ajax_toggle_status');

// Toggle Queue Status
add_action('wp_ajax_fue_email_toggle_queue_status', 'FollowUpEmails::ajax_toggle_queue_status');

// Convert to Action Scheduler
add_action( 'wp_ajax_fue_as_count_import_rows', 'FollowUpEmails::ajax_count_import_rows' );
add_action( 'wp_ajax_fue_as_import', 'FollowUpEmails::ajax_import' );

add_action( 'wp_ajax_fue_as_import_start', 'FollowUpEmails::ajax_import_start' );
add_action( 'wp_ajax_fue_as_import_complete', 'FollowUpEmails::ajax_import_complete' );

// Convert back to WP-Cron
add_action( 'wp_ajax_fue_wpc_import', 'FollowUpEmails::ajax_use_wp_cron' );

// Register our own Logger class to stop Action Scheduler from posting comments as logs
//add_filter( 'action_scheduler_logger_class', create_function('', 'return "FUE_ActionScheduler_Logger";') );