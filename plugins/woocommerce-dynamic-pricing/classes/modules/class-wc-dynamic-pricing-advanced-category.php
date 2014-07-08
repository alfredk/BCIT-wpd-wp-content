<?php

class WC_Dynamic_Pricing_Advanced_Category extends WC_Dynamic_Pricing_Advanced_Base {
	private static $instance;

	public static function instance() {
		if (self::$instance == null) {
			self::$instance = new WC_Dynamic_Pricing_Advanced_Category('advanced_category');
		}
		return self::$instance;
	}

	public $adjustment_sets;

	public function __construct($module_id) {
		parent::__construct($module_id);
		$sets = get_option('_a_category_pricing_rules');
		if ($sets && is_array($sets) && sizeof($sets) > 0) {
			foreach ($sets as $id => $set_data) {
				$obj_adjustment_set = new WC_Dynamic_Pricing_Adjustment_Set_Category($id, $set_data);
				$this->adjustment_sets[$id] = $obj_adjustment_set;
			}
		}
	}

	public function adjust_cart($temp_cart) {

		if ($this->adjustment_sets && count($this->adjustment_sets)) {

			$valid_sets = wp_list_filter($this->adjustment_sets, array('is_valid_rule' => true, 'is_valid_for_user' => true));
			if (empty($valid_sets)) {
				return;
			}

			foreach ($temp_cart as $cart_item_key => $values) {
				$temp_cart[$cart_item_key] = $values;
				$temp_cart[$cart_item_key]['available_quantity'] = $values['quantity'];
			}

			//Process block discounts first
			foreach ($valid_sets as $set_id => $set) {

				if ($set->mode != 'block') {
					continue;
				}

				//check if this set is valid for the current user;
				$is_valid_for_user = $set->is_valid_for_user();


				if (!($is_valid_for_user)) {
					continue;
				}


				//Lets actuall process the rule. 
				//Setup the matching quantity
				$targets = $set->targets;

				$collector = $set->get_collector();
				$q = 0;
				if (isset($collector['args']) && isset($collector['args']['cats']) && is_array($collector['args']['cats'])) {
					foreach ($collector['args']['cats'] as $cat_id) {
						$q += WC_Dynamic_Pricing_Counter::get_category_count($cat_id);
					}
				} else {
					continue; //no categories
				}

				$rule = reset($set->pricing_rules); //block rules can only have one line item. 
				if ($q < $rule['from']) {
					//continue;
				}
				if ($rule['repeating'] == 'yes') {
					$b = floor($q / ( $rule['from'] )); //blocks - this is how many times has the required amount been met. 
				} else {
					$b = 1;
				}

				$ct = 0; //clean targets
				$mt = 0;

				$cq = 0; //matched clean quantity;
				$mq = 0; //matched mixed quantity;

				foreach ($temp_cart as $cart_item_key => &$cart_item) {
					$terms = wp_get_post_terms($cart_item['product_id'], 'product_cat', array('fields' => 'ids'));
					if (count(array_intersect($collector['args']['cats'], $terms)) > 0) {
						if (count(array_intersect($targets, $terms)) > 0) {
							$mq += $cart_item['available_quantity'];
						} else {
							$cq += $cart_item['available_quantity'];
						}
					}

					if (count(array_intersect($targets, $terms)) > 0) {
						if (count(array_intersect($collector['args']['cats'], $terms)) == 0) {
							$ct += $cart_item['quantity'];
						} else {
							$mt += $cart_item['quantity'];
						}
					}
				}

				$rt = $ct + $mt; //remaining targets. 
				$rcq = $cq; //remaining clean quantity
				$rmq = $mq; //remaining mixed quantity

				$tt = 0; //the total number of items we can discount. 
				//for each block reduce the amount of remaining items which can make up a discount by the amount required. 
				for ($x = 0; $x < $b; $x++) {
					//If the remaining clean quantity minus what is required to make a block is greater than 0 there are more clean quantity items remaining. 
					//This means we don't have to eat into mixed quantities yet. 
					if ($rcq - $rule['from'] >= 0) {
						$rcq -= $rule['from'];
						$tt += $rule['adjust'];
						//If the total items that can be dicsounted is greater than the number of clean items to be discounted, reduce the 
						//mixed quantity by the difference, because those items will be discounted and can not count towards making another discounted item. 
						if ($tt > $ct) {
							$rmq -= ($tt - $ct);
						}

						if ($tt > $mt + $ct) {
							$tt = $mt + $ct;
						}

						$rt -= ($ct + $mt) - $tt;
					} else {
						//how many items left over from clean quantities.  if we have a buy two get one free, we may have one quantity of clean item, and two mixed items. 
						$l = $rcq ? $rule['from'] - $rcq : 0;
						if ($rcq > 0) {
							//If the remaining mixed quantity minus the left overs trigger items is more than 0, we have another discount available
							if ($rt - $l > 0) {
								$tt += min($rt - $l, $rule['adjust']);
							}

							$rt -= ($ct + $mt) - $tt;
						} else {
							$rt -= $rule['from'];
							//$rt -= ($ct + $mt) - $tt;
							if ($rt > 0) {
								$tt += min($rt, $rule['adjust']);
								$rt -= min($rt, $rule['adjust']);
								$rmq = $rmq - $l - ($rule['adjust'] + $rule['from']);
							}
						}

						$rcq = 0;
					}
				}

				foreach ($temp_cart as $cart_item_key => $ctitem) {
					$price_adjusted = false;

					$original_price = $this->get_price_to_discount($ctitem, $cart_item_key);


					$terms = wp_get_post_terms($ctitem['product_id'], 'product_cat', array('fields' => 'ids'));
					if (count(array_intersect($targets, $terms)) > 0) {

						$price_adjusted = $this->get_block_adjusted_price($ctitem, $original_price, $rule, $tt);

						if ($tt > $ctitem['quantity']) {
							$tt -= $ctitem['quantity'];
							$temp_cart[$cart_item_key]['available_quantity'] = 0;
						} else {
							$temp_cart[$cart_item_key]['available_quantity'] = $ctitem['quantity'] - $tt;
							$tt = 0;
						}

						if ($price_adjusted !== false && floatval($original_price) != floatval($price_adjusted)) {
							WC_Dynamic_Pricing::apply_cart_item_adjustment($cart_item_key, $original_price, $price_adjusted, 'advanced_category', $set_id);
						}
					}
				}
			}


			//Now process bulk rules
			foreach ($valid_sets as $set_id => $set) {
				if ($set->mode != 'bulk') {
					continue;
				}


				//check if this set is valid for the current user;
				$is_valid_for_user = $set->is_valid_for_user();

				if (!($is_valid_for_user)) {
					continue;
				}

				//Lets actuall process the rule. 
				//Setup the matching quantity
				$targets = $set->targets;

				//Get the quantity to compare
				$collector = $set->get_collector();
				$q = 0;
				foreach ($temp_cart as $t_cart_item_key => $t_cart_item) {
					$terms = wp_get_post_terms($t_cart_item['product_id'], 'product_cat', array('fields' => 'ids'));
					if (count(array_intersect($targets, $terms)) > 0) {
						if (!$this->is_cumulative($t_cart_item, $t_cart_item_key)) {
							if ($this->is_item_discounted($t_cart_item, $t_cart_item_key)) {
								continue;
							}
						}

						if (isset($collector['type']) && $collector['type'] == 'cat_product') {
							$q = $t_cart_item['quantity'];
						} else {
							$q = 0;
							if (isset($collector['args']) && isset($collector['args']['cats']) && is_array($collector['args']['cats'])) {
								foreach ($temp_cart as $lck => $l_cart_item) {
									if (is_object_in_term($l_cart_item['product_id'], 'product_cat', $collector['args']['cats'])) {
										$q += (int) $l_cart_item['quantity'];
									}
								}
							}
						}

						$price_adjusted = false;
						$original_price = $this->get_price_to_discount($t_cart_item, $t_cart_item_key);


						if (is_array($set->pricing_rules) && sizeof($set->pricing_rules) > 0) {
							foreach ($set->pricing_rules as $rule) {
								$price_adjusted = $this->get_bulk_adjusted_price($t_cart_item, $original_price, $rule, $q);
								if ($price_adjusted !== false) {
									break;
								}
							}
						}

						if ($price_adjusted !== false && floatval($original_price) != floatval($price_adjusted)) {
							WC_Dynamic_Pricing::apply_cart_item_adjustment($t_cart_item_key, $original_price, $price_adjusted, 'advanced_category', $set_id);
						}
					}
				}
			}
		}
	}

