<?php
/**
 * LearnDash Settings Section for PayPal Checkout.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Settings\Sections
 */

use LearnDash\Core\Template\Template;
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use LearnDash\Core\Utilities\Countries;
use LearnDash\Core\Utilities\Cast;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Webhook_Client;
use StellarWP\Learndash\StellarWP\SuperGlobals\SuperGlobals;
use LearnDash\Core\App;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LearnDash_Settings_Section' ) ) {
	return;
}

/**
 * Class LearnDash Settings Section for PayPal Checkout.
 *
 * @since 4.25.0
 */
class LearnDash_Settings_Section_PayPal_Checkout extends LearnDash_Settings_Section {
	/**
	 * Protected constructor for class.
	 *
	 * @since 4.25.0
	 */
	protected function __construct() {
		$this->settings_page_id = 'learndash_lms_payments';

		// This is the 'option_name' key used in the wp_options table.
		$this->setting_option_key = 'learndash_settings_paypal_checkout';

		// This is the HTML form field prefix used.
		$this->setting_field_prefix = 'learndash_settings_paypal_checkout';

		// Used within the Settings API to uniquely identify this section.
		$this->settings_section_key = 'settings_paypal_checkout';

		// Section label/header.
		$this->settings_section_label = esc_html__( 'PayPal Checkout', 'learndash' );

		// Used to associate this section with the parent section.
		$this->settings_parent_section_key = 'settings_payments_list';

		$this->settings_section_listing_label = esc_html__( 'PayPal Checkout', 'learndash' );

		parent::__construct();
	}

	/**
	 * Initialize the metabox settings values.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public function load_settings_values() {
		parent::load_settings_values();

		if ( ! isset( $this->setting_option_values['payment_methods'] ) ) {
			$this->setting_option_values['payment_methods'] = [ 'paypal', 'card' ];
		}

		if ( ! isset( $this->setting_option_values['test_mode'] ) ) {
			$this->setting_option_values['test_mode'] = '0';
		}

		if ( ! isset( $this->setting_option_values['country'] ) ) {
			$this->setting_option_values['country'] = 'US';
		}
	}

	/**
	 * Initialize the metabox settings fields.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public function load_settings_fields() {
		$this->setting_option_fields = $this->get_disconnected_fields();

		// Update the options to reflect the connected state.
		if ( $this->account_is_connected() ) {
			// Those fields are not allowed to be changed when the account is connected.
			$this->setting_option_fields = Arr::set( $this->setting_option_fields, 'test_mode.type', 'hidden' );
			$this->setting_option_fields = Arr::set( $this->setting_option_fields, 'country.type', 'hidden' );

			// Remove the connection button label to match the design.
			$this->setting_option_fields = Arr::set( $this->setting_option_fields, 'connection_button.label_none', true );

			// Insert the description before all content.
			$this->setting_option_fields = Arr::insert_before_key(
				'connection_button',
				$this->setting_option_fields,
				[
					'description' => [
						'name'       => 'description',
						'type'       => 'html',
						'label'      => '',
						'label_none' => true,
						'value'      => sprintf(
							// translators: %s: Courses.
							esc_html__( 'This PayPal Gateway will handle all payment types, including trial periods and recurring payments. Customers can purchase %s directly on your site using debit or credit cards with no additional fees.', 'learndash' ),
							learndash_get_custom_label_lower( 'courses' )
						),
					],
				]
			);

			// Merge the disconnected fields with the existing fields.
			$this->setting_option_fields = array_merge(
				$this->setting_option_fields,
				$this->get_connected_fields()
			);
		}

		/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
		$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

		parent::load_settings_fields();
	}

	/**
	 * Renders the buttons for the PayPal Checkout settings.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public function render_buttons(): void {
		if ( $this->account_is_connected() ) {
			Template::show_admin_template(
				'modules/payments/gateways/paypal/buttons'
			);
		} else {
			Template::show_admin_template(
				'modules/payments/gateways/paypal/connect-button'
			);
		}
	}

	/**
	 * Renders a list of API granted scopes.
	 *
	 * @since 4.25.0
	 *
	 * @param array<string,mixed> $args The arguments for the field.
	 *
	 * @return void
	 */
	public function render_api_granted_scopes( array $args ): void {
		$values = array_filter(
			explode(
				',',
				Cast::to_string(
					Arr::get( $args, 'value', '' )
				)
			)
		);

		if ( empty( $values ) ) {
			return;
		}

		$name_map = [
			'PAYMENT'           => esc_html__( 'Payment', 'learndash' ),
			'REFUND'            => esc_html__( 'Refund', 'learndash' ),
			'PARTNER_FEE'       => esc_html__( 'Partner Fee', 'learndash' ),
			'VAULT'             => esc_html__( 'Vault', 'learndash' ),
			'BILLING_AGREEMENT' => esc_html__( 'Billing Agreement', 'learndash' ),
		];

		// Map the values to the names.
		$values = array_map(
			function ( $value ) use ( $name_map ) {
				return $name_map[ $value ] ?? $value;
			},
			$values
		);

		Template::show_admin_template(
			'modules/payments/gateways/paypal/text-list',
			[
				'list'  => $values,
				'class' => 'ld-paypal-checkout__list',
			]
		);
	}

