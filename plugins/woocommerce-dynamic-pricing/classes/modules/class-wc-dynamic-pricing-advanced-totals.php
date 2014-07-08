<?php

class WC_Dynamic_Pricing_Advanced_Totals extends WC_Dynamic_Pricing_Advanced_Base {
	private static $instance;

	public static function instance() {
		if (self::$instance == null) {
			self::$instance = new WC_Dynamic_Pricing_Advanced_Totals('advanced_totals');
		}
		return self::$instance;
	}

	public $adjustment_sets;

	public function __construct($module_id) {
		parent::__construct($module_id);

		$sets = get_option('_a_totals_pricing_rules');
		if ($sets && is_array($sets) && sizeof($sets) > 0) {
			foreach ($sets as $id => $set_data) {
				$obj_adjustment_set = new WC_Dynamic_Pricing_Adjustment_Set_Totals($id, $set_data);
				$this->adjustment_sets[$id] = $obj_adjustment_set;
			}
		}
	}

	public function adjust_cart($temp_cart) {
		$q = $this->get_cart_total();
		$num_decimals = apply_filters('woocommerce_dynamic_pricing_get_decimals', (int) get_option('woocommerce_price_num_decimals'));

		if ($this->adjustment_sets && count($this->adjustment_sets)) {
			foreach ($this->adjustment_sets as $set_id => $set) {
				$matched = false;
				$pricing_rules = $set->pricing_rules;
				$is_valid_for_user = $set->is_valid_for_user();

				if ($is_valid_for_user && is_array($pricing_rules) && sizeof($pricing_rules) > 0) {
					foreach ($pricing_rules as $rule) {
						if ($rule['from'] == '*') {
							$rule['from'] = 0;
						}

						if (empty($rule['to']) || $rule['to'] == '*') {
							$rule['to'] = $q;
						}

						if ($q >= $rule['from'] && $q <= $rule['to']) {

							$matched = true;

							//Adjust the cart items. 
							foreach ($temp_cart as $cart_item_key => $cart_item) {
								$process_discounts = apply_filters('woocommerce_dynamic_pricing_process_product_discounts', true, $cart_item['data'], 'advanced_totals', $this);
								if (!$process_discounts) {
									continue;
								}

								if (!$this->is_cumulative($cart_item, $cart_item_key)) {
									if ($this->is_item_discounted($cart_item, $cart_item_key)) {
										continue;
									}
								}

								$original_price = $this->get_price_to_discount($cart_item, $cart_item_key);
								if ($original_price) {

									if ($rule['amount'] > 1) {
										$rule['amount'] = $rule['amount'] / 100;
									}

									$price_adjusted = round(floatval($original_price) - ( floatval($rule['amount']) * $original_price), (int) $num_decimals);
									WC_Dynamic_Pricing::apply_cart_item_adjustment($cart_item_key, $original_price, $price_adjusted, $this->module_id, $set_id);
								}
							}
						}
					}
				}

				//Only process the first matched rule set
				if ($matched) {
					return;
				}
			}
		}
	}

	private function get_cart_total() {
		global $woocommerce;

		$quantity = 0;
		foreach ($woocommerce->cart->cart_contents as $cart_item) {

			$process_discounts = apply_filters('woocommerce_dynamic_pricing_process_product_discounts', true, $cart_item['data'], 'advanced_totals', $this);
			if ($process_discounts) {
				$q = $cart_item['quantity'] ? $cart_item['quantity'] : 1;

				if (isset($cart_item['discounts']) && isset($cart_item['discounts']['by']) && $cart_item['discounts']['by'] == $this->module_id) {
					$quantity += floatval($cart_item['discounts']['price_base']) * $q;
				} else {
					$quantity += $cart_item['data']->get_price() * $q;
				}
			}
		}

		return $quantity;
	}

}

?>