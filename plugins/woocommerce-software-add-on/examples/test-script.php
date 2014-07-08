<?php
$wp_root = dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) );

if ( file_exists( $wp_root . '/wp-load.php' ) ) {
	require_once( $wp_root . "/wp-load.php" );
} else {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) die();

/*
 * WooCommerce Software Add-On testing script
 * Author: WooThemes
 */

// API variables, please override via test-script-local.php file
$base_url      = '';
$email         = '';
$format        = '';
$product_id    = '';
$licence_key   = '';
$instance      = '';
$secret_key    = '';
$order_id	   = '';

include( 'test-script-local.php' );

// Fire away!
function execute_request( $args ) {
	$target_url = create_url( $args );
	$data = wp_remote_get( $target_url );
	var_dump( $data['body'] );
}

// Create an url based on 
function create_url( $args ) {
	global $base_url;
	
	$base_url = add_query_arg( 'wc-api', 'software-api', $base_url );
	
	return $base_url . '&' . http_build_query( $args );
}

$request = ( isset( $_GET['request'] ) ) ? $_GET['request'] : '';

$links = array(
	'invalid' => 'Invalid request',
	'generate_key' => 'Generate key',
	'check' => 'Check request',
	'activation' => 'Activation request',
	'activation_reset' => 'Application reset',
	'deactivation' => 'Deactivation'
);

foreach ( $links as $key => $value ) {
	echo '<a href="' . add_query_arg( 'request', $key ) . '">' . $value . '</a> | ';
}

echo '<br/><br/>';

//
// Invalid request
if ( $request == 'invalid' ) {
	$args = array(
		'request' => 'invalid',
	);
	
	echo '<b>Invalid request:</b><br />';
	execute_request( $args );
}

//
// Valid generate key request
if ( $request == 'generate_key' ) {
	$args = array(
		'wc-api'	 => 'software-api',
		'request'    => 'generate_key',
		'product_id' => $product_id,
		'secret_key' => $secret_key,
		'order_id'	 => $order_id,
		'email'		 => $email
	);
	
	echo '<b>Valid generate key request:</b><br />';
	execute_request( $args );
}

//
// Valid check request
if ( $request == 'check' ) {
	$args = array(
		'wc-api'	  => 'software-api',
		'request'     => 'check',
		'email'		  => $email,
		'licence_key' => $licence_key,
		'product_id'  => $product_id
	);
	
	echo '<b>Valid check request:</b><br />';
	execute_request( $args );
}

//
// Valid activation request
if ( $request == 'activation' ) {
	$args = array(
		'request'     => 'activation',
		'email'       => $email,
		'licence_key' => $licence_key,
		'product_id'  => $product_id,
		'secret_key'  => $secret_key,
		'instance' 	  => $instance
	);
	
	echo '<b>Valid activation request:</b><br />';
	execute_request( $args );
}

//
// Valid activation reset request
if ( $request == 'activation_reset' ) {
	$args = array(
		'request'     => 'activation_reset',
		'email'       => $email,
		'product_id'  => $product_id,
		'licence_key' => $licence_key,
		'secret_key'  => $secret_key,
	);
	
	echo '<b>Valid activation reset request:</b><br />';
	execute_request( $args );
}

//
// Valid deactivation reset request
if ( $request == 'deactivation' ) {
	$args = array(
		'request'       => 'deactivation',
		'email'         => $email,
		'licence_key'   => $licence_key,
		'product_id'  	=> $product_id,
		'instance'      => $instance,
	);
	
	echo '<b>Valid deactivation request:</b><br />';
	execute_request( $args );
}
