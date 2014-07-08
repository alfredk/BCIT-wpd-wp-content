<?php

abstract class WC_Dynamic_Pricing_Simple_Base extends WC_Dynamic_Pricing_Module_Base {

    public $available_rulesets;

    public function __construct($module_id) {
        parent::__construct($module_id, 'simple');

        add_action('init', array(&$this, 'initialize_rules'), 0);
    }

    public abstract function initialize_rules();

    public abstract function is_applied_to_product($product);

    public abstract function get_discounted_price_for_shop($product, $working_price);

    protected function is_cumulative($cart_item, $cart_item_key, $default = false) {
        global $woocommerce;
        //Check to make sure the item has not already been discounted by this module.  This could happen if update_totals is called more than once in the cart. 
        if (isset($woocommerce->cart->cart_contents[$cart_item_key]['discounts'])) {
            if (in_array($this->module_id, $woocommerce->cart->cart_contents[$cart_item_key]['discounts']['by'])) {
                return false;
            } elseif (count(array_intersect(array('simple_category', 'simple_membership'), $woocommerce->cart->cart_contents[$cart_item_key]['discounts']['by'])) > 0) {
                return true;
            }
        } else {
            return apply_filters('woocommerce_dynamic_pricing_is_cumulative', $default, $this->module_id, $cart_item, $cart_item_key);
        }
    }

    public function get_product_working_price($working_price, $product) {

        return apply_filters('woocommerce_dynamic_pricing_get_product_price_to_discount', $working_price, $product);
    }

}

?>