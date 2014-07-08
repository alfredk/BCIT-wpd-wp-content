<div class="wrap">
<div class="icon32"><img src="<?php echo FUE_TEMPLATES_URL .'/images/send_mail.png'; ?>" /></div>
<h2>
    <?php _e('Follow-Up Emails', 'follow_up_emails'); ?>
    <a class="add-new-h2" href="admin.php?page=followup-emails-form&new=1"><?php _e('Add New', 'follow_up_emails'); ?></a>
</h2>

<?php if (isset($_GET['created'])): ?>
    <div id="message" class="updated"><p><?php _e('Follow-up email created', 'follow_up_emails'); ?></p></div>
<?php endif; ?>

<?php if (isset($_GET['updated'])): ?>
    <div id="message" class="updated"><p><?php _e('Follow-up email updated', 'follow_up_emails'); ?></p></div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
    <div id="message" class="updated"><p><?php _e('Follow-up email deleted!', 'follow_up_emails'); ?></p></div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div id="message" class="error"><p><?php echo $_GET['error']; ?></p></div>
<?php endif; ?>

<?php if (isset($_GET['manual_sent'])): ?>
    <div id="message" class="updated"><p><?php _e('Email(s) have been sent', 'follow_up_emails'); ?></p></div>
<?php endif; ?>

<?php do_action('fue_settings_notification'); ?>

<style type="text/css">
    span.priority {
        display: inline;
        padding: 4px 7px;
        background: #EAF2FA;
        border-radius: 10px;
        border: 1px solid #ddd;
    }

    .fue_table_footer {
        position: relative;
        overflow: hidden;
        padding: 8px;
        background: #EAF2FA;
        border: #c7d7e2 solid 1px;
        border-top:0 none;
    }

    .fue_table_footer .order_message {
        background: url(<?php echo FUE_TEMPLATES_URL; ?>/images/drag_and_drop_to_reorder.png);
        width: 161px;
        height: 23px;
        float: left;
        margin-left: 20px;
    }

    .ui-sortable tr {
        cursor: move;
    }
</style>
<form action="admin-post.php" method="post" id="update_priorities">

<div class="subsubsub_section">
<ul class="subsubsub">
    <?php
    $num    = count($types);
    $i      = 0;

    foreach ( $types as $key => $type ):
        $i++;
        $cls = ($i == 1) ? 'current' : '';
        echo '<li><a href="#'. $key .'_mails" class="'. $cls .'">'. $type .'</a>';

        if ($i < $num) echo '|';
        echo '</li>';

    endforeach;
    ?>
    <?php do_action( 'fue_email_types_sub' ); ?>
</ul>
<br class="clear">

