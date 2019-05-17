<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link  https://www.getshifter.io
 * @since 1.0.0
 *
 * @package    Shifter
 * @subpackage Shifter/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Shifter
 * @subpackage Shifter/includes
 * @author     DigitalCube <hello@getshifter.io>
 */
class Shifter {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    Shifter_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( defined( 'SHIFTER_VERSION' ) ) {
			$this->version = SHIFTER_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'shifter';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_global_hooks();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_api_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Shifter_Loader. Orchestrates the hooks of the plugin.
	 * - Shifter_I18n. Defines internationalization functionality.
	 * - Shifter_Admin. Defines all hooks for the admin area.
	 * - Shifter_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-shifter-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-shifter-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-shifter-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-shifter-public.php';

		/**
		 * The class responsible for defining all actions that occur in the api-facing
		 * side of the site.
		 */
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'api/class-shifter-api.php';

		/**
			 * The class responsible for defining all actions that occur globally.
			 */
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'global/class-shifter-global.php';

		$this->loader = new Shifter_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Shifter_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function set_locale() {

		$plugin_i18n = new Shifter_I18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Shifter_Admin( $this->get_plugin_name(), $this->get_version() );

		// Dashboard Timer.
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'notice_shifter_dashboard_timer' );

		// Redis Cache Fix.
		$this->loader->add_action( 'add_option', $plugin_admin, 'option_cache_flush' );
		$this->loader->add_action( 'update_option', $plugin_admin, 'option_cache_flush' );
		$this->loader->add_action( 'delete_option', $plugin_admin, 'option_cache_flush' );

		// Shifter Heartbeat.
		$this->loader->add_action( 'wp_footer', $plugin_admin, 'shifter_heartbert_on_sitepreview_write_script', 999 );

		// Shifter Mail From Helper.
		$this->loader->add_filter( 'wp_mail_from', $plugin_admin, 'shifter_mail_from' );

		// Shifter Algolia Intergrations.
		$this->loader->add_filter( 'algolia_post_shared_attributes', $plugin_admin, 'shifter_replace_algolia_permalink', 10, 2 );
		$this->loader->add_filter( 'algolia_searchable_post_shared_attributes', $plugin_admin, 'shifter_replace_algolia_permalink', 10, 2 );

		// Shifter Admin Page.
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'shifter_mu_admin_page' );

		// Hide upgrade notice.
		$this->loader->add_action( 'admin_head', $plugin_admin, 'hide_update_notice', 1 );

		// Remove Core Update.
		$this->loader->add_filter( 'pre_site_transient_update_core', $plugin_admin, 'remove_core_updates' );
	}

		/**
		 * Register all of the hooks related to global functionality
		 * of the plugin.
		 *
		 * @since  1.0.0
		 * @access private
		 */
	private function define_global_hooks() {

		$plugin_global = new Shifter_Global( $this->get_plugin_name(), $this->get_version() );

		// Global Scripts and Styles.
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_global, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_global, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_global, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_global, 'enqueue_scripts' );

		// Shifter Admin Bar Items.
		$this->loader->add_action( 'wp_before_admin_bar_render', $plugin_global, 'shifter_admin_bar_items' );

		// Shifter Admin Bar.
		$this->loader->add_action( 'wp_before_admin_bar_render', $plugin_global, 'shifter_admin_bar' );

		// Generate Artifact Request.
		$this->loader->add_action( 'wp_ajax_shifter_app_generate', $plugin_global, 'shifter_app_generate' );

		// Terminate Container Request.
		$this->loader->add_action( 'wp_ajax_shifter_app_terminate', $plugin_global, 'shifter_app_terminate' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_public_hooks() {
		$plugin_public = new Shifter_Public( $this->get_plugin_name(), $this->get_version() );

		// Yoast Sitemaps.
		$this->loader->add_action( 'plugins_loaded', $plugin_public, 'yoast_sitemaps_fix' );
	}

		/**
		 * Register all of the hooks related to the api-facing functionality
		 * of the plugin.
		 *
		 * @since  1.0.0
		 * @access private
		 */
	private function define_api_hooks() {
		$plugin_api = new Shifter_API( $this->get_plugin_name(), $this->get_version() );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since 1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since  1.0.0
	 * @return string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since  1.0.0
	 * @return Shifter_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since  1.0.0
	 * @return string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
