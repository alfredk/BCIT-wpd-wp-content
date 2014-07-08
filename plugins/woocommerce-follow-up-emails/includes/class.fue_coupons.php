<?php

class FUE_Coupons{

    public function __construct() {
        add_action('fue_menu', array($this, 'menu'), 20);

        // settings styles and scripts
        add_action('admin_enqueue_scripts', array($this, 'settings_scripts'));

        add_action( 'fue_settings_tab_controller', array(&$this, 'settings_tab_controller') );
        add_action( 'fue_settings_notification', array(&$this, 'print_notifications') );

        add_action( 'admin_post_sfn_followup_new_coupon', array(&$this, 'create_coupon') );
        add_action( 'admin_post_sfn_followup_edit_coupon', array(&$this, 'update_coupon') );

        add_filter( 'fue_email_defaults', array(&$this, 'form_defaults'), 10, 2 );
        add_filter( 'fue_email_pre_save', array($this, 'save_email'), 10, 2 );
        add_action( 'fue_email_form_after_interval', array(&$this, 'email_form_fields') );
        add_action( 'fue_email_variables_list', array(&$this, 'form_variables') );
        add_action( 'fue_email_manual_variables_list', array(&$this, 'form_variables') );
        add_action( 'fue_email_form_script', array(&$this, 'form_script') );

        add_action( 'fue_email_form_before_message', array($this, 'before_message') );

        //add_filter( 'fue_manual_email_defaults', array(&$this, 'form_edit_defaults'), 10, 2 );
        //add_action( 'fue_manual_email_form_before_message', array(&$this, 'form_before_message') );
        //add_action( 'fue_manual_email_form_script', array(&$this, 'form_script') );

        add_action( 'fue_email_created', array(&$this, 'email_created'), 10, 2 );
        add_action( 'fue_email_updated', array(&$this, 'email_updated'), 10, 2 );

        add_action( 'woocommerce_checkout_order_processed', array(&$this, 'order_processed'), 10, 2 );

        add_filter( 'fue_send_email_message', array(&$this, 'insert_coupon_code'), 10, 2 );
        add_filter( 'fue_send_test_email_message', array(&$this, 'insert_test_coupon_code'), 10 );

        // Email Cloned
        add_action( 'fue_email_cloned', array($this, 'cloned'), 10, 2 );

        // Email Deleted
        add_action( 'fue_email_deleted', array($this, 'deleted') );
    }

    public function menu() {
        add_submenu_page( 'followup-emails', __('Coupons', 'follow_up_emails'), __('Coupons', 'follow_up_emails'), 'manage_follow_up_emails', 'followup-emails-coupons', 'FUE_Coupons::settings_main' );
    }

