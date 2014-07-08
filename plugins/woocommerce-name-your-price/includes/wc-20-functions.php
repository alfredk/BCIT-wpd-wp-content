<?php
/**
 * NYP WC 2.0 Compatibility Functions
 * @version 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function wc_nyp_get_template( $file, $data, $empty, $path ) {

	return woocommerce_get_template( $file, $data, $empty, $path );
}

function wc_nyp_price( $arg ) {

	return woocommerce_price( $arg );
}

function wc_nyp_clean( $arg ) {

	return woocommerce_clean( $arg );
}

function wc_nyp_add_notice( $message, $notice_type ) {

	global $woocommerce;

	if ( $notice_type == 'success' || $notice_type == 'notice' )
		return $woocommerce->add_message( $message );
	elseif ( $notice_type == 'error' )
		return $woocommerce->add_error( $message );
}

function wc_nyp_format_localized_price( $price ){
	
	return $price;
}

function wc_nyp_format_decimal( $price ){
	
	return woocommerce_format_decimal( $price );
}

function wc_nyp_trim_zeros( $price ){
	
	return woocommerce_trim_zeros( $price );
}