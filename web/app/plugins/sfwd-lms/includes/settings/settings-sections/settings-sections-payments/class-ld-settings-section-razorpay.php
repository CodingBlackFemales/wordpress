<?php
/**
 * LearnDash Settings Section for Razorpay.
 *
 * @since   4.2.0
 * @package \LearnDash\Settings\Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'LearnDash_Settings_Section' ) && ! class_exists( 'LearnDash_Settings_Section_Razorpay' ) ) {
	/**
	 * Class LearnDash Settings Section for Razorpay.
	 *
	 * @since 4.2.0
	 */
	class LearnDash_Settings_Section_Razorpay extends LearnDash_Settings_Section {
		const CUSTOMER_ID_META_KEY      = 'ld_razorpay_customer_id';
		const CUSTOMER_ID_TEST_META_KEY = 'ld_razorpay_test_customer_id';

		/**
		 * Protected constructor for class
		 *
		 * @since 4.2.0
		 */
		protected function __construct() {
			$this->settings_page_id = 'learndash_lms_payments';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_razorpay';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_razorpay';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_razorpay';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Razorpay Settings', 'learndash' );

			// Used to associate this section with the parent section.
			$this->settings_parent_section_key = 'settings_payments_list';

			$this->settings_section_listing_label = esc_html__( 'Razorpay', 'learndash' );

			parent::__construct();

			add_action( 'admin_notices', array( $this, 'webhook_notice' ) );
		}

		/**
		 * Initialize the metabox settings values.
		 *
		 * @since 4.2.0
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			if ( ! isset( $this->setting_option_values['payment_methods'] ) ) {
				$this->setting_option_values['payment_methods'] = array( 'card' );
			}

			if ( ! isset( $this->setting_option_values['test_mode'] ) ) {
				$this->setting_option_values['test_mode'] = '';
			}
		}

		/**
		 * Get the webhook URL.
		 *
		 * @since 4.2.0
		 */
		protected function get_webhook_url() {
			if ( ! empty( $this->setting_option_values['webhook_url'] ) ) {
				return $this->setting_option_values['webhook_url'];
			}

			return add_query_arg(
				array( 'learndash-integration' => 'razorpay' ),
				esc_url_raw( get_site_url() )
			);
		}

		/**
		 * Initialize the metabox settings fields.
		 *
		 * @since 4.2.0
		 */
		public function load_settings_fields() {
			$this->setting_option_fields = array(
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
					'name'                => 'test_mode',
					'label'               => esc_html__( 'Test Mode', 'learndash' ),
					'help_text'           => esc_html__( 'Check this box to enable test mode.', 'learndash' ),
					'type'                => 'checkbox-switch',
					'options'             => array(
						'1' => '',
						'0' => '',
					),
					'default'             => '',
					'value'               => $this->setting_option_values['test_mode'] ?? 0,
					'child_section_state' => ( '1' === $this->setting_option_values['test_mode'] ) ? 'open' : 'closed',
				),
				'publishable_key_test' => array(
					'name'           => 'publishable_key_test',
					'label'          => __( 'Test: Key Id', 'learndash' ),
					'type'           => 'text',
					'value'          => $this->setting_option_values['publishable_key_test'] ?? '',
					'parent_setting' => 'test_mode',
				),
				'secret_key_test'      => array(
					'name'           => 'secret_key_test',
					'label'          => __( 'Test: Key Secret', 'learndash' ),
					'type'           => 'text',
					'value'          => $this->setting_option_values['secret_key_test'] ?? '',
					'parent_setting' => 'test_mode',
				),
				'webhook_secret_test'  => array(
					'name'           => 'webhook_secret_test',
					'label'          => __( 'Test: Webhook Secret', 'learndash' ),
					'type'           => 'text',
					'value'          => $this->setting_option_values['webhook_secret_test'] ?? '',
					'parent_setting' => 'test_mode',
				),
				'publishable_key_live' => array(
					'name'  => 'publishable_key_live',
					'label' => __( 'Live: Key Id', 'learndash' ),
					'type'  => 'text',
					'value' => $this->setting_option_values['publishable_key_live'] ?? '',
				),
				'secret_key_live'      => array(
					'name'  => 'secret_key_live',
					'label' => __( 'Live: Key Secret', 'learndash' ),
					'type'  => 'text',
					'value' => $this->setting_option_values['secret_key_live'] ?? '',
				),
				'webhook_secret_live'  => array(
					'name'  => 'webhook_secret_live',
					'label' => __( 'Live: Webhook Secret', 'learndash' ),
					'type'  => 'text',
					'value' => $this->setting_option_values['webhook_secret_live'] ?? '',
				),
				'return_url'           => array(
					'name'      => 'return_url',
					'label'     => __( 'Return URL ', 'learndash' ),
					'help_text' => __(
						'Redirect the user to a specific URL after the purchase. Leave blank to let a user to be redirected to the course/group page.',
						'learndash'
					),
					'type'      => 'text',
					'value'     => $this->setting_option_values['return_url'] ?? '',
				),
				'webhook_url'          => array(
					'name'      => 'webhook_url',
					'type'      => 'text',
					'label'     => esc_html__( 'Webhook URL', 'learndash' ),
					'help_text' => esc_html__( 'You have to add this URL in the webhooks section of your Razorpay Dashboard.', 'learndash' ),
					'value'     => $this->get_webhook_url(),
					'class'     => 'regular-text',
					'attrs'     => defined( 'LEARNDASH_DEBUG' ) && LEARNDASH_DEBUG // @phpstan-ignore-line -- Constant can be true/false.
						? array()
						: array(
							'readonly' => 'readonly',
							'disable'  => 'disable',
						),
				),
			);

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
		 * @since 4.2.0
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
		 * Show notices.
		 */
		public function webhook_notice() {
			$is_settings_page   = isset( $_GET['section-payment'] ) && $this->settings_section_key === $_GET['section-payment']; // phpcs:ignore
			$webhook_secret_key = 'webhook_secret_' . ( '1' === $this->setting_option_values['test_mode'] ? 'test' : 'live' );

			if ( ! $is_settings_page || ! empty( $this->setting_option_values[ $webhook_secret_key ] ) ) {
				return;
			}
			?>
			<div class="notice notice-info is-dismissible">
				<h1>
					<?php esc_html_e( "Don't forget to configure your webhook on Razorpay.", 'learndash' ); ?>
				</h1>
				<p>
					<?php esc_html_e( 'In order for Razorpay to function properly, you must add a new webhook endpoint.', 'learndash' ); ?>
				</p>
				<p>
					<?php
					echo wp_kses_post(
						sprintf(
							// Translators: Webhooks dashboard url, Button title, Webhook url.
							_x(
								'To do this please visit the %1$s and click the %2$s button and paste the following URL: %3$s',
								'placeholders: Webhooks dashboard url, button title, webhook url',
								'learndash'
							),
							sprintf( '<a href="https://dashboard.razorpay.com/app/webhooks" target="_blank">%s</a>', __( 'Webhooks Section', 'learndash' ) ),
							sprintf( '<strong>%s</strong>', __( 'Add New Webhook', 'learndash' ) ),
							sprintf( '<strong>%s</strong>', $this->get_webhook_url() )
						)
					);
					?>
				</p>
				<p>
					<?php esc_html_e( 'After that you will have a webhook secret, so you can set it here.', 'learndash' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'Razorpay webhooks are required so LearnDash can communicate properly with the payment gateway to confirm payment completion, renewals, and more.', 'learndash' ); ?>
				</p>
			</div>
			<?php
		}
	}

	add_action(
		'learndash_settings_sections_init',
		array( LearnDash_Settings_Section_Razorpay::class, 'add_section_instance' )
	);
}