	/**
	 * Renders a list of webhooks.
	 *
	 * @since 4.25.0
	 *
	 * @param array<string,mixed> $args The arguments for the field.
	 *
	 * @return void
	 */
	public function render_webhooks( array $args ): void {
		$webhook_client = App::get( Webhook_Client::class );

		if ( ! $webhook_client instanceof Webhook_Client ) {
			return;
		}

		$webhooks = $webhook_client->get_available_webhooks();

		if ( empty( $webhooks ) ) {
			$webhooks = [
				esc_html__( 'No webhooks found. Please reconnect your PayPal account.', 'learndash' ),
			];
		}

		Template::show_admin_template(
			'modules/payments/gateways/paypal/text-list',
			[
				'list'  => $webhooks,
				'class' => 'ld-paypal-checkout__list',
			]
		);
	}

	/**
	 * Filters the section saved values.
	 *
	 * @since 4.25.0
	 *
	 * @param array<string,mixed> $value                An array of setting fields values.
	 * @param array<string,mixed> $old_value            An array of setting fields old values.
	 * @param string              $settings_section_key Settings section key.
	 * @param string              $settings_screen_id   Settings screen ID.
	 *
	 * @return array<string,mixed>
	 */
	public function filter_section_save_fields( $value, $old_value, $settings_section_key, $settings_screen_id ): array {
		if ( $settings_section_key !== $this->settings_section_key ) {
			return $value;
		}

		if ( ! isset( $value['enabled'] ) ) {
			$value['enabled'] = '';
		}

		// If the settings are saved from the payments list, we need to update the old values.
		if ( '' !== SuperGlobals::get_post_var( 'learndash_settings_payments_list_nonce', '' ) ) {
			if ( ! is_array( $old_value ) ) {
				$old_value = [];
			}

			foreach ( $value as $value_idx => $value_val ) {
				$old_value[ $value_idx ] = $value_val;
			}

			$value = $old_value;
		}

		return $value;
	}

