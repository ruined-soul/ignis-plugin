<?php
/*
Plugin Name: Ignis Plugin
Plugin URI: https://t.me/IgnisReborn
Description: Modular plugin for Madara theme with points, currency, store, airdrops, and user engagement features.
Version: 1.0.0
Author: Ignis
Author URI: https://t.me/IgnisReborn
License: GPL-2.0+
Text Domain: ignis-plugin
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('IGNIS_PLUGIN_VERSION', '1.0.0');
define('IGNIS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IGNIS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('IGNIS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Core plugin class
class Ignis_Plugin {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', [$this, 'init']);
        add_action('admin_menu', [$this, 'register_admin_menu']);
    }

    public function init() {
        // Check for WP Manga dependency
        if (!class_exists('WP_MANGA')) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>Ignis Plugin requires WP Manga (Madara-Core) plugin.</p></div>';
            });
            return;
        }

        // Load text domain
        load_plugin_textdomain('ignis-plugin', false, dirname(IGNIS_PLUGIN_BASENAME) . '/languages/');

        // Load core files
        $core_files = [
            'database.php',
            'hooks.php',
            'utilities.php',
            'api.php'
        ];
        foreach ($core_files as $file) {
            $path = IGNIS_PLUGIN_DIR . 'core/' . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }

        // Load modules
        $this->load_modules();
    }

    private function load_modules() {
        $modules_dir = IGNIS_PLUGIN_DIR . 'modules/';
        if (!is_dir($modules_dir)) {
            return;
        }

        $modules = glob($modules_dir . '*/', GLOB_ONLYDIR);
        foreach ($modules as $module_dir) {
            $main_file = $module_dir . basename($module_dir) . '.php';
            $admin_file = $module_dir . 'admin.php';
            if (file_exists($main_file)) {
                require_once $main_file;
            }
            if (file_exists($admin_file)) {
                require_once $admin_file;
            }
        }
    }

    public function register_admin_menu() {
        add_menu_page(
            __('Ignis Control Center', 'ignis-plugin'),
            __('Ignis Control', 'ignis-plugin'),
            'manage_options',
            'ignis-control-center',
            null,
            'dashicons-admin-tools',
            30
        );

        // Allow modules to register submenus
        $submenus = apply_filters('ignis_admin_submenus', []);
        foreach ($submenus as $submenu) {
            add_submenu_page(
                'ignis-control-center',
                $submenu['title'],
                $submenu['menu_title'],
                $submenu['capability'],
                $submenu['slug'],
                $submenu['callback']
            );
        }
    }
}

// Activation hook
function ignis_plugin_activate() {
    require_once IGNIS_PLUGIN_DIR . 'core/database.php';
    Ignis_Database::create_tables();
    update_option('ignis_plugin_version', IGNIS_PLUGIN_VERSION);
}
register_activation_hook(__FILE__, 'ignis_plugin_activate');

// Deactivation hook
function ignis_plugin_deactivate() {
    // Optional cleanup
}
register_deactivation_hook(__FILE__, 'ignis_plugin_deactivate');

// Initialize plugin
Ignis_Plugin::get_instance();
?>
