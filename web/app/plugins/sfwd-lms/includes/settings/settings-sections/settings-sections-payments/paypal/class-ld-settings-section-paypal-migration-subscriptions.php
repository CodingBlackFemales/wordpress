<?php
/**
 * LearnDash Settings Section for PayPal - Current Subscriptions Migration.
 *
 * @since 4.25.3
 *
 * @package LearnDash\Settings\Sections
 */

use StellarWP\Learndash\StellarWP\SuperGlobals\SuperGlobals;
use LearnDash\Core\Template\Template;
use LearnDash\Core\App;
use LearnDash\Core\Modules\Payments\Gateways\Paypal_Standard\Migration\Subscriptions;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\Arrays\Arr;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LearnDash_Settings_Section' ) ) {
	return;
}

/**
 * Class LearnDash Settings Section for PayPal - Current Subscriptions Migration.
 *
 * @since 4.25.3
 */
class LearnDash_Settings_Section_PayPal_Migration_Subscriptions extends LearnDash_Settings_Section {
	/**
	 * Protected constructor for class
	 *
	 * @since 4.25.3
	 */
	protected function __construct() {
		$this->settings_page_id = 'learndash_lms_payments';

		// This is the 'option_name' key used in the wp_options table.
		$this->setting_option_key = 'learndash_settings_paypal_migration_subscriptions';

		// This is the HTML form field prefix used.
		$this->setting_field_prefix = 'learndash_settings_paypal_migration_subscriptions';

		// Used within the Settings API to uniquely identify this section.
		$this->settings_section_key = 'settings_paypal_migration_subscriptions';

		// Controls metabox priority on page.
		$this->metabox_priority = 'high';

		// Section label/header.
		$this->settings_section_label = esc_html__( 'Current Subscriptions - PayPal Standard', 'learndash' );

		add_action(
			'learndash_settings_page_init',
			[ $this, 'learndash_settings_page_init' ],
			10
		);

		parent::__construct();
	}

	/**
	 * Registers the section with the settings page.
	 *
	 * @since 4.25.3
	 *
	 * @param string $settings_page_id Settings page ID.
	 *
	 * @return void
	 */
	public function learndash_settings_page_init( $settings_page_id ): void {
		if ( $settings_page_id !== 'learndash_lms_payments' ) {
			return;
		}

		add_filter(
			'learndash_show_section',
			[ $this, 'show_section' ],
			10,
			2
		);
	}

	/**
	 * Controls whether the PayPal Migration Current Subscriptions section is shown on the settings page.
	 *
	 * @since 4.25.3
	 *
	 * @param bool   $show_section Whether to show the section.
	 * @param string $section_key  The settings section key to be shown.
	 *
	 * @return bool Whether to show the section.
	 */
	public function show_section( bool $show_section, string $section_key ): bool {
		// Skip if not the PayPal Migration Current Subscriptions section.
		if ( $section_key !== $this->settings_section_key ) {
			return $show_section;
		}

		// Skip if not the PayPal Standard settings page.
		if ( SuperGlobals::get_get_var( 'section-payment' ) !== 'settings_paypal' ) {
			return false;
		}

		return true;
	}

	/**
	 * Loads the settings fields.
	 *
	 * @since 4.25.3
	 *
	 * @return void
	 */
	public function load_settings_fields() {
		$subscriptions = App::get( Subscriptions::class );

		if ( ! $subscriptions instanceof Subscriptions ) {
			return;
		}

		$this->setting_option_fields = [
			'include_migrated'    => [
				'name'       => 'include_migrated',
				'type'       => 'checkbox',
				'label_none' => true,
				'input_full' => true,
				'value'      => $this->setting_option_values['include_migrated'] ?? false,
				'label'      => null,
				'default'    => '',
				'options'    => [
					'on' => sprintf(
						// translators: %d: Total number of migrated users.
						esc_html__( 'Include migrated subscriptions (%d)', 'learndash' ),
						esc_html(
							number_format_i18n(
								$subscriptions->get_total_migrated_users()
							)
						)
					),
				],
			],
			'subscriptions_table' => [
				'name'             => 'subscriptions_table',
				'type'             => 'html',
				'label_none'       => true,
				'label'            => null,
				'value'            => '',
				'display_callback' => [ $this, 'show_current_subscriptions_table' ],
			],
		];

		parent::load_settings_fields();
	}

	/**
	 * Displays the current subscriptions table.
	 *
	 * @since 4.25.3
	 *
	 * @return void
	 */
	public function show_current_subscriptions_table(): void {
		$subscriptions = App::get( Subscriptions::class );

		if ( ! $subscriptions instanceof Subscriptions ) {
			return;
		}

		$include_migrated = 'on' === ( $this->setting_option_values['include_migrated'] ?? '' );
		$current_page     = Cast::to_int( SuperGlobals::get_get_var( 'paged' ) ?? 1 );
		$per_page         = 10; // Default items per page.

		Template::show_admin_template(
			'modules/payments/gateways/paypal-standard/current-subscriptions',
			[
				'subscriptions'       => $subscriptions->get_current_subscriptions( $current_page, $per_page, $include_migrated ),
				'current_page'        => $current_page,
				'total_items'         => $subscriptions->get_total_subscriptions( $include_migrated ),
				'per_page'            => $per_page,
				'paypal_account_link' => $this->get_paypal_account_link(),
			]
		);
	}

	/**
	 * Returns the PayPal account link.
	 *
	 * @since 4.25.3
	 *
	 * @return string The PayPal account link.
	 */
	private function get_paypal_account_link(): string {
		$paypal_settings = array_filter(
			Arr::wrap(
				LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Section_PayPal' )
			)
		);

		$is_sandbox = 'yes' === Cast::to_string( Arr::get( $paypal_settings, 'paypal_sandbox', 'no' ) );

		return $is_sandbox
			? 'https://www.sandbox.paypal.com/billing/subscriptions/'
			: 'https://www.paypal.com/billing/subscriptions/';
	}
}

add_action(
	'learndash_settings_sections_init',
	[
		LearnDash_Settings_Section_PayPal_Migration_Subscriptions::class,
		'add_section_instance',
	]
);
