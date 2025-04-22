jQuery(document).ready(function($) {
    // Handle purchase
    $('.ignis-store-item button').on('click', function() {
        var $button = $(this);
        var $item = $button.closest('.ignis-store-item');
        var item_id = $item.data('item-id');
        var payment_method = $item.find('select').val();

        $.ajax({
            url: ignis_store.ajax_url,
            type: 'POST',
            data: {
                action: 'ignis_purchase_item',
                nonce: ignis_store.nonce,
                item_id: item_id,
                payment_method: payment_method
            },
            success: function(response) {
                if (response.success) {
                    var $toast = $('<div class="ignis-store-toast">' + response.data.message + '</div>');
                    $('body').append($toast);
                    setTimeout(function() {
                        $toast.remove();
                    }, 6000);
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('Failed to process purchase.');
            }
        });
    });
});
