<?php
/**
 * LearnDash Checkbox Switch / Toggle Settings Field.
 *
 * @since 3.0.0
 * @package LearnDash\Settings\Fields
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Fields' ) ) && ( ! class_exists( 'LearnDash_Settings_Fields_Checkbox_Switch' ) ) ) {

	/**
	 * Class LearnDash Checkbox Switch / Toggle Settings Field.
	 *
	 * @since 3.0.0
	 * @uses LearnDash_Settings_Fields
	 */
	class LearnDash_Settings_Fields_Checkbox_Switch extends LearnDash_Settings_Fields {

		/**
		 * Public constructor for class
		 *
		 * @since 3.0.0
		 */
		public function __construct() {
			$this->field_type = 'checkbox-switch';

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

			/**
			 * Filters setting field arguments.
			 *
			 * @param array $field_arguments An array of setting field arguments.
			 */
			$field_args = apply_filters( 'learndash_settings_field', $field_args );

			/**
			 * Filters the HTML output to be displayed before settings field.
			 *
			 * @param string $output         The HTML output to be displayed before setting field.
			 * @param array  $field_arguments An array of setting field arguments.
			 */
			$html = apply_filters( 'learndash_settings_field_html_before', '', $field_args );

			if ( ( isset( $field_args['options'] ) ) && ( ! empty( $field_args['options'] ) ) ) {
				if ( ( isset( $field_args['desc'] ) ) && ( ! empty( $field_args['desc'] ) ) ) {
					$html .= $field_args['desc'];
				}

				if ( ! isset( $field_args['class'] ) ) {
					$field_args['class'] = '';
				}
				$field_args['class'] .= ' ld-switch__input';

				$html .= '<fieldset>';
				$html .= $this->get_field_legend( $field_args );

				$sel_option_key   = $field_args['value'];
				$sel_option_label = '';
				if ( count( $field_args['options'] ) > 1 ) {
					if ( isset( $field_args['options'][ $sel_option_key ] ) ) {
						$sel_option_label = $field_args['options'][ $sel_option_key ];
					}
				} else {
					foreach ( $field_args['options'] as $option_key => $option_label ) {
						if ( is_string( $option_label ) ) {
							$sel_option_label = $option_label;
						} elseif ( ( is_array( $option_label ) ) && ( isset( $option_label['label'] ) ) && ( ! empty( $option_label['label'] ) ) ) {
							$sel_option_label = $option_label['label'];
						}
					}
				}

				$html .= ' <label for="' . esc_attr( $field_args['id'] ) . '" >';
				$html .= '<div class="ld-switch-wrapper">';
				$html .= '<span class="ld-switch';
				if ( isset( $field_args['attrs']['disabled'] ) ) {
					$html .= ' -disabled';
				}
				foreach ( $field_args['options'] as $option_key => $option_label ) {
					if ( ( ! empty( $option_key ) ) && ( isset( $option_label['tooltip'] ) ) && ( ! empty( $option_label['tooltip'] ) ) ) {
						$html .= ' tooltip';
					}
				}
				$html .= '">';

				$html .= '<input ';
				$html .= ' type="checkbox" autocomplete="off" ';
				$html .= $this->get_field_attribute_id( $field_args );
				$html .= $this->get_field_attribute_name( $field_args );
				$html .= $this->get_field_attribute_class( $field_args );
				$html .= $this->get_field_attribute_misc( $field_args );
				$html .= $this->get_field_attribute_required( $field_args );

				foreach ( $field_args['options'] as $option_key => $option_label ) {
					if ( ! empty( $option_key ) ) {
						$html .= ' value="' . esc_attr( $option_key ) . '" ';
						break;
					}
				}

				if ( ! empty( $sel_option_key ) ) {
					$html .= ' ' . checked( $sel_option_key, $field_args['value'], false ) . ' ';
				}

				$html_sub_fields = '';
				if ( ( isset( $field_args['inline_fields'] ) ) && ( ! empty( $field_args['inline_fields'] ) ) ) {
					foreach ( $field_args['inline_fields'] as $sub_field_key => $sub_fields ) {
						$html .= ' data-settings-inner-trigger="ld-settings-inner-' . esc_attr( $sub_field_key ) . '" ';

						if ( ( isset( $field_args['inner_section_state'] ) ) && ( 'open' === $field_args['inner_section_state'] ) ) {
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
				} else {
					$html .= ' data-settings-sub-trigger="ld-settings-sub-' . esc_attr( $field_args['name'] ) . '" ';
				}
				$html .= ' />';

				$html .= '<span class="ld-switch__track"></span>';
				$html .= '<span class="ld-switch__thumb"></span>';
				$html .= '<span class="ld-switch__on-off"></span>';

				foreach ( $field_args['options'] as $option_key => $option_label ) {
					if ( ( ! empty( $option_key ) ) && ( isset( $option_label['tooltip'] ) ) && ( ! empty( $option_label['tooltip'] ) ) ) {
						$html .= '<span class="tooltiptext">' . wp_kses_post( $option_label['tooltip'] ) . '</span>';
						break;
					}
				}
				$html .= '</span>'; // end of ld-switch.

				$html .= '<span class="label-text';
				if ( count( $field_args['options'] ) > 1 ) {
					 // phpcs:ignore CSpell: CSS changes needed when updating spelling.
					$html .= ' label-text-multple'; // cspell:disable-line.
				}
				$html .= '">';

				if ( count( $field_args['options'] ) > 1 ) {

					foreach ( $field_args['options'] as $option_key => $option_label ) {
						$label_display_state = '';
						if ( $option_key !== $sel_option_key ) {
							$label_display_state = ' style="display:none;" ';
						}
						if ( is_string( $option_label ) ) {
							$html .= '<span class="ld-label-text ld-label-text-' . esc_attr( $option_key ) . '"' . $label_display_state . '>' . wp_kses_post( $option_label ) . '</span>';
						} elseif ( ( is_array( $option_label ) ) && ( isset( $option_label['label'] ) ) && ( ! empty( $option_label['label'] ) ) ) {
							$html .= '<span class="ld-label-text ld-label-text-' . esc_attr( $option_key ) . '"' . $label_display_state . '>' . wp_kses_post( $option_label['label'] ) . '</span>';
						}
					}
				} else {
					if ( is_string( $sel_option_label ) ) {
							$html .= $sel_option_label;
					} elseif ( ( is_array( $sel_option_label ) ) && ( isset( $sel_option_label['label'] ) ) && ( ! empty( $sel_option_label['label'] ) ) ) {
						$html .= $sel_option_label['label'];
					}
				}
				$html .= '</span>';
				$html .= '</div></label>';

				$html .= $html_sub_fields;
				$html .= '</fieldset>';
			}

			/**
			 * Filters the HTML output to be displayed after settings field.
			 *
			 * @param string $output         The HTML output to be displayed after setting field.
			 * @param array  $field_arguments An array of setting field arguments.
			 */
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
		public function validate_section_field( $val, $key, $args = array() ) {
			if ( ( ! empty( $val ) ) && ( isset( $args['field']['type'] ) ) && ( $args['field']['type'] === $this->field_type ) ) {
				if ( isset( $args['field']['options'][ $val ] ) ) {
					return $val;
				} elseif ( isset( $args['field']['default'] ) ) {
					return $args['field']['default'];
				} else {
					return '';
				}
			}

			return $val;
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
				if ( in_array( $val, array( 'on', 'yes' ), true ) ) {
					$val = true;
				} else {
					$val = false;
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
				if ( true === $val ) {
					$val = 'on';
				} else {
					$val = '';
				}
			}
			return $val;
		}
	}
}
add_action(
	'learndash_settings_sections_fields_init',
	function() {
		LearnDash_Settings_Fields_Checkbox_Switch::add_field_instance( 'checkbox-switch' );
	}
);
