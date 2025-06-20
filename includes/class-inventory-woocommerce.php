<?php
/**
 * Handle WooCommerce integration.
 *
 * @since      1.0.0
 * @package    Inventory_Manager_Pro
 */

class Inventory_Manager_WooCommerce {
       private $plugin;
       private $db;

       public function __construct( $plugin ) {
               $this->plugin = $plugin;
               $this->db     = new Inventory_Database();

               // Order processing
               add_action( 'woocommerce_checkout_order_processed', array( $this, 'checkout_order_stock_reduction' ), 10, 3 );
               add_action( 'woocommerce_order_status_processing', array( $this, 'process_order_stock_reduction' ), 10, 2 );
               add_action( 'woocommerce_order_status_completed', array( $this, 'process_order_stock_reduction' ), 10, 2 );
               add_action( 'woocommerce_order_status_cancelled', array( $this, 'process_order_stock_restoration' ), 10, 2 );
               add_action( 'woocommerce_order_status_refunded', array( $this, 'process_order_stock_restoration' ), 10, 2 );

		// Product display.
		add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'display_batch_info_single_product' ) );
		add_action( 'woocommerce_after_shop_loop_item_title', array( $this, 'display_batch_info_archive' ) );

		// Backend order interface
		add_action( 'woocommerce_admin_order_item_headers', array( $this, 'add_batch_headers_to_order_items' ) );
		add_action( 'woocommerce_admin_order_item_values', array( $this, 'add_batch_values_to_order_items' ), 10, 3 );

		// Ajax endpoints for batch selection
		add_action( 'wp_ajax_get_product_batches', array( $this, 'get_product_batches' ) );
               add_action( 'wp_ajax_select_order_item_batch', array( $this, 'select_order_item_batch' ) );
       }

       /**
        * Reduce stock when an order is placed during checkout.
        *
        * @param int        $order_id The order ID.
        * @param array      $posted_data Posted checkout data.
        * @param WC_Order   $order     Order object.
        */
       public function checkout_order_stock_reduction( $order_id, $posted_data, $order ) {
               $this->process_order_stock_reduction( $order_id, $order );
       }

	/**
	 * Reduce stock when order is processed.
	 */
       public function process_order_stock_reduction( $order_id, $order ) {
               global $wpdb;

		// Get settings
		$stock_deduction_method = get_option( 'inventory_manager_frontend_deduction_method', 'closest_expiry' );

		// Process each order item
		foreach ( $order->get_items() as $item_id => $item ) {
			$product_id = $item->get_product_id();
			$sku        = get_post_meta( $product_id, '_sku', true );
			$qty        = $item->get_quantity();

			// Skip if no SKU
			if ( empty( $sku ) ) {
				continue;
			}

			// Check if specific batch was selected for this item
			$selected_batch_id = wc_get_order_item_meta( $item_id, '_selected_batch_id', true );

			if ( $selected_batch_id ) {
				// Deduct from specific batch
				$this->deduct_stock_from_batch( $selected_batch_id, $qty, $order_id, $item_id );
			} else {
				// Deduct based on method
				$this->deduct_stock_by_method( $sku, $qty, $stock_deduction_method, $order_id, $item_id );
			}
		}
	}

	/**
	 * Restore stock when order is cancelled or refunded.
	 */
	public function process_order_stock_restoration( $order_id, $order ) {
		global $wpdb;

               $movements = $wpdb->get_results(
                       $wpdb->prepare(
                               "SELECT * FROM {$wpdb->prefix}inventory_stock_movements
                WHERE movement_type = 'wc_order_placed'
                AND reference = %s",
                               $order_id
                       )
               );

               foreach ( $movements as $movement ) {
                       // Create credit note (opposite of invoice)
                       $wpdb->insert(
                               $wpdb->prefix . 'inventory_stock_movements',
                               array(
                                       'batch_id'      => $movement->batch_id,
                                       'movement_type' => 'credit_note',
                                       'reference'     => 'return_' . $order_id,
                                       'quantity'      => abs( $movement->quantity ), // Make positive
                                       'date_created'  => current_time( 'mysql' ),
                                       'created_by'    => get_current_user_id(),
                               )
                       );

                       // Update batch stock quantity
                       $wpdb->query(
                               $wpdb->prepare(
                                       "UPDATE {$wpdb->prefix}inventory_batches
                    SET stock_qty = stock_qty + %f
                    WHERE id = %d",
                                       abs( $movement->quantity ),
                                       $movement->batch_id
                               )
                       );

                       // Update product stock
                       $batch = $wpdb->get_row(
                               $wpdb->prepare(
                                       "SELECT product_id FROM {$wpdb->prefix}inventory_batches WHERE id = %d",
                                       $movement->batch_id
                               )
                       );

                       if ( $batch ) {
                               $this->update_product_stock( $batch->product_id );
                       }
               }
	}

	/**
	 * Deduct stock from specific batch.
	 */
       private function deduct_stock_from_batch( $batch_id, $qty, $order_id, $item_id ) {
				global $wpdb;
				$exists = $wpdb->get_var( $wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}inventory_stock_movements 
					WHERE reference = %s AND batch_id = %d AND movement_type = %s",
					$order_id, $batch_id, 'wc_order_placed'
				) );
				if ( ! $exists ) {
					$this->db->update_batch_quantity(
							$batch_id,
							-1 * $qty,
							'wc_order_placed',
							$order_id
					);
				}

               if ( $item_id ) {
                       wc_update_order_item_meta( $item_id, '_selected_batch_id', $batch_id );
               }
       }

	/**
	 * Deduct stock based on method (closest expiry or FIFO).
	 */
       private function deduct_stock_by_method( $sku, $qty, $method, $order_id, $item_id ) {
               global $wpdb;

               $remaining_qty = $qty;
               $selected_batch_id = 0;

		// Get batches based on method
		if ( $method === 'closest_expiry' ) {
			// Get batches by expiry date (closest first)
			$batches = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}inventory_batches 
                WHERE sku = %s AND stock_qty > 0 
                ORDER BY expiry_date ASC, id ASC",
					$sku
				)
			);
		} else {
			// FIFO - get batches by creation date (oldest first)
			$batches = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}inventory_batches 
                WHERE sku = %s AND stock_qty > 0 
                ORDER BY date_created ASC, id ASC",
					$sku
				)
			);
		}

		// Deduct from batches until quantity is satisfied
               foreach ( $batches as $batch ) {
                       if ( $remaining_qty <= 0 ) {
                               break;
                       }

                       $deduct_qty = min( $remaining_qty, $batch->stock_qty );

                       if ( ! $selected_batch_id ) {
                               $selected_batch_id = $batch->id;
                       }

					   $exists = $wpdb->get_var( $wpdb->prepare(
						   "SELECT COUNT(*) FROM {$wpdb->prefix}inventory_stock_movements 
						   WHERE reference = %s AND batch_id = %d AND movement_type = %s",
						   $order_id, $batch->id, 'wc_order_placed'
					   ) );

					   if ( ! $exists ) {
							$this->db->update_batch_quantity(
									$batch->id,
									-1 * $deduct_qty,
									'wc_order_placed',
									$order_id
							);
						}

                       $remaining_qty -= $deduct_qty;
               }

               if ( $selected_batch_id ) {
                       wc_update_order_item_meta( $item_id, '_selected_batch_id', $selected_batch_id );
               }

		// Handle backorders if remaining quantity
		if ( $remaining_qty > 0 ) {
			// Get product ID
			$product_id = wc_get_product_id_by_sku( $sku );

			if ( $product_id ) {
				// Check if product allows backorders
				$product = wc_get_product( $product_id );

				if ( $product && $product->is_on_backorder( $remaining_qty ) ) {
					// Record backorder
					wc_add_order_item_meta( $item_id, '_backorder_qty', $remaining_qty );
				}
			}
		}

               // Update product stock handled in update_batch_quantity
       }

	/**
	 * Update WooCommerce product stock based on batch quantities.
	 */
	private function update_product_stock( $product_id ) {
		global $wpdb;

		// Get total stock for all batches of this product
		$total_stock = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(stock_qty) FROM {$wpdb->prefix}inventory_batches WHERE product_id = %d",
				$product_id
			)
		);

		if ( $total_stock === null ) {
			return;
		}

		// Update product stock
		update_post_meta( $product_id, '_stock', $total_stock );

		// Set stock status
		$stock_status = $total_stock > 0 ? 'instock' : 'outofstock';
		update_post_meta( $product_id, '_stock_status', $stock_status );

		// Clear product cache
		wc_delete_product_transients( $product_id );
	}

	/**
	 * Display batch info on single product page.
	 */
	public function display_batch_info_single_product() {
		do_shortcode( '[inventory_batch_single]' );
	}

	/**
	 * Display batch info on archive pages.
	 */
	public function display_batch_info_archive() {
		do_shortcode( '[inventory_batch_archive]' );
	}

	/**
	 * Add batch headers to admin order items.
	 */
	public function add_batch_headers_to_order_items() {
		// Check if show fields option is enabled
		$show_fields = get_option( 'inventory_manager_backend_fields', array() );

		if ( empty( $show_fields ) ) {
			return;
		}

		echo '<th class="batch-info">' . __( 'Batch Info', 'inventory-manager-pro' ) . '</th>';
	}

	/**
	 * Add batch values to admin order items.
	 */
	public function add_batch_values_to_order_items( $product, $item, $item_id ) {
		// Check if show fields option is enabled
		$show_fields = get_option( 'inventory_manager_backend_fields', array() );

		if ( empty( $show_fields ) ) {
			return;
		}

		if ( ! $product ) {
			echo '<td class="batch-info">&mdash;</td>';
			return;
		}

               $sku         = $product->get_sku();
               $product_id  = $product->get_id();

				// If still empty, try the parent product SKU
				if ( method_exists( $product, 'get_parent_id' ) ) {
						$parent_id = $product->get_parent_id();
						if ( $parent_id ) {
								$parent_sku = get_post_meta( $parent_id, '_sku', true );
								if ( ! empty( $parent_sku ) ) {
										$sku        = $parent_sku;
										$product_id = $parent_id;
								}
						}
				}

				if ( empty( $sku ) ) {
						echo '<td class="batch-info">&mdash;</td>';
						return;
				}

		// Get selected batch ID
		$selected_batch_id = wc_get_order_item_meta( $item_id, '_selected_batch_id', true );

               // Get batches for this SKU and product
               global $wpdb;
               $batches = $wpdb->get_results(
                       $wpdb->prepare(
                               "SELECT * FROM {$wpdb->prefix}inventory_batches
            WHERE sku = %s AND product_id = %d AND stock_qty > 0
            ORDER BY expiry_date ASC",
                               $sku,
                               $product_id
                       )
               );

		if ( empty( $batches ) ) {
			echo '<td class="batch-info">' . __( 'No batches available', 'inventory-manager-pro' ) . '</td>';
			return;
		}

		// Check if batch selection is enabled
		$select_batch = get_option( 'inventory_manager_backend_select_batch', 'no' );

		if ( $select_batch === 'yes' ) {
			// Batch selection dropdown
			echo '<td class="batch-info">';
			echo '<select class="batch-select" data-item-id="' . esc_attr( $item_id ) . '">';
			echo '<option value="">' . __( 'Select batch', 'inventory-manager-pro' ) . '</option>';

			foreach ( $batches as $batch ) {
				$selected = $selected_batch_id == $batch->id ? 'selected' : '';
				echo '<option value="' . esc_attr( $batch->id ) . '" ' . $selected . '>';
				echo esc_html( $batch->batch_number ) . ' (' . esc_html( $batch->stock_qty ) . ' ' . __( 'in stock', 'inventory-manager-pro' ) . ')';
				echo '</option>';
			}

			echo '</select>';
			echo '</td>';

			// Add JS for batch selection
			wc_enqueue_js(
				"
                jQuery('.batch-select').on('change', function() {
                    var batchId = jQuery(this).val();
                    var itemId = jQuery(this).data('item-id');
                    
                    jQuery.ajax({
                        url: woocommerce_admin.ajax_url,
                        data: {
                            action: 'select_order_item_batch',
                            batch_id: batchId,
                            item_id: itemId,
                            security: woocommerce_admin.nonce
                        },
                        type: 'POST',
                        success: function(response) {
                            if (response.success) {
                                alert('" . __( 'Batch selected successfully', 'inventory-manager-pro' ) . "');
                            } else {
                                alert('" . __( 'Error selecting batch', 'inventory-manager-pro' ) . "');
                            }
                        }
                    });
                });
            "
			);
		} else {
			// Show batch info
			$batch_info = array();

			if ( $selected_batch_id ) {
				// Get selected batch
				$batch = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT * FROM {$wpdb->prefix}inventory_batches WHERE id = %d",
						$selected_batch_id
					)
				);

				if ( $batch ) {
					// Get supplier name
					$supplier_name = '';
					if ( $batch->supplier_id ) {
						$supplier_name = $wpdb->get_var(
							$wpdb->prepare(
								"SELECT name FROM {$wpdb->prefix}inventory_suppliers WHERE id = %d",
								$batch->supplier_id
							)
						);
					}

					$batch_info = array(
						'supplier'  => $supplier_name,
						'batch'     => $batch->batch_number,
                                               'expiry'    => $batch->expiry_date ? date_i18n( INVENTORY_MANAGER_DATE_FORMAT, strtotime( $batch->expiry_date ) ) : '',
						'origin'    => $batch->origin,
						'location'  => $batch->location,
						'stock_qty' => $batch->stock_qty,
					);
				}
			} else {
				// Show closest expiry batch
				$batch = $batches[0];

				// Get supplier name
				$supplier_name = '';
				if ( $batch->supplier_id ) {
					$supplier_name = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT name FROM {$wpdb->prefix}inventory_suppliers WHERE id = %d",
							$batch->supplier_id
						)
					);
				}

				$batch_info = array(
					'supplier'  => $supplier_name,
					'batch'     => $batch->batch_number,
                                       'expiry'    => $batch->expiry_date ? date_i18n( INVENTORY_MANAGER_DATE_FORMAT, strtotime( $batch->expiry_date ) ) : '',
					'origin'    => $batch->origin,
					'location'  => $batch->location,
					'stock_qty' => $batch->stock_qty,
				);
			}

			echo '<td class="batch-info">';

			foreach ( $show_fields as $field_key => $field ) {
				if ( isset( $field['show'] ) && $field['show'] === 'yes' && isset( $batch_info[ $field_key ] ) && ! empty( $batch_info[ $field_key ] ) ) {
					$style = '';
					if ( ! empty( $field['color'] ) ) {
						$style = 'style="color:' . esc_attr( $field['color'] ) . ';"';
					}

					echo '<div class="batch-field ' . esc_attr( $field_key ) . '">';
					echo '<span class="label" ' . $style . '>' . esc_html( $field['label'] ) . ': </span>';
					echo '<span class="value" ' . $style . '>' . esc_html( $batch_info[ $field_key ] ) . '</span>';
					echo '</div>';
				}
			}

			echo '</td>';
		}
	}

	/**
	 * AJAX handler for getting product batches.
	 */
	public function get_product_batches() {
		check_ajax_referer( 'woocommerce-order', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to do this', 'inventory-manager-pro' ) ) );
		}

		$product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;

		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product ID', 'inventory-manager-pro' ) ) );
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'Product not found', 'inventory-manager-pro' ) ) );
		}

               $sku        = $product->get_sku();
               $lookup_id  = $product_id;


				if ( method_exists( $product, 'get_parent_id' ) ) {
						$parent_id = $product->get_parent_id();
						if ( $parent_id ) {
								$parent_sku = get_post_meta( $parent_id, '_sku', true );
								if ( ! empty( $parent_sku ) ) {
										$sku       = $parent_sku;
										$lookup_id = $parent_id;
								}
						}
				}

				if ( empty( $sku ) ) {
						wp_send_json_error( array( 'message' => __( 'Product has no SKU', 'inventory-manager-pro' ) ) );
				}

               // Get batches for this SKU and product
               global $wpdb;
               $batches = $wpdb->get_results(
                       $wpdb->prepare(
                               "SELECT * FROM {$wpdb->prefix}inventory_batches
            WHERE sku = %s AND product_id = %d AND stock_qty > 0
            ORDER BY expiry_date ASC",
                               $sku,
                               $lookup_id
                       )
               );

		if ( empty( $batches ) ) {
			wp_send_json_error( array( 'message' => __( 'No batches available for this product', 'inventory-manager-pro' ) ) );
		}

		$formatted_batches = array();

		foreach ( $batches as $batch ) {
			// Get supplier name
			$supplier_name = '';
			if ( $batch->supplier_id ) {
				$supplier_name = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT name FROM {$wpdb->prefix}inventory_suppliers WHERE id = %d",
						$batch->supplier_id
					)
				);
			}

			$formatted_batches[] = array(
				'id'           => $batch->id,
				'batch_number' => $batch->batch_number,
				'stock_qty'    => $batch->stock_qty,
                               'expiry'       => $batch->expiry_date ? date_i18n( INVENTORY_MANAGER_DATE_FORMAT, strtotime( $batch->expiry_date ) ) : '',
				'supplier'     => $supplier_name,
				'location'     => $batch->location,
			);
		}

		wp_send_json_success(
			array(
				'batches' => $formatted_batches,
			)
		);
	}

	/**
	 * AJAX handler for selecting batch for order item.
	 */
	public function select_order_item_batch() {
		check_ajax_referer( 'woocommerce-order', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to do this', 'inventory-manager-pro' ) ) );
		}

		$batch_id = isset( $_POST['batch_id'] ) ? intval( $_POST['batch_id'] ) : 0;
		$item_id  = isset( $_POST['item_id'] ) ? intval( $_POST['item_id'] ) : 0;

		if ( ! $item_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid item ID', 'inventory-manager-pro' ) ) );
		}

		// Update or delete meta
		if ( $batch_id ) {
			wc_update_order_item_meta( $item_id, '_selected_batch_id', $batch_id );
		} else {
			wc_delete_order_item_meta( $item_id, '_selected_batch_id' );
		}

		wp_send_json_success();
	}
}
