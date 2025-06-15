<?php
/**
 * Dashboard tabs navigation template
 *
 * @package Inventory_Manager_Pro
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current tab.
$tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'overview';

// Dashboard page URL.
$dashboard_url = get_permalink();
?>

<div class="inventory-manager-tabs">
	<ul class="nav-tabs">
		<li class="tab <?php echo $tab === 'overview' ? 'active' : ''; ?>">
			<a href="<?php echo esc_url( add_query_arg( 'tab', 'overview', $dashboard_url ) ); ?>">
				<?php _e( 'Overview', 'inventory-manager-pro' ); ?>
			</a>
		</li>
		<li class="tab <?php echo $tab === 'detailed-logs' ? 'active' : ''; ?>">
			<a href="<?php echo esc_url( add_query_arg( 'tab', 'detailed-logs', $dashboard_url ) ); ?>">
				<?php _e( 'Detailed Logs', 'inventory-manager-pro' ); ?>
			</a>
		</li>
		<li class="tab <?php echo $tab === 'add-manually' ? 'active' : ''; ?>">
			<a href="<?php echo esc_url( add_query_arg( 'tab', 'add-manually', $dashboard_url ) ); ?>">
				<?php _e( 'Add Manually', 'inventory-manager-pro' ); ?>
			</a>
		</li>
		<li class="tab <?php echo $tab === 'import' ? 'active' : ''; ?>">
			<a href="<?php echo esc_url( add_query_arg( 'tab', 'import', $dashboard_url ) ); ?>">
				<?php _e( 'Import', 'inventory-manager-pro' ); ?>
			</a>
		</li>
		<li class="tab <?php echo $tab === 'settings' ? 'active' : ''; ?>">
			<a href="<?php echo esc_url( add_query_arg( 'tab', 'settings', $dashboard_url ) ); ?>">
				<?php _e( 'Settings', 'inventory-manager-pro' ); ?>
			</a>
		</li>
	</ul>
</div>