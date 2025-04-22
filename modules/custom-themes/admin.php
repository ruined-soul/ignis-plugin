<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Custom Themes Admin
class Ignis_Custom_Themes_Admin {
    public function __construct() {
        add_filter('ignis_admin_submenus', [$this, 'register_admin_submenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_admin_submenu($submenus) {
        $submenus[] = [
            'title' => __('Custom Themes', 'ignis-plugin'),
            'menu_title' => __('Themes', 'ignis-plugin'),
            'capability' => 'manage_options',
            'slug' => 'ignis-custom-themes',
            'callback' => [$this, 'admin_page_callback']
        ];
        return $submenus;
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'ignis-custom-themes') !== false) {
            wp_enqueue_style('ignis-themes-admin', IGNIS_PLUGIN_URL . 'modules/custom-themes/assets/admin.css', [], IGNIS_PLUGIN_VERSION);
            wp_enqueue_script('ignis-themes-admin', IGNIS_PLUGIN_URL . 'modules/custom-themes/assets/admin.js', ['jquery'], IGNIS_PLUGIN_VERSION, true);
        }
    }

    public function admin_page_callback() {
        $themes = get_option('ignis_custom_themes', []);

        if (isset($_POST['ignis_custom_themes']) && check_admin_referer('ignis_custom_themes')) {
            $new_themes = [];
            foreach ($_POST['ignis_themes'] as $index => $theme) {
                $new_themes[$index] = [
                    'name' => sanitize_text_field($theme['name']),
                    'css' => wp_kses_post($theme['css']),
                    'cost' => absint($theme['cost']),
                    'enabled' => isset($theme['enabled']) ? 1 : 0
                ];
            }
            update_option('ignis_custom_themes', $new_themes);
            echo '<div class="updated"><p>' . __('Themes saved.', 'ignis-plugin') . '</p></div>';
        }

        ?>
        <div class="wrap">
            <h1><?php _e('Custom Themes', 'ignis-plugin'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('ignis_custom_themes'); ?>
                <table class="form-table ignis-themes-table">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'ignis-plugin'); ?></th>
                            <th><?php _e('CSS', 'ignis-plugin'); ?></th>
                            <th><?php _e('Cost', 'ignis-plugin'); ?></th>
                            <th><?php _e('Enabled', 'ignis-plugin'); ?></th>
                            <th><?php _e('Actions', 'ignis-plugin'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($themes as $index => $theme) : ?>
                            <tr>
                                <td><input type="text" name="ignis_themes[<?php echo $index; ?>][name]" value="<?php echo esc_attr($theme['name']); ?>" required></td>
                                <td><textarea name="ignis_themes[<?php echo $index; ?>][css]" rows="5" class="large-text"><?php echo esc_textarea($theme['css']); ?></textarea></td>
                                <td><input type="number" name="ignis_themes[<?php echo $index; ?>][cost]" value="<?php echo esc_attr($theme['cost']); ?>" min="0" required></td>
                                <td><input type="checkbox" name="ignis_themes[<?php echo $index; ?>][enabled]" <?php checked($theme['enabled'], 1); ?>></td>
                                <td><button type="button" class="button ignis-remove-theme"><?php _e('Remove', 'ignis-plugin'); ?></button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="button" class="button ignis-add-theme"><?php _e('Add Theme', 'ignis-plugin'); ?></button>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

// Initialize module
new Ignis_Custom_Themes_Admin();
?>
