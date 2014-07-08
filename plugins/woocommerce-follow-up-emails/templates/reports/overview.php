<style>
a.reset { color: red; }

/* TipTip CSS - Version 1.2 */
#tiptip_holder {
    display: none;
    position: absolute;
    top: 0;
    left: 0;
    z-index: 99999;
}
#tiptip_holder.tip_top {
    padding-bottom: 5px;
}
#tiptip_holder.tip_bottom {
    padding-top: 5px;
}
#tiptip_holder.tip_right {
    padding-left: 5px;
}
#tiptip_holder.tip_left {
    padding-right: 5px;
}
#tiptip_content {
    font-size: 11px;
    color: #fff;
    padding: 4px 8px;
    background:#a2678c;
    border-radius: 3px;
    -webkit-border-radius: 3px;
    -moz-border-radius: 3px;
    box-shadow: 1px 1px 3px rgba(0,0,0,0.10);
    -webkit-box-shadow: 1px 1px 3px rgba(0,0,0,0.10);
    -moz-box-shadow: 1px 1px 3px rgba(0,0,0,0.10);
    text-align: center;
    code {
        background: #855c76;
        padding: 1px;
    }
}
#tiptip_arrow, #tiptip_arrow_inner {
    position: absolute;
    border-color: transparent;
    border-style: solid;
    border-width: 6px;
    height: 0;
    width: 0;
}
#tiptip_holder.tip_top #tiptip_arrow_inner {
    margin-top: -7px;
    margin-left: -6px;
    border-top-color: #a2678c;
}

#tiptip_holder.tip_bottom #tiptip_arrow_inner {
    margin-top: -5px;
    margin-left: -6px;
    border-bottom-color: #a2678c;
}

#tiptip_holder.tip_right #tiptip_arrow_inner {
    margin-top: -6px;
    margin-left: -5px;
    border-right-color: #a2678c;
}

