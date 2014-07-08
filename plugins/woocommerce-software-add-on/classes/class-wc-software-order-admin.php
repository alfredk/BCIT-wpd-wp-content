<?php

/**
 * WC_Software_Order_Admin class.
 */
class WC_Software_Order_Admin extends WC_Software {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {

		// Ajax
		add_action( 'wp_ajax_woocommerce_delete_licence_key', array( $this, 'delete_key' ) );
		add_action( 'wp_ajax_woocommerce_add_licence_key', array( $this, 'add_key' ) );
		add_action( 'wp_ajax_woocommerce_toggle_activation', array( $this, 'toggle_activation' ) );

		// Hooks
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'order_save_data' ) );
	}

	/**
	 * Delete a key via ajax
	 */
	function delete_key() {

		check_ajax_referer( 'delete-key', 'security' );

		global $wpdb;

		$key_id = intval( $_POST['key_id'] );
		$order_id 	= intval( $_POST['order_id'] );

		$wpdb->query("
			DELETE FROM {$wpdb->prefix}woocommerce_software_licences
			WHERE key_id = $key_id
		");

		$wpdb->query("
			DELETE FROM {$wpdb->prefix}woocommerce_software_activations
			WHERE key_id = $key_id
		");

		die();
	}

	/**
	 * Add a key via ajax
	 */
	function add_key() {

		check_ajax_referer( 'add-key', 'security' );

		global $wpdb;

		$product_id = intval( $_POST['product_id'] );
		$order_id 	= intval( $_POST['order_id'] );

		$order = new WC_Order( $order_id );
		$meta = get_post_custom( $product_id );

		$wpdb->hide_errors();

		$data = array(
			'order_id' 				=> $order_id,
			'activation_email'		=> $order->billing_email,
			'prefix'				=> '',
			'licence_key' 			=> ( empty( $meta['_software_license_key_prefix'][0] ) ? '' : $meta['_software_license_key_prefix'][0] ) . $this->generate_licence_key(),
			'software_product_id'	=> empty( $meta['_software_product_id'][0] ) ? '' : $meta['_software_product_id'][0],
			'software_version'		=> empty( $meta['_software_version'][0] ) ? '' : $meta['_software_version'][0],
			'activations_limit'		=> empty( $meta['_software_activations'][0] ) ? '' : (int) $meta['_software_activations'][0],
        );

		$key_id = $this->save_licence_key( $data );

		if ( $key_id ) {

			$data['success'] = 1;
			$data['key_id'] = $key_id;

			echo json_encode( $data );
		}

		die();
	}

	/**
	 * Toggle activation via ajax
	 */
	function toggle_activation() {

		check_ajax_referer( 'toggle-activation', 'security' );

		global $wpdb;

		$activation_id = intval( $_POST['activation_id'] );

		$active = $wpdb->get_var( "SELECT activation_active FROM {$wpdb->prefix}woocommerce_software_activations WHERE activation_id = {$activation_id}" );

		$active = ( $active ) ? 0 : 1;

		$wpdb->query("
			UPDATE {$wpdb->prefix}woocommerce_software_activations
			SET activation_active = {$active}
			WHERE activation_id = $activation_id;
		");

		echo ( $active ) ? __( 'Activated', 'wc_software' ) : __( 'Deactivated', 'wc_software' );

		die();
	}

	/**
	 * registers meta boxes
	 *
	 * @since 1.0
	 * @return void
	 */
	function add_meta_boxes() {
		add_meta_box( 'woocommerce-order-licence-keys', __( 'Software Licence Keys', 'wc_software'), array( $this, 'licence_keys_meta_box' ), 'shop_order', 'normal', 'high' );
		add_meta_box( 'wc_software-activation-data', __( 'Activations', 'wc_software' ), array( $this, 'activation_meta_box' ), 'shop_order', 'normal', 'high' );
	}

	/**
	 * Order notes meta box
	 */
	function licence_keys_meta_box() {
		global $woocommerce, $post, $wpdb;

		?>
		<div class="order_licence_keys wc-metaboxes-wrapper">

			<div class="wc-metaboxes">

				<?php
					$i = -1;

					$licence_keys = $wpdb->get_results("
						SELECT * FROM {$wpdb->prefix}woocommerce_software_licences
						WHERE order_id = $post->ID
					");

					if ( $licence_keys && sizeof( $licence_keys ) > 0 ) foreach ( $licence_keys as $licence_key ) :
						$i++;

						?>
			    		<div class="wc-metabox closed">
							<h3 class="fixed">
								<button type="button" rel="<?php echo $licence_key->key_id; ?>" class="delete_key button"><?php _e( 'Delete key', 'wc_software' ); ?></button>
								<div class="handlediv" title="<?php _e( 'Click to toggle', 'wc_software' ); ?>"></div>
								<strong><?php printf( __( 'Product: %s, version %s', 'wc_software' ), $licence_key->software_product_id, $licence_key->software_version ); ?> &mdash; <?php echo $licence_key->licence_key; ?></strong>
								<input type="hidden" name="key_id[<?php echo $i; ?>]" value="<?php echo $licence_key->key_id; ?>" />
							</h3>
							<table cellpadding="0" cellspacing="0" class="wc-metabox-content">
								<tbody>
									<tr>
										<td>
											<label><?php _e( 'Licence Key', 'wc_software' ); ?>:</label>
											<input type="text" class="short" name="licence_key[<?php echo $i; ?>]" value="<?php echo $licence_key->licence_key; ?>" />
										</td>
										<td>
											<label><?php _e( 'Activation Email', 'wc_software' ); ?>:</label>
											<input type="text" class="short" name="activation_email[<?php echo $i; ?>]" value="<?php echo $licence_key->activation_email; ?>" />
										</td>
										<td>
											<label><?php _e( 'Activation Limit', 'wc_software' ); ?>:</label>
											<input type="text" class="short" name="activations_limit[<?php echo $i; ?>]" value="<?php echo $licence_key->activations_limit; ?>" placeholder="<?php _e( 'Unlimited', 'wc_software' ); ?>" />
										</td>
									</tr>
									<tr>
										<td>
											<label><?php _e( 'Software Product ID', 'wc_software' ); ?>:</label>
											<input type="text" class="short" name="software_product_id[<?php echo $i; ?>]" value="<?php echo $licence_key->software_product_id; ?>" />
										</td>
										<td>
											<label><?php _e( 'Software Version', 'wc_software' ); ?>:</label>
											<input type="text" class="short" name="software_version[<?php echo $i; ?>]" value="<?php echo $licence_key->software_version; ?>" />
										</td>
										<td>&nbsp;</td>
									</tr>
								</tbody>
							</table>
						</div>
						<?php
					endforeach;
				?>
			</div>

			<div class="toolbar">
				<p class="buttons">
					<select name="add_software_id" class="add_software_id chosen_select_nostd" data-placeholder="<?php _e( 'Choose a software product&hellip;', 'woocommerce' ) ?>">
						<?php
							echo '<option value=""></option>';

							$args = array(
								'post_type' 		=> 'product',
								'posts_per_page' 	=> -1,
								'post_status'		=> 'publish',
								'order'				=> 'ASC',
								'orderby'			=> 'title',
								'meta_query'		=> array(
									array(
										'key' 	=> '_is_software',
										'value' => 'yes'
									)
								)
							);
							$products = get_posts( $args );

							if ($products) foreach ($products as $product) :

								$sku = get_post_meta($product->ID, '_sku', true);

								if ($sku) $sku = ' SKU: '.$sku;

								echo '<option value="'.$product->ID.'">'.$product->post_title.$sku.' (#'.$product->ID.''.$sku.')</option>';

								$args_get_children = array(
									'post_type' => array( 'product_variation', 'product' ),
									'posts_per_page' 	=> -1,
									'order'				=> 'ASC',
									'orderby'			=> 'title',
									'post_parent'		=> $product->ID
								);

								if ( $children_products =& get_children( $args_get_children ) ) :

									foreach ($children_products as $child) :

										echo '<option value="'.$child->ID.'">&nbsp;&nbsp;&mdash;&nbsp;'.$child->post_title.'</option>';

									endforeach;

								endif;

							endforeach;
						?>
					</select>

					<button type="button" class="button add_key"><?php _e( 'Add Licence Key', 'wc_software' ); ?></button>
				</p>
				<div class="clear"></div>
			</div>

		</div>
		<?php
		/**
		 * Javascript
		 */
		ob_start();
		?>
		jQuery(function(){

			jQuery('.order_licence_keys').on('click', 'button.add_key', function(){

				var product = jQuery('select.add_software_id').val();

				if ( ! product ) return false;

				jQuery('.order_licence_keys').block({ message: null, overlayCSS: { background: '#fff url(<?php echo $woocommerce->plugin_url(); ?>/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

				var data = {
					action: 		'woocommerce_add_licence_key',
					product_id: 	product,
					order_id: 		'<?php echo $post->ID; ?>',
					security: 		'<?php echo wp_create_nonce("add-key"); ?>'
				};

				jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function(response) {

					var loop = jQuery('.order_licence_keys .wc-metabox').size();

					new_software = jQuery.parseJSON( response );

					if ( new_software && new_software.success == 1 ) {

						jQuery('.order_licence_keys .wc-metaboxes').append('<div class="wc-metabox closed">\
							<h3 class="fixed">\
								<button type="button" rel="' + new_software.key_id + '" class="delete_key button"><?php _e('Delete key', 'wc_software'); ?></button>\
								<div class="handlediv" title="<?php _e('Click to toggle', 'wc_software'); ?>"></div>\
								<strong><?php printf( __( 'Product: %s, version %s', 'wc_software' ), "' + new_software.software_product_id + '", "' + new_software.software_version + '" ); ?> &mdash; ' + new_software.licence_key + '</strong>\
								<input type="hidden" name="key_id[' + loop + ']" value="' + new_software.key_id + '" />\
							</h3>\
							<table cellpadding="0" cellspacing="0" class="wc-metabox-content">\
								<tbody>	\
									<tr>\
										<td>\
											<label><?php _e('Licence Key', 'wc_software'); ?>:</label>\
											<input type="text" class="short" name="licence_key[' + loop + ']" value="' + new_software.licence_key + '" />\
										</td>\
										<td>\
											<label><?php _e('Activation Email', 'wc_software'); ?>:</label>\
											<input type="text" class="short" name="activation_email[' + loop + ']" value="' + new_software.activation_email + '" />\
										</td>\
										<td>\
											<label><?php _e('Activations Remaining', 'wc_software'); ?>:</label>\
											<input type="text" class="short" name="activations_limit[' + loop + ']" value="' + new_software.activations_limit + '" placeholder="<?php _e('Unlimited', 'wc_software'); ?>" />\
										</td>\
									</tr>\
									<tr>\
										<td>\
											<label><?php _e('Software Product ID', 'wc_software'); ?>:</label>\
											<input type="text" class="short" name="software_product_id[' + loop + ']" value="' + new_software.software_product_id + '" />\
										</td>\
										<td>\
											<label><?php _e('Software Version', 'wc_software'); ?>:</label>\
											<input type="text" class="short" name="software_version[' + loop + ']" value="' + new_software.software_version + '" />\
										</td>\
										<td>&nbsp;</td>\
									</tr>\
								</tbody>\
							</table>\
						</div>');

					}

					jQuery('.order_licence_keys').unblock();

				});

				return false;

			});

			jQuery('.order_licence_keys').on('click', 'button.delete_key', function(e){
				e.preventDefault();
				var answer = confirm('<?php _e('Are you sure you want to delete this licence key?', 'wc_software'); ?>');
				if (answer){

					var el = jQuery(this).parent().parent();

					var key_id = jQuery(this).attr('rel');

					if ( key_id > 0 ) {

						jQuery(el).block({ message: null, overlayCSS: { background: '#fff url(<?php echo $woocommerce->plugin_url(); ?>/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

						var data = {
							action: 		'woocommerce_delete_licence_key',
							key_id: 		key_id,
							order_id: 		'<?php echo $post->ID; ?>',
							security: 		'<?php echo wp_create_nonce("delete-key"); ?>'
						};

						jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function(response) {
							// Success
							jQuery(el).fadeOut('300', function(){
								jQuery(el).remove();
							});
						});

					} else {
						jQuery(el).fadeOut('300', function(){
							jQuery(el).remove();
						});
					}

				}
				return false;
			});

		});
		<?php
		$javascript = ob_get_clean();
		if ( function_exists( 'wc_enqueue_js' ) ) {
			wc_enqueue_js( $javascript );
		} else {
			$woocommerce->add_inline_js( $javascript );
		}
	}

	/**
	 * adds activations meta box
	 *
	 * @since 1.0
	 * @param object $post the current post object
	 * @return void
	 */
	function activation_meta_box( $post ) {
		global $wpdb, $woocommerce;

		$activations = $wpdb->get_results("
			SELECT * FROM {$wpdb->prefix}woocommerce_software_activations as activations
			LEFT JOIN {$wpdb->prefix}woocommerce_software_licences as licences ON activations.key_id = licences.key_id
			WHERE order_id = {$post->ID}
		");

		if ( sizeof( $activations ) > 0 ) {

			?>
			<div class="woocommerce_order_items_wrapper">
				<table id="activations-table" class="woocommerce_order_items" cellspacing="0">
					<thead>
				    	<tr>
							<th><?php _e( 'Licence Key', 'wc_software' ) ?></th>
							<th><?php _e( 'Instance', 'wc_software' ) ?></th>
							<th><?php _e( 'Software ID', 'wc_software' ) ?></th>
							<th><?php _e( 'Status', 'wc_software' ) ?></th>
							<th><?php _e( 'Date &amp; Time', 'wc_software' ) ?></th>
							<th><?php _e( 'Software Version', 'wc_software' ) ?></th>
							<th><?php _e( 'Platform/OS', 'wc_software' ) ?></th>
							<th><?php _e( 'Action', 'wc_software' ) ?></th>
						</tr>
					</thead>
					<tbody>
				    	<?php $i = 1; foreach ( $activations as $activation ) : $i++ ?>
							<tr<?php if ( $i % 2 == 1 ) echo ' class="alternate"' ?>>
								<td><?php echo $activation->licence_key; ?></td>
								<td><?php echo ( $activation->instance ) ? $activation->instance : _e('N/A', 'wc_software'); ?></td>
								<td><?php echo $activation->software_product_id; ?></td>
								<td class="activation_active"><?php echo ( $activation->activation_active ) ? __( 'Activated', 'wc_software' ) : __( 'Deactivated', 'wc_software' ) ?></td>
								<td><?php echo date( __( 'D j M Y \a\t h:ia', 'wc_software' ), strtotime( $activation->activation_time ) ) ?></td>
								<td><?php echo $activation->software_version ?></td>
								<td><?php echo ucwords( $activation->activation_platform ) ?></td>
								<td>
									<button class="button toggle_activation" data-id="<?php echo $activation->activation_id; ?>"><?php _e( 'Toggle Activation', 'wc_software' ); ?></button>
								</td>
				      		</tr>
				    	<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php
			/**
			 * Javascript
			 */
			ob_start();
			?>
			jQuery(function(){

				jQuery('#activations-table').on('click', 'button.toggle_activation', function(){

					var $this = jQuery( this );
					var activation = jQuery(this).attr( 'data-id' );

					if ( ! activation ) return;

					jQuery('#activations-table').block({ message: null, overlayCSS: { background: '#fff url(<?php echo $woocommerce->plugin_url(); ?>/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

					var data = {
						action: 		'woocommerce_toggle_activation',
						activation_id: 	activation,
						security: 		'<?php echo wp_create_nonce("toggle-activation"); ?>'
					};

					jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function( result ) {

						$this.closest('tr').find('td.activation_active').html( result );

						jQuery('#activations-table').unblock();

					});

					return false;

				});
			});
			<?php
			$javascript = ob_get_clean();
			
			if ( function_exists( 'wc_enqueue_js' ) ) {
				wc_enqueue_js( $javascript );
			} else {
				$woocommerce->add_inline_js( $javascript );
			}

		} else {
			?><p style="padding:0 8px;"><?php _e( 'No activations yet', 'wc_software' ) ?></p><?php
		}
	}

	/**
	 * saves the data inputed into the order boxes
	 *
	 * @see order_meta_box()
	 * @since 1.0
	 * @return void
	 */
	function order_save_data() {
		global $post, $wpdb;

		$key_id 				= stripslashes_deep( $_POST['key_id'] );
		$licence_key			= stripslashes_deep( $_POST['licence_key'] );
		$activation_email		= stripslashes_deep( $_POST['activation_email'] );
		$activations_limit		= stripslashes_deep( $_POST['activations_limit'] );
		$software_product_id	= stripslashes_deep( $_POST['software_product_id'] );
		$software_version		= stripslashes_deep( $_POST['software_version'] );

		$key_id_count = sizeof( $key_id );

		for ( $i = 0; $i < $key_id_count; $i++ ) {
			if ( ! isset( $key_id[$i] ) ) continue;

			$data = array(
				'licence_key' 			=> esc_attr( $licence_key[$i] ),
				'activation_email' 		=> esc_attr( $activation_email[$i] ),
				'activations_limit' 	=> ( $activations_limit[$i] == '' ) ? '' : (int) $activations_limit[$i],
				'software_product_id' 	=> esc_attr( $software_product_id[$i] ),
				'software_version' 		=> esc_attr( $software_version[$i] ),
            );

            $format = array(
				'%s',
				'%s',
				'%d',
				'%s',
				'%s',
				'%s'
            );

			$wpdb->update(
				$wpdb->prefix . 'woocommerce_software_licences',
				$data,
				array( 'key_id' => $key_id[$i] ),
				$format,
				array( '%d' )
			);

		}

	}

}

$GLOBALS['WC_Software_Order_Admin'] = new WC_Software_Order_Admin(); // Init
