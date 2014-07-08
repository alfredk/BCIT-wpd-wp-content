(function($) {
    $.extend({
        keys: function(obj) {
            var a = [];
            $.each(obj, function(k) {
                a.push(k)
            });
            return a;
        }
    });
})(jQuery);



jQuery(document).ready(function($) {

    var info_row;
    var info_box;

    var info_row_index;

    var width = 0;
    
    $('.btn-single').click(function() {
        $('form.variations_form').slideDown();
    });

    $('.btn-back-to-single').click(function() {
        $('#matrix_form').slideUp('200', function() {
            $('div.product').slideDown('400', function() {
                $('form.variations_form').slideDown();
            });
        });
    });

    $('.btn-bulk').click(function() {
        $('div.product').slideUp('200', function() {
            $('form.variations_form').hide();
            $('#matrix_form').slideDown('400', function() {
                $('#qty_input_0').focus();
            });
        });

    });

    $('.qty_input').focus(function() {

        var $tr = $(this).closest('tr');

        $('td', '#matrix_form_table').removeClass('active');

        $(this).closest('td').addClass('active');
        var cols = $(this).closest('tr').find('td').length;

        if (!info_row) {
            info_row = $('<tr class="info-row"><td id="info_cell" colspan="' + cols + '"></td></tr>').insertBefore($tr);
        } else {
            if (info_row_index != $tr.data('index')) {
                info_row_index = $tr.data('index');
                info_row.insertBefore($tr);
            }
        }
        var info_box_id = '#' + $(this).attr('id') + '_info';

        if (info_box) {
            //move it back into storage
            info_box.appendTo('#matrix_form_info_holder');
        }

        info_box = $(info_box_id);
        info_box.appendTo($('#info_cell'));
    });



    //Setup the validation
    $("#wholesale_form").validate({
        errorElement: "div",
        wrapper: "div", // a wrapper around the error message
        errorPlacement: function(error, element) {
            offset = element.offset();
            error.insertBefore(element)
            error.addClass('message');  // add a class to the wrapper
            error.css('position', 'absolute');
            error.css('left', offset.left + element.outerWidth());
            error.css('top', offset.top);
        }
    });


    $('.qty_input').each(function(index, element) {

        var manage_stock = $(element).data('manage-stock') == 'yes';
        var stock_max = $(element).data('max');
        var in_stock = $(element).data('instock');
        var backorders = $(element).data('backorders');
        var vmsg = $(element).data('vmsg');

        if (manage_stock && !backorders) {
            $(element).rules('add', {
                max: stock_max,
                messages: {
                    max: vmsg
                }
            });
        }

    });

});