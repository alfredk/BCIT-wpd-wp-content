<?php

$my_emails = $wpdb->get_results( $wpdb->prepare("SELECT COUNT(*) AS num, user_email, order_id FROM {$wpdb->prefix}followup_email_orders WHERE (user_id = %d OR user_email = %s) AND is_sent = 0 AND order_id > 0 GROUP BY order_id", $user->ID, $user->user_email) );
$order = new WC_Order();

if ( isset($_GET['fue_order_unsubscribed']) ):
?><div class="woocommerce-message"><?php _e('Successfully unsubscribed from the selected email', 'follow_up_emails'); ?></div><?php
endif;

if ( $my_emails ):
?>
<table class="shop_table my_accout_emails">
    <thead>
        <tr>
            <th class="order-number"><span class="nobr"><?php _e('Order', 'woocommerce'); ?></span></th>
            <th class="actions"><span class="nobr"><?php _e('Actions', 'follow_up_emails'); ?></span></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ( $my_emails as $email ):
            $order->get_order($email->order_id);
        ?>
        <tr>
            <td class="order-number">
                <a href="<?php echo esc_url( add_query_arg('order', $email->order_id, get_permalink( woocommerce_get_page_id( 'view_order' ) ) ) ); ?>">
                    <?php echo $order->get_order_number(); ?></a>
                    &ndash;
                <em>(<?php printf( _n('1 email', '%d emails', $email->num, 'follow_up_emails'), $email->num ); ?>)</em>
            </td>
            <td><a href="<?php echo wp_nonce_url(add_query_arg(array('fue_action' => 'order_unsubscribe', 'email' => $email->user_email, 'order_id' => $email->order_id)), 'fue_unsubscribe'); ?>" class="button"><?php _e('Unsubscribe', 'follow_up_emails'); ?></a></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<div class="woocommerce-info">
    <a href="<?php echo get_permalink( woocommerce_get_page_id('myaccount') ); ?>" class="button"><?php _e('Back to My Account', 'follow_up_emails'); ?></a>
    <?php _e('You are not subscribed to any emails.', 'follow_up_emails'); ?>
</div>
<?php endif; ?>
