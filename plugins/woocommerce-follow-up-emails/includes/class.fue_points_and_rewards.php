<?php

class FUE_Points_And_Rewards {

    public function __construct() {

        if (self::is_installed()) {
            add_filter( 'fue_email_types', array($this, 'add_type') );
            add_filter( 'fue_trigger_types', array(&$this, 'add_trigger') );
            add_filter( 'fue_email_type_triggers', array($this, 'add_email_triggers') );
            add_action( 'fue_email_form_interval_meta', array($this, 'add_interval_meta') );

            add_filter( 'fue_email_type_long_descriptions', array($this, 'email_type_long_descriptions') );
            add_filter( 'fue_email_type_short_descriptions', array($this, 'email_type_short_descriptions') );

            // reports
            add_filter( 'fue_interval_str', array($this, 'interval_string'), 10, 2 );

            add_filter( 'fue_email_type_is_valid', array($this, 'email_type_valid'), 10, 2 );

            add_action( 'fue_email_form_script', array($this, 'add_script') );

            add_action( 'wc_points_rewards_after_increase_points', array(&$this, 'after_points_increased'), 10, 5 );
            add_action( 'fue_email_variables_list', array(&$this, 'email_variables_list') );

            add_filter( 'fue_send_test_email_subject', array(&$this, 'test_replace_variables') );
            add_filter( 'fue_send_test_email_message', array(&$this, 'test_replace_variables') );

            add_filter( 'fue_send_email_subject', array(&$this, 'replace_variables'), 10, 2 );
            add_filter( 'fue_send_email_message', array(&$this, 'replace_variables'), 10, 2 );
        }
    }

    public static function is_installed() {
        return class_exists('WC_Points_Rewards');
    }

    public function add_type( $types ) {
        $types['points_and_rewards'] = 'Points and Rewards Email';

        return $types;
    }

    public function add_trigger( $triggers ) {
        $triggers['points_earned'] = __('After: Points Earned', 'wc_followup_emails');
        $triggers['points_greater_than'] = __('Earned Points is greater than', 'wc_followup_emails');
        return $triggers;
    }

    public function add_email_triggers( $email_triggers ) {
        $email_triggers['points_and_rewards'] = array('points_earned', 'points_greater_than');

        return $email_triggers;
    }

    public function add_interval_meta( $defaults ) {
        ?>
        <span class="points-greater-than-meta" style="display:none;">
            <input type="text" name="meta[points_greater_than]" value="<?php if (isset($defaults['meta']['points_greater_than'])) echo $defaults['meta']['points_greater_than']; ?>" />
        </span>
        <?php
    }

    public function email_type_long_descriptions( $descriptions ) {
        $descriptions['points_and_rewards']    = __('Points and Rewards emails will send to a user based upon the point earnings status you define when creating your emails. Below are the existing Points and Rewards emails set up for your store. Use the priorities to define which emails are most important. These emails are selected first when sending the email to the customer if more than one criteria is met by multiple emails. Only one email is sent out to the customer (unless you enable the Always Send option when creating your emails), so prioritizing the emails for occasions where multiple criteria are met ensures you send the right email to the right customer at the time you choose.', 'follow_up_emails');

        return $descriptions;
    }

    public function email_type_short_descriptions( $descriptions ) {
        $descriptions['points_and_rewards']    = __('Points and Rewards emails will send to a user based upon the point earnings status you define when creating your emails.', 'follow_up_emails');

        return $descriptions;
    }

    public function email_type_valid( $is_valid, $data ) {
        if ( $data['email_type'] == 'points_and_rewards' ) $is_valid = true;

        return $is_valid;
    }

    public function add_script() {
        ?>
        jQuery("body").bind("fue_email_type_changed", function(evt, type) {

            points_rewards_toggle_fields( type );
        });

        function points_rewards_toggle_fields( type ) {
            if (type == "points_and_rewards") {

                var show = ['.var_points_and_rewards'];
                var hide = ['.always_send_tr', '.signup_description', '.product_description_tr', '.product_tr', '.category_tr', '.use_custom_field_tr', '.custom_field_tr', '.var_item_name', '.var_item_category', '.var_item_names', '.var_item_categories', '.var_item_name', '.var_item_category', '.interval_type_after_last_purchase', '.interval_duration_date', '.var_customer'];

                for (x = 0; x < hide.length; x++) {
                    jQuery(hide[x]).hide();
                }

                for (x = 0; x < show.length; x++) {
                    jQuery(show[x]).show();
                }

                // triggers
                jQuery(".interval_type_option").remove();

                if ( email_intervals && email_intervals.points_and_rewards.length > 0 ) {
                    for (var x = 0; x < email_intervals.points_and_rewards.length; x++) {
                        var int_key = email_intervals.points_and_rewards[x];
                        jQuery("#interval_type").append('<option class="interval_type_option interval_type_'+ int_key +'" id="interval_type_option_'+ int_key +'" value="'+ int_key +'">'+ interval_types[int_key] +'</option>');
                    }
                }

                jQuery(".interval_duration_date").hide();

                jQuery("#interval_type")
                    .val("points_earned")
                    .change();
            } else {
                var hide = ['.interval_type_points_earned', '.interval_type_points_greater_than', '.var_points_and_rewards'];

                for (x = 0; x < hide.length; x++) {
                    jQuery(hide[x]).hide();
                }
            }
        }

        jQuery(document).ready(function() {
            points_rewards_toggle_fields( jQuery("#email_type").val() );

            jQuery("#interval_type").change(function() {
                if (jQuery(this).val() == "points_greater_than") {
                    jQuery(".points-greater-than-meta").show();
                } else {
                    jQuery(".points-greater-than-meta").hide();
                }
            });
        });
        <?php
    }

