<?php
/**
 * LearnDash Lessons (sfwd-lessons) Posts Listing.
 *
 * @since 3.0.0
 * @package LearnDash\Lesson\Listing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'Learndash_Admin_Posts_Listing' ) ) && ( ! class_exists( 'Learndash_Admin_Lessons_Listing' ) ) ) {

	/**
	 * Class LearnDash Lessons (sfwd-lessons) Posts Listing.
	 *
	 * @since 3.0.0
	 * @uses Learndash_Admin_Posts_Listing
	 */
	class Learndash_Admin_Lessons_Listing extends Learndash_Admin_Posts_Listing {

		/**
		 * Public constructor for class
		 *
		 * @since 3.0.0
		 */
		public function __construct() {
			$this->post_type = learndash_get_post_type_slug( 'lesson' );

			parent::__construct();
		}

		/**
		 * Called via the WordPress init action hook.
		 *
		 * @since 3.0.0
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
			);

			if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) ) {
				unset( $this->columns['course'] );
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

		// End of functions.
	}
}
new Learndash_Admin_Lessons_Listing();
