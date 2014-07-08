<?php

class FollowUpEmails {

    public static $db_version           = '5.2';
    public static $triggers             = array();
    public static $email_types          = array();
    public static $email_type_triggers  = array();
    public static $email_templates      = array();
    public static $durations            = array();
    public static $is_woocommerce       = false;
    public static $is_sensei            = false;
    public static $scheduling_system    = 'wp-cron';

    public static $email_type_long_descriptions     = array();
    public static $email_type_short_descriptions    = array();

    public function __construct() {
        self::$email_types = array(
            'signup' => __('User Signup Email', 'follow_up_emails'),
            'manual' => __('Manual Email', 'follow_up_emails')
        );

        self::$email_type_long_descriptions = array(
            'signup'    => __('Sign up emails will send to a new user in your store based upon the criteria you define when creating your emails. Below are the existing Sign up emails set up for your store. Use the priorities to define which emails are most important. These emails are selected first when sending the email to the customer if more than one criteria is met by multiple emails. Only one email is sent out to the customer (unless you enable the Always Send option when creating your emails), so prioritizing the emails for occasions where multiple criteria are met ensures you send the right email to the right customer at the time you choose.', 'follow_up_emails'),
            'manual'    => __('Manual emails allow you to create email templates for you and your team to utilize when you need to send emails immediately to customers or prospective customers. Creating a manual email will allow you to reduce manual entry and duplication when you send emails from your email client, and keep emails consistent. Below are the existing Manual emails set up for your store.', 'follow_up_emails')
        );

        self::$email_type_short_descriptions = array(
            'signup'    => __('', 'follow_up_emails'),
            'manual'    => __('', 'follow_up_emails')
        );

        self::$durations = array(
            'minutes'   => __('minutes', 'follow_up_emails'),
            'hours'     => __('hours', 'follow_up_emails'),
            'days'      => __('days', 'follow_up_emails'),
            'weeks'     => __('weeks', 'follow_up_emails'),
            'months'    => __('months', 'follow_up_emails'),
            'years'     => __('years', 'follow_up_emails'),
            'date'      => __('on this date', 'follow_up_emails')
        );

        self::$email_type_triggers = array(
            'signup'        => array(),
            'manual'        => array()
        );

        self::$email_templates = array(
            '2col-1-2.html'     => '2 Column 1-2'
        );

        self::include_files();

        if ( self::is_woocommerce_installed() ) {
            self::$is_woocommerce = true;
            require_once FUE_INC_DIR .'/class.woocommerce.php';
        }

        if ( self::is_sensei_installed() ) {
            self::$is_sensei = true;
            require_once FUE_INC_DIR .'/class.sensei.php';
        }

        self::$scheduling_system = get_option( 'fue_scheduling_system', 'wp-cron' );

        do_action( 'fue_init' );
    }

    public static function include_files() {
        require_once FUE_INC_DIR .'/fue_functions.php';
        require_once FUE_INC_DIR .'/class.fue.php';
        require_once FUE_INC_DIR .'/action-scheduler/action-scheduler.php';
        //require_once FUE_INC_DIR .'/class.fue_action_scheduler_logger.php';
        require_once FUE_INC_DIR .'/plugin_hooks.php';

        if ( is_admin() ) {
            require_once FUE_INC_DIR .'/class.fue_admin.php';
        }
    }

    public static function is_woocommerce_installed() {
        return in_array('woocommerce/woocommerce.php', get_option('active_plugins'));
    }

    public static function is_sensei_installed() {
        return in_array('woothemes-sensei/woothemes-sensei.php', get_option('active_plugins'));
    }

    /*public static function load_addons() {
        do_action( 'fue_addons_loaded' );
    }*/

    public static function update_db( $force = false ) {
        global $wpdb;

        $installed_db_version = get_option('fue_db_version', 0);

        if ( !$force && $installed_db_version == self::$db_version ) return;

        // reinstall the cron jobs
        self::install_cronjobs();

        $wpdb->hide_errors();
        $collate = '';

        if ( method_exists($wpdb, 'has_cap') ) {
            if ( $wpdb->has_cap('collation') ) {
                if( ! empty($wpdb->charset ) ) $collate .= "DEFAULT CHARACTER SET $wpdb->charset";
                if( ! empty($wpdb->collate ) ) $collate .= " COLLATE $wpdb->collate";
            }
        } else {
            if ( $wpdb->supports_collation() ) {
                if( ! empty($wpdb->charset ) ) $collate .= "DEFAULT CHARACTER SET $wpdb->charset";
                if( ! empty($wpdb->collate ) ) $collate .= " COLLATE $wpdb->collate";
            }
        }

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $table = $wpdb->prefix . 'followup_emails';
        $sql = "CREATE TABLE $table (
id bigint(20) NOT NULL AUTO_INCREMENT,
product_id bigint(20) NOT NULL,
category_id BIGINT(20) NOT NULL DEFAULT 0,
name varchar(100) NOT NULL,
interval_num int(3) DEFAULT 1 NOT NULL,
interval_duration VARCHAR(50) DEFAULT 'days' NOT NULL,
interval_type VARCHAR(50) DEFAULT  'purchase' NOT NULL,
send_date VARCHAR(50) DEFAULT '' NOT NULL,
send_date_hour VARCHAR(4) DEFAULT '' NOT NULL,
send_date_minute VARCHAR(4) DEFAULT '' NOT NULL,
subject VARCHAR(255) NOT NULL,
message LONGTEXT NOT NULL,
tracking_code VARCHAR(255) NOT NULL,
usage_count BIGINT(20) DEFAULT 0 NOT NULL,
date_added DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
email_type VARCHAR(50) DEFAULT '' NOT NULL,
priority INT(3) DEFAULT 0 NOT NULL,
always_send INT(1) DEFAULT 0 NOT NULL,
meta TEXT NOT NULL,
status INT(1) DEFAULT 1 NOT NULL,
KEY category_id (category_id),
KEY product_id (product_id),
KEY email_type (email_type),
KEY status (status),
PRIMARY KEY  (id)
) $collate";
        dbDelta($sql);

        $table = $wpdb->prefix . 'followup_email_excludes';
        $sql = "CREATE TABLE $table (
id bigint(20) NOT NULL AUTO_INCREMENT,
email_id bigint(20) NOT NULL,
email_name varchar(255) NOT NULL,
email varchar(100) NOT NULL,
date_added DATETIME NOT NULL,
PRIMARY KEY  (id)

) $collate";
        dbDelta($sql);

        $table = $wpdb->prefix . 'followup_email_orders';
        $sql = "CREATE TABLE $table (
