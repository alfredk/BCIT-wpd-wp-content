jQuery( function() {

	/* Open and close */
	jQuery("form.checkout, form#order_review").on('change', 'input[name=stripe_customer_id]', function() {
		if ( jQuery('input[name=stripe_customer_id]:checked').val() == 'new' ) {
			jQuery('div.stripe_new_card').slideDown( 200 );
		} else {
			jQuery('div.stripe_new_card').slideUp( 200 );
		}
	} );

	jQuery(document).on( 'click', '#place_order', function(){
		if ( ! jQuery('#payment_method_stripe').is(':checked') ) {
			return true;
		}
		if ( jQuery('input[name=stripe_customer_id]').length > 0 && jQuery('input[name=stripe_customer_id]:checked').val() != 'new' ) {
			return true;
		}
		if ( jQuery('input#terms').size() === 1 && jQuery('input#terms:checked').size() === 0 ) {
			alert( wc_stripe_params.i18n_terms );
			return false;
		}

		var $form = jQuery("form.checkout, form#order_review");
		var $stripe_new_card = jQuery( '.stripe_new_card' );
		var token = $form.find('input.stripe_token');

		token.val('');

		var token_action = function( res ) {
			$form.find('input.stripe_token').remove();
			$form.append("<input type='hidden' class='stripe_token' name='stripe_token' value='" + res.id + "'/>");
			$form.submit();
		};

		StripeCheckout.open({
			key:         wc_stripe_params.key,
			address:     false,
			amount:      $stripe_new_card.data( 'amount' ),
			name:        $stripe_new_card.data( 'name' ),
			description: $stripe_new_card.data( 'description' ),
			panelLabel:  $stripe_new_card.data( 'label' ),
			currency:    $stripe_new_card.data( 'currency' ),
			image:       $stripe_new_card.data( 'image' ),
			email: 		 jQuery('#billing_email').val(),
			token:       token_action
		});

		return false;
    });

    var eventList = jQuery._data( jQuery(document)[0], "events" );
	eventList.click.unshift( eventList.click.pop() );
} );