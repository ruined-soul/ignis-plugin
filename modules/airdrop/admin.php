<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Airdrop Admin
class Ignis_Airdrop_Admin {
    public function __construct() {
        add_filter('ignis_admin_submenus', [$this, 'register_admin_submenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_admin_submenu($submenus) {
        $submenus[] = [
            'title' => __('Airdrop Settings', 'ignis-plugin'),
            'menu_title' => __('Airdrop', 'ignis-plugin'),
            'capability' => 'manage_options',
            'slug' => 'ignis-airdrop',
            'callback' => [$this, 'admin_page_callback']
        ];
        return $submenus;
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'ignis-airdrop') !== false) {
            wp_enqueue_style('ignis-airdrop-admin', IGNIS_PLUGIN_URL . 'modules/airdrop/assets/admin.css', [], IGNIS_PLUGIN_VERSION);
            wp_enqueue_script('ignis-airdrop-admin', IGNIS_PLUGIN_URL . 'modules/airdrop/assets/admin.js', ['jquery'], IGNIS_PLUGIN_VERSION, true);
        }
    }

    public function admin_page_callback() {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'campaigns';
        ?>
        <div class="wrap">
            <h1><?php _e('Airdrop Settings', 'ignis-plugin'); ?></h1>
            <nav class="nav-tab-wrapper">
                <a href="?page=ignis-airdrop&tab=campaigns" class="nav-tab <?php echo $tab === 'campaigns' ? 'nav-tab-active' : ''; ?>"><?php _e('Campaigns', 'ignis-plugin'); ?></a>
                <a href="?page=ignis-airdrop&tab=claims" class="nav-tab <?php echo $tab === 'claims' ? 'nav-tab-active' : ''; ?>"><?php _e('Claims', 'ignis-plugin'); ?></a>
            </nav>
            <?php
            switch ($tab) {
                case 'claims':
                    $this->render_claims_tab();
                    break;
                default:
                    $this->render_campaigns_tab();
                    break;
            }
            ?>
        </div>
        <?php
    }

    private function render_campaigns_tab() {
        $campaigns = get_option('ignis_airdrop_campaigns', []);

        if (isset($_POST['ignis_airdrop_campaigns']) && check_admin_referer('ignis_airdrop_campaigns')) {
            $new_campaigns = [];
            foreach ($_POST['ignis_campaigns'] as $index => $campaign) {
                $new_campaigns[$index] = [
                    'name' => sanitize_text_field($campaign['name']),
                    'reward_type' => sanitize_text_field($campaign['reward_type']),
                    'reward_amount' => absint($campaign['reward_amount']),
                    'start_date' => sanitize_text_field($campaign['start_date']),
                    'end_date' => sanitize_text_field($campaign['end_date']),
                    'requires_shortener' => isset($campaign['requires_shortener']) ? 1 : 0,
                    'enabled' => isset($campaign['enabled']) ? 1 : 0
                ];
            }
            update_option('ignis_airdrop_campaigns', $new_campaigns);
            echo '<div class="updated"><p>' . __('Campaigns saved.', 'ignis-plugin') . '</p></div>';
        }

        ?>
        <form method="post">
            <?php wp_nonce_field('ignis_airdrop_campaigns'); ?>
            <table class="form-table ignis-airdrop-campaigns">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'ignis-plugin'); ?></th>
                        <th><?php _e('Reward Type', 'ignis-plugin'); ?></th>
                        <th><?php _e('Amount', 'ignis-plugin'); ?></th>
                        <th><?php _e('Start Date', 'ignis-plugin'); ?></th>
                        <th><?php _e('End Date', 'ignis-plugin'); ?></th>
                        <th><?php _e('Requires Shortener', 'ignis-plugin'); ?></th>
                        <th><?php _e('Enabled', 'ignis-plugin'); ?></th>
                        <th><?php _e('Actions', 'ignis-plugin'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($campaigns as $index => $campaign) : ?>
                        <tr>
                            <td><input type="text" name="ignis_campaigns[<?php echo $index; ?>][name]" value="<?php echo esc_attr($campaign['name']); ?>" required></td>
                            <td>
                                <select name="ignis_campaigns[<?php echo $index; ?>][reward_type]">
                                    <option value="points" <?php selected($campaign['reward_type'], 'points'); ?>><?php _e('Points', 'ignis-plugin'); ?></option>
                                    <option value="currency" <?php selected($campaign['reward_type'], 'currency'); ?>><?php _e('MangaCoin', 'ignis-plugin'); ?></option>
                                </select>
                            </td>
                            <td><input type="number" name="ignis_campaigns[<?php echo $index; ?>][reward_amount]" value="<?php echo esc_attr($campaign['reward_amount']); ?>" min="1" required></td>
                            <td><input type="datetime-local" name="ignis_campaigns[<?php echo $index; ?>][start_date]" value="<?php echo esc_attr($campaign['start_date']); ?>" required></td>
                            <td><input type="datetime-local" name="ignis_campaigns[<?php echo $index; ?>][end_date]" value="<?php echo esc_attr($campaign['end_date']); ?>" required></td>
                            <td><input type="checkbox" name="ignis_campaigns[<?php echo $index; ?>][requires_shortener]" <?php checked($campaign['requires_shortener'], 1); ?>></td>
                            <td><input type="checkbox" name="ignis_campaigns[<?php echo $index; ?>][enabled]" <?php checked($campaign['enabled'], 1); ?>></td>
                            <td><button type="button" class="button ignis-remove-campaign"><?php _e('Remove', 'ignis-plugin'); ?></button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="button" class="button ignis-add-campaign"><?php _e('Add Campaign', 'ignis-plugin'); ?></button>
            <?php submit_button(); ?>
        </form>
        <?php
    }

