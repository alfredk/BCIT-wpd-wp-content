<div class="wrap">
    <div class="icon32"><img src="<?php echo FUE_TEMPLATES_URL .'/images/send_mail.png'; ?>" /></div>
    <h2>
        <?php _e('Scheduler System', 'follow_up_emails'); ?>
    </h2>

    <?php if ( FollowUpEmails::$scheduling_system == 'wp-cron' ): ?>
        <button class="button-primary" id="run_process_to_as"><?php _e('Switch to Action Scheduler', 'follow_up_emails'); ?></button>
    <?php else: ?>
        <button class="button-primary" id="run_process_to_wpc"><?php _e('Switch to WP-Cron', 'follow_up_emails'); ?></button>
    <?php endif; ?>

    <div id="proc_container" style="width: 800px; display: none;">
        <h3><?php _e('Status', 'follow_up_emails'); ?></h3>

        <p class="description"><?php _e('Importing in progress. Please do not close this browser or reload this page.', 'follow_up_emails'); ?></p>

        <img src="<?php echo FUE_TEMPLATES_URL .'/images/ajax-loader.gif'; ?>" style="float:right;" class="loader-img" />
        <div id="proc_status"><?php _e('Loading...', 'follow_up_emails'); ?></div>
        <div id="proc_window" style="width: 800px; height: 30px; border: 1px solid #CECECE;"></div>
    </div>
</div>