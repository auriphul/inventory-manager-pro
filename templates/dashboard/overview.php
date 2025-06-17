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
</style>

<div class="inventory-manager-overview">
	<div class="filters-row">
		<div class="expiry-filters">
			<label>
				<input type="checkbox" class="filter-expiry" data-range="6+" checked>
				<?php echo isset( $expiry_ranges['6_plus']['label'] ) ? esc_html( $expiry_ranges['6_plus']['label'] ) : __( '6+ months', 'inventory-manager-pro' ); ?>
			</label>
			<label>
				<input type="checkbox" class="filter-expiry" data-range="3-6" checked>
				<?php echo isset( $expiry_ranges['3_6']['label'] ) ? esc_html( $expiry_ranges['3_6']['label'] ) : __( '3-6 months', 'inventory-manager-pro' ); ?>
			</label>
			<label>
				<input type="checkbox" class="filter-expiry" data-range="1-3" checked>
				<?php echo isset( $expiry_ranges['1_3']['label'] ) ? esc_html( $expiry_ranges['1_3']['label'] ) : __( '1-3 months', 'inventory-manager-pro' ); ?>
			</label>
			<label>
				<input type="checkbox" class="filter-expiry" data-range="<1" checked>
				<?php echo isset( $expiry_ranges['less_1']['label'] ) ? esc_html( $expiry_ranges['less_1']['label'] ) : __( '< 1 month', 'inventory-manager-pro' ); ?>
			</label>
			<label>
				<input type="checkbox" class="filter-expiry" data-range="expired" checked>
				<?php echo isset( $expiry_ranges['expired']['label'] ) ? esc_html( $expiry_ranges['expired']['label'] ) : __( 'expired', 'inventory-manager-pro' ); ?>
			</label>
		</div>
		
		<div class="column-filters">
			<label><input type="checkbox" class="toggle-column" data-column="sku" checked disabled> <?php _e( 'SKU', 'inventory-manager-pro' ); ?></label>
			<label><input type="checkbox" class="toggle-column" data-column="product_name" checked disabled> <?php _e( 'PRODUCT NAME', 'inventory-manager-pro' ); ?></label>
			<label><input type="checkbox" class="toggle-column" data-column="batch" checked disabled> <?php _e( 'BATCH', 'inventory-manager-pro' ); ?></label>
			<label><input type="checkbox" class="toggle-column" data-column="stock_qty" checked disabled> <?php _e( 'STOCK QTY', 'inventory-manager-pro' ); ?></label>
			<label><input type="checkbox" class="toggle-column" data-column="supplier" checked> <?php _e( 'SUPPLIER', 'inventory-manager-pro' ); ?></label>
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
		<div class="logs-search-box">
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
				<th data-sort="supplier" class="column-supplier"><?php _e( 'SUPPLIER', 'inventory-manager-pro' ); ?> <span class="sort-icon"></span></th>
				<th data-sort="expiry" class="column-expiry"><?php _e( 'EXPIRY', 'inventory-manager-pro' ); ?> <span class="sort-icon"></span></th>
				<th data-sort="origin" class="column-origin"><?php _e( 'ORIGIN', 'inventory-manager-pro' ); ?> <span class="sort-icon"></span></th>
				<th data-sort="location" class="column-location"><?php _e( 'LOCATION', 'inventory-manager-pro' ); ?> <span class="sort-icon"></span></th>
				<th data-sort="stock_cost" class="column-stock_cost"><?php _e( 'STOCK COST', 'inventory-manager-pro' ); ?> <span class="sort-icon"></span></th>
				<th data-sort="landed_cost" class="column-landed_cost"><?php _e( 'LANDED COST', 'inventory-manager-pro' ); ?> <span class="sort-icon"></span></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td colspan="10" class="loading"><?php _e( 'Loading...', 'inventory-manager-pro' ); ?></td>
			</tr>
		</tbody>
	</table>
	
	<div class="pagination">
		<!-- Pagination will be added via JavaScript -->
	</div>
</div>

<script id="batch-row-template" type="text/template">
	<tr data-id="{{id}}" class="expiry-{{expiry_range}}">
		<td>{{sku}}</td>
		<td>{{product_name}}</td>
		<td>{{batch_number}}</td>
		<td>{{stock_qty}}</td>
		<td class="column-supplier">{{supplier_name}}</td>
		<td class="column-expiry">{{expiry_formatted}}</td>
		<td class="column-origin">{{origin}}</td>
		<td class="column-location">{{location}}</td>
		<td class="column-stock_cost">{{stock_cost_formatted}}</td>
		<td class="column-landed_cost">{{landed_cost_formatted}}</td>
	</tr>
</script>