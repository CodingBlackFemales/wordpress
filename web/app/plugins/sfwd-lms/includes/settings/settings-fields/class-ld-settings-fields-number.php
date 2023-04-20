<?php
/**
 * LearnDash Input Number Settings Field.
 *
 * @since 3.0.0
 * @package LearnDash\Settings\Field
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Fields' ) ) && ( ! class_exists( 'LearnDash_Settings_Fields_Number' ) ) ) {

	/**
	 * Class LearnDash Input Number Settings Field.
	 *
	 * @since 3.0.0
	 * @uses LearnDash_Settings_Fields
	 */
	class LearnDash_Settings_Fields_Number extends LearnDash_Settings_Fields {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			$this->field_type = 'number';

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

			$html .= '<input autocomplete="off" ';
			$html .= $this->get_field_attribute_type( $field_args );
			$html .= $this->get_field_attribute_name( $field_args );
			$html .= $this->get_field_attribute_id( $field_args );
			$html .= $this->get_field_attribute_class( $field_args );
			$html .= $this->get_field_attribute_placeholder( $field_args );
			$html .= $this->get_field_attribute_misc( $field_args );
			$html .= $this->get_field_attribute_required( $field_args );

			if ( isset( $field_args['value'] ) ) {
				$html .= ' value="' . esc_attr( $field_args['value'] ) . '" ';
			} else {
				$html .= ' value="" ';
			}

			$html .= ' />';

			$html .= $this->get_field_attribute_input_label( $field_args );
			$html .= $this->get_field_error_message( $field_args );

			/** This filter is documented in includes/settings/settings-fields/class-ld-settings-fields-checkbox-switch.php */
			$html = apply_filters( 'learndash_settings_field_html_after', $html, $field_args );

			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML
		}

		/**
		 * Validate field
		 *
		 * @since 3.0.0
		 *
		 * @param mixed  $val Value to validate.
		 * @param string $key Key of value being validated.
		 * @param array  $args Array of field args.
		 *
		 * @return integer value.
		 */
		public function validate_section_field( $val, $key = '', $args = array() ) {
			if ( ( isset( $args['field']['type'] ) ) && ( $args['field']['type'] === $this->field_type ) ) {
				// If empty check our settings.
				if ( ( '' === $val ) && ( isset( $args['field']['attrs']['can_empty'] ) ) && ( true === $args['field']['attrs']['can_empty'] ) ) {
					return $val;
				}

				if ( ! isset( $args['field']['attrs']['can_decimal'] ) ) {
					$args['field']['attrs']['can_decimal'] = 0;
				}

				if ( $args['field']['attrs']['can_decimal'] > 0 ) {
					$val = floatval( $val );
				} else {
					$val = intval( $val );
				}

				if ( ( isset( $args['field']['attrs']['min'] ) ) && ( ! empty( $args['field']['attrs']['min'] ) ) && ( $val < $args['field']['attrs']['min'] ) ) {
					return false;
				} elseif ( ( isset( $args['field']['attrs']['max'] ) ) && ( ! empty( $args['field']['attrs']['max'] ) ) && ( $val > $args['field']['attrs']['max'] ) ) {
					return false;
				}

				return $val;
			}

			return false;
		}
	}
}
add_action(
	'learndash_settings_sections_fields_init',
	function() {
		LearnDash_Settings_Fields_Number::add_field_instance( 'number' );
	}
);
