<?php

/**
 * WC_Check_Request class.
 * 
 * @extends WC_Software_API_Request
 */
class WC_Check_Request extends WC_Software_API_Request {
	
	/**
	 * do_request function.
	 * 
	 * @access public
	 */
	public function do_request() {
		global $wc_software;
		
		$this->check_required( array( 'email', 'licence_key', 'product_id' ) );

		$input = $this->check_input( array( 'email', 'licence_key', 'product_id' ) );

		// Validate email
		if ( ! is_email( $input['email'] ) ) 
			$this->wc_software_api->error( '100', __( 'The email provided is invalid', 'wc_software' ), null, array( 'success' => false ) );
		
		// Check if the licence key is valid for this user and get the key
		$data = $wc_software->get_licence_key( $input['licence_key'], $input['product_id'], $input['email'] );
		
		if ( ! $data ) 
			$this->wc_software_api->error( '101', __( 'No matching licence key exists', 'wc_software' ), null, array( 'success' => false ) );
			
		// Validate order if set
		if ( $data->order_id ) {
			$order_status = wp_get_post_terms( $data->order_id, 'shop_order_status' );
			$order_status = $order_status[0]->slug;
			if ( $order_status != 'completed' )
				$this->wc_software_api->error( '102', __( 'The purchase matching this product is not complete', 'wc_software' ), null,  array( 'success' => false ) );
		}
		
		// Check was successful - return json
		$output_data = get_object_vars( $data );
		$platform = $wc_software->get_platform( $data->key_id );
		
		$output_data['success'] = true;
		$output_data['time'] = time();
		$output_data['remaining'] = $wc_software->activations_remaining( $data->key_id );
		$output_data['platform'] = ( empty( $platform ) ) ? '' : $platform;
		
		$to_output = array( 'success' );
		$to_output['message'] = 'message';
		$to_output['timestamp'] = 'time';
		$to_output['remaining'] = 'remaining';
		$to_output['platform'] = 'platform';
		
		return $this->prepare_output( $to_output, $output_data );
	}
	
}
