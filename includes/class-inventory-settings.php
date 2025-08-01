<?php
/**
 * Handle plugin settings.
 *
 * @since      1.0.0
 * @package    Inventory_Manager_Pro
 */

class Inventory_Settings {
       private $plugin;
       /** Database helper */
       private $db;

       public function __construct( $plugin ) {
               $this->plugin = $plugin;
               $this->db     = new Inventory_Database();
       }

	/**
	 * Add settings page to admin menu.
	 */
	public function add_settings_page() {
		add_menu_page(
			__( 'Inventory Manager', 'inventory-manager-pro' ),
			__( 'Inventory Manager', 'inventory-manager-pro' ),
			'manage_inventory',
			'inventory-manager',
			array( $this, 'render_settings_page' ),
			'dashicons-clipboard',
			56
		);

		add_submenu_page(
			'inventory-manager',
			__( 'Settings', 'inventory-manager-pro' ),
			__( 'Settings', 'inventory-manager-pro' ),
			'manage_inventory',
			'inventory-manager-settings',
			array( $this, 'render_settings_page' )
		);

        }

        /**
         * Redirect to frontend dashboard.
         */
        public function redirect_to_dashboard() {}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		// Backend settings
		register_setting( 'inventory_manager_backend', 'inventory_manager_backend_fields' );
		register_setting( 'inventory_manager_backend', 'inventory_manager_backend_deduction_method' );
                register_setting( 'inventory_manager_backend', 'inventory_manager_backend_select_batch' );
                register_setting( 'inventory_manager_backend', 'inventory_manager_sync_stock' );

		// Frontend settings
		register_setting( 'inventory_manager_frontend', 'inventory_manager_frontend_fields' );
		register_setting( 'inventory_manager_frontend', 'inventory_manager_frontend_deduction_method' );
		register_setting( 'inventory_manager_frontend', 'inventory_manager_frontend_notes' );

               // Supplier settings
               register_setting( 'inventory_manager_suppliers', 'inventory_manager_suppliers' );

               // Brand transit time settings
               register_setting( 'inventory_manager_brands', 'inventory_manager_brand_transit' );

