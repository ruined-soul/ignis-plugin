<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Currency Admin
class Ignis_Currency_Admin {
    public function __construct() {
        add_filter('ignis_admin_submenus', [$this, 'register_admin_submenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_admin_submenu($submenus) {
        $submenus[] = [
            'title' => __('Currency Settings', 'ignis-plugin'),
            'menu_title' => __('Currency', 'ignis-plugin'),
            'capability' => 'manage_options',
            'slug' => 'ignis-currency',
            'callback' => [$this, 'admin_page_callback']
        ];
        return $submenus;
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'ignis-currency') !== false) {
            wp_enqueue_style('ignis-currency-admin', IGNIS_PLUGIN_URL . 'modules/currency/assets/admin.css', [], IGNIS_PLUGIN_VERSION);
        }
    }

    public function admin_page_callback() {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';
        ?>
        <div class="wrap">
            <h1><?php _e('Currency Settings', 'ignis-plugin'); ?></h1>
            <nav class="nav-tab-wrapper">
                <a href="?page=ignis-currency&tab=settings" class="nav-tab <?php echo $tab === 'settings' ? 'nav-tab-active' : ''; ?>"><?php _e('Settings', 'ignis-plugin'); ?></a>
                <a href="?page=ignis-currency&tab=transactions" class="nav-tab <?php echo $tab === 'transactions' ? 'nav-tab-active' : ''; ?>"><?php _e('Transactions', 'ignis-plugin'); ?></a>
            </nav>
            <?php
            switch ($tab) {
                case 'transactions':
                    $this->render_transactions_tab();
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
        $options = get_option('ignis_currency_settings', [
            'points_to_currency_ratio' => 100,
            'enable_conversions' => 1
        ]);

        if (isset($_POST['ignis_currency_settings']) && check_admin_referer('ignis_currency_settings')) {
            $options = array_merge($options, array_map('sanitize_text_field', $_POST['ignis_currency']));
            $options['enable_conversions'] = isset($_POST['ignis_currency']['enable_conversions']) ? 1 : 0;
            $options['points_to_currency_ratio'] = max(1, absint($options['points_to_currency_ratio']));
            update_option('ignis_currency_settings', $options);
            echo '<div class="updated"><p>' . __('Settings saved.', 'ignis-plugin') . '</p></div>';
        }

        ?>
        <form method="post">
            <?php wp_nonce_field('ignis_currency_settings'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="points_to_currency_ratio"><?php _e('Points to MangaCoin Ratio', 'ignis-plugin'); ?></label></th>
                    <td>
                        <input type="number" name="ignis_currency[points_to_currency_ratio]" id="points_to_currency_ratio" value="<?php echo esc_attr($options['points_to_currency_ratio']); ?>" min="1" class="small-text">
                        <p class="description"><?php _e('Number of points required to convert to 1 MangaCoin.', 'ignis-plugin'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label><?php _e('Enable Points to Currency Conversion', 'ignis-plugin'); ?></label></th>
                    <td><input type="checkbox" name="ignis_currency[enable_conversions]" <?php checked($options['enable_conversions'], 1); ?>></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <?php
    }

    private function render_transactions_tab() {
        global $wpdb;
        $log_table = $wpdb->prefix . 'ignis_logs';
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;

        $where = "type = 'currency'";
        if (isset($_GET['user_id']) && $_GET['user_id']) {
            $where .= $wpdb->prepare(" AND user_id = %d", intval($_GET['user_id']));
        }

        $transactions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $log_table WHERE $where ORDER BY timestamp DESC LIMIT %d OFFSET %d",
            $per_page,
            ($page - 1) * $per_page
        ));

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $log_table WHERE $where");

        ?>
        <h2><?php _e('Currency Transactions', 'ignis-plugin'); ?></h2>
        <form method="get">
            <input type="hidden" name="page" value="ignis-currency">
            <input type="hidden" name="tab" value="transactions">
            <label><?php _e('Filter by User ID:', 'ignis-plugin'); ?></label>
            <input type="number" name="user_id" value="<?php echo isset($_GET['user_id']) ? esc_attr($_GET['user_id']) : ''; ?>">
            <input type="submit" class="button" value="<?php _e('Filter', 'ignis-plugin'); ?>">
        </form>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('User ID', 'ignis-plugin'); ?></th>
                    <th><?php _e('Action', 'ignis-plugin'); ?></th>
                    <th><?php _e('Amount', 'ignis-plugin'); ?></th>
                    <th><?php _e('Timestamp', 'ignis-plugin'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction) : ?>
                    <tr>
                        <td><?php echo esc_html($transaction->user_id); ?></td>
                        <td><?php echo esc_html($transaction->action); ?></td>
                        <td><?php echo esc_html($transaction->amount); ?></td>
                        <td><?php echo esc_html($transaction->timestamp); ?></td>
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
        <?php
    }
}

// Initialize module
new Ignis_Currency_Admin();
?>
