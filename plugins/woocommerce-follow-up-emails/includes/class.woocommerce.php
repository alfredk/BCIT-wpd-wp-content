<?php

class FUE_Woocommerce {

    public function __construct() {
        global $wpdb, $woocommerce;

        add_filter( 'body_class', array( $this, 'output_body_class' ) );

        add_action( 'admin_enqueue_scripts', array($this, 'admin_scripts') );
        add_filter( 'fue_script_locale', array($this, 'admin_script_locale') );

        // load woocommerce addons
        add_action( 'fue_addons_loaded', array($this, 'load_addons') );

        add_filter( 'fue_trigger_types', array($this, 'trigger_types'), 10, 2 );
        add_filter( 'fue_email_types', array($this, 'email_types') );
        add_filter( 'fue_email_type_long_descriptions', array($this, 'email_type_long_descriptions') );
        add_filter( 'fue_email_type_short_descriptions', array($this, 'email_type_short_descriptions') );
        add_filter( 'fue_email_type_triggers', array($this, 'email_type_triggers') );

        add_filter( 'fue_email_query', array($this, 'email_query'), 10, 2 );

        // cart actions
        add_action( 'woocommerce_cart_updated', array($this, 'cart_updated') );
        add_action( 'woocommerce_cart_emptied', array($this, 'cart_emptied') );

        // email forms
        add_action( 'fue_email_form_after_interval', array($this, 'email_form'), 9, 3 );
        //add_action( 'fue_edit_email_form_before_message', array($this, 'email_form'), 9, 3 );
        add_action( 'fue_email_form_interval_meta', array($this, 'email_interval_meta') );

        // excluded categories for generic emails
        add_action( 'fue_email_form_after_interval', array($this, 'excluded_categories_form'), 10 );

        add_action( 'wp_ajax_fue_product_has_children', array($this, 'ajax_product_has_children') );

        // email form custom fields
        add_action( 'fue_email_form_before_message', array($this, 'custom_fields_form') );

        // step 2 validation script
        add_action( 'fue_email_form_submit_script', array($this, 'email_form_validation') );

        add_filter( 'woocommerce_json_search_found_products', array($this, 'found_products') );

        add_action( 'init', array($this, 'initial_order_import') );

        // @since 2.2.1 support custom order statuses
        add_action( 'init', array($this, 'hook_statuses') );
        add_action( 'woocommerce_checkout_order_processed', array($this, 'order_status_updated') );

        // settings page
        add_action( 'fue_settings_email', array($this, 'settings_email_form') );

        add_filter( 'fue_insert_email_order', array($this, 'get_correct_email') );

        // manual emails
        add_filter( 'fue_send_manual_emails', array($this, 'send_manual_emails'), 10, 2 );

        // send email
        add_filter( 'fue_send_email_data', array($this, 'send_email_data'), 10, 3 );

        // test email field
        add_action( 'fue_test_email_fields', array($this, 'test_email_form') );

        // email form variables list
        add_action( 'fue_email_variables_list', array($this, 'generic_variables') );
        add_action( 'fue_email_variables_list', array($this, 'normal_variables') );
        add_action( 'fue_email_variables_list', array($this, 'customer_variables') );
        add_action( 'fue_email_variables_list', array($this, 'reminder_variables') );

        // email variable replacements
        add_filter( 'fue_email_generic_variables', array($this, 'email_variables'), 10, 4 );
        add_filter( 'fue_email_generic_replacements', array($this, 'email_replacements'), 10, 4 );

        add_filter( 'fue_email_customer_variables', array($this, 'email_variables'), 10, 4 );
        add_filter( 'fue_email_customer_replacements', array($this, 'email_replacements'), 10, 4 );

        add_filter( 'fue_email_normal_variables', array($this, 'email_variables'), 10, 4 );
        add_filter( 'fue_email_normal_replacements', array($this, 'email_replacements'), 10, 4 );

        add_filter( 'fue_email_reminder_variables', array($this, 'email_variables'), 10, 4 );
        add_filter( 'fue_email_reminder_replacements', array($this, 'email_replacements'), 10, 4 );

        // test email variable replacements
        add_filter( 'fue_email_generic_test_variables', array($this, 'email_test_variables'), 10, 4 );
        add_filter( 'fue_email_generic_test_replacements', array($this, 'email_test_replacements'), 10, 4 );

        add_filter( 'fue_email_customer_test_variables', array($this, 'email_test_variables'), 10, 4 );
        add_filter( 'fue_email_customer_test_replacements', array($this, 'email_test_replacements'), 10, 4 );

        add_filter( 'fue_email_normal_test_variables', array($this, 'email_test_variables'), 10, 4 );
        add_filter( 'fue_email_normal_test_replacements', array($this, 'email_test_replacements'), 10, 4 );

        add_filter( 'fue_email_reminder_test_variables', array($this, 'email_test_variables'), 10, 4 );
        add_filter( 'fue_email_reminder_test_replacements', array($this, 'email_test_replacements'), 10, 4 );

        // Reports
        add_action( 'fue_reports_section_list', array($this, 'report_section_list') );
        add_action( 'fue_reports_section_div', array($this, 'report_section_div') );
        add_filter( 'fue_report_email_trigger', array($this, 'report_email_trigger'), 10, 2 );

        add_filter( 'fue_report_email_address', array($this, 'report_email_address'), 10, 2 );
        add_filter( 'fue_report_order_str', array($this, 'report_order_str'), 10, 2 );

        // Send Manual
        add_action( 'fue_manual_types', array($this, 'manual_types') );
        add_action( 'fue_manual_type_actions', array($this, 'manual_type_actions') );
        add_action( 'fue_manual_js', array($this, 'manual_js') );

        // My Account Email Subscriptions
        // shortcode for the My Subscriptions page
        add_shortcode('fue_followup_subscriptions', array($this, 'my_email_subscriptions'));
        add_action('template_redirect', array($this, 'process_unsubscribe_request'));

        // Reminders
        add_filter( 'fue_email_message', array($this, 'email_message'), 10, 3 );
    }

    public function output_body_class( $classes ) {
        if ( is_page( fue_get_page_id('followup_unsubscribe') ) || is_page( fue_get_page_id('followup_my_subscriptions') ) ) {
            $classes[] = 'woocommerce';
            $classes[] = 'woocommerce-page';
        }

        return $classes;
    }

    public function load_addons() {
        require_once FUE_INC_DIR .'/class.fue_coupons.php';
        require_once FUE_INC_DIR .'/class.fue_subscriptions.php';
        require_once FUE_INC_DIR .'/class.fue_warranty.php';
        require_once FUE_INC_DIR .'/class.fue_points_and_rewards.php';
        require_once FUE_INC_DIR .'/class.the_events_calendar.php';
        require_once FUE_INC_DIR .'/class.bookings.php';
    }