		// Detailed logs settings
		register_setting( 'inventory_manager_logs', 'inventory_manager_expiry_ranges' );
		register_setting( 'inventory_manager_logs', 'inventory_manager_adjustment_types' );
                register_setting( 'inventory_manager_logs', 'inventory_manager_email_notifications' );
                register_setting( 'inventory_manager_logs', 'inventory_manager_currency' );
        }

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		// Check if settings were saved
		$message = '';
		if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) {
			$message = __( 'Settings saved.', 'inventory-manager-pro' );
		}

		// Get current tab
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'backend';

		echo '<div class="wrap">';

		// Settings header
		echo '<h1>' . __( 'Inventory Manager Settings', 'inventory-manager-pro' ) . '</h1>';

		// Show message
		if ( $message ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
		}

		// Tabs navigation
		echo '<h2 class="nav-tab-wrapper">';
		echo '<a href="?page=inventory-manager-settings&tab=backend" class="nav-tab ' . ( $tab === 'backend' ? 'nav-tab-active' : '' ) . '">' . __( 'Backend Settings', 'inventory-manager-pro' ) . '</a>';
		echo '<a href="?page=inventory-manager-settings&tab=frontend" class="nav-tab ' . ( $tab === 'frontend' ? 'nav-tab-active' : '' ) . '">' . __( 'Frontend Settings', 'inventory-manager-pro' ) . '</a>';
            //    echo '<a href="?page=inventory-manager-settings&tab=suppliers" class="nav-tab ' . ( $tab === 'suppliers' ? 'nav-tab-active' : '' ) . '">' . __( 'Suppliers & Transit Time', 'inventory-manager-pro' ) . '</a>';
               echo '<a href="?page=inventory-manager-settings&tab=brands" class="nav-tab ' . ( $tab === 'brands' ? 'nav-tab-active' : '' ) . '">' . __( 'WooCommerce Brands', 'inventory-manager-pro' ) . '</a>';
               echo '<a href="?page=inventory-manager-settings&tab=logs" class="nav-tab ' . ( $tab === 'logs' ? 'nav-tab-active' : '' ) . '">' . __( 'Detailed Logs Settings', 'inventory-manager-pro' ) . '</a>';
		echo '</h2>';

               if ( 'suppliers' === $tab ) {
                       $form_message = $this->handle_supplier_form_submission();

                       echo '<form method="post" action="options.php">';
                       settings_fields( 'inventory_manager_suppliers' );
                       $this->render_supplier_settings();
                       submit_button();
                       echo '</form>';

                       $this->render_supplier_form( $form_message );
               } elseif ( 'brands' === $tab ) {
                       $brand_message = $this->handle_brand_form_submission();

                       echo '<form method="post" action="options.php">';
                       settings_fields( 'inventory_manager_brands' );
                       $this->render_brands_settings();
                       submit_button();
                       echo '</form>';

                       $this->render_brand_form( $brand_message );
               } else {
                        echo '<form method="post" action="options.php">';

                        switch ( $tab ) {
                                case 'frontend':
                                        settings_fields( 'inventory_manager_frontend' );
                                        $this->render_frontend_settings();
                                        break;
                               case 'logs':
                                       settings_fields( 'inventory_manager_logs' );
                                       $this->render_logs_settings();
                                       break;
                               default:
                                       settings_fields( 'inventory_manager_backend' );
                                       $this->render_backend_settings();
                                       break;
                        }

                        submit_button();
                        echo '</form>';
                }

                echo '</div>';
        }

	/**
	 * Render backend settings.
	 */
	private function render_backend_settings() {
		$backend_fields   = get_option( 'inventory_manager_backend_fields', array() );
		$deduction_method = get_option( 'inventory_manager_backend_deduction_method', 'closest_expiry' );
		$select_batch     = get_option( 'inventory_manager_backend_select_batch', 'no' );

		echo '<h2>' . __( 'Backend Settings', 'inventory-manager-pro' ) . '</h2>';

		// Fields to show on order page
		// echo '<h3>' . __( 'Fields to Show on Backend: Order Page', 'inventory-manager-pro' ) . '</h3>';
		// echo '<table class="form-table">';

		// $field_options = array(
		// 	'supplier'  => __( 'Supplier', 'inventory-manager-pro' ),
		// 	'batch'     => __( 'Batch', 'inventory-manager-pro' ),
		// 	'expiry'    => __( 'Expiry', 'inventory-manager-pro' ),
		// 	'origin'    => __( 'Origin', 'inventory-manager-pro' ),
		// 	'location'  => __( 'Location', 'inventory-manager-pro' ),
		// 	'stock_qty' => __( 'Stock Qty', 'inventory-manager-pro' ),
		// );

		// foreach ( $field_options as $field_key => $field_label ) {
		// 	$checked = isset( $backend_fields[ $field_key ]['show'] ) && $backend_fields[ $field_key ]['show'] === 'yes' ? 'checked' : '';
		// 	$color   = isset( $backend_fields[ $field_key ]['color'] ) ? $backend_fields[ $field_key ]['color'] : '#333333';

		// 	echo '<tr>';
		// 	echo '<th scope="row">' . esc_html( $field_label ) . '</th>';
		// 	echo '<td>';
		// 	echo '<label>';
		// 	echo '<input type="checkbox" name="inventory_manager_backend_fields[' . esc_attr( $field_key ) . '][show]" value="yes" ' . $checked . '>';
		// 	echo '</label>';
		// 	echo '<input type="text" name="inventory_manager_backend_fields[' . esc_attr( $field_key ) . '][label]" value="' . esc_attr( isset( $backend_fields[ $field_key ]['label'] ) ? $backend_fields[ $field_key ]['label'] : $field_label ) . '" class="regular-text">';
		// 	echo '<input type="color" name="inventory_manager_backend_fields[' . esc_attr( $field_key ) . '][color]" value="' . esc_attr( $color ) . '">';
		// 	echo '</td>';
		// 	echo '</tr>';
		// }

		// echo '</table>';

		// Stock deduction method
		echo '<h3>' . __( 'Stock Deduction for Orders Placed on Backend', 'inventory-manager-pro' ) . '</h3>';
		echo '<table class="form-table">';

		echo '<tr>';
		echo '<th scope="row">' . __( 'Option to select Batch', 'inventory-manager-pro' ) . '</th>';
		echo '<td>';
		echo '<label>';
		echo '<input type="radio" name="inventory_manager_backend_select_batch" value="yes" ' . checked( $select_batch, 'yes', false ) . '>';
		echo __( 'Yes', 'inventory-manager-pro' );
		echo '</label>';
		echo '<br>';
		echo '<label>';
		echo '<input type="radio" name="inventory_manager_backend_select_batch" value="no" ' . checked( $select_batch, 'no', false ) . '>';
		echo __( 'No', 'inventory-manager-pro' );
		echo '</label>';
		echo '<p class="description">' . __( 'If this option is selected, a dropdown with all available Batches will appear on Order page', 'inventory-manager-pro' ) . '</p>';
		echo '</td>';
		echo '</tr>';

                echo '<tr>';
                echo '<th scope="row">' . __( 'Deduction Method', 'inventory-manager-pro' ) . '</th>';
                echo '<td>';
		echo '<label>';
		echo '<input type="radio" name="inventory_manager_backend_deduction_method" value="closest_expiry" ' . checked( $deduction_method, 'closest_expiry', false ) . '>';
		echo __( 'Closest Expiry first', 'inventory-manager-pro' );
		echo '</label>';
		echo '<p class="description">' . __( 'When closest Expiry is depleted, deduction will move on to next closest expiry.', 'inventory-manager-pro' ) . '</p>';
		echo '<br>';
		echo '<label>';
		echo '<input type="radio" name="inventory_manager_backend_deduction_method" value="fifo" ' . checked( $deduction_method, 'fifo', false ) . '>';
		echo __( 'FIFO (first in, first out)', 'inventory-manager-pro' );
		echo '</label>';
		echo '<p class="description">' . __( 'Stock deducted from oldest entry, and when depleted, deduction will move on to next oldest entry.', 'inventory-manager-pro' ) . '</p>';
		echo '</td>';
		echo '</tr>';

                echo '</table>';

                // Option to sync WooCommerce stock with batches
                $sync_stock = get_option( 'inventory_manager_sync_stock', 'yes' );
                echo '<h3>' . __( 'Sync Product Stock with Batches', 'inventory-manager-pro' ) . '</h3>';
                echo '<table class="form-table">';
                echo '<tr>';
                echo '<th scope="row">' . __( 'Synchronize product stock', 'inventory-manager-pro' ) . '</th>';
                echo '<td>';
                echo '<label>';
                echo '<input type="radio" name="inventory_manager_sync_stock" value="yes" ' . checked( $sync_stock, 'yes', false ) . '>';
                echo __( 'Yes', 'inventory-manager-pro' );
                echo '</label>';
                echo '<br>';
                echo '<label>';
                echo '<input type="radio" name="inventory_manager_sync_stock" value="no" ' . checked( $sync_stock, 'no', false ) . '>';
                echo __( 'No', 'inventory-manager-pro' );
                echo '</label>';
                echo '<p class="description">' . __( 'If disabled, WooCommerce product stock and batch stock will be managed separately.', 'inventory-manager-pro' ) . '</p>';
                echo '</td>';
                echo '</tr>';
                echo '</table>';
        }

       /**
        * Get transit time options.
        *
        * @return array
        */
       private function get_transit_time_options() {
               $times = array(
                       '3_days'  => __( '3 days', 'inventory-manager-pro' ),
                       '1_week'  => __( '1 week', 'inventory-manager-pro' ),
                       '2_weeks' => __( '2 weeks', 'inventory-manager-pro' ),
                       '20_days' => __( '20 days', 'inventory-manager-pro' ),
                       '1_month' => __( '1 month', 'inventory-manager-pro' ),
                       '40_days' => __( '40 days', 'inventory-manager-pro' ),
               );

            //    $custom = get_option( 'inventory_manager_suppliers', array() );
            //    if ( ! empty( $custom['transit_times'] ) && is_array( $custom['transit_times'] ) ) {
            //            $times = $custom['transit_times'];
            //    }

               return $times;
       }

       /**
        * Handle supplier form submission.
        *
        * @return string|WP_Error
        */
       private function handle_supplier_form_submission() {
               if ( empty( $_POST['add_supplier'] ) ) {
                       return '';
               }

               if ( ! isset( $_POST['supplier_nonce'] ) || ! wp_verify_nonce( $_POST['supplier_nonce'], 'supplier_registration' ) ) {
                       return new WP_Error( 'nonce', __( 'Security check failed.', 'inventory-manager-pro' ) );
               }

               $name    = isset( $_POST['supplier_name'] ) ? sanitize_text_field( $_POST['supplier_name'] ) : '';
               $transit = isset( $_POST['transit_time'] ) ? sanitize_text_field( $_POST['transit_time'] ) : '';

               if ( '' === $name ) {
                       return new WP_Error( 'name', __( 'Supplier name is required.', 'inventory-manager-pro' ) );
               }

               if ( '' === $transit ) {
                       return new WP_Error( 'transit', __( 'Transit time is required.', 'inventory-manager-pro' ) );
               }

               $result = $this->db->create_supplier( $name, $transit );
               if ( is_wp_error( $result ) ) {
                       return $result;
               }

               return __( 'Supplier added successfully.', 'inventory-manager-pro' );
       }

       /**
        * Render supplier registration form.
        *
        * @param string|WP_Error $message Optional message to display.
        */
       private function render_supplier_form( $message = '' ) {
               $transit_times = $this->get_transit_time_options();

               if ( $message ) {
                       if ( is_wp_error( $message ) ) {
                               echo '<div class="notice notice-error"><p>' . esc_html( $message->get_error_message() ) . '</p></div>';
                       } else {
                               echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
                       }
               }

               echo '<h3>' . __( 'Add Supplier', 'inventory-manager-pro' ) . '</h3>';
               echo '<form method="post" action="">';
               wp_nonce_field( 'supplier_registration', 'supplier_nonce' );
               echo '<table class="form-table"style="max-width: 800px;">';
               echo '<tr>';
               echo '<th scope="row"><label for="supplier_name">' . __( 'Supplier Name', 'inventory-manager-pro' ) . '</label></th>';
               echo '<th scope="row"><label for="transit_time">' . __( 'Transit Time', 'inventory-manager-pro' ) . '</label></th>';
               echo '</tr>';
               echo '<tr>';
               echo '<td><input type="text" name="supplier_name" id="supplier_name" class="regular-text" required></td>';
               echo '<td><select name="transit_time" id="transit_time" required style="width:100%;">';
               foreach ( $transit_times as $key => $label ) {
                       echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</option>';
               }
               echo '</select></td>';
               echo '</tr>';
               echo '</table>';
               echo '<p><input type="submit" name="add_supplier" class="button button-primary" value="' . esc_attr__( 'Register Supplier', 'inventory-manager-pro' ) . '"></p>';
               echo '</form>';
       }

       /**
        * Handle brand form submission.
        *
        * @return string|WP_Error
        */
       private function handle_brand_form_submission() {
               if ( empty( $_POST['add_brand_transit'] ) && empty( $_POST['remove_brand_transit'] ) ) {
                       return '';
               }

               if ( ! isset( $_POST['brand_nonce'] ) || ! wp_verify_nonce( $_POST['brand_nonce'], 'brand_transit_assignment' ) ) {
                       return new WP_Error( 'nonce', __( 'Security check failed.', 'inventory-manager-pro' ) );
               }

               $brand_id = isset( $_POST['brand_id'] ) ? absint( $_POST['brand_id'] ) : 0;
               $transit  = isset( $_POST['brand_transit_time'] ) ? sanitize_text_field( $_POST['brand_transit_time'] ) : '';

               $mappings = get_option( 'inventory_manager_brand_transit', array() );

               if ( isset( $_POST['remove_brand_transit'] ) ) {
                       if ( $brand_id && isset( $mappings[ $brand_id ] ) ) {
                               unset( $mappings[ $brand_id ] );
                               update_option( 'inventory_manager_brand_transit', $mappings );
                       }

                       return __( 'Brand transit time removed.', 'inventory-manager-pro' );
               }

               if ( ! $brand_id ) {
                       return new WP_Error( 'brand', __( 'Brand is required.', 'inventory-manager-pro' ) );
               }

               if ( '' === $transit ) {
                       return new WP_Error( 'transit', __( 'Transit time is required.', 'inventory-manager-pro' ) );
               }

               $mappings[ $brand_id ] = $transit;

               update_option( 'inventory_manager_brand_transit', $mappings );

               return __( 'Brand transit time saved.', 'inventory-manager-pro' );
       }

       /**
        * Render form for assigning transit times to brands.
        *
        * @param string|WP_Error $message Optional message to display.
        */
       private function render_brand_form( $message = '' ) {
               $brands        = taxonomy_exists( 'product_brand' ) ? get_terms( array( 'taxonomy' => 'product_brand', 'hide_empty' => false ) ) : array();
               $transit_times = $this->get_transit_time_options();

               if ( $message ) {
                       if ( is_wp_error( $message ) ) {
                               echo '<div class="notice notice-error"><p>' . esc_html( $message->get_error_message() ) . '</p></div>';
                       } else {
                               echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
                       }
               }

               if ( empty( $brands ) ) {
                       echo '<p>' . __( 'No brands found.', 'inventory-manager-pro' ) . '</p>';
                       return;
               }

            //    echo '<h3>' . __( 'Assign Transit Time to Brand', 'inventory-manager-pro' ) . '</h3>';
            //    echo '<form method="post" action="">';
            //    wp_nonce_field( 'brand_transit_assignment', 'brand_nonce' );
            //    echo '<table class="form-table" style="max-width: 800px;">';
            //    echo '<tr>';
            //    echo '<th scope="row"><label for="brand_id">' . __( 'Brand', 'inventory-manager-pro' ) . '</label></th>';
            //    echo '<th scope="row"><label for="brand_transit_time">' . __( 'Transit Time', 'inventory-manager-pro' ) . '</label></th>';
            //    echo '</tr>';
            //    echo '<tr>';
            //    echo '<td><select name="brand_id" id="brand_id" class="brand-select" style="width:100%;">';
            //    foreach ( $brands as $brand ) {
            //            echo '<option value="' . esc_attr( $brand->term_id ) . '">' . esc_html( $brand->name ) . '</option>';
            //    }
            //    echo '</select></td>';

            //    echo '<td><select name="brand_transit_time" id="brand_transit_time" style="width:100%;">';
            //    foreach ( $transit_times as $key => $label ) {
            //            echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</option>';
            //    }
            //    echo '</select></td>';
            //    echo '</tr>';
            //    echo '</table>';
            //    echo '<p><input type="submit" name="add_brand_transit" class="button button-primary" value="' . esc_attr__( 'Assign Transit Time', 'inventory-manager-pro' ) . '"></p>';
            //    echo '</form>';
       }

       /**
        * Render WooCommerce brand transit time settings.
        */
       private function render_brands_settings() {
               $brands   = taxonomy_exists( 'product_brand' ) ? get_terms( array( 'taxonomy' => 'product_brand', 'hide_empty' => false ) ) : array();
               $mappings = get_option( 'inventory_manager_brand_transit', array() );
               $transit_times = $this->get_transit_time_options();

               echo '<h2>' . __( 'WooCommerce Brands Transit Times', 'inventory-manager-pro' ) . '</h2>';

               if ( empty( $brands ) ) {
                       echo '<p>' . __( 'No brands found.', 'inventory-manager-pro' ) . '</p>';
                       return;
               }

               echo '<table class="widefat fixed" cellspacing="0" style="max-width:800px;">';
               echo '<thead><tr><th>' . __( 'Brand', 'inventory-manager-pro' ) . '</th><th>' . __( 'Transit Time', 'inventory-manager-pro' ) . '</th><th>' . __( 'Actions', 'inventory-manager-pro' ) . '</th></tr></thead>';
               echo '<tbody>';
               foreach ( $brands as $brand ) {
                       $selected = isset( $mappings[ $brand->term_id ] ) ? $mappings[ $brand->term_id ] : '';
                       echo '<tr>';
                       echo '<td>' . esc_html( $brand->name ) . '</td>';
                       echo '<td>';
                       echo '<select name="inventory_manager_brand_transit[' . esc_attr( $brand->term_id ) . ']" class="brand-transit-select" style="width:100%;">';
                       echo '<option value="">' . esc_html__( 'Default', 'inventory-manager-pro' ) . '</option>';
                       foreach ( $transit_times as $key => $label ) {
                               echo '<option value="' . esc_attr( $key ) . '" ' . selected( $selected, $key, false ) . '>' . esc_html( $label ) . '</option>';
                       }
                       echo '</select>';
                       echo '</td>';
                       echo '<td>';
                       echo '<form method="post" style="display:inline;">';
                       wp_nonce_field( 'brand_transit_assignment', 'brand_nonce' );
                       echo '<input type="hidden" name="brand_id" value="' . esc_attr( $brand->term_id ) . '">';
                       echo '<input type="submit" name="remove_brand_transit" class="button" value="' . esc_attr__( 'Remove', 'inventory-manager-pro' ) . '">';
                       echo '</form>';
                       echo '</td>';
                       echo '</tr>';
               }
               echo '</tbody></table>';
               echo '<p class="description">' . __( 'Use the form below to assign new transit times. Existing selections can be adjusted above and saved.', 'inventory-manager-pro' ) . '</p>';
       }

	/**
	 * Render frontend settings.
	 */
	private function render_frontend_settings() {
		$frontend_fields  = get_option( 'inventory_manager_frontend_fields', array() );
		$deduction_method = get_option( 'inventory_manager_frontend_deduction_method', 'closest_expiry' );
		$notes            = get_option( 'inventory_manager_frontend_notes', array() );

		echo '<h2>' . __( 'Frontend Settings', 'inventory-manager-pro' ) . '</h2>';

		// Notes to show on frontend
		echo '<h3>' . __( 'Notes to Show on Frontend', 'inventory-manager-pro' ) . '</h3>';
		echo '<table class="form-table">';

		// In-stock notes
		echo '<tr>';
		echo '<th scope="row">' . __( 'When item is in stock', 'inventory-manager-pro' ) . '</th>';
		echo '<td>';

		// Show note when in stock
		$show_in_stock = isset( $notes['show_in_stock'] ) && $notes['show_in_stock'] === 'yes' ? 'checked' : '';
		echo '<label>';
		echo '<input type="checkbox" name="inventory_manager_frontend_notes[show_in_stock]" value="yes" ' . $show_in_stock . '>';
		echo __( 'Show note when in Stock', 'inventory-manager-pro' );
		echo '</label>';
		echo '<br>';
		echo '<input type="text" name="inventory_manager_frontend_notes[in_stock_note]" value="' . esc_attr( isset( $notes['in_stock_note'] ) ? $notes['in_stock_note'] : __( 'In stock', 'inventory-manager-pro' ) ) . '" class="regular-text">';
		echo '<input type="color" name="inventory_manager_frontend_notes[in_stock_color]" value="' . esc_attr( isset( $notes['in_stock_color'] ) ? $notes['in_stock_color'] : '#009900' ) . '">';

		// Show stock quantity
		$show_stock_qty = isset( $notes['show_stock_qty'] ) && $notes['show_stock_qty'] === 'yes' ? 'checked' : '';
		echo '<br><br>';
		echo '<label>';
		echo '<input type="checkbox" name="inventory_manager_frontend_notes[show_stock_qty]" value="yes" ' . $show_stock_qty . '>';
		echo __( 'Show Stock Qty (if in stock)', 'inventory-manager-pro' );
		echo '</label>';
		echo '<br>';
		echo '<input type="text" name="inventory_manager_frontend_notes[stock_qty_note]" value="' . esc_attr( isset( $notes['stock_qty_note'] ) ? $notes['stock_qty_note'] : '{qty} ' . __( 'units in stock', 'inventory-manager-pro' ) ) . '" class="regular-text">';
		echo '<input type="color" name="inventory_manager_frontend_notes[stock_qty_color]" value="' . esc_attr( isset( $notes['stock_qty_color'] ) ? $notes['stock_qty_color'] : '#333333' ) . '">';
		echo '<p class="description">' . __( 'Use {qty} to display the stock quantity.', 'inventory-manager-pro' ) . '</p>';

		echo '</td>';
		echo '</tr>';

		// Backorder notes
		echo '<tr>';
		echo '<th scope="row">' . __( 'When item is on backorder', 'inventory-manager-pro' ) . '</th>';
		echo '<td>';

		// Show note when on backorder
		$show_backorder = isset( $notes['show_backorder'] ) && $notes['show_backorder'] === 'yes' ? 'checked' : '';
		echo '<label>';
		echo '<input type="checkbox" name="inventory_manager_frontend_notes[show_backorder]" value="yes" ' . $show_backorder . '>';
		echo __( 'Show note when on Backorder', 'inventory-manager-pro' );
		echo '</label>';
		echo '<br>';
		echo '<input type="text" name="inventory_manager_frontend_notes[backorder_note]" value="' . esc_attr( isset( $notes['backorder_note'] ) ? $notes['backorder_note'] : __( 'On backorder', 'inventory-manager-pro' ) ) . '" class="regular-text">';
		echo '<input type="color" name="inventory_manager_frontend_notes[backorder_color]" value="' . esc_attr( isset( $notes['backorder_color'] ) ? $notes['backorder_color'] : '#cc9900' ) . '">';

		// Show popup with stock quantity
		$show_backorder_popup = isset( $notes['show_backorder_popup'] ) && $notes['show_backorder_popup'] === 'yes' ? 'checked' : '';
		echo '<br><br>';
		echo '<label>';
		echo '<input type="checkbox" name="inventory_manager_frontend_notes[show_backorder_popup]" value="yes" ' . $show_backorder_popup . '>';
		echo __( 'Show popup with Transit Time', 'inventory-manager-pro' );
		echo '</label>';
		echo '<br>';
		echo '<textarea name="inventory_manager_frontend_notes[backorder_popup]" rows="3" cols="50">' . esc_textarea( isset( $notes['backorder_popup'] ) ? $notes['backorder_popup'] : __( 'This item is on backorder and scheduled to arrive in {transit_time} days', 'inventory-manager-pro' ) ) . '</textarea>';
		echo '<input type="color" name="inventory_manager_frontend_notes[backorder_popup_color]" value="' . esc_attr( isset( $notes['backorder_popup_color'] ) ? $notes['backorder_popup_color'] : '#cc9900' ) . '">';
		echo '<p class="description">' . __( 'Use {transit_time} to display the transit time for the supplier.', 'inventory-manager-pro' ) . '</p>';

		echo '</td>';
		echo '</tr>';

		echo '</table>';

		// Fields to show on frontend
		echo '<h3>' . __( 'Fields to Show on Frontend', 'inventory-manager-pro' ) . '</h3>';
		echo '<table class="form-table">';

		$field_options = array(
			'supplier'  => __( 'Supplier', 'inventory-manager-pro' ),
			'batch'     => __( 'Batch', 'inventory-manager-pro' ),
			'expiry'    => __( 'Expiry', 'inventory-manager-pro' ),
			'origin'    => __( 'Origin', 'inventory-manager-pro' ),
			'location'  => __( 'Location', 'inventory-manager-pro' ),
			'stock_qty' => __( 'Stock Qty', 'inventory-manager-pro' ),
			'background_color' => __( 'Background Color', 'inventory-manager-pro' ),
		);

		foreach ( $field_options as $field_key => $field_label ) {
			$display_single  = isset( $frontend_fields[ $field_key ]['display_single'] ) && $frontend_fields[ $field_key ]['display_single'] === 'yes' ? 'checked' : '';
			$display_archive = isset( $frontend_fields[ $field_key ]['display_archive'] ) && $frontend_fields[ $field_key ]['display_archive'] === 'yes' ? 'checked' : '';
			$color           = isset( $frontend_fields[ $field_key ]['color'] ) ? $frontend_fields[ $field_key ]['color'] : '#333333';

			echo '<tr>';
			echo '<th scope="row">' . esc_html( $field_label ) . '</th>';
			echo '<td>';
			echo '<input type="text" name="inventory_manager_frontend_fields[' . esc_attr( $field_key ) . '][label]" value="' . esc_attr( isset( $frontend_fields[ $field_key ]['label'] ) ? $frontend_fields[ $field_key ]['label'] : $field_label ) . '" class="regular-text">';
			echo '<input type="color" name="inventory_manager_frontend_fields[' . esc_attr( $field_key ) . '][color]" value="' . esc_attr( $color ) . '">';
			echo '<br>';
			echo '<label>';
			echo '<input type="checkbox" name="inventory_manager_frontend_fields[' . esc_attr( $field_key ) . '][display_single]" value="yes" ' . $display_single . '>';
			echo __( 'Show on Single Product Page', 'inventory-manager-pro' );
			echo '</label>';
			echo '<br>';
			echo '<label>';
			echo '<input type="checkbox" name="inventory_manager_frontend_fields[' . esc_attr( $field_key ) . '][display_archive]" value="yes" ' . $display_archive . '>';
			echo __( 'Show on Product Archive Page', 'inventory-manager-pro' );
			echo '</label>';
			echo '</td>';
			echo '</tr>';
		}

		// Shortcode info
		echo '<tr>';
		echo '<th scope="row">' . __( 'Shortcodes', 'inventory-manager-pro' ) . '</th>';
		echo '<td>';
                echo '<p>' . __( 'Show selected Frontend Fields on Product Archive Page by adding this shortcode:', 'inventory-manager-pro' ) . ' <code>[inventory_batch_archive]</code></p>';
                echo '<p>' . __( 'Optionally pass <code>sku</code> or <code>product_id</code> to target a specific product.', 'inventory-manager-pro' ) . '</p>';
		echo '<p>' . __( 'Show selected Frontend Fields on Single Product Page by adding this shortcode:', 'inventory-manager-pro' ) . ' <code>[inventory_batch_single]</code></p>';
		echo '</td>';
		echo '</tr>';

		echo '</table>';

		// Stock deduction method
		echo '<h3>' . __( 'Stock Deduction for Orders Placed on Frontend', 'inventory-manager-pro' ) . '</h3>';
		echo '<table class="form-table">';

		echo '<tr>';
		echo '<th scope="row">' . __( 'Deduction Method', 'inventory-manager-pro' ) . '</th>';
		echo '<td>';
		echo '<label>';
		echo '<input type="radio" name="inventory_manager_frontend_deduction_method" value="closest_expiry" ' . checked( $deduction_method, 'closest_expiry', false ) . '>';
		echo __( 'Closest Expiry first', 'inventory-manager-pro' );
		echo '</label>';
		echo '<p class="description">' . __( 'When closest Expiry is depleted, deduction will move on to next closest expiry.', 'inventory-manager-pro' ) . '</p>';
		echo '<br>';
		echo '<label>';
		echo '<input type="radio" name="inventory_manager_frontend_deduction_method" value="fifo" ' . checked( $deduction_method, 'fifo', false ) . '>';
		echo __( 'FIFO (first in, first out)', 'inventory-manager-pro' );
		echo '</label>';
		echo '<p class="description">' . __( 'Stock deducted from oldest entry, and when depleted, deduction will move on to next oldest entry.', 'inventory-manager-pro' ) . '</p>';
		echo '</td>';
		echo '</tr>';

		echo '</table>';
	}

	/**
	 * Render supplier settings.
	 */
	private function render_supplier_settings() {
		echo '<h2>' . __( 'Suppliers & Transit Time Settings', 'inventory-manager-pro' ) . '</h2>';

		// Transit time options
		$transit_times = array(
			'3_days'  => __( '3 days', 'inventory-manager-pro' ),
			'1_week'  => __( '1 week', 'inventory-manager-pro' ),
			'2_weeks' => __( '2 weeks', 'inventory-manager-pro' ),
			'20_days' => __( '20 days', 'inventory-manager-pro' ),
			'1_month' => __( '1 month', 'inventory-manager-pro' ),
			'40_days' => __( '40 days', 'inventory-manager-pro' ),
		);

		echo '<h3>' . __( 'Transit Time Options', 'inventory-manager-pro' ) . '</h3>';
		echo '<p>' . __( 'These options will be available when adding new suppliers.', 'inventory-manager-pro' ) . '</p>';
		echo '<table class="form-table">';

		echo '<tr>';
		echo '<th scope="row">' . __( 'Transit Time Options', 'inventory-manager-pro' ) . '</th>';
		echo '<td>';

		foreach ( $transit_times as $key => $label ) {
			echo '<label>';
			echo '<input type="text" name="inventory_manager_suppliers[transit_times][' . esc_attr( $key ) . ']" value="' . esc_attr( $label ) . '" class="regular-text">';
			echo '</label><br>';
		}

		echo '</td>';
		echo '</tr>';

		echo '</table>';

		// Matched suppliers & transit times
		echo '<h3>' . __( 'Matched Suppliers & Transit Times', 'inventory-manager-pro' ) . '</h3>';
		echo '<p>' . __( 'This is a read-only view of current supplier transit times.', 'inventory-manager-pro' ) . '</p>';

		global $wpdb;
		$suppliers = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}inventory_suppliers ORDER BY id ASC" );

		if ( $suppliers ) {
			echo '<table class="widefat fixed" cellspacing="0">';
			echo '<thead>';
			echo '<tr>';
			echo '<th>' . __( 'Supplier ID', 'inventory-manager-pro' ) . '</th>';
			echo '<th>' . __( 'Supplier', 'inventory-manager-pro' ) . '</th>';
			echo '<th>' . __( 'Transit Time', 'inventory-manager-pro' ) . '</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';

			foreach ( $suppliers as $supplier ) {
				$pretty_time = ucwords( str_replace( '_', ' ', $supplier->transit_time ) );
				echo '<tr>';
				echo '<td>' . esc_html( $supplier->id ) . '</td>';
				echo '<td>' . esc_html( $supplier->name ) . '</td>';
				echo '<td>' . esc_html( $pretty_time ) . '</td>';
				echo '</tr>';
			}

			echo '</tbody>';
			echo '</table>';
		} else {
			echo '<p>' . __( 'No suppliers found.', 'inventory-manager-pro' ) . '</p>';
		}
	}

	/**
	 * Render logs settings.
	 */
	private function render_logs_settings() {
		$expiry_ranges       = get_option( 'inventory_manager_expiry_ranges', array() );
		$adjustment_types    = get_option( 'inventory_manager_adjustment_types', array() );
		$email_notifications = get_option( 'inventory_manager_email_notifications', array() );

		echo '<h2>' . __( 'Detailed Logs Settings', 'inventory-manager-pro' ) . '</h2>';

		// Expiry range colors
		echo '<h3>' . __( 'Expiry Range', 'inventory-manager-pro' ) . '</h3>';
		echo '<table class="form-table">';

		$range_options = array(
			'6_plus'  => __( '6+ months', 'inventory-manager-pro' ),
			'3_6'     => __( '3-6 months', 'inventory-manager-pro' ),
			'1_3'     => __( '1-3 months', 'inventory-manager-pro' ),
			'less_1'  => __( '< 1 month', 'inventory-manager-pro' ),
			'expired' => __( 'expired', 'inventory-manager-pro' ),
		);

		foreach ( $range_options as $range_key => $range_label ) {
			$bg_color   = isset( $expiry_ranges[ $range_key ]['color'] ) ? $expiry_ranges[ $range_key ]['color'] : '#ffffff';
			$text_color = isset( $expiry_ranges[ $range_key ]['text_color'] ) ? $expiry_ranges[ $range_key ]['text_color'] : '#333333';

			echo '<tr>';
			echo '<th scope="row">' . esc_html( $range_label ) . '</th>';
			echo '<td>';
			echo '<input type="text" name="inventory_manager_expiry_ranges[' . esc_attr( $range_key ) . '][label]" value="' . esc_attr( isset( $expiry_ranges[ $range_key ]['label'] ) ? $expiry_ranges[ $range_key ]['label'] : $range_label ) . '" class="regular-text">';
			echo '<br>';
			echo '<label>' . __( 'Background Color:', 'inventory-manager-pro' ) . '</label>';
			echo '<input type="color" name="inventory_manager_expiry_ranges[' . esc_attr( $range_key ) . '][color]" value="' . esc_attr( $bg_color ) . '">';
			echo '<br>';
			echo '<label>' . __( 'Text Color:', 'inventory-manager-pro' ) . '</label>';
			echo '<input type="color" name="inventory_manager_expiry_ranges[' . esc_attr( $range_key ) . '][text_color]" value="' . esc_attr( $text_color ) . '">';

			// Email notifications
			echo '<br>';
			echo '<label>';
			echo '<input type="checkbox" name="inventory_manager_email_notifications[' . esc_attr( $range_key ) . '][enabled]" value="yes" ' . ( isset( $email_notifications[ $range_key ]['enabled'] ) && $email_notifications[ $range_key ]['enabled'] === 'yes' ? 'checked' : '' ) . '>';
			echo __( 'Send email notification when products enter this range', 'inventory-manager-pro' );
			echo '</label>';
			echo '<br>';
			echo '<input type="email" name="inventory_manager_email_notifications[' . esc_attr( $range_key ) . '][email]" value="' . esc_attr( isset( $email_notifications[ $range_key ]['email'] ) ? $email_notifications[ $range_key ]['email'] : get_option( 'admin_email' ) ) . '" class="regular-text">';

			echo '</td>';
			echo '</tr>';
		}

                echo '</table>';

                // Currency
                echo '<h3>' . __( 'Currency', 'inventory-manager-pro' ) . '</h3>';
                echo '<table class="form-table">';
                $currency = get_option( 'inventory_manager_currency', get_woocommerce_currency_symbol() );
                echo '<tr>';
                echo '<th scope="row">' . __( 'Select Currency', 'inventory-manager-pro' ) . '</th>';
                echo '<td>';
                echo '<select name="inventory_manager_currency">';
                $symbols = array( '$' => '$', '€' => '€', '£' => '£' );
                foreach ( $symbols as $symbol => $label ) {
                        echo '<option value="' . esc_attr( $symbol ) . '" ' . selected( $currency, $symbol, false ) . '>' . esc_html( $label ) . '</option>';
                }
                echo '</select>';
                echo '</td>';
                echo '</tr>';
                echo '</table>';

                // Adjustment types
		echo '<h3>' . __( 'Options for Adjustments', 'inventory-manager-pro' ) . '</h3>';
		echo '<table class="form-table">';

		echo '<tr>';
		echo '<th scope="row">' . __( 'Adjustment Types', 'inventory-manager-pro' ) . '</th>';
		echo '<td>';

		$default_types = array(
			'damages'       => array(
				'label'       => __( 'Damages', 'inventory-manager-pro' ),
				'calculation' => 'deduct',
			),
			'received_more' => array(
				'label'       => __( 'Received MORE', 'inventory-manager-pro' ),
				'calculation' => 'add',
			),
			'received_less' => array(
				'label'       => __( 'Received LESS', 'inventory-manager-pro' ),
				'calculation' => 'deduct',
			),
			'free_samples'  => array(
				'label'       => __( 'Free Samples', 'inventory-manager-pro' ),
				'calculation' => 'deduct',
			),
		);

		// Merge with saved types
		$types = array_merge( $default_types, $adjustment_types );

		// Display types
		foreach ( $types as $type_key => $type ) {
			echo '<div class="adjustment-type-row">';
			echo '<input type="text" name="inventory_manager_adjustment_types[' . esc_attr( $type_key ) . '][label]" value="' . esc_attr( $type['label'] ) . '" class="regular-text">';
			echo '<select name="inventory_manager_adjustment_types[' . esc_attr( $type_key ) . '][calculation]">';
			echo '<option value="add" ' . selected( $type['calculation'], 'add', false ) . '>' . __( 'ADD', 'inventory-manager-pro' ) . '</option>';
			echo '<option value="deduct" ' . selected( $type['calculation'], 'deduct', false ) . '>' . __( 'DEDUCT', 'inventory-manager-pro' ) . '</option>';
			echo '</select>';
			echo '</div>';
		}

		echo '<p class="description">' . __( 'If ADD is selected, the quantity entered will be added to stock. If DEDUCT is selected, the quantity will be subtracted from stock.', 'inventory-manager-pro' ) . '</p>';

		echo '</td>';
		echo '</tr>';

		echo '</table>';

		// Additional note
		echo '<h3>' . __( 'Additional Notes', 'inventory-manager-pro' ) . '</h3>';
		echo '<table class="form-table">';

		echo '<tr>';
		echo '<th scope="row">' . __( 'Date Format', 'inventory-manager-pro' ) . '</th>';
		echo '<td>';
		echo '<p>' . __( 'Dates throughout the Plugin will be referred to in format DD/MM/YYYY', 'inventory-manager-pro' ) . '</p>';
		echo '</td>';
		echo '</tr>';

		echo '</table>';
	}
}
