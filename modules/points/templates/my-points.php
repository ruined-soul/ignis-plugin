<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check if user is logged in
if (!is_user_logged_in()) {
    echo '<p>' . __('Please log in to view your points.', 'ignis-plugin') . '</p>';
    return;
}

$user_id = get_current_user_id();
$points = Ignis_Utilities::get_user_points($user_id);
$general_settings = get_option('ignis_general_settings', ['enable_points' => 1]);

if (!$general_settings['enable_points']) {
    echo '<p>' . __('Points system is currently disabled.', 'ignis-plugin') . '</p>';
    return;
}

global $wpdb;
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 10;
$history = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ignis_logs WHERE user_id = %d AND type = 'points' ORDER BY timestamp DESC LIMIT %d OFFSET %d",
    $user_id,
    $per_page,
    ($page - 1) * $per_page
));
$total = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}ignis_logs WHERE user_id = %d AND type = 'points'",
    $user_id
));

get_header();
?>

<div class="ignis-my-points">
    <h2><?php _e('My Points', 'ignis-plugin'); ?></h2>
    <p class="points-balance ignis-points-balance"><?php echo esc_html($points); ?></p>
    <button class="ignis-refresh-points"><?php _e('Refresh Points', 'ignis-plugin'); ?></button>

    <?php if ($history) : ?>
        <div class="history">
            <h3><?php _e('Points History', 'ignis-plugin'); ?></h3>
            <table>
                <thead>
                    <tr>
                        <th><?php _e('Action', 'ignis-plugin'); ?></th>
                        <th><?php _e('Points', 'ignis-plugin'); ?></th>
                        <th><?php _e('Timestamp', 'ignis-plugin'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $log) : ?>
                        <tr>
                            <td><?php echo esc_html($log->action); ?></td>
                            <td><?php echo esc_html($log->amount); ?></td>
                            <td><?php echo esc_html($log->timestamp); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
            $total_pages = ceil($total / $per_page);
            if ($total_pages > 1) :
            ?>
                <div class="pagination">
                    <?php if ($page > 1) : ?>
                        <a href="<?php echo esc_url(add_query_arg('paged', $page - 1)); ?>"><?php _e('Previous', 'ignis-plugin'); ?></a>
                    <?php endif; ?>
                    <span><?php printf(__('Page %d of %d', 'ignis-plugin'), $page, $total_pages); ?></span>
                    <?php if ($page < $total_pages) : ?>
                        <a href="<?php echo esc_url(add_query_arg('paged', $page + 1)); ?>"><?php _e('Next', 'ignis-plugin'); ?></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php else : ?>
        <p><?php _e('No points history available.', 'ignis-plugin'); ?></p>
    <?php endif; ?>
</div>

<?php
get_footer();
?>
