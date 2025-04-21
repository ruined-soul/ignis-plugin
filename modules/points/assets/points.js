jQuery(document).ready(function($) {
    // Display toast notification from transient
    function checkToast() {
        $.ajax({
            url: ignis_vars.ajax_url,
            type: 'GET',
            data: {
                action: 'ignis_get_toast',
                nonce: ignis_vars.nonce
            },
            success: function(response) {
                if (response.success && response.data.message) {
                    var $toast = $('<div class="ignis-points-toast ' + response.data.type + '">' + response.data.message + '</div>');
                    $('body').append($toast);
                    setTimeout(function() {
                        $toast.remove();
                    }, 6000);
                }
            }
        });
    }

    // Check for toast on page load and after points events
    checkToast();
    $(document).on('ignis_points_awarded', checkToast);

    // Refresh points balance
    function refreshPointsBalance() {
        $.ajax({
            url: ignis_vars.ajax_url,
            type: 'GET',
            data: {
                action: 'ignis_get_user_points',
                nonce: ignis_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.ignis-points-balance').text(response.data.points);
                }
            }
        });
    }

    // Trigger points refresh on specific actions
    $(document).on('ignis_points_awarded', refreshPointsBalance);

    // Manual refresh button
    $('.ignis-refresh-points').on('click', function(e) {
        e.preventDefault();
        refreshPointsBalance();
    });

    // Initialize points balance
    if ($('.ignis-points-balance').length) {
        refreshPointsBalance();
    }
});

// AJAX action for getting toast
jQuery(document).ready(function($) {
    $.ajaxPrefilter(function(options, originalOptions, jqXHR) {
        if (options.data && options.data.action === 'ignis_get_toast') {
            options.success = function(response) {
                if (response.success) {
                    var $toast = $('<div class="ignis-points-toast ' + response.data.type + '">' + response.data.message + '</div>');
                    $('body').append($toast);
                }
            };
        }
    });
});
