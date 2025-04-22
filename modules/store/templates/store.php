<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$general_settings = get_option('ignis_general_settings', ['enable_store' => 1]);
if (!$general_settings['enable_store']) {
    echo '<p>' . __('Store is currently disabled.', 'ignis-plugin') . '</p>';
    return;
}

$items = get_option('ignis_store_items', []);
$category = isset($atts['category']) ? $atts['category'] : '';

get_header();
?>

<div class="ignis-store">
    <h2><?php _e('Store', 'ignis-plugin'); ?></h2>
    <?php if ($items) : ?>
        <div class="ignis-store-items">
            <?php foreach ($items as $index => $item) : ?>
                <?php if ($item['enabled'] && (!$category || $item['type'] === $category)) : ?>
                    <div class="ignis-store-item" data-item-id="<?php echo esc_attr($index); ?>">
                        <h3><?php echo esc_html($item['name']); ?></h3>
                        <p><?php echo esc_html($item['type'] === 'chapter' ? __('Unlock a manga chapter', 'ignis-plugin') : __('Premium subscription', 'ignis-plugin')); ?></p>
                        <div class="cost"><?php echo esc_html($item['cost']); ?> <?php echo esc_html($general_settings['enable_currency'] ? 'MangaCoin/Points' : 'Points'); ?></div>
                        <select name="payment_method">
                            <?php if ($general_settings['enable_currency']) : ?>
                                <option value="currency"><?php _e('MangaCoin', 'ignis-plugin'); ?></option>
                            <?php endif; ?>
                            <option value="points"><?php _e('Points', 'ignis-plugin'); ?></option>
                        </select>
                        <button><?php _e('Purchase', 'ignis-plugin'); ?></button>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <p><?php _e('No items available in the store.', 'ignis-plugin'); ?></p>
    <?php endif; ?>
</div>

<?php
get_footer();
?>
