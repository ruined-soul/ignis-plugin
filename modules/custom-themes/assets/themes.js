jQuery(document).ready(function($) {
    // Handle theme application
    $('.ignis-theme button').on('click', function() {
        var $button = $(this);
        var $theme = $button.closest('.ignis-theme');
        var theme_id = $theme.data('theme-id');
        var payment_method = $theme.find('select').val();

        $.ajax({
            url: ignis_themes.ajax_url,
            type: 'POST',
            data: {
                action: 'ignis_apply_theme',
                nonce: ignis_themes.nonce,
                theme_id: theme_id,
                payment_method: payment_method
            },
            success: function(response) {
                if (response.success) {
                    var $toast = $('<div class="ignis-themes-toast">' + response.data.message + '</div>');
                    $('body').append($toast);
                    setTimeout(function() {
                        $toast.remove();
                    }, 6000);
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('Failed to apply theme.');
            }
        });
    });
});
