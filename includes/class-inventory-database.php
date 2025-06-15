<?php
/**
 * The class responsible for database operations.
 *
 * @since      1.0.0
 * @package    Inventory_Manager_Pro
 */

class Inventory_Database {

    /**
     * Get batches with filtering and pagination.
     *
     * @param array $args Query arguments.
     * @return array Array of batch objects.
     */
    public function get_batches($args = array()) {
        global $wpdb;

        // Default arguments
        $defaults = array(
            'sku' => '',
            'product_id' => 0,
            'supplier_id' => 0,
            'expiry_filters' => array(),
            'search' => '',
            'orderby' => 'sku',
            'order' => 'ASC',
            'per_page' => 20,
            'offset' => 0,
        );

        $args = wp_parse_args($args, $defaults);

        // Start building query
        $query = "SELECT b.*, p.post_title as product_name, 
                  (SELECT name FROM {$wpdb->prefix}inventory_suppliers WHERE id = b.supplier_id) as supplier_name 
                  FROM {$wpdb->prefix}inventory_batches b
                  LEFT JOIN {$wpdb->posts} p ON b.product_id = p.ID
                  WHERE 1=1";

        $query_args = array();

        // Filter by SKU
        if (!empty($args['sku'])) {
            $query .= " AND b.sku = %s";
            $query_args[] = $args['sku'];
        }

        // Filter by product ID
        if (!empty($args['product_id'])) {
            $query .= " AND b.product_id = %d";
            $query_args[] = $args['product_id'];
        }

        // Filter by supplier ID
        if (!empty($args['supplier_id'])) {
            $query .= " AND b.supplier_id = %d";
            $query_args[] = $args['supplier_id'];
        }

        // Filter by expiry range
        if (!empty($args['expiry_filters'])) {
            $expiry_conditions = array();

            foreach ($args['expiry_filters'] as $range) {
                switch ($range) {
                    case '6+':
                        $expiry_conditions[] = "(b.expiry_date > DATE_ADD(CURDATE(), INTERVAL 6 MONTH))";
                        break;
                    case '3-6':
                        $expiry_conditions[] = "(b.expiry_date > DATE_ADD(CURDATE(), INTERVAL 3 MONTH) AND b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 6 MONTH))";
                        break;
                    case '1-3':
                        $expiry_conditions[] = "(b.expiry_date > DATE_ADD(CURDATE(), INTERVAL 1 MONTH) AND b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 3 MONTH))";
                        break;
                    case '<1':
                        $expiry_conditions[] = "(b.expiry_date > CURDATE() AND b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 1 MONTH))";
                        break;
                    case 'expired':
                        $expiry_conditions[] = "(b.expiry_date <= CURDATE())";
                        break;
                    case 'no_expiry':
                        $expiry_conditions[] = "(b.expiry_date IS NULL)";
                        break;
                }
            }

            if (!empty($expiry_conditions)) {
                $query .= " AND (" . implode(" OR ", $expiry_conditions) . ")";
            }
        }

        // Search
        if (!empty($args['search'])) {
            $query .= " AND (
                b.sku LIKE %s OR 
                p.post_title LIKE %s OR 
                b.batch_number LIKE %s OR
                (SELECT name FROM {$wpdb->prefix}inventory_suppliers WHERE id = b.supplier_id) LIKE %s OR
                b.origin LIKE %s OR
                b.location LIKE %s
            )";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $query_args[] = $search_term;
            $query_args[] = $search_term;
            $query_args[] = $search_term;
            $query_args[] = $search_term;
            $query_args[] = $search_term;
            $query_args[] = $search_term;
        }

        // Ordering
        if (!empty($args['orderby'])) {
            $allowed_orderby = array(
                'sku' => 'b.sku',
                'product_name' => 'p.post_title',
                'batch' => 'b.batch_number',
                'stock_qty' => 'b.stock_qty',
                'supplier' => 'supplier_name',
                'expiry' => 'b.expiry_date',
                'origin' => 'b.origin',
                'location' => 'b.location',
                'date_created' => 'b.date_created',
                'stock_cost' => 'b.unit_cost',
                'landed_cost' => '(b.unit_cost + b.freight_markup)'
            );

            $orderby = isset($allowed_orderby[$args['orderby']]) ? $allowed_orderby[$args['orderby']] : 'b.sku';
            $order = in_array(strtoupper($args['order']), array('ASC', 'DESC')) ? strtoupper($args['order']) : 'ASC';

            $query .= " ORDER BY {$orderby} {$order}";
        }

