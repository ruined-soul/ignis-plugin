jQuery(document).ready(function($) {
    // Handle form submission
    $('.ignis-shortener-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var url = $form.find('input[name="url"]').val();
        var $result = $form.siblings('.ignis-shortener-result');

        $.ajax({
            url: ignis_shortener.ajax_url,
            type: 'POST',
            data: {
                action: 'ignis_shorten_url',
                nonce: ignis_shortener.nonce,
                url: url
            },
            success: function(response) {
                if (response.success) {
                    var $toast = $('<div class="ignis-shortener-toast">' + response.data.message + '</div>');
                    $('body').append($toast);
                    setTimeout(function() {
                        $toast.remove();
                    }, 6000);
                    $result.html('<a href="' + response.data.short_url + '" target="_blank">' + response.data.short_url + '</a>').show();
                    $form[0].reset();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('Failed to shorten URL.');
            }
        });
    });
});
