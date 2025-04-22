<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Airdrop Module
class Ignis_Airdrop {
    public function __construct() {
        // Check if module is enabled
        $general_settings = get_option('ignis_general_settings', ['enable_airdrop' => 1]);
        if (!$general_settings['enable_airdrop']) {
            return;
        }

        // Register hooks
        add_action('wp_ajax_ignis_claim_airdrop', [$this, 'claim_airdrop']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_shortcode('ignis_airdrop', [$this, 'airdrop_shortcode']);
    }

    public function claim_airdrop() {
        check_ajax_referer('ignis_airdrop', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in.', 'ignis-plugin')]);
        }

        $user_id = get_current_user_id();
        $airdrop_id = isset($_POST['airdrop_id']) ? absint($_POST['airdrop_id']) : 0;
        $short_url = isset($_POST['short_url']) ? sanitize_text_field($_POST['short_url']) : '';

        $airdrops = get_option('ignis_airdrop_campaigns', []);
        $airdrop = isset($airdrops[$airdrop_id]) ? $airdrops[$airdrop_id] : null;

        if (!$airdrop || !$airdrop['enabled'] || current_time('timestamp') > strtotime($airdrop['end_date'])) {
            wp_send_json_error(['message' => __('Airdrop not available.', 'ignis-plugin')]);
        }

        // Check if user already claimed
        global $wpdb;
        $claimed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ignis_logs WHERE user_id = %d AND action = %s AND meta_key = %s",
            $user_id, 'airdrop_claim', $airdrop_id
        ));

        if ($claimed > 0) {
            wp_send_json_error(['message' => __('You already claimed this airdrop.', 'ignis-plugin')]);
        }

        // Handle shortener requirement
        if ($airdrop['requires_shortener'] && !$short_url) {
            wp_send_json_error(['message' => __('Shortened URL required.', 'ignis-plugin')]);
        }

        if ($airdrop['requires_shortener']) {
            // Assume shortener validation via Ignis_Shortener
            $shortener = new Ignis_Shortener();
            $valid = apply_filters('ignis_shortener_response', false, $short_url);
            if (!$valid) {
                wp_send_json_error(['message' => __('Invalid shortened URL.', 'ignis-plugin')]);
            }
        }

        // Award reward
        if ($airdrop['reward_type'] === 'points') {
            Ignis_Utilities::update_user_points($user_id, Ignis_Utilities::get_user_points($user_id) + $airdrop['reward_amount']);
            Ignis_Utilities::log_action($user_id, 'points', $airdrop['reward_amount'], 'airdrop_claim', $airdrop_id);
            do_action('ignis_points_awarded', $user_id, $airdrop['reward_amount'], 'airdrop_claim');
        } else {
            Ignis_Utilities::update_user_currency($user_id, Ignis_Utilities::get_user_currency($user_id) + $airdrop['reward_amount']);
            Ignis_Utilities::log_action($user_id, 'currency', $airdrop['reward_amount'], 'airdrop_claim', $airdrop_id);
            do_action('ignis_currency_awarded', $user_id, $airdrop['reward_amount'], 'airdrop_claim');
        }

        set_transient('ignis_toast_' . $user_id, [
            'message' => sprintf(__('Claimed %d %s from %s!', 'ignis-plugin'), $airdrop['reward_amount'], $airdrop['reward_type'], esc_html($airdrop['name'])),
            'type' => 'airdrop'
        ], 30);

        wp_send_json_success(['message' => __('Airdrop claimed successfully.', 'ignis-plugin')]);
    }

    public function enqueue_assets() {
        wp_enqueue_style('ignis-airdrop', IGNIS_PLUGIN_URL . 'modules/airdrop/assets/airdrop.css', [], IGNIS_PLUGIN_VERSION);
        wp_enqueue_script('ignis-airdrop', IGNIS_PLUGIN_URL . 'modules/airdrop/assets/airdrop.js', ['jquery'], IGNIS_PLUGIN_VERSION, true);
        wp_localize_script('ignis-airdrop', 'ignis_airdrop', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ignis_airdrop')
        ]);
    }

    public function airdrop_shortcode($atts) {
        $atts = shortcode_atts(['id' => ''], $atts);
        $airdrop_id = absint($atts['id']);
        ob_start();
        include IGNIS_PLUGIN_DIR . 'modules/airdrop/templates/airdrop.php';
        return ob_get_clean();
    }
}

// Initialize module
new Ignis_Airdrop();
?>
