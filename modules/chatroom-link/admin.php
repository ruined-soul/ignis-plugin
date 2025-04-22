<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Chatroom Link Admin
class Ignis_Chatroom_Admin {
    public function __construct() {
        add_filter('ignis_admin_submenus', [$this, 'register_admin_submenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_admin_submenu($submenus) {
        $submenus[] = [
            'title' => __('Chatroom Settings', 'ignis-plugin'),
            'menu_title' => __('Chatroom', 'ignis-plugin'),
            'capability' => 'manage_options',
            'slug' => 'ignis-chatroom',
            'callback' => [$this, 'admin_page_callback']
        ];
        return $submenus;
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'ignis-chatroom') !== false) {
            wp_enqueue_style('ignis-chatroom-admin', IGNIS_PLUGIN_URL . 'modules/chatroom-link/assets/admin.css', [], IGNIS_PLUGIN_VERSION);
            wp_enqueue_script('ignis-chatroom-admin', IGNIS_PLUGIN_URL . 'modules/chatroom-link/assets/admin.js', ['jquery'], IGNIS_PLUGIN_VERSION, true);
        }
    }

    public function admin_page_callback() {
        $settings = get_option('ignis_chatroom_settings', ['endpoint' => '', 'auth_key' => '']);

        if (isset($_POST['ignis_chatroom_settings']) && check_admin_referer('ignis_chatroom_settings')) {
            $settings['endpoint'] = sanitize_text_field($_POST['ignis_chatroom']['endpoint']);
            $settings['auth_key'] = sanitize_text_field($_POST['ignis_chatroom']['auth_key']);
            update_option('ignis_chatroom_settings', $settings);
            echo '<div class="updated"><p>' . __('Settings saved.', 'ignis-plugin') . '</p></div>';
        }

        ?>
        <div class="wrap">
            <h1><?php _e('Chatroom Settings', 'ignis-plugin'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('ignis_chatroom_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="endpoint"><?php _e('API Endpoint', 'ignis-plugin'); ?></label></th>
                        <td>
                            <input type="url" name="ignis_chatroom[endpoint]" id="endpoint" value="<?php echo esc_attr($settings['endpoint']); ?>" class="regular-text" required>
                            <p class="description"><?php _e('Enter the chatroom service API endpoint (e.g., Discord invite API).', 'ignis-plugin'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="auth_key"><?php _e('Authentication Key', 'ignis-plugin'); ?></th>
                        <td>
                            <input type="text" name="ignis_chatroom[auth_key]" id="auth_key" value="<?php echo esc_attr($settings['auth_key']); ?>" class="regular-text" required>
                            <p class="description"><?php _e('Enter the authentication key for the chatroom service.', 'ignis-plugin'); ?></p>
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
new Ignis_Chatroom_Admin();
?>
