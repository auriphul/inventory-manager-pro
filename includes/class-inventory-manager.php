<?php
/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    Inventory_Manager_Pro
 */

/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    Inventory_Manager_Pro
 */
class Inventory_Manager {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Inventory_Manager_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_shortcodes();
		$this->define_api_endpoints();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		// The class responsible for orchestrating the actions and filters of the core plugin.
		require_once INVENTORY_MANAGER_PATH . 'includes/class-inventory-loader.php';

		// The class responsible for defining internationalization functionality.
		require_once INVENTORY_MANAGER_PATH . 'includes/class-inventory-i18n.php';

		// The class responsible for database operations.
		require_once INVENTORY_MANAGER_PATH . 'includes/class-inventory-database.php';

		// The class responsible for defining API endpoints.
		require_once INVENTORY_MANAGER_PATH . 'includes/class-inventory-api.php';

                // The class responsible for defining all shortcodes.
                require_once INVENTORY_MANAGER_PATH . 'includes/class-inventory-shortcodes.php';

                // Admin dashboard handler.
                require_once INVENTORY_MANAGER_PATH . 'includes/class-inventory-admin-dashboard.php';

		// The class responsible for integrating with WooCommerce.
		require_once INVENTORY_MANAGER_PATH . 'includes/class-inventory-woocommerce.php';

		// The class responsible for plugin settings.
		require_once INVENTORY_MANAGER_PATH . 'includes/class-inventory-settings.php';

		require_once INVENTORY_MANAGER_PATH . 'includes/class-inventory-fullscreen.php';

		$this->loader = new Inventory_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new Inventory_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_admin_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_admin_scripts' );

                $settings = new Inventory_Settings( $this );
                $this->loader->add_action( 'admin_menu', $settings, 'add_settings_page' );
                $this->loader->add_action( 'admin_init', $settings, 'register_settings' );

                $dashboard = new Inventory_Admin_Dashboard( $this );
                $this->loader->add_action( 'admin_menu', $dashboard, 'register_menu' );
                $this->loader->add_action( 'admin_enqueue_scripts', $dashboard, 'enqueue_scripts' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_public_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_public_scripts' );

		$woocommerce = new Inventory_Manager_WooCommerce( $this );
	}

	/**
	 * Register all shortcodes.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_shortcodes() {
		$shortcodes = new Inventory_Shortcodes( $this );
		$shortcodes->register_shortcodes();
	}

	/**
	 * Register all API endpoints.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_api_endpoints() {
		$api = new Inventory_API( $this );
		$this->loader->add_action( 'rest_api_init', $api, 'register_routes' );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_admin_styles() {
		wp_enqueue_style(
			'inventory-manager-admin',
			INVENTORY_MANAGER_URL . 'assets/css/admin.css',
			array(),
			INVENTORY_MANAGER_VERSION,
			'all'
		);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_admin_scripts() {
		wp_enqueue_script(
			'inventory-manager-admin',
			INVENTORY_MANAGER_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			INVENTORY_MANAGER_VERSION,
			false
		);

		wp_localize_script(
			'inventory-manager-admin',
			'inventory_manager_admin',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'inventory-manager-admin-nonce' ),
			)
		);
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_public_styles() {
		if ( $this->is_inventory_page() || is_product() || is_shop() ) {
			wp_enqueue_style(
				'inventory-manager',
				INVENTORY_MANAGER_URL . 'assets/css/inventory-manager.css',
				array(),
				INVENTORY_MANAGER_VERSION,
				'all'
			);
		}
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_public_scripts() {
		if ( $this->is_inventory_page() ) {
			wp_enqueue_script(
				'inventory-tables',
				INVENTORY_MANAGER_URL . 'assets/js/inventory-tables.js',
				array( 'jquery' ),
				INVENTORY_MANAGER_VERSION,
				true
			);

			wp_enqueue_script(
				'inventory-logs',
				INVENTORY_MANAGER_URL . 'assets/js/inventory-logs.js',
				array( 'jquery' ),
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

			wp_localize_script(
				'inventory-tables',
				'inventory_manager',
				array(
					'api_url' => rest_url( 'inventory-manager/v1' ),
					'nonce'   => wp_create_nonce( 'wp_rest' ),
					'pages'   => array(
						'add_manually' => add_query_arg( 'tab', 'add-manually', get_permalink( get_option( 'inventory_dashboard_page_id' ) ) ),
						'import'       => add_query_arg( 'tab', 'import', get_permalink( get_option( 'inventory_dashboard_page_id' ) ) ),
						'settings'     => add_query_arg( 'tab', 'settings', get_permalink( get_option( 'inventory_dashboard_page_id' ) ) ),
					),
				)
			);
		}
	}

	/**
	 * Check if current page is inventory dashboard page.
	 *
	 * @since    1.0.0
	 * @return   boolean
	 */
	public function is_inventory_page() {
		global $post;

		if ( ! is_object( $post ) ) {
			return false;
		}

		$dashboard_page_id = get_option( 'inventory_dashboard_page_id' );

		return $post->ID == $dashboard_page_id;
	}

	/**
	 * Check if user has access to inventory dashboard.
	 *
	 * @since    1.0.0
	 * @return   boolean
	 */
	public function check_dashboard_access() {
		if ( ! current_user_can( 'manage_inventory' ) && ! current_user_can( 'manage_woocommerce' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get template path.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	public function template_path() {
		return INVENTORY_MANAGER_PATH . 'templates/';
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}
}
