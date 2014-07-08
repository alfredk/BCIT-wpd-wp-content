<div class="upgrade_licence_key">

	<h3><?php _e( 'Upgrading from an old version?', 'wc_software' ); ?></h3>
	
	<p><?php printf( __( 'Upgrade from %s for just %s by entering your details below.', 'wc_software' ), $upgradable_product, woocommerce_price( $price ) ); ?></p>
	
	<p class="form-row form-row-wide">
		<label for="activation_email"><?php _e( 'Activation email:', 'wc_software' ); ?></label>
		<input name="activation_email" class="input-text" id="activation_email" value="" placeholder="<?php _e( 'Enter your email address', 'wc_software' ); ?>" />
	</p>
	
	<p class="form-row form-row-wide">
		<label for="licence_key"><?php _e( 'Licence Key:', 'wc_software' ); ?></label>
		<input name="licence_key" class="input-text" id="licence_key" value="<?php if ( $prefix ) echo $prefix; ?>" placeholder="<?php _e( 'Enter your licence key', 'wc_software' ); ?>" />
	</p>
	
	<p>
		<input type="submit" class="button" name="upgrade_software" value="Upgrade" />
		<input type="hidden" name="software_product_id" value="<?php echo $software_product_id; ?>" />
	</p>
					
</div>