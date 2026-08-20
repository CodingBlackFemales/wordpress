<?php

namespace WPMailSMTP\Pro\Alerts\Providers\Push;

use WPMailSMTP\Options as PluginOptions;
use WPMailSMTP\Pro\Alerts\Loader;
use WPMailSMTP\WP;

/**
 * Class Provider.
 *
 * @since 4.4.0
 */
class Provider {

	/**
	 * Ajax action slug.
	 *
	 * @since 4.4.0
	 */
	const AJAX_ACTION = 'wp_mail_smtp_admin_pro_push_notifications';

	/**
	 * Service worker request parameter.
	 *
	 * @since 4.4.0
	 */
	const SERVICE_WORKER_PARAMETER = 'wp-mail-smtp-pro-push-notifications-service-worker';

	/**
	 * Manifest request parameter.
	 *
	 * @since 4.4.0
	 */
	const MANIFEST_PARAMETER = 'wp-mail-smtp-pro-push-notifications-manifest';

	/**
	 * Register hooks.
	 *
	 * @since 4.4.0
	 *
	 * @return void
	 */
	public function hooks() {

		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_assets' ], PHP_INT_MAX );
		add_action( 'wp_enqueue_scripts', [ $this, 'frontend_enqueue_assets' ] );
		add_action( 'admin_init', [ $this, 'maybe_output_service_worker' ] );
		add_action( 'init', [ $this, 'maybe_output_manifest' ] );
		add_action( 'admin_head', [ $this, 'enqueue_manifest' ] );

		add_filter( 'wp_ajax_' . self::AJAX_ACTION, [ $this, 'process_ajax' ] );
	}

	/**
	 * Whether push notifications are enabled.
	 *
	 * @since 4.4.0
	 *
	 * @return mixed|null
	 */
	private function is_enabled() {

		$options = PluginOptions::init();

		return $options->get( 'alert_' . Options::SLUG, 'enabled' );
	}

	/**
	 * Get the public key.
	 *
	 * @since 4.4.0
	 *
	 * @return mixed|null
	 */
	private function get_public_key() {

		$options = PluginOptions::init();

		return $options->get( 'alert_' . Options::SLUG, 'public_key' );
	}

	/**
	 * Make a request to the remote API.
	 *
	 * @since 4.4.0
	 *
	 * @param string $method   Request method.
	 * @param string $endpoint Request endpoint.
	 * @param array  $args     Request arguments.
	 *
	 * @return array|WP_Error
	 */
	public function request( $method, $endpoint, $args = [] ) {

		$client   = wp_mail_smtp()->get_pro()->get_product_api()->get_client();
		$response = $client->request( $method, $endpoint, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! $response->is_successful() ) {
			return $response->get_errors();
		}

		$body = $response->get_body();

		return ! empty( $body['data'] ) ? $body['data'] : [];
	}

	/**
	 * Handle AJAX calls.
	 *
	 * @since 4.4.0
	 *
	 * @return false|void|null
	 */
	public function process_ajax() { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh,Generic.Metrics.CyclomaticComplexity.MaxExceeded

		if (
			! current_user_can( wp_mail_smtp()->get_capability_manage_global_options() ) ||
			! check_ajax_referer( self::AJAX_ACTION, false, false )
		) {
			wp_send_json_error();
		}

		$task = ! empty( $_REQUEST['task'] ) ? sanitize_key( $_REQUEST['task'] ) : ''; // phpcs:ignore WordPress.Security

		if ( empty( $task ) ) {
			wp_send_json_error();
		}

		switch ( $task ) {
			case 'enable_site':
				$this->enable_site();
				break;

			case 'disable_site':
				$this->disable_site();
				break;

			case 'get_subscriptions':
				$this->get_subscriptions();
				break;

			case 'create_subscription':
				$this->create_subscription();
				break;

			case 'update_subscription':
				$this->update_subscription();
				break;

			case 'delete_subscription':
				$this->delete_subscription();
				break;

			default:
				wp_send_json_error();
		}
	}

