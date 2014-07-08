jQuery(function($) {
    // WP-Cron to Action Scheduler
    $("#run_process_to_as").click(function(e) {
        e.preventDefault();

        if (! confirm(FUE_Scheduler.confirm_message) )
            return false;

        $(this).attr("disabled", true);

        $("#proc_container").show();
        var $proc_win   = $("#proc_window");
        var total       = 0;
        var current     = 0;

        // start import
        $.post( ajaxurl, {action: "fue_as_import_start"} );

        $("body").bind("fue_as_total_rows_updated", function(event, total) {

            if ( total == 0 ) {
                $("body").trigger("fue_as_import_complete");
                return false;
            }

            fue_as_total_updated(total);
            fue_as_import(current);
        });

        $("body").bind("fue_as_imported", function(event, data) {
            current = data;
            var percent = Math.round(((current / total) * 100) * 100) / 100;

            $("#proc_status").html( fue_number_format(current) +' of '+ fue_number_format(total) +' ('+ percent +'%)' );
            $("#proc_window").progressbar({
                value: percent
            });

            if ( current < total ) {
                fue_as_import(current);
            } else {
                $("body").trigger("fue_as_import_complete");
            }
        });

        $("body").bind("fue_as_import_complete", function( event ) {
            // done!
            $(".loader-img").hide();
            $("#proc_container").hide();

            // mark import as completed
            $.post( ajaxurl, {action: "fue_as_import_complete"}, function() {
                window.location.href = 'admin.php?page=followup-emails-settings&tab=system&switched_scheduler=as';
            } );
        });

        // get the total number of records to import
        var request_data = {
            action: 'fue_as_count_import_rows'
        };
        $.getJSON( ajaxurl, request_data, function(response) {

            if ( response.error ) {
                alert(response.error);
                return false;
            } else {
                total = response.total;
            }

            $("body").trigger("fue_as_total_rows_updated", total);
        });

    });

    function fue_as_total_updated( total ) {
        $("#proc_status").html( '0 of '+ fue_number_format(total) +' (0%)' );
    }

    function fue_as_import(current) {
        var request_data = {
            action: 'fue_as_import',
            next: current
        }
        $.post(ajaxurl, request_data, function(response) {
            var json = $.parseJSON(response);
            $("body").trigger("fue_as_imported", json.next);
        });
    }

    // Action Scheduler to WP-Cron
    $("#run_process_to_wpc").click(function(e) {
        e.preventDefault();

        if (! confirm(FUE_Scheduler.confirm_message) )
            return false;

        $(this).attr("disabled", true);

        // mark import as completed
        $.post( ajaxurl, {action: "fue_wpc_import"}, function() {
            window.location.href = 'admin.php?page=followup-emails-settings&tab=system&switched_scheduler=wp';
        } );

    });

    function fue_number_format( num ) {
        num = num.toString().replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,");

        return num;
    }

});