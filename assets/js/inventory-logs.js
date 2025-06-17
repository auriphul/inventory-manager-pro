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
        search: '',
        order: 'ASC',
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
            loadLogs();
        });
        
        // Export button
        $('.logs-export-btn').on('click', function() {
            exportLogs();
        });
        
        // Toggle product details
        $(document).on('click', '.product-header', function() {
            $(this).closest('.product-section').find('.batches-container').slideToggle();
        });
        
        // Toggle batch details
        $(document).on('click', '.toggle-batch-details', function() {
            $(this).closest('tr').next('.batch-details-row').toggle();
        });
        
        // Add adjustment button
        $(document).on('click', '.add-adjustment-btn', function() {
            const batchId = $(this).data('batch-id');
            openAdjustmentModal(batchId);
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
            search: state.search,
            order: state.order
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
    }
    
    /**
     * Render product section
     */
    function renderProduct(product) {
        let html = '<div class="product-section">';

        // Product header
        html += '<div class="product-header">';
        html += '<div class="product-info">';
        html += '<strong>' + product.product_name + '</strong><br>';
        html += '<span class="sku">SKU: ' + product.sku + '</span>';
        html += '</div>';

        // Product summary
        html += '<div class="product-summary">';

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

        html += '<div class="batch-count">';
        html += '<span class="label">Batches</span>';
        html += '<span class="value">' + product.batches.length + '</span>';
        html += '</div>';

        html += '<div class="total-stock">';
        html += '<span class="label">Total Stock Qty</span>';
        html += '<span class="value">' + totalStock.toFixed(2) + '</span>';
        html += '</div>';

        html += '<div class="avg-unit-cost">';
        html += '<span class="label">Avg Unit Cost</span>';
        html += '<span class="value">' + avgUnitCost.toFixed(2) + '</span>';
        html += '</div>';

        html += '<div class="total-stock-cost">';
        html += '<span class="label">Total Stock Cost</span>';
        html += '<span class="value">' + totalStockCost.toFixed(2) + '</span>';
        html += '</div>';

        html += '<div class="avg-freight">';
        html += '<span class="label">Avg Freight Markup</span>';
        html += '<span class="value">' + avgFreight.toFixed(2) + '</span>';
        html += '</div>';

        html += '<div class="total-landed-cost">';
        html += '<span class="label">Total Landed Cost</span>';
        html += '<span class="value">' + totalLandedCost.toFixed(2) + '</span>';
        html += '</div>';

        html += '</div>'; // End product-summary
        html += '</div>'; // End product-header

        html += '<div class="batches-container" style="display:none;">';
        html += '<table class="widefat striped batch-table">';
        html += '<thead><tr>';
        html += '<th>Batch</th>';
        html += '<th>Stock Qty</th>';
        html += '<th>Expiry</th>';
        html += '<th>Unit Cost</th>';
        html += '<th>Stock Cost</th>';
        html += '<th>Freight Markup</th>';
        html += '<th>Landed Cost</th>';
        html += '<th>Actions</th>';
        html += '</tr></thead><tbody>';

        $.each(product.batches, function(index, batch) {
            html += renderBatch(batch);
        });

        html += '</tbody></table></div>';
        html += '</div>'; // End product-section

        return html;
    }
    
    /**
     * Render batch section
     */
    function renderBatch(batch) {
        let html = '<tr class="batch-row" data-batch-id="' + batch.id + '">';
        html += '<td>' + batch.batch_number + '</td>';
        html += '<td>' + batch.stock_qty + '</td>';
        html += '<td>' + (batch.expiry_formatted || 'N/A') + '</td>';
        html += '<td>' + (parseFloat(batch.unit_cost || 0).toFixed(2)) + '</td>';
        html += '<td>' + (batch.stock_cost_formatted || parseFloat(batch.stock_cost || 0).toFixed(2)) + '</td>';
        html += '<td>' + (parseFloat(batch.freight_markup || 0).toFixed(2)) + '</td>';
        html += '<td>' + (batch.landed_cost_formatted || parseFloat(batch.landed_cost || 0).toFixed(2)) + '</td>';
        html += '<td><button class="button toggle-batch-details" data-batch-id="' + batch.id + '">Details</button></td>';
        html += '</tr>';

        html += '<tr class="batch-details-row" style="display:none;">';
        html += '<td colspan="8">';
        html += '<div class="batch-details">';
        html += '<span class="label">Supplier</span> <span class="value">' + (batch.supplier_name || 'N/A') + '</span><br>';
        html += '<span class="label">Origin</span> <span class="value">' + (batch.origin || 'N/A') + '</span><br>';
        html += '<span class="label">Location</span> <span class="value">' + (batch.location || 'N/A') + '</span>';
        html += '<div class="batch-actions">';
        html += '<button class="button add-adjustment-btn" data-batch-id="' + batch.id + '">Add Adjustment</button>';
        html += '</div>';
        html += '</div>';

        html += '<div class="movement-log">';
        if (batch.movements && batch.movements.length > 0) {
            html += '<table class="widefat striped movements-table">';
            html += '<thead><tr>';
            html += '<th>Date & Time</th>';
            html += '<th>Type</th>';
            html += '<th>Reference</th>';
            html += '<th>Stock In</th>';
            html += '<th>Stock Out</th>';
            html += '</tr></thead><tbody>';
            $.each(batch.movements, function(index, movement) {
                html += renderMovement(movement);
            });
            html += '</tbody></table>';
        } else {
            html += '<div class="no-movements">No movements recorded</div>';
        }
        html += '</div>'; // movement-log
        html += '</td></tr>';

        return html;
    }

    function renderMovement(movement) {
        let html = '<tr>';
        html += '<td>' + movement.date_time + '</td>';
        html += '<td>' + movement.movement_type + '</td>';
        html += '<td>' + movement.reference + '</td>';
        html += '<td>' + (movement.stock_in || '') + '</td>';
        html += '<td>' + (movement.stock_out || '') + '</td>';
        html += '</tr>';
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
        html += '<p><strong>Current Stock:</strong> ' + batch.stock_qty + '</p>';
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
            search: state.search
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