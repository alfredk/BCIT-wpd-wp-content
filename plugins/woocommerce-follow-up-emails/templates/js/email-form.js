jQuery(document).ready(function() {
        var sfn_checked = false;

        if ( jQuery("select.ajax_chosen_select_products_and_variations").length > 0 ) {
            jQuery("select.ajax_chosen_select_products_and_variations").change(function() {
                // remove the first option to limit to only 1 product per email
                if (jQuery(this).find("option:selected").length > 1) {
                    while (jQuery(this).find("option:selected").length > 1) {
                        jQuery(jQuery(this).find("option:selected")[0]).remove();
                    }

                    jQuery(this).trigger("liszt:updated");
                }
                jQuery("#use_custom_field").change();

                if (jQuery(this).find("option:selected").length == 1) {
                    // if selected product contain variations, show option to include variations
                    jQuery(".product_tr").block({ message: null, overlayCSS: { background: '#fff url('+ FUE.ajax_loader +') no-repeat center', opacity: 0.6 } });

                    jQuery.get(ajaxurl, {action: 'fue_product_has_children', product_id: jQuery(this).find("option:selected").val()}, function(resp) {
                        if ( resp == 1) {
                            jQuery(".product_include_variations").show();
                        } else {
                            jQuery("#include_variations").attr("checked", false);
                            jQuery(".product_include_variations").hide();
                        }

                        jQuery(".product_tr").unblock();
                    });
                } else {
                    jQuery("#include_variations").attr("checked", false);
                    jQuery(".product_include_variations").hide();
                }
            });

            jQuery("select.ajax_chosen_select_products_and_variations").ajaxChosen({
                method:     'GET',
                url:        ajaxurl,
                dataType:   'json',
                afterTypeDelay: 100,
                data:       {
                    action:         'woocommerce_json_search_products_and_variations',
                    security:       FUE.nonce
                }
            }, function (data) {
                var terms = {};

                jQuery.each(data, function (i, val) {
                    terms[i] = val;
                });

                return terms;
            });
        }

        if ( jQuery("select.chzn-select").length > 0 )
            jQuery("select.chzn-select").chosen();

        jQuery("#test_send").click(function() {
            var $btn    = jQuery(this);
            var old_val = $btn.val();

            $btn
                .val("Please wait...")
                .attr("disabled", true);

            var data = {
                'action'    : 'sfn_test_email',
                'id'        : jQuery("#id").val(),
                'message'   : (tinyMCE.get('email_message')) ? tinyMCE.get('email_message').getContent() : jQuery("#email_message").val()
            };

            jQuery(".test-email-field").each(function() {
                var field = jQuery(this).attr("id");
                data[field] = jQuery(this).val();
            });

            jQuery.post(ajaxurl, data, function(resp) {
                if (resp == "OK")
                    alert("Email sent!");
                else
                    alert(resp);

                $btn
                    .val(old_val)
                    .removeAttr("disabled");
            });
        });

        // Tooltips
        jQuery(".tips, .help_tip").tipTip({
            'attribute' : 'title',
            'fadeIn' : 50,
            'fadeOut' : 50,
            'delay' : 200
        });

        jQuery("#email_type").live("change", function() {
            var val = jQuery(this).val();
            reset_elements();

            if (val == "generic") {
                var show = ['.always_send_tr', '.var_item_names', '.var_item_categories', '.interval_type_option', '.interval_type_span', '.var'];
                var hide = ['.adjust_date_tr', '.signup_description', '.email_receipient_tr', '.btn_send_save', '.product_description_tr', '.product_tr', '.category_tr', '.use_custom_field_tr', '.custom_field_tr', '.var_item_name', '.var_item_category', '.interval_type_order_total_above', '.interval_type_order_total_below', '.interval_type_purchase_above_one', '.interval_type_total_purchases', '.interval_type_total_orders', '.interval_type_after_last_purchase', '.var_customer'];

                for (x = 0; x < show.length; x++) {
                    jQuery(show[x]).show();
                }

                for (x = 0; x < hide.length; x++) {
                    jQuery(hide[x]).hide();
                }

                // triggers
                var current_interval_type = jQuery("#interval_type").val();
                jQuery(".interval_type_option").remove();

                if ( email_intervals && email_intervals.generic.length > 0 ) {
                    for (var x = 0; x < email_intervals.generic.length; x++) {
                        var int_key = email_intervals.generic[x];
                        jQuery("#interval_type").append('<option class="interval_type_option interval_type_'+ int_key +'" id="interval_type_option_'+ int_key +'" value="'+ int_key +'">'+ interval_types[int_key] +'</option>');
                    }
                }

                jQuery("#interval_type").val(current_interval_type);
            }

            if (val == "normal") {
                var show = ['.always_send_tr', '.interval_type_option', '.interval_type_span', '.product_description_tr', '.product_tr', '.category_tr', '.use_custom_field_tr', '.var', '.var_item_name', '.var_item_category'];
                var hide = ['.adjust_date_tr', '.var_item_names', '.email_receipient_tr', '.btn_send_save', '.var_item_categories', '.signup_description', '.interval_type_order_total_above', '.interval_type_order_total_below', '.interval_type_purchase_above_one', '.interval_type_total_purchases', '.interval_type_total_orders', '.interval_type_after_last_purchase', '.var_customer'];

                for (x = 0; x < show.length; x++) {
                    jQuery(show[x]).show();
                }

                for (x = 0; x < hide.length; x++) {
                    jQuery(hide[x]).hide();
                }

                // triggers
                var current_interval_type = jQuery("#interval_type").val();
                jQuery(".interval_type_option").remove();

                if ( email_intervals && email_intervals.normal.length > 0 ) {
                    for (var x = 0; x < email_intervals.normal.length; x++) {
                        var int_key = email_intervals.normal[x];
                        jQuery("#interval_type").append('<option class="interval_type_option interval_type_'+ int_key +'" id="interval_type_option_'+ int_key +'" value="'+ int_key +'">'+ interval_types[int_key] +'</option>');
                    }
                }

                jQuery("#interval_type").val(current_interval_type);
            }

            if (val == "signup") {
                var show = ['.interval_type_option', '.signup_description', '.var'];
                var hide = ['.always_send_tr', '.interval_type_span', '.adjust_date_tr', '.btn_send_save', '.email_receipient_tr', '.product_description_tr', '.product_tr', '.category_tr', '.use_custom_field_tr', '.var_item_name', '.var_item_category', '.var_item_names', '.var_item_categories', '.var_order_number', '.var_order_datetime', '.interval_type_order_total_above', '.interval_type_order_total_below', '.interval_type_purchase_above_one', '.interval_type_after_last_purchase', '.interval_type_total_purchases', '.interval_type_total_orders', '.interval_type_after_last_purchase', '.var_customer'];

                for (x = 0; x < hide.length; x++) {
                    jQuery(hide[x]).hide();
                }

                // triggers
                var current_interval_type = jQuery("#interval_type").val();
                jQuery(".interval_type_option").remove();

                if ( email_intervals && email_intervals.signup.length > 0 ) {
                    for (var x = 0; x < email_intervals.signup.length; x++) {
                        var int_key = email_intervals.signup[x];
                        jQuery("#interval_type").append('<option class="interval_type_option interval_type_'+ int_key +'" id="interval_type_option_'+ int_key +'" value="'+ int_key +'">'+ interval_types[int_key] +'</option>');
                    }
                }

                jQuery("#interval_type").val(current_interval_type);
            }

            if (val == "manual") {
                var hide = ['.interval-field', '.always_send_tr', '.interval_tr', '.adjust_date_tr', '.product_description_tr', '.product_tr', '.category_tr', '.use_custom_field_tr', '.var_item_name', '.var_item_category', '.var_item_names', '.var_item_categories', '.var_order', '.var_order_datetime', '.var_order_date', '.interval_type_order_total_above', '.interval_type_order_total_below', '.interval_type_purchase_above_one', '.interval_type_total_purchases', '.interval_type_total_orders', '.interval_type_after_last_purchase', '.var_customer'];

                for (x = 0; x < hide.length; x++) {
                    jQuery(hide[x]).hide();
                }

                // triggers
                var current_interval_type = jQuery("#interval_type").val();
                jQuery(".interval_type_option").remove();

                if ( typeof email_intervals != "undefined" && email_intervals.manual.length > 0 ) {
                    for (var x = 0; x < email_intervals.manual.length; x++) {
                        var int_key = email_intervals.manual[x];
                        jQuery("#interval_type").append('<option class="interval_type_option interval_type_'+ int_key +'" id="interval_type_option_'+ int_key +'" value="'+ int_key +'">'+ interval_types[int_key] +'</option>');
                    }
                }

                jQuery("#interval_type").val(current_interval_type);
            }

            if (val == "customer") {
                var show = ['.always_send_tr', '.interval_type_order_total_above', '.interval_type_order_total_below', '.interval_type_purchase_above_one', '.interval_type_total_purchases', '.interval_type_total_orders', '.interval_type_total_purchases', '.interval_type_after_last_purchase', '.interval_type_span', '.var_customer'];
                var hide = ['.adjust_date_tr', '.interval_type_option', '.always_send_tr', '.signup_description', '.product_description_tr', '.product_tr', '.category_tr', '.use_custom_field_tr', '.custom_field_tr', '.var_item_name', '.var_item_category', '.var_item_names', '.var_item_categories', '.var_item_name', '.var_item_category', '.interval_duration_date'];

                for (x = 0; x < hide.length; x++) {
                    jQuery(hide[x]).hide();
                }

                for (x = 0; x < show.length; x++) {
                    jQuery(show[x]).show();
                }

                // triggers
                var current_interval_type = jQuery("#interval_type").val();
                jQuery(".interval_type_option").remove();

                if ( email_intervals && email_intervals.customer.length > 0 ) {
                    for (var x = 0; x < email_intervals.customer.length; x++) {
                        var int_key = email_intervals.customer[x];
                        jQuery("#interval_type").append('<option class="interval_type_option interval_type_'+ int_key +'" id="interval_type_option_'+ int_key +'" value="'+ int_key +'">'+ interval_types[int_key] +'</option>');
                    }
                }

                jQuery("#interval_type").val(current_interval_type);
            }

            jQuery(".var_first_email, .var_quantity_email, .var_final_email").hide();
            if (val == "reminder") {
                var show = ['.interval_type_option', '.interval_type_span', '.product_description_tr', '.product_tr', '.category_tr', '.use_custom_field_tr', '.var'];
                var hide = ['.always_send_tr', '.adjust_date_tr', '.var_item_names', '.email_receipient_tr', '.btn_send_save', '.var_item_categories', '.signup_description', '.interval_type_order_total_above', '.interval_type_order_total_below', '.interval_type_purchase_above_one', '.interval_type_total_purchases', '.interval_type_total_orders', '.interval_type_after_last_purchase', '.var_customer'];

                for (x = 0; x < show.length; x++) {
                    jQuery(show[x]).show();
                }

                for (x = 0; x < hide.length; x++) {
                    jQuery(hide[x]).hide();
                }

                jQuery("#interval_type option").remove();
                for (key in interval_types) {
                    if (key == "processing" || key == "completed") {
                        jQuery("#interval_type").append('<option class="interval_type_option interval_type_'+ key +'" id="interval_type_option_'+ key +'" value="'+ key +'">'+ interval_types[key] +'</option>');
                    }
                }

                jQuery(".var_first_email, .var_quantity_email, .var_final_email").show();
            }

            jQuery(".adjust_date_tr").hide();

            jQuery("body").trigger("fue_email_type_changed", [val]);

            if (jQuery("#interval_duration").val() == "date") {
                jQuery(".hide-if-date").hide();
                jQuery(".show-if-date").show();
            } else {
                jQuery(".hide-if-date").show();
                jQuery(".show-if-date").hide();

                if (val == "signup") {
                    jQuery(".interval_type_span").hide();
                }
            }

            if (jQuery("#interval_type").val() != "order_total_above" ) {
                jQuery(".show-if-order_total_above").hide();
            } else {
                jQuery(".show-if-order_total_above").show();
            }

            if (jQuery("#interval_type").val() != "order_total_below" ) {
                jQuery(".show-if-order_total_below").hide();
            } else {
                jQuery(".show-if-order_total_below").show();
            }

            if (jQuery("#interval_type").val() != "total_orders") {
                jQuery(".show-if-total_orders").hide();
            } else {
                jQuery(".show-if-total_orders").show();
            }

            if (jQuery("#interval_type").val() != "total_purchases") {
                jQuery(".show-if-total_purchases").hide();
            } else {
                jQuery(".show-if-total_purchases").show();
            }

            if (jQuery("#interval_type option:selected").css("display") == "none") {
                jQuery("#interval_type option").each(function() {
                    if (jQuery(this).css("display") != "none") {
                        jQuery("#interval_type").val(jQuery(this).val());
                        return false;
                    }
                });
            }

            if (jQuery("#interval_type").val() == "after_last_purchase" ) {
                jQuery(".adjust_date_tr").show();
            }

        });

        jQuery("#email_type").change();

        jQuery("#tracking_on").change(function() {
            if (jQuery(this).attr("checked")) {
                jQuery(".tracking_on").show();
            } else {
                jQuery(".tracking_on").hide();
            }
        }).change();

        jQuery("#interval_type").change(function() {

            jQuery(".adjust_date_tr").hide();

            if (jQuery(this).val() != "cart") {
                jQuery(".not-cart").show();
            } else {
                jQuery(".not-cart").hide();
            }

            if (jQuery(this).val() == "after_last_purchase" ) {
                jQuery(".adjust_date_tr").show();
            }

            if (jQuery(this).val() != "order_total_above" ) {
                jQuery(".show-if-order_total_above").hide();
            } else {
                jQuery(".show-if-order_total_above").show();
            }

            if (jQuery(this).val() != "order_total_below" ) {
                jQuery(".show-if-order_total_below").hide();
            } else {
                jQuery(".show-if-order_total_below").show();
            }

            if (jQuery(this).val() != "total_orders") {
                jQuery(".show-if-total_orders").hide();
            } else {
                jQuery(".show-if-total_orders").show();
            }

            if (jQuery(this).val() != "total_purchases") {
                jQuery(".show-if-total_purchases").hide();
            } else {
                jQuery(".show-if-total_purchases").show();
            }

            if (jQuery(this).val() == "total_orders" || jQuery(this).val() == "total_purchases") {
                jQuery(".meta_one_time_tr").show();
            } else {
                jQuery(".meta_one_time_tr").hide();
            }

            jQuery("body").trigger("fue_interval_type_changed", [jQuery(this).val()]);

        }).change();

        jQuery("#interval_duration").change(function() {
            jQuery("#email_type").change();
        }).change();

        jQuery(".date").datepicker();

        jQuery("#use_custom_field").change(function() {
            if (jQuery(this).attr("checked")) {
                jQuery(".show-if-custom-field").show();
            } else {
                jQuery(".show-if-custom-field").hide();
            }
        }).change();

        jQuery("#custom_fields").change(function() {
            if (jQuery(this).val() == "Select a product first.") return;
            jQuery(".show-if-cf-selected").show();
            jQuery("#custom_field").val("{cf "+ jQuery("#product_id").val() +" "+ jQuery(this).val() +"}");
        }).change();

        /*jQuery("#sfn_form").submit(function(e) {
            if (sfn_checked == false) {
                jQuery("#save")
                    .val(FUE.processing_request)
                    .attr("disabled", true);

                var data = {
                    'action'            : 'sfn_fe_find_dupes',
                    'type'              : jQuery("#email_type").val(),
                    'interval'          : jQuery("#interval").val(),
                    'interval_duration' : jQuery("#interval_duration").val(),
                    'interval_type'     : jQuery("#interval_type").val(),
                    'product_id'        : jQuery("#product_id").val(),
                    'category_id'       : jQuery("#category_id").val(),
                    'always_send'       : jQuery("#always_send").is(":checked") ? 1 : 0
                };

                if (jQuery("#id").length > 0) {
                    data.id = jQuery("#id").val();
                }

                jQuery.post(ajaxurl, data, function(resp) {
                    jQuery(".sfn-error").remove();
                    if (resp == "DUPE") {
                        jQuery('<div class="message error sfn-error"><p>'+ FUE.dupe +'</p></div>').insertAfter("#sfn_form h3");

                        jQuery('html, body').animate({
                             scrollTop: jQuery(jQuery(".sfn-error")[0]).offset().top-50
                         }, 1000);
                    } else if (resp == "SIMILAR") {
                        if (confirm(FUE.similar)) {
                            sfn_checked = true;
                            jQuery("#sfn_form").submit();
                        }
                    } else {
                        sfn_checked = true;
                        jQuery("#sfn_form").submit();
                    }

                    jQuery("#save")
                        .val(FUE.save)
                        .attr("disabled", false)
                });
                return false;
            }
            return true;
        });*/

    });

    function reset_elements() {
        jQuery(".hideable").show();

        var trigger = jQuery("#interval_type").val();

        /*jQuery("#interval_type option").remove();
        for (key in interval_types) {
            jQuery("#interval_type").append('<option class="interval_type_option interval_type_'+ key +'" id="interval_type_option_'+ key +'" value="'+ key +'">'+ interval_types[key] +'</option>');
        }*/

        if (trigger) {
            //jQuery("#interval_type_option_"+trigger).attr("selected", true);
        }
    }
