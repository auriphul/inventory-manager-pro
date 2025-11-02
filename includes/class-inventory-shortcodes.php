<?php
/**
 * The class responsible for defining all shortcodes.
 *
 * @since      1.0.0
 * @package    Inventory_Manager_Pro
 */

class Inventory_Shortcodes {
	private $plugin;
	private $iwc;

	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->iwc    = new Inventory_Manager_WooCommerce( $this->plugin );
	}

	/**
	 * Register all shortcodes.
	 */
	public function register_shortcodes() {
		add_shortcode( 'inventory_dashboard', array( $this, 'render_dashboard' ) );
		add_shortcode( 'inventory_batch_archive', array( $this, 'render_batch_archive' ) );
		add_shortcode( 'inventory_pro_batch_info', array( $this, 'render_batch_single' ) );
		add_shortcode( 'inventory_pro_stock_note', array( $this, 'render_stock_single_page' ) );
		add_shortcode( 'inventory_pro_backorder_note', array( $this, 'output_product_stock_badge' ) );
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
                        // return '';
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

                // Fetch up to 3 closest expiry batches.
                $batches = $wpdb->get_results(
                        $wpdb->prepare(
                                "SELECT * FROM {$wpdb->prefix}inventory_batches
            WHERE sku = %s AND stock_qty > 0
            ORDER BY expiry_date ASC
            LIMIT 3",
                                $sku
                        )
                );

                if ( empty( $batches ) ) {
                        // return '';
                }

                $batches_info = array();

                $brands      = wp_get_post_terms( $product_obj->get_id(), 'product_brand' );
                $brand_names = '';
                if ( ! is_wp_error( $brands ) && ! empty( $brands ) ) {
                        $brand_names = implode( ', ', wp_list_pluck( $brands, 'name' ) );
                }

                foreach ( $batches as $batch ) {
                        $batches_info[] = array(
                                'supplier'  => $brand_names,
                                'batch'     => $batch->batch_number,
                                'expiry'    => $batch->expiry_date ? date_i18n( INVENTORY_MANAGER_DATE_FORMAT, strtotime( $batch->expiry_date ) ) : '',
                                'origin'    => $batch->origin,
                                'location'  => $batch->location,
                                'stock_qty' => number_format( (float) $batch->stock_qty, 2 ),
                        );
                }

                // Render template.
                ob_start();
                wc_get_template(
                        'frontend/product-batch-archive.php',
                        array(
                                'batches_info'    => $batches_info,
                                'displayed_fields' => $displayed_fields,
                        ),
                        '',
                        $this->plugin->template_path()
                );
                $this->output_product_stock_badge();

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
		$this->output_product_stock_badge();

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

                // Fetch up to 3 closest expiry batches.
                $batches = $wpdb->get_results(
                        $wpdb->prepare(
                                "SELECT * FROM {$wpdb->prefix}inventory_batches
            WHERE sku = %s AND stock_qty > 0
            ORDER BY expiry_date ASC
            LIMIT 3",
                                $sku
                        )
                );

                if ( empty( $batches ) ) {
                        // return '';
                }

                $batches_info = array();

                $brands      = wp_get_post_terms( $product->get_id(), 'product_brand' );
                $brand_names = '';
                if ( ! is_wp_error( $brands ) && ! empty( $brands ) ) {
                        $brand_names = implode( ', ', wp_list_pluck( $brands, 'name' ) );
                }

                foreach ( $batches as $batch ) {
                        $batches_info[] = array(
                                'supplier'  => $brand_names,
                                'batch'     => $batch->batch_number,
                                'expiry'    => $batch->expiry_date ? date_i18n( INVENTORY_MANAGER_DATE_FORMAT, strtotime( $batch->expiry_date ) ) : '',
                                'origin'    => $batch->origin,
                                'location'  => $batch->location,
                                'stock_qty' => number_format( (float) $batch->stock_qty, 2 ),
                        );
                }

                $total_stock = $wpdb->get_var(
                        $wpdb->prepare(
                                "SELECT SUM(stock_qty) FROM {$wpdb->prefix}inventory_batches WHERE sku = %s",
                                $sku
                        )
                );

                foreach ( $batches_info as &$info ) {
                        $info['total_stock'] = $total_stock;
                }
                unset( $info );

                wc_get_template(
                        'frontend/product-batch-single.php',
                        array(
                                'batches_info'    => $batches_info,
                                'displayed_fields' => $displayed_fields,
                        ),
                        '',
                        $this->plugin->template_path()
                );

                ob_start();
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
				$qty 	=	number_format($qty, 2);
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
	public function render_stock_single_page(){
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

                // Fetch up to 3 closest expiry batches.
                $batches = $wpdb->get_results(
                        $wpdb->prepare(
                                "SELECT * FROM {$wpdb->prefix}inventory_batches
            WHERE sku = %s AND stock_qty > 0
            ORDER BY expiry_date ASC
            LIMIT 3",
                                $sku
                        )
                );

                if ( empty( $batches ) ) {
                        return '';
                }

                $batches_info = array();

                foreach ( $batches as $batch ) {
                        $supplier_name = '';

                        if ( $batch->supplier_id ) {
                                $supplier_name = $wpdb->get_var(
                                        $wpdb->prepare(
                                                "SELECT name FROM {$wpdb->prefix}inventory_suppliers WHERE id = %d",
                                                $batch->supplier_id
                                        )
                                );
                        }

                        $batches_info[] = array(
                                'supplier'  => $supplier_name,
                                'batch'     => $batch->batch_number,
                                'expiry'    => $batch->expiry_date ? date_i18n( INVENTORY_MANAGER_DATE_FORMAT, strtotime( $batch->expiry_date ) ) : '',
                                'origin'    => $batch->origin,
                                'location'  => $batch->location,
                                'stock_qty' => number_format( (float) $batch->stock_qty, 2 ),
                        );
                }

                $total_stock = $wpdb->get_var(
                        $wpdb->prepare(
                                "SELECT SUM(stock_qty) FROM {$wpdb->prefix}inventory_batches WHERE sku = %s",
                                $sku
                        )
                );

                foreach ( $batches_info as &$info ) {
                        $info['total_stock'] = $total_stock;
                }
                unset( $info );

                $this->display_stock_notes( $product, $batches_info[0] );
                ob_start();
                return ob_get_clean();

	}

	/**
	 * Output badge on single product page.
	 */
	public function output_product_stock_badge() {
			global $product;

			if ( ! $product ) {
					return;
			}
			$product_id      =       $product->get_id();

			$qty = isset( $_REQUEST['quantity'] ) ? floatval( $_REQUEST['quantity'] ) : 1;

			global $wpdb;
			$batches = $wpdb->get_results(
					$wpdb->prepare(
							"SELECT * FROM {$wpdb->prefix}inventory_batches
		 WHERE product_id = %d AND supplier_id IS NOT NULL
		 ORDER BY expiry_date ASC",
							$product_id
					)
			);
			$supplier_id    =   isset( $batches[0]->supplier_id ) ? intval( $batches[0]->supplier_id ) : 0;
			$transit_time    =       0;
			
			$brands = wp_get_post_terms( $product_id, 'product_brand' );
			$brand_transits = get_option( 'inventory_manager_brand_transit', array() );
			$transit_map    = array(); // [label => total_days]
			$transit_labels = array();

			
			if ( ! is_wp_error( $brands ) && ! empty( $brands ) ) {
				foreach ( $brands as $brand ) {
					$brand_id = $brand->term_id;
					if ( isset( $brand_transits[ $brand_id ] ) ) {
						$label = $brand_transits[ $brand_id ];
						if($brand_transits[ $brand_id ] == ''){
							continue;
						}
						$transit_map[ $label ] = $this->inventory_manager_pro_convert_transit_to_days( $label );
					}
				}
			}

			if ( ! empty( $transit_map ) ) {
				// Sort by numeric duration
				asort( $transit_map );

				$sorted_labels = array_keys( $transit_map );

				if ( count( $sorted_labels ) === 1 ) {
					$transit_labels	=	$this->inventory_manager_pro_format_transit_label( $sorted_labels[0] );
				} else {
					$transit_labels	=	$this->inventory_manager_pro_format_transit_label( $sorted_labels[0] ) . ' to ' . $this->inventory_manager_pro_format_transit_label( end( $sorted_labels ) );
				}
			} else {
				$transit_labels	=	'0 Days';
			}
			// echo '</pre>';print_r($transit_labels);echo '</pre>';
			$transit_time 	=	$transit_labels;
			// $transit_time = ucwords( str_replace( '_', ' ', $transit_time ) );
			
			// Check stock breakdown to determine which note to show
			$info = $this->iwc->fetch_stock_breakdown( $product->get_id(), $qty );
			
			// Show in-stock note if there's any stock available (supports decimal quantities)
			if ( $info['total_stock'] > 0 ) {
				// Show in-stock note when there's any stock available
				$this->render_stock_single_page();
			} else {
				// Only show backorder note if there's no stock at all
				$this->render_stock_badge_for_single_product_page( $product->get_id(), $qty, $transit_time );
			}
	}
	private function inventory_manager_pro_format_transit_label( $label ) {
		return ucwords( str_replace( ['_','-'], ' ', $label ) );
	}
	private function inventory_manager_pro_convert_transit_to_days( $label ) {
		if ( preg_match( '/(\d+)_day(s)?/', $label, $m ) ) {
			return (int) $m[1];
		}
		if ( preg_match( '/(\d+)_week(s)?/', $label, $m ) ) {
			return (int) $m[1] * 7;
		}
		if ( preg_match( '/(\d+)_month(s)?/', $label, $m ) ) {
			return (int) $m[1] * 30; // Approximate a month as 30 days
		}
		return 9999; // fallback high number
	}
	/**
	 * Render a stock/backorder badge for a single product page.
	 *
	 * @param int    $product_id Product ID.
	 * @param float  $qty        Requested quantity.
	 * @param string $transit_time       Optional transit_time override.
	 * @param string $name       Optional name override.
	 */
	private function render_stock_badge_for_single_product_page( $product_id, $qty, $transit_time, $name = '' ) {
			 $product = wc_get_product( $product_id );
			 if ( ! $product ) {
					 return;
			 }
			//  $inv_reduction_per_item	=	$this->inv_reduction_per_item_shortcode($product);
			 $settings  = get_option( 'inventory_manager_frontend_notes', array() );
			//  echo '<pre>';print_r($settings);exit;
			 $backorder_title_color 	=	isset( $settings['backorder_color'] ) ? $settings['backorder_color'] : '';
			 $backorder_popup_color 	=	isset( $settings['backorder_popup_color'] ) ? $settings['backorder_popup_color'] : '';

			if ( isset( $settings['show_backorder_popup'] ) && $settings['show_backorder_popup'] === 'yes' ) {
			 $template = isset( $settings['backorder_popup'] ) ? $settings['backorder_popup'] : __( '%1$d items of %2$s will be delivered immediately. %3$d items will be in backorder and delivered when stock arrives.', 'inventory-manager-pro' );
			}else{
				$template	=	'';
			}
			if ( isset( $settings['show_backorder'] ) && $settings['show_backorder'] === 'yes' ) {
			 	$backorder_title = isset( $settings['backorder_note'] ) ? $settings['backorder_note'] : __( 'Backorder:', 'inventory-manager-pro' );
			}else{
				$backorder_title = '';
			}
			 if ( ! empty( $transit_time ) && strpos( $template, '{transit_time}' ) !== false ) {

			 	$template = '<span style="color:'.$backorder_title_color.'">'.$backorder_title.'</span> <span style="color:'.$backorder_popup_color.'">'.str_replace( '{transit_time}', esc_html( $transit_time ), $template ).'</span>';
			 } else {
					 	$template = '<span style="color:'.$backorder_title_color.'">'.$backorder_title.'</span> <span style="color:'.$backorder_popup_color.'"> '.preg_replace( '/\{transit_time\}/i', '', $template ).'</span>';
				 }

			 $info = $this->iwc->fetch_stock_breakdown( $product_id, $qty );
			 if ( $info['backorder_qty'] <= 0 ) {
					 return;
			 }

			 $name = $name ? $name : $product->get_name();
			 $message = str_replace(
					 array( '{immediate_qty}', '{product_name}', '{backorder_qty}' ),
					 array( $info['immediate_qty'], $product->get_name(), $info['backorder_qty'] ),
					 $template
			 );

			 if ( $message === $template ) {
					 $message = sprintf( $template, $info['immediate_qty'], $product->get_name(), $info['backorder_qty'] );
			 }

			 echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	private function inv_reduction_per_item_shortcode($product){
			 if ( $product && $product->is_type( 'variation' ) ) {
					 $variation_id = $product->get_id();
					 $quantity	=	get_post_meta( $variation_id, 'wsvi_multiplier', true );
					//  $quantity	=	5;
			 }else{
					 $quantity	=	1;
			 }
			 return $quantity;
	}
}
