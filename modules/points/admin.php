<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Points Admin
class Ignis_Points_Admin {
    public function __construct() {
        add_filter('ignis_admin_submenus', [$this, 'register_admin_submenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_admin_submenu($submenus) {
        $submenus[] = [
            'title' => __('Points Settings', 'ignis-plugin'),
            'menu_title' => __('Points', 'ignis-plugin'),
            'capability' => 'manage_options',
            'slug' => 'ignis-points',
            'callback' => [$this, 'admin_page_callback']
        ];
        return $submenus;
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'ignis-points') !== false) {
            wp_enqueue_style('ignis-points-css', IGNIS_PLUGIN_URL . 'modules/points/assets/points.css', [], IGNIS_PLUGIN_VERSION);
            wp_enqueue_script('ignis-points-js', IGNIS_PLUGIN_URL . 'modules/points/assets/points.js', ['jquery'], IGNIS_PLUGIN_VERSION, true);
        }
    }

    public function admin_page_callback() {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';
        ?>
        <div class="wrap">
            <h1><?php _e('Points Settings', 'ignis-plugin'); ?></h1>
            <nav class="nav-tab-wrapper">
                <a href="?page=ignis-points&tab=settings" class="nav-tab <?php echo $tab === 'settings' ? 'nav-tab-active' : ''; ?>"><?php _e('Settings', 'ignis-plugin'); ?></a>
                <a href="?page=ignis-points&tab=users" class="nav-tab <?php echo $tab === 'users' ? 'nav-tab-active' : ''; ?>"><?php _e('User Management', 'ignis-plugin'); ?></a>
                <a href="?page=ignis-points&tab=logs" class="nav-tab <?php echo $tab === 'logs' ? 'nav-tab-active' : ''; ?>"><?php _e('Logs', 'ignis-plugin'); ?></a>
            </nav>
            <?php
            switch ($tab) {
                case 'users':
                    $this->render_users_tab();
                    break;
                case 'logs':
                    $this->render_logs_tab();
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
        $options = get_option('ignis_points_settings', [
            'points_per_chapter' => 2,
            'points_per_chapter_extra' => 1,
            'points_per_comment' => 1,
            'points_per_bug_submit' => 5,
            'points_per_bug_approved' => 50,
            'points_per_airdrop' => 15,
            'points_per_request' => 25,
            'points_per_login' => 3,
            'points_per_streak' => 10,
            'points_per_referral' => 100,
            'points_per_referral_bonus' => 200,
            'points_per_signup' => 100,
            'points_per_signup_referral' => 50,
            'points_per_share' => 10,
            'points_to_gold_ratio' => 100,
            'daily_action_limit' => 1,
            'chapter_lifetime_cap' => 4,
            'comment_lifetime_cap' => 3,
            'enable_chapter_points' => 1,
            'enable_comment_points' => 1,
            'enable_bug_points' => 1,
            'enable_airdrop_points' => 1,
            'enable_request_points' => 1,
            'enable_login_points' => 1,
            'enable_streak_points' => 1,
            'enable_referral_points' => 1,
            'enable_signup_points' => 1,
            'enable_share_points' => 1,
            'toast_message' => '+{points} points for {action}!',
            'milestone_message' => 'Congrats! You’ve earned {points} points!'
        ]);

        if (isset($_POST['ignis_points_settings']) && check_admin_referer('ignis_points_settings')) {
            $options = array_merge($options, array_map('sanitize_text_field', $_POST['ignis_points']));
            foreach ([
                'enable_chapter_points', 'enable_comment_points', 'enable_bug_points', 'enable_airdrop_points',
                'enable_request_points', 'enable_login_points', 'enable_streak_points', 'enable_referral_points',
                'enable_signup_points', 'enable_share_points'
            ] as $key) {
                $options[$key] = isset($_POST['ignis_points'][$key]) ? 1 : 0;
            }
            update_option('ignis_points_settings', $options);
            echo '<div class="updated"><p>' . __('Settings saved.', 'ignis-plugin') . '</p></div>';
        }

        ?>
        <form method="post">
            <?php wp_nonce_field('ignis_points_settings'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="points_per_chapter"><?php _e('Points per First Chapter Read', 'ignis-plugin'); ?></label></th>
                    <td>
                        <input type="number" name="ignis_points[points_per_chapter]" value="<?php echo esc_attr($options['points_per_chapter']); ?>" min="0">
                        <label><input type="checkbox" name="ignis_points[enable_chapter_points]" <?php checked($options['enable_chapter_points'], 1); ?>> <?php _e('Enable', 'ignis-plugin'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th><label for="points_per_chapter_extra"><?php _e('Points per 2nd/3rd Chapter Read', 'ignis-plugin'); ?></label></th>
                    <td><input type="number" name="ignis_points[points_per_chapter_extra]" value="<?php echo esc_attr($options['points_per_chapter_extra']); ?>" min="0"></td>
                </tr>
                <tr>
                    <th><label for="chapter_lifetime_cap"><?php _e('Chapter Lifetime Cap', 'ignis-plugin'); ?></label></th>
                    <td><input type="number" name="ignis_points[chapter_lifetime_cap]" value="<?php echo esc_attr($options['chapter_lifetime_cap']); ?>" min="0"></td>
                </tr>
                <tr>
                    <th><label for="points_per_comment"><?php _e('Points per Comment', 'ignis-plugin'); ?></label></th>
                    <td>
                        <input type="number" name="ignis_points[points_per_comment]" value="<?php echo esc_attr($options['points_per_comment']); ?>" min="0">
                        <label><input type="checkbox" name="ignis_points[enable_comment_points]" <?php checked($options['enable_comment_points'], 1); ?>> <?php _e('Enable', 'ignis-plugin'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th><label for="comment_lifetime_cap"><?php _e('Comment Lifetime Cap', 'ignis-plugin'); ?></label></th>
                    <td><input type="number" name="ignis_points[comment_lifetime_cap]" value="<?php echo esc_attr($options['comment_lifetime_cap']); ?>" min="0"></td>
                </tr>
                <tr>
                    <th><label for="points_per_bug_submit"><?php _e('Points per Bug Submission', 'ignis-plugin'); ?></label></th>
                    <td>
                        <input type="number" name="ignis_points[points_per_bug_submit]" value="<?php echo esc_attr($options['points_per_bug_submit']); ?>" min="0">
                        <label><input type="checkbox" name="ignis_points[enable_bug_points]" <?php checked($options['enable_bug_points'], 1); ?>> <?php _e('Enable', 'ignis-plugin'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th><label for="points_per_bug_approved"><?php _e('Points per Bug Approval', 'ignis-plugin'); ?></label></th>
                    <td><input type="number" name="ignis_points[points_per_bug_approved]" value="<?php echo esc_attr($options['points_per_bug_approved']); ?>" min="0"></td>
                </tr>
                <tr>
                    <th><label for="points_per_airdrop"><?php _e('Points per Airdrop', 'ignis-plugin'); ?></label></th>
                    <td>
                        <input type="number" name="ignis_points[points_per_airdrop]" value="<?php echo esc_attr($options['points_per_airdrop']); ?>" min="0">
                        <label><input type="checkbox" name="ignis_points[enable_airdrop_points]" <?php checked($options['enable_airdrop_points'], 1); ?>> <?php _e('Enable', 'ignis-plugin'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th><label for="points_per_request"><?php _e('Points per Manga Request', 'ignis-plugin'); ?></label></th>
                    <td>
                        <input type="number" name="ignis_points[points_per_request]" value="<?php echo esc_attr($options['points_per_request']); ?>" min="0">
                        <label><input type="checkbox" name="ignis_points[enable_request_points]" <?php checked($options['enable_request_points'], 1); ?>> <?php _e('Enable', 'ignis-plugin'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th><label for="points_per_login"><?php _e('Points per Daily Login', 'ignis-plugin'); ?></label></th>
                    <td>
                        <input type="number" name="ignis_points[points_per_login]" value="<?php echo esc_attr($options['points_per_login']); ?>" min="0">
                        <label><input type="checkbox" name="ignis_points[enable_login_points]" <?php checked($options['enable_login_points'], 1); ?>> <?php _e('Enable', 'ignis-plugin'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th><label for="points_per_streak"><?php _e('Points per Weekly Streak', 'ignis-plugin'); ?></label></th>
                    <td>
                        <input type="number" name="ignis_points[points_per_streak]" value="<?php echo esc_attr($options['points_per_streak']); ?>" min="0">
                        <label><input type="checkbox" name="ignis_points[enable_streak_points]" <?php checked($options['enable_streak_points'], 1); ?>> <?php _e('Enable', 'ignis-plugin'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th><label for="points_per_referral"><?php _e('Points per Referral', 'ignis-plugin'); ?></label></th>
                    <td>
                        <input type="number" name="ignis_points[points_per_referral]" value="<?php echo esc_attr($options['points_per_referral']); ?>" min="0">
                        <label><input type="checkbox" name="ignis_points[enable_referral_points]" <?php checked($options['enable_referral_points'], 1); ?>> <?php _e('Enable', 'ignis-plugin'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th><label for="points_per_referral_bonus"><?php _e('Referral Bonus (3rd)', 'ignis-plugin'); ?></label></th>
                    <td><input type="number" name="ignis_points[points_per_referral_bonus]" value="<?php echo esc_attr($options['points_per_referral_bonus']); ?>" min="0"></td>
                </tr>
                <tr>
                    <th><label for="points_per_signup"><?php _e('Points per Signup', 'ignis-plugin'); ?></label></th>
                    <td>
                        <input type="number" name="ignis_points[points_per_signup]" value="<?php echo esc_attr($options['points_per_signup']); ?>" min="0">
                        <label><input type="checkbox" name="ignis_points[enable_signup_points]" <?php checked($options['enable_signup_points'], 1); ?>> <?php _e('Enable', 'ignis-plugin'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th><label for="points_per_signup_referral"><?php _e('Points per Referral Signup', 'ignis-plugin'); ?></label></th>
                    <td><input type="number" name="ignis_points[points_per_signup_referral]" value="<?php echo esc_attr($options['points_per_signup_referral']); ?>" min="0"></td>
                </tr>
                <tr>
                    <th><label for="points_per_share"><?php _e('Points per Share', 'ignis-plugin'); ?></label></th>
                    <td>
                        <input type="number" name="ignis_points[points_per_share]" value="<?php echo esc_attr($options['points_per_share']); ?>" min="0">
                        <label><input type="checkbox" name="ignis_points[enable_share_points]" <?php checked($options['enable_share_points'], 1); ?>> <?php _e('Enable', 'ignis-plugin'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th><label for="points_to_gold_ratio"><?php _e('Points to MangaCoin Ratio', 'ignis-plugin'); ?></label></th>
                    <td><input type="number" name="ignis_points[points_to_gold_ratio]" value="<?php echo esc_attr($options['points_to_gold_ratio']); ?>" min="1"></td>
                </tr>
                <tr>
                    <th><label for="daily_action_limit"><?php _e('Daily Action Limit', 'ignis-plugin'); ?></label></th>
                    <td><input type="number" name="ignis_points[daily_action_limit]" value="<?php echo esc_attr($options['daily_action_limit']); ?>" min="0"></td>
                </tr>
                <tr>
                    <th><label for="toast_message"><?php _e('Toast Message', 'ignis-plugin'); ?></label></th>
                    <td><input type="text" name="ignis_points[toast_message]" value="<?php echo esc_attr($options['toast_message']); ?>" placeholder="+{points} points for {action}!" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="milestone_message"><?php _e('Milestone Message', 'ignis-plugin'); ?></label></th>
                    <td><input type="text" name="ignis_points[milestone_message]" value="<?php echo esc_attr($options['milestone_message']); ?>" placeholder="Congrats! You’ve earned {points} points!" class="regular-text"></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <?php
    }

    private function render_users_tab() {
        global $wpdb;

        if (isset($_POST['ignis_points_adjust']) && check_admin_referer('ignis_points_adjust')) {
            $user_id = intval($_POST['user_id']);
            $points = intval($_POST['points']);
            $action = sanitize_text_field($_POST['action_type']);

            if ($user_id && $points !== 0) {
                $current_points = (int) get_user_meta($user_id, 'ignis_points', true);
                $new_points = $action === 'add' ? $current_points + $points : $current_points - $points;

                if ($new_points >= 0) {
                    update_user_meta($user_id, 'ignis_points', $new_points);
                    $wpdb->insert($wpdb->prefix . 'ignis_logs', [
                        'user_id' => $user_id,
                        'type' => 'points',
                        'amount' => $action === 'add' ? $points : -$points,
                        'action' => 'admin_adjust',
                        'timestamp' => current_time('mysql'),
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                    ]);
                    echo '<div class="updated"><p>' . __('Points updated.', 'ignis-plugin') . '</p></div>';
                } else {
                    echo '<div class="error"><p>' . __('Insufficient points for deduction.', 'ignis-plugin') . '</p></div>';
                }
            }
        }

        $users = get_users(['number' => 20]);
        ?>
        <h2><?php _e('Manage User Points', 'ignis-plugin'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Username', 'ignis-plugin'); ?></th>
                    <th><?php _e('Points', 'ignis-plugin'); ?></th>
                    <th><?php _e('Actions', 'ignis-plugin'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user) : ?>
                    <tr>
                        <td><?php echo esc_html($user->user_login); ?></td>
                        <td><?php echo esc_html((int) get_user_meta($user->ID, 'ignis_points', true)); ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <?php wp_nonce_field('ignis_points_adjust'); ?>
                                <input type="hidden" name="user_id" value="<?php echo esc_attr($user->ID); ?>">
                                <input type="number" name="points" min="1" style="width:80px;" required>
                                <select name="action_type">
                                    <option value="add"><?php _e('Add', 'ignis-plugin'); ?></option>
                                    <option value="remove"><?php _e('Remove', 'ignis-plugin'); ?></option>
                                </select>
                                <input type="submit" name="ignis_points_adjust" class="button" value="<?php _e('Apply', 'ignis-plugin'); ?>">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    private function render_logs_tab() {
        global $wpdb;
        $log_table = $wpdb->prefix . 'ignis_logs';
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;

        $where = "type = 'points'";
        if (isset($_GET['user_id']) && $_GET['user_id']) {
            $where .= $wpdb->prepare(" AND user_id = %d", intval($_GET['user_id']));
        }

        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $log_table WHERE $where ORDER BY timestamp DESC LIMIT %d OFFSET %d",
            $per_page,
            ($page - 1) * $per_page
        ));

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $log_table WHERE $where");

        ?>
        <h2><?php _e('Points Logs', 'ignis-plugin'); ?></h2>
        <form method="get">
            <input type="hidden" name="page" value="ignis-points">
            <input type="hidden" name="tab" value="logs">
            <label><?php _e('Filter by User ID:', 'ignis-plugin'); ?></label>
            <input type="number" name="user_id" value="<?php echo isset($_GET['user_id']) ? esc_attr($_GET['user_id']) : ''; ?>">
            <input type="submit" class="button" value="<?php _e('Filter', 'ignis-plugin'); ?>">
        </form>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('User ID', 'ignis-plugin'); ?></th>
                    <th><?php _e('Action', 'ignis-plugin'); ?></th>
                    <th><?php _e('Points', 'ignis-plugin'); ?></th>
                    <th><?php _e('Timestamp', 'ignis-plugin'); ?></th>
                    <th><?php _e('IP Address', 'ignis-plugin'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log) : ?>
                    <tr>
                        <td><?php echo esc_html($log->user_id); ?></td>
                        <td><?php echo esc_html($log->action); ?></td>
                        <td><?php echo esc_html($log->amount); ?></td>
                        <td><?php echo esc_html($log->timestamp); ?></td>
                        <td><?php echo esc_html($log->ip_address); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        $this->render_pagination($total, $per_page, $page);
    }

    private function render_pagination($total, $per_page, $current_page) {
        $total_pages = ceil($total / $per_page);
        if ($total_pages <= 1) {
            return;
        }

        $base_url = add_query_arg(['page' => 'ignis-points', 'tab' => 'logs']);
        ?>
        <div class="tablenav">
            <div class="tablenav-pages">
                <span class="displaying-num"><?php printf(__('%d items', 'ignis-plugin'), $total); ?></span>
                <span class="pagination-links">
                    <?php if ($current_page > 1) : ?>
                        <a class="prev-page" href="<?php echo esc_url(add_query_arg('paged', $current_page - 1, $base_url)); ?>">«</a>
                    <?php endif; ?>
                    <span class="current-page"><?php printf(__('Page %d of %d', 'ignis-plugin'), $current_page, $total_pages); ?></span>
                    <?php if ($current_page < $total_pages) : ?>
                        <a class="next-page" href="<?php echo esc_url(add_query_arg('paged', $current_page + 1, $base_url)); ?>">»</a>
                    <?php endif; ?>
                </span>
            </div>
        </div>
        <?php
    }
}

// Initialize module
new Ignis_Points_Admin();
?>
