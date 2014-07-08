<?php

$GLOBALS['fue_subscriptions_product_link'] = 'http://www.75nineteen.com/woocommerce';

class FUE_Subscriptions {

    static $license_product = 'subscriptions';
    static $platform        = 'woocommerce';

    public function __construct() {
        if ( self::is_installed() ) {
            // subscriptions integration
            add_filter( 'fue_email_types', array($this, 'add_type') );
            add_filter( 'fue_trigger_types', array(&$this, 'add_triggers') );
            add_filter( 'fue_email_type_triggers', array($this, 'add_email_triggers') );

            add_filter( 'fue_email_type_long_descriptions', array($this, 'email_type_long_descriptions') );
            add_filter( 'fue_email_type_short_descriptions', array($this, 'email_type_short_descriptions') );

            add_action( 'email_types_update_priorities_script', array($this, 'email_list_update_script') );

            add_action( 'fue_email_form_after_interval', array($this, 'email_form_product_selector'), 10, 3 );

            // ajax subscriptions search
            add_action( 'wp_ajax_woocommerce_json_search_subscription_products', array($this, 'json_search_products') );

            // manual emails
            add_action( 'fue_manual_types', array($this, 'manual_types') );
            add_action( 'fue_manual_type_actions', array($this, 'manual_type_actions') );
            add_action( 'fue_manual_js', array($this, 'manual_js') );
            add_filter( 'fue_send_manual_emails', array($this, 'send_manual_email'), 10, 2 );

            add_action( 'fue_email_variables_list', array(&$this, 'email_variables_list') );

            add_filter( 'fue_email_type_is_valid', array($this, 'email_type_valid'), 10, 2 );

            add_action( 'activated_subscription', array(&$this, 'subscription_activated'), 10, 2 );
            add_action( 'cancelled_subscription', array(&$this, 'subscription_cancelled'), 10, 2 );
            add_action( 'subscription_expired', array(&$this, 'subscription_expired'), 10, 2 );
            add_action( 'reactivated_subscription', array(&$this, 'subscription_reactivated'), 10, 2 );
            add_action( 'suspended_subscription', array(&$this, 'suspended_subscription'), 10, 2 );

            add_action( 'processed_subscription_payment', array($this, 'set_renewal_reminder'), 10, 2 );
            add_action( 'processed_subscription_payment', array($this, 'set_expiration_reminder'), 10, 2 );

            add_action( 'fue_email_form_script', array($this, 'add_script') );

            add_filter( 'fue_email_subscription_variables', array($this, 'email_variables'), 10, 4 );
            add_filter( 'fue_email_subscription_replacements', array($this, 'email_replacements'), 10, 4 );
            add_filter( 'fue_email_subscription_test_variables', array($this, 'email_test_variables'), 10, 4 );
            add_filter( 'fue_email_subscription_test_replacements', array($this, 'email_test_replacements'), 10, 4 );

            add_filter( 'fue_skip_email_sending', array($this, 'skip_sending_if_status_changed'), 10, 3 );

            add_filter( 'fue_send_email_data', array($this, 'get_email_address_to_send'), 10, 3 );

            // settings page
            add_action( 'fue_settings_crm', array($this, 'settings_form'), 9 );
            add_action( 'fue_settings_crm_save', array($this, 'save_settings') );

            // listen for payment failure events
            add_action( 'processed_subscription_payment_failure_for_order', array($this, 'payment_failed_for_order') );
        }
    }

    public static function is_installed() {
        return ( class_exists( 'WC_Subscriptions' ) );
    }

    public function add_type( $types ) {
        $types['subscription'] = 'Subscription Email';

        return $types;
    }

    public function add_triggers( $triggers ) {
        $triggers['subs_activated']     = __('after subscription activated', 'follow_up_emails');
        $triggers['subs_renewed']       = __('after subscription renewed', 'follow_up_emails');
        $triggers['subs_cancelled']     = __('after subscription cancelled', 'follow_up_emails');
        $triggers['subs_expired']       = __('after subscription expired', 'follow_up_emails');
        $triggers['subs_suspended']     = __('after subscription suspended', 'follow_up_emails');
        $triggers['subs_reactivated']   = __('after subscription reactivated', 'follow_up_emails');
        $triggers['subs_before_renewal']= __('before next automatic subscription payment', 'follow_up_emails');
        $triggers['subs_before_expire'] = __('before active subscription expires', 'follow_up_emails');

        return $triggers;
    }

    public function add_email_triggers( $email_triggers ) {
        $email_triggers['subscription'] = array('subs_activated', 'subs_renewed', 'subs_cancelled', 'subs_expired', 'subs_suspended', 'subs_reactivated', 'subs_before_renewal', 'subs_before_expire');

        return $email_triggers;
    }