	/**
	 * Returns the fields for the disconnected state.
	 *
	 * @since 4.25.0
	 *
	 * @return array<string,mixed>
	 */
	protected function get_disconnected_fields(): array {
		return [
			'connection_button'         => [
				'name'             => 'connection_button',
				'type'             => 'text',
				'label'            => '',
				'value'            => null,
				'display_callback' => [ $this, 'render_buttons' ],
			],
			'enabled'                   => [
				'name'    => 'enabled',
				'type'    => 'checkbox-switch',
				'label'   => esc_html__( 'Active', 'learndash' ),
				'value'   => $this->setting_option_values['enabled'] ?? '',
				'options' => [
					'yes' => '',
					''    => '',
				],
			],
			'test_mode'                 => [
				'name'      => 'test_mode',
				'type'      => 'checkbox-switch',
				'label'     => esc_html__( 'Test Mode', 'learndash' ),
				'help_text' => esc_html__( 'Check this box to enable test mode.', 'learndash' ),
				'value'     => $this->setting_option_values['test_mode'] ?? '0',
				'options'   => [
					'1' => '',
					'0' => '',
				],
			],
			'payment_methods'           => [
				'name'      => 'payment_methods',
				'type'      => 'checkbox',
				'label'     => esc_html__( 'Payment Methods', 'learndash' ),
				'help_text' => esc_html__( 'Select the payment methods you want to enable.', 'learndash' ),
				'value'     => $this->setting_option_values['payment_methods'] ?? [],
				'options'   => [
					'paypal' => esc_html__( 'PayPal', 'learndash' ),
					'card'   => esc_html__( 'Credit Card', 'learndash' ),
				],
			],
			'country'                   => [
				'name'        => 'country',
				'type'        => 'select',
				'label'       => esc_html__( 'Account Country', 'learndash' ),
				'help_text'   => esc_html__( 'Select the country where your PayPal account is registered.', 'learndash' ),
				'value'       => $this->setting_option_values['country'] ?? 'US',
				'options'     => Countries::get_all(),
				'placeholder' => esc_html__( 'Select your country', 'learndash' ),
			],
			// Declare fields to allow saving the settings when the account is disconnected.
			'merchant_id'               => [
				'name'  => 'merchant_id',
				'type'  => 'hidden',
				'label' => null,
				'value' => $this->setting_option_values['merchant_id'] ?? '',
			],
			'account_id'                => [
				'name'  => 'account_id',
				'type'  => 'hidden',
				'label' => null,
				'value' => $this->setting_option_values['account_id'] ?? '',
			],
			'api_granted_scopes'        => [
				'name'  => 'api_granted_scopes',
				'type'  => 'hidden',
				'label' => null,
				'value' => $this->setting_option_values['api_granted_scopes'] ?? '',
			],
			'signup_hash'               => [
				'name'  => 'signup_hash',
				'type'  => 'hidden',
				'label' => null,
				'value' => $this->setting_option_values['signup_hash'] ?? '',
			],
			'client_id'                 => [
				'name'  => 'client_id',
				'type'  => 'hidden',
				'label' => null,
				'value' => $this->setting_option_values['client_id'] ?? '',
			],
			'client_secret'             => [
				'name'  => 'client_secret',
				'type'  => 'hidden',
				'label' => null,
				'value' => $this->setting_option_values['client_secret'] ?? '',
			],
			'supports_custom_payments'  => [
				'name'  => 'supports_custom_payments',
				'type'  => 'hidden',
				'label' => null,
				'value' => $this->setting_option_values['supports_custom_payments'] ?? '',
			],
			'merchant_id_in_paypal'     => [
				'name'  => 'merchant_id_in_paypal',
				'type'  => 'hidden',
				'label' => null,
				'value' => $this->setting_option_values['merchant_id_in_paypal'] ?? '',
			],
			'merchant_account_is_ready' => [
				'name'  => 'merchant_account_is_ready',
				'type'  => 'hidden',
				'label' => null,
				'value' => $this->setting_option_values['merchant_account_is_ready'] ?? '',
			],
			'merchant_account_verified' => [
				'name'  => 'merchant_account_verified',
				'type'  => 'hidden',
				'label' => null,
				'value' => $this->setting_option_values['merchant_account_verified'] ?? '',
			],
		];
	}

