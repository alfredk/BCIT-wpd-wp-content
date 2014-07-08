<?php global $woocommerce; ?>

<form id="wc_lost_license_form" method="post">
	
	<div class="form-row form-row-first">
		
		<p><?php _e( 'Please tell us the email address used during the purchase. Your license along with the order receipt will be sent by email.', 'wc_software' ) ?></p>
		
		<p><?php _e( 'If your email address has changed, please contact us.', 'wc_software' ) ?></p>
	
	</div>
	
	<div class="form-row form-row-last">
		
		<noscript><p class="woocommerce_error"><?php _e( 'Javascript must be enabled to use this form.', 'wc_software' ); ?></p></noscript>
	
		<p><label for="wc_email"><?php _e( 'Your email address', 'wc_software' ) ?>:</label> <input type="text" class="input-text" id="wc_email" name="wc_email" /></p>
		
		<p><input type="submit" class="button-alt" name="wc_lost_license_btn" id="wc_lost_license_btn" value="<?php _e( 'Email Licence Keys', 'wc_software' ); ?>" /></p>

	</div>
	
	<div class="clear"></div>
</form>

<script type="text/javascript">

	jQuery(function(){
		jQuery('#wc_lost_license_form').submit(function(){
			
			$form = jQuery('#wc_lost_license_form');

			$form.block({message: null, overlayCSS: {background: 'transparent url(<?php echo $woocommerce->plugin_url(); ?>/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6}});
			
			if ( ! $form.hasClass('loading') ) {
			
				$form.addClass('loading');
			
				jQuery('.woocommerce_error, .woocommerce_message').fadeOut('fast', function(){
					jQuery(this).remove();
				});
								
				var data = {
					action: 			'woocommerce_lost_licence',
					security: 			'<?php echo wp_create_nonce("wc-lost-licence"); ?>',
					email: 				jQuery('input[name=wc_email]').val()
				};

				jQuery.post("<?php echo $woocommerce->ajax_url() ?>", data, function( response ){
					
					$form.removeClass('loading');
					$form.unblock();
					
					response = jQuery.parseJSON( response );
					
					if ( response.success ) {
						
						$form.prepend( '<div class="woocommerce_message">' + response.message + '</div>' ).fadeIn();
						
					} else {
						if ( response.success === false ) {
							$form.prepend( '<div class="woocommerce_error">' + response.message + '</div>' ).fadeIn();
						} else {
							$form.prepend( '<div class="woocommerce_error">' + '<?php _e('Error processing request', 'wc_software'); ?>' + '</div>' ).fadeIn();
						}
					}
					
				});
			}
			
			return false;
		});
	});
</script>