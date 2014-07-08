<?php
/**
 * WC_Software_API class.
 * 
 * @extends WooCommerce_Software
 */
class WC_Software_API {
	public $debug;
	private $available_requests = array();

	public function __construct( $debug = false ) {
		$this->debug = ( WP_DEBUG ) ? true : $debug; // always on if WP_DEBUG is on

		$this->load_available_requests();

		if ( isset( $_REQUEST['request'] ) ) {
			
			$request = $_REQUEST['request'];

			if ( isset( $this->available_requests[ $request ] ) ) {
				$json = $this->available_requests[ $request ]->do_request();
			}

			if ( ! isset( $json ) ) $this->error( '100', __( 'Invalid API Request', 'wc_software' ) );
			
		} else {
			$this->error( '100', __( 'No API Request Made', 'wc_software' ) );
		}

		nocache_headers();
		header( 'Content-Type: application/json' );
		die( json_encode( $json ) );
	}

	private function load_available_requests() {
		require_once( 'class-wc-software-api-request.php' );
		
		require( 'requests/class-wc-generate-key-request.php' );
		require( 'requests/class-wc-check-request.php' );
		require( 'requests/class-wc-activation-request.php' );
		require( 'requests/class-wc-activation-reset-request.php' );
		require( 'requests/class-wc-deactivation-request.php' );

		$this->available_requests['generate_key'] = new WC_Generate_Key_Request( $this );
		$this->available_requests['check'] = new WC_Check_Request( $this );
		$this->available_requests['activation'] = new WC_Activation_Request( $this );
		$this->available_requests['activation_reset'] = new WC_Activation_Reset_Request( $this );
		$this->available_requests['deactivation'] = new WC_Deactivation_Request( $this );
	}

	public function error( $code = 100, $debug_message = null, $secret = null, $addtl_data = array() ) {
		switch ( $code ) {
			case '101' :
				$error = array( 'error' => __( 'Invalid License Key', 'wc_software' ), 'code' => '101' );
				break;
			case '102' :
				$error = array( 'error' => __( 'Software has been deactivated', 'wc_software' ), 'code' => '102' );
				break;
			case '103' :
				$error = array( 'error' => __( 'Exceeded maximum number of activations', 'wc_software' ), 'code' => '103' );
				break;
			case '104' :
				$error = array( 'error' => __( 'Invalid Instance ID', 'wc_software' ), 'code' => '104' );
				break;
			case '105' :
				$error = array( 'error' => __( 'Invalid security key', 'wc_software' ), 'code' => '105' );
				break;
			default :
				$error = array( 'error' => __( 'Invalid Request', 'wc_software' ), 'code' => '100' );
				break;
		}

		if ( isset( $this->debug ) && $this->debug ) {
			if ( ! isset( $debug_message ) || ! $debug_message ) $debug_message = __( 'No debug information available', 'wc_software' );
			$error['additional info'] = $debug_message;
		}

		if ( isset( $addtl_data['secret'] ) ) {
			$secret = $addtl_data['secret'];
			unset( $addtl_data['secret'] );
		}

		foreach ( $addtl_data as $k => $v ) {
			$error[ $k ] = $v;
		}

		$secret = ( $secret ) ? $secret : 'null';
		$error['timestamp'] = time();

		foreach ( $error as $k => $v ) {
			if ( $v === false ) $v = 'false';
			if ( $v === true ) $v = 'true';
			$sigjoined[] = "$k=$v";
		}

		$sig = implode( '&', $sigjoined );
		$sig = 'secret=' . $secret . '&' . $sig;

		if ( !$this->debug ) $sig = md5( $sig );

		$error['sig'] = $sig;
		$json = $error;

		nocache_headers();
		header( 'Content-Type: application/json' );

		die( json_encode( $json ) );
		exit;
	}
}

$GLOBALS['wc_software_api'] = new WC_Software_API(); // run the API
