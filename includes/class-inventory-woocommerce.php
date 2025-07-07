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

       /**
        * Collected checkout stock notice messages.
        *
        * @var array
        */
       private static $checkout_stock_messages = array();

       public function __construct( $plugin ) {
               $this->plugin = $plugin;
               $this->db     = new Inventory_Database();

               // Order processing
               add_action( 'woocommerce_checkout_order_processed', array( $this, 'checkout_order_stock_reduction' ), 10, 3 );
               add_action( 'woocommerce_order_status_processing', array( $this, 'process_order_stock_reduction' ), 10, 2 );
               add_action( 'woocommerce_order_status_completed', array( $this, 'process_order_stock_reduction' ), 10, 2 );
               add_action( 'woocommerce_order_status_cancelled', array( $this, 'process_order_stock_restoration' ), 10, 2 );
               add_action( 'woocommerce_order_status_refunded', array( $this, 'process_order_stock_restoration' ), 10, 2 );
               add_action( 'woocommerce_order_status_invoice', array( $this, 'admin_order_invoice_status' ), 10, 2 );
               add_action( 'woocommerce_order_status_credit-note', array( $this, 'admin_order_credit_note_status' ), 10, 2 );

		// Product display.
        //        add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'display_batch_info_single_product' ) );
        //        add_action( 'woocommerce_after_shop_loop_item_title', array( $this, 'display_batch_info_archive' ) );

               // Assign expiring products to special offers category
               add_action( 'init', array( $this, 'assign_special_offers_category' ) );

		// Backend order interface
		add_action( 'woocommerce_admin_order_item_headers', array( $this, 'add_batch_headers_to_order_items' ) );
		add_action( 'woocommerce_admin_order_item_values', array( $this, 'add_batch_values_to_order_items' ), 10, 3 );

               // Ajax endpoints for batch selection
               add_action( 'wp_ajax_get_product_batches', array( $this, 'get_product_batches' ) );
               add_action( 'wp_ajax_select_order_item_batch', array( $this, 'select_order_item_batch' ) );

               // Detect quantity changes on admin order update.
               add_action( 'woocommerce_before_save_order_items', array( $this, 'maybe_adjust_order_item_quantities' ), 10, 2 );

               // Show stock breakdown notices during checkout
               add_action( 'woocommerce_after_checkout_validation', array( $this, 'add_checkout_stock_notices' ), 10, 2 );
               add_action( 'woocommerce_check_cart_items', array( $this, 'add_checkout_stock_notices' ) );

               // Frontend stock badges
               add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'output_product_stock_badge' ) );
        //        add_action( 'woocommerce_after_cart_item_name', array( $this, 'output_cart_stock_badge' ), 10, 2 );
               add_action( 'woocommerce_review_order_after_cart_contents', array( $this, 'output_checkout_stock_badge' ) );
               add_filter( 'wc_add_to_cart_message_html', array( $this, 'add_batch_stock_cart_message' ), 10, 2 );
        //        add_action( 'init', [$this, 'register_custom_order_statuses'] );
        //        add_filter( 'wc_order_statuses', [$this,'add_custom_order_statuses'] );
        //        add_filter( 'bulk_actions-edit-shop_order', [$this, 'add_custom_bulk_actions'] );
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
			$product = $item->get_product();
                        $inv_reduction_per_item	=	$this->inv_reduction_per_item($product);
			$sku        = get_post_meta( $product_id, '_sku', true );
			$qty        = $item->get_quantity()	*	$inv_reduction_per_item;

			// Skip if no SKU
			if ( empty( $sku ) ) {
				continue;
			}

			// Check if specific batch was selected for this item
			$selected_batch_id = wc_get_order_item_meta( $item_id, '_selected_batch_id', true );

                       if ( $selected_batch_id ) {
                               $batch = $this->db->get_batch( $selected_batch_id );
                               if ( ! $batch ) {
                                       $order->add_order_note( sprintf( __( 'Batch ID %1$d not found for SKU %2$s.', 'inventory-manager-pro' ), $selected_batch_id, $sku ) );
                                       $this->deduct_stock_by_method( $sku, $qty, $stock_deduction_method, $order_id, $item_id );
                                       continue;
                               }

                               $available = floatval( $batch->stock_qty );

                               if ( $qty > $available ) {
                                       if ( $available > 0 ) {
                                               $this->deduct_stock_from_batch( $selected_batch_id, $available, $order_id, $item_id );
                                       }

                                       $remaining = $qty - $available;
                                       $this->deduct_stock_by_method( $sku, $remaining, 'closest_expiry', $order_id, $item_id );
                               } else {
                                       $this->deduct_stock_from_batch( $selected_batch_id, $qty, $order_id, $item_id );
                               }
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
                AND reference LIKE %s",
                               $wpdb->esc_like( $order_id ) . '%'
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
                               $ref = $order_id . ':' . $item_id;
                               $movement = $wpdb->get_row( $wpdb->prepare(
                                       "SELECT id, quantity FROM {$wpdb->prefix}inventory_stock_movements
                                        WHERE reference = %s AND batch_id = %d AND movement_type = %s",
                                       $ref, $batch_id, 'wc_order_placed'
                               ) );

                               if ( $movement ) {
                                       $difference = ( -1 * $qty ) - floatval( $movement->quantity );

                                       if ( 0 !== $difference ) {
                                               $wpdb->update(
                                                       $wpdb->prefix . 'inventory_stock_movements',
                                                       array( 'quantity' => -1 * $qty ),
                                                       array( 'id' => $movement->id )
                                               );

                                               $wpdb->query( $wpdb->prepare(
                                                       "UPDATE {$wpdb->prefix}inventory_batches
                                                        SET stock_qty = stock_qty + %f
                                                        WHERE id = %d",
                                                       $difference,
                                                       $batch_id
                                               ) );

                                               $batch = $wpdb->get_row( $wpdb->prepare(
                                                       "SELECT product_id FROM {$wpdb->prefix}inventory_batches WHERE id = %d",
                                                       $batch_id
                                               ) );
                                               if ( $batch ) {
                                                       $this->update_product_stock( $batch->product_id );
                                               }
                                       }
                               } else {
                                       $this->db->update_batch_quantity(
                                                       $batch_id,
                                                       -1 * $qty,
                                                       'wc_order_placed',
                                                       $ref
                                       );
                               }
               if ( $item_id ) {
                       wc_update_order_item_meta( $item_id, '_selected_batch_id', $batch_id );
               }

               $batch_info = $this->db->get_batch( $batch_id );
               if ( $batch_info ) {
                       $this->add_allocation_note( $order_id, sprintf( __( 'Allocated %1$s units from batch %2$s.', 'inventory-manager-pro' ), $qty, $batch_info->batch_number ) );
               }
       }

	/**
	 * Deduct stock based on method (closest expiry or FIFO).
	 */
       private function deduct_stock_by_method( $sku, $qty, $method, $order_id, $item_id ) {
               global $wpdb;

               $remaining_qty     = $qty;
               $selected_batch_id = 0;
               $allocations       = array();

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

                       $ref     = $order_id . ':' . $item_id;
                       $movement = $wpdb->get_row( $wpdb->prepare(
                                                   "SELECT id, quantity FROM {$wpdb->prefix}inventory_stock_movements
                                                   WHERE reference = %s AND batch_id = %d AND movement_type = %s",
                                                   $ref, $batch->id, 'wc_order_placed'
                                           ) );

                                           if ( $movement ) {
                                                        $difference = ( -1 * $deduct_qty ) - floatval( $movement->quantity );

                                                        if ( 0 !== $difference ) {
                                                                $wpdb->update(
                                                                        $wpdb->prefix . 'inventory_stock_movements',
                                                                        array( 'quantity' => -1 * $deduct_qty ),
                                                                        array( 'id' => $movement->id )
                                                                );

                                                                $wpdb->query( $wpdb->prepare(
                                                                        "UPDATE {$wpdb->prefix}inventory_batches
                                                                         SET stock_qty = stock_qty + %f
                                                                         WHERE id = %d",
                                                                        $difference,
                                                                        $batch->id
                                                                ) );

                                                                $batch_product = $wpdb->get_row( $wpdb->prepare(
                                                                        "SELECT product_id FROM {$wpdb->prefix}inventory_batches WHERE id = %d",
                                                                        $batch->id
                                                                ) );
                                                                if ( $batch_product ) {
                                                                        $this->update_product_stock( $batch_product->product_id );
                                                                }
                                                        }
                                           } else {
                                                        $this->db->update_batch_quantity(
                                                                        $batch->id,
                                                                        -1 * $deduct_qty,
                                                                        'wc_order_placed',
                                                                        $ref
                                                        );
                                           }

                       $remaining_qty -= $deduct_qty;

                       if ( isset( $allocations[ $batch->id ] ) ) {
                               $allocations[ $batch->id ] += $deduct_qty;
                       } else {
                               $allocations[ $batch->id ] = $deduct_qty;
                       }

                       $this->add_allocation_note( $order_id, sprintf( __( 'Allocated %1$s units from batch %2$s.', 'inventory-manager-pro' ), $deduct_qty, $batch->batch_number ) );
               }

               if ( $selected_batch_id ) {
                       wc_update_order_item_meta( $item_id, '_selected_batch_id', $selected_batch_id );
               }

               if ( ! empty( $allocations ) ) {
                       wc_update_order_item_meta( $item_id, '_batch_allocations', $allocations );
               }

		// Handle backorders if remaining quantity
                if ( $remaining_qty > 0 ) {
                        $this->add_allocation_note( $order_id, sprintf( __( 'Insufficient stock for SKU %1$s. Short by %2$s units.', 'inventory-manager-pro' ), $sku, $remaining_qty ) );
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
               if ( 'yes' !== get_option( 'inventory_manager_sync_stock', 'yes' ) ) {
                       return;
               }

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
        * Add an allocation note to the order.
        */
       private function add_allocation_note( $order_id, $message ) {
               $order = wc_get_order( $order_id );
               if ( $order ) {
                       $order->add_order_note( $message );
               }
       }

       /**
        * Calculate stock availability across batches for a product.
        *
        * @param int $product_id    Product ID.
        * @param float $requested   Requested quantity.
        * @return array             Breakdown of total, immediate and backorder quantities.
        */
       private function get_stock_breakdown( $product_id, $requested ) {
               global $wpdb;

               $sku = get_post_meta( $product_id, '_sku', true );

               if ( empty( $sku ) ) {
                       return array(
                               'total_stock'    => 0,
                               'immediate_qty'  => 0,
                               'backorder_qty'  => $requested,
                       );
               }

               $batches = $wpdb->get_results(
                       $wpdb->prepare(
                               "SELECT stock_qty FROM {$wpdb->prefix}inventory_batches WHERE sku = %s AND stock_qty > 0 ORDER BY expiry_date ASC",
                               $sku
                       )
               );

               $immediate = 0;
               $remaining = $requested;
               foreach ( $batches as $batch ) {
                       if ( $remaining <= 0 ) {
                               break;
                       }

                       $take = min( $remaining, $batch->stock_qty );
                       $immediate += $take;
                       $remaining -= $take;
               }

               $total_stock = $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(stock_qty),0) FROM {$wpdb->prefix}inventory_batches WHERE sku = %s", $sku ) );

               return array(
                       'total_stock'   => floatval( $total_stock ),
                       'immediate_qty' => $immediate,
                       'backorder_qty' => max( 0, $requested - $immediate ),
               );
       }

       /**
        * Add customer notices during checkout about stock allocation.
        */
       public function add_checkout_stock_notices() {
               if ( ! WC()->cart ) {
                       return;
               }

               self::$checkout_stock_messages = array();

               foreach ( WC()->cart->get_cart() as $cart_item ) {
                        $product_id = $cart_item['product_id'];
//                         $product = wc_get_product( $product_id );
                       $product    = $cart_item['data'];
                        $inv_reduction_per_item	=	$this->inv_reduction_per_item($product);
                       $qty        = $cart_item['quantity'] * $inv_reduction_per_item;

                       $info = $this->get_stock_breakdown( $product_id, $qty );

                       if ( $info['backorder_qty'] > 0 ) {
                               $message = sprintf(
                                       /* translators: 1: immediate qty 2: product name 3: backorder qty */
                                       __( '%1$d items of %2$s will be delivered immediately. %3$d items will be in backorder and delivered when stock arrives.', 'inventory-manager-pro' ),
                                       $info['immediate_qty'],
                                       $product->get_name(),
                                       $info['backorder_qty']
                               );

                               $message = apply_filters( 'inventory_manager_stock_notice_message', $message, $product_id, $info, $cart_item );

                               self::$checkout_stock_messages[] = $message;

                               do_action( 'inventory_manager_stock_notice_added', $message, $product_id, $info, $cart_item );

                               wc_add_notice( $message, 'notice' );
                       }
               }
       }

       /**
        * Render a stock/backorder badge for a product.
        *
        * @param int    $product_id Product ID.
        * @param float  $qty        Requested quantity.
        * @param string $name       Optional name override.
        */
       private function render_stock_badge( $product_id, $qty, $name = '' ) {
               $product = wc_get_product( $product_id );
               if ( ! $product ) {
                       return;
               }
               $inv_reduction_per_item	=	$this->inv_reduction_per_item($product);

               $info = $this->get_stock_breakdown( $product_id, $qty );

               if ( $info['backorder_qty'] <= 0 ) {
                       return;
               }

               $name = $name ? $name : $product->get_name();

               $message = sprintf(
                       '<div class="wc-block-components-product-badge wc-block-components-product-backorder-badge">%s: %d items available now, %d items on backorder</div>',
                       esc_html( $name ),
                       intval( $info['immediate_qty'] ),
                       intval( $info['backorder_qty'] )
               );

               echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
       }

       /**
        * Output badge on single product page.
        */
       public function output_product_stock_badge() {
               global $product;

               if ( ! $product ) {
                       return;
               }

               $qty = isset( $_REQUEST['quantity'] ) ? floatval( $_REQUEST['quantity'] ) : 1;
               $this->render_stock_badge( $product->get_id(), $qty );
       }

       /**
        * Output badge for cart items.
        *
        * @param string $cart_item_key Cart item key.
        * @param array  $cart_item     Cart item data.
        */
       public function output_cart_stock_badge( $cart_item_key, $cart_item ) {
               $product_id = $cart_item['product_id'];
               $qty        = $cart_item['quantity'];
               $product    = $cart_item['data'];

               $this->render_stock_badge( $product_id, $qty, $product->get_name() );
       }

       /**
        * Output badges during checkout.
        */
       public function output_checkout_stock_badge() {
               if ( ! WC()->cart ) {
                       return;
               }

               foreach ( WC()->cart->get_cart() as $cart_item ) {
                       $product_id = $cart_item['product_id'];
                       $qty        = $cart_item['quantity'];
                       $product    = $cart_item['data'];

                       $this->render_stock_badge( $product_id, $qty, $product->get_name() );
               }
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
                       if ( $selected_batch_id ) {
                               $batch = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}inventory_batches WHERE id = %d", $selected_batch_id ) );
                               if ( $batch ) {
                                       $batches = array( $batch );
                               }
                       }

                       if ( empty( $batches ) ) {
                               echo '<td class="batch-info">' . __( 'No batches available', 'inventory-manager-pro' ) . '</td>';
                               return;
                       }
               }

		// Check if batch selection is enabled
		$select_batch = get_option( 'inventory_manager_backend_select_batch', 'no' );

		if ( $select_batch === 'yes' ) {
			// Batch selection dropdown
			echo '<td class="batch-info">';
			echo '<select class="batch-select" name="_selected_batch_id" data-item-id="' . esc_attr( $item_id ) . '">';
			echo '<option value="">' . __( 'Select batch', 'inventory-manager-pro' ) . '</option>';

			foreach ( $batches as $batch ) {
				$selected = $selected_batch_id == $batch->id ? 'selected' : '';
				echo '<option value="' . esc_attr( $batch->id ) . '" ' . $selected . '>';
				echo esc_html( $batch->expiry_date ) . ' (' . esc_html( $batch->stock_qty ) . ' ' . __( 'in stock', 'inventory-manager-pro' ) . ')';
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
                            security: inventory_manager_admin.order_nonce
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

       /**
        * Adjust stock movements when order item quantities are edited.
        *
        * @param int   $order_id Order ID.
        * @param array $items    Posted items.
        */
       public function maybe_adjust_order_item_quantities( $order_id, $items ) {
               global $wpdb;

               $order = wc_get_order( $order_id );
               if ( ! $order ) {
                       return;
               }

               $deduction_method = get_option( 'inventory_manager_frontend_deduction_method', 'closest_expiry' );

               foreach ( $items as $item_id => $data ) {
                       if ( ! isset( $data['qty'] ) ) {
                               continue;
                       }

                       $new_qty = floatval( $data['qty'] );
                       $ref     = $order_id . ':' . $item_id;

                       $logged = $wpdb->get_var(
                               $wpdb->prepare(
                                       "SELECT SUM(quantity) FROM {$wpdb->prefix}inventory_stock_movements WHERE reference = %s AND movement_type = 'wc_order_placed'",
                                       $ref
                               )
                       );

                       if ( null === $logged ) {
                               continue;
                       }

                       $current_qty = abs( floatval( $logged ) );

                       if ( $new_qty == $current_qty ) {
                               continue;
                       }

                       $item       = $order->get_item( $item_id );
                       $product_id = $item ? $item->get_product_id() : 0;
                       $sku        = $product_id ? get_post_meta( $product_id, '_sku', true ) : '';

                       $difference = $new_qty - $current_qty;

                       if ( $difference > 0 ) {
                               // Deduct additional quantity.
                               $remaining   = $difference;
                               $batch_id    = wc_get_order_item_meta( $item_id, '_selected_batch_id', true );
                               $first_batch = 0;

                               if ( $batch_id ) {
                                       $this->db->update_batch_quantity( $batch_id, -1 * $remaining, 'wc_order_placed', $ref );
                                       $first_batch = $batch_id;
                               } elseif ( $sku ) {
                                       $batches = $wpdb->get_results(
                                               $wpdb->prepare(
                                                       $deduction_method === 'closest_expiry'
                                                               ? "SELECT * FROM {$wpdb->prefix}inventory_batches WHERE sku = %s AND stock_qty > 0 ORDER BY expiry_date ASC, id ASC"
                                                               : "SELECT * FROM {$wpdb->prefix}inventory_batches WHERE sku = %s AND stock_qty > 0 ORDER BY date_created ASC, id ASC",
                                                       $sku
                                               )
                                       );

                                       foreach ( $batches as $batch ) {
                                               if ( $remaining <= 0 ) {
                                                       break;
                                               }

                                               $deduct_qty = min( $remaining, $batch->stock_qty );
                                               $this->db->update_batch_quantity( $batch->id, -1 * $deduct_qty, 'wc_order_placed', $ref );

                                               if ( ! $first_batch ) {
                                                       $first_batch = $batch->id;
                                               }

                                               $remaining -= $deduct_qty;
                                       }
                               }

                               if ( $first_batch ) {
                                       wc_update_order_item_meta( $item_id, '_selected_batch_id', $first_batch );
                               }
                       } else {
                               // Quantity reduced - restore the difference.
                               $restore = abs( $difference );

                               $movements = $wpdb->get_results(
                                       $wpdb->prepare(
                                               "SELECT batch_id, ABS(quantity) AS qty FROM {$wpdb->prefix}inventory_stock_movements WHERE reference = %s AND movement_type = 'wc_order_placed' ORDER BY id DESC",
                                               $ref
                                       )
                               );

                               foreach ( $movements as $movement ) {
                                       if ( $restore <= 0 ) {
                                               break;
                                       }

                                       $qty = min( $restore, $movement->qty );
                                       $this->db->update_batch_quantity( $movement->batch_id, $qty, 'credit_note', 'order_edit_' . $ref );
                                       $restore -= $qty;
                               }

                               if ( $new_qty <= 0 ) {
                                       wc_delete_order_item_meta( $item_id, '_selected_batch_id' );
                               }
                       }
               }
       }

       /**
        * Deduct stock when an admin sets the order status to invoice.
        *
        * @param int      $order_id Order ID.
        * @param WC_Order $order    Order object.
        */
       public function admin_order_invoice_status( $order_id, $order ) {
        // echo '<pre>';print_r($order);exit;
               if ( $order && $order->get_created_via() === 'admin' ) {
                       $this->process_order_stock_reduction( $order_id, $order );
               }
       }

       /**
        * Restore stock when an admin sets the order status to credit note.
        *
        * @param int      $order_id Order ID.
        * @param WC_Order $order    Order object.
        */
       public function admin_order_credit_note_status( $order_id, $order ) {
               if ( $order && $order->get_created_via() === 'admin' ) {
                       $this->process_order_stock_restoration( $order_id, $order );
               }
       }

       /**
        * Assign products with batches expiring within 30 days to the Special Offers category.
        */
       public function assign_special_offers_category() {
               $category_slug = 'special-offers';
               $term          = get_term_by( 'slug', $category_slug, 'product_cat' );

               if ( ! $term ) {
                       $created = wp_insert_term( 'Special Offers', 'product_cat', array( 'slug' => $category_slug ) );
                       if ( is_wp_error( $created ) ) {
                               return;
                       }
                       $term = get_term( $created['term_id'], 'product_cat' );
               }

               $expiring = $this->db->get_expiring_products( 30 );

               if ( empty( $expiring ) ) {
                       return;
               }

               foreach ( $expiring as $product ) {
                       wp_set_object_terms( $product->product_id, (int) $term->term_id, 'product_cat', true );
               }
       }
       function register_custom_order_statuses() {
                register_post_status( 'wc-credit-note', array(
                'label'                     => _x( 'Credit Note', 'Order status', 'woocommerce' ),
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop( 'Credit Note (%s)', 'Credit Note (%s)', 'woocommerce' )
                ) );
        
                register_post_status( 'wc-invoice', array(
                'label'                     => _x( 'Invoice', 'Order status', 'woocommerce' ),
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop( 'Invoice (%s)', 'Invoice (%s)', 'woocommerce' )
                ) );
        }
        function add_custom_order_statuses( $order_statuses ) {
                $new_statuses = array();
                
                // Place custom statuses after processing
                foreach ( $order_statuses as $key => $status ) {
                        $new_statuses[ $key ] = $status;
                
                        if ( 'wc-processing' === $key ) {
                        $new_statuses['wc-credit-note'] = _x( 'Credit Note', 'Order status', 'woocommerce' );
                        $new_statuses['wc-invoice']     = _x( 'Invoice', 'Order status', 'woocommerce' );
                        }
                }
                
                return $new_statuses;
        }
       function add_custom_bulk_actions( $bulk_actions ) {
               $bulk_actions['mark_credit-note'] = 'Mark as Credit Note';
               $bulk_actions['mark_invoice']     = 'Mark as Invoice';
               return $bulk_actions;
       }
       function inv_reduction_per_item($product){
                if ( $product && $product->is_type( 'variation' ) ) {
                        $variation_id = $product->get_id();
                        $quantity	=	get_post_meta( $variation_id, 'wsvi_multiplier', true );
                }else{
                        $quantity	=	1;
                }
                return $quantity;
       }

       /**
        * Append batch stock availability info to add-to-cart messages.
        *
        * @param string $message  Original cart message HTML.
        * @param array  $products Array of product IDs and quantities added.
        * @return string Modified message with batch info appended.
        */
       public function add_batch_stock_cart_message( $message, $products ) {
                if ( ! WC()->cart ) {
                        return;
                }
                $details = array();

                foreach ( WC()->cart->get_cart() as $cart_item ) {
                        $product_id = $cart_item['product_id'];
//                         $product = wc_get_product( $product_id );
                        $product    = $cart_item['data'];
                        $inv_reduction_per_item	=	$this->inv_reduction_per_item($product);
                       	$qty        = $cart_item['quantity'] * $inv_reduction_per_item;

                        $info    = $this->get_stock_breakdown( $product_id, $qty, $product->get_name() );

                        if ( $info['backorder_qty'] <= 0 ) {
                                continue;
                        }


                        if ( ! $product ) {
                                continue;
                        }

                        $details[] = sprintf(
                                /* translators: 1: immediate qty 2: product name 3: backorder qty */
                                __( '%1$d items of %2$s available now, %3$d on backorder due to batch limits.', 'inventory-manager-pro' ),
                                $info['immediate_qty'],
                                $product->get_name(),
                                $info['backorder_qty']
                        );
                }

               if ( ! empty( $details ) ) {
                       $message .= '<br /><span class="inventory-batch-message" style="background-color:red;color:#fff;font-size: 24px;font-weight: 700;padding: 2px 15px;">' . implode( '<br />', array_map( 'esc_html', $details ) ) . '</span>';
               }

               return $message;
       }

       /**
        * Retrieve collected checkout stock messages.
        *
        * Allows themes to display notices in custom contexts like modals.
        *
        * @return array Stock notice messages.
        */
       public static function get_checkout_stock_messages() {
               return self::$checkout_stock_messages;
       }
}
