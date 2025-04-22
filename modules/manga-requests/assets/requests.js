jQuery(document).ready(function($) {
    // Handle form submission
    $('.ignis-manga-requests form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var title = $form.find('input[name="title"]').val();
        var description = $form.find('textarea[name="description"]').val();

        $.ajax({
            url: ignis_requests.ajax_url,
            type: 'POST',
            data: {
                action: 'ignis_submit_manga_request',
                nonce: ignis_requests.nonce,
                title: title,
                description: description
            },
            success: function(response) {
                if (response.success) {
                    var $toast = $('<div class="ignis-manga-requests-toast">' + response.data.message + '</div>');
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
                alert('Failed to submit request.');
            }
        });
    });
});
