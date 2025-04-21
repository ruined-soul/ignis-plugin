<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Database management
class Ignis_Database {
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Logs table
        $log_table = $wpdb->prefix . 'ignis_logs';
        $sql = "CREATE TABLE $log_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            type ENUM('points', 'currency', 'store', 'airdrop', 'request', 'referral', 'bug', 'username') NOT NULL,
            amount INT NOT NULL,
            action VARCHAR(100) NOT NULL,
            timestamp DATETIME NOT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY type (type)
        ) $charset_collate;";

        // Referrals table
        $referral_table = $wpdb->prefix . 'ignis_referrals';
        $sql .= "CREATE TABLE $referral_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            referral_code VARCHAR(50) NOT NULL,
            referred_user_id BIGINT(20) UNSIGNED DEFAULT NULL,
            status ENUM('pending', 'completed') DEFAULT 'pending',
            timestamp DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY referral_code (referral_code),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Bug reports table
        $bug_table = $wpdb->prefix . 'ignis_bug_reports';
        $sql .= "CREATE TABLE $bug_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            screenshot VARCHAR(255) DEFAULT NULL,
            status ENUM('pending', 'approved', 'rejected', 'duplicate') DEFAULT 'pending',
            timestamp DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
?>