    public function email_type_long_descriptions( $descriptions ) {
        $descriptions['subscription']    = __('Subscription emails will send to a user based upon the subscription status you define when creating your emails. Below are the existing Subscription emails set up for your store. Use the priorities to define which emails are most important. These emails are selected first when sending the email to the customer if more than one criteria is met by multiple emails. Only one email is sent out to the customer (unless you enable the Always Send option when creating your emails), so prioritizing the emails for occasions where multiple criteria are met ensures you send the right email to the right customer at the time you choose.', 'follow_up_emails');

        return $descriptions;
    }

    public function email_type_short_descriptions( $descriptions ) {
        $descriptions['subscription']    = __('Subscription emails will send to a user based upon the subscription status you define when creating your emails.', 'follow_up_emails');

        return $descriptions;
    }

    public function email_type_valid( $is_valid, $data ) {
        if ( $data['email_type'] == 'subscription' ) $is_valid = true;

        return $is_valid;
    }

    public function email_list_update_script() {
        ?>
        jQuery('.subscription-table tbody tr').each(function(x){
            jQuery(this).find('td .priority').html(x+1);
        });
        <?php
    }

    public function manual_types() {
        ?><option value="active_subscription"><?php _e('Customers with an active subscription', 'follow_up_emails'); ?></option><?php
    }

