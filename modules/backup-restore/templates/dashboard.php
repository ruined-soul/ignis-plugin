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

$backup_dir = WP_CONTENT_DIR . '/ignis-backups/';
$backups = glob($backup_dir . '*.json');
$backups = array_map('basename', $backups);

get_header();
?>

<div class="ignis-backup">
    <h2><?php _e('Backup & Restore Dashboard', 'ignis-plugin'); ?></h2>
    <button class="ignis-create-backup"><?php _e('Create Backup Now', 'ignis-plugin'); ?></button>
    <?php if ($backups) : ?>
        <table>
            <thead>
                <tr>
                    <th><?php _e('Backup File', 'ignis-plugin'); ?></th>
                    <th><?php _e('Actions', 'ignis-plugin'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($backups as $backup) : ?>
                    <tr>
                        <td><?php echo esc_html($backup); ?></td>
                        <td>
                            <button class="ignis-restore-backup" data-filename="<?php echo esc_attr($backup); ?>"><?php _e('Restore', 'ignis-plugin'); ?></button>
                            <a href="<?php echo esc_url(content_url('ignis-backups/' . $backup)); ?>" class="button"><?php _e('Download', 'ignis-plugin'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p><?php _e('No backups available.', 'ignis-plugin'); ?></p>
    <?php endif; ?>
</div>

<?php
get_footer();
?>
