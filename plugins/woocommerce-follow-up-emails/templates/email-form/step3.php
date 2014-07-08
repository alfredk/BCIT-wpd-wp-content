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
        <?php _e('Step Three: Design your email and launch campaign!', 'follow_up_emails'); ?>
    </h2>

    <div id="progress" class="help_tip" title="100%">
        <div class="complete" style="width: 100%">&nbsp;</div>
    </div>

    <form action="admin-post.php" method="post" id="fue_form">
        <div class="field-container">

            <div class="fields-left">

                <?php do_action('fue_email_form_before_message', $defaults); ?>

                <div class="field" id="design_custom">

                    <div id="custom_toolbar_container">
                        <div id="custom_toolbar_inner">
                            <div id="custom_editor_toolbar"></div>
                        </div>
                    </div>

                    <!--<div id="custom_editor_div" contenteditable="true">
                        <?php echo $defaults['message']; ?>
                    </div>-->
                    <?php
                    $settings = array(
                        'textarea_rows' => 20,
                        'teeny'         => false
                    );
                    wp_editor($defaults['message'], 'email_message', $settings); ?>
                </div>

                <div class="field">
                    <label for="email"><strong><?php _e('Send a test email', 'follow_up_emails'); ?></strong></label>
                    <input type="text" id="email" placeholder="Email Address" value="" class="test-email-field" />
                    <?php do_action('fue_test_email_fields', $defaults); ?>
                    <input type="button" id="test_send" value="<?php _e('Send Email', 'follow_up_emails'); ?>" class="button" />
                </div>

                <div class="field">
                    <a class="button-secondary" href="admin.php?page=followup-emails-form&step=2&id=<?php echo $defaults['id']; ?>"><?php _e('Back to Step 2', 'follow_up_emails'); ?></a>

                    <input type="hidden" id="email_type" value="<?php echo $defaults['type']; ?>" />
                    <input type="hidden" name="action" value="sfn_followup_form" />
                    <input type="hidden" name="id" id="id" value="<?php echo $defaults['id']; ?>" />
                    <input type="hidden" name="new" value="<?php echo (isset($_GET['new'])) ? 1 : 0; ?>" />
                    <input type="hidden" name="step" value="3" />
                    <input type="hidden" name="meta[design_type]" value="custom" class="design_type" checked />
                    <input type="submit" class="button-primary" name="submit" value="<?php _e('Save your email', 'follow_up_emails'); ?>" />
                </div>
            </div>

            <div class="fields-right">

                <div class="post-box vars-box" id="vars_box">
                    <span class="description">
                        <?php _e('Available Variables', 'follow_up_emails'); ?>
                        <ul>
                            <?php do_action('fue_email_variables_list', $defaults); ?>
                            <li class="var hideable var_customer_first_name"><strong>{customer_first_name}</strong> <img class="help_tip" title="<?php _e('The first name of the customer who purchased from your store.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                            <li class="var hideable var_customer_name"><strong>{customer_name}</strong> <img class="help_tip" title="<?php _e('The full name of the customer who purchased from your store.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                            <li class="var hideable var_customer_email"><strong>{customer_email}</strong> <img class="help_tip" title="<?php _e('The email address of the customer who purchased from your store.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                            <li class="var hideable var_store_url"><strong>{store_url}</strong> <img class="help_tip" title="<?php _e('The URL/Address of your store.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                            <li class="var hideable var_store_url_path"><strong>{store_url=path}</strong> <img class="help_tip" title="<?php _e('The URL/Address of your store with path added at the end. Ex. {store_url=/categories}', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                            <li class="var hideable var_store_name"><strong>{store_name}</strong> <img class="help_tip" title="<?php _e('The name of your store.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                            <li class="var hideable var_order var_order_number not-cart non-signup"><strong>{order_number}</strong> <img class="help_tip" title="<?php _e('The generated Order Number for the puchase', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                            <li class="var hideable var_order var_order_date not-cart non-signup"><strong>{order_date}</strong> <img class="help_tip" title="<?php _e('The date that the order was made', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                            <li class="var hideable var_order var_order_datetime not-cart non-signup"><strong>{order_datetime}</strong> <img class="help_tip" title="<?php _e('The date and time that the order was made', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                            <li class="var hideable var_order var_order_billing_address not-cart non-signup"><strong>{order_billing_address}</strong> <img class="help_tip" title="<?php _e('The billing address of the order', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                            <li class="var hideable var_order var_order_shipping_address not-cart non-signup"><strong>{order_shipping_address}</strong> <img class="help_tip" title="<?php _e('The shipping address of the order', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                            <li class="var hideable var_unsubscribe_url"><strong>{unsubscribe_url}</strong> <img class="help_tip" title="<?php _e('URL where users will be able to opt-out of the email list.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                            <li class="var hideable var_post_id"><strong>{post_id=xx}</strong> <img class="help_tip" title="<?php _e('Include the excerpt of the specified Post ID.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                        </ul>
                    </span>
                </div>
            </div>

        </div>

    </form>
</div>
<script type="text/javascript">
var interval_types = <?php echo json_encode(FollowUpEmails::get_trigger_types()); ?>;
var email_intervals = <?php echo json_encode(FollowUpEmails::get_email_type_triggers()); ?>;
jQuery(document).ready(function() {
    <?php do_action('fue_email_form_script', $defaults); ?>

    var top = jQuery('#vars_box').offset().top - parseFloat(jQuery('#vars_box').css('marginTop').replace(/auto/, 0));
    jQuery(window).scroll(function (event) {
        // what the y position of the scroll is
        var y = jQuery(this).scrollTop();

        // whether that's below the form
        if (y >= top) {
          // if so, ad the fixed class
          jQuery('#vars_box').addClass('fixed');
        } else {
          // otherwise remove it
          jQuery('#vars_box').removeClass('fixed');
        }
    });

});

</script>
