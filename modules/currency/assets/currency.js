jQuery(document).ready(function($) {
    // Convert points to currency
    $('.ignis-convert-form').on('submit', function(e) {
        e.preventDefault();
        var points = $(this).find('input[name="points"]').val();

        $.ajax({
            url: ignis_currency.ajax_url,
            type: 'POST',
            data: {
                action: 'ignis_convert_points',
                nonce: ignis_currency.nonce,
                points: points
            },
            success: function(response) {
                if (response.success) {
                    $('.ignis-currency-balance').text(response.data.currency);
                    var $toast = $('<div class="ignis-currency-toast">' + response.data.message + '</div>');
                    $('body').append($toast);
                    setTimeout(function() {
                        $toast.remove();
                    }, 6000);
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('Failed to convert points.');
            }
        });
    });

    // Refresh currency balance
    function refreshCurrencyBalance() {
        $.ajax({
            url: ignis_currency.ajax_url,
            type: 'GET',
            data: {
                action: 'ignis_get_currency',
                nonce: ignis_currency.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.ignis-currency-balance').text(response.data.currency);
                }
            }
        });
    }

    // Trigger balance refresh on specific actions
    $(document).on('ignis_currency_awarded ignis_currency_deducted', refreshCurrencyBalance);

    // Manual refresh button
    $('.ignis-refresh-currency').on('click', function(e) {
        e.preventDefault();
        refreshCurrencyBalance();
    });

    // Initialize balance
    if ($('.ignis-currency-balance').length) {
        refreshCurrencyBalance();
    }
});
