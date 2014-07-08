<?php

class FUE_Bookings {

    public static $statuses = array();

    public function __construct() {

        self::$statuses = array( 'unpaid', 'pending', 'confirmed', 'paid', 'cancelled', 'complete' );

        add_filter( 'fue_email_types', array($this, 'add_type') );
        add_filter( 'fue_trigger_types', array($this, 'add_trigger') );
        add_filter( 'fue_email_type_triggers', array($this, 'add_email_triggers') );

        add_filter( 'fue_email_type_long_descriptions', array($this, 'email_type_long_descriptions') );
        add_filter( 'fue_email_type_short_descriptions', array($this, 'email_type_short_descriptions') );

        add_filter( 'fue_email_type_is_valid', array($this, 'email_type_valid'), 10, 2 );

        // manual emails
        add_action( 'fue_manual_types', array($this, 'manual_types') );
        add_action( 'fue_manual_type_actions', array($this, 'manual_type_actions') );
        add_action( 'fue_manual_js', array($this, 'manual_js') );
        add_filter( 'fue_send_manual_emails', array($this, 'send_manual_email'), 10, 2 );

        add_filter( 'fue_interval_str', array($this, 'interval_string'), 10, 2 );
        add_action( 'fue_email_form_script', array($this, 'form_script') );
        add_action( 'fue_manual_js', array($this, 'manual_form_script') );

        add_action( 'fue_email_variables_list', array($this, 'email_variables_list') );
        add_action( 'fue_email_manual_variables_list', array($this, 'email_variables_list') );

        add_filter( 'fue_email_wc_bookings_variables', array($this, 'email_variables'), 10, 4 );
        add_filter( 'fue_email_wc_bookings_replacements', array($this, 'email_replacements'), 10, 4 );

        add_filter( 'fue_email_wc_bookings_test_variables', array($this, 'email_test_variables'), 10, 4 );
        add_filter( 'fue_email_wc_bookings_test_replacements', array($this, 'email_test_replacements'), 10, 4 );

        add_filter( 'fue_email_manual_variables', array($this, 'email_manual_variables'), 10, 4 );
        add_filter( 'fue_email_manual_replacements', array($this, 'email_manual_replacements'), 10, 4 );

        add_action( 'woocommerce_new_booking', array($this, 'booking_created') );
        foreach ( self::$statuses as $status ) {
            add_action( 'woocommerce_booking_'. $status, array($this, 'booking_status_updated') );
        }
    }

    public static function is_installed() {
        return class_exists('WC_Booking');
    }

    public function add_type( $types ) {
        $types['wc_bookings'] = 'WooCommerce Bookings';

        return $types;
    }

    public function add_trigger( $triggers ) {
        $triggers['before_booking_event']   = __( 'Before Booked Date', 'follow_up_emails' );
        $triggers['after_booking_event']   = __( 'After Booked Date', 'follow_up_emails' );
        $triggers['booking_created']        = __( 'After Booking is Created', 'follow_up_emails' );

        // add booking statuses
        foreach ( self::$statuses as $status )
            $triggers['booking_status_'. $status] = sprintf( __( 'After Booking Status: %s', 'follow_up_emails' ), $status );

        return $triggers;
    }

    public function add_email_triggers( $email_triggers ) {
        $email_triggers['wc_bookings'][] = 'before_booking_event';
        $email_triggers['wc_bookings'][] = 'after_booking_event';
        $email_triggers['wc_bookings'][] = 'booking_created';

        // add booking statuses
        foreach ( self::$statuses as $status )
            $email_triggers['wc_bookings'][] = 'booking_status_'. $status;

        return $email_triggers;
    }

    public function email_type_long_descriptions( $descriptions ) {
        $descriptions['wc_bookings'] = __('WooCommerce Bookings is an extension that allows you to sell your time or date based bookings. You can send follow-up emails to customers that book appointments, services or rentals.', 'follow_up_emails');

        return $descriptions;
    }

