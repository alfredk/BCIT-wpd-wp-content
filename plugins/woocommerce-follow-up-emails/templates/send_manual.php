<?php
$defaults = apply_filters( 'fue_manual_email_defaults', array(
    'type'              => $email->email_type,
    'always_send'       => $email->always_send,
    'name'              => $email->name,
    'interval'          => $email->interval_num,
    'interval_duration' => $email->interval_duration,
    'interval_type'     => $email->interval_type,
    'send_date'         => $email->send_date,
    'send_date_hour'    => $email->send_date_hour,
    'send_date_minute'  => $email->send_date_minute,
    'product_id'        => $email->product_id,
    'category_id'       => $email->category_id,
    'subject'           => $email->subject,
    'message'           => $email->message,
    'tracking_on'       => (!empty($email->tracking_code)) ? 1 : 0,
    'tracking'          => $email->tracking_code
), $email);

// if type is date, switch columns
if ( $defaults['interval_type'] == 'date' ) {
    $defaults['interval_type'] = $defaults['interval_duration'];
    $defaults['interval_duration'] = 'date';
}

if ( isset($_POST) && !empty($_POST) ) {
    $defaults = array_merge( $defaults, $_POST );
}
?>
<div class="wrap email-form">
    <div class="icon32"><img src="<?php echo FUE_TEMPLATES_URL .'/images/send_mail.png'; ?>" /></div>
    <h2><?php _e('Follow-Up Emails &raquo; Send Manual Email', 'follow_up_emails'); ?></h2>

    <form action="admin-post.php" method="post" id="">
        <h3><?php printf(__('Send Email: %s', 'follow_up_emails'), $email->name); ?></h3>

        <table class="form-table">
            <tbody>
                <tr valign="top" class="send_type_tr">
                    <th scope="row" class="send_type_th">
                        <label for="send_type"><?php _e('Send Email To', 'follow_up_emails'); ?></label>
                    </th>
                    <td class="send_type_td">
                        <select name="send_type" id="send_type">
                            <option value="email"><?php _e('This email address', 'follow_up_emails'); ?></option>
                            <?php do_action( 'fue_manual_types', $email ); ?>
                        </select>

                        <div class="send-type-email send-type-div">
                            <input type="text" name="recipient_email" id="recipients" class="email-recipients" placeholder="someone@example.com" style="width: 600px;" />
                            <!--<select name="recipients[]" id="recipients" class="email-search-select" multiple data-placeholder="Search by name or email..." style="width: 600px;"></select>-->
                        </div>

                        <?php do_action( 'fue_manual_type_actions', $email ); ?>
                    </td>
                </tr>

                <tr valign="top" class="send_again_tr">
                    <th scope="row">
                        <label for="send_again"><?php _e('Send now and send again', 'follow_up_emails'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" name="send_again" id="send_again" value="1" />
                    </td>
                </tr>

                <tr valign="top" class="class_send_again send_again_interval_tr">
                    <th scope="row" class="interval_th">
                        <label for="interval_type"><?php _e('Send now and send again in:', 'follow_up_emails'); ?></label>
                    </th>
                    <td class="interval_td">
                        <span class="hide-if-date interval_span hideable">
                            <input type="text" name="interval" id="interval" value="<?php echo esc_attr($defaults['interval']); ?>" size="2" placeholder="0" />
                        </span>
                        <select name="interval_duration" id="interval_duration" class="interval_duration hideable">
                            <?php
                            $durations = FollowUpEmails::get_durations();

                            foreach ( $durations as $key => $value ):
                                if ( $key == 'date') continue;
                            ?>
                            <option class="interval_duration_<?php echo $key; ?> hideable" value="<?php echo esc_attr($key); ?>"><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>

                    </td>
                </tr>

                <?php do_action( 'fue_manual_email_form_before_message', $defaults ); ?>

                <tr valign="top">
                    <th scope="row">
                        <label for="subject"><?php _e('Email Subject', 'follow_up_emails'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="email_subject" id="email_subject" value="<?php echo esc_attr($defaults['subject']); ?>" class="regular-text" />
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">
                        <label for="message"><?php _e('Email Body', 'follow_up_emails'); ?></label>
                        <br />
                        <span class="description">
                            <?php _e('You may use the following variables in the Email Subject and Body', 'follow_up_emails'); ?>
                            <ul>
                                <?php do_action('fue_email_manual_variables_list'); ?>
                                <li class="var hideable var_customer_first_name"><strong>{customer_first_name}</strong> <img class="help_tip" title="<?php _e('The first name of the customer who purchased from your store.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                                <li class="var hideable var_customer_name"><strong>{customer_name}</strong> <img class="help_tip" title="<?php _e('The full name of the customer who purchased from your store.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                                <li class="var hideable var_customer_email"><strong>{customer_email}</strong> <img class="help_tip" title="<?php _e('The email address of the customer who purchased from your store.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                                <li class="var hideable var_store_url"><strong>{store_url}</strong> <img class="help_tip" title="<?php _e('The URL/Address of your store.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                                <li class="var hideable var_store_url_path"><strong>{store_url=path}</strong> <img class="help_tip" title="<?php _e('The URL/Address of your store with path added at the end. Ex. {store_url=/categories}', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                                <li class="var hideable var_store_name"><strong>{store_name}</strong> <img class="help_tip" title="<?php _e('The name of your store.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                                <li class="var hideable var_unsubscribe_url"><strong>{unsubscribe_url}</strong> <img class="help_tip" title="<?php _e('URL where users will be able to opt-out of the email list.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                                <li class="var hideable var_post_id"><strong>{post_id=xx}</strong> <img class="help_tip" title="<?php _e('Include the excerpt of the specified Post ID.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                            </ul>
                        </span>
                    </th>
                    <td>
                        <?php
                        $settings = array(
                            'textarea_rows' => 20,
                            'teeny'         => false
                        );
                        wp_editor($defaults['message'], 'email_message', $settings); ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="tracking_on"><?php _e('Add Google Analytics tracking to links', 'follow_up_emails'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" name="tracking_on" id="tracking_on" value="1" <?php if ($defaults['tracking_on'] == 1) echo 'checked'; ?> />
                    </td>
                </tr>
                <tr class="tracking_on">
                    <th scope="row">
                        <label for="tracking"><?php _e('Link Tracking', 'follow_up_emails'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="tracking" id="tracking" value="<?php echo esc_attr($defaults['tracking']); ?>" placeholder="e.g. utm_campaign=Follow-up-Emails-by-75nineteen" size="40" />
                        <p class="description">
                            <?php _e('The value inserted here will be appended to all URLs in the Email Body', 'follow_up_emails'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="test_email"><strong>Send a test email</strong></label>
                    </th>
                    <td>
                        <input type="hidden" id="email_type" value="manual" class="test-email-field" />
                        <input type="text" id="email" placeholder="Email Address" value="" class="test-email-field" />
                        <input type="button" id="test_send" value="<?php _e('Send Test', 'follow_up_emails'); ?>" class="button" />
                    </td>
                </tr>
            </tbody>
        </table>
        <p class="submit">
            <input type="hidden" name="action" value="sfn_followup_send_manual" />
            <input type="hidden" name="id" id="id" value="<?php echo $_GET['id']; ?>" />
            <input type="submit" name="save" id="save" value="<?php _e('Send Email Now', 'follow_up_emails'); ?>" class="button-primary" />
        </p>
    </form>
</div>

<script type="text/javascript">
var interval_types = <?php echo json_encode(FollowUpEmails::get_trigger_types()); ?>;
jQuery(document).ready(function($) {
    jQuery(".send-type-div").hide();

    jQuery("#send_type").change(function() {
        jQuery(".send-type-div").hide();
        switch (jQuery(this).val()) {

            case "email":
                jQuery(".send-type-email").show();
                break;

            default:
                break;

        }
    }).change();

    jQuery("select.chzn-select").chosen();

    jQuery("#tracking_on").change(function() {
        if (jQuery(this).attr("checked")) {
            jQuery(".tracking_on").show();
        } else {
            jQuery(".tracking_on").hide();
        }
    }).change();

    jQuery("#interval_type").change(function() {
        if (jQuery(this).val() != "cart") {
            jQuery(".not-cart").show();
        } else {
            jQuery(".not-cart").hide();
        }
    }).change();

    jQuery("#interval_duration").change(function() {
        if (jQuery(this).val() == "date") {
            jQuery(".hide-if-date").hide();
            jQuery(".show-if-date").show();
        } else {
            jQuery(".hide-if-date").show();
            jQuery(".show-if-date").hide();
        }

        jQuery("#email_type").change();
    }).change();

    jQuery(".date").datepicker();

    jQuery("#timeframe_from").datepicker({
        onClose: function( selectedDate ) {
            $( "#timeframe_to" ).datepicker( "option", "minDate", selectedDate );
        }
    });
    jQuery("#timeframe_to").datepicker();

    <?php do_action('fue_manual_email_form_script'); ?>

    jQuery("#send_again").change(function() {
        if (jQuery(this).attr("checked")) {
            jQuery(".class_send_again").show();
        } else {
            jQuery(".class_send_again").hide();
        }
    }).change();

    <?php do_action( 'fue_manual_js' ); ?>

});
function reset_elements() {
    jQuery(".hideable").show();

    var trigger = jQuery("#interval_type").val();

    jQuery("#interval_type option").remove();
    for (key in interval_types) {
        jQuery("#interval_type").append('<option class="interval_type_option interval_type_'+ key +'" id="interval_type_option_'+ key +'" value="'+ key +'">'+ interval_types[key] +'</option>');
    }

    if (trigger) {
        jQuery("#interval_type_option_"+trigger).attr("selected", true);
    }
}
</script>
