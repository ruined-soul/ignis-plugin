<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Backup Restore Module
class Ignis_Backup_Restore {
    public function __construct() {
        // Check if module is enabled
        $general_settings = get_option('ignis_general_settings', ['enable_backup_restore' => 1]);
        if (!$general_settings['enable_backup_restore']) {
            return;
        }

        // Register hooks
        add_action('wp_ajax_ignis_create_backup', [$this, 'create_backup']);
        add_action('wp_ajax_ignis_restore_backup', [$this, 'restore_backup']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_shortcode('ignis_backup_dashboard', [$this, 'dashboard_shortcode']);
        add_shortcode('ignis_backup_settings', [$this, 'settings_shortcode']);
        add_action('ignis_daily_backup', [$this, 'scheduled_backup']);
        add_action('init', [$this, 'schedule_cron']);
    }

    public function schedule_cron() {
        if (!wp_next_scheduled('ignis_daily_backup')) {
            wp_schedule_event(time(), 'daily', 'ignis_daily_backup');
        }
    }

    public function create_backup() {
        check_ajax_referer('ignis_backup', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized.', 'ignis-plugin')]);
        }

        global $wpdb;
        $tables = ['ignis_logs', 'ignis_referrals', 'ignis_bug_reports', 'ignis_manga_requests'];
        $backup_data = [];

        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $backup_data[$table] = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
        }

        $options = [
            'ignis_general_settings', 'ignis_points_settings', 'ignis_currency_settings',
            'ignis_username_settings', 'ignis_custom_themes', 'ignis_shortener_settings',
            'ignis_chatroom_settings'
        ];
        $backup_data['options'] = [];
        foreach ($options as $option) {
            $backup_data['options'][$option] = get_option($option, []);
        }

        $backup_dir = WP_CONTENT_DIR . '/ignis-backups/';
        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
        }

        $filename = 'ignis-backup-' . date('Y-m-d-H-i-s') . '.json';
        $file_path = $backup_dir . $filename;
        file_put_contents($file_path, json_encode($backup_data));

        wp_send_json_success([
            'message' => __('Backup created successfully.', 'ignis-plugin'),
            'filename' => $filename
        ]);
    }

    public function restore_backup() {
        check_ajax_referer('ignis_backup', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized.', 'ignis-plugin')]);
        }

        $filename = isset($_POST['filename']) ? sanitize_file_name($_POST['filename']) : '';
        $file_path = WP_CONTENT_DIR . '/ignis-backups/' . $filename;

        if (!file_exists($file_path)) {
            wp_send_json_error(['message' => __('Backup file not found.', 'ignis-plugin')]);
        }

        $backup_data = json_decode(file_get_contents($file_path), true);
        if (!$backup_data) {
            wp_send_json_error(['message' => __('Invalid backup file.', 'ignis-plugin')]);
        }

        global $wpdb;
        foreach ($backup_data as $table => $rows) {
            if ($table === 'options') {
                foreach ($rows as $option => $value) {
                    update_option($option, $value);
                }
                continue;
            }

            $table_name = $wpdb->prefix . $table;
            $wpdb->query("TRUNCATE TABLE $table_name");
            foreach ($rows as $row) {
                $wpdb->insert($table_name, $row);
            }
        }

        wp_send_json_success(['message' => __('Backup restored successfully.', 'ignis-plugin')]);
    }

    public function scheduled_backup() {
        $settings = get_option('ignis_backup_settings', ['enable_auto_backup' => 0]);
        if (!$settings['enable_auto_backup']) {
            return;
        }

        $this->create_backup();
    }

    public function enqueue_assets() {
        wp_enqueue_style('ignis-backup', IGNIS_PLUGIN_URL . 'modules/backup-restore/assets/backup.css', [], IGNIS_PLUGIN_VERSION);
        wp_enqueue_script('ignis-backup', IGNIS_PLUGIN_URL . 'modules/backup-restore/assets/backup.js', ['jquery'], IGNIS_PLUGIN_VERSION, true);
        wp_localize_script('ignis-backup', 'ignis_backup', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ignis_backup')
        ]);
    }

    public function dashboard_shortcode($atts) {
        ob_start();
        include IGNIS_PLUGIN_DIR . 'modules/backup-restore/templates/dashboard.php';
        return ob_get_clean();
    }

    public function settings_shortcode($atts) {
        ob_start();
        include IGNIS_PLUGIN_DIR . 'modules/backup-restore/templates/settings.php';
        return ob_get_clean();
    }
}

// Initialize module
new Ignis_Backup_Restore();
?>
