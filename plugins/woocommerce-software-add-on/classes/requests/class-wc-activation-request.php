<?php

/**
 * WC_Activation_Request class.
 * 
 * @extends WC_Software_API_Request
 *
 * @todo Email customer upon activation
 */
class WC_Activation_Request extends WC_Software_API_Request {
	
	/**
	 * do_request function.
	 * 
	 * @access public
	 */
	public function do_request() {
		global $wc_software;
		
		$this->check_required( array( 'email', 'licence_key', 'product_id' ) );

		$input = $this->check_input( array( 'email', 'licence_key', 'product_id', 'version', 'platform', 'secret_key', 'instance' ) );
		
		// Validate email
		if ( ! is_email( $input['email'] ) ) 
			$this->wc_software_api->error( '100', __( 'The email provided is invalid', 'wc_software' ), null, array( 'activated' => false ) );
		
		// Check if the licence key is valid for this user and get the key
		$data = $wc_software->get_licence_key( $input['licence_key'], $input['product_id'], $input['email'] );
		
		if ( ! $data ) 
			$this->wc_software_api->error( '101', __( 'No matching licence key exists', 'wc_software' ), null, array( 'activated' => false ) );
			
		// Validate order if set
		if ( $data->order_id ) {
			$order_status = wp_get_post_terms( $data->order_id, 'shop_order_status' );
			$order_status = $order_status[0]->slug;
			if ( $order_status != 'completed' )
				$this->wc_software_api->error( '102', __( 'The purchase matching this product is not complete', 'wc_software' ), null,  array( 'activated' => false ) );
		}
		
		// Check remaining activations
		$activations_remaining = $wc_software->activations_remaining( $data->key_id );
		
		if ( ! $activations_remaining )
			$this->wc_software_api->error( '103', __( 'Remaining activations is equal to zero', 'wc_software' ), null, array( 'activated' => false ) );
			
		// Activation
		$result = $wc_software->activate_licence_key( $data->key_id, $input['instance'], $input['platform'] );
		
		if ( ! $result )
			$this->wc_software_api->error( '104', __( 'Could not activate key', 'wc_software' ), null, array( 'activated' => false ) );
		
		// Check remaining activations
		$activations_remaining = $wc_software->activations_remaining( $data->key_id );
		
		// Activation was successful - return json
		$output_data = get_object_vars( $data );
		
		$output_data['activated'] = true;
		$output_data['instance'] = $input['instance'];
		$output_data['message'] = sprintf( __( '%s out of %s activations remaining', 'wc_software' ), $activations_remaining, $data->activations_limit );
		$output_data['time'] = time();
		
		$to_output = array( 'activated', 'instance' );
		$to_output['message'] = 'message';
		$to_output['timestamp'] = 'time';
		
		return $this->prepare_output( $to_output, $output_data );
	}
	
}