<?php
/**
 * Plugin Name: Inventory Manager Pro
 * Plugin URI: https://aurang.dev/inventory-manager-pro
 * Description: Advanced inventory management system for WooCommerce with batch tracking capabilities
 * Version: 2.3.4
 * Author: Aurang Zeb
 * Author URI: https://aurang.dev
 * Text Domain: inventory-manager-pro
 * Domain Path: /languages
 * WC requires at least: 5.0.0
 * WC tested up to: 9.8.0
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'INVENTORY_MANAGER_VERSION', '2.3.4' );
define( 'INVENTORY_MANAGER_FILE', __FILE__ );
define( 'INVENTORY_MANAGER_PATH', plugin_dir_path( __FILE__ ) );
define( 'INVENTORY_MANAGER_URL', plugin_dir_url( __FILE__ ) );
define( 'INVENTORY_MANAGER_BASENAME', plugin_basename( __FILE__ ) );
define( 'INVENTORY_MANAGER_DATE_FORMAT', 'd/m/Y' );

/**
 * Format currency values using plugin setting.
 *
 * @param float $amount Amount to format.
 * @return string
 */
function inventory_manager_format_price( $amount ) {
    $currency = get_option( 'inventory_manager_currency', get_woocommerce_currency_symbol() );
    $decimals = wc_get_price_decimals();

    return $currency . number_format( (float) $amount, $decimals );
}

/**
 * The code that runs during plugin activation.
 */
function activate_inventory_manager() {
	require_once INVENTORY_MANAGER_PATH . 'includes/class-inventory-activator.php';
	Inventory_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_inventory_manager() {
	require_once INVENTORY_MANAGER_PATH . 'includes/class-inventory-deactivator.php';
	Inventory_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_inventory_manager' );
register_deactivation_hook( __FILE__, 'deactivate_inventory_manager' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require INVENTORY_MANAGER_PATH . 'includes/class-inventory-manager.php';

/**
 * Begins execution of the plugin.
 */
function run_inventory_manager() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="error"><p>' . __( 'Inventory Manager Pro requires WooCommerce to be installed and activated.', 'inventory-manager-pro' ) . '</p></div>';
			}
		);
		return;
	}

	$plugin = new Inventory_Manager();
	$plugin->run();
}

add_action( 'plugins_loaded', 'run_inventory_manager' );
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true // set to false if not compatible
        );
    }
});