	/**
	 * Enable the current site on the remote API.
	 *
	 * @since 4.4.0
	 *
	 * @return void
	 */
	private function enable_site() {

		$body = [
			'email'  => get_option( 'admin_email' ),
			'status' => 'active',
		];
		$site = $this->request(
			'POST',
			'push/v1/account',
			[
				'json' => $body,
			]
		);

		if ( is_wp_error( $site ) ) {
			wp_send_json_error(
				[
					'message' => $site->get_error_message(),
				]
			);
		}

		$plugin_options = PluginOptions::init();
		$all            = $plugin_options->get_all();

		$all['alert_push_notifications']['public_key'] = $site['public_key'];

		$plugin_options->set( $all, false, true );

		wp_send_json_success( $site );
	}

	/**
	 * Disable the current site on the remote API.
	 *
	 * @since 4.4.0
	 *
	 * @return void
	 */
	private function disable_site() {

		$body = [
			'status' => 'inactive',
		];
		$site = $this->request(
			'POST',
			'push/v1/account',
			[
				'json' => $body,
			]
		);

		if ( is_wp_error( $site ) ) {
			wp_send_json_error(
				[
					'message' => $site->get_error_message(),
				]
			);
		}

		wp_send_json_success( $site );
	}

	/**
	 * Validate subscription details.
	 *
	 * @since 4.4.0
	 *
	 * @return array|null
	 */
	private function validate_subscription_payload() { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$details = ! empty( $_REQUEST['details'] ) ? $_REQUEST['details'] : ''; // phpcs:ignore WordPress.Security

		if ( empty( $details ) ) {
			return null;
		}

		$details = json_decode( wp_unslash( $details ), JSON_OBJECT_AS_ARRAY );

		if ( empty( $details ) ) {
			return null;
		}

		if ( empty( $details['endpoint'] ) ) {
			return null;
		}

		$user_agent = ! empty( $_REQUEST['user_agent'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['user_agent'] ) ) : ''; // phpcs:disable WordPress.Security.NonceVerification.Recommended

		if ( empty( $user_agent ) ) {
			return null;
		}

		$body = [
			'user_agent' => $user_agent,
			'details'    => $details,
		];

		return $body;
	}

	/**
	 * Add a subscription to the remote API.
	 *
	 * @since 4.4.0
	 *
	 * @return void
	 */
	private function create_subscription() {

		$body = $this->validate_subscription_payload();

		if ( empty( $body ) ) {
			wp_send_json_error();
		}

		$subscription = $this->request(
			'POST',
			'push/v1/subscriptions',
			[
				'json' => $body,
			]
		);

		if ( is_wp_error( $subscription ) ) {
			wp_send_json_error(
				[
					'message' => $subscription->get_error_message(),
				]
			);
		}

		wp_send_json_success( $subscription );
	}

	/**
	 * Update a subscription on the remote API.
	 *
	 * @since 4.4.0
	 *
	 * @return void
	 */
	private function update_subscription() {

		$subscription_id = ! empty( $_REQUEST['subscription_id'] ) ? $_REQUEST['subscription_id'] : ''; // phpcs:ignore WordPress.Security

		if ( empty( $subscription_id ) ) {
			wp_send_json_error();
		}

		$body = $this->validate_subscription_payload();

		if ( empty( $body ) ) {
			wp_send_json_error();
		}

		$subscription = $this->request(
			'PUT',
			"push/v1/subscriptions/$subscription_id",
			[
				'json' => $body,
			]
		);

		if ( is_wp_error( $subscription ) ) {
			wp_send_json_error(
				[
					'message' => $subscription->get_error_message(),
				]
			);
		}

		wp_send_json_success( $subscription );
	}

	/**
	 * Delete a subscription from the remote API.
	 *
	 * @since 4.4.0
	 *
	 * @return void
	 */
	private function delete_subscription() {

		$subscription_id = ! empty( $_REQUEST['subscription_id'] ) ? $_REQUEST['subscription_id'] : ''; // phpcs:ignore WordPress.Security

		if ( empty( $subscription_id ) ) {
			wp_send_json_error();
		}

		$subscription = $this->request(
			'DELETE',
			"push/v1/subscriptions/$subscription_id"
		);

		if ( is_wp_error( $subscription ) ) {
			wp_send_json_error(
				[
					'message' => $subscription->get_error_message(),
				]
			);
		}

		wp_send_json_success( $subscription );
	}

