<style type="text/css">
    .red-pill {
        font-size: 10px;
        font-family: Verdana, Tahoma, Arial;
        font-weight: bold;
        display: inline-block;
        margin-left: 5px;
        background: #f00;
        color: #fff;
        padding: 0px 8px;
        border-radius: 20px;
        vertical-align: super;
    }
</style>
<form action="admin-post.php" method="post" enctype="multipart/form-data">

    <h3><?php _e('Backup &amp; Restore', 'follow_up_emails'); ?></h3>

    <table class="form-table">
        <tbody>
        <tr valign="top">
            <td colspan="2">
                <strong><?php _e('Download Backup of', 'follow_up_emails'); ?></strong>
                <br/>
                <a class="button" href="<?php echo wp_nonce_url('admin-post.php?action=fue_backup_emails', 'fue_backup'); ?>"><?php _e('Follow-Up Emails', 'follow_up_emails'); ?></a>
                <a class="button" href="<?php echo wp_nonce_url('admin-post.php?action=fue_backup_settings', 'fue_backup'); ?>"><?php _e('Settings', 'follow_up_emails'); ?></a>
            </td>
        </tr>
        <tr valign="top">
            <td colspan="2">
                <strong><?php _e('Restore Backup of', 'follow_up_emails'); ?></strong>
                <table class="form-table">
                    <tbody>
                    <tr valign="top">
                        <th><label for="restore_emails"><?php _e('Follow-up Emails', 'follow_up_emails'); ?></label></th>
                        <td><input type="file" name="emails_file" /></td>
                    </tr>
                    <tr valign="top">
                        <th><label for="restore_settings"><?php _e('Settings', 'follow_up_emails'); ?></label></th>
                        <td><input type="file" name="settings_file" /></td>
                    </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        </tbody>
    </table>

	<!-- Future location of reporting data improvement settings -->

    <h3>
        <?php _e('Scheduling System', 'follow_up_emails'); ?>
        <span class="red-pill show-scheduler-option" style="cursor: pointer;"><?php _e('experimental', 'follow_up_emails'); ?></span>
    </h3>

    <div class="scheduler-block" style="display: none;">
        <p><?php _e('Follow-Up Emails uses WP-Cron, but the Action Scheduler shows potential to greatly improve scheduling of tasks.', 'follow_up_emails'); ?></p>

        <p>Support for the new <a href="https://github.com/flightless/action-scheduler">Action Scheduler</a> system is now available, but is considered extremely experimental. If you want to try it out, please <a href="http://wordpress.org/plugins/wp-db-backup/">create a backup of your database</a> first. We believe that the Action Scheduler will provide much better performance, and ensure better stability, but is considered extremely experimental. We are confident in its ability, but are hesitant to guarantee its performance.</p>

        <p>
            <b><?php _e('Current Scheduler:', 'follow_up_emails'); ?></b>
            <?php echo (FollowUpEmails::$scheduling_system == 'action-scheduler') ? 'Action Scheduler' : 'WP-Cron'; ?>
            <a href="admin.php?page=followup-emails&tab=scheduler"><?php _e('Change', 'follow_up_emails'); ?></a>
        </p>
    </div>

    <?php do_action( 'fue_settings_system' ); ?>

    <p class="submit">
        <input type="hidden" name="action" value="sfn_followup_save_settings" />
        <input type="hidden" name="section" value="<?php echo $tab; ?>" />
        <input type="submit" name="save" value="<?php _e('Save Settings', 'follow_up_emails'); ?>" class="button-primary" />
    </p>

</form>
<?php
$js = '
$(".show-scheduler-option").click(function(e) {
    e.preventDefault();

    $(".scheduler-block").show();
});

$(".toggle-feedback").click(function(e) {
    e.preventDefault();

    $(".feedback_row").slideToggle();
});

$(".feedback_cancel").click(function(e) {
    e.preventDefault();

    $(".feedback_row").slideUp();
});

$("#send_feedback").click(function() {
    var msg = $("#feedback").val();
    var that = this;

    $(this).attr("disabled", true);

    if ( !msg )
        return false;

    var postdata = {
        action: "fue_send_feedback",
        feedback: msg
    }
    $.post(ajaxurl, postdata, function(resp) {
        alert("'. __('You feedback has been sent.', 'follow_up_emails') .'");
        $(that).removeAttr("disabled");
        $(".feedback_row").slideUp();
    });
});
';

if ( function_exists('wc_enqueue_js') ) {
    wc_enqueue_js( $js );
} else {
    $woocommerce->add_inline_js( $js );
}