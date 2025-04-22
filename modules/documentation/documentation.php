<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Documentation Module
class Ignis_Documentation {
    public function __construct() {
        // Check if module is enabled
        $general_settings = get_option('ignis_general_settings', ['enable_documentation' => 1]);
        if (!$general_settings['enable_documentation']) {
            return;
        }

        // Register hooks
        add_filter('ignis_admin_submenus', [$this, 'register_admin_submenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_admin_submenu($submenus) {
        $submenus[] = [
            'title' => __('Documentation', 'ignis-plugin'),
            'menu_title' => __('Documentation', 'ignis-plugin'),
            'capability' => 'manage_options',
            'slug' => 'ignis-documentation',
            'callback' => [$this, 'admin_page_callback']
        ];
        return $submenus;
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'ignis-documentation') !== false) {
            wp_enqueue_style('ignis-documentation', IGNIS_PLUGIN_URL . 'modules/documentation/assets/documentation.css', [], IGNIS_PLUGIN_VERSION);
            wp_enqueue_script('ignis-documentation', IGNIS_PLUGIN_URL . 'modules/documentation/assets/documentation.js', ['jquery'], IGNIS_PLUGIN_VERSION, true);
        }
    }

    public function admin_page_callback() {
        ?>
        <div class="wrap ignis-documentation">
            <h1><?php _e('Ignis Plugin Documentation', 'ignis-plugin'); ?></h1>
            
            <h2><?php _e('Setup and Installation', 'ignis-plugin'); ?></h2>
            <div class="ignis-doc-section">
                <h3 class="ignis-doc-toggle"><?php _e('1. Installation', 'ignis-plugin'); ?></h3>
                <div class="ignis-doc-content">
                    <p><?php _e('1. Download the Ignis Plugin from the official repository or upload via WordPress.', 'ignis-plugin'); ?></p>
                    <p><?php _e('2. Activate the plugin through the Plugins menu in WordPress.', 'ignis-plugin'); ?></p>
                    <p><?php _e('3. Ensure the WP Manga plugin (Madara theme) is installed and activated.', 'ignis-plugin'); ?></p>
                    <p><?php _e('4. Configure general settings under Ignis > Settings.', 'ignis-plugin'); ?></p>
                </div>

                <h3 class="ignis-doc-toggle"><?php _e('2. Database Setup', 'ignis-plugin'); ?></h3>
                <div class="ignis-doc-content">
                    <p><?php _e('The plugin automatically creates the following database tables on activation:', 'ignis-plugin'); ?></p>
                    <ul>
                        <li><code>wp_ignis_logs</code>: Tracks user actions (points, currency, etc.).</li>
                        <li><code>wp_ignis_referrals</code>: Manages referral codes and statuses.</li>
                        <li><code>wp_ignis_bug_reports</code>: Stores bug report submissions.</li>
                        <li><code>wp_ignis_manga_requests</code>: Stores manga translation requests.</li>
                    </ul>
                    <p><?php _e('If tables are not created, check file permissions and enable WP_DEBUG.', 'ignis-plugin'); ?></p>
                </div>

                <h3 class="ignis-doc-toggle"><?php _e('3. Module Configuration', 'ignis-plugin'); ?></h3>
                <div class="ignis-doc-content">
                    <p><?php _e('Enable/disable modules in Ignis > Settings. Configure each module:', 'ignis-plugin'); ?></p>
                    <ul>
                        <li><strong>Points</strong>: Set earning limits and rewards (Ignis > Points).</li>
                        <li><strong>Currency</strong>: Configure MangaCoin settings (Ignis > Currency).</li>
                        <li><strong>Username Change</strong>: Set costs and limits (Ignis > Username).</li>
                        <li><strong>Custom Themes</strong>: Manage theme purchases (Ignis > Themes).</li>
                        <li><strong>Manga Requests</strong>: Review translation requests (Ignis > Manga Requests).</li>
                        <li><strong>Airdrop</strong>: Set up campaigns and rewards (Ignis > Airdrop).</li>
                        <li><strong>Bug Bounty</strong>: Manage bug reports and rewards (Ignis > Bug Bounty).</li>
                        <li><strong>Shortener Link</strong>: Configure URL shortening API (Ignis > Shortener).</li>
                        <li><strong>Chatroom Link</strong>: Set up chat service API (Ignis > Chatroom).</li>
                        <li><strong>Backup Restore</strong>: Schedule backups and restore data (Ignis > Backup).</li>
                    </ul>
                </div>
            </div>

            <h2><?php _e('Usage Guide', 'ignis-plugin'); ?></h2>
            <div class="ignis-doc-section">
                <h3 class="ignis-doc-toggle"><?php _e('1. User Features', 'ignis-plugin'); ?></h3>
                <div class="ignis-doc-content">
                    <p><?php _e('Users can:', 'ignis-plugin'); ?></p>
                    <ul>
                        <li><?php _e('Earn points/MangaCoin through referrals, airdrops, and bug bounties.', 'ignis-plugin'); ?></li>
                        <li><?php _e('Change usernames using points/currency.', 'ignis-plugin'); ?></li>
                        <li><?php _e('Purchase custom themes.', 'ignis-plugin'); ?></li>
                        <li><?php _e('Request manga translations.', 'ignis-plugin'); ?></li>
                        <li><?php _e('Join chatrooms via invite links.', 'ignis-plugin'); ?></li>
                        <li><?php _e('Shorten URLs for airdrop tasks.', 'ignis-plugin'); ?></li>
                    </ul>
                </div>

                <h3 class="ignis-doc-toggle"><?php _e('2. Admin Features', 'ignis-plugin'); ?></h3>
                <div class="ignis-doc-content">
                    <p><?php _e('Admins can:', 'ignis-plugin'); ?></p>
                    <ul>
                        <li><?php _e('Review and approve manga requests and bug reports.', 'ignis-plugin'); ?></li>
                        <li><?php _e('Manage airdrop campaigns and validate submissions.', 'ignis-plugin'); ?></li>
                        <li><?php _e('Configure APIs for shortener and chatroom services.', 'ignis-plugin'); ?></li>
                        <li><?php _e('Schedule and restore backups.', 'ignis-plugin'); ?></li>
                        <li><?php _e('Monitor user points, currency, and logs.', 'ignis-plugin'); ?></li>
                    </ul>
                </div>
            </div>

            <h2><?php _e('Shortcodes', 'ignis-plugin'); ?></h2>
            <div class="ignis-doc-section">
                <h3 class="ignis-doc-toggle"><?php _e('1. Points Module', 'ignis-plugin'); ?></h3>
                <div class="ignis-doc-content">
                    <ul>
                        <li><code>[ignis_points_balance]</code>: Displays the user’s points balance.</li>
                        <li><code>[ignis_points_history]</code>: Shows the user’s points transaction history.</li>
                    </ul>
                </div>

                <h3 class="ignis-doc-toggle"><?php _e('2. Currency Module', 'ignis-plugin'); ?></h3>
                <div class="ignis-doc-content">
                    <ul>
                        <li><code>[ignis_currency_balance]</code>: Displays the user’s MangaCoin balance.</li>
                        <li><code>[ignis_currency_history]</code>: Shows the user’s currency transaction history.</li>
                    </ul>
                </div>

                <h3 class="ignis-doc-toggle"><?php _e('3. Username Change Module', 'ignis-plugin'); ?></h3>
                <div class="ignis-doc-content">
                    <ul>
                        <li><code>[ignis_username_change]</code>: Displays a form to change the user’s username.</li>
                    </ul>
                </div>

                <h3 class="ignis-doc-toggle"><?php _e('4. Custom Themes Module', 'ignis-plugin'); ?></h3>
                <div class="ignis-doc-content">
                    <ul>
                        <li><code>[ignis_custom_themes]</code>: Displays available themes for purchase.</li>
                    </ul>
                </div>

                <h3 class="ignis-doc-toggle"><?php _e('5. Manga Requests Module', 'ignis-plugin'); ?></h3>
                <div class="ignis-doc-content">
                    <ul>
                        <li><code>[ignis_manga_request_form]</code>: Displays a form to submit manga translation requests.</li>
                    </ul>
                </div>

                <h3 class="ignis-doc-toggle"><?php _e('6. Airdrop Module', 'ignis-plugin'); ?></h3>
                <div class="ignis-doc-content">
                    <ul>
                        <li><code>[ignis_airdrop]</code>: Displays available airdrop campaigns and submission form.</li>
                    </ul>
                </div>

                <h3 class="ignis-doc-toggle"><?php _e('7. Bug Bounty Module', 'ignis-plugin'); ?></h3>
                <div class="ignis-doc-content">
                    <ul>
                        <li><code>[ignis_bug_report_form]</code>: Displays a form to submit bug reports.</li>
                    </ul>
                </div>

                <h3 class="ignis-doc-toggle"><?php _e('8. Shortener Link Module', 'ignis-plugin'); ?></h3>
                <div class="ignis-doc-content">
                    <ul>
                        <li><code>[ignis_shortener_form]</code>: Displays a form to shorten URLs.</li>
                    </ul>
                </div>

                <h3 class="ignis-doc-toggle"><?php _e('9. Chatroom Link Module', 'ignis-plugin'); ?></h3>
                <div class="ignis-doc-content">
                    <ul>
                        <li><code>[ignis_chatroom_link]</code>: Displays a button to generate a chatroom invite link.</li>
                    </ul>
                </div>

                <h3 class="ignis-doc-toggle"><?php _e('10. Backup Restore Module', 'ignis-plugin'); ?></h3>
                <div class="ignis-doc-content">
                    <ul>
                        <li><code>[ignis_backup_dashboard]</code>: Displays the backup and restore dashboard (admin-only).</li>
                        <li><code>[ignis_backup_settings]</code>: Displays the backup settings page (admin-only).</li>
                    </ul>
                </div>
            </div>

            <h2><?php _e('Troubleshooting', 'ignis-plugin'); ?></h2>
            <div class="ignis-doc-section">
                <p><?php _e('1. <strong>Plugin Activation Error</strong>: Enable WP_DEBUG in wp-config.php and check /wp-content/debug.log.', 'ignis-plugin'); ?></p>
                <p><?php _e('2. <strong>Missing Tables</strong>: Verify database permissions and re-run activation.', 'ignis-plugin'); ?></p>
                <p><?php _e('3. <strong>WP Manga Dependency</strong>: Install and activate the WP Manga plugin.', 'ignis-plugin'); ?></p>
                <p><?php _e('4. <strong>Contact Support</strong>: Reach out to @IgnisReborn on Telegram or https://wordpress.org/support/.', 'ignis-plugin'); ?></p>
            </div>
        </div>
        <?php
    }
}

// Initialize module
new Ignis_Documentation();
?>
