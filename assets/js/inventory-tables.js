/**
 * Inventory Manager Pro - Inventory Tables JS
 * 
 * Handles the overview table functionality
 */
(function($) {
    'use strict';

    // Current state
    let state = {
        batches: [],
        pagination: {
            current_page: 1,
            per_page: 20,
            total_batches: 0,
            total_pages: 0
        },
        filters: {
            // expiry: ['6+', '3-6', '1-3', '<1', 'expired'],
            expiry: [],
            search: '',
            orderby: 'sku',
            order: 'ASC'
        },
        visible_columns: [
            'sku', 'product_name', 'batch', 'stock_qty',
            'brand', 'expiry', 'origin', 'location',
            'stock_cost', 'landed_cost'
        ]
    };

    /**
     * Initialize the inventory tables
     */
    function init() {
        // Set up event listeners
        setupEventListeners();
        
        // Load initial batches
        loadBatches();
    }

    /**
     * Set up event listeners
     */
    function setupEventListeners() {
        // Expiry filters
        $('.filter-expiry').on('change', function() {
            const range = $(this).data('range');
            const index = state.filters.expiry.indexOf(range);
            
            if ($(this).is(':checked') && index === -1) {
                state.filters.expiry.push(range);
            } else if (!$(this).is(':checked') && index !== -1) {
                state.filters.expiry.splice(index, 1);
            }
            
            state.pagination.current_page = 1; // Reset to first page
            loadBatches();
        });
        
        // Column toggles
        $('.toggle-column').on('change', function() {
            const column = $(this).data('column');
            const columnClass = '.column-' + column;
            
            if ($(this).is(':checked')) {
                $(columnClass).show();
                const index = state.visible_columns.indexOf(column);
                if (index === -1) {
                    state.visible_columns.push(column);
                }
            } else {
                $(columnClass).hide();
                const index = state.visible_columns.indexOf(column);
                if (index !== -1) {
                    state.visible_columns.splice(index, 1);
                }
            }
        });
        
        // Column sorting
        $('.inventory-table th').on('click', function() {
            const orderby = $(this).data('sort');
            
            if (orderby) {
                if (state.filters.orderby === orderby) {
                    // Toggle order direction
                    state.filters.order = state.filters.order === 'ASC' ? 'DESC' : 'ASC';
                } else {
                    state.filters.orderby = orderby;
                    state.filters.order = 'ASC';
                }
                
                // Update sort icons
                $('.inventory-table th .sort-icon').removeClass('asc desc');
                const icon = $(this).find('.sort-icon');
                icon.addClass(state.filters.order.toLowerCase());
                
                loadBatches();
            }
        });
        
        // Search
        $('.search-box button').on('click', function() {
            state.filters.search = $('.search-box input').val();
            state.pagination.current_page = 1; // Reset to first page
            loadBatches();
        });
        
        $('.search-box input').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                state.filters.search = $(this).val();
                state.pagination.current_page = 1; // Reset to first page
                loadBatches();
            }
        });
        
        // Show all batches button
        $('.show-all-btn').on('click', function() {
            // Check all expiry filters
            $('.filter-expiry').prop('checked', true);
            state.filters.expiry = ['6+', '3-6', '1-3', '<1', 'expired'];
            
            // Clear search
            $('.search-box input').val('');
            state.filters.search = '';
            
            // Reset pagination
            state.pagination.current_page = 1;
            
            loadBatches();
        });
        
        // Export button
        $('.export-btn').on('click', function() {
            exportData();
        });
        
        // Attach pagination events (will be created dynamically)
        $(document).on('click', '.pagination-links a', function(e) {
            e.preventDefault();
            const page = $(this).data('page');
            
            if (page) {
                state.pagination.current_page = page;
                loadBatches();
                $('html, body').animate({
                    scrollTop: $('.inventory-table').offset().top - 50
                }, 300);
            }
        });

        // Delete batch
        $(document).on('click', '.delete-batch', function() {
            if (!confirm('Are you sure you want to delete this batch?')) {
                return;
            }

            const batchId = $(this).data('id');

            $.ajax({
                url: inventory_manager.api_url + '/batch/' + batchId,
                method: 'DELETE',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', inventory_manager.nonce);
                },
                success: function() {
                    inventoryManager.showNotification('Batch deleted', 'success');
                    loadBatches();
                },
                error: function(xhr) {
                    console.log(xhr)
                    let message = 'Error deleting batch';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    inventoryManager.showNotification(message, 'error');
                }
            });
        });
    }
    
    /**
     * Load batches via API
     */
    function loadBatches() {
        const tableBody = $('.inventory-table tbody');
        
        // Show loading
        tableBody.html('<tr><td colspan="11" class="loading">Loading...</td></tr>');
        
        // Prepare request data
        const data = {
            page: state.pagination.current_page,
            per_page: state.pagination.per_page,
            orderby: state.filters.orderby,
            order: state.filters.order,
            expiry_filters: state.filters.expiry,
            search: state.filters.search
        };
        
        // Make API request
        $.ajax({
            url: inventory_manager.api_url + '/batches',
            method: 'GET',
            data: data,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', inventory_manager.nonce);
            },
            success: function(response) {
                // Update state
                state.batches = response.batches;
                state.pagination = response.pagination;
                
                // Render batches
                renderBatches();
                
                // Render pagination
                renderPagination();
            },
            error: function(xhr) {
                let message = 'Error loading batches';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                
                tableBody.html('<tr><td colspan="11" class="error">' + message + '</td></tr>');
            }
        });
    }
    
    /**
     * Render batches to table
     */
    function renderBatches() {
        const tableBody = $('.inventory-table tbody');
        
        if (state.batches.length === 0) {
            tableBody.html('<tr><td colspan="11" class="no-results">No batches found</td></tr>');
            return;
        }
        
        let html = '';
        
        // Get row template
        const template = $('#batch-row-template').html();
        
        // Process each batch
        $.each(state.batches, function(index, batch) {
            // Replace template variables
            let row = template
                .replace(/\{\{id\}\}/g, batch.id)
                .replace(/\{\{sku\}\}/g, batch.sku)
                .replace(/\{\{product_name\}\}/g, batch.product_name)
                .replace(/\{\{batch_number\}\}/g, batch.batch_number)
                .replace(/\{\{stock_qty\}\}/g, parseFloat(batch.stock_qty).toFixed(2))
                .replace(/\{\{brand_name\}\}/g, batch.brand_name || '')
                .replace(/\{\{expiry_formatted\}\}/g, batch.expiry_formatted || '')
                .replace(/\{\{origin\}\}/g, batch.origin || '')
                .replace(/\{\{location\}\}/g, batch.location || '')
                .replace(/\{\{stock_cost_formatted\}\}/g, batch.stock_cost_formatted || '')
                .replace(/\{\{landed_cost_formatted\}\}/g, batch.landed_cost_formatted || '')
                .replace(/\{\{expiry_range\}\}/g, batch.expiry_range || '');
            
            html += row;
        });
        
        tableBody.html(html);
    }
    
    /**
     * Render pagination
     */
    function renderPagination() {
        const pagination = $('.pagination');
        
        if (state.pagination.total_pages <= 1) {
            pagination.html('');
            return;
        }
        
        let html = '<div class="pagination-links">';
        
        // Previous button
        if (state.pagination.current_page > 1) {
            html += '<a href="#" data-page="' + (state.pagination.current_page - 1) + '" class="prev">« Previous</a>';
        }
        
        // Page numbers
        let start = Math.max(1, state.pagination.current_page - 2);
        let end = Math.min(state.pagination.total_pages, start + 4);
        
        if (end - start < 4) {
            start = Math.max(1, end - 4);
        }
        
        for (let i = start; i <= end; i++) {
            if (i === state.pagination.current_page) {
                html += '<span class="current-page">' + i + '</span>';
            } else {
                html += '<a href="#" data-page="' + i + '">' + i + '</a>';
            }
        }
        
        // Next button
        if (state.pagination.current_page < state.pagination.total_pages) {
            html += '<a href="#" data-page="' + (state.pagination.current_page + 1) + '" class="next">Next »</a>';
        }
        
        html += '</div>';
        html += '<div class="pagination-info">Page ' + state.pagination.current_page + ' of ' + state.pagination.total_pages + ' (' + state.pagination.total_batches + ' batches)</div>';
        
        pagination.html(html);
    }
    
    /**
     * Export data
     */
    function exportData() {
        // Prepare export params
        const params = {
            format: $('.export-format').val() || 'csv',
            type: 'overview',
            sku: '',
            search: state.filters.search,
            expiry_filters: state.filters.expiry,
            visible_columns: state.visible_columns
        };
        
        // Create form and submit it
        const form = $('<form></form>')
            .attr('method', 'get')
            .attr('action', inventory_manager.api_url + '/export')
            .css('display', 'none');
        
        // Add CSRF token
        form.append($('<input></input>')
            .attr('type', 'hidden')
            .attr('name', '_wpnonce')
            .attr('value', inventory_manager.nonce));
        
        // Add export parameters
        $.each(params, function(key, value) {
            if (Array.isArray(value)) {
                $.each(value, function(i, item) {
                    form.append($('<input></input>')
                        .attr('type', 'hidden')
                        .attr('name', key + '[]')
                        .attr('value', item));
                });
            } else {
                form.append($('<input></input>')
                    .attr('type', 'hidden')
                    .attr('name', key)
                    .attr('value', value));
            }
        });
        
        // Append form, submit it, then remove it
        $('body').append(form);
        form.submit();
        form.remove();
    }
    
    // Initialize on document ready
    $(document).ready(function() {
        init();
    });

})(jQuery);