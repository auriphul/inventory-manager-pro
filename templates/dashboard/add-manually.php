<?php
/**
 * Add Manually template
 *
 * @package Inventory_Manager_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

// Fetch transit times from the options table
$inventory_db   = new Inventory_Database();
$transit_times  = $inventory_db->get_transit_times();
?>

<div class="inventory-manager-add-manually">
	<div class="section-header">
		<h2><?php _e( 'Add Batch Manually', 'inventory-manager-pro' ); ?></h2>
		<p><?php _e( 'Add a new batch to your inventory', 'inventory-manager-pro' ); ?></p>
	</div>

	<div class="inventory-form">
		<form id="add-batch-form" method="post">
			<div class="form-section">
				<h3><?php _e( 'Product Information', 'inventory-manager-pro' ); ?></h3>
				
				<div class="form-row">
					<div class="form-field required">
						<label for="sku"><?php _e( 'SKU', 'inventory-manager-pro' ); ?></label>
						<input type="text" id="sku" name="sku" required>
						<p class="description"><?php _e( 'Enter the product SKU to add a batch', 'inventory-manager-pro' ); ?></p>
					</div>
					
					<div class="form-field">
						<label for="product_name"><?php _e( 'Product Name', 'inventory-manager-pro' ); ?></label>
						<input type="text" id="product_name" name="product_name">
					</div>
				</div>
				
				<div class="product-info-container">
					<!-- Product info and existing batches will be displayed here -->
				</div>
			</div>
			
			<div class="form-section">
				<h3><?php _e( 'Batch Information', 'inventory-manager-pro' ); ?></h3>
				<div class="form-row">
					<div class="form-field required">
						<label for="batch_number"><?php _e( 'Batch Number', 'inventory-manager-pro' ); ?></label>
						<input type="text" id="batch_number" name="batch_number" required>
					</div>
					
					<div class="form-field required">
						<label for="stock_qty"><?php _e( 'Stock Quantity', 'inventory-manager-pro' ); ?></label>
						<input type="number" id="stock_qty" name="stock_qty" min="0.01" step="0.01" required>
					</div>
					<div class="form-field">
						<label for="expiry_date"><?php _e( 'Expiry Date', 'inventory-manager-pro' ); ?></label>
						<input type="date" id="expiry_date" name="expiry_date" class="date-picker">
					</div>
				</div>
				
				<div class="form-row">
					
					<div class="form-field required">
						<label for="reference"><?php _e( 'Reference', 'inventory-manager-pro' ); ?></label>
						<input type="text" id="reference" name="reference" required>
						<p class="description"><?php _e( 'PO number, invoice, or other reference', 'inventory-manager-pro' ); ?></p>
					</div>
					<div class="form-field">
						<label for="origin"><?php _e( 'Origin', 'inventory-manager-pro' ); ?></label>
						<input type="text" id="origin" name="origin">
						<p class="description"><?php _e( 'Country or region of origin', 'inventory-manager-pro' ); ?></p>
					</div>
					
					<div class="form-field">
						<label for="location"><?php _e( 'Location', 'inventory-manager-pro' ); ?></label>
						<input type="text" id="location" name="location">
						<p class="description"><?php _e( 'Storage location', 'inventory-manager-pro' ); ?></p>
					</div>
				</div>
			</div>
			
                        <div class="form-section">
                                <h3><?php _e( 'Brand Information', 'inventory-manager-pro' ); ?></h3>

                                <div class="form-row">
                                        <div class="form-field required">
                                                <label for="brand_id"><?php _e( 'Brand', 'inventory-manager-pro' ); ?></label>
                                                <select id="brand_id" name="brand_id">
                                                        <option value=""><?php _e( 'Select brand', 'inventory-manager-pro' ); ?></option>
                                                        <!-- Brands will be populated via JavaScript -->
                                                </select>
                                        </div>
                                </div>
                        </div>
			
			<div class="form-section">
				<h3><?php _e( 'Additional Information', 'inventory-manager-pro' ); ?></h3>
				
				<div class="form-row">
				</div>
				
				<div class="form-row">
					<div class="form-field">
						<label for="unit_cost"><?php _e( 'Unit Cost', 'inventory-manager-pro' ); ?></label>
						<input type="number" id="unit_cost" name="unit_cost" min="0" step="0.01">
					</div>
					
					<div class="form-field">
						<label for="freight_markup"><?php _e( 'Freight Markup (if 25% enter 1.25)', 'inventory-manager-pro' ); ?></label>
						<input type="number" id="freight_markup" name="freight_markup" min="0" step="0.01">
					</div>
					<div class="form-field">
						<label for="add-batch-btn"><?php _e( '', 'inventory-manager-pro' ); ?></label>
						<button type="submit" id="add-batch-btn" class="button button-primary"><?php _e( 'Add Batch', 'inventory-manager-pro' ); ?></button>
					</div>
				</div>
				<div class="form-row pb-5 mb-5">
				</div>
			</div>
			
		</form>
	</div>
</div>