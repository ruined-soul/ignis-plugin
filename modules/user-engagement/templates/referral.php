<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check if user is logged in
if (!is_user_logged_in()) {
    echo '<p>' . __('Please log in to view your referral page.', 'ignis-plugin') . '</p>';
    return;
}

$user_id = get_current_user_id();
$options = get_option('ignis_user_engagement_settings', ['enable_referrals' => 1]);

if (!$options['enable_referrals']) {
    echo '<p>' . __('Referral system is currently disabled.', 'ignis-plugin') . '</p>';
    return;
}

global $wpdb;
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 10;
$referrals = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ignis_referrals WHERE user_id = %d AND status = 'completed' ORDER BY timestamp DESC LIMIT %d OFFSET %d",
    $user_id,
    $per_page,
    ($page - 1) * $per_page
));
$total = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}ignis_referrals WHERE user_id = %d AND status = 'completed'",
    $user_id
));

get_header();
?>

<div class="ignis-referral-page">
    <h2><?php _e('My Referrals', 'ignis-plugin'); ?></h2>
    
    <div class="ignis-referral-link-section">
        <?php echo do_shortcode('[ignis_referral_link]'); ?>
    </div>

    <?php if ($referrals) : ?>
        <div class="referral-history">
            <h3><?php _e('Referral History', 'ignis-plugin'); ?></h3>
            <table>
                <thead>
                    <tr>
                        <th><?php _e('Referred User', 'ignis-plugin'); ?></th>
                        <th><?php _e('Referral Code', 'ignis-plugin'); ?></th>
                        <th><?php _e('Timestamp', 'ignis-plugin'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($referrals as $referral) : ?>
                        <?php
                        $referred_user = get_userdata($referral->referred_user_id);
                        $referred_username = $referred_user ? $referred_user->user_login : __('Unknown', 'ignis-plugin');
                        ?>
                        <tr>
                            <td><?php echo esc_html($referred_username); ?></td>
                            <td><?php echo esc_html($referral->referral_code); ?></td>
                            <td><?php echo esc_html($referral->timestamp); ?></td>
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
        <p><?php _e('No referrals yet. Share your referral link to invite friends!', 'ignis-plugin'); ?></p>
    <?php endif; ?>
</div>

<style>
.ignis-referral-page {
    max-width: 800px;
    margin: 30px auto;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.ignis-referral-page h2 {
    font-size: 2em;
    color: #333;
    margin-bottom: 20px;
    text-align: center;
}

.ignis-referral-link-section {
    margin-bottom: 30px;
    text-align: center;
}

.ignis-referral-history {
    margin-top: 30px;
}

.ignis-referral-history table {
    width: 100%;
    border-collapse: collapse;
}

.ignis-referral-history th,
.ignis-referral-history td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: left;
}

.ignis-referral-history th {
    background: #f1f1f1;
    font-weight: bold;
}

.ignis-referral-page .pagination {
    margin-top: 20px;
    text-align: center;
}

.ignis-referral-page .pagination a {
    margin: 0 10px;
    color: #007bff;
    text-decoration: none;
}

.ignis-referral-page .pagination a:hover {
    text-decoration: underline;
}
</style>

<?php
get_footer();
?>
