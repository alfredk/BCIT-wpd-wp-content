<?php

class FUE_The_Events_Calendar {

    public function __construct() {
        if (self::is_installed()) {
            add_filter( 'fue_email_types', array($this, 'add_type') );
            add_filter( 'fue_trigger_types', array($this, 'add_trigger') );
            add_filter( 'fue_email_type_triggers', array($this, 'add_email_triggers') );

            add_filter( 'fue_email_type_long_descriptions', array($this, 'email_type_long_descriptions') );
            add_filter( 'fue_email_type_short_descriptions', array($this, 'email_type_short_descriptions') );

            add_filter( 'fue_email_type_is_valid', array($this, 'email_type_valid'), 10, 2 );

            add_filter( 'fue_interval_str', array($this, 'interval_string'), 10, 2 );
            add_action( 'fue_email_form_script', array($this, 'add_script') );

            add_action( 'fue_email_variables_list', array($this, 'add_variables') );

            add_action( 'fue_email_form_after_interval', array($this, 'after_interval') );

            add_filter( 'fue_send_email_subject', array($this, 'replace_vars'), 10, 2 );
            add_filter( 'fue_send_email_message', array($this, 'replace_vars'), 10, 2 );

            add_filter( 'fue_send_test_email_subject', array(&$this, 'test_replace_vars') );
            add_filter( 'fue_send_test_email_message', array(&$this, 'test_replace_vars') );

            add_action( 'woocommerce_order_status_completed', array($this, 'set_reminders'), 20 );
        }
    }

    public static function is_installed() {
        return in_array('wootickets/wootickets.php', get_option('active_plugins', array()));
    }

    public function add_type( $types ) {
        $types['wootickets'] = 'WooTickets';

        return $types;
    }

    public function add_trigger( $triggers ) {
        $triggers['before_tribe_event_starts'] = __('before event starts', 'wc_followup_emails');
        $triggers['after_tribe_event_ends'] = __('after event ends', 'wc_followup_emails');

        return $triggers;
    }

    public function add_email_triggers( $email_triggers ) {
        $email_triggers['wootickets'] = array('before_tribe_event_starts', 'after_tribe_event_ends');

        return $email_triggers;
    }

    public function interval_string( $string, $email ) {
        if ( $email->interval_type == 'before_tribe_event_starts' || $email->interval_type == 'after_tribe_event_ends' ) {
            $string = sprintf( __('%d %s %s'), $email->interval_num, FollowUpEmails::get_duration($email->interval_duration), FollowUpEmails::get_trigger_name($email->interval_type) );
        }
        return $string;
    }

    public function email_type_long_descriptions( $descriptions ) {
        $descriptions['wootickets']    = __('WooCommerce Tickets emails will send to a user based upon the event/ticket status you define when creating your emails. Below are the existing WooCommerce Tickets emails set up for your store. Use the priorities to define which emails are most important. These emails are selected first when sending the email to the customer if more than one criteria is met by multiple emails. Only one email is sent out to the customer (unless you enable the Always Send option when creating your emails), so prioritizing the emails for occasions where multiple criteria are met ensures you send the right email to the right customer at the time you choose.', 'follow_up_emails');

        return $descriptions;
    }

    public function email_type_short_descriptions( $descriptions ) {
        $descriptions['wootickets']    = __('WooCommerce Tickets emails will send to a user based upon the event or ticket status you define when creating your emails.', 'follow_up_emails');

        return $descriptions;
    }

    public function add_script() {
        ?>
        jQuery("body").bind("fue_email_type_changed", function(evt, type) {
            wootickets_toggle_fields(type);
        });

        jQuery("body").bind("fue_interval_type_changed", function(evt, type) {
            if (type == "before_tribe_event_starts" || type == "after_tribe_event_ends") {
                jQuery(".adjust_date_tr").show();
            }
        });

        function wootickets_toggle_fields( type ) {
            if (type == "wootickets") {
                var show = ['.adjust_date_tr', '.interval_type_before_tribe_event_starts', '.interval_type_after_tribe_event_ends'];
                var hide = ['.interval_type_option', '.always_send_tr', '.signup_description', '.product_description_tr', '.product_tr', '.category_tr', '.use_custom_field_tr', '.custom_field_tr', '.var_item_name', '.var_item_category', '.var_item_names', '.var_item_categories', '.var_item_name', '.var_item_category', '.interval_type_after_last_purchase', '.var_customer'];

                for (x = 0; x < hide.length; x++) {
                    jQuery(hide[x]).hide();
                }

                for (x = 0; x < show.length; x++) {
                    jQuery(show[x]).show();
                }

                // triggers
                jQuery(".interval_type_option").remove();

                if ( email_intervals && email_intervals.wootickets.length > 0 ) {
                    for (var x = 0; x < email_intervals.wootickets.length; x++) {
                        var int_key = email_intervals.wootickets[x];
                        jQuery("#interval_type").append('<option class="interval_type_option interval_type_'+ int_key +'" id="interval_type_option_'+ int_key +'" value="'+ int_key +'">'+ interval_types[int_key] +'</option>');
                    }
                }

                jQuery("#interval_type").change();
            } else {
                var hide = ['.var_events_calendar', '.interval_type_before_tribe_event_starts', '.interval_type_after_tribe_event_ends', '.tribe_limit_tr'];

                for (x = 0; x < hide.length; x++) {
                    jQuery(hide[x]).hide();
                }
            }
        }

        jQuery(document).ready(function() {
            wootickets_toggle_fields( jQuery("#email_type").val() );

            jQuery("#interval_type").change(function() {
                var val = jQuery(this).val();
                if ( val == "before_tribe_event_starts" || val == "after_tribe_event_ends" ) {
                    jQuery(".interval_duration_date").hide();
                    jQuery(".interval_type_after_span").hide();
                    jQuery(".var_events_calendar").show();
                } else {
                    jQuery(".var_events_calendar").hide();
                }

                if (val == "before_tribe_event_starts") {
                    jQuery(".tribe_limit_tr").show();
                } else {
                    jQuery(".tribe_limit_tr").hide();
                }

            }).change();
        });
        <?php
    }

