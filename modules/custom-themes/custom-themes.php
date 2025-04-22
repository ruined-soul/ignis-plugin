<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Custom Themes Module
class Ignis_Custom_Themes {
    public function __construct() {
        // Check if module is enabled
        $general_settings = get_option('ignis_general_settings', ['enable_custom_themes' => 1]);
        if (!$general_settings['enable_custom_themes']) {
            return;
        }

        // Register hooks
        add_action('wp_ajax_ignis_apply_theme', [$this, 'apply_theme']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_head', [$this, 'output_user_theme']);
        add_shortcode('ignis_profile_customization', [$this, 'profile_shortcode']);
    }

    public function apply_theme() {
        check_ajax_referer('ignis_theme', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in.', 'ignis-plugin')]);
        }

        $user_id = get_current_user_id();
        $theme_id = isset($_POST['theme_id']) ? absint($_POST['theme_id']) : 0;
        $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : 'points';

        $themes = get_option('ignis_custom_themes', []);
        $theme = isset($themes[$theme_id]) ? $themes[$theme_id] : null;

        if (!$theme) {
            wp_send_json_error(['message' => __('Invalid theme.', 'ignis-plugin')]);
        }

        // Check if user already owns the theme
        $owned_themes = get_user_meta($user_id, 'ignis_owned_themes', true);
        $owned_themes = is_array($owned_themes) ? $owned_themes : [];

        if (!in_array($theme_id, $owned_themes)) {
            // Check cost and balance
            $cost = absint($theme['cost']);
            if ($payment_method === 'points') {
                $points = Ignis_Utilities::get_user_points($user_id);
                if ($points < $cost) {
                    wp_send_json_error(['message' => __('Insufficient points.', 'ignis-plugin')]);
                }
                Ignis_Utilities::update_user_points($user_id, $points - $cost);
                Ignis_Utilities::log_action($user_id, 'points', -$cost, 'theme_purchase', $theme_id);
                do_action('ignis_points_deducted', $user_id, $cost, 'theme_purchase');
            } else {
                $currency = Ignis_Utilities::get_user_currency($user_id);
                if ($currency < $cost) {
                    wp_send_json_error(['message' => __('Insufficient MangaCoin.', 'ignis-plugin')]);
                }
                Ignis_Utilities::update_user_currency($user_id, $currency - $cost);
                Ignis_Utilities::log_action($user_id, 'currency', -$cost, 'theme_purchase', $theme_id);
                do_action('ignis_currency_deducted', $user_id, $cost, 'theme_purchase');
            }
            $owned_themes[] = $theme_id;
            update_user_meta($user_id, 'ignis_owned_themes', $owned_themes);
        }

        // Apply theme
        update_user_meta($user_id, 'ignis_active_theme', $theme_id);

        set_transient('ignis_toast_' . $user_id, [
            'message' => sprintf(__('Theme %s applied!', 'ignis-plugin'), esc_html($theme['name'])),
            'type' => 'theme'
        ], 30);

        wp_send_json_success(['message' => __('Theme applied successfully.', 'ignis-plugin')]);
    }

    public function enqueue_assets() {
        wp_enqueue_style('ignis-themes', IGNIS_PLUGIN_URL . 'modules/custom-themes/assets/themes.css', [], IGNIS_PLUGIN_VERSION);
        wp_enqueue_script('ignis-themes', IGNIS_PLUGIN_URL . 'modules/custom-themes/assets/themes.js', ['jquery'], IGNIS_PLUGIN_VERSION, true);
        wp_localize_script('ignis-themes', 'ignis_themes', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ignis_theme')
        ]);
    }

    public function output_user_theme() {
        if (!is_user_logged_in()) {
            return;
        }

        $user_id = get_current_user_id();
        $theme_id = get_user_meta($user_id, 'ignis_active_theme', true);
        $themes = get_option('ignis_custom_themes', []);

        if ($theme_id && isset($themes[$theme_id])) {
            $theme = $themes[$theme_id];
            echo '<style id="ignis-user-theme">' . esc_html($theme['css']) . '</style>';
        }
    }

    public function profile_shortcode($atts) {
        ob_start();
        include IGNIS_PLUGIN_DIR . 'modules/custom-themes/templates/profile.php';
        return ob_get_clean();
    }
}

// Initialize module
new Ignis_Custom_Themes();
?>
