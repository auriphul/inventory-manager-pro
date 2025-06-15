<?php
/**
 * Define the internationalization functionality.
 *
 * @since      1.0.0
 * @package    Inventory_Manager_Pro
 */
class Inventory_i18n {
	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'inventory-manager-pro',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}
