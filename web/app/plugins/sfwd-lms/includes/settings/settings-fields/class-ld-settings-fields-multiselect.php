<?php
/**
 * LearnDash Multiselect Settings Field.
 *
 * @since 3.0.0
 * @package LearnDash\Settings\Field
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Fields' ) ) && ( ! class_exists( 'LearnDash_Settings_Fields_Multiselect' ) ) ) {
	/**
	 * Class LearnDash Multiselect Settings Field.
	 *
	 * @since 3.0.0
	 * @uses LearnDash_Settings_Fields
	 */
	class LearnDash_Settings_Fields_Multiselect extends LearnDash_Settings_Fields {

		/**
		 * Public constructor for class
		 *
		 * @since 3.0.0
		 */
		public function __construct() {
			$this->field_type = 'multiselect';

			parent::__construct();
		}

		/**
		 * Function to crete the settings field.
		 *
		 * @since 3.0.0
		 *
		 * @param array $field_args An array of field arguments used to process the output.
		 * @return void
		 */
		public function create_section_field( $field_args = array() ) {
			// Force multiple.
			$field_args['multiple'] = true;

			/** This filter is documented in includes/settings/settings-fields/class-ld-settings-fields-checkbox-switch.php */
			$field_args = apply_filters( 'learndash_settings_field', $field_args );

			/** This filter is documented in includes/settings/settings-fields/class-ld-settings-fields-checkbox-switch.php */
			$html = apply_filters( 'learndash_settings_field_html_before', '', $field_args );

			$html .= '<span class="ld-select ld-select-multiple">';
			$html .= '<select multiple autocomplete="off" ';
			$html .= $this->get_field_attribute_name( $field_args );
			$html .= $this->get_field_attribute_id( $field_args );
			$html .= $this->get_field_attribute_class( $field_args );
			$html .= $this->get_field_attribute_placeholder( $field_args );

			if ( learndash_use_select2_lib() ) {
				if ( ! isset( $field_args['attrs']['data-ld-select2'] ) ) {
					$html .= ' data-ld-select2="1" ';
				}
			}

			$html .= $this->get_field_attribute_misc( $field_args );
			$html .= $this->get_field_attribute_required( $field_args );

			$html .= ' >';

			if ( ( isset( $field_args['options'] ) ) && ( ! empty( $field_args['options'] ) ) ) {
				foreach ( $field_args['options'] as $option_key => $option_label ) {
					if ( ( '' === $option_key ) && ( learndash_use_select2_lib() ) ) {
						continue;
					}
					$selected_item = '';
					if ( is_string( $field_args['value'] ) ) {
						$selected_item = selected( $option_key, $field_args['value'], false );
					} elseif ( is_array( $field_args['value'] ) ) {
						if ( in_array( $option_key, $field_args['value'], true ) ) {
							$selected_item = ' selected="" ';
						}
					}

					$html .= '<option value="' . esc_attr( $option_key ) . '" ' . $selected_item . '>' . wp_kses_post( $option_label ) . '</option>';
				}
			}

			$html .= '</select>';
			$html .= '</span>';

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

				if ( ( is_array( $val ) ) && ( ! empty( $val ) ) ) {
					$val = array_map( $args['field']['value_type'], $val );
				} elseif ( ! empty( $val ) ) {
					$val = call_user_func( $args['field']['value_type'], $val );
				} else {
					$val = '';
				}

				return $val;
			}
			return false;
		}

		/**
		 * Convert Settings Field value to REST value.
		 *
		 * @since 3.3.0
		 *
		 * @param mixed           $val        Value from REST to be converted to internal value.
		 * @param string          $key        Key field for value.
		 * @param array           $field_args Array of field args.
		 * @param WP_REST_Request $request    Request object.
		 */
		public function field_value_to_rest_value( $val, $key, $field_args, WP_REST_Request $request ) {
			if ( ! is_array( $val ) ) {
				$val = array( $val );
			}
			return $val;
		}

		// end of functions.
	}
}
add_action(
	'learndash_settings_sections_fields_init',
	function() {
		LearnDash_Settings_Fields_Multiselect::add_field_instance( 'multiselect' );
	}
);
