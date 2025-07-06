<?php
/**
 * Import template
 *
 * @package Inventory_Manager_Pro
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="inventory-manager-import">
    <div class="section-header">
        <h2><?php _e( 'Import Inventory', 'inventory-manager-pro' ); ?></h2>
        <p><?php _e( 'Import batches from a CSV or Excel file', 'inventory-manager-pro' ); ?></p>
    </div>

    <div class="inventory-form" style="max-width:100%;">
        <form id="import-form" method="post" enctype="multipart/form-data">
            <div class="form-section">
                <h3><?php _e( 'File Selection', 'inventory-manager-pro' ); ?></h3>
                
                <div class="form-row">
                    <div class="form-field required">
                        <label for="file"><?php _e( 'Select File', 'inventory-manager-pro' ); ?></label>
                        <div class="file-input-container">
                            <input type="file" id="file" name="file" accept=".csv,.xls,.xlsx" required>
                            <!-- <div class="file-input-button">
                                <button type="button" class="button"><?php _e( 'Choose File', 'inventory-manager-pro' ); ?></button>
                                <span id="file-name"><?php _e( 'No file selected', 'inventory-manager-pro' ); ?></span>
                            </div> -->
                        </div>
                        <p class="description"><?php _e( 'Upload a CSV or Excel file with batch information', 'inventory-manager-pro' ); ?></p>
                    </div>
                </div>
                
                <div class="form-actions">
                    <!-- <button type="button" id="preview-import" class="button button-secondary"><?php _e( 'Preview Import', 'inventory-manager-pro' ); ?></button> -->
                    <button type="submit" id="import-btn" class="button button-primary"><?php _e( 'Import', 'inventory-manager-pro' ); ?></button>
                </div>
            </div>
            
            <div class="import-preview">
                <!-- Preview content will be loaded here via JavaScript -->
            </div>
            
            <div class="form-section">
                <h3><?php _e( 'File Format', 'inventory-manager-pro' ); ?></h3>
                
                <div class="format-info">
                    <p><?php _e( 'Your import file should contain the following columns:', 'inventory-manager-pro' ); ?></p>
                    
                    <table class="format-table">
                        <thead>
                            <tr>
                                <th><?php _e( 'Column Name', 'inventory-manager-pro' ); ?></th>
                                <th><?php _e( 'Required', 'inventory-manager-pro' ); ?></th>
                                <th><?php _e( 'Description', 'inventory-manager-pro' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>inventory_sku</code></td>
                                <td><span class="required">Yes</span></td>
                                <td><?php _e( 'Product SKU', 'inventory-manager-pro' ); ?></td>
                            </tr>
                            <tr>
                                <td><code>inventory_batch</code></td>
                                <td><span class="required">Yes</span></td>
                                <td><?php _e( 'Batch number', 'inventory-manager-pro' ); ?></td>
                            </tr>
                            <tr>
                                <td><code>inventory_stock_qty</code></td>
                                <td><span class="required">Yes</span></td>
                                <td><?php _e( 'Stock quantity', 'inventory-manager-pro' ); ?></td>
                            </tr>
                            <tr>
                                <td><code>inventory_reference</code></td>
                                <td><span class="required">Yes</span></td>
                                <td><?php _e( 'Reference number (PO, invoice, etc.)', 'inventory-manager-pro' ); ?></td>
                            </tr>
                            <tr>
                                <td><code>inventory_supplier_id</code></td>
                                <td><span class="required">Yes</span></td>
                                <td><?php _e( 'Supplier ID (Make sure that all suppliers are entered onto Suppliers & Transit Time tab before importing. Any products with a supplier not pre-entered, will be ignored.)', 'inventory-manager-pro' ); ?></td>
                            </tr>
                            <tr>
                                <td><code>inventory_expiry</code></td>
                                <td><span class="optional">No</span></td>
                                <td><?php _e( 'Expiry date (DD/MM/YYYY)', 'inventory-manager-pro' ); ?></td>
                            </tr>
                            <tr>
                                <td><code>inventory_origin</code></td>
                                <td><span class="optional">No</span></td>
                                <td><?php _e( 'Country or region of origin', 'inventory-manager-pro' ); ?></td>
                            </tr>
                            <tr>
                                <td><code>inventory_location</code></td>
                                <td><span class="optional">No</span></td>
                                <td><?php _e( 'Storage location', 'inventory-manager-pro' ); ?></td>
                            </tr>
                            <tr>
                                <td><code>inventory_unit_cost</code></td>
                                <td><span class="optional">No</span></td>
                                <td><?php _e( 'Unit cost', 'inventory-manager-pro' ); ?></td>
                            </tr>
                            <tr>
                                <td><code>inventory_freight_markup</code></td>
                                <td><span class="optional">No</span></td>
                                <td><?php _e( 'Freight markup per unit', 'inventory-manager-pro' ); ?></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="sample-download">
                        <p><?php _e( 'Download a sample import file:', 'inventory-manager-pro' ); ?></p>
                        <button type="button" id="download-sample" class="button"><?php _e( 'Download Sample CSV', 'inventory-manager-pro' ); ?></button>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3><?php _e( 'Import Notes', 'inventory-manager-pro' ); ?></h3>
                
                <ul class="import-notes">
                    <li><?php _e( 'SKUs must exist in WooCommerce before importing.', 'inventory-manager-pro' ); ?></li>
                    <li><?php _e( 'Batch numbers must be unique for each product.', 'inventory-manager-pro' ); ?></li>
                    <li><?php _e( 'If a supplier does not exist, it will not be created automatically.', 'inventory-manager-pro' ); ?></li>
                    <li><?php _e( 'Dates should be in DD/MM/YYYY format.', 'inventory-manager-pro' ); ?></li>
                    <li><?php _e( 'Use a decimal point (.) for decimal numbers, not a comma.', 'inventory-manager-pro' ); ?></li>
                    <li><?php _e( 'The maximum file size for import is 10MB.', 'inventory-manager-pro' ); ?></li>
                </ul>
            </div>
        </form>
    </div>
</div>