    public function email_type_short_descriptions( $descriptions ) {
        $descriptions['wc_bookings']    = __('You can send follow-up emails to customers that book appointments, services or rentals.', 'follow_up_emails');

        return $descriptions;
    }

    public function manual_types() {
        ?><option value="booked_event"><?php _e('Customers who booked this event', 'follow_up_emails'); ?></option><?php
    }

    public function manual_type_actions() {
        $products = array();

        $posts = get_posts( array(
                'post_type'     => 'product',
                'post_status'   => 'publish'
            ) );

        foreach ($posts as $post) {
            $product = sfn_get_product( $post->ID );

            if ( $product->is_type( array( 'booking' ) ) )
                $products[] = $product;
        }

        ?>
        <div class="send-type-bookings send-type-div">
            <select id="booking_event_id" name="booking_event_id" class="chzn-select" style="width: 400px;">
                <?php foreach ( $products as $product ): ?>
                    <option value="<?php echo $product->id; ?>"><?php echo esc_html( $product->get_title() ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php
    }

    public function manual_js() {
        ?>
        jQuery("#send_type").change(function() {
            switch (jQuery(this).val()) {
                case "booked_event":
                    jQuery(".send-type-bookings").show();
                    break;
            }
        }).change();
    <?php
    }

    public function send_manual_email( $recipients, $post ) {
        global $wpdb;

        if ( $post['send_type'] == 'booked_event' ) {

            $search_args = array(
                'post_type'     => 'wc_booking',
                'post_status'   => array( 'complete', 'paid' ),
                'meta_query'    => array(
                                        array(
                                            'key'       => '_booking_product_id',
                                            'value'     => $post['booking_event_id'],
                                            'compare'   => '='
                                        )
                                    )
            );

            $bookings = get_posts( $search_args );

            foreach ( $bookings as $booking ) {

                $order_item_id  = get_post_meta( $booking->ID, '_booking_order_item_id', true );
                $user_id        = get_post_meta( $booking->ID, '_booking_customer_id', true );
                $order_id       = $wpdb->get_var( $wpdb->prepare("SELECT order_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d", $order_item_id) );
                $order          = new WC_Order( $order_id );

                $key = $user_id .'|'. $order->billing_email .'|'. $order->billing_first_name .' '. $order->billing_last_name;
                $recipients[$key] = array($user_id, $order->billing_email, $order->billing_first_name .' '. $order->billing_last_name);

            }

        }

        return $recipients;
    }

    public function email_type_valid( $is_valid, $data ) {
        if ( $data['email_type'] == 'wc_bookings' )
            $is_valid = true;

        return $is_valid;
    }

    public function interval_string( $string, $email ) {

        return $string;
    }

    public function form_script() {
        ?>
        jQuery("body").bind("fue_email_type_changed", function(evt, type) {
            wc_bookings_toggle_fields(type);
        });

        function wc_bookings_toggle_fields( type ) {
            if (type == "wc_bookings") {
                var show = ['.interval_type_before_booking_event', '.interval_type_after_booking', '.interval_type_after_booking_approved'];
                var hide = ['.interval_type_option', '.interval_duration_date', '.always_send_tr', '.signup_description', '.product_description_tr', '.product_tr', '.category_tr', '.use_custom_field_tr', '.custom_field_tr', '.var_item_names', '.var_item_categories', '.interval_type_after_last_purchase', '.var_customer'];

                for (x = 0; x < hide.length; x++) {
                    jQuery(hide[x]).hide();
                }

                for (x = 0; x < show.length; x++) {
                    jQuery(show[x]).show();
                }

                // triggers
                var interval_type_value = jQuery("#interval_type").val();
                jQuery(".interval_type_option").remove();

                if ( email_intervals && email_intervals.wc_bookings.length > 0 ) {
                    for (var x = 0; x < email_intervals.wc_bookings.length; x++) {
                        var int_key = email_intervals.wc_bookings[x];
                        var selected = (int_key == interval_type_value) ? 'selected' : '';

                        jQuery("#interval_type").append('<option class="interval_type_option interval_type_'+ int_key +'" id="interval_type_option_'+ int_key +'" value="'+ int_key +'" '+ selected +'>'+ interval_types[int_key] +'</option>');
                    }
                }

                jQuery("#interval_type").change();
            } else {
                var hide = ['.interval_type_before_booking_event', '.interval_type_after_booking', '.interval_type_after_booking_approved', '.var_wc_bookings'];

                for (x = 0; x < hide.length; x++) {
                    jQuery(hide[x]).hide();
                }
            }
        }

        jQuery(document).ready(function() {
            wc_bookings_toggle_fields( jQuery("#email_type").val() );
            jQuery(".interval_duration_date").remove();
        });
        <?php
    }

    public function manual_form_script() {
        ?>
        jQuery("#send_type").change(function() {
            if ( jQuery(this).val() == "booked_event" ) {
                jQuery(".var_wc_bookings").show();
            } else {
                jQuery(".var_wc_bookings").hide();
            }
        }).change();
        <?php
    }

    public function email_variables_list() {
        global $woocommerce;
        ?>
        <li class="var hideable var_wc_bookings"><strong>{item_name}</strong> <img class="help_tip" title="<?php _e('The name of the purchased item.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
        <li class="var hideable var_wc_bookings"><strong>{item_category}</strong> <img class="help_tip" title="<?php _e('The list of categories where the purchased item is under.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
        <li class="var hideable var_wc_bookings"><strong>{booking_duration}</strong> <img class="help_tip" title="<?php _e('The duration of the booked product or service', 'follow_up_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
        <li class="var hideable var_wc_bookings"><strong>{booking_date}</strong> <img class="help_tip" title="<?php _e('The date of the booked product or service', 'follow_up_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
        <li class="var hideable var_wc_bookings"><strong>{booking_time}</strong> <img class="help_tip" title="<?php _e('The time of the booked product or service', 'follow_up_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
        <li class="var hideable var_wc_bookings"><strong>{booking_amount}</strong> <img class="help_tip" title="<?php _e('The amount or cost of the booked product or service', 'follow_up_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
        <li class="var hideable var_wc_bookings"><strong>{booking_resource}</strong> <img class="help_tip" title="<?php _e('The resource booked', 'follow_up_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
        <li class="var hideable var_wc_bookings"><strong>{booking_persons}</strong> <img class="help_tip" title="<?php _e('The count of persons this booking is for', 'follow_up_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
    <?php
    }

    public function email_variables( $vars, $email_data, $email_order, $email_row ) {
        $vars = array_merge($vars, array('{order_number}', '{order_date}', '{order_datetime}', '{order_billing_address}', '{order_shipping_address}', '{customer_first_name}', '{customer_name}', '{customer_email}', '{item_name}', '{item_category}', '{booking_duration}', '{booking_date}', '{booking_time}', '{booking_amount}', '{booking_resource}', '{booking_persons}'));

        return $vars;
    }

    public function email_replacements( $reps, $email_data, $email_order, $email_row ) {

        if ( $email_order->order_id && $email_order->product_id ) {
            $order          = new WC_Order( $email_order->order_id );
            $order_date     = date(get_option('date_format'), strtotime($order->order_date));
            $order_datetime = date(get_option('date_format') .' '. get_option('time_format'), strtotime($order->order_date));

            $order_id   = apply_filters( 'woocommerce_order_number', '#'.$email_order->order_id, $order );

            $billing_address    = $order->get_formatted_billing_address();
            $shipping_address   = $order->get_formatted_shipping_address();

            $item_id    = $email_order->product_id;
            $item_url   = FUE::create_email_url( $email_order->id, $email_row->id, $email_data['user_id'], $email_data['email_to'], get_permalink($item_id) );

            $categories = '';

            $cats   = get_the_terms($item_id, 'product_cat');

            if (is_array($cats) && !empty($cats)) {
                foreach ($cats as $cat) {
                    $categories .= $cat->name .', ';
                }
                $categories = rtrim($categories, ', ');
            }

            // booking data
            $meta       = maybe_unserialize($email_order->meta);
            $booking_id = $meta['booking_id'];

            /**
             * @var $booking WC_Booking
             * @var $booking_product WC_Product_Booking
             */
            $booking            = get_wc_booking( $booking_id );
            $booking_product    = $booking->get_product();

            $booking_duration   = $this->duration_to_string( $booking_product->get_duration(), $booking_product->get_duration_unit() );
            $booking_date       = $booking->get_start_date( get_option('date_format'), '' );
            $booking_time       = $booking->get_start_date( '', get_option('time_format') );
            $booking_amount     = woocommerce_price( $booking->cost );
            $booking_persons    = '';
            $booking_resource   = ($booking->resource_id > 0) ? get_the_title($booking->resource_id) : '';

            if ( $booking->has_persons() ) {
                $booking_persons = '<ul>';

                foreach ( $booking->get_persons() as $person_id => $num ) {
                    $booking_persons .= '<li>'. get_the_title($person_id) .': '. $num .'</li>';
                }

                $booking_persons .= '</ul>';
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
                    '<a href="'. $item_url .'">'. get_the_title($item_id) .'</a>',
                    $categories,
                    $booking_duration,
                    $booking_date,
                    $booking_time,
                    $booking_amount,
                    $booking_resource,
                    $booking_persons
                ));
        }

        return $reps;

    }

