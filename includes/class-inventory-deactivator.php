<?php
/**
 * Deactivator
 *
 * @package    Inventory_Manager_Pro
 */

/**
 * Fired during plugin deactivation.
 *
 * @since      1.0.0
 * @package    Inventory_Manager_Pro
 */
class Inventory_Deactivator {
	/**
	 * Actions to perform during plugin deactivation.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
	}

	/**
	 * Remove custom capabilities for inventory management.
	 * Uncomment this function call in deactivate() if you want to remove capabilities.
	 */
	private static function remove_capabilities() {
		$roles = array( 'administrator', 'shop_manager' );

		foreach ( $roles as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				$role->remove_cap( 'manage_inventory' );
			}
		}
	}
}
