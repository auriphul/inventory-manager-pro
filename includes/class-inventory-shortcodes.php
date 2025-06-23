<?php
/**
 * The class responsible for defining all shortcodes.
 *
 * @since      1.0.0
 * @package    Inventory_Manager_Pro
 */

class Inventory_Shortcodes {
	private $plugin;

	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Register all shortcodes.
	 */
	public function register_shortcodes() {
		add_shortcode( 'inventory_dashboard', array( $this, 'render_dashboard' ) );
		add_shortcode( 'inventory_batch_archive', array( $this, 'render_batch_archive' ) );
		add_shortcode( 'inventory_batch_single', array( $this, 'render_batch_single' ) );
	}

	/**
	 * Render dashboard shortcode.
	 */
	public function render_dashboard( $atts ) {
		if ( ! $this->plugin->check_dashboard_access() ) {
			return '<div class="inventory-manager-access-denied">' . __( 'You do not have permission to access this page.', 'inventory-manager-pro' ) . '</div>';
		}

		// Get current tab.
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'overview';

		// Buffer output.
		ob_start();

		echo '<div class="inventory-manager">';

		// Include tabs navigation.
		include $this->plugin->template_path() . 'dashboard/tabs-nav.php';

		// Include tab template.
		switch ( $tab ) {
			case 'detailed-logs':
				include $this->plugin->template_path() . 'dashboard/detailed-logs.php';
				break;
			case 'add-manually':
				include $this->plugin->template_path() . 'dashboard/add-manually.php';
				break;
			case 'import':
				include $this->plugin->template_path() . 'dashboard/import.php';
				break;
			case 'settings':
				include $this->plugin->template_path() . 'dashboard/settings.php';
				break;
			default:
				include $this->plugin->template_path() . 'dashboard/overview.php';
				break;
		}

		echo '</div>';

		return ob_get_clean();
	}

