<?php

class FUE_Warranty {

    public function __construct() {
        if ( self::is_installed() ) {
            add_filter( 'fue_trigger_types', array(&$this, 'add_trigger') );
            add_filter( 'fue_email_type_triggers', array($this, 'add_email_triggers') );
            add_action( 'wc_warranty_status_updated', array(&$this, 'status_updated'), 10, 2 );
        }
    }

    public static function is_installed() {
        return class_exists('WC_Warranty');
    }

    public function add_trigger( $triggers ) {
        $triggers['warranty_status'] = __('after warranty status changes', 'wc_followup_emails');
        return $triggers;
    }

    public function add_email_triggers( $email_triggers ) {
        $email_triggers['generic'][] = 'warranty_status';
        $email_triggers['normal'][] = 'warranty_status';

        return $email_triggers;
    }

    public function status_updated( $request_id, $status ) {
        global $wpdb;

        $order_id   = get_post_meta( $request_id, '_order_id', true );
        $triggers   = array('warranty_status');

        $emails = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}followup_emails WHERE `interval_type` = 'warranty_status' AND status = 1" );

        foreach ( $emails as $email ) {
            $interval   = (int)$email->interval_num;

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
                'user_id'       => 0,
                'order_id'      => $order_id,
                'is_cart'       => 0
            );

            FUE::insert_email_order( $insert );

            // Tell FUE that an email order has been created
            // to stop it from sending generic emails
            if (! defined('FUE_ORDER_CREATED'))
                define('FUE_ORDER_CREATED', true);
        }

        FUE_Woocommerce::create_email_orders( $triggers, $order_id );
    }

}

$GLOBALS['fue_warranty'] = new FUE_Warranty();
