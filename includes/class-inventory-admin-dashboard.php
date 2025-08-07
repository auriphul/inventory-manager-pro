<?php
/**
 * Admin dashboard for Inventory Manager Pro.
 *
 * @package Inventory_Manager_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Inventory_Admin_Dashboard {
    private $plugin;

    public function __construct( $plugin ) {
        $this->plugin = $plugin;
    }

    public function register_menu() {
        add_submenu_page(
            'inventory-manager-settings',
            __( 'Dashboard', 'inventory-manager-pro' ),
            __( 'Dashboard', 'inventory-manager-pro' ),
            'manage_inventory',
            'inventory-manager-dashboard',
            array( $this, 'render_dashboard' )
        );
    }

    public function enqueue_scripts( $hook ) {
        if ( $hook !== 'inventory-manager-pro_page_inventory-manager-dashboard' ) {
            return;
        }

        wp_enqueue_style(
            'inventory-manager',
            INVENTORY_MANAGER_URL . 'assets/css/inventory-manager.css',
            array( 'dashicons' ),
            INVENTORY_MANAGER_VERSION
        );
        wp_enqueue_style( 'dashicons' );

        wp_enqueue_style(
            'daterangepicker',
            'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css',
            array(),
            INVENTORY_MANAGER_VERSION
        );

        wp_enqueue_script(
            'inventory-tables',
            INVENTORY_MANAGER_URL . 'assets/js/inventory-tables.js',
            array( 'jquery' ),
            INVENTORY_MANAGER_VERSION,
            true
        );

        wp_enqueue_script(
            'moment-jjs',
            'https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js',
            array( 'jquery' ),
            INVENTORY_MANAGER_VERSION,
            true
        );

        wp_enqueue_script(
            'daterangepicker',
            'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js',
            array( 'jquery', 'moment-jjs' ),
            INVENTORY_MANAGER_VERSION,
            true
        );

        wp_enqueue_script(
            'inventory-logs',
            INVENTORY_MANAGER_URL . 'assets/js/inventory-logs.js',
            array( 'jquery', 'daterangepicker' ),
            INVENTORY_MANAGER_VERSION,
            true
        );

        wp_enqueue_script(
            'inventory-forms',
            INVENTORY_MANAGER_URL . 'assets/js/inventory-forms.js',
            array( 'jquery' ),
            INVENTORY_MANAGER_VERSION,
            true
        );

        wp_enqueue_script(
            'inventory-settings',
            INVENTORY_MANAGER_URL . 'assets/js/inventory-settings.js',
            array( 'jquery' ),
            INVENTORY_MANAGER_VERSION,
            true
        );

        wp_localize_script(
            'inventory-tables',
            'inventory_manager',
            array(
                'api_url' => rest_url( 'inventory-manager-pro/v1' ),
                'nonce'   => wp_create_nonce( 'wp_rest' ),
                'currency_symbol' => get_option( 'inventory_manager_currency', get_woocommerce_currency_symbol() ),
                'pages'   => array(
                    'add_manually' => admin_url( 'admin.php?page=inventory-manager-dashboard&tab=add-manually' ),
                    'import'       => admin_url( 'admin.php?page=inventory-manager-dashboard&tab=import' ),
                    'settings'     => admin_url( 'admin.php?page=inventory-manager-dashboard&tab=settings' ),
                ),
            )
        );
    }

    public function render_dashboard() {
        if ( ! $this->plugin->check_dashboard_access() ) {
            wp_die( __( 'You do not have permission to access this page.', 'inventory-manager-pro' ) );
        }

        $tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'overview';
        echo '<div class="wrap inventory-manager">';
        echo '<h1>' . esc_html__( 'Inventory Manager Pro', 'inventory-manager-pro' ) . '</h1>';
        echo '<h2 class="nav-tab-wrapper">';
        $tabs = array(
            'overview'      => __( 'Overview', 'inventory-manager-pro' ),
            'detailed-logs' => __( 'Detailed Logs', 'inventory-manager-pro' ),
            'add-manually'  => __( 'Add Manually', 'inventory-manager-pro' ),
            'import'        => __( 'Import', 'inventory-manager-pro' ),
            // 'settings'      => __( 'Settings', 'inventory-manager-pro' ),
        );
        foreach ( $tabs as $key => $label ) {
            $class = ( $tab === $key ) ? ' nav-tab-active' : '';
            echo '<a href="' . esc_url( admin_url( 'admin.php?page=inventory-manager-dashboard&tab=' . $key ) ) . '" class="nav-tab' . $class . '">' . esc_html( $label ) . '</a>';
        }
        echo '</h2>';

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
    }
}