id bigint(20) NOT NULL AUTO_INCREMENT,
user_id bigint(20) NOT NULL,
user_email varchar(255) NOT NULL,
order_id bigint(20) NOT NULL,
product_id bigint(20) NOT NULL,
email_id varchar(100) NOT NULL,
send_on bigint(20) NOT NULL,
is_cart int(1) DEFAULT 0 NOT NULL,
is_sent int(1) DEFAULT 0 NOT NULL,
date_sent datetime NOT NULL,
email_trigger varchar(100) NOT NULL,
meta TEXT NOT NULL,
status INT(1) DEFAULT 1 NOT NULL,
KEY user_id (user_id),
KEY user_email (user_email),
KEY order_id (order_id),
KEY is_sent (is_sent),
KEY date_sent (date_sent),
KEY status (status),
PRIMARY KEY  (id)
) $collate";
        dbDelta($sql);

        $table = $wpdb->prefix .'followup_email_coupons';
        $sql = "CREATE TABLE $table (
email_id bigint(20) NOT NULL,
send_coupon int(1) DEFAULT 0 NOT NULL,
coupon_id bigint(20) DEFAULT 0 NOT NULL,
KEY email_id (email_id)
) $collate";
        dbDelta($sql);

        $table = $wpdb->prefix .'followup_email_order_coupons';
        $sql = "CREATE TABLE $table (
email_order_id bigint(20) NOT NULL,
coupon_name varchar(100) NOT NULL,
coupon_code varchar(20) NOT NULL,
KEY emil_order_id (email_order_id)
) $collate";
        dbDelta($sql);

        $table = $wpdb->prefix . 'followup_coupon_logs';
        $sql = "CREATE TABLE $table (
id bigint(20) NOT NULL AUTO_INCREMENT,
coupon_id bigint(20) NOT NULL,
coupon_name varchar(100) NOT NULL,
email_name varchar(100) NOT NULL,
email_address varchar(255) NOT NULL,
coupon_code varchar(100) NOT NULL,
coupon_used INT(1) DEFAULT 0 NOT NULL,
date_sent datetime NOT NULL,
date_used datetime NOT NULL,
KEY coupon_id (coupon_id),
KEY date_sent (date_sent),
PRIMARY KEY  (id)
) $collate";
        dbDelta($sql);

        $table = $wpdb->prefix . 'followup_coupons';
        $sql = "CREATE TABLE $table (
id bigint(20) NOT NULL AUTO_INCREMENT,
coupon_name varchar(100) NOT NULL,
coupon_type varchar(25) default 0 NOT NULL,
coupon_prefix varchar(25) default '' NOT NULL,
amount double(12,2) default 0.00 NOT NULL,
individual int(1) default 0 NOT NULL,
before_tax int(1) default 0 NOT NULL,
free_shipping int(1) default 0 NOT NULL,
usage_count bigint(20) default 0 NOT NULL,
expiry_value varchar(3) NOT NULL DEFAULT 0,
expiry_type varchar(25) NOT NULL DEFAULT '',
product_ids varchar(255) NOT NULL DEFAULT '',
exclude_product_ids varchar(255) NOT NULL DEFAULT '',
product_categories TEXT,
exclude_product_categories TEXT,
minimum_amount varchar(50) NOT NULL DEFAULT '',
usage_limit varchar(3) NOT NULL DEFAULT '',
KEY coupon_name (coupon_name),
KEY usage_count (usage_count),
PRIMARY KEY  (id)
) $collate";
        dbDelta($sql);

        $table = $wpdb->prefix . 'followup_email_tracking';
        $sql = "CREATE TABLE $table (
id bigint(20) NOT NULL AUTO_INCREMENT,
event_type varchar(20) NOT NULL,
email_order_id bigint(20) DEFAULT 0 NOT NULL,
email_id bigint(20) NOT NULL,
user_id bigint(20) DEFAULT 0 NOT NULL,
user_email varchar(255) NOT NULL,
target_url varchar(255) NOT NULL,
date_added datetime NOT NULL,
KEY email_id (email_id),
KEY user_id (user_id),
KEY user_email (user_email),
KEY date_added (date_added),
KEY event_type (event_type),
PRIMARY KEY  (id)
) $collate";
        dbDelta($sql);

        $table = $wpdb->prefix . 'followup_email_logs';
        $sql = "CREATE TABLE $table (
id bigint(20) NOT NULL AUTO_INCREMENT,
email_order_id bigint(20) DEFAULT 0 NOT NULL,
email_id bigint(20) NOT NULL,
user_id bigint(20) DEFAULT 0 NOT NULL,
email_name varchar(100) NOT NULL,
customer_name varchar(255) NOT NULL,
email_address varchar(255) NOT NULL,
date_sent datetime NOT NULL,
order_id bigint(20) NOT NULL,
product_id bigint(20) NOT NULL,
email_trigger varchar(100) NOT NULL,
KEY email_name (email_name),
KEY user_id (user_id),
KEY date_sent (date_sent),
PRIMARY KEY  (id)
) $collate";
        dbDelta($sql);

        $table = $wpdb->prefix .'followup_customers';
        $sql = "CREATE TABLE $table (
id bigint(20) NOT NULL AUTO_INCREMENT,
user_id bigint(20) NOT NULL,
email_address varchar(255) NOT NULL,
total_purchase_price double(10,2) DEFAULT 0 NOT NULL,
total_orders int(11) DEFAULT 0 NOT NULL,
KEY user_id (user_id),
KEY email_address (email_address),
KEY total_purchase_price (total_purchase_price),
KEY total_orders (total_orders),
PRIMARY KEY  (id)
) $collate";
        dbDelta($sql);

        $table = $wpdb->prefix .'followup_customer_orders';
        $sql = "CREATE TABLE $table (
followup_customer_id bigint(20) NOT NULL,
order_id bigint(20) NOT NULL,
price double(10, 2) NOT NULL,
KEY followup_customer_id (followup_customer_id),
KEY order_id (order_id)
) $collate";
        dbDelta($sql);

        $table = $wpdb->prefix .'followup_order_items';
        $sql = "CREATE TABLE $table (
order_id bigint(20) NOT NULL,
product_id bigint(20) NOT NULL,
KEY order_id (order_id),
KEY product_id (product_id)
) $collate";
        dbDelta($sql);

        $table = $wpdb->prefix .'followup_order_categories';
        $sql = "CREATE TABLE $table (
