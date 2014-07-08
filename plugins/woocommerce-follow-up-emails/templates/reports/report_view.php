<?php
/** @var array $reports */

// get the name of the email
if ( empty($reports) ) {
    $report_name = sprintf(__('Email #%d', 'follow_up_emails'), $id);
} else {
    $report = $reports[0];
    $report_name = $report->email_name;
}
?>
<h3><?php printf(__('Report for %s', 'wc_folloup_emails'), $report_name); ?></h3>

<table class="wp-list-table widefat fixed posts">
    <thead>
    <tr>
        <th scope="col" id="type" class="manage-column column-type" style=""><?php _e('Customer Name', 'follow_up_emails'); ?></th>
        <th scope="col" id="user_email" class="manage-column column-user_email" style=""><?php _e('Email', 'follow_up_emails'); ?></th>
        <th scope="col" id="product" class="manage-column column-product" style=""><?php _e('Product', 'wc_folloup_emails'); ?></th>
        <th scope="col" id="trigger" class="manage-column column-trigger" style=""><?php _e('Trigger', 'wc_folloup_emails'); ?></th>
        <th scope="col" id="order" class="manage-column column-order" style="">&nbsp;</th>
        <th scope="col" id="date_sent" class="manage-column column-date_sent" style=""><?php _e('Date Sent', 'follow_up_emails'); ?></th>
    </tr>
    </thead>
    <tbody id="the_list">
    <?php
    if ( empty($reports) ):
    ?>
        <tr scope="row">
            <th colspan="6"><?php _e('No reports available', 'follow_up_emails'); ?></th>
        </tr>
    <?php
    else:
        foreach ( $reports as $report ):
            if ($report->user_id != 0) {
                $wp_user = new WP_User($report->user_id);
                $name = $wp_user->first_name .' '. $wp_user->last_name;
                $name = '<strong><a href="edit.php?post_status=all&post_type=shop_order&_customer_user='. $report->user_id .'">'. stripslashes($name) .'</a></strong>';
            } else {
                if (! empty($report->customer_name) ) {
                    $name = '<strong>'. stripslashes($report->customer_name) .'</strong>';
                } else {
                    $name = '-';
                }
            }
    ?>
        <tr scope="row">
            <td class="post-title column-title">
                <?php echo $name; ?>
            </td>
            <td><?php echo stripslashes($report->email_address); ?></td>
            <td>
                <?php

                if ( $report->product_id != 0 ) {
                    echo '<a href="'. get_permalink($report->product_id) .'">'. get_the_title($report->product_id) .'</a>';
                } else {
                    echo '-';
                }
                ?>
            </td>
            <td><?php echo $report->email_trigger; ?></td>
            <td>
                <?php
                if ($report->order_id != 0) {
                    echo '<a href="post.php?post='. $report->order_id .'&action=edit">View Order</a>';
                } else {
                    echo '-';
                }
                ?>
            </td>
            <td><?php echo date( get_option('date_format') .' '. get_option('time_format') , strtotime($report->date_sent)); ?></td>
        </tr>
    <?php
        endforeach;
    endif; //empty ($reports)
    ?>
    </tbody>
</table>