    public function admin_scripts() {
        global $wpdb, $woocommerce;

        $page = isset($_GET['page']) ? $_GET['page'] : '';

        if ( $page == 'followup-emails' || $page == 'followup-emails-settings') {
            if (! function_exists('wc_add_notice') ) {
                woocommerce_admin_scripts();
            } else {
                $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
                wp_register_script( 'ajax-chosen', WC()->plugin_url() . '/assets/js/chosen/ajax-chosen.jquery' . $suffix . '.js', array('jquery', 'chosen'), WC()->version );
                wp_register_script( 'chosen', WC()->plugin_url() . '/assets/js/chosen/chosen.jquery' . $suffix . '.js', array('jquery'), WC()->version );
            }

            wp_enqueue_script( 'woocommerce_admin' );
            wp_enqueue_script('farbtastic');
            wp_enqueue_script( 'ajax-chosen' );
            wp_enqueue_script( 'chosen' );
            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_script( 'jquery-ui-autocomplete', null, array('jquery-ui-core') );

            ?>
            <style type="text/css">
            .chzn-choices li.search-field .default {
                width: auto !important;
            }
            select option[disabled] {display:none;}
            </style>
            <?php

            wp_enqueue_style( 'woocommerce_admin_styles', $woocommerce->plugin_url() . '/assets/css/admin.css' );



        } elseif ( $page == 'followup-emails-form' || $page == 'followup-emails-reports' ) {
            if (! function_exists('wc_add_notice') ) {
                woocommerce_admin_scripts();
            } else {
                $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
                wp_register_script( 'ajax-chosen', WC()->plugin_url() . '/assets/js/chosen/ajax-chosen.jquery' . $suffix . '.js', array('jquery', 'chosen'), WC()->version );
                wp_register_script( 'chosen', WC()->plugin_url() . '/assets/js/chosen/chosen.jquery' . $suffix . '.js', array('jquery'), WC()->version );
            }

            wp_enqueue_script( 'woocommerce_admin' );
            wp_enqueue_script('farbtastic');
            wp_enqueue_script( 'ajax-chosen' );
            wp_enqueue_script( 'chosen' );
            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_script( 'jquery-ui-core', null, array('jquery') );
            wp_enqueue_script( 'jquery-ui-datepicker', null, array('jquery-ui-core') );
            wp_enqueue_script( 'jquery-ui-autocomplete', null, array('jquery-ui-core') );

            wp_enqueue_style( 'woocommerce_admin_styles', $woocommerce->plugin_url() . '/assets/css/admin.css' );
            wp_enqueue_style( 'jquery-ui-css', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.21/themes/base/jquery-ui.css' );
        }
    }

    public function admin_script_locale( $translation ) {
        $translation['nonce'] = wp_create_nonce("search-products");

        return $translation;
    }

    public function trigger_types( $triggers = array(), $email_type = '' ) {
        $order_statuses = (array)get_terms( 'shop_order_status', array('hide_empty' => 0, 'orderby' => 'id') );
        $order_triggers = array();

        if (! isset($order_statuses['errors']) ) {
            foreach ( $order_statuses as $status ) {
                $triggers[ $status->slug ] = sprintf(__('after Order Status: %s', 'follow_up_emails'), $status->name );
                $order_triggers[] = $status->slug;
            }
        }

        $triggers['first_purchase']         = __('after first purchase', 'follow_up_emails');
        $triggers['cart']                   = __('after added to cart', 'follow_up_emails');
        $triggers['after_last_purchase']    = __('after last purchase', 'follow_up_emails');
        $triggers['order_total_above']      = __('after order total is above', 'follow_up_emails');
        $triggers['order_total_below']      = __('after order total is below', 'follow_up_emails');
        $triggers['purchase_above_one']     = __('after customer purchased more than one time', 'follow_up_emails');
        $triggers['product_purchase_above_one']     = __('after customer purchased more than one time', 'follow_up_emails');
        $triggers['total_orders']           = __('after total orders by customer', 'follow_up_emails');
        $triggers['total_purchases']        = __('after total purchase amount by customer', 'follow_up_emails');

        return $triggers;

    }

    public function email_types( $types ) {
        $generic = array('generic' => __('Storewide Email', 'follow_up_emails'));
        $types = $generic + $types;

        $types['normal']    = __('Product/Category Email', 'follow_up_emails');
        $types['customer']  = __('Customer Email', 'follow_up_emails');
        $types['reminder']  = __('Reminder Email', 'wc_followup_emails');

        return $types;
    }

    public function email_type_long_descriptions( $descriptions ) {
        $descriptions['generic']    = __('Storewide emails will send to a buyer of any product within your store based upon the criteria you define when creating your emails.', 'follow_up_emails');
        $descriptions['normal']     = __('Product and Category emails will send to a buyer of products within the specific products or categories from your store based upon the criteria you define when creating your emails.', 'follow_up_emails');
        $descriptions['customer']   = __('Customer specific emails will re-engage your customers in the future by following up with emails specifically related to total purchases, dollar amounts, and other customer lifetime value metrics.', 'follow_up_emails');
        $descriptions['reminder']   = __('Reminder emails will send to a user based upon the quantity of products purchased. They will automatically trigger at a set interval for a duration equal to the quantity purchased.', 'follow_up_emails');

        return $descriptions;
    }

    public function email_type_short_descriptions( $descriptions ) {
        $descriptions['generic']    = __('Storewide emails will send to a buyer of any product within your store based upon the criteria you define when creating your emails.', 'follow_up_emails');
        $descriptions['normal']     = __('Product and Category emails will send to a buyer of products within the specific products or categories from your store based upon the criteria you define when creating your emails.', 'follow_up_emails');
        $descriptions['customer']   = __('Customer specific emails will re-engage your customers in the future by following up with emails specifically related to total purchases, dollar amounts, and other customer lifetime value metrics.', 'follow_up_emails');
        $descriptions['reminder']   = __('Reminder emails will send to a user based upon the quantity of products purchased. They will automatically trigger at a set interval for a duration equal to the quantity purchased.', 'follow_up_emails');

        return $descriptions;
    }

    public function email_type_triggers( $type_triggers ) {
        $type_triggers['normal']    = array('first_purchase', 'cart', 'product_purchase_above_one');
        $type_triggers['generic']   = array('first_purchase', 'cart');
        $type_triggers['customer']  = array(
                                        'after_last_purchase', 'order_total_above', 'order_total_below',
                                        'purchase_above_one', 'total_orders', 'total_purchases'
                                    );

        $order_statuses = (array)get_terms( 'shop_order_status', array('hide_empty' => 0, 'orderby' => 'id') );
        $order_triggers = array();

        if (! isset($order_statuses['errors']) ) {
            foreach ( $order_statuses as $status ) {
                $order_triggers[] = $status->slug;
            }
        }

        $type_triggers['normal']    = array_unique(array_merge($type_triggers['normal'], $order_triggers));
        $type_triggers['generic']   = array_unique(array_merge($type_triggers['generic'], $order_triggers));
        $type_triggers['reminder']  = array('processing', 'completed');

        return $type_triggers;
    }

    public function email_query( $results, $term ) {
        global $wpdb, $woocommerce;

        $all_emails = array();

        // Guest customers (billing email)
        $billing_results = $wpdb->get_results("SELECT `post_id`, `meta_value` FROM {$wpdb->prefix}postmeta WHERE meta_key = '_billing_email' AND meta_value LIKE '{$term}%'");

        if ( $billing_results ) {
            foreach ( $billing_results as $result ) {
                if ( in_array($result->meta_value, $all_emails) ) continue;

                $all_emails[] = $result->meta_value;

                // get the name
                $first_name = get_post_meta( $result->post_id, '_billing_first_name', true );
                $last_name = get_post_meta( $result->post_id, '_billing_last_name', true );

                $key = '0|'. $result->meta_value .'|'. $first_name .' '. $last_name;

                $results[$key] = $first_name .' '. $last_name .' &lt;'. $result->meta_value .'&gt;';
            }
        }

        return $results;
    }

    public function email_interval_meta( $values ) {
        global $wpdb, $woocommerce;

        ?>
        <span class="show-if-order_total_above hide-if-date order_total_above_span hideable">
            <?php echo get_woocommerce_currency_symbol(); ?>
            <input type="text" name="meta[order_total_above]" id="order_total_above" value="<?php if (isset($defaults['meta']['order_total_above'])) echo $defaults['meta']['order_total_above']; ?>" />
        </span>

        <span class="show-if-order_total_below hide-if-date order_total_below_span hideable">
            <?php echo get_woocommerce_currency_symbol(); ?>
            <input type="text" name="meta[order_total_below]" id="order_total_below" value="<?php if (isset($defaults['meta']['order_total_below'])) echo $defaults['meta']['order_total_below']; ?>" />
        </span>

        <span class="show-if-total_purchases hide-if-date total_purchases_span hideable">
            <span class="description"><?php _e('is', 'follow_up_emails'); ?></span>
            <select name="meta[total_purchases_mode]">
                <option value="equal to" <?php if (isset($defaults['meta']['total_purchases_mode']) && $defaults['meta']['total_purchases_mode'] == 'equal to') echo  'selected'; ?>><?php _e('equal to', 'follow_up_emails'); ?></option>
                <option value="greater than" <?php if (isset($defaults['meta']['total_purchases_mode']) && $defaults['meta']['total_purchases_mode'] == 'greater than') echo  'selected'; ?>><?php _e('greater than', 'follow_up_emails'); ?></option>
            </select>

            <?php echo get_woocommerce_currency_symbol(); ?>
            <input type="text" name="meta[total_purchases]" value="<?php if (isset($defaults['meta']['total_purchases'])) echo $defaults['meta']['total_purchases']; ?>" />
        </span>

        <span class="show-if-total_orders hide-if-date total_orders_span hideable">
            <span class="description"><?php _e('is', 'follow_up_emails'); ?></span>
            <select name="meta[total_orders_mode]">
                <option value="equal to" <?php if (isset($defaults['meta']['total_orders_mode']) && $defaults['meta']['total_orders_mode'] == 'equal to') echo  'selected'; ?>><?php _e('equal to', 'follow_up_emails'); ?></option>
                <option value="greater than" <?php if (isset($defaults['meta']['total_orders_mode']) && $defaults['meta']['total_purchases_mode'] == 'greater than') echo  'selected'; ?>><?php _e('greater than', 'follow_up_emails'); ?></option>
            </select>

            <input type="text" name="meta[total_orders]" value="<?php if (isset($defaults['meta']['total_orders'])) echo $defaults['meta']['total_orders']; ?>" />
        </span>
        <?php
    }

    public function email_form( $values ) {
        global $wpdb, $woocommerce;

        // load the categories
        $categories = get_terms( 'product_cat', array( 'order_by' => 'name', 'order' => 'ASC' ) );
        ?>
        <div class="field non-generic non-reminder hideable <?php do_action('fue_form_product_description_tr_class', $values); ?> product_description_tr">
            <strong><?php _e('Select the product that, when bought or added to the cart, will trigger this follow-up email.', 'follow_up_emails'); ?></strong>
        </div>

        <div class="field non-generic non-signup reminder hideable <?php do_action('fue_form_product_tr_class', $values); ?> product_tr">
            <label for="product_ids"><?php _e('Product', 'follow_up_emails'); ?></label>
            <select id="product_id" name="product_id" class="ajax_chosen_select_products_and_variations" multiple data-placeholder="<?php _e('Search for a product&hellip;', 'woocommerce'); ?>" style="width: 400px">
                <?php if ( !empty($values['product_id']) ): ?>
                <option value="<?php echo $values['product_id']; ?>" selected><?php echo get_the_title($values['product_id']) .' #'. $values['product_id']; ?></option>
                <?php endif; ?>
            </select>
            <br/>
            <?php
            $display        = 'display: none;';
            $has_variations = (!empty($values['product_id']) && FUE_Woocommerce::product_has_children($values['product_id'])) ? true : false;

            if ($has_variations) $display = '';
            ?>
            <div class="product_include_variations" style="<?php echo $display; ?>">
                <input type="checkbox" name="meta[include_variations]" id="include_variations" value="yes" <?php if (isset($values['meta']['include_variations']) && $values['meta']['include_variations'] == 'yes') echo 'checked'; ?> />
                <label for="include_variations" class="inline"><?php _e('Include variations', 'follow_up_emails'); ?></label>
            </div>
        </div>

        <div class="field non-generic non-signup reminder hideable <?php do_action('fue_form_category_tr_class', $values); ?> category_tr">
            <label for="category_id"><?php _e('Category', 'follow_up_emails'); ?></label>

            <select id="category_id" name="category_id" class="chzn-select" data-placeholder="<?php _e('Search for a category&hellip;', 'follow_up_emails'); ?>" style="width: 400px;">
                    <option value="0"><?php _e('Select a category', 'follow_up_emails'); ?></option>
                <?php
                foreach ($categories as $category):
                    $selected = ($category->term_id != $values['category_id']) ? '' : 'selected';
                ?>
                    <option value="<?php _e($category->term_id); ?>" <?php echo $selected; ?>><?php echo esc_html($category->name); ?></option>
                <?php endforeach; ?>
                </select>
        </div>

        <?php
    }

    public function excluded_categories_form( $values ) {
        if ( $values['type'] != 'generic' )
            return;


        // load the categories
        $categories = get_terms( 'product_cat', array( 'order_by' => 'name', 'order' => 'ASC' ) );
        ?>
        <div class="field generic non-signup hideable <?php do_action('fue_form_excluded_category_tr_class', $values); ?> excluded_category_tr">
            <label for="excluded_category_ids"><?php _e('Exclude these categories', 'follow_up_emails'); ?></label>

            <select id="excluded_category_ids" name="meta[excluded_categories][]" multiple class="chzn-select" data-placeholder="<?php _e('Search for a category&hellip;', 'follow_up_emails'); ?>" style="width: 400px;">
                <option value="" selected></option>
                <?php
                $excluded = (isset($values['meta']['excluded_categories'])) ? $values['meta']['excluded_categories'] : array();
                foreach ($categories as $category):
                    $selected = (in_array($category->term_id, $excluded)) ? 'selected' : '';
                    ?>
                    <option value="<?php _e($category->term_id); ?>" <?php echo $selected; ?>><?php echo esc_html($category->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }

    public function custom_fields_form( $defaults ) {
        global $woocommerce;

        if ( $defaults['type'] == 'normal' && $defaults['product_id'] > 0 ):
            $use_custom_field = (isset($defaults['meta']['use_custom_field'])) ? $defaults['meta']['use_custom_field'] : 0;
        ?>
        <div class="field non-generic non-signup reminder hideable <?php do_action('fue_form_custom_field_tr_class', $defaults); ?> use_custom_field_tr">
            <label for="use_custom_field">
                <input type="hidden" id="product_id" value="<?php echo $defaults['product_id']; ?>" />
                <input type="checkbox" name="meta[use_custom_field]" value="1" id="use_custom_field" <?php checked(1, $use_custom_field); ?> />
                <?php _e('Use Custom Field', 'follow_up_emails'); ?>
            </label>
        </div>

        <div class="field show-if-custom-field custom_field_tr">
            <label for="cf_product"><?php _e('Select the product and custom field to use', 'follow_up_emails'); ?></label>
            <div class="if-product-selected custom_field_select_div">
                <select name="custom_fields" id="custom_fields">
                    <?php
                    $meta   = get_post_custom($defaults['product_id']);

                    foreach ( $meta as $key => $value ): ?>
                    <option value="<?php echo $key; ?>"><?php echo $key; ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="show-if-cf-selected"><input type="text" readonly onclick="jQuery(this).select();" value="" size="25" id="custom_field" /></span>
            </div>
        </div>
        <?php
        endif;
    }

    public function ajax_product_has_children() {
        $id = $_REQUEST['product_id'];

        if ( FUE_Woocommerce::product_has_children($id) ) {
            echo 1;
        } else {
            echo 0;
        }
        exit;
    }

    public static function product_has_children( $product_id ) {
        global $wpdb;

        if ( 0 == $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}posts WHERE post_parent = %d AND post_type = 'product_variation'", $product_id) ) ) {
            return false;
        } else {
            return true;
        }
    }

    public function email_form_validation( $values ) {
        if ( $values['type'] == 'normal' ) {
            ?>
            if ( !jQuery("#product_id").val() && jQuery("#category_id").val() == 0 ) {
                jQuery(".product_tr").addClass("fue-error");
                jQuery(".category_tr").addClass("fue-error");
                errors = true;
            }
            <?php
        }
    }

    public function found_products( $products ) {
        foreach ( $products as $id => $title ) {
            $product = sfn_get_product($id);

            if ( is_a($product, 'WC_Product_Subscription_Variation') ) {
                $extra_data = '';
                $identifier = '#' . $id;
                $attributes = $product->get_variation_attributes();
                $extra_data = ' &ndash; ' . implode( ', ', $attributes ) . ' &ndash; ' . woocommerce_price( $product->get_price() );

                $products[$id] = sprintf( __( '%s &ndash; %s%s', 'woocommerce' ), $identifier, $product->get_title(), $extra_data );
            }

        }

        return $products;
    }

    public function settings_email_form() {
        ?>
        <h3><?php _e('Remove WooCommerce Email Styles', 'follow_up_emails'); ?></h3>

        <p><?php _e('Want completely customized, and the ability to fully design your emails? Simply check this box, and the default WooCommerce styling will be removed from the emails you send via Follow-up Emails.', 'follow_up_emails'); ?></p>
        <p>
            <label for="disable_email_wrapping">
                <input type="checkbox" name="disable_email_wrapping" id="disable_email_wrapping" value="1" <?php if (1 == get_option('fue_disable_wrapping', 0)) echo 'checked'; ?> />
                <?php _e('Disable wrapping of email templates together.', 'follow_up_emails'); ?>
            </label>
        </p>
        <?php
    }

    public function initial_order_import() {
        global $wpdb, $woocommerce;

        $tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}followup_order_items'");

        if ( empty($tables) ) return;

        if ( get_option( 'fue_orders_imported', false ) == true ) return;

        // Fresh start
        $wpdb->query("DELETE FROM {$wpdb->prefix}followup_order_items");
        $wpdb->query("DELETE FROM {$wpdb->prefix}followup_customers");
        $wpdb->query("DELETE FROM {$wpdb->prefix}followup_order_categories");
        $wpdb->query("DELETE FROM {$wpdb->prefix}followup_customer_orders");

        $wc2        = (bool)function_exists('get_product');
        $results    = $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'shop_order'");

        foreach ( $results as $row ) {
            $order = new WC_Order( $row->ID );
            self::record_order( $order );
        }

        update_option( 'fue_orders_imported', true );
    }

