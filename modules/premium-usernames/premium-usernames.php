<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Premium Usernames Module
class Ignis_Premium_Usernames {
    public function __construct() {
        // Check if module is enabled
        $general_settings = get_option('ignis_general_settings', ['enable_premium_usernames' => 1]);
        if (!$general_settings['enable_premium_usernames']) {
            return;
        }

        // Register hooks
        add_action('wp_ajax_ignis_change_username', [$this, 'change_username']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_shortcode('ignis_username_form', [$this, 'username_form_shortcode']);
    }

    public function change_username() {
        check_ajax_referer('ignis_username', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in.', 'ignis-plugin')]);
        }

        $user_id = get_current_user_id();
        $new_username = isset($_POST['username']) ? sanitize_user($_POST['username'], true) : '';
        $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : 'points';

        if (empty($new_username)) {
            wp_send_json_error(['message' => __('Username is required.', 'ignis-plugin')]);
        }

        // Validate username
        if (!validate_username($new_username)) {
            wp_send_json_error(['message' => __('Invalid username.', 'ignis-plugin')]);
        }

        if (username_exists($new_username)) {
            wp_send_json_error(['message' => __('Username is already taken.', 'ignis-plugin')]);
        }

        // Check blocked usernames
        $blocked_usernames = get_option('ignis_blocked_usernames', []);
        if (in_array(strtolower($new_username), array_map('strtolower', $blocked_usernames))) {
            wp_send_json_error(['message' => __('This username is not allowed.', 'ignis-plugin')]);
        }

        // Check cost and balance
        $settings = get_option('ignis_username_settings', ['cost' => 100]);
        $cost = absint($settings['cost']);

        if ($payment_method === 'points') {
            $points = Ignis_Utilities::get_user_points($user_id);
            if ($points < $cost) {
                wp_send_json_error(['message' => __('Insufficient points.', 'ignis-plugin')]);
            }
            Ignis_Utilities::update_user_points($user_id, $points - $cost);
            Ignis_Utilities::log_action($user_id, 'points', -$cost, 'username_change', $new_username);
            do_action('ignis_points_deducted', $user_id, $cost, 'username_change');
        } else {
            $currency = Ignis_Utilities::get_user_currency($user_id);
            if ($currency < $cost) {
                wp_send_json_error(['message' => __('Insufficient MangaCoin.', 'ignis-plugin')]);
            }
            Ignis_Utilities::update_user_currency($user_id, $currency - $cost);
            Ignis_Utilities::log_action($user_id, 'currency', -$cost, 'username_change', $new_username);
            do_action('ignis_currency_deducted', $user_id, $cost, 'username_change');
        }

        // Update username
        $user = get_user_by('ID', $user_id);
        $result = wp_update_user([
            'ID' => $user_id,
            'user_login' => $new_username,
            'user_nicename' => sanitize_title($new_username)
        ]);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => __('Failed to update username.', 'ignis-plugin')]);
        }

        set_transient('ignis_toast_' . $user_id, [
            'message' => sprintf(__('Username changed to %s!', 'ignis-plugin'), esc_html($new_username)),
            'type' => 'username'
        ], 30);

        wp_send_json_success(['message' => __('Username changed successfully.', 'ignis-plugin')]);
    }

    public function enqueue_assets() {
        wp_enqueue_style('ignis-usernames', IGNIS_PLUGIN_URL . 'modules/premium-usernames/assets/usernames.css', [], IGNIS_PLUGIN_VERSION);
        wp_enqueue_script('ignis-usernames', IGNIS_PLUGIN_URL . 'modules/premium-usernames/assets/usernames.js', ['jquery'], IGNIS_PLUGIN_VERSION, true);
        wp_localize_script('ignis-usernames', 'ignis_usernames', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ignis_username')
        ]);
    }

    public function username_form_shortcode($atts) {
        ob_start();
        include IGNIS_PLUGIN_DIR . 'modules/premium-usernames/templates/username-form.php';
        return ob_get_clean();
    }
}

// Initialize module
new Ignis_Premium_Usernames();
?>
