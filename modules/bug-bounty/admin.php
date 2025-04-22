<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Bug Bounty Admin
class Ignis_Bug_Bounty_Admin {
    public function __construct() {
        add_filter('ignis_admin_submenus', [$this, 'register_admin_submenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_admin_submenu($submenus) {
        $submenus[] = [
            'title' => __('Bug Bounty', 'ignis-plugin'),
            'menu_title' => __('Bug Bounty', 'ignis-plugin'),
            'capability' => 'manage_options',
            'slug' => 'ignis-bug-bounty',
            'callback' => [$this, 'admin_page_callback']
        ];
        return $submenus;
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'ignis-bug-bounty') !== false) {
            wp_enqueue_style('ignis-bug-bounty-admin', IGNIS_PLUGIN_URL . 'modules/bug-bounty/assets/admin.css', [], IGNIS_PLUGIN_VERSION);
            wp_enqueue_script('ignis-bug-bounty-admin', IGNIS_PLUGIN_URL . 'modules/bug-bounty/assets/admin.js', ['jquery'], IGNIS_PLUGIN_VERSION, true);
        }
    }

    public function admin_page_callback() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ignis_bug_reports';
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        $report_id = isset($_GET['report_id']) ? absint($_GET['report_id']) : 0;

        if ($action === 'update' && $report_id && check_admin_referer('ignis_update_bug_report_' . $report_id)) {
            $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'pending';
            $reward_type = isset($_POST['reward_type']) ? sanitize_text_field($_POST['reward_type']) : '';
            $reward_amount = isset($_POST['reward_amount']) ? absint($_POST['reward_amount']) : 0;

            $wpdb->update($table_name, ['status' => $status], ['id' => $report_id]);

            if ($status === 'approved' && $reward_amount > 0) {
                $report = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $report_id));
                $user_id = $report->user_id;

                if ($reward_type === 'points') {
                    $points = Ignis_Utilities::get_user_points($user_id);
                    Ignis_Utilities::update_user_points($user_id, $points + $reward_amount);
                    Ignis_Utilities::log_action($user_id, 'points', $reward_amount, 'bug_bounty_reward', $report_id);
                    do_action('ignis_points_added', $user_id, $reward_amount, 'bug_bounty_reward');
                } elseif ($reward_type === 'currency') {
                    $currency = Ignis_Utilities::get_user_currency($user_id);
                    Ignis_Utilities::update_user_currency($user_id, $currency + $reward_amount);
                    Ignis_Utilities::log_action($user_id, 'currency', $reward_amount, 'bug_bounty_reward', $report_id);
                    do_action('ignis_currency_added', $user_id, $reward_amount, 'bug_bounty_reward');
                }
            }

            echo '<div class="updated"><p>' . __('Bug report updated.', 'ignis-plugin') . '</p></div>';
        }

        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $reports = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT %d OFFSET %d",
            $per_page,
            ($page - 1) * $per_page
        ));
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

        ?>
        <div class="wrap">
            <h1><?php _e('Bug Bounty', 'ignis-plugin'); ?></h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('User ID', 'ignis-plugin'); ?></th>
                        <th><?php _e('Title', 'ignis-plugin'); ?></th>
                        <th><?php _e('Description', 'ignis-plugin'); ?></th>
                        <th><?php _e('Screenshot', 'ignis-plugin'); ?></th>
                        <th><?php _e('Status', 'ignis-plugin'); ?></th>
                        <th><?php _e('Timestamp', 'ignis-plugin'); ?></th>
                        <th><?php _e('Actions', 'ignis-plugin'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report) : ?>
                        <tr>
                            <td><?php echo esc_html($report->user_id); ?></td>
                            <td><?php echo esc_html($report->title); ?></td>
                            <td><?php echo esc_html(wp_trim_words($report->description, 20)); ?></td>
                            <td><?php echo $report->screenshot ? '<a href="' . esc_url($report->screenshot) . '" target="_blank">' . __('View', 'ignis-plugin') . '</a>' : '-'; ?></td>
                            <td><?php echo esc_html($report->status); ?></td>
                            <td><?php echo esc_html($report->timestamp); ?></td>
                            <td>
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=ignis-bug-bounty&action=update&report_id=' . $report->id), 'ignis_update_bug_report_' . $report->id)); ?>" class="button"><?php _e('Edit', 'ignis-plugin'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
            $total_pages = ceil($total / $per_page);
            if ($total_pages > 1) :
            ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php printf(__('%d items', 'ignis-plugin'), $total); ?></span>
                        <span class="pagination-links">
                            <?php if ($page > 1) : ?>
                                <a class="prev-page" href="<?php echo esc_url(add_query_arg('paged', $page - 1)); ?>">«</a>
                            <?php endif; ?>
                            <span class="current-page"><?php printf(__('Page %d of %d', 'ignis-plugin'), $page, $total_pages); ?></span>
                            <?php if ($page < $total_pages) : ?>
                                <a class="next-page" href="<?php echo esc_url(add_query_arg('paged', $page + 1)); ?>">»</a>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($action === 'update' && $report_id) : ?>
                <?php $report = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $report_id)); ?>
                <h2><?php _e('Update Bug Report', 'ignis-plugin'); ?></h2>
                <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=ignis-bug-bounty&action=update&report_id=' . $report_id . '&_wpnonce=' . wp_create_nonce('ignis_update_bug_report_' . $report_id))); ?>">
                    <table class="form-table">
                        <tr>
                            <th><label><?php _e('Title', 'ignis-plugin'); ?></label></th>
                            <td><input type="text" value="<?php echo esc_attr($report->title); ?>" disabled></td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Description', 'ignis-plugin'); ?></label></th>
                            <td><textarea disabled rows="5"><?php echo esc_textarea($report->description); ?></textarea></td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Screenshot', 'ignis-plugin'); ?></label></th>
                            <td><?php echo $report->screenshot ? '<a href="' . esc_url($report->screenshot) . '" target="_blank">' . __('View Screenshot', 'ignis-plugin') . '</a>' : __('No screenshot', 'ignis-plugin'); ?></td>
                        </tr>
                        <tr>
                            <th><label for="status"><?php _e('Status', 'ignis-plugin'); ?></label></th>
                            <td>
                                <select name="status" id="status">
                                    <option value="pending" <?php selected($report->status, 'pending'); ?>><?php _e('Pending', 'ignis-plugin'); ?></option>
                                    <option value="approved" <?php selected($report->status, 'approved'); ?>><?php _e('Approved', 'ignis-plugin'); ?></option>
                                    <option value="rejected" <?php selected($report->status, 'rejected'); ?>><?php _e('Rejected', 'ignis-plugin'); ?></option>
                                    <option value="resolved" <?php selected($report->status, 'resolved'); ?>><?php _e('Resolved', 'ignis-plugin'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="reward_type"><?php _e('Reward Type', 'ignis-plugin'); ?></label></th>
                            <td>
                                <select name="reward_type" id="reward_type">
                                    <option value=""><?php _e('None', 'ignis-plugin'); ?></option>
                                    <option value="points"><?php _e('Points',=db 'ignis-plugin'); ?></option>
                                    <option value="currency"><?php _e('MangaCoin', 'ignis-plugin'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="reward_amount"><?php _e('Reward Amount', 'ignis-plugin'); ?></label></th>
                            <td>
                                <input type="number" name="reward_amount" id="reward_amount" value="0" min="0" class="small-text">
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(__('Update Report', 'ignis-plugin')); ?>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }
}

// Initialize module
new Ignis_Bug_Bounty_Admin();
?>
