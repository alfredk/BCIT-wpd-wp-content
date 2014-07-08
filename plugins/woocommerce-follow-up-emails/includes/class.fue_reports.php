<?php

class FUE_Reports {

    public function __construct() {
        add_action('fue_menu', array($this, 'menu'), 20);

        add_action('admin_enqueue_scripts', array($this, 'settings_scripts'));

        // email open tracking
        add_action( 'init', array(&$this, 'pixel_tracker') );

        // email tracking
        add_filter('query_vars', array(&$this, 'query_vars'));
        add_action('template_redirect', array(&$this, 'template_redirect'));

        // forms
        add_action('fue_new_email_form_after_message', array(&$this, 'form_after_message'));
        add_action('fue_edit_email_form_after_message', array(&$this, 'form_after_message'));

        // sending
        add_filter('fue_email_message', array(&$this, 'email_message'), 10, 3);

        // log emails sent
        add_action( 'fue_email_sent_details', array(&$this, 'emails_sent'), 10, 6 );

        // link from user profile
        add_action( 'edit_user_profile', array($this, 'add_profile_link'), 11 );
        add_action( 'show_user_profile', array($this, 'add_profile_link'), 11 );
    }

    public function menu() {
        add_submenu_page( 'followup-emails', __('Reports', 'follow_up_emails'), __('Reports', 'follow_up_emails'), 'manage_follow_up_emails', 'followup-emails-reports', 'FUE_Reports::settings_main' );
    }

    public function settings_scripts() {
        global $woocommerce;

    }

    public function emails_sent( $email_order, $user_id, $email, $email_to, $cname, $trigger ) {
        global $wpdb;

        // load order and email row
        $email  = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}followup_emails` WHERE `id` = '%d'", $email_order->email_id) );

        FUE_Reports::email_log($email->id, $email_order->id, $user_id, $email->name, $cname, $email_to, $email_order->order_id, $email_order->product_id, $trigger);
    }

    function query_vars($vars) {
        $vars[] = 'sfn_trk';
        $vars[] = 'sfn_payload';
        return $vars;
    }

    function template_redirect() {
        global $wpdb;

        if ( intval(get_query_var('sfn_trk')) == 1 && get_query_var('sfn_payload') ) {
            $payload = base64_decode( get_query_var('sfn_payload') );
            $payload = str_replace( '&amp;', '&', $payload );

            $parsed = array();
            parse_str($payload, $parsed);

            if ( ! is_array($parsed) || count($parsed) < 3 ) return;

            // log this
            $insert = array(
                'event_type'    => 'click',
                'email_order_id'=> isset($parsed['oid']) ? $parsed['oid'] : 0,
                'email_id'      => $parsed['eid'],
                'user_id'       => isset($parsed['user_id']) ? $parsed['user_id'] : 0,
                'user_email'    => $parsed['user_email'],
                'target_url'    => $parsed['next'],
                'date_added'    => current_time('mysql')
            );
            $wpdb->insert($wpdb->prefix .'followup_email_tracking', $insert);

            wp_redirect( add_query_arg('fueid', $parsed['eid'], $parsed['next']) );
            exit;
        }
    }

    public function pixel_tracker() {
        global $wpdb;

        if ( isset($_GET['fuepx']) && $_GET['fuepx'] == 1 ) {
            if (!isset($_GET['data'])||empty($_GET['data'])) return;

            header("Content-Type: image/gif");

            $data   = base64_decode($_GET['data']);
            $parsed = array();
            parse_str($data, $parsed);

            // log this
            $insert = array(
                'event_type'    => 'open',
                'email_order_id'=> isset($parsed['oid']) ? $parsed['oid'] : 0,
                'email_id'      => (int)$parsed['eid'],
                'user_id'       => isset($parsed['user_id']) ? $parsed['user_id'] : 0,
                'user_email'    => $parsed['user_email'],
                'target_url'    => '',
                'date_added'    => current_time('mysql')
            );

            // only log the first 'open' event
            $count = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}followup_email_tracking WHERE event_type = 'open' AND email_order_id = %d AND email_id = %d AND user_id = %d", $insert['email_order_id'], $insert['email_id'], $insert['user_id'] ) );

            if ( $count == 0 ) {
                $wpdb->insert($wpdb->prefix .'followup_email_tracking', $insert);
            }

        }
    }

    public function form_after_message() {

    }

    public function email_message( $message, $email, $email_order ) {
        global $wpdb;

        $user_id = 0;
        if ( $email_order->order_id != 0 ) {
            // order
            $order = new WC_Order($email_order->order_id);

            if ( isset($order->user_id) && $order->user_id > 0 ) {
                $user_id    = $order->user_id;
                $wp_user    = new WP_User( $order->user_id );
                $email_to   = $wp_user->user_email;
            } else {
                $email_to   = $order->billing_email;
            }
        } else {
            $order      = false;
            $wp_user    = new WP_User( $email_order->user_id );
            $user_id    = $email_order->user_id;
            $email_to   = $wp_user->user_email;
        }

        $email  = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}followup_emails` WHERE `id` = '%d'", $email_order->email_id) );

