<?php

/*
  Plugin Name: WooCommerce Dynamic Pricing
  Plugin URI: http://www.woothemes.com/woocommerce
  Description: WooCommerce Dynamic Pricing lets you configure dynamic pricing rules for products, categories and members. For WooCommerce 1.4+
  Version: 2.5.6
  Author: Lucas Stark
  Author URI: http://lucasstark.com
  Requires at least: 3.3
  Tested up to: 3.5.1

  Copyright: © 2009-2011 Lucas Stark.
  License: GNU General Public License v3.0
  License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */


/**
 * Required functions
 */
if (!function_exists('woothemes_queue_update')) {
	require_once( 'woo-includes/woo-functions.php' );
}

/**
 * Plugin updates
 */
woothemes_queue_update(plugin_basename(__FILE__), '9a41775bb33843f52c93c922b0053986', '18643');

if (is_woocommerce_active()) {


	/**
	 * Localisation
	 * */
	load_plugin_textdomain('wc_pricing', false, dirname(plugin_basename(__FILE__)) . '/languages/');



	/**
	 * Boot up dynamic pricing
	 */
	WC_Dynamic_Pricing::init();
}


class WC_Dynamic_Pricing {
	private static $instance;

	public static function init() {
		if (self::$instance == null) {
			self::$instance = new WC_Dynamic_Pricing();
		}
	}

	public static function instance() {
		if (self::$instance == null) {
			self::init();
		}

		return self::$instance;
	}

	public $modules = array();

	public function __construct() {
		add_action('woocommerce_cart_loaded_from_session', array(&$this, 'on_cart_loaded_from_session'), 99, 1);

		//Add the actions dynamic pricing uses to trigger price adjustments
		add_action('woocommerce_before_calculate_totals', array(&$this, 'on_calculate_totals'), 99, 1);


		if (is_admin()) {
			require 'admin/admin-init.php';
		}

		//Include additional integrations
		if (wc_dynamic_pricing_is_groups_active()) {
			include 'integrations/groups/groups.php';
		}

		include 'classes/class-wc-dynamic-pricing-compatibility.php';

		if (!is_admin() || defined('DOING_AJAX')) {
			//Include helper classes
			include 'classes/class-wc-dynamic-pricing-counter.php';
			include 'classes/class-wc-dynamic-pricing-tracker.php';
			include 'classes/class-wc-dynamic-pricing-cart-query.php';


			include 'classes/class-wc-dynamic-pricing-adjustment-set.php';
			include 'classes/class-wc-dynamic-pricing-adjustment-set-category.php';
			include 'classes/class-wc-dynamic-pricing-adjustment-set-product.php';
			include 'classes/class-wc-dynamic-pricing-adjustment-set-totals.php';


			//The base pricing module.
			include 'classes/modules/class-wc-dynamic-pricing-module-base.php';

			//Include the advanced pricing modules.
			include 'classes/modules/class-wc-dynamic-pricing-advanced-base.php';
			include 'classes/modules/class-wc-dynamic-pricing-advanced-product.php';
			include 'classes/modules/class-wc-dynamic-pricing-advanced-category.php';
			include 'classes/modules/class-wc-dynamic-pricing-advanced-totals.php';

			//Include the simple pricing modules.
			include 'classes/modules/class-wc-dynamic-pricing-simple-base.php';
			include 'classes/modules/class-wc-dynamic-pricing-simple-product.php';
			include 'classes/modules/class-wc-dynamic-pricing-simple-category.php';
			include 'classes/modules/class-wc-dynamic-pricing-simple-membership.php';




			//Include the UX module - This controls the display of discounts on cart items and products.
			include 'classes/class-wc-dynamic-pricing-frontend-ux.php';


			//Boot up the instances of the pricing modules
			$modules['advanced_product'] = WC_Dynamic_Pricing_Advanced_Product::instance();
			$modules['advanced_category'] = WC_Dynamic_Pricing_Advanced_Category::instance();

			$modules['simple_product'] = WC_Dynamic_Pricing_Simple_Product::instance();
			$modules['simple_category'] = WC_Dynamic_Pricing_Simple_Category::instance();
			$modules['simple_membership'] = WC_Dynamic_Pricing_Simple_Membership::instance();

			if (wc_dynamic_pricing_is_groups_active()) {
				include 'integrations/groups/class-wc-dynamic-pricing-simple-group.php';
				$modules['simple_group'] = WC_Dynamic_Pricing_Simple_Group::instance();
			}

			$modules['advanced_totals'] = WC_Dynamic_Pricing_Advanced_Totals::instance();

			$this->modules = apply_filters('wc_dynamic_pricing_load_modules', $modules);



			/* Boot up required classes */
			//Initialize the dynamic pricing counter.  Records various counts when items are restored from session.
			WC_Dynamic_Pricing_Counter::init();

			//Initialize the FrontEnd UX modifications
			WC_Dynamic_Pricing_FrontEnd_UX::init();


			//Filters for simple adjustment types
			add_filter('woocommerce_grouped_price_html', array(&$this, 'on_price_html'), 10, 2);
			add_filter('woocommerce_variable_price_html', array(&$this, 'on_price_html'), 10, 2);
			add_filter('woocommerce_sale_price_html', array(&$this, 'on_price_html'), 10, 2);
			add_filter('woocommerce_price_html', array(&$this, 'on_price_html'), 10, 2);

			add_filter('woocommerce_empty_price_html', array(&$this, 'on_price_html'), 10, 2);

			add_filter('woocommerce_variation_price_html', array(&$this, 'on_price_html'), 10, 2);
			add_filter('woocommerce_variation_sale_price_html', array(&$this, 'on_price_html'), 10, 2);
		}
	}

