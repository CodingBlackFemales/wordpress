<?php
/**
 * LearnDash Lesson Bulk Edit.
 *
 * @since 4.2.0
 *
 * @package LearnDash\Bulk_Edit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	class_exists( 'Learndash_Admin_Bulk_Edit_Action' ) &&
	! class_exists( 'Learndash_Admin_Bulk_Edit_Action_Lessons' )
) {
	/**
	 * Lessons Bulk Edit Class.
	 *
	 * @since 4.2.0
	 */
	class Learndash_Admin_Bulk_Edit_Action_Lessons extends Learndash_Admin_Bulk_Edit_Action {
		/**
		 * Lesson display metabox.
		 *
		 * @since 4.2.0
		 *
		 * @var LearnDash_Settings_Metabox_Lesson_Display_Content
		 */
		private $metabox_display_content;

		/**
		 * Constructor.
		 *
		 * @since 4.2.0
		 *
		 * @param LearnDash_Settings_Metabox_Lesson_Display_Content $metabox_display_content Lesson display metabox.
		 */
		public function __construct( LearnDash_Settings_Metabox_Lesson_Display_Content $metabox_display_content ) {
			$this->metabox_display_content = $metabox_display_content;
			$this->metabox_display_content->load_settings_values();
			$this->metabox_display_content->load_settings_fields();
		}

		/**
		 * Returns a tab name.
		 *
		 * @since 4.2.0
		 *
		 * @return string
		 */
		public function get_tab_name(): string {
			return LearnDash_Custom_Label::get_label( 'lessons' );
		}

		/**
		 * Returns a post type.
		 *
		 * @since 4.2.0
		 *
		 * @return string
		 */
		public function get_post_type(): string {
			return LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::LESSON );
		}

		/**
		 * Returns supported field names.
		 *
		 * @since 4.2.0
		 *
		 * @return array
		 */
		protected function get_supported_field_names(): array {
			return array(
				'lesson_video_auto_start',
				'lesson_video_show_controls',
				'lesson_video_focus_pause',
				'lesson_video_track_time',
			);
		}

		/**
		 * Updates the post field.
		 *
		 * @since 4.2.0
		 *
		 * @param int    $post_id     Post ID.
		 * @param string $field_name  Field name.
		 * @param string $field_value Field value.
		 *
		 * @return void
		 */
		protected function update_post_field( int $post_id, string $field_name, string $field_value ): void {
			learndash_update_setting( $post_id, $field_name, $field_value );
		}

		/**
		 * Inits filters.
		 *
		 * @since 4.2.0
		 *
		 * @return void
		 */
		protected function init_filters(): void {
			$lesson_label = LearnDash_Custom_Label::get_label( LDLMS_Post_Types::LESSON );

			$this->filters = array(
				Learndash_Admin_Filter_Factory::create_filter(
					Learndash_Admin_Filters::TYPE_POST_ID,
					$lesson_label,
					$this->get_select_ajax_query_data_for_post_type( $this->get_post_type() )
				),
				Learndash_Admin_Filter_Factory::create_filter(
					Learndash_Admin_Filters::TYPE_POST_TITLE,
					$lesson_label
				),
				Learndash_Admin_Filter_Factory::create_filter(
					Learndash_Admin_Filters::TYPE_POST_STATUS,
					$lesson_label
				),
				Learndash_Admin_Filter_Factory::create_filter(
					Learndash_Admin_Filters::TYPE_META_SWITCH,
					'_sfwd-lessons',
					esc_html__( 'Video Progression', 'learndash' ),
					'lesson_video_enabled'
				),
				Learndash_Admin_Filter_Factory::create_filter(
					Learndash_Admin_Filters::TYPE_META_SWITCH,
					'_sfwd-lessons',
					esc_html__( 'Video Resume', 'learndash' ),
					'lesson_video_track_time'
				),
			);

			// if shared steps is enabled then add the simple filter.

			$shared_steps = LearnDash_Settings_Section::get_section_setting(
				'LearnDash_Settings_Courses_Builder',
				'shared_steps'
			);

			if ( 'yes' !== $shared_steps ) {
				$this->filters[] = Learndash_Admin_Filter_Factory::create_filter(
					Learndash_Admin_Filters::TYPE_META_SELECT_AJAX,
					'course_id',
					LearnDash_Custom_Label::get_label( 'course' ),
					$this->get_select_ajax_query_data_for_post_type(
						LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::COURSE )
					)
				);
			} else {
				$this->filters[] = Learndash_Admin_Filter_Factory::create_filter(
					Learndash_Admin_Filters::TYPE_SHARED_STEPS,
					$this->get_select_ajax_query_data_for_post_type(
						LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::COURSE )
					)
				);
			}
		}

		/**
		 * Inits fields.
		 *
		 * @since 4.2.0
		 *
		 * @return void
		 */
		public function init_fields(): void {
			$metabox_fields = array_intersect_key(
				$this->metabox_display_content->get_settings_metabox_fields(),
				array_flip( $this->get_supported_field_names() )
			);

			$this->fields = array();

			foreach ( $metabox_fields as $field ) {
				$this->fields[] = new Learndash_Admin_Bulk_Edit_Field( $field['args'] );
			}
		}
	}
}
