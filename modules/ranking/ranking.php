<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Ranking Module
class Ignis_Ranking {
    public function __construct() {
        // Check if module is enabled
        $general_settings = get_option('ignis_general_settings', ['enable_ranking' => 1]);
        if (!$general_settings['enable_ranking']) {
            return;
        }

        // Register hooks
        add_action('wp_ajax_ignis_get_leaderboard', [$this, 'get_leaderboard']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_shortcode('ignis_leaderboard', [$this, 'leaderboard_shortcode']);
    }

    public function get_leaderboard() {
        check_ajax_referer('ignis_ranking', 'nonce');

        $options = get_option('ignis_ranking_settings', [
            'metric' => 'points',
            'limit' => 10,
            'cache_duration' => 3600
        ]);

        $cache_key = 'ignis_leaderboard_' . $options['metric'] . '_' . $options['limit'];
        $leaderboard = get_transient($cache_key);

        if (false === $leaderboard) {
            global $wpdb;
            $meta_key = $options['metric'] === 'points' ? 'ignis_points' : 'ignis_currency';
            $leaderboard = $wpdb->get_results($wpdb->prepare(
                "SELECT u.ID, u.user_login, um.meta_value AS score
                FROM {$wpdb->users} u
                LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = %s
                WHERE um.meta_value IS NOT NULL
                ORDER BY CAST(um.meta_value AS UNSIGNED) DESC
                LIMIT %d",
                $meta_key,
                $options['limit']
            ));

            set_transient($cache_key, $leaderboard, $options['cache_duration']);
        }

        wp_send_json_success(['leaderboard' => $leaderboard]);
    }

    public function enqueue_assets() {
        wp_enqueue_style('ignis-ranking', IGNIS_PLUGIN_URL . 'modules/ranking/assets/ranking.css', [], IGNIS_PLUGIN_VERSION);
        wp_enqueue_script('ignis-ranking', IGNIS_PLUGIN_URL . 'modules/ranking/assets/ranking.js', ['jquery'], IGNIS_PLUGIN_VERSION, true);
        wp_localize_script('ignis-ranking', 'ignis_ranking', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ignis_ranking')
        ]);
    }

    public function leaderboard_shortcode($atts) {
        $atts = shortcode_atts(['metric' => ''], $atts);
        $metric = sanitize_text_field($atts['metric']);
        ob_start();
        include IGNIS_PLUGIN_DIR . 'modules/ranking/templates/leaderboard.php';
        return ob_get_clean();
    }
}

// Initialize module
new Ignis_Ranking();
?>