	/**
	 * Render batch info on product archive pages.
	 */
        public function render_batch_archive( $atts ) {
                $atts = shortcode_atts(
                        array(
                                'sku'        => '',
                                'product_id' => 0,
                        ),
                        $atts,
                        'inventory_batch_archive'
                );

                $product_obj = null;

                if ( ! empty( $atts['product_id'] ) ) {
                        $product_obj = wc_get_product( absint( $atts['product_id'] ) );
                } elseif ( ! empty( $atts['sku'] ) ) {
                        $product_id  = wc_get_product_id_by_sku( $atts['sku'] );
                        if ( $product_id ) {
                                $product_obj = wc_get_product( $product_id );
                        }
                } else {
                        global $product;
                        $product_obj = $product;
                }

                if ( ! $product_obj ) {
                        return '';
                }

                $sku = $product_obj->get_sku();

		if ( empty( $sku ) ) {
			return '';
		}

		// Get batch info settings.
		$show_fields      = get_option( 'inventory_manager_frontend_fields', array() );
		$displayed_fields = array_filter(
			$show_fields,
			function ( $field ) {
				return isset( $field['display_archive'] ) && $field['display_archive'] === 'yes';
			}
		);

		if ( empty( $displayed_fields ) ) {
			return '';
		}

		// Get batch information.
		global $wpdb;

		// Get closest expiry batch.
		$batch = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}inventory_batches 
            WHERE sku = %s AND stock_qty > 0 
            ORDER BY expiry_date ASC 
            LIMIT 1",
				$sku
			)
		);

		if ( ! $batch ) {
			return '';
		}

		// Get supplier name.
		$supplier_name = '';
		if ( $batch->supplier_id ) {
			$supplier_name = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT name FROM {$wpdb->prefix}inventory_suppliers WHERE id = %d",
					$batch->supplier_id
				)
			);
		}

		// Prepare batch info.
		$batch_info = array(
			'supplier'  => $supplier_name,
			'batch'     => $batch->batch_number,
                       'expiry'    => $batch->expiry_date ? date_i18n( INVENTORY_MANAGER_DATE_FORMAT, strtotime( $batch->expiry_date ) ) : '',
			'origin'    => $batch->origin,
			'location'  => $batch->location,
			'stock_qty' => $batch->stock_qty,
		);

                // Render template.
                ob_start();
                wc_get_template(
                        'frontend/product-batch-archive.php',
                        array(
                                'batch_info'       => $batch_info,
                                'displayed_fields' => $displayed_fields,
                        ),
                        '',
                        $this->plugin->template_path()
                );

                return ob_get_clean();
        }

	/**
	 * Render batch info on single product page.
	 *
	 * @param array $atts Array of attributes.
	 * @return string
	 */
	public function render_batch_single( array $atts ) {
		global $product;

		if ( ! $product ) {
			return '';
		}

		$sku = $product->get_sku();

		if ( empty( $sku ) ) {
			return '';
		}

		// Get batch info settings.
		$show_fields      = get_option( 'inventory_manager_frontend_fields', array() );
		$displayed_fields = array_filter(
			$show_fields,
			function ( $field ) {
				return isset( $field['display_single'] ) && $field['display_single'] === 'yes';
			}
		);

		if ( empty( $displayed_fields ) ) {
			return '';
		}

		global $wpdb;

		// Get closest expiry batch.
		$batch = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}inventory_batches 
            WHERE sku = %s AND stock_qty > 0 
            ORDER BY expiry_date ASC 
            LIMIT 1",
				$sku
			)
		);

		if ( ! $batch ) {
			return '';
		}

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

		// Prepare batch info
		$batch_info = array(
			'supplier'  => $supplier_name,
			'batch'     => $batch->batch_number,
                       'expiry'    => $batch->expiry_date ? date_i18n( INVENTORY_MANAGER_DATE_FORMAT, strtotime( $batch->expiry_date ) ) : '',
			'origin'    => $batch->origin,
			'location'  => $batch->location,
			'stock_qty' => $batch->stock_qty,
		);

		$total_stock = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(stock_qty) FROM {$wpdb->prefix}inventory_batches WHERE sku = %s",
				$sku
			)
		);

		$batch_info['total_stock'] = $total_stock;

                ob_start();

                wc_get_template(
                        'frontend/product-batch-single.php',
                        array(
                                'batch_info'       => $batch_info,
                                'displayed_fields' => $displayed_fields,
                        ),
                        '',
                        $this->plugin->template_path()
                );

                $this->display_stock_notes( $product, $batch_info );

                return ob_get_clean();
	}

	/**
	 * Display stock or backorder notes.
	 */
	private function display_stock_notes( $product, $batch_info ) {
		$settings = get_option( 'inventory_manager_frontend_notes', array() );

		if ( $product->is_in_stock() ) {
			if ( isset( $settings['show_in_stock'] ) && $settings['show_in_stock'] === 'yes' ) {
				$note  = isset( $settings['in_stock_note'] ) ? $settings['in_stock_note'] : __( 'In stock', 'inventory-manager-pro' );
				$style = '';

				if ( ! empty( $settings['in_stock_color'] ) ) {
					$style = 'style="color:' . esc_attr( $settings['in_stock_color'] ) . ';"';
				}

				echo '<div class="stock-note in-stock" ' . $style . '>' . esc_html( $note ) . '</div>';
			}

			if ( isset( $settings['show_stock_qty'] ) && $settings['show_stock_qty'] === 'yes' ) {
				$qty   = isset( $batch_info['total_stock'] ) ? $batch_info['total_stock'] : $product->get_stock_quantity();
				$note  = isset( $settings['stock_qty_note'] ) ? str_replace( '{qty}', $qty, $settings['stock_qty_note'] ) : $qty . ' ' . __( 'units in stock', 'inventory-manager-pro' );
				$style = '';

				if ( ! empty( $settings['stock_qty_color'] ) ) {
					$style = 'style="color:' . esc_attr( $settings['stock_qty_color'] ) . ';"';
				}

				echo '<div class="stock-note stock-qty" ' . $style . '>' . esc_html( $note ) . '</div>';
			}
		} elseif ( $product->is_on_backorder( 'notify' ) ) {
			// Show backorder note
			if ( isset( $settings['show_backorder'] ) && $settings['show_backorder'] === 'yes' ) {
				$note  = isset( $settings['backorder_note'] ) ? $settings['backorder_note'] : __( 'On backorder', 'inventory-manager-pro' );
				$style = '';

				if ( ! empty( $settings['backorder_color'] ) ) {
					$style = 'style="color:' . esc_attr( $settings['backorder_color'] ) . ';"';
				}

				echo '<div class="stock-note backorder" ' . $style . '>' . esc_html( $note ) . '</div>';
			}

			// Show backorder popup
			if ( isset( $settings['show_backorder_popup'] ) && $settings['show_backorder_popup'] === 'yes' ) {
				$supplier = isset( $batch_info['supplier'] ) ? $batch_info['supplier'] : '';

				// Get transit time for supplier
				global $wpdb;
				$transit_time = '30'; // Default

				if ( $supplier ) {
					$supplier_transit = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT transit_time FROM {$wpdb->prefix}inventory_suppliers WHERE name = %s",
							$supplier
						)
					);

					if ( $supplier_transit ) {
						$transit_time = $supplier_transit;
					}
				}

				$popup_message = isset( $settings['backorder_popup'] ) ? $settings['backorder_popup'] : __( 'This item is on backorder and scheduled to arrive in {transit_time} days', 'inventory-manager-pro' );
				$popup_message = str_replace( '{transit_time}', $transit_time, $popup_message );

				echo '<div class="backorder-popup" style="display:none;">' . esc_html( $popup_message ) . '</div>';

				wc_enqueue_js(
					"
                    jQuery('.single_add_to_cart_button, .add_to_cart_button').hover(
                        function() {
                            jQuery('.backorder-popup').fadeIn(200);
                        },
                        function() {
                            jQuery('.backorder-popup').fadeOut(200);
                        }
                    );
                "
				);
			}
		}
	}
}