    public function email_manual_variables( $vars, $email_data, $email_order, $email_row ) {
        $vars = array_merge($vars, array('{order_number}', '{order_date}', '{order_datetime}', '{order_billing_address}', '{order_shipping_address}', '{customer_first_name}', '{customer_name}', '{customer_email}', '{item_name}', '{item_category}', '{booking_duration}', '{booking_date}', '{booking_time}', '{booking_amount}', '{booking_resource}', '{booking_persons}'));

        return $vars;
    }

    public function email_manual_replacements( $reps, $email_data, $email_order, $email_row ) {
        global $wpdb;

        if ( isset($_POST['send_type']) && $_POST['send_type'] == 'booked_event' ) {
            $product_id = $_POST['booking_event_id'];

            $search_args = array(
                'post_type'     => 'wc_booking',
                'post_status'   => array( 'complete', 'paid' ),
                'meta_query'    => array(
                    array(
                        'key'       => '_booking_product_id',
                        'value'     => $product_id,
                        'compare'   => '='
                    )
                )
            );

            $bookings = get_posts( $search_args );

            foreach ( $bookings as $booking ) {
                $booking_id     = $booking->ID;
                $order_item_id  = get_post_meta( $booking->ID, '_booking_order_item_id', true );
                $user_id        = get_post_meta( $booking->ID, '_booking_customer_id', true );
                $order_id       = $wpdb->get_var( $wpdb->prepare("SELECT order_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d", $order_item_id) );
                $order          = new WC_Order( $order_id );

                $order_date     = date(get_option('date_format'), strtotime($order->order_date));
                $order_datetime = date(get_option('date_format') .' '. get_option('time_format'), strtotime($order->order_date));

                $order_id   = apply_filters( 'woocommerce_order_number', '#'.$email_order->order_id, $order );

                $billing_address    = $order->get_formatted_billing_address();
                $shipping_address   = $order->get_formatted_shipping_address();

                $item_id    = $product_id;
                $item_url   = FUE::create_email_url( $email_order->id, $email_row->id, $email_data['user_id'], $email_data['email_to'], get_permalink($item_id) );

                $categories = '';

                $cats   = get_the_terms($item_id, 'product_cat');

                if (is_array($cats) && !empty($cats)) {
                    foreach ($cats as $cat) {
                        $categories .= $cat->name .', ';
                    }
                    $categories = rtrim($categories, ', ');
                }

                /**
                 * @var $booking WC_Booking
                 * @var $booking_product WC_Product_Booking
                 */
                $booking            = get_wc_booking( $booking_id );
                $booking_product    = $booking->get_product();

                $booking_duration   = $this->duration_to_string( $booking_product->get_duration(), $booking_product->get_duration_unit() );
                $booking_date       = $booking->get_start_date( get_option('date_format'), '' );
                $booking_time       = $booking->get_start_date( '', get_option('time_format') );
                $booking_amount     = woocommerce_price( $booking->cost );
                $booking_persons    = '';
                $booking_resource   = ($booking->resource_id > 0) ? get_the_title($booking->resource_id) : '';

                if ( $booking->has_persons() ) {
                    $booking_persons = '<ul>';

                    foreach ( $booking->get_persons() as $person_id => $num ) {
                        $booking_persons .= '<li>'. get_the_title($person_id) .': '. $num .'</li>';
                    }

                    $booking_persons .= '</ul>';
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
                        '<a href="'. $item_url .'">'. get_the_title($item_id) .'</a>',
                        $categories,
                        $booking_duration,
                        $booking_date,
                        $booking_time,
                        $booking_amount,
                        $booking_resource,
                        $booking_persons
                    ));
            }

        }

