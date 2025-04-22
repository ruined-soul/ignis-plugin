<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$general_settings = get_option('ignis_general_settings', ['enable_ranking' => 1]);
if (!$general_settings['enable_ranking']) {
    echo '<p>' . __('Ranking system is currently disabled.', 'ignis-plugin') . '</p>';
    return;
}

$options = get_option('ignis_ranking_settings', ['metric' => 'points', 'limit' => 10]);
$metric = isset($atts['metric']) && $atts['metric'] ? sanitize_text_field($atts['metric']) : $options['metric'];

get_header();
?>

<div class="ignis-leaderboard">
    <h2><?php printf(__('Top %d %s Leaderboard', 'ignis-plugin'), esc_html($options['limit']), esc_html($metric === 'points' ? 'Points' : 'MangaCoin')); ?></h2>
    <table>
        <thead>
            <tr>
                <th><?php _e('Rank', 'ignis-plugin'); ?></th>
                <th><?php _e('User', 'ignis-plugin'); ?></th>
                <th><?php echo esc_html($metric === 'points' ? __('Points', 'ignis-plugin') : __('MangaCoin', 'ignis-plugin')); ?></th>
            </tr>
        </thead>
        <tbody>
            <!-- Populated by JavaScript -->
        </tbody>
    </table>
    <button class="refresh-button"><?php _e('Refresh Leaderboard', 'ignis-plugin'); ?></button>
</div>

<?php
get_footer();
?>
