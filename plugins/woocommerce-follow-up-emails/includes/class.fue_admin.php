<?php

class FUE_Admin {

    public static function add_menu() {
        add_menu_page( __('Follow-Up Emails', 'follow_up_emails'), __('Follow-Up Emails', 'follow_up_emails'), 'manage_follow_up_emails', 'followup-emails', 'FUE_Admin::admin_controller', 'dashicons-email-alt', '54.51' );
        add_submenu_page( 'followup-emails', __('Follow-Up Emails', 'follow_up_emails'), __('Emails', 'follow_up_emails'), 'manage_follow_up_emails', 'followup-emails', 'FUE_Admin::admin_controller' );

        if ( (isset($_GET['page']) && $_GET['page'] == 'followup-emails-form') && ( (isset($_GET['new']) && $_GET['new'] == 1) || !isset($_GET['id']) ) ) {
            add_submenu_page( 'followup-emails', __('New Email', 'follow_up_emails'), __('New Email', 'follow_up_emails'), 'manage_follow_up_emails', 'followup-emails-form', 'FUE_Admin::email_form' );
        } else {
            add_submenu_page( 'followup-emails', __('Update Email', 'follow_up_emails'), __('New Email', 'follow_up_emails'), 'manage_follow_up_emails', 'followup-emails-form', 'FUE_Admin::email_form' );
        }

        do_action( 'fue_menu' );

        add_submenu_page( 'followup-emails', __('Manage Opt-outs', 'follow_up_emails'), __('Opt-outs', 'follow_up_emails'), 'manage_follow_up_emails', 'followup-emails-optouts', 'FUE_Admin::optout_table' );
        add_submenu_page( 'followup-emails', __('Follow-Up Emails Settings', 'follow_up_emails'), __('Settings', 'follow_up_emails'), 'manage_follow_up_emails', 'followup-emails-settings', 'FUE_Admin::settings' );
    }