    public function manual_type_actions() {
        $subscriptions = array();

        $posts = get_posts( array(
            'post_type'     => 'product',
            'post_status'   => 'publish'
        ) );

        foreach ($posts as $post) {
            $product = sfn_get_product( $post->ID );

            if ( $product->is_type( array( WC_Subscriptions::$name, 'subscription_variation', 'variable-subscription' ) ) )
                $subscriptions[] = $product;
        }

        ?>
        <div class="send-type-subscription send-type-div">
            <select id="subscription_id" name="subscription_id" class="chzn-select" style="width: 400px;">
                <?php foreach ( $subscriptions as $subscription ): ?>
                <option value="<?php echo $subscription->id; ?>"><?php echo esc_html( $subscription->get_title() ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }

    public function manual_js() {
        ?>
        jQuery("#send_type").change(function() {
            switch (jQuery(this).val()) {
                case "active_subscription":
                    jQuery(".send-type-subscription").show();
                    break;
            }
        });
        <?php
    }

    public function send_manual_email( $recipients, $post ) {
        global $wpdb;

        if ( $post['send_type'] == 'active_subscription' ) {
            $subscriptions = WC_Subscriptions_Manager::get_all_users_subscriptions();

            foreach ( $subscriptions as $user_id => $user_subscriptions ) {
                foreach ( $user_subscriptions as $sub_key => $subscription ) {
                    if ( $subscription['product_id'] == $post['subscription_id'] || $subscription['variation_id'] == $post['subscription_id'] ) {
                        $user = new WP_User( $user_id );
                        $key = $user->user_id .'|'. $user->user_email .'|'. $user->first_name .' '. $user->last_name;
                        $recipients[$key] = array($user->user_id, $user->user_email, $user->first_name .' '. $user->last_name);
                    }
                }
            }

        }

        return $recipients;
    }

    public function email_variables_list() {
        global $woocommerce;
        ?>
        <li class="var hideable var_subscriptions"><strong>{subs_renew_date}</strong> <img class="help_tip" title="<?php _e('The date that a customer\'s subscription renews', 'follow_up_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
        <li class="var hideable var_subscriptions"><strong>{days_to_renew}</strong> <img class="help_tip" title="<?php _e('The number of days before a subscription is up for renewal', 'follow_up_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
        <?php
    }

    public static function subscription_activated( $user_id, $subs_key ) {
        global $wpdb;

        $parts = explode('_', $subs_key);
        $order_id       = $parts[0];
        $product_id     = $parts[1];

        // delete queued emails with the same product id and the 'subs_cancelled' or 'subs_suspended' trigger
        $rows = $wpdb->get_results( $wpdb->prepare("
            SELECT eo.id
            FROM {$wpdb->prefix}followup_email_orders eo, {$wpdb->prefix}followup_emails e
            WHERE eo.is_sent = 0
            AND eo.product_id = %d
            AND eo.email_id = e.id
            AND (
              e.interval_type = 'subs_cancelled' OR e.interval_type = 'subs_suspended'
            )
        ", $product_id) );

        if ( $rows ) {
            foreach ( $rows as $row ) {
                $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}followup_email_orders WHERE id = %d", $row->id) );
            }
        }

        $subscription   = WC_Subscriptions_Manager::get_subscription( $subs_key );

        if ( count($subscription['completed_payments']) > 1 ) {
            $triggers[]     = 'subs_renewed';
        } else {
            $triggers[]     = 'subs_activated';
        }

        // Tell FUE that an email order has been created
        // to stop it from sending generic emails
        if (! defined('FUE_ORDER_CREATED'))
            define('FUE_ORDER_CREATED', true);

        self::create_email_orders($order_id, $triggers, $subs_key, $user_id);

    }

    public static function subscription_cancelled( $user_id, $subs_key ) {
        global $wpdb;

        $parts = explode('_', $subs_key);
        $order_id       = $parts[0];
        $product_id     = $parts[1];

        // delete queued emails with the same product id and the 'subs_activated' or 'subs_renewed', or 'subs_reactivated' trigger
        $rows = $wpdb->get_results( $wpdb->prepare("
            SELECT eo.id
            FROM {$wpdb->prefix}followup_email_orders eo, {$wpdb->prefix}followup_emails e
            WHERE eo.is_sent = 0
            AND eo.product_id = %d
            AND eo.email_id = e.id
            AND e.interval_type IN ('subs_activated', 'subs_renewed', 'subs_reactivated')
        ", $product_id) );

        if ( $rows ) {
            foreach ( $rows as $row ) {
                $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}followup_email_orders WHERE id = %d", $row->id) );
            }
        }

        $triggers[]     = 'subs_cancelled';

        // get the user's email address
        $user = new WP_User($user_id);

        self::create_email_orders($order_id, $triggers, $subs_key, $user->user_email);
    }

    public static function subscription_expired( $user_id, $subs_key ) {
        global $wpdb;

        $parts = explode('_', $subs_key);
        $order_id       = $parts[0];

        $triggers[]     = 'subs_expired';

        self::create_email_orders($order_id, $triggers, $subs_key, $user_id);
    }

    public static function subscription_reactivated( $user_id, $subs_key ) {
        global $wpdb;

        $parts = explode('_', $subs_key);
        $order_id       = $parts[0];
        $product_id     = $parts[1];

        // delete queued emails with the same product id and the 'subs_cancelled' or 'subs_suspended' trigger
        $rows = $wpdb->get_results( $wpdb->prepare("
            SELECT eo.id
            FROM {$wpdb->prefix}followup_email_orders eo, {$wpdb->prefix}followup_emails e
            WHERE eo.is_sent = 0
            AND eo.product_id = %d
            AND eo.email_id = e.id
            AND (
              e.interval_type = 'subs_cancelled' OR e.interval_type = 'subs_suspended'
            )
        ", $product_id) );

        if ( $rows ) {
            foreach ( $rows as $row ) {
                $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}followup_email_orders WHERE id = %d", $row->id) );
            }
        }

        $triggers[]     = 'subs_reactivated';

        self::create_email_orders($order_id, $triggers, $subs_key, $user_id);
    }

    public static function suspended_subscription( $user_id, $subs_key ) {
        global $wpdb;

        $parts = explode('_', $subs_key);
        $order_id       = $parts[0];

        $triggers[]     = 'subs_suspended';

        self::create_email_orders($order_id, $triggers, $subs_key, $user_id);

    }

    public static function create_email_orders($order_id, $triggers, $subs_key = '', $user_id = '') {
        global $wpdb;

        $trigger = '';
        foreach ( $triggers as $t ) {
            $trigger .= "'". esc_sql($t) ."',";
        }
        $trigger = rtrim($trigger, ',');

        if ( empty($trigger) ) $trigger = "''";

        $emails = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}followup_emails WHERE `interval_type` IN ($trigger) AND status = 1" );

        foreach ( $emails as $email ) {
            $interval   = (int  )$email->interval_num;

            $add        = FUE::get_time_to_add( $interval, $email->interval_duration );
            $send_on    = current_time('timestamp') + $add;
            $prod_id    = 0;

            if ( $subs_key ) {
                $parts = explode('_', $subs_key);
                $prod_id = $parts[1];

                if ( !empty($email->product_id) && $email->product_id != $prod_id ) {
                    continue;
                }
            }

            $insert = array(
                'send_on'       => $send_on,
                'email_id'      => $email->id,
                'product_id'    => $prod_id,
                'order_id'      => $order_id
            );

            if ( $subs_key ) {
                $insert['meta']['subs_key'] = $subs_key;
            }

            if ($user_id) {
                $user = new WP_User($user_id);
                $insert['user_id']      = $user_id;
                $insert['user_email']   = $user->user_email;
            }

            FUE::insert_email_order( $insert );
        }
    }

    public function skip_sending_if_status_changed( $skip, $email, $email_order ) {
        global $wpdb;

        if ( isset($email_order->meta) && !empty($email_order->meta) ) {

            $meta = maybe_unserialize($email_order->meta);

            if ( isset($meta['subs_key']) ) {
                $delete         = false;
                $subscription   = WC_Subscriptions_Manager::get_subscription( $meta['subs_key'] );

                if ( $subscription ) {

                    if ( $email->interval_type == 'subs_suspended' && $subscription['status'] != 'on-hold' ) {
                        $delete = true;
                        $skip = true;
                    } elseif ( $email->interval_type == 'subs_expired' && $subscription['status'] != 'expired' ) {
                        $delete = true;
                        $skip = true;
                    } elseif ( ($email->interval_type == 'subs_activated' || $email->interval_type == 'subs_renewed' || $email->interval_type == 'subs_reactivated') && $subscription['status'] != 'active' ) {
                        $delete = true;
                        $skip = true;
                    } elseif ( $email->interval_type == 'subs_cancelled' && $subscription['status'] != 'cancelled' ) {
                        $delete = true;
                        $skip = true;
                    }

                    if ( $delete ) {
                        $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}followup_email_orders WHERE id = %d", $email_order->id) );
                    }

                } // if ($subscription)
            } // if ( isset($meta['subs_key']) )

        } // if ( isset($email_order->meta) && !empty($email_order->meta) )

        return $skip;

    }

    public static function set_renewal_reminder( $user_id, $subs_key ) {
        global $wpdb;

        $parts      = explode('_', $subs_key);
        $order_id   = $parts[0];
        $order      = new WC_Order( $order_id );

        if ( WC_Subscriptions_Order::order_contains_subscription($order) ) {

            // look for renewal emails
            $emails = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}followup_emails WHERE `interval_type` = 'subs_before_renewal' AND status = 1");

            if ( count($emails) > 0 ) {
                $item       = WC_Subscriptions_Order::get_item_by_product_id($order);
                $item_id    = WC_Subscriptions_Order::get_items_product_id($item);
                $renewal    = WC_Subscriptions_Order::get_next_payment_timestamp($order, $item_id);

                if ( 0 == $renewal ) return;

                foreach ( $emails as $email ) {
                    // add this email to the queue
                    $interval   = (int)$email->interval_num;
                    $add        = FUE::get_time_to_add( $interval, $email->interval_duration );
                    $send_on    = $renewal - $add;

                    $insert = array(
                        'user_id'       => $user_id,
                        'send_on'       => $send_on,
                        'email_id'      => $email->id,
                        'product_id'    => 0,
                        'order_id'      => $order_id
                    );

                    if ( $subs_key ) {
                        $insert['meta']['subs_key'] = $subs_key;
                    }

                    FUE::insert_email_order( $insert );
                }
            }

        }
    }

    public function set_expiration_reminder( $user_id, $subs_key ) {
        global $wpdb;

        $parts      = explode('_', $subs_key);
        $order_id   = $parts[0];
        $order      = new WC_Order( $order_id );

        if ( WC_Subscriptions_Order::order_contains_subscription($order) ) {

            // look for renewal emails
            $emails = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}followup_emails WHERE `interval_type` = 'subs_before_expire' AND status = 1");

            if ( count($emails) > 0 ) {
                $subs       = WC_Subscriptions_Manager::get_subscription( $subs_key );
                $expiry     = WC_Subscriptions_Manager::get_subscription_expiration_date( $subs_key, $user_id, 'timestamp' );

                if ( 0 == $expiry ) return;

                foreach ( $emails as $email ) {
                    // add this email to the queue
                    $interval   = (int)$email->interval_num;
                    $add        = FUE::get_time_to_add( $interval, $email->interval_duration );
                    $send_on    = $expiry - $add;

                    $insert = array(
                        'user_id'       => $user_id,
                        'send_on'       => $send_on,
                        'email_id'      => $email->id,
                        'product_id'    => 0,
                        'order_id'      => $order_id
                    );

                    if ( $subs_key ) {
                        $insert['meta']['subs_key'] = $subs_key;
                    }

                    FUE::insert_email_order( $insert );
                }
            }

        }
    }

