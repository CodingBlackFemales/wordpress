<?php
/**
 * LearnDash Media Upload Settings Field.
 *
 * @since 3.0.0
 * @package LearnDash\Settings\Field
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Fields' ) ) && ( ! class_exists( 'LearnDash_Settings_Fields_Media_Upload' ) ) ) {

	/**
	 * Class LearnDash Media Upload Settings Field.
	 *
	 * @since 3.0.0
	 * @uses LearnDash_Settings_Fields
	 */
	class LearnDash_Settings_Fields_Media_Upload extends LearnDash_Settings_Fields {

		/**
		 * Public constructor for class
		 *
		 * @since 3.0.0
		 */
		public function __construct() {
			$this->field_type = 'media-upload';

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

			if ( ( isset( $field_args['desc'] ) ) && ( ! empty( $field_args['desc'] ) ) ) {
				$html .= $field_args['desc'];
			}

			$html .= '<fieldset>';
			$html .= $this->get_field_legend( $field_args );

			$html .= '<div class="learndash-section-field-media-upload_wrapper" ';
			$html .= ' id="' . esc_attr( $this->get_field_attribute_id( $field_args, false ) ) . '_wrapper" ';
			$html .= '>';

			$default_img_url = LEARNDASH_LMS_PLUGIN_URL . 'assets/images/nologo.jpg'; // cspell:disable-line.

			$image_id  = 0;
			$image_url = $default_img_url;

			if ( isset( $field_args['value'] ) ) {
				$image_id = absint( $field_args['value'] );
			}

			if ( ! empty( $image_id ) ) {
				$image_url = wp_get_attachment_url( $image_id );
				if ( empty( $image_url ) ) {
					$image_id  = 0;
					$image_url = $default_img_url;
				}
			}

			$html .= '<div class="image-preview-wrapper">';
			$html .= '<img class="image-preview" src="' . esc_url( $image_url ) . '" style="max-width: 100%; max-height: 200px; border: 1px dashed #ccc;" data-default="' . esc_attr( $default_img_url ) . '"/>';
			$html .= '</div>';
			$html .= '<input type="button" class="button image-remove-button" title="' . esc_html__( 'remove image', 'learndash' ) . '" value="' . esc_html_x( 'X', 'placeholder: clear image', 'learndash' ) . '" />';
			$html .= '<input type="button" class="button image-upload-button" title="' . esc_html__( 'Select/upload image', 'learndash' ) . '"  value="' . esc_html__( 'Select image', 'learndash' ) . '" />';
			$html .= '<input ';
			$html .= ' type="hidden" ';
			$html .= $this->get_field_attribute_name( $field_args );
			$html .= $this->get_field_attribute_id( $field_args );
			$html .= $this->get_field_attribute_class( $field_args );
			$html .= $this->get_field_attribute_placeholder( $field_args );
			$html .= $this->get_field_attribute_misc( $field_args );
			$html .= $this->get_field_attribute_required( $field_args );

			if ( ( isset( $image_id ) ) && ( ! empty( $image_id ) ) ) {
				$html .= ' value="' . esc_attr( $image_id ) . '" ';
			} else {
				$html .= ' value="" ';
			}
			$html .= ' />';

			$html .= '</div>';
			$html .= '</fieldset>';

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
			if ( isset( $args['field']['options'][ $val ] ) ) {
				return $val;
			} elseif ( isset( $args['field']['default'] ) ) {
				return $args['field']['default'];
			} else {
				return '';
			}
		}
	}
}
add_action(
	'learndash_settings_sections_fields_init',
	function() {
		LearnDash_Settings_Fields_Media_Upload::add_field_instance( 'media-upload' );
	}
);