	public function on_cart_loaded_from_session($cart) {
		global $woocommerce;

		$sorted_cart = array();
		if (sizeof($cart->cart_contents) > 0) {
			foreach ($cart->cart_contents as $cart_item_key => $values) {
				$sorted_cart[$cart_item_key] = $values;
			}
		}

		//Sort the cart so that the lowest priced item is discounted when using block rules.
		@uasort($sorted_cart, 'WC_Dynamic_Pricing_Cart_Query::sort_by_price');

		$modules = apply_filters('wc_dynamic_pricing_load_modules', $this->modules);
		foreach ($modules as $module) {
			$module->adjust_cart($sorted_cart);
		}
	}

	public function on_calculate_totals($cart) {
		global $woocommerce;

		$sorted_cart = array();
		if (sizeof($cart->cart_contents) > 0) {
			foreach ($cart->cart_contents as $cart_item_key => $values) {
				$sorted_cart[$cart_item_key] = $values;
			}
		}

		//Sort the cart so that the lowest priced item is discounted when using block rules.
		uasort($sorted_cart, 'WC_Dynamic_Pricing_Cart_Query::sort_by_price');

		$modules = apply_filters('wc_dynamic_pricing_load_modules', $this->modules);
		foreach ($modules as $module) {
			$module->adjust_cart($sorted_cart);
		}
	}

