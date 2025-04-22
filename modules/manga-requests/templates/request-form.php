<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$general_settings = get_option('ignis_general_settings', ['enable_manga_requests' => 1]);
if (!$general_settings['enable_manga_requests']) {
    echo '<p>' . __('Manga requests are currently disabled.', 'ignis-plugin') . '</p>';
    return;
}

if (!is_user_logged_in()) {
    echo '<p>' . __('You must be logged in to submit a manga request.', 'ignis-plugin') . '</p>';
    return;
}

get_header();
?>

<div class="ignis-manga-requests">
    <h2><?php _e('Request a Manga', 'ignis-plugin'); ?></h2>
    <form>
        <label for="title"><?php _e('Manga Title', 'ignis-plugin'); ?></label>
        <input type="text" name="title" id="title" required>
        
        <label for="description"><?php _e('Description', 'ignis-plugin'); ?></label>
        <textarea name="description" id="description" required></textarea>
        
        <button type="submit"><?php _e('Submit Request', 'ignis-plugin'); ?></button>
    </form>
</div>

<?php
get_footer();
?>
