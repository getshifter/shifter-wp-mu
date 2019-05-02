<?php

/**
 * The api-facing functionality of the plugin.
 *
 * @link  https://www.getshifter.io
 * @since 1.0.0
 *
 * @package    Shifter
 * @subpackage Shifter/api
 */

/**
 * The api-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Shifter
 * @subpackage Shifter/api
 * @author     DigitalCube <hello@getshifter.io>
 */
class Shifter_API {

	// start.
	private $site_id       = '';
	private $generate_url  = '';
	private $terminate_url = '';
	private $access_token  = '';
	private $refresh_token = '';
	private static $token_update_date;
	// stop.

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

		// start.
		$this->site_id       = getenv( 'SITE_ID' );
		$this->access_token  = getenv( 'SHIFTER_ACCESS_TOKEN' );
		$this->refresh_token = getenv( 'SHIFTER_REFRESH_TOKEN' );

		$shifte_api_v1               = getenv( 'SHIFTER_API_URL_V1' );
		$shifte_api_v2               = getenv( 'SHIFTER_API_URL_V2' );
		$this->terminate_url         = "$shifte_api_v2/projects/$this->site_id/wordpress_site/stop";
		$this->generate_url          = "$shifte_api_v1/projects/$this->site_id/artifacts";
		$this->refresh_url           = "$shifte_api_v1/login";
		$this->shifter_dashboard_url = "https://go.getshifter.io/admin/sites/$this->site_id";

		$bootup_unixtimestamp    = file_get_contents( ABSPATH . '/.bootup' );
		$bootup_date             = new DateTime();
		self::$token_update_date = $bootup_date->setTimestamp( intval( $bootup_unixtimestamp ) );
		// stop.
	}

		/**
		 * Terminate App
		 *
		 * @since 1.0.0
		 */
	public function terminate_wp_app() {
		if ( $this->access_token_is_expired() ) {
			$this->refresh_token();
		}
		wp_remote_request( $this->terminate_url, $this->build_args() );
	}

			/**
			 * Start Generator
			 *
			 * @since 1.0.0
			 */
	public function generate_wp_app() {
		if ( $this->access_token_is_expired() ) {
			$this->refresh_token();
		}
		return wp_remote_request( $this->generate_url, $this->build_args() );
	}

	/**
	 * Build Args
	 *
	 * @since 1.0.0
	 */
	private function build_args() {
		$headers = array(
			'authorization' => $this->access_token,
			'content-Type'  => 'application/json',
		);
		return array(
			'method'   => 'POST',
			'headers'  => $headers,
			'blocking' => false,
		);
	}

	/**
	 * Refresh API Token
	 *
	 * @since 1.0.0
	 */
	private function refresh_token() {
		$headers            = array( 'content-type' => 'application/json' );
		$args               = array(
			'method'  => 'PUT',
			'headers' => $headers,
			'body'    => json_encode( array( 'refreshToken' => $this->refresh_token ) ),
		);
		$response           = wp_remote_request( $this->refresh_url, $args );
		$body               = $response[ body ];
		$body_array         = json_decode( $body );
		$this->access_token = $body_array->AccessToken;
		putenv( "SHIFTER_ACCESS_TOKEN=$this->access_token" );
	}

	/**
	 * Access Token Expired
	 *
	 * @since 1.0.0
	 */
	private function access_token_is_expired() {
		$now     = new DateTime();
		$elapsed = self::$token_update_date->diff( $now, true );
		if ( $elapsed->h > 1 ) {
			self::$token_update_date = $now;
			return true;
		}
		return false;
	}
}
