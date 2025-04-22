<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Documentation Admin
class Ignis_Documentation_Admin {
    public function __construct() {
        add_filter('ignis_admin_submenus', [$this, 'register_admin_submenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_admin_submenu($submenus) {
        $submenus[] = [
            'title' => __('Documentation Settings', 'ignis-plugin'),
            'menu_title' => __('Doc Settings', 'ignis-plugin'),
            'capability' => 'manage_options',
            'slug' => 'ignis-documentation-settings',
            'callback' => [$this, 'admin_page_callback']
        ];
        return $submenus;
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'ignis-documentation-settings') !== false) {
            wp_enqueue_style('ignis-documentation', IGNIS_PLUGIN_URL . 'modules/documentation/assets/documentation.css', [], IGNIS_PLUGIN_VERSION);
            wp_enqueue_script('ignis-documentation', IGNIS_PLUGIN_URL . 'modules/documentation/assets/documentation.js', ['jquery'], IGNIS_PLUGIN_VERSION, true);
        }
    }

    public function admin_page_callback() {
        $general_settings = get_option('ignis_general_settings', ['enable_documentation' => 1]);

        if (isset($_POST['ignis_documentation_settings']) && check_admin_referer('ignis_documentation_settings')) {
            $general_settings['enable_documentation'] = isset($_POST['ignis_documentation']['enable_documentation']) ? 1 : 0;
            update_option('ignis_general_settings', $general_settings);
            echo '<div class="updated"><p>' . __('Settings saved.', 'ignis-plugin') . '</p></div>';
        }

        ?>
        <div class="wrap">
            <h1><?php _e('Documentation Settings', 'ignis-plugin'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('ignis_documentation_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="enable_documentation"><?php _e('Enable Documentation', 'ignis-plugin'); ?></label></th>
                        <td>
                            <input type="checkbox" name="ignis_documentation[enable_documentation]" id="enable_documentation" <?php checked($general_settings['enable_documentation'], 1); ?>>
                            <p class="description"><?php _e('Enable or disable the documentation submenu in the admin dashboard.', 'ignis-plugin'); ?></p>
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
new Ignis_Documentation_Admin();
?>
