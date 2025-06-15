/**
 * Fullscreen functionality for Inventory Manager Pro
 */
(function($) {
    'use strict';
    
    /**
     * Initialize fullscreen functionality
     */
    function initFullscreenMode() {
        // Check if we're on the inventory dashboard page
        if (!$('.inventory-manager').length) {
            return;
        }
        
        // Add header elements to the inventory manager
        addHeaderElements();
        
        // Check for saved preference
        checkFullscreenPreference();
        
        // Handle click on toggle button
        $(document).on('click', '.toggle-fullscreen', function(e) {
            e.preventDefault();
            toggleFullscreenMode();
        });
        
        // Handle escape key to exit fullscreen
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('body').hasClass('inventory-fullscreen')) {
                toggleFullscreenMode(false);
            }
        });
    }
    
    /**
     * Add header elements to the inventory manager
     */
    function addHeaderElements() {
        // Add a header and wrapper for content
        $('.inventory-manager').wrapInner('<div class="inventory-manager-content"></div>');
        $('.inventory-manager').prepend(
            '<div class="inventory-manager-header">' +
                '<h1>Inventory Manager Pro</h1>' +
                '<button class="toggle-fullscreen" title="Toggle Fullscreen">' +
                    '<span class="fullscreen-icon">⛶</span> ' +
                    '<span class="fullscreen-text">Toggle Fullscreen</span>' +
                '</button>' +
            '</div>'
        );
    }
    
    /**
     * Check for saved fullscreen preference
     */
    function checkFullscreenPreference() {
        // Check localStorage for preference
        var isFullscreen = localStorage.getItem('inventory_fullscreen') === 'true';
        
        // Set initial state
        if (isFullscreen) {
            toggleFullscreenMode(true);
        }
    }
    
    /**
     * Toggle fullscreen mode
     */
    function toggleFullscreenMode(forceState) {
        var $body = $('body');
        var shouldBeFullscreen = typeof forceState !== 'undefined' ? forceState : !$body.hasClass('inventory-fullscreen');
        
        if (shouldBeFullscreen) {
            $body.addClass('inventory-fullscreen');
            $('.fullscreen-text').text('Exit Fullscreen');
            
            // Save preference
            localStorage.setItem('inventory_fullscreen', 'true');
            
            // Scroll to top
            window.scrollTo(0, 0);
        } else {
            $body.removeClass('inventory-fullscreen');
            $('.fullscreen-text').text('Enter Fullscreen');
            
            // Save preference
            localStorage.setItem('inventory_fullscreen', 'false');
        }
    }
    
    // Initialize on document ready
    $(document).ready(function() {
        initFullscreenMode();
    });
    
})(jQuery);