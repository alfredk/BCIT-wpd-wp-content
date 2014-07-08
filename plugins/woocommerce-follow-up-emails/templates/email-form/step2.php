<div class="wrap email-form">
    <div class="icon32"><img src="<?php echo FUE_TEMPLATES_URL .'/images/send_mail.png'; ?>" /></div>
    <h2>
        <?php
        if ( empty($defaults['id']) || (isset($_GET['new']) && $_GET['new'] == 1) ) {
            _e('Follow-Up Emails &raquo; Create a New Email', 'follow_up_emails');
        } else {
            _e('Follow-Up Emails &raquo; Update Email', 'follow_up_emails');
        }
        ?>
    </h2>

    <h2 class="subtitle">
        <?php _e('Step Two: Let\'s start creating the email!', 'follow_up_emails'); ?>
    </h2>

    <div id="progress">
        <div class="complete help_tip" title="66%" class="66%" style="width: 66%; float: none;">&nbsp;</div>
    </div>

    <div id="fue_error_message" style="display: none;" class="error"><p><?php _e('Please complete the required fields below before continuing.', 'follow_up_emails'); ?></p></div>

    <form action="admin-post.php" method="post" id="fue_form" class="fue-form-step2">

        <div class="field">
            <input type="hidden" name="always_send" id="always_send_off" value="0" />
            <input type="checkbox" name="always_send" id="always_send" value="1" <?php if ($defaults['always_send'] == 1) echo 'checked'; ?> />
            <label for="always_send" class="inline">
                <?php _e('Do you want this email to ALWAYS send?', 'follow_up_emails'); ?>
                <img class="help_tip" title="<?php _e('Use this setting carefully, as this setting could result in multiple emails being sent per order', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL .'/images/help.png'; ?>" width="16" height="16" style="float:none;" />
            </label>

            <br/>

            <input type="hidden" name="meta[one_time]" id="meta_one_time_off" value="no" />
            <input type="checkbox" name="meta[one_time]" id="meta_one_time" value="yes" <?php if (isset($defaults['meta']['one_time']) && $defaults['meta']['one_time'] == 'yes') echo 'checked'; ?> />
            <label for="meta_one_time" class="inline">
                <?php _e('Only send this email once per customer', 'follow_up_emails'); ?>
                <img class="help_tip" title="<?php _e('A customer will only receive this email once, even if purchased multiple times at different dates', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL .'/images/help.png'; ?>" width="16" height="16" style="float:none;" />
            </label>

            <br/>

            <input type="hidden" name="meta[adjust_date]" id="adjust_date_off" value="no" />
            <input type="checkbox" name="meta[adjust_date]" id="adjust_date" value="yes" <?php if (isset($defaults['meta']['adjust_date']) && $defaults['meta']['adjust_date'] == 'yes') echo 'checked'; ?> />
            <label for="adjust_date" class="inline">
                <?php _e('Adjust the send date of an email if the same email is already in the queue instead of sending it twice.', 'follow_up_emails'); ?>
                <img class="help_tip" title="<?php _e('Setting this will change the email date to further in the future if the customer already has the same email queued for a future send', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL .'/images/help.png'; ?>" width="16" height="16" style="float:none;" />
            </label>
        </div>

        <div class="field">
            <label for="email_subject"><?php _e('What is your subject line?', 'follow_up_emails'); ?></label>
            <input type="text" name="email_subject" id="email_subject" value="<?php echo esc_attr($defaults['subject']); ?>" class="regular-text" />
        </div>

        <div class="field">
            <label for="email_bcc">
                <?php _e('Send a copy of this email to this address (BCC)', 'follow_up_emails'); ?>
                <img class="help_tip" title="<?php _e('All these emails will be blind carbon copied to this address', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL .'/images/help.png'; ?>" width="16" height="16" style="float:none;" />
            </label>
            <input type="text" name="meta[bcc]" id="email_bcc" value="<?php echo (isset($defaults['meta']['bcc'])) ? esc_attr($defaults['meta']['bcc']) : ''; ?>" class="regular-text" />
        </div>

        <div class="field interval-field">
            <label><?php _e('When should the email be sent?', 'follow_up_emails'); ?></label>
            <div>
                <input type="hidden" name="email_type" id="email_type" value="<?php echo $defaults['type']; ?>" />
                <span class="hide-if-date interval_span hideable">
                    <input type="number" min="1" step="1" name="interval" id="interval" value="<?php echo esc_attr($defaults['interval']); ?>" size="2" style="vertical-align: top; width: 50px;" />
                </span>
                <select name="interval_duration" id="interval_duration" class="interval_duration hideable">
                    <?php
                    $durations = FollowUpEmails::get_durations();

                    foreach ( $durations as $key => $value ):
                        $selected = ($defaults['interval_duration'] == $key) ? 'selected' : '';
                    ?>
                    <option class="interval_duration_<?php echo $key; ?> hideable" value="<?php echo esc_attr($key); ?>" <?php echo $selected; ?>><?php echo $value; ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="description signup signup_description hideable"><?php _e('after user signs up', 'follow_up_emails'); ?></span>
                <span class="hide-if-date non-signup interval_type_span hideable">
                    &nbsp;
                    <select name="interval_type" id="interval_type" class="interval_type hideable">
                        <?php
                        $triggers = FollowUpEmails::get_trigger_types();

                        foreach ( $triggers as $key => $value ):
                            $selected = ($defaults['interval_type'] == $key) ? 'selected' : '';
                        ?>
                        <option class="interval_type_option interval_type_<?php echo $key; ?> <?php if ( $key != 'purchase' && $key != 'completed' ) echo 'non-reminder'; ?> hideable <?php do_action('fue_form_interval_type', $key); ?>" value="<?php echo esc_attr($key); ?>" <?php echo $selected; ?>><?php echo $value; ?></option>
                        <?php endforeach; ?>
                    </select>
                </span>
                <span class="show-if-date interval_date_span hideable">
                    <input type="text" name="send_date" class="date" value="<?php echo esc_attr($defaults['send_date']); ?>" readonly />

                    <select name="send_date_hour">
                        <option value=""><?php _e('Hour', 'follow_up_emails'); ?></option>
                        <?php
                        for ( $x = 1; $x <= 12; $x++ ):
                            $sel = ($defaults['send_date_hour'] == $x) ? 'selected' : '';
                        ?>
                        <option value="<?php echo $x; ?>" <?php echo $sel; ?>><?php echo $x; ?></option>
                        <?php endfor; ?>
                    </select>

                    <select name="send_date_minute">
                        <option value=""><?php _e('Minute', 'follow_up_emails'); ?></option>
                        <?php
                        for ( $x = 0; $x <= 55; $x+=5 ):
                            $sel = ($defaults['send_date_minute'] === $x) ? 'selected' : '';
                        ?>
                        <option value="<?php echo $x; ?>" <?php echo $sel; ?>><?php echo $x; ?></option>
                        <?php endfor; ?>
                    </select>

                    <select name="meta[send_date_ampm]">
                        <option value="am">AM</option>
                        <option value="pm">PM</option>
                    </select>
                </span>

                <?php do_action('fue_email_form_interval_meta', $defaults); ?>
            </div>
        </div>

        <?php do_action('fue_email_form_after_interval', $defaults); ?>

        <div class="field">
            <label for="tracking_on">
                <input type="checkbox" name="tracking_on" id="tracking_on" value="1" <?php if ($defaults['tracking_on'] == 1) echo 'checked'; ?> />
                <?php _e('Add Google Analytics tracking to links', 'follow_up_emails'); ?>
            </label>
        </div>

        <div class="field tracking_on">
            <label for="tracking"><?php _e('Link Tracking', 'follow_up_emails'); ?></label>
            <input type="text" name="tracking" id="tracking" value="<?php echo esc_attr($defaults['tracking']); ?>" placeholder="e.g. utm_campaign=Follow-up-Emails-by-75nineteen" size="40" />
            <p class="description">
                <?php _e('The value inserted here will be appended to all URLs in the Email Body', 'follow_up_emails'); ?>
            </p>
        </div>

        <div class="field">
            <a class="button-secondary" href="admin.php?page=followup-emails-form&step=1&id=<?php echo $_GET['id']; ?>"><?php _e('Back to Step 1', 'follow_up_emails'); ?></a>

            <input type="hidden" name="action" value="sfn_followup_form" />
            <input type="hidden" name="step" value="2" />
            <input type="hidden" name="new" value="<?php echo (isset($_GET['new'])) ? 1 : 0; ?>" />
            <input type="hidden" name="id" id="id" value="<?php echo $defaults['id']; ?>" />
            <input type="submit" class="button-primary" id="save" name="save" value="<?php _e('Continue to Step 3', 'follow_up_emails'); ?>" />
        </div>

    </form>