        // Pagination
        if ($args['per_page'] > 0) {
            $query .= " LIMIT %d OFFSET %d";
            $query_args[] = $args['per_page'];
            $query_args[] = $args['offset'];
        }

        // Prepare and execute query
        $sql = empty($query_args) ? $query : $wpdb->prepare($query, $query_args);
        $results = $wpdb->get_results($sql);

        // Format results
        if ($results) {
            foreach ($results as &$batch) {
                // Format expiry date
                if (!empty($batch->expiry_date)) {
                    $batch->expiry_formatted = date_i18n(get_option('date_format'), strtotime($batch->expiry_date));
                    
                    // Determine expiry range
                    $today = time();
                    $expiry = strtotime($batch->expiry_date);
                    
                    if ($expiry <= $today) {
                        $batch->expiry_range = 'expired';
                    } else {
                        $diff_months = ($expiry - $today) / (30 * 24 * 60 * 60); // Approximate months
                        
                        if ($diff_months > 6) {
                            $batch->expiry_range = '6plus';
                        } elseif ($diff_months > 3) {
                            $batch->expiry_range = '3-6';
                        } elseif ($diff_months > 1) {
                            $batch->expiry_range = '1-3';
                        } else {
                            $batch->expiry_range = 'less1';
                        }
                    }
                } else {
                    $batch->expiry_formatted = '';
                    $batch->expiry_range = 'no_expiry';
                }

                // Format costs
                $batch->stock_cost = $batch->unit_cost * $batch->stock_qty;
                $batch->landed_cost = ($batch->unit_cost * $batch->freight_markup) * $batch->stock_qty;
                // $batch->landed_cost = $batch->freight_markup * $batch->stock_qty;
                
                $batch->stock_cost_formatted = wc_price($batch->stock_cost);
                $batch->landed_cost_formatted = wc_price($batch->landed_cost);
            }
        }

