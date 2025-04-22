<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Bug Bounty Module
class Ignis_Bug_Bounty {
    public function __construct() {
        // Check if module is enabled
        $general_settings = get_option('ignis_general_settings', ['enable_bug_bounty' => 1]);
        if (!$general_settings['enable_bug_bounty']) {
            return;
        }

        // Register hooks
        add_action('wp_ajax_ignis_submit_bug_report', [$this, 'submit_bug_report']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_shortcode('ignis_bug_report_form', [$this, 'bug_report_shortcode']);
    }

    public function submit_bug_report() {
        check_ajax_referer('ignis_bug_bounty', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in.', 'ignis-plugin')]);
        }

        $user_id = get_current_user_id();
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';

        if (empty($title) || empty($description)) {
            wp_send_json_error(['message' => __('Title and description are required.', 'ignis-plugin')]);
        }

        $screenshot = '';
        if (!empty($_FILES['screenshot']['name'])) {
            $upload = wp_handle_upload($_FILES['screenshot'], ['test_form' => false]);
            if (isset($upload['url']) && !isset($upload['error'])) {
                $screenshot = $upload['url'];
            } else {
                wp_send_json_error(['message' => __('Failed to upload screenshot.', 'ignis-plugin')]);
            }
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'ignis_bug_reports';

        $result = $wpdb->insert($table_name, [
            'user_id' => $user_id,
            'title' => $title,
            'description' => $description,
            'screenshot' => $screenshot,
            'status' => 'pending',
            'timestamp' => current_time('mysql')
        ]);

        if ($result === false) {
            wp_send_json_error(['message' => __('Failed to submit bug report.', 'ignis-plugin')]);
        }

        set_transient('ignis_toast_' . $user_id, [
            'message' => __('Bug report submitted!', 'ignis-plugin'),
            'type' => 'bug_report'
        ], 30);

        wp_send_json_success(['message' => __('Bug report submitted successfully.', 'ignis-plugin')]);
    }

    public function enqueue_assets() {
        wp_enqueue_style('ignis-bug-bounty', IGNIS_PLUGIN_URL . 'modules/bug-bounty/assets/bug-bounty.css', [], IGNIS_PLUGIN_VERSION);
        wp_enqueue_script('ignis-bug-bounty', IGNIS_PLUGIN_URL . 'modules/bug-bounty/assets/bug-bounty.js', ['jquery'], IGNIS_PLUGIN_VERSION, true);
        wp_localize_script('ignis-bug-bounty', 'ignis_bug_bounty', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ignis_bug_bounty')
        ]);
    }

    public function bug_report_shortcode($atts) {
        ob_start();
        include IGNIS_PLUGIN_DIR . 'modules/bug-bounty/templates/bug-report.php';
        return ob_get_clean();
    }
}

// Initialize module
new Ignis_Bug_Bounty();
?>
