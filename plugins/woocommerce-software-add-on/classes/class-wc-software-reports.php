<?php
/**
 * WC_Software_Reports class.
 *
 * Shows reports related to software in the woocommerce backend
 */
class WC_Software_Reports {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @param bool $debug (default: false)
	 * @return void
	 */
	function __construct( $debug = false ) {

		add_filter( 'woocommerce_reports_charts', array( $this, 'reports_tab' ) );

	}

	/**
	 * reports_tab function.
	 *
	 * @access public
	 * @return void
	 */
	function reports_tab( $reports ) {

		$reports['software'] = array(
			'title'  =>  __( 'Software', 'woocommerce' ),
			'charts' => array(
				array(
					'title'       => __( 'Overview', 'woocommerce' ),
					'description' => '',
					'hide_title'  => true,
					'function'    => 'woocommerce_software_report'
				),
				array(
					'title'       => __( 'Activations', 'woocommerce' ),
					'description' => '',
					'function'    => 'woocommerce_software_report'
				),
			)
		);

		return $reports;

	}

	/**
	 * generate_report function.
	 *
	 * @access public
	 * @return void
	 */
	function generate_report() {

		$chart = ( empty( $_GET['chart'] ) ) ? 0 : (int) esc_attr( $_GET['chart'] );

		if ( $chart == 0 ) {

			$this->sales();

		} else {

			$this->activations();

		}

	}

