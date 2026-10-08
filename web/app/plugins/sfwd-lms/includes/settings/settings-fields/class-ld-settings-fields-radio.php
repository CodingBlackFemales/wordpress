<?php
/**
 * LearnDash Input Radio Settings Field.
 *
 * @since 3.0.0
 * @package LearnDash\Settings\Field
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Fields' ) ) && ( ! class_exists( 'LearnDash_Settings_Fields_Radio' ) ) ) {

	/**
	 * Class LearnDash Input Radio Settings Field.
	 *
	 * @since 3.0.0
	 * @uses LearnDash_Settings_Fields
	 */
	class LearnDash_Settings_Fields_Radio extends LearnDash_Settings_Fields {

		/**
		 * Public constructor for class
		 *
		 * @since 3.0.0
		 */
		public function __construct() {
			$this->field_type = 'radio';

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

			if ( ( isset( $field_args['options'] ) ) && ( ! empty( $field_args['options'] ) ) ) {

				if ( ( isset( $field_args['desc'] ) ) && ( ! empty( $field_args['desc'] ) ) ) {
					$html .= $field_args['desc'];
				}

				if ( ! isset( $field_args['class'] ) ) {
					$field_args['class'] = '';
				}
				$field_args['class'] .= ' ld-radio-input';

				$html .= '<fieldset>';
				$html .= $this->get_field_legend( $field_args );

				foreach ( $field_args['options'] as $option_key => $option_label ) {

					$html .= '<p class="ld-radio-input-wrapper">';
					$html .= '<input autocomplete="off" ';

					$html .= $this->get_field_attribute_type( $field_args );
					$html .= ' id="' . esc_attr( $this->get_field_attribute_id( $field_args, false ) ) . '-' . esc_attr( $option_key ) . '"';

					$html .= $this->get_field_attribute_name( $field_args );
					$html .= $this->get_field_attribute_class( $field_args );
					$html .= $this->get_field_attribute_misc( $field_args );
					$html .= $this->get_field_attribute_required( $field_args );

					$html .= ' value="' . esc_attr( $option_key ) . '" ';

					$html .= ' ' . checked( $option_key, $field_args['value'], false ) . ' ';

					$html_sub_fields = '';
					if ( ( is_array( $option_label ) ) && ( ! empty( $option_label ) ) ) {
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

					$html .= ' />';

					$html .= '<label class="ld-radio-input__label" for="' . esc_attr( $field_args['id'] ) . '-' . esc_attr( $option_key ) . '" >';
					if ( is_string( $option_label ) ) {
						$html .= '<span>' . $option_label . '</span></label><p>';
					} elseif ( ( is_array( $option_label ) ) && ( ! empty( $option_label ) ) ) {
						if ( ( isset( $option_label['label'] ) ) && ( ! empty( $option_label['label'] ) ) ) {
							$html .= '<span>' . $option_label['label'] . '</span></label>';
						}
						$html .= '</p>';

						if ( ( isset( $option_label['description'] ) ) && ( ! empty( $option_label['description'] ) ) ) {
							$html .= '<p class="ld-radio-description">' . wp_kses_post( $option_label['description'] ) . '</p>';
						}
					}

					$html .= $html_sub_fields;
				}

				$html .= '</fieldset>';
			}

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
				if ( isset( $args['field']['options'][ $val ] ) ) {
					return $val;
				}
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
					if ( 'boolean' === $field_args['field']['rest']['rest_args']['schema']['type'] ) {
						if ( in_array( $val, array( 'on', 'yes' ), true ) ) {
							$val = true;
						} else {
							$val = false;
						}
					} elseif ( 'string' === $field_args['field']['rest']['rest_args']['schema']['type'] ) {
						if ( ( isset( $field_args['field']['rest']['rest_args']['schema']['enum'] ) ) && ( ! empty( $field_args['field']['rest']['rest_args']['schema']['enum'] ) ) ) {
							if ( ! in_array( $val, $field_args['field']['rest']['rest_args']['schema']['enum'], true ) ) {
								if ( isset( $field_args['field']['rest']['rest_args']['schema']['default'] ) ) {
									$val = $field_args['field']['rest']['rest_args']['schema']['default'];
								} elseif ( isset( $field_args['field']['default'] ) ) {
									$val = $field_args['field']['default'];
								}
							}
						}
					}
				}
			}
			return $val;
		}

		/**
		 * Convert REST submit value to internal Settings Field acceptable value.
		 *
		 * @since 3.3.0
		 *
		 * @param mixed  $val         Value from REST to be converted to internal value.
		 * @param string $key         Key field for value.
		 * @param array  $field_args Array of field args.
		 */
		public function rest_value_to_field_value( $val = '', $key = '', $field_args = array() ) {
			if ( ( isset( $field_args['field']['type'] ) ) && ( $field_args['field']['type'] === $this->field_type ) ) {
				if ( 'boolean' === $field_args['field']['rest']['rest_args']['schema']['type'] ) {
					if ( true === $val ) {
						$val = 'on';
					} else {
						$val = '';
					}
				} elseif ( 'string' === $field_args['field']['rest']['rest_args']['schema']['type'] ) {
					if ( ( isset( $field_args['field']['rest']['rest_args']['schema']['enum'] ) ) && ( ! empty( $field_args['field']['rest']['rest_args']['schema']['enum'] ) ) ) {
						if ( ! in_array( $val, $field_args['field']['rest']['rest_args']['schema']['enum'], true ) ) {
							if ( isset( $field_args['field']['rest']['rest_args']['schema']['default'] ) ) {
								$val = $field_args['field']['rest']['rest_args']['schema']['default'];
							} elseif ( isset( $field_args['field']['default'] ) ) {
								$val = $field_args['field']['default'];
							}
						}
					}
				}
			}
			return $val;
		}

		// End of functions.
	}
}
add_action(
	'learndash_settings_sections_fields_init',
	function() {
		LearnDash_Settings_Fields_Radio::add_field_instance( 'radio' );
	}
);
