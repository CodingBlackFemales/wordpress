<?php
/**
 * LearnDash Topics (sfwd-topic) Posts Listing.
 *
 * @since 3.0.0
 * @package LearnDash\Topic\Listing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'Learndash_Admin_Posts_Listing' ) ) && ( ! class_exists( 'Learndash_Admin_Topics_Listing' ) ) ) {

	/**
	 * Class LearnDash Topics (sfwd-topic) Posts Listing.
	 *
	 * @since 3.0.0
	 * @uses Learndash_Admin_Posts_Listing
	 */
	class Learndash_Admin_Topics_Listing extends Learndash_Admin_Posts_Listing {

		/**
		 * Public constructor for class
		 *
		 * @since 3.0.0
		 */
		public function __construct() {
			$this->post_type = learndash_get_post_type_slug( 'topic' );

			parent::__construct();
		}

		/**
		 * Called via the WordPress init action hook.
		 *
		 * @since 3.2.3
		 */
		public function listing_init() {
			if ( $this->listing_init_done ) {
				return;
			}

			$this->selectors = array(
				'course_id' => array(
					'type'                     => 'post_type',
					'post_type'                => learndash_get_post_type_slug( 'course' ),
					'show_all_value'           => '',
					'show_all_label'           => sprintf(
						// translators: placeholder: Courses.
						esc_html_x( 'All %s', 'placeholder: Courses', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'courses' )
					),
					'show_empty_value'         => 'empty',
					'show_empty_label'         => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '-- No %s --', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'listing_query_function'   => array( $this, 'listing_filter_by_course' ),
					'selector_filter_function' => array( $this, 'selector_filter_for_course' ),
					'selector_value_function'  => array( $this, 'selector_value_for_course' ),
				),
				'lesson_id' => array(
					'type'                     => 'post_type',
					'post_type'                => learndash_get_post_type_slug( 'lesson' ),
					'show_all_value'           => '',
					'show_all_label'           => sprintf(
						// translators: placeholder: Lessons.
						esc_html_x( 'All %s', 'placeholder: Lessons', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'lessons' )
					),
					'show_empty_value'         => 'empty',
					'show_empty_label'         => sprintf(
						// translators: placeholder: Lesson.
						esc_html_x( '-- No %s --', 'placeholder: Lesson', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'lesson' )
					),
					'listing_query_function'   => array( $this, 'listing_filter_by_lesson' ),
					'selector_filters'         => array( 'course_id' ),
					'selector_filter_function' => array( $this, 'selector_filter_for_lesson' ),
					'selector_value_function'  => array( $this, 'selector_value_integer' ),
				),
			);

			$this->columns = array(
				'course' => array(
					'label'    => sprintf(
						// translators: placeholder: Course.
						esc_html_x( 'Assigned %s', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'after'    => 'title',
					'display'  => array( $this, 'show_column_step_course' ),
					'required' => true,
				),
				'lesson' => array(
					'label'    => sprintf(
						// translators: placeholder: Lesson.
						esc_html_x( 'Assigned %s', 'placeholder: Lesson', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'lesson' )
					),
					'after'    => 'course',
					'display'  => array( $this, 'show_column_step_lesson' ),
					'required' => true,
				),
			);

			if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) ) {
				unset( $this->columns['course'] );
				unset( $this->columns['lesson'] );
				unset( $this->selectors['lesson_id']['show_empty_value'] );
				unset( $this->selectors['lesson_id']['show_empty_label'] );
			}

			// If Group Leader remove the selector empty option.
			if ( learndash_is_group_leader_user() ) {
				$gl_manage_courses_capabilities = learndash_get_group_leader_manage_courses();
				if ( 'advanced' !== $gl_manage_courses_capabilities ) {
					unset( $this->selectors['course_id']['show_empty_value'] );
					unset( $this->selectors['course_id']['show_empty_label'] );
				}
			}

			parent::listing_init();

			$this->listing_init_done = true;
		}

		/**
		 * Call via the WordPress load sequence for admin pages.
		 *
		 * @since 3.2.3
		 */
		public function on_load_listing() {
			if ( $this->post_type_check() ) {
				parent::on_load_listing();
			}
		}

		/**
		 * Filter the Topics Lessons selector filters.
		 *
		 * @since 3.2.3
		 *
		 * @param array  $query_args Query Args for Selector.
		 * @param string $post_type  Post Type slug for selector.
		 */
		public function filter_course_lessons_selector( $query_args = array(), $post_type = '' ) {
			global $sfwd_lms;

			// Check that the selector post type matches for out listing post type.
			if ( $post_type === $this->post_type ) {
				if ( isset( $query_args['post_type'] ) ) {
					if ( ( ( is_string( $query_args['post_type'] ) ) && ( learndash_get_post_type_slug( 'lesson' ) === $query_args['post_type'] ) ) || ( ( is_array( $query_args['post_type'] ) ) && ( in_array( learndash_get_post_type_slug( 'lesson' ), $query_args['post_type'], true ) ) ) ) {
						$course_selector = $this->get_selector( 'course_id' );
						if ( ( $course_selector ) && ( isset( $course_selector['selected'] ) ) && ( ! empty( $course_selector['selected'] ) ) ) {
							$lessons_items = $sfwd_lms->select_a_lesson_or_topic( absint( $course_selector['selected'] ), false, false );
							if ( ! empty( $lessons_items ) ) {
								$query_args['post__in'] = array_keys( $lessons_items );
								$query_args['orderby']  = 'post__in';
							} else {
								$query_args['post__in'] = array( 0 );
							}
						}
					}
				}
			}

			return $query_args;
		}

		// End of functions.
	}
}
new Learndash_Admin_Topics_Listing();