	/**
	 * Fetch subscriptions from the remote API.
	 *
	 * @since 4.4.0
	 *
	 * @return void
	 */
	private function get_subscriptions() {

		$subscriptions = $this->request( 'GET', 'push/v1/subscriptions' );

		if ( is_wp_error( $subscriptions ) ) {
			wp_send_json_error(
				[
					'message' => $subscriptions->get_error_message(),
				]
			);
		}

		$plugin_options     = PluginOptions::init();
		$all                = $plugin_options->get_all();
		$cached_connections = [];

		if ( ! empty( $all['alert_push_notifications']['connections'] ) ) {
			$cached_connections = array_column( $all['alert_push_notifications']['connections'], null, 'id' );
		}

		$subscriptions = array_column( $subscriptions, null, 'id' );
		$connections   = [];

		// Copy labels from previously saved connections.
		foreach ( $subscriptions as $id => $subscription ) {
			$connection       = [];
			$connection['id'] = $id;

			if ( ! empty( $cached_connections[ $id ]['label'] ) ) {
				$connection['label'] = $cached_connections[ $id ]['label'];
			} else {
				$connection['label'] = $subscription['user_agent'];
			}

			$connections[] = $connection;
		}

		// Update stored connections.
		$all['alert_push_notifications']['connections'] = $connections;

		$plugin_options->set( $all, false, true );

		// Build connections HTML output.
		$options_class = ( new Loader() )->get_options( Options::SLUG );
		$options       = new $options_class();
		$output        = [];

		foreach ( $connections as $i => $connection ) {
			$output[] = $options->get_connection_options( $connection, $i );
		}

		$output = implode( '', $output );

		wp_send_json_success( $output );
	}

