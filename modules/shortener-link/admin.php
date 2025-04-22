<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Shortener Link Admin
class Ignis_Shortener_Admin {
    public function __construct() {
        add_filter('ignis_admin_submenus', [$this, 'register_admin_submenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_admin_submenu($submenus) {
        $submenus[] = [
            'title' => __('URL Shortener', 'ignis-plugin'),
            'menu_title' => __('Shortener', 'ignis-plugin'),
            'capability' => 'manage_options',
            'slug' => 'ignis-shortener',
            'callback' => [$this, 'admin_page_callback']
        ];
        return $submenus;
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'ignis-shortener') !== false) {
            wp_enqueue_style('ignis-shortener-admin', IGNIS_PLUGIN_URL . 'modules/shortener-link/assets/admin.css', [], IGNIS_PLUGIN_VERSION);
            wp_enqueue_script('ignis-shortener-admin', IGNIS_PLUGIN_URL . 'modules/shortener-link/assets/admin.js', ['jquery'], IGNIS_PLUGIN_VERSION, true);
        }
    }

    public function admin_page_callback() {
        $settings = get_option('ignis_shortener_settings', ['endpoint' => '', 'api_key' => '']);

        if (isset($_POST['ignis_shortener_settings']) && check_admin_referer('ignis_shortener_settings')) {
            $settings['endpoint'] = sanitize_text_field($_POST['ignis_shortener']['endpoint']);
            $settings['api_key'] = sanitize_text_field($_POST['ignis_shortener']['api_key']);
            update_option('ignis_shortener_settings', $settings);
            echo '<div class="updated"><p>' . __('Settings saved.', 'ignis-plugin') . '</p></div>';
        }

        ?>
        <div class="wrap">
            <h1><?php _e('URL Shortener Settings', 'ignis-plugin'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('ignis_shortener_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="endpoint"><?php _e('API Endpoint', 'ignis-plugin'); ?></label></th>
                        <td>
                            <input type="url" name="ignis_shortener[endpoint]" id="endpoint" value="<?php echo esc_attr($settings['endpoint']); ?>" class="regular-text" required>
                            <p class="description"><?php _e('Enter the URL shortening service API endpoint (e.g., https://api.bitly.com/v4/shorten).', 'ignis-plugin'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="api_key"><?php _e('API Key', 'ignis-plugin'); ?></label></th>
                        <td>
                            <input type="text" name="ignis_shortener[api_key]" id="api_key" value="<?php echo esc_attr($settings['api_key']); ?>" class="regular-text" required>
                            <p class="description"><?php _e('Enter the API key for the URL shortening service.', 'ignis-plugin'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

// Initialize module
new Ignis_Shortener_Admin();
?>
