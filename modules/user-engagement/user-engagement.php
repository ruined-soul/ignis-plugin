<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// User Engagement Module
class Ignis_User_Engagement {
    public function __construct() {
        // Check if module is enabled
        $general_settings = get_option('ignis_general_settings', ['enable_referrals' => 1, 'enable_bug_bounty' => 1]);
        if (!$general_settings['enable_referrals'] && !$general_settings['enable_bug_bounty']) {
            return;
        }

        // Register hooks
        add_action('user_register', [$this, 'handle_referral_signup'], 10, 2);
        add_action('wp_ajax_ignis_submit_bug', [$this, 'submit_bug_report']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_shortcode('ignis_referral_link', [$this, 'referral_link_shortcode']);
        add_action('ignis_bug_status_updated', [$this, 'award_bug_approval_points'], 10, 2);
    }

    public function handle_referral_signup($user_id, $meta = []) {
        if (!isset($_COOKIE['ignis_referral_code'])) {
            return;
        }

        $options = get_option('ignis_points_settings', [
            'points_per_referral' => 100,
            'points_per_signup_referral' => 50,
            'points_per_referral_bonus' => 200,
            'enable_referral_points' => 1
        ]);

        if (!$options['enable_referral_points']) {
            return;
        }

        global $wpdb;
        $referral_code = sanitize_text_field($_COOKIE['ignis_referral_code']);
        $referrer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ignis_referrals WHERE referral_code = %s AND status = 'pending'",
            $referral_code
        ));

        if ($referrer) {
            // Award points to referred user
            Ignis_Utilities::update_user_points($user_id, Ignis_Utilities::get_user_points($user_id) + $options['points_per_signup_referral']);
            Ignis_Utilities::log_action($user_id, 'points', $options['points_per_signup_referral'], 'referral_signup', $referral_code);

            // Award points to referrer
            Ignis_Utilities::update_user_points($referrer->user_id, Ignis_Utilities::get_user_points($referrer->user_id) + $options['points_per_referral']);
            Ignis_Utilities::log_action($referrer->user_id, 'points', $options['points_per_referral'], 'referral', $referral_code);

            // Update referral status
            $wpdb->update(
                $wpdb->prefix . 'ignis_referrals',
                ['referred_user_id' => $user_id, 'status' => 'completed', 'timestamp' => current_time('mysql')],
                ['id' => $referrer->id]
            );

            // Check for referral bonus (3rd referral)
            $referral_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}ignis_referrals WHERE user_id = %d AND status = 'completed'",
                $referrer->user_id
            ));

            if ($referral_count == 3) {
                Ignis_Utilities::update_user_points($referrer->user_id, Ignis_Utilities::get_user_points($referrer->user_id) + $options['points_per_referral_bonus']);
                Ignis_Utilities::log_action($referrer->user_id, 'points', $options['points_per_referral_bonus'], 'referral_bonus');
            }
        }
    }

    public function submit_bug_report() {
        check_ajax_referer('ignis_user_engagement', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in.', 'ignis-plugin')]);
        }

        $user_id = get_current_user_id();
        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description']);
        $screenshot = '';

        // Handle screenshot upload
        if (!empty($_FILES['screenshot'])) {
            $upload = wp_handle_upload($_FILES['screenshot'], ['test_form' => false]);
            if ($upload && !isset($upload['error'])) {
                $screenshot = $upload['url'];
            }
        }

        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'ignis_bug_reports', [
            'user_id' => $user_id,
            'title' => $title,
            'description' => $description,
            'screenshot' => $screenshot,
            'status' => 'pending',
            'timestamp' => current_time('mysql')
        ]);

        $options = get_option('ignis_points_settings', ['points_per_bug_submit' => 5, 'enable_bug_points' => 1]);
        if ($options['enable_bug_points']) {
            Ignis_Utilities::update_user_points($user_id, Ignis_Utilities::get_user_points($user_id) + $options['points_per_bug_submit']);
            Ignis_Utilities::log_action($user_id, 'points', $options['points_per_bug_submit'], 'bug_submit');
            set_transient('ignis_toast_' . $user_id, ['message' => sprintf(__('+5 points for submitting a bug report!', 'ignis-plugin')), 'type' => 'points'], 30);
        }

        wp_send_json_success(['message' => __('Bug report submitted.', 'ignis-plugin')]);
    }

    public function award_bug_approval_points($bug_id, $status) {
        if ($status !== 'approved') {
            return;
        }

        global $wpdb;
        $bug = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ignis_bug_reports WHERE id = %d",
            $bug_id
        ));

        if (!$bug) {
            return;
        }

        $options = get_option('ignis_points_settings', ['points_per_bug_approved' => 50, 'enable_bug_points' => 1]);
        if ($options['enable_bug_points']) {
            Ignis_Utilities::update_user_points($bug->user_id, Ignis_Utilities::get_user_points($bug->user_id) + $options['points_per_bug_approved']);
            Ignis_Utilities::log_action($bug->user_id, 'points', $options['points_per_bug_approved'], 'bug_approved');
            set_transient('ignis_toast_' . $bug->user_id, ['message' => sprintf(__('+50 points for approved bug report!', 'ignis-plugin')), 'type' => 'points'], 30);
        }
    }

    public function enqueue_assets() {
        wp_enqueue_style('ignis-user-engagement', IGNIS_PLUGIN_URL . 'modules/user-engagement/assets/user-engagement.css', [], IGNIS_PLUGIN_VERSION);
        wp_enqueue_script('ignis-user-engagement', IGNIS_PLUGIN_URL . 'modules/user-engagement/assets/user-engagement.js', ['jquery'], IGNIS_PLUGIN_VERSION, true);
        wp_localize_script('ignis-user-engagement', 'ignis_engagement', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ignis_user_engagement')
        ]);
    }

    public function referral_link_shortcode($atts) {
        if (!is_user_logged_in()) {
            return __('Please log in to view your referral link.', 'ignis-plugin');
        }

        $user_id = get_current_user_id();
        global $wpdb;
        $referral = $wpdb->get_row($wpdb->prepare(
            "SELECT referral_code FROM {$wpdb->prefix}ignis_referrals WHERE user_id = %d",
            $user_id
        ));

        if (!$referral) {
            $code = Ignis_Utilities::generate_referral_code($user_id);
            $wpdb->insert($wpdb->prefix . 'ignis_referrals', [
                'user_id' => $user_id,
                'referral_code' => $code,
                'status' => 'pending',
                'timestamp' => current_time('mysql')
            ]);
            $referral_code = $code;
        } else {
            $referral_code = $referral->referral_code;
        }

        $link = add_query_arg('ref', $referral_code, home_url('/register'));
        ob_start();
        ?>
        <div class="ignis-referral-link">
            <p><?php _e('Your referral link:', 'ignis-plugin'); ?></p>
            <input type="text" value="<?php echo esc_url($link); ?>" readonly>
            <button class="ignis-copy-link"><?php _e('Copy', 'ignis-plugin'); ?></button>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize module
new Ignis_User_Engagement();
?>
