<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link  https://www.getshifter.io
 * @since 1.0.0
 *
 * @package    Shifter
 * @subpackage Shifter/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Shifter
 * @subpackage Shifter/admin
 * @author     DigitalCube <hello@getshifter.io>
 */
class Shifter_Admin {

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
	 * Display Container Time
	 *
	 * @since 1.0.0
	 */
	public function notice_shifter_dashboard_timer() {
		$bootup_filename = '../.bootup';
		$hard_limit      = 180;
		if ( file_exists( $bootup_filename ) ) {
			$unixtime       = file_get_contents( $bootup_filename, true );
			$shifter_remain = $hard_limit - round( ( time() - intval( $unixtime ) ) / 60 );
			if ( $shifter_remain < 3 ) {
				?>
	<div class="error"><ul>
	Notice: Shifter will power down WordPress in few minutes. Please restart your from the Shifter Dashboard.
	</ul></div>
				<?php
			} elseif ( $shifter_remain < 30 ) {
				?>
	<div class="error"><ul>
	Notice: Shifter will power down WordPress in <?php echo esc_html( $shifter_remain ); ?> minutes. Please restart your from the Shifter Dashboard.
	</ul></div>
				<?php
			}
		}
	}

	/**
	 * Redis Cache Fix
	 *
	 * @since 1.0.0
	 * @param    string $option Redis Option.
	 * @param    string $old_value Old Value.
	 * @param    string $value Value.
	 */
	public function option_cache_flush( $option, $old_value = '', $value = '' ) {
		if ( ! empty( $option ) ) {
			wp_cache_delete( $option, 'options' );
			foreach ( array( 'alloptions', 'notoptions' ) as $options_name ) {
				$options = wp_cache_get( $options_name, 'options' );
				if ( ! is_array( $options ) ) {
					$options = array();
				}
				if ( isset( $options[ $option ] ) ) {
					unset( $options[ $option ] );
					wp_cache_set( $options_name, $options, 'options' );
				}
				unset( $options );
			}
		}
	}


	/**
	 * Shifter Heartbeat
	 *
	 * @since 1.0.0
	 */
	public function shifter_heartbert_on_sitepreview_write_script() {
		if ( is_user_logged_in() ) {
			?>
		<script>
		function shifter_heartbert_getajax() {
			var xhr= new XMLHttpRequest();
			xhr.open("GET","/wp-admin/admin-ajax.php?action=nopriv_heartbeat");
			xhr.send();
		}
		setInterval("shifter_heartbert_getajax()", 30000);
		</script>
			<?php
		}
	}


	/**
	 * Shifter Mail From Helper
	 *
	 * @since 1.0.0
	 * @param string $email_address Email address.
	 */
	public function shifter_mail_from( $email_address ) {
		return 'wordpress@app.getshifter.io';
	}

	/**
	 * Integrations between Shifter and Algolia
	 *
	 * @param string $shared_attributes Shared attrs.
	 * @param string $post Post.
	 *
	 * @since 1.0.0
	 */
	public function shifter_replace_algolia_permalink( $shared_attributes, $post ) {
		$replaced_domain = getenv( 'SHIFTER_DOMAIN' );
		if ( ! $replaced_domain ) {
			$replaced_domain = getenv( 'CF_DOMAIN' );
		}
		if ( $replaced_domain ) {
			$url            = $shared_attributes['permalink'];
			$parsed_url     = wp_parse_url( $url );
			$replace_target = $parsed_url['host'];
			if ( isset( $parsed_url['port'] ) && $parsed_url['port'] ) {
				$replace_target .= ":{$parsed_url['port']}";
			}
			$shared_attributes['permalink'] = preg_replace( "#{$replace_target}#i", $replaced_domain, $url );
		}
		return $shared_attributes;
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
	 * Shifter Admin Page
	 *
	 * @since 1.0.0
	 */
	public function shifter_mu_admin() {
		echo "<div class='wrap'>";
		echo '<h1>' . esc_html__( 'Shifter', 'shifter-mu-admin' ) . '</h1>';
		echo "<div class='card'>
			 <h2 class='title'>Generator Settings</h2>
			 <span>Customize your static site generator settings for faster build times.</span>
			 <p class='submit'><a class='button button-primary' href='admin.php?page=shifter-settings'>Generator Settings</a></p>
		 </div>";
		echo "<div class='card'>
			 <h2 class='title'>Docs & Support</h2>
			 <span>Need help with something or have a question? Check out our documentation or contact support for more help.</span>
			 <p class='submit'><a target='_blank' rel='noopener noreferrer' class='button button-primary' href='https://support.getshifter.io'>Docs & Support</a></p>
		 </div>";
		echo "<div class='card'>
		 <h2 class='title'>Shifter Blog</h2>
		 <span>Learn about WordPress, static site generators, case studies and features sites, upcoming WordCamp events, and the latest news from Shifter.</span>
		 <p class='submit'><a class='button button-primary' target='_blank' rel='noopener noreferrer' href='https://www.getshifter.io/blog'>Shifter Blog</a></p>
	 </div>";
		echo '</div>';
	}

	/**
	 * Shifter Settings Page
	 *
	 * @since 1.0.0
	 */
	public function shifter_mu_admin_page() {
		add_menu_page(
			'Shifter',
			'Shifter',
			'manage_options',
			'shifter',
			array(
				$this,
				'shifter_mu_admin',
			),
			$this->shifter_icon()
		);
	}

	/**
	 * Hide upgrade notice
	 *
	 * @since  1.0.3
	 */
	public function hide_update_notice() {
		remove_action( 'admin_notices', 'update_nag', 3 );
	}

	/**
	 * Remove Core Update
	 *
	 * @since  1.0.4
	 */
	public function remove_core_updates() {
		global $wp_version;
		return (object) array(
			'last_checked'    => time(),
			'version_checked' => $wp_version,
			'updates'         => array(),
		);
	}

}