	/**
	 * Returns the fields for the connected state.
	 *
	 * @since 4.25.0
	 *
	 * @return array<string,mixed>
	 */
	protected function get_connected_fields(): array {
		return [
			'mode_info'               => [
				'name'  => 'mode_info',
				'type'  => 'html',
				'label' => esc_html__( 'PayPal Mode', 'learndash' ),
				'value' => $this->setting_option_values['test_mode']
					? esc_html__( 'Sandbox Mode (test)', 'learndash' )
					: esc_html__( 'Live Mode', 'learndash' ),
			],
			'country_info'            => [
				'name'  => 'country_info',
				'type'  => 'html',
				'label' => esc_html__( 'Account Country', 'learndash' ),
				'value' => Countries::get_name( $this->setting_option_values['country'] ?? 'US' ),
			],
			'connected_as'            => [
				'name'  => 'connected_as',
				'type'  => 'html',
				'label' => esc_html__( 'Connected as', 'learndash' ),
				'value' => $this->setting_option_values['merchant_id'] ?? '',
			],
			'account_info'            => [
				'name'  => 'account_info',
				'type'  => 'html',
				'label' => esc_html__( 'PayPal Account ID', 'learndash' ),
				'value' => $this->setting_option_values['account_id'] ?? '',
			],
			'api_granted_scopes_info' => [
				'name'             => 'api_granted_scopes_info',
				'type'             => 'text',
				'label'            => esc_html__( 'API Granted Scopes', 'learndash' ),
				'value'            => $this->setting_option_values['api_granted_scopes'] ?? '',
				'display_callback' => [ $this, 'render_api_granted_scopes' ],
			],
			'webhooks_info'           => [
				'name'             => 'webhooks_info',
				'type'             => 'text',
				'label'            => esc_html__( 'Webhooks', 'learndash' ),
				'value'            => '',
				'display_callback' => [ $this, 'render_webhooks' ],
			],
			'save_instructions'       => [
				'name'       => 'save_instructions',
				'type'       => 'html',
				'label'      => null,
				'label_none' => true,
				'value'      => sprintf(
					'<strong>%s</strong>',
					esc_html__( 'To make changes, please disconnect and reconnect your gateway.', 'learndash' )
				),
			],
			'return_url'              => [
				'name'              => 'return_url',
				'type'              => 'text',
				'label'             => esc_html__( 'Return URL', 'learndash' ),
				'help_text'         => esc_html__( 'Redirect the user to a specific URL after the purchase. Leave blank to let user remain on the Course page.', 'learndash' ),
				'value'             => $this->setting_option_values['return_url'] ?? '',
				'class'             => 'regular-text',
				'validate_callback' => function ( $value ) {
					return Cast::to_string( wp_http_validate_url( Cast::to_string( $value ) ) );
				},
			],
			'webhook_url'             => [
				'name'      => 'webhook_url',
				'type'      => 'text',
				'label'     => esc_html__( 'Webhook URL', 'learndash' ),
				'help_text' => esc_html__( 'PayPal webhooks are essential for payments to function correctly in LearnDash. We\'ll automatically configure them for you, but you can access the webhook URL here if needed.', 'learndash' ),
				'value'     => rest_url( 'learndash/v1/commerce/paypal/webhook' ),
				'class'     => 'regular-text',
				'attrs'     => [
					'readonly' => 'readonly',
					'disabled' => 'disabled',
				],
			],
		];
	}

	/**
	 * Checks if account is already connected.
	 *
	 * @since 4.25.0
	 *
	 * @return bool
	 */
	private function account_is_connected(): bool {
		return ! empty( $this->setting_option_values['merchant_id'] )
			&& ! empty( $this->setting_option_values['account_id'] )
			&& ! empty( $this->setting_option_values['client_id'] )
			&& ! empty( $this->setting_option_values['client_secret'] );
	}
}

add_action(
	'learndash_settings_sections_init',
	[
		LearnDash_Settings_Section_PayPal_Checkout::class,
		'add_section_instance',
	]
);
