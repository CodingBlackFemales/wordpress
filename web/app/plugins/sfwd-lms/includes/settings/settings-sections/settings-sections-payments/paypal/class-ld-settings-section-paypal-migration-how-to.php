<?php
/**
 * LearnDash Settings Section for PayPal - How To Migrate.
 *
 * @since 4.25.3
 *
 * @package LearnDash\Settings\Sections
 */

use LearnDash\Core\App;
use LearnDash\Core\Modules\Payments\Gateways\Paypal_Standard\Migration\Subscriptions;
use StellarWP\Learndash\StellarWP\SuperGlobals\SuperGlobals;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LearnDash_Settings_Section' ) ) {
	return;
}

/**
 * Class LearnDash Settings Section for PayPal - How To Migrate.
 *
 * @since 4.25.3
 */
class LearnDash_Settings_Section_PayPal_Migration_How_To extends LearnDash_Settings_Section {
	/**
	 * Protected constructor for class
	 *
	 * @since 4.25.3
	 */
	protected function __construct() {
		$this->settings_page_id = 'learndash_lms_payments';

		// This is the 'option_name' key used in the wp_options table.
		$this->setting_option_key = 'learndash_settings_paypal_migration_settings';

		// This is the HTML form field prefix used.
		$this->setting_field_prefix = 'learndash_settings_paypal_migration_settings';

		// Used within the Settings API to uniquely identify this section.
		$this->settings_section_key = 'settings_paypal_migration_settings';

		// Controls metabox priority on page.
		$this->metabox_priority = 'high';

		// Section label/header.
		$this->settings_section_label = esc_html__( 'How To Migrate - PayPal Standard', 'learndash' );

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
	 * Controls whether the PayPal Migration How To section is shown on the settings page.
	 *
	 * @since 4.25.3
	 *
	 * @param bool   $show_section Whether to show the section.
	 * @param string $section_key  The settings section key to be shown.
	 *
	 * @return bool Whether to show the section.
	 */
	public function show_section( bool $show_section, string $section_key ): bool {
		// Skip if not the PayPal Migration How To section.
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
			'instructions_line_1' => [
				'name'       => 'instructions_line_1',
				'type'       => 'html',
				'label'      => null,
				'label_none' => true,
				'value'      => __( '1. Enter your PayPal API username, password, and signature below and save.', 'learndash' ),
			],
			'api_username'        => [
				'name'      => 'api_username',
				'type'      => 'password',
				'label'     => esc_html__( 'API Username', 'learndash' ),
				'help_text' => esc_html__( 'Your PayPal API username.', 'learndash' ),
				'value'     => $this->setting_option_values['api_username'] ?? '',
			],
			'api_password'        => [
				'name'      => 'api_password',
				'type'      => 'password',
				'label'     => esc_html__( 'API Password', 'learndash' ),
				'help_text' => esc_html__( 'Your PayPal API password.', 'learndash' ),
				'value'     => $this->setting_option_values['api_password'] ?? '',
			],
			'api_signature'       => [
				'name'      => 'api_signature',
				'type'      => 'password',
				'label'     => esc_html__( 'API Signature', 'learndash' ),
				'help_text' => esc_html__( 'Your PayPal API signature.', 'learndash' ),
				'value'     => $this->setting_option_values['api_signature'] ?? '',
			],
			'instructions_line_2' => [
				'name'       => 'instructions_line_2',
				'type'       => 'html',
				'label'      => null,
				'label_none' => true,
				'value'      => sprintf(
					// translators: %s: Shortcode.
					__( '2. Add the provided shortcode %s to a page on your site.', 'learndash' ),
					'<code>[[ld_migrate_paypal_subscription]]</code>'
				),
			],
			'instructions_line_3' => [
				'name'       => 'instructions_line_3',
				'type'       => 'html',
				'label'      => null,
				'label_none' => true,
				'value'      => sprintf(
					// translators: %s: Copy all emails link.
					__( '3. Email all of your enrolled users with recurring payments using PayPal Standard. %s', 'learndash' ),
					sprintf(
						'<button class="learndash-copy-text button button-link" data-tooltip="%1$s" data-tooltip-default="%1$s" data-tooltip-success="%2$s" data-text="%3$s">%4$s</button>',
						__( 'Copy all emails', 'learndash' ),
						__( 'Copied!', 'learndash' ),
						implode( ', ', $subscriptions->get_user_emails() ),
						__( 'Copy all emails', 'learndash' )
					)
				),
			],
			'instructions_line_4' => [
				'name'       => 'instructions_line_4',
				'type'       => 'html',
				'label'      => null,
				'label_none' => true,
				'value'      => __( '4. Your users need to add their payment method and move to the new PayPal Vault connection through the shortcode\'s form.', 'learndash' ),
			],
			'instructions_line_5' => [
				'name'       => 'instructions_line_5',
				'type'       => 'html',
				'label'      => null,
				'label_none' => true,
				'value'      => __( '5. LearnDash will cancel the old subscription and start a new one with the provided payment method on the renewal date for each enrolled subscription for that user.', 'learndash' ),
			],
			'instructions_note'   => [
				'name'       => 'instructions_note',
				'type'       => 'html',
				'label'      => null,
				'label_none' => true,
				'value'      => sprintf(
					// translators: %s: Migration Guide link.
					__( 'Reference the %s with full instructions on how to use the shortcode and direct users through the process, including page and email content templates.', 'learndash' ),
					sprintf(
						'<a href="%s" target="_blank">%s</a>',
						'https://go.learndash.com/paypal-migration/',
						__( 'Migration Guide', 'learndash' )
					)
				),
			],
		];

		parent::load_settings_fields();
	}
}

add_action(
	'learndash_settings_sections_init',
	[
		LearnDash_Settings_Section_PayPal_Migration_How_To::class,
		'add_section_instance',
	]
);
