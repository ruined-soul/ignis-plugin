<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$general_settings = get_option('ignis_general_settings', ['enable_points' => 1]);
if (!$general_settings['enable_points']) {
    echo '<p>' . __('Points system is currently disabled.', 'ignis-plugin') . '</p>';
    return;
}

$atts = shortcode_atts(['limit' => 10], $atts ?? []);
$limit = max(1, min(100, intval($atts['limit'])));

global $wpdb;
$top_users = $wpdb->get_results($wpdb->prepare(
    "SELECT u.ID, u.user_login, um.meta_value as points
     FROM {$wpdb->users} u
     JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
     WHERE um.meta_key = 'ignis_points'
     ORDER BY CAST(um.meta_value AS UNSIGNED) DESC
     LIMIT %d",
    $limit
));

$emojis = ['🥇', '🥈', '🥉', '🏅', '🎖️'];

get_header();
?>

<div class="ignis-scoreboard-container">
    <h2><?php _e('Top Users', 'ignis-plugin'); ?></h2>
    <?php if ($top_users) : ?>
        <ul class="ignis-scoreboard-list">
            <?php foreach ($top_users as $index => $user) : ?>
                <li>
                    <span class="rank"><?php echo esc_html($emojis[min($index, 4)] ?? '#') . ' ' . ($index + 1); ?></span>
                    <span class="username"><?php echo esc_html($user->user_login); ?></span>
                    <span class="points"><?php echo esc_html($user->points); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <p><?php _e('No users found.', 'ignis-plugin'); ?></p>
    <?php endif; ?>
</div>

<?php
get_footer();
?>