    public function email_type_valid( $is_valid, $data ) {
        if ( $data['email_type'] == 'wootickets' ) $is_valid = true;

        return $is_valid;
    }

    public function add_variables() {
        global $woocommerce;
        ?>
        <li class="var hideable var_events_calendar var_event_name"><strong>{event_name}</strong> <img class="help_tip" title="<?php _e('The name of the event', 'wc_followup_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
        <li class="var hideable var_events_calendar var_event_link"><strong>{event_link}</strong> <img class="help_tip" title="<?php _e('The name of the event with a link to the event page', 'wc_followup_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
        <li class="var hideable var_events_calendar var_event_url"><strong>{event_url}</strong> <img class="help_tip" title="<?php _e('The URL of the event', 'wc_followup_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
        <li class="var hideable var_events_calendar var_event_location"><strong>{event_location}</strong> <img class="help_tip" title="<?php _e('The name and address of the venue', 'wc_followup_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
        <li class="var hideable var_events_calendar var_event_organizer"><strong>{event_organizer}</strong> <img class="help_tip" title="<?php _e('The name of the event organizer', 'wc_followup_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
        <li class="var hideable var_events_calendar var_event_start_datetime"><strong>{event_start_datetime}</strong> <img class="help_tip" title="<?php _e('The start date/time of the event', 'wc_followup_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
        <li class="var hideable var_events_calendar var_event_end_datetime"><strong>{event_end_datetime}</strong> <img class="help_tip" title="<?php _e('The end date/time of the event', 'wc_followup_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
        <li class="var hideable var_events_calendar var_event_ticket_name"><strong>{ticket_name}</strong> <img class="help_tip" title="<?php _e('The name of the ticket', 'wc_followup_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
        <li class="var hideable var_events_calendar var_event_ticket_description"><strong>{ticket_description}</strong> <img class="help_tip" title="<?php _e('The description of the ticket', 'wc_followup_emails'); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></li>
        <?php
    }

    public function after_interval( $defaults ) {
        $days = (isset($defaults['meta']['tribe_limit_days']) ) ? $defaults['meta']['tribe_limit_days'] : '';
        ?>
        <div class="field tribe_limit_tr">
            <label for="meta_tribe_limit">
                <input type="checkbox" name="meta[tribe_limit]" id="meta_tribe_limit" value="yes" <?php if (isset($defaults['meta']['tribe_limit']) && $defaults['meta']['tribe_limit'] == 'yes') echo 'checked'; ?> style="vertical-align: baseline;" />
                <?php printf( __('Do not send email if a customer books a ticket %s days before the event starts.', 'wc_followup_emails'), '<input type="text" name="meta[tribe_limit_days]" size="2" value="'. $days .'" placeholder="5" />'); ?>
            </label>
        </div>
        <?php
    }

