<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Store Module
class Ignis_Store {
    public function __construct() {
        // Check if module is enabled
        $general_settings = get_option('ignis_general_settings', ['enable_store' => 1]);
        if (!$general_settings['enable_store']) {
            return;
        }

        // Register hooks
        add_action('wp_ajax_ignis_purchase_item', [$this, 'purchase_item']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_shortcode('ignis_store', [$this, 'store_shortcode']);
    }

    public function purchase_item() {
        check_ajax_referer('ignis_store', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in.', 'ignis-plugin')]);
        }

        $user_id = get_current_user_id();
        $item_id = isset($_POST['item_id']) ? absint($_POST['item_id']) : 0;
        $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : 'points';

        if (!$item_id) {
            wp_send_json_error(['message' => __('Invalid item.', 'ignis-plugin')]);
        }

        $items = get_option('ignis_store_items', []);
        $item = isset($items[$item_id]) ? $items[$item_id] : null;

        if (!$item || !$item['enabled']) {
            wp_send_json_error(['message' => __('Item not available.', 'ignis-plugin')]);
        }

        $cost = $item['cost'];
        $type = $item['type'];

        if ($payment_method === 'points') {
            $points = Ignis_Utilities::get_user_points($user_id);
            if ($points < $cost) {
                wp_send_json_error(['message' => __('Insufficient points.', 'ignis-plugin')]);
            }
            Ignis_Utilities::update_user_points($user_id, $points - $cost);
            Ignis_Utilities::log_action($user_id, 'points', -$cost, 'store_purchase', $item_id);
            do_action('ignis_points_deducted', $user_id, $cost, 'store_purchase');
        } else {
            $currency = Ignis_Utilities::get_user_currency($user_id);
            if ($currency < $cost) {
                wp_send_json_error(['message' => __('Insufficient MangaCoin.', 'ignis-plugin')]);
            }
            Ignis_Utilities::update_user_currency($user_id, $currency - $cost);
            Ignis_Utilities::log_action($user_id, 'currency', -$cost, 'store_purchase', $item_id);
            do_action('ignis_currency_deducted', $user_id, $cost, 'store_purchase');
        }

        // Grant access based on item type
        if ($type === 'chapter') {
            update_user_meta($user_id, 'ignis_unlocked_chapter_' . $item['chapter_id'], true);
        } elseif ($type === 'subscription') {
            $expiration = strtotime('+' . $item['duration'] . ' days', current_time('timestamp'));
            update_user_meta($user_id, 'ignis_subscription', $expiration);
        }

        set_transient('ignis_toast_' . $user_id, [
            'message' => sprintf(__('Purchased: %s!', 'ignis-plugin'), esc_html($item['name'])),
            'type' => 'store'
        ], 30);

        wp_send_json_success(['message' => __('Purchase successful.', 'ignis-plugin')]);
    }

    public function enqueue_assets() {
        wp_enqueue_style('ignis-store', IGNIS_PLUGIN_URL . 'modules/store/assets/store.css', [], IGNIS_PLUGIN_VERSION);
        wp_enqueue_script('ignis-store', IGNIS_PLUGIN_URL . 'modules/store/assets/store.js', ['jquery'], IGNIS_PLUGIN_VERSION, true);
        wp_localize_script('ignis-store', 'ignis_store', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ignis_store')
        ]);
    }

    public function store_shortcode($atts) {
        $atts = shortcode_atts(['category' => ''], $atts);
        $category = sanitize_text_field($atts['category']);
        ob_start();
        include IGNIS_PLUGIN_DIR . 'modules/store/templates/store.php';
        return ob_get_clean();
    }
}

// Initialize module
new Ignis_Store();
?>
