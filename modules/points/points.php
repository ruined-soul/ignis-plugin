<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Points Module
class Ignis_Points {
    public function __construct() {
        // Check if module is enabled
        $general_settings = get_option('ignis_general_settings', ['enable_points' => 1]);
        if (!$general_settings['enable_points']) {
            return;
        }

        // Register hooks
        add_action('wp_manga_after_chapter_read', [$this, 'award_chapter_points'], 10, 2);
        add_action('comment_post', [$this, 'award_comment_points'], 10, 3);
        add_action('wp_login', [$this, 'award_login_points'], 10, 2);
        add_action('user_register', [$this, 'award_signup_points'], 10, 2);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_shortcode('ignis_scoreboard', [$this, 'scoreboard_shortcode']);
        add_action('wp_footer', [$this, 'render_toast']);
    }

    public function award_chapter_points($chapter, $manga_id) {
        if (!is_user_logged_in()) {
            return;
        }

        $user_id = get_current_user_id();
        $options = get_option('ignis_points_settings', [
            'points_per_chapter' => 2,
            'points_per_chapter_extra' => 1,
            'chapter_lifetime_cap' => 4,
            'enable_chapter_points' => 1,
            'daily_action_limit' => 1
        ]);

        if (!$options['enable_chapter_points']) {
            return;
        }

        global $wpdb;
        $log_table = $wpdb->prefix . 'ignis_logs';
        $chapter_id = $chapter['chapter_id'];

        // Check lifetime cap
        $total_points = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM $log_table WHERE user_id = %d AND type = 'points' AND action = 'chapter_read' AND meta_key = %s",
            $user_id,
            "chapter_$chapter_id"
        ));

        if ($total_points >= $options['chapter_lifetime_cap']) {
            return;
        }

        // Check daily limit
        $today = date('Y-m-d');
        $daily_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $log_table WHERE user_id = %d AND type = 'points' AND action = 'chapter_read' AND DATE(timestamp) = %s",
            $user_id,
            $today
        ));

        if ($daily_count >= $options['daily_action_limit']) {
            return;
        }

        // Determine points
        $read_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $log_table WHERE user_id = %d AND type = 'points' AND action = 'chapter_read' AND meta_key = %s",
            $user_id,
            "chapter_$chapter_id"
        ));

        $points = $read_count == 0 ? $options['points_per_chapter'] : $options['points_per_chapter_extra'];

        if ($read_count >= 3) {
            return; // Max 3 reads (1st + 2nd + 3rd)
        }

        // Award points
        $this->award_points($user_id, $points, 'chapter_read', "chapter_$chapter_id");
        $this->show_toast($points, __('reading chapter', 'ignis-plugin'));
    }

    public function award_comment_points($comment_id, $comment_approved, $commentdata) {
        if (!is_user_logged_in() || $comment_approved !== 1) {
            return;
        }

        $user_id = get_current_user_id();
        $options = get_option('ignis_points_settings', [
            'points_per_comment' => 1,
            'comment_lifetime_cap' => 3,
            'enable_comment_points' => 1,
            'daily_action_limit' => 1
        ]);

        if (!$options['enable_comment_points']) {
            return;
        }

        global $wpdb;
        $log_table = $wpdb->prefix . 'ignis_logs';
        $post_id = $commentdata['comment_post_ID'];

        // Check lifetime cap
        $total_points = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM $log_table WHERE user_id = %d AND type = 'points' AND action = 'comment' AND meta_key = %s",
            $user_id,
            "post_$post_id"
        ));

        if ($total_points >= $options['comment_lifetime_cap']) {
            return;
        }

        // Check daily limit
        $today = date('Y-m-d');
        $daily_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $log_table WHERE user_id = %d AND type = 'points' AND action = 'comment' AND DATE(timestamp) = %s",
            $user_id,
            $today
        ));

        if ($daily_count >= $options['daily_action_limit']) {
            return;
        }

        // Award points
        $this->award_points($user_id, $options['points_per_comment'], 'comment', "post_$post_id");
        $this->show_toast($options['points_per_comment'], __('commenting', 'ignis-plugin'));
    }

    public function award_login_points($user_login, $user) {
        $user_id = $user->ID;
        $options = get_option('ignis_points_settings', [
            'points_per_login' => 3,
            'points_per_streak' => 10,
            'enable_login_points' => 1,
            'enable_streak_points' => 1
        ]);

        if (!$options['enable_login_points']) {
            return;
        }

        global $wpdb;
        $log_table = $wpdb->prefix . 'ignis_logs';
        $today = date('Y-m-d');

        // Check if already awarded today
        $last_login = $wpdb->get_var($wpdb->prepare(
            "SELECT timestamp FROM $log_table WHERE user_id = %d AND type = 'points' AND action = 'login' ORDER BY timestamp DESC LIMIT 1",
            $user_id
        ));

        if ($last_login && date('Y-m-d', strtotime($last_login)) === $today) {
            return;
        }

        // Award login points
        $this->award_points($user_id, $options['points_per_login'], 'login');
        $this->show_toast($options['points_per_login'], __('logging in', 'ignis-plugin'));

        // Check streak
        if ($options['enable_streak_points']) {
            $streak = (int) get_user_meta($user_id, 'ignis_login_streak', true);
            $last_streak_date = get_user_meta($user_id, 'ignis_last_login_date', true);

            if ($last_streak_date && date('Y-m-d', strtotime($last_streak_date . ' +1 day')) === $today) {
                $streak++;
            } else {
                $streak = 1;
            }

            update_user_meta($user_id, 'ignis_login_streak', $streak);
            update_user_meta($user_id, 'ignis_last_login_date', $today);

            if ($streak === 7) {
                $this->award_points($user_id, $options['points_per_streak'], 'streak');
                $this->show_toast($options['points_per_streak'], __('weekly login streak', 'ignis-plugin'));
                update_user_meta($user_id, 'ignis_login_streak', 0);
            }
        }
    }

    public function award_signup_points($user_id, $meta = []) {
        $options = get_option('ignis_points_settings', [
            'points_per_signup' => 100,
            'points_per_signup_referral' => 50,
            'enable_signup_points' => 1
        ]);

        if (!$options['enable_signup_points']) {
            return;
        }

        // Award signup points
        $this->award_points($user_id, $options['points_per_signup'], 'signup');
        $this->show_toast($options['points_per_signup'], __('signing up', 'ignis-plugin'));

        // Check referral
        // Note: Referral logic requires User Engagement module; placeholder for integration
    }

    private function award_points($user_id, $points, $action, $meta_key = null) {
        global $wpdb;
        $current_points = (int) get_user_meta($user_id, 'ignis_points', true);
        $new_points = $current_points + $points;
        update_user_meta($user_id, 'ignis_points', $new_points);

        $log_data = [
            'user_id' => $user_id,
            'type' => 'points',
            'amount' => $points,
            'action' => $action,
            'timestamp' => current_time('mysql'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ];

        if ($meta_key) {
            $log_data['meta_key'] = $meta_key;
        }

        $wpdb->insert($wpdb->prefix . 'ignis_logs', $log_data);

        // Check milestone
        $milestones = [100, 500, 1000, 5000];
        $options = get_option('ignis_points_settings', ['milestone_message' => 'Congrats! You’ve earned {points} points!']);
        foreach ($milestones as $milestone) {
            if ($new_points >= $milestone && $current_points < $milestone) {
                $message = str_replace('{points}', $milestone, $options['milestone_message']);
                set_transient('ignis_toast_' . $user_id, ['message' => $message, 'type' => 'milestone'], 30);
            }
        }
    }

    private function show_toast($points, $action) {
        if (!is_user_logged_in()) {
            return;
        }

        $options = get_option('ignis_points_settings', ['toast_message' => '+{points} points for {action}!']);
        $message = str_replace(['{points}', '{action}'], [$points, $action], $options['toast_message']);
        set_transient('ignis_toast_' . get_current_user_id(), ['message' => $message, 'type' => 'points'], 30);
    }

    public function enqueue_assets() {
        wp_enqueue_style('ignis-points-css', IGNIS_PLUGIN_URL . 'modules/points/assets/points.css', [], IGNIS_PLUGIN_VERSION);
        wp_enqueue_script('ignis-points-js', IGNIS_PLUGIN_URL . 'modules/points/assets/points.js', ['jquery'], IGNIS_PLUGIN_VERSION, true);
        wp_localize_script('ignis-points-js', 'ignis_points', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ignis_points')
        ]);
    }

    public function scoreboard_shortcode($atts) {
        $atts = shortcode_atts(['limit' => 10], $atts, 'ignis_scoreboard');
        $users = get_users([
            'meta_key' => 'ignis_points',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'number' => (int) $atts['limit']
        ]);

        ob_start();
        ?>
        <div class="ignis-scoreboard">
            <h2><?php _e('Top Users', 'ignis-plugin'); ?></h2>
            <ul>
                <?php foreach ($users as $index => $user) : ?>
                    <li>
                        <span class="rank"><?php echo $index + 1; ?>.</span>
                        <span class="username"><?php echo esc_html($user->user_login); ?></span>
                        <span class="points"><?php echo esc_html((int) get_user_meta($user->ID, 'ignis_points', true)); ?> 🏆</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_toast() {
        if (!is_user_logged_in()) {
            return;
        }

        $user_id = get_current_user_id();
        $toast = get_transient('ignis_toast_' . $user_id);
        if ($toast) {
            ?>
            <div class="ignis-toast ignis-toast-<?php echo esc_attr($toast['type']); ?>">
                <?php echo esc_html($toast['message']); ?>
            </div>
            <?php
            delete_transient('ignis_toast_' . $user_id);
        }
    }
}

// Initialize module
new Ignis_Points();
?>
