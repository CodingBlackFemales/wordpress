<?php
/**
 * LearnDash Select Settings Field.
 *
 * @since 3.0.0
 * @package LearnDash\Settings\Field
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Fields' ) ) && ( ! class_exists( 'LearnDash_Settings_Fields_Select' ) ) ) {
	/**
	 * LearnDash Select Settings Field.
	 *
	 * @since 3.0.0
	 * @uses LearnDash_Settings_Fields
	 */
	class LearnDash_Settings_Fields_Select extends LearnDash_Settings_Fields {

		/**
		 * Public constructor for class
		 *
		 * @since 3.0.0
		 */
		public function __construct() {
			$this->field_type = 'select';

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

			/** This filter is documented in includes/settings/settings-fields/class-ld-settings-fields-checkbox-switch.php */
			$field_args = apply_filters( 'learndash_settings_field', $field_args );

			/** This filter is documented in includes/settings/settings-fields/class-ld-settings-fields-checkbox-switch.php */
			$html = apply_filters( 'learndash_settings_field_html_before', '', $field_args );

			$html .= '<span class="ld-select">';
			$html .= '<select autocomplete="off" ';
			$html .= $this->get_field_attribute_type( $field_args );
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
			$html .= $this->get_field_sub_trigger( $field_args );
			$html .= $this->get_field_inner_trigger( $field_args );
			$html .= ' >';

			$html_sub_fields = '';

			if ( ( isset( $field_args['options'] ) ) && ( ! empty( $field_args['options'] ) ) ) {
				foreach ( $field_args['options'] as $option_key => $option_label ) {
					$selected_item = '';

					if ( is_array( $field_args['value'] ) ) {
						if ( in_array( $option_key, $field_args['value'], true ) ) {
							$selected_item = ' selected="" ';
						}
					} else {
						$selected_item = selected( $option_key, $field_args['value'], false );
					}

					if ( is_array( $option_label ) ) {

						// Support for <optgroup> within the select.
						if ( ( isset( $option_label['optgroup_options'] ) ) && ( ! empty( $option_label['optgroup_options'] ) ) ) {

							$html .= '<optgroup ';
							if ( ( isset( $option_label['optgroup_label'] ) ) && ( ! empty( $option_label['optgroup_label'] ) ) ) {
								$html .= ' label="' . esc_html( $option_label['optgroup_label'] ) . '" ';
							}
							$html .= '>';

							foreach ( $option_label['optgroup_options'] as $optgroup_option_key => $optgroup_option_label ) {
								$html .= '<option value="' . esc_attr( $optgroup_option_key ) . '" ' . $selected_item . '>' . wp_kses_post( $optgroup_option_label ) . '</option>';
							}
							$html .= '</optgroup>';
						} else {
							/**
							 * Support for nested inline fields. See 'quizModus' settings field in
							 * includes/settings/settings-metaboxes/class-ld-settings-metabox-quiz-display-content.php
							 * as example.
							 */
							if ( ( isset( $option_label['label'] ) ) && ( ! empty( $option_label['label'] ) ) ) {
								$html .= '<option value="' . esc_attr( $option_key ) . '" ' . $selected_item . '>' . wp_kses_post( $option_label['label'] ) . '</option>';
							}

							if ( ( isset( $option_label['inline_fields'] ) ) && ( ! empty( $option_label['inline_fields'] ) ) ) {
								foreach ( $option_label['inline_fields'] as $sub_field_key => $sub_fields ) {
									$html .= ' data-settings-inner-trigger="ld-settings-inner-' . esc_attr( $sub_field_key ) . '" ';

									if ( ( isset( $option_label['inner_section_state'] ) ) && ( 'open' === $option_label['inner_section_state'] ) ) {
										$inner_section_state = 'open';
									} else {
										$inner_section_state = 'closed';
									}
									$html_sub_fields .= '<div class="ld-settings-inner ld-settings-inner-' . esc_attr( $sub_field_key ) . ' ld-settings-inner-state-' . esc_attr( $inner_section_state ) . '">';

									$level = ob_get_level();
									ob_start();
									foreach ( $sub_fields as $sub_field ) {
										self::show_section_field_row( $sub_field );
									}
									$html_sub_fields .= learndash_ob_get_clean( $level );
									$html_sub_fields .= '</div>';
								}
							}
						}
					} elseif ( is_string( $option_label ) ) {
						$html .= '<option value="' . esc_attr( $option_key ) . '" ' . $selected_item . '>' . wp_kses_post( $option_label ) . '</option>';
					}
				}
			}

			$html .= '</select>';
			$html .= '</span>';
			$html .= $this->get_field_attribute_input_label( $field_args );

			$html .= $html_sub_fields;

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
				if ( ! empty( $val ) ) {
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
			if ( ( isset( $field_args['field']['type'] ) ) && ( $field_args['field']['type'] === $this->field_type ) ) {
				if ( isset( $field_args['field']['rest']['rest_args']['schema']['type'] ) ) {
					if ( 'integer' === $field_args['field']['rest']['rest_args']['schema']['type'] ) {
						$val = absint( $val );
					}
				}
			}
			return $val;
		}

		// end of functions.
	}
}
add_action(
	'learndash_settings_sections_fields_init',
	function() {
		LearnDash_Settings_Fields_Select::add_field_instance( 'select' );
	}
);
