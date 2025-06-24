<?php
/**
 * Settings dashboard tab template
 *
 * @package Inventory_Manager_Pro
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Ensure settings class is available and output the settings page.
$settings = new Inventory_Settings( null );
$settings->render_settings_page();