    public function add_script() {
        ?>

        jQuery("body").bind("fue_email_type_changed", function(evt, type) {
            subscriptions_toggle_fields( type );
        });

        function subscriptions_toggle_fields( type ) {
            if (type == "subscription") {
                var val  = jQuery("#interval_type").val();
                var show = ['.var_subscriptions', '.interval_type_subs_activated', '.interval_type_subs_renewed', '.interval_type_subs_cancelled', '.interval_type_subs_expired', '.interval_type_subs_suspended', '.interval_type_subs_reactivated', '.interval_type_subs_before_renewal', '.var_item_name', '.var_item_category', '.product_description_tr', '.subscription_product_tr', '.category_tr'];
                var hide = ['.interval_type_option', '.always_send_tr', '.signup_description', '.use_custom_field_tr', '.custom_field_tr', '.var_item_name', '.var_item_category', '.var_item_names', '.var_item_categories', '.interval_type_after_last_purchase', '.interval_duration_date', '.var_customer'];

                for (x = 0; x < hide.length; x++) {
                    jQuery(hide[x]).hide();
                }

                for (x = 0; x < show.length; x++) {
                    jQuery(show[x]).show();
                }

                jQuery("div.product_tr, div.category_tr").remove();

                // triggers
                jQuery(".interval_type_option").remove();

                if ( email_intervals && email_intervals.subscription.length > 0 ) {
                    for (var x = 0; x < email_intervals.subscription.length; x++) {
                        var int_key = email_intervals.subscription[x];
                        jQuery("#interval_type").append('<option class="interval_type_option interval_type_'+ int_key +'" id="interval_type_option_'+ int_key +'" value="'+ int_key +'">'+ interval_types[int_key] +'</option>');
                    }

                    jQuery("#interval_type").val(val);
                }

                jQuery(".interval_duration_date").hide();

                jQuery("#interval_type").change();
            } else {
                var hide = ['.var_subscriptions', '.interval_type_subs_activated', '.interval_type_subs_renewed', '.interval_type_subs_cancelled', '.interval_type_subs_expired', '.interval_type_subs_suspended', '.interval_type_subs_reactivated', '.interval_type_subs_before_renewal', '.var_item_name', '.var_item_category', '.subscription_product_tr'];

                for (x = 0; x < hide.length; x++) {
                    jQuery(hide[x]).hide();
                }
            }
        }

        jQuery(document).ready(function() {
            subscriptions_toggle_fields( jQuery("#email_type").val() );

            jQuery("select.ajax_chosen_select_subscriptions").change(function() {
                // remove the first option to limit to only 1 product per email
                if (jQuery(this).find("option:selected").length > 1) {
                    while (jQuery(this).find("option:selected").length > 1) {
                        jQuery(jQuery(this).find("option:selected")[0]).remove();
                    }

                    jQuery(this).trigger("liszt:updated");
                }


                if (jQuery(this).find("option:selected").length == 1) {
                    // if selected product contain variations, show option to include variations
                    jQuery(".subscription_product_tr").block({ message: null, overlayCSS: { background: '#fff url('+ FUE.ajax_loader +') no-repeat center', opacity: 0.6 } });

                    jQuery.get(ajaxurl, {action: 'fue_product_has_children', product_id: jQuery(this).find("option:selected").val()}, function(resp) {
                        if ( resp == 1) {
                            jQuery(".product_include_variations").show();
                        } else {
                            jQuery("#include_variations").attr("checked", false);
                            jQuery(".product_include_variations").hide();
                        }

                        jQuery(".subscription_product_tr").unblock();
                    });
                } else {
                    jQuery("#include_variations").attr("checked", false);
                    jQuery(".product_include_variations").hide();
                }
            });

            jQuery("select.ajax_chosen_select_subscriptions").ajaxChosen({
                method:     'GET',
                url:        ajaxurl,
                dataType:   'json',
                afterTypeDelay: 100,
                data:       {
                    action:         'woocommerce_json_search_subscription_products',
                    security:       FUE.nonce
                }
            }, function (data) {
                var terms = {};

                jQuery.each(data, function (i, val) {
                    terms[i] = val;
                });

                return terms;
            });
        });
        <?php
    }