    public function settings_scripts() {
        global $woocommerce;

        if ( isset($_GET['page']) && $_GET['page'] == 'followup-emails-coupons') {
            if (! function_exists('wc_add_notice') ) {
                woocommerce_admin_scripts();
            } else {
                $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
                wp_register_script( 'ajax-chosen', WC()->plugin_url() . '/assets/js/chosen/ajax-chosen.jquery' . $suffix . '.js', array('jquery', 'chosen'), WC()->version );
                wp_register_script( 'chosen', WC()->plugin_url() . '/assets/js/chosen/chosen.jquery' . $suffix . '.js', array('jquery'), WC()->version );
            }

            wp_enqueue_script( 'woocommerce_admin' );
            wp_enqueue_script('farbtastic');
            wp_enqueue_script( 'ajax-chosen' );
            wp_enqueue_script( 'chosen' );
            wp_enqueue_script( 'jquery-ui-datepicker', null, array('jquery-ui-core') );

            wp_enqueue_style( 'woocommerce_admin_styles', $woocommerce->plugin_url() . '/assets/css/admin.css' );
            wp_enqueue_style( 'jquery-ui-css', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.21/themes/base/jquery-ui.css' );

        }
    }

    public function activated() {
        global $wpdb;

    }

    public function deactivated() {

    }

    public static function is_installed() {
        return true;
    }

    public static function is_licensed() {
        return true;
    }

    public function install() {

    }

    public static function create_coupon() {
        global $wpdb;

        $_POST = array_map('stripslashes_deep', $_POST);

        $data = array(
            'coupon_name'   => $_POST['name'],
            'coupon_prefix' => $_POST['prefix'],
            'coupon_type'   => $_POST['type'],
            'amount'        => floatval($_POST['amount']),
            'individual'    => (isset($_POST['individual_use']) && $_POST['individual_use'] == 'yes') ? 1 : 0,
            'before_tax'    => (isset($_POST['before_tax']) && $_POST['before_tax'] == 'yes') ? 1 : 0,
            'free_shipping' => (isset($_POST['free_shipping']) && $_POST['free_shipping'] == 'yes') ? 1 : 0,
            'minimum_amount'=> (isset($_POST['minimum_amount']) && $_POST['minimum_amount'] > 0 ) ? (float)$_POST['minimum_amount'] : '',
            'usage_limit'   => (isset($_POST['usage_limit']) && !empty($_POST['usage_limit'])) ? (int)$_POST['usage_limit'] : '',
            'expiry_value'  => (isset($_POST['expiry_value']) && !empty($_POST['expiry_value'])) ? intval($_POST['expiry_value']) : '',
            'expiry_type'   => $_POST['expiry_type']
        );

        if ( isset($_POST['product_ids']) && !empty($_POST['product_ids']) ) {
            $data['product_ids'] = implode(',', $_POST['product_ids']);
        }

        if ( isset($_POST['exclude_product_ids']) && !empty($_POST['exclude_product_ids']) ) {
            $data['exclude_product_ids'] = implode(',', $_POST['exclude_product_ids']);
        }

        if ( isset($_POST['product_categories']) && !empty($_POST['product_categories']) ) {
            $data['product_categories'] = serialize($_POST['product_categories']);
        }

        if ( isset($_POST['exclude_product_categories']) && !empty($_POST['exclude_product_categories']) ) {
            $data['exclude_product_categories'] = serialize($_POST['exclude_product_categories']);
        }

        $wpdb->insert($wpdb->prefix .'followup_coupons', $data);

        wp_redirect('admin.php?page=followup-emails-coupons&coupon_created=1');
        exit;
    }

    public static function update_coupon() {
        global $wpdb;

        $_POST = array_map('stripslashes_deep', $_POST);

        $data = array(
            'coupon_name'   => $_POST['name'],
            'coupon_prefix' => $_POST['prefix'],
            'coupon_type'   => $_POST['type'],
            'amount'        => floatval($_POST['amount']),
            'individual'    => (isset($_POST['individual_use']) && $_POST['individual_use'] == 'yes') ? 1 : 0,
            'before_tax'    => (isset($_POST['before_tax']) && $_POST['before_tax'] == 'yes') ? 1 : 0,
            'free_shipping' => (isset($_POST['free_shipping']) && $_POST['free_shipping'] == 'yes') ? 1 : 0,
            'minimum_amount'=> (isset($_POST['minimum_amount']) && $_POST['minimum_amount'] > 0 ) ? (float)$_POST['minimum_amount'] : '',
            'usage_limit'   => (isset($_POST['usage_limit']) && !empty($_POST['usage_limit'])) ? (int)$_POST['usage_limit'] : '',
            'expiry_value'  => (isset($_POST['expiry_value']) && !empty($_POST['expiry_value'])) ? intval($_POST['expiry_value']) : '',
            'expiry_type'   => $_POST['expiry_type']
        );

        if ( isset($_POST['product_ids']) && !empty($_POST['product_ids']) ) {
            $data['product_ids'] = implode(',', $_POST['product_ids']);
        } else {
            $data['product_ids'] = '';
        }

        if ( isset($_POST['exclude_product_ids']) && !empty($_POST['exclude_product_ids']) ) {
            $data['exclude_product_ids'] = implode(',', $_POST['exclude_product_ids']);
        } else {
            $data['exclude_product_ids'] = '';
        }

        if ( isset($_POST['product_categories']) && !empty($_POST['product_categories']) ) {
            $data['product_categories'] = serialize($_POST['product_categories']);
        } else {
            $data['product_categories'] = '';
        }

        if ( isset($_POST['exclude_product_categories']) && !empty($_POST['exclude_product_categories']) ) {
            $data['exclude_product_categories'] = serialize($_POST['exclude_product_categories']);
        } else {
            $data['exclude_product_categories'] = '';
        }

        $wpdb->update($wpdb->prefix .'followup_coupons', $data, array('id' => $_POST['id']));

        wp_redirect('admin.php?page=followup-emails-coupons&coupon_updated=1');
        exit;
    }

    public function insert_test_coupon_code( $message ) {
        $message = str_replace('{coupon_code}', 'CODETEST', $message);

        return $message;
    }

    public function insert_coupon_code( $message, $email_order ) {
        global $wpdb;

        $order          = ($email_order->order_id != 0) ? new WC_Order( $email_order->order_id ) : false;
        $email          = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}followup_emails` WHERE `id` = %d", $email_order->email_id) );
        $email_coupon   = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}followup_email_coupons` WHERE email_id = %d", $email_order->email_id) );

        if ( !$email || !$email_coupon ) return $message;

        if ( $email_order->order_id != 0 ) {
            // order
            $order      = new WC_Order($email_order->order_id);

            if ( isset($order->user_id) && $order->user_id > 0 ) {
                $wp_user    = new WP_User( $order->user_id );
                $email_to   = $wp_user->user_email;
            } else {
                $email_to   = $order->billing_email;
            }
        } else {
            $wp_user    = new WP_User( $email_order->user_id );
            $email_to   = $wp_user->user_email;
        }


        $coupon_code    = '';
        $coupon         = false;
        if ( $email_coupon->send_coupon == 1 && $email_coupon->coupon_id != 0 ) {
            $coupon         = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_coupons WHERE `id` = %d", $email_coupon->coupon_id) );
            $coupon_code    = FUE_Coupons::add_prefix( self::generateCouponCode(), $coupon, $order );
            $coupon_array   = array(
                'post_title'    => $coupon_code,
                'post_author'   => 1,
                'post_date'     => date("Y-m-d H:i:s"),
                'post_status'   => 'publish',
                'comment_status'=> 'closed',
                'ping_status'   => 'closed',
                'post_name'     => $coupon_code,
                'post_parent'   => 0,
                'menu_order'    => 0,
                'post_type'     => 'shop_coupon'
            );
            $coupon_id = wp_insert_post($coupon_array);
            $wpdb->query("UPDATE {$wpdb->prefix}posts SET post_status = 'publish' WHERE ID = $coupon_id");

            $expiry = '';
            if ($coupon->expiry_value > 0 && !empty($coupon->expiry_type)) {
                $exp    = $coupon->expiry_value .' '. $coupon->expiry_type;
                $now    = current_time('mysql');
                $ts     = strtotime("$now +$exp");

                if ($ts !== false) {
                    $expiry = date('Y-m-d', $ts);
                }
            }

            update_post_meta($coupon_id, 'discount_type', $coupon->coupon_type);
            update_post_meta($coupon_id, 'coupon_amount', $coupon->amount);
            update_post_meta($coupon_id, 'individual_use', ($coupon->individual == 0) ? 'no' : 'yes');
            update_post_meta($coupon_id, 'product_ids', $coupon->product_ids);
            update_post_meta($coupon_id, 'exclude_product_ids', $coupon->exclude_product_ids);
            update_post_meta($coupon_id, 'usage_limit', $coupon->usage_limit);
            update_post_meta($coupon_id, 'expiry_date', $expiry);
            update_post_meta($coupon_id, 'apply_before_tax', ($coupon->before_tax == 0) ? 'no' : 'yes');
            update_post_meta($coupon_id, 'free_shipping', ($coupon->free_shipping == 0) ? 'no' : 'yes');
            update_post_meta($coupon_id, 'product_categories', maybe_unserialize($coupon->product_categories));
            update_post_meta($coupon_id, 'exclude_product_categories', maybe_unserialize($coupon->exclude_product_categories));
            update_post_meta($coupon_id, 'minimum_amount', $coupon->minimum_amount);

            $product_categories = '';
            $exclude_product_categories = '';

            if (! empty($coupon->product_categories) ) {
                $product_categories = unserialize($coupon->product_categories);
            }
            update_post_meta($coupon_id, 'product_categories', $product_categories);

            if (! empty($coupon->exclude_product_categories) ) {
                $exclude_product_categories = unserialize($coupon->exclude_product_categories);
            }
            update_post_meta($coupon_id, 'exclude_product_categories', $exclude_product_categories);

            $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->prefix}followup_coupons SET `usage_count` = `usage_count` + 1 WHERE `id` = %d", $coupon->id) );

            FUE_Coupons::coupon_log($coupon_id, $coupon->coupon_name, $email->name, $email_to, $coupon_code);

            // record into the email_orders table
            $wpdb->query( $wpdb->prepare("UPDATE `{$wpdb->prefix}followup_email_order_coupons` SET `coupon_name` = %s, `coupon_code` = %s WHERE `email_order_id` = %d", $coupon->coupon_name, $coupon_code, $email_order->id) );

            $message = str_replace('{coupon_code}', $coupon_code, $message);
        }