    public static function record_order($order) {
        global $wpdb, $woocommerce;

        $order_categories   = array();
        $wc2                = function_exists('get_product');
        $order_id           = $order->id;

        $recorded = get_post_meta( $order_id, '_fue_recorded', true );

        if ( $recorded == true ) return;

        if ( $order->user_id > 0 ) {
            $user_id    = $order->user_id;
            $user       = new WP_User( $user_id );
            $email      = $user->user_email;
        } else {
            $user_id    = 0;
            $email      = $order->billing_email;
        }

        $customer = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_customers WHERE user_id = %d AND email_address = %s", $user_id, $email) );

        if (! $customer ) {
            $insert = array(
                'user_id'               => $user_id,
                'email_address'         => $email,
                'total_purchase_price'  => $order->order_total,
                'total_orders'          => 1
            );

            $wpdb->insert( $wpdb->prefix .'followup_customers', $insert );
            $customer_id = $wpdb->insert_id;
            $customer = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_customers WHERE id = %d", $customer_id) );
        } else {
            $total_orders       = $customer->total_orders + 1;
            $total_purchases    = $customer->total_purchase_price + $order->order_total;

            $wpdb->update( $wpdb->prefix .'followup_customers', array('total_purchase_price' => $total_purchases, 'total_orders' => $total_orders), array('id' => $customer->id));
        }

        // record order
        $wpdb->insert( $wpdb->prefix .'followup_customer_orders', array('followup_customer_id' => $customer->id, 'order_id' => $order_id, 'price' => $order->order_total) );

        if ( $wc2 ) {
            $order_item_ids = $wpdb->get_results("SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = {$order_id}");

            foreach ( $order_item_ids as $order_item ) {
                $product_id = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id = {$order_item->order_item_id} AND meta_key = '_product_id'");

                if ( $product_id ) {
                    $insert = array(
                        'order_id'      => $order_id,
                        'product_id'    => $product_id
                    );
                    $wpdb->insert( $wpdb->prefix .'followup_order_items', $insert );

                    // get the categories
                    $cat_ids = wp_get_post_terms( $product_id, 'product_cat', array('fields' => 'ids') );

                    if ( $cat_ids ) {
                        foreach ( $cat_ids as $cat_id ) {
                            $order_categories[] = $cat_id;
                        }
                    }
                }
            }
        } else {
            $order_items = get_post_meta( $order_id, '_order_items', true );

            foreach ( $order_items as $item ) {
                $insert = array(
                    'order_id'      => $order_id,
                    'product_id'    => $item['id']
                );
                $wpdb->insert( $wpdb->prefix .'followup_order_items', $insert );

                // get the categories
                $cat_ids = wp_get_post_terms( $item['id'], 'product_cat', array('fields' => 'ids') );

                if ( $cat_ids ) {
                    foreach ( $cat_ids as $cat_id ) {
                        $order_categories[] = $cat_id;
                    }
                }
            }
        }

        $order_categories = array_unique($order_categories);

        foreach ( $order_categories as $category_id ) {
            $insert = array(
                'order_id'      => $order_id,
                'category_id'   => $category_id
            );
            $wpdb->insert( $wpdb->prefix .'followup_order_categories', $insert );
        }

        update_post_meta( $order_id, '_fue_recorded', true );
    }

    function hook_statuses() {
        $order_statuses = (array)get_terms( 'shop_order_status', array('hide_empty' => 0, 'orderby' => 'id') );

        if (! isset($order_statuses['errors']) ) {
            foreach ( $order_statuses as $status ) {
                add_action('woocommerce_order_status_'. $status->slug, array($this, 'order_status_updated'), 100);
            }
        }
    }

    function order_status_updated($order_id) {
        global $wpdb, $woocommerce;

        $order          = new WC_Order($order_id);
        $triggers       = array();

        self::record_order( $order );

        if ( $order->status == 'processing' ) {
            //$triggers[] = 'purchase';
            $triggers[] = 'processing';

            // add the date trigger
            $triggers[] = 'date';

            // check for order_total
            $triggers[] = 'order_total_above';
            $triggers[] = 'order_total_below';
            $triggers[] = 'total_orders';
            $triggers[] = 'total_purchases';

            // get the user's number of orders
            if ( $order->user_id > 0 ) {
                $num_orders = $wpdb->get_var( $wpdb->prepare("SELECT total_orders FROM {$wpdb->prefix}followup_customers WHERE user_id = %d", $order->user_id) );
            } else {
                $num_orders = $wpdb->get_var( $wpdb->prepare("SELECT total_orders FROM {$wpdb->prefix}followup_customers WHERE email_address = %s", $order->billing_email) );
            }

            if ( $num_orders > 1 ) {
                $triggers[] = 'purchase_above_one';
            }

        } elseif ( $order->status == 'completed' ) {
            // if there are no order_items in the database, it's time to extract them from the order and insert into order_items
            if ( 0 == $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}followup_order_items WHERE order_id = $order_id") ) {
                // extract items and categories
                $order_categories = array();

                if ( function_exists('get_product') ) {
                    $order_item_ids = $wpdb->get_results("SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = {$order_id}");

                    foreach ( $order_item_ids as $order_item ) {
                        $product_id = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id = {$order_item->order_item_id} AND meta_key = '_product_id'");

                        if ( $product_id ) {
                            $insert = array(
                                'order_id'      => $order_id,
                                'product_id'    => $product_id
                            );
                            $wpdb->insert( $wpdb->prefix .'followup_order_items', $insert );

                            // get the categories
                            $cat_ids = wp_get_post_terms( $product_id, 'product_cat', array('fields' => 'ids') );

                            if ( $cat_ids ) {
                                foreach ( $cat_ids as $cat_id ) {
                                    $order_categories[] = $cat_id;
                                }
                            }
                        }
                    }
                } else {
                    $order_items = get_post_meta( $order_id, '_order_items', true );

                    foreach ( $order_items as $item ) {
                        $insert = array(
                            'order_id'      => $order_id,
                            'product_id'    => $item['id']
                        );
                        $wpdb->insert( $wpdb->prefix .'followup_order_items', $insert );

                        // get the categories
                        $cat_ids = wp_get_post_terms( $item['id'], 'product_cat', array('fields' => 'ids') );

                        if ( $cat_ids ) {
                            foreach ( $cat_ids as $cat_id ) {
                                $order_categories[] = $cat_id;
                            }
                        }
                    }
                }

                $order_categories = array_unique($order_categories);

                foreach ( $order_categories as $category_id ) {
                    $insert = array(
                        'order_id'      => $order_id,
                        'category_id'   => $category_id
                    );
                    $wpdb->insert( $wpdb->prefix .'followup_order_categories', $insert );
                }
            }

            $triggers[] = $order->status;
        } else {
            $triggers[] = $order->status;
        }

        $triggers = apply_filters( 'fue_new_order_triggers', $triggers, $order_id );

