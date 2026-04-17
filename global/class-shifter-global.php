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
	const ACTIVE_META_KEY                = '_shifter_last_active';
	const ACTIVE_WINDOW_SECONDS          = 300;
	const ACTIVE_WRITE_THROTTLE_SECONDS  = 60;
	const ACTIVE_USERS_CACHE_KEY         = 'shifter_active_users_payload';
	const ACTIVE_USERS_CACHE_TTL_SECONDS = 20;

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
			wp_enqueue_script( 'heartbeat' );

			// Load SweetAlert2 first and in the footer.
			wp_register_script( 'sweetalert2', 'https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.26.11/sweetalert2.min.js', array(), '7.26.11', true );
			wp_enqueue_script( 'sweetalert2' );

			// Load plugin script after dependencies and in the footer.
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/shifter-global.js', array( 'jquery', 'sweetalert2' ), $this->version, true );

			// Localize ajax params to the plugin script so it's always available when it runs.
			wp_localize_script(
				$this->plugin_name,
				'ajax_object',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'shifter_ops' ),
				)
			);
		}
	}


		/**
		 * Send Terminate Request
		 *
		 * @since  1.0.0
		 * @return mixed
		 */
	public function shifter_app_terminate() {
		$nonce_valid = check_ajax_referer( 'shifter_ops', 'security', false );
		if ( false === $nonce_valid ) {
			wp_send_json(
				array(
					'success'    => false,
					'statusCode' => 403,
				),
				403
			);
			exit;
		}

		$this->reset_all_active_users_presence();
		$api = new Shifter_API();
		return $api->terminate_wp_app();
	}

		/**
		 * Send Generator Request
		 *
		 * @since  1.0.0
		 * @return mixed
		 */
	public function shifter_app_generate() {
		$nonce_valid = check_ajax_referer( 'shifter_ops', 'security', false );
		if ( false === $nonce_valid ) {
			wp_send_json(
				array(
					'success'    => false,
					'statusCode' => 403,
				),
				403
			);
			exit;
		}

		$this->reset_all_active_users_presence();
		$api = new Shifter_API();
		return $api->generate_wp_app();
	}

		/**
		 * Send Upload Single Page Request
		 *
		 * @since  1.3.0
		 * @return mixed
		 */
	public function shifter_app_upload_single() {
		// Verify nonce without dying to allow logging on failure.
		$nonce_valid = check_ajax_referer( 'shifter_ops', 'security', false );
		if ( false === $nonce_valid ) {
			// Log minimal context for debugging (no secrets).
			$user_id   = get_current_user_id();
			$logged_in = is_user_logged_in() ? '1' : '0';
			$ref       = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
			$origin    = isset( $_SERVER['HTTP_ORIGIN'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ) : '';
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( "[Shifter] upload_single: nonce invalid; user={$user_id} logged_in={$logged_in} referer={$ref} origin={$origin}" );
			wp_send_json(
				array(
					'success'        => false,
					'statusCode'     => 403,
					'httpStatusCode' => 403,
				),
				403
			);
			exit;
		}
		$api  = new Shifter_API();
		$path = isset( $_POST['path'] ) ? sanitize_text_field( wp_unslash( $_POST['path'] ) ) : '';
		// Log request meta (path only; no sensitive data).
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( "[Shifter] upload_single: request path={$path}" );
		$response = $api->upload_single_page( $path );

		if ( is_wp_error( $response ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[Shifter] upload_single: wp_error=' . $response->get_error_message() );
			wp_send_json(
				array(
					'success'        => false,
					'statusCode'     => 500,
					'httpStatusCode' => 500,
				),
				500
			);
			exit;
		}

		$http_status    = wp_remote_retrieve_response_code( $response );
		$body           = wp_remote_retrieve_body( $response );
		$data           = json_decode( $body, true );
		$api_status     = is_array( $data ) && isset( $data['statusCode'] ) ? intval( $data['statusCode'] ) : null;
		$http_ok        = ( $http_status >= 200 && $http_status < 300 );
		$api_ok         = is_null( $api_status ) ? true : ( $api_status >= 200 && $api_status < 300 );
		$success        = ( $http_ok && $api_ok );
		$api_status_log = is_null( $api_status ) ? 'null' : (string) $api_status;
		$success_log    = $success ? '1' : '0';
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( '[Shifter] upload_single: http_status=' . $http_status . ' api_status=' . $api_status_log . ' success=' . $success_log );
		wp_send_json(
			array(
				'success'        => $success,
				'statusCode'     => ! is_null( $api_status ) ? $api_status : $http_status,
				'httpStatusCode' => $http_status,
			),
			$http_status ? $http_status : 500
		);
		exit;
	}

	/**
	 * Heartbeat presence update and response payload.
	 *
	 * @param array  $response  Heartbeat response.
	 * @param array  $_data      Heartbeat request payload.
	 * @param string $_screen_id Screen ID.
	 * @return array
	 */
	public function shifter_heartbeat_presence( $response, $_data, $_screen_id ) {
		unset( $_data, $_screen_id );

		if ( ! is_user_logged_in() ) {
			return $response;
		}

		$this->touch_active_user();
		$response['shifter_active_users'] = $this->build_active_users_payload( true );
		return $response;
	}

	/**
	 * Return active users payload.
	 */
	public function shifter_get_active_users() {
		$nonce_valid = check_ajax_referer( 'shifter_ops', 'security', false );
		if ( false === $nonce_valid ) {
			wp_send_json(
				array(
					'success'    => false,
					'statusCode' => 403,
				),
				403
			);
			exit;
		}

		if ( ! is_user_logged_in() ) {
			wp_send_json(
				array(
					'success'    => false,
					'statusCode' => 401,
				),
				401
			);
			exit;
		}

		$this->touch_active_user();
		$payload = $this->build_active_users_payload( true );
		wp_send_json(
			array(
				'success' => true,
				'data'    => $payload,
			),
			200
		);
		exit;
	}

	/**
	 * Shifter Admin Bar
	 *
	 * @since 1.0.0
	 */
	public function shifter_admin_bar_items() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$local_class = getenv( 'SHIFTER_LOCAL' ) ? 'disable_shifter_operation' : '';
		$api         = new Shifter_API();
		global $wp_admin_bar;
		$active_users = $this->build_active_users_payload();
		$count        = intval( $active_users['count'] );
		$names        = $active_users['names_preview'];

		$shifter_support_back_to_shifter_dashboard = array(
			'id'     => 'shifter_support_back_to_shifter_dashboard',
			'title'  => "Shifter Dashboard <span style='font-family: dashicons; position: relative; top:-2px' class='dashicons dashicons-external'></span>",
			'parent' => 'shifter',
			'href'   => $api->get_shifter_dashboard_url(),
			'meta'   => array(
				'target' => '_blank',
				'rel'    => 'nofollow noopener noreferrer',
			),
		);

		$shifter_support_terminate = array(
			'id'     => 'shifter_support_terminate',
			'title'  => 'Stop WordPress',
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

		$shifter_support_upload_single = array(
			'id'     => 'shifter_support_upload_single',
			'title'  => 'Upload Single Page',
			'parent' => 'shifter',
			'href'   => '#',
			'meta'   => array( 'class' => $local_class ),
		);

		$shifter_active_users = array(
			'id'    => 'shifter_active_users',
			'title' => 'Working now: ' . $count,
			'href'  => '#',
		);

		$shifter_active_users_list = array(
			'id'     => 'shifter_active_users_list',
			'title'  => esc_html( $names ),
			'parent' => 'shifter_active_users',
			'href'   => '#',
		);

		$wp_admin_bar->add_menu( $shifter_support_back_to_shifter_dashboard );
		$wp_admin_bar->add_menu( $shifter_active_users );
		$wp_admin_bar->add_menu( $shifter_active_users_list );
		if ( is_singular() && ! is_preview() ) {
			$wp_admin_bar->add_menu( $shifter_support_upload_single );
		}
		if ( ! getenv( 'SHIFTER_DISABLE_GENERATE' ) ) {
			$wp_admin_bar->add_menu( $shifter_support_generate );
		}
		$wp_admin_bar->add_menu( $shifter_support_terminate );
	}

	/**
	 * Update current user's last active timestamp.
	 */
	private function touch_active_user() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		$now         = time();
		$last_active = intval( get_user_meta( $user_id, self::ACTIVE_META_KEY, true ) );
		if ( $last_active > 0 && ( $now - $last_active ) < self::ACTIVE_WRITE_THROTTLE_SECONDS ) {
			return;
		}

		update_user_meta( $user_id, self::ACTIVE_META_KEY, $now );
		delete_transient( self::ACTIVE_USERS_CACHE_KEY );
	}

	/**
	 * Reset all currently active users to inactive state.
	 *
	 * @return void
	 */
	private function reset_all_active_users_presence() {
		$threshold          = time() - self::ACTIVE_WINDOW_SECONDS;
		$inactive_timestamp = $threshold - 1;

		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		$active_users = get_users(
			array(
				'number'       => 500,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_key'     => self::ACTIVE_META_KEY,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_value'   => $threshold,
				'meta_compare' => '>=',
				'meta_type'    => 'NUMERIC',
				'fields'       => array( 'ID' ),
			)
		);

		foreach ( $active_users as $active_user ) {
			update_user_meta( $active_user->ID, self::ACTIVE_META_KEY, $inactive_timestamp );
		}

		delete_transient( self::ACTIVE_USERS_CACHE_KEY );
	}

	/**
	 * Build active users payload from user meta.
	 *
	 * @param bool $force_refresh Force bypassing transient cache.
	 * @return array
	 */
	private function build_active_users_payload( $force_refresh = false ) {
		if ( ! $force_refresh ) {
			$cached_payload = get_transient( self::ACTIVE_USERS_CACHE_KEY );
			if ( is_array( $cached_payload ) ) {
				return $cached_payload;
			}
		}

		$threshold = time() - self::ACTIVE_WINDOW_SECONDS;
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		$users = get_users(
			array(
				'number'       => 200,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_key'     => self::ACTIVE_META_KEY,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_value'   => $threshold,
				'meta_compare' => '>=',
				'meta_type'    => 'NUMERIC',
				'orderby'      => 'meta_value_num',
				'order'        => 'DESC',
				'fields'       => array( 'ID', 'display_name' ),
			)
		);

		$current_user_id = get_current_user_id();
		$items           = array();
		$user_ids        = array();
		foreach ( $users as $user ) {
			$user_ids[] = intval( $user->ID );
		}
		if ( ! empty( $user_ids ) ) {
			update_meta_cache( 'user', $user_ids );
		}

		foreach ( $users as $user ) {
			$last_active = intval( get_user_meta( $user->ID, self::ACTIVE_META_KEY, true ) );
			$items[]     = array(
				'id'           => intval( $user->ID ),
				'display_name' => $user->display_name,
				'last_active'  => $last_active,
				'is_me'        => intval( $user->ID ) === intval( $current_user_id ),
			);
		}

		$count        = count( $items );
		$others_count = 0;
		foreach ( $items as $item ) {
			if ( empty( $item['is_me'] ) ) {
				++$others_count;
			}
		}

		$payload = array(
			'count'          => $count,
			'others_count'   => $others_count,
			'users'          => $items,
			'names_preview'  => $this->build_names_preview( $items ),
			'window_seconds' => self::ACTIVE_WINDOW_SECONDS,
		);

		set_transient( self::ACTIVE_USERS_CACHE_KEY, $payload, self::ACTIVE_USERS_CACHE_TTL_SECONDS );
		return $payload;
	}

	/**
	 * Build compact user names list for admin bar.
	 *
	 * @param array $items Active users payload items.
	 * @return string
	 */
	private function build_names_preview( $items ) {
		if ( empty( $items ) ) {
			return 'No active users';
		}

		$names = array();
		foreach ( $items as $item ) {
			if ( count( $names ) >= 5 ) {
				break;
			}
			$names[] = $item['display_name'];
		}

		$extra = count( $items ) - count( $names );
		if ( $extra > 0 ) {
			$names[] = '+' . $extra . ' more';
		}

		return implode( ', ', $names );
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

	/**
	 * Deactivate user immediately on logout.
	 *
	 * @since 1.3.2
	 * @return void
	 */
	public function shifter_logout_deactivate() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}
		// Push last active outside of active window so it drops from the list immediately.
		$inactive_timestamp = time() - self::ACTIVE_WINDOW_SECONDS - 1;
		update_user_meta( $user_id, self::ACTIVE_META_KEY, $inactive_timestamp );
		delete_transient( self::ACTIVE_USERS_CACHE_KEY );
	}
}
