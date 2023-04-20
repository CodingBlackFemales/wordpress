<?php
/**
 * LearnDash Shortcode Section for Video Progress placeholder [ld_video].
 *
 * @since 2.4.5
 * @package LearnDash\Settings\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Shortcodes_Section' ) ) && ( ! class_exists( 'LearnDash_Shortcodes_Section_ld_video' ) ) ) {
	/**
	 * Class LearnDash Shortcode Section for Video Progress placeholder [ld_video].
	 *
	 * @since 2.4.5
	 */
	class LearnDash_Shortcodes_Section_ld_video extends LearnDash_Shortcodes_Section /* phpcs:ignore PEAR.NamingConventions.ValidClassName.Invalid */ {

		/**
		 * Public constructor for class.
		 *
		 * @since 2.4.5
		 *
		 * @param array $fields_args Field Args.
		 */
		public function __construct( $fields_args = array() ) {
			$this->fields_args = $fields_args;

			$this->shortcodes_section_key  = 'ld_video';
			$this->shortcodes_section_type = 1;

			// translators: placeholders: Lessons, Topics.
			$this->shortcodes_section_description = sprintf( esc_html_x( 'This shortcode is used on %1$s and %2$s where Video Progression is enabled. The video player will be added above the content. This shortcode allows positioning the player elsewhere within the content. This shortcode does not take any parameters.', 'placeholders: Lessons, Topics', 'learndash' ), LearnDash_Custom_Label::get_label( 'lessons' ), LearnDash_Custom_Label::get_label( 'topics' ) );

			if ( learndash_get_post_type_slug( 'lesson' ) == $this->fields_args['post_type'] ) {
				// translators: placeholder: lesson.
				$this->shortcodes_section_title = sprintf( esc_html_x( '%s Video', 'placeholder: lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) );
			} elseif ( learndash_get_post_type_slug( 'topic' ) == $this->fields_args['post_type'] ) {
				// translators: placeholder: topic.
				$this->shortcodes_section_title = sprintf( esc_html_x( '%s Video', 'placeholder: topic', 'learndash' ), LearnDash_Custom_Label::get_label( 'topic' ) );
			}

			parent::__construct();
		}

		/**
		 * Initialize the shortcode fields.
		 *
		 * @since 2.4.5
		 */
		public function init_shortcodes_section_fields() {
			$this->shortcodes_option_fields = array();

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
			$this->shortcodes_option_fields = apply_filters( 'learndash_settings_fields', $this->shortcodes_option_fields, $this->shortcodes_section_key );

			parent::init_shortcodes_section_fields();
		}
	}
}
