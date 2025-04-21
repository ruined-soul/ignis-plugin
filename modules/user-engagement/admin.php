<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// User Engagement Admin
class Ignis_User_Engagement_Admin {
    public function __construct() {
        add_filter('ignis_admin_submenus', [$this, 'register_admin_submenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_admin_submenu($submenus) {
        $submenus[] = [
            'title' => __('User Engagement Settings', 'ignis-plugin'),
            'menu_title' => __('User Engagement', 'ignis-plugin'),
            'capability' => 'manage_options',
            'slug' => 'ignis-user-engagement',
            'callback' => [$this, 'admin_page_callback']
        ];
        return $submenus;
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'ignis-user-engagement') !== false) {
            wp_enqueue_style('ignis-user-engagement-admin', IGNIS_PLUGIN_URL . 'modules/user-engagement/assets/admin.css', [], IGNIS_PLUGIN_VERSION);
            wp_enqueue_script('ignis-user-engagement-admin', IGNIS_PLUGIN_URL . 'modules/user-engagement/assets/admin.js', ['jquery'], IGNIS_PLUGIN_VERSION, true);
        }
    }

    public function admin_page_callback() {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';
        ?>
        <div class="wrap">
            <h1><?php _e('User Engagement Settings', 'ignis-plugin'); ?></h1>
            <nav class="nav-tab-wrapper">
                <a href="?page=ignis-user-engagement&tab=settings" class="nav-tab <?php echo $tab === 'settings' ? 'nav-tab-active' : ''; ?>"><?php _e('Settings', 'ignis-plugin'); ?></a>
                <a href="?page=ignis-user-engagement&tab=referrals" class="nav-tab <?php echo $tab === 'referrals' ? 'nav-tab-active' : ''; ?>"><?php _e('Referrals', 'ignis-plugin'); ?></a>
                <a href="?page=ignis-user-engagement&tab=bugs" class="nav-tab <?php echo $tab === 'bugs' ? 'nav-tab-active' : ''; ?>"><?php _e('Bug Reports', 'ignis-plugin'); ?></a>
            </nav>
            <?php
            switch ($tab) {
                case 'referrals':
                    $this->render_referrals_tab();
                    break;
                case 'bugs':
                    $this->render_bugs_tab();
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
        $options = get_option('ignis_user_engagement_settings', [
            'enable_referrals' => 1,
            'enable_bug_reports' => 1,
            'bug_report_notification_email' => get_option('admin_email')
        ]);

        if (isset($_POST['ignis_user_engagement_settings']) && check_admin_referer('ignis_user_engagement_settings')) {
            $options = array_merge($options, array_map('sanitize_text_field', $_POST['ignis_engagement']));
            $options['enable_referrals'] = isset($_POST['ignis_engagement']['enable_referrals']) ? 1 : 0;
            $options['enable_bug_reports'] = isset($_POST['ignis_engagement']['enable_bug_reports']) ? 1 : 0;
            update_option('ignis_user_engagement_settings', $options);
            echo '<div class="updated"><p>' . __('Settings saved.', 'ignis-plugin') . '</p></div>';
        }

        ?>
        <form method="post">
            <?php wp_nonce_field('ignis_user_engagement_settings'); ?>
            <table class="form-table">
                <tr>
                    <th><label><?php _e('Enable Referrals', 'ignis-plugin'); ?></label></th>
                    <td><input type="checkbox" name="ignis_engagement[enable_referrals]" <?php checked($options['enable_referrals'], 1); ?>></td>
                </tr>
                <tr>
                    <th><label><?php _e('Enable Bug Reports', 'ignis-plugin'); ?></label></th>
                    <td><input type="checkbox" name="ignis_engagement[enable_bug_reports]" <?php checked($options['enable_bug_reports'], 1); ?>></td>
                </tr>
                <tr>
                    <th><label for="bug_report_notification_email"><?php _e('Bug Report Notification Email', 'ignis-plugin'); ?></label></th>
                    <td><input type="email" name="ignis_engagement[bug_report_notification_email]" value="<?php echo esc_attr($options['bug_report_notification_email']); ?>" class="regular-text"></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <?php
    }

    private function render_referrals_tab() {
        global $wpdb;
        $referral_table = $wpdb->prefix . 'ignis_referrals';
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;

        $where = '1=1';
        if (isset($_GET['user_id']) && $_GET['user_id']) {
            $where .= $wpdb->prepare(" AND user_id = %d", intval($_GET['user_id']));
        }

        $referrals = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $referral_table WHERE $where ORDER BY timestamp DESC LIMIT %d OFFSET %d",
            $per_page,
            ($page - 1) * $per_page
        ));

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $referral_table WHERE $where");

