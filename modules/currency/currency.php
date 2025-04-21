<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Currency Module
class Ignis_Currency {
    public function __construct() {
        // Check if module is enabled
        $general_settings = get_option('ignis_general_settings', ['enable_currency' => 1]);
        if (!$general_settings['enable_currency']) {
            return;
        }

        // Register hooks
        add_action('wp_ajax_ignis_convert_points', [$this, 'convert_points']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('ignis_register_hooks', [$this, 'register_ajax_actions']);
    }

    public function register_ajax_actions() {
        add_action('wp_ajax_ignis_get_currency', [$this, 'get_currency_balance']);
    }

    public function award_currency($user_id, $amount, $action, $meta_key = null) {
        if ($amount <= 0) {
            return false;
        }

        $current_balance = Ignis_Utilities::get_user_currency($user_id);
        $new_balance = $current_balance + $amount;
        Ignis_Utilities::update_user_currency($user_id, $new_balance);
        Ignis_Utilities::log_action($user_id, 'currency', $amount, $action, $meta_key);

        do_action('ignis_currency_awarded', $user_id, $amount, $action);
        return true;
    }

    public function deduct_currency($user_id, $amount, $action, $meta_key = null) {
        if ($amount <= 0) {
            return false;
        }

        $current_balance = Ignis_Utilities::get_user_currency($user_id);
        if ($current_balance < $amount) {
            return false;
        }

        $new_balance = $current_balance - $amount;
        Ignis_Utilities::update_user_currency($user_id, $new_balance);
        Ignis_Utilities::log_action($user_id, 'currency', -$amount, $action, $meta_key);

        do_action('ignis_currency_deducted', $user_id, $amount, $action);
        return true;
    }

    public function convert_points() {
        check_ajax_referer('ignis_currency', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in.', 'ignis-plugin')]);
        }

        $user_id = get_current_user_id();
        $points_to_convert = isset($_POST['points']) ? absint($_POST['points']) : 0;

        if ($points_to_convert <= 0) {
            wp_send_json_error(['message' => __('Invalid points amount.', 'ignis-plugin')]);
        }

        $options = get_option('ignis_currency_settings', ['points_to_currency_ratio' => 100]);
        $current_points = Ignis_Utilities::get_user_points($user_id);

        if ($current_points < $points_to_convert) {
            wp_send_json_error(['message' => __('Insufficient points.', 'ignis-plugin')]);
        }

        $currency_amount = floor($points_to_convert / $options['points_to_currency_ratio']);
        if ($currency_amount <= 0) {
            wp_send_json_error(['message' => __('Points too low for conversion.', 'ignis-plugin')]);
        }

        // Deduct points
        Ignis_Utilities::update_user_points($user_id, $current_points - $points_to_convert);
        Ignis_Utilities::log_action($user_id, 'points', -$points_to_convert, 'currency_conversion');

        // Award currency
        $this->award_currency($user_id, $currency_amount, 'points_conversion');

        set_transient('ignis_toast_' . $user_id, [
            'message' => sprintf(__('%d MangaCoin added from points conversion!', 'ignis-plugin'), $currency_amount),
            'type' => 'currency'
        ], 30);

        wp_send_json_success(['message' => __('Conversion successful.', 'ignis-plugin'), 'currency' => Ignis_Utilities::get_user_currency($user_id)]);
    }

    public function get_currency_balance() {
        check_ajax_referer('ignis_currency', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in.', 'ignis-plugin')]);
        }

        $user_id = get_current_user_id();
        $balance = Ignis_Utilities::get_user_currency($user_id);
        wp_send_json_success(['currency' => $balance]);
    }

    public function enqueue_assets() {
        wp_enqueue_style('ignis-currency', IGNIS_PLUGIN_URL . 'modules/currency/assets/currency.css', [], IGNIS_PLUGIN_VERSION);
        wp_enqueue_script('ignis-currency', IGNIS_PLUGIN_URL . 'modules/currency/assets/currency.js', ['jquery'], IGNIS_PLUGIN_VERSION, true);
        wp_localize_script('ignis-currency', 'ignis_currency', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ignis_currency')
        ]);
    }
}

// Initialize module
new Ignis_Currency();

// Extend Ignis_Utilities for currency
class Ignis_Utilities {
    public static function get_user_currency($user_id) {
        return (int) get_user_meta($user_id, 'ignis_currency', true);
    }

    public static function update_user_currency($user_id, $currency) {
        return update_user_meta($user_id, 'ignis_currency', max(0, (int) $currency));
    }
}
?>
