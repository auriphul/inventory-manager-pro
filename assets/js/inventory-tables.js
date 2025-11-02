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
            'stock_cost', 'landed_cost', 'actions'
        ]
    };

    /**
     * Initialize the inventory tables
     */
    function init() {
        // Set up event listeners
        setupEventListeners();
        
        // Set initial per page selector value
        $('.per-page-select').val(state.pagination.per_page);
        
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
            state.filters.expiry = ['6+', '3-6', '1-3', '<1', 'expired', 'no_expiry'];
            
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

        // Per page selector
        $('.per-page-select').on('change', function() {
            const perPage = $(this).val();
            
            if (perPage === 'all') {
                state.pagination.per_page = 9999; // Large number to get all records
            } else {
                state.pagination.per_page = parseInt(perPage);
            }
            
            state.pagination.current_page = 1; // Reset to first page
            loadBatches();
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

        // Edit batch
        $(document).on('click', '.edit-batch', function() {
            const batchId = $(this).data('id');
            const row = $(this).closest('tr');
            
            // Extract data from the table row
            const batchData = {
                id: batchId,
                sku: row.find('td:nth-child(1)').text().trim(),
                product_name: row.find('td:nth-child(2)').text().trim(),
                batch_number: row.find('td:nth-child(3)').text().trim(),
                stock_qty: parseFloat(row.find('td:nth-child(4)').text().trim()) || 0,
                supplier: row.find('td:nth-child(5)').text().trim(),
                expiry: row.find('td:nth-child(6)').text().trim(),
                origin: row.find('td:nth-child(7)').text().trim(),
                location: row.find('td:nth-child(8)').text().trim()
            };
            
            openEditModal(batchData);
        });

        // Modal close events
        $(document).on('click', '.modal-close, .modal-cancel, .modal-overlay', function() {
            closeEditModal();
        });

        // Prevent modal close when clicking inside modal content
        $(document).on('click', '.modal-content', function(e) {
            e.stopPropagation();
        });

        // Edit form submission
        $(document).on('submit', '#edit-batch-form', function(e) {
            e.preventDefault();
            submitEditBatch();
        });

        // Date format validation and auto-formatting
        $(document).on('input', '#edit-expiry-date', function() {
            let value = $(this).val().replace(/\D/g, ''); // Remove non-digits
            
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2);
            }
            if (value.length >= 5) {
                value = value.substring(0, 5) + '/' + value.substring(5, 9);
            }
            
            $(this).val(value);
        });

        // Validate date format on blur
        $(document).on('blur', '#edit-expiry-date', function() {
            const dateStr = $(this).val();
            if (dateStr && !dateStr.match(/^\d{2}\/\d{2}\/\d{4}$/)) {
                $(this).addClass('error');
                $(this).attr('title', 'Please enter date in DD/MM/YYYY format');
            } else {
                $(this).removeClass('error');
                $(this).removeAttr('title');
            }
        });

        // Delete batch
        $(document).on('click', '.delete-batch', function() {
            const $button = $(this);
            const row = $button.closest('tr');
            const sku = row.find('td:first').text();
            const batchNumber = row.find('td:nth-child(3)').text();
            
            if (!confirm(`Are you sure you want to permanently delete batch "${batchNumber}" for SKU "${sku}"? This will permanently remove the batch and all its data from the database. This action cannot be undone.`)) {
                return;
            }

            const batchId = $button.data('id');
            
            // Disable button and show loading state
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update"></span>');

            $.ajax({
                url: inventory_manager.api_url + '/batch/' + batchId,
                method: 'DELETE',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', inventory_manager.nonce);
                },
                success: function(response) {
                    console.log('Delete response:', response);
                    
                    // Show success notification
                    if (typeof inventoryManager !== 'undefined' && inventoryManager.showNotification) {
                        inventoryManager.showNotification('Batch deleted successfully', 'success');
                    } else {
                        alert('Batch deleted successfully');
                    }
                    
                    // Refresh the table
                    loadBatches();
                },
                error: function(xhr) {
                    console.error('Delete error:', xhr);
                    
                    let message = 'Error deleting batch';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        message = 'Error: ' + xhr.responseText;
                    }
                    
                    // Show error notification
                    if (typeof inventoryManager !== 'undefined' && inventoryManager.showNotification) {
                        inventoryManager.showNotification(message, 'error');
                    } else {
                        alert(message);
                    }
                    
                    // Re-enable button
                    $button.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span>');
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
        tableBody.html('<tr><td colspan="12" class="loading">Loading...</td></tr>');
        
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
                
                tableBody.html('<tr><td colspan="12" class="error">' + message + '</td></tr>');
            }
        });
    }
    
    /**
     * Render batches to table
     */
    function renderBatches() {
        const tableBody = $('.inventory-table tbody');
        
        if (state.batches.length === 0) {
            tableBody.html('<tr><td colspan="12" class="no-results">No batches found</td></tr>');
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
        
        // Hide pagination if "All" is selected or if only one page
        if (state.pagination.per_page >= 9999 || state.pagination.total_pages <= 1) {
            if (state.pagination.per_page >= 9999) {
                // Show only the total count when "All" is selected
                pagination.html('<div class="pagination-info">Showing all ' + state.pagination.total_batches + ' batches</div>');
            } else {
                pagination.html('');
            }
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
     * Open edit modal and populate with batch data
     */
    function openEditModal(batchData) {
        // First, get the full batch details via API
        $.ajax({
            url: inventory_manager.api_url + '/batch/' + batchData.id,
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', inventory_manager.nonce);
            },
            success: function(batch) {
                populateEditForm(batch);
                loadBrandsForProduct(batch.sku, batch.supplier_id);
                $('#edit-batch-modal').fadeIn(200);
            },
            error: function(xhr) {
                console.error('Error loading batch details:', xhr);
                if (typeof inventoryManager !== 'undefined' && inventoryManager.showNotification) {
                    inventoryManager.showNotification('Error loading batch details', 'error');
                } else {
                    alert('Error loading batch details');
                }
            }
        });
    }

    /**
     * Populate edit form with batch data
     */
    function populateEditForm(batch) {
        $('#edit-batch-id').val(batch.id);
        $('#edit-sku').val(batch.sku);
        $('#edit-product-name').val(batch.product_name);
        $('#edit-batch-number').val(batch.batch_number);
        $('#edit-stock-qty').val(batch.stock_qty);
        $('#edit-unit-cost').val(batch.unit_cost || '');
        $('#edit-freight-markup').val(batch.freight_markup || 1);
        $('#edit-expiry-date').val(convertDateToDisplayFormat(batch.expiry_date || ''));
        $('#edit-origin').val(batch.origin || '');
        $('#edit-location').val(batch.location || '');
    }

    /**
     * Load brands for product based on SKU
     */
    function loadBrandsForProduct(sku, selectedBrandId = null) {
        $.ajax({
            url: inventory_manager.api_url + '/product/' + encodeURIComponent(sku) + '/brands',
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', inventory_manager.nonce);
            },
            success: function(response) {
                const $select = $('#edit-brand');
                $select.find('option:not(:first)').remove(); // Keep the "Select Brand" option
                
                const brands = response.brands || [];
                brands.forEach(brand => {
                    $select.append(`<option value="${brand.id}">${brand.name}</option>`);
                });
                
                // Set the selected brand after loading options
                if (selectedBrandId) {
                    $select.val(selectedBrandId);
                }
            },
            error: function(xhr) {
                console.error('Error loading brands for product:', xhr);
                
                // If no brands found or error, show message in dropdown
                const $select = $('#edit-brand');
                $select.find('option:not(:first)').remove();
                $select.append('<option value="" disabled>No brands assigned to this product</option>');
            }
        });
    }

    /**
     * Close edit modal
     */
    function closeEditModal() {
        $('#edit-batch-modal').fadeOut(200);
        $('#edit-batch-form')[0].reset();
    }

    /**
     * Submit edit batch form
     */
    function submitEditBatch() {
        const formData = {
            batch_number: $('#edit-batch-number').val(),
            stock_qty: parseFloat($('#edit-stock-qty').val()),
            unit_cost: parseFloat($('#edit-unit-cost').val()) || null,
            freight_markup: parseFloat($('#edit-freight-markup').val()) || 1,
            expiry_date: convertDateToApiFormat($('#edit-expiry-date').val()) || null,
            supplier_id: $('#edit-brand').val() || null,
            origin: $('#edit-origin').val() || null,
            location: $('#edit-location').val() || null
        };

        const batchId = $('#edit-batch-id').val();
        const $submitBtn = $('.modal-footer .button-primary');
        
        // Disable submit button and show loading
        $submitBtn.prop('disabled', true).text('Updating...');

        $.ajax({
            url: inventory_manager.api_url + '/batch/' + batchId,
            method: 'PUT',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', inventory_manager.nonce);
            },
            success: function(response) {
                console.log('Edit response:', response);
                
                // Show success notification
                if (typeof inventoryManager !== 'undefined' && inventoryManager.showNotification) {
                    inventoryManager.showNotification('Batch updated successfully', 'success');
                } else {
                    alert('Batch updated successfully');
                }
                
                // Close modal and refresh table
                closeEditModal();
                loadBatches();
            },
            error: function(xhr) {
                console.error('Edit error:', xhr);
                
                let message = 'Error updating batch';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    message = 'Error: ' + xhr.responseText;
                }
                
                // Show error notification
                if (typeof inventoryManager !== 'undefined' && inventoryManager.showNotification) {
                    inventoryManager.showNotification(message, 'error');
                } else {
                    alert(message);
                }
            },
            complete: function() {
                // Re-enable submit button
                $submitBtn.prop('disabled', false).text('Update Batch');
            }
        });
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
    
    /**
     * Convert date from YYYY-MM-DD to DD/MM/YYYY format
     */
    function convertDateToDisplayFormat(dateStr) {
        if (!dateStr) return '';
        
        // Check if already in DD/MM/YYYY format
        if (dateStr.match(/^\d{2}\/\d{2}\/\d{4}$/)) {
            return dateStr;
        }
        
        // Convert from YYYY-MM-DD to DD/MM/YYYY
        const parts = dateStr.split('-');
        if (parts.length === 3) {
            return parts[2] + '/' + parts[1] + '/' + parts[0];
        }
        
        return dateStr;
    }
    
    /**
     * Convert date from DD/MM/YYYY to YYYY-MM-DD format for API
     */
    function convertDateToApiFormat(dateStr) {
        if (!dateStr) return null;
        
        // Check if already in YYYY-MM-DD format
        if (dateStr.match(/^\d{4}-\d{2}-\d{2}$/)) {
            return dateStr;
        }
        
        // Convert from DD/MM/YYYY to YYYY-MM-DD
        const parts = dateStr.split('/');
        if (parts.length === 3) {
            const day = parts[0].padStart(2, '0');
            const month = parts[1].padStart(2, '0');
            const year = parts[2];
            return year + '-' + month + '-' + day;
        }
        
        return null;
    }

    // Initialize on document ready
    $(document).ready(function() {
        init();
    });

})(jQuery);