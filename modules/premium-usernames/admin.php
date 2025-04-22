<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Premium Usernames Admin
class Ignis_Premium_Usernames_Admin {
    public function __construct() {
        add_filter('ignis_admin_submenus', [$this, 'register_admin_submenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_admin_submenu($submenus) {
        $submenus[] = [
            'title' => __('Premium Usernames', 'ignis-plugin'),
            'menu_title' => __('Usernames', 'ignis-plugin'),
            'capability' => 'manage_options',
            'slug' => 'ignis-premium-usernames',
            'callback' => [$this, 'admin_page_callback']
        ];
        return $submenus;
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'ignis-premium-usernames') !== false) {
            wp_enqueue_style('ignis-usernames-admin', IGNIS_PLUGIN_URL . 'modules/premium-usernames/assets/admin.css', [], IGNIS_PLUGIN_VERSION);
            wp_enqueue_script('ignis-usernames-admin', IGNIS_PLUGIN_URL . 'modules/premium-usernames/assets/admin.js', ['jquery'], IGNIS_PLUGIN_VERSION, true);
        }
    }

    public function admin_page_callback() {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';
        ?>
        <div class="wrap">
            <h1><?php _e('Premium Usernames', 'ignis-plugin'); ?></h1>
            <nav class="nav-tab-wrapper">
                <a href="?page=ignis-premium-usernames&tab=settings" class="nav-tab <?php echo $tab === 'settings' ? 'nav-tab-active' : ''; ?>"><?php _e('Settings', 'ignis-plugin'); ?></a>
                <a href="?page=ignis-premium-usernames&tab=blocked" class="nav-tab <?php echo $tab === 'blocked' ? 'nav-tab-active' : ''; ?>"><?php _e('Blocked Usernames', 'ignis-plugin'); ?></a>
                <a href="?page=ignis-premium-usernames&tab=assign" class="nav-tab <?php echo $tab === 'assign' ? 'nav-tab-active' : ''; ?>"><?php _e('Assign Username', 'ignis-plugin'); ?></a>
            </nav>
            <?php
            switch ($tab) {
                case 'blocked':
                    $this->render_blocked_tab();
                    break;
                case 'assign':
                    $this->render_assign_tab();
                    break;
                default:
                    $this->render_settings_tab();
                    break;
            }
            ?>
        </div>
        <?php
    }

    private function render_settings_tab() {
        $settings = get_option('ignis_username_settings', ['cost' => 100]);

        if (isset($_POST['ignis_username_settings']) && check_admin_referer('ignis_username_settings')) {
            $settings['cost'] = absint($_POST['ignis_username']['cost']);
            update_option('ignis_username_settings', $settings);
            echo '<div class="updated"><p>' . __('Settings saved.', 'ignis-plugin') . '</p></div>';
        }

        ?>
        <form method="post">
            <?php wp_nonce_field('ignis_username_settings'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="cost"><?php _e('Username Change Cost', 'ignis-plugin'); ?></label></th>
                    <td>
                        <input type="number" name="ignis_username[cost]" id="cost" value="<?php echo esc_attr($settings['cost']); ?>" min="0" class="small-text">
                        <p class="description"><?php _e('Cost in Points or MangaCoin to change a username.', 'ignis-plugin'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <?php
    }

    private function render_blocked_tab() {
        $blocked_usernames = get_option('ignis_blocked_usernames', []);

        if (isset($_POST['ignis_blocked_usernames']) && check_admin_referer('ignis_blocked_usernames')) {
            $blocked_usernames = array_map('sanitize_text_field', array_filter(explode("\n", $_POST['blocked_usernames'])));
            update_option('ignis_blocked_usernames', $blocked_usernames);
            echo '<div class="updated"><p>' . __('Blocked usernames updated.', 'ignis-plugin') . '</p></div>';
        }

        ?>
        <form method="post">
            <?php wp_nonce_field('ignis_blocked_usernames'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="blocked_usernames"><?php _e('Blocked Usernames', 'ignis-plugin'); ?></label></th>
                    <td>
                        <textarea name="blocked_usernames" id="blocked_usernames" rows="10" class="large-text"><?php echo esc_textarea(implode("\n", $blocked_usernames)); ?></textarea>
                        <p class="description"><?php _e('Enter one username per line to block.', 'ignis-plugin'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <?php
    }

    private function render_assign_tab() {
        if (isset($_POST['ignis_assign_username']) && check_admin_referer('ignis_assign_username')) {
            $user_id = absint($_POST['user_id']);
            $new_username = sanitize_user($_POST['new_username'], true);

            if (!$user_id || empty($new_username)) {
                echo '<div class="error"><p>' . __('User ID and username are required.', 'ignis-plugin') . '</p></div>';
            } elseif (username_exists($new_username)) {
                echo '<div class="error"><p>' . __('Username is already taken.', 'ignis-plugin') . '</p></div>';
            } else {
                $result = wp_update_user([
                    'ID' => $user_id,
                    'user_login' => $new_username,
                    'user_nicename' => sanitize_title($new_username)
                ]);

                if (is_wp_error($result)) {
                    echo '<div class="error"><p>' . __('Failed to assign username.', 'ignis-plugin') . '</p></div>';
                } else {
                    echo '<div class="updated"><p>' . sprintf(__('Username %s assigned to user %d.', 'ignis-plugin'), esc_html($new_username), $user_id) . '</p></div>';
                }
            }
        }

        ?>
        <form method="post">
            <?php wp_nonce_field('ignis_assign_username'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="user_id"><?php _e('User ID', 'ignis-plugin'); ?></label></th>
                    <td>
                        <input type="number" name="user_id" id="user_id" class="small-text" required>
                        <p class="description"><?php _e('Enter the ID of the user to assign the username to.', 'ignis-plugin'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="new_username"><?php _e('New Username', 'ignis-plugin'); ?></label></th>
                    <td>
                        <input type="text" name="new_username" id="new_username" class="regular-text" required>
                        <p class="description"><?php _e('Enter the new username to assign.', 'ignis-plugin'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Assign Username', 'ignis-plugin')); ?>
        </form>
        <?php
    }
}

// Initialize module
new Ignis_Premium_Usernames_Admin();
?>
