jQuery(document).ready(function($) {
    // Handle form submission
    $('.ignis-bug-bounty form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var formData = new FormData();
        formData.append('action', 'ignis_submit_bug_report');
        formData.append('nonce', ignis_bug_bounty.nonce);
        formData.append('title', $form.find('input[name="title"]').val());
        formData.append('description', $form.find('textarea[name="description"]').val());
        if ($form.find('input[name="screenshot"]')[0].files[0]) {
            formData.append('screenshot', $form.find('input[name="screenshot"]')[0].files[0]);
        }

        $.ajax({
            url: ignis_bug_bounty.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    var $toast = $('<div class="ignis-bug-bounty-toast">' + response.data.message + '</div>');
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
                alert('Failed to submit bug report.');
            }
        });
    });
});
