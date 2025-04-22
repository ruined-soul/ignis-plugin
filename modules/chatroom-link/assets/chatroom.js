jQuery(document).ready(function($) {
    // Handle join button click
    $('.ignis-chatroom-join').on('click', function() {
        var $button = $(this);
        var $result = $button.siblings('.ignis-chatroom-result');

        $.ajax({
            url: ignis_chatroom.ajax_url,
            type: 'POST',
            data: {
                action: 'ignis_join_chatroom',
                nonce: ignis_chatroom.nonce
            },
            success: function(response) {
                if (response.success) {
                    var $toast = $('<div class="ignis-chatroom-toast">' + response.data.message + '</div>');
                    $('body').append($toast);
                    setTimeout(function() {
                        $toast.remove();
                    }, 6000);
                    $result.html('<a href="' + response.data.invite_link + '" target="_blank">' + response.data.invite_link + '</a>').show();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('Failed to generate invite link.');
            }
        });
    });
});