#tiptip_holder.tip_left #tiptip_arrow_inner {
    margin-top: -6px;
    margin-left: -7px;
    border-left-color: #a2678c;
}
</style>
<div class="subsubsub_section">
    <ul class="subsubsub">
        <li><a href="#emails" class="current"><?php _e('Emails', 'follow_up_emails'); ?></a> | </li>
        <li><a href="#users"><?php _e('Users', 'follow_up_emails'); ?></a> | </li>
        <li><a href="#excludes"><?php _e('Opt-Outs', 'follow_up_emails'); ?></a></li>
        <?php do_action( 'fue_reports_section_list' ); ?>
    </ul>
    <br class="clear">

    <div class="section" id="emails">
        <h3><?php _e('Emails', 'follow_up_emails'); ?></h3>
        <form action="admin-post.php" method="post">
            <table class="wp-list-table widefat fixed posts">
                <thead>
                    <tr>
                        <th scope="col" id="cb" class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                            <input id="cb-select-all-1" type="checkbox">
                        </th>
                        <th scope="col" id="type" class="manage-column column-type" style=""><?php _e('Email Name', 'follow_up_emails'); ?></th>
                        <th scope="col" id="usage_count" class="manage-column column-usage_count" style=""><?php _e('Emails Sent', 'follow_up_emails'); ?> <img class="help_tip" width="16" height="16" title="<?php _e('The number of individual emails sent using this follow-up email', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" /></th>
                        <th scope="col" id="opened" class="manage-column column-usage_count" style=""><?php _e('Emails Opened', 'follow_up_emails'); ?> <img class="help_tip" width="16" height="16" title="<?php _e('The number of times the this specific follow-up emails has been opened', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" /></th>
                        <th scope="col" id="clicked" class="manage-column column-usage_count" style=""><?php _e('Links Clicked', 'follow_up_emails'); ?> <img class="help_tip" width="16" height="16" title="<?php _e('The number of times links in this follow-up email have been clicked', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" /></th>
                    </tr>
                </thead>
                <tbody id="the_list">
                    <?php
                    if (empty($email_reports)) {
                        ?>
                        <tr scope="row">
                            <th colspan="5"><?php _e('No reports available', 'follow_up_emails'); ?></th>
                        </tr><?php
                    } else {
                        foreach ($email_reports as $report) {
                            $opened     = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->prefix}followup_email_tracking` WHERE `email_id` = %d AND `event_type` = 'open'", $report->email_id) );
                            $clicked    = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->prefix}followup_email_tracking` WHERE `email_id` = %d AND `event_type` = 'click'", $report->email_id) );
                            $meta       = '';

                            $email_row = $wpdb->get_row( $wpdb->prepare("SELECT interval_type, meta FROM {$wpdb->prefix}followup_emails WHERE id = %d", $report->email_id) );

                            ?><tr scope="row">
                                <th scope="row" class="check-column">
                                    <input id="cb-select-106" type="checkbox" name="email_id[]" value="<?php echo $report->email_id; ?>">
                                    <div class="locked-indicator"></div>
                                </th>
                                <td class="post-title column-title">
                                    <strong><?php echo stripslashes($report->email_name); ?></strong>
                                    <em><?php echo apply_filters( 'fue_report_email_trigger', $report->email_trigger, $email_row ); ?></em><br/>
                                    <a href="admin.php?page=followup-emails-reports&tab=reportview&eid=<?php echo urlencode($report->email_id); ?>"><?php _e('View Report', 'follow_up_emails'); ?></a>
                                </td>
                                <td><a class="row-title" href="admin.php?page=followup-emails-reports&tab=reportview&eid=<?php echo urlencode($report->email_id); ?>"><?php echo $report->sent; ?></a></td>
                                <td><a class="row-title" href="admin.php?page=followup-emails-reports&tab=emailopen_view&eid=<?php echo urlencode($report->email_id); ?>&ename=<?php echo urlencode($report->email_name); ?>"><?php echo $opened; ?></a></td>
                                <td><a class="row-title" href="admin.php?page=followup-emails-reports&tab=linkclick_view&eid=<?php echo urlencode($report->email_id); ?>&ename=<?php echo urlencode($report->email_name); ?>"><?php echo $clicked; ?></a></td>
                            </tr><?php
                        }
                    }
                    ?>
                </tbody>
            </table>
            <div class="tablenav bottom">
                <div class="alignleft actions bulkactions">
                    <input type="hidden" name="action" value="fue_reset_reports" />
                    <input type="hidden" name="type" value="emails" />
                    <select name="emails_action">
                        <option value="-1" selected="selected"><?php _e('Bulk Actions', 'wordpress'); ?></option>
                        <option value="trash"><?php _e('Delete Selected', 'follow_up_emails'); ?></option>
                    </select>
                    <input type="submit" name="" id="doaction2" class="button action" value="Apply">
                </div>
            </div>
        </form>
    </div>
    <div class="section" id="users">
        <h3><?php _e('Users', 'follow_up_emails'); ?></h3>

        <form action="admin-post.php" method="post">
            <table class="wp-list-table widefat fixed posts">
                <thead>
                    <tr>
                        <th scope="col" id="cb_users" class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-2">Select All</label>
                            <input id="cb-select-all-2" type="checkbox">
                        </th>
                        <th scope="col" id="type" class="manage-column column-type" style=""><?php _e('Customer', 'follow_up_emails'); ?></th>
                        <th scope="col" id="usage_count" class="manage-column column-usage_count" style=""><?php _e('Emails Sent', 'follow_up_emails'); ?> <img class="help_tip" width="16" height="16" title="<?php _e('The number of individual emails sent using this follow-up email', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" /></th>
                        <th scope="col" id="opened" class="manage-column column-usage_count" style=""><?php _e('Emails Opened', 'follow_up_emails'); ?> <img class="help_tip" width="16" height="16" title="<?php _e('The number of times the this specific follow-up emails has been opened', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" /></th>
                        <th scope="col" id="clicked" class="manage-column column-usage_count" style=""><?php _e('Links Clicked', 'follow_up_emails'); ?> <img class="help_tip" width="16" height="16" title="<?php _e('The number of times links in this follow-up email have been clicked', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" /></th>
                    </tr>
                </thead>
                <tbody id="the_list">
                    <?php
                    if (empty($user_reports)) {
                        ?><tr scope="row">
                            <th colspan="5"><?php _e('No reports available', 'follow_up_emails'); ?></th>
                        </tr><?php
                    } else {
                        foreach ($user_reports as $report) {
                            if ( empty($report->email_address) ) continue;

                            $name       = $report->customer_name;
                            $email_key  = sanitize_title_with_dashes( $report->email_address );
                            $sent       = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->prefix}followup_email_logs` WHERE `email_address` = %s", $report->email_address) );
                            $opened     = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->prefix}followup_email_tracking` WHERE `user_email` = %s AND `event_type` = 'open'", $report->email_address) );
                            $clicked    = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->prefix}followup_email_tracking` WHERE `user_email` = %s AND `event_type` = 'click'", $report->email_address) );

                            if ( $report->user_id != 0 ) {
                                $wp_user = new WP_User($report->user_id);
                                $name = $wp_user->first_name .' '. $wp_user->last_name;
                            }

                            ?><tr scope="row">
                                <th scope="row" class="check-column">
                                    <input id="cb-select-<?php echo $email_key; ?>" type="checkbox" name="user_email[]" value="<?php echo $report->email_address; ?>">
                                    <div class="locked-indicator"></div>
                                </th>
                                <td class="post-title column-title">
                                    <strong><?php echo apply_filters( 'fue_report_customer_name', $name, $report ); ?></strong>
                                    <a href="admin.php?page=followup-emails-reports&tab=reportuser_view&email=<?php echo urlencode($report->email_address); ?>"><?php _e('View Report'); ?></a>
                                </td>
                                <td><?php echo esc_html($sent); ?></td>
                                <td><?php echo esc_html($opened); ?></td>
                                <td><?php echo esc_html($clicked) ?></td>
                            </tr><?php
                        }
                    }
                    ?>
                </tbody>
            </table>
            <div class="tablenav bottom">
                <div class="alignleft actions bulkactions">
                    <input type="hidden" name="action" value="fue_reset_reports" />
                    <input type="hidden" name="type" value="users" />
                    <select name="users_action">
                        <option value="-1" selected="selected"><?php _e('Bulk Actions', 'wordpress'); ?></option>
                        <option value="trash"><?php _e('Delete Selected', 'follow_up_emails'); ?></option>
                    </select>
                    <input type="submit" name="" id="doaction2" class="button action" value="Apply">
                </div>
            </div>
        </form>
    </div>

    <div class="section" id="excludes">
        <h3><?php _e('Opt-Outs', 'follow_up_emails'); ?></h3>
        <table class="wp-list-table widefat fixed posts">
            <thead>
                <tr>
                    <th scope="col" id="coupon_name" class="manage-column column-type" style=""><?php _e('Email Name', 'follow_up_emails'); ?> <img class="help_tip" width="16" height="16" title="<?php _e('The name of the follow-up email that a customer has opted out of', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" /></th>
                    <th scope="col" id="coupon_name" class="manage-column column-type" style=""><?php _e('Email Address', 'follow_up_emails'); ?> <img class="help_tip" width="16" height="16" title="<?php _e('The email address of the customer that opted out', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" /></th>
                    <th scope="col" id="coupon_name" class="manage-column column-type" style=""><?php _e('Date', 'follow_up_emails'); ?> <img class="help_tip" width="16" height="16" title="<?php _e('The date and time that the email address was opted out this follow-up email', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" /></th>
                </tr>
            </thead>
            <tbody id="the_list">
                <?php
                if (empty($exclude_reports)) {
                    ?><tr scope="row">
                        <th colspan="3"><?php _e('No reports available', 'follow_up_emails'); ?></th>
                    </tr><?php
                } else {
                    $excludes_block = '';
                    foreach ($exclude_reports as $report) {
                        echo '
                        <tr scope="row">
                            <td class="post-title column-title">
                                <strong>'. stripslashes($report->email_name) .'</strong>
                            </td>
                            <td>'. esc_html($report->email) .'</td>
                            <td>'. date( get_option('date_format') .' '. get_option('time_format') , strtotime($report->date_added)) .'</td>
                        </tr>
                        ';
                    }
                }
                ?>
            </tbody>
        </table>
    </div>

    <?php do_action( 'fue_reports_section_div' ); ?>
</div>
<script type="text/javascript">
jQuery(window).load(function(){
    jQuery(".help_tip").tipTip();
    // Subsubsub tabs
    jQuery('div.subsubsub_section ul.subsubsub li a:eq(0)').addClass('current');
    jQuery('div.subsubsub_section .section:gt(0)').hide();

    jQuery('div.subsubsub_section ul.subsubsub li a').click(function(){
        var $clicked = jQuery(this);
        var $section = $clicked.closest('.subsubsub_section');
        var $target  = $clicked.attr('href');

        $section.find('a').removeClass('current');

        if ( $section.find('.section:visible').size() > 0 ) {
            $section.find('.section:visible').fadeOut( 100, function() {
                $section.find( $target ).fadeIn('fast');
            });
        } else {
            $section.find( $target ).fadeIn('fast');
        }

        $clicked.addClass('current');
        jQuery('#last_tab').val( $target );

        return false;
    });
});
</script>
