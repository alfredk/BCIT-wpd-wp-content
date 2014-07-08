<div class="wrap email-form">
    <div class="icon32"><img src="<?php echo FUE_TEMPLATES_URL .'/images/send_mail.png'; ?>" /></div>
    <h2>
        <?php
        if ( empty($defaults['id']) ) {
            _e('Follow-Up Emails &raquo; Create a New Email', 'follow_up_emails');
        } else {
            _e('Follow-Up Emails &raquo; Update Email', 'follow_up_emails');
        }
        ?>
    </h2>

    <h2 class="subtitle">
        <?php _e('Step One: What kind of email do you want to send?', 'follow_up_emails'); ?>
    </h2>

    <div id="progress">
        <div class="complete help_tip" title="33%" style="width: 33%; float: none;">&nbsp;</div>
    </div>

    <div id="fue_error_message" style="display: none;" class="error"><p><?php _e('Please complete the required fields below before continuing.', 'follow_up_emails'); ?></p></div>

    <form action="admin-post.php" method="post" id="fue_form">

        <div class="field">
            <label for="email_name"><?php _e('Name your email', 'follow_up_emails'); ?></label>
            <input type="text" name="name" id="name" value="<?php echo esc_attr($defaults['name']); ?>" class="regular-text" />
        </div>

        <div class="field">
            <ul class="email-types-list">
            <?php
            $types = FollowUpEmails::get_email_types();

            foreach ( $types as $key => $value ) {
                $desc   = FollowUpEmails::get_email_type_short_description($key);
                $tip    = ($desc) ? '<div id="email_type_'. $key .'_desc" class="email-description">'. $desc .'</div>' : '';
                $chk    = ($defaults['type'] == $key) ? 'checked' : '';
                echo '<li>
                        <label>
                            <input type="radio" name="email_type" value="'. $key .'" class="email-type" data-key="'. $key .'" id="email_type_'. $key .'" '. $chk .' /> '. $value .'
                            '. $tip .'
                        </label>
                    </li>';
            }
            ?>
            </ul>
        </div>

        <div class="field">
            <input type="hidden" name="action" value="sfn_followup_form" />
            <input type="hidden" name="id" value="<?php echo $defaults['id']; ?>" />
            <input type="hidden" name="step" value="1" />
            <input type="hidden" name="new" value="<?php echo (isset($_GET['new'])) ? 1 : 0; ?>" />
            <?php if ( empty($defaults['id']) ): ?>
            <input type="submit" class="button-primary" name="submit" value="<?php _e('Continue to Step 2', 'follow_up_emails'); ?>" />
            <?php else: ?>
            <input type="submit" class="button-primary" name="submit" value="<?php _e('Continue to Step 2', 'follow_up_emails'); ?>" />
            <?php endif; ?>
        </div>

    </form>
</div>
<script type="text/javascript">
var interval_types = <?php echo json_encode(FollowUpEmails::get_trigger_types()); ?>;
var email_intervals = <?php echo json_encode(FollowUpEmails::get_email_type_triggers()); ?>;
jQuery(document).ready(function() {
    jQuery("#fue_form").submit(function() {
        jQuery(".fue-error").removeClass('fue-error');
        jQuery("#fue_error_message").hide();

        if ( jQuery("#name").val().length == 0 ) {
            jQuery("#name").parents(".field").addClass("fue-error");
            jQuery("#fue_error_message").show();
            return false;
        }
    });

    jQuery(".email-description:visible").hide();

    jQuery(".email-type").change(function() {
        if ( jQuery(".email-description:visible").length == 1 ) {
            jQuery(".email-description:visible").slideUp("fast", function() {
                var el = jQuery(".email-type:checked");
                jQuery("#email_type_"+ jQuery(el).data("key") +"_desc").slideDown();
            });
        } else {
            var el = jQuery(".email-type:checked");
            jQuery("#email_type_"+ jQuery(el).data("key") +"_desc").slideDown();
        }
    }).change();
});
</script>