	/**
	 * Return a list of all possible error notices.
	 *
	 * @since 4.4.0
	 *
	 * @return array
	 */
	private function get_notices() {

		$notices = [
			'unsupported'       => esc_html__( 'This browser doesn\'t support push notifications', 'wp-mail-smtp-pro' ),
			'permission_denied' => wp_kses(
				sprintf(
				/* translators: %s - Plugin general settings page link. */
					__( 'Push notifications are disabled on this browser. <a href="%s" target="_blank" rel="noopener noreferrer">Follow this guide</a> to enable them', 'wp-mail-smtp-pro' ),
					esc_url(
						wp_mail_smtp()->get_utm_url(
							'https://wpmailsmtp.com/docs/setting-up-email-alerts/#resetting-push-notifications-permissions',
							[
								'medium'  => 'Alerts Settings',
								'content' => 'Resetting push notification permissions',
							]
						)
					)
				),
				[
					'a' => [
						'href'   => [],
						'rel'    => [],
						'target' => [],
					],
				]
			),
			'service_worker'    => esc_html__( 'Error loading service worker', 'wp-mail-smtp-pro' ),
			'subscription'      => esc_html__( 'Error fetching push notification subscription', 'wp-mail-smtp-pro' ),
			'subscribe'         => esc_html__( 'Error subscribing to push notifications', 'wp-mail-smtp-pro' ),
			'unsubscribe'       => esc_html__( 'Error unsubscribing from push notifications', 'wp-mail-smtp-pro' ),
			'request'           => esc_html__( 'API request error', 'wp-mail-smtp-pro' ),
		];

		return $notices;
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @since 4.4.0
	 */
	public function admin_enqueue_assets() {

		// Bail if not admin.
		if ( ! current_user_can( wp_mail_smtp()->get_capability_manage_global_options() ) ) {
			return;
		}

		$admin         = wp_mail_smtp()->get_admin();
		$is_alerts_tab = $admin->is_admin_page( 'general' ) && $admin->get_current_tab() === 'alerts';

		// Bail if not in alerts tab
		// and push notifications are disabled.
		if ( ! $is_alerts_tab && ! $this->is_enabled() ) {
			return;
		}

		$this->enqueue_common_assets();

		// Bail if we're not in Alerts tab.
		if ( ! $is_alerts_tab ) {
			return;
		}

		wp_enqueue_script(
			'wp-mail-smtp-pro-alerts-push-notifications',
			wp_mail_smtp()->assets_url . '/pro/js/smtp-pro-alerts-push-notifications' . WP::asset_min() . '.js',
			[ 'wp-mail-smtp-alerts' ],
			WPMS_PLUGIN_VER,
			true
		);
	}

	/**
	 * Enqueue frontend assets.
	 *
	 * @since 4.4.0
	 *
	 * @return void
	 */
	public function frontend_enqueue_assets() {

		// Bail if not logged in,
		// or not an admin,
		// or push notifications are disabled.
		if (
			! is_user_logged_in() ||
			! current_user_can( wp_mail_smtp()->get_capability_manage_global_options() ) ||
			! $this->is_enabled()
		) {
			return;
		}

		$this->enqueue_common_assets();
	}

	/**
	 * Enqueue common assets.
	 *
	 * @since 4.4.0
	 *
	 * @return void
	 */
	private function enqueue_common_assets() {

		wp_enqueue_script(
			'wp-mail-smtp-pro-push-notifications',
			wp_mail_smtp()->assets_url . '/pro/js/smtp-pro-push-notifications' . WP::asset_min() . '.js',
			[],
			WPMS_PLUGIN_VER,
			true
		);

		$ajax_url = add_query_arg(
			'action',
			self::AJAX_ACTION,
			wp_nonce_url(
				admin_url( 'admin-ajax.php' ),
				self::AJAX_ACTION
			)
		);

		$service_worker_url = add_query_arg(
			[
				'tab'                          => 'alerts',
				self::SERVICE_WORKER_PARAMETER => 1,
				'v'                            => ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? time() : WPMS_PLUGIN_VER,
			],
			wp_mail_smtp()->get_admin()->get_admin_page_url()
		);

		$script_settings = [
			'apiUrl'           => $ajax_url,
			'serviceWorkerUrl' => $service_worker_url,
			'publicKey'        => $this->get_public_key(),
		];

		$admin = wp_mail_smtp()->get_admin();

		if (
			$admin->is_admin_page( 'general' ) &&
			$admin->get_current_tab() === 'alerts'
		) {
			$script_settings['notices'] = $this->get_notices();
		}

		wp_add_inline_script(
			'wp-mail-smtp-pro-push-notifications',
			'var WPMailSMTPPushNotificationsSettings = ' . wp_json_encode( $script_settings ),
			true
		);
	}

	/**
	 * Output the application manifest.
	 *
	 * @since 4.4.0
	 *
	 * @return void
	 */
	public function maybe_output_manifest() {

		$admin = wp_mail_smtp()->get_admin();

		// Bail if it's not a manifest request,
		// or we're not in Alerts tab.
		if (
			! isset( $_GET[ self::MANIFEST_PARAMETER ] ) || // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
			! $admin->is_admin_page( 'general' ) ||
			$admin->get_current_tab() !== 'alerts'
		) {
			return;
		}

		$name     = esc_html__( 'WP Mail SMTP Alerts', 'wp-mail-smtp-pro' );
		$manifest = [
			'short_name' => $name,
			'name'       => $name,
			'start_url'  => '/wp-admin/admin.php?page=wp-mail-smtp&tab=alerts',
			'display'    => 'standalone',
		];

		header( 'Content-Type: application/json' );

		echo json_encode( $manifest, JSON_PRETTY_PRINT ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode

		die();
	}

	/**
	 * Output the service worker.
	 *
	 * @since 4.4.0
	 *
	 * @return void
	 */
	public function maybe_output_service_worker() {

		$admin = wp_mail_smtp()->get_admin();

		// Bail if we're not in Alerts tab,
		// or it's not a service worker request.
		if (
			! $admin->is_admin_page( 'general' ) ||
			$admin->get_current_tab() !== 'alerts' ||
			! isset( $_GET[ self::SERVICE_WORKER_PARAMETER ] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
		) {
			return;
		}

		$url = wp_mail_smtp()->assets_url . '/pro/js/smtp-pro-push-notifications-service-worker' . WP::asset_min() . '.js';

		header( 'Content-Type: application/javascript' );
		echo "importScripts('{$url}')"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		die();
	}

	/**
	 * Enqueue the application manifest.
	 *
	 * @since 4.4.0
	 *
	 * @return void
	 */
	public function enqueue_manifest() {

		$admin = wp_mail_smtp()->get_admin();

		// Bail if we're not in Alerts tab.
		if (
			! $admin->is_admin_page( 'general' ) ||
			$admin->get_current_tab() !== 'alerts'
		) {
			return;
		}

		$manifest_url = add_query_arg(
			[
				'tab'                    => 'alerts',
				self::MANIFEST_PARAMETER => 1,
			],
			$admin->get_admin_page_url()
		);
		?>
		<link rel="manifest" href="<?php echo esc_attr( $manifest_url ); ?>"/>
		<?php
	}
}