    public function replace_vars( $text, $email_order ) {
        global $wpdb;

        $ticket_id      = $email_order->product_id;
        $event_id       = get_post_meta( $ticket_id, '_tribe_wooticket_for_event', true );
        $woo_tickets    = TribeWooTickets::get_instance();
        $ticket         = $woo_tickets->get_ticket( $event_id, $ticket_id );

        // Ticket Vars
        $ticket_name    = $ticket->name;
        $ticket_desc    = $ticket->description;

        // Event Vars
        $event_name     = get_the_title( $event_id );
        $event_link     = '<a href="'. get_permalink( $event_id ) .'">'. $event_name .'</a>';
        $event_url      = get_permalink( $event_id );
        $event_location = '';
        $event_org      = '';
        $event_start    = '';
        $event_end      = '';

        $venue_id = get_post_meta( $event_id, '_EventVenueID', true );

        if (! empty($venue_id) ) {
            $venue_name     = get_post_meta( $venue_id, '_VenueVenue', true );
            $venue_address  = get_post_meta( $venue_id, '_VenueAddress', true );
            $venue_city     = get_post_meta( $venue_id, '_VenueCity', true );
            $venue_country  = get_post_meta( $venue_id, '_VenueCountry', true );
            $venue_state    = get_post_meta( $venue_id, '_VenueStateProvince', true );
            $venue_zip      = get_post_meta( $venue_id, '_VenueZip', true );

            $event_location = sprintf( '<b>%s</b><br/>%s<br/>%s, %s<br/>%s %s', $venue_name, $venue_address, $venue_city, $venue_state, $venue_country, $venue_zip );
        }

        $org_id = get_post_meta( $event_id, '_EventOrganizerID', true );

        if (! empty($org_id) ) {
            $event_org = get_post_meta( $org_id, '_OrganizerOrganizer', true );
        }

        $start_stamp    = strtotime( get_post_meta( $event_id, '_EventStartDate', true ) );
        if ( $start_stamp ) {
            $event_start    = date( get_option('date_format') .' '. get_option('time_format'), $start_stamp );
        }

        $end_stamp      = strtotime( get_post_meta( $event_id, '_EventEndDate', true ) );
        if ( $end_stamp ) {
            $event_end    = date( get_option('date_format') .' '. get_option('time_format'), $end_stamp );
        }

        $search         = array( '{event_name}', '{event_start_datetime}', '{event_end_datetime}', '{event_link}', '{event_url}', '{event_location}', '{event_organizer}', '{ticket_name}', '{ticket_description}' );
        $replacements   = array( $event_name, $event_start, $event_end, $event_link, $event_url, $event_location, $event_org, $ticket_name, $ticket_desc );
        $text           = str_replace( $search, $replacements, $text );

        return $text;

    }

    public function test_replace_vars( $text ) {
        $now            = current_time('timestamp');
        $event_name     = 'Event Name';
        $event_start    = date( get_option('date_format') .' '. get_option('time_format'), $now + 86400 );
        $event_end      = date( get_option('date_format') .' '. get_option('time_format'), $now + (86400*2) );
        $event_link     = '<a href="'. site_url() .'">Event Name</a>';
        $event_url      = site_url();
        $event_location = 'The Venue';
        $event_org      = 'Event Organizer';
        $ticket_name    = 'Ticket A Upper Box B';
        $ticket_desc    = 'The ticket\'s description';

        $search         = array( '{event_name}', '{event_start_datetime}', '{event_end_datetime}', '{event_link}', '{event_url}', '{event_location}', '{event_organizer}', '{ticket_name}', '{ticket_description}' );
        $replacements   = array( $event_name, $event_start, $event_end, $event_link, $event_url, $event_location, $event_org, $ticket_name, $ticket_desc );
        $text           = str_replace( $search, $replacements, $text );

        return $text;
    }

    public function set_reminders( $order_id ) {
        global $woocommerce, $wpdb;

        // load reminder emails
        $emails     = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}followup_emails WHERE `interval_type` IN ('before_tribe_event_starts', 'after_tribe_event_ends') AND status = 1 ORDER BY `priority` ASC");
        $tickets    = array();

        if ( empty($emails) ) return;

        $has_tickets = get_post_meta( $order_id, '_tribe_has_tickets', true );

        $order  = new WC_Order( $order_id );
        $items  = $order->get_items();

        foreach ( $items as $item ) {
            $ticket_id = (isset($item['id'])) ? $item['id'] : $item['product_id'];

            // if $item is a ticket, load the event where the ticket is attached to
            $event_id = get_post_meta( $ticket_id, '_tribe_wooticket_for_event', true );

            if (! $event_id ) continue;

            if (! in_array($ticket_id, $tickets) ) $tickets[] = $ticket_id;
        }

        $now = current_time('timestamp');
        foreach ( $emails as $email ) {
            $interval   = (int)$email->interval_num;
            $add        = FUE::get_time_to_add( $interval, $email->interval_duration );

            foreach ( $tickets as $ticket_id ) {
                $event_id = get_post_meta( $ticket_id, '_tribe_wooticket_for_event', true );

                if ( $email->interval_type == 'before_tribe_event_starts' ) {
                    $start = get_post_meta( $event_id, '_EventStartDate', true );

                    if ( empty($start) ) continue;
                    $start = strtotime($start);

                    // check if a limit is in place
                    if ( isset($email->meta['tribe_limit'], $email->meta['tribe_limit_days']) && !empty($tribe_limit_days) ) {
                        $days = ($start - $now) / 86400;

                        if ( $days <= $email->meta['tribe_limit_days'] ) {
                            // $days is within limit - skip
                            continue;
                        }
                    }

                    $send_on    = $start - $add;

                    // if send_on is in the past, do not queue it
                    if ( $now > $send_on ) continue;
                } else {
                    $end        = get_post_meta( $event_id, '_EventEndDate', true );

                    if ( empty($end) ) continue;

                    $end        = strtotime($end);
                    $send_on    = $end + $add;

                    // if send_on is in the past, do not queue it
                    if ( $now > $send_on ) continue;
                }

                $insert = array(
                    'user_id'       => $order->user_id,
                    'order_id'      => $order_id,
                    'product_id'    => $ticket_id,
                    'email_id'      => $email->id,
                    'send_on'       => $send_on
                );
                FUE::insert_email_order($insert);
            }
        }
    }

}

$GLOBALS['fue_the_events_calendar'] = new FUE_The_Events_Calendar();
