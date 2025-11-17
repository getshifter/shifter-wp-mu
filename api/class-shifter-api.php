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
	 * Publish Single Page URL
	 *
	 * @since  1.2.0
	 * @access private
	 * @var    string    $publish_single_url    Publish single page URL
	 */
	private $publish_single_url = '';

	/**
	 * Terminate URL
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string    $terminate_url    Terminate URL
	 */
	private $terminate_url = '';

	/**
	 * Update Active User URL
	 *
	 * @since  1.2.0
	 * @access private
	 * @var    string    $update_active_user_url    Update active user URL
	 */
	private $update_active_user_url = '';

	/**
	 * Refresh URL
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string    $refresh_url    Refresh URL
	 */
	private $refresh_url = '';

	/**
	 * Shifter Dashboard URL
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string    $shifter_dashboard_url    Shifter Dashboard URL
	 */
	private $shifter_dashboard_url = '';

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

		$shifter_api                  = getenv( 'SHIFTER_API_URL' );
		$this->terminate_url          = "$shifter_api/sites/$this->site_id/wordpress_site/stop";
		$this->generate_url           = "$shifter_api/sites/$this->site_id/artifacts";
		$this->update_active_user_url = "$shifter_api/sites/$this->site_id/wordpress_site/update_active_user";
		$this->publish_single_url     = "$shifter_api/sites/$this->site_id/wordpress_site/pages/publish";
		$this->refresh_url            = "$shifter_api/login";
		$this->shifter_dashboard_url  = "https://go.getshifter.io/admin/sites/$this->site_id";

		$bootup_unixtimestamp    = file_get_contents( ABSPATH . '/.bootup' );
		$bootup_date             = new DateTime();
		self::$token_update_date = $bootup_date->setTimestamp( intval( $bootup_unixtimestamp ) );
		// stop.
	}

	/**
	 * Stop WordPress
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
	 * Upload Single Page
	 *
	 * @since 1.2.0
	 * @param string $path Target page path.
	 */
	public function upload_single_page( $path ) {
		if ( $this->access_token_is_expired() ) {
			$this->refresh_token();
		}

		$headers = array(
			'authorization' => $this->access_token,
			'content-Type'  => 'application/json',
		);
		$body    = wp_json_encode(
			array(
				'siteId' => $this->site_id,
				'path'   => $path,
			)
		);
		$args    = array(
			'method'   => 'POST',
			'headers'  => $headers,
			'blocking' => false,
			'body'     => $body,
		);

		return wp_remote_request( $this->publish_single_url, $args );
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
	 * Notify Login
	 *
	 * @since 1.2.0
	 * @param string $username Username.
	 */
	public function notify_login( $username ) {
		$result = $this->call_update_active_user( true, $username );
	}

	/**
	 * Notify Logout
	 *
	 * @since 1.2.0
	 * @param int $user_id User ID.
	 */
	public function notify_logout( $user_id ) {
		$user   = get_user_by( 'ID', $user_id );
		$result = $this->call_update_active_user( false, $user->user_login );
	}

	/**
	 * Call Update Active User
	 *
	 * @since 1.2.0
	 * @param bool   $append   Append.
	 * @param string $username Username.
	 */
	private function call_update_active_user( $append, $username ) {
		if ( $this->access_token_is_expired() ) {
			$this->refresh_token();
		}

		$headers = array(
			'authorization' => $this->access_token,
			'content-Type'  => 'application/json',
		);
		$body    = wp_json_encode(
			array(
				'append'   => $append,
				'username' => $username,
			)
		);
		$args    = array(
			'method'   => 'POST',
			'headers'  => $headers,
			'blocking' => false,
			'body'     => $body,
		);

		return wp_remote_request( $this->update_active_user_url, $args );
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
		$body       = $response['body'];
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

	/**
	 * Get Shifter Dashboard URL
	 *
	 * @since 1.0.0
	 * @return string Shifter Dashboard URL
	 */
	public function get_shifter_dashboard_url() {
		return $this->shifter_dashboard_url;
	}
}