    private function render_claims_tab() {
        global $wpdb;
        $log_table = $wpdb->prefix . 'ignis_logs';
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;

        $where = "type IN ('points', 'currency') AND action = 'airdrop_claim'";
        if (isset($_GET['user_id']) && $_GET['user_id']) {
            $where .= $wpdb->prepare(" AND user_id = %d", intval($_GET['user_id']));
        }

        $claims = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $log_table WHERE $where ORDER BY timestamp DESC LIMIT %d OFFSET %d",
            $per_page,
            ($page - 1) * $per_page
        ));

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $log_table WHERE $where");

        ?>
        <h2><?php _e('Airdrop Claims', 'ignis-plugin'); ?></h2>
        <form method="get">
            <input type="hidden" name="page" value="ignis-airdrop">
            <input type="hidden" name="tab" value="claims">
            <label><?php _e('Filter by User ID:', 'ignis-plugin'); ?></label>
            <input type="number" name="user_id" value="<?php echo isset($_GET['user_id']) ? esc_attr($_GET['user_id']) : ''; ?>">
            <input type="submit" class="button" value="<?php _e('Filter', 'ignis-plugin'); ?>">
        </form>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('User ID', 'ignis-plugin'); ?></th>
                    <th><?php _e('Type', 'ignis-plugin'); ?></th>
                    <th><?php _e('Amount', 'ignis-plugin'); ?></th>
                    <th><?php _e('Airdrop ID', 'ignis-plugin'); ?></th>
                    <th><?php _e('Timestamp', 'ignis-plugin'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($claims as $claim) : ?>
                    <tr>
                        <td><?php echo esc_html($claim->user_id); ?></td>
                        <td><?php echo esc_html($claim->type); ?></td>
                        <td><?php echo esc_html($claim->amount); ?></td>
                        <td><?php echo esc_html($claim->meta_key); ?></td>
                        <td><?php echo esc_html($claim->timestamp); ?></td>
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
                        <span class="current-page"><?php printf(__('Page %d of %d', 'ignis-plugin'), $page, $total_pages); ?></span>
                        <?php if ($page < $total_pages) : ?>
                            <a class="next-page" href="<?php echo esc_url(add_query_arg('paged', $page + 1)); ?>">»</a>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>
        <?php
    }
}

// Initialize module
new Ignis_Airdrop_Admin();
?>
