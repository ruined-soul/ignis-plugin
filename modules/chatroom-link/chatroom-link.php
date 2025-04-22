<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Chatroom Link Module
class Ignis_Chatroom_Link {
    public function __construct() {
        // Check if module is enabled
        $general_settings = get_option('ignis_general_settings', ['enable_chatroom' => 1]);
        if (!$general_settings['enable_chatroom']) {
            return;
        }

        // Register hooks
        add_action('wp_ajax_ignis_join_chatroom', [$this, 'join_chatroom']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_shortcode('ignis_chatroom_link', [$this, 'chatroom_shortcode']);
    }

    public function join_chatroom() {
        check_ajax_referer('ignis_chatroom', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in.', 'ignis-plugin')]);
        }

        $user_id = get_current_user_id();
        $settings = get_option('ignis_chatroom_settings', ['endpoint' => '', 'auth_key' => '']);
        $endpoint = $settings['endpoint'];
        $auth_key = $settings['auth_key'];

        if (empty($endpoint) || empty($auth_key)) {
            wp_send_json_error(['message' => __('Chatroom service not configured.', 'ignis-plugin')]);
        }

        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $auth_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode(['user_id' => $user_id, 'wp_user' => wp_get_current_user()->user_login]),
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => __('Failed to connect to chatroom.', 'ignis-plugin')]);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data['invite_link'])) {
            wp_send_json_error(['message' => __('Failed to retrieve chatroom invite.', 'ignis-plugin')]);
        }

        Ignis_Utilities::log_action($user_id, 'chatroom', 0, 'join_chatroom', $data['invite_link']);

        set_transient('ignis_toast_' . $user_id, [
            'message' => __('Chatroom invite generated!', 'ignis-plugin'),
            'type' => 'chatroom'
        ], 30);

        wp_send_json_success([
            'message' => __('Invite generated successfully.', 'ignis-plugin'),
            'invite_link' => $data['invite_link']
        ]);
    }

    public function enqueue_assets() {
        wp_enqueue_style('ignis-chatroom', IGNIS_PLUGIN_URL . 'modules/chatroom-link/assets/chatroom.css', [], IGNIS_PLUGIN_VERSION);
        wp_enqueue_script('ignis-chatroom', IGNIS_PLUGIN_URL . 'modules/chatroom-link/assets/chatroom.js', ['jquery'], IGNIS_PLUGIN_VERSION, true);
        wp_localize_script('ignis-chatroom', 'ignis_chatroom', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ignis_chatroom')
        ]);
    }

    public function chatroom_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('You must be logged in to join the chatroom.', 'ignis-plugin') . '</p>';
        }

        ob_start();
        ?>
        <div class="ignis-chatroom">
            <h2><?php _e('Join Chatroom', 'ignis-plugin'); ?></h2>
            <button class="ignis-chatroom-join"><?php _e('Get Invite Link', 'ignis-plugin'); ?></button>
            <div class="ignis-chatroom-result" style="display: none;"></div>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize module
new Ignis_Chatroom_Link();
?>
