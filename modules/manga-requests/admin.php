<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Manga Requests Admin
class Ignis_Manga_Requests_Admin {
    public function __construct() {
        add_filter('ignis_admin_submenus', [$this, 'register_admin_submenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_admin_submenu($submenus) {
        $submenus[] = [
            'title' => __('Manga Requests', 'ignis-plugin'),
            'menu_title' => __('Manga Requests', 'ignis-plugin'),
            'capability' => 'manage_options',
            'slug' => 'ignis-manga-requests',
            'callback' => [$this, 'admin_page_callback']
        ];
        return $submenus;
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'ignis-manga-requests') !== false) {
            wp_enqueue_style('ignis-requests-admin', IGNIS_PLUGIN_URL . 'modules/manga-requests/assets/admin.css', [], IGNIS_PLUGIN_VERSION);
            wp_enqueue_script('ignis-requests-admin', IGNIS_PLUGIN_URL . 'modules/manga-requests/assets/admin.js', ['jquery'], IGNIS_PLUGIN_VERSION, true);
        }
    }

    public function admin_page_callback() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ignis_manga_requests';
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        $request_id = isset($_GET['request_id']) ? absint($_GET['request_id']) : 0;

        if ($action === 'update' && $request_id && check_admin_referer('ignis_update_request_' . $request_id)) {
            $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'pending';
            $wpdb->update($table_name, ['status' => $status], ['id' => $request_id]);
            echo '<div class="updated"><p>' . __('Request updated.', 'ignis-plugin') . '</p></div>';
        }

        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $requests = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT %d OFFSET %d",
            $per_page,
            ($page - 1) * $per_page
        ));
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

        ?>
        <div class="wrap">
            <h1><?php _e('Manga Requests', 'ignis-plugin'); ?></h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('User ID', 'ignis-plugin'); ?></th>
                        <th><?php _e('Title', 'ignis-plugin'); ?></th>
                        <th><?php _e('Description', 'ignis-plugin'); ?></th>
                        <th><?php _e('Status', 'ignis-plugin'); ?></th>
                        <th><?php _e('Timestamp', 'ignis-plugin'); ?></th>
                        <th><?php _e('Actions', 'ignis-plugin'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request) : ?>
                        <tr>
                            <td><?php echo esc_html($request->user_id); ?></td>
                            <td><?php echo esc_html($request->title); ?></td>
                            <td><?php echo esc_html(wp_trim_words($request->description, 20)); ?></td>
                            <td><?php echo esc_html($request->status); ?></td>
                            <td><?php echo esc_html($request->timestamp); ?></td>
                            <td>
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=ignis-manga-requests&action=update&request_id=' . $request->id), 'ignis_update_request_' . $request->id)); ?>" class="button"><?php _e('Edit', 'ignis-plugin'); ?></a>
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
            <?php if ($action === 'update' && $request_id) : ?>
                <?php $request = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $request_id)); ?>
                <h2><?php _e('Update Request', 'ignis-plugin'); ?></h2>
                <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=ignis-manga-requests&action=update&request_id=' . $request_id . '&_wpnonce=' . wp_create_nonce('ignis_update_request_' . $request_id))); ?>">
                    <table class="form-table">
                        <tr>
                            <th><label><?php _e('Title', 'ignis-plugin'); ?></label></th>
                            <td><input type="text" value="<?php echo esc_attr($request->title); ?>" disabled></td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Description', 'ignis-plugin'); ?></label></th>
                            <td><textarea disabled rows="5"><?php echo esc_textarea($request->description); ?></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="status"><?php _e('Status', 'ignis-plugin'); ?></label></th>
                            <td>
                                <select name="status" id="status">
                                    <option value="pending" <?php selected($request->status, 'pending'); ?>><?php _e('Pending', 'ignis-plugin'); ?></option>
                                    <option value="approved" <?php selected($request->status, 'approved'); ?>><?php _e('Approved', 'ignis-plugin'); ?></option>
                                    <option value="rejected" <?php selected($request->status, 'rejected'); ?>><?php _e('Rejected', 'ignis-plugin'); ?></option>
                                    <option value="fulfilled" <?php selected($request->status, 'fulfilled'); ?>><?php _e('Fulfilled', 'ignis-plugin'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(__('Update Status', 'ignis-plugin')); ?>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }
}

// Initialize module
new Ignis_Manga_Requests_Admin();
?>