        return $reps;

    }

    public function email_test_variables() {}
    public function email_test_replacements() {}

    public function booking_created( $booking_id ) {
        $this->create_email_order( $booking_id, array('booking_created') );
    }

    public function booking_status_updated( $booking_id ) {
        /* @var $booking WC_Booking */
        $booking = get_wc_booking( $booking_id );
        $status  = $booking->get_status();

        $triggers = array('booking_status_'. $status);

        if ( $status == 'paid' ) {
            $triggers[] = 'before_booking_event';
            $triggers[] = 'after_booking_event';
        }

        $this->create_email_order( $booking_id, $triggers );

    }

    private function create_email_order( $booking_id, $triggers = array() ) {
        global $wpdb;

        $triggers_str = '';

        foreach ( $triggers as $trigger ) {
            $triggers_str .= "'". $trigger ."',";
        }
        $triggers_str = rtrim($triggers_str, ',');

        /**
         * @var $booking WC_Booking
         * @var $order WC_Order
         */
        $booking    = get_wc_booking( $booking_id );
        $order      = $booking->get_order();
        $emails     = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}followup_emails WHERE `interval_type` IN ($triggers_str) AND status = 1" );

        foreach ( $emails as $email ) {

            if ( $email->interval_type == 'before_booking_event' ) {
                $start  = get_post_meta( $booking_id, '_booking_start', true );
                $time   = FUE::get_time_to_add( $email->interval_num, $email->interval_duration );

                $send_on = $start - $time;
            } elseif ( $email->interval_type == 'after_booking_event' ) {
                $start  = get_post_meta( $booking_id, '_booking_end', true );
                $time   = FUE::get_time_to_add( $email->interval_num, $email->interval_duration );

                $send_on = $start + $time;
            } else {
                $interval   = (int)$email->interval_num;

                $add        = FUE::get_time_to_add( $interval, $email->interval_duration );
                $send_on    = current_time('timestamp') + $add;
            }

            $insert = array(
                'send_on'       => $send_on,
                'email_id'      => $email->id,
                'product_id'    => $booking->product_id,
                'order_id'      => $booking->order_id,
                'meta'          => array('booking_id' => $booking_id)
            );

            if ( $order->customer_user ) {
                $user_id                = $order->customer_user;
                $user                   = new WP_User($user_id);
                $insert['user_id']      = $user_id;
                $insert['user_email']   = $user->user_email;
            }

            FUE::insert_email_order( $insert );

            // Tell FUE that an email order has been created
            // to stop it from sending generic emails
            if (! defined('FUE_ORDER_CREATED'))
                define('FUE_ORDER_CREATED', true);
        }
    }

    private function duration_to_string( $duration, $unit ) {
        $unit = rtrim($unit, 's');

        return ($duration == 1) ? $duration .' '. $unit : $duration .' '. $unit .'s';
    }

}

if ( FUE_Bookings::is_installed() )
    new FUE_Bookings();