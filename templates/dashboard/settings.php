<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="inventory-manager-settings" id="settings-tab">
    <div class="section-header">
        <h2><?php _e( 'Settings', 'inventory-manager-pro' ); ?></h2>
    </div>

    <div class="inventory-settings-suppliers">
        <h3><?php _e( 'Suppliers', 'inventory-manager-pro' ); ?></h3>
        <table class="widefat">
            <thead><tr><th><?php _e( 'Name', 'inventory-manager-pro' ); ?></th><th><?php _e( 'Transit Time', 'inventory-manager-pro' ); ?></th><th></th></tr></thead>
            <tbody id="supplier-list"></tbody>
        </table>
        <h4><?php _e( 'Add Supplier', 'inventory-manager-pro' ); ?></h4>
        <form id="add-supplier-form">
            <input type="text" id="new_supplier_name" placeholder="<?php esc_attr_e( 'Supplier Name', 'inventory-manager-pro' ); ?>" required>
            <select id="new_supplier_transit"></select>
            <button type="submit" class="button"><?php _e( 'Add', 'inventory-manager-pro' ); ?></button>
        </form>
    </div>

    <hr />
    <div class="inventory-settings-transit">
        <h3><?php _e( 'Transit Times', 'inventory-manager-pro' ); ?></h3>
        <table class="widefat">
            <thead><tr><th><?php _e( 'ID', 'inventory-manager-pro' ); ?></th><th><?php _e( 'Label', 'inventory-manager-pro' ); ?></th><th></th></tr></thead>
            <tbody id="transit-list"></tbody>
        </table>
        <h4><?php _e( 'Add Transit Time', 'inventory-manager-pro' ); ?></h4>
        <form id="add-transit-form">
            <input type="text" id="new_transit_id" placeholder="<?php esc_attr_e( 'ID', 'inventory-manager-pro' ); ?>" required>
            <input type="text" id="new_transit_name" placeholder="<?php esc_attr_e( 'Label', 'inventory-manager-pro' ); ?>" required>
            <button type="submit" class="button"><?php _e( 'Add', 'inventory-manager-pro' ); ?></button>
        </form>
    </div>
</div>
