<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<?php do_action( 'woocommerce_email_header', $email_heading ); ?>

<?php if ( sizeof( $keys ) > 0 ) : ?> 

	<?php foreach ( $keys as $key ) : ?>
	
		<h3><?php echo $key->software_product_id; ?> <?php if ( $key->software_version ) printf( __( 'Version %s', 'wc_software' ), $key->software_version ); ?></h3>
		
		<ul>
			<li><?php _e( 'Licence Email:', 'wc_software' ); ?> <strong><?php echo $key->activation_email; ?></strong></li>
			<li><?php _e( 'Licence Key:', 'wc_software' ); ?> <strong><?php echo $key->licence_key; ?></strong></li>
			<?php if ( $remaining = $GLOBALS['wc_software']->activations_remaining( $key->key_id ) ) echo '<li>' . sprintf( __( '%s activations remaining', 'wc_software' ), $remaining ) . '</li>'; ?>
		</ul>
	
	<?php endforeach; ?>
	
<?php endif; ?>

<?php do_action( 'woocommerce_email_footer' ); ?>