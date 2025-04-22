<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$general_settings = get_option('ignis_general_settings', ['enable_bug_bounty' => 1]);
if (!$general_settings['enable_bug_bounty']) {
    echo '<p>' . __('Bug bounty is currently disabled.', 'ignis-plugin') . '</p>';
    return;
}

if (!is_user_logged_in()) {
    echo '<p>' . __('You must be logged in to submit a bug report.', 'ignis-plugin') . '</p>';
    return;
}

get_header();
?>

<div class="ignis-bug-bounty">
    <h2><?php _e('Submit a Bug Report', 'ignis-plugin'); ?></h2>
    <form enctype="multipart/form-data">
        <label for="title"><?php _e('Bug Title', 'ignis-plugin'); ?></label>
        <input type="text" name="title" id="title" required>
        
        <label for="description"><?php _e('Description', 'ignis-plugin'); ?></label>
        <textarea name="description" id="description" required></textarea>
        
        <label for="screenshot"><?php _e('Screenshot (Optional)', 'ignis-plugin'); ?></label>
        <input type="file" name="screenshot" id="screenshot" accept="image/*">
        
        <button type="submit"><?php _e('Submit Bug Report', 'ignis-plugin'); ?></button>
    </form>
</div>

<?php
get_footer();
?>
