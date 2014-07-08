<?php

class WC_Bulk_Variations_Admin {

    var $settings_tabs;
    var $current_tab;
    var $fields = array();
    public $row_attribute;
    public $column_attribute;

    public function __construct() {

        add_action('init', array(&$this, 'on_init'), 99);

        add_action('add_meta_boxes', array(&$this, 'add_meta_box'));
        add_action('woocommerce_process_product_meta', array(&$this, 'process_meta_box'), 1, 2);
    }

    public function on_init() {
        global $wc_bulk_variations;
        wp_enqueue_style('wc_bulk_variations', $wc_bulk_variations->plugin_url() . '/assets/css/bulk-variations-admin.css');
    }

    public function add_meta_box() {
        global $post;
        if ($post && $post->post_type == 'product') {

	        // 2.0 Compat
	        if ( function_exists( 'get_product' ) )
           		$product = get_product( $post->ID );
           	else
           		$product = new WC_Product( $post->ID );
            
            if (!$product->is_type('variable')) {
                return;
            } else {
                add_meta_box('woocommerce-bulk-variations', __('Bulk Variation Input', 'wc_bulk_variations'), array(&$this, 'meta_box'), 'product', 'side', 'default');
            }
        }
    }

    public function meta_box($post) {

        // 2.0 Compat
        if ( function_exists( 'get_product' ) )
       		$product = get_product( $post->ID );
       	else
       		$product = new WC_Product( $post->ID );
           		
        if (!$product->is_type('variable')) {
            remove_meta_box('woocommerce-bulk-variations', 'product', 'side');
            return;
        }

        $cur_product_view = get_post_meta($post->ID, '_bv_type', true);
        $cur_x = get_post_meta($post->ID, '_bv_x', true);
        $cur_y = get_post_meta($post->ID, '_bv_y', true);

        $axis_attributes = $product->get_variation_attributes(); //Attributes configured on this product already. 
        $available_axis_attributes = array();
        foreach ($axis_attributes as $name => $attribute) {
            if (taxonomy_exists($name)) {
                $tax = get_taxonomy($name);
                $available_axis_attributes[$name] = $tax->label;
            } else {
                $available_axis_attributes[$name] = $name;
            }
        }
        asort($available_axis_attributes);
        array_unshift($available_axis_attributes, __('Select Variation Attribute...', 'wc_bulk_variations'));
        ?>
        <div id="product_views" class="panel">
            <div class="woocommerce_product_views">
                <?php if (count(array_keys($axis_attributes)) > 2) : ?>
                    <p>
                        <?php _e('Bulk variation forms only support product with two variation attributes', 'wc_bulk_variations'); ?>
                        <input type="hidden" name="_bv_type" value="0" />
                    </p>
                <?php elseif (count(array_keys($axis_attributes)) == 1) : ?>
                    <p>
                        <?php _e('Bulk variation forms only support product with two variation attributes', 'wc_bulk_variations'); ?>
                        <input type="hidden" name="_bv_type" value="0" />
                    </p>

                <?php else : ?>

                    <p>
                        <label class=""><?php _e('View:', 'wc_bulk_variations'); ?></label>
                        <select name="_bv_type">
                            <option value="0">Disabled</option>
                            <option <?php echo $cur_product_view == 'matrix' ? 'selected="selected"' : '' ?> value="matrix">Enabled</option>
                        </select>
                    </p>
                    <p>
                        <label class=""><?php _e('Columns:', 'wc_bulk_variations'); ?></label>
                        <select class="bv-select-max-width" name="_bv_x">
                            <?php foreach ($available_axis_attributes as $name => $label): ?>

                                <option <?php echo $cur_x == $name ? 'selected="selected"' : '' ?> value="<?php echo $name; ?>"><?php echo $label; ?></option>

                            <?php endforeach; ?>
                        </select>
                    </p>
                    <p>
                        <label class=""><?php _e('Rows:', 'wc_bulk_variations'); ?></label>
                        <select class="bv-select-max-width" name="_bv_y">
                            <?php foreach ($available_axis_attributes as $name => $label): ?>

                                <option <?php echo $cur_y == $name ? 'selected="selected"' : '' ?> value="<?php echo $name; ?>"><?php echo $label; ?></option>

                            <?php endforeach; ?>
                        </select>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    function process_meta_box($post_id, $post) {
        if (isset($_POST['_bv_type']) && $_POST['_bv_type'] && $_POST['_bv_type'] != 'disabled') {
            update_post_meta($post_id, '_bv_type', $_POST['_bv_type']);
            update_post_meta($post_id, '_bv_x', $_POST['_bv_x']);
            update_post_meta($post_id, '_bv_y', $_POST['_bv_y']);
        } else {
            delete_post_meta($post_id, '_bv_type');
            delete_post_meta($post_id, '_bv_x');
            delete_post_meta($post_id, '_bv_y');
        }
    }

}
?>