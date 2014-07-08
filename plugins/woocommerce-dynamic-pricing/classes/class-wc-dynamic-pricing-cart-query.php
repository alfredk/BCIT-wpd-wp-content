<?php

class WC_Dynamic_Pricing_Cart_Query {
    public static function sort_by_price($cart_item_a, $cart_item_b) {
        return $cart_item_a['data']->get_price() > $cart_item_b['data']->get_price();
    }
    
    public static function sort_by_price_desc($cart_item_a, $cart_item_b) {
        return $cart_item_a['data']->get_price() < $cart_item_b['data']->get_price();
    }
}

?>