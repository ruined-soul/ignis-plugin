<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Manga Requests Module
class Ignis_Manga_Requests {
    public function __construct() {
        // Check if module is enabled
        $general_settings = get_option('ignis_general_settings', ['enable_manga_requests' => 1]);
        if (!$general_settings['enable_manga_requests']) {
            return;
        }

        // Register hooks
        add_action('wp_ajax_ignis_submit_manga_request', [$this, 'submit_manga_request']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_shortcode('ignis_manga_request_form', [$this, 'request_form_shortcode']);
        register_activation_hook(IGNIS_PLUGIN_DIR . 'ignis-plugin.php', [$this, 'create_tables']);
    }

    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'ignis_manga_requests';

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            timestamp DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        $version = get_option('ignis_db_version', '0.0.0');
        if (version_compare($version, '1.1.0', '<')) {
            update_option('ignis_db_version', '1.1.0');
        }
    }

    public function submit_manga_request() {
        check_ajax_referer('ignis_manga_request', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in.', 'ignis-plugin')]);
        }

        $user_id = get_current_user_id();
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';

        if (empty($title) || empty($description)) {
            wp_send_json_error(['message' => __('Title and description are required.', 'ignis-plugin')]);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'ignis_manga_requests';

        $result = $wpdb->insert($table_name, [
            'user_id' => $user_id,
            'title' => $title,
            'description' => $description,
            'status' => 'pending',
            'timestamp' => current_time('mysql')
        ]);

        if ($result === false) {
            wp_send_json_error(['message' => __('Failed to submit request.', 'ignis-plugin')]);
        }

        set_transient('ignis_toast_' . $user_id, [
            'message' => __('Manga request submitted!', 'ignis-plugin'),
            'type' => 'manga_request'
        ], 30);

        wp_send_json_success(['message' => __('Request submitted successfully.', 'ignis-plugin')]);
    }

    public function enqueue_assets() {
        wp_enqueue_style('ignis-requests', IGNIS_PLUGIN_URL . 'modules/manga-requests/assets/requests.css', [], IGNIS_PLUGIN_VERSION);
        wp_enqueue_script('ignis-requests', IGNIS_PLUGIN_URL . 'modules/manga-requests/assets/requests.js', ['jquery'], IGNIS_PLUGIN_VERSION, true);
        wp_localize_script('ignis-requests', 'ignis_requests', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ignis_manga_request')
        ]);
    }

    public function request_form_shortcode($atts) {
        ob_start();
        include IGNIS_PLUGIN_DIR . 'modules/manga-requests/templates/request-form.php';
        return ob_get_clean();
    }
}

// Initialize module
new Ignis_Manga_Requests();
?>
