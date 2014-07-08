<?php
/*
  Plugin Name: WooCommerce Bulk Variations
  Plugin URI: http://woothemes.com/woocommerce
  Description: WooCommerce Bulk Variations allows your shoppers to add more than one variation at a time to the cart.  Great for wholesales, B2B sites, and for easing the process of adding more than one variation at a time for anyone.
  Version: 1.1.3
  Author: Lucas Stark
  Author URI: http://lucasstark.com
  Requires at least: 3.1
  Tested up to: 3.3

  Copyright: © 2009-2012 Lucas Stark.
  License: GNU General Public License v3.0
  License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Required functions
 */
if (!function_exists('woothemes_queue_update'))
	require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
woothemes_queue_update(plugin_basename(__FILE__), 'aa3a54eef10ec085a1b1357375e86c2d', '187872');

if (is_woocommerce_active()) {
	/**
	 * Localisation
	 * */
	load_plugin_textdomain('wc_bulk_variations', false, dirname(plugin_basename(__FILE__)) . '/');
	class WC_Bulk_Variations {
		/** URLS ***************************************************************** */
		var $plugin_url;
		var $plugin_path;

		public function __construct() {
			global $pagenow;
			
			require 'class-wc-bulk-variations-compatibility.php';

			if (is_admin() && ( $pagenow == 'post-new.php' || $pagenow == 'post.php' || $pagenow == 'edit.php' )) {

				require 'woocommerce-bulk-variations-admin.php';
				$this->admin = new WC_Bulk_Variations_Admin();
			} elseif (!is_admin()) {

				require 'woocommerce-bulk-variations-functions.php';

				add_action('template_redirect', array(&$this, 'include_bulk_form_assets'), 99);

				if (isset($_POST['add-variations-to-cart']) && $_POST['add-variations-to-cart']) {
					add_action('init', array(&$this, 'process_matrix_submission'), 99);
				}
			}
		}

		public function include_bulk_form_assets() {
			global $post;

			if (!$post) {
				return;
			}

			if (is_product() && !get_post_meta($post->ID, '_bv_type', true)) {
				return;
			}

			// 2.0 Compat
			if (function_exists('get_product'))
				$product = get_product($post->ID);
			else
				$product = new WC_Product($post->ID);

			if ($product && !$product->has_child() && !$product->is_type('variable')) {
				return;
			}

			if (apply_filters('woocommerce_bv_render_form', __return_true())) {
				//Enqueue scripts and styles for bulk variations
				wp_enqueue_style('bulk-variations', $this->plugin_url() . '/assets/css/bulk-variations.css');
				wp_enqueue_script('jquery-validate', $this->plugin_url() . '/assets/js/jquery.validate.js', array('jquery'));
				wp_enqueue_script('bulk-variations', $this->plugin_url() . '/assets/js/bulk-variations.js', array('jquery', 'jquery-validate'));


				//Register the hook to render the bulk form as late as possibile
				add_action('woocommerce_before_single_product', array(&$this, 'render_bulk_form'), 999);
				add_action('woocommerce_before_add_to_cart_form', array(&$this, 'before_add_to_cart_form'));
				add_action('woocommerce_after_add_to_cart_button', array(&$this, 'after_add_to_cart_button'));
			}
		}

		public function render_bulk_form() {
			global $woocommerce;
			if (WC_Bulk_Variations_Compatibility::is_wc_version_gte_2_1()) {
				wc_get_template('variable-grid.php', array(), WC_TEMPLATE_PATH . 'templates/single-product/', $this->plugin_path() . '/templates/single-product/');
			} else {
				woocommerce_get_template('variable-grid.php', array(), $woocommerce->template_url . 'templates/single-product/', $this->plugin_path() . '/templates/single-product/');
			}
		}

		public function before_add_to_cart_form() {
			?>
			<input class="button btn-bulk" type="button" value="<?php _e('Bulk Order Form', 'wc_bulk_variations'); ?>"  />
			<input class="button btn-single" type="button" value="<?php _e('Singular Order Form', 'wc_bulk_variations'); ?>" />
			<?php
		}

		public function after_add_to_cart_button() {
			
		}

		//Helper functions
		/**
		 * Get the plugin url
		 */
		function plugin_url() {
			if ($this->plugin_url)
				return $this->plugin_url;
			return $this->plugin_url = plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__));
		}

		/**
		 * Get the plugin path
		 */
		function plugin_path() {
			if ($this->plugin_path)
				return $this->plugin_path;

			return $this->plugin_path = untrailingslashit(plugin_dir_path(__FILE__));
		}

		function get_setting($key, $default = null) {
			return get_option($key, $default);
		}

		/**
		 * Ajax URL
		 */
		function ajax_url() {
			$url = admin_url('admin-ajax.php');

			$url = ( is_ssl() ) ? $url : str_replace('https', 'http', $url);

			return $url;
		}

		//Add to cart handling
		public function process_matrix_submission() {
			global $woocommerce;

			$items = $_POST['order_info'];
			$product_id = $_POST['product_id'];

			$all_added_to_cart = false;

			$added_count = 0;
			$failed_count = 0;

			$success_message = '';
			$error_message = '';

			foreach ($items as $item) {
				$q = floatval($item['quantity']) ? floatval($item['quantity']) : 0;
				if ($q) {
					$added = $woocommerce->cart->add_to_cart($product_id, $q, $item['variation_id'], $item['variation_data']);
					$all_added_to_cart &= $added;

					if ($added) {
						$added_count++;
					} else {
						$failed_count++;
					}
				}
			}

			if ($added_count) {
				woocommerce_bulk_variations_add_to_cart_message($added_count);
			}

			if ($failed_count) {
				WC_Bulk_Variations_Compatibility::wc_add_error(sprintf(__('Unable to add %s to the cart.  Please check your quantities and make sure the item is available and in stock', 'wc_bulk_variations'), $failed_count));
			}

			if (!$added_count && !$failed_count) {
				WC_Bulk_Variations_Compatibility::wc_add_error(__('No product quantities entered.', 'wc_bulk_variations'));
			}

			// If we added the product to the cart we can now do a redirect, otherwise just continue loading the page to show errors
			if ($all_added_to_cart) {

				$url = apply_filters('add_to_cart_redirect', $url);

				// If has custom URL redirect there
				if ($url) {
					wp_safe_redirect($url);
					exit;
				}

				// Redirect to cart option
				elseif (get_option('woocommerce_cart_redirect_after_add') == 'yes' && $woocommerce->error_count() == 0) {
					wp_safe_redirect($woocommerce->cart->get_cart_url());
					exit;
				}
			}
		}

	}

	$GLOBALS['wc_bulk_variations'] = new WC_Bulk_Variations();
}
?>