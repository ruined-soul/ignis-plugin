jQuery(document).ready(function($) {
    // Toggle documentation sections
    $('.ignis-doc-toggle').on('click', function() {
        var $content = $(this).next('.ignis-doc-content');
        $content.toggleClass('active');
    });
});
