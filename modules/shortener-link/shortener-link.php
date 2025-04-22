<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Shortener Link Module
class Ignis_Shortener {
    public function __construct() {
        // Check if module is enabled
        $general_settings = get_option('ignis_general_settings', ['enable_shortener' => 1]);
        if (!$general_settings['enable_shortener']) {
            return;
        }

        // Register hooks
        add_action('wp_ajax_ignis_shorten_url', [$this, 'shorten_url']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_shortcode('ignis_shortener_form', [$this, 'shortener_form_shortcode']);
        add_filter('ignis_shortener_response', [$this, 'validate_shortened_url'], 10, 2);
    }

    public function shorten_url() {
        check_ajax_referer('ignis_shortener', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in.', 'ignis-plugin')]);
        }

        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            wp_send_json_error(['message' => __('Invalid URL.', 'ignis-plugin')]);
        }

        $settings = get_option('ignis_shortener_settings', ['endpoint' => '', 'api_key' => '']);
        $endpoint = $settings['endpoint'];
        $api_key = $settings['api_key'];

        if (empty($endpoint) || empty($api_key)) {
            wp_send_json_error(['message' => __('Shortener service not configured.', 'ignis-plugin')]);
        }

        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode(['long_url' => $url]),
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => __('Failed to shorten URL.', 'ignis-plugin')]);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data['link'])) {
            wp_send_json_error(['message' => __('Failed to retrieve shortened URL.', 'ignis-plugin')]);
        }

        $user_id = get_current_user_id();
        set_transient('ignis_toast_' . $user_id, [
            'message' => __('URL shortened successfully!', 'ignis-plugin'),
            'type' => 'shortener'
        ], 30);

        wp_send_json_success([
            'message' => __('URL shortened successfully.', 'ignis-plugin'),
            'short_url' => $data['link']
        ]);
    }

    public function validate_shortened_url($valid, $short_url) {
        $settings = get_option('ignis_shortener_settings', ['endpoint' => '', 'api_key' => '']);
        $endpoint = $settings['endpoint'];
        $api_key = $settings['api_key'];

        if (empty($endpoint) || empty($api_key)) {
            return false;
        }

        $response = wp_remote_get($endpoint . '/info?url=' . urlencode($short_url), [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key
            ],
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return !empty($data['long_url']);
    }

    public function enqueue_assets() {
        wp_enqueue_style('ignis-shortener', IGNIS_PLUGIN_URL . 'modules/shortener-link/assets/shortener.css', [], IGNIS_PLUGIN_VERSION);
        wp_enqueue_script('ignis-shortener', IGNIS_PLUGIN_URL . 'modules/shortener-link/assets/shortener.js', ['jquery'], IGNIS_PLUGIN_VERSION, true);
        wp_localize_script('ignis-shortener', 'ignis_shortener', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ignis_shortener')
        ]);
    }

    public function shortener_form_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('You must be logged in to shorten URLs.', 'ignis-plugin') . '</p>';
        }

        ob_start();
        ?>
        <div class="ignis-shortener">
            <h2><?php _e('Shorten URL', 'ignis-plugin'); ?></h2>
            <form class="ignis-shortener-form">
                <label for="url"><?php _e('URL to Shorten', 'ignis-plugin'); ?></label>
                <input type="url" name="url" id="url" required>
                <button type="submit"><?php _e('Shorten', 'ignis-plugin'); ?></button>
            </form>
            <div class="ignis-shortener-result" style="display: none;"></div>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize module
new Ignis_Shortener();
?>
