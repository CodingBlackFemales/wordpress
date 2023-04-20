<?php
/**
 * LearnDash HTML Settings Field.
 *
 * @since 3.0.0
 * @package LearnDash\Settings\Field
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Fields' ) ) && ( ! class_exists( 'LearnDash_Settings_Fields_Html' ) ) ) {
	/**
	 * Class LearnDash HTML Settings Field.
	 *
	 * @since 3.0.0
	 * @uses LearnDash_Settings_Fields
	 */
	class LearnDash_Settings_Fields_Html extends LearnDash_Settings_Fields {

		/**
		 * Public constructor for class
		 *
		 * @since 3.0.0
		 */
		public function __construct() {
			$this->field_type = 'html';

			parent::__construct();
		}

		/**
		 * Function to crete the settings field.
		 *
		 * @since 3.0.0
		 *
		 * @param array $field_args An array of field arguments used to process the output.
		 *
		 * @return void
		 */
		public function create_section_field( $field_args = array() ) {

			/** This filter is documented in includes/settings/settings-fields/class-ld-settings-fields-checkbox-switch.php */
			$field_args = apply_filters( 'learndash_settings_field', $field_args );

			/** This filter is documented in includes/settings/settings-fields/class-ld-settings-fields-checkbox-switch.php */
			$html = apply_filters( 'learndash_settings_field_html_before', '', $field_args );

			/**
			 * Filters setting field HTML element.
			 *
			 * @param string $html_element The HTML element to be used for setting field.
			 */
			$field_type = apply_filters( 'learndash_settings_field_element_html', 'div' );
			$html      .= '<' . $field_type . ' ';
			$html      .= $this->get_field_attribute_id( $field_args );
			$html      .= $this->get_field_attribute_class( $field_args );
			$html      .= $this->get_field_attribute_misc( $field_args );
			$html      .= '>';

			if ( isset( $field_args['value'] ) ) {
				$html .= wptexturize( do_shortcode( $field_args['value'] ) );
			}

			$html .= '</' . $field_type . '>';

			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML
		}

		/**
		 * Default validation function. Should be overridden in Field subclass.
		 *
		 * @since 3.0.0
		 *
		 * @param mixed  $val Value to validate.
		 * @param string $key Key of value being validated.
		 * @param array  $args Array of field args.
		 *
		 * @return mixed $val validated value.
		 */
		public function validate_section_field( $val, $key = '', $args = array() ) {
			if ( ( ! empty( $val ) ) && ( isset( $args['field']['type'] ) ) && ( $args['field']['type'] === $this->field_type ) ) {
				return sanitize_textarea_field( $val );
			}

			return $val;
		}

		// End of functions.
	}
}
add_action(
	'learndash_settings_sections_fields_init',
	function() {
		LearnDash_Settings_Fields_Html::add_field_instance( 'html' );
	}
);
