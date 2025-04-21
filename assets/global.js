jQuery(document).ready(function($) {
    // Handle toast dismissal
    $('.ignis-toast').on('click', function() {
        $(this).fadeOut(300, function() {
            $(this).remove();
        });
    });

    // Fetch user points via AJAX
    function refreshUserPoints() {
        $.ajax({
            url: ignis_vars.ajax_url,
            type: 'GET',
            data: {
                action: 'ignis_get_user_points',
                nonce: ignis_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.ignis-user-points').text(response.data.points);
                }
            },
            error: function() {
                console.error('Failed to fetch user points');
            }
        });
    }

    // Trigger points refresh on specific actions
    $(document).on('ignis_points_awarded', refreshUserPoints);

    // Initialize
    if ($('.ignis-user-points').length) {
        refreshUserPoints();
    }
});

// AJAX action for getting user points
jQuery(document).ready(function($) {
    $(document).on('click', '.ignis-refresh-points', function(e) {
        e.preventDefault();
        $.ajax({
            url: ignis_vars.ajax_url,
            type: 'GET',
            data: {
                action: 'ignis_get_user_points',
                nonce: ignis_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.ignis-user-points').text(response.data.points);
                    $(document).trigger('ignis_points_awarded');
                }
            }
        });
    });
});
