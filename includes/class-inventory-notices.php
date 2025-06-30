<?php
/**
 * Custom notices handler to bypass WooCommerce default system.
 *
 * @since 2.3.0
 * @package Inventory_Manager_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Inventory_Manager_Notices {
    /**
     * Collected notices.
     *
     * @var array
     */
    private static $notices = array();

    /**
     * Bootstraps hooks.
     */
    public static function init() {
        // Capture all wc_add_notice calls.
        add_filter( 'woocommerce_add_notice', array( __CLASS__, 'capture_notice' ), 10, 2 );

        // Remove WooCommerce default notice output.
        add_action( 'init', array( __CLASS__, 'remove_wc_notice_hooks' ), 5 );
    }

    /**
     * Store notice and prevent WooCommerce from displaying it.
     *
     * @param string $message Notice text.
     * @param string $type    Notice type: success|error|notice.
     * @return false Always false to prevent WC default handling.
     */
    public static function capture_notice( $message, $type ) {
        self::$notices[] = array(
            'message' => $message,
            'type'    => $type,
        );
        return false;
    }

    /**
     * Remove wc_print_notices from common WooCommerce hooks.
     */
    public static function remove_wc_notice_hooks() {
        $hooks = array(
            'woocommerce_before_single_product',
            'woocommerce_before_main_content',
            'woocommerce_before_cart',
            'woocommerce_before_checkout_form',
            'woocommerce_before_customer_login_form',
            'woocommerce_before_shop_loop',
        );

        foreach ( $hooks as $hook ) {
            remove_action( $hook, 'woocommerce_output_all_notices', 10 );
            add_action( $hook, array( __CLASS__, 'print_notices' ), 10 );
        }
    }

    /**
     * Print collected notices using a custom hook.
     */
    public static function print_notices() {
        foreach ( self::$notices as $notice ) {
            /**
             * Filter notice HTML before display.
             *
             * @param string $html   The notice HTML.
             * @param string $type   Notice type.
             * @param string $message Original message.
             */
            $html = apply_filters(
                'inventory_manager_notice_html',
                '<div class="inventory-notice inventory-' . esc_attr( $notice['type'] ) . '">' . wp_kses_post( $notice['message'] ) . '</div>',
                $notice['type'],
                $notice['message']
            );

            echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        // Reset after output.
        self::$notices = array();
    }
}

Inventory_Manager_Notices::init();