    public static function admin_controller() {
        global $wpdb;

        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'list';

        if ( $tab == 'list' ) {
            self::list_emails_page();

            $fue_table = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}followup_emails'");

            if ( $fue_table != $wpdb->prefix .'followup_emails' || false == get_option('fue_installed_tables', false) ) {
                FollowUpEmails::update_db(true);
            }

            if ( false === wp_next_scheduled( 'sfn_followup_emails' ) ) {
                wp_schedule_event(time(), 'minute', 'sfn_followup_emails');
            }

            if ( false === wp_next_scheduled('sfn_optimize_tables' ) ) {
                wp_schedule_event(time(), 'weekly', 'sfn_optimize_tables');
            }
        } elseif ( $tab == 'edit' ) {
            self::email_form(1, $_GET['id']);
        } elseif ($tab == 'send') {
            $id             = $_GET['id'];
            $email          = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}followup_emails` WHERE `id` = %d AND `email_type` = 'manual'", $id) );

            if (! $email) {
                wp_die("The requested data could not be found!");
            }

            self::send_manual_form($email);
        } elseif ($tab == 'scheduler') {
            include FUE_TEMPLATES_DIR .'/scheduler.php';
        } else {
            // allow add-ons to add tabs
            do_action( 'fue_admin_controller', $tab );
        }

    }

    public static function list_emails_page() {
        global $wpdb;

        $types      = FollowUpEmails::get_email_types();
        $bccs       = get_option('fue_bcc_types', false);

        include FUE_TEMPLATES_DIR .'/emails_list.php';
    }

    public static function email_form($step = 1, $id = '') {
        global $wpdb;

        if ( isset($_GET['step']) ) $step = absint( $_GET['step'] );
        if ( isset($_GET['id']) ) $id = absint( $_GET['id'] );

        if ( $id ) {
            $email      = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_emails WHERE id = %d", $id) );
            $defaults   = apply_filters( 'fue_email_defaults', array(
                'id'                => $id,
                'type'              => $email->email_type,
                'always_send'       => $email->always_send,
                'name'              => $email->name,
                'interval'          => $email->interval_num,
                'interval_duration' => $email->interval_duration,
                'interval_type'     => $email->interval_type,
                'send_date'         => $email->send_date,
                'send_date_hour'    => $email->send_date_hour,
                'send_date_minute'  => $email->send_date_minute,
                'product_id'        => $email->product_id,
                'category_id'       => $email->category_id,
                'subject'           => $email->subject,
                'message'           => $email->message,
                'tracking_on'       => (!empty($email->tracking_code)) ? 1 : 0,
                'tracking'          => $email->tracking_code,
                'meta'              => (isset($email->meta)) ? maybe_unserialize( $email->meta ) : array()
            ), $id );
        } else {
            $defaults = apply_filters( 'fue_email_defaults', array(
                'id'                => '',
                'type'              => 'generic',
                'always_send'       => 0,
                'name'              => '',
                'interval'          => 1,
                'interval_duration' => 'hours',
                'interval_type'     => 'purchase',
                'send_date'         => '',
                'send_date_hour'    => '',
                'send_date_minute'  => '',
                'product_id'        => '',
                'category_id'       => '',
                'subject'           => '',
                'message'           => '',
                'tracking_on'       => 0,
                'tracking'          => '',
                'meta'              => array()
            ), '' );
        }

        // if type is date, switch columns
        if ( $defaults['interval_type'] == 'date' ) {
            $defaults['interval_type'] = $defaults['interval_duration'];
            $defaults['interval_duration'] = 'date';
        }

        if ( isset($_POST) && !empty($_POST) ) {
            $defaults = array_merge( $defaults, $_POST );
        }

        switch ( $step ) {
            case '':
            case 1:
                include FUE_TEMPLATES_DIR .'/email-form/step1.php';
                break;

            case 2:
                include FUE_TEMPLATES_DIR .'/email-form/step2.php';
                break;

            case 3:
                include FUE_TEMPLATES_DIR .'/email-form/step3.php';
                break;
        }

    }

    public static function edit_email_form($email) {
        global $wpdb;

        include FUE_TEMPLATES_DIR .'/edit_email.php';
    }

    public static function send_manual_form( $email ) {
        global $wpdb;

        include FUE_TEMPLATES_DIR .'/send_manual.php';
    }

    public static function optout_table() {
        global $wpdb;

        include FUE_TEMPLATES_DIR .'/optout_table.php';
    }

    public static function settings() {
        global $wpdb;

        $pages      = get_pages();
        $page       = fue_get_page_id('followup_unsubscribe');
        $emails     = get_option('fue_daily_emails');
        $bcc        = get_option('fue_bcc', '');
        $tab        = (isset($_GET['tab'])) ? $_GET['tab'] : 'email';

        include FUE_TEMPLATES_DIR .'/settings.php';
    }

    public static function settings_scripts() {

        if (! wp_script_is( 'jquery-tiptip', 'registered' ) ) {
            wp_register_script( 'jquery-tiptip', FUE_URL .'/templates/js/jquery.tipTip.min.js', array( 'jquery' ), FUE_VERSION, true );
        }

        // blockUI
        if (! wp_script_is('jquery-blockui', 'registered') ) {
            wp_register_script( 'jquery-blockui', FUE_URL . '/templates/js/jquery-blockui/jquery.blockUI.min.js', array( 'jquery' ), FUE_VERSION, true );
        }

        $page = isset($_GET['page']) ? $_GET['page'] : '';

        if ( $page == 'followup-emails' || $page == 'followup-emails-form' || $page == 'followup-emails-reports' ) {
            wp_enqueue_script('jquery-blockui');
            wp_enqueue_script('media-upload');
            wp_enqueue_script('thickbox');
            wp_enqueue_script('editor');

            wp_enqueue_style('thickbox');

            wp_enqueue_script( 'jquery-tiptip' );
            wp_enqueue_script( 'jquery-ui-core', null, array('jquery') );
            wp_enqueue_script( 'jquery-ui-datepicker', null, array('jquery-ui-core') );
            wp_enqueue_script( 'jquery-ui-sortable', null, array('jquery-ui-core') );
            wp_enqueue_script( 'fue-form', plugins_url( 'templates/js/email-form.js', FUE_FILE ), array('jquery'), '1.5' );

            wp_enqueue_style( 'jquery-ui-css', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.21/themes/base/jquery-ui.css' );
            wp_enqueue_style( 'fue-email-form-css', plugins_url( 'templates/email-form.css', FUE_FILE ) );

            $translate = apply_filters( 'fue_script_locale', array(
                'processing_request'    => __('Processing request...', 'follow_up_emails'),
                'dupe'                  => __('A follow-up email with the same settings already exists. Do you want to create it anyway?', 'follow_up_emails'),
                'similar'               => __('A similar follow-up email already exists. Do you wish to continue?', 'follow_up_emails'),
                'save'                  => __('Build your email', 'follow_up_emails'),
                'ajax_loader'           => plugins_url() .'/woocommerce-follow-up-emails/templates/images/ajax-loader.gif'
            ) );
            wp_localize_script( 'fue-form', 'FUE', $translate );

            if ( isset($_GET['tab']) && $_GET['tab'] == 'scheduler' ) {
                // progress bar
                wp_enqueue_script( 'jquery-ui-progressbar', null, array('jquery', 'jquery-ui-core') );

                // action scheduler import script
                wp_enqueue_script( 'fue-action-scheduler', plugins_url( 'templates/js/action-scheduler.js', FUE_FILE ), array('jquery') );
                $translate = array(
                    'confirm_message'   => __('Importing will take a while depending on the number of emails in the queue. Do you want to continue?', 'follow_up_emails'),
                    'import_nonce'      => wp_create_nonce('import-action-scheduler')
                );
                wp_localize_script( 'fue-action-scheduler', 'FUE_Scheduler', $translate );
            }

        }

    }

}
