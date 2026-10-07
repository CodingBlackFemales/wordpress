<?php
/**
 * LearnDash Timer Entry Settings Field.
 *
 * @since 3.0.0
 * @package LearnDash\Settings\Field
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Fields' ) ) && ( ! class_exists( 'LearnDash_Settings_Fields_Timer_Entry' ) ) ) {

	/**
	 * Class LearnDash Timer Entry Settings Field.
	 *
	 * @since 3.0.0
	 * @uses LearnDash_Settings_Fields
	 */
	class LearnDash_Settings_Fields_Timer_Entry extends LearnDash_Settings_Fields {

		/**
		 * Public constructor for class
		 *
		 * @since 3.0.0
		 */
		public function __construct() {
			$this->field_type = 'timer-entry';

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
			global $wp_locale;

			/** This filter is documented in includes/settings/settings-fields/class-ld-settings-fields-checkbox-switch.php */
			$field_args = apply_filters( 'learndash_settings_field', $field_args );

			/** This filter is documented in includes/settings/settings-fields/class-ld-settings-fields-checkbox-switch.php */
			$html = apply_filters( 'learndash_settings_field_html_before', '', $field_args );

			if ( ( isset( $field_args['value'] ) ) && ( ! empty( $field_args['value'] ) ) ) {
				$field_args['value'] = learndash_convert_lesson_time_time( $field_args['value'] );
				$value_hh            = gmdate( 'H', $field_args['value'] );
				$value_mn            = gmdate( 'i', $field_args['value'] );
				$value_ss            = gmdate( 's', $field_args['value'] );
			} else {
				$value_hh = '';
				$value_mn = '';
				$value_ss = '';
			}

			$field_name  = $this->get_field_attribute_name( $field_args, false );
			$field_class = $this->get_field_attribute_class( $field_args, false );
			$field_id    = $this->get_field_attribute_id( $field_args, false );

			$hour_field = '<span class="screen-reader-text">' . esc_html__( 'Hour', 'learndash' ) . '</span><input type="number" min="0" max="23" placeholder="HH" class="ld_date_hh ' . $field_class . '" name="' . $field_name . '[hh]" value="' . $value_hh . '" size="2" maxlength="2" autocomplete="off" />';

			$minute_field = '<span class="screen-reader-text">' . esc_html__( 'Minute', 'learndash' ) . '</span><input type="number" min="0" max="59" placeholder="MM" class="ld_date_mn ' . $field_class . '" name="' . $field_name . '[mn]" value="' . $value_mn . '" size="2" maxlength="2" autocomplete="off" />';

			$second_field = '<span class="screen-reader-text">' . esc_html__( 'Seconds', 'learndash' ) . '</span><input type="number" min="0" max="59" placeholder="SS" class="ld_date_ss ' . $field_class . '" name="' . $field_name . '[ss]" value="' . $value_ss . '" size="2" maxlength="2" autocomplete="off" />';

			$html .= '<div class="ld_timer_selector">' . sprintf(
				// translators: placeholders: Hour, Minute, Second.
				esc_html__( '%1$s:%2$s:%3$s', 'learndash' ),
				$hour_field,
				$minute_field,
				$second_field
			) . '</div>';

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
		public function validate_section_field( $val, $key, $args = array() ) {
			return absint( $val );
		}

		/**
		 * Get Settings Field Value
		 *
		 * @since 3.0.0
		 *
		 * @param mixed  $val       Value to validate.
		 * @param string $key       Key of value being validated.
		 * @param array  $args      Array of field args.
		 * @param array  $post_args Array of post args.
		 *
		 * @return mixed $val validated value.
		 */
		public function value_section_field( $val = '', $key = '', $args = array(), $post_args = array() ) {
			if ( ( isset( $args['field']['type'] ) ) && ( $args['field']['type'] === $this->field_type ) ) {

				if ( isset( $val['hh'] ) ) {
					$val_hh = absint( $val['hh'] );
				} else {
					$val_hh = 0;
				}

				if ( isset( $val['mn'] ) ) {
					$val_mn = absint( $val['mn'] );
				} else {
					$val_mn = 0;
				}

				if ( isset( $val['ss'] ) ) {
					$val_ss = absint( $val['ss'] );
				} else {
					$val_ss = 0;
				}

				$val_seconds = $val_ss + ( $val_mn * 60 ) + ( $val_hh * 60 * 60 );
				return $val_seconds;
			}

			return false;
		}

		/**
		 * Convert REST submit value to internal Settings Field acceptable value.
		 *
		 * @since 3.4.0
		 *
		 * @param mixed  $val        Value from REST to be converted to internal value.
		 * @param string $key        Key field for value.
		 * @param array  $field_args Array of field args.
		 */
		public function rest_value_to_field_value( $val = '', $key = '', $field_args = array() ) {
			$value_hh = 0;
			$value_mn = 0;
			$value_ss = 0;

			if ( ! empty( $val ) ) {
				$val      = learndash_convert_lesson_time_time( $val );
				$value_hh = gmdate( 'H', $val );
				$value_mn = gmdate( 'i', $val );
				$value_ss = gmdate( 's', $val );
			}
			$val = array(
				'hh' => $value_hh,
				'mn' => $value_mn,
				'ss' => $value_ss,
			);

			return $val;
		}

		// End of functions.
	}
}
add_action(
	'learndash_settings_sections_fields_init',
	function() {
		LearnDash_Settings_Fields_Timer_Entry::add_field_instance( 'timer-entry' );
	}
);