	//calculate the block based price
	private function get_block_adjusted_price($cart_item, $price, $rule, $a) {
		if ($a > $cart_item['quantity']) {
			$a = $cart_item['quantity'];
		}
		$num_decimals = apply_filters('woocommerce_dynamic_pricing_get_decimals', (int) get_option('woocommerce_price_num_decimals'));
		switch ($rule['type']) {
			case 'fixed_adjustment':
				$adjusted = floatval($price) - floatval($rule['amount']);
				$adjusted = $adjusted >= 0 ? $adjusted : 0;
				$line_total = 0;
				$full_price_quantity = $cart_item['quantity'] - $a;

				$discount_quantity = $a;

				$line_total = ($discount_quantity * $adjusted) + ($full_price_quantity * $price);
				$result = $line_total / $cart_item['quantity'];
				$result = $result >= 0 ? $result : 0;

				break;
			case 'percent_adjustment':
				if ($rule['amount'] > 1) {
					$rule['amount'] = $rule['amount'] / 100;
				}
				$adjusted = round(floatval($price) - ( floatval($rule['amount']) * $price), (int) $num_decimals);
				$line_total = 0;

				$full_price_quantity = $cart_item['available_quantity'] - $a;
				$discount_quantity = $a;

				$line_total = ($discount_quantity * $adjusted) + ($full_price_quantity * $price);
				$result = $line_total / $cart_item['quantity'];

				if ($cart_item['available_quantity'] != $cart_item['quantity']) {
					
				}

				$result = $result >= 0 ? $result : 0;
				break;
			case 'fixed_price':
				$adjusted = round($rule['amount'], (int) $num_decimals);
				$line_total = 0;
				$full_price_quantity = $cart_item['quantity'] - $a;
				$discount_quantity = $a;
				$line_total = ($discount_quantity * $adjusted) + ($full_price_quantity * $price);
				$result = $line_total / $cart_item['quantity'];
				$result = $result >= 0 ? $result : 0;

				break;
			default:
				$result = false;
				break;
		}

		return $result;
	}

	private function get_bulk_adjusted_price($cart_item, $price, $rule, $q) {
		$result = false;
		$num_decimals = apply_filters('woocommerce_dynamic_pricing_get_decimals', (int) get_option('woocommerce_price_num_decimals'));


		if ($rule['from'] == '*') {
			$rule['from'] = 0;
		}

		if (empty($rule['to']) || $rule['to'] == '*') {
			$rule['to'] = $q;
		}

		if ($q >= $rule['from'] && $q <= $rule['to']) {
			switch ($rule['type']) {
				case 'price_discount':
					$adjusted = floatval($price) - floatval($rule['amount']);
					$result = $adjusted >= 0 ? $adjusted : 0;
					break;
				case 'percentage_discount':

					if ($rule['amount'] > 1) {
						$rule['amount'] = $rule['amount'] / 100;
					}

					$result = round(floatval($price) - ( floatval($rule['amount']) * $price), (int) $num_decimals);
					break;
				case 'fixed_price':
					$result = round($rule['amount'], (int) $num_decimals);
					break;
				default:
					$result = false;
					break;
			}
		}


		return $result;
	}

	public function get_adjusted_price($cart_item_key, $cart_item) {
		
	}

}

?>