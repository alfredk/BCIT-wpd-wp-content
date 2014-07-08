<?php

class WC_Activation_Reset_Request extends WC_Software_API_Request {
	public function do_request() {
		global $wc_software;

		$required = array( 'email', 'licence_key', 'product_id' );
		$this->check_required( $required );

		$input = $this->check_input( array( 'email', 'licence_key', 'product_id' ) );

		// Validate email
		if ( ! is_email( $input['email'] ) )
			$this->wc_software_api->error( '100', __( 'The email provided is invalid', 'wc_software' ), null, array( 'reset' => false ) );

		$data = $wc_software->get_licence_key( $input['licence_key'], $input['product_id'], $input['email'] );

		if ( ! $data )
			$this->wc_software_api->error( '101', __( 'No matching licence key exists', 'wc_software' ), null, array( 'activated' => false ) );

		// reset number of activations
		if ( $wc_software->deactivate_licence_key( $data->key_id ) ) {

            // Reset the platforms for all activations for this key
            $wc_software->reset_platform_for_key( $data->key_id );

            // Prepare the output data
			$output_data = get_object_vars( $data );
			$output_data['reset'] = true;
			$output_data['timestamp'] = time();
			$to_output = array();
			$to_output['reset'] = 'reset';
			$to_output['timestamp'] = 'timestamp';
			$json = $this->prepare_output( $to_output, $output_data );
			return $json;
		
		} else {
			$this->wc_software_api->error( '100', __( 'An undisclosed error occurred', 'wc_software' ), null, array( 'reset' => false ) );
		}
	}
}

?>