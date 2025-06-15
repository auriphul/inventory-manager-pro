<?php
/**
 * Fullscreen functionality for Inventory Manager Pro
 *
 * @package    Inventory_Manager_Pro
 */

class Inventory_Fullscreen {
	/**
	 * Initialize the class and set its hooks
	 */
	public function __construct() {
		// Enqueue scripts and styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Add body class if needed
		add_filter( 'body_class', array( $this, 'add_body_class' ) );

		// Hide admin bar in fullscreen mode via JavaScript
		add_action( 'wp_head', array( $this, 'maybe_hide_admin_bar' ) );
	}

	/**
	 * Enqueue scripts and styles
	 */
	public function enqueue_scripts() {
		// Only enqueue on pages that might have our shortcode
		if ( $this->is_inventory_page() ) {
			wp_enqueue_style(
				'inventory-manager-fullscreen',
				INVENTORY_MANAGER_URL . 'assets/css/fullscreen.css',
				array( 'inventory-manager' ),
				INVENTORY_MANAGER_VERSION
			);

			wp_enqueue_script(
				'inventory-manager-fullscreen',
				INVENTORY_MANAGER_URL . 'assets/js/fullscreen.js',
				array( 'jquery' ),
				INVENTORY_MANAGER_VERSION,
				true
			);
		}
	}

	/**
	 * Add custom body class
	 */
	public function add_body_class( $classes ) {
		// We'll add a base class that our JS will toggle
		if ( $this->is_inventory_page() ) {
			$classes[] = 'inventory-manager-page';
		}
		return $classes;
	}

	/**
	 * Check if current page could potentially contain our shortcode
	 */
	private function is_inventory_page() {
		global $post;

		// Check for dashboard page ID
		$dashboard_page_id = get_option( 'inventory_dashboard_page_id' );

		if ( is_object( $post ) && $dashboard_page_id == $post->ID ) {
			return true;
		}

		// If post content contains our shortcode
		if ( is_object( $post ) && has_shortcode( $post->post_content, 'inventory_dashboard' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Maybe hide admin bar based on cookie or parameter
	 */
	public function maybe_hide_admin_bar() {
		if ( $this->is_inventory_page() ) {
			// This ensures the admin bar doesn't cause a jump when we toggle fullscreen
			echo '<style type="text/css">
                html { margin-top: 0 !important; }
                * html body { margin-top: 0 !important; }
            </style>';

			// This adds JS to help hide the admin bar
			echo '<script type="text/javascript">
                (function() {
                    // Check for fullscreen preference
                    if (localStorage.getItem("inventory_fullscreen") === "true") {
                        document.documentElement.className += " inventory-fullscreen-html";
                        document.body.className += " inventory-fullscreen";
                    }
                })();
            </script>';
		}
	}
	public function render_fullscreen() {
		$view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'overview';
	
		$allowed_views = [
			'overview' => 'overview.php',
			'add-manually' => 'add-manually.php',
			// Add more views here as needed
		];
	
		$template_file = isset($allowed_views[$view])
			? plugin_dir_path(__FILE__) . '../templates/dashboard/' . $allowed_views[$view]
			: plugin_dir_path(__FILE__) . '../templates/dashboard/overview.php';
	
		include plugin_dir_path(__FILE__) . '../templates/dashboard/wrapper.php';
	}
}

// Initialize the class
new Inventory_Fullscreen();
