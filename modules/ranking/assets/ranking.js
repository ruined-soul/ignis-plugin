jQuery(document).ready(function($) {
    // Load leaderboard
    function loadLeaderboard() {
        $.ajax({
            url: ignis_ranking.ajax_url,
            type: 'POST',
            data: {
                action: 'ignis_get_leaderboard',
                nonce: ignis_ranking.nonce
            },
            success: function(response) {
                if (response.success) {
                    var $tbody = $('.ignis-leaderboard tbody');
                    $tbody.empty();
                    $.each(response.data.leaderboard, function(index, user) {
                        var rankClass = index < 3 ? 'rank-' + (index + 1) : '';
                        $tbody.append(
                            '<tr class="' + rankClass + '">' +
                            '<td>' + (index + 1) + '</td>' +
                            '<td>' + user.user_login + '</td>' +
                            '<td>' + user.score + '</td>' +
                            '</tr>'
                        );
                    });
                } else {
                    alert('Failed to load leaderboard.');
                }
            },
            error: function() {
                alert('Failed to load leaderboard.');
            }
        });
    }

    // Initial load
    if ($('.ignis-leaderboard').length) {
        loadLeaderboard();
    }

    // Refresh button
    $('.ignis-leaderboard .refresh-button').on('click', function() {
        loadLeaderboard();
    });
});