        return $message;
    }

    public static function generateCouponCode() {
        global $wpdb;

        $chars = 'abcdefghijklmnopqrstuvwxyz01234567890';
        do {
            $code = '';
            for ($x = 0; $x < 8; $x++) {
                $code .= $chars[ rand(0, strlen($chars)-1) ];
            }

            $check = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->prefix}posts` WHERE `post_title` = %s AND `post_type` = 'shop_coupon'", $code));

            if ($check == 0) break;
        } while (true);

        return $code;
    }

    public static function coupon_log($id, $name, $email_name, $email_address, $coupon_code) {
        global $wpdb;

        $log = array(
            'coupon_id'     => $id,
            'coupon_name'   => $name,
            'email_name'    => $email_name,
            'email_address' => $email_address,
            'date_sent'     => date('Y-m-d H:i:s'),
            'coupon_code'   => $coupon_code
        );
        $wpdb->insert( $wpdb->prefix .'followup_coupon_logs', $log );
    }

    public static function mark_used_coupon( $code ) {
        global $wpdb;

        $date = date('Y-m-d H:i:s');
        $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->prefix}followup_coupon_logs SET `coupon_used` = 1, `date_used` = %s WHERE `coupon_code` = %s", $date, $code) );

        return true;
    }

    public static function add_prefix( $code, $coupon, $order ) {
        if ( $coupon->coupon_prefix != '' && $order !== false ) {
            $prefix = '';

            switch ( $coupon->coupon_prefix ) {
                case '{customer_first_name}':
                    $prefix = $order->billing_first_name .'_';
                    break;

                case '{customer_last_name}':
                    $prefix = $order->billing_last_name .'_';
                    break;

                case '{order_number}':
                    $prefix = $order->id .'_';
                    break;
            }

            $code = $prefix . $code;
        }

        return $code;
    }

    public static function settings_main() {
        global $wpdb;

        $action = (isset($_GET['action'])) ? $_GET['action'] : 'list';

        if ( $action == 'list' ) {
            echo FUE_Coupons::coupons_html();
        } elseif ( $action == 'new-coupon' ) {
            FUE_Coupons::new_coupon_html();
        } elseif ( $action == 'edit-coupon' ) {
            FUE_Coupons::edit_coupon_html();
        } elseif ( $action == 'delete-coupon' ) {
            $id = $_GET['id'];
            // delete
            $wpdb->query( $wpdb->prepare("DELETE FROM `{$wpdb->prefix}followup_coupons` WHERE `id` = %d", $id) );

            wp_redirect('admin.php?page=followup-emails-coupons&coupon_deleted=true');
            exit;
        }

    }

    public function settings_tab_controller( $tab ) {
        global $wpdb;

        if ($tab == 'coupons') {
            fue_settings_header($tab);
            FUE_Coupons::settings_main();
            fue_settings_footer();
        } elseif ($tab == 'new-coupon') {
            fue_settings_header($tab);
            FUE_Coupons::new_coupon_html();
            fue_settings_footer();
        } elseif ($tab == 'edit-coupon') {
            fue_settings_header($tab);
            FUE_Coupons::edit_coupon_html();
            fue_settings_footer();
        } elseif ($tab == 'delete-coupon') {
            $id = $_GET['id'];
            // delete
            $wpdb->query( $wpdb->prepare("DELETE FROM `{$wpdb->prefix}followup_coupons` WHERE `id` = %d", $id) );

            wp_redirect('admin.php?page=wc-followup-emails&tab=coupons&coupon_deleted=true');
            exit;
        }
    }

    public function print_notifications() {
        if (isset($_GET['coupon_created'])): ?>
        <div id="message" class="updated"><p><?php _e('Coupon created', 'follow_up_emails'); ?></p></div>
        <?php
        endif;

        if (isset($_GET['coupon_deleted'])): ?>
        <div id="message" class="updated"><p><?php _e('Coupon deleted!', 'follow_up_emails'); ?></p></div>
        <?php
        endif;

        if (isset($_GET['coupon_updated'])): ?>
        <div id="message" class="updated"><p><?php _e('Coupon updated', 'follow_up_emails'); ?></p></div>
        <?php
        endif;
    }

    public function email_created( $id, $data ) {
        global $wpdb;

        if (!isset($_POST['step']) || $_POST['step'] != 2) return;

        $insert = array(
            'email_id'      => $id,
            'send_coupon'   => 0,
            'coupon_id'     => 0
        );

        if ( isset($_POST['send_coupon']) && $_POST['send_coupon'] == 1 ) {
            $insert['send_coupon']  = 1;
            $insert['coupon_id']    = $_POST['coupon_id'];
        }

        $wpdb->insert( $wpdb->prefix .'followup_email_coupons', $insert );
    }

    public function email_updated( $id, $data ) {
        global $wpdb;

        if (!isset($_POST['step']) || $_POST['step'] != 2) return;

        $update = array(
            'send_coupon'   => 0,
            'coupon_id'     => 0
        );

        if ( isset($_POST['send_coupon']) && $_POST['send_coupon'] == 1 ) {
            $update['send_coupon']  = 1;
            $update['coupon_id']    = $_POST['coupon_id'];
        }

        $check = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}followup_email_coupons WHERE email_id = $id");

        if ( $check > 0 ) {
            $wpdb->update( $wpdb->prefix .'followup_email_coupons', $update, array('email_id' => $id) );
        } else {
            $insert = $update;
            $insert['email_id'] = $id;
            $wpdb->insert( $wpdb->prefix .'followup_email_coupons', $insert );
        }
    }

    public function form_defaults( $defaults = array(), $id = '' ) {
        global $wpdb;

        if (! $id ) {
            $defaults['send_coupon']    = '';
            $defaults['coupon_id']      = 0;
        } else {
            $row = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}followup_email_coupons WHERE email_id = {$id}");

            if ($row) {
                $defaults['send_coupon']    = $row->send_coupon;
                $defaults['coupon_id']      = $row->coupon_id;
            } else {
                $defaults['send_coupon']    = '';
                $defaults['coupon_id']      = 0;
            }
        }

        return $defaults;
    }

    public function save_email( $data, $id = '' ) {
        global $wpdb;

        unset($data['send_coupon'], $data['coupon_id']);

        return $data;
    }

    public function email_form_fields($defaults = array()) {
        global $wpdb;
        $coupons    = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}followup_coupons ORDER BY `coupon_name` ASC");

        ?>
        <div class="field send_coupon_tr">
            <?php if (! empty($coupons) ): ?>
            <label for="send_coupon">
                <input type="checkbox" name="send_coupon" id="send_coupon" value="1" <?php if ($defaults['send_coupon'] == 1) echo 'checked'; ?> />

                <?php _e('Generate &amp; Send a Coupon Code', 'follow_up_emails'); ?>
            </label>
            <?php
            else:
            ?>
            <label for="send_coupon"><?php _e('Generate &amp; Send a Coupon Code', 'follow_up_emails'); ?></label>
            <a href="admin.php?page=followup-emails-coupons&action=new-coupon" class="button-secondary"><?php _e('No coupons found. Create a Coupon', 'follow_up_emails'); ?></a>
            <?php
            endif;
            ?>
        </div>

        <div class="field class_coupon coupon_tr">
            <label for="coupon_id"><?php _e('Select a Coupon', 'follow_up_emails'); ?></label>

            <select name="coupon_id" id="coupon_id" class="chzn-select" data-placeholder="<?php _e('Select a coupon&hellip;', 'follow_up_emails'); ?>" style="width: 400px;">
                <option value="0" <?php if ($defaults['coupon_id'] == 0) echo 'selected'; ?>>Select a coupon&hellip;</option>
                <?php
                foreach ( $coupons as $coupon ):
                    $selected = ($defaults['coupon_id'] == $coupon->id) ? 'selected' : '';
                ?>
                <option value="<?php echo esc_attr($coupon->id); ?>" <?php echo $selected; ?>><?php echo esc_attr($coupon->coupon_name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }

    public function form_variables() {
        global $woocommerce;
        echo '<li class="var hideable var_coupon class_coupon"><strong>{coupon_code}</strong> <img class="help_tip" title="'. __('Coupon code generated', 'follow_up_emails') .'" src="'. $woocommerce->plugin_url() .'/assets/images/help.png" width="16" height="16" /></li>';
    }

    public function before_message( $defaults ) {
        if ( $defaults['send_coupon'] == 1 )
            echo '<input type="checkbox" id="send_coupon" value="1" checked />';
    }

    public function form_script() {
    ?>
    jQuery("#send_coupon").change(function() {
        if (jQuery(this).attr("checked")) {
            jQuery(".class_coupon").show();
        } else {
            jQuery(".class_coupon").hide();
        }
    });

    if (jQuery("#send_coupon").attr("checked")) {
        jQuery(".class_coupon").show();
    } else {
        jQuery(".class_coupon").hide();
    }
    <?php
    }

    public function order_processed( $order_id, $post ) {
        if ( class_exists('FUE_Reports') ) {
            // look for used coupons
            $coupons = get_post_meta( $order_id, 'coupons' );

            if (! empty($coupons) ) {
                // look for this coupon in the coupons log and update it
                foreach ( $coupons as $coupon ) {
                    if ( strpos($coupon, ',') !== false ) {
                        $codes = explode(',', $coupon);
                    } else {
                        $codes = array($coupon);
                    }

                    foreach ( $codes as $code ) {
                        $code = trim($code);
                        if ( empty($code) ) continue;

                        FUE_Coupons::mark_used_coupon( $code );
                    }
                }
            }
        }
    }

    public static function coupons_html() {
        global $wpdb, $woocommerce;

        $coupons    = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}followup_coupons ORDER BY `coupon_name` ASC");
        $html       = '
    <div class="wrap woocommerce">
        <div class="icon32"><img src="'. FUE_TEMPLATES_URL .'/images/send_mail.png' .'" /></div>
        <h2>
            '. __('Follow-Up Emails &raquo; Email Coupons', 'follow_up_emails') .'
            <a href="admin.php?page=followup-emails-coupons&action=new-coupon" class="add-new-h2">'. __('Add Coupon', 'wc_followup_emalis') .'</a>
        </h2>

    <form action="admin-post.php" method="post">
        <table class="wp-list-table widefat fixed posts">
            <thead>
                <tr>
                    <th scope="col" id="name" class="manage-column column-name" style="">'. __('Name', 'follow_up_emails') .'</th>
                    <th scope="col" id="type" class="manage-column column-type" style="">'. __('Type', 'follow_up_emails') .'</th>
                    <th scope="col" id="amount" class="manage-column column-amount" style="">'. __('Amount', 'follow_up_emails') .'</th>
                    <th scope="col" id="usage_count" class="manage-column column-usage_count" style="">'. __('Sent', 'follow_up_emails') .'</th>
                </tr>
            </thead>
            <tbody id="the_list">';

        if (empty($coupons)):
            $html .= '
                <tr scope="row">
                    <th colspan="4">'. __('No coupons available', 'follow_up_emails') .'</th>
                </tr>';
        else:
            foreach ($coupons as $coupon):
                $html .= '
                <tr scope="row">
                    <td class="post-title column-title">
                        <strong><a class="row-title" href="admin.php?page=followup-emails-coupons&action=edit-coupon&id='. $coupon->id .'">'. stripslashes($coupon->coupon_name) .'</a></strong>
                        <div class="row-actions">
                            <span class="edit"><a href="admin.php?page=followup-emails-coupons&action=edit-coupon&id='. $coupon->id .'">'. __('Edit', 'follow_up_emails') .'</a></span>
                            |
                            <span class="trash"><a onclick="return confirm(\'Really delete this entry?\');" href="admin.php?page=followup-emails-coupons&action=delete-coupon&id='. $coupon->id .'">'. __('Delete', 'follow_up_emails') .'</a></span>
                        </div>
                    </td>
                    <td>'. FUE_Coupons::get_discount_type($coupon->coupon_type) .'</td>
                    <td>'. floatval($coupon->amount) .'</td>
                    <td>'. $coupon->usage_count .'</td>
                </tr>
                ';
            endforeach;
        endif;

        $html .= '
            </tbody>
        </table>
    </form>
    </div>';
        return $html;
    }

    public static function new_coupon_html() {
        global $wpdb, $woocommerce;

        $categories = get_terms( 'product_cat', array( 'order_by' => 'name', 'order' => 'ASC' ) );
        $defaults   = array(
            'name'                  => '',
            'prefix'                => '',
            'type'                  => 'fixed_cart',
            'amount'                => '',
            'send_mode'             => 'immediately',
            'before_tax'            => 0,
            'individual'            => 0,
            'free_shipping'         => 0,
            'limit'                 => 0,
            'products'              => array(),
            'categories'            => array(),
            'exclude_products'      => array(),
            'exclude_categories'    => array(),
            'expiry_value'          => 0,
            'expiry_type'           => ''
        );

        ?>
        <div class="wrap woocommerce">
            <div class="icon32"><img src="<?php echo FUE_TEMPLATES_URL .'/images/send_mail.png'; ?>" /></div>
            <h2>
                <?php _e('Create a New Coupon', 'follow_up_emails'); ?>
            </h2>
            <form action="admin-post.php" method="post">

                <div id="poststuff">
                    <div id="post-body">
                        <div class="postbox-container" id="postbox-container-2" style="float:none; width: 75%;">
                            <div id="normal-sortables">
                                <div id="woocommerce-coupon-data" class="postbox">
                                    <div class="handlediv"><br/></div>
                                    <h3 class="hndle"><span>Coupon Data</span></h3>
                                    <div class="inside">
                                        <div id="coupon_options" class="panel woocommerce_options_panel">
                                            <div class="options_group">
                                                <p class="form-field">
                                                    <label for="name"><?php _e('Name', 'follow_up_emails'); ?></label>
                                                    <input type="text" name="name" id="name" value="<?php echo esc_attr($defaults['name']); ?>" class="short" />
                                                    <span class="description"><?php _e('For internal use only', 'follow_up_emails'); ?></span>
                                                </p>
                                                <p class="form-field">
                                                    <label for="prefix"><?php _e('Coupon Prefix', 'follow_up_emails'); ?></label>
                                                    <input type="text" name="prefix" id="prefix" value="<?php echo esc_attr($defaults['prefix']); ?>" class="input-text sized" size="25" />
                                                    <select id="prefixes">
                                                        <option value=""><?php _e('Choose a Variable', 'follow_up_emails'); ?></option>
                                                        <option value="{customer_first_name}"><?php _e('Customer\'s First Name', 'follow_up_emails'); ?></option>
                                                        <option value="{customer_last_name}"><?php _e('Customer\'s Last Name', 'follow_up_emails'); ?></option>
                                                    </select>
                                                    <span class="description"><?php _e('Add a prefix to the generated coupon code', 'follow_up_emails'); ?></span>
                                                </p>
                                                <p class="form-field">
                                                    <label for="type"><?php _e('Coupon type', 'follow_up_emails'); ?></label>
                                                    <select id="type" name="type">
                                                    <?php
                                                    $types = self::get_discount_types();

                                                    foreach ($types as $key => $type) {
                                                        $select = ($defaults['type'] == $key) ? 'selected' : '';
                                                        echo '<option value="'. $key .'" '. $select .'>'. $type .'</option>';
                                                    }
                                                    ?>
                                                    </select>
                                                </p>
                                                <p class="form-field">
                                                    <label for="amount"><?php _e('Amount / Percentage', 'follow_up_emails'); ?></label>
                                                    <input type="text" name="amount" id="amount" class="short" value="<?php echo esc_attr($defaults['amount']); ?>" placeholder="0.0" />
                                                    <span class="description"><?php _e('e.g. 5.99 (do not include the percent symbol)', 'follow_up_emails'); ?></span>
                                                </p>
                                                <p class="form-field">
                                                    <label for="individual"><?php _e('Individual use', 'follow_up_emails'); ?></label>
                                                    <input type="checkbox" class="checkbox" name="individual_use" id="individual" value="yes" <?php if ($defaults['individual'] != 0) echo 'checked'; ?> />
                                                    <span class="description"><?php _e('Check this box if the coupon cannot be used in conjunction with other coupons', 'follow_up_emails'); ?></span>
                                                </p>
                                                <p class="form-field">
                                                    <label for="before_tax"><?php _e('Apply before tax', 'follow_up_emails'); ?></label>
                                                    <input type="checkbox" class="checkbox" name="before_tax" id="before_tax" value="yes" <?php if ($defaults['before_tax'] != 0) echo 'checked'; ?> />
                                                    <span class="description"><?php _e('Check this box if the coupon should be applied before calculating cart tax', 'follow_up_emails'); ?></span>
                                                </p>
                                                <p class="form-field">
                                                    <label for="free_shipping"><?php _e('Enable free shipping', 'follow_up_emails'); ?></label>
                                                    <input type="checkbox" class="checkbox" name="free_shipping" id="free_shipping" value="yes" <?php if ($defaults['free_shipping'] != 0) echo 'checked'; ?> />
                                                    <span class="description"><?php _e('Check this box if the coupon enables free shipping (see <a href="admin.php?page=woocommerce&tab=shipping_methods&subtab=shipping-free_shipping">Free Shipping</a>)', 'follow_up_emails'); ?></span>
                                                </p>
                                            </div>
                                            <div class="options_group">
                                                <p class="form-field">
                                                    <label for="minimum_amount"><?php _e('Minimum amount', 'woocommerce'); ?></label>
                                                    <input type="text" class="short" name="minimum_amount" id="minimum_amount" value="" placeholder="<?php _e('No minimum', 'woocommerce'); ?>">
                                                    <span class="description"><?php _e('This field allows you to set the minimum subtotal needed to use the coupon.', 'woocommerce'); ?></span>
                                                </p>
                                            </div>
                                            <div class="options_group">
                                                <p class="form-field">
                                                    <label for="product_ids"><?php _e('Products', 'woocommerce'); ?></label>
                                                    <select id="product_ids" name="product_ids[]" class="ajax_chosen_select_products_and_variations" multiple="multiple" data-placeholder="Search for a product..." style="width: 400px">
                                                    </select>
                                                    <img class="help_tip" src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" width="16" height="16" data-tip="Products which need to be in the cart to use this coupon or, for &quot;Product Discounts&quot;, which products are discounted.">
                                                </p>
                                                <p class="form-field">
                                                    <label for="exclude_product_ids"><?php _e('Exclude Products', 'woocommerce'); ?></label>
                                                    <select id="exclude_product_ids" name="exclude_product_ids[]" class="ajax_chosen_select_products_and_variations" multiple="multiple" data-placeholder="Search for a product...">
                                                    </select>
                                                    <img class="help_tip" src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" width="16" height="16" data-tip='Products which must not be in the cart to use this coupon or, for "Product Discounts", which products are not discounted.'>
                                                </p>
                                            </div>
                                            <div class="options_group">
                                                <p class="form-field">
                                                    <label for="product_ids"><?php _e('Product Categories', 'woocommerce'); ?></label>
                                                    <select id="product_categories" name="product_categories[]" class="chzn-select" multiple="multiple" data-placeholder="Any category">
                                                        <?php
                                                        foreach ($categories as $category):
                                                            $selected = ($category->term_id != $defaults['category_id']) ? '' : 'selected';
                                                        ?>
                                                            <option value="<?php _e($category->term_id); ?>" <?php echo $selected; ?>><?php echo esc_html($category->name); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <img class="help_tip" src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" width="16" height="16" data-tip='A product must be in this category for the coupon to remain valid or, for "Product Discounts", products in these categories will be discounted.'>
                                                </p>
                                                <p class="form-field">
                                                    <label for="exclude_product_categories"><?php _e('Exclude Categories', 'woocommerce'); ?></label>
                                                    <select id="exclude_product_categories" name="exclude_product_categories[]" class="chzn-select" multiple="multiple" data-placeholder="No categories">
                                                        <?php
                                                        foreach ($categories as $category):
                                                            $selected = ($category->term_id != $defaults['category_id']) ? '' : 'selected';
                                                        ?>
                                                            <option value="<?php _e($category->term_id); ?>" <?php echo $selected; ?>><?php echo esc_html($category->name); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <img class="help_tip" src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" width="16" height="16" data-tip='Product must not be in this category for the coupon to remain valid or, for "Product Discounts", products in these categories will not be discounted.'>
                                                </p>
                                            </div>
                                            <div class="options_group">
                                                <p class="form-field">
                                                    <label for="expiry"><?php _e('Expiry', 'follow_up_emails'); ?></label>
                                                    <select name="expiry_value">
                                                        <option value="" <?php if ($defaults['expiry_value'] == 0) echo 'selected'; ?>><?php _e('Does not expire', 'follow_up_emails'); ?></option>
                                                        <?php for ($x = 1; $x <= 30; $x++): ?>
                                                        <option value="<?php echo $x; ?>" <?php if ($defaults['expiry_value'] == $x) echo 'selected'; ?>><?php echo $x; ?></option>
                                                        <?php endfor; ?>
                                                    </select>
                                                    <select name="expiry_type">
                                                        <option value="" <?php if ($defaults['expiry_type'] == '') echo 'selected'; ?>>-</option>
                                                        <option value="days" <?php if ($defaults['expiry_type'] == 'days') echo 'selected'; ?>><?php _e('days', 'follow_up_emails'); ?></option>
                                                        <option value="weeks" <?php if ($defaults['expiry_type'] == 'weeks') echo 'selected'; ?>><?php _e('weeks', 'follow_up_emails'); ?></option>
                                                        <option value="months" <?php if ($defaults['expiry_type'] == 'months') echo 'selected'; ?>><?php _e('months', 'follow_up_emails'); ?></option>
                                                    </select>
                                                    <span class="description"><?php _e('after the discount has been sent to the user', 'follow_up_emails'); ?></span>
                                                </p>
                                                <p class="form-field usage_limit_field">
                                                    <label for="usage_limit"><?php _e('Usage limit', 'woocommerce'); ?></label>
                                                    <input type="text" class="short" name="usage_limit" id="usage_limit" value="" placeholder="Unlimited usage">
                                                    <span class="description"><?php _e('How many times this coupon can be used before it is void', 'woocommerce'); ?></span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="advanced-sortables" class="meta-box-sortables ui-sortable"></div>
                        </div>
                        <div class="clear"></div>
                    </div> <!-- /post-body -->
                    <br class="clear">
                </div>

                <p class="submit">
                    <input type="hidden" name="action" value="sfn_followup_new_coupon" />
                    <input type="submit" name="save" value="<?php _e('Create Coupon', 'follow_up_emails'); ?>" class="button-primary" />
                </p>
            </form>
        </div>
        <script type="text/javascript">
        jQuery(document).ready(function() {
            jQuery("#prefixes").change(function() {
                jQuery("#prefix").val(jQuery(this).val());
            }).change();

            jQuery("select.ajax_chosen_select_products_and_variations").ajaxChosen({
                method: 	'GET',
                url: 		ajaxurl,
                dataType: 	'json',
                afterTypeDelay: 100,
                data:		{
                    action: 		'woocommerce_json_search_products_and_variations',
                    security: 		'<?php echo wp_create_nonce("search-products"); ?>'
                }
            }, function (data) {
                var terms = {};

                jQuery.each(data, function (i, val) {
                    terms[i] = val;
                });

                return terms;
            });
            jQuery("select.chzn-select").chosen();
        });
        </script>
        <?php
    }

    public static function edit_coupon_html() {
        global $wpdb, $woocommerce;

        $coupon = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_coupons WHERE id = %d", $_GET['id']) );

        if (! $coupon ) wp_die('Coupon could not be found');

        $categories = get_terms( 'product_cat', array( 'order_by' => 'name', 'order' => 'ASC' ) );
        $defaults   = array(
            'name'                  => $coupon->coupon_name,
            'prefix'                => $coupon->coupon_prefix,
            'type'                  => $coupon->coupon_type,
            'amount'                => $coupon->amount,
            'before_tax'            => $coupon->before_tax,
            'individual'            => $coupon->individual,
            'free_shipping'         => $coupon->free_shipping,
            'limit'                 => $coupon->usage_limit,
            'products'              => $coupon->product_ids,
            'exclude_products'      => $coupon->exclude_product_ids,
            'categories'            => (empty($coupon->product_categories)) ? array() : maybe_unserialize($coupon->product_categories),
            'exclude_categories'    => (empty($coupon->exclude_product_categories)) ? array() : maybe_unserialize($coupon->exclude_product_categories),
            'minimum_amount'        => $coupon->minimum_amount,
            'expiry_value'          => $coupon->expiry_value,
            'expiry_type'           => $coupon->expiry_type,
            'usage_limit'           => $coupon->usage_limit,
        );
        ?>
        <form action="admin-post.php" method="post">
            <h3><?php _e('Edit Coupon', 'follow_up_emails'); ?></h3>

            <div id="poststuff">
                <div id="post-body">
                    <div class="postbox-container" id="postbox-container-2" style="float:none; width: 75%;">
                        <div id="normal-sortables">
                            <div id="woocommerce-coupon-data" class="postbox">
                                <div class="handlediv"><br/></div>
                                <h3 class="hndle"><span>Coupon Data</span></h3>
                                <div class="inside">
                                    <div id="coupon_options" class="panel woocommerce_options_panel">
                                        <div class="options_group">
                                            <p class="form-field">
                                                <label for="name"><?php _e('Name', 'follow_up_emails'); ?></label>
                                                <input type="text" name="name" id="name" value="<?php echo esc_attr($defaults['name']); ?>" class="short" />
                                                <span class="description"><?php _e('For internal use only', 'follow_up_emails'); ?></span>
                                            </p>
                                            <p class="form-field">
                                                <label for="prefix"><?php _e('Coupon Prefix', 'follow_up_emails'); ?></label>
                                                <input type="text" name="prefix" id="prefix" value="<?php echo esc_attr($defaults['prefix']); ?>" class="input-text sized" size="25" />
                                                <select id="prefixes">
                                                    <option value=""><?php _e('Choose a Variable', 'follow_up_emails'); ?></option>
                                                    <option value="{customer_first_name}"><?php _e('Customer\'s First Name', 'follow_up_emails'); ?></option>
                                                    <option value="{customer_last_name}"><?php _e('Customer\'s Last Name', 'follow_up_emails'); ?></option>
                                                </select>
                                                <span class="description"><?php _e('Add a prefix to the generated coupon code', 'follow_up_emails'); ?></span>
                                            </p>
                                            <p class="form-field">
                                                <label for="type"><?php _e('Coupon type', 'follow_up_emails'); ?></label>
                                                <select id="type" name="type">
                                                <?php
                                                $types = self::get_discount_types();

                                                foreach ($types as $key => $type) {
                                                    $select = ($defaults['type'] == $key) ? 'selected' : '';
                                                    echo '<option value="'. $key .'" '. $select .'>'. $type .'</option>';
                                                }
                                                ?>
                                                </select>
                                            </p>
                                            <p class="form-field">
                                                <label for="amount"><?php _e('Amount / Percentage', 'follow_up_emails'); ?></label>
                                                <input type="text" name="amount" id="amount" class="short" value="<?php echo esc_attr($defaults['amount']); ?>" placeholder="0.0" />
                                                <span class="description"><?php _e('e.g. 5.99 (do not include the percent symbol)', 'follow_up_emails'); ?></span>
                                            </p>
                                            <p class="form-field">
                                                <label for="individual"><?php _e('Individual use', 'follow_up_emails'); ?></label>
                                                <input type="checkbox" class="checkbox" name="individual_use" id="individual" value="yes" <?php if ($defaults['individual'] != 0) echo 'checked'; ?> />
                                                <span class="description"><?php _e('Check this box if the coupon cannot be used in conjunction with other coupons', 'follow_up_emails'); ?></span>
                                            </p>
                                            <p class="form-field">
                                                <label for="before_tax"><?php _e('Apply before tax', 'follow_up_emails'); ?></label>
                                                <input type="checkbox" class="checkbox" name="before_tax" id="before_tax" value="yes" <?php if ($defaults['before_tax'] != 0) echo 'checked'; ?> />
                                                <span class="description"><?php _e('Check this box if the coupon should be applied before calculating cart tax', 'follow_up_emails'); ?></span>
                                            </p>
                                            <p class="form-field">
                                                <label for="free_shipping"><?php _e('Enable free shipping', 'follow_up_emails'); ?></label>
                                                <input type="checkbox" class="checkbox" name="free_shipping" id="free_shipping" value="yes" <?php if ($defaults['free_shipping'] != 0) echo 'checked'; ?> />
                                                <span class="description"><?php _e('Check this box if the coupon enables free shipping (see <a href="admin.php?page=woocommerce&tab=shipping_methods&subtab=shipping-free_shipping">Free Shipping</a>)', 'follow_up_emails'); ?></span>
                                            </p>
                                        </div>
                                        <div class="options_group">
                                            <p class="form-field">
                                                <label for="minimum_amount"><?php _e('Minimum amount', 'woocommerce'); ?></label>
                                                <input type="text" class="short" name="minimum_amount" id="minimum_amount" value="<?php echo esc_attr($defaults['minimum_amount']); ?>" placeholder="<?php _e('No minimum', 'woocommerce'); ?>">
                                                <span class="description"><?php _e('This field allows you to set the minimum subtotal needed to use the coupon.', 'woocommerce'); ?></span>
                                            </p>
                                        </div>
                                        <div class="options_group">
                                            <p class="form-field">
                                                <label for="product_ids"><?php _e('Products', 'woocommerce'); ?></label>
                                                <select id="product_ids" name="product_ids[]" class="ajax_chosen_select_products_and_variations" multiple="multiple" data-placeholder="Search for a product..." style="width: 400px">
                                                    <?php
                                                    if (!empty($defaults['products'])):
                                                        $ids = explode(',', $defaults['products']);
                                                        foreach ($ids as $id):
                                                    ?>
                                                    <option selected value="<?php echo $id; ?>"><?php echo esc_attr(get_the_title($id)) .' &ndash; #'. $id; ?></option>
                                                    <?php
                                                        endforeach;
                                                    endif;
                                                    ?>
                                                </select>
                                                <img class="help_tip" src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" width="16" height="16" data-tip="Products which need to be in the cart to use this coupon or, for &quot;Product Discounts&quot;, which products are discounted.">
                                            </p>
                                            <p class="form-field">
                                                <label for="exclude_product_ids"><?php _e('Exclude Products', 'woocommerce'); ?></label>
                                                <select id="exclude_product_ids" name="exclude_product_ids[]" class="ajax_chosen_select_products_and_variations" multiple="multiple" data-placeholder="Search for a product...">
                                                    <?php
                                                    if (!empty($defaults['exclude_products'])):
                                                        $ids = explode(',', $defaults['exclude_products']);
                                                        foreach ($ids as $id):
                                                    ?>
                                                    <option selected value="<?php echo $id; ?>"><?php echo esc_attr(get_the_title($id)) .' &ndash; #'. $id; ?></option>
                                                    <?php
                                                        endforeach;
                                                    endif;
                                                    ?>
                                                </select>
                                                <img class="help_tip" src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" width="16" height="16" data-tip='Products which must not be in the cart to use this coupon or, for "Product Discounts", which products are not discounted.'>
                                            </p>
                                        </div>
                                        <div class="options_group">
                                            <p class="form-field">
                                                <label for="product_ids"><?php _e('Product Categories', 'woocommerce'); ?></label>
                                                <select id="product_categories" name="product_categories[]" class="chzn-select" multiple="multiple" data-placeholder="Any category">
                                                    <?php
                                                    foreach ($categories as $category):
                                                        $selected = (!in_array($category->term_id, $defaults['categories'])) ? '' : 'selected';
                                                    ?>
                                                        <option value="<?php _e($category->term_id); ?>" <?php echo $selected; ?>><?php echo esc_html($category->name); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <img class="help_tip" src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" width="16" height="16" data-tip='A product must be in this category for the coupon to remain valid or, for "Product Discounts", products in these categories will be discounted.'>
                                            </p>
                                            <p class="form-field">
                                                <label for="exclude_product_categories"><?php _e('Exclude Categories', 'woocommerce'); ?></label>
                                                <select id="exclude_product_categories" name="exclude_product_categories[]" class="chzn-select" multiple="multiple" data-placeholder="No categories">
                                                    <?php
                                                    foreach ($categories as $category):
                                                        $selected = (!in_array($category->term_id, $defaults['exclude_categories'])) ? '' : 'selected';
                                                    ?>
                                                        <option value="<?php _e($category->term_id); ?>" <?php echo $selected; ?>><?php echo esc_html($category->name); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <img class="help_tip" src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" width="16" height="16" data-tip='Product must not be in this category for the coupon to remain valid or, for "Product Discounts", products in these categories will not be discounted.'>
                                            </p>
                                        </div>
                                        <div class="options_group">
                                            <p class="form-field">
                                                <label for="expiry"><?php _e('Expiry', 'follow_up_emails'); ?></label>
                                                <select name="expiry_value">
                                                    <option value="" <?php if ($defaults['expiry_value'] == 0) echo 'selected'; ?>><?php _e('Does not expire', 'follow_up_emails'); ?></option>
                                                    <?php for ($x = 1; $x <= 30; $x++): ?>
                                                    <option value="<?php echo $x; ?>" <?php if ($defaults['expiry_value'] == $x) echo 'selected'; ?>><?php echo $x; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                                <select name="expiry_type">
                                                    <option value="" <?php if ($defaults['expiry_type'] == '') echo 'selected'; ?>>-</option>
                                                    <option value="days" <?php if ($defaults['expiry_type'] == 'days') echo 'selected'; ?>><?php _e('days', 'follow_up_emails'); ?></option>
                                                    <option value="weeks" <?php if ($defaults['expiry_type'] == 'weeks') echo 'selected'; ?>><?php _e('weeks', 'follow_up_emails'); ?></option>
                                                    <option value="months" <?php if ($defaults['expiry_type'] == 'months') echo 'selected'; ?>><?php _e('months', 'follow_up_emails'); ?></option>
                                                </select>
                                                <span class="description"><?php _e('after the discount has been sent to the user', 'follow_up_emails'); ?></span>
                                            </p>
                                            <p class="form-field usage_limit_field">
                                                <label for="usage_limit"><?php _e('Usage limit', 'woocommerce'); ?></label>
                                                <input type="text" class="short" name="usage_limit" id="usage_limit" value="<?php echo esc_attr($defaults['usage_limit']); ?>" placeholder="Unlimited usage">
                                                <span class="description"><?php _e('How many times this coupon can be used before it is void', 'woocommerce'); ?></span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="advanced-sortables" class="meta-box-sortables ui-sortable"></div>
                    </div>
                    <div class="clear"></div>
                </div> <!-- /post-body -->
                <br class="clear">

            <p class="submit">
                <input type="hidden" name="action" value="sfn_followup_edit_coupon" />
                <input type="hidden" name="id" value="<?php echo $coupon->id; ?>" />
                <input type="submit" name="save" value="<?php _e('Update Coupon', 'follow_up_emails'); ?>" class="button-primary" />
            </p>
        </form>
        <script type="text/javascript">
        jQuery(document).ready(function() {
            jQuery("#prefixes").change(function() {
                jQuery("#prefix").val(jQuery(this).val());
            });

            jQuery("select.ajax_chosen_select_products_and_variations").ajaxChosen({
                method: 	'GET',
                url: 		ajaxurl,
                dataType: 	'json',
                afterTypeDelay: 100,
                data:		{
                    action: 		'woocommerce_json_search_products_and_variations',
                    security: 		'<?php echo wp_create_nonce("search-products"); ?>'
                }
            }, function (data) {
                var terms = {};

                jQuery.each(data, function (i, val) {
                    terms[i] = val;
                });

                return terms;
            });
            jQuery("select.chzn-select").chosen();
        });
        </script>
        <?php
    }

    public static function get_discount_type( $type ) {
        global $woocommerce;

        return (function_exists('wc_get_coupon_type')) ? wc_get_coupon_type($type) : $woocommerce->get_coupon_discount_type($type);
    }

    public static function get_discount_types() {
        global $woocommerce;

        return (function_exists('wc_get_coupon_types')) ? wc_get_coupon_types() : $woocommerce->get_coupon_discount_types();
    }

    public function cloned( $new_id, $old_id ) {
        global $wpdb;

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_email_coupons WHERE email_id = %d", $old_id), ARRAY_A);

        if ($row) {
            $row['email_id'] = $new_id;
            $wpdb->insert($wpdb->prefix .'followup_email_coupons', $row);
        }
    }

    public function deleted($id) {
        global $wpdb;

        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}followup_email_coupons WHERE email_id = %d", $id));
    }

}

$GLOBALS['fue_coupons'] = new FUE_Coupons();
