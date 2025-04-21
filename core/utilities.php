<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Utility functions
class Ignis_Utilities {
    /**
     * Log an action to the ignis_logs table
     */
    public static function log_action($user_id, $type, $amount, $action, $meta_key = null, $ip_address = null) {
        global $wpdb;
        $log_table = $wpdb->prefix . 'ignis_logs';

        $data = [
            'user_id' => (int) $user_id,
            'type' => sanitize_text_field($type),
            'amount' => (int) $amount,
            'action' => sanitize_text_field($action),
            'timestamp' => current_time('mysql'),
            'ip_address' => $ip_address ? sanitize_text_field($ip_address) : ($_SERVER['REMOTE_ADDR'] ?? null)
        ];

        if ($meta_key) {
            $data['meta_key'] = sanitize_text_field($meta_key);
        }

        return $wpdb->insert($log_table, $data);
    }

    /**
     * Get user points balance
     */
    public static function get_user_points($user_id) {
        return (int) get_user_meta($user_id, 'ignis_points', true);
    }

    /**
     * Update user points balance
     */
    public static function update_user_points($user_id, $points) {
        return update_user_meta($user_id, 'ignis_points', max(0, (int) $points));
    }

    /**
     * Cache data with transient
     */
    public static function cache_data($key, $data, $expiration = HOUR_IN_SECONDS) {
        return set_transient('ignis_' . $key, $data, $expiration);
    }

    /**
     * Retrieve cached data
     */
    public static function get_cached_data($key) {
        return get_transient('ignis_' . $key);
    }

    /**
     * Sanitize input array
     */
    public static function sanitize_array($input) {
        if (!is_array($input)) {
            return sanitize_text_field($input);
        }

        return array_map([self::class, 'sanitize_array'], $input);
    }

    /**
     * Generate unique referral code
     */
    public static function generate_referral_code($user_id) {
        $code = substr(md5($user_id . wp_rand()), 0, 8);
        global $wpdb;
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ignis_referrals WHERE referral_code = %s",
            $code
        ));

        if ($exists) {
            return self::generate_referral_code($user_id);
        }

        return $code;
    }

    /**
     * Check if action is within daily limit
     */
    public static function check_daily_limit($user_id, $action, $limit = 1) {
        global $wpdb;
        $today = date('Y-m-d');
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ignis_logs WHERE user_id = %d AND action = %s AND DATE(timestamp) = %s",
            $user_id,
            $action,
            $today
        ));

        return $count < $limit;
    }
}
?>
