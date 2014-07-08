<div class="fue-unsubscribe-form">
    <form action="" method="post">
        <input type="hidden" name="fue_action" value="fue_unsubscribe" />
        <input type="hidden" name="fue_eid" value="<?php echo esc_attr($eid); ?>" />
        <p>
            <label for="fue_email"><?php _e('Email Address:', 'follow_up_emails'); ?></label>
            <input type="text" id="fue_email" name="fue_email" value="<?php echo esc_attr($email); ?>" size="25" />
        </p>
        <?php do_action('fue_unsubscribe_form', $email); ?>
        <p>
            <input type="submit" name="fue_submit" value="<?php _e('Unsubscribe', 'follow_up_emails'); ?>" />
        </p>
    </form>
</div>
