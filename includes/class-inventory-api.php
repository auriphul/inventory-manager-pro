<?php
/**
 * The class responsible for defining API endpoints.
 *
 * @since      1.0.0
 * @package    Inventory_Manager_Pro
 */

class Inventory_API {
	private $plugin;
	private $db;

	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->db     = new Inventory_Database();
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		// Batches endpoints
		register_rest_route(
			'inventory-manager/v1',
			'/batches',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_batches' ),
				'permission_callback' => array( $this, 'check_api_permissions' ),
			)
		);

		register_rest_route(
			'inventory-manager/v1',
			'/batch/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_batch' ),
				'permission_callback' => array( $this, 'check_api_permissions' ),
			)
		);

                register_rest_route(
                        'inventory-manager/v1',
                        '/batch',
                        array(
                                'methods'             => 'POST',
                                'callback'            => array( $this, 'create_batch' ),
                                'permission_callback' => array( $this, 'check_api_permissions' ),
                        )
                );

                register_rest_route(
                        'inventory-manager/v1',
                        '/batch/(?P<id>\d+)',
                        array(
                                'methods'             => 'DELETE',
                                'callback'            => array( $this, 'delete_batch' ),
                                'permission_callback' => array( $this, 'check_api_permissions' ),
                        )
                );

                register_rest_route(
                        'inventory-manager/v1',
                        '/movement/(?P<id>\d+)',
                        array(
                                'methods'             => 'DELETE',
                                'callback'            => array( $this, 'delete_movement' ),
                                'permission_callback' => array( $this, 'check_api_permissions' ),
                        )
                );

		// Adjustment endpoint
		register_rest_route(
			'inventory-manager/v1',
			'/adjustment',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'add_adjustment' ),
				'permission_callback' => array( $this, 'check_api_permissions' ),
			)
		);

		// Detailed logs endpoint
		register_rest_route(
			'inventory-manager/v1',
			'/detailed-logs',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_detailed_logs' ),
				'permission_callback' => array( $this, 'check_api_permissions' ),
			)
		);

		// Export endpoints
		register_rest_route(
			'inventory-manager/v1',
			'/export',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'export_data' ),
				'permission_callback' => array( $this, 'check_api_permissions' ),
			)
		);

		// Helper endpoints
		register_rest_route(
			'inventory-manager/v1',
			'/suppliers',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_suppliers' ),
				'permission_callback' => array( $this, 'check_api_permissions' ),
			)
		);

		register_rest_route(
			'inventory-manager/v1',
			'/transit-times',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_transit_times' ),
				'permission_callback' => array( $this, 'check_api_permissions' ),
			)
		);

		register_rest_route(
			'inventory-manager/v1',
			'/adjustment-types',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_adjustment_types' ),
				'permission_callback' => array( $this, 'check_api_permissions' ),
			)
		);

		register_rest_route(
			'inventory-manager/v1',
			'/skus',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_skus' ),
				'permission_callback' => array( $this, 'check_api_permissions' ),
			)
		);

		register_rest_route(
			'inventory-manager/v1',
			'/product-info',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_product_info' ),
				'permission_callback' => array( $this, 'check_api_permissions' ),
			)
		);
	}

	/**
	 * Check permissions for API requests.
	 *
	 * @return bool True if the user has the required permissions, false otherwise.
	 */
	public function check_api_permissions() {
		return current_user_can( 'manage_inventory' ) || current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Get batches.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response object.
	 */
	public function get_batches( $request ) {
		$params = $request->get_params();

		// Build query args
		$args = array(
			'sku'            => isset( $params['sku'] ) ? sanitize_text_field( $params['sku'] ) : '',
			'product_id'     => isset( $params['product_id'] ) ? intval( $params['product_id'] ) : 0,
			'supplier_id'    => isset( $params['supplier_id'] ) ? intval( $params['supplier_id'] ) : 0,
			'expiry_filters' => isset( $params['expiry_filters'] ) ? (array) $params['expiry_filters'] : array(),
			'search'         => isset( $params['search'] ) ? sanitize_text_field( $params['search'] ) : '',
			'orderby'        => isset( $params['orderby'] ) ? sanitize_text_field( $params['orderby'] ) : 'sku',
			'order'          => isset( $params['order'] ) ? sanitize_text_field( $params['order'] ) : 'ASC',
			'per_page'       => isset( $params['per_page'] ) ? intval( $params['per_page'] ) : 20,
			'page'           => isset( $params['page'] ) ? intval( $params['page'] ) : 1,
		);

		// Calculate offset
		$args['offset'] = ( $args['page'] - 1 ) * $args['per_page'];

		// Get batches
		$batches = $this->db->get_batches( $args );

		// Get total count for pagination
		$total_batches = $this->db->count_batches(
			array(
				'sku'            => $args['sku'],
				'product_id'     => $args['product_id'],
				'supplier_id'    => $args['supplier_id'],
				'expiry_filters' => $args['expiry_filters'],
				'search'         => $args['search'],
			)
		);

		// Calculate pagination
		$total_pages = $args['per_page'] > 0 ? ceil( $total_batches / $args['per_page'] ) : 1;

		$response = array(
			'batches'    => $batches,
			'pagination' => array(
				'current_page'  => $args['page'],
				'per_page'      => $args['per_page'],
				'total_batches' => $total_batches,
				'total_pages'   => $total_pages,
			),
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Get single batch.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error The response object or error.
	 */
	public function get_batch( $request ) {
		$batch_id = $request['id'];

		$batch = $this->db->get_batch( $batch_id );

		if ( ! $batch ) {
			return new WP_Error( 'batch_not_found', __( 'Batch not found', 'inventory-manager-pro' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( $batch );
	}

	/**
	 * Create a new batch.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error The response object or error.
	 */
	public function create_batch( $request ) {
		$params = $request->get_params();
		// echo '<pre>';print_r($params);exit;
		// Validate required fields
		$required_fields = array( 'sku', 'batch_number', 'stock_qty', 'reference' );
		
		foreach ( $required_fields as $field ) {
			if ( empty( $params[ $field ] ) ) {
				return new WP_Error( 'missing_required_field', sprintf( __( 'Missing required field: %s', 'inventory-manager-pro' ), $field ), array( 'status' => 400 ) );
			}
		}
		
		// Create batch
		$result = $this->db->create_batch( $params );
		
		// echo '<pre>';print_r($result);exit;
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Get created batch
		$batch = $this->db->get_batch( $result );

		return rest_ensure_response(
			array(
				'success'  => true,
				'batch_id' => $result,
				'batch'    => $batch,
			)
		);
	}
	public function get_product_info( $request ){
		global $wpdb;
	
		// Get and sanitize SKU from the request
		$sku = sanitize_text_field( $request->get_param( 'sku' ) );
	
		if ( empty( $sku ) ) {
			return new WP_Error( 'invalid_sku', 'SKU is required.', array( 'status' => 400 ) );
		}
	
		// Get product by SKU
		$product_id = wc_get_product_id_by_sku( $sku );
		if ( ! $product_id ) {
			return new WP_Error( 'product_not_found', 'Product not found for the given SKU.', array( 'status' => 404 ) );
		}
	
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return new WP_Error( 'invalid_product', 'Unable to load product object.', array( 'status' => 500 ) );
		}
	
		// Initialize response
		$response = array(
			'product_name' => $product->get_name(),
		);
	
		// Fetch batches (if any) for this product and SKU
		$table_batches = $wpdb->prefix . 'inventory_batches';
		$batches = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT batch_number, stock_qty, expiry_date, location 
				 FROM {$table_batches} 
				 WHERE product_id = %d AND sku = %s",
				$product_id,
				$sku
			)
		);
	
		if ( ! empty( $batches ) ) {
			$response['batches'] = array_map( function( $batch ) {
				return array(
					'batch_number'     => $batch->batch_number,
					'stock_qty'        => $batch->stock_qty,
                                       'expiry_formatted' => $batch->expiry_date ? date_i18n( INVENTORY_MANAGER_DATE_FORMAT, strtotime( $batch->expiry_date ) ) : null,
					'location'         => $batch->location,
				);
			}, $batches );
		}
	
		return rest_ensure_response( $response );

	}

	/**
	 * Add adjustment to batch.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error The response object or error.
	 */
	public function add_adjustment( $request ) {
		$params = $request->get_params();

		// Validate required fields
		$required_fields = array( 'batch_id', 'adjustment_type', 'adjustment_qty', 'adjustment_reference' );

		foreach ( $required_fields as $field ) {
			if ( ! isset( $params[ $field ] ) || $params[ $field ] === '' ) {
				return new WP_Error( 'missing_required_field', sprintf( __( 'Missing required field: %s', 'inventory-manager-pro' ), $field ), array( 'status' => 400 ) );
			}
		}

		$batch_id             = intval( $params['batch_id'] );
		$adjustment_type      = sanitize_text_field( $params['adjustment_type'] );
		$adjustment_qty       = floatval( $params['adjustment_qty'] );
		$adjustment_reference = sanitize_text_field( $params['adjustment_reference'] );

		// Get adjustment type label
                // Fetch adjustment types with defaults in case none are saved
                $default_types     = array(
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

                $adjustment_types = get_option( 'inventory_manager_adjustment_types', $default_types );
                $adjustment_label  = '';
                $calculation       = 'add';

                if ( isset( $adjustment_types[ $adjustment_type ] ) ) {
                        $adjustment_label = $adjustment_types[ $adjustment_type ]['label'];
                        if ( isset( $adjustment_types[ $adjustment_type ]['calculation'] ) ) {
                                $calculation = $adjustment_types[ $adjustment_type ]['calculation'];
                        }
                }

                // Adjust quantity based on calculation type
                if ( 'deduct' === $calculation ) {
                        $adjustment_qty = -abs( $adjustment_qty );
                } else {
                        $adjustment_qty = abs( $adjustment_qty );
                }

		// Create reference with type label
		$reference = $adjustment_label . ': ' . $adjustment_reference;

		// Add adjustment
		$result = $this->db->update_batch_quantity( $batch_id, $adjustment_qty, 'adjustment', $reference );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Get updated batch
		$batch = $this->db->get_batch( $batch_id );

		return rest_ensure_response(
			array(
				'success' => true,
				'batch'   => $batch,
			)
		);
	}

	/**
	 * Get detailed logs.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response object.
	 */
	public function get_detailed_logs( $request ) {
		$params = $request->get_params();

                $args = array(
                        'period' => isset( $params['period'] ) ? sanitize_text_field( $params['period'] ) : '',
                        'search' => isset( $params['search'] ) ? sanitize_text_field( $params['search'] ) : '',
                        'order'  => isset( $params['order'] ) ? sanitize_text_field( $params['order'] ) : 'ASC',
                );

		$products = $this->db->get_detailed_logs( $args );

		return rest_ensure_response(
			array(
				'products' => $products,
			)
		);
	}

	/**
	 * Export data as CSV or Excel.
	 *
	 * @param WP_REST_Request $request The request object.
	 */
	public function export_data( $request ) {
		$params = $request->get_params();

		$format = isset( $params['format'] ) ? sanitize_text_field( $params['format'] ) : 'csv';
		$type   = isset( $params['type'] ) ? sanitize_text_field( $params['type'] ) : 'overview';

		// Build query args based on type
		if ( $type === 'overview' ) {
			$args = array(
				'sku'            => isset( $params['sku'] ) ? sanitize_text_field( $params['sku'] ) : '',
				'search'         => isset( $params['search'] ) ? sanitize_text_field( $params['search'] ) : '',
				'expiry_filters' => isset( $params['expiry_filters'] ) ? (array) $params['expiry_filters'] : array(),
				'per_page'       => -1, // Get all records
			);

			// Get batches
			$batches = $this->db->get_batches( $args );

			// Define columns to export
			$columns = array(
				'sku'          => __( 'SKU', 'inventory-manager-pro' ),
				'product_name' => __( 'PRODUCT NAME', 'inventory-manager-pro' ),
				'batch_number' => __( 'BATCH', 'inventory-manager-pro' ),
				'stock_qty'    => __( 'STOCK QTY', 'inventory-manager-pro' ),
			);

			// Add optional columns based on visible_columns parameter
			$visible_columns = isset( $params['visible_columns'] ) ? (array) $params['visible_columns'] : array();

			$optional_columns = array(
				'supplier_name'         => __( 'SUPPLIER', 'inventory-manager-pro' ),
				'expiry_formatted'      => __( 'EXPIRY', 'inventory-manager-pro' ),
				'origin'                => __( 'ORIGIN', 'inventory-manager-pro' ),
				'location'              => __( 'LOCATION', 'inventory-manager-pro' ),
				'stock_cost_formatted'  => __( 'STOCK COST', 'inventory-manager-pro' ),
				'landed_cost_formatted' => __( 'LANDED COST', 'inventory-manager-pro' ),
			);

			foreach ( $optional_columns as $column_key => $column_label ) {
				if ( in_array( str_replace( '_formatted', '', $column_key ), $visible_columns ) ) {
					$columns[ $column_key ] = $column_label;
				}
			}

			// Prepare data for export
			$data = array();

			foreach ( $batches as $batch ) {
				$row = array();

                                foreach ( array_keys( $columns ) as $column_key ) {
                                        if ( 'stock_cost_formatted' === $column_key ) {
                                                $row[ $column_key ] = isset( $batch->stock_cost ) ? $batch->stock_cost : '';
                                        } elseif ( 'landed_cost_formatted' === $column_key ) {
                                                $row[ $column_key ] = isset( $batch->landed_cost ) ? $batch->landed_cost : '';
                                        } else {
                                                $row[ $column_key ] = isset( $batch->$column_key ) ? wp_strip_all_tags( $batch->$column_key ) : '';
                                        }
                                }

				$data[] = $row;
			}

			// Export data
			$this->export_file( 'inventory_overview', $format, $columns, $data );
		} elseif ( $type === 'detailed-logs' ) {
			$args = array(
				'period' => isset( $params['period'] ) ? sanitize_text_field( $params['period'] ) : '',
				'search' => isset( $params['search'] ) ? sanitize_text_field( $params['search'] ) : '',
			);

			// Get detailed logs
			$products = $this->db->get_detailed_logs( $args );

			// Define columns
			$columns = array(
				'sku'          => __( 'SKU', 'inventory-manager-pro' ),
				'product_name' => __( 'PRODUCT NAME', 'inventory-manager-pro' ),
				'supplier'     => __( 'SUPPLIER', 'inventory-manager-pro' ),
				'batch'        => __( 'BATCH', 'inventory-manager-pro' ),
				'expiry'       => __( 'EXPIRY', 'inventory-manager-pro' ),
				'origin'       => __( 'ORIGIN', 'inventory-manager-pro' ),
				'location'     => __( 'LOCATION', 'inventory-manager-pro' ),
				'stock_qty'    => __( 'STOCK QTY', 'inventory-manager-pro' ),
				'date_time'    => __( 'DATE & TIME', 'inventory-manager-pro' ),
				'type'         => __( 'TYPE', 'inventory-manager-pro' ),
				'reference'    => __( 'REFERENCE', 'inventory-manager-pro' ),
				'stock_in'     => __( 'STOCK IN', 'inventory-manager-pro' ),
				'stock_out'    => __( 'STOCK OUT', 'inventory-manager-pro' ),
			);

			// Prepare data for export
			$data = array();

			foreach ( $products as $product ) {
				foreach ( $product['batches'] as $batch ) {
					if ( ! empty( $batch->movements ) ) {
						foreach ( $batch->movements as $movement ) {
							$row = array(
								'sku'          => $product['sku'],
								'product_name' => $product['product_name'],
								'supplier'     => $batch->supplier_name,
								'batch'        => $batch->batch_number,
								'expiry'       => $batch->expiry_formatted,
								'origin'       => $batch->origin,
								'location'     => $batch->location,
								'stock_qty'    => $batch->stock_qty,
								'date_time'    => $movement->date_time,
								'type'         => $movement->movement_type,
								'reference'    => $movement->reference,
								'stock_in'     => $movement->stock_in,
								'stock_out'    => $movement->stock_out,
							);

							$data[] = $row;
						}
					} else {
						// Include batches even if they have no movements
						$row = array(
							'sku'          => $product['sku'],
							'product_name' => $product['product_name'],
							'supplier'     => $batch->supplier_name,
							'batch'        => $batch->batch_number,
							'expiry'       => $batch->expiry_formatted,
							'origin'       => $batch->origin,
							'location'     => $batch->location,
							'stock_qty'    => $batch->stock_qty,
							'date_time'    => '',
							'type'         => '',
							'reference'    => '',
							'stock_in'     => '',
							'stock_out'    => '',
						);

						$data[] = $row;
					}
				}
			}

			// Export data
			$this->export_file( 'inventory_detailed_logs', $format, $columns, $data );
		}

		// This should not be reached as the export_file function should exit
		return rest_ensure_response(
			array(
				'success' => false,
				'message' => __( 'Export failed', 'inventory-manager-pro' ),
			)
		);
	}

	/**
	 * Export data as a file (CSV or Excel).
	 *
	 * @param string $filename Base filename without extension.
	 * @param string $format Format (csv or xls).
	 * @param array  $columns Column headers.
	 * @param array  $data Data to export.
	 */
        private function export_file( $filename, $format, $columns, $data ) {
		$date     = date( 'Y-m-d' );
		$filename = $filename . '_' . $date;

		if ( $format === 'csv' ) {
			// CSV export
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=' . $filename . '.csv' );

			$output = fopen( 'php://output', 'w' );

			// Add UTF-8 BOM
			fputs( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

			// Add headers
			fputcsv( $output, array_values( $columns ) );

			// Add data
			foreach ( $data as $row ) {
				fputcsv( $output, $row );
        }

        /**
         * Delete a batch.
         *
         * @param WP_REST_Request $request The request object.
         * @return WP_REST_Response|WP_Error The response object or error.
         */
        public function delete_batch( $request ) {
                $batch_id = intval( $request['id'] );

                $result = $this->db->delete_batch( $batch_id );

                if ( is_wp_error( $result ) ) {
                        return $result;
                }

                return rest_ensure_response( array( 'success' => true ) );
        }

        /**
         * Delete a stock movement entry.
         *
         * @param WP_REST_Request $request The request object.
         * @return WP_REST_Response|WP_Error The response object or error.
         */
        public function delete_movement( $request ) {
                $movement_id = intval( $request['id'] );

                $result = $this->db->delete_movement( $movement_id );

                if ( is_wp_error( $result ) ) {
                        return $result;
                }

                return rest_ensure_response( array( 'success' => true ) );
        }

			fclose( $output );
			exit;
		} elseif ( $format === 'xls' ) {
			// Excel export
			// Require PHPExcel library
			// For this example, we'll use a basic approach without PHPExcel

			header( 'Content-Type: application/vnd.ms-excel' );
			header( 'Content-Disposition: attachment; filename=' . $filename . '.xls' );

			echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
			echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">';
			echo "\n";
			echo '<Worksheet ss:Name="Sheet1">';
			echo "\n";
			echo '<Table>';
			echo "\n";

			// Add headers
			echo '<Row>';
			foreach ( $columns as $header ) {
				echo '<Cell><Data ss:Type="String">' . htmlspecialchars( $header ) . '</Data></Cell>';
			}
			echo '</Row>';
			echo "\n";

			// Add data
			foreach ( $data as $row ) {
				echo '<Row>';
				foreach ( $row as $cell ) {
					// Determine cell type
					if ( is_numeric( $cell ) ) {
						echo '<Cell><Data ss:Type="Number">' . $cell . '</Data></Cell>';
					} else {
						echo '<Cell><Data ss:Type="String">' . htmlspecialchars( $cell ) . '</Data></Cell>';
					}
				}
				echo '</Row>';
				echo "\n";
			}

			echo '</Table>';
			echo "\n";
			echo '</Worksheet>';
			echo "\n";
			echo '</Workbook>';

			exit;
		}
	}

	/**
	 * Get suppliers.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response object.
	 */
	public function get_suppliers( $request ) {
		$suppliers = $this->db->get_suppliers();

		return rest_ensure_response(
			array(
				'suppliers' => $suppliers,
			)
		);
	}

	/**
	 * Get transit times.
	 *
	 * @return array Array of transit time options.
	 */
	public function get_transit_times() {
		$transit_times = array(
			array(
				'id'   => '3_days',
				'name' => __( '3 days', 'inventory-manager-pro' ),
			),
			array(
				'id'   => '1_week',
				'name' => __( '1 week', 'inventory-manager-pro' ),
			),
			array(
				'id'   => '2_weeks',
				'name' => __( '2 weeks', 'inventory-manager-pro' ),
			),
			array(
				'id'   => '20_days',
				'name' => __( '20 days', 'inventory-manager-pro' ),
			),
			array(
				'id'   => '1_month',
				'name' => __( '1 month', 'inventory-manager-pro' ),
			),
			array(
				'id'   => '40_days',
				'name' => __( '40 days', 'inventory-manager-pro' ),
			),
		);

		// Get custom transit times from settings
		$custom_times = get_option( 'inventory_manager_suppliers', array() );

		if ( ! empty( $custom_times['transit_times'] ) ) {
			$transit_times = array();

			foreach ( $custom_times['transit_times'] as $id => $name ) {
				$transit_times[] = array(
					'id'   => $id,
					'name' => $name,
				);
			}
		}

		return $transit_times;
	}

	/**
	 * Get adjustment types.
	 *
	 * @return array Array of adjustment type options.
	 */
	public function get_adjustment_types() {
		$adjustment_types = get_option( 'inventory_manager_adjustment_types', array() );
		$types            = array();

		if ( empty( $adjustment_types ) ) {
			// Default types
			$types = array(
				array(
					'id'          => 'damages',
					'name'        => __( 'Damages', 'inventory-manager-pro' ),
					'calculation' => 'deduct',
				),
				array(
					'id'          => 'received_more',
					'name'        => __( 'Received MORE', 'inventory-manager-pro' ),
					'calculation' => 'add',
				),
				array(
					'id'          => 'received_less',
					'name'        => __( 'Received LESS', 'inventory-manager-pro' ),
					'calculation' => 'deduct',
				),
				array(
					'id'          => 'free_samples',
					'name'        => __( 'Free Samples', 'inventory-manager-pro' ),
					'calculation' => 'deduct',
				),
			);
		} else {
			foreach ( $adjustment_types as $key => $type ) {
				$types[] = array(
					'id'          => $key,
					'name'        => $type['label'],
					'calculation' => $type['calculation'],
				);
			}
		}

		return $types;
	}

	/**
	 * Get expiry range for a date.
	 *
	 * @param string $expiry_date Expiry date in MySQL format.
	 * @return string Expiry range identifier.
	 */
	private function get_expiry_range( $expiry_date ) {
		if ( empty( $expiry_date ) ) {
			return 'no_expiry';
		}

		$today  = time();
		$expiry = strtotime( $expiry_date );

		if ( $expiry <= $today ) {
			return 'expired';
		}

		$diff_months = ( $expiry - $today ) / ( 30 * 24 * 60 * 60 ); // Approximate months

		if ( $diff_months > 6 ) {
			return '6plus';
		} elseif ( $diff_months > 3 ) {
			return '3-6';
		} elseif ( $diff_months > 1 ) {
			return '1-3';
		} else {
			return 'less1';
		}
	}

	/**
	 * Import batches from CSV or Excel file.
	 *
	 * @param array $file File array from $_FILES.
	 * @return array|WP_Error Import results or WP_Error on failure.
	 */
	public function import_batches( $file ) {
		if ( empty( $file ) || ! isset( $file['tmp_name'] ) || empty( $file['tmp_name'] ) ) {
			return new WP_Error( 'invalid_file', __( 'Invalid file', 'inventory-manager-pro' ) );
		}

		$file_type = wp_check_filetype( basename( $file['name'] ) );

		// Check if the file type is allowed
		if ( ! in_array( $file_type['ext'], array( 'csv', 'xls', 'xlsx' ) ) ) {
			return new WP_Error( 'invalid_file_type', __( 'Invalid file type. Only CSV and Excel files are allowed.', 'inventory-manager-pro' ) );
		}

		// Parse the file based on its type
		$batches = array();

		if ( $file_type['ext'] === 'csv' ) {
			// Parse CSV
			$batches = $this->parse_csv_file( $file['tmp_name'] );
		} else {
			// Parse Excel
			$batches = $this->parse_excel_file( $file['tmp_name'], $file_type['ext'] );
		}

		if ( is_wp_error( $batches ) ) {
			return $batches;
		}

		// Import batches
		$results = array(
			'success' => 0,
			'errors'  => array(),
			'total'   => count( $batches ),
		);

		foreach ( $batches as $batch_data ) {
			$result = $this->create_batch( $batch_data );

			if ( is_wp_error( $result ) ) {
				$results['errors'][] = sprintf(
					__( 'Error importing batch %1$s for SKU %2$s: %3$s', 'inventory-manager-pro' ),
					$batch_data['batch_number'],
					$batch_data['sku'],
					$result->get_error_message()
				);
			} else {
				++$results['success'];
			}
		}

		return $results;
	}

	/**
	 * Parse CSV file.
	 *
	 * @param string $file File path.
	 * @return array|WP_Error Array of batch data or WP_Error on failure.
	 */
	private function parse_csv_file( $file ) {
		$batches = array();

		// Open the file
		$handle = fopen( $file, 'r' );

		if ( ! $handle ) {
			return new WP_Error( 'file_error', __( 'Error opening file', 'inventory-manager-pro' ) );
		}

		// Get headers
		$headers = fgetcsv( $handle );

		if ( ! $headers ) {
			fclose( $handle );
			return new WP_Error( 'invalid_csv', __( 'Invalid CSV format', 'inventory-manager-pro' ) );
		}

		// Map headers to columns
		$column_map = array(
			'inventory_sku'            => false,
			'inventory_product_name'   => false,
			'inventory_batch'          => false,
			'inventory_stock_qty'      => false,
			'inventory_supplier'       => false,
			'inventory_reference'      => false,
			'inventory_expiry'         => false,
			'inventory_origin'         => false,
			'inventory_location'       => false,
			'inventory_unit_cost'      => false,
			'inventory_freight_margin' => false,
		);

		foreach ( $headers as $index => $header ) {
			if ( isset( $column_map[ $header ] ) ) {
				$column_map[ $header ] = $index;
			}
		}

		// Check required columns
		$required_columns = array( 'inventory_sku', 'inventory_batch', 'inventory_stock_qty' );

		foreach ( $required_columns as $column ) {
			if ( $column_map[ $column ] === false ) {
				fclose( $handle );
				return new WP_Error( 'missing_column', sprintf( __( 'Missing required column: %s', 'inventory-manager-pro' ), $column ) );
			}
		}

		// Parse rows
		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			$batch_data = array();

			// Map columns to batch data
			foreach ( $column_map as $column_key => $column_index ) {
				if ( $column_index !== false && isset( $row[ $column_index ] ) ) {
					// Remove "inventory_" prefix and map to batch data field
					$field_key = str_replace( 'inventory_', '', $column_key );

					// Special handling for some fields
					switch ( $field_key ) {
						case 'batch':
							$batch_data['batch_number'] = $row[ $column_index ];
							break;
						case 'freight_margin':
							$batch_data['freight_markup'] = $row[ $column_index ];
							break;
						default:
							$batch_data[ $field_key ] = $row[ $column_index ];
							break;
					}
				}
			}

			// Set default reference if not provided
			if ( ! isset( $batch_data['reference'] ) || empty( $batch_data['reference'] ) ) {
				$batch_data['reference'] = __( 'Imported', 'inventory-manager-pro' );
			}

			// Add to batches array
			if ( ! empty( $batch_data['sku'] ) && ! empty( $batch_data['batch_number'] ) && isset( $batch_data['stock_qty'] ) ) {
				$batches[] = $batch_data;
			}
		}

		fclose( $handle );

		return $batches;
	}

	/**
	 * Parse Excel file.
	 *
	 * @param string $file File path.
	 * @param string $ext File extension.
	 * @return array|WP_Error Array of batch data or WP_Error on failure.
	 */
	private function parse_excel_file( $file, $ext ) {
		// We need to include PHPExcel library or a similar library for Excel parsing
		// This example assumes a simple approach similar to CSV but with a hypothetical Excel reader

		// For this implementation, we'll return an error suggesting to use CSV instead
		return new WP_Error(
			'excel_not_supported',
			__( 'Excel file parsing is not supported in this implementation. Please convert to CSV and try again.', 'inventory-manager-pro' )
		);

		// In a full implementation, you would:
		// 1. Load the Excel file using a library like PHPExcel or PhpSpreadsheet
		// 2. Get the active sheet
		// 3. Read headers from the first row
		// 4. Map headers to columns similar to CSV parsing
		// 5. Loop through rows and create batch data
		// 6. Return the array of batch data
	}

	/**
	 * Check for expiring batches and send notifications.
	 */
	public function check_expiring_batches() {
		global $wpdb;

		// Get email notification settings
		$email_notifications = get_option( 'inventory_manager_email_notifications', array() );

		// Check each expiry range
		$ranges = array(
			'3_6'     => array(
				'condition' => 'expiry_date > DATE_ADD(CURDATE(), INTERVAL 3 MONTH) AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 6 MONTH)',
				'label'     => __( '3-6 months', 'inventory-manager-pro' ),
			),
			'1_3'     => array(
				'condition' => 'expiry_date > DATE_ADD(CURDATE(), INTERVAL 1 MONTH) AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 3 MONTH)',
				'label'     => __( '1-3 months', 'inventory-manager-pro' ),
			),
			'less_1'  => array(
				'condition' => 'expiry_date > CURDATE() AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 1 MONTH)',
				'label'     => __( '< 1 month', 'inventory-manager-pro' ),
			),
			'expired' => array(
				'condition' => 'expiry_date <= CURDATE()',
				'label'     => __( 'expired', 'inventory-manager-pro' ),
			),
		);

		foreach ( $ranges as $range_key => $range ) {
			// Check if notifications are enabled for this range
			if ( ! isset( $email_notifications[ $range_key ]['enabled'] ) || $email_notifications[ $range_key ]['enabled'] !== 'yes' ) {
				continue;
			}

			// Get recipients
			$recipients = isset( $email_notifications[ $range_key ]['email'] ) ? $email_notifications[ $range_key ]['email'] : get_option( 'admin_email' );

			// Get batches in this range
			$batches = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT b.*, p.post_title as product_name, 
                (SELECT name FROM {$wpdb->prefix}inventory_suppliers WHERE id = b.supplier_id) as supplier_name 
                FROM {$wpdb->prefix}inventory_batches b
                LEFT JOIN {$wpdb->posts} p ON b.product_id = p.ID
                WHERE b.expiry_date IS NOT NULL AND " . $range['condition'] . '
                ORDER BY b.expiry_date ASC',
					array()
				)
			);

			if ( empty( $batches ) ) {
				continue;
			}

			// Format data for email
			$email_content = sprintf(
				__( 'The following batches are %s:', 'inventory-manager-pro' ),
				$range['label']
			) . "\n\n";

			foreach ( $batches as $batch ) {
				$email_content .= sprintf(
					__( 'SKU: %1$s - Product: %2$s - Batch: %3$s - Expiry: %4$s - Stock: %5$s', 'inventory-manager-pro' ),
					$batch->sku,
					$batch->product_name,
					$batch->batch_number,
                                       date_i18n( INVENTORY_MANAGER_DATE_FORMAT, strtotime( $batch->expiry_date ) ),
					$batch->stock_qty
				) . "\n";
			}

			// Send email
			$subject = sprintf(
				__( '[%1$s] Batches %2$s', 'inventory-manager-pro' ),
				get_bloginfo( 'name' ),
				$range['label']
			);

			wp_mail( $recipients, $subject, $email_content );
		}
	}
}