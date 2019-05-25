<?php
/**
 * The global functionality of the plugin.
 *
 * @link  https://www.getshifter.io
 * @since 1.0.0
 *
 * @package    Shifter
 * @subpackage Shifter/global
 */

/**
 * The global functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Shifter
 * @subpackage Shifter/global
 * @author     DigitalCube <hello@getshifter.io>
 */
class Shifter_Global {

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
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

		/**
		 * Shifter Icon
		 *
		 * @since 1.0.0
		 */
	public function shifter_icon() {
		return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMAAC4jAAAuIwF4pT92AAAAB3RJTUUH4QUQCAwVRk5ANwAAAeFJREFUOMu9lEFIVEEYx38zs7LiQSja7BAaHcIoaqGDslFIyEadYusU0cki6yAaBV0FD8kSBGlLeVEwIcgkKMukDmEYLLXtJamI7dihQ0Qs+t73psM+t0V9vidI32mGmfl98/2/+Y/Cj9nMSN6IHIiJgxEHI+6ftplrW9hgxGrGFqgLWIscmk2OWqDajGS1ZS0A5RpwGeBDR7824hITB+05Xut8llLystKeKCNuRW/XVUpZ2fZlogKczYzQOdl1LiBpCYgD9aAO+vMe4Ea1Mq0KWDkO2BhA52QXr07dw3jSqj25YMTJp6Z7J/wDiQoMwC7L0ABs93lvEp/H06t0OjZ1EavUDNAHPHiXzu6PINnXHQujR3/sPR8ofKL6hpRKhMB+WaP3ATR9GgsAWo4Aj4Du9hdXX68D+yi6fuvO4v2l9bpMx5NLeeAMwNsTt0hN961J21UYflpKXtnYww6C/YMO/R+nRPHruO/xOuB32OaVdmPu5G2lrBf3fxyMuN6yU4y4uuoOcW1zMbcY5YaNvg3jIRf5BhyKAiz7TmgMqe5hpKYcftazhGUwBOY1F3M3I3c59bx3AMvjtVkWqzgN8D3ZHQ04n87S9vJ6BjgLzAGLFn4COWDP7vd3pgBaCndXnf0LIlef9HGSOIAAAAAASUVORK5CYII=';
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {

		// Only load assets when generator is running.
		if ( is_user_logged_in() ) {
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/shifter-global.css', array(), $this->version, 'all' );
			wp_register_style( 'sweetalert2', 'https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.26.11/sweetalert2.min.css', array(), '7.26.11' );
			wp_enqueue_style( 'sweetalert2' );
		}

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {

		// Only load assets when generator is running.
		if ( is_user_logged_in() ) {
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/shifter-global.js', array( 'jquery' ), $this->version, false );
			wp_register_script( 'sweetalert2', 'https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.26.11/sweetalert2.min.js', array(), '7.26.11', true );
			wp_localize_script( 'sweetalert2', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
			wp_enqueue_script( 'sweetalert2' );
		}

	}


		/**
		 * Send Terminate Request
		 *
		 * @since  1.0.0
		 * @return string    The version number of the plugin.
		 */
	public function shifter_app_terminate() {
		$api = new Shifter_API();
		return $api->terminate_wp_app();
	}

		/**
		 * Send Generator Request
		 *
		 * @since  1.0.0
		 * @return string    The version number of the plugin.
		 */
	public function shifter_app_generate() {
		$api = new Shifter_API();
		return $api->generate_wp_app();
	}

	/**
	 * Shifter Admin Bar
	 *
	 * @since 1.0.0
	 */
	public function shifter_admin_bar_items() {
		$local_class = getenv( 'SHIFTER_LOCAL' ) ? 'disable_shifter_operation' : '';
		$api         = new Shifter_API();
		global $wp_admin_bar;

		$shifter_support_back_to_shifter_dashboard = array(
			'id'     => 'shifter_support_back_to_shifter_dashboard',
			'title'  => "Shifter Dashboard <span style='font-family: dashicons; position: relative; top:-2px' class='dashicons dashicons-external'></span>",
			'parent' => 'shifter',
			'href'   => $api->shifter_dashboard_url,
			'meta'   => array(
				'target' => '_blank',
				'rel'    => 'nofollow noopener noreferrer',
			),
		);

		$shifter_support_terminate = array(
			'id'     => 'shifter_support_terminate',
			'title'  => 'Terminate App',
			'parent' => 'shifter',
			'href'   => '#',
			'meta'   => array( 'class' => $local_class ),
		);

		$shifter_support_generate = array(
			'id'     => 'shifter_support_generate',
			'title'  => 'Generate Artifact',
			'parent' => 'shifter',
			'href'   => '#',
			'meta'   => array( 'class' => $local_class ),
		);

		$wp_admin_bar->add_menu( $shifter_support_back_to_shifter_dashboard );
		$wp_admin_bar->add_menu( $shifter_support_generate );
		$wp_admin_bar->add_menu( $shifter_support_terminate );
	}

	/**
	 * Shifter Admin Bar Toggle
	 *
	 * @since 1.0.0
	 */
	public function shifter_admin_bar() {

		global $wp_admin_bar;

		$shifter_top_menu = '
			<span class="ab-icon">
				<img src="' . $this->shifter_icon() . '" alt="Shifter Icon" />
			</span>
			<span class="ab-label">Shifter</span>';

		$wp_admin_bar->add_menu(
			array(
				'id'    => 'shifter',
				'title' => $shifter_top_menu,
				'href'  => admin_url() . 'admin.php?page=shifter',
			)
		);
	}

}
