/**
 * Inventory Manager Pro - Inventory Forms JS
 * 
 * Handles the manual entry and import forms
 */
(function($) {
    'use strict';

    /**
     * Initialize the inventory forms
     */
    function init() {
        // Set up tabs if present
        setupTabs();
        
        // Set up SKU autocomplete
        setupSkuAutocomplete();
        
        // Set up supplier fields
        setupSupplierFields();
        
        // Set up form submission
        setupFormSubmission();
        
        // Set up import form
        setupImportForm();
    }

    /**
     * Set up tab navigation
     */
    function setupTabs() {
        $('.tab-btn').on('click', function() {
            const tabId = $(this).data('tab');
            
            // Update active tab button
            $('.tab-btn').removeClass('active');
            $(this).addClass('active');
            
            // Show selected tab content
            $('.tab-pane').removeClass('active');
            $('#' + tabId).addClass('active');
        });
    }
    
    /**
     * Set up SKU autocomplete
     */
    function setupSkuAutocomplete() {
        const skuInput = $('#sku');
        
        if (skuInput.length) {
            // Fetch SKUs
            $.ajax({
                url: inventory_manager.api_url + '/skus',
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', inventory_manager.nonce);
                },
                success: function(response) {
                    if (response.skus && response.skus.length) {
                        // Set up autocomplete
                        skuInput.autocomplete({
                            source: response.skus,
                            minLength: 2,
                            select: function(event, ui) {
                                // Fetch product info
                                fetchProductInfo(ui.item.value);
                            }
                        });
                    }
                }
            });
            
            // Handle manual input change
            skuInput.on('change', function() {
                const sku = $(this).val();
                
                if (sku) {
                    fetchProductInfo(sku);
                }
            });
        }
    }
    
    /**
     * Fetch product info by SKU
     */
    function fetchProductInfo(sku) {
        $.ajax({
            url: inventory_manager.api_url + '/product-info',
            method: 'GET',
            data: { sku: sku },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', inventory_manager.nonce);
            },
            success: function(response) {
                // Update product name field
                if (response.product_name) {
                    $('#product_name').val(response.product_name);
                }
                
                // Check if there are existing batches
                if (response.batches && response.batches.length) {
                    // Show existing batches info
                    let batchesHtml = '<div class="existing-batches">';
                    batchesHtml += '<h4>Existing Batches</h4>';
                    batchesHtml += '<table class="existing-batches-table">';
                    batchesHtml += '<thead><tr><th>Batch</th><th>Stock</th><th>Expiry</th><th>Location</th></tr></thead>';
                    batchesHtml += '<tbody>';
                    
                    $.each(response.batches, function(index, batch) {
                        batchesHtml += '<tr>';
                        batchesHtml += '<td>' + batch.batch_number + '</td>';
                        batchesHtml += '<td>' + batch.stock_qty + '</td>';
                        batchesHtml += '<td>' + (batch.expiry_formatted || 'N/A') + '</td>';
                        batchesHtml += '<td>' + (batch.location || 'N/A') + '</td>';
                        batchesHtml += '</tr>';
                    });
                    
                    batchesHtml += '</tbody></table></div>';
                    
                    $('.product-info-container').html(batchesHtml);
                } else {
                    $('.product-info-container').html('<p>No existing batches for this product.</p>');
                }
            },
            error: function() {
                $('.product-info-container').html('<p class="error">Error fetching product information.</p>');
            }
        });
    }
    
    /**
     * Set up supplier fields
     */
    function setupSupplierFields() {
        const supplierSelect = $('#supplier_id');
        const newSupplierInput = $('#new_supplier');
        const newSupplierTransitSelect = $('#new_supplier_transit');
        const useExistingRadio = $('input[name="supplier_option"][value="existing"]');
        const useNewRadio = $('input[name="supplier_option"][value="new"]');
        
        if (supplierSelect.length) {
            // Fetch suppliers
            $.ajax({
                url: inventory_manager.api_url + '/suppliers',
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', inventory_manager.nonce);
                },
                success: function(response) {
                    if (response.suppliers && response.suppliers.length) {
                        // Add options to select
                        $.each(response.suppliers, function(index, supplier) {
                            supplierSelect.append('<option value="' + supplier.id + '">' + supplier.name + '</option>');
                        });
                    }
                }
            });
            
            // Fetch transit times
            $.ajax({
                url: inventory_manager.api_url + '/transit-times',
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', inventory_manager.nonce);
                },
                success: function(response) {
                    if (response.transit_times && response.transit_times.length) {
                        // Add options to select
                        $.each(response.transit_times, function(index, transit) {
                            newSupplierTransitSelect.append('<option value="' + transit.id + '">' + transit.name + '</option>');
                        });
                    }
                }
            });
            
            // Toggle supplier fields based on radio selection
            useExistingRadio.on('change', function() {
                if ($(this).is(':checked')) {
                    $('.existing-supplier-row').show();
                    $('.new-supplier-row').hide();
                }
            });
            
            useNewRadio.on('change', function() {
                if ($(this).is(':checked')) {
                    $('.existing-supplier-row').hide();
                    $('.new-supplier-row').show();
                }
            });
        }
    }
    
    /**
     * Set up form submission
     */
    function setupFormSubmission() {
        const addBatchForm = $('#add-batch-form');
        
        if (addBatchForm.length) {
            addBatchForm.on('submit', function(e) {
                e.preventDefault();
                
                // Get form data
                const formData = {};
                
                // Basic batch info
                formData.sku = $('#sku').val();
                formData.batch_number = $('#batch_number').val();
                formData.stock_qty = $('#stock_qty').val();
                formData.reference = $('#reference').val();
                
                // Optional fields
                if ($('#expiry_date').val()) {
                    formData.expiry_date = $('#expiry_date').val();
                }
                
                if ($('#origin').val()) {
                    formData.origin = $('#origin').val();
                }
                
                if ($('#location').val()) {
                    formData.location = $('#location').val();
                }
                
                if ($('#unit_cost').val()) {
                    formData.unit_cost = $('#unit_cost').val();
                }
                
                if ($('#freight_markup').val()) {
                    formData.freight_markup = $('#freight_markup').val();
                }
                
                // Supplier info
                const supplierOption = $('input[name="supplier_option"]:checked').val();
                
                if (supplierOption === 'existing') {
                    formData.supplier_id = $('#supplier_id').val();
                } else if (supplierOption === 'new' && $('#new_supplier').val()) {
                    formData.new_supplier = $('#new_supplier').val();
                    formData.new_supplier_transit = $('#new_supplier_transit').val();
                }
                
                // Validate required fields
                if (!formData.sku) {
                    alert('Please enter a SKU');
                    $('#sku').focus();
                    return;
                }
                
                if (!formData.batch_number) {
                    alert('Please enter a batch number');
                    $('#batch_number').focus();
                    return;
                }
                
                if (!formData.stock_qty) {
                    alert('Please enter stock quantity');
                    $('#stock_qty').focus();
                    return;
                }
                
                if (!formData.reference) {
                    alert('Please enter a reference');
                    $('#reference').focus();
                    return;
                }
                
                // Submit form data
                $.ajax({
                    url: inventory_manager.api_url + '/batch',
                    method: 'POST',
                    data: formData,
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', inventory_manager.nonce);
                        
                        // Disable submit button and show loading
                        $('#add-batch-btn').prop('disabled', true).text('Saving...');
                    },
                    success: function(response) {
                        alert('Batch added successfully');
                        
                        // Reset form
                        addBatchForm[0].reset();
                        $('.product-info-container').html('');
                        
                        // Re-enable submit button
                        $('#add-batch-btn').prop('disabled', false).text('Add Batch');
                    },
                    error: function(xhr) {
                        let message = 'Error adding batch';
                        
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        
                        alert(message);
                        
                        // Re-enable submit button
                        $('#add-batch-btn').prop('disabled', false).text('Add Batch');
                    }
                });
            });
        }
    }
    
    /**
     * Set up import form
     */
    function setupImportForm() {
        const importForm = $('#import-form');
        
        if (importForm.length) {
            // File selection
            $('#file').on('change', function() {
                const fileName = $(this).val().split('\\').pop();
                $('#file-name').text(fileName || 'No file selected');
            });
            
            // Download sample
            $('#download-sample').on('click', function(e) {
                e.preventDefault();
                
                const sampleData = [
                    ['inventory_sku', 'inventory_batch', 'inventory_stock_qty', 'inventory_reference', 'inventory_supplier', 'inventory_expiry', 'inventory_origin', 'inventory_location', 'inventory_unit_cost', 'inventory_freight_margin'],
                    ['SKU123', 'BATCH001', '10', 'Initial Stock', 'Supplier A', '2023-12-31', 'USA', 'Warehouse A', '10.50', '1.25'],
                    ['SKU456', 'BATCH002', '5', 'Initial Stock', 'Supplier B', '2023-10-15', 'China', 'Warehouse B', '8.75', '2.00']
                ];
                
                let csvContent = '';
                
                // Convert data to CSV
                sampleData.forEach(function(row) {
                    csvContent += row.join(',') + '\r\n';
                });
                
                // Create blob and download link
                const blob = new Blob([csvContent], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                
                a.setAttribute('href', url);
                a.setAttribute('download', 'inventory_import_sample.csv');
                a.click();
                
                window.URL.revokeObjectURL(url);
            });
            
            // Form submission
            importForm.on('submit', function(e) {
                e.preventDefault();
                
                const fileInput = $('#file')[0];
                
                if (!fileInput.files.length) {
                    alert('Please select a file to import');
                    return;
                }
                
                const file = fileInput.files[0];
                const formData = new FormData();

                // Required parameters for WordPress AJAX
                formData.append('action', 'import_batches');
                if (typeof inventory_manager_admin !== 'undefined') {
                    formData.append('security', inventory_manager_admin.nonce);
                }

                formData.append('file', file);
                
                $.ajax({
                    url: ajaxurl, // WP admin AJAX URL
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function() {
                        // Disable submit button and show loading
                        $('#import-btn').prop('disabled', true).text('Importing...');
                    },
                    success: function(response) {
                        if (response.success) {
                            let message = 'Import completed';
                            
                            if (response.data) {
                                message += '\n\n' + response.data.success + ' batches imported successfully';
                                
                                if (response.data.errors && response.data.errors.length) {
                                    message += '\n\n' + response.data.errors.length + ' errors encountered:';
                                    
                                    $.each(response.data.errors, function(index, error) {
                                        message += '\n- ' + error;
                                    });
                                }
                            }
                            
                            alert(message);
                            
                            // Reset form
                            importForm[0].reset();
                            $('#file-name').text('No file selected');
                        } else {
                            alert('Error importing batches: ' + (response.data || 'Unknown error'));
                        }
                        
                        // Re-enable submit button
                        $('#import-btn').prop('disabled', false).text('Import');
                    },
                    error: function() {
                        alert('Error importing batches');
                        
                        // Re-enable submit button
                        $('#import-btn').prop('disabled', false).text('Import');
                    }
                });
            });
        }
    }
    
    // Initialize on document ready
    $(document).ready(function() {
        init();
    });

})(jQuery);