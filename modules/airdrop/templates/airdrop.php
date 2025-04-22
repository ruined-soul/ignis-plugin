<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$general_settings = get_option('ignis_general_settings', ['enable_airdrop' => 1]);
if (!$general_settings['enable_airdrop']) {
    echo '<p>' . __('Airdrop system is currently disabled.', 'ignis-plugin') . '</p>';
    return;
}

$airdrops = get_option('ignis_airdrop_campaigns', []);
$airdrop_id = isset($atts['id']) ? absint($atts['id']) : 0;

get_header();
?>

<div class="ignis-airdrop">
    <h2><?php _e('Airdrop Campaigns', 'ignis-plugin'); ?></h2>
    <?php if ($airdrops) : ?>
        <?php foreach ($airdrops as $index => $airdrop) : ?>
            <?php if ($airdrop['enabled'] && (!$airdrop_id || $index === $airdrop_id) && current_time('timestamp') <= strtotime($airdrop['end_date'])) : ?>
                <div class="ignis-airdrop-campaign" data-airdrop-id="<?php echo esc_attr($index); ?>" data-end-date="<?php echo esc_attr($airdrop['end_date']); ?>">
                    <h3><?php echo esc_html($airdrop['name']); ?></h3>
                    <p><?php printf(__('Reward: %d %s', 'ignis-plugin'), esc_html($airdrop['reward_amount']), esc_html($airdrop['reward_type'] === 'points' ? 'Points' : 'MangaCoin')); ?></p>
                    <p class="countdown"><?php _e('Loading...', 'ignis-plugin'); ?></p>
                    <?php if ($airdrop['requires_shortener']) : ?>
                        <input type="text" class="shortener-input" placeholder="<?php _e('Enter shortened URL', 'ignis-plugin'); ?>">
                    <?php endif; ?>
                    <button><?php _e('Claim Airdrop', 'ignis-plugin'); ?></button>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if (!$airdrops || !array_filter($airdrops, function($a) use ($airdrop_id) { return $a['enabled'] && (!$airdrop_id || array_key_exists($airdrop_id, $airdrops)) && current_time('timestamp') <= strtotime($a['end_date']); })) : ?>
            <p><?php _e('No active airdrops available.', 'ignis-plugin'); ?></p>
        <?php endif; ?>
    <?php else : ?>
        <p><?php _e('No airdrops available.', 'ignis-plugin'); ?></p>
    <?php endif; ?>
</div>

<?php
get_footer();
?>
