jQuery(document).ready(function($) {
    // Handle create backup
    $('.ignis-create-backup').on('click', function() {
        $.ajax({
            url: ignis_backup.ajax_url,
            type: 'POST',
            data: {
                action: 'ignis_create_backup',
                nonce: ignis_backup.nonce
            },
            success: function(response) {
                if (response.success) {
                    var $toast = $('<div class="ignis-backup-toast">' + response.data.message + '</div>');
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
                alert('Failed to create backup.');
            }
        });
    });

    // Handle restore backup
    $('.ignis-restore-backup').on('click', function() {
        var filename = $(this).data('filename');
        if (!confirm('Are you sure you want to restore this backup? This will overwrite existing data.')) {
            return;
        }

        $.ajax({
            url: ignis_backup.ajax_url,
            type: 'POST',
            data: {
                action: 'ignis_restore_backup',
                nonce: ignis_backup.nonce,
                filename: filename
            },
            success: function(response) {
                if (response.success) {
                    var $toast = $('<div class="ignis-backup-toast">' + response.data.message + '</div>');
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
                alert('Failed to restore backup.');
            }
        });
    });
});