	function sales() {
		global $woocommerce, $wpdb;

		$licence_keys_count = $wpdb->get_var("
			SELECT COUNT(key_id) FROM {$wpdb->prefix}woocommerce_software_licences
		");

		$activations_count = $wpdb->get_var("
			SELECT COUNT(activation_id) FROM {$wpdb->prefix}woocommerce_software_activations
		");

		$activations_active_count = $wpdb->get_var("
			SELECT COUNT(activation_id) FROM {$wpdb->prefix}woocommerce_software_activations
			WHERE activation_active = 1
		");

		$software_items_serialized = $wpdb->get_col("
			SELECT meta.meta_value AS items FROM {$wpdb->posts} AS posts

			LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
			LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID
			LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
			LEFT JOIN {$wpdb->terms} AS term USING( term_id )

			WHERE 	meta.meta_key 		= '_order_items'
			AND 	posts.post_type 	= 'shop_order'
			AND 	posts.post_status 	= 'publish'
			AND 	tax.taxonomy		= 'shop_order_status'
			AND		term.slug			IN ('completed', 'processing', 'on-hold')
		");

		$software_sales = $software_items = 0;

		if ( $software_items_serialized ) foreach ( $software_items_serialized as $software_items_array ) {

			$software_items_array = maybe_unserialize( $software_items_array );

			if ( is_array( $software_items_array ) ) {
				foreach ( $software_items_array as $item ) {
					if ( get_post_meta( $item['id'], '_is_software', true) != 'yes' ) continue;
					$software_items += (int) $item['qty'];
					$software_sales += number_format( $item['line_total'] + $item['line_tax'] , 2, '.', '' );
				}
			}
		}

		?>
		<div id="poststuff" class="woocommerce-reports-wrap">
			<div class="woocommerce-reports-sidebar">
				<div class="postbox">
					<h3><span><?php _e('Total software sales', 'woocommerce'); ?></span></h3>
					<div class="inside">
						<p class="stat"><?php if ($software_sales>0) echo woocommerce_price($software_sales); else _e('n/a', 'woocommerce'); ?></p>
					</div>
				</div>
				<div class="postbox">
					<h3><span><?php _e('Total software sold', 'woocommerce'); ?></span></h3>
					<div class="inside">
						<p class="stat"><?php if ( $software_items > 0 ) echo $software_items; else _e('n/a', 'woocommerce'); ?></p>
					</div>
				</div>
				<div class="postbox">
					<h3><span><?php _e('Total licence keys', 'woocommerce'); ?></span></h3>
					<div class="inside">
						<p class="stat"><?php if ( $licence_keys_count > 0 ) echo (int) $licence_keys_count; else _e('n/a', 'woocommerce'); ?></p>
					</div>
				</div>
				<div class="postbox">
					<h3><span><?php _e('Total activations', 'woocommerce'); ?></span></h3>
					<div class="inside">
						<p class="stat"><?php if ( $activations_count > 0 ) echo $activations_count . ' (' . $activations_active_count . ' ' . __('active', 'wc_software') . ')'; else _e('n/a', 'woocommerce'); ?></p>
					</div>
				</div>
			</div>
			<div class="woocommerce-reports-main">
				<div class="postbox">
					<h3><span><?php _e('Recent Activations', 'woocommerce'); ?></span></h3>
					<div>
						<?php
							$activations = $wpdb->get_results("
								SELECT * FROM {$wpdb->prefix}woocommerce_software_activations as activations
								LEFT JOIN {$wpdb->prefix}woocommerce_software_licences as licences ON activations.key_id = licences.key_id
								ORDER BY activations.activation_time ASC
								LIMIT 50
							");

							if ( sizeof( $activations ) > 0 ) {

								?>
								<div class="woocommerce_order_items_wrapper">
									<table id="activations-table" class="woocommerce_order_items" cellspacing="0">
										<thead>
									    	<tr>
									    		<th><?php _e( 'Order', 'wc_software' ) ?></th>
									    		<th><?php _e( 'Software ID', 'wc_software' ) ?></th>
												<th><?php _e( 'Licence Key', 'wc_software' ) ?></th>
												<th><?php _e( 'Status', 'wc_software' ) ?></th>
												<th><?php _e( 'Date &amp; Time', 'wc_software' ) ?></th>
												<th><?php _e( 'Software Version', 'wc_software' ) ?></th>
												<th><?php _e( 'Platform/OS', 'wc_software' ) ?></th>
											</tr>
										</thead>
										<tbody>
									    	<?php $i = 1; foreach ( $activations as $activation ) : $i++ ?>
												<tr<?php if ( $i % 2 == 1 ) echo ' class="alternate"' ?>>
													<td><?php if ( $activation->order_id ) : ?><a href="<?php echo admin_url('post.php?post=' . $activation->order_id . '&action=edit'); ?>"><?php echo $activation->order_id; ?></a><?php else : _e('N/A', 'wc_software'); endif; ?></td>
													<td><?php echo $activation->software_product_id; ?></td>
													<td><?php echo $activation->licence_key; ?></td>
													<td><?php echo ( $activation->activation_active ) ? __( 'Activated', 'wc_software' ) : __( 'Deactivated', 'wc_software' ) ?></td>
													<td><?php echo date( __( 'D j M Y \a\t h:ia', 'wc_software' ), strtotime( $activation->activation_time ) ) ?></td>
													<td><?php echo $activation->software_version ?></td>
													<td><?php echo ucwords( $activation->activation_platform ) ?></td>
									      		</tr>
									    	<?php endforeach; ?>
										</tbody>
									</table>
								</div>
								<?php
							} else {
								?><p><?php _e( 'No activations yet', 'wc_software' ) ?></p><?php
							}
						?>
					</div>
				</div>
			</div>
		</div>
		<?php

	}

	function activations() {
		global $wpdb;

		$start_date = (isset($_POST['start_date'])) ? $_POST['start_date'] : '';
		$end_date	= (isset($_POST['end_date'])) ? $_POST['end_date'] : '';

		if ( ! $start_date) $start_date = date('Ymd', strtotime( date('Ym', current_time('timestamp')).'01' ));
		if ( ! $end_date) $end_date = date('Ymd', current_time('timestamp'));

		$start_date = strtotime( $start_date );
		$end_date = strtotime( $end_date );

		?>
		<form method="post" action="">
			<p><label for="from"><?php _e('From:', 'woocommerce'); ?></label> <input type="text" name="start_date" id="from" readonly="readonly" value="<?php echo esc_attr( date('Y-m-d', $start_date) ); ?>" /> <label for="to"><?php _e('To:', 'woocommerce'); ?></label> <input type="text" name="end_date" id="to" readonly="readonly" value="<?php echo esc_attr( date('Y-m-d', $end_date) ); ?>" /> <input type="submit" class="button" value="<?php _e('Show', 'woocommerce'); ?>" /></p>
		</form>
		<script type="text/javascript">
			jQuery(function(){
				<?php woocommerce_datepicker_js(); ?>
			});
		</script>
		<?php

		$activations = $wpdb->get_results("
			SELECT * FROM {$wpdb->prefix}woocommerce_software_activations as activations
			LEFT JOIN {$wpdb->prefix}woocommerce_software_licences as licences ON activations.key_id = licences.key_id
			WHERE date_format( activation_time ,'%Y%m%d') >= '" . date("Ymd", $start_date ) . "'
			AND date_format( activation_time ,'%Y%m%d') <= '" . date("Ymd", $end_date ) . "'
			ORDER BY activation_time ASC
			LIMIT 50
		");

		if ( sizeof( $activations ) > 0 ) {

			?>
			<table id="activations-table" class="widefat" cellspacing="0">
				<thead>
			    	<tr>
			    		<th><?php _e( 'Order', 'wc_software' ) ?></th>
			    		<th><?php _e( 'Instance', 'wc_software' ) ?></th>
			    		<th><?php _e( 'Software ID', 'wc_software' ) ?></th>
						<th><?php _e( 'Licence Key', 'wc_software' ) ?></th>
						<th><?php _e( 'Status', 'wc_software' ) ?></th>
						<th><?php _e( 'Date &amp; Time', 'wc_software' ) ?></th>
						<th><?php _e( 'Software Version', 'wc_software' ) ?></th>
						<th><?php _e( 'Platform/OS', 'wc_software' ) ?></th>
					</tr>
				</thead>
				<tbody>
			    	<?php $i = 1; foreach ( $activations as $activation ) : $i++ ?>
						<tr<?php if ( $i % 2 == 1 ) echo ' class="alternate"' ?>>
							<td><?php if ( $activation->order_id ) : ?><a href="<?php echo admin_url('post.php?post=' . $activation->order_id . '&action=edit'); ?>"><?php echo $activation->order_id; ?></a><?php else : _e('N/A', 'wc_software'); endif; ?></td>
							<td><?php if ( $activation->instance ) : ?><?php echo $activation->instance; ?><?php else : _e('N/A', 'wc_software'); endif; ?></td>
							<td><?php echo $activation->software_product_id; ?></td>
							<td><?php echo $activation->licence_key; ?></td>
							<td><?php echo ( $activation->activation_active ) ? __( 'Activated', 'wc_software' ) : __( 'Deactivated', 'wc_software' ) ?></td>
							<td><?php echo date( __( 'D j M Y \a\t h:ia', 'wc_software' ), strtotime( $activation->activation_time ) ) ?></td>
							<td><?php echo $activation->software_version ?></td>
							<td><?php echo ucwords( $activation->activation_platform ) ?></td>
			      		</tr>
			    	<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		} else {
			?><p><?php _e( 'No activations found.', 'wc_software' ) ?></p><?php
		}
	}
}

$GLOBALS['WC_Software_Reports'] = new WC_Software_Reports();

// Function for reports hook
function woocommerce_software_report() {
	$GLOBALS['WC_Software_Reports']->generate_report();
}