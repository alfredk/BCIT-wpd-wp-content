<?php

class WC_Dynamic_Pricing_Simple_Category extends WC_Dynamic_Pricing_Simple_Base {

	private static $instance;

	public static function instance() {
		if ( self::$instance == null ) {
			self::$instance = new WC_Dynamic_Pricing_Simple_Category( 'simple_category' );
		}
		return self::$instance;
	}

	public function __construct( $module_id ) {
		parent::__construct( $module_id );
	}

	public function initialize_rules() {
		$pricing_rule_sets = get_option( '_s_category_pricing_rules', array() );

		if ( is_array( $pricing_rule_sets ) && sizeof( $pricing_rule_sets ) > 0 ) {
			foreach ( $pricing_rule_sets as $set_id => $pricing_rule_set ) {
				$execute_rules = false;
				$conditions_met = 0;
				$pricing_conditions = $pricing_rule_set['conditions'];
				if ( is_array( $pricing_conditions ) && sizeof( $pricing_conditions ) > 0 ) {
					foreach ( $pricing_conditions as $condition ) {
						$conditions_met += $this->handle_condition( $condition );
					}
					if ( $pricing_rule_set['conditions_type'] == 'all' ) {
						$execute_rules = $conditions_met == count( $pricing_conditions );
					} elseif ( $pricing_rule_set['conditions_type'] == 'any' ) {
						$execute_rules = $conditions_met > 0;
					}
				} else {
					//empty conditions - default match, process price adjustment rules
					$execute_rules = true;
				}

				if ( $execute_rules && isset( $pricing_rule_set['collector']['args']['cats'][0] ) ) {
					$this->available_rulesets[$set_id] = $pricing_rule_set;
				}
			}
		}
	}

	public function adjust_cart( $cart ) {
		global $woocommerce;

		if ( $this->available_rulesets && count( $this->available_rulesets ) ) {

			foreach ( $cart as $cart_item_key => $cart_item ) {
				$process_discounts = apply_filters( 'woocommerce_dynamic_pricing_process_product_discounts', true, $cart_item['data'], 'simple_category', $this );
				if ( !$process_discounts ) {
					continue;
				}

				if ( !$this->is_cumulative( $cart_item, $cart_item_key ) ) {

					if ( $this->is_item_discounted( $cart_item, $cart_item_key ) ) {
						continue;
					}
				}

				$original_price = $this->get_price_to_discount( $cart_item, $cart_item_key );

				$_product = $cart_item['data'];
				$price_adjusted = false;
				$applied_rule = false;
				$applied_rule_set = false;
				$applied_rule_set_id;

				foreach ( $this->available_rulesets as $set_id => $pricing_rule_set ) {

					if ( $this->is_applied_to_product( $_product, $pricing_rule_set['collector']['args']['cats'][0] ) ) {
						$rule = $pricing_rule_set['rules'][0];

						$temp = $this->get_adjusted_price( $rule, $original_price );

						if ( !$price_adjusted || $temp < $price_adjusted ) {
							$price_adjusted = $temp;
							$applied_rule = $rule;
							$applied_rule_set = $pricing_rule_set;
							$applied_rule_set_id = $set_id;
						}
					}
				}

				if ( $price_adjusted !== false && floatval( $original_price ) != floatval( $price_adjusted ) ) {
					WC_Dynamic_Pricing::apply_cart_item_adjustment( $cart_item_key, $original_price, $price_adjusted, $this->module_id, $applied_rule_set_id );
				}
			}
		}
	}

	public function is_applied_to_product( $_product, $cat_id = false ) {
		if ( is_admin() && !is_ajax() ) {
			return false;
		}

		$process_discounts = false;
		if ( isset( $this->available_rulesets ) && count( $this->available_rulesets ) > 0 ) {
			if ( $cat_id ) {
				$process_discounts = is_object_in_term( $_product->id, 'product_cat', $cat_id );
			}
		}

		return apply_filters( 'woocommerce_dynamic_pricing_is_applied_to', $process_discounts, $_product, $this->module_id, $this, $cat_id );
	}

	private function get_adjusted_price( $rule, $price ) {
		$result = false;
		$num_decimals = apply_filters( 'woocommerce_dynamic_pricing_get_decimals', (int) get_option( 'woocommerce_price_num_decimals' ) );

		switch ( $rule['type'] ) {
			case 'fixed_product':
				$adjusted = floatval( $price ) - floatval( $rule['amount'] );
				$result = $adjusted >= 0 ? $adjusted : 0;
				break;
			case 'percent_product':
				if ( $rule['amount'] > 1 ) {
					$rule['amount'] = $rule['amount'] / 100;
				}
				$result = round( floatval( $price ) - ( floatval( $rule['amount'] ) * $price), (int) $num_decimals );
				break;
			case 'fixed_price':
				$result = round( $rule['amount'], (int) $num_decimals );
				break;
			default:
				$result = false;
				break;
		}


		return $result;
	}

	private function handle_condition( $condition ) {
		$result = 0;
		switch ( $condition['type'] ) {
			case 'apply_to':
				if ( is_array( $condition['args'] ) && isset( $condition['args']['applies_to'] ) ) {
					if ( $condition['args']['applies_to'] == 'everyone' ) {
						$result = 1;
					} elseif ( $condition['args']['applies_to'] == 'unauthenticated' ) {
						if ( !is_user_logged_in() ) {
							$result = 1;
						}
					} elseif ( $condition['args']['applies_to'] == 'authenticated' ) {
						if ( is_user_logged_in() ) {
							$result = 1;
						}
					} elseif ( $condition['args']['applies_to'] == 'roles' && isset( $condition['args']['roles'] ) && is_array( $condition['args']['roles'] ) ) {
						if ( is_user_logged_in() ) {
							foreach ( $condition['args']['roles'] as $role ) {
								if ( current_user_can( $role ) ) {
									$result = 1;
									break;
								}
							}
						}
					}
				}
				break;
			default:
				break;
		}

		return $result;
	}

	public function get_discounted_price_for_shop( $_product, $working_price ) {
		$price_adjusted = false;
		$applied_rule = false;
		$applied_rule_set = false;
		if ( $this->available_rulesets && count( $this->available_rulesets ) ) {
			foreach ( $this->available_rulesets as $set_id => $pricing_rule_set ) {
				$process_discounts = apply_filters( 'woocommerce_dynamic_pricing_process_product_discounts', true,  $_product, 'simple_category', $this );
				if ( $process_discounts ) {

					if ( $this->is_applied_to_product( $_product, $pricing_rule_set['collector']['args']['cats'][0] ) ) {
						$rule = $pricing_rule_set['rules'][0];

						$temp = $this->get_adjusted_price( $rule, $working_price );

						if ( !$price_adjusted || $temp < $price_adjusted ) {
							$price_adjusted = $temp;
							$applied_rule = $rule;
							$applied_rule_set = $pricing_rule_set;
						}
					}
				}

				if ( $price_adjusted !== false && floatval( $working_price ) != floatval( $price_adjusted ) ) {
					return $price_adjusted;
				}
			}
		}

		return $working_price;
	}

}

?>