    public function email_form_product_selector( $values ) {
        // load the categories
        $categories = get_terms( 'product_cat', array( 'order_by' => 'name', 'order' => 'ASC' ) );
        ?>
        <div class="field hideable subscription_product_tr">
            <label for="product_id"><?php _e('Subscription Product', 'follow_up_emails'); ?></label>
            <select id="product_id" name="product_id" class="ajax_chosen_select_subscriptions" multiple data-placeholder="<?php _e('Search for a subscription product&hellip;', 'woocommerce'); ?>" style="width: 400px">
                <?php if ( !empty($values['product_id']) ): ?>
                    <option value="<?php echo $values['product_id']; ?>" selected><?php echo get_the_title($values['product_id']) .' #'. $values['product_id']; ?></option>
                <?php endif; ?>
            </select>
            <br/>
            <?php
            $display        = 'display: none;';
            $has_variations = (!empty($values['product_id']) && FUE_Woocommerce::product_has_children($values['product_id'])) ? true : false;

            if ($has_variations) $display = '';
            ?>
            <div class="product_include_variations" style="<?php echo $display; ?>">
                <input type="checkbox" name="meta[include_variations]" id="include_variations" value="yes" <?php if (isset($values['meta']['include_variations']) && $values['meta']['include_variations'] == 'yes') echo 'checked'; ?> />
                <label for="include_variations" class="inline"><?php _e('Include variations', 'follow_up_emails'); ?></label>
            </div>
        </div>

        <?php
    }

    public function email_variables( $vars, $email_data, $email_order, $email_row ) {
        $vars = array_merge($vars, array('{order_number}', '{order_date}', '{order_datetime}', '{order_billing_address}', '{order_shipping_address}', '{customer_first_name}', '{customer_name}', '{customer_email}', '{subs_renew_date}', '{days_to_renew}', '{item_name}', '{item_category}'));

        return $vars;
    }

