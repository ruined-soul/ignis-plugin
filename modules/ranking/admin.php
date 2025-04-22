<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Ranking Admin
class Ignis_Ranking_Admin {
    public function __construct() {
        add_filter('ignis_admin_submenus', [$this, 'register_admin_submenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_admin_submenu($submenus) {
        $submenus[] = [
            'title' => __('Ranking Settings', 'ignis-plugin'),
            'menu_title' => __('Ranking', 'ignis-plugin'),
            'capability' => 'manage_options',
            'slug' => 'ignis-ranking',
            'callback' => [$this, 'admin_page_callback']
        ];
        return $submenus;
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'ignis-ranking') !== false) {
            wp_enqueue_style('ignis-ranking-admin', IGNIS_PLUGIN_URL . 'modules/ranking/assets/admin.css', [], IGNIS_PLUGIN_VERSION);
        }
    }

    public function admin_page_callback() {
        $options = get_option('ignis_ranking_settings', [
            'metric' => 'points',
            'limit' => 10,
            'cache_duration' => 3600
        ]);

        if (isset($_POST['ignis_ranking_settings']) && check_admin_referer('ignis_ranking_settings')) {
            $options = array_merge($options, array_map('sanitize_text_field', $_POST['ignis_ranking']));
            $options['limit'] = max(1, absint($options['limit']));
            $options['cache_duration'] = max(0, absint($options['cache_duration']));
            update_option('ignis_ranking_settings', $options);
            delete_transient('ignis_leaderboard_' . $options['metric'] . '_' . $options['limit']);
            echo '<div class="updated"><p>' . __('Settings saved.', 'ignis-plugin') . '</p></div>';
        }

        ?>
        <div class="wrap">
            <h1><?php _e('Ranking Settings', 'ignis-plugin'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('ignis_ranking_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="metric"><?php _e('Ranking Metric', 'ignis-plugin'); ?></label></th>
                        <td>
                            <select name="ignis_ranking[metric]" id="metric">
                                <option value="points" <?php selected($options['metric'], 'points'); ?>><?php _e('Points', 'ignis-plugin'); ?></option>
                                <option value="currency" <?php selected($options['metric'], 'currency'); ?>><?php _e('MangaCoin', 'ignis-plugin'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="limit"><?php _e('Number of Users', 'ignis-plugin'); ?></label></th>
                        <td>
                            <input type="number" name="ignis_ranking[limit]" id="limit" value="<?php echo esc_attr($options['limit']); ?>" min="1" class="small-text">
                            <p class="description"><?php _e('Number of users to display in the leaderboard.', 'ignis-plugin'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="cache_duration"><?php _e('Cache Duration (seconds)', 'ignis-plugin'); ?></label></th>
                        <td>
                            <input type="number" name="ignis_ranking[cache_duration]" id="cache_duration" value="<?php echo esc_attr($options['cache_duration']); ?>" min="0" class="small-text">
                            <p class="description"><?php _e('How long to cache leaderboard data (0 to disable).', 'ignis-plugin'); ?></p>
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
new Ignis_Ranking_Admin();
?>
