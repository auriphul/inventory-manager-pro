/**
 * Inventory Manager Pro - Inventory Logs JS
 * 
 * Handles the detailed logs functionality
 */
(function($) {
    'use strict';

    // Current state
    let state = {
        period: 'last_30_days',
        batch_period: 'all',
        search: '',
        order: 'ASC',
        expiry_filters: [],
        products: []
    };

    /**
     * Initialize the detailed logs
     */
    function init() {
        if (!$('.inventory-manager-logs').length) {
            return; // Not on logs page
        }

        // Set up event listeners
        setupEventListeners();
        
        // Load initial logs
        loadLogs();
    }

    /**
     * Set up event listeners
     */
    function setupEventListeners() {
        // Period filter
        $('.period-filter').on('change', function() {
            state.period = $(this).val();
            loadLogs();
        });

        // Expiry filters
        $('.filter-expiry').on('change', function() {
            const range = $(this).data('range');
            const index = state.expiry_filters.indexOf(range);

            if ($(this).is(':checked') && index === -1) {
                state.expiry_filters.push(range);
            } else if (!$(this).is(':checked') && index !== -1) {
                state.expiry_filters.splice(index, 1);
            }

            loadLogs();
        });

        // Batch period filter
        $('.batch-period-filter').on('change', function() {
            state.batch_period = $(this).val();
            loadLogs();
        });

        // Order filter
        $('.order-filter').on('change', function() {
            state.order = $(this).val();
            loadLogs();
        });
        
        // Search
        $('.logs-search-btn').on('click', function() {
            state.search = $('.logs-search-box input').val();
            loadLogs();
        });
        
        $('.logs-search-box input').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                state.search = $(this).val();
                loadLogs();
            }
        });

        // Show all batches button
        $('.show-all-batches-btn').on('click', function() {
            $('.logs-search-box input').val('');
            state.search = '';
            $('.filter-expiry').prop('checked', true);
            state.expiry_filters = [];
            loadLogs();
        });
        
        // Export button
        $('.logs-export-btn').on('click', function() {
            exportLogs();
        });
        
        // Toggle product details
        $(document).on('click', '.product-header', function() {
            const header = $(this);
            header.closest('.product-section').find('.batches-container').slideToggle();

            const icon = header.find('.toggle-icon');
            if (header.hasClass('expanded')) {
                header.removeClass('expanded');
                icon
                    .removeClass('dashicons-arrow-down-alt2')
                    .addClass('dashicons-arrow-up-alt2');
            } else {
                header.addClass('expanded');
                icon
                    .removeClass('dashicons-arrow-up-alt2')
                    .addClass('dashicons-arrow-down-alt2');
            }
        });
        
        // Toggle batch details
        $(document).on('click', '.batch-header', function() {
            const header = $(this);
            header.closest('.batch-section').find('.batch-details, .movement-log').slideToggle();

            const icon = header.find('.toggle-icon');
            if (header.hasClass('expanded')) {
                header.removeClass('expanded');
                icon
                    .removeClass('dashicons-arrow-up-alt2')
                    .addClass('dashicons-arrow-down-alt2');
            } else {
                header.addClass('expanded');
                icon
                    .removeClass('dashicons-arrow-down-alt2')
                    .addClass('dashicons-arrow-up-alt2');
            }
        });
        
        // Add adjustment button
        $(document).on('click', '.add-adjustment-btn', function() {
            const batchId = $(this).data('batch-id');
            openAdjustmentModal(batchId);
        });

        // Delete movement entry
        $(document).on('click', '.delete-entry-btn', function() {
            if (!confirm('Are you sure you want to delete this entry?')) {
                return;
            }

            const entryId = $(this).data('id');

            $.ajax({
                url: inventory_manager.api_url + '/movement/' + entryId,
                method: 'DELETE',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', inventory_manager.nonce);
                },
                success: function() {
                    inventoryManager.showNotification('Entry deleted', 'success');
                    loadLogs();
                },
                error: function(xhr) {
                    let message = 'Error deleting entry';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    inventoryManager.showNotification(message, 'error');
                }
            });
        });
    }
    
    /**
     * Load detailed logs via API
     */
    function loadLogs() {
        const logsContainer = $('.products-container');
        
        // Show loading
        logsContainer.html('<div class="loading">Loading...</div>');
        
        // Prepare request data
        const data = {
            period: state.period,
            batch_period: state.batch_period,
            search: state.search,
            order: state.order,
            expiry_filters: state.expiry_filters
        };
        
        // Make API request
        $.ajax({
            url: inventory_manager.api_url + '/detailed-logs',
            method: 'GET',
            data: data,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', inventory_manager.nonce);
            },
            success: function(response) {
                // Update state
                state.products = response.products;
                
                // Render logs
                renderLogs();
            },
            error: function(xhr) {
                let message = 'Error loading logs';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                
                logsContainer.html('<div class="error">' + message + '</div>');
            }
        });
    }
    
    /**
     * Render detailed logs
     */
    function renderLogs() {
        const logsContainer = $('.products-container');
        
        if (state.products.length === 0) {
            logsContainer.html('<div class="no-results">No products found</div>');
            return;
        }
        
        let html = '';
        
        // Process each product
        $.each(state.products, function(index, product) {
            html += renderProduct(product);
        });
        
        logsContainer.html(html);
        $('.product-header').click();
    }
    
    /**
     * Render product section
     */
    function renderProduct(product) {
        let html = '<div class="product-section">';
        
        // Product header
        html += '<div class="inv-product-info"><span class="half-flex">SKU: ' + product.sku +'</span>' + '<span>' + product.product_name +'</span>' + '<span class="half-flex">BATCHES: ' + product.batches.length +'</span>' + '</div>';
        html += '<div class="product-header">';
        
        // Product summary
        html += '<div class="product-summary">';
        
        // Totals and averages
        let totalStock = 0;
        let totalStockCost = 0;
        let totalLandedCost = 0;
        let weightedUnitCost = 0;
        let weightedFreight = 0;

        $.each(product.batches, function(index, batch) {
            const qty = parseFloat(batch.stock_qty);
            const unitCost = parseFloat(batch.unit_cost) || 0;
            const freightMarkup = parseFloat(batch.freight_markup) || 0;

            totalStock += qty;
            totalStockCost += unitCost * qty;
            totalLandedCost += unitCost * freightMarkup * qty;
            weightedUnitCost += unitCost * qty;
            weightedFreight += freightMarkup * qty;
        });

        const avgUnitCost = totalStock ? (weightedUnitCost / totalStock) : 0;
        const avgFreight = totalStock ? (weightedFreight / totalStock) : 0;

        // Total batches

        html += '<div class="total-stock">';
        html += '<span class="label">Total Stock Qty</span>';
        html += '<span class="value">' + totalStock.toFixed(2) + '</span>';
        html += '</div>';

        html += '<div class="avg-unit-cost">';
        html += '<span class="label">Avg Unit Cost</span>';
        html += '<span class="value">' + inventory_manager.currency_symbol + avgUnitCost.toFixed(2) + '</span>';
        html += '</div>';

        html += '<div class="total-stock-cost">';
        html += '<span class="label">Total Stock Cost</span>';
        html += '<span class="value">' + inventory_manager.currency_symbol + totalStockCost.toFixed(2) + '</span>';
        html += '</div>';

        html += '<div class="avg-freight">';
        html += '<span class="label">Avg Freight Markup</span>';
        html += '<span class="value">' + inventory_manager.currency_symbol + avgFreight.toFixed(2) + '</span>';
        html += '</div>';

        html += '<div class="total-landed-cost">';
        html += '<span class="label">Total Landed Cost</span>';
        html += '<span class="value">' + inventory_manager.currency_symbol + totalLandedCost.toFixed(2) + '</span>';
        html += '</div>';

        html += '</div>'; // End product-summary
        html += '<span class="toggle-icon dashicons dashicons-arrow-down-alt2"></span>';
        html += '</div>'; // End product-header
        
        // Batches container
        html += '<div class="batches-container">';
        
        // Render each batch
        $.each(product.batches, function(index, batch) {
            html += renderBatch(batch);
        });
        
        html += '</div>'; // End batches-container
        html += '</div>'; // End product-section
        
        return html;
    }
    
    /**
     * Render batch section
     */
    function renderBatch(batch) {
        let html = '<div class="batch-section">';
        
        // Batch header
        const expiryClass = batch.expiry_range ? ' expiry-' + batch.expiry_range : '';
        html += '<div class="batch-header">';
        
        html += '<div class="batch-supplier' + expiryClass + '">';
        html += '<span class="label">Supplier</span>';
        html += '<span class="value">' + (batch.supplier_name || '-') + '</span>';
        html += '</div>';

        html += '<div class="batch-number' + expiryClass + '">';
        html += '<span class="label">Batch</span>';
        html += '<span class="value">' + batch.batch_number + '</span>';
        html += '</div>';
        
        html += '<div class="batch-expiry' + expiryClass + '">';
        html += '<span class="label">Expiry</span>';
        html += '<span class="value">' + (batch.expiry_formatted || '-') + '</span>';
        html += '</div>';
        
        html += '<div class="batch-origin' + expiryClass + '">';
        html += '<span class="label">Origin</span>';
        html += '<span class="value">' + (batch.origin || '-') + '</span>';
        html += '</div>';
        
        html += '<div class="batch-location' + expiryClass + '">';
        html += '<span class="label">Location</span>';
        html += '<span class="value">' + (batch.location || '-') + '</span>';
        html += '</div>';

        html += '<span class="toggle-icon dashicons dashicons-arrow-down-alt2"></span>';
        html += '</div>'; // End batch-header
        
        // Batch details
        html += '<div class="batch-details" style="display: none;">';
        
        
        html += '</div>'; // End batch-details
        
        // Movement log
        html += '<div class="movement-log" style="display: none;">';
        html += '<div class="batch-header">';
        
        html += '<div class="batch-stock ' + expiryClass + '">';
        html += '<span class="label">Stock Qty</span>';
        html += '<span class="value">' + parseFloat(batch.stock_qty).toFixed(2) + '</span>';
        html += '</div>';
        // Newly requested fields
        html += '<div class="batch-unit-cost ' + expiryClass + '">';
        html += '<span class="label">Unit Cost</span>';
        html += '<span class="value">' + inventory_manager.currency_symbol + (parseFloat(batch.unit_cost || 0).toFixed(2)) + '</span>';
        html += '</div>';

        html += '<div class="batch-stock-cost ' + expiryClass + '">';
        html += '<span class="label">Stock Cost</span>';
        html += '<span class="value">' + (batch.stock_cost_formatted || (inventory_manager.currency_symbol + parseFloat(batch.stock_cost || 0).toFixed(2))) + '</span>';
        html += '</div>';

        html += '<div class="batch-freight ' + expiryClass + '">';
        html += '<span class="label">Freight Markup</span>';
        html += '<span class="value">' + inventory_manager.currency_symbol + (parseFloat(batch.freight_markup || 0).toFixed(2)) + '</span>';
        html += '</div>';

        html += '<div class="batch-landed-cost ' + expiryClass + '">';
        html += '<span class="label">Landed Cost</span>';
        html += '<span class="value">' + (batch.landed_cost_formatted || (inventory_manager.currency_symbol + parseFloat(batch.landed_cost || 0).toFixed(2))) + '</span>';
        html += '</div>';
        html += '</div>'; // End batch-details

        
        if (batch.movements && batch.movements.length > 0) {
            html += '<div class="log-header">';
            html += '<div class="log-date">Date & Time</div>';
            html += '<div class="log-type">Type</div>';
            html += '<div class="log-reference">Reference</div>';
            html += '<div class="log-in">Stock In</div>';
            html += '<div class="log-out">Stock Out</div>';
            html += '<div class="log-out">Action</div>';
            html += '</div>';
            
            html += '<div class="log-entries">';
            
            // Render each movement
            $.each(batch.movements, function(index, movement) {
                html += '<div class="log-entry">';
                html += '<div class="log-date">' + movement.date_time + '</div>';
                html += '<div class="log-type">' + movement.movement_type + '</div>';
                html += '<div class="log-reference">' + movement.reference + '</div>';
                html += '<div class="log-in">' + (movement.stock_in || '') + '</div>';
                html += '<div class="log-out">' + (movement.stock_out || '') + '</div>';
                html += '<div class="log-actions"><button class="button delete-entry-btn" data-id="' + movement.id + '">Delete</button></div>';
                html += '</div>';
            });
            html += '<div class="log-entry">';
            html += '<div class="log-date"></div>';
            html += '<div class="log-type"></div>';
            html += '<div class="log-reference"></div>';
            html += '<div class="log-in"></div>';
            html += '<div class="log-out"></div>';
            html += '<div class="log-actions"><button class="button add-adjustment-btn" data-batch-id="' + batch.id + '">Add Adjustment</button></div>';
            html += '</div>';
            
            html += '</div>'; // End log-entries
        } else {
            html += '<div class="no-movements">No movements recorded</div>';
        }
        
        html += '</div>'; // End movement-log
        html += '</div>'; // End batch-section
        
        return html;
    }
    
    /**
     * Open adjustment modal
     */
    function openAdjustmentModal(batchId) {
        // Get batch info
        let batch = null;
        
        $.each(state.products, function(i, product) {
            $.each(product.batches, function(j, b) {
                if (b.id == batchId) {
                    batch = b;
                    return false;
                }
            });
            
            if (batch) {
                return false;
            }
        });
        
        if (!batch) {
            alert('Batch not found');
            return;
        }
        
        // Fetch adjustment types
        $.ajax({
            url: inventory_manager.api_url + '/adjustment-types',
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', inventory_manager.nonce);
            },
            success: function(response) {
                console.log(response)
                createAdjustmentModal(batch, response);
            },
            error: function() {
                alert('Error loading adjustment types');
            }
        });
    }
    
    /**
     * Create adjustment modal
     */
    function createAdjustmentModal(batch, adjustmentTypes) {
        // console.log(adjustmentTypes);
        // Create modal HTML
        let html = '<div class="inventory-modal adjustment-modal">';
        html += '<div class="modal-content">';
        
        html += '<div class="modal-header">';
        html += '<h3>Add Adjustment for Batch ' + batch.batch_number + '</h3>';
        html += '<button class="close-modal">&times;</button>';
        html += '</div>';
        
        html += '<div class="modal-body">';
        
        html += '<div class="batch-info">';
        html += '<p><strong>SKU:</strong> ' + batch.sku + '</p>';
        html += '<p><strong>Current Stock:</strong> ' + parseFloat(batch.stock_qty).toFixed(2) + '</p>';
        html += '</div>';
        
        html += '<form id="adjustment-form">';
        html += '<input type="hidden" name="batch_id" value="' + batch.id + '">';
        
        // Adjustment type
        html += '<div class="form-field required">';
        html += '<label for="adjustment_type">Adjustment Type</label>';
        html += '<select name="adjustment_type" id="adjustment_type" required>';
        html += '<option value="">Select adjustment type</option>';
        
        $.each(adjustmentTypes, function(index, type) {
            html += '<option value="' + type.id + '" data-calculation="' + type.calculation + '">' + type.name + '</option>';
        });
        
        html += '</select>';
        html += '</div>';
        
        // Quantity
        html += '<div class="form-field required">';
        html += '<label for="adjustment_qty">Quantity</label>';
        html += '<input type="number" name="adjustment_qty" id="adjustment_qty" step="0.01" min="0.01" required>';
        html += '<p class="description">The system will automatically add or deduct based on the adjustment type.</p>';
        html += '</div>';
        
        // Reference
        html += '<div class="form-field required">';
        html += '<label for="adjustment_reference">Reference</label>';
        html += '<input type="text" name="adjustment_reference" id="adjustment_reference" required>';
        html += '<p class="description">Add a reference for this adjustment (e.g., invoice number).</p>';
        html += '</div>';
        
        html += '<div class="form-actions">';
        html += '<button type="submit" class="button submit-adjustment">Save Adjustment</button>';
        html += '<button type="button" class="button secondary cancel-adjustment">Cancel</button>';
        html += '</div>';
        
        html += '</form>';
        html += '</div>'; // End modal-body
        
        html += '</div>'; // End modal-content
        html += '</div>'; // End inventory-modal
        
        // Append modal to body
        $('body').append(html);
        
        // Set up modal events
        $('.close-modal, .cancel-adjustment').on('click', function() {
            $('.inventory-modal').remove();
        });
        
        // Form submission
        $('#adjustment-form').on('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                batch_id: batch.id,
                adjustment_type: $('#adjustment_type').val(),
                adjustment_qty: $('#adjustment_qty').val(),
                adjustment_reference: $('#adjustment_reference').val()
            };
            
            // Validate form
            if (!formData.adjustment_type || !formData.adjustment_qty || !formData.adjustment_reference) {
                alert('Please fill in all required fields');
                return;
            }
            
            // Submit adjustment
            $.ajax({
                url: inventory_manager.api_url + '/adjustment',
                method: 'POST',
                data: formData,
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', inventory_manager.nonce);
                },
                success: function(response) {
                    $('.inventory-modal').remove();
                    alert('Adjustment saved successfully');
                    loadLogs(); // Reload logs
                },
                error: function(xhr) {
                    let message = 'Error saving adjustment';
                    
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    
                    alert(message);
                }
            });
        });
    }
    
    /**
     * Export logs
     */
    function exportLogs() {
        // Prepare export params
        const params = {
            format: $('.logs-export-format').val() || 'csv',
            type: 'detailed-logs',
            period: state.period,
            batch_period: state.batch_period,
            search: state.search,
            expiry_filters: state.expiry_filters
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
            form.append($('<input></input>')
                .attr('type', 'hidden')
                .attr('name', key)
                .attr('value', value));
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