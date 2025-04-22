jQuery(document).ready(function($) {
    // Countdown timer
    $('.ignis-airdrop-campaign').each(function() {
        var $campaign = $(this);
        var endDate = new Date($campaign.data('end-date')).getTime();
        var $countdown = $campaign.find('.countdown');

        function updateCountdown() {
            var now = new Date().getTime();
            var distance = endDate - now;

            if (distance <= 0) {
                $countdown.text('Expired');
                $campaign.find('button').prop('disabled', true);
                return;
            }

            var days = Math.floor(distance / (1000 * 60 * 60 * 24));
            var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((distance % (1000 * 60)) / 1000);

            $countdown.text(days + 'd ' + hours + 'h ' + minutes + 'm ' + seconds + 's');
        }

        updateCountdown();
        setInterval(updateCountdown, 1000);
    });

    // Handle claim
    $('.ignis-airdrop-campaign button').on('click', function() {
        var $button = $(this);
        var $campaign = $button.closest('.ignis-airdrop-campaign');
        var airdrop_id = $campaign.data('airdrop-id');
        var short_url = $campaign.find('.shortener-input').val() || '';

        $.ajax({
            url: ignis_airdrop.ajax_url,
            type: 'POST',
            data: {
                action: 'ignis_claim_airdrop',
                nonce: ignis_airdrop.nonce,
                airdrop_id: airdrop_id,
                short_url: short_url
            },
            success: function(response) {
                if (response.success) {
                    var $toast = $('<div class="ignis-airdrop-toast">' + response.data.message + '</div>');
                    $('body').append($toast);
                    setTimeout(function() {
                        $toast.remove();
                    }, 6000);
                    $button.prop('disabled', true);
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('Failed to claim airdrop.');
            }
        });
    });
});
