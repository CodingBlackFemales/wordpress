<?php
/**
 * LearnDash Settings Section for Stripe Connect.
 *
 * @since 4.0.0
 *
 * @package \LearnDash\Settings\Sections
 */

use LearnDash\Core\Modules\Payments\Gateways\Stripe\Connection_Handler;
use LearnDash\Core\Template\Template;
use StellarWP\Learndash\StellarWP\SuperGlobals\SuperGlobals;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Stripe_Connect' ) ) ) {
	/**
	 * Class LearnDash Settings Section for Stripe Connect.
	 *
	 * @since 4.0.0
	 */
	class LearnDash_Settings_Section_Stripe_Connect extends LearnDash_Settings_Section {
		const CONNECT_SERVER_URL = 'https://connect.learndash.com/stripe/connect.php';

		const STRIPE_RETURNED_SUCCESS               = 1;
		const STRIPE_RETURNED_AND_PROCESSED_SUCCESS = 2;

		const STRIPE_CUSTOMER_ID_META_KEY      = 'stripe_connect_customer_id';
		const STRIPE_CUSTOMER_ID_META_KEY_TEST = 'stripe_connect_test_customer_id';

		/**
		 * Protected constructor for class
		 *
		 * @since 4.0.0
		 */
		protected function __construct() {
			$this->settings_page_id = 'learndash_lms_payments';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_stripe_connection_settings';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_stripe_connection_settings';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_stripe_connection';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Stripe Connect Settings', 'learndash' );

			// Used to associate this section with the parent section.
			$this->settings_parent_section_key = 'settings_payments_list';

			$this->settings_section_listing_label = esc_html__( 'Stripe Connect', 'learndash' );

			parent::__construct();

			$this->handle_connection_request();
			$this->handle_disconnection_request();

			add_action( 'admin_notices', array( $this, 'show_notices' ) );

			add_filter(
				'learndash_settings_field_html_after',
				[ $this, 'webhook_url_add_copy_text' ],
				10,
				2
			);
		}

		/**
		 * Initialize the metabox settings values.
		 *
		 * @since 4.0.0
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			if ( empty( $this->setting_option_values['payment_methods'] ) ) {
				$this->setting_option_values['payment_methods'] = array( 'card' );
			}

			if ( ! isset( $this->setting_option_values['test_mode'] ) ) {
				$this->setting_option_values['test_mode'] = '';
			}
		}

		/**
		 * Get the default webhook url.
		 *
		 * @return string
		 */
		public static function get_default_stripe_webhook_url(): string {
			return add_query_arg(
				array( 'learndash-integration' => 'stripe_connect' ),
				esc_url_raw(
					trailingslashit( get_site_url() )
				)
			);
		}

		/**
		 * Get the stripe webhook URL.
		 *
		 * @since 4.0.0
		 *
		 * @return string
		 */
		private function get_stripe_webhook_url(): string {
			return ! empty( $this->setting_option_values['webhook_url'] )
				? $this->setting_option_values['webhook_url']
				: self::get_default_stripe_webhook_url();
		}

		/**
		 * Initialize the metabox settings fields.
		 *
		 * @since 4.0.0
		 */
		public function load_settings_fields() {
			$this->setting_option_fields = array(
				'connection_button'    => array(
					'name'             => 'connection_button',
					'type'             => 'text',
					'label'            => '',
					'value'            => null,
					'display_callback' => array( $this, 'connection_button' ),
				),
				'enabled'              => array(
					'name'    => 'enabled',
					'type'    => 'checkbox-switch',
					'label'   => esc_html__( 'Active', 'learndash' ),
					'value'   => $this->setting_option_values['enabled'] ?? '',
					'options' => array(
						'yes' => '',
						''    => '',
					),
				),
				'test_mode'            => array(
					'name'      => 'test_mode',
					'label'     => esc_html__( 'Test Mode', 'learndash' ),
					'help_text' => esc_html__( 'Check this box to enable test mode.', 'learndash' ),
					'type'      => 'checkbox-switch',
					'options'   => array(
						'1' => '',
						'0' => '',
					),
					'default'   => '',
					'value'     => $this->setting_option_values['test_mode'] ?? 0,
				),
				'publishable_key_test' => array(
					'name'      => 'publishable_key_test',
					'label'     => __( 'Test Publishable Key', 'learndash' ),
					'help_text' => __( 'Test publishable key used in test mode.', 'learndash' ),
					'type'      => 'hidden',
					'value'     => $this->setting_option_values['publishable_key_test'] ?? '',
				),
				'secret_key_test'      => array(
					'name'      => 'secret_key_test',
					'label'     => __( 'Test Secret Key', 'learndash' ),
					'help_text' => __( 'Test secret key used in test mode.', 'learndash' ),
					'type'      => 'hidden',
					'value'     => $this->setting_option_values['secret_key_test'] ?? '',
				),
				'publishable_key_live' => array(
					'name'      => 'publishable_key_live',
					'label'     => __( 'Live Publishable Key', 'learndash' ),
					'help_text' => __( 'Live publishable key used in real transaction.', 'learndash' ),
					'type'      => 'hidden',
					'value'     => $this->setting_option_values['publishable_key_live'] ?? '',
				),
				'secret_key_live'      => array(
					'name'      => 'secret_key_live',
					'label'     => __( 'Live Secret Key', 'learndash' ),
					'help_text' => __( 'Live secret key used in real transaction.', 'learndash' ),
					'type'      => 'hidden',
					'value'     => $this->setting_option_values['secret_key_live'] ?? '',
				),
				'account_id'           => array(
					'name'  => 'account_id',
					'label' => __( 'Account Id', 'learndash' ),
					'type'  => 'hidden',
					'value' => $this->setting_option_values['account_id'] ?? '',
				),
				'payment_methods'      => array(
					'name'      => 'payment_methods',
					'label'     => __( 'Payment Methods', 'learndash' ),
					'help_text' => __( 'Stripe payment methods to be enabled on the site.', 'learndash' ),
					'value'     => $this->setting_option_values['payment_methods'],
					'type'      => 'checkbox',
					'options'   => array(
						'card'  => __( 'Credit Card', 'learndash' ),
						'ideal' => __( 'Ideal', 'learndash' ),
					),
				),
				'return_url'           => array(
					'name'      => 'return_url',
					'label'     => __( 'Return URL ', 'learndash' ),
					'help_text' => __(
						'Redirect the user to a specific URL after the purchase. Leave blank to let user remain on the Course page.',
						'learndash'
					),
					'type'      => 'text',
					'value'     => $this->setting_option_values['return_url'] ?? '',
				),
				'webhook_url'          => array(
					'name'      => 'webhook_url',
					'type'      => 'text',
					'label'     => esc_html__( 'Webhook URL', 'learndash' ),
					'help_text' => esc_html__( 'Stripe webhooks are essential for payments to function correctly in LearnDash. We\'ll automatically configure them for you, but you can access the webhook URL here if needed.', 'learndash' ),
					'value'     => $this->get_stripe_webhook_url(),
					'class'     => 'regular-text',
					'attrs'     => defined( 'LEARNDASH_DEBUG' ) && LEARNDASH_DEBUG // @phpstan-ignore-line -- Constant can be true/false.
						? array()
						: array(
							'readonly' => 'readonly',
							'disable'  => 'disable',
						),
				),
			);

			// Add fields available only if the account is connected.

			if ( $this->account_is_connected() ) {
				$this->setting_option_fields['webhook_status_message_live'] = [
					'name'             => 'webhook_status_message_live',
					'type'             => 'text',
					'label'            => 'Live Webhooks',
					'value'            => null,
					'display_callback' => [ $this, 'webhook_status_message_live' ],
				];

				$this->setting_option_fields['webhook_status_message_test'] = [
					'name'             => 'webhook_status_message_test',
					'type'             => 'text',
					'label'            => 'Test Webhooks',
					'value'            => null,
					'display_callback' => [ $this, 'webhook_status_message_test' ],
				];

				$this->setting_option_fields['webhook_validation_button'] = [
					'name'             => 'webhook_validation_button',
					'type'             => 'text',
					'label'            => '',
					'value'            => null,
					'display_callback' => [ $this, 'webhook_validation_button' ],
				];
			}

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

			parent::load_settings_fields();
		}

		/**
		 * Filter the section saved values.
		 *
		 * @param array  $value An array of setting fields values.
		 * @param array  $old_value An array of setting fields old values.
		 * @param string $settings_section_key Settings section key.
		 * @param string $settings_screen_id Settings screen ID.
		 *
		 * @return array
		 * @since 4.0.0
		 */
		public function filter_section_save_fields( $value, $old_value, $settings_section_key, $settings_screen_id ): array {
			if ( $settings_section_key !== $this->settings_section_key ) {
				return $value;
			}

			if ( ! isset( $value['enabled'] ) ) {
				$value['enabled'] = '';
			}

			if ( ! isset( $value['payment_methods'] ) ) {
				$value['payment_methods'] = array();
			}

			if ( isset( $_POST['learndash_settings_payments_list_nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				if ( ! is_array( $old_value ) ) {
					$old_value = array();
				}

				foreach ( $value as $value_idx => $value_val ) {
					$old_value[ $value_idx ] = $value_val;
				}

				$value = $old_value;
			}

			return $value;
		}

		/**
		 * Adds the copy text to the webhook url field.
		 *
		 * @since 4.20.1
		 *
		 * @param string              $html       The HTML of the field.
		 * @param array<string,mixed> $field_args The field arguments.
		 *
		 * @return string
		 */
		public function webhook_url_add_copy_text( string $html, array $field_args ): string {
			if ( 'webhook_url' === $field_args['name'] ) {
				$html .= wp_kses(
					Template::get_admin_template(
						'common/copy-text',
						[
							'text'            => $field_args['value'],
							'tooltip_default' => esc_html__( 'Copy Webhook URL', 'learndash' ),
						]
					),
					[
						'button' => [
							'class'                => true,
							'data-tooltip'         => true,
							'data-tooltip-default' => true,
							'data-tooltip-success' => true,
							'data-text'            => true,
						],
						'span'   => [
							'class'       => true,
							'aria-hidden' => true,
						],
					]
				);

				// Wrap the html in a span to style it properly.
				$html = "<span class=\"learndash-stripe-webhook-url\">$html</span>";
			}

			return $html;
		}

		/**
		 * Show notices.
		 */
		public function show_notices() {
			// Show Stripe disconnection error.
			if ( ! empty( $_GET['ld_stripe_error'] ) && ! empty( $_GET['error_code'] ) && ! empty ( $_GET['error_message'] ) ) { // phpcs:ignore
				?>
				<div class="notice notice-error is-dismissible">
					<p>
						<b>
							<?php esc_html_e( 'Stripe Error', 'learndash' ); ?>:
						</b>
						<?php esc_html_e( $_GET['error_message'] ); // phpcs:ignore ?>
					</p>
				</div>
				<?php
			}

			// Show Stripe connection success.
			if (
				isset( $_GET['ld_stripe_connected'] ) && self::STRIPE_RETURNED_AND_PROCESSED_SUCCESS === intval( $_GET['ld_stripe_connected'] ) && // phpcs:ignore
				$this->account_is_connected()
			) {
				?>
				<div class="notice notice-success is-dismissible">
					<p>
						<?php esc_html_e( 'You have successfully connected to Stripe', 'learndash' ); ?>.
					</p>
				</div>
				<?php
			}

			// Show Stripe disconnection success.
			if ( isset( $_GET['ld_stripe_disconnected'] ) && self::STRIPE_RETURNED_AND_PROCESSED_SUCCESS === intval( $_GET['ld_stripe_disconnected'] ) ) { // phpcs:ignore
				?>
				<div class="notice notice-success is-dismissible">
					<p>
						<?php esc_html_e( 'Stripe disconnected', 'learndash' ); ?>.
					</p>
				</div>
				<?php
			}

			// Show Stripe Connect button.
			if ( $this->is_on_payments_setting_page() && ! $this->account_is_connected() ) {
				?>
				<div class="notice connect-stripe" style="display: flex; justify-content: space-between; padding: 20px 25px;">
					<h1>
						<?php esc_html_e( 'Want to accept credit card payments directly on your website?', 'learndash' ); ?>
					</h1>
					<?php $this->connection_button(); ?>
				</div>
				<?php
			}
		}

		/**
		 * Shows Stripe Connect button
		 */
		public function connection_button(): void {
			if ( $this->account_is_connected() ) :
				?>
				<a id="learndash-stripe-disconnect" href="#" data-disconnect-url="<?php echo esc_url( $this->generate_disconnect_url() ); ?>"
					data-nonce="<?php echo esc_attr( wp_create_nonce( Connection_Handler::$ajax_action_pre_disconnect ) ); ?>"
					class="learndash-stripe-connect" title="<?php esc_html_e( 'Disconnect Stripe', 'learndash' ); ?>">
					<span class="stripe-logo">
						<svg width="15" height="21" viewBox="0 0 15 21" fill="none"
							xmlns="http://www.w3.org/2000/svg">
							<path d="M6.05469 6.55469C6.05469 5.69531 6.75781 5.34375 7.92969 5.34375C9.64844 5.34375 11.7969 5.85156 13.4766 6.78906V1.55469C11.6406 0.8125 9.80469 0.5 7.92969 0.5C3.4375 0.5 0.429688 2.88281 0.429688 6.82812C0.429688 13 8.86719 11.9844 8.86719 14.6406C8.86719 15.6953 7.96875 16.0078 6.75781 16.0078C4.88281 16.0078 2.5 15.2656 0.664062 14.25V19.25C2.5 20.0703 4.57031 20.5 6.75781 20.5391C11.3672 20.5391 14.5703 18.5469 14.5703 14.5234C14.5703 7.88281 6.05469 9.05469 6.05469 6.55469Z"
								fill="white"></path>
						</svg>
					</span>
					<span>
						<?php esc_html_e( 'Disconnect Stripe', 'learndash' ); ?>
					</span>
				</a>
				<?php
			else :
				?>
				<a href="<?php echo esc_url( self::generate_connect_url() ); ?>"
					class="learndash-stripe-connect" title="<?php esc_html_e( 'Connect Stripe', 'learndash' ); ?>">
					<span class="stripe-logo">
						<svg width="15" height="21" viewBox="0 0 15 21" fill="none"
							xmlns="http://www.w3.org/2000/svg">
							<path d="M6.05469 6.55469C6.05469 5.69531 6.75781 5.34375 7.92969 5.34375C9.64844 5.34375 11.7969 5.85156 13.4766 6.78906V1.55469C11.6406 0.8125 9.80469 0.5 7.92969 0.5C3.4375 0.5 0.429688 2.88281 0.429688 6.82812C0.429688 13 8.86719 11.9844 8.86719 14.6406C8.86719 15.6953 7.96875 16.0078 6.75781 16.0078C4.88281 16.0078 2.5 15.2656 0.664062 14.25V19.25C2.5 20.0703 4.57031 20.5 6.75781 20.5391C11.3672 20.5391 14.5703 18.5469 14.5703 14.5234C14.5703 7.88281 6.05469 9.05469 6.05469 6.55469Z"
								fill="white"></path>
						</svg>
					</span>
					<span>
						<?php esc_html_e( 'Connect Stripe', 'learndash' ); ?>
					</span>
				</a>
				<?php
			endif;
		}

		/**
		 * Shows Stripe webhook validation button.
		 *
		 * @since 4.6.0
		 *
		 * @return void
		 */
		public function webhook_validation_button(): void {
			if ( ! $this->account_is_connected() ) {
				return;
			}
			?>
			<button
				id="learndash-validate-stripe-webhook"
				class="button"
				data-nonce="<?php echo esc_attr( wp_create_nonce( Connection_Handler::$ajax_action_post_connect ) ); ?>"
			>
				<span class="learndash-validate-stripe-webhook-text-default">
					<?php esc_html_e( 'Validate Webhook Setup', 'learndash' ); ?>
				</span>
				<span class="learndash-validate-stripe-webhook-text-loading" style="display: none;">
					<?php esc_html_e( 'Validating Webhook Setup...', 'learndash' ); ?>
				</span>
			</button>
			<?php
		}

		/**
		 * Shows the webhook status message for live mode.
		 *
		 * @since 4.20.1
		 *
		 * @return void
		 */
		public function webhook_status_message_live(): void {
			if ( ! $this->account_is_connected() ) {
				return;
			}

			?>
			<span class="ld-stripe-webhook-live-status__message ld-stripe-webhook-live-status__message--loading" style="display: none;">
				<?php Template::show_template( 'components/icons/refresh' ); ?>

				<?php esc_html_e( 'Configuring webhooks. This can take a minute...', 'learndash' ); ?>
			</span>

			<span class="ld-stripe-webhook-live-status__message ld-stripe-webhook-live-status__message--error" style="display: none;">
				<?php Template::show_template( 'components/icons/alert' ); ?>
				<span class="ld-stripe-webhook-live-status__error-html"></span>
			</span>

			<span class="ld-stripe-webhook-live-status__message ld-stripe-webhook-live-status__message--success" style="display: none;">
				<?php Template::show_template( 'components/icons/check' ); ?>

				<?php esc_html_e( 'Webhooks were properly validated.', 'learndash' ); ?>
			</span>
			<?php
		}

		/**
		 * Shows the webhook status message for test mode.
		 *
		 * @since 4.20.1
		 *
		 * @return void
		 */
		public function webhook_status_message_test(): void {
			if ( ! $this->account_is_connected() ) {
				return;
			}

			?>
			<span class="ld-stripe-webhook-test-status__message ld-stripe-webhook-test-status__message--loading" style="display: none;">
				<?php Template::show_template( 'components/icons/refresh' ); ?>

				<?php esc_html_e( 'Configuring webhooks. This can take a minute...', 'learndash' ); ?>
			</span>

			<span class="ld-stripe-webhook-test-status__message ld-stripe-webhook-test-status__message--error" style="display: none;">
				<?php Template::show_template( 'components/icons/alert' ); ?>
				<span class="ld-stripe-webhook-test-status__error-html"></span>
			</span>

			<span class="ld-stripe-webhook-test-status__message ld-stripe-webhook-test-status__message--success" style="display: none;">
				<?php Template::show_template( 'components/icons/check' ); ?>
				<?php esc_html_e( 'Webhooks were properly validated.', 'learndash' ); ?>
			</span>
			<?php
		}

		/**
		 * Checks if account is already connected.
		 *
		 * @return bool
		 */
		private function account_is_connected(): bool {
			return ! empty( $this->setting_option_values['account_id'] );
		}

		/**
		 * Checks if payments settings is a current page.
		 *
		 * @return bool
		 */
		private function is_on_payments_setting_page(): bool {
			/**
			 * Filters the check for whether the current page is the payments settings page.
			 *
			 * @since 4.25.0
			 *
			 * @param bool $is_on_payments_setting_page Whether the current page is the payments settings page.
			 *
			 * @return bool
			 */
			return apply_filters(
				'learndash_stripe_is_on_payments_setting_page',
				SuperGlobals::get_get_var( 'page' ) === 'learndash_lms_payments'
			);
		}

		/**
		 * Generates a connect url.
		 *
		 * @param string $return_url The url to return to after connection. Defaults to the current page.
		 *
		 * @return string
		 */
		public static function generate_connect_url( $return_url = '' ): string {
			if ( empty( $return_url ) ) {
				// remove any subfolder from the home url, if present.
				$url_parsed = wp_parse_url( home_url() );
				$return_url = $url_parsed['scheme'] . '://' . $url_parsed['host'] . add_query_arg( array() ); // @phpstan-ignore-line -- home url is safe.
			}

			$args = array(
				'stripe_action' => 'connect',
				'return_url'    => rawurlencode( $return_url ),
			);

			return add_query_arg(
				$args,
				esc_url_raw( self::CONNECT_SERVER_URL )
			);
		}

		/**
		 * Generates Stripe disconnect url.
		 *
		 * @return string
		 */
		private function generate_disconnect_url(): string {
			// remove any subfolder from the home url, if present.
			$url_parsed = wp_parse_url( home_url() );
			$return_url = $url_parsed['scheme'] . '://' . $url_parsed['host'] . add_query_arg( array() ); // @phpstan-ignore-line -- home url is safe.

			$args = array(
				'stripe_action'  => 'disconnect',
				'stripe_user_id' => $this->setting_option_values['account_id'],
				'return_url'     => rawurlencode( $return_url ),
			);

			return add_query_arg(
				$args,
				esc_url_raw( self::CONNECT_SERVER_URL )
			);
		}

		/**
		 * Clean up Stripe Connect customer_id metadata from users.
		 */
		private function cleanup_stripe_connect_customer_id() {
			global $wpdb;
			$sql = $wpdb->prepare(
				"DELETE FROM $wpdb->usermeta
								WHERE meta_key IN ( %s, %s )",
				self::STRIPE_CUSTOMER_ID_META_KEY,
				self::STRIPE_CUSTOMER_ID_META_KEY_TEST
			);
			$wpdb->query( $sql ); // phpcs:ignore
		}

		/**
		 * Handle connection.
		 */
		private function handle_connection_request(): void {
			if (
				current_user_can( 'manage_options' )
				&& isset( $_GET['ld_stripe_connected'] )
				&& self::STRIPE_RETURNED_SUCCESS === intval( $_GET['ld_stripe_connected'] ) // phpcs:ignore
			) {
				$this->load_settings_values();

				// check if account connected is same of last time.

				$old_account_id = $this->setting_option_values['last_account_id'] ?? '';
				$new_account_id = isset( $_GET['stripe_user_id'] ) ? sanitize_text_field( wp_unslash( $_GET['stripe_user_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				if ( $old_account_id !== $new_account_id ) {
					$this->cleanup_stripe_connect_customer_id(); // clear customer_id metadata from users.
				}

				// Update settings.

				$this->setting_option_values['enabled']              = 'yes';
				$this->setting_option_values['account_id']           = $new_account_id;
				$this->setting_option_values['secret_key_live']      = sanitize_text_field( $_GET['stripe_access_token'] ); // phpcs:ignore
				$this->setting_option_values['secret_key_test']      = sanitize_text_field( $_GET['stripe_access_token_test'] ); // phpcs:ignore
				$this->setting_option_values['publishable_key_live'] = sanitize_text_field( $_GET['stripe_publishable_key'] ); // phpcs:ignore
				$this->setting_option_values['publishable_key_test'] = sanitize_text_field( $_GET['stripe_publishable_key_test'] ); // phpcs:ignore

				$this->save_settings_values();

				// Redirect to the same page to remove query args.

				$reload_url = remove_query_arg(
					array( 'ld_stripe_connected', 'ld_stripe_disconnected', 'stripe_user_id', 'stripe_access_token', 'stripe_access_token_test', 'stripe_publishable_key', 'stripe_publishable_key_test', 'ld_stripe_error', 'error_code', 'error_message' )
				);

				$reload_url = add_query_arg(
					[
						'ld_stripe_connected'       => self::STRIPE_RETURNED_AND_PROCESSED_SUCCESS,
						'ld_stripe_connected_nonce' => wp_create_nonce( Connection_Handler::$ajax_action_post_connect ) ,
					],
					$reload_url
				);

				learndash_safe_redirect( $reload_url );
			}
		}

		/**
		 * Handle disconnection.
		 */
		private function handle_disconnection_request(): void {
			if (
				current_user_can( 'manage_options' ) &&
				( isset( $_GET['ld_stripe_disconnected'] ) && self::STRIPE_RETURNED_SUCCESS === intval( $_GET['ld_stripe_disconnected'] ) ) ||
				( current_user_can( 'manage_options' ) && isset( $_GET['ld_stripe_error'] ) && 1 === intval( $_GET['ld_stripe_error'] ) && ! isset( $_GET['ld_stripe_disconnected'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			) {
				$this->load_settings_values();

				$this->setting_option_values['last_account_id']      = isset( $this->setting_option_values['account_id'] ) ? $this->setting_option_values['account_id'] : '';
				$this->setting_option_values['account_id']           = '';
				$this->setting_option_values['publishable_key_live'] = '';
				$this->setting_option_values['secret_key_live']      = '';
				$this->setting_option_values['publishable_key_test'] = '';
				$this->setting_option_values['secret_key_test']      = '';

				$this->save_settings_values();

				$reload_url = remove_query_arg( array( 'ld_stripe_connected' ) );
				$reload_url = add_query_arg(
					array( 'ld_stripe_disconnected' => self::STRIPE_RETURNED_AND_PROCESSED_SUCCESS ),
					$reload_url
				);

				learndash_safe_redirect( $reload_url );
			}
		}

		/**
		 * Check if Stripe is connected.
		 *
		 * @return boolean true if connected, false otherwise.
		 */
		public static function is_stripe_connected(): bool {
			$options = LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Section_Stripe_Connect' );
			return ( isset( $options['account_id'] ) && ! empty( $options['account_id'] ) );
		}

		/**
		 * Return the stripe Webhook notice
		 *
		 * @return string
		 */
		public static function get_stripe_webhook_notice() {
			return __( 'Your Stripe Webhooks have been configured. You can test this connection in your LearnDash Stripe settings area if needed.', 'learndash' );
		}
	}

	add_action(
		'learndash_settings_sections_init',
		array( LearnDash_Settings_Section_Stripe_Connect::class, 'add_section_instance' )
	);
}
