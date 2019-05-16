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


	/**
	 * Site ID
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string    $site_id    Site ID
	 */
	private $site_id = '';

	/**
	 * Generate URL
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string    $generate_url    Generate URL
	 */
	private $generate_url = '';

		/**
		 * Terminate URL
		 *
		 * @since  1.0.0
		 * @access private
		 * @var    string    $terminate_url    Terminate URL
		 */
	private $terminate_url = '';

			/**
			 * Access Token
			 *
			 * @since  1.0.0
			 * @access private
			 * @var    string    $access_token    Access Token
			 */
	private $access_token = '';

			/**
			 * Refresh Token
			 *
			 * @since  1.0.0
			 * @access private
			 * @var    string    $refresh_token    Refresh Token
			 */
	private $refresh_token = '';

				/**
				 * Refresh Token Date
				 *
				 * @since  1.0.0
				 * @access private
				 * @var    string    $token_update_date    Refresh Token Date
				 */
	private static $token_update_date;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

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
		$headers    = array( 'content-type' => 'application/json' );
		$args       = array(
			'method'  => 'PUT',
			'headers' => $headers,
			'body'    => wp_json_encode( array( 'refreshToken' => $this->refresh_token ) ),
		);
		$response   = wp_remote_request( $this->refresh_url, $args );
		$body       = $response[ body ];
		$body_array = json_decode( $body );
		// phpcs:disable
		$this->access_token = $body_array->AccessToken;
		putenv( "SHIFTER_ACCESS_TOKEN=$this->access_token" );
		// phpcs:enable
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
