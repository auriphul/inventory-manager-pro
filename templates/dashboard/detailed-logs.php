<?php
/**
 * Detailed Logs template
 *
 * @package Inventory_Manager_Pro
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="inventory-manager-logs">
    <div class="section-header">
        <h2><?php _e( 'Detailed Inventory Logs', 'inventory-manager-pro' ); ?></h2>
        <p><?php _e( 'View detailed movement history for your inventory', 'inventory-manager-pro' ); ?></p>
    </div>

    <div class="filters-row">
        <div class="period-filter-container">
            <label for="period-filter"><?php _e( 'Time Period:', 'inventory-manager-pro' ); ?></label>
            <select id="period-filter" class="period-filter">
                <option value="all"><?php _e( 'All Time', 'inventory-manager-pro' ); ?></option>
                <option value="today"><?php _e( 'Today', 'inventory-manager-pro' ); ?></option>
                <option value="yesterday"><?php _e( 'Yesterday', 'inventory-manager-pro' ); ?></option>
                <option value="this_week"><?php _e( 'This Week', 'inventory-manager-pro' ); ?></option>
                <option value="last_week"><?php _e( 'Last Week', 'inventory-manager-pro' ); ?></option>
                <option value="this_month" selected><?php _e( 'This Month', 'inventory-manager-pro' ); ?></option>
                <option value="last_month"><?php _e( 'Last Month', 'inventory-manager-pro' ); ?></option>
                <option value="last_3_months"><?php _e( 'Last 3 Months', 'inventory-manager-pro' ); ?></option>
                <option value="last_6_months"><?php _e( 'Last 6 Months', 'inventory-manager-pro' ); ?></option>
                <option value="this_year"><?php _e( 'This Year', 'inventory-manager-pro' ); ?></option>
                <option value="custom"><?php _e( 'Custom Range', 'inventory-manager-pro' ); ?></option>
            </select>
            
            <div class="custom-date-range" style="display:none;">
                <input type="text" class="logs-date-range" placeholder="<?php _e( 'Select date range', 'inventory-manager-pro' ); ?>">
            </div>
        </div>
        
        <div class="logs-search-box">
            <input type="text" placeholder="<?php _e( 'Search products...', 'inventory-manager-pro' ); ?>">
            <button class="button logs-search-btn"><?php _e( 'Search', 'inventory-manager-pro' ); ?></button>
            <button class="button show-all-batches-btn"><?php _e( 'Show All Batches', 'inventory-manager-pro' ); ?></button>
        </div>
        
        <div class="logs-export">
            <select class="logs-export-format">
                <option value="csv">CSV</option>
                <option value="xls">XLS</option>
            </select>
            <button class="button logs-export-btn"><?php _e( 'Export Logs', 'inventory-manager-pro' ); ?></button>
        </div>
    </div>
    
    <div class="products-container">
        <div class="loading"><?php _e( 'Loading...', 'inventory-manager-pro' ); ?></div>
        
        <!-- Products will be loaded here via JavaScript -->
    </div>
</div>

<!-- Templates for JavaScript rendering -->
<script type="text/template" id="product-template">
    <div class="product-section" data-product-id="{{product_id}}">
        <div class="product-header">
            <div class="product-info">
                <strong>{{product_name}}</strong><br>
                <span class="sku">SKU: {{sku}}</span>
            </div>
            
            <div class="product-summary">
                <div class="batch-count">
                    <span class="label"><?php _e( 'Batches', 'inventory-manager-pro' ); ?></span>
                    <span class="value">{{batch_count}}</span>
                </div>
                
                <div class="total-stock">
                    <span class="label"><?php _e( 'Total Stock', 'inventory-manager-pro' ); ?></span>
                    <span class="value">{{total_stock}}</span>
                </div>
            </div>
        </div>
        
        <div class="batches-container">
            <!-- Batches will be inserted here -->
        </div>
    </div>
</script>

<script type="text/template" id="batch-template">
    <div class="batch-section" data-batch-id="{{id}}">
        <div class="batch-header">
            <div class="batch-number">
                <span class="label"><?php _e( 'Batch', 'inventory-manager-pro' ); ?></span>
                <span class="value">{{batch_number}}</span>
            </div>
            
            <div class="batch-stock">
                <span class="label"><?php _e( 'Stock Qty', 'inventory-manager-pro' ); ?></span>
                <span class="value">{{stock_qty}}</span>
            </div>
            
            <div class="batch-expiry">
                <span class="label"><?php _e( 'Expiry', 'inventory-manager-pro' ); ?></span>
                <span class="value">{{expiry_formatted}}</span>
            </div>

            <div class="batch-unit-cost">
                <span class="label"><?php _e( 'Unit Cost', 'inventory-manager-pro' ); ?></span>
                <span class="value">{{unit_cost}}</span>
            </div>

            <div class="batch-stock-cost">
                <span class="label"><?php _e( 'Stock Cost', 'inventory-manager-pro' ); ?></span>
                <span class="value">{{stock_cost_formatted}}</span>
            </div>

            <div class="batch-freight">
                <span class="label"><?php _e( 'Freight Markup', 'inventory-manager-pro' ); ?></span>
                <span class="value">{{freight_markup}}</span>
            </div>

            <div class="batch-landed-cost">
                <span class="label"><?php _e( 'Landed Cost', 'inventory-manager-pro' ); ?></span>
                <span class="value">{{landed_cost_formatted}}</span>
            </div>
        </div>
        
        <div class="batch-details" style="display: none;">
            <div class="batch-supplier">
                <span class="label"><?php _e( 'Supplier', 'inventory-manager-pro' ); ?></span>
                <span class="value">{{supplier_name}}</span>
            </div>
            
            <div class="batch-origin">
                <span class="label"><?php _e( 'Origin', 'inventory-manager-pro' ); ?></span>
                <span class="value">{{origin}}</span>
            </div>
            
            <div class="batch-location">
                <span class="label"><?php _e( 'Location', 'inventory-manager-pro' ); ?></span>
                <span class="value">{{location}}</span>
            </div>
            
            <div class="batch-actions">
                <button class="button add-adjustment-btn" data-batch-id="{{id}}"><?php _e( 'Add Adjustment', 'inventory-manager-pro' ); ?></button>
            </div>
        </div>
        
        <div class="movement-log" style="display: none;">
            <div class="log-header">
                <div class="log-date"><?php _e( 'Date & Time', 'inventory-manager-pro' ); ?></div>
                <div class="log-type"><?php _e( 'Type', 'inventory-manager-pro' ); ?></div>
                <div class="log-reference"><?php _e( 'Reference', 'inventory-manager-pro' ); ?></div>
                <div class="log-in"><?php _e( 'Stock In', 'inventory-manager-pro' ); ?></div>
                <div class="log-out"><?php _e( 'Stock Out', 'inventory-manager-pro' ); ?></div>
            </div>
            
            <div class="log-entries">
                <!-- Movement entries will be inserted here -->
            </div>
        </div>
    </div>
</script>

<script type="text/template" id="movement-template">
    <div class="log-entry">
        <div class="log-date">{{date_time}}</div>
        <div class="log-type">{{movement_type}}</div>
        <div class="log-reference">{{reference}}</div>
        <div class="log-in">{{stock_in}}</div>
        <div class="log-out">{{stock_out}}</div>
    </div>
</script>

<script type="text/template" id="adjustment-modal-template">
    <div class="inventory-modal adjustment-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><?php _e( 'Add Adjustment for Batch', 'inventory-manager-pro' ); ?> {{batch_number}}</h3>
                <button class="close-modal">&times;</button>
            </div>
            
            <div class="modal-body">
                <div class="batch-info">
                    <p><strong><?php _e( 'SKU:', 'inventory-manager-pro' ); ?></strong> {{sku}}</p>
                    <p><strong><?php _e( 'Current Stock:', 'inventory-manager-pro' ); ?></strong> {{stock_qty}}</p>
                </div>
                
                <form id="adjustment-form">
                    <input type="hidden" name="batch_id" value="{{id}}">
                    
                    <div class="form-field required">
                        <label for="adjustment_type"><?php _e( 'Adjustment Type', 'inventory-manager-pro' ); ?></label>
                        <select name="adjustment_type" id="adjustment_type" required>
                            <option value=""><?php _e( 'Select adjustment type', 'inventory-manager-pro' ); ?></option>
                            <!-- Adjustment types will be populated via JavaScript -->
                        </select>
                    </div>
                    
                    <div class="form-field required">
                        <label for="adjustment_qty"><?php _e( 'Quantity', 'inventory-manager-pro' ); ?></label>
                        <input type="number" name="adjustment_qty" id="adjustment_qty" step="0.01" min="0.01" required>
                        <p class="description"><?php _e( 'The system will automatically add or deduct based on the adjustment type.', 'inventory-manager-pro' ); ?></p>
                    </div>
                    
                    <div class="form-field required">
                        <label for="adjustment_reference"><?php _e( 'Reference', 'inventory-manager-pro' ); ?></label>
                        <input type="text" name="adjustment_reference" id="adjustment_reference" required>
                        <p class="description"><?php _e( 'Add a reference for this adjustment (e.g., invoice number).', 'inventory-manager-pro' ); ?></p>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="button submit-adjustment"><?php _e( 'Save Adjustment', 'inventory-manager-pro' ); ?></button>
                        <button type="button" class="button secondary cancel-adjustment"><?php _e( 'Cancel', 'inventory-manager-pro' ); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</script>