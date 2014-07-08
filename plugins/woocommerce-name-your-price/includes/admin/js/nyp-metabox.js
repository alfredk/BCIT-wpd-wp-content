;(function($){

	$.extend({
		moveNYPmetaFields: function(){
			$( '.options_group.show_if_nyp' ).insertBefore( '.options_group.pricing' );
		},
		addClasstoRegularPrice: function(){
			$( '.options_group.pricing' ).addClass( 'hide_if_nyp' );
		},
		toggleRegularPriceClass: function( is_nyp ){
			if( is_nyp ){
				$( '.options_group.pricing' ).removeClass( 'show_if_simple' );
			} else {
				$( '.options_group.pricing' ).addClass( 'show_if_simple' );
			}
		},
		showHideNYPelements: function(){
			var product_type = $( 'select#product-type' ).val();
			var is_nyp = $( '#_nyp:checked' ).size();

			$.toggleRegularPriceClass( is_nyp );

			switch ( product_type ) {
				case 'subscription' :
						$.showHideNYPprices( is_nyp, true );
						$.enableDisableSubscriptionPrice( is_nyp );
						var is_variable_billing = $( '#_variable_billing:checked' ).size();
						$.showHideNYPvariablePeriods( is_variable_billing );
						$.enableDisableSubscriptionPeriod( is_variable_billing );
					break;
				case 'simple':
				case 'bundle':
				case 'bto':
					$.showHideNYPprices( is_nyp, true );
					$.showHideNYPvariablePeriods( false )
					break;
				case 'variable':
					$.showHideNYPprices( false );
					$.moveNYPvariationFields();
					$.showHideNYPmetaforVariableProducts();
					break;
				case 'variable-subscription':
					$.showHideNYPprices( false );
					$.moveNYPvariationFields();
					$.showHideNYPmetaforVariableSubscriptions();
					break;
				default :
					$.showHideNYPprices( false );
				break;
			}
		},
		showHideNYPprices: function( show, restore ) {
			// for simple and sub types we'll want to restore the regular price inputs
			restore = typeof restore !== 'undefined' ? restore : false;

			if ( show ) {
				$( '.show_if_nyp' ).show();
				$( '.hide_if_nyp' ).hide();
			} else {
				$( '.show_if_nyp' ).hide();
				if ( restore )
					$( '.hide_if_nyp' ).show();
			}
		},
		enableDisableSubscriptionPrice: function( enable ){
			if( enable ){
				$( '#_subscription_price' ).prop( 'disabled', true ).css( 'background', '#CCC' );
			} else {
				$( '#_subscription_price' ).prop( 'disabled', false ).css( 'background', '#FFF' );
			}
		},
		showHideNYPvariablePeriods: function( show ) {
			$variable_periods = $( '._suggested_billing_period_field, ._minimum_billing_period_field' );
			if( show ){
				$variable_periods.show();
			} else {
				$variable_periods.hide();
			}
		},
		enableDisableSubscriptionPeriod: function( disable ){
			$subscription_period = $( '#_subscription_period_interval, #_subscription_period' );
			if( disable ){
				$subscription_period.prop( 'disabled', true ).css( 'background','#CCC' );
			} else {
				$subscription_period.prop( 'disabled', false ).css( 'background', '#FFF' );
			}
		},
		addClasstoVariablePrice: function(){
			$( '.woocommerce_variation .variable_pricing' ).addClass( 'hide_if_variable_nyp' );
		},
		moveNYPvariationFields: function(){
			$( '#variable_product_options tr.variable_nyp_pricing' ).not( '.nyp_moved' ).each(function(){
				$(this).insertBefore($(this).siblings( 'tr.variable_pricing' )).addClass( 'nyp_moved' );
			});
		},
		showHideNYPvariableMeta: function(){
			if ( $( '#product-type' ).val() == 'variable-subscription' ) {
				$.showHideNYPmetaforVariableSubscriptions();
			} else {
				$.showHideNYPmetaforVariableProducts();
			}
		},
		showHideNYPmetaforVariableProducts: function(){

			$( '.variation_is_nyp' ).each( function( index ) {

				var $variable_pricing = $(this).closest( '.woocommerce_variation' ).find( '.variable_pricing' );

				var $nyp_pricing = $(this).closest( '.woocommerce_variation' ).find( '.variable_nyp_pricing' );

				// hide or display on load
				if ( $(this).is( ':checked' ) ) {
					$nyp_pricing.show();
					$variable_pricing.hide();

				} else {
					$nyp_pricing.hide();
					$variable_pricing.removeAttr( 'style' );

				}

			});

		},
		showHideNYPmetaforVariableSubscriptions: function(){

			$( '.variation_is_nyp' ).each( function( index ) {
				var $variable_pricing = $(this).closest( '.woocommerce_variation' ).find( '.variable_pricing' );
				var $variable_subscription_price = $(this).closest( '.woocommerce_variation' ).find( '.wc_input_subscription_price' );

				var $nyp_pricing = $(this).closest( '.woocommerce_variation' ).find( '.variable_nyp_pricing' );

				if ( $(this).is( ':checked' ) ) {
					$nyp_pricing.show();
					$variable_subscription_price.prop( 'disabled', true ).css( 'background','#CCC' );
					$variable_pricing.children().not( '.show_if_variable-subscription' ).hide();
				} else {
					$nyp_pricing.hide();
					$variable_subscription_price.prop( 'disabled', false ).css( 'background', '#FFF' );
					$variable_pricing.children().not( '.hide_if_variable-subscription' ).show();
				}

			});

		}
	} ); //end extend


	// magically move the simple inputs into the sample location as the normal pricing section
	if( $( '.options_group.pricing' ).length > 0) {
		$.moveNYPmetaFields();
		$.addClasstoRegularPrice();
		$.showHideNYPelements();
	}

	// adjust fields when the product type is changed
	$( 'body' ).on( 'woocommerce-product-type-change',function(){
		$.showHideNYPelements();
	});

	// adjust the fields when NYP status is changed
	$( 'input#_nyp' ).on( 'change', function(){
		$.showHideNYPelements();
	});

	// adjust the fields when variable billing period status is changed
	$( '#_variable_billing' ).on( 'change', function(){
		$.showHideNYPvariablePeriods( this.checked );
		$.enableDisableSubscriptionPeriod( this.checked );
	});

	//handle variable products on load
	if($( '#variable_product_options .woocommerce_variation' ).length > 0) {
		$.addClasstoVariablePrice();
		$.moveNYPvariationFields();
		$.showHideNYPvariableMeta();
	}

	// When a variation is added
	$( '#variable_product_options' ).on( 'woocommerce_variations_added',function(){
		$.addClasstoVariablePrice();
		$.moveNYPvariationFields();
		$.showHideNYPvariableMeta();
	});

	// hide/display variable nyp prices on single nyp checkbox change
	$( '#variable_product_options' ).on( 'change', '.variation_is_nyp', function(event){
		$.showHideNYPvariableMeta();
	});

	// hide/display variable nyp prices on bulk nyp checkbox change
	$( 'select#field_to_edit' ).on( 'woocommerce_variable_bulk_nyp_toggle', function(event){
		$.showHideNYPvariableMeta();
	});

	/*
	* Bulk Edit callbacks
	*/

	// toggle all variations to NYP
	$( 'select#field_to_edit' ).on( 'toggle_nyp', function(){
		var checkbox = $( 'input[name^="variation_is_nyp"]' );
		checkbox.attr( 'checked', !checkbox.attr( 'checked' ));
		$( 'select#field_to_edit' ).trigger( 'woocommerce_variable_bulk_nyp_toggle' );
	});

	// set all suggestd prices
	$( 'select#field_to_edit' ).on( 'variable_suggested_price', function(){

		var input_tag = $( 'select#field_to_edit :selected' ).attr( 'rel' ) ? $( 'select#field_to_edit :selected' ).attr( 'rel' ) : 'input';

		var value = prompt(woocommerce_nyp_metabox.enter_value);
		$(input_tag + '[name^="' + field_to_edit + '["]' ).val( value ).change();

	});

	// increase all suggested prices
	$( 'select#field_to_edit' ).on( 'variable_suggested_price_increase', function(){
		field_to_edit = 'variable_suggested_price';
		var input_tag = $( 'select#field_to_edit :selected' ).attr( 'rel' ) ? $( 'select#field_to_edit :selected' ).attr( 'rel' ) : 'input';

		var value = prompt(woocommerce_nyp_metabox.price_adjust);
		$(input_tag + '[name^="' + field_to_edit + '"]' ).each(function() {
		var current_value = $(this).val();

			if ( value.indexOf("%") >= 0 ) {
				var new_value = Number( current_value ) + ( ( Number( current_value ) / 100 ) * Number( value.replace(/\%/, "" ) ) );
			} else {
				var new_value = Number( current_value ) + Number ( value );
			}
			$(this).val( new_value ).change();
		});
	});

	// decrease all suggested prices
	$( 'select#field_to_edit' ).on( 'variable_suggested_price_decrease', function(){
		field_to_edit = 'variable_suggested_price';
		var input_tag = $( 'select#field_to_edit :selected' ).attr( 'rel' ) ? $( 'select#field_to_edit :selected' ).attr( 'rel' ) : 'input';

		var value = prompt(woocommerce_nyp_metabox.price_adjust);
		$(input_tag + '[name^="' + field_to_edit + '"]' ).each(function() {
			var current_value = $(this).val();

			if ( value.indexOf("%") >= 0 ) {
				var new_value = Number( current_value ) - ( ( Number( current_value ) / 100 ) * Number( value.replace(/\%/, "" ) ) );
			} else {
				var new_value = Number( current_value ) - Number ( value );
			}
			$(this).val( new_value ).change();
		});
	});

	// set all minimum prices
	$( 'select#field_to_edit' ).on( 'variable_minimum_price', function(){
		var input_tag = $( 'select#field_to_edit :selected' ).attr( 'rel' ) ? $( 'select#field_to_edit :selected' ).attr( 'rel' ) : 'input';

		var value = prompt(woocommerce_nyp_metabox.enter_value);
		$(input_tag + '[name^="' + field_to_edit + '["]' ).val( value ).change();
	});

	// increase all minimum prices
	$( 'select#field_to_edit' ).on( 'variable_minimum_price_increase', function(){
		field_to_edit = 'variable_minimum_price';
		var input_tag = $( 'select#field_to_edit :selected' ).attr( 'rel' ) ? $( 'select#field_to_edit :selected' ).attr( 'rel' ) : 'input';

		var value = prompt(woocommerce_nyp_metabox.price_adjust);
		$(input_tag + '[name^="' + field_to_edit + '"]' ).each(function() {
			var current_value = $(this).val();

			if ( value.indexOf("%") >= 0 ) {
				var new_value = Number( current_value ) + ( ( Number( current_value ) / 100 ) * Number( value.replace(/\%/, "" ) ) );
			} else {
				var new_value = Number( current_value ) + Number ( value );
			}
			$(this).val( new_value ).change();
		});
	});

	// decrease all minimu prices
	$( 'select#field_to_edit' ).on( 'variable_minimum_price_decrease', function(){
		field_to_edit = 'variable_minimum_price';
		var input_tag = $( 'select#field_to_edit :selected' ).attr( 'rel' ) ? $( 'select#field_to_edit :selected' ).attr( 'rel' ) : 'input';

		var value = prompt(woocommerce_nyp_metabox.price_adjust);
		$(input_tag + '[name^="' + field_to_edit + '"]' ).each(function() {
			var current_value = $(this).val();

			if ( value.indexOf("%") >= 0 ) {
				var new_value = Number( current_value ) - ( ( Number( current_value ) / 100 ) * Number( value.replace(/\%/, "" ) ) );
			} else {
				var new_value = Number( current_value ) - Number ( value );
			}
			$(this).val( new_value ).change();
		});
	});


})(jQuery); //end