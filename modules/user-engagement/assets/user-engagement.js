jQuery(document).ready(function($) {
    // Copy referral link
    $('.ignis-copy-link').on('click', function() {
        var $input = $(this).siblings('input');
        $input.select();
        document.execCommand('copy');
        alert('Referral link copied!');
    });

    // Bug report form submission
    $('.ignis-bug-report-form').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);

        formData.append('action', 'ignis_submit_bug');
        formData.append('nonce', ignis_engagement.nonce);

        $.ajax({
            url: ignis_engagement.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    $('.ignis-bug-report-form')[0].reset();
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