        self::create_email_orders( $triggers, $order_id );

    }

    function cart_emptied() {
        global $wpdb;

        // only if user is logged in
        $user = wp_get_current_user();

        if ( 0 == $user->ID ) return;

        do_action('fue_cart_emptied');
        $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}followup_email_orders WHERE `is_cart` = 1 AND `is_sent` = 0 AND user_id = %d", $user->ID) );
        update_user_meta( $user->ID, '_wcfue_cart_emails', array() );
        return;
    }

    function cart_updated() {
        global $wpdb;

        // only if user is logged in
        $user = wp_get_current_user();

        if ( 0 == $user->ID ) return;

        $cart = get_user_meta( $user->ID, '_woocommerce_persistent_cart', true );
        //var_dump($cart); exit;
        if ( !$cart || empty($cart) ) {
            // cart has been emptied. we need to remove existing email orders for this user
            do_action('fue_cart_emptied');
            $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}followup_email_orders WHERE `is_cart` = 1 AND `is_sent` = 0 AND user_id = %d", $user->ID) );
            update_user_meta( $user->ID, '_wcfue_cart_emails', array() );
            return;
        }

        $cart_session   = get_user_meta( $user->ID, '_wcfue_cart_emails', true );

        if (! $cart_session ) $cart_session = array();

        $emails         = array();
        $always_prods   = array();
        $always_cats    = array();
        $email_created  = false;

        foreach ( $cart['cart'] as $item_key => $item ) {

            $email = $wpdb->get_row("SELECT `id`, `priority` FROM {$wpdb->prefix}followup_emails WHERE `interval_type` = 'cart' AND `product_id` = '". $item['product_id'] ."' ORDER BY `priority` ASC");

            if ( $email ) {
                $check = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->prefix}followup_email_orders` WHERE `is_sent` = 0 AND `order_id` = 0 AND `product_id` = %d AND `email_id` = %d AND `user_id` = %d AND `is_cart` = 1", $item['product_id'], $email->id, $user->ID) );

                if ( $check == 0 && !in_array($email->id .'_'. $item['product_id'], $cart_session) ) {
                    $cart_session[] = $email->id .'_'. $item['product_id'];
                    $emails[] = array('id' => $email->id, 'item' => $item['product_id'], 'priority' => $email->priority);
                }
            }

            // always_send product matches
            $results = $wpdb->get_results("SELECT `id` FROM {$wpdb->prefix}followup_emails WHERE `interval_type` = 'cart' AND `product_id` = '". $item['product_id'] ."' AND `always_send` = 1");

            foreach ( $results as $row ) {
                $check = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->prefix}followup_email_orders` WHERE `is_sent` = 0 AND `order_id` = 0 AND `product_id` = %d AND `email_id` = %d AND `user_id` = %d AND `is_cart` = 1", $item['product_id'], $row->id, $user->ID) );

                if ( $check == 0 && !in_array($row->id .'_'. $item['product_id'], $cart_session) ) {
                    $cart_session[] = $row->id .'_'. $item['product_id'];
                    $always_prods[] = array( 'id' => $row->id, 'item' => $item['product_id'] );
                }
            }

            // always_send category matches
            $cat_ids    = wp_get_object_terms( $item['product_id'], 'product_cat', array('fields' => 'ids') );
            $ids        = implode(',', $cat_ids);

            if (empty($ids)) $ids = "''";

            $results = $wpdb->get_results("SELECT `id` FROM {$wpdb->prefix}followup_emails WHERE `interval_type` = 'cart' AND `always_send` = 1 AND `category_id` IN (". $ids .")");

            foreach ( $results as $row ) {
                $check = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->prefix}followup_email_orders` WHERE `is_sent` = 0 AND `order_id` = 0 AND `product_id` = %d AND `email_id` = %d AND `user_id` = %d AND `is_cart` = 1", $item['product_id'], $row->id, $user->ID) );

                if ( $check == 0 && !in_array($row->id .'_'. $item['product_id'], $cart_session) ) {
                    $cart_session[] = $row->id .'_'. $item['product_id'];
                    $always_cats[] = array('id' => $row->id, 'item' => $item['product_id']);
                }
            }
        }

        if ( !empty($always_prods) ) {
            foreach ( $always_prods as $row ) {
                $email      = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_emails WHERE `id` = %d", $row['id']) );
                $interval   = (int)$email->interval_num;
                $add        = FUE::get_time_to_add( $interval, $email->interval_duration );
                $send_on    = time() + $add;

                $insert = array(
                    'product_id'=> $row['item'],
                    'email_id'  => $email->id,
                    'send_on'   => $send_on,
                    'is_cart'   => 1,
                    'user_id'   => $user->ID
                );
                FUE::insert_email_order( $insert );
            }
        }

        if ( !empty($always_cats) ) {
            foreach ( $always_cats as $row ) {
                $email      = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_emails WHERE `id` = %d", $row['id']) );
                $interval   = (int)$email->interval_num;
                $add        = FUE::get_time_to_add( $interval, $email->interval_duration );
                $send_on    = time() + $add;

                $insert = array(
                    'product_id'=> $row['item'],
                    'email_id'  => $email->id,
                    'send_on'   => $send_on,
                    'is_cart'   => 1,
                    'user_id'   => $user->ID
                );
                FUE::insert_email_order( $insert );
            }
        }

        // product matches
        if ( !empty($emails) ) {
            // find the one with the highest priority
            $top        = false;
            $highest    = 1000;
            foreach ( $emails as $email ) {
                if ( $email['priority'] < $highest ) {
                    $highest    = $email['priority'];
                    $top        = $email;
                }
            }

            if ( $top !== false ) {
                $email = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_emails WHERE `id` = %d", $top['id']) );

                $interval   = (int)$email->interval_num;
                $add        = FUE::get_time_to_add( $interval, $email->interval_duration );
                $send_on    = time() + $add;

                $insert = array(
                    'product_id'=> $top['item'],
                    'email_id'  => $email->id,
                    'send_on'   => $send_on,
                    'is_cart'   => 1,
                    'user_id'   => $user->ID
                );
                FUE::insert_email_order( $insert );
                $email_created = true;
            }
        }

        // find a category match
        if ( !$email_created ) {
            $emails = array();
            foreach ( $cart['cart'] as $item_key => $item ) {
                $cat_ids    = wp_get_object_terms( $item['product_id'], 'product_cat', array('fields' => 'ids') );
                $ids        = implode(',', $cat_ids);

                if (empty($ids)) $ids = "''";

                $email = $wpdb->get_results("SELECT `id`, `priority` FROM {$wpdb->prefix}followup_emails WHERE `interval_type` = 'cart' AND `category_id` IN (". $ids .") ORDER BY `priority` ASC");

                foreach ( $email as $e ) {
                    $check = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->prefix}followup_email_orders` WHERE `is_sent` = 0 AND `order_id` = 0 AND `product_id` = %d AND `email_id` = %d AND `user_id` = %d AND `is_cart` = 1", $item['product_id'], $e->id, $user->ID) );

                    if ( $check == 0 && !in_array($e->id .'_'. $item['product_id'], $cart_session) ) {
                        $cart_session[] = $e->id .'_'. $item['product_id'];
                        $emails[] = array('id' => $e->id, 'item' => $item['product_id'], 'priority' => $e->category_priority);
                    }
                }
            }

            if ( !empty($emails) ) {
                // find the one with the highest priority
                $top        = false;
                $highest    = 1000;
                foreach ( $emails as $email ) {
                    if ( $email['priority'] < $highest ) {
                        $highest    = $email['priority'];
                        $top        = $email;
                    }
                }

                if ( $top !== false ) {
                    $email = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_emails WHERE `id` = %d", $top['id']) );

                    $interval   = (int)$email->interval_num;
                    $add        = FUE::get_time_to_add( $interval, $email->interval_duration );
                    $send_on    = time() + $add;

                    $insert = array(
                        'product_id'=> $top['item'],
                        'email_id'  => $email->id,
                        'send_on'   => $send_on,
                        'is_cart'   => 1,
                        'user_id'   => $user->ID
                    );
                    FUE::insert_email_order( $insert );
                    $email_created = true;
                }
            }
        }

        if ( !$email_created ) {
            // find a generic mailer
            $emails = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}followup_emails WHERE `email_type` = 'generic' AND `interval_type` = 'cart' ORDER BY `priority` ASC");

            foreach ( $emails as $email ) {
                $check = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->prefix}followup_email_orders` WHERE `is_sent` = 0 AND `order_id` = 0 AND `product_id` = 0 AND `email_id` = %d AND `user_id` = %d AND `is_cart` = 1", $email->id, $user->ID) );

                if ( $check > 0 || in_array($email->id .'_0', $cart_session) ) continue;
                $cart_session[] = $email->id .'_0';
                $interval   = (int)$email->interval_num;
                $add        = FUE::get_time_to_add( $interval, $email->interval_duration );
                $send_on    = time() + $add;

                $insert = array(
                    'email_id'  => $email->id,
                    'send_on'   => $send_on,
                    'is_cart'   => 1,
                    'user_id'   => $user->ID
                );

                FUE::insert_email_order( $insert );
            }
        }

        update_user_meta( $user->ID, '_wcfue_cart_emails', $cart_session );
    }

    public function get_correct_email( $data ) {
        if ( $data['order_id'] > 0 ) {
            $order = new WC_Order( $data['order_id'] );
            $data['user_email'] = $order->billing_email;
        }

        return $data;
    }

    public static function create_email_orders( $triggers, $order_id = '' ) {
        global $woocommerce, $wpdb;

        $order          = ($order_id > 0) ? new WC_Order($order_id) : false;
        $items          = $order->get_items();
        $item_ids       = array();
        $order_created  = false;
        $queued         = array();
        $num_queued     = 0;
        $fue_customer_id= 0;
        $all_categories = array();

        if ( $order ) {

            if ( $order->user_id > 0 ) {
                $fue_customer_id = $wpdb->get_var( $wpdb->prepare("SELECT id FROM {$wpdb->prefix}followup_customers WHERE user_id = %d", $order->user_id) );
            } else {
                $fue_customer_id = $wpdb->get_var( $wpdb->prepare("SELECT id FROM {$wpdb->prefix}followup_customers WHERE email_address = %s", $order->billing_email) );
            }

        }

        $trigger = '';
        foreach ( $triggers as $t ) {
            $trigger .= "'". esc_sql($t) ."',";
        }
        $trigger = rtrim($trigger, ',');

        if ( empty($trigger) ) $trigger = "''";

        // find a product match
        $emails         = array();
        $always_prods   = array();
        $always_cats    = array();

        foreach ( $items as $item ) {
            $prod_id = (isset($item['id'])) ? $item['id'] : $item['product_id'];

            if (! in_array($prod_id, $item_ids) )
                $item_ids[] = $prod_id;

            // variation support
            $parent_id = -1;
            if ( isset($item['variation_id']) && $item['variation_id'] > 0 ) {
                $parent_id  = $prod_id;
                $prod_id    = $item['variation_id'];
            }

            $email_results = $wpdb->get_results( $wpdb->prepare("
                SELECT DISTINCT `id`, `product_id`, `priority`, `meta`
                FROM {$wpdb->prefix}followup_emails
                WHERE `interval_type` IN ($trigger)
                AND ( `product_id` = %d OR `product_id` = %d )
                AND `email_type` <> 'generic'
                AND `always_send` = 0
                AND status = 1
                ORDER BY `priority` ASC
            ", $prod_id, $parent_id) );

            if ( $email_results ) {
                foreach ($email_results as $email) {
                    $meta = maybe_unserialize($email->meta);
                    if ( $prod_id == $email->product_id ) {
                        // exact product ID match
                        $emails[] = array('id' => $email->id, 'item' => $prod_id, 'priority' => $email->priority);
                    } elseif ( $parent_id > 0 && $parent_id == $email->product_id && isset($meta['include_variations']) && $meta['include_variations'] == 'yes' ) {
                        $emails[] = array('id' => $email->id, 'item' => $parent_id, 'priority' => $email->priority);
                    }
                }
            }

            // always_send product matches
            $results = $wpdb->get_results( $wpdb->prepare("
                SELECT DISTINCT `id`, `product_id`, `meta` , `email_type`
                FROM {$wpdb->prefix}followup_emails
                WHERE `interval_type` IN ($trigger)
                AND ( `product_id` = %d OR `product_id` = %d OR `email_type` = 'reminder' )
                AND `always_send` = 1
                AND status = 1
            ", $prod_id, $parent_id) );

            foreach ( $results as $row ) {
                $meta = maybe_unserialize($row->meta);
                if ( $prod_id == $row->product_id || $row->email_type == 'reminder' ) {
                    // exact product ID match
                    $always_prods[] = array( 'id' => $row->id, 'item' => $prod_id );
                } elseif ( $parent_id > 0 && $parent_id == $row->product_id && isset($meta['include_variations']) && $meta['include_variations'] == 'yes' ) {
                    $always_prods[] = array( 'id' => $row->id, 'item' => $parent_id );
                }
            }

            // always_send category matches
            $cat_ids    = wp_get_object_terms( $prod_id, 'product_cat', array('fields' => 'ids') );
            $ids        = implode(',', $cat_ids);

            if (empty($ids)) $ids = "''";

            $all_categories = array_merge($all_categories, $cat_ids);

            $results = $wpdb->get_results("
                SELECT DISTINCT `id`
                FROM {$wpdb->prefix}followup_emails
                WHERE `interval_type` IN ($trigger)
                AND `always_send` = 1
                AND status = 1
                AND ( `category_id` <> '' AND `category_id` IN (". $ids .") )
            ");

            foreach ( $results as $row ) {
                $always_cats[] = array('id' => $row->id, 'item' => $prod_id);
            }
        }

        if ( !empty($always_prods) ) {
            foreach ( $always_prods as $row ) {
                $email      = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_emails WHERE `id` = %d AND status = 1", $row['id']) );
                $interval           = (int)$email->interval_num;
                $interval_duration  = $email->interval_duration;

                $reminder_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}followup_email_orders WHERE `order_id` = $order_id AND `email_id` = {$row['id']}");
                if ( $reminder_count == 0 && $email->email_type == 'reminder') {

                    // get the item's quantity
                    $qty            = 0;
                    $num_products   = false;
                    foreach ( $items as $item ) {
                        $item_id = (isset($item['product_id'])) ? $item['product_id'] : $item['id'];
                        if ( $item_id == $row['item'] ) {
                            $qty = $item['qty'];

                            if ( isset($item['item_meta']) && !empty($item['item_meta']) ) {
                                $wc2 = function_exists('get_product');
                                foreach ( $item['item_meta'] as $meta ) {
                                    if ($wc2) {
                                        if ( $meta['meta_name'] == 'Filters/Case' ) {
                                            $num_products = $meta['meta_value'];
                                        }
                                    } else {
                                        if ( isset($meta['Filters/Case']) ) {
                                            $num_products = $meta['Filters/Case'][0];
                                        }
                                    }
                                }
                            }

                            break;
                        }
                    }

                    // look for a lifespan product variable
                    $lifespan = get_post_meta( $row['item'], 'filter_lifespan', true );

                    if ( $lifespan && $lifespan > 0 ) {
                        $interval = (int)$lifespan;
                        $interval_duration = 'months';

                        if ( $num_products !== false && $num_products > 0 ) {
                            $qty = $qty * $num_products;
                        }
                    }

                    if ( $qty == 1 ) {
                        // only send the first email
                        $add        = FUE::get_time_to_add( $interval, $interval_duration );
                        $send_on    = current_time('timestamp') + $add;

                        $insert = array(
                            'send_on'       => $send_on,
                            'email_id'      => $email->id,
                            'product_id'    => $row['item'],
                            'order_id'      => $order_id
                        );
                        FUE::insert_email_order( $insert );

                        $queued[] = $insert;
                    } elseif ( $qty == 2 ) {
                        // only send the first and last emails
                        $add        = FUE::get_time_to_add( $interval, $interval_duration );
                        $send_on    = current_time('timestamp')+ $add;

                        $insert = array(
                            'send_on'       => $send_on,
                            'email_id'      => $email->id,
                            'product_id'    => $row['item'],
                            'order_id'      => $order_id
                        );
                        FUE::insert_email_order( $insert );
                        $queued[] = $insert;


                        $last       = FUE::get_time_to_add( $interval, $interval_duration );
                        $send_on    = current_time('timestamp') + $add + $last;

                        $insert = array(
                            'send_on'       => $send_on,
                            'email_id'      => $email->id,
                            'product_id'    => $row['item'],
                            'order_id'      => $order_id
                        );
                        FUE::insert_email_order( $insert );
                        $queued[] = $insert;
                    } else {
                        // send all emails
                        $add    = FUE::get_time_to_add( $interval, $interval_duration );
                        $last   = 0;
                        for ($x = 1; $x <= $qty; $x++) {
                            $send_on    = current_time('timestamp') + $add + $last;
                            $last       += $add;

                            $insert = array(
                                'send_on'       => $send_on,
                                'email_id'      => $email->id,
                                'product_id'    => $row['item'],
                                'order_id'      => $order_id
                            );
                            FUE::insert_email_order( $insert );
                            $queued[] = $insert;
                        }
                    }

                    continue;
                }

                $skip = false;
                do_action('fue_create_order_always_send', $email, $order_id, $row);

                if (false == $skip ) {

                    $insert = array(
                        'send_on'       => FUE::get_email_send_timestamp( $email ),
                        'email_id'      => $email->id,
                        'product_id'    => $row['item'],
                        'order_id'      => $order_id
                    );
                    FUE::insert_email_order( $insert );
                    $num_queued++;
                    $queued[] = $insert;
                }
            }
        }

        if ( !empty($always_cats) ) {
            foreach ( $always_cats as $row ) {
                $email      = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_emails WHERE `id` = %d AND status = 1", $row['id']) );
                $interval   = (int)$email->interval_num;

                $skip = false;
                do_action('fue_create_order_always_send', $email, $order_id, $row);

                if ( false == $skip ) {

                    $insert = array(
                        'send_on'       => FUE::get_email_send_timestamp( $email ),
                        'email_id'      => $email->id,
                        'order_id'      => $order_id,
                        'product_id'    => $row['item']
                    );
                    FUE::insert_email_order( $insert );
                    $num_queued++;
                    $queued[] = $insert;
                }
            }
        }

        if ( !empty($emails) ) {
            // find the one with the highest priority
            $top        = false;
            $highest    = 1000;
            foreach ( $emails as $email ) {
                if ( $email['priority'] < $highest ) {
                    $highest    = $email['priority'];
                    $top        = $email;
                }
            }

            if ( $top !== false ) {
                $insert = array(
                    'send_on'       => FUE::get_email_send_timestamp( $top['id'] ),
                    'email_id'      => $top['id'],
                    'product_id'    => $top['item'],
                    'order_id'      => $order_id
                );
                FUE::insert_email_order( $insert );
                $num_queued++;
                $queued[] = $insert;
                $order_created = true;

                // look for other emails with the same product id
                foreach ( $emails as $prod_email ) {
                    if ( $prod_email['id'] == $top['id'] ) continue;

                    if ( $prod_email['item'] == $top['item'] ) {

                        $insert = array(
                            'send_on'       => FUE::get_email_send_timestamp( $prod_email['id'] ),
                            'email_id'      => $prod_email['id'],
                            'product_id'    => $prod_email['item'],
                            'order_id'      => $order_id
                        );
                        FUE::insert_email_order( $insert );
                        $num_queued++;
                        $queued[] = $insert;
                    } else {
                        // if schedule is within 60 minutes, add to queue
                        $email      = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_emails WHERE `id` = %d AND status = 1", $prod_email['id']) );
                        $interval   = (int)$email->interval_num;

                        if ( $email->interval_type == 'date' ) {
                            continue;
                        } else {
                            $add = FUE::get_time_to_add( $interval, $email->interval_duration );

                            if ( $add > 3600 ) continue;

                            // less than 60 minutes, add to queue
                            $send_on = current_time('timestamp') + $add;
                        }

                        $insert = array(
                            'send_on'       => $send_on,
                            'email_id'      => $email->id,
                            'product_id'    => $prod_email['item'],
                            'order_id'      => $order_id
                        );
                        FUE::insert_email_order( $insert );
                        $num_queued++;
                        $queued[] = $insert;
                    }
                }
            }
        }

        // find a category match
        if ( !$order_created ) {
            $emails = array();
            foreach ( $items as $item ) {
                $prod_id    = (isset($item['id'])) ? $item['id'] : $item['product_id'];
                $cat_ids    = wp_get_object_terms( $prod_id, 'product_cat', array('fields' => 'ids') );
                $ids        = implode(',', $cat_ids);

                if (empty($ids)) $ids = "''";

                $email = $wpdb->get_results("SELECT DISTINCT `id`, `priority` FROM {$wpdb->prefix}followup_emails WHERE `interval_type` IN ($trigger) AND `product_id` = 0 AND `category_id` > 0 AND `category_id` IN (". $ids .") AND `email_type` <> 'generic' AND `always_send` = 0 AND status = 1 ORDER BY `priority` ASC");

                foreach ( $email as $e ) {
                    $emails[] = array('id' => $e->id, 'item' => $prod_id, 'priority' => $e->priority);
                }
            }

            if ( !empty($emails) ) {
                // find the one with the highest priority
                $top        = false;
                $highest    = 1000;
                foreach ( $emails as $email ) {
                    if ( $email['priority'] < $highest ) {
                        $highest    = $email['priority'];
                        $top        = $email;
                    }
                }

                if ( $top !== false ) {
                    $insert = array(
                        'send_on'       => FUE::get_email_send_timestamp( $top['id'] ),
                        'email_id'      => $top['id'],
                        'product_id'    => $top['item'],
                        'order_id'      => $order_id
                    );
                    FUE::insert_email_order( $insert );
                    $queued[] = $insert;
                    $num_queued++;
                    $order_created = true;

                    // look for other emails with the same category id
                    foreach ( $emails as $cat_email ) {
                        if ( $cat_email['id'] == $top['id'] ) continue;

                        if ( $cat_email['item'] == $top['item'] ) {
                            $insert = array(
                                'send_on'       => FUE::get_email_send_timestamp( $cat_email['id'] ),
                                'email_id'      => $cat_email['id'],
                                'product_id'    => $cat_email['item'],
                                'order_id'      => $order_id
                            );
                            FUE::insert_email_order( $insert );
                            $queued[] = $insert;
                            $num_queued++;
                        } else {
                            // if schedule is within 60 minutes, add to queue
                            $email      = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_emails WHERE `id` = %d AND status = 1", $cat_email['id']) );
                            $interval   = (int)$email->interval_num;

                            if ( $email->interval_type == 'date' ) {
                                continue;
                            } else {
                                $add = FUE::get_time_to_add( $interval, $email->interval_duration );

                                if ( $add > 3600 ) continue;

                                // less than 60 minutes, add to queue
                                $send_on = current_time('timestamp') + $add;
                            }

                            $insert = array(
                                'send_on'       => $send_on,
                                'email_id'      => $email->id,
                                'product_id'    => $cat_email['item'],
                                'order_id'      => $order_id
                            );
                            FUE::insert_email_order( $insert );
                            $num_queued++;
                            $queued[] = $insert;
                        }
                    }
                }
            }
        }

        // allow plugins to stop FUE from sending generic emails using this hook
        // or by defining a 'FUE_ORDER_CREATED' constant
        $order_created = apply_filters('fue_order_created', $order_created, $triggers, $order_id);

        if ( !$order_created && !defined('FUE_ORDER_CREATED') ) {
            // find a generic mailer
            $emails         = $wpdb->get_results("SELECT DISTINCT * FROM {$wpdb->prefix}followup_emails WHERE `email_type` = 'generic' AND `interval_type` IN ($trigger) AND status = 1 ORDER BY `priority` ASC");
            $all_categories = array_unique($all_categories);
            foreach ( $emails as $email ) {
                // excluded categories
                $meta = unserialize($email->meta);
                $excludes = (isset($meta['excluded_categories'])) ? $meta['excluded_categories'] : array();

                if ( count($excludes) > 0 ) {
                    foreach ($all_categories as $cat_id) {
                        if (in_array($cat_id, $excludes))
                            continue 2;
                    }
                }

                $insert = array(
                    'send_on'       => FUE::get_email_send_timestamp( $email->id ),
                    'email_id'      => $email->id,
                    'order_id'      => $order_id
                );
                FUE::insert_email_order( $insert );
                $num_queued++;
                $queued[] = $insert;
            }

        }

        if ( $order !== false && $order->status == 'completed' ) {
            /**
             * Look for and queue first_purchase and product_purchase_above_one emails
             * for the 'normal' email type
             */
            if ( $fue_customer_id ) {

                foreach ( $item_ids as $item_id ) {

                    // number of time this customer have purchased the current item
                    $count  = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}followup_order_items oi, {$wpdb->prefix}followup_customer_orders co WHERE co.followup_customer_id = %d AND co.order_id = oi.order_id AND oi.product_id = %d", $fue_customer_id, $item_id) );

                    if ( $count == 1 ) {
                        // First Purchase emails
                        $emails = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_emails WHERE interval_type = 'first_purchase' AND email_type = 'normal' AND product_id = %d AND status = 1", $item_id) );

                        if ( $emails ) {
                            foreach ( $emails as $email ) {
                                // first time purchasing this item
                                $insert = array(
                                    'send_on'       => FUE::get_email_send_timestamp( $email->id ),
                                    'email_id'      => $email->id,
                                    'order_id'      => $order_id,
                                    'product_id'    => $item_id
                                );
                                FUE::insert_email_order( $insert );
                                $num_queued++;
                                $queued[] = $insert;
                            }
                        }

                    } elseif ( $count > 1 ) {
                        // Purchase Above One emails
                        $emails = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_emails WHERE interval_type = 'product_purchase_above_one' AND email_type = 'normal' AND product_id = %d AND status = 1", $item_id) );

                        if ( $emails ) {
                            foreach ( $emails as $email ) {
                                // first time purchasing this item
                                $insert = array(
                                    'send_on'       => FUE::get_email_send_timestamp( $email->id ),
                                    'email_id'      => $email->id,
                                    'order_id'      => $order_id,
                                    'product_id'    => $item_id
                                );
                                FUE::insert_email_order( $insert );
                                $num_queued++;
                                $queued[] = $insert;
                            }
                        }
                    }

                    // category match
                    $cat_ids = wp_get_post_terms( $item_id, 'product_cat', array('fields' => 'ids') );

                    if ( $cat_ids ) {
                        foreach ( $cat_ids as $cat_id ) {

                            $count = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}followup_order_categories oc, {$wpdb->prefix}followup_customer_orders co WHERE co.followup_customer_id = %d AND co.order_id = oc.order_id AND oc.category_id = %d", $fue_customer_id, $cat_id) );

                            if ( $count == 1 ) {
                                // first time purchasing from this category
                                $emails = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_emails WHERE interval_type = 'first_purchase' AND email_type = 'normal' AND category_id = %d AND status = 1", $cat_id) );

                                if ( $emails ) {
                                    foreach ( $emails as $email ) {
                                        $insert = array(
                                            'send_on'       => FUE::get_email_send_timestamp( $email->id ),
                                            'email_id'      => $email->id,
                                            'order_id'      => $order_id,
                                            'product_id'    => $item_id
                                        );
                                        FUE::insert_email_order( $insert );
                                        $num_queued++;
                                        $queued[] = $insert;
                                    }
                                }

                            } elseif ( $count > 1 ) {
                                // purchased from this category more than once
                                $emails = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_emails WHERE interval_type = 'product_purchase_above_one' AND email_type = 'normal' AND category_id = %d AND status = 1", $cat_id) );

                                if ( $emails ) {
                                    foreach ( $emails as $email ) {
                                        $insert = array(
                                            'send_on'       => FUE::get_email_send_timestamp( $email->id ),
                                            'email_id'      => $email->id,
                                            'order_id'      => $order_id,
                                            'product_id'    => $item_id
                                        );
                                        FUE::insert_email_order( $insert );
                                        $num_queued++;
                                        $queued[] = $insert;
                                    }
                                }
                            }

                        }
                    }
                    // end category match

                }

                // storewide first purchase
                $count = $wpdb->get_var( $wpdb->prepare("SELECT total_orders FROM {$wpdb->prefix}followup_customers WHERE id = %d", $fue_customer_id) );

                if ( $count == 1 ) {
                    // first time ordering
                    $emails = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}followup_emails WHERE interval_type = 'first_purchase' AND email_type = 'generic' AND status = 1");

                    if ( $emails ) {
                        foreach ( $emails as $email ) {
                            // first time purchasing this item
                            $insert = array(
                                'send_on'       => FUE::get_email_send_timestamp( $email->id ),
                                'email_id'      => $email->id,
                                'order_id'      => $order_id
                            );
                            FUE::insert_email_order( $insert );
                            $num_queued++;
                            $queued[] = $insert;
                        }
                    }
                }
            }

            // look for customer emails
            $emails = $wpdb->get_results("SELECT DISTINCT * FROM {$wpdb->prefix}followup_emails WHERE `email_type` = 'customer' AND `interval_type` IN ($trigger) AND status = 1 ORDER BY `priority` ASC");

            foreach ( $emails as $email ) {
                $interval   = (int)$email->interval_num;
                $meta       = maybe_unserialize( $email->meta );

                // check for order total triggers first
                if ( $email->interval_type == 'order_total_above' ) {
                    if ( !isset($meta['order_total_above'])) continue;
                    if ( $order->order_total < $meta['order_total_above'] ) continue;
                } elseif ( $email->interval_type == 'order_total_below' ) {
                    if ( !isset($meta['order_total_below'])) continue;
                    if ( $order->order_total > $meta['order_total_below'] ) continue;
                } elseif ( $email->interval_type == 'total_orders' ) {
                    $mode           = $meta['total_orders_mode'];
                    $requirement    = $meta['total_orders'];

                    if ( isset($meta['one_time']) && $meta['one_time'] == 'yes' ) {
                        // get the correct email address
                        if ( $order->user_id > 0 ) {
                            $user = new WP_User( $order->user_id );
                            $user_email = $user->user_email;
                        } else {
                            $user_email = $order->billing_email;
                        }

                        $search = $wpdb->get_var( $wpdb->prepare(
                            "SELECT COUNT(*)
                            FROM {$wpdb->prefix}followup_email_orders
                            WHERE email_id = %d
                            AND user_email = %s",
                            $email->id,
                            $user_email
                        ) );

                        if ( $search > 0 ) continue;
                    }

                    // get user's total number of orders
                    if ( $order->user_id > 0 ) {
                        $num_orders = $wpdb->get_var( $wpdb->prepare("SELECT total_orders FROM {$wpdb->prefix}followup_customers WHERE user_id = %d", $order->user_id) );
                    } else {
                        $num_orders = $wpdb->get_var( $wpdb->prepare("SELECT total_orders FROM {$wpdb->prefix}followup_customers WHERE email_address = %s", $order->billing_email) );
                    }

                    if ( $mode == 'less than' && $num_orders >= $requirement ) {
                        continue;
                    } elseif ( $mode == 'equal to' && $num_orders != $requirement ) {
                        continue;
                    } elseif ( $mode == 'greater than' && $num_orders <= $requirement ) {
                        continue;
                    }
                } elseif ( $email->interval_type == 'total_purchases' ) {
                    $mode           = $meta['total_purchases_mode'];
                    $requirement    = $meta['total_purchases'];

                    if ( isset($meta['one_time']) && $meta['one_time'] == 'yes' ) {
                        // get the correct email address
                        if ( $order->user_id > 0 ) {
                            $user = new WP_User( $order->user_id );
                            $user_email = $user->user_email;
                        } else {
                            $user_email = $order->billing_email;
                        }

                        $search = $wpdb->get_var( $wpdb->prepare(
                            "SELECT COUNT(*)
                            FROM {$wpdb->prefix}followup_email_orders
                            WHERE email_id = %d
                            AND user_email = %s",
                            $email->id,
                            $user_email
                        ) );

                        if ( $search > 0 ) continue;
                    }

                    // get user's total amount of purchases
                    if ( $order->user_id > 0 ) {
                        $purchases = $wpdb->get_var( $wpdb->prepare("SELECT total_purchase_price FROM {$wpdb->prefix}followup_customers WHERE user_id = %d", $order->user_id) );
                    } else {
                        $purchases = $wpdb->get_var( $wpdb->prepare("SELECT total_purchase_price FROM {$wpdb->prefix}followup_customers WHERE email_address = %s", $order->billing_email) );
                    }

                    if ( $mode == 'less than' && $purchases >= $requirement ) {
                        continue;
                    } elseif ( $mode == 'equal to' && $purchases != $requirement ) {
                        continue;
                    } elseif ( $mode == 'greater than' && $purchases <= $requirement ) {
                        continue;
                    }
                } elseif ( $email->interval_type == 'purchase_above_one' ) {
                    // look for duplicate emails
                    if ( $order->user_id > 0 ) {
                        $wp_user = new WP_User( $order->user_id );
                        $user_email = $wp_user->user_email;
                    } else {
                        $user_email = $order->billing_email;
                    }

                    $num = $wpdb->get_var( $wpdb->prepare(
                                "SELECT COUNT(*)
                                FROM {$wpdb->prefix}followup_email_orders
                                WHERE email_id = %d
                                AND user_email = %s",
                                $email->id,
                                $user_email
                            ) );

                    if ( $num > 0 ) continue;
                }

                $add        = FUE::get_time_to_add( $interval, $email->interval_duration );
                $send_on    = current_time('timestamp') + $add;

                $insert = array(
                    'send_on'       => $send_on,
                    'email_id'      => $email->id,
                    'order_id'      => $order_id
                );
                FUE::insert_email_order( $insert );
                $num_queued++;
                $queued[] = $insert;
            }
        }

        // special trigger: last purchased
        if ( $order && ( $order->status == 'processing' || $order->status == 'completed' ) ) {
            $recipient = ($order->user_id > 0) ? $order->user_id : $order->billing_email;

            // if there are any "last purchased" emails, automatically add this order to the queue
            $emails = $wpdb->get_results("SELECT DISTINCT * FROM {$wpdb->prefix}followup_emails WHERE `email_type` = 'customer' AND `interval_type` = 'after_last_purchase' AND status = 1 ORDER BY `priority` ASC");

            foreach ( $emails as $email ) {

                // look for unsent emails in the queue with the same email ID
                $queued_emails = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}followup_email_orders WHERE is_sent = 0 AND email_id = {$email->id}");

                // loop through the queue and delete entries with identical customers
                foreach ( $queued_emails as $queue ) {
                    if ( $queue->user_id > 0 && $order->user_id > 0 && $queue->user_id == $order->user_id ) {

                        $wpdb->query("DELETE FROM {$wpdb->prefix}followup_email_orders WHERE id = {$queue->id}");
                    } elseif ( $order->user_id == 0 ) {
                        // try to match the email address
                        $email = get_post_meta( $queue->order_id, '_billing_email', true );

                        if ( $email == $order->billing_email ) {
                            $wpdb->query("DELETE FROM {$wpdb->prefix}followup_email_orders WHERE id = {$queue->id}");
                        }
                    }
                }

                if ( $order->user_id > 0 ) {
                    $last_order_date    = $wpdb->get_var( $wpdb->prepare("SELECT p.post_date FROM {$wpdb->posts} p, {$wpdb->prefix}followup_customer_orders co WHERE co.followup_customer_id = %d AND co.order_id = p.ID AND p.post_status = 'publish' ORDER BY p.ID DESC LIMIT 1", $order->user_id) );
                } else {
                    $last_order_date    = $wpdb->get_var( $wpdb->prepare("SELECT p.post_date FROM {$wpdb->posts} p, {$wpdb->prefix}followup_customer_orders co WHERE co.followup_customer_id = %d AND co.order_id = p.ID AND p.post_status = 'publish' ORDER BY p.ID DESC LIMIT 1", $order->billing_email) );
                }

                // add this email to the queue
                $interval   = (int)$email->interval_num;
                $add        = FUE::get_time_to_add( $interval, $email->interval_duration );
                $send_on    = current_time('timestamp') + $add;

                $insert = array(
                    'send_on'       => $send_on,
                    'email_id'      => $email->id,
                    'product_id'    => 0,
                    'order_id'      => $order_id
                );
                FUE::insert_email_order( $insert );
                $num_queued++;
                $queued[] = $insert;
            }
        }

        $num_queued = count($queued);

        /**
         * If a queue is linked to an order, add an order note that contains
         * the email name, trigger and schedule
         */
        foreach ( $queued as $row ) {
            $_order = new WC_Order();
            if ( isset($row['order_id']) && $row['order_id'] > 0 ) {
                $_order->get_order($row['order_id']);

                $email = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_emails WHERE id = %d", $row['email_id']) );

                if ( $email->email_type == 'manual' ) {
                    $email_trigger = __('Manual Email', 'follow_up_emails');
                } else {
                    if ( $email->interval_type == 'date' ) {
                        $email_trigger = sprintf( __('Send on %s'), $email->send_date .' '. $email->send_date_hour .':'. $email->send_date_minute .' '. $email_meta['send_date_ampm'] );
                    } elseif ( $email->interval_type == 'signup' ) {
                        $email_trigger = sprintf( __('%d %s after user signs up', 'follow_up_emails'), $email->interval_num, $email->interval_duration );
                    } else {
                        $email_trigger = sprintf( __('%d %s %s'), $email->interval_num, $email->interval_duration, FollowUpEmails::get_trigger_name( $email->interval_type ) );
                    }
                }
                $email_trigger  = apply_filters( 'fue_interval_str', $email_trigger, $email );
                $send_date      = date( get_option('date_format') .' '. get_option('time_format'), $row['send_on'] );

                $note = sprintf( __('Email queued: %s scheduled on %s<br/>Trigger: %s', 'follow_up_emails'), $email->name, $send_date, $email_trigger );

                $_order->add_order_note( $note );
            }
        }

        return $num_queued;
    }

    public function send_manual_emails( $recipients, $post ) {
        global $wpdb;

        $send_type  = $post['send_type'];

        if ( $send_type == 'storewide' ) {
            // Send to all customers
            //$users = $wpdb->get_results( "SELECT u.ID, u.user_email, u.display_name FROM {$wpdb->prefix}users u, {$wpdb->prefix}usermeta um WHERE u.ID = um.user_id AND um.meta_key = 'paying_customer' AND um.meta_value = 1" );
            $users = get_users( array(
                    'role' => 'customer'
                ));

            foreach ( $users as $user ) {
                $key    = $user->ID .'|'. $user->user_email .'|'. $user->display_name;
                $value  = array( $user->ID, $user->user_email, $user->display_name );

                if (! isset($recipients[$key]) ) {
                    $recipients[$key] = $value;
                }
            }

        } elseif ( $send_type == 'customer' ) {
            // individual email addresses
            if ( count($post['recipients']) > 0 ) {
                foreach ( $post['recipients'] as $key ) {
                    $data   = explode('|', $key);

                    if ( 3 == count($data) ) {
                        $value = array($data[0], $data[1], $data[2]);

                        if (! isset($recipients[$key]) ) {
                            $recipients[$key] = $value;
                        }
                    }
                }
            }
        } elseif ( $send_type == 'product' ) {
            // customers who bought the selected products
            if ( is_array($post['product_ids']) ) {

                if ( function_exists('get_product') ) {
                    // if WC >= 2.0, do a direct query
                    foreach ( $post['product_ids'] as $product_id ) {
                        $order_ids = $wpdb->get_results( $wpdb->prepare("SELECT DISTINCT order_id FROM {$wpdb->prefix}followup_order_items WHERE product_id = %d", $product_id) );

                        foreach ( $order_ids as $row ) {
                            $order = new WC_Order( $row->order_id );

                            // only on processing and completed orders
                            if ( $order->status != 'processing' && $order->status != 'completed' ) continue;

                            $order_user_id  = ($order->user_id > 0) ? $order->user_id : 0;
                            $key            = $order_user_id .'|'. $order->billing_email .'|'. $order->billing_first_name .' '. $order->billing_last_name;
                            $value          = array( $order_user_id, $order->billing_email, $order->billing_first_name .' '. $order->billing_last_name );

                            if (! isset($recipients[$key]) ) {
                                $recipients[$key] = $value;
                            }
                        }
                    }
                } else {
                    foreach ( $post['product_ids'] as $product_id ) {
                        $order_ids = $wpdb->get_results( $wpdb->prepare("SELECT DISTINCT order_id FROM {$wpdb->prefix}followup_order_items WHERE product_id = %d", $product_id) );

                        foreach ( $order_ids as $order_id ) {
                            // load the order and check the status
                            $order = new WC_Order( $order_id );

                            // only on processing and completed orders
                            if ( $order->status != 'processing' && $order->status != 'completed' ) continue;

                            $order_user_id  = ($order->user_id > 0) ? $order->user_id : 0;
                            $key            = $order_user_id .'|'. $order->billing_email .'|'. $order->billing_first_name .' '. $order->billing_last_name;
                            $value          = array( $order_user_id, $order->billing_email, $order->billing_first_name .' '. $order->billing_last_name );

                            if (! isset($recipients[$key]) ) {
                                $recipients[$key] = $value;
                                break;
                            }

                        } // endforeach ( $order_items_result as $order_items )
                    }

                } // endif: function_exists('get_product')

            } // endif: is_array($post['product_ids'])

        } elseif ( $send_type == 'category' ) {
            // customers who bought products from the selected categories
            if ( is_array($post['category_ids']) ) {
                foreach ( $post['category_ids'] as $category_id ) {
                    $order_ids = $wpdb->get_results( $wpdb->prepare("SELECT DISTINCT order_id FROM {$wpdb->prefix}followup_order_categories WHERE category_id = %d", $category_id) );

                    foreach ( $order_ids as $order_id_row ) {
                        // load the order and check the status
                        $order_id = $order_id_row->order_id;
                        $order = new WC_Order( $order_id );

                        // only on processing and completed orders
                        if ( $order->status != 'processing' && $order->status != 'completed' ) continue;

                        $order_user_id  = ($order->user_id > 0) ? $order->user_id : 0;
                        $key            = $order_user_id .'|'. $order->billing_email .'|'. $order->billing_first_name .' '. $order->billing_last_name;
                        $value          = array( $order_user_id, $order->billing_email, $order->billing_first_name .' '. $order->billing_last_name );

                        if (! isset($recipients[$key]) ) {
                            $recipients[$key] = $value;
                        }

                    } // endforeach ( $order_items_result as $order_items )
                }

            } // endif: is_array($post['product_ids'])
        } elseif ( $send_type == 'timeframe' ) {
            $from_ts    = strtotime($post['timeframe_from']);
            $to_ts      = strtotime($post['timeframe_to']);

            $from       = date('Y-m-d', $from_ts) . ' 00:00:00';
            $to         = date('Y-m-d', $to_ts) .' 23:59:59';

            $order_ids  = $wpdb->get_results( $wpdb->prepare("SELECT DISTINCT ID FROM {$wpdb->posts} WHERE post_type = 'shop_order' AND post_status = 'publish' AND post_date BETWEEN %s AND %s", $from, $to) );

            foreach ( $order_ids as $order_id ) {
                $order = new WC_Order( $order_id );

                $order_user_id  = ($order->user_id > 0) ? $order->user_id : 0;
                $key            = $order_user_id .'|'. $order->billing_email .'|'. $order->billing_first_name .' '. $order->billing_last_name;
                $value          = array( $order_user_id, $order->billing_email, $order->billing_first_name .' '. $order->billing_last_name );

                if (! isset($recipients[$key]) ) {
                    $recipients[$key] = $value;
                }
            }
        }

        return $recipients;
    }

    public function send_email_data( $email_data, $email_order, $email_row ) {
        global $wpdb;

        if ( $email_order->order_id != 0 ) {
            // order
            $order      = new WC_Order($email_order->order_id);

            // if this is an "Order Status" email, make sure that the order is still
            // in the same status as the one it was originally intended for
            // e.g. Do not send "on-hold" order status emails if the order status has
            // been changed to "completed" before sending the email
            $order_statuses_array   = (array)get_terms( 'shop_order_status', array('hide_empty' => 0, 'orderby' => 'id') );
            $all_statuses           = array();
            foreach ( $order_statuses_array as $status ) {
                $all_statuses[] = $status->slug;
            }

            if (in_array($email_row->interval_type, $all_statuses) && $email_row->interval_type !== $order->status) {
                // order status looks to have been changed already
                $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}followup_email_orders WHERE id = %d AND is_sent = 0", $email_order->id) );
                return false;
            }

            if ( isset($order->user_id) && $order->user_id > 0 ) {
                $email_data['user_id']  = $order->user_id;

                $wp_user    = new WP_User( $order->user_id );

                $email_data['email_to']     = $wp_user->user_email;
                $email_data['first_name']   = $wp_user->first_name;
                $email_data['last_name']    = $wp_user->last_name;
            } else {
                $email_data['email_to']     = $order->billing_email;
                $email_data['first_name']   = $order->billing_first_name;
                $email_data['last_name']    = $order->billing_last_name;
            }

            $email_data['cname'] = $email_data['first_name'] .' '. $email_data['last_name'];

            $order_date = date(get_option('date_format') .' '. get_option('time_format'), strtotime($order->order_date));
        }

        return $email_data;
    }

    public function test_email_form( $values ) {
        global $woocommerce;

        if ($values['type'] == 'generic') {
            _e(' as Order #', 'follow_up_emails');
            echo '<input type="text" id="order_id" placeholder="e.g. 105" size="5" class="test-email-field" />';
        } elseif ($values['type'] == 'normal') {
            _e(' as ', 'follow_up_emails');
            echo '<select class="product-id-chzn" data-placeholder="Select a Product" style="width: 200px;" multiple></select>';
            echo '<input type="hidden" id="product_id" class="test-email-field" />';

            $script = '
            var last_product_id = false;

            jQuery("select.product-id-chzn").one("focus", function() {
                last_product_id = jQuery(this).find("option:selected").eq(0).val();
            }).change(function(e) {
                e.preventDefault();

                // remove the first option to limit to only 1 product per email
                if (jQuery(this).find("option:selected").length > 1) {
                    jQuery(this).find("option:selected").each(function() {
                        if (jQuery(this).val() == last_product_id) {
                            jQuery(this).remove();
                        }
                    });

                    jQuery("select.product-id-chzn").trigger("chosen:updated");
                }

                last_product_id = jQuery(this).find("option:selected").eq(0).val();
                jQuery("#product_id").val(last_product_id);
            });

            jQuery("select.product-id-chzn").ajaxChosen({
                method:     "GET",
                url:        ajaxurl,
                dataType:   "json",
                allow_single_deselect: true,
                afterTypeDelay: 100,
                data:       {
                    action:         "woocommerce_json_search_products_and_variations",
                    security:       FUE.nonce
                }
            }, function (data) {
                var terms = {};

                jQuery.each(data, function (i, val) {
                    terms[i] = val;
                });

                return terms;
            });
            ';

            if (function_exists('wc_enqueue_js')) {
                wc_enqueue_js($script);
            } else {
                $woocommerce->add_inline_js($script);
            }
        }
    }

    public function generic_variables( $defaults ) {
        if ($defaults['type'] !== 'generic') return;

        ?>
        <li class=""><strong>{item_names}</strong> <img class="help_tip" title="<?php _e('Displays a list of purchased items.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
        <li class=""><strong>{item_categories}</strong> <img class="help_tip" title="<?php _e('The list of categories where the purchased items are under.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
        <?php
    }

    public function normal_variables( $defaults ) {
        if ($defaults['type'] !== 'normal') return;

        ?>
        <li class=""><strong>{item_name}</strong> <img class="help_tip" title="<?php _e('The name of the purchased item.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
        <li class=""><strong>{item_category}</strong> <img class="help_tip" title="<?php _e('The list of categories where the purchased item is under.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
        <?php
    }

    public function customer_variables( $defaults ) {
        if ($defaults['type'] !== 'customer') return;

        ?>
        <li class=""><strong>{dollars_spent_order}</strong> <img class="help_tip" title="<?php _e('The the amount spent on an order', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
        <li class=""><strong>{dollars_spent_total}</strong> <img class="help_tip" title="<?php _e('Total amount spent by the customer', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
        <li class=""><strong>{number_orders}</strong> <img class="help_tip" title="<?php _e('Total amount spent by the customer', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
        <li class=""><strong>{last_purchase_date}</strong> <img class="help_tip" title="<?php _e('The date the customer last ordered', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
        <?php
    }

    public function reminder_variables( $defaults ) {
        if ($defaults['type'] !== 'reminder') return;
        ?>
        <li class=""><strong>{first_email}...{/first_email}</strong> <img class="help_tip" title="<?php _e('The first email description...', 'wc_followup_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
        <li class=""><strong>{quantity_email}...{/quantity_email}</strong> <img class="help_tip" title="<?php _e('The quantity email description...', 'wc_followup_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
        <li class=""><strong>{final_email}...{/final_email}</strong> <img class="help_tip" title="<?php _e('The last email description...', 'wc_followup_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
        <?php
    }

    public function email_variables( $vars, $email_data, $email_order, $email_row ) {
        $email_type = $email_row->email_type;

        switch ( $email_type ) {
            case 'generic':
                $vars = array_merge($vars, array('{order_number}', '{order_date}', '{order_datetime}', '{order_billing_address}', '{order_shipping_address}', '{customer_first_name}', '{customer_name}', '{customer_email}', '{item_names}', '{item_categories}'));
                break;

            case 'customer':
                $vars = array_merge($vars, array('{order_number}', '{order_date}', '{order_datetime}', '{customer_first_name}', '{customer_name}', '{customer_email}', '{dollars_spent_order}', '{dollars_spent_total}', '{number_orders}', '{last_purchase_date}'));
                break;

            case 'reminder':
            case 'normal':
                $vars = array_merge($vars, array('{order_number}', '{order_date}', '{order_datetime}', '{order_billing_address}', '{order_shipping_address}', '{customer_first_name}', '{customer_name}', '{customer_email}', '{item_name}', '{item_category}'));
                break;
        }

        return $vars;
    }

    public function email_replacements( $reps, $email_data, $email_order, $email_row ) {
        global $wpdb, $woocommerce;

        $email_type         = $email_row->email_type;
        $order_date         = '';
        $order_datetime     = '';
        $order_id           = '';
        $billing_address    = '';
        $shipping_address   = '';

        if ( $email_order->order_id ) {
            $order              = new WC_Order( $email_order->order_id );
            $order_date         = date(get_option('date_format'), strtotime($order->order_date));
            $order_datetime     = date(get_option('date_format') .' '. get_option('time_format'), strtotime($order->order_date));
            $billing_address    = $order->get_formatted_billing_address();
            $shipping_address   = $order->get_formatted_shipping_address();

            $order_id = apply_filters( 'woocommerce_order_number', '#'.$email_order->order_id, $order );
        }

        if ( $email_type == 'generic' ) {
            if ( $email_order->order_id ) {
                $used_cats  = array();
                $item_list  = '<ul>';
                $item_cats  = '<ul>';
                $items      = $order->get_items();

                foreach ( $items as $item ) {
                    $item_id = (isset($item['product_id'])) ? $item['product_id'] : $item['id'];
                    $item_list .= apply_filters( 'fue_email_item_list', '<li><a href="'. FUE::create_email_url( $email_order->id, $email_row->id, $email_data['user_id'], $email_data['email_to'], get_permalink($item_id) ) .'">'. get_the_title($item_id) .'</a></li>', $email_order->id, $item );

                    $cats   = get_the_terms($item_id, 'product_cat');

                    if ( is_array($cats) && !empty($cats) ) {
                        foreach ($cats as $cat) {
                            if (!in_array($cat->term_id, $used_cats)) {
                                $item_cats .= apply_filters( 'fue_email_cat_list', '<li>'. $cat->name .'</li>', $email_order->id, $cat );
                            }
                        }
                    }
                }

                $item_list .= '</ul>';
                $item_cats .= '</ul>';
            } else {
                $item_list = '';
                $item_cats = '';
            }

            $reps = array_merge($reps, array(
                $order_id,
                $order_date,
                $order_datetime,
                $billing_address,
                $shipping_address,
                $email_data['first_name'],
                $email_data['first_name'] .' '. $email_data['last_name'],
                $email_data['email_to'],
                $item_list,
                $item_cats
            ));
        } elseif ( $email_type == 'customer' ) {
            if ( $email_data['user_id'] > 0 ) {
                $customer       = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_customers WHERE user_id = %d", $email_data['user_id']) );
                $spent_order    = woocommerce_price($order->order_total);
                $spent_total    = woocommerce_price($customer->total_purchase_price);
                $num_orders     = $customer->total_orders;
                $last_order_date= $wpdb->get_var( $wpdb->prepare("SELECT p.post_date FROM {$wpdb->posts} p, {$wpdb->prefix}followup_customer_orders co WHERE co.followup_customer_id = %d AND co.order_id = p.ID AND p.post_status = 'publish' ORDER BY p.ID DESC LIMIT 1", $email_data['user_id']) );
                $last_purchase  = date( get_option('date_format'), strtotime($last_order_date) );
            } else {
                $customer       = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_customers WHERE email_address = %s", $email_data['email_to']) );
                $spent_order    = woocommerce_price($order->order_total);
                $spent_total    = woocommerce_price($customer->total_purchase_price);
                $num_orders     = $customer->total_orders;
                $last_order_date= $wpdb->get_var( $wpdb->prepare("SELECT p.post_date FROM {$wpdb->posts} p, {$wpdb->postmeta} pm WHERE pm.meta_key = '_billing_email' AND pm.meta_value = %d AND pm.post_id = p.ID AND p.post_status = 'publish' ORDER BY p.ID DESC LIMIT 1", $email_data['email_to']) );
                $last_purchase  = date( get_option('date_format'), strtotime($last_order_date) );
            }

            $reps = array_merge($reps, array(
                $order_id,
                $order_date,
                $order_datetime,
                $email_data['first_name'],
                $email_data['first_name'] .' '. $email_data['last_name'],
                $email_data['email_to'],
                $spent_order,
                $spent_total,
                $num_orders,
                $last_purchase
            ));
        } elseif ( $email_type == 'normal' || $email_type == 'reminder' ) {
            $categories = '';

            if ( !empty($email_order->product_id) ) {
                $item   = (function_exists('get_product')) ? get_product($email_order->product_id) : new WC_Product($email_order->product_id);
                $cats   = get_the_terms($item->id, 'product_cat');

                if (is_array($cats) && !empty($cats)) {
                    foreach ($cats as $cat) {
                        $categories .= $cat->name .', ';
                    }
                    $categories = rtrim($categories, ', ');
                }

            }

            $item_url   = FUE::create_email_url( $email_order->id, $email_order->id, $email_data['user_id'], $email_data['email_to'], get_permalink($item->id) );

            if (! empty($codes) ) add_query_arg($codes, $item_url);

            $order_id = '';
            if (0 != $email_order->order_id) {
                $order_id = apply_filters( 'woocommerce_order_number', '#'.$email_order->order_id, $order );
            }

            $reps = array_merge($reps, array(
                $order_id,
                $order_date,
                $order_datetime,
                $billing_address,
                $shipping_address,
                $email_data['first_name'],
                $email_data['first_name'] .' '. $email_data['last_name'],
                $email_data['email_to'],
                '<a href="'. $item_url .'">'. get_the_title($item->id) .'</a>',
                $categories
            ));
        }

        return $reps;
    }

    public function email_test_variables( $vars ) {
        global $wpdb;

        $post       = array_map('stripslashes_deep', $_POST);
        $id         = $post['id'];
        $data       = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_emails WHERE id = %d", $id) );
        $type       = $data->email_type;

        switch ( $type ) {
            case 'generic':
                $vars = array_merge($vars, array('{order_number}', '{order_date}', '{order_datetime}', '{order_billing_address}', '{order_shipping_address}', '{customer_first_name}', '{customer_name}', '{customer_email}', '{item_names}', '{item_categories}'));
                break;

            case 'reminder':
            case 'normal':
                $vars = array_merge($vars, array('{order_number}', '{order_date}', '{order_datetime}', '{order_billing_address}', '{order_shipping_address}', '{customer_first_name}', '{customer_name}', '{customer_email}', '{item_name}', '{item_category}'));
                break;

            default:
                $vars = array_merge($vars, array('{order_number}', '{order_date}', '{order_datetime}', '{customer_first_name}', '{customer_name}', '{customer_email}', '{item_names}', '{item_categories}', '{dollars_spent_order}', '{dollars_spent_total}', '{number_orders}', '{last_purchase_date}', '{item_name}', '{item_category}'));
                break;
        }

        return $vars;
    }

    public function email_test_replacements( $reps ) {
        global $wpdb, $woocommerce;

        $post       = array_map('stripslashes_deep', $_POST);
        $id         = $post['id'];
        $data       = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_emails WHERE id = %d", $id) );
        $email_type = $data->email_type;

        $order_date     = '';
        $order_datetime = '';
        $order_id       = '';

        if ( $email_type == 'generic' ) {
            // check if user wants to simulate email from a specific order
            if (isset($post['order_id']) && !empty($post['order_id'])) {
                // make sure the order exist
                $order = new WC_Order($post['order_id']);

                if (! $order->id) {
                    die(__('The Order ID does not exist. Please try again.'));
                }

                $order_date     = date(get_option('date_format'), strtotime($order->order_date));
                $order_datetime = date(get_option('date_format') .' '. get_option('time_format'), strtotime($order->order_date));
                $order_id       = apply_filters( 'woocommerce_order_number', '#'.$order->id, $order );

                $billing_address    = $order->get_formatted_billing_address();
                $shipping_address   = $order->get_formatted_shipping_address();
                $used_cats  = array();
                $item_list  = '<ul>';
                $item_cats  = '<ul>';
                $items      = $order->get_items();

                foreach ( $items as $item ) {
                    $item_id = (isset($item['product_id'])) ? $item['product_id'] : $item['id'];
                    $item_list .= '<li><a href="'. get_permalink($item_id) .'">'. get_the_title($item_id) .'</a></li>';

                    $cats   = get_the_terms($item_id, 'product_cat');

                    if ( is_array($cats) && !empty($cats) ) {
                        foreach ($cats as $cat) {
                            if (!in_array($cat->term_id, $used_cats)) {
                                $item_cats .= '<li>'. $cat->name .'</li>';
                            }
                        }
                    }
                }

                $item_list .= '</ul>';
                $item_cats .= '</ul>';

                $customer_first = $order->billing_first_name;
                $customer_last  = $order->billing_first_name .' '. $order->billing_last_name;
                $customer_email = $order->billing_email;
            } else {
                $item_list = '<ul><li><a href="#">Item 1</a></li><li><a href="#">Item 2</a></li></ul>';
                $item_cats = '<ul><li>Category 1</li><li>Category 2</li></ul>';
                $billing_address    = '77 North Beach Dr., Miami, FL 35122';
                $shipping_address   = '77 North Beach Dr., Miami, FL 35122';

                $customer_first = 'John';
                $customer_last  = 'John Doe';
                $customer_email = 'john@example.org';
            }

            $reps = array_merge($reps, array(
                    $order_id,
                    $order_date,
                    $order_datetime,
                    $billing_address,
                    $shipping_address,
                    $customer_first,
                    $customer_first .' '. $customer_last,
                    $customer_email,
                    $item_list,
                    $item_cats
                ));
        } elseif ( $email_type == 'normal' || $email_type == 'reminder' ) {
            $categories = '';

            $order_id           = '1100';
            $order_date         = date(get_option('date_format'));
            $order_datetime     = date(get_option('date_format') .' '. get_option('time_format'));
            $billing_address    = '77 North Beach Dr., Miami, FL 35122';
            $shipping_address   = '77 North Beach Dr., Miami, FL 35122';
            $customer_first     = 'John';
            $customer_last      = 'John Doe';
            $customer_email     = 'john@example.org';

            // check if user wants to simulate email from a specific order
            if (isset($post['product_id']) && !empty($post['product_id'])) {
                $item       = (function_exists('get_product')) ? get_product($post['product_id']) : new WC_Product($post['product_id']);
                $cats       = get_the_terms($item->id, 'product_cat');

                if (is_array($cats) && !empty($cats)) {
                    foreach ($cats as $cat) {
                        $categories .= $cat->name .', ';
                    }
                    $categories = rtrim($categories, ', ');
                }
                $item_name  = $item->get_title();
                $item_url   = get_permalink($item->id);
            } else {
                $item_name  = '<a href="#">Name of Product</a>';
                $categories = 'Test Category';
                $item_url   = get_bloginfo('url');
            }

            $reps = array_merge($reps, array(
                    $order_id,
                    $order_date,
                    $order_datetime,
                    $billing_address,
                    $shipping_address,
                    $customer_first,
                    $customer_first .' '. $customer_last,
                    $customer_email,
                    '<a href="'. $item_url .'">'. $item_name .'</a>',
                    $categories
                ));
        } else {
            $order_number   = '1100';
            $order_date     = date(get_option('date_format'));
            $order_datetime = date(get_option('date_format') .' '. get_option('time_format'));
            $customer_first = 'John';
            $customer_last  = 'John Doe';
            $customer_email = 'john@example.org';

            $item_list = '<ul><li><a href="#">Item 1</a></li><li><a href="#">Item 2</a></li></ul>';
            $item_cats = '<ul><li>Category 1</li><li>Category 2</li></ul>';

            $spent_order    = woocommerce_price(19.99);
            $spent_total    = woocommerce_price(3250);
            $total_orders   = 12;
            $last_order_date= $order_date;

            $item_name  = '<a href="#">Name of Product</a>';
            $item_cat   = 'Test Category';

            $reps = array_merge($reps, array(
                    $order_number,
                    $order_date,
                    $order_datetime,
                    $customer_first,
                    $customer_last,
                    $customer_email,
                    $item_list,
                    $item_cats,
                    $spent_order,
                    $spent_total,
                    $total_orders,
                    $last_order_date,
                    $item_name,
                    $item_cat
                ) );
        }

        return $reps;
    }

    public function report_section_list() {
        echo '<li>| <a href="#coupons">'. __('Coupons', 'follow_up_emails') .'</a></li>';
    }

    public function report_section_div() {
        // coupons sorting
        $sort['sortby'] = 'date_sent';
        $sort['sort']   = 'desc';

        if ( isset($_GET['sortby']) && !empty($_GET['sortby']) ) {
            $valid = array('date_sent', 'email_address', 'coupon_used');
            if ( in_array($_GET['sortby'], $valid) ) {
                $sort['sortby'] = $_GET['sortby'];
                $sort['sort']   = (isset($_GET['sort']) && $_GET['sort'] == 'asc') ? 'asc' : 'desc';
            }
        }

        $coupon_reports = FUE_Reports::get_reports(array('type' => 'coupons', 'sort' => $sort));

        $email_address_class    = ($sort['sortby'] != 'email_address') ? 'sortable' : 'sorted';
        $email_address_sort     = ($email_address_class == 'sorted') ? $sort['sort'] : 'asc';
        $email_address_dir      = ($email_address_sort == 'asc') ? 'desc' : 'asc';

        $used_class     = ($sort['sortby'] != 'coupon_used') ? 'sortable' : 'sorted';
        $used_sort      = ($used_class == 'sorted') ? $sort['sort'] : 'asc';
        $used_dir       = ($used_sort == 'asc') ? 'desc' : 'asc';

        $sent_class     = ($sort['sortby'] != 'date_sent') ? 'sortable' : 'sorted';
        $sent_sort      = ($sent_class == 'sorted') ? $sort['sort'] : 'asc';
        $sent_dir       = ($sent_sort == 'asc') ? 'desc' : 'asc';

        ?>
        <div class="section" id="coupons">
            <h3><?php _e('Coupons', 'follow_up_emails'); ?></h3>
            <table class="wp-list-table widefat fixed posts">
                <thead>
                    <tr>
                        <th scope="col" id="coupon_name" class="manage-column column-type" style=""><?php _e('Coupon Name', 'follow_up_emails'); ?></th>
                        <th scope="col" id="email_address" class="manage-column column-usage_count <?php echo $email_address_class .' '. $email_address_sort; ?>" style="">
                            <a href="admin.php?page=followup-emails-reports&tab=reports&sortby=email_address&sort=<?php echo $email_address_dir; ?>&v=coupons">
                                <span><?php _e('Email Address', 'follow_up_emails'); ?></span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th scope="col" id="coupon_code" class="manage-column column-usage_count" style=""><?php _e('Coupon Code', 'follow_up_emails'); ?> <img class="help_tip" width="16" height="16" title="<?php _e('This is the unique coupon code generated by the follow-up email for this specific email address', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" /></th>
                        <th scope="col" id="email_name" class="manage-column column-usage_count" style=""><?php _e('Email Name', 'follow_up_emails'); ?> <img class="help_tip" width="16" height="16" title="<?php _e('This is the name of the follow-up email that generated the coupon that was sent to this specific email address', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" /></th>
                        <th scope="col" id="used" class="manage-column column-used <?php echo $used_class .' '. $used_sort; ?>" style="">
                            <a href="admin.php?page=followup-emails-reports&tab=reports&sortby=coupon_used&sort=<?php echo $used_dir; ?>&v=coupons">
                                <span><?php _e('Used', 'follow_up_emails'); ?>  <img class="help_tip" width="16" height="16" title="<?php _e('This tells you if this specific coupon code generated and sent via follow-up emails has been used, and if it has, it includes the date and time', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" /></span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th scope="col" id="date_sent" class="manage-column column-date_sent <?php echo $sent_class .' '. $sent_sort; ?>" style="">
                            <a href="admin.php?page=followup-emails-reports&tab=reports&sortby=date_sent&sort=<?php echo $sent_dir; ?>&v=coupons">
                                <span><?php _e('Date Sent', 'follow_up_emails'); ?> <img class="help_tip" width="16" height="16" title="<?php _e('This is the date and time that this specific coupon code was sent to this email address', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" /></span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                    </tr>
                </thead>
                <tbody id="the_list">
                    <?php
                    if (empty($coupon_reports)) {
                        echo '
                        <tr scope="row">
                            <th colspan="6">'. __('No reports available', 'follow_up_emails') .'</th>
                        </tr>';
                    } else {
                        foreach ($coupon_reports as $report) {
                            $used = __('No', 'follow_up_emails');

                            if ( $report->coupon_used == 1 ) {
                                $date = date( get_option('date_format') .' '. get_option('time_format') , strtotime($report->date_used));
                                $used = sprintf(__('Yes (%s)', 'follow_up_emails'), $date);
                            }

                            echo '
                            <tr scope="row">
                                <td class="post-title column-title">
                                    <strong>'. stripslashes($report->coupon_name) .'</strong>
                                </td>
                                <td>'. esc_html($report->email_address) .'</td>
                                <td>'. esc_html($report->coupon_code) .'</td>
                                <td>'. esc_html($report->email_name) .'</td>
                                <td>'. $used .'</td>
                                <td>'. date( get_option('date_format') .' '. get_option('time_format') , strtotime($report->date_sent)) .'</td>
                            </tr>
                            ';
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function report_email_trigger( $trigger, $email_row ) {
        $meta = '';

        if ( isset($email_row->meta) ) {
            $email_meta = maybe_unserialize( $email_row->meta );

            if ( $email_row->interval_type == 'order_total_above' && isset($email_meta['order_total_above']) ) {
                $meta = woocommerce_price($email_meta['order_total_above']);
            } elseif ( $email_row->interval_type == 'order_total_below' && isset($email_meta['order_total_below']) ) {
                $meta = woocommerce_price( $email_meta['order_total_below'] );
            }
        }

        return $trigger .' '. $meta;
    }

    public function report_email_address( $email, $report ) {

        if ( $report->order_id && $report->order_id != 0 ) {
            $order = new WC_Order($report->order_id);
            $email = $order->billing_email;
        }

        return $email;
    }

    public static function report_order_str( $str, $report ) {
        if ( $report->order_id != 0 ) {
            $order      = new WC_Order( $report->order_id );
            $str  = '<a href="'. get_admin_url() .'post.php?post='. $report->order_id .'&action=edit">View Order</a>';
        }

        return $str;
    }

    public function manual_types() {
        ?><option value="storewide"><?php _e('All Customers', 'follow_up_emails'); ?></option>
        <option value="customer"><?php _e('This Customer', 'follow_up_emails'); ?></option>
        <option value="product"><?php _e('Customers who bought these products', 'follow_up_emails'); ?></option>
        <option value="category"><?php _e('Customers who bought from these categories', 'follow_up_emails'); ?></option>
        <option value="timeframe"><?php _e('Customers who bought between these dates', 'follow_up_emails'); ?></option>
        <?php
    }

    public function manual_type_actions($email) {
        $categories = get_terms( 'product_cat', array( 'order_by' => 'name', 'order' => 'ASC' ) );
        ?>
        <div class="send-type-customer send-type-div">
            <select name="recipients[]" id="recipients" class="chzn-select email-search-select" multiple data-placeholder="Search by customer name or email..." style="width: 600px;"></select>
        </div>
        <div class="send-type-product send-type-div">
            <select id="product_ids" name="product_ids[]" class="ajax_chosen_select_products_and_variations" multiple data-placeholder="<?php _e('Search for a product&hellip;', 'woocommerce'); ?>" style="width: 600px"></select>
        </div>
        <div class="send-type-category send-type-div">
            <select id="category_ids" name="category_ids[]" class="chzn-select" data-placeholder="<?php _e('Search for a category&hellip;', 'follow_up_emails'); ?>" style="width: 600px;" multiple>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php _e($category->term_id); ?>" <?php echo ($email->category_id == $category->term_id) ? 'selected' : ''; ?>><?php echo esc_html($category->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="send-type-timeframe send-type-div">
            <?php _e('From:', 'follow_up_emails'); ?>
            <input type="text" class="" name="timeframe_from" id="timeframe_from" />

            <?php _e('To:', 'follow_up_emails'); ?>
            <input type="text" class="" name="timeframe_to" id="timeframe_to" />
        </div>
        <?php
    }

    public function manual_js() {
        ?>
        jQuery("#send_type").change(function() {
            switch (jQuery(this).val()) {
                case "customer":
                    jQuery(".send-type-customer").show();
                    break;

                case "product":
                    jQuery(".send-type-product").show();
                    break;

                case "category":
                    jQuery(".send-type-category").show();
                    break;

                case "timeframe":
                    jQuery(".send-type-timeframe").show();
                    break;
            }
        });

        jQuery("select.ajax_chosen_select_products_and_variations").ajaxChosen({
            method:     'GET',
            url:        ajaxurl,
            dataType:   'json',
            afterTypeDelay: 100,
            data:       {
                action:         'woocommerce_json_search_products_and_variations',
                security:       '<?php echo wp_create_nonce("search-products"); ?>'
            }
        }, function (data) {
            var terms = {};

            jQuery.each(data, function (i, val) {
                terms[i] = val;
            });

            return terms;
        });
        jQuery("select.email-search-select").ajaxChosen({
            method:     'GET',
            url:        ajaxurl,
            dataType:   'json',
            afterTypeDelay: 100,
            data:       {
                action:         'fue_email_query'
            }
        }, function (data) {
            var terms = {};

            jQuery.each(data, function (i, val) {
                terms[i] = val;
            });

            return terms;
        });
        <?php
    }

    public function my_email_subscriptions() {
        global $wpdb, $woocommerce;

        $user = wp_get_current_user();

        if ( $user->ID == 0 ) return;

        include FUE_TEMPLATES_DIR .'/my_account_emails.php';
    }

    public function process_unsubscribe_request() {
        global $wpdb;

        if (isset($_GET['fue_action']) && $_GET['fue_action'] == 'order_unsubscribe') {

            if (! wp_verify_nonce( $_GET['_wpnonce'], 'fue_unsubscribe' ) ) {
                die( 'Request error. Please try again.' );
            }

            $order_id   = $_GET['order_id'];
            $email      = $_GET['email'];

            $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}followup_email_orders WHERE user_email = %s AND order_id = %d AND is_sent = 0", $email, $order_id) );

            wp_redirect( add_query_arg( 'fue_order_unsubscribed', 1, get_permalink(fue_get_page_id('followup_my_subscriptions')) ) );
            exit;
        }
    }

    public function email_message($message, $email, $email_order) {
        global $wpdb;
        if ( $email_order->order_id > 0 && $email->email_type == 'reminder' ) {
            // count the total emails and the number of sent emails
            $total_emails   = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}followup_email_orders WHERE order_id = %d AND email_id = %d", $email_order->order_id, $email->id) );
            $sent_emails    = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}followup_email_orders WHERE order_id = %d AND email_id = %d AND is_sent = 1", $email_order->order_id, $email->id) );

            if ( $total_emails == 1 ) {
                $messages = self::str_search('{first_email}', '{/first_email}', $message);

                $message = (isset($messages[0])) ? $messages[0] : '';
            } elseif ( $total_emails == 2 ) {
                if ( $sent_emails == 0 ) {
                    $messages = self::str_search('{first_email}', '{/first_email}', $message);

                    $message = (isset($messages[0])) ? $messages[0] : '';
                } else {
                    $messages = self::str_search('{final_email}', '{/final_email}', $message);
                    $message = (isset($messages[0])) ? $messages[0] : '';
                }
            } else {
                if ( $sent_emails == 0 ) {
                    $messages = self::str_search('{first_email}', '{/first_email}', $message);
                    $message = (isset($messages[0])) ? $messages[0] : '';
                } elseif ( $sent_emails == ($total_emails - 1) ) {
                    $messages = self::str_search('{final_email}', '{/final_email}', $message);
                    $message = (isset($messages[0])) ? $messages[0] : '';
                } else {
                    $messages = self::str_search('{quantity_email}', '{/quantity_email}', $message);
                    $message = (isset($messages[0])) ? $messages[0] : '';
                }
            }
        }

        return $message;
    }

    public static function str_search($start, $end, $string, $borders = false) {
        $reg = "!".preg_quote ($start)."(.*?)".preg_quote ($end)."!is";
        preg_match_all ($reg, $string, $matches);
        if ($borders) {
            return $matches[0];
        } else {
            return $matches[1];
        }
    }

}

$GLOBALS['fue_woocommerce'] = new FUE_Woocommerce();