</div>
<script type="text/javascript">
var interval_types = <?php echo json_encode(FollowUpEmails::get_trigger_types()); ?>;
var email_intervals = <?php echo json_encode(FollowUpEmails::get_email_type_triggers()); ?>;
var sfn_checked = false;
jQuery(document).ready(function() {

    <?php do_action('fue_email_form_script', $defaults); ?>

    jQuery("#fue_form").submit(function(e) {
        var error = false;
        jQuery(".fue-error").removeClass('fue-error');
        jQuery("#fue_error_message").hide();

        <?php do_action('fue_email_form_submit_script', $defaults); ?>

        if ( jQuery("#email_subject").val().length == 0 ) {
            jQuery("#email_subject").parents(".field").addClass("fue-error");

            error = true;
        }

        if ( error ) {
            jQuery("#fue_error_message").show();
            return false;
        }

        if ( sfn_checked == false ) {
            jQuery("#save")
                .val(FUE.processing_request)
                .attr("disabled", true);

            var data = {
                'action'            : 'sfn_fe_find_dupes',
                'type'              : jQuery("#email_type").val(),
                'interval'          : jQuery("#interval").val(),
                'interval_duration' : jQuery("#interval_duration").val(),
                'interval_type'     : jQuery("#interval_type").val(),
                'product_id'        : (jQuery("#product_id").val() != null) ? jQuery("#product_id").val()[0] : '',
                'category_id'       : jQuery("#category_id").val(),
                'always_send'       : jQuery("#always_send").is(":checked") ? 1 : 0
            };

            if (jQuery("#id").length > 0) {
                data.id = jQuery("#id").val();
            }

            jQuery.post(ajaxurl, data, function(resp) {
                sfn_checked = true;
                jQuery(".sfn-error").remove();
                if (resp == "DUPE") {
                    if ( confirm(FUE.dupe) ) {
                        jQuery("#fue_form").submit();
                        return false;
                    } else {
                        // clicked cancel
                        jQuery("#save")
                            .val(FUE.save)
                            .attr("disabled", false)
                        return false;
                    }
                } else if (resp == "SIMILAR") {
                    if (confirm(FUE.similar)) {
                        sfn_checked = true;
                        jQuery("#fue_form").submit();
                    }
                } else {
                    sfn_checked = true;
                    jQuery("#fue_form").submit();
                }

                jQuery("#save")
                    .val(FUE.save)
                    .attr("disabled", false)
            });
            return false;
        }

        return true;
    });
});
</script>
