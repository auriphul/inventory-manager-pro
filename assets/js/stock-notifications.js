/**
 * Inventory Manager Pro - Stock Notifications
 *
 * Handles fetching and displaying stock notices when WooCommerce cart
 * fragments are refreshed.
 */
(function($) {
    'use strict';

    /**
     * Request stock notices from the server via AJAX.
     */
    function requestStockNotices() {
        if (!inventory_stock_notices || !inventory_stock_notices.ajax_url) {
            return;
        }

        $.ajax({
            url: inventory_stock_notices.ajax_url,
            method: 'POST',
            data: {
                action: 'inventory_manager_get_stock_notices',
                security: inventory_stock_notices.nonce
            },
            success: function(response) {
                console.log('Stock notices response:', response);
                if (response.success && response.data && response.data.messages && response.data.messages.length) {
                    $(document.body).trigger('inventory_stock_notices_received', [response.data.messages]);
                }
            }
        });
    }

    /**
     * Display notices inside a modal window.
     *
     * @param {Array} messages Array of notice strings.
     */
    function showNoticeModal(messages) {
        if (!messages || !messages.length) {
            return;
        }

        var html = '<div class="inventory-modal stock-notice-modal">';
        html += '<div class="modal-content">';
        html += '<div class="modal-header">';
        html += '<h3>' + inventory_stock_notices.title + '</h3>';
        html += '<button class="close-modal">&times;</button>';
        html += '</div>';
        html += '<div class="modal-body">';

        $.each(messages, function(i, msg) {
            html += '<p>' + msg + '</p>';
        });

        html += '</div></div></div>';
        $('body').append(html);

        $('.close-modal').on('click', function() {
            $('.stock-notice-modal').remove();
        });
    }

    // Listen for WooCommerce fragment refreshes
    $(document.body).on('wc_fragments_refreshed', function() {
        console.log('wc_fragments_refreshed detected');
        requestStockNotices();
    });

    // Display modal when notices are received
    $(document.body).on('inventory_stock_notices_received', function(event, messages) {
        showNoticeModal(messages);
    });

    // Initial request in case fragments were loaded before script
    $(function() {
        requestStockNotices();
    });
})(jQuery);
