<h2 id="saved-cards" style="margin-top:40px;"><?php _e( 'Saved cards', 'woocommerce-gateway-stripe' ); ?></h2>
<table class="shop_table">
	<thead>
		<tr>
			<th><?php _e( 'Card ending in...', 'woocommerce-gateway-stripe' ); ?></th>
			<th><?php _e( 'Expires', 'woocommerce-gateway-stripe' ); ?></th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $credit_cards as $i => $credit_card ) : ?>
		<tr>
            <td><?php esc_html_e( $credit_card['active_card'] ); ?></td>
            <td><?php echo esc_html( $credit_card['exp_month'] ) . '/' . esc_html( $credit_card['exp_year'] ); ?></td>
			<td>
                <form action="" method="POST">
                    <?php wp_nonce_field ( 'stripe_del_card' ); ?>
                    <input type="hidden" name="stripe_delete_card" value="<?php echo esc_attr( $i ); ?>">
                    <input type="submit" class="button" value="<?php _e( 'Delete card', 'woocommerce-gateway-stripe' ); ?>">
                </form>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>