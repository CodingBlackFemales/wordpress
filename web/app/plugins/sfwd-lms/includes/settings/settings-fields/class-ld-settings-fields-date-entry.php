<?php
/**
 * LearnDash Date Entry Settings Field.
 *
 * @since 3.0.0
 * @package LearnDash\Settings\Field
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Fields' ) ) && ( ! class_exists( 'LearnDash_Settings_Fields_Date_Entry' ) ) ) {

	/**
	 * Class LearnDash Date Entry Settings Field.
	 *
	 * @since 3.0.0
	 * @uses LearnDash_Settings_Fields
	 */
	class LearnDash_Settings_Fields_Date_Entry extends LearnDash_Settings_Fields {

		/**
		 * Public constructor for class
		 *
		 * @since 3.0.0
		 */
		public function __construct() {
			$this->field_type = 'date-entry';

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

			$date_value = '';
			if ( isset( $field_args['value'] ) ) {
				if ( ! empty( $field_args['value'] ) ) {
					if ( ! is_numeric( $field_args['value'] ) ) {
						$date_value = learndash_get_timestamp_from_date_string( $field_args['value'] );
					} else {
						// If we have a timestamp we assume it is GMT. So we need to convert it to local.
						// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
						$value_ymd  = get_date_from_gmt( date( 'Y-m-d H:i:s', $field_args['value'] ), 'Y-m-d H:i:s' );
						$date_value = strtotime( $value_ymd );
					}
				}
			}

			if ( ! empty( $date_value ) ) {
				$value_jj = gmdate( 'd', $date_value );
				$value_mm = gmdate( 'm', $date_value );
				$value_aa = gmdate( 'Y', $date_value );
				$value_hh = gmdate( 'H', $date_value );
				$value_mn = gmdate( 'i', $date_value );
			} else {
				$value_jj = '';
				$value_mm = '';
				$value_aa = '';
				$value_hh = '';
				$value_mn = '';
			}

			$field_name  = $this->get_field_attribute_name( $field_args, false );
			$field_class = $this->get_field_attribute_class( $field_args, false );
			$field_id    = $this->get_field_attribute_id( $field_args, false );

			$month_field = '<span class="screen-reader-text">' . esc_html__( 'Month', 'learndash' ) . '</span><select class="ld_date_mm ' . $field_class . '" name="' . $field_name . '[mm]" ><option value="">' . esc_html__( 'MM', 'learndash' ) . '</option>';
			for ( $i = 1; $i < 13; $i++ ) {
				$monthnum     = zeroise( $i, 2 );
				$monthtext    = $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) );
				$month_field .= "\t\t\t" . '<option value="' . esc_attr( $monthnum ) . '" data-text="' . esc_attr( $monthtext ) . '" ' . selected( $monthnum, $value_mm, false ) . '>';
				// translators: placeholder: month number, month text.
				$month_field .= sprintf( esc_html_x( '%1$s-%2$s', 'placeholder: month number, month text', 'learndash' ), esc_html( $monthnum ), esc_html( $monthtext ) ) . "</option>\n";
			}
				$month_field .= '</select>';

			$day_field    = '<span class="screen-reader-text">' . esc_html__( 'Day', 'learndash' ) . '</span><input type="number" placeholder="DD" min="1" max="31" class="ld_date_jj ' . $field_class . '" name="' . $field_name . '[jj]" value="' . $value_jj . '" size="2" maxlength="2" autocomplete="off" />';
			$year_field   = '<span class="screen-reader-text">' . esc_html__( 'Year', 'learndash' ) . '</span><input  type="number" placeholder="YYYY" min="0000" max="9999" class="ld_date_aa ' . $field_class . '" name="' . $field_name . '[aa]" value="' . $value_aa . '" size="4" maxlength="4" autocomplete="off" />';
			$hour_field   = '<span class="screen-reader-text">' . esc_html__( 'Hour', 'learndash' ) . '</span><input type="number" min="0" max="23" placeholder="HH" class="ld_date_hh ' . $field_class . '" name="' . $field_name . '[hh]" value="' . $value_hh . '" size="2" maxlength="2" autocomplete="off" />';
			$minute_field = '<span class="screen-reader-text">' . esc_html__( 'Minute', 'learndash' ) . '</span><input type="number" min="0" max="59" placeholder="MN" class="ld_date_mn ' . $field_class . '" name="' . $field_name . '[mn]" value="' . $value_mn . '" size="2" maxlength="2" autocomplete="off" />';

			$html .= '<div class="ld_date_selector">' . sprintf(
				// translators: placeholders: Month Name, Day number, Year number, Hour number, Minute number.
				esc_html__( '%1$s %2$s , %3$s @ %4$s : %5$s', 'learndash' ),
				$month_field,
				$day_field,
				$year_field,
				$hour_field,
				$minute_field
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
			return sanitize_text_field( $val );
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
				if ( isset( $val['aa'] ) ) {
					$val_aa = intval( $val['aa'] );
				} else {
					$val_aa = 0;
				}

				if ( isset( $val['mm'] ) ) {
					$val_mm = intval( $val['mm'] );
				} else {
					$val_mm = 0;
				}

				if ( isset( $val['jj'] ) ) {
					$val_jj = intval( $val['jj'] );
				} else {
					$val_jj = 0;
				}

				if ( isset( $val['hh'] ) ) {
					$val_hh = intval( $val['hh'] );
				} else {
					$val_hh = 0;
				}

				if ( isset( $val['mn'] ) ) {
					$val_mn = intval( $val['mn'] );
				} else {
					$val_mn = 0;
				}

				if ( ( ! empty( $val_aa ) ) && ( ! empty( $val_mm ) ) && ( ! empty( $val_jj ) ) ) {
					$date_string = sprintf(
						'%04d-%02d-%02d %02d:%02d:00',
						intval( $val_aa ),
						intval( $val_mm ),
						intval( $val_jj ),
						intval( $val_hh ),
						intval( $val_mn )
					);

					$date_string_gmt = get_gmt_from_date( $date_string, 'Y-m-d H:i:s' );
					$val             = strtotime( $date_string_gmt );
				} else {
					$val = 0;
				}
				return $val;
			}

			return false;
		}
		// End of functions.
	}
}
add_action(
	'learndash_settings_sections_fields_init',
	function() {
		LearnDash_Settings_Fields_Date_Entry::add_field_instance( 'date-entry' );
	}
);
