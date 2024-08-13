<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link  https://www.getshifter.io
 * @since 1.0.0
 *
 * @package    Shifter
 * @subpackage Shifter/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Shifter
 * @subpackage Shifter/public
 * @author     DigitalCube <hello@getshifter.io>
 */
class Shifter_Public {




	/**
	 * The ID of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/shifter-public.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/shifter-public.js', array( 'jquery' ), $this->version, false );
	}

	/**
	 * Fix for Yoast Sitemaps
	 *
	 * @since 1.0.0
	 */
	public function yoast_sitemaps_fix() {
		if ( ! function_exists( 'surbma_yoast_seo_sitemap_to_robotstxt_init' ) ) {
			add_filter(
				'robots_txt',
				function ( $output ) {
					$options = get_option( 'wpseo' );

					if ( class_exists( 'WPSEO_Sitemaps' ) && true === $options['enable_xml_sitemap'] ) {
						$home_url = get_home_url();
						$output  .= "Sitemap: {$home_url}/sitemap_index.xml\n";
					}

					return $output;
				},
				9999,
				1
			);
		}
	}
}