    public function email_replacements( $reps, $email_data, $email_order, $email_row ) {
        global $wpdb, $woocommerce;

        $email_type     = $email_row->email_type;
        $order_date     = '';
        $order_datetime = '';
        $order_id       = '';

        if ( $email_order->order_id ) {
            $order          = new WC_Order( $email_order->order_id );
            $order_date     = date(get_option('date_format'), strtotime($order->order_date));
            $order_datetime = date(get_option('date_format') .' '. get_option('time_format'), strtotime($order->order_date));

            $order_id = apply_filters( 'woocommerce_order_number', '#'.$email_order->order_id, $order );

            $billing_address    = $order->get_formatted_billing_address();
            $shipping_address   = $order->get_formatted_shipping_address();

            $item       = WC_Subscriptions_Order::get_item_by_product_id($order);
            $item_id    = WC_Subscriptions_Order::get_items_product_id($item);
            $renewal    = self::calculate_next_payment_timestamp($order, $item_id);

            $renew_date = date( get_option('date_format'), $renewal );

            // calc days to renew
            $now    = current_time( 'timestamp' );
            $diff   = $renewal - $now;
            $days_to_renew = 0;
            if ( $diff > 0 ) {
                $days_to_renew = floor( $diff / 86400 );
            }

            $item_url   = FUE::create_email_url( $email_order->id, $email_row->id, $email_data['user_id'], $email_data['email_to'], get_permalink($item_id) );

            $categories = '';

            if ( $item_id ) {
                $cats   = get_the_terms($item_id, 'product_cat');

                if (is_array($cats) && !empty($cats)) {
                    foreach ($cats as $cat) {
                        $categories .= $cat->name .', ';
                    }
                    $categories = rtrim($categories, ', ');
                }

            }

            $reps = array_merge($reps, array(
                    $order_id,
                    $order_date,
                    $order_datetime,
                    $billing_address,
                    $shipping_address,
                    $email_data['first_name'],
                    $email_data['first_name'] .' '. $email_data['last_name'],
                    $email_data['email_to'],
                    $renew_date,
                    $days_to_renew,
                    '<a href="'. $item_url .'">'. get_the_title($item_id) .'</a>',
                    $categories
                ));
        }

        return $reps;
    }

    public function email_test_variables( $vars ) {
        $vars = array_merge($vars, array('{order_number}', '{order_date}', '{order_datetime}', '{order_billing_address}', '{order_shipping_address}', '{customer_first_name}', '{customer_name}', '{customer_email}', '{subs_renew_date}', '{days_to_renew}', '{item_name}', '{item_category}'));

        return $vars;
    }

    public function email_test_replacements( $reps ) {
        $order_number   = '1100';
        $order_date     = date(get_option('date_format'));
        $order_datetime = date(get_option('date_format') .' '. get_option('time_format'));
        $customer_first = 'Scott';
        $customer_last  = 'Scott Doe';
        $customer_email = 'noreply@75nineteen.com';

        $renew_date     = date(get_option('date_format') .' '. get_option('time_format'), time()+86400);
        $days_to_renew  = 1;

        $item_name  = '<a href="#">Name of Product</a>';
        $item_cat   = 'Test Category';

        $billing_address    = '123 Broken Top Dr., Bend, OR 97702';
        $shipping_address   = '123 Broken Top Dr., Bend, OR 97702';

        $reps = array_merge($reps, array(
                $order_number,
                $order_date,
                $order_datetime,
                $billing_address,
                $shipping_address,
                $customer_first,
                $customer_last,
                $customer_email,
                $renew_date,
                $days_to_renew,
                $item_name,
                $item_cat
            ) );

        return $reps;
    }

    public function get_email_address_to_send( $email_data, $email_order, $email ) {

        if ($email->email_type != 'subscription')
            return $email_data;

        $meta = maybe_unserialize($email_order->meta);

        if (isset($meta['subs_key'])) {
            $subscription = WC_Subscriptions_Manager::get_subscription($meta['subs_key']);

            if (! empty($subscription)) {
                $order  = new WC_Order($subscription['order_id']);
                $user   = new WP_User($order->user_id);

                $email_data['email_to']     = $user->user_email;
                $email_data['first_name']   = $user->first_name;
                $email_data['last_name']    = $user->last_name;
                $email_data['cname']        = $user->first_name .' '. $user->last_name;
            }
        }

        return $email_data;
    }

