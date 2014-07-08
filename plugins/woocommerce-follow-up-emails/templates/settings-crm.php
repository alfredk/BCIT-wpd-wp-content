<form action="admin-post.php" method="post" enctype="multipart/form-data">

    <h3><?php _e('Daily Emails Summary', 'follow_up_emails'); ?></h3>

    <table class="form-table">
        <tbody>
        <tr valign="top">
            <th><label for="daily_emails"><?php _e('Email Address(es)', 'follow_up_emails'); ?></label></th>
            <td>
                <input type="text" name="daily_emails" id="daily_emails" value="<?php echo esc_attr( get_option('fue_daily_emails', '') ); ?>" />
                <span class="description"><?php _e('comma separated', 'follow_up_emails'); ?></span>
            </td>
        </tr>
        <tr valign="top">
            <th><label for="daily_emails_time_hour"><?php _e('Preferred Time', 'follow_up_emails'); ?></label></th>
            <td>
                <?php
                $time   = get_option('fue_daily_emails_time', '12:00 AM');
                $parts  = explode(':', $time);
                $parts2 = explode(' ', $parts[1]);
                $hour   = $parts[0];
                $minute = $parts2[0];
                $ampm   = $parts2[1];
                ?>
                <select name="daily_emails_time_hour" id="daily_emails_time_hour">
                    <?php for ($x = 1; $x <= 12; $x++): ?>
                        <option value="<?php echo $x; ?>" <?php selected($hour, $x); ?>><?php echo ($x >= 10) ? $x : '0'. $x; ?></option>
                    <?php endfor; ?>
                </select>

                <select name="daily_emails_time_minute" id="daily_emails_time_minute">
                    <?php for ($x = 0; $x <= 55; $x+=15): ?>
                        <option value="<?php echo $x; ?>" <?php selected($minute, $x); ?>><?php echo ($x >= 10) ? $x : '0'. $x; ?></option>
                    <?php endfor; ?>
                </select>

                <select name="daily_emails_time_ampm" id="daily_emails_time_ampm">
                    <option value="AM" <?php selected($ampm, 'AM'); ?>>AM</option>
                    <option value="PM" <?php selected($ampm, 'PM'); ?>>PM</option>
                </select>
            </td>
        </tr>
        </tbody>
    </table>

    <h3><?php _e('BCC Settings', 'follow_up_emails'); ?></h3>

    <table class="form-table">
        <tbody>
        <tr valign="top">
            <th><label for="bcc"><?php _e('Email Address', 'follow_up_emails'); ?></label></th>
            <td>
                <input type="text" name="bcc" id="bcc" value="<?php echo esc_attr( $bcc ); ?>" />
            </td>
        </tr>
        </tbody>
    </table>

    <?php do_action( 'fue_settings_crm' ); ?>

    <p class="submit">
        <input type="hidden" name="action" value="sfn_followup_save_settings" />
        <input type="hidden" name="section" value="<?php echo $tab; ?>" />
        <input type="submit" name="save" value="<?php _e('Save Settings', 'follow_up_emails'); ?>" class="button-primary" />
    </p>

</form>