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
        <h2><?php _e( 'Import Products', 'inventory-manager-pro' ); ?></h2>
        <p><?php _e( 'Import products from a CSV or Excel file', 'inventory-manager-pro' ); ?></p>
    </div>

    <div class="inventory-form">
        <form id="product-import-form" method="post" enctype="multipart/form-data">
            <div class="form-section">
                <h3><?php _e( 'File Selection', 'inventory-manager-pro' ); ?></h3>
                
                <div class="form-row">
                    <div class="form-field required">
                        <label for="file"><?php _e( 'Select File', 'inventory-manager-pro' ); ?></label>
                        <div class="file-input-container">
                            <input type="file" id="product-file" name="file" accept=".csv,.xls,.xlsx" required>
                            <div class="file-input-button">
                                <button type="button" class="button"><?php _e( 'Choose File', 'inventory-manager-pro' ); ?></button>
                                <span id="product-file-name"><?php _e( 'No file selected', 'inventory-manager-pro' ); ?></span>
                            </div>
                        </div>
                        <p class="description"><?php _e( 'Upload a CSV or Excel file with batch information', 'inventory-manager-pro' ); ?></p>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" id="preview-product-import" class="button button-secondary"><?php _e( 'Preview Import', 'inventory-manager-pro' ); ?></button>
                    <button type="submit" id="product-import-btn" class="button button-primary"><?php _e( 'Import', 'inventory-manager-pro' ); ?></button>
                </div>
            </div>

            <div class="product-import-preview">
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
                                <td><code>SKU</code></td>
                                <td><span class="required">Yes</span></td>
                                <td><?php _e( 'Product SKU', 'inventory-manager-pro' ); ?></td>
                            </tr>
                            <tr>
                                <td><code>Product Name</code></td>
                                <td><span class="required">Yes</span></td>
                                <td><?php _e( 'Name of the product', 'inventory-manager-pro' ); ?></td>
                            </tr>
                            <tr>
                                <td><code>Price</code></td>
                                <td><span class="optional">No</span></td>
                                <td><?php _e( 'Regular price', 'inventory-manager-pro' ); ?></td>
                            </tr>
                            <tr>
                                <td><code>Quantity</code></td>
                                <td><span class="optional">No</span></td>
                                <td><?php _e( 'Initial stock quantity', 'inventory-manager-pro' ); ?></td>
                            </tr>
                            <tr>
                                <td><code>Category</code></td>
                                <td><span class="optional">No</span></td>
                                <td><?php _e( 'Product category name', 'inventory-manager-pro' ); ?></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="sample-download">
                        <p><?php _e( 'Download a sample import file:', 'inventory-manager-pro' ); ?></p>
                        <button type="button" id="download-product-sample" class="button"><?php _e( 'Download Sample CSV', 'inventory-manager-pro' ); ?></button>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3><?php _e( 'Import Notes', 'inventory-manager-pro' ); ?></h3>
                
                <ul class="import-notes">
                    <li><?php _e( 'SKU and Product Name columns are mandatory.', 'inventory-manager-pro' ); ?></li>
                    <li><?php _e( 'SKUs must be unique and not already assigned to another product.', 'inventory-manager-pro' ); ?></li>
                    <li><?php _e( 'Price and Quantity should contain numeric values.', 'inventory-manager-pro' ); ?></li>
                    <li><?php _e( 'Duplicate rows will be ignored during import.', 'inventory-manager-pro' ); ?></li>
                    <li><?php _e( 'The maximum file size for import is 10MB.', 'inventory-manager-pro' ); ?></li>
                </ul>
            </div>
        </form>
    </div>
</div>
