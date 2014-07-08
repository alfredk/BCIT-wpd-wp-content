<form action="admin-post.php" method="post" enctype="multipart/form-data">

    <h3><?php _e('Unsubscribe Page', 'follow_up_emails'); ?></h3>

    <table class="form-table">
        <tbody>
        <tr valign="top">
            <th><label for="unsubscribe_page"><?php _e('Select Unsubscribe Page', 'follow_up_emails'); ?></label></th>
            <td>
                <select name="unsubscribe_page" id="unsubscribe_page">
                    <?php
                    foreach ($pages as $p):
                        $sel = ($p->ID == $page) ? 'selected' : '';
                        ?>
                        <option value="<?php echo esc_attr($p->ID); ?>" <?php echo $sel; ?>><?php echo esc_html($p->post_title); ?></option>
                    <?php endforeach; ?>
                </select>
                <a class="button" href="post.php?post=<?php echo $page; ?>&action=edit"><?php _e('Edit Unsubscribe Page', 'follow_up_emails'); ?></a>
            </td>
        </tr>
        </tbody>
    </table>

    <h3><?php _e('Permissions', 'follow_up_emails'); ?></h3>

    <table class="form-table">
        <tbody>
        <tr valign="top">
            <th><label for="roles"><?php _e('Roles', 'follow_up_emails'); ?></label></th>
            <td>
                <select name="roles[]" id="roles" class="chzn" multiple style="width: 400px;">
                    <?php
                    $roles = get_editable_roles();
                    foreach ( $roles as $key => $role ) {
                        $selected = false;
                        $readonly = '';
                        if (array_key_exists('manage_follow_up_emails', $role['capabilities'])) {
                            $selected = true;

                            if ( $key == 'administrator' ) {
                                $readonly = 'readonly';
                            }
                        }
                        echo '<option value="'. $key .'" '. selected($selected, true, false) .'>'. $role['name'] .'</option>';

                    }
                    ?>
                </select>
                <script>jQuery("#roles").chosen();</script>
            </td>
        </tr>
        </tbody>
    </table>

    <?php do_action( 'fue_settings_email' ); ?>

    <p class="submit">
        <input type="hidden" name="action" value="sfn_followup_save_settings" />
        <input type="hidden" name="section" value="<?php echo $tab; ?>" />
        <input type="submit" name="save" value="<?php _e('Save Settings', 'follow_up_emails'); ?>" class="button-primary" />
    </p>

</form>