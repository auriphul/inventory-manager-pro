/**
 * Inventory Manager Pro - Main JS
 * 
 * Main JavaScript file for the Inventory Manager Pro plugin
 */
(function($) {
    'use strict';

    // Global variables
    const inventoryManager = {
        init: function() {
            // Initialize components based on current page
            this.initCommon();
            this.initDashboard();
            // this.initSettings();
            this.initWooCommerceIntegration();
        },

        /**
         * Initialize common elements across all pages
         */
        initCommon: function() {
            // Date picker initialization
            if ($.fn.datepicker) {
                $('.date-picker').datepicker({
                    dateFormat: 'yy-mm-dd',
                    changeMonth: true,
                    changeYear: true
                });
            }

            // Color picker initialization for all admin pages
            if ($.fn.wpColorPicker) {
                $('input[type="color"]').wpColorPicker({
                    defaultColor: false,
                    change: function() {},
                    clear: function() {},
                    hide: true,
                    palettes: true
                });
                
                // Force hex format using global event delegation
                $(document).on('click', '.wp-color-result', function() {
                    setTimeout(function() {
                        const $picker = $('.iris-picker:visible');
                        if ($picker.length) {
                            // Try multiple possible selectors for the format toggle
                            let $formatButton = $picker.find('.iris-square-value');
                            
                            // If not found, try other possible selectors
                            if (!$formatButton.length) {
                                $formatButton = $picker.find('[data-current]');
                            }
                            
                            // If still not found, look for any element containing "rgb" or "hex"
                            if (!$formatButton.length) {
                                $picker.find('*').each(function() {
                                    const text = $(this).text().toLowerCase();
                                    if (text.indexOf('rgb') !== -1 || text.indexOf('hex') !== -1) {
                                        $formatButton = $(this);
                                        return false;
                                    }
                                });
                            }
                            
                            // Click to switch to hex if currently showing RGB
                            if ($formatButton.length) {
                                const currentText = $formatButton.text().toLowerCase();
                                const currentAttr = $formatButton.attr('data-current');
                                
                                if (currentText.indexOf('rgb') !== -1 || currentAttr === 'rgb') {
                                    $formatButton.click();
                                }
                            }
                        }
                    }, 200);
                });
            }

            // Tooltips
            this.initTooltips();

            // Notification system
            this.initNotifications();

            // Initialize brand selects on settings page
            this.initBrandSelect();
        },

        /**
         * Initialize dashboard components
         */
        initDashboard: function() {
            // Only run on dashboard page
            if (!$('.inventory-manager').length) {
                return;
            }

            // Get current tab
            const currentTab = this.getCurrentTab();

            // Initialize tab-specific functionality
            switch (currentTab) {
                case 'overview':
                    this.initOverviewTab();
                    break;
                case 'detailed-logs':
                    this.initDetailedLogsTab();
                    break;
                case 'add-manually':
                    this.initAddManuallyTab();
                    break;
                case 'import':
                    this.initImportTab();
                    break;
                case 'settings':
                    this.initSettingsTab();
                    break;
            }

            // Initialize global dashboard components
            this.initTabNavigation();
        },

        /**
         * Get current tab from URL or default to overview
         */
        getCurrentTab: function() {
            // Check URL for tab parameter
            const urlParams = new URLSearchParams(window.location.search);
            let tab = urlParams.get('tab');

            // Default to overview if no tab specified
            if (!tab) {
                tab = 'overview';
            }

            return tab;
        },

        /**
         * Initialize tab navigation
         */
        initTabNavigation: function() {
            $('.nav-tabs a').on('click', function(e) {
                // If using regular links, let the browser handle navigation
                if ($(this).attr('href').indexOf('?') !== -1) {
                    return;
                }

                e.preventDefault();
                
                const tab = $(this).data('tab');
                
                // Update URL without reloading
                if (history.pushState) {
                    const newUrl = new URL(window.location);
                    newUrl.searchParams.set('tab', tab);
                    window.history.pushState({path: newUrl.toString()}, '', newUrl.toString());
                }
                
                // Show active tab
                $('.nav-tabs li').removeClass('active');
                $(this).parent().addClass('active');
                
                $('.tab-content > div').hide();
                $('#' + tab + '-tab').show();
            });
        },

        /**
         * Initialize overview tab
         */
        initOverviewTab: function() {
            // Check if we're on the overview tab
            if (!$('.inventory-manager-overview').length) {
                return;
            }

            // Summary cards
            this.loadInventorySummary();

            // Initialize charts
            this.initInventoryCharts();
        },

        /**
         * Initialize detailed logs tab
         */
        initDetailedLogsTab: function() {
            // Check if we're on the detailed logs tab
            // if (!$('.inventory-manager-logs').length) {
            //     return;
            // }

            // Initialize date range picker if available
            if ($.fn.daterangepicker) {
                $('.logs-date-range').daterangepicker({
                    alwaysShowCalendars: true,
                    startDate: moment().utc().subtract(29, 'days'),
                    endDate: moment().utc(),
                    locale: {
                        format: 'YYYY-MM-DD'
                    }
                });

                $('.batch-date-range').daterangepicker({
                    ranges: {
                        'Today': [moment(), moment()],
                        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                        'This Month': [moment().startOf('month'), moment().endOf('month')],
                        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                    },
                    alwaysShowCalendars: true,
                    startDate: moment().subtract(29, 'days'),
                    endDate: moment()
                });
            }
        },

        /**
         * Initialize add manually tab
         */
        initAddManuallyTab: function() {
            // Check if we're on the add manually tab
            console.log('initAddManuallyTab')

            // Product search autocomplete
            this.initProductSearch();

            // Dynamic validation
            this.initFormValidation();
            if ($.fn.daterangepicker) {
                $('.expiry_date').daterangepicker({
                    singleDatePicker: true,
                    showDropdowns: true,
                    // startDate: moment(),
                    locale: {
                      format: 'DD/MM/YYYY'
                    },
                    minYear: 2024,
                    // maxYear: parseInt(moment().format('YYYY'),10)
                }, function(chosen_date) {
                    // Create a hidden field for submission in Y-m-d format
                    $('#expiry_date').val(chosen_date.format('YYYY-MM-DD'));
                });
            }
        },

        /**
         * Initialize import tab
         */
        initImportTab: function() {
            // Check if we're on the import tab
            if (!$('#import-tab').length) {
                return;
            }

            // File input styling
            this.initFileInput();

            // Import preview
            this.initImportPreview();
        },

        /**
         * Initialize settings tab
         */
        initSettingsTab: function() {
            // Check if we're on the settings tab
            if (!$('#settings-tab').length) {
                return;
            }

            // Color picker initialization (global handler will manage hex format)
            if ($.fn.wpColorPicker) {
                $('input[type="color"]').wpColorPicker({
                    defaultColor: false,
                    change: function() {},
                    clear: function() {},
                    hide: true,
                    palettes: true
                });
            }

            // Sortable lists
            this.initSortable();

            // Dynamic field addition
            this.initDynamicFields();

            // Initialize brand dropdowns
            this.initBrandSelect();

            // Initialize archive badge settings
            this.initArchiveBadgeSettings();

            // Force hex format for all color pickers (including new ones)
            this.forceHexFormatOnAllColorPickers();

            // Debug color picker structure
            this.debugColorPicker();
        },

        /**
         * Initialize WooCommerce integration
         */
        initWooCommerceIntegration: function() {
            // Only run on WooCommerce order edit pages
            if (!$('.woocommerce-order-data').length) {
                return;
            }

            // Batch selection in order items
            this.initBatchSelection();
        },

        /**
         * Initialize batch selection in WooCommerce orders
         */
        initBatchSelection: function() {
            // Batch selection dropdown
            $(document).on('change', '.batch-select', function() {
                const batchId = $(this).val();
                const itemId = $(this).data('item-id');
                
                $.ajax({
                    url: woocommerce_admin.ajax_url,
                    data: {
                        action: 'select_order_item_batch',
                        batch_id: batchId,
                        item_id: itemId,
                        security: inventory_manager_admin.order_nonce
                    },
                    type: 'POST',
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            inventoryManager.showNotification('Batch selected successfully', 'success');
                        } else {
                            inventoryManager.showNotification('Error selecting batch', 'error');
                        }
                    }
                });
            });
        },

        /**
         * Load inventory summary data for dashboard
         */
        loadInventorySummary: function() {
            if (!$('.inventory-summary').length) {
                return;
            }

            $.ajax({
                url: inventory_manager.api_url + '/summary',
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', inventory_manager.nonce);
                },
                success: function(response) {
                    // Update summary cards
                    if (response.product_count) {
                        $('.product-count').text(response.product_count);
                    }
                    
                    if (response.batch_count) {
                        $('.batch-count').text(response.batch_count);
                    }
                    
                    if (response.total_stock) {
                        $('.total-stock').text(response.total_stock);
                    }
                    
                    if (response.low_stock) {
                        $('.low-stock').text(response.low_stock);
                    }
                    
                    if (response.expiring_soon) {
                        $('.expiring-soon').text(response.expiring_soon);
                    }
                }
            });
        },

        /**
         * Initialize inventory charts
         */
        initInventoryCharts: function() {
            if (!$('#expiry-chart').length || !window.Chart) {
                return;
            }

            // Fetch data for expiry chart
            $.ajax({
                url: inventory_manager.api_url + '/expiry-summary',
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', inventory_manager.nonce);
                },
                success: function(response) {
                    if (response.expiry_data) {
                        // Create expiry chart
                        const ctx = document.getElementById('expiry-chart').getContext('2d');
                        new Chart(ctx, {
                            type: 'pie',
                            data: {
                                labels: ['6+ months', '3-6 months', '1-3 months', '< 1 month', 'Expired', 'No expiry'],
                                datasets: [{
                                    data: [
                                        response.expiry_data.months_6_plus,
                                        response.expiry_data.months_3_6,
                                        response.expiry_data.months_1_3,
                                        response.expiry_data.months_less_1,
                                        response.expiry_data.expired,
                                        response.expiry_data.no_expiry
                                    ],
                                    backgroundColor: [
                                        '#e3f2fd',
                                        '#e8f5e9',
                                        '#fff9c4',
                                        '#ffecb3',
                                        '#ffccbc',
                                        '#f5f5f5'
                                    ],
                                    borderColor: [
                                        '#0d47a1',
                                        '#1b5e20',
                                        '#f57f17',
                                        '#e65100',
                                        '#bf360c',
                                        '#757575'
                                    ],
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                legend: {
                                    position: 'right'
                                },
                                title: {
                                    display: true,
                                    text: 'Stock by Expiry Range'
                                }
                            }
                        });
                    }
                }
            });
        },

        /**
         * Initialize product search
         */
        initProductSearch: function() {
            const skuInput = $('#sku');
            
            if (!skuInput.length) {
                return;
            }
            
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
                                inventoryManager.fetchProductInfo(ui.item.value);
                            }
                        });
                    }
                }
            });
            
            // Handle manual input change
            skuInput.on('change', function() {
                const sku = $(this).val();
                
                if (sku) {
                    inventoryManager.fetchProductInfo(sku);
                }
            });
        },

        /**
         * Fetch product info by SKU
         */
        fetchProductInfo: function(sku) {
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

                    // Auto-select brand if available
                    if (response.brand_ids && response.brand_ids.length) {
                        const brandSelect = $('#brand_id');
                        const brandId = response.brand_ids[0];
                        brandSelect.val(brandId);
                        brandSelect.data('selected', brandId);
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
        },

        /**
         * Initialize form validation
         */
        initFormValidation: function() {
            const addBatchForm = $('#add-batch-form');
            
            if (!addBatchForm.length) {
                return;
            }
            
            // Validate on submit
            addBatchForm.on('submit', function(e) {
                const requiredFields = ['sku', 'batch_number', 'stock_qty', 'reference'];
                let isValid = true;
                
                // Check each required field
                $.each(requiredFields, function(index, field) {
                    const input = $('#' + field);
                    
                    if (!input.val().trim()) {
                        isValid = false;
                        input.addClass('error');
                        
                        // Show error message
                        if (!input.next('.error-message').length) {
                            input.after('<span class="error-message">This field is required</span>');
                        }
                    } else {
                        input.removeClass('error');
                        input.next('.error-message').remove();
                    }
                });
                
                // Check brand selection
                if (!$('#brand_id').val()) {
                    isValid = false;
                    $('#brand_id').addClass('error');

                    if (!$('#brand_id').next('.error-message').length) {
                        $('#brand_id').after('<span class="error-message">Please select a brand</span>');
                    }
                }
                
                if (!isValid) {
                    e.preventDefault();
                    inventoryManager.showNotification('Please correct the errors in the form', 'error');
                    return false;
                }
            });
            
            // Clear errors on input
            addBatchForm.find('input, select').on('input change', function() {
                $(this).removeClass('error');
                $(this).next('.error-message').remove();
            });
        },

        /**
         * Initialize file input styling
         */
        initFileInput: function() {
            $('#file').on('change', function() {
                const fileName = $(this).val().split('\\').pop();
                $('#file-name').text(fileName || 'No file selected');
            });
        },

        /**
         * Initialize import preview
         */
        initImportPreview: function() {
            $('#preview-import').on('click', function(e) {
                e.preventDefault();
                
                const fileInput = $('#file')[0];
                
                if (!fileInput.files.length) {
                    inventoryManager.showNotification('Please select a file to preview', 'error');
                    return;
                }
                
                const file = fileInput.files[0];
                const formData = new FormData();
                
                formData.append('action', 'preview_import');
                formData.append('file', file);
                formData.append('security', inventory_manager.nonce);
                
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function() {
                        $('#preview-import').prop('disabled', true).text('Loading preview...');
                    },
                    success: function(response) {
                        $('#preview-import').prop('disabled', false).text('Preview Import');
                        
                        if (response.success && response.data) {
                            // Show preview
                            inventoryManager.renderImportPreview(response.data);
                        } else {
                            inventoryManager.showNotification('Error previewing import file: ' + (response.data || 'Unknown error'), 'error');
                        }
                    },
                    error: function() {
                        $('#preview-import').prop('disabled', false).text('Preview Import');
                        inventoryManager.showNotification('Error previewing import file', 'error');
                    }
                });
            });
        },

        /**
         * Render import preview
         */
        renderImportPreview: function(previewData) {
            if (!previewData.rows || !previewData.rows.length) {
                $('.import-preview').html('<p>No data to preview</p>');
                return;
            }
            
            let html = '<div class="preview-container">';
            html += '<h3>Import Preview</h3>';
            html += '<p>Showing ' + Math.min(previewData.rows.length, 10) + ' of ' + previewData.rows.length + ' rows</p>';
            
            html += '<table class="preview-table">';
            html += '<thead><tr>';
            
            // Headers
            $.each(previewData.headers, function(index, header) {
                html += '<th>' + header + '</th>';
            });
            
            html += '</tr></thead><tbody>';
            
            // Rows (limited to 10)
            const maxRows = Math.min(previewData.rows.length, 10);
            
            for (let i = 0; i < maxRows; i++) {
                html += '<tr>';
                
                $.each(previewData.headers, function(index, header) {
                    html += '<td>' + (previewData.rows[i][header] || '') + '</td>';
                });
                
                html += '</tr>';
            }
            
            html += '</tbody></table>';
            
            // Validation results
            if (previewData.validation) {
                html += '<div class="validation-results">';
                html += '<h4>Validation Results</h4>';
                
                if (previewData.validation.valid) {
                    html += '<p class="success">File is valid and ready to import</p>';
                } else {
                    html += '<p class="error">There are issues with the import file:</p>';
                    html += '<ul class="error-list">';
                    
                    $.each(previewData.validation.errors, function(index, error) {
                        html += '<li>' + error + '</li>';
                    });
                    
                    html += '</ul>';
                }
                
                html += '</div>';
            }
            
            html += '</div>';
            
            $('.import-preview').html(html);
        },

        /**
         * Initialize sortable lists
         */
        initSortable: function() {
            if ($.fn.sortable) {
                $('.sortable-list').sortable({
                    handle: '.sort-handle',
                    update: function() {
                        // Update order in hidden field
                        const order = [];
                        
                        $(this).find('li').each(function() {
                            order.push($(this).data('id'));
                        });
                        
                        $('#sortable-order').val(order.join(','));
                    }
                });
            }
        },

        /**
         * Initialize dynamic fields
         */
        initDynamicFields: function() {
            // Add field button
            $('.add-field-btn').on('click', function(e) {
                e.preventDefault();
                
                const fieldType = $(this).data('field-type');
                const template = $('#' + fieldType + '-template').html();
                const container = $('.' + fieldType + '-container');
                const index = container.children().length;
                
                // Replace placeholders with index
                const newField = template.replace(/\{index\}/g, index);
                
                container.append(newField);
                
                // Initialize color picker for new field (global handler will manage hex format)
                if ($.fn.wpColorPicker) {
                    container.find('input[type="color"]').wpColorPicker({
                        defaultColor: false,
                        change: function() {},
                        clear: function() {},
                        hide: true,
                        palettes: true
                    });
                }
            });
            
            // Remove field button
            $(document).on('click', '.remove-field-btn', function(e) {
                e.preventDefault();
                
                $(this).closest('.dynamic-field').remove();
            });
        },

        /**
         * Initialize Select2 for brand dropdowns
         */
        initBrandSelect: function() {
            if ($.fn.selectWoo) {
                $('.brand-select').selectWoo();
            } else if ($.fn.select2) {
                $('.brand-select').select2();
            }
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            $('.tooltip-trigger').hover(
                function() {
                    const tooltip = $(this).data('tooltip');
                    
                    if (tooltip) {
                        $('<div class="inventory-tooltip"></div>')
                            .text(tooltip)
                            .appendTo('body')
                            .css({
                                top: $(this).offset().top + $(this).outerHeight(),
                                left: $(this).offset().left
                            })
                            .fadeIn('fast');
                    }
                },
                function() {
                    $('.inventory-tooltip').remove();
                }
            );
        },

        /**
         * Initialize notification system
         */
        initNotifications: function() {
            // Clear existing notification container
            if ($('#inventory-notification').length) {
                $('#inventory-notification').remove();
            }
            
            // Create notification container
            $('body').append('<div id="inventory-notification"></div>');
        },

        /**
         * Initialize archive badge settings
         */
        initArchiveBadgeSettings: function() {
            // Toggle badge type settings visibility
            $(document).on('change', 'input[name="inventory_manager_archive_badge[type]"]', function() {
                const badgeType = $(this).val();
                
                if (badgeType === 'text') {
                    $('.badge-text-settings').show();
                    $('.badge-image-settings').hide();
                } else if (badgeType === 'image') {
                    $('.badge-text-settings').hide();
                    $('.badge-image-settings').show();
                }
            });

            // Initialize on page load
            const selectedType = $('input[name="inventory_manager_archive_badge[type]"]:checked').val();
            if (selectedType === 'text') {
                $('.badge-text-settings').show();
                $('.badge-image-settings').hide();
            } else if (selectedType === 'image') {
                $('.badge-text-settings').hide();
                $('.badge-image-settings').show();
            }

            // Image upload functionality
            $(document).on('click', '.upload-image-btn', function(e) {
                e.preventDefault();
                
                const button = $(this);
                const targetField = button.data('target');
                const targetInput = $('input[name="' + targetField + '"]');
                
                // WordPress media uploader
                if (typeof wp !== 'undefined' && wp.media) {
                    const mediaUploader = wp.media({
                        title: 'Select Badge Image',
                        button: {
                            text: 'Use This Image'
                        },
                        multiple: false,
                        library: {
                            type: 'image'
                        }
                    });
                    
                    mediaUploader.on('select', function() {
                        const attachment = mediaUploader.state().get('selection').first().toJSON();
                        targetInput.val(attachment.url);
                    });
                    
                    mediaUploader.open();
                } else {
                    // Fallback to prompt if media uploader not available
                    const imageUrl = prompt('Enter image URL:');
                    if (imageUrl) {
                        targetInput.val(imageUrl);
                    }
                }
            });
        },

        /**
         * Show notification
         */
        showNotification: function(message, type = 'success', duration = 3000) {
            const notification = $('#inventory-notification');
            
            // Clear any existing notifications
            notification.empty().removeClass('success error warning info');
            
            // Add message and type
            notification.text(message).addClass(type).fadeIn();
            
            // Auto-hide after duration
            setTimeout(function() {
                notification.fadeOut();
            }, duration);
        },

        /**
         * Force hex format for all color pickers on the page
         */
        forceHexFormatOnAllColorPickers: function() {
            // More aggressive approach - monitor for any color picker opening
            $(document).on('click', '.wp-color-result', function() {
                const $this = $(this);
                setTimeout(function() {
                    // Find the currently visible picker
                    const $activePicker = $('.iris-picker:visible');
                    if ($activePicker.length) {
                        // Force to hex format
                        inventoryManager.switchToHexFormat($activePicker);
                    }
                }, 50);
            });
            
            // Also try when iris opens
            $(document).on('irisopen', function(event, ui) {
                setTimeout(function() {
                    const $activePicker = $('.iris-picker:visible');
                    if ($activePicker.length) {
                        inventoryManager.switchToHexFormat($activePicker);
                    }
                }, 50);
            });
        },

        /**
         * Switch color picker to hex format
         */
        switchToHexFormat: function($picker) {
            // Try multiple selectors to find the format toggle
            let $formatToggle = $picker.find('.iris-square-value');
            
            // If that doesn't work, try other possible selectors
            if (!$formatToggle.length) {
                $formatToggle = $picker.find('[data-current]');
            }
            
            // Try clicking any element that might switch format
            if (!$formatToggle.length) {
                $picker.find('*').each(function() {
                    const $el = $(this);
                    const text = $el.text().toLowerCase();
                    if (text.includes('rgb') || text.includes('HSL')) {
                        $formatToggle = $el;
                        return false;
                    }
                });
            }
            
            // Click to switch format if found
            if ($formatToggle.length) {
                const currentText = $formatToggle.text().toLowerCase();
                const currentAttr = $formatToggle.attr('data-current');
                
                // Only click if currently showing RGB or HSL
                if (currentText.includes('rgb') || currentText.includes('HSL') || 
                    currentAttr === 'rgb' || currentAttr === 'HSL') {
                    $formatToggle.click();
                }
            }
        },

        /**
         * Aggressively remove non-hex format elements
         */
        removeNonHexElements: function($picker) {
            // Remove elements containing RGB or HSL text
            $picker.find('*').each(function() {
                const $el = $(this);
                const text = $el.text().toLowerCase();
                
                if (text.includes('rgb') || text.includes('hsl') || 
                    text === 'rgb' || text === 'hsl' ||
                    $el.attr('data-current') === 'rgb' || 
                    $el.attr('data-current') === 'hsl') {
                    
                    // Don't remove if it contains hex value or is the hex input
                    if (!text.includes('#') && !$el.hasClass('iris-square-value')) {
                        $el.hide().css({
                            'display': 'none',
                            'visibility': 'hidden',
                            'opacity': '0',
                            'pointer-events': 'none'
                        });
                    }
                }
            });
            
            // Specifically target known RGB/HSL format elements
            $picker.find('[data-current="rgb"], [data-current="hsl"], .iris-square:not(.iris-square-value)').hide();
            
            // Force show hex elements
            $picker.find('.iris-square-value, [data-current="hex"]').show().css({
                'display': 'block',
                'visibility': 'visible',
                'opacity': '1'
            });
        },

        /**
         * Debug color picker structure
         */
        debugColorPicker: function() {
            $(document).on('click', '.wp-color-result', function() {
                setTimeout(function() {
                    const $picker = $('.iris-picker:visible');
                    if ($picker.length) {
                        console.log('Color Picker Structure:', $picker[0]);
                        console.log('All elements in picker:', $picker.find('*'));
                        $picker.find('*').each(function() {
                            const $el = $(this);
                            const text = $el.text();
                            const classes = $el.attr('class');
                            const dataAttrs = $el.data();
                            if (text.toLowerCase().includes('rgb') || text.toLowerCase().includes('hsl')) {
                                console.log('Found RGB/HSL element:', {
                                    element: this,
                                    text: text,
                                    classes: classes,
                                    data: dataAttrs
                                });
                            }
                        });
                    }
                }, 100);
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        inventoryManager.init();
    });

})(jQuery);