        $qstring    = base64_encode('oid='. $email_order->id .'&eid='. $email->id .'&user_email='. $email_to .'&user_id='. $user_id);
        $px_url     = add_query_arg('fuepx', 1, add_query_arg('data', $qstring, site_url()));
        $message    .= '<img src="'. $px_url .'" height="1" width="1" />';

        return $message;
    }

    public static function email_log($id, $email_order_id, $user_id, $name, $cname, $mail_to, $order_id, $product_id, $trigger = '') {
        global $wpdb;

        $log = array(
            'email_id'      => $id,
            'email_order_id'=> $email_order_id,
            'user_id'       => $user_id,
            'email_name'    => $name,
            'customer_name' => $cname,
            'email_address' => $mail_to,
            'date_sent'     => current_time('mysql'),
            'order_id'      => $order_id,
            'product_id'    => $product_id,
            'email_trigger' => $trigger
        );
        $wpdb->insert( $wpdb->prefix .'followup_email_logs', $log );
    }

    static function settings_main() {
        $tab = (isset($_GET['tab'])) ? $_GET['tab'] : 'reports';

        self::reports_header($tab);
        if ($tab == 'reports') {
            FUE_Reports::reports_html();
        } elseif ($tab == 'reportview') {
            FUE_Reports::reportview_html();
        } elseif ($tab == 'reportuser_view') {
            echo FUE_Reports::user_view_html();
        } elseif ($tab == 'emailopen_view') {
            echo FUE_Reports::email_open_html($_GET['eid'], $_GET['ename']);
        } elseif ($tab == 'linkclick_view') {
            echo FUE_Reports::link_click_html($_GET['eid'], $_GET['ename']);
        } elseif ($tab == 'dbg_queue') {
            FUE_Reports::queue();
        }

        self::reports_footer();

    }

    static function reports_html() {
        global $wpdb;

        $email_reports  = FUE_Reports::get_reports(array('type' => 'emails'));
        $user_reports   = FUE_Reports::get_reports(array('type' => 'users'));
        $exclude_reports= FUE_Reports::get_reports(array('type' => 'excludes'));

        $emails_block   = '';
        $users_block    = '';

        include FUE_TEMPLATES_DIR .'/reports/overview.php';
        return;

    }

    public static function reportview_html() {
        $id         = urldecode($_GET['eid']);
        $reports    = FUE_Reports::get_reports(array('id' => $id, 'type' => 'emails'));

        include FUE_TEMPLATES_DIR .'/reports/report_view.php';
        return;
    }

    public static function user_view_html() {
        global $wpdb;

        $email      = $_GET['email'];
        $reports    = FUE_Reports::get_reports(array('email' => $email, 'type' => 'users'));

        $user_id    = 0;
        $fue_user   = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_customers WHERE email_address = %s", $email) );

        if ( $fue_user ) {
            $user_id = $fue_user->user_id;
        }

        $queue = $wpdb->get_results( $wpdb->prepare("SELECT DISTINCT * FROM {$wpdb->prefix}followup_email_orders WHERE is_sent = 0 AND user_email = %s ORDER BY send_on ASC", $email) );

        include FUE_TEMPLATES_DIR .'/reports/user_view.php';
        return;
    }

    public static function email_open_html($id, $name) {
        global $wpdb;

        $reports = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_email_tracking WHERE `event_type` = 'open' AND `email_id` = %d ORDER BY `date_added` DESC", $id) );

        include FUE_TEMPLATES_DIR .'/reports/email_open.php';
        return;
    }

    public static function link_click_html($id, $name) {
        global $wpdb;

        $reports = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_email_tracking WHERE `event_type` = 'click' AND `email_id` = %d ORDER BY `date_added` DESC", $id) );

        include FUE_TEMPLATES_DIR .'/reports/link_click.php';
        return;
    }

    public static function send_summary() {
        global $wpdb;

        $last_send      = get_option('fue_last_summary', 0);
        $next_send      = get_option('fue_next_summary', 0);
        $now            = current_time('timestamp');
        $reports        = '';

        if ( $now < $next_send ) return;

        $sfn_reports = $wpdb->get_results( $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}followup_email_orders` WHERE `is_sent` = 1 AND `date_sent` >= %s", date('Y-m-d H:i:s', $last_send)) );

        if ( empty($sfn_reports) ) return;

        foreach ( $sfn_reports as $report ) {
            $product_str    = 'n/a';
            $order_str      = 'n/a';
            $coupon_str     = '-';
            $order          = false;
            $email          = $wpdb->get_row("SELECT * FROM `{$wpdb->prefix}followup_emails` WHERE `id` = {$report->email_id}");

            if ( $report->product_id != 0 ) {
                $product_str = '<a href="'. get_permalink($report->product_id) .'">'. get_the_title($report->product_id) .'</a>';
            }

            if (! empty($report->coupon_name) && ! empty($report->coupon_code )) {
                $coupon_str = $report->coupon_name .' ('. $report->coupon_code .')';
            }

            $email_address = '';
            if ( $email->email_type == 'manual' ) {
                $meta = maybe_unserialize( $report->meta );
                $email_address = $meta['email_address'];
            } else {
                $user = new WP_User( $report->user_id );
                $email_address = $user->user_email;
            }

            $email_address  = apply_filters( 'fue_report_email_address', $email_address, $report );
            $order_str      = apply_filters( 'fue_report_order_str', '', $report );

            $reports .= '
            <tr>
                <td style="font-size: 11px; text-align:left; vertical-align:middle; border: 1px solid #eee;">'. $email->name .'</td>
                <td style="font-size: 11px; text-align:left; vertical-align:middle; border: 1px solid #eee;">'. $email_address .'</td>
                <td style="font-size: 11px; text-align:left; vertical-align:middle; border: 1px solid #eee;">'. $product_str .'</td>
                <td style="font-size: 11px; text-align:left; vertical-align:middle; border: 1px solid #eee;">'. $order_str .'</td>
                <td style="font-size: 11px; text-align:left; vertical-align:middle; border: 1px solid #eee;">'. $report->email_trigger.'</td>
                <td style="font-size: 11px; text-align:left; vertical-align:middle; border: 1px solid #eee;">'. $coupon_str .'</td>
            </tr>';
        }

        $body       = '<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;" border="1" bordercolor="#eee">
	<thead>
		<tr>
			<th scope="col" style="text-align:left; border: 1px solid #eee;">'. __('Email Name', 'follow_up_emails') .'</th>
			<th scope="col" style="text-align:left; border: 1px solid #eee;">'. __('Email Address', 'follow_up_emails') .'</th>
			<th scope="col" style="text-align:left; border: 1px solid #eee;">'. __('Product', 'follow_up_emails') .'</th>
			<th scope="col" style="text-align:left; border: 1px solid #eee;">'. __('Order', 'follow_up_emails') .'</th>
            <th scope="col" style="text-align:left; border: 1px solid #eee;">'. __('Trigger', 'follow_up_emails') .'</th>
			<th scope="col" style="text-align:left; border: 1px solid #eee;">'. __('Sent Coupon', 'follow_up_emails') .'</th>
		</tr>
	</thead>
	<tbody>
		'. $reports .'
	</tbody>
</table>';

        // send the email
        $subject    = __('Follow-up emails summary', 'follow_up_emails');
        $recipient  = get_option('fue_daily_emails', false);

        if (! $recipient) {
            $recipient = get_bloginfo('admin_email');
        }

        FUE::mail($recipient, $subject, $body);

        update_option( 'fue_last_summary', current_time( 'timestamp' ) );
        update_option( 'fue_next_summary', current_time( 'timestamp' ) + 86400 );
    }

    public static function get_reports( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'id'    => '',
            'email' => '',
            'type'  => 'emails',
            'sort'  => array()
        );
        $args = array_merge($defaults, $args);

        if ( $args['type'] == 'emails' ) {
            if ( empty($args['id']) ) {
                return $wpdb->get_results( "SELECT email_id, email_name, email_trigger, COUNT( email_name ) AS sent FROM  `{$wpdb->prefix}followup_email_logs` GROUP BY email_id ORDER BY date_sent DESC" );
            } else {
                return $wpdb->get_results( $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}followup_email_logs` WHERE `email_id` = %d ORDER BY `date_sent` DESC", $args['id']) );
            }
        } elseif ( $args['type'] == 'users' ) {
            $sortby = 'date_sent';
            $sort   = 'desc';

            if ( !empty($args['sort']) ) {
                $sortby = $args['sort']['sortby'];
                $sort   = $args['sort']['sort'];
            }

            if ( empty($args['email']) ) {
                return $wpdb->get_results( "SELECT customer_name, email_address, user_id FROM `{$wpdb->prefix}followup_email_logs` GROUP BY email_address ORDER BY $sortby $sort" );
            } else {
                return $wpdb->get_results( $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}followup_email_logs` WHERE `email_address` = %s ORDER BY $sortby $sort", $args['email']) );
            }
        } elseif ( $args['type'] == 'coupons' ) {
            $sortby = 'date_sent';
            $sort   = 'desc';
            if ( !empty($args['sort']) ) {
                $sortby = $args['sort']['sortby'];
                $sort   = $args['sort']['sort'];
            }

            if ( empty($args['id']) ) {
                return $wpdb->get_results( "SELECT * FROM  `{$wpdb->prefix}followup_coupon_logs` ORDER BY $sortby $sort" );
            } else {
                return $wpdb->get_results( $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}followup_coupon_logs` WHERE `coupon_id` = %d ORDER BY $sortby $sort", $args['id']) );
            }
        } elseif ( $args['type'] == 'excludes' ) {
            if ( empty($args['id']) ) {
                return $wpdb->get_results( "SELECT * FROM `{$wpdb->prefix}followup_email_excludes` ORDER BY `date_added` DESC" );
            } else {
                return $wpdb->get_results( $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}followup_email_excludes` WHERE `email_id` = %d ORDER BY `date_added` DESC", $args['id']) );
            }
        }
    }

    public static function queue() {
        global $wpdb;

        $items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}followup_email_orders ORDER BY id DESC", ARRAY_A);
        $cron_time = wp_next_scheduled( 'sfn_followup_emails' );

        ?>
        <p>
            <code>Server Time: <?php echo date('F d, Y h:i:s A', current_time('timestamp')); ?></code>
        </p>

        <p>
            <?php if ( false === $cron_time ): ?>
                <code>CRONJOB is not installed!</code>
            <?php else: ?>
                <code>Next CRON run: <?php echo date( 'F d, Y h:i:s A', $cron_time ); ?></code>
            <?php endif; ?>
        </p>

        <?php if (! $items ): ?>
            <p><?php _e('No items in the queue', 'follow_up_emails'); ?></p>
        <?php
        else:
            $heading = array_keys($items[0]);
            ?>
            <table class="wp-list-table widefat fixed posts">
                <thead>
                <tr>
                    <?php
                    foreach ( $heading as $key ):
                        $label = ($key == 'date_sent') ? 'date_scheduled' : $key;
                        ?>
                        <th scope="col" id="<?php echo $key; ?>" class="manage-column column-<?php echo $key; ?>" style=""><?php echo $label; ?></th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody id="the_list">
                <?php foreach ( $items as $item ): ?>
                    <tr>
                        <?php
                        foreach ($heading as $key):
                            $value = $item[$key];

                            if ( $key == 'send_on' ) $value = date('F d h:i a', $item[$key]);

                            if ( $key == 'meta' && !empty($value) ) {
                                $meta = maybe_unserialize( $value );
                                $value = '';
                                if ( $meta) foreach ( $meta as $meta_key => $meta_value ) {
                                    if ( is_array($meta_value) ) {
                                        $value .= '<b>'. $meta_key .'</b>: <pre>'. print_r($meta_value, true).'</pre><br/>';
                                    } else {
                                        $value .= '<b>'. $meta_key .'</b>: '. $meta_value.'<br/>';
                                    }

                                }
                            }
                            ?>
                            <td><?php echo $value; ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    <?php
    }

    public static function reports_header($tab) {
        ?>
        <div class="wrap">
        <div class="icon32"><img src="<?php echo FUE_TEMPLATES_URL .'/images/send_mail.png'; ?>" /></div>
        <h2>
        <?php _e('Follow-Up Emails &raquo; Email Reports', 'follow_up_emails'); ?>
        </h2><?php

        if ( isset($_GET['cleared']) ) {
            echo '<div class="updated"><p>'. __('The selected reports have been deleted', 'follow_up_emails') .'</p></div>';
        }
    }

    public static function reports_footer() {
        ?>
        </div>
    <?php
    }

    public static function reset($data) {
        global $wpdb;

        if ( $data['type'] == 'emails' && $data['emails_action'] == 'trash' ) {
            $email_ids_str = implode(',', $data['email_id']);

            $wpdb->query("UPDATE {$wpdb->prefix}followup_emails SET usage_count = 0 WHERE id IN ($email_ids_str)");
            $wpdb->query("DELETE FROM {$wpdb->prefix}followup_email_logs WHERE email_id IN ($email_ids_str)");
            $wpdb->query("DELETE FROM {$wpdb->prefix}followup_email_tracking WHERE email_id IN ($email_ids_str)");
        } elseif ( $data['type'] == 'users' && $data['users_action'] == 'trash' ) {
            $emails_str = '';

            foreach ( $data['user_email'] as $email ) {
                $emails_str .= "'$email',";
            }
            $emails_str = rtrim($emails_str, ',');

            $wpdb->query("DELETE FROM {$wpdb->prefix}followup_email_logs WHERE email_address IN ($emails_str)");
        }



        //$wpdb->query("DELETE FROM {$wpdb->prefix}followup_coupon_logs");


        return true;
    }

    public function add_profile_link( $user ) {
        if ( current_user_can( 'manage_follow_up_emails' ) ) {

            ?>
            <h3><?php _e( 'Follow-Up Emails' ); ?></h3>

            <p><a class="button" href="admin.php?page=followup-emails-reports&tab=reportuser_view&email=<?php echo urlencode($user->user_email); ?>"><?php _e('User Follow-Up Emails Report', 'follow_up_emails'); ?></a></p>
        <?php
        }
    }

}

if ( FollowUpEmails::$scheduling_system == 'action-scheduler' ) {
    if (! wc_next_scheduled_action( 'fue_send_summary' ) ) {
        wc_schedule_recurring_action( time(), 900, 'fue_send_summary', array(), 'fue' );
    }
} else {
    if (! wp_next_scheduled( 'fue_send_summary' ) ) {
        wp_schedule_event( current_time('timestamp'), 'everyquarter', 'fue_send_summary' );
    }

}
add_action('fue_send_summary', array('FUE_Reports', 'send_summary'));

$GLOBALS['fue_reports'] = new FUE_Reports();