order_id bigint(20) NOT NULL,
category_id bigint(20) NOT NULL,
KEY order_id (order_id),
KEY category_id (category_id)
) $collate";
        dbDelta($sql);

        // Update email triggers from purchase to processing
        $wpdb->update( $wpdb->prefix .'followup_emails', array('interval_type' => 'processing'), array('interval_type' => 'purchase') );

        if ( self::$db_version == '1.1' ) {
            // convert send dates to 12-hour format
            $emails = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}followup_emails WHERE interval_duration = 'date'");

            foreach ( $emails as $email ) {
                $meta   = maybe_unserialize( $email->meta );

                if ( $email->send_date_hour < 12 ) {
                    $hour   = $email->send_date_hour;
                    $meta['send_date_ampm'] = 'am';
                } elseif ( $email->send_date_hour == 12 ) {
                    $hour   = $email->send_date_hour;
                    $meta['send_date_ampm'] = 'pm';
                } else {
                    $hour   = $email->send_date_hour - 12;
                    $meta['send_date_ampm'] = 'pm';
                }

                $wpdb->update( $wpdb->prefix .'followup_emails', array('send_date_hour' => $hour, 'meta' => serialize($meta) ), array('id' => $email->id) );
            }
        }

        update_option( 'fue_installed_tables', true );
        update_option( 'fue_db_version', self::$db_version );
    }

    public static function add_capabilities() {
        $role   = get_role('administrator');

        if ( array_key_exists('manage_follow_up_emails', $role->capabilities) )
            return;

        $roles = new WP_Roles();
        $roles->add_cap( 'administrator', 'manage_follow_up_emails', true );
    }

    public static function add_role() {
        $role = add_role( 'fue_manager', __('Follow-Up Emails Manager', 'follow_up_emails'), array(
            'read'                   => true,
        ) );

        if ( $role ) {
            $role->add_cap( 'manage_follow_up_emails' );
        }
    }

    public static function install() {
        global $wpdb;

        // install db tables
        self::update_db();

        // register custom capabilities
        self::add_capabilities();

        // register fue_admin role
        self::add_role();

        // install scheduled task
        self::install_cronjobs();

        // subscriptions & unsubscribe page
        self::install_unsubscribe_page();
        self::install_my_subscriptions_page();

        do_action( 'fue_install' );
    }

    public static function cron_schedules( $schedules ) {
        // add cron schedules if system used is wp-cron

        $schedules['minute'] = array(
            'interval' => 60,
            'display' => __('Every Minute', 'follow_up_emails')
        );

        $schedules['everyquarter'] = array(
            'interval'  => 900,
            'display'   => __('Every quarter of an hour', 'follow_up_emails')
        );

        if (! isset($schedules['weekly']) ) {
            $schedules['weekly'] = array(
                'interval'  => 604800,
                'display'   => __('Once Weekly', 'follow_up_emails')
            );
        }


        return $schedules;
    }

    public static function install_cronjobs() {
        wp_clear_scheduled_hook('sfn_followup_emails');
        wp_clear_scheduled_hook('sfn_send_summary');
        wp_clear_scheduled_hook('fue_send_summary');
        wp_clear_scheduled_hook('fue_optimize_tables');
        wp_clear_scheduled_hook('sfn_send_usage_report');

        $system = get_option( 'fue_scheduling_system', 'wp-cron' );

        if ( $system == 'wp-cron' ) {
            wp_schedule_event(time(), 'minute', 'sfn_followup_emails');
        }

        wp_schedule_event(time(), 'weekly', 'sfn_optimize_tables');
        wp_schedule_event(time(), 'daily', 'sfn_send_usage_report');

    }

    public static function install_unsubscribe_page() {
        global $wpdb;

        $wpdb->update($wpdb->options, array('option_name' => 'fue_followup_unsubscribe_page_id'), array('option_name' => 'woocommerce_followup_unsubscribe_page_id'));

        $page_id    = fue_get_page_id('followup_unsubscribe');
        $page       = get_page($page_id);
        if ($page_id == -1 || !$page) {
            // add page and assign
            $page = array(
                'menu_order'        => 0,
                'comment_status'    => 'closed',
                'ping_status'       => 'closed',
                'post_author'       => 1,
                'post_content'      => '[fue_followup_unsubscribe]',
                'post_name'         => 'unsubscribe',
                'post_parent'       => 0,
                'post_title'        => 'Unsubscribe from Email List',
                'post_type'         => 'page',
                'post_status'       => 'publish',
                'post_category'     => array(1)
            );

            $page_id = wp_insert_post($page);
        }

        update_option('fue_followup_unsubscribe_page_id', $page_id);

        return $page_id;
    }

    public static function install_my_subscriptions_page() {
        if (! self::is_woocommerce_installed() ) return;

        $page_id    = fue_get_page_id('followup_my_subscriptions');
        $page       = get_page($page_id);
        $parent_id  = woocommerce_get_page_id( 'myaccount' );

        if ($page_id == -1 || !$page) {
            // add page and assign
            $page = array(
                'menu_order'        => 0,
                'comment_status'    => 'closed',
                'ping_status'       => 'closed',
                'post_author'       => 1,
                'post_content'      => '[fue_followup_subscriptions]',
                'post_name'         => 'email-subscriptions',
                'post_parent'       => $parent_id,
                'post_title'        => 'Email Subscriptions',
                'post_type'         => 'page',
                'post_status'       => 'publish',
                'post_category'     => array(1)
            );

            $page_id = wp_insert_post($page);
        }

        update_option('fue_followup_my_subscriptions_page_id', $page_id);

        return $page_id;
    }

    public static function uninstall() {
        wp_clear_scheduled_hook('sfn_followup_emails');
        wp_clear_scheduled_hook('sfn_optimize_tables');

        // delete database tables
        //self::delete_tables();

        // delete the unsubcribe page
        //self::delete_unsubscribe_page();

        do_action( 'fue_uninstall' );
    }

    public static function delete_tables() {
        global $wpdb;

        $tables     = array();
        $results    = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}followup_%'", 'ARRAY_N');

        foreach ($results as $row) {
            $tables[] = $row[0];
        }

        foreach ($tables as $tbl) {
            $wpdb->query("DROP TABLE `$tbl`");
        }
    }

    public static function delete_unsubscribe_page() {
        $page_id = fue_get_page_id('followup_unsubscribe');

        if ($page_id) {
            wp_delete_post($page_id, true);
        }

        delete_option('fue_followup_unsubscribe_page_id');
    }

    public static function load_addons() {
        // Email Reporting, Tracking and Daily Summary
        require_once FUE_INC_DIR .'/class.fue_reports.php';

        if ( self::$is_sensei ) {
            require_once FUE_INC_DIR .'/class.sensei.php';
        }

        if ( self::$is_woocommerce ) {
            require_once FUE_INC_DIR .'/class.woocommerce.php';
        }

        do_action( 'fue_addons_loaded' );
    }

    public static function optimize_tables() {
        global $wpdb;

        $tables = apply_filters('fue_tables', array(
            'followup_coupons', 'followup_coupon_logs', 'followup_customers',
            'followup_customer_orders', 'followup_emails', 'followup_email_coupons',
            'followup_email_excludes', 'followup_email_logs', 'followup_email_orders',
            'followup_email_order_coupons', 'followup_email_tracking',
            'followup_order_categories', 'followup_order_items'
        ) );

        foreach ( $tables as $table ) {
            $wpdb->query("OPTIMIZE TABLE $table");
        }
    }

    public static function show_message( $message ) {
        if ( self::$is_sensei ) {
            global $woothemes_sensei;
            $woothemes_sensei->frontend->messages .= '<div class="woo-sc-box info">'. __('Account updated', 'follow_up_emails') .'</div>';
        } elseif ( self::$is_woocommerce ) {

            if ( function_exists('wc_add_notice') ) {
                wc_add_notice(__('Account updated', 'follow_up_emails'));
            } else {
                global $woocommerce;
                $woocommerce->add_message(__('Account updated', 'follow_up_emails'));
            }

        }
    }

    public static function get_message_url() {
        if ( self::$is_sensei ) {
            return get_permalink( get_option('woothemes-sensei_user_dashboard_page_id', -1) );
        } elseif ( self::$is_woocommerce ) {
            return get_permalink( woocommerce_get_page_id( 'myaccount' ) );
        }

        return get_bloginfo( 'url' );
    }

    public static function get_trigger_types( $email_type = '' ) {
        $triggers = apply_filters( 'fue_trigger_types', array(), $email_type );

        self::$triggers = $triggers;

        return self::$triggers;
    }

    public static function get_email_types() {
        return apply_filters( 'fue_email_types', self::$email_types );
    }

    public static function get_email_type( $type ) {
        $email_types = self::get_email_types();
        if ( isset( $email_types[$type] ) ) {
            return $email_types[$type];
        }
        return $type;
    }

    public static function get_email_type_long_description( $email_type ) {
        $descriptions = apply_filters( 'fue_email_type_long_descriptions', self::$email_type_long_descriptions );

        if ( isset($descriptions[$email_type]) ) {
            return $descriptions[$email_type];
        }

        return '';
    }

    public static function get_email_type_short_description( $email_type ) {
        $descriptions = apply_filters( 'fue_email_type_short_descriptions', self::$email_type_short_descriptions );

        if ( isset($descriptions[$email_type]) ) {
            return $descriptions[$email_type];
        }

        return '';
    }

    public static function get_email_type_triggers() {
        return apply_filters( 'fue_email_type_triggers', self::$email_type_triggers );
    }

    public static function get_trigger_name( $trigger ) {
        $triggers = self::get_trigger_types();

        if ( isset( $triggers[$trigger]) ) {
            return $triggers[$trigger];
        }
        return $trigger;
    }

    public static function get_durations() {
        return apply_filters( 'fue_durations', self::$durations );
    }

    public static function get_duration( $duration, $value = 0 ) {
        $durations = self::get_durations();
        if ( isset( $durations[$duration] ) ) {
            return ($value == 1) ? rtrim($durations[$duration], 's') : $durations[$duration];
        }
        return $duration;
    }

    public static function opt_out_form() {
        ob_start();

        $me = wp_get_current_user();

        if ( $me->ID == 0 ) return;

        include apply_filters( 'fue_optout_form', FUE_TEMPLATES_DIR .'/optout_form.php' );

        $content = ob_get_clean();
        return $content;
    }

    public static function update_my_account() {
        global $wpdb;

        if (isset($_POST['fue_action']) && $_POST['fue_action'] == 'fue_save_myaccount') {
            $opted_out  = (isset($_POST['fue_opt_out']) && $_POST['fue_opt_out'] == 1) ? true : false;
            $user       = wp_get_current_user();

            if ( $opted_out ) {
                // unsubscribe this user using his/her email
                update_user_meta( $user->ID, 'fue_opted_out', true );
            } else {
                update_user_meta( $user->ID, 'fue_opted_out', false );
            }

            wp_redirect( add_query_arg('fue_updated', 1, self::get_message_url()) );
            exit;
        } elseif (isset($_GET['fue_updated'])) {
            self::show_message(__('Account updated', 'follow_up_emails'));
        }
    }

    public static function unsubscribe_form() {
        $email = '';
        if (isset($_GET['fue']) && !empty($_GET['fue'])) {
            $email = $_GET['fue'];
        }

        $eid = isset($_GET['fueid']) ? $_GET['fueid'] : '';

        include apply_filters( 'fue_unsubscribe_form', FUE_TEMPLATES_DIR .'/unsubscribe_form.php' );
    }

    public static function unsubscribe_request() {
        global $wpdb;

        if (isset($_POST['fue_action']) && $_POST['fue_action'] == 'fue_unsubscribe') {
            $email      = $_POST['fue_email'];
            $email_id   = $_POST['fue_eid'];

            FUE::exclude_email_address( $email, $email_id );

            if ( isset($_GET['fue']) )
                do_action('fue_user_unsubscribed', $_GET['fue']);

            wp_redirect( add_query_arg( 'fue_unsubscribed', 1, self::get_message_url() ) );
            exit;
        } elseif (isset($_GET['fue_unsubscribed'])) {
            add_action('wp_head', 'FollowUpEmails::show_messages');
        }
    }

    public static function show_messages() {
        $message = apply_filters('fue_unsubscribed_message', __('Thank you. You have been unsubscribed from our list.', 'follow_up_emails') );
        echo '<script type="text/javascript">';
        echo 'alert("'. $message .'");';
        echo '</script>';
    }

    public static function user_register( $user_id ) {
        global $wpdb;

        $triggers = apply_filters( 'fue_user_register_triggers', array('signup'), $user_id );

        FUE::create_order_from_signup( $user_id, $triggers );
    }

    public static function email_query() {
        global $wpdb;
        $term       = stripslashes($_GET['term']);
        $results    = array();
        $all_emails = array();

        // Registered users
        $email_results = $wpdb->get_results("SELECT DISTINCT ID, display_name, user_email FROM {$wpdb->prefix}users WHERE `user_email` LIKE '{$term}%' OR display_name LIKE '%{$term}%'");

        if ( $email_results ) {
            foreach ( $email_results as $result ) {
                $all_emails[] = $result->user_email;

                $wp_user = new WP_User( $result->ID );

                $key = $result->ID .'|'. $result->user_email .'|'. $result->display_name;

                $results[$key] = $result->display_name .' &lt;'. $result->user_email .'&gt;';
            }
        }

        // Full name (First Last format)
        $name_results = $wpdb->get_results("
            SELECT DISTINCT m1.user_id, u.user_email, m1.meta_value AS first_name, m2.meta_value AS last_name
            FROM {$wpdb->prefix}users u, {$wpdb->prefix}usermeta m1, {$wpdb->prefix}usermeta m2
            WHERE u.ID = m1.user_id
            AND m1.user_id = m2.user_id
            AND m1.meta_key =  'first_name'
            AND m2.meta_key =  'last_name'
            AND CONCAT_WS(  ' ', m1.meta_value, m2.meta_value ) LIKE  '%{$term}%'
        ");

        if ( $name_results ) {
            foreach ( $name_results as $result ) {
                if ( in_array($result->user_email, $all_emails) ) continue;

                $all_emails[] = $result->user_email;

                $key = $result->user_id .'|'. $result->user_email .'|'. $result->first_name .' '. $result->last_name;

                $results[$key] = $result->first_name .' '. $result->last_name .' &lt;'. $result->user_email .'&gt;';
            }
        }

        $results = apply_filters( 'fue_email_query', $results, $term );

        die(json_encode($results));
    }

    public static function get_custom_fields() {
        $id     = isset($_POST['id']) ? $_POST['id'] : 0;
        $meta   = get_post_custom($id);
        die(json_encode($meta));
    }

    /**
     * Looks for duplicate and similar emails based on different parameters.
     */
    public static function find_dupes() {
        global $wpdb;

        $id             = isset($_POST['id']) ? $_POST['id'] : false;
        $type           = $_POST['type'];
        $interval       = (int)$_POST['interval'];
        $interval_dur   = $_POST['interval_duration'];
        $interval_type  = $_POST['interval_type'];
        $product        = (isset($_POST['product_id'])) ? $_POST['product_id'] : 0;
        $category       = (isset($_POST['category_id'])) ? $_POST['category_id'] : 0;
        $is_generic     = ($type == 'generic') ? true : false;
        $always_send    = (isset($_POST['always_send'])) ? $_POST['always_send'] : 0;

        if ( $type == 'manual' ) die('');

        // see if there is an email setup which is a duplicate of this
        if ( $type == 'generic' ) {
            if (! $id) {
                $sql = "SELECT COUNT(*) FROM `{$wpdb->prefix}followup_emails`
                        WHERE `interval_num` = %d
                        AND `interval_duration` = %s
                        AND `interval_type` = %s
                        AND `always_send` = %d
                        AND `email_type` = 'generic'";
                $count = $wpdb->get_var( $wpdb->prepare($sql, $interval, $interval_dur, $interval_type, $always_send) );
            } else {
                $sql = "SELECT COUNT(*) FROM `{$wpdb->prefix}followup_emails`
                        WHERE `interval_num` = %d
                        AND `interval_duration` = %s
                        AND `interval_type` = %s
                        AND `always_send` = %d
                        AND `id` <> %d
                        AND `email_type` = 'generic'";
                $count = $wpdb->get_var( $wpdb->prepare($sql, $interval, $interval_dur, $interval_type, $always_send, $id) );
            }

            if ($count > 0) {
                die("DUPE");
            }

            if (! $id) {
                $sql = "SELECT COUNT(*) FROM `{$wpdb->prefix}followup_emails`
                        WHERE `interval_duration` = %s
                        AND `interval_type` = %s
                        AND `always_send` = %d
                        AND `email_type` = 'generic'";
                $count = $wpdb->get_var( $wpdb->prepare($sql, $interval_dur, $interval_type) );
            } else {
                $sql = "SELECT COUNT(*) FROM `{$wpdb->prefix}followup_emails`
                        WHERE `interval_duration` = %s
                        AND `interval_type` = %s
                        AND `always_send` = %d
                        AND `id` <> %d
                        AND `email_type` = 'generic'";
                $count = $wpdb->get_var( $wpdb->prepare($sql, $interval_dur, $interval_type, $id) );
            }

            if ($count > 0) {
                die("SIMILAR");
            }
        }

        if ( $always_send ) {
            if (! $id) {
                $sql = "SELECT COUNT(*) FROM `{$wpdb->prefix}followup_emails`
                        WHERE `interval_num` = %d
                        AND `interval_duration` = %s
                        AND `interval_type` = %s
                        AND `product_id` = %d
                        AND `category_id` = %d
                        AND `always_send` = 1";
                $count = $wpdb->get_var( $wpdb->prepare($sql, $interval, $interval_dur, $interval_type, $product, $category) );
            } else {
                $sql = "SELECT COUNT(*) FROM `{$wpdb->prefix}followup_emails`
                        WHERE `interval_num` = %d
                        AND `interval_duration` = %s
                        AND `interval_type` = %s
                        AND `id` <> %d
                        AND `product_id` = %d
                        AND `category_id` = %d
                        AND `always_send` = 1";
                $count = $wpdb->get_var( $wpdb->prepare($sql, $interval, $interval_dur, $interval_type, $id, $product, $category) );
            }

            if ($count > 0) {
                die("DUPE");
            }

            if (! $id) {
                $sql = "SELECT COUNT(*) FROM `{$wpdb->prefix}followup_emails`
                        WHERE `interval_duration` = %s
                        AND `interval_type` = %s
                        AND `always_send` = 1";
                $count = $wpdb->get_var( $wpdb->prepare($sql, $interval_dur, $interval_type) );
            } else {
                $sql = "SELECT COUNT(*) FROM `{$wpdb->prefix}followup_emails`
                        WHERE `interval_duration` = %s
                        AND `interval_type` = %s
                        AND `id` <> %d
                        AND `always_send` = 1";
                $count = $wpdb->get_var( $wpdb->prepare($sql, $interval_dur, $interval_type, $id) );
            }

            if ($count > 0) {
                die("SIMILAR");
            }
        }

        if ( $product != 0 ) {
            if (! $id) {
                $sql = "SELECT COUNT(*) FROM `{$wpdb->prefix}followup_emails`
                        WHERE `interval_num` = %d
                        AND `interval_duration` = %s
                        AND `interval_type` = %s
                        AND `product_id` = %d
                        AND `email_type` = 'normal'";
                $count = $wpdb->get_var( $wpdb->prepare($sql, $interval, $interval_dur, $interval_type, $product) );
            } else {
                $sql = "SELECT COUNT(*) FROM `{$wpdb->prefix}followup_emails`
                        WHERE `interval_num` = %d
                        AND `interval_duration` = %s
                        AND `interval_type` = %s
                        AND `product_id` = %d
                        AND `id` <> %d
                        AND `email_type` = 'normal'";
                $count = $wpdb->get_var( $wpdb->prepare($sql, $interval, $interval_dur, $interval_type, $product, $id) );
            }

            if ( $count > 0)  {
                // this is a duplicate
                die("DUPE");
            }
        } elseif ($category != 0) {
            if (! $id) {
                $sql = "SELECT COUNT(*) FROM `{$wpdb->prefix}followup_emails`
                        WHERE `interval_num` = %d
                        AND `interval_duration` = %s
                        AND `interval_type` = %s
                        AND `category_id` = %d
                        AND `email_type` = 'normal'";
                $count = $wpdb->get_var( $wpdb->prepare($sql, $interval, $interval_dur, $interval_type, $category) );
            } else {
                $sql = "SELECT COUNT(*) FROM `{$wpdb->prefix}followup_emails`
                        WHERE `interval_num` = %d
                        AND `interval_duration` = %s
                        AND `interval_type` = %s
                        AND `category_id` = %d
                        AND `id` <> %d
                        AND `email_type` = 'normal'";
                $count = $wpdb->get_var( $wpdb->prepare($sql, $interval, $interval_dur, $interval_type, $category, $id) );
            }

            if ( $count > 0)  {
                // this is a duplicate
                die("DUPE");
            }
        }

        // check for similar entries
        if ( $product != 0 ) {
            if (! $id) {
                $sql = "SELECT COUNT(*) FROM `{$wpdb->prefix}followup_emails`
                        WHERE `interval_duration` = %s
                        AND `interval_type` = %s
                        AND `product_id` = %d
                        AND `email_type` = 'normal'";
                $count = $wpdb->get_var( $wpdb->prepare($sql, $interval_dur, $interval_type, $product) );
            } else {
                $sql = "SELECT COUNT(*) FROM `{$wpdb->prefix}followup_emails`
                        WHERE `interval_duration` = %s
                        AND `interval_type` = %s
                        AND `product_id` = %d
                        AND `id` <> %d
                        AND `email_type` = 'normal'";
                $count = $wpdb->get_var( $wpdb->prepare($sql, $interval_dur, $interval_type, $product, $id) );
            }

            if ( $count > 0)  {
                // similar entry found
                die("SIMILAR");
            }
        } elseif ($category != 0) {
            if (! $id) {
                $sql = "SELECT COUNT(*) FROM `{$wpdb->prefix}followup_emails`
                        WHERE `interval_duration` = %s
                        AND `interval_type` = %s
                        AND `category_id` = %d
                        AND `email_type` = 'normal'";
                $count = $wpdb->get_var( $wpdb->prepare($sql, $interval_dur, $interval_type, $category) );
            } else {
                $sql = "SELECT COUNT(*) FROM `{$wpdb->prefix}followup_emails`
                        WHERE `interval_duration` = %s
                        AND `interval_type` = %s
                        AND `category_id` = %d
                        AND `id` <> %d
                        AND `email_type` = 'normal'";
                $count = $wpdb->get_var( $wpdb->prepare($sql, $interval_dur, $interval_type, $category, $id) );
            }

            if ( $count > 0)  {
                // similar entry found
                die("SIMILAR");
            }
        }
    }

    public static function send_manual() {
        global $wpdb;

        $post = array_map('stripslashes_deep', $_POST);

        $send_type  = $post['send_type'];
        $recipients = array(); //format: array(user_id, email_address, name)

        if ( $send_type == 'email' ) {
            $key = '0|'. $post['recipient_email'] .'|';
            $recipients[$key] = array(0, $post['recipient_email'], '');
        }

        $recipients = apply_filters('fue_send_manual_emails', $recipients, $post);

        if (! empty($recipients) ) {
            $args = apply_filters( 'fue_manual_email_args', array(
                'email_id'          => $post['id'],
                'recipients'        => $recipients,
                'subject'           => $post['email_subject'],
                'message'           => $post['email_message'],
                'tracking'          => $post['tracking'],
                'send_again'        => (isset($post['send_again']) && $post['send_again'] == 1) ? true : false,
                'interval'          => $post['interval'],
                'interval_duration' => $post['interval_duration']
            ), $post );

            FUE::send_manual_emails( $args );
        }

        wp_redirect( 'admin.php?page=followup-emails&manual_sent=1#manual_mails' );
    }

    public static function process_email_form() {
        $post = array_map( 'stripslashes_deep', $_POST );

        $step   = absint( $post['step'] );
        $id     = ( isset($post['id']) ) ? $post['id'] : '';
        $data   = array();
        $new    = ( empty($id) || (isset($_POST['new']) && $_POST['new'] == 1) ) ? '&new=1' : '';

        if ( $step == 1 ) {
            $data['name']               = $post['name'];
            $data['email_type']         = $post['email_type'];
        } elseif ( $step == 2 ) {
            $data['always_send']        = isset($post['always_send']) ? $post['always_send'] : 0;
            $data['meta']               = $post['meta'];
            $data['subject']            = $post['email_subject'];
            $data['interval_num']       = $post['interval'];
            $data['interval_duration']  = $post['interval_duration'];
            $data['interval_type']      = (isset($post['interval_type'])) ? $post['interval_type'] : '';
            $data['send_date']          = $post['send_date'];
            $data['send_date_hour']     = $post['send_date_hour'];
            $data['send_date_minute']   = $post['send_date_minute'];
            $data['tracking_on']        = isset($post['tracking_on']) ? $post['tracking_on'] : 0;
            $data['tracking_code']      = $post['tracking'];
            $data['product_id']         = isset($post['product_id']) ? $post['product_id'] : 0;
            $data['category_id']        = isset($post['category_id']) ? $post['category_id'] : 0;
        } elseif ( $step == 3 ) {
            $data['message']            = $post['email_message'];
            $data['meta']               = $post['meta'];
        }

        $data = apply_filters( 'fue_pre_save_data', $data, $post );

        $id = FUE::save_email( $data, $id );

        $step++;

        $total_steps = apply_filters('fue_form_total_steps', 3);

        if ( $step > $total_steps ) {
            // process is complete
            $save_type = (empty($new)) ? 'updated' : 'created';
            wp_redirect( 'admin.php?page=followup-emails&'. $save_type .'=1' );
        } else {
            // load next step
            $params = array(
                'step'  => $step,
                'id'    => $id
            );

            wp_redirect( 'admin.php?page=followup-emails-form&step='. $step .'&id='. $id . $new );
        }

        exit;
    }

    static function delete_email() {
        global $wpdb;

        check_admin_referer( 'delete-email' );

        $id = absint( $_GET['id'] );

        // delete
        $wpdb->query( $wpdb->prepare("DELETE FROM `{$wpdb->prefix}followup_email_orders` WHERE `email_id` = %d", $id) );
        $wpdb->query( $wpdb->prepare("DELETE FROM `{$wpdb->prefix}followup_emails` WHERE `id` = %d", $id) );

        do_action('fue_email_deleted', $id);

        wp_redirect('admin.php?page=followup-emails&deleted=true');
        exit;
    }

    public static function update_priorities() {
        global $wpdb;

        $types = self::get_email_types();

        foreach ( $types as $key => $label ) {
            if ( isset($_POST[$key .'_order']) && !empty($_POST[$key .'_order']) ) {
                foreach ( $_POST[$key .'_order'] as $idx => $email_id ) {
                    $priority = $idx + 1;
                    $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->prefix}followup_emails SET `priority` = %d WHERE `id` = %d", $priority, $email_id) );
                }
            }
        }


        if ( isset($_POST['bcc']) ) {
            update_option( 'fue_bcc_types', $_POST['bcc'] );
        }

        do_action( 'fue_update_priorities', $_POST );

        wp_redirect("admin.php?page=followup-emails&tab=list&settings_updated=1");
        exit;
    }

    static function update_settings() {
        global $wpdb;

        $section    = $_POST['section'];
        $imported   = '';

        if ( $section == 'email' ) {
            // update unsubscribe page
            update_option('fue_followup_unsubscribe_page_id', (int)$_POST['unsubscribe_page']);

            // capability
            if ( isset($_POST['roles']) ) {
                $roles      = get_editable_roles();
                $wp_roles   = new WP_Roles();

                foreach ($roles as $key => $role ) {
                    if (in_array($key, $_POST['roles'])) {
                        $wp_roles->add_cap($key, 'manage_follow_up_emails');
                    } else {
                        $wp_roles->remove_cap($key, 'manage_follow_up_emails');
                    }
                }

                // make sure the admin has this capability
                $wp_roles->add_cap('administrator', 'manage_follow_up_emails');
            }

            // disable email wrapping
            $disable = (isset($_POST['disable_email_wrapping'])) ? (int)$_POST['disable_email_wrapping'] : 0;
            update_option( 'fue_disable_wrapping', $disable );

            do_action( 'fue_settings_email_save', $_POST );

        } elseif ( $section == 'crm' ) {
            // bcc
            if ( isset($_POST['bcc']) )
                update_option('fue_bcc', $_POST['bcc']);

            // daily summary emails
            if ( isset($_POST['daily_emails']) ) update_option('fue_daily_emails', $_POST['daily_emails'] );

            if ( isset($_POST['daily_emails_time_hour']) ) {
                $time = $_POST['daily_emails_time_hour'] .':'. $_POST['daily_emails_time_minute'] .' '. $_POST['daily_emails_time_ampm'];
                update_option( 'fue_daily_emails_time', $time );

                $next_send = strtotime( date('m/d/Y '. $time) );

                if ( current_time('timestamp') > $next_send ) {
                    // already in the past. Set it for tomorrow
                    $next_send += 86400;
                    update_option('fue_next_summary', $next_send);
                }
            }

            do_action( 'fue_settings_crm_save', $_POST );
        } elseif ( $section == 'system' ) {
            // process importing request
            if ( isset($_FILES['emails_file']) && is_uploaded_file($_FILES['emails_file']['tmp_name']) ) {
                $fh         = @fopen( $_FILES['emails_file']['tmp_name'], 'r' );
                $columns    = array();
                $i          = 0;

                while ( $row = fgetcsv($fh) ) {
                    $i++;

                    if ( $i == 1 ) {
                        foreach ( $row as $idx => $col ) {
                            $columns[$idx] = $col;
                        }

                        continue;
                    }

                    $data = array();

                    foreach ( $columns as $idx => $col ) {
                        $data[$col] = $row[$idx];
                    }

                    $wpdb->insert( $wpdb->prefix .'followup_emails', $data );

                }

                $imported = '&imported=1';
            }

            // restore settings file from backup
            if ( isset($_FILES['settings_file']) && is_uploaded_file($_FILES['settings_file']['tmp_name']) ) {
                $fh         = @fopen( $_FILES['settings_file']['tmp_name'], 'r' );
                $i          = 0;

                while ( $row = fgetcsv($fh) ) {
                    $i++;

                    if ( $i == 1 ) {
                        continue;
                    }

                    update_option( $row[0], $row[1] );

                }

                $imported = '&imported=1';
            }

            // usage data
            if ( isset($_POST['disable_usage_data']) && $_POST['disable_usage_data'] == 1 ) {
                update_option( 'fue_disable_usage_data', 1 );
            } else {
                delete_option( 'fue_disable_usage_data' );
            }

            do_action( 'fue_settings_system_save', $_POST );
        } else {
            do_action( 'fue_settings_save', $_POST );
        }

        wp_redirect("admin.php?page=followup-emails-settings&settings_updated=1$imported");
        exit;
    }

    static function manage_optout() {
        global $wpdb;

        $post       = stripslashes_deep($_POST);

        if ( isset($post['button_add']) && $post['button_add'] == 'Add' ) {
            // add an email address to the excludes list
            $email = $post['email_address'];

            // make sure it is a valid email address
            if ( !is_email($email) ) {
                wp_redirect( 'admin.php?page=followup-emails-optouts&error='. urlencode(__('The email address is invalid', 'follow_up_emails') ) );
                exit;
            }

            FUE::exclude_email_address( $email );

            wp_redirect( 'admin.php?page=followup-emails-optouts&added='. urlencode($email) );
            exit;
        } elseif ( isset($post['button_restore']) && $post['button_restore'] == 'Apply' ) {
            $emails     = $post['email'];
            $email_ids  = '';

            if ( is_array($emails) && !empty($emails) ) {
                $email_ids = "'". implode("','", $emails) ."'";
            }

            if (! empty($email_ids) ) {
                $wpdb->query("DELETE FROM {$wpdb->prefix}followup_email_excludes WHERE id IN($email_ids)");
            }

            wp_redirect( 'admin.php?page=followup-emails-optouts&restored='. count($emails) );
            exit;
        }

        wp_redirect( 'admin.php?page=followup-emails-optouts' );
        exit;
    }

    static function reset_reports() {
        global $wpdb;

        $data = $_POST;

        FUE_Reports::reset($data);

        wp_redirect( 'admin.php?page=followup-emails-reports&cleared=1' );
        exit;

    }

    static function backup_emails() {
        global $wpdb;

        check_admin_referer( 'fue_backup' );

        $contents = "";

        $emails = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}followup_emails", ARRAY_A);

        foreach ( $emails as $email ) {
            $keys = array_keys($email);

            if (empty($contents)) {
                $headers = array();
                foreach ($keys as $key) {
                    if ( $key == 'id' ) continue;
                    $headers[] = $key;
                }
                $contents .= self::array_to_csv($headers);
            }

            $row = array();
            foreach ( $keys as $key ) {
                if ($key == 'id') continue;

                $row[] = $email[$key];
            }

            $contents .= self::array_to_csv($row);

        }

        header('Content-Type: application/csv');
        header('Content-Disposition:attachment;filename=follow_up_emails.csv');
        header('Pragma: no-cache');

        echo $contents;
        exit;
    }

    static function backup_settings() {
        check_admin_referer('fue_backup');

        $contents = '';

        $headers = array('meta_key', 'meta_value');
        $contents .= self::array_to_csv($headers);

        // unsubscribe page
        $row = array('fue_followup_unsubscribe_page_id', get_option('fue_followup_unsubscribe_page_id'));
        $contents .= self::array_to_csv($row);

        // bcc
        $row = array('fue_bcc', get_option('fue_bcc'));
        $contents .= self::array_to_csv($row);

        $row = array('fue_bcc_types', maybe_serialize(get_option('fue_bcc_types')));
        $contents .= self::array_to_csv($row);

        // daily summary emails
        $row = array('fue_daily_emails', get_option('fue_daily_emails'));
        $contents .= self::array_to_csv($row);

        $row = array('fue_last_summary', get_option('fue_last_summary'));
        $contents .= self::array_to_csv($row);
        $row = array('fue_next_summary', get_option('fue_next_summary'));
        $contents .= self::array_to_csv($row);

        // disable email wrapping
        $row = array('fue_disable_wrapping', get_option('fue_disable_wrapping'));
        $contents .= self::array_to_csv($row);

        header('Content-Type: application/csv');
        header('Content-Disposition:attachment;filename=follow_up_settings.csv');
        header('Pragma: no-cache');

        echo $contents;
        exit;
    }

    public static function ajax_clone_email() {
        $id     = $_POST['id'];
        $name   = $_POST['name'];

        $new_email_id = FUE::clone_email($id, $name);

        if (! is_wp_error($new_email_id)) {
            $resp = array(
                'status'    => 'OK',
                'id'        => $new_email_id,
                'url'       => 'admin.php?page=followup-emails-form&step=1&id='. $new_email_id
            );
        } else {
            $resp = array(
                'status'    => 'ERROR',
                'message'   => $new_email_id->get_error_message()
            );
        }

        die(json_encode($resp));
    }

    public static function ajax_toggle_status() {
        global $wpdb;
        $id     = $_POST['id'];
        $status = $wpdb->get_var( $wpdb->prepare("SELECT status FROM {$wpdb->prefix}followup_emails WHERE id = %d", $id) );
        $resp   = array('ack' => 'OK');

        if ($status == 0) {
            // activate
            $wpdb->update($wpdb->prefix .'followup_emails', array('status' => 1), array('id' => $id));
            $resp['new_status'] = __('Active', 'follow_up_emails');
            $resp['new_action'] = __('Deactivate', 'follow_up_emails');
        } else {
            // deactivate
            $wpdb->update($wpdb->prefix .'followup_emails', array('status' => 0), array('id' => $id));
            $resp['new_status'] = __('Inactive', 'follow_up_emails');
            $resp['new_action'] = __('Activate', 'follow_up_emails');
        }

        die(json_encode($resp));
    }

    public static function ajax_toggle_queue_status() {
        global $wpdb;
        $id     = $_POST['id'];
        $status = $wpdb->get_var( $wpdb->prepare("SELECT status FROM {$wpdb->prefix}followup_email_orders WHERE id = %d", $id) );
        $resp   = array('ack' => 'OK');

        if ($status == 0) {
            // activate
            $wpdb->update($wpdb->prefix .'followup_email_orders', array('status' => 1), array('id' => $id));

            // if using action-scheduler, re-create the task
            if ( FollowUpEmails::$scheduling_system == 'action-scheduler' ) {
                $param = array(
                    'email_order_id'    => $id
                );
                $send_time = $wpdb->get_var( $wpdb->prepare("SELECT send_on FROM {$wpdb->prefix}followup_email_orders WHERE id = %d", $id) );
                wc_schedule_single_action( $send_time, 'sfn_followup_emails', $param, 'fue' );
            }

            $resp['new_status'] = __('Queued', 'follow_up_emails');
            $resp['new_action'] = __('Do not send', 'follow_up_emails');
        } else {
            // deactivate
            $wpdb->update($wpdb->prefix .'followup_email_orders', array('status' => 0), array('id' => $id));

            // if using action-scheduler, delete the task
            $param = array(
                'email_order_id'    => $id
            );
            wc_unschedule_action( 'sfn_followup_emails',  $param, 'fue' );

            $resp['new_status'] = __('Suspended', 'follow_up_emails');
            $resp['new_action'] = __('Re-enable', 'follow_up_emails');
        }

        die(json_encode($resp));
    }

    /**
     * Count the number of rows to be imported into Action Scheduler.
     * Only loads orders that have not been sent yet.
     */
    public static function ajax_count_import_rows() {
        global $wpdb;

        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}followup_email_orders WHERE is_sent = 0");

        die( json_encode(array('total' => $count)) );
    }

    public static function ajax_import() {
        $next = $_POST['next'];

        $next   = self::action_scheduler_import($next, 50);
        $usage  = memory_get_usage(true);
        $limit  = ini_get('memory_limit');

        if ($usage < 1024)
            $usage = $usage." bytes";
        elseif ($usage < 1048576)
            $usage = round($usage/1024,2)." kilobytes";
        else
            $usage = round($usage/1048576,2)." megabytes";

        die( json_encode(array('next' => $next, 'usage' => $usage, 'limit' => $limit)) );
    }

    public static function ajax_import_start() {
        // disable email sending for a maximum of 1 hour
        // while importing all records
        set_transient( 'fue_importing', 'true', 3600 );
    }

    public static function ajax_import_complete() {
        global $wpdb;

        // delete unsent emails in the queue
        //$wpdb->query("DELETE FROM {$wpdb->prefix}followup_email_orders WHERE is_sent = 0");

        // use the action scheduler system
        update_option( 'fue_scheduling_system', 'action-scheduler' );

        // convert all scheduled events to use action-scheduler
        wp_clear_scheduled_hook('sfn_followup_emails');
        wp_clear_scheduled_hook('fue_send_summary');
        wp_clear_scheduled_hook('sfn_optimize_tables');

        if (! wc_next_scheduled_action( 'sfn_optimize_tables' ) ) {
            wc_schedule_recurring_action( time(), 604800, 'sfn_optimize_tables', array(), 'fue' );
        }

        // done importing
        delete_transient( 'fue_importing' );
    }

    public static function ajax_use_wp_cron() {

        set_transient( 'fue_importing', 'true', 3600 );

        // use the action scheduler system
        update_option( 'fue_scheduling_system', 'wp-cron' );

        if ( function_exists('wc_get_scheduled_actions') ) {
            $actions = wc_get_scheduled_actions(array('hook' => 'sfn_followup_emails'));

            if ( $actions ) {
                foreach ( $actions as $action ) {
                    $args = $action->get_args();
                    wc_unschedule_action( 'sfn_followup_emails', $args, 'fue' );
                }
            }
        }

        FollowUpEmails::install_cronjobs();

        // done importing
        delete_transient( 'fue_importing' );

    }

    private static function action_scheduler_import( $pos = 0, $length = 50 ) {
        global $wpdb;

        $rows = $wpdb->get_results("SELECT id, send_on FROM {$wpdb->prefix}followup_email_orders WHERE is_sent = 0 ORDER BY id ASC LIMIT $pos, $length");

        if (! $rows ) {
            return false;
        }

        foreach ( $rows as $row ) {
            $data = array(
                'email_order_id' => $row->id
            );

            $job_id = wc_schedule_single_action( $row->send_on, 'sfn_followup_emails', $data, 'fue' );

            $pos++;
        }

        return $pos;
    }

    private static function array_to_csv( $fields = array(), $delimiter = ',', $enclosure = '"', $encloseAll = false, $nullToMysqlNull = false ) {
        $delimiter_esc = preg_quote($delimiter, '/');
        $enclosure_esc = preg_quote($enclosure, '/');

        $output = array();
        foreach ( $fields as $field ) {
            if ($field === null && $nullToMysqlNull) {
                $output[] = 'NULL';
                continue;
            }

            // Enclose fields containing $delimiter, $enclosure or whitespace
            if ( $encloseAll || preg_match( "/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field ) ) {
                $output[] = $enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure;
            }
            else {
                $output[] = $field;
            }
        }

        return implode( $delimiter, $output ) ."\n";
    }

    /**
    * Check is the installed version of WooCommerce is 2.1 or newer
    */
   public static function is_woocommerce_pre_2_1() {
        return !function_exists('wc_add_notice');
    }


}
