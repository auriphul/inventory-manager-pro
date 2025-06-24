<?php
/**
 * The Activator
 *
 * @since      1.0.0
 * @package    Inventory_Manager_Pro
 */

/**
 * Fired during plugin activation.
 *
 * @since      1.0.0
 * @package    Inventory_Manager_Pro
 */
class Inventory_Activator {
	/**
	 * Create necessary database tables during plugin activation.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$table_batches = $wpdb->prefix . 'inventory_batches';
		$sql_batches   = "CREATE TABLE {$table_batches} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sku varchar(100) NOT NULL,
            product_id bigint(20) NOT NULL,
            batch_number varchar(100) NOT NULL,
            supplier_id bigint(20),
            expiry_date date,
            origin varchar(100),
            location varchar(100),
            unit_cost decimal(15,4),
            freight_markup decimal(15,4),
            stock_qty decimal(15,4) NOT NULL DEFAULT 0,
            date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY batch_product (product_id, batch_number),
            KEY sku (sku),
            KEY expiry_date (expiry_date)
        ) {$charset_collate};";

		$table_movements = $wpdb->prefix . 'inventory_stock_movements';
		$sql_movements   = "CREATE TABLE {$table_movements} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            batch_id bigint(20) NOT NULL,
            movement_type varchar(20) NOT NULL,
            reference varchar(100),
            quantity decimal(15,4) NOT NULL,
            date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_by bigint(20),
            PRIMARY KEY (id),
            KEY batch_id (batch_id),
            KEY movement_type (movement_type),
            KEY date_created (date_created)
        ) {$charset_collate};";

		$table_suppliers = $wpdb->prefix . 'inventory_suppliers';
		$sql_suppliers   = "CREATE TABLE {$table_suppliers} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            transit_time varchar(20) NOT NULL,
            date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY name (name)
        ) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_batches );
		dbDelta( $sql_movements );
		dbDelta( $sql_suppliers );

		self::add_capabilities();

		self::create_dashboard_page();

		self::set_default_options();
	}

	/**
	 * Add custom capabilities for inventory management.
	 */
	private static function add_capabilities() {
		$roles = array( 'administrator', 'shop_manager' );

		foreach ( $roles as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				$role->add_cap( 'manage_inventory' );
			}
		}
	}

	/**
	 * Create dashboard page for the inventory manager.
	 */
	private static function create_dashboard_page() {
		$page_id = get_option( 'inventory_dashboard_page_id' );

		if ( ! $page_id || get_post_status( $page_id ) !== 'publish' ) {
			$page_data = array(
				'post_title'   => 'Inventory Manager',
				'post_content' => '[inventory_dashboard]',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_author'  => 1,
			);

			$page_id = wp_insert_post( $page_data );
			update_option( 'inventory_dashboard_page_id', $page_id );
		}
	}

	/**
	 * Set default plugin options.
	 */
	private static function set_default_options() {
		if ( ! get_option( 'inventory_manager_frontend_deduction_method' ) ) {
			update_option( 'inventory_manager_frontend_deduction_method', 'closest_expiry' );
		}

		if ( ! get_option( 'inventory_manager_backend_deduction_method' ) ) {
			update_option( 'inventory_manager_backend_deduction_method', 'closest_expiry' );
		}

		$default_frontend_fields = array(
			'supplier'  => array(
				'display_single'  => 'yes',
				'display_archive' => 'no',
				'label'           => 'Supplier',
				'color'           => '#333333',
			),
			'batch'     => array(
				'display_single'  => 'yes',
				'display_archive' => 'no',
				'label'           => 'Batch',
				'color'           => '#333333',
			),
			'expiry'    => array(
				'display_single'  => 'yes',
				'display_archive' => 'no',
				'label'           => 'Expiry',
				'color'           => '#333333',
			),
			'origin'    => array(
				'display_single'  => 'no',
				'display_archive' => 'no',
				'label'           => 'Origin',
				'color'           => '#333333',
			),
			'location'  => array(
				'display_single'  => 'no',
				'display_archive' => 'no',
				'label'           => 'Location',
				'color'           => '#333333',
			),
			'stock_qty' => array(
				'display_single'  => 'no',
				'display_archive' => 'no',
				'label'           => 'Stock Qty',
				'color'           => '#333333',
			),
		);

		if ( ! get_option( 'inventory_manager_frontend_fields' ) ) {
			update_option( 'inventory_manager_frontend_fields', $default_frontend_fields );
		}

		$default_notes = array(
			'show_in_stock'        => 'yes',
			'in_stock_note'        => 'In stock',
			'in_stock_color'       => '#009900',
			'show_stock_qty'       => 'yes',
			'stock_qty_note'       => '{qty} units in stock',
			'stock_qty_color'      => '#333333',
			'show_backorder'       => 'yes',
			'backorder_note'       => 'On backorder',
			'backorder_color'      => '#cc9900',
			'show_backorder_popup' => 'yes',
			'backorder_popup'      => 'This item is on backorder and scheduled to arrive in {transit_time} days',
		);

		if ( ! get_option( 'inventory_manager_frontend_notes' ) ) {
			update_option( 'inventory_manager_frontend_notes', $default_notes );
		}

		$default_expiry_ranges = array(
			'6_plus'  => array(
				'label'      => '6+ months',
				'color'      => '#e3f2fd',
				'text_color' => '#0d47a1',
			),
			'3_6'     => array(
				'label'      => '3-6 months',
				'color'      => '#e8f5e9',
				'text_color' => '#1b5e20',
			),
			'1_3'     => array(
				'label'      => '1-3 months',
				'color'      => '#fff9c4',
				'text_color' => '#f57f17',
			),
			'less_1'  => array(
				'label'      => '< 1 month',
				'color'      => '#ffecb3',
				'text_color' => '#e65100',
			),
			'expired' => array(
				'label'      => 'expired',
				'color'      => '#ffccbc',
				'text_color' => '#bf360c',
			),
		);

		if ( ! get_option( 'inventory_manager_expiry_ranges' ) ) {
			update_option( 'inventory_manager_expiry_ranges', $default_expiry_ranges );
		}

		$default_adjustment_types = array(
			'damages'       => array(
				'label'       => 'Damages',
				'calculation' => 'deduct',
			),
			'received_more' => array(
				'label'       => 'Received MORE',
				'calculation' => 'add',
			),
			'received_less' => array(
				'label'       => 'Received LESS',
				'calculation' => 'deduct',
			),
			'free_samples'  => array(
				'label'       => 'Free Samples',
				'calculation' => 'deduct',
			),
		);

                if ( ! get_option( 'inventory_manager_adjustment_types' ) ) {
                        update_option( 'inventory_manager_adjustment_types', $default_adjustment_types );
                }

                if ( ! get_option( 'inventory_manager_currency' ) ) {
                        update_option( 'inventory_manager_currency', get_woocommerce_currency_symbol() );
                }
        }
}