    public function settings_form() {
        ?>
        <h3><?php _e('Notify me for Failed Subscription Payments', 'follow_up_emails'); ?></h3>

        <table class="form-table">
            <tr>
                <th><label for="subscription_failure_notification"><?php _e('Send Notification Email', 'follow_up_emails'); ?></label></th>
                <td>
                    <input type="checkbox" name="subscription_failure_notification" id="subscription_failure_notification" value="1" <?php if (1 == get_option('fue_subscription_failure_notification', 0)) echo 'checked'; ?> />

                </td>
            </tr>
            <tr>
                <th>
                    <label for="subscription_failure_notification_emails">
                        <?php _e('Email Address', 'follow_up_emails'); ?>
                    </label>
                </th>
                <td>
                    <input type="text" name="subscription_failure_notification_emails" id="subscription_failure_notification_emails" value="<?php echo esc_attr(get_option('fue_subscription_failure_notification_emails', '')); ?>" />
                    <span class="description"><?php _e('Comma-separated email addresses of recipients', 'follow_up_emails'); ?></span>
                </td>
            </tr>
        </table>
    <?php
    }

    public function save_settings( $post ) {
        $notification   = (isset($post['subscription_failure_notification']) && $post['subscription_failure_notification'] == 1) ? 1 : 0;
        $emails         = (isset($post['subscription_failure_notification_emails'])) ? $post['subscription_failure_notification_emails'] : '';

        update_option( 'fue_subscription_failure_notification', $notification );
        update_option( 'fue_subscription_failure_notification_emails', $emails );
    }

    /**
     * @param WC_Order $order
     */
    public function payment_failed_for_order( $order ) {

        if ( 1 == get_option('fue_subscription_failure_notification', 0) ) {
            // notification enabled
            $emails_string = get_option('fue_subscription_failure_notification_emails', '');

            if ( empty($emails_string) )
                return;

            // get the product id to get the subscription string
            $order_items        = WC_Subscriptions_Order::get_recurring_items( $order );
            $first_order_item   = reset( $order_items );
            $product_id         = WC_Subscriptions_Order::get_items_product_id( $first_order_item );
            $subs_key           = WC_Subscriptions_Manager::get_subscription_key( $order->id, $product_id );

            $subject    = sprintf( __('Subscription payment failed for Order %s'), $order->get_order_number() );
            $message    = sprintf( __('A subscription payment for the order %s has failed. The subscription has now been automatically put on hold.'), $order->get_order_number() );

            $recipients = array();

            if ( strpos( $emails_string, ',') !== false ) {
                $recipients = array_map('trim', explode( ',', $emails_string ) );
            } else {
                $recipients = array($emails_string);
            }

            foreach ( $recipients as $email ) {
                FUE::mail( $email, $subject, $message );
            }
        }

    }

