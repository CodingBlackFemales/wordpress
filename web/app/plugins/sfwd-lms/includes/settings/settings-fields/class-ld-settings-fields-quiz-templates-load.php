<?php
/**
 * LearnDash Quiz Load Templates Settings Field.
 *
 * @since 3.0.0
 * @package LearnDash\Settings\Field
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Fields' ) ) && ( ! class_exists( 'LearnDash_Settings_Fields_Quiz_Templates_Load' ) ) ) {
	/**
	 * Class LearnDash Quiz Load Templates Settings Field.
	 *
	 * @since 3.0.0
	 * @uses LearnDash_Settings_Fields
	 */
	class LearnDash_Settings_Fields_Quiz_Templates_Load extends LearnDash_Settings_Fields {

		/**
		 * Public constructor for class
		 *
		 * @since 3.0.0
		 */
		public function __construct() {
			$this->field_type = 'quiz-templates-load';

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

			if ( ( isset( $field_args['value'] ) ) && ( ! empty( $field_args['value'] ) ) ) {
				$template_loaded_id = absint( $field_args['value'] );
			} else {
				$template_loaded_id = 0;
			}

			$select_template_options = array();
			$template_type           = '';
			if ( isset( $field_args['template_type'] ) ) {
				$template_type = $field_args['template_type'];
			} else {
				global $post_type;
				if ( learndash_get_post_type_slug( 'quiz' ) === $post_type ) {
					$template_type = WpProQuiz_Model_Template::TEMPLATE_TYPE_QUIZ;
				} elseif ( learndash_get_post_type_slug( 'question' ) === $post_type ) {
					$template_type = WpProQuiz_Model_Template::TEMPLATE_TYPE_QUESTION;
				}
			}
			if ( ( isset( $template_type ) ) && ( '' !== $template_type ) ) {
				$template_mapper = new WpProQuiz_Model_TemplateMapper();
				$templates       = $template_mapper->fetchAll( $template_type, false );
				if ( ! empty( $templates ) ) {
					foreach ( $templates as $template ) {
						$select_template_options[ absint( $template->getTemplateId() ) ] = esc_html( $template->getName() );
					}
				}
			}

			$html .= '<div class="ld-settings-info-banner ld-settings-info-banner-alert">' . wpautop(
				sprintf(
					// translators: placeholders: Quiz.
					esc_html_x( 'Loading a template into this %s will replace ALL existing settings.', 'placeholders: Quiz.', 'learndash' ),
					learndash_get_custom_label( 'quiz' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
				)
			) . '</div>';

			$html .= '<div style="clear:both; margin-bottom: 20px;"></div>';

			$html .= '<span class="ld-select">';

			$html .= '<select autocomplete="off" ';
			$html .= $this->get_field_attribute_type( $field_args );
			$html .= ' name="templateLoadId" ';
			$html .= $this->get_field_attribute_id( $field_args );
			$html .= $this->get_field_attribute_class( $field_args );

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

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ( isset( $_GET['post'] ) ) && ( ! empty( $_GET['post'] ) ) && ( isset( $_GET['templateLoadId'] ) ) && ( ! empty( $_GET['templateLoadId'] ) ) ) {
				$template_url = remove_query_arg( 'templateLoadId' );
				$template_url = add_query_arg( 'currentTab', learndash_get_post_type_slug( 'quiz' ) . '-settings', $template_url );
				$html        .= '<option value="' . esc_url( $template_url ) . '">' . sprintf(
					// translators: Quiz Title.
					esc_html_x( 'Revert: %s', 'placeholder: Quiz Title', 'learndash' ),
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended
					get_the_title( intval( $_GET['post'] ) )
				) . '</option>';
			} else {
				if ( learndash_use_select2_lib() ) {
					$html .= '<option value="-1">' . esc_html__( 'Search or select a template…', 'learndash' ) . '</option>';
				} else {
					$html .= '<option value="">' . esc_html__( 'Select a Template to load', 'learndash' ) . '</option>';
				}
			}

			if ( ! empty( $select_template_options ) ) {
				foreach ( $select_template_options as $template_id => $template_name ) {
					if ( $template_id > 0 ) {
						$template_url = add_query_arg( 'templateLoadId', $template_id );
					} else {
						$template_url = $template_id;
					}

					$selected = '';
					if ( absint( $template_loaded_id ) === absint( $template_id ) ) {
						$selected = ' selected="selected" ';
					}

					$html .= '<option ' . $selected . ' value="' . esc_url( $template_url ) . '">' . esc_html( $template_name ) . '</option>';
				}
			}

			$html .= '</select>';
			$html .= '</span><br />';

			if ( 'yes' !== LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) ) {
				// cspell:disable-next-line.
				$html .= '<p><label for="templateload-option-replace-course"><input type="checkbox" id="templateload-option-replace-course" name="templateLoadReplaceCourse" />' . sprintf(
					// translators: placeholders: course, lessons, topics.
					esc_html_x( 'Replace associated steps (%1$s, %2$s, or %3$s) with values from template.', 'placeholders: course, lessons, topics.', 'learndash' ),
					learndash_get_custom_label_lower( 'course' ),
					learndash_get_custom_label_lower( 'lessons' ),
					learndash_get_custom_label_lower( 'topics' )
				) . '</label></p>';
			}

			$html .= '<input type="submit" name="templateLoad" value="' . esc_html__( 'load template', 'learndash' ) . '" class="button-primary"></p>';

			/** This filter is documented in includes/settings/settings-fields/class-ld-settings-fields-checkbox-switch.php */
			$html = apply_filters( 'learndash_settings_field_html_after', $html, $field_args );

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
		public function validate_section_field( $val, $key, $args = array() ) {
			if ( ( isset( $args['field']['type'] ) ) && ( $args['field']['type'] === $this->field_type ) ) {
				if ( ! empty( $val ) ) {
					$val = wp_check_invalid_utf8( strval( $val ) );
					if ( ! empty( $val ) ) {
						$val = sanitize_post_field( 'post_content', $val, 0, 'db' );
					}
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
		LearnDash_Settings_Fields_Quiz_Templates_Load::add_field_instance( 'quiz-templates-load' );
	}
);