    public function after_points_increased( $user_id, $points, $event_type, $data = null, $order_id = 0 ) {
        global $wpdb;

        $emails = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}followup_emails WHERE `interval_type` IN ('points_earned', 'points_greater_than') AND status = 1" );

        foreach ( $emails as $email ) {
            $interval   = (int)$email->interval_num;

            if ( $email->interval_type == 'points_greater_than' ) {
                $meta = maybe_unserialize( $email->meta );
                if ( $points < $meta['points_greater_than'] ) continue;
            }

            if ( $email->interval_type == 'date' ) {
                if ( !empty($email->send_date_hour) && !empty($email->send_date_minute) ) {
                    $send_on = strtotime($email->send_date .' '. $email->send_date_hour .':'. $email->send_date_minute);

                    if ( false === $send_on ) {
                        // fallback to only using the date
                        $send_on = strtotime($email->send_date);
                    }
                } else {
                    $send_on = strtotime($email->send_date);
                }
            } else {
                $add        = FUE::get_time_to_add( $interval, $email->interval_duration );
                $send_on    = current_time('timestamp') + $add;
            }

            $insert = array(
                'send_on'       => $send_on,
                'email_id'      => $email->id,
                'user_id'       => $user_id,
                'order_id'      => $order_id,
                'is_cart'       => 0
            );

            $email_order_id = FUE::insert_email_order( $insert );
            $data = array(
                'user_id'       => $user_id,
                'points'        => $points,
                'event_type'    => $event_type
            );
            update_option( 'fue_email_order_'. $email_order_id, $data );

            // Tell FUE that an email order has been created
            // to stop it from sending generic emails
            if (! defined('FUE_ORDER_CREATED'))
                define('FUE_ORDER_CREATED', true);
        }
    }

    public function email_variables_list() {
        global $woocommerce;
        ?>
        <li class="var hideable var_points_and_rewards"><strong>{points_earned}</strong> <img class="help_tip" title="<?php _e('The number of points earned', 'wc_followup_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
        <li class="var hideable var_points_and_rewards"><strong>{reward_event_description}</strong> <img class="help_tip" title="<?php _e('The description of the action', 'wc_followup_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
        <?php
    }

    public function replace_variables( $text, $email_order ) {
        global $wc_points_rewards;

        $event_data     = get_option( 'fue_email_order_'. $email_order->id, false );

        if (! $event_data ) {
            $event_data = array(
                'user_id'       => 0,
                'points'        => 0,
                'event_type'    => ''
            );
        }

        $points         = $event_data['points'];
        $points_label   = $wc_points_rewards->get_points_label( $points );
        $description    = WC_Points_Rewards_Manager::event_type_description($event_data['event_type']);
        $search         = array( '{order_number}', '{order_date}', '{order_datetime}', '{customer_first_name}', '{customer_name}', '{customer_email}', '{points_earned}', '{reward_event_description}' );

        if ( $email_order->order_id > 0 ) {
            $order = new WC_Order( $email_order->order_id );
            $order_number   = $order->get_order_number();
            $order_date     = date(get_option('date_format'), strtotime($order->order_date));
            $order_datetime = date(get_option('date_format') .' '. get_option('time_format'), strtotime($order->order_date));
            $first_name     = $order->billing_first_name;
            $name           = $order->billing_first_name .' '. $order->billing_last_name;
            $email          = $order->billing_email;
        } else {
            $user           = new WP_User( $email_order->user_id );
            $order_number   = '';
            $order_date     = '';
            $order_datetime = '';
            $first_name     = $user->first_name;
            $name           = $user->first_name .' '. $user->last_name;
            $email          = $user->user_email;
        }

        $replacements   = array( $order_number, $order_date, $order_datetime, $first_name, $name, $email, $points, $description );
        
        return str_replace( $search, $replacements, $text );
    }

    public function test_replace_variables( $text ) {
        $search         = array( '{points_earned}', '{reward_event_description}' );
        $replacements   = array( 50, 'Test Event Description' );
        return str_replace( $search, $replacements, $text );
    }

    public function interval_string( $string, $email ) {
        if ( $email->interval_type == 'points_greater_than' ) {
            $meta = maybe_unserialize( $email->meta );
            $string = sprintf( __('%d %s %s %d'), $email->interval_num, FollowUpEmails::get_duration($email->interval_duration), FollowUpEmails::get_trigger_name($email->interval_type), $meta['points_greater_than'] );
        }

        return $string;
    }

}

$GLOBALS['fue_points_and_rewards'] = new FUE_Points_And_Rewards();
