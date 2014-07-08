<?php

class WC_Generate_Key_Request extends WC_Software_API_Request {
	public function do_request() {
		global $wc_software, $wpdb;
		
		$this->check_required( array( 'product_id', 'secret_key', 'email' ) );

		$input = $this->check_input( array( 'product_id', 'secret_key', 'email', 'order_id', 'version', 'key_prefix', 'activations' ) );
			
		if ( $wc_software->check_product_secret( $input['product_id'], $input['secret_key'] ) ) {
			
			$key_prefix 	= $input['key_prefix'];
			$key 			= $wc_software->generate_licence_key();
			$version 		= $input['version'];
			$activations 	= $input['activations'];
			
			// Get product details
			$product_id = $wpdb->get_var( $wpdb->prepare( "
				SELECT post_id FROM {$wpdb->postmeta} 
				WHERE meta_key = '_software_product_id' 
				AND meta_value = %s LIMIT 1
			", $input['product_id'] ) );
			
			if ( $product_id ) {
			
				$meta = get_post_custom( $product_id );
				
				if ( ! $key_prefix ) $key_prefix = $meta['_software_license_key_prefix'][0];
				if ( ! $version ) $version = $meta['_software_version'][0];
				if ( ! $activations ) $activations = $meta['_software_activations'][0];
				
			}
						
			$data = array(
				'order_id' 				=> $input['order_id'],
				'activation_email'		=> $input['email'],
				'prefix'				=> $key_prefix,
				'licence_key'			=> $key,
				'software_product_id'	=> $input['product_id'],
				'software_version'		=> $version,
				'activations_limit'		=> $activations,
	        );

			$key_id = $wc_software->save_licence_key( $data );
			
			$json = array( 'key' => $key_prefix . $key, 'key_id' => $key_id );

			return $json;
		} else {
			$this->wc_software_api->error( '105', __( 'Non matching product_id and secret_key provided', 'wc_software' ), null, array( 'activated' => false ) );
		}
	}
};

?>