<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Centralized hook management
class Ignis_Hooks {
    public function __construct() {
        // Core WordPress hooks
        add_action('init', [$this, 'register_shortcodes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_global_assets']);
        add_action('admin_init', [$this, 'register_admin_settings']);
        
        // Madara-specific hooks
        if (class_exists('WP_MANGA')) {
            // Example Madara hooks for modules
            add_action('wp_manga_after_chapter_read', [$this, 'trigger_chapter_read'], 10, 2);
            add_action('wp_manga_user_bookmark', [$this, 'trigger_bookmark'], 10, 3);
        }

        // Allow modules to register custom hooks
        do_action('ignis_register_hooks');
    }

    public function register_shortcodes() {
        // Placeholder for global shortcodes
        // Modules can register their own via filters
        $shortcodes = apply_filters('ignis_shortcodes', []);
        foreach ($shortcodes as $tag => $callback) {
            add_shortcode($tag, $callback);
        }
    }

    public function enqueue_global_assets() {
        // Enqueue global styles/scripts if needed
        wp_enqueue_style('ignis-global', IGNIS_PLUGIN_URL . 'assets/global.css', [], IGNIS_PLUGIN_VERSION);
        wp_enqueue_script('ignis-global', IGNIS_PLUGIN_URL . 'assets/global.js', ['jquery'], IGNIS_PLUGIN_VERSION, true);
        wp_localize_script('ignis-global', 'ignis_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ignis_global')
        ]);
    }

    public function register_admin_settings() {
        // Register settings for general plugin options
        register_setting('ignis_general_settings', 'ignis_general_settings', [
            'sanitize_callback' => [$this, 'sanitize_general_settings']
        ]);
    }

    public function sanitize_general_settings($input) {
        $sanitized = [];
        $defaults = [
            'enable_points' => 1,
            'enable_currency' => 1,
            'enable_store' => 1,
            'enable_airdrop' => 1,
            'enable_ranking' => 1,
            'enable_requests' => 1,
            'enable_usernames' => 1,
            'enable_themes' => 1,
            'enable_bug_bounty' => 1,
            'enable_shortener' => 1,
            'enable_chatroom' => 1,
            'enable_backup' => 1,
            'shortener_key' => '',
            'chatroom_key' => ''
        ];

        foreach ($defaults as $key => $default) {
            if (isset($input[$key])) {
                if (in_array($key, ['shortener_key', 'chatroom_key'])) {
                    $sanitized[$key] = sanitize_text_field($input[$key]);
                } else {
                    $sanitized[$key] = (int) $input[$key];
                }
            } else {
                $sanitized[$key] = $default;
            }
        }

        return $sanitized;
    }

    public function trigger_chapter_read($chapter, $manga_id) {
        // Trigger action for modules (e.g., Points, Ranking)
        do_action('ignis_chapter_read', $chapter, $manga_id);
    }

    public function trigger_bookmark($user_id, $manga_id, $action) {
        // Trigger action for modules (e.g., Points, User Engagement)
        do_action('ignis_bookmark', $user_id, $manga_id, $action);
    }
}

// Initialize hooks
new Ignis_Hooks();
?>
