<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$general_settings = get_option('ignis_general_settings', ['enable_custom_themes' => 1, 'enable_currency' => 1]);
if (!$general_settings['enable_custom_themes']) {
    echo '<p>' . __('Custom themes are currently disabled.', 'ignis-plugin') . '</p>';
    return;
}

if (!is_user_logged_in()) {
    echo '<p>' . __('You must be logged in to customize your profile.', 'ignis-plugin') . '</p>';
    return;
}

$user_id = get_current_user_id();
$themes = get_option('ignis_custom_themes', []);
$owned_themes = get_user_meta($user_id, 'ignis_owned_themes', true);
$owned_themes = is_array($owned_themes) ? $owned_themes : [];
$active_theme = get_user_meta($user_id, 'ignis_active_theme', true);

get_header();
?>

<div class="ignis-profile-customization">
    <h2><?php _e('Customize Profile', 'ignis-plugin'); ?></h2>
    <?php if ($themes) : ?>
        <?php foreach ($themes as $index => $theme) : ?>
            <?php if ($theme['enabled']) : ?>
                <div class="ignis-theme" data-theme-id="<?php echo esc_attr($index); ?>">
                    <h3><?php echo esc_html($theme['name']); ?></h3>
                    <div class="preview" style="<?php echo esc_attr($theme['css']); ?>"></div>
                    <p><?php printf(__('Cost: %d %s', 'ignis-plugin'), esc_html($theme['cost']), esc_html($general_settings['enable_currency'] ? 'Points/MangaCoin' : 'Points')); ?></p>
                    <?php if (!in_array($index, $owned_themes)) : ?>
                        <select name="payment_method">
                            <?php if ($general_settings['enable_currency']) : ?>
                                <option value="currency"><?php _e('MangaCoin', 'ignis-plugin'); ?></option>
                            <?php endif; ?>
                            <option value="points"><?php _e('Points', 'ignis-plugin'); ?></option>
                        </select>
                    <?php endif; ?>
                    <button <?php echo $index == $active_theme ? 'disabled' : ''; ?>>
                        <?php echo $index == $active_theme ? __('Active', 'ignis-plugin') : __('Apply Theme', 'ignis-plugin'); ?>
                    </button>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if (!array_filter($themes, function($t) { return $t['enabled']; })) : ?>
            <p><?php _e('No themes available.', 'ignis-plugin'); ?></p>
        <?php endif; ?>
    <?php else : ?>
        <p><?php _e('No themes available.', 'ignis-plugin'); ?></p>
    <?php endif; ?>
</div>

<?php
get_footer();
?>
