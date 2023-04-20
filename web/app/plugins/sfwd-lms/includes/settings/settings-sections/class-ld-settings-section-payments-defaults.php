<?php
/**
 * LearnDash Settings Section for Payments Defaults Configurations Metabox.
 *
 * @since 4.1.0
 * @package LearnDash\Settings\Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Payments_Defaults' ) ) ) {

	/**
	 * Class LearnDash Settings Section for Payments Defaults Configurations Metabox.
	 *
	 * @since 4.1.0
	 */
	class LearnDash_Settings_Section_Payments_Defaults extends LearnDash_Settings_Section {

		/**
		 * Protected constructor for class
		 *
		 * @since 4.1.0
		 */
		protected function __construct() {
			$this->settings_page_id = 'learndash_lms_payments';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_payments_defaults';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_payments_defaults';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_payments_defaults';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Default Payments Configurations', 'learndash' );

			parent::__construct();
		}

		/**
		 * Initialize the metabox settings values.
		 *
		 * @since 4.1.0
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			// trying to get old currency data.
			if ( ! isset( $this->setting_option_values['currency'] ) || empty( $this->setting_option_values['currency'] ) ) {
				// Stripe add-on.
				$stripe_settings = get_option( 'learndash_stripe_settings' );
				if ( ! function_exists( 'is_plugin_active' ) ) {
					include_once ABSPATH . 'wp-admin/includes/plugin.php';
				}
				if ( is_plugin_active( 'learndash-stripe/learndash-stripe.php' ) && ! empty( $stripe_settings ) && ! empty( $stripe_settings['currency'] ) ) {
					$this->setting_option_values['currency'] = $stripe_settings['currency'];
				} else {
					// PayPal and Stripe Connect in LD core.
					$paypal_currency = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_PayPal', 'paypal_currency' );
					$stripe_currency = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Stripe_Connect', 'currency' );
					if ( 'on' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_PayPal', 'enabled' ) ) {
						$this->setting_option_values['currency'] = $paypal_currency;
					} else {
						$this->setting_option_values['currency'] = ! empty( $stripe_currency ) ? $stripe_currency : $paypal_currency;
					}
				}
			}
		}

		/**
		 * Initialize the metabox settings fields.
		 *
		 * @since 4.1.0
		 */
		public function load_settings_fields() {
			$this->setting_option_fields = array();

			$this->setting_option_fields['currency'] = array(
				'name'             => 'currency',
				'type'             => 'select',
				'label'            => esc_html__( 'Currency', 'learndash' ),
				'help_text'        => sprintf(
					// translators: placeholder: Link to ISO 4217.
					esc_html_x( 'Enter the currency code for transactions. It should be one currency code from the %s list.', 'placeholder: URL to ISO 4217', 'learndash' ),
					'<a href="https://en.wikipedia.org/wiki/ISO_4217#Active_codes" target="_blank">' . esc_html__( 'ISO 4217', 'learndash' ) . '</a>'
				),
				'value'            => $this->setting_option_values['currency'] ?? '',
				'class'            => 'regular-text',
				'display_callback' => array( $this, 'display_currency_selector' ),
			);

			$this->setting_option_fields['country'] = array(
				'name'  => 'country',
				'label' => __( 'Country', 'learndash' ),
				'type'  => 'hidden',
				'value' => $this->setting_option_values['country'] ?? '',
			);

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

			parent::load_settings_fields();
		}

		/**
		 * Display function for custom selectors.
		 *
		 * @since 4.4.0
		 *
		 * @param array $field_args An array of field arguments used to process the output.
		 *
		 * @return void
		 */
		public function display_currency_selector( $field_args = array() ): void {
			$html = '';

			/** This filter is documented in includes/settings/settings-fields/class-ld-settings-fields-checkbox-switch.php */
			$field_args = apply_filters( 'learndash_settings_field', $field_args );

			if ( ( isset( $field_args['type'] ) ) && ( ! empty( $field_args['type'] ) ) ) {
				$field_ref = LearnDash_Settings_Fields::get_field_instance( $field_args['type'] );
				if ( is_a( $field_ref, 'LearnDash_Settings_Fields' ) ) {

					/** This filter is documented in includes/settings/settings-fields/class-ld-settings-fields-checkbox-switch.php */
					$html = apply_filters( 'learndash_settings_field_html_before', '', $field_args );

					$html .= '<span class="ld-select">';

					$field_name  = $field_ref->get_field_attribute_name( $field_args, false );
					$field_id    = $field_ref->get_field_attribute_id( $field_args, false );
					$field_class = $field_ref->get_field_attribute_class( $field_args, false );

					$currency_country    = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Payments_Defaults', 'country' ) ?? '';
					$currency_codes_list = learndash_currency_codes_list();

					$select_html = '<select name="' . $field_name . '" class="' . $field_class . '" id="' . $field_id . '">';

					if ( empty( $currency_country ) ) {
						$old_currency = $this->setting_option_values['currency'];
						$select_html .= sprintf(
							'<option value="%1$s">%2$s</option>',
							$old_currency,
							! empty( $old_currency ) ? $old_currency : esc_html__( 'Select your country', 'learndash' )
						);
					}

					foreach ( $currency_codes_list as $code ) {
						$select_html .= sprintf(
							'<option %1$s value="%2$s" data-country="%3$s">%4$s</option>',
							( $currency_country === $code['country'] ? 'selected' : '' ),
							$code['currency_code'],
							$code['country'],
							$code['option_label']
						);
					}

					$select_html .= '</select>';

					if ( learndash_use_select2_lib() ) {
						$select_html = str_replace( '<select ', '<select data-ld-select2="1" ', $select_html );
					}
					$html .= $select_html;

					$html .= '</span>';

					/** This filter is documented in includes/settings/settings-fields/class-ld-settings-fields-checkbox-switch.php */
					$html = apply_filters( 'learndash_settings_field_html_after', $html, $field_args );
				}
			}

			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML
		}

		// End of functions.
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Section_Payments_Defaults::add_section_instance();
	}
);