    private static function calculate_next_payment_timestamp( $order, $product_id ) {
        $type = 'timestamp';
        $from_date = '';

        $from_date_arg = $from_date;

        $subscription              = WC_Subscriptions_Manager::get_subscription( WC_Subscriptions_Manager::get_subscription_key( $order->id, $product_id ) );
        $subscription_period       = WC_Subscriptions_Order::get_subscription_period( $order, $product_id );
        $subscription_interval     = WC_Subscriptions_Order::get_subscription_interval( $order, $product_id );
        $subscription_trial_length = WC_Subscriptions_Order::get_subscription_trial_length( $order, $product_id );
        $subscription_trial_period = WC_Subscriptions_Order::get_subscription_trial_period( $order, $product_id );

        $trial_end_time   = ( ! empty( $subscription['trial_expiry_date'] ) ) ? $subscription['trial_expiry_date'] : WC_Subscriptions_Product::get_trial_expiration_date( $product_id, get_gmt_from_date( $order->order_date ) );
        $trial_end_time   = strtotime( $trial_end_time );

        // If the subscription has a free trial period, and we're still in the free trial period, the next payment is due at the end of the free trial
        if ( $subscription_trial_length > 0 && $trial_end_time > ( gmdate( 'U' ) + 60 * 60 * 23 + 120 ) ) { // Make sure trial expiry is more than 23+ hours in the future to account for trial expiration dates incorrectly stored in non-UTC/GMT timezone (and also for any potential changes to the site's timezone)

            $next_payment_timestamp = $trial_end_time;

            // The next payment date is {interval} billing periods from the from date
        } else {

            // We have a timestamp
            if ( ! empty( $from_date ) && is_numeric( $from_date ) )
                $from_date = date( 'Y-m-d H:i:s', $from_date );

            if ( empty( $from_date ) ) {

                if ( ! empty( $subscription['completed_payments'] ) ) {
                    $from_date = array_pop( $subscription['completed_payments'] );
                    $add_failed_payments = true;
                } else if ( ! empty ( $subscription['start_date'] ) ) {
                    $from_date = $subscription['start_date'];
                    $add_failed_payments = true;
                } else {
                    $from_date = gmdate( 'Y-m-d H:i:s' );
                    $add_failed_payments = false;
                }

                $failed_payment_count = WC_Subscriptions_Order::get_failed_payment_count( $order, $product_id );

                // Maybe take into account any failed payments
                if ( true === $add_failed_payments && $failed_payment_count > 0 ) {
                    $failed_payment_periods = $failed_payment_count * $subscription_interval;
                    $from_timestamp = strtotime( $from_date );

                    if ( 'month' == $subscription_period )
                        $from_date = date( 'Y-m-d H:i:s', WC_Subscriptions::add_months( $from_timestamp, $failed_payment_periods ) );
                    else // Safe to just add the billing periods
                        $from_date = date( 'Y-m-d H:i:s', strtotime( "+ {$failed_payment_periods} {$subscription_period}", $from_timestamp ) );
                }
            }

            $from_timestamp = strtotime( $from_date );

            if ( 'month' == $subscription_period ) // Workaround potential PHP issue
                $next_payment_timestamp = WC_Subscriptions::add_months( $from_timestamp, $subscription_interval );
            else
                $next_payment_timestamp = strtotime( "+ {$subscription_interval} {$subscription_period}", $from_timestamp );

            // Make sure the next payment is in the future
            $i = 1;
            while ( $next_payment_timestamp < gmdate( 'U' ) && $i < 30 ) {
                if ( 'month' == $subscription_period ) {
                    $next_payment_timestamp = WC_Subscriptions::add_months( $next_payment_timestamp, $subscription_interval );
                } else { // Safe to just add the billing periods
                    $next_payment_timestamp = strtotime( "+ {$subscription_interval} {$subscription_period}", $next_payment_timestamp );
                }
                $i = $i + 1;
            }

        }

        // If the subscription has an expiry date and the next billing period comes after the expiration, return 0
        if ( isset( $subscription['expiry_date'] ) && 0 != $subscription['expiry_date'] && ( $next_payment_timestamp + 120 ) > strtotime( $subscription['expiry_date'] ) )
            $next_payment_timestamp =  0;

        $next_payment = ( 'mysql' == $type && 0 != $next_payment_timestamp ) ? date( 'Y-m-d H:i:s', $next_payment_timestamp ) : $next_payment_timestamp;

        return apply_filters( 'woocommerce_subscriptions_calculated_next_payment_date', $next_payment, $order, $product_id, $type, $from_date, $from_date_arg );

    }

    /**
     * Search for products and echo json
     */
    public function json_search_products() {

        check_ajax_referer( 'search-products', 'security' );

        header( 'Content-Type: application/json; charset=utf-8' );

        $term       = (string) wc_clean( stripslashes( $_GET['term'] ) );
        $post_types = array('product', 'product_variation');

        if (empty($term)) die();

        if ( is_numeric( $term ) ) {

            $args = array(
                'post_type'			=> $post_types,
                'post_status'	 	=> 'publish',
                'posts_per_page' 	=> -1,
                'post__in' 			=> array(0, $term),
                'fields'			=> 'ids'
            );

            $args2 = array(
                'post_type'			=> $post_types,
                'post_status'	 	=> 'publish',
                'posts_per_page' 	=> -1,
                'post_parent' 		=> $term,
                'fields'			=> 'ids'
            );

            $args3 = array(
                'post_type'			=> $post_types,
                'post_status' 		=> 'publish',
                'posts_per_page' 	=> -1,
                'meta_query' 		=> array(
                    array(
                        'key' 	=> '_sku',
                        'value' => $term,
                        'compare' => 'LIKE'
                    )
                ),
                'fields'			=> 'ids'
            );

            $posts = array_unique(array_merge( get_posts( $args ), get_posts( $args2 ), get_posts( $args3 ) ));

        } else {

            $args = array(
                'post_type'			=> $post_types,
                'post_status' 		=> 'publish',
                'posts_per_page' 	=> -1,
                's' 				=> $term,
                'fields'			=> 'ids'
            );

            $args2 = array(
                'post_type'			=> $post_types,
                'post_status' 		=> 'publish',
                'posts_per_page' 	=> -1,
                'meta_query' 		=> array(
                    array(
                        'key' 	=> '_sku',
                        'value' => $term,
                        'compare' => 'LIKE'
                    )
                ),
                'fields'			=> 'ids'
            );

            $posts = array_unique(array_merge( get_posts( $args ), get_posts( $args2 ) ));

        }

        $found_products = array();

        if ( $posts ) foreach ( $posts as $post ) {

            $product = get_product( $post );

            if ( WC_Subscriptions_Product::is_subscription( $product ) )
                $found_products[ $post ] = $product->get_formatted_name();

        }

        $found_products = apply_filters( 'woocommerce_json_search_found_products', $found_products );

        echo json_encode( $found_products );

        die();
    }

}

$GLOBALS['fue_subscriptions'] = new FUE_Subscriptions();
