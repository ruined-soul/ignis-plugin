<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// REST API endpoints
class Ignis_API {
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_endpoints']);
    }

    public function register_endpoints() {
        // Shortener endpoint
        register_rest_route('ignis/v1', '/shortener', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'handle_shortener'],
            'permission_callback' => [$this, 'check_shortener_permissions'],
            'args' => [
                'url' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return filter_var($param, FILTER_VALIDATE_URL);
                    }
                ]
            ]
        ]);

        // Chatroom endpoint
        register_rest_route('ignis/v1', '/chatroom', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'handle_chatroom'],
            'permission_callback' => [$this, 'check_chatroom_permissions']
        ]);

        // User points endpoint
        register_rest_route('ignis/v1', '/user/points', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_user_points'],
            'permission_callback' => [$this, 'check_user_permissions']
        ]);
    }

    public function check_shortener_permissions(WP_REST_Request $request) {
        $options = get_option('ignis_general_settings', ['shortener_key' => '']);
        $key = $request->get_header('X-Ignis-Shortener-Key');
        return $key && $key === $options['shortener_key'];
    }

    public function handle_shortener(WP_REST_Request $request) {
        $url = $request['url'];
        // Placeholder for shortener service integration
        $shortened_url = $this->create_shortened_url($url);

        if ($shortened_url) {
            return new WP_REST_Response([
                'success' => true,
                'short_url' => $shortened_url
            ], 200);
        }

        return new WP_REST_Response([
            'success' => false,
            'message' => __('Failed to shorten URL', 'ignis-plugin')
        ], 400);
    }

    private function create_shortened_url($url) {
        // Implement shortener service (e.g., Bitly, TinyURL)
        // Requires external API integration
        return false; // Placeholder
    }

    public function check_chatroom_permissions(WP_REST_Request $request) {
        $options = get_option('ignis_general_settings', ['chatroom_key' => '']);
        $key = $request->get_header('X-Ignis-Chatroom-Key');
        return $key && $key === $options['chatroom_key'];
    }

    public function handle_chatroom(WP_REST_Request $request) {
        // Placeholder for chatroom service integration
        $chatroom_url = $this->get_chatroom_url();

        if ($chatroom_url) {
            return new WP_REST_Response([
                'success' => true,
                'chatroom_url' => $chatroom_url
            ], 200);
        }

        return new WP_REST_Response([
            'success' => false,
            'message' => __('Failed to retrieve chatroom URL', 'ignis-plugin')
        ], 400);
    }

    private function get_chatroom_url() {
        // Implement chatroom service (e.g., Discord, Telegram)
        // Requires external API integration
        return false; // Placeholder
    }

    public function check_user_permissions(WP_REST_Request $request) {
        return is_user_logged_in();
    }

    public function get_user_points(WP_REST_Request $request) {
        $user_id = get_current_user_id();
        $points = Ignis_Utilities::get_user_points($user_id);

        return new WP_REST_Response([
            'success' => true,
            'points' => $points
        ], 200);
    }
}

// Initialize API
new Ignis_API();
?>
