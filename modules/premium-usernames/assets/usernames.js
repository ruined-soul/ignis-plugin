jQuery(document).ready(function($) {
    // Handle form submission
    $('.ignis-usernames form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var username = $form.find('input[name="username"]').val();
        var payment_method = $form.find('select[name="payment_method"]').val();

        $.ajax({
            url: ignis_usernames.ajax_url,
            type: 'POST',
            data: {
                action: 'ignis_change_username',
                nonce: ignis_usernames.nonce,
                username: username,
                payment_method: payment_method
            },
            success: function(response) {
                if (response.success) {
                    var $toast = $('<div class="ignis-usernames-toast">' + response.data.message + '</div>');
                    $('body').append($toast);
                    setTimeout(function() {
                        $toast.remove();
                    }, 6000);
                    $form[0].reset();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('Failed to change username.');
            }
        });
    });
});
