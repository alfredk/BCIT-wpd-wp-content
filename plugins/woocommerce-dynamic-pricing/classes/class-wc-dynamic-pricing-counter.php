<?php

class WC_Dynamic_Pricing_Counter {

    public $product_counts = array();
    public $variation_counts = array();
    public $category_counts = array();
    
    private $categories_in_cart = array();
    
    private static $instance;

    public static function init() {
        if (self::$instance == null) {
            self::$instance = new WC_Dynamic_Pricing_Counter();
        }
    }

    public static function reset() {
        self::$instance = null;
        self::init();
    }
    
    public static function instance() {
        if (self::$instance == null){
            self::init();
        }
        return self::$instance;
    }

    public function __construct() {
        add_filter('woocommerce_get_cart_item_from_session', array(&$this, 'get_cart_item_from_session'), 100, 3);
        //Add action to reset counters when product added to cart
        add_action('woocommerce_add_to_cart', array(&$this, 'on_add_to_cart'), 100, 6);
    }

    public function on_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
        

        //Store product counts
        self::$instance->product_counts[$product_id] = isset(self::$instance->product_counts[$product_id]) ?
                self::$instance->product_counts[$product_id] + $quantity : $quantity;
        
        //Gather product variation id counts
        if (isset($variation_id) && !empty($variation_id)) {
            self::$instance->variation_counts[$variation_id] = isset(self::$instance->variation_counts[$variation_id]) ?
                    self::$instance->variation_counts[$variation_id] + $quantity : $quantity;
        }

        //Gather product category counts
        $product_categories = wp_get_post_terms($product_id, 'product_cat');
        foreach ($product_categories as $category) {
            self::$instance->category_counts[$category->term_id] = isset(self::$instance->category_counts[$category->term_id]) ?
                    self::$instance->category_counts[$category->term_id] + $quantity : $quantity;
	    
	    self::$instance->categories_in_cart[] = $category->term_id;
        }
	
	do_action('wc_dynamic_pricing_counter_updated');
    }

    public function get_cart_item_from_session($cart_item, $values, $cart_item_key) {
        $product = $cart_item['data'];

        //Store product counts
        self::$instance->product_counts[$product->id] = isset(self::$instance->product_counts[$product->id]) ?
                self::$instance->product_counts[$product->id] + $cart_item['quantity'] : $cart_item['quantity'];

        //Gather product variation id counts
        if (isset($cart_item['variation_id']) && !empty($cart_item['variation_id'])) {
            self::$instance->variation_counts[$cart_item['variation_id']] = isset(self::$instance->variation_counts[$cart_item['variation_id']]) ?
                    self::$instance->variation_counts[$cart_item['variation_id']] + $cart_item['quantity'] : $cart_item['quantity'];
        }

        //Gather product category counts
        $product_categories = wp_get_post_terms($product->id, 'product_cat');
        foreach ($product_categories as $category) {
            self::$instance->category_counts[$category->term_id] = isset(self::$instance->category_counts[$category->term_id]) ?
                    self::$instance->category_counts[$category->term_id] + $cart_item['quantity'] : $cart_item['quantity'];
	    
	    self::$instance->categories_in_cart[] = $category->term_id;
        }
	
	do_action('wc_dynamic_pricing_counter_updated');
	
        return $cart_item;
    }

    /** Static Access Methods * */
    public static function get_product_count($product_id) {
        return isset(self::$instance->product_counts[$product_id]) ? self::$instance->product_counts[$product_id] : 0;
    }

    public static function get_variation_count($variation_id) {
        return isset(self::$instance->variation_counts[$variation_id]) ? self::$instance->variation_counts[$variation_id] : 0;
    }

    public static function get_category_count($category_id) {
        return isset(self::$instance->category_counts[$category_id]) ? self::$instance->category_counts[$category_id] : 0;
    }
    
    public static function categories_in_cart($categories) {
	    if (!is_array($categories)) {
		    $categories = array($categories);
	    }
	    
	    return count(array_intersect(self::$instance->categories_in_cart, $categories)) > 0;
    }

}

?>