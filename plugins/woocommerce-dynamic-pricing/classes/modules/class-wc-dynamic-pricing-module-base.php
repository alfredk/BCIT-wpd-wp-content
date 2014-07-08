<?php

abstract class WC_Dynamic_Pricing_Module_Base {
	public $module_id;
	public $module_type;

	public function __construct($module_id, $module_type) {
		$this->module_id = $module_id;
		$this->module_type = $module_type;
	}

	public abstract function adjust_cart($cart);
	public function get_price_to_discount($cart_item, $cart_item_key) {
		global $woocommerce;

		$result = false;
		
		$filter_cart_item = $cart_item;
		if (isset($woocommerce->cart->cart_contents[$cart_item_key])) {
			$filter_cart_item  = $woocommerce->cart->cart_contents[$cart_item_key];
			
			if (isset($woocommerce->cart->cart_contents[$cart_item_key]['discounts'])) {
				if ($this->is_cumulative($cart_item, $cart_item_key)) {
					$result = $woocommerce->cart->cart_contents[$cart_item_key]['discounts']['price_adjusted'];
				} else {
					$result = $woocommerce->cart->cart_contents[$cart_item_key]['discounts']['price_base'];
				}
			} else {
				$result = $woocommerce->cart->cart_contents[$cart_item_key]['data']->get_price();
			}
		}

		return apply_filters('woocommerce_dynamic_pricing_get_price_to_discount', $result, $filter_cart_item, $cart_item_key);
	}

	protected function is_item_discounted($cart_item, $cart_item_key) {
		global $woocommerce;

		return isset($woocommerce->cart->cart_contents[$cart_item_key]['discounts']);
	}

	protected function is_cumulative($cart_item, $cart_item_key, $default = false) {
		global $woocommerce;
		//Check to make sure the item has not already been discounted by this module.  This could happen if update_totals is called more than once in the cart. 
		if (isset($woocommerce->cart->cart_contents[$cart_item_key]['discounts']) && in_array($this->module_id, $woocommerce->cart->cart_contents[$cart_item_key]['discounts']['by'])) {
			return false;
		} else {
			return apply_filters('woocommerce_dynamic_pricing_is_cumulative', $default, $this->module_id, $cart_item, $cart_item_key);
		}
	}

	protected function reset_cart_item(&$cart_item, $cart_item_key) {
		global $woocommerce;
		if (isset($woocommerce->cart->cart_contents[$cart_item_key]['discounts']) && in_array($this->module_id, $woocommerce->cart->cart_contents[$cart_item_key]['discounts']['by'])) {
			foreach ($woocommerce->cart->cart_contents[$cart_item_key]['discounts'] as $module) {
				
			}
		}
	}

}

?>