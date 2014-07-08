<?php

class WC_Deactivation_Request extends WC_Software_API_Request {
	public function do_request() {
		global $wc_software;

		$required = array( 'email', 'licence_key', 'instance' );
		$this->check_required( $required );

		$input = $this->check_input( array( 'email', 'licence_key', 'product_id', 'version', 'platform', 'instance' ) );

		// Validate email
		if ( ! is_email( $input['email'] ) )
			$this->wc_software_api->error( '100', __( 'The email provided is invalid', 'wc_software' ), null, array( 'reset' => false ) );

		$data = $wc_software->get_licence_key( $input['licence_key'], $input['product_id'], $input['email'] );

		if ( ! $data )
			$this->wc_software_api->error( '101', __( 'No matching licence key exists', 'wc_software' ), null, array( 'activated' => false ) );

		// reset number of activations
		$is_deactivated = $wc_software->deactivate_licence_key( $data->key_id, $input['instance'] );

		if ( !$is_deactivated )
			$this->wc_software_api->error( '104', __( 'No matching instance exists', 'wc_software' ), null, array( 'activated' => false ) );

		$output_data = get_object_vars( $data );
		$output_data['reset'] = true;
		$output_data['timestamp'] = time();
		$to_output = array();
		$to_output['reset'] = 'reset';
		$to_output['timestamp'] = 'timestamp';
		$json = $this->prepare_output( $to_output, $output_data );
		return $json;
	}
}