        ?>
        <h2><?php _e('Referral Logs', 'ignis-plugin'); ?></h2>
        <form method="get">
            <input type="hidden" name="page" value="ignis-user-engagement">
            <input type="hidden" name="tab" value="referrals">
            <label><?php _e('Filter by User ID:', 'ignis-plugin'); ?></label>
            <input type="number" name="user_id" value="<?php echo isset($_GET['user_id']) ? esc_attr($_GET['user_id']) : ''; ?>">
            <input type="submit" class="button" value="<?php _e('Filter', 'ignis-plugin'); ?>">
        </form>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('User ID', 'ignis-plugin'); ?></th>
                    <th><?php _e('Referral Code', 'ignis-plugin'); ?></th>
                    <th><?php _e('Referred User ID', 'ignis-plugin'); ?></th>
                    <th><?php _e('Status', 'ignis-plugin'); ?></th>
                    <th><?php _e('Timestamp', 'ignis-plugin'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($referrals as $referral) : ?>
                    <tr>
                        <td><?php echo esc_html($referral->user_id); ?></td>
                        <td><?php echo esc_html($referral->referral_code); ?></td>
                        <td><?php echo esc_html($referral->referred_user_id ?: '-'); ?></td>
                        <td><?php echo esc_html($referral->status); ?></td>
                        <td><?php echo esc_html($referral->timestamp); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        $this->render_pagination($total, $per_page, $page, 'referrals');
    }

    private function render_bugs_tab() {
        global $wpdb;
        $bug_table = $wpdb->prefix . 'ignis_bug_reports';
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;

        $where = '1=1';
        if (isset($_GET['user_id']) && $_GET['user_id']) {
            $where .= $wpdb->prepare(" AND user_id = %d", intval($_GET['user_id']));
        }

        $bugs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $bug_table WHERE $where ORDER BY timestamp DESC LIMIT %d OFFSET %d",
            $per_page,
            ($page - 1) * $per_page
        ));

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $bug_table WHERE $where");

        if (isset($_POST['ignis_bug_status']) && check_admin_referer('ignis_bug_status')) {
            $bug_id = intval($_POST['bug_id']);
            $status = sanitize_text_field($_POST['status']);
            if ($bug_id && in_array($status, ['pending', 'approved', 'rejected', 'duplicate'])) {
                $wpdb->update(
                    $bug_table,
                    ['status' => $status],
                    ['id' => $bug_id]
                );
                do_action('ignis_bug_status_updated', $bug_id, $status);
                echo '<div class="updated"><p>' . __('Bug status updated.', 'ignis-plugin') . '</p></div>';
            }
        }

        ?>
        <h2><?php _e('Bug Reports', 'ignis-plugin'); ?></h2>
        <form method="get">
            <input type="hidden" name="page" value="ignis-user-engagement">
            <input type="hidden" name="tab" value="bugs">
            <label><?php _e('Filter by User ID:', 'ignis-plugin'); ?></label>
            <input type="number" name="user_id" value="<?php echo isset($_GET['user_id']) ? esc_attr($_GET['user_id']) : ''; ?>">
            <input type="submit" class="button" value="<?php _e('Filter', 'ignis-plugin'); ?>">
        </form>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('User ID', 'ignis-plugin'); ?></th>
                    <th><?php _e('Title', 'ignis-plugin'); ?></th>
                    <th><?php _e('Status', 'ignis-plugin'); ?></th>
                    <th><?php _e('Timestamp', 'ignis-plugin'); ?></th>
                    <th><?php _e('Actions', 'ignis-plugin'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bugs as $bug) : ?>
                    <tr>
                        <td><?php echo esc_html($bug->user_id); ?></td>
                        <td>
                            <?php echo esc_html($bug->title); ?>
                            <p><small><?php echo esc_html(wp_trim_words($bug->description, 20)); ?></small></p>
                            <?php if ($bug->screenshot) : ?>
                                <a href="<?php echo esc_url($bug->screenshot); ?>" target="_blank"><?php _e('View Screenshot', 'ignis-plugin'); ?></a>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($bug->status); ?></td>
                        <td><?php echo esc_html($bug->timestamp); ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <?php wp_nonce_field('ignis_bug_status'); ?>
                                <input type="hidden" name="bug_id" value="<?php echo esc_attr($bug->id); ?>">
                                <select name="status">
                                    <option value="pending" <?php selected($bug->status, 'pending'); ?>><?php _e('Pending', 'ignis-plugin'); ?></option>
                                    <option value="approved" <?php selected($bug->status, 'approved'); ?>><?php _e('Approved', 'ignis-plugin'); ?></option>
                                    <option value="rejected" <?php selected($bug->status, 'rejected'); ?>><?php _e('Rejected', 'ignis-plugin'); ?></option>
                                    <option value="duplicate" <?php selected($bug->status, 'duplicate'); ?>><?php _e('Duplicate', 'ignis-plugin'); ?></option>
                                </select>
                                <input type="submit" name="ignis_bug_status" class="button" value="<?php _e('Update', 'ignis-plugin'); ?>">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        $this->render_pagination($total, $per_page, $page, 'bugs');
    }

    private function render_pagination($total, $per_page, $current_page, $tab) {
        $total_pages = ceil($total / $per_page);
        if ($total_pages <= 1) {
            return;
        }

        $base_url = add_query_arg(['page' => 'ignis-user-engagement', 'tab' => $tab]);
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
new Ignis_User_Engagement_Admin();
?>
