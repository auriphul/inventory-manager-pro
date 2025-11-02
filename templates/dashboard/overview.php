<?php
/**
 * Dashboard overview template
 *
 * @package Inventory_Manager_Pro
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get expiry ranges
$expiry_ranges = get_option( 'inventory_manager_expiry_ranges', array() );
?>
<style>
	.expiry-expired{
		background-color:<?php echo $expiry_ranges['expired']['color'];?> !important;
		color:<?php echo $expiry_ranges['expired']['text_color'];?> !important;
	}
	.expiry-less1{
		background-color:<?php echo $expiry_ranges['less_1']['color'];?> !important;
		color:<?php echo $expiry_ranges['less_1']['text_color'];?> !important;
	}
	.expiry-1-3{
		background-color:<?php echo $expiry_ranges['1_3']['color'];?> !important;
		color:<?php echo $expiry_ranges['1_3']['text_color'];?> !important;
	}
	.expiry-3-6{
		background-color:<?php echo $expiry_ranges['3_6']['color'];?> !important;
		color:<?php echo $expiry_ranges['3_6']['text_color'];?> !important;
	}
	.expiry-6plus{
		background-color:<?php echo $expiry_ranges['6_plus']['color'];?> !important;
		color:<?php echo $expiry_ranges['6_plus']['text_color'];?> !important;
	}
	.expiry-no_expiry{
		background-color:<?php echo isset($expiry_ranges['no_expiry']['color']) ? $expiry_ranges['no_expiry']['color'] : '#f0f0f0';?> !important;
		color:<?php echo isset($expiry_ranges['no_expiry']['text_color']) ? $expiry_ranges['no_expiry']['text_color'] : '#666666';?> !important;
	}
</style>

<div class="inventory-manager-overview">
	<div class="filters-row">
		<div class="expiry-filters">
                        <label>
                                <input type="checkbox" class="filter-expiry" data-range="6+">
                                <span class="expiry-6plus"><?php echo isset( $expiry_ranges['6_plus']['label'] ) ? esc_html( $expiry_ranges['6_plus']['label'] ) : __( '6+ months', 'inventory-manager-pro' ); ?></span>
                        </label>
                        <label>
                                <input type="checkbox" class="filter-expiry" data-range="3-6" >
                                <span class="expiry-3-6"><?php echo isset( $expiry_ranges['3_6']['label'] ) ? esc_html( $expiry_ranges['3_6']['label'] ) : __( '3-6 months', 'inventory-manager-pro' ); ?></span>
                        </label>
                        <label>
                                <input type="checkbox" class="filter-expiry" data-range="1-3" >
                                <span class="expiry-1-3"><?php echo isset( $expiry_ranges['1_3']['label'] ) ? esc_html( $expiry_ranges['1_3']['label'] ) : __( '1-3 months', 'inventory-manager-pro' ); ?></span>
                        </label>
                        <label>
                                <input type="checkbox" class="filter-expiry" data-range="<1" >
                                <span class="expiry-less1"><?php echo isset( $expiry_ranges['less_1']['label'] ) ? esc_html( $expiry_ranges['less_1']['label'] ) : __( '< 1 month', 'inventory-manager-pro' ); ?></span>
                        </label>
                        <label>
                                <input type="checkbox" class="filter-expiry" data-range="expired" >
                                <span class="expiry-expired"><?php echo isset( $expiry_ranges['expired']['label'] ) ? esc_html( $expiry_ranges['expired']['label'] ) : __( 'expired', 'inventory-manager-pro' ); ?></span>
                        </label>
                        <label>
                                <input type="checkbox" class="filter-expiry" data-range="no_expiry" >
                                <span class="expiry-no_expiry"><?php echo isset( $expiry_ranges['no_expiry']['label'] ) ? esc_html( $expiry_ranges['no_expiry']['label'] ) : __( 'no expiry date', 'inventory-manager-pro' ); ?></span>
                        </label>
						<span>click/unclick here to filter by expiry ranges</span>
                </div>
		
		<div class="column-filters">
			<label><input type="checkbox" class="toggle-column" data-column="sku" checked disabled> <?php _e( 'SKU', 'inventory-manager-pro' ); ?></label>
			<label><input type="checkbox" class="toggle-column" data-column="product_name" checked disabled> <?php _e( 'PRODUCT NAME', 'inventory-manager-pro' ); ?></label>
			<label><input type="checkbox" class="toggle-column" data-column="batch" checked disabled> <?php _e( 'BATCH', 'inventory-manager-pro' ); ?></label>
			<label><input type="checkbox" class="toggle-column" data-column="stock_qty" checked disabled> <?php _e( 'STOCK QTY', 'inventory-manager-pro' ); ?></label>
                        <label><input type="checkbox" class="toggle-column" data-column="supplier" checked> <?php _e( 'BRAND', 'inventory-manager-pro' ); ?></label>
			<label><input type="checkbox" class="toggle-column" data-column="expiry" checked> <?php _e( 'EXPIRY', 'inventory-manager-pro' ); ?></label>
			<label><input type="checkbox" class="toggle-column" data-column="origin" checked> <?php _e( 'ORIGIN', 'inventory-manager-pro' ); ?></label>
			<label><input type="checkbox" class="toggle-column" data-column="location" checked> <?php _e( 'LOCATION', 'inventory-manager-pro' ); ?></label>
			<label><input type="checkbox" class="toggle-column" data-column="stock_cost" checked> <?php _e( 'STOCK COST', 'inventory-manager-pro' ); ?></label>
			<label><input type="checkbox" class="toggle-column" data-column="landed_cost" checked> <?php _e( 'LANDED COST', 'inventory-manager-pro' ); ?></label>
		</div>
	</div>
	<div class="filters-row">
		<div class="period-filter-container">
			<select class="export-format">
					<option value="csv">CSV</option>
					<option value="xls">XLS</option>
			</select>
			<button class="button export-btn"><?php _e( 'EXPORT', 'inventory-manager-pro' ); ?></button>
		</div>
		<div class="search-box">
			<input type="text" placeholder="<?php _e( 'Search...', 'inventory-manager-pro' ); ?>">
			<button class="button search-btn"><?php _e( 'Search', 'inventory-manager-pro' ); ?></button>
			<button class="button show-all-btn"><?php _e( 'Show All Batches', 'inventory-manager-pro' ); ?></button>
		</div>
	</div>
	
	<table class="inventory-table">
		<thead>
			<tr>
				<th data-sort="sku"><?php _e( 'SKU', 'inventory-manager-pro' ); ?> <span class="sort-icon"></span></th>
				<th data-sort="product_name"><?php _e( 'PRODUCT NAME', 'inventory-manager-pro' ); ?> <span class="sort-icon"></span></th>
				<th data-sort="batch"><?php _e( 'BATCH', 'inventory-manager-pro' ); ?> <span class="sort-icon"></span></th>
				<th data-sort="stock_qty"><?php _e( 'STOCK QTY', 'inventory-manager-pro' ); ?> <span class="sort-icon"></span></th>
                                <th data-sort="supplier" class="column-supplier"><?php _e( 'BRAND', 'inventory-manager-pro' ); ?> <span class="sort-icon"></span></th>
				<th data-sort="expiry" class="column-expiry"><?php _e( 'EXPIRY', 'inventory-manager-pro' ); ?> <span class="sort-icon"></span></th>
				<th data-sort="origin" class="column-origin"><?php _e( 'ORIGIN', 'inventory-manager-pro' ); ?> <span class="sort-icon"></span></th>
				<th data-sort="location" class="column-location"><?php _e( 'LOCATION', 'inventory-manager-pro' ); ?> <span class="sort-icon"></span></th>
				<th data-sort="stock_cost" class="column-stock_cost"><?php _e( 'STOCK COST', 'inventory-manager-pro' ); ?> <span class="sort-icon"></span></th>
                                <th data-sort="landed_cost" class="column-landed_cost"><?php _e( 'LANDED COST', 'inventory-manager-pro' ); ?> <span class="sort-icon"></span></th>
                                <th class="column-actions"><?php _e( 'ACTIONS', 'inventory-manager-pro' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
                                <td colspan="12" class="loading"><?php _e( 'Loading...', 'inventory-manager-pro' ); ?></td>
			</tr>
		</tbody>
	</table>

	<div class="pagination-controls">
		<div class="per-page-selector">
			<label for="per-page-select"><?php _e( 'Show:', 'inventory-manager-pro' ); ?></label>
			<select id="per-page-select" class="per-page-select">
				<option value="10">10</option>
				<option value="20" selected>20</option>
				<option value="50">50</option>
				<option value="100">100</option>
				<option value="all"><?php _e( 'All', 'inventory-manager-pro' ); ?></option>
			</select>
			<span><?php _e( 'per page', 'inventory-manager-pro' ); ?></span>
		</div>
		
		<div class="pagination">
			<!-- Pagination will be added via JavaScript -->
		</div>
	</div>

	<!-- Edit Batch Modal -->
	<div id="edit-batch-modal" class="inventory-modal" style="display: none;">
		<div class="modal-overlay"></div>
		<div class="modal-content">
			<div class="modal-header">
				<h3><?php _e( 'Edit Batch', 'inventory-manager-pro' ); ?></h3>
				<button class="modal-close">&times;</button>
			</div>
			<div class="modal-body">
				<form id="edit-batch-form">
					<input type="hidden" id="edit-batch-id" name="batch_id" value="">
					
					<div class="form-row">
						<div class="form-group">
							<label for="edit-sku"><?php _e( 'SKU', 'inventory-manager-pro' ); ?></label>
							<input type="text" id="edit-sku" name="sku" readonly class="readonly-field">
						</div>
						<div class="form-group">
							<label for="edit-product-name"><?php _e( 'Product', 'inventory-manager-pro' ); ?></label>
							<input type="text" id="edit-product-name" name="product_name" readonly class="readonly-field">
						</div>
					</div>

					<div class="form-row">
						<div class="form-group">
							<label for="edit-batch-number"><?php _e( 'Batch Number', 'inventory-manager-pro' ); ?> *</label>
							<input type="text" id="edit-batch-number" name="batch_number" required>
						</div>
						<div class="form-group">
							<label for="edit-stock-qty"><?php _e( 'Stock Quantity', 'inventory-manager-pro' ); ?> *</label>
							<input type="number" id="edit-stock-qty" name="stock_qty" step="0.01" min="0" required>
						</div>
					</div>

					<div class="form-row">
						<div class="form-group">
							<label for="edit-unit-cost"><?php _e( 'Unit Cost', 'inventory-manager-pro' ); ?></label>
							<input type="number" id="edit-unit-cost" name="unit_cost" step="0.01" min="0">
						</div>
						<div class="form-group">
							<label for="edit-freight-markup"><?php _e( 'Freight Markup', 'inventory-manager-pro' ); ?></label>
							<input type="number" id="edit-freight-markup" name="freight_markup" step="0.01" min="1" value="1">
						</div>
					</div>

					<div class="form-row">
						<div class="form-group">
							<label for="edit-expiry-date"><?php _e( 'Expiry Date (Optional)', 'inventory-manager-pro' ); ?></label>
							<input type="text" id="edit-expiry-date" name="expiry_date" placeholder="DD/MM/YYYY" pattern="^(0[1-9]|[12][0-9]|3[01])/(0[1-9]|1[0-2])/([0-9]{4})$">
						</div>
						<div class="form-group">
							<label for="edit-brand"><?php _e( 'Brand', 'inventory-manager-pro' ); ?></label>
							<select id="edit-brand" name="brand_id">
								<option value=""><?php _e( 'Select Brand', 'inventory-manager-pro' ); ?></option>
								<!-- Options will be populated via JavaScript -->
							</select>
						</div>
					</div>

					<div class="form-row">
						<div class="form-group">
							<label for="edit-origin"><?php _e( 'Origin', 'inventory-manager-pro' ); ?></label>
							<input type="text" id="edit-origin" name="origin">
						</div>
						<div class="form-group">
							<label for="edit-location"><?php _e( 'Location', 'inventory-manager-pro' ); ?></label>
							<input type="text" id="edit-location" name="location">
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="button button-secondary modal-cancel"><?php _e( 'Cancel', 'inventory-manager-pro' ); ?></button>
				<button type="submit" form="edit-batch-form" class="button button-primary"><?php _e( 'Update Batch', 'inventory-manager-pro' ); ?></button>
			</div>
		</div>
	</div>
</div>

<script id="batch-row-template" type="text/template">
        <tr data-id="{{id}}" class="expiry-{{expiry_range}}">
                <td>{{sku}}</td>
                <td>{{product_name}}</td>
                <td>{{batch_number}}</td>
                <td>{{stock_qty}}</td>
                <td class="column-supplier">{{brand_name}}</td>
                <td class="column-expiry">{{expiry_formatted}}</td>
                <td class="column-origin">{{origin}}</td>
                <td class="column-location">{{location}}</td>
                <td class="column-stock_cost">{{stock_cost_formatted}}</td>
                <td class="column-landed_cost">{{landed_cost_formatted}}</td>
                <td class="column-actions">
                    <button class="button button-small edit-batch" data-id="{{id}}" title="<?php _e( 'Edit Batch', 'inventory-manager-pro' ); ?>">
                        <span class="dashicons dashicons-edit"></span>
                    </button>
                    <button class="button button-small delete-batch" data-id="{{id}}" title="<?php _e( 'Delete Batch', 'inventory-manager-pro' ); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </td>
        </tr>
</script>