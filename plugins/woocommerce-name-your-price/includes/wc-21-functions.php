<?php
/**
 * NYP WC 2.1 Compatibility Functions
 * @version 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function wc_nyp_get_template( $file, $data, $empty, $path ) {

	return wc_get_template( $file, $data, $empty, $path );
}

function wc_nyp_price( $arg ) {

	return wc_price( $arg );
}

function wc_nyp_clean( $arg ) {

	return wc_clean( $arg );
}

function wc_nyp_add_notice( $message, $notice_type ) {

	return wc_add_notice( $message, $notice_type );
}

function wc_nyp_format_localized_price( $price ){
	
	return wc_format_localized_price( $price );
}

function wc_nyp_format_decimal( $price ){
	
	return wc_format_decimal( $price );
}

function wc_nyp_trim_zeros( $price ){
	
	return wc_trim_zeros( $price );
}