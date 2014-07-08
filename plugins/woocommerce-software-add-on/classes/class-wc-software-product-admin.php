<?php

/**
 * WC_Software_Product_admin class.
 */
class WC_Software_Product_Admin {
	
	var $product_fields;
	
	/**
	 * __construct function.
	 * 
	 * @access public
	 * @return void
	 */
	function __construct() {
		
		// Hooks
		add_action( 'woocommerce_product_options_product_type', array( $this, 'is_software' ) );
		add_action( 'woocommerce_product_write_panel_tabs', array( $this, 'product_write_panel_tab' ) );
		add_action( 'woocommerce_product_write_panels', array( $this, 'product_write_panel' ) );
		add_filter( 'woocommerce_process_product_meta', array( $this, 'product_save_data' ) );
		
		// New product type option - 1.6.2
	    add_filter( 'product_type_options', array( $this, 'product_type_options' ) );
	}
	   
    function product_type_options( $options ) {
	    $options['is_software'] = array( 
			'id' => '_is_software', 
			'wrapper_class' => 'show_if_simple', 
			'label' => __( 'Software', 'woocommerce' ), 
			'description' => __( 'Enable this option if this is software (and you want to manage licence keys)', 'woocommerce' ) 
		);
		return $options;
    }
	    
	function define_fields() {
	
		if ( $this->product_fields ) return;
	
		// Fields
		$this->product_fields = array(
			'start_group',
			array( 
				'id' 	=> '_software_product_id', 
				'label' => __( 'Product ID', 'wc_software' ), 
				'description' => __( 'This ID is used for the licence key API.', 'wc_software' ), 
				'placeholder' => __( 'e.g. SOFTWARE1', 'wc_software' ), 
				'type' 	=> 'text' 
			),
			array( 
				'id' => '_software_license_key_prefix', 
				'label' => __( 'License key prefix', 'wc_software' ), 
				'description' => __( 'Optional prefix for generated license keys.', 'wc_software' ), 
				'placeholder' => __( 'N/A', 'wc_software' ), 
				'type' => 'text' 
			),
			array( 
				'id' => '_software_secret_product_key', 
				'label' => __( 'Secret key', 'wc_software' ), 
				'description' => __( 'Secret Product Key to use  for API.', 'wc_software' ), 
				'placeholder' => __( 'any random string', 'wc_software' ), 
				'type' => 'text',
				'default' => substr(str_shuffle(MD5(microtime())), 0, 32)
			),
			array( 
				'id' => '_software_version', 
				'label' => __( 'Version', 'wc_software' ), 
				'description' => __( 'Version number for the software.', 'wc_software' ), 
				'placeholder' => __( 'e.g. 1.0', 'wc_software' ), 
				'type' => 'text' 
			),
			array( 
				'id' => '_software_activations', 
				'label' => __( 'Activation limit', 'wc_software' ), 
				'description' => __( 'Amount of activations possible per licence key.', 'wc_software' ), 
				'placeholder' => __( 'Unlimited', 'wc_software' ), 
				'type' => 'text' 
			),
			'end_group',
			'start_group',
			array( 
				'id' => '_software_upgradable_product', 
				'label' => __( 'Upgradable product', 'wc_software' ), 
				'description' => __( 'Name of the product which can be upgraded.', 'wc_software' ), 
				'placeholder' => '', 
				'type' => 'text' 
			),
			array( 
				'id' => '_software_upgrade_price', 
				'label' => __( 'Upgrade Price', 'wc_software' ) . ' ( ' . get_woocommerce_currency_symbol() . ' )', 
				'description' => __( 'Users with a valid upgrade key will be able to pay this amount.', 'wc_software' ), 
				'placeholder' => __( 'e.g. 10.99', 'wc_software' ), 
				'class'	=> 'wc_input_price short',
				'type' => 'text' 
			),
			array( 
				'id' => '_software_upgrade_license_keys', 
				'label' => __( 'Valid upgrade keys', 'wc_software' ), 
				'description' => __( 'A comma separated list of keys which can be upgraded.', 'wc_software' ), 
				'placeholder' => '', 
				'type' => 'textarea' 
			),
			array( 
				'id' => '_software_used_license_keys', 
				'label' => __( 'Used upgrade keys', 'wc_software' ), 
				'description' => __( 'A comma separated list of keys which have been used for an upgrade already.', 'wc_software' ), 
				'placeholder' => '', 
				'type' => 'textarea' 
			),
			'end_group',
		);

	}
	
	/**
	 * is_software function.
	 */
	function is_software() {
		
		woocommerce_wp_checkbox( array( 'id' => '_is_software', 'wrapper_class' => 'show_if_simple', 'label' => __( 'Software', 'woocommerce' ), 'description' => __( 'Enable this option if this is software (and you want to manage licence keys)', 'woocommerce' ) ) );
		
	}

	/**
	 * adds a new tab to the product interface
	 */
	function product_write_panel_tab() {
		?>
		<li class="software_tab show_if_software"><a href="#software_data"><?php _e( 'Software', 'wc_software' ); ?></a></li>
		<?php
	}

	/**
	 * adds the panel to the product interface
	 */
	function product_write_panel() {
		global $post, $woocommerce;
		
		$this->define_fields();
		
		$data = get_post_meta( $post->ID, 'product_data', true );
		?>
		<div id="software_data" class="panel woocommerce_options_panel">
		<?php
			foreach ( $this->product_fields as $field ) {
				
				if ( ! is_array( $field ) ) {
					
					if ( $field == 'start_group' ) {
						echo '<div class="options_group">';
					} elseif ( $field == 'end_group' ) {
						echo '</div>';
					}
					
				} else {
					
					$func = 'woocommerce_wp_' . $field['type'] . '_input';
					
					if ( function_exists( $func ) )
						$func( $field );
				
				}
			}
			?>
		</div>
		<?php

		$javascript = "
			
			jQuery('input#_is_software').change(function(){

				jQuery('.show_if_software').hide();
				
				if ( jQuery('#_is_software').is(':checked') ) {
					jQuery('.show_if_software').show();
				} else {
					if ( jQuery('.software_tab').is('.active') ) jQuery('ul.tabs li:visible').eq(0).find('a').click();
				}
				
			}).change();

		";
		
		if ( function_exists( 'wc_enqueue_js' ) ) {
			wc_enqueue_js( $javascript );
		} else {
			$woocommerce->add_inline_js( $javascript );
		}		
	}

	/**
	 * saves the data inputed into the product boxes into a serialized array
	 */
	function product_save_data() {
		global $post;
		
		$this->define_fields();
		
		if ( ! empty( $_POST['_is_software'] ) )
			update_post_meta( $post->ID, '_is_software', 'yes' );
		else
			update_post_meta( $post->ID, '_is_software', 'no' );
		
		foreach ( $this->product_fields as $field ) {
				
			if ( is_array( $field ) ) {
			
				$data = isset( $_POST[ $field['id'] ] ) ? esc_attr( trim( stripslashes( $_POST[ $field['id'] ] ) ) ) : '';
				
				update_post_meta( $post->ID, $field['id'], $data );
				
			}
			
		}

	}

}

$GLOBALS['WC_Software_Product_Admin'] = new WC_Software_Product_Admin(); // Init
