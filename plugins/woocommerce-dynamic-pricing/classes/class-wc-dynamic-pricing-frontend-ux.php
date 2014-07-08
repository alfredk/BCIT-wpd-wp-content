<?php

class WC_Dynamic_Pricing_FrontEnd_UX {
	private static $instance;

	public static function init() {
		if (self::$instance == null) {
			self::$instance = new WC_Dynamic_Pricing_FrontEnd_UX();
		}
	}

	public function __construct() {
		add_action('init', array($this, 'on_init'), 0);
	}

	public function on_init() {
		//Filter for the cart adjustment for advanced rules. 
		if (WC_Dynamic_Pricing_Compatibility::is_wc_version_gte_2_1()) {
			add_filter('woocommerce_cart_item_price', array(&$this, 'on_display_cart_item_price_html'), 10, 3);
		} else {
			add_filter('woocommerce_cart_item_price_html', array(&$this, 'on_display_cart_item_price_html'), 10, 3);
		}
	}

	public function on_display_cart_item_price_html($html, $cart_item, $cart_item_key) {
		if ($this->is_cart_item_discounted($cart_item)) {
			$_product = $cart_item['data'];

			if (function_exists('get_product')) {
				$price_adjusted = get_option('woocommerce_tax_display_cart') == 'excl' ? $_product->get_price_excluding_tax() : $_product->get_price_including_tax();
				$price_base = $cart_item['discounts']['display_price'];
			} else {
				if (get_option('woocommerce_display_cart_prices_excluding_tax') == 'yes') :
					$price_adjusted = $cart_item['data']->get_price_excluding_tax();
					$price_base = $cart_item['discounts']['display_price'];
				else :
					$price_adjusted = $cart_item['data']->get_price();
					$price_base = $cart_item['discounts']['display_price'];
				endif;
			}
			
			if (!empty($price_adjusted) || $price_adjusted === 0) {
				if (apply_filters('wc_dynamic_pricing_use_discount_format', true)) {
					$html = '<del>' . WC_Dynamic_Pricing_Compatibility::wc_price($price_base) . '</del><ins> ' . WC_Dynamic_Pricing_Compatibility::wc_price($price_adjusted) . '</ins>';
				} else {
					$html = '<span class="amount">' . WC_Dynamic_Pricing_Compatibility::wc_price($price_adjusted) . '</span>';
				}
			}
		}

		return $html;
	}

	public function is_cart_item_discounted($cart_item) {
		return isset($cart_item['discounts']);
	}

}

?>