<?php

class WC_Software_API_Request {
	protected $wc_software_api;

	public function __construct( $wc_software_api ) {
		$this->wc_software_api = $wc_software_api;
	}

	protected function check_required( $required ) {
		$i = 0;
		$missing = '';

		foreach ( $required as $req ) {
			if ( ! isset( $_REQUEST[ $req ] ) || $req == '' ) {
				$i++;
				if ( $i > 1 ) $missing .= ', ';
				$missing .= $req;
			}
		}

		if ( $missing != '' ) {
			$this->wc_software_api->error( '100', __( 'The following required information is missing', 'wc_software' ) . ': ' . $missing, null, array( 'activated' => false ) );
		}
	}

	protected function check_input( $input ) {
		$return = array();

		foreach ( $input as $key ) {
			$return[ $key ] = ( isset( $_REQUEST[ $key ] ) ) ? $_REQUEST[ $key ] : '';
		}

		return $return;
	}

	protected function prepare_output( $to_output = array(), $data = array() ) {
		$secret = ( isset( $data->secret_key ) ) ? $data->secret_key : 'null';
		$sig_array = array( 'secret' => $secret );

		foreach ( $to_output as $k => $v ) {
			if ( isset( $data[ $v ] ) ) {
				if ( is_string( $k ) ) {
					$output[ $k ] = $data[ $v ];
				} else {
					$output[ $v ] = $data[ $v ];
				}
			}
		}

		$sig_out = $output;
		$sig_array = array_merge( $sig_array, $sig_out );

		foreach ( $sig_array as $k => $v ) {
			if ( $v === false ) $v = 'false';
			if ( $v === true ) $v = 'true';
			$sigjoined[] = "$k=$v";
		}

		$sig = implode( '&', $sigjoined );

		$output['sig'] = $sig;
		return $output;
	}
}

?>