        return $results;
    }

    /**
     * Count total batches for pagination.
     *
     * @param array $args Query arguments.
     * @return int Total count.
     */
    public function count_batches($args = array()) {
        global $wpdb;

        // Default arguments
        $defaults = array(
            'sku' => '',
            'product_id' => 0,
            'supplier_id' => 0,
            'expiry_filters' => array(),
            'search' => '',
        );

        $args = wp_parse_args($args, $defaults);

        // Start building query
        $query = "SELECT COUNT(*) FROM {$wpdb->prefix}inventory_batches b
                  LEFT JOIN {$wpdb->posts} p ON b.product_id = p.ID
                  WHERE 1=1";

        $query_args = array();

        // Filter by SKU
        if (!empty($args['sku'])) {
            $query .= " AND b.sku = %s";
            $query_args[] = $args['sku'];
        }

        // Filter by product ID
        if (!empty($args['product_id'])) {
            $query .= " AND b.product_id = %d";
            $query_args[] = $args['product_id'];
        }

        // Filter by supplier ID
        if (!empty($args['supplier_id'])) {
            $query .= " AND b.supplier_id = %d";
            $query_args[] = $args['supplier_id'];
        }

        // Filter by expiry range
        if (!empty($args['expiry_filters'])) {
            $expiry_conditions = array();

            foreach ($args['expiry_filters'] as $range) {
                switch ($range) {
                    case '6+':
                        $expiry_conditions[] = "(b.expiry_date > DATE_ADD(CURDATE(), INTERVAL 6 MONTH))";
                        break;
                    case '3-6':
                        $expiry_conditions[] = "(b.expiry_date > DATE_ADD(CURDATE(), INTERVAL 3 MONTH) AND b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 6 MONTH))";
                        break;
                    case '1-3':
                        $expiry_conditions[] = "(b.expiry_date > DATE_ADD(CURDATE(), INTERVAL 1 MONTH) AND b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 3 MONTH))";
                        break;
                    case '<1':
                        $expiry_conditions[] = "(b.expiry_date > CURDATE() AND b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 1 MONTH))";
                        break;
                    case 'expired':
                        $expiry_conditions[] = "(b.expiry_date <= CURDATE())";
                        break;
                    case 'no_expiry':
                        $expiry_conditions[] = "(b.expiry_date IS NULL)";
                        break;
                }
            }

            if (!empty($expiry_conditions)) {
                $query .= " AND (" . implode(" OR ", $expiry_conditions) . ")";
            }
        }

        // Search
        if (!empty($args['search'])) {
            $query .= " AND (
                b.sku LIKE %s OR 
                p.post_title LIKE %s OR 
                b.batch_number LIKE %s OR
                (SELECT name FROM {$wpdb->prefix}inventory_suppliers WHERE id = b.supplier_id) LIKE %s OR
                b.origin LIKE %s OR
                b.location LIKE %s
            )";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $query_args[] = $search_term;
            $query_args[] = $search_term;
            $query_args[] = $search_term;
            $query_args[] = $search_term;
            $query_args[] = $search_term;
            $query_args[] = $search_term;
        }

        // Prepare and execute query
        $sql = empty($query_args) ? $query : $wpdb->prepare($query, $query_args);
        return $wpdb->get_var($sql);
    }

    /**
     * Get a single batch by ID.
     *
     * @param int $batch_id Batch ID.
     * @return object|false Batch object or false if not found.
     */
    public function get_batch($batch_id) {
        global $wpdb;

        $batch = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT b.*, p.post_title as product_name, 
                (SELECT name FROM {$wpdb->prefix}inventory_suppliers WHERE id = b.supplier_id) as supplier_name 
                FROM {$wpdb->prefix}inventory_batches b
                LEFT JOIN {$wpdb->posts} p ON b.product_id = p.ID
                WHERE b.id = %d",
                $batch_id
            )
        );

        if (!$batch) {
            return false;
        }

        // Format expiry date
        if (!empty($batch->expiry_date)) {
            $batch->expiry_formatted = date_i18n(get_option('date_format'), strtotime($batch->expiry_date));
        } else {
            $batch->expiry_formatted = '';
        }

        // Format costs
        $batch->stock_cost = $batch->unit_cost * $batch->stock_qty;
        $batch->landed_cost = ($batch->unit_cost * $batch->freight_markup) * $batch->stock_qty;
        // $batch->landed_cost = $batch->freight_markup * $batch->stock_qty;
        
        $batch->stock_cost_formatted = wc_price($batch->stock_cost);
        $batch->landed_cost_formatted = wc_price($batch->landed_cost);

        return $batch;
    }

    /**
     * Create a new batch.
     *
     * @param array $data Batch data.
     * @return int|WP_Error Batch ID or WP_Error on failure.
     */
    public function create_batch($data) {
        global $wpdb;

        // Sanitize and validate data
        $sku = sanitize_text_field($data['sku']);
        $batch_number = sanitize_text_field($data['batch_number']);
        $stock_qty = floatval($data['stock_qty']);
        $reference = sanitize_text_field($data['reference']);

        // Get product_id from SKU
        $product_id = wc_get_product_id_by_sku($sku);

        if (!$product_id) {
            return new WP_Error('invalid_sku', __('Invalid SKU. Product not found.', 'inventory-manager-pro'));
        }

        // Check if batch already exists for this product
        $existing_batch = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}inventory_batches 
                WHERE product_id = %d AND batch_number = %s",
                $product_id,
                $batch_number
            )
        );

        if ($existing_batch) {
            return new WP_Error('duplicate_batch', __('A batch with this number already exists for this product.', 'inventory-manager-pro'));
        }

        // Prepare batch data
        $batch_data = array(
            'sku' => $sku,
            'product_id' => $product_id,
            'batch_number' => $batch_number,
            'stock_qty' => $stock_qty,
            'date_created' => current_time('mysql')
        );

        // Optional fields
        if (!empty($data['supplier_id'])) {
            $batch_data['supplier_id'] = intval($data['supplier_id']);
        }else{
            $batch_data['supplier_id']  =   $this->maybe_insert_supplier($data);
        }

        if (!empty($data['expiry_date'])) {
            $batch_data['expiry_date'] = sanitize_text_field($data['expiry_date']);
        }

        if (!empty($data['origin'])) {
            $batch_data['origin'] = sanitize_text_field($data['origin']);
        }

        if (!empty($data['location'])) {
            $batch_data['location'] = sanitize_text_field($data['location']);
        }

        if (isset($data['unit_cost'])) {
            $batch_data['unit_cost'] = floatval($data['unit_cost']);
        }

        if (isset($data['freight_markup'])) {
            $batch_data['freight_markup'] = floatval($data['freight_markup']);
        }

        // Insert batch
        $result = $wpdb->insert(
            $wpdb->prefix . 'inventory_batches',
            $batch_data
        );

        if (!$result) {
            return new WP_Error('db_error', __('Error creating batch.', 'inventory-manager-pro'));
        }

        $batch_id = $wpdb->insert_id;

        // Create stock movement (initial stock)
        $wpdb->insert(
            $wpdb->prefix . 'inventory_stock_movements',
            array(
                'batch_id' => $batch_id,
                'movement_type' => 'initial_stock',
                'reference' => $reference,
                'quantity' => $stock_qty,
                'date_created' => current_time('mysql'),
                'created_by' => get_current_user_id()
            )
        );

        // Update product stock
        $this->update_product_stock($product_id);

        return $batch_id;
    }
    public function maybe_insert_supplier( $form_data ) {
        global $wpdb;
    
        // Extract and sanitize
        $supplier_name   = isset( $form_data['new_supplier'] ) ? sanitize_text_field( $form_data['new_supplier'] ) : '';
        $transit_time    = isset( $form_data['new_supplier_transit'] ) ? sanitize_text_field( $form_data['new_supplier_transit'] ) : '';
    
        if ( empty( $supplier_name ) || empty( $transit_time ) ) {
            // One or both fields missing – do not insert
            return false;
        }
    
        $table_suppliers = $wpdb->prefix . 'inventory_suppliers';
    
        // Check if supplier already exists
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table_suppliers} WHERE name = %s LIMIT 1",
                $supplier_name
            )
        );
    
        if ( $existing ) {
            // Already exists – do not insert again
            return intval( $existing );
        }
    
        // Insert new supplier
        $inserted = $wpdb->insert(
            $table_suppliers,
            array(
                'name'         => $supplier_name,
                'transit_time' => $transit_time,
                'date_created' => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s' )
        );
    
        if ( $inserted !== false ) {
            return $wpdb->insert_id;
        }
    
        return false;
    }

    /**
     * Update batch quantity.
     *
     * @param int $batch_id Batch ID.
     * @param float $quantity Quantity to adjust (positive to add, negative to subtract).
     * @param string $movement_type Type of movement (e.g., 'adjustment', 'invoice').
     * @param string $reference Reference for the movement.
     * @return bool|WP_Error True on success or WP_Error on failure.
     */
    public function update_batch_quantity($batch_id, $quantity, $movement_type, $reference) {
        global $wpdb;

        // Get batch
        $batch = $this->get_batch($batch_id);

        if (!$batch) {
            return new WP_Error('batch_not_found', __('Batch not found.', 'inventory-manager-pro'));
        }

        // Check quantity for deduction
        if ($quantity < 0 && abs($quantity) > $batch->stock_qty) {
            return new WP_Error('insufficient_stock', __('Insufficient stock. The requested quantity exceeds available stock.', 'inventory-manager-pro'));
        }

        // Create stock movement
        $wpdb->insert(
            $wpdb->prefix . 'inventory_stock_movements',
            array(
                'batch_id' => $batch_id,
                'movement_type' => $movement_type,
                'reference' => $reference,
                'quantity' => $quantity,
                'date_created' => current_time('mysql'),
                'created_by' => get_current_user_id()
            )
        );

        // Update batch stock
        $wpdb->update(
            $wpdb->prefix . 'inventory_batches',
            array('stock_qty' => $batch->stock_qty + $quantity),
            array('id' => $batch_id)
        );

        // Update product stock
        $this->update_product_stock($batch->product_id);

        return true;
    }

    /**
     * Update WooCommerce product stock based on batch quantities.
     *
     * @param int $product_id Product ID.
     */
    public function update_product_stock($product_id) {
        global $wpdb;

        // Get total stock for all batches
        $total_stock = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(stock_qty), 0) FROM {$wpdb->prefix}inventory_batches WHERE product_id = %d",
                $product_id
            )
        );

        // Update product stock
        update_post_meta($product_id, '_stock', $total_stock);

        // Set stock status
        $stock_status = $total_stock > 0 ? 'instock' : 'outofstock';
        update_post_meta($product_id, '_stock_status', $stock_status);

        // Clear product cache
        wc_delete_product_transients($product_id);
    }

    /**
     * Get detailed logs for products.
     *
     * @param array $args Query arguments.
     * @return array Array of products with batches and movements.
     */
    public function get_detailed_logs($args = array()) {
        global $wpdb;
        // Default arguments
        $defaults = array(
            'period' => '',
            'search' => '',
        );

        $args = wp_parse_args($args, $defaults);
        // echo '<pre>';print_r($args);exit;

        // Get products with filterable batches
        $query = "SELECT DISTINCT p.ID as product_id, p.post_title as product_name, m.meta_value as sku
                  FROM {$wpdb->posts} p
                  JOIN {$wpdb->postmeta} m ON p.ID = m.post_id AND m.meta_key = '_sku'
                  WHERE p.post_type = 'product' 
                  AND p.post_status = 'publish'";

        $query_args = array();

        // Search filter
        if (!empty($args['search'])) {
            $query .= " AND (
                p.post_title LIKE %s OR 
                m.meta_value LIKE %s
            )";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $query_args[] = $search_term;
            $query_args[] = $search_term;
        }

        $query .= " ORDER BY p.post_title ASC";

        // Get products
        $sql = empty($query_args) ? $query : $wpdb->prepare($query, $query_args);
        $products = $wpdb->get_results($sql, ARRAY_A);

        $result = array();

        foreach ($products as $product) {
            // Get batches for this product
            $batches_query = "SELECT b.*, 
                            (SELECT name FROM {$wpdb->prefix}inventory_suppliers WHERE id = b.supplier_id) as supplier_name
                            FROM {$wpdb->prefix}inventory_batches b
                            WHERE b.product_id = %d
                            ORDER BY b.batch_number ASC";
            
            $batches = $wpdb->get_results($wpdb->prepare($batches_query, $product['product_id']));

            if (empty($batches)) {
                continue; // Skip products with no batches
            }

            // Format batches and get movements
            foreach ($batches as &$batch) {
                // Format expiry date
                if (!empty($batch->expiry_date)) {
                    $batch->expiry_formatted = date_i18n(get_option('date_format'), strtotime($batch->expiry_date));
                } else {
                    $batch->expiry_formatted = '';
                }

                // Get movements for this batch
                $movements_query = "SELECT m.*, 
                                  DATE_FORMAT(m.date_created, '%d/%m/%Y %H:%i') as date_time,
                                  CASE 
                                    WHEN m.quantity > 0 THEN m.quantity 
                                    ELSE NULL 
                                  END as stock_in,
                                  CASE 
                                    WHEN m.quantity < 0 THEN ABS(m.quantity) 
                                    ELSE NULL 
                                  END as stock_out
                                  FROM {$wpdb->prefix}inventory_stock_movements m
                                  WHERE m.batch_id = %d";

                // Period filter
                $movements_args = array($batch->id);

                if (!empty($args['period'])) {
                    switch ($args['period']) {
                        case 'today':
                            $movements_query .= " AND DATE(m.date_created) = CURDATE() ";
                            $movements_query .= ' ';
                            break;
                        case 'yesterday':
                            $movements_query .= " AND DATE(m.date_created) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) ";
                            $movements_query .= ' ';
                            break;
                        case 'this_week':
                            $movements_query .= " AND YEARWEEK(m.date_created) = YEARWEEK(CURDATE()) ";
                            $movements_query .= ' ';
                            break;
                        case 'last_week':
                            $movements_query .= " AND YEARWEEK(m.date_created) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK)) ";
                            break;
                        case 'this_month':
                            $movements_query .= " AND MONTH(m.date_created) = MONTH(CURDATE()) AND YEAR(m.date_created) = YEAR(CURDATE()) ";
                            break;
                        case 'last_month':
                            $movements_query .= " AND MONTH(m.date_created) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(m.date_created) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) ";
                            break;
                        case 'last_3_months':
                            $movements_query .= " AND m.date_created >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH) ";
                            break;
                        case 'last_6_months':
                            $movements_query .= " AND m.date_created >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) ";
                            break;
                        case 'this_year':
                            $movements_query .= " AND YEAR(m.date_created) = YEAR(CURDATE()) ";
                            break;
                    }
                }

                $movements_query .= " ORDER BY m.date_created DESC";
                $batch->movements = $wpdb->get_results($wpdb->prepare($movements_query, $batch->id));
            }
            // Add product to result
            $result[] = array(
                'product_id' => $product['product_id'],
                'product_name' => $product['product_name'],
                'sku' => $product['sku'],
                'batches' => $batches
            );
            // echo '<pre>';print_r($result);
        }

        return $result;
    }

    /**
     * Get all suppliers.
     *
     * @return array Array of supplier objects.
     */
    public function get_suppliers() {
        global $wpdb;

        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}inventory_suppliers ORDER BY name ASC");
    }

    /**
     * Get transit times.
     *
     * @return array Array of transit time options.
     */
    public function get_transit_times() {
        $transit_times = array(
            array('id' => '3_days', 'name' => __('3 days', 'inventory-manager-pro')),
            array('id' => '1_week', 'name' => __('1 week', 'inventory-manager-pro')),
            array('id' => '2_weeks', 'name' => __('2 weeks', 'inventory-manager-pro')),
            array('id' => '20_days', 'name' => __('20 days', 'inventory-manager-pro')),
            array('id' => '1_month', 'name' => __('1 month', 'inventory-manager-pro')),
            array('id' => '40_days', 'name' => __('40 days', 'inventory-manager-pro'))
        );

        // Get custom transit times from settings
        $custom_times = get_option('inventory_manager_suppliers', array());

        if (!empty($custom_times['transit_times'])) {
            $transit_times = array();

            foreach ($custom_times['transit_times'] as $id => $name) {
                $transit_times[] = array('id' => $id, 'name' => $name);
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
        $adjustment_types = get_option('inventory_manager_adjustment_types', array());
        $types = array();

        if (empty($adjustment_types)) {
            // Default types
            $types = array(
                array('id' => 'damages', 'name' => __('Damages', 'inventory-manager-pro'), 'calculation' => 'deduct'),
                array('id' => 'received_more', 'name' => __('Received MORE', 'inventory-manager-pro'), 'calculation' => 'add'),
                array('id' => 'received_less', 'name' => __('Received LESS', 'inventory-manager-pro'), 'calculation' => 'deduct'),
                array('id' => 'free_samples', 'name' => __('Free Samples', 'inventory-manager-pro'), 'calculation' => 'deduct')
            );
        } else {
            foreach ($adjustment_types as $key => $type) {
                $types[] = array(
                    'id' => $key,
                    'name' => $type['label'],
                    'calculation' => $type['calculation']
                );
            }
        }

        return $types;
    }

    /**
     * Get all SKUs.
     *
     * @return array Array of SKUs.
     */
    public function get_skus() {
        global $wpdb;

        $skus = $wpdb->get_col(
            "SELECT meta_value FROM {$wpdb->postmeta} 
            WHERE meta_key = '_sku' AND meta_value != '' 
            ORDER BY meta_value ASC"
        );

        return array_unique($skus);
    }

    /**
     * Create a new supplier.
     *
     * @param string $name Supplier name.
     * @param string $transit_time Transit time.
     * @return int|WP_Error Supplier ID or WP_Error on failure.
     */
    public function create_supplier($name, $transit_time) {
        global $wpdb;

        // Check if supplier already exists
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}inventory_suppliers WHERE name = %s",
                $name
            )
        );

        if ($existing) {
            return new WP_Error('duplicate_supplier', __('A supplier with this name already exists.', 'inventory-manager-pro'));
        }

        // Insert supplier
        $result = $wpdb->insert(
            $wpdb->prefix . 'inventory_suppliers',
            array(
                'name' => $name,
                'transit_time' => $transit_time,
                'date_created' => current_time('mysql')
            )
        );

        if (!$result) {
            return new WP_Error('db_error', __('Error creating supplier.', 'inventory-manager-pro'));
        }

        return $wpdb->insert_id;
    }

    /**
     * Delete a batch.
     *
     * @param int $batch_id Batch ID.
     * @return bool|WP_Error True on success or WP_Error on failure.
     */
    public function delete_batch($batch_id) {
        global $wpdb;

        // Get batch info before deletion
        $batch = $this->get_batch($batch_id);

        if (!$batch) {
            return new WP_Error('batch_not_found', __('Batch not found.', 'inventory-manager-pro'));
        }

        // Check if there are movements for this batch
        $movements = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}inventory_stock_movements WHERE batch_id = %d",
                $batch_id
            )
        );

        if ($movements > 0) {
            return new WP_Error('has_movements', __('Cannot delete batch with movements. Please adjust stock to zero first.', 'inventory-manager-pro'));
        }

        // Delete batch
        $result = $wpdb->delete(
            $wpdb->prefix . 'inventory_batches',
            array('id' => $batch_id),
            array('%d')
        );

        if (!$result) {
            return new WP_Error('db_error', __('Error deleting batch.', 'inventory-manager-pro'));
        }

        // Update product stock
        $this->update_product_stock($batch->product_id);

        return true;
    }

    /**
     * Update batch information.
     *
     * @param int $batch_id Batch ID.
     * @param array $data Batch data to update.
     * @return bool|WP_Error True on success or WP_Error on failure.
     */
    public function update_batch($batch_id, $data) {
        global $wpdb;

        // Get batch
        $batch = $this->get_batch($batch_id);

        if (!$batch) {
            return new WP_Error('batch_not_found', __('Batch not found.', 'inventory-manager-pro'));
        }

        // Prepare batch data
        $batch_data = array();
        $formats = array();

        // Optional fields
        if (isset($data['batch_number'])) {
            // Check if the batch number already exists for this product
            if ($data['batch_number'] !== $batch->batch_number) {
                $existing_batch = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT id FROM {$wpdb->prefix}inventory_batches 
                        WHERE product_id = %d AND batch_number = %s AND id != %d",
                        $batch->product_id,
                        $data['batch_number'],
                        $batch_id
                    )
                );

                if ($existing_batch) {
                    return new WP_Error('duplicate_batch', __('A batch with this number already exists for this product.', 'inventory-manager-pro'));
                }
            }

            $batch_data['batch_number'] = sanitize_text_field($data['batch_number']);
            $formats[] = '%s';
        }

        if (isset($data['supplier_id'])) {
            $batch_data['supplier_id'] = intval($data['supplier_id']);
            $formats[] = '%d';
        }

        if (isset($data['expiry_date'])) {
            $batch_data['expiry_date'] = sanitize_text_field($data['expiry_date']);
            $formats[] = '%s';
        }

        if (isset($data['origin'])) {
            $batch_data['origin'] = sanitize_text_field($data['origin']);
            $formats[] = '%s';
        }

        if (isset($data['location'])) {
            $batch_data['location'] = sanitize_text_field($data['location']);
            $formats[] = '%s';
        }

        if (isset($data['unit_cost'])) {
            $batch_data['unit_cost'] = floatval($data['unit_cost']);
            $formats[] = '%f';
        }

        if (isset($data['freight_markup'])) {
            $batch_data['freight_markup'] = floatval($data['freight_markup']);
            $formats[] = '%f';
        }

        // Update batch if there's data to update
        if (!empty($batch_data)) {
            $result = $wpdb->update(
                $wpdb->prefix . 'inventory_batches',
                $batch_data,
                array('id' => $batch_id),
                $formats,
                array('%d')
            );

            if ($result === false) {
                return new WP_Error('db_error', __('Error updating batch.', 'inventory-manager-pro'));
            }
        }

        return true;
    }

    /**
     * Get stock movements for a batch.
     *
     * @param int $batch_id Batch ID.
     * @return array Array of movement objects.
     */
    public function get_batch_movements($batch_id) {
        global $wpdb;

        $movements = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT m.*, 
                DATE_FORMAT(m.date_created, '%%d/%%m/%%Y %%H:%%i') as date_time,
                CASE 
                  WHEN m.quantity > 0 THEN m.quantity 
                  ELSE NULL 
                END as stock_in,
                CASE 
                  WHEN m.quantity < 0 THEN ABS(m.quantity) 
                  ELSE NULL 
                END as stock_out,
                u.display_name as user_name
                FROM {$wpdb->prefix}inventory_stock_movements m
                LEFT JOIN {$wpdb->users} u ON m.created_by = u.ID
                WHERE m.batch_id = %d
                ORDER BY m.date_created DESC",
                $batch_id
            )
        );

        return $movements;
    }

    /**
     * Get stock summary by product.
     *
     * @return array Stock summary by product.
     */
    public function get_stock_summary() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT p.ID as product_id, 
            p.post_title as product_name, 
            m.meta_value as sku,
            SUM(b.stock_qty) as total_stock,
            COUNT(b.id) as batch_count,
            MIN(b.expiry_date) as closest_expiry
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} m ON p.ID = m.post_id AND m.meta_key = '_sku'
            LEFT JOIN {$wpdb->prefix}inventory_batches b ON b.product_id = p.ID
            WHERE p.post_type = 'product' 
            AND p.post_status = 'publish'
            GROUP BY p.ID, p.post_title, m.meta_value
            ORDER BY p.post_title ASC"
        );
    }

    /**
     * Get stock expiry summary.
     *
     * @return array Stock expiry summary.
     */
    public function get_expiry_summary() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT 
            SUM(CASE WHEN b.expiry_date > DATE_ADD(CURDATE(), INTERVAL 6 MONTH) THEN b.stock_qty ELSE 0 END) as months_6_plus,
            SUM(CASE WHEN b.expiry_date > DATE_ADD(CURDATE(), INTERVAL 3 MONTH) AND b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 6 MONTH) THEN b.stock_qty ELSE 0 END) as months_3_6,
            SUM(CASE WHEN b.expiry_date > DATE_ADD(CURDATE(), INTERVAL 1 MONTH) AND b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 3 MONTH) THEN b.stock_qty ELSE 0 END) as months_1_3,
            SUM(CASE WHEN b.expiry_date > CURDATE() AND b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 1 MONTH) THEN b.stock_qty ELSE 0 END) as months_less_1,
            SUM(CASE WHEN b.expiry_date <= CURDATE() THEN b.stock_qty ELSE 0 END) as expired,
            SUM(CASE WHEN b.expiry_date IS NULL THEN b.stock_qty ELSE 0 END) as no_expiry
            FROM {$wpdb->prefix}inventory_batches b"
        );
    }

    /**
     * Get products with low stock.
     *
     * @param int $threshold Low stock threshold.
     * @return array Products with low stock.
     */
    public function get_low_stock_products($threshold = 5) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.ID as product_id, 
                p.post_title as product_name, 
                m.meta_value as sku,
                SUM(b.stock_qty) as total_stock
                FROM {$wpdb->posts} p
                JOIN {$wpdb->postmeta} m ON p.ID = m.post_id AND m.meta_key = '_sku'
                LEFT JOIN {$wpdb->prefix}inventory_batches b ON b.product_id = p.ID
                WHERE p.post_type = 'product' 
                AND p.post_status = 'publish'
                GROUP BY p.ID, p.post_title, m.meta_value
                HAVING total_stock <= %d
                ORDER BY total_stock ASC",
                $threshold
            )
        );
    }

    /**
     * Get products with expiring stock.
     *
     * @param int $days Days threshold for expiry.
     * @return array Products with expiring stock.
     */
    public function get_expiring_products($days = 30) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.ID as product_id, 
                p.post_title as product_name, 
                pm.meta_value as sku,
                b.id as batch_id,
                b.batch_number,
                b.expiry_date,
                b.stock_qty,
                (SELECT name FROM {$wpdb->prefix}inventory_suppliers WHERE id = b.supplier_id) as supplier_name
                FROM {$wpdb->posts} p
                JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_sku'
                JOIN {$wpdb->prefix}inventory_batches b ON b.product_id = p.ID
                WHERE p.post_type = 'product' 
                AND p.post_status = 'publish'
                AND b.expiry_date IS NOT NULL 
                AND b.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL %d DAY)
                AND b.stock_qty > 0
                ORDER BY b.expiry_date ASC, p.post_title ASC",
                $days
            )
        );
    }
}