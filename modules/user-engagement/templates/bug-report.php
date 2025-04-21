<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    echo '<p>' . __('Please log in to submit a bug report.', 'ignis-plugin') . '</p>';
    return;
}

$options = get_option('ignis_user_engagement_settings', ['enable_bug_reports' => 1]);
if (!$options['enable_bug_reports']) {
    echo '<p>' . __('Bug reporting is currently disabled.', 'ignis-plugin') . '</p>';
    return;
}
?>

<div class="ignis-bug-report-form">
    <h2><?php _e('Submit a Bug Report', 'ignis-plugin'); ?></h2>
    <form method="post" enctype="multipart/form-data">
        <label for="bug_title"><?php _e('Title', 'ignis-plugin'); ?></label>
        <input type="text" id="bug_title" name="title" required>

        <label for="bug_description"><?php _e('Description', 'ignis-plugin'); ?></label>
        <textarea id="bug_description" name="description" rows="5" required></textarea>

        <label for="bug_screenshot"><?php _e('Screenshot (optional)', 'ignis-plugin'); ?></label>
        <input type="file" id="bug_screenshot" name="screenshot" accept="image/*">

        <button type="submit"><?php _e('Submit Bug Report', 'ignis-plugin'); ?></button>
    </form>
</div>
