jQuery(document).ready( function($) {

	/*
	 * woocommerce_nyp_update function
	 * wraps all important nyp callbacks for plugins that maybe don't have elements available on load
	 * ie: quickview, bundles, etc
	 */ 
	function woocommerce_nyp_update() {

		// automatically put cursor in NYP input
		$('input.nyp-input').eq(0).focus();

		/*
		 * Name Your Price Handler for individual items
		 */ 
		$( '.cart' ).on( 'woocommerce-nyp-update', function() { 

			// some important objects
			var $cart 			= $(this);
			var $nyp 			= $cart.find( '.nyp' );
			var $nyp_input 	= $cart.find( '.nyp-input' );
			var $submit = $cart.find(':submit');

			// the current price
			var form_price 	= $nyp_input.val();
			
			// add a div to hold the error message
			var $error = $cart.find( '.woocommerce-nyp-message' );

			if ( ! $error.length )
				$nyp.prepend( '<div class="woocommerce-nyp-message woocommerce-error" style="display:none"></div>' );

			// the default error message
			var error_message = woocommerce_nyp_params.minimum_error;
			var error = false;

			// convert price to default decimal setting for calculations
			var form_price_num 	= woocommerce_nyp_unformat_price( form_price ); 

			var min_price 			= parseFloat( $nyp.data( 'min-price' ) );
			var annual_minimum	= parseFloat( $nyp.data( 'annual-minimum' ) );

			// get variable billing period data
			var $nyp_period		= $cart.find( '.nyp-period' );
			var form_period		= $nyp_period.val();

			// if has variable billing period AND a minimum then we need to annulalize min price for comparison
			if ( annual_minimum > 0 ){

				// calculate the price over the course of a year for comparison
				form_annulualized_price = form_price_num * woocommerce_nyp_params.annual_price_factors[form_period];

				// if the calculated annual price is less than the annual minimum
				if( form_annulualized_price < annual_minimum ){

				//	form_price_num = annual_minimum / woocommerce_nyp_params.annual_price_factors[form_period]; // this would set the input automatically to the minimum

					// in the case of variable period we need to adjust the error message a bit
					error_message = error_message + ' / ' + $nyp_period.find('option[value="' + form_period + '"]').text();

					error = annual_minimum / woocommerce_nyp_params.annual_price_factors[form_period];


				}

			// otherwise a regular product or subscription with non-variable periods
			// compare price directly
			} else if ( form_price_num < min_price ) {

				// form_price_num = min_price; // this would set the input automatically to the minimum
				
				error = min_price;
				
			}

			// set the input with the properly formatted value
			$nyp_input.val( woocommerce_nyp_format_price( form_price_num ) );

			// if we've set an error, show message and prevent submit
			if ( error ){

				// disable submit
				$nyp.data( 'submit', false ); 

				// show error
				error_message = error_message.replace( "%s", woocommerce_nyp_format_price( error, woocommerce_nyp_params.currency_symbol ) );

				$error.html(error_message).show(); 

				// re-focus on price input
				$nyp_input.focus();

			// otherwise allow submit and update
			} else {

				// allow submit
				$nyp.data( 'submit', true ); 

				// remove error
				$error.fadeOut('slow'); 

				// product add ons compatibility
				$(this).find( '#product-addons-total' ).data( 'price', form_price_num );
				$cart.trigger( 'woocommerce-product-addons-update' );

				// bundles compatibility
				$nyp.data( 'price', form_price_num );
				$cart.trigger( 'woocommerce-nyp-updated-item' );
				$('body').trigger( 'woocommerce-nyp-updated' );

			}

		} ); // end woocommerce-nyp-update handler

		// nyp update on change to any nyp input
		$( '.cart' ).on( 'change', [ '.nyp-input', '.nyp-period' ], function() {
			var $cart = $(this).closest( '.cart' );
			$cart.trigger( 'woocommerce-nyp-update' ); 
		});

		// add to cart on submit, don't submit if minimum wasn't valid
		$( '.cart' ).on( 'submit', function() { 

			// if submit is not allowed, don't submit
			if ( $(this).find('.nyp').data( 'submit' ) === false ){ 
				event.preventDefault();
				//re-enable submit
				$(this).find(':submit').removeAttr( 'disabled' );
			} 

		});

		// trigger right away
		$( '.cart input.nyp-input' ).trigger('change');

		/*
		 * Handle NYP Variations
		 */
		$('.variations_form').each( function() {
			
			// some important objects
			var $variation_form = $(this);
			var $add_to_cart = $(this).find('button.single_add_to_cart_button');
			var $nyp = $(this).find('.nyp');
			var $nyp_input = $nyp.find('.nyp-input');
			var $minimum = $nyp.find( '.minimum-price' );

			// the add to cart text
			var default_add_to_cart_text = $add_to_cart.html();
			var nyp_add_to_cart_text = woocommerce_nyp_params.add_to_cart_text;

			// hide the nyp form by default
			$nyp.hide();
			$minimum.hide();

			// listeners
			$variation_form

			// when variation is found, decide if it is NYP or not
			.on( 'found_variation', function( event, variation ) {

				// switch add to cart button text if variation is NYP
				$add_to_cart.html(nyp_add_to_cart_text);

				// if NYP show the price input and tweak the data attributes
				if ( typeof variation.is_nyp != undefined && variation.is_nyp === true ){

					posted_price = variation.posted_price ? variation.posted_price : 0;
					minimum_price = variation.minimum_price ? variation.minimum_price : 0;
					minimum_price_html = variation.minimum_price_html ? variation.minimum_price_html : 0;

					$nyp_input.focus().val( woocommerce_nyp_format_price( posted_price ) );

					if( minimum_price_html ){
						$minimum.html ( minimum_price_html ).show();
					}

					$nyp.data( 'min-price', minimum_price ).slideDown('200');

				// if not NYP, hide the price input
				} else {

					// use default add to cart button text if variation is not NYP
					$add_to_cart.html(default_add_to_cart_text);

					// hide
					$nyp.slideUp('200');

				}

			})

			// hide the price input when reset is clicked
			.on( 'click', '.reset_variations', function( event ) {
				$add_to_cart.html(default_add_to_cart_text);
				$nyp.slideUp('200');
			});

		});

		// need to re-trigger some things on load since Woo unbinds the found_variation event
		$('.variations_form').find('.variations select').trigger('change');

	} // end woocommerce_nyp_update()



	
	/*
	 * run when Quick view item is launched
	 */
	$('body').on('quick-view-displayed', function() {
		woocommerce_nyp_update();
	});

	/*
	 * run on load
	 */
	woocommerce_nyp_update();


	/*
	 * helper functions
	 */
	// format the price with accounting
	function woocommerce_nyp_format_price( price, currency_symbol ){

		if ( typeof currency_symbol === 'undefined' )
			currency_symbol = '';

		return accounting.formatMoney( price, currency_symbol, woocommerce_nyp_params.currency_format_num_decimals, woocommerce_nyp_params.currency_format_thousand_sep, woocommerce_nyp_params.currency_format_decimal_sep );

	}

	// turn price into standard decimal
	function woocommerce_nyp_unformat_price( price ){

		return parseFloat( accounting.unformat( price, woocommerce_nyp_params.currency_format_decimal_sep ) );

	}	

} );