	public function on_price_html($html, $_product) {

		$from = strstr($html, __('From', 'woocommerce')) !== false ? ' ' . __('From', 'woocommerce') . ' ' : ' ';

		$discount_price = false;
		$id = isset($_product->variation_id) ? $_product->variation_id : $_product->id;
		$working_price = isset($this->discounted_products[$id]) ? $this->discounted_products[$id] : $_product->get_price();

		$base_price = $_product->get_price();

		foreach ($this->modules as $module) {
			if ($module->module_type == 'simple') {

				//Make sure we are using the price that was just discounted.
				$working_price = $discount_price ? $discount_price : $base_price;

				$working_price = $module->get_product_working_price($working_price, $_product);

				if (floatval($working_price)) {
					$discount_price = $module->get_discounted_price_for_shop($_product, $working_price);
					if ($discount_price && $discount_price != $base_price) {
						if (apply_filters('wc_dynamic_pricing_use_discount_format', true)) {
							
							if ($_product->is_type('variable')) {
								$from = '<span class="from">' . _x('From:', 'min_price', 'woocommerce') . ' </span>';
							}
							
							$html = '<del>' . WC_Dynamic_Pricing_Compatibility::wc_price($base_price) . '</del><ins> ' . $from . WC_Dynamic_Pricing_Compatibility::wc_price($discount_price) . '</ins>';
						} else {
							
							if ($_product->is_type('variable')) {
								$from = '<span class="from">' . _x('From:', 'min_price', 'woocommerce') . ' </span>';
							}
							
							$html = $from . WC_Dynamic_Pricing_Compatibility::wc_price($discount_price);
						}
					} elseif ($discount_price === 0 || $discount_price === 0.00) {
						$html = $_product->get_price_html_from_to($_product->regular_price, __('Free!', 'woocommerce'));
					}
				}
			}
		}

		$this->discounted_products[$id] = $discount_price ? $discount_price : $base_price;
		return $html;
	}

	//Helper functions to modify the woocommerce cart.  Called from the individual modules.
	public static function apply_cart_item_adjustment($cart_item_key, $original_price, $adjusted_price, $module, $set_id) {
		global $woocommerce;
		if (isset($woocommerce->cart->cart_contents[$cart_item_key])) {
			$_product = $woocommerce->cart->cart_contents[$cart_item_key]['data'];
			$display_price = get_option('woocommerce_tax_display_cart') == 'excl' ? $_product->get_price_excluding_tax() : $_product->get_price_including_tax();

			$woocommerce->cart->cart_contents[$cart_item_key]['data']->price = $adjusted_price;

			if (!isset($woocommerce->cart->cart_contents[$cart_item_key]['discounts'])) {

				$discount_data = array(
				    'by' => array($module),
				    'set_id' => $set_id,
				    'price_base' => $original_price,
				    'display_price' => $display_price,
				    'price_adjusted' => $adjusted_price,
				    'applied_discounts' => array(array('by' => $module, 'set_id' => $set_id, 'price_base' => $original_price, 'price_adjusted' => $adjusted_price))
				);
				$woocommerce->cart->cart_contents[$cart_item_key]['discounts'] = $discount_data;
			} else {

				$existing = $woocommerce->cart->cart_contents[$cart_item_key]['discounts'];

				$discount_data = array(
				    'by' => $existing['by'],
				    'set_id' => $set_id,
				    'price_base' => $original_price,
				    'display_price' => $existing['display_price'],
				    'price_adjusted' => $adjusted_price
				);

				$woocommerce->cart->cart_contents[$cart_item_key]['discounts'] = $discount_data;

				$history = array('by' => $existing['by'], 'set_id' => $existing['set_id'], 'price_base' => $existing['price_base'], 'price_adjusted' => $existing['price_adjusted']);
				array_push($woocommerce->cart->cart_contents[$cart_item_key]['discounts']['by'], $module);
				$woocommerce->cart->cart_contents[$cart_item_key]['discounts']['applied_discounts'][] = $history;
			}
		}
		
		do_action('woocommerce_dynamic_pricing_apply_cartitem_adjustment', $cart_item_key, $original_price, $adjusted_price, $module, $set_id);
	}

	/** Helper functions ***************************************************** */
	/**
	 * Get the plugin url.
	 *
	 * @access public
	 * @return string
	 */
	public static function plugin_url() {
		return plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__));
	}

	/**
	 * Get the plugin path.
	 *
	 * @access public
	 * @return string
	 */
	public static function plugin_path() {
		return untrailingslashit(plugin_dir_path(__FILE__));
	}

}

/* Helper Functions */
function wc_dynamic_pricing_is_groups_active() {
	$result = false;
	$result = in_array('groups/groups.php', (array) get_option('active_plugins', array()));
	if (!$result && is_multisite()) {
		$plugins = get_site_option('active_sitewide_plugins');
		$result = isset($plugins['groups/groups.php']);
	}

	return $result;
}