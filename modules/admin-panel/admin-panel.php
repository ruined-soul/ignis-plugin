<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Admin Panel Module
class Ignis_Admin_Panel {
    public function __construct() {
        add_filter('ignis_admin_submenus', [$this, 'register_admin_submenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_admin_submenu($submenus) {
        $submenus[] = [
            'title' => __('Ignis Dashboard', 'ignis-plugin'),
            'menu_title' => __('Dashboard', 'ignis-plugin'),
            'capability' => 'manage_options',
            'slug' => 'ignis-control-center',
            'callback' => [$this, 'dashboard_callback']
        ];
        $submenus[] = [
            'title' => __('General Settings', 'ignis-plugin'),
            'menu_title' => __('Settings', 'ignis-plugin'),
            'capability' => 'manage_options',
            'slug' => 'ignis-settings',
            'callback' => [$this, 'settings_callback']
        ];
        return $submenus;
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'ignis-control-center') !== false || strpos($hook, 'ignis-settings') !== false) {
            wp_enqueue_style('ignis-admin-css', IGNIS_PLUGIN_URL . 'modules/admin-panel/assets/admin.css', [], IGNIS_PLUGIN_VERSION);
            wp_enqueue_script('ignis-admin-js', IGNIS_PLUGIN_URL . 'modules/admin-panel/assets/admin.js', ['jquery'], IGNIS_PLUGIN_VERSION, true);
        }
    }

    public function dashboard_callback() {
        global $wpdb;
        $total_points = $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}ignis_logs WHERE type = 'points' AND amount > 0");
        $active_users = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}ignis_logs WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $recent_logs = $wpdb->get_results("SELECT user_id, type, action, timestamp FROM {$wpdb->prefix}ignis_logs ORDER BY timestamp DESC LIMIT 10");

        ?>
        <div class="wrap">
            <h1><?php _e('Ignis Control Center', 'ignis-plugin'); ?></h1>
            <div class="ignis-dashboard">
                <div class="card">
                    <h2><?php _e('Plugin Stats', 'ignis-plugin'); ?></h2>
                    <p><?php _e('Total Points Distributed', 'ignis-plugin'); ?>: <span><?php echo esc_html($total_points ?: 0); ?></span></p>
                    <p><?php _e('Active Users (Weekly)', 'ignis-plugin'); ?>: <span><?php echo esc_html($active_users ?: 0); ?></span></p>
                    <p><?php _e('Total Store Purchases', 'ignis-plugin'); ?>: <span>0</span></p>
                </div>
                <div class="card">
                    <h2><?php _e('Recent Activity', 'ignis-plugin'); ?></h2>
                    <table class="wp-list-table widefat striped">
                        <thead>
                            <tr>
                                <th><?php _e('User ID', 'ignis-plugin'); ?></th>
                                <th><?php _e('Type', 'ignis-plugin'); ?></th>
                                <th><?php _e('Action', 'ignis-plugin'); ?></th>
                                <th><?php _e('Timestamp', 'ignis-plugin'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_logs) : ?>
                                <?php foreach ($recent_logs as $log) : ?>
                                    <tr>
                                        <td><?php echo esc_html($log->user_id); ?></td>
                                        <td><?php echo esc_html($log->type); ?></td>
                                        <td><?php echo esc_html($log->action); ?></td>
                                        <td><?php echo esc_html($log->timestamp); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr><td colspan="4"><?php _e('No recent activity', 'ignis-plugin'); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    public function settings_callback() {
        $options = get_option('ignis_general_settings', [
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
        ]);

        if (isset($_POST['ignis_general_settings']) && check_admin_referer('ignis_general_settings')) {
            $options = array_merge($options, array_map('sanitize_text_field', $_POST['ignis_settings']));
            foreach (['enable_points', 'enable_currency', 'enable_store', 'enable_airdrop', 'enable_ranking', 'enable_requests', 'enable_usernames', 'enable_themes', 'enable_bug_bounty', 'enable_shortener', 'enable_chatroom', 'enable_backup'] as $key) {
                $options[$key] = isset($_POST['ignis_settings'][$key]) ? 1 : 0;
            }
            update_option('ignis_general_settings', $options);
            echo '<div class="updated"><p>' . __('Settings saved.', 'ignis-plugin') . '</p></div>';
        }

        ?>
        <div class="wrap">
            <h1><?php _e('General Settings', 'ignis-plugin'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('ignis_general_settings'); ?>
                <table class="form-table">
                    <?php foreach ([
                        'enable_points' => __('Enable Points Module', 'ignis-plugin'),
                        'enable_currency' => __('Enable Currency Module', 'ignis-plugin'),
                        'enable_store' => __('Enable Store Module', 'ignis-plugin'),
                        'enable_airdrop' => __('Enable Airdrop Module', 'ignis-plugin'),
                        'enable_ranking' => __('Enable Ranking Module', 'ignis-plugin'),
                        'enable_requests' => __('Enable Manga Requests Module', 'ignis-plugin'),
                        'enable_usernames' => __('Enable Premium Usernames Module', 'ignis-plugin'),
                        'enable_themes' => __('Enable Custom Themes Module', 'ignis-plugin'),
                        'enable_bug_bounty' => __('Enable Bug Bounty Module', 'ignis-plugin'),
                        'enable_shortener' => __('Enable Shortener Link Module', 'ignis-plugin'),
                        'enable_chatroom' => __('Enable Chatroom Link Module', 'ignis-plugin'),
                        'enable_backup' => __('Enable Backup/Restore Module', 'ignis-plugin')
                    ] as $key => $label) : ?>
                        <tr>
                            <th><label><?php echo esc_html($label); ?></label></th>
                            <td><input type="checkbox" name="ignis_settings[<?php echo esc_attr($key); ?>]" <?php checked($options[$key], 1); ?>></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <th><label for="shortener_key"><?php _e('Shortener Private Key', 'ignis-plugin'); ?></label></th>
                        <td><input type="text" name="ignis_settings[shortener_key]" value="<?php echo esc_attr($options['shortener_key']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="chatroom_key"><?php _e('Chatroom Private Key', 'ignis-plugin'); ?></label></th>
                        <td><input type="text" name="ignis_settings[chatroom_key]" value="<?php echo esc_attr($options['chatroom_key']); ?>" class="regular-text"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

// Initialize module
new Ignis_Admin_Panel();
?>
