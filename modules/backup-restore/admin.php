<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Backup Restore Admin
class Ignis_Backup_Restore_Admin {
    public function __construct() {
        add_filter('ignis_admin_submenus', [$this, 'register_admin_submenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_admin_submenu($submenus) {
        $submenus[] = [
            'title' => __('Backup & Restore', 'ignis-plugin'),
            'menu_title' => __('Backup', 'ignis-plugin'),
            'capability' => 'manage_options',
            'slug' => 'ignis-backup-restore',
            'callback' => [$this, 'admin_page_callback']
        ];
        return $submenus;
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'ignis-backup-restore') !== false) {
            wp_enqueue_style('ignis-backup-admin', IGNIS_PLUGIN_URL . 'modules/backup-restore/assets/admin.css', [], IGNIS_PLUGIN_VERSION);
            wp_enqueue_script('ignis-backup-admin', IGNIS_PLUGIN_URL . 'modules/backup-restore/assets/admin.js', ['jquery'], IGNIS_PLUGIN_VERSION, true);
        }
    }

    public function admin_page_callback() {
        $settings = get_option('ignis_backup_settings', ['enable_auto_backup' => 0]);

        if (isset($_POST['ignis_backup_settings']) && check_admin_referer('ignis_backup_settings')) {
            $settings['enable_auto_backup'] = isset($_POST['ignis_backup']['enable_auto_backup']) ? 1 : 0;
            update_option('ignis_backup_settings', $settings);
            echo '<div class="updated"><p>' . __('Settings saved.', 'ignis-plugin') . '</p></div>';
        }

        $backup_dir = WP_CONTENT_DIR . '/ignis-backups/';
        $backups = glob($backup_dir . '*.json');
        $backups = array_map('basename', $backups);

        ?>
        <div class="wrap">
            <h1><?php _e('Backup & Restore', 'ignis-plugin'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('ignis_backup_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="enable_auto_backup"><?php _e('Enable Auto Backup', 'ignis-plugin'); ?></label></th>
                        <td>
                            <input type="checkbox" name="ignis_backup[enable_auto_backup]" id="enable_auto_backup" <?php checked($settings['enable_auto_backup'], 1); ?>>
                            <p class="description"><?php _e('Schedule daily backups of plugin data.', 'ignis-plugin'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2><?php _e('Manual Backup', 'ignis-plugin'); ?></h2>
            <button class="button ignis-create-backup"><?php _e('Create Backup Now', 'ignis-plugin'); ?></button>
            <h2><?php _e('Restore Backup', 'ignis-plugin'); ?></h2>
            <?php if ($backups) : ?>
                <table class="wp-list-table widefat fixed striped">
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
                                    <button class="button ignis-restore-backup" data-filename="<?php echo esc_attr($backup); ?>"><?php _e('Restore', 'ignis-plugin'); ?></button>
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
    }
}

// Initialize module
new Ignis_Backup_Restore_Admin();
?>
