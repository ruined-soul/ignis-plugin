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
            wp_localize_script('ignis-bug-bounty-admin', 'ignis_bug_bounty', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ignis_bug_bounty')
            ]);
        }
    }

    public function admin_page_callback() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ignis_bug_reports';

        // Handle status update
        if (isset($_POST['ignis_bug_bounty_update']) && check_admin_referer('ignis_bug_bounty_update')) {
            $report_id = intval($_POST['report_id']);
            $status = sanitize_text_field($_POST['status']);
            $reward = intval($_POST['reward']);

            $wpdb->update(
                $table_name,
                ['status' => $status],
                ['id' => $report_id],
                ['%s'],
                ['%d']
            );

            if ($status === 'approved' && $reward > 0) {
                $report = $wpdb->get_row($wpdb->prepare("SELECT user_id FROM $table_name WHERE id = %d", $report_id));
                if ($report) {
                    $points = Ignis_Utilities::get_user_points($report->user_id) + $reward;
                    Ignis_Utilities::update_user_points($report->user_id, $points);
                    Ignis_Utilities::log_action($report->user_id, 'points', $reward, 'bug_bounty_reward', $report_id);
                }
            }

            echo '<div class="updated"><p>' . __('Report updated.', 'ignis-plugin') . '</p></div>';
        }

        // Fetch bug reports
        $reports = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC");

        ?>
        <div class="wrap ignis-bug-bounty">
            <h1><?php _e('Bug Bounty Management', 'ignis-plugin'); ?></h1>
            <?php if ($reports) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('ID', 'ignis-plugin'); ?></th>
                            <th><?php _e('User', 'ignis-plugin'); ?></th>
                            <th><?php _e('Title', 'ignis-plugin'); ?></th>
                            <th><?php _e('Description', 'ignis-plugin'); ?></th>
                            <th><?php _e('Screenshot', 'ignis-plugin'); ?></th>
                            <th><?php _e('Status', 'ignis-plugin'); ?></th>
                            <th><?php _e('Actions', 'ignis-plugin'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report) : ?>
                            <tr>
                                <td><?php echo esc_html($report->id); ?></td>
                                <td><?php echo esc_html(get_userdata($report->user_id)->user_login); ?></td>
                                <td><?php echo esc_html($report->title); ?></td>
                                <td><?php echo esc_html(wp_trim_words($report->description, 20)); ?></td>
                                <td>
                                    <?php if ($report->screenshot) : ?>
                                        <a href="<?php echo esc_url($report->screenshot); ?>" target="_blank"><?php _e('View', 'ignis-plugin'); ?></a>
                                    <?php else : ?>
                                        <?php _e('None', 'ignis-plugin'); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($report->status); ?></td>
                                <td>
                                    <form method="post">
                                        <?php wp_nonce_field('ignis_bug_bounty_update'); ?>
                                        <input type="hidden" name="report_id" value="<?php echo esc_attr($report->id); ?>">
                                        <select name="status">
                                            <option value="pending" <?php selected($report->status, 'pending'); ?>><?php _e('Pending', 'ignis-plugin'); ?></option>
                                            <option value="approved" <?php selected($report->status, 'approved'); ?>><?php _e('Approved', 'ignis-plugin'); ?></option>
                                            <option value="rejected" <?php selected($report->status, 'rejected'); ?>><?php _e('Rejected', 'ignis-plugin'); ?></option>
                                        </select>
                                        <input type="number" name="reward" value="0" min="0" style="width: 80px;">
                                        <button type="submit" name="ignis_bug_bounty_update" class="button"><?php _e('Update', 'ignis-plugin'); ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php _e('No bug reports found.', 'ignis-plugin'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
}

// Initialize module
new Ignis_Bug_Bounty_Admin();
?>
