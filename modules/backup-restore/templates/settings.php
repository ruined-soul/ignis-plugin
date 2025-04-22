<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$general_settings = get_option('ignis_general_settings', ['enable_backup_restore' => 1]);
if (!$general_settings['enable_backup_restore']) {
    echo '<p>' . __('Backup and restore is currently disabled.', 'ignis-plugin') . '</p>';
    return;
}

if (!current_user_can('manage_options')) {
    echo '<p>' . __('You do not have permission to access this page.', 'ignis-plugin') . '</p>';
    return;
}

$settings = get_option('ignis_backup_settings', ['enable_auto_backup' => 0]);

get_header();
?>

<div class="ignis-backup">
    <h2><?php _e('Backup Settings', 'ignis-plugin'); ?></h2>
    <form method="post">
        <table class="form-table">
            <tr>
                <th><label for="enable_auto_backup"><?php _e('Enable Auto Backup', 'ignis-plugin'); ?></label></th>
                <td>
                    <input type="checkbox" name="enable_auto_backup" id="enable_auto_backup" <?php checked($settings['enable_auto_backup'], 1); ?>>
                    <p class="description"><?php _e('Schedule daily backups of plugin data.', 'ignis-plugin'); ?></p>
                </td>
            </tr>
        </table>
        <button type="submit" class="button"><?php _e('Save Settings', 'ignis-plugin'); ?></button>
    </form>
</div>

<?php
get_footer();
?>