<?php
foreach ( $types as $key => $type ):
    $mails = FUE::get_emails($key);
    $bcc    = isset($bccs[$key]) ? $bccs[$key] : '';

    // Manual Emails
    if ( $key == 'manual' ) {
        ?>
        <div class="section" id="manual_mails" style="display:none;">
        <h3><?php _e('Manual Emails', 'follow_up_emails'); ?></h3>

        <p class="description">Manual emails allow you to create email templates for you and your team to utilize when you need to send emails immediately to customers or prospective customers. Creating a manual email will allow you to reduce manual entry and duplication when you send emails from your email client, and keep emails consistent. Below are the existing Manual emails set up for your store.</p><br />

        <p>
            <?php _e('Send a copy of all emails of this type to:', 'follow_up_emails'); ?>
            <input type="text" name="bcc[<?php echo $key; ?>]" value="<?php echo esc_attr( $bcc ); ?>" />
        </p>

        <table class="wp-list-table widefat fixed posts manual-table">
            <thead>
            <tr>
                <th scope="col" class="manage-column column-type" style=""><?php _e('Name', 'follow_up_emails'); ?></th>
                <th scope="col" class="manage-column column-usage_count" style=""><?php _e('Used', 'follow_up_emails'); ?></th>
                <?php do_action( 'fue_table_manual_head' ); ?>
            </tr>
            </thead>
            <tbody id="the_list">
            <?php if (empty($mails)): ?>
                <tr scope="row">
                    <th colspan="2"><?php _e('No emails available', 'follow_up_emails'); ?></th>
                </tr>
            <?php
            else:
                $p = 0;
                foreach ($mails as $email):
                    $p++;
                    ?>
                    <tr scope="row">
                        <td class="post-title column-title">
                            <input type="hidden" name="manual_order[]" value="<?php echo $email->id; ?>" />
                            <strong><a class="row-title" href="admin.php?page=followup-emails&tab=send&id=<?php echo $email->id; ?>"><?php echo stripslashes($email->name); ?></a></strong>
                            <div class="row-actions">
                                <span class="send"><a href="admin.php?page=followup-emails&tab=send&id=<?php echo $email->id; ?>"><?php _e('Send', 'follow_up_emails'); ?></a></span>
                                |
                                <span class="edit"><a href="admin.php?page=followup-emails-form&step=1&id=<?php echo $email->id; ?>"><?php _e('Edit', 'follow_up_emails'); ?></a></span>
                                |
                                <span class="trash"><a onclick="return confirm('Really delete this email?');" href="<?php echo wp_nonce_url('admin-post.php?action=sfn_followup_delete&id='. $email->id, 'delete-email'); ?>"><?php _e('Delete', 'follow_up_emails'); ?></a></span>
                            </div>
                        </td>
                        <td>
                            <?php echo $email->usage_count; ?>
                        </td>
                        <?php do_action( 'fue_table_manual_body' ); ?>
                    </tr>
                <?php
                endforeach;
                ?>
            <?php endif; ?>
            </tbody>
        </table>
        </div><?php
    } elseif ( $key == 'normal' ) {
        ?>
        <div class="section" id="normal_mails" style="display:none;">
        <h3><?php _e('Product/Category Emails', 'follow_up_emails'); ?></h3>

        <p class="description"><?php _e('Product and Category emails will send to a buyer of products within the specific products or categories from your store based upon the criteria you define when creating your emails.', 'follow_up_emails'); ?></p><br />

        <p>
            <?php _e('Send a copy of all emails of this type to:', 'follow_up_emails'); ?>
            <input type="text" name="bcc[<?php echo $key; ?>]" value="<?php echo esc_attr( $bcc ); ?>" />
        </p>

        <table class="wp-list-table widefat fixed posts manual-table">
            <thead>
            <tr>
                <th scope="col" id="priority" class="manage-column column-type" style="width:50px;"><?php _e('Priority', 'follow_up_emails'); ?></th>
                <th scope="col" id="type" class="manage-column column-type" style=""><?php _e('Name', 'follow_up_emails'); ?></th>
                <th scope="col" id="amount" class="manage-column column-amount" style=""><?php _e('Interval', 'follow_up_emails'); ?></th>
                <th scope="col" id="product" class="manage-column column-product" style=""><?php _e('Product', 'follow_up_emails'); ?></th>
                <th scope="col" id="category" class="manage-column column-category" style=""><?php _e('Category', 'follow_up_emails'); ?></th>
                <th scope="col" id="usage_count" class="manage-column column-usage_count" style=""><?php _e('Used', 'follow_up_emails'); ?></th>
                <th scope="col" id="generic_always_send"><?php _e('Always Send', 'follow_up_emails'); ?></th>
                <?php do_action( 'fue_table_'. $key .'_head' ); ?>
                <th scope="col" id="status" class="manage-column column-status"><?php _e('Status', 'follow_up_emails'); ?></th>
            </tr>
            </thead>
            <tbody id="the_list">
            <?php if (empty($mails)): ?>
                <tr scope="row">
                    <th colspan="8"><?php _e('No emails available', 'follow_up_emails'); ?></th>
                </tr>
            <?php
            else:
                $p = 0;
                foreach ($mails as $email):
                    $p++;
                    ?>
                    <tr scope="row">
                        <td style="text-align: center; vertical-align:middle;"><span class="priority"><?php echo $p; ?></span></td>
                        <td class="post-title column-title">
                            <input type="hidden" name="<?php echo $key; ?>_order[]" value="<?php echo $email->id; ?>" />
                            <strong><a class="row-title" href="admin.php?page=followup-emails-form&step=1&id=<?php echo $email->id; ?>"><?php echo stripslashes($email->name); ?></a></strong>
                            <div class="row-actions">
                                <span class="edit"><a href="admin.php?page=followup-emails-form&step=1&id=<?php echo $email->id; ?>"><?php _e('Edit', 'follow_up_emails'); ?></a></span>
                                |
                                <span class="trash"><a onclick="return confirm('Really delete this email?');" href="<?php echo wp_nonce_url('admin-post.php?action=sfn_followup_delete&id='. $email->id, 'delete-email'); ?>"><?php _e('Delete', 'follow_up_emails'); ?></a></span>
                            </div>
                        </td>
                        <td>
                            <?php
                            $interval_str = '';
                            $meta = maybe_unserialize($email->meta);

                            if ( $email->interval_type == 'signup' ) {
                                $interval_str = sprintf( __('%d %s after user signs up', 'follow_up_emails'), $email->interval_num, FollowUpEmails::get_duration($email->interval_duration, $email->interval_num) );
                            } elseif ( $email->interval_type == 'order_total_above' ) {
                                $interval_str = sprintf( __('%d %s when %s %s%s'), $email->interval_num, FollowUpEmails::get_duration($email->interval_duration), FollowUpEmails::get_trigger_name($email->interval_type), get_woocommerce_currency_symbol(), $meta['order_total_above'] );
                            } elseif ( $email->interval_type == 'order_total_below' ) {
                                $interval_str = sprintf( __('%d %s when %s %s%s'), $email->interval_num, FollowUpEmails::get_duration($email->interval_duration), FollowUpEmails::get_trigger_name($email->interval_type), get_woocommerce_currency_symbol(), $meta['order_total_below'] );
                            } elseif ( $email->interval_duration != 'date' ) {
                                $interval_str = sprintf( __('%d %s %s'), $email->interval_num, FollowUpEmails::get_duration($email->interval_duration), FollowUpEmails::get_trigger_name($email->interval_type) );
                            } else {
                                $send_date = (!empty($email->send_date_hour)) ? $email->send_date .' '. $email->send_date_hour .':'. $email->send_date_minute .' '. $meta['send_date_ampm'] : $email->send_date;
                                $interval_str = sprintf( __('Send on %s'), $send_date) ;
                            }

                            echo apply_filters( 'fue_interval_str', $interval_str, $email );
                            ?>
                        </td>
                        <td><?php echo ($email->product_id > 0) ? '<a href="post.php?post='. $email->product_id .'&action=edit">'. get_the_title($email->product_id) .'</a>' : '-'; ?></td>
                        <td>
                            <?php
                            if ($email->category_id == 0) {
                                echo '-';
                            } else {
                                $term = get_term( $email->category_id, 'product_cat' );
                                echo '<a href="edit-tags.php?action=edit&taxonomy=product_cat&tag_ID='. $email->category_id .'&post_type=product">'. $term->name .'</a>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php echo $email->usage_count; ?>
                        </td>
                        <td>
                            <?php echo ($email->always_send == 1) ? __('Yes', 'follow_up_emails') : __('No', 'follow_up_emails'); ?>
                        </td>
                        <!--<td><input type="hidden" class="generic_priorities" name="priority[<?php echo $email->id; ?>]" value="<?php echo $email->priority; ?>" size="3" /></td>-->
                        <?php do_action( 'fue_table_all_products_body' ); ?>
                        <td class="status">
                            <?php if ($email->status == 1): ?>
                                <?php _e('Active', 'follow_up_emails'); ?>
                                <br/><small><a href="#" class="toggle-activation" data-id="<?php echo $email->id; ?>"><?php _e('Deactivate', 'follow_up_emails'); ?></a></small>
                            <?php else: ?>
                                <?php _e('Inactive', 'follow_up_emails'); ?>
                                <br/><small><a href="#" class="toggle-activation" data-id="<?php echo $email->id; ?>"><?php _e('Activate', 'follow_up_emails'); ?></a></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php
                endforeach;
                ?>
            <?php endif; ?>
            </tbody>
        </table>
        <div class="fue_table_footer">
            <div class="order_message"></div>
        </div>
        </div><?php
    } else {
        ?><div class="section" id="<?php echo $key; ?>_mails">
        <h3><?php echo $type; ?></h3>

        <p class="description"><?php echo FollowUpEmails::get_email_type_long_description($key); ?></p><br />

        <p>
            <?php _e('Send a copy of all emails of this type to:', 'follow_up_emails'); ?>
            <input type="text" name="bcc[<?php echo $key; ?>]" value="<?php echo esc_attr( $bcc ); ?>" />
        </p>

        <table class="wp-list-table widefat fixed posts generic-table">
            <thead>
            <tr>
                <th scope="col" id="priority" class="manage-column column-type" style="width:50px;"><?php _e('Priority', 'follow_up_emails'); ?></th>
                <th scope="col" id="type" class="manage-column column-type" style=""><?php _e('Name', 'follow_up_emails'); ?></th>
                <th scope="col" id="amount" class="manage-column column-amount" style=""><?php _e('Interval', 'follow_up_emails'); ?></th>
                <th scope="col" id="usage_count" class="manage-column column-usage_count" style=""><?php _e('Used', 'follow_up_emails'); ?></th>
                <th scope="col" id="generic_always_send"><?php _e('Always Send', 'follow_up_emails'); ?></th>
                <?php do_action( 'fue_table_'. $key .'_head' ); ?>
                <th scope="col" id="status" class="manage-column column-status"><?php _e('Status', 'follow_up_emails'); ?></th>
            </tr>
            </thead>
            <tbody id="the_list">
            <?php if (empty($mails)): ?>
                <tr scope="row">
                    <th colspan="6"><?php _e('No emails available', 'follow_up_emails'); ?></th>
                </tr>
            <?php
            else:
                $p = 0;
                foreach ($mails as $email):
                    $p++;
                    ?>
                    <tr scope="row">
                        <td style="text-align: center; vertical-align:middle;"><span class="priority"><?php echo $p; ?></span></td>
                        <td class="post-title column-title">
                            <input type="hidden" name="<?php echo $key; ?>_order[]" value="<?php echo $email->id; ?>" />
                            <strong><a class="row-title" href="admin.php?page=followup-emails-form&step=1&id=<?php echo $email->id; ?>"><?php echo stripslashes($email->name); ?></a></strong>
                            <div class="row-actions">
                                <span class="edit"><a href="admin.php?page=followup-emails-form&step=1&id=<?php echo $email->id; ?>"><?php _e('Edit', 'follow_up_emails'); ?></a></span>
                                |
                                <span class="edit"><a href="#" class="clone-email" data-id="<?php echo $email->id; ?>"><?php _e('Clone as...', 'follow_up_emails'); ?></a></span>
                                |
                                <span class="trash"><a onclick="return confirm('Really delete this entry?');" href="<?php echo wp_nonce_url('admin-post.php?action=sfn_followup_delete&id='. $email->id, 'delete-email'); ?>"><?php _e('Delete', 'follow_up_emails'); ?></a></span>
                            </div>
                        </td>
                        <td>
                            <?php
                            $interval_str = '';
                            $meta = maybe_unserialize($email->meta);

                            if ( $email->interval_type == 'signup' ) {
                                $interval_str = sprintf( __('%d %s after user signs up', 'follow_up_emails'), $email->interval_num, FollowUpEmails::get_duration($email->interval_duration, $email->interval_num) );
                            } elseif ( $email->interval_type == 'order_total_above' ) {
                                $interval_str = sprintf( __('%d %s when %s %s%s'), $email->interval_num, FollowUpEmails::get_duration($email->interval_duration), FollowUpEmails::get_trigger_name($email->interval_type), get_woocommerce_currency_symbol(), $meta['order_total_above'] );
                            } elseif ( $email->interval_type == 'order_total_below' ) {
                                $interval_str = sprintf( __('%d %s when %s %s%s'), $email->interval_num, FollowUpEmails::get_duration($email->interval_duration), FollowUpEmails::get_trigger_name($email->interval_type), get_woocommerce_currency_symbol(), $meta['order_total_below'] );
                            } elseif ( $email->interval_duration != 'date' ) {
                                $interval_str = sprintf( __('%d %s %s'), $email->interval_num, FollowUpEmails::get_duration($email->interval_duration), FollowUpEmails::get_trigger_name($email->interval_type) );
                            } else {
                                $send_date = (!empty($email->send_date_hour)) ? $email->send_date .' '. $email->send_date_hour .':'. $email->send_date_minute .' '. $meta['send_date_ampm'] : $email->send_date;
                                $interval_str = sprintf( __('Send on %s'), $send_date) ;
                            }

                            echo apply_filters( 'fue_interval_str', $interval_str, $email );
                            ?>
                        </td>
                        <td>
                            <?php echo $email->usage_count; ?>
                        </td>
                        <td>
                            <?php echo ($email->always_send == 1) ? __('Yes', 'follow_up_emails') : __('No', 'follow_up_emails'); ?>
                        </td>
                        <!--<td><input type="hidden" class="generic_priorities" name="priority[<?php echo $email->id; ?>]" value="<?php echo $email->priority; ?>" size="3" /></td>-->
                        <?php do_action( 'fue_table_all_products_body' ); ?>
                        <td class="status">
                            <?php if ($email->status == 1): ?>
                                <?php _e('Active', 'follow_up_emails'); ?>
                                <br/><small><a href="#" class="toggle-activation" data-id="<?php echo $email->id; ?>"><?php _e('Deactivate', 'follow_up_emails'); ?></a></small>
                            <?php else: ?>
                                <?php _e('Inactive', 'follow_up_emails'); ?>
                                <br/><small><a href="#" class="toggle-activation" data-id="<?php echo $email->id; ?>"><?php _e('Activate', 'follow_up_emails'); ?></a></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php
                endforeach;
                ?>
            <?php endif; ?>
            </tbody>
        </table>
        <div class="fue_table_footer">
            <div class="order_message"></div>
        </div>
        </div><?php
    }
    ?>

<?php endforeach; ?>

<?php do_action('fue_email_types_section'); ?>
</div>

<p class="submit">
    <input type="hidden" name="action" value="sfn_followup_save_priorities" />
    <input type="submit" name="save" value="<?php _e('Update', 'follow_up_emails'); ?>" class="button-primary" />
</p>
</form>

<script type="text/javascript">
    jQuery(window).load(function(){
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

        var url_hash = window.location.hash;
        if (url_hash != "") {
            jQuery("a[href="+ url_hash +"]").click();
        }

        // Sorting
        jQuery('table tbody').sortable({
            items:'tr',
            cursor:'move',
            axis:'y',
            handle: 'td',
            scrollSensitivity:40,
            helper:function(e,ui){
                ui.children().each(function(){
                    jQuery(this).width(jQuery(this).width());
                });
                ui.css('left', '0');
                return ui;
            },
            start:function(event,ui){
                ui.item.css('background-color','#f6f6f6');
            },
            stop:function(event,ui){
                ui.item.removeAttr('style');
                update_priorities();
            }
        });

        // Cloning
        jQuery("a.clone-email").click(function(e) {
            e.preventDefault();

            var name        = prompt("<?php _e('Email Name', 'follow_up_emails'); ?>");
            var email_id    = jQuery(this).data("id");
            var parent      = jQuery(this).parents("table");

            if (name) {
                jQuery(parent).block({ message: null, overlayCSS: { background: '#fff url('+ FUE.ajax_loader +') no-repeat center', opacity: 0.6 } });

                var data = {
                    action: 'fue_email_clone',
                    id:     email_id,
                    name:   name
                };

                jQuery.post(ajaxurl, data, function(resp) {
                    resp = JSON.parse(resp);

                    if (resp.status == "OK") {
                        window.location.href = resp.url;
                    } else {
                        alert(resp.message);
                        jQuery(parent).unblock();
                    }

                });

            }
        });

        jQuery(".toggle-activation").live("click", function(e) {
            e.preventDefault();

            var parent  = jQuery(this).parents("tr");
            var id      = jQuery(this).data("id");
            var that    = this;

            jQuery(parent).block({ message: null, overlayCSS: { background: '#fff url('+ FUE.ajax_loader +') no-repeat center', opacity: 0.6 } });

            var data = {
                action: 'fue_email_toggle_status',
                id:     id
            };

            jQuery.post(ajaxurl, data, function(resp) {
                resp = jQuery.parseJSON(resp);
                if (resp.ack != "OK") {
                    alert(resp.error);
                } else {
                    var td = jQuery(that).parents("td.status").eq(0);
                    jQuery(td).html(resp.new_status + '<br/><small><a href="#" class="toggle-activation" data-id="'+ id +'">'+ resp.new_action +'</a></small>');
                }
                jQuery(parent).unblock();
            });

        });

        <?php do_action('email_types_script'); ?>
    });
    function update_priorities() {
        jQuery('table tbody').each(function(i) {

            jQuery(tbody).find("tr").each(function(x) {
                jQuery(this).find("td .priority").html(x+1);
            });

        });

        <?php do_action('email_types_update_priorities_script'); ?>
    }
</script>
</div>
