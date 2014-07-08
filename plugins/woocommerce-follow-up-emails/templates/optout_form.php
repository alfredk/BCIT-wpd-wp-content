<h2><?php _e('Email Notifications', 'follow_up_emails'); ?></h2>
<form action="" method="post" id="followup_emails_form">
    <input type="hidden" name="fue_action" value="fue_save_myaccount" />
    <label>
        <input type="checkbox" name="fue_opt_out" value="1" <?php if (get_user_meta($me->ID, 'fue_opted_out', true) == true) echo 'checked'; ?> />
        <?php _e('I wish to unsubscribe from non-purchase related emails', 'follow_up_emails'); ?>
    </label>
    <input type="submit" name="submit" value="Update" class="button-primary" />
</form>
