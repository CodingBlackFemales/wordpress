<?php
/**
 * LearnDash Binary Selector User Courses.
 *
 * @since 2.2.1
 * @package LearnDash\Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'Learndash_Binary_Selector_User_Courses' ) ) && ( class_exists( 'Learndash_Binary_Selector_Posts' ) ) ) {

	/**
	 * Class LearnDash Binary Selector User Courses.
	 *
	 * @since 2.2.1
	 * @uses Learndash_Binary_Selector_Posts
	 */
	class Learndash_Binary_Selector_User_Courses extends Learndash_Binary_Selector_Posts {

		/**
		 * Public constructor for class
		 *
		 * @since 2.2.1
		 *
		 * @param array $args Array of arguments for class.
		 */
		public function __construct( $args = array() ) {

			$this->selector_class = get_class( $this );

			$defaults = array(
				'user_id'            => 0,
				'post_type'          => 'sfwd-courses',
				'html_title'         => '<h3>' . sprintf(
					// translators: placeholder: Courses.
					esc_html_x( 'User Enrolled in %s', 'User Enrolled in Courses Label', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'courses' )
				) . '</h3>',
				'html_id'            => 'learndash_user_courses',
				'html_class'         => 'learndash_user_courses',
				'html_name'          => 'learndash_user_courses',
				'search_label_left'  => sprintf(
					// translators: placeholder: Courses.
					esc_html_x( 'Search All %s', 'Search All Courses Label', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'courses' )
				),
				'search_label_right' => sprintf(
					// translators: placeholder: Courses.
					esc_html_x( 'Search Enrolled %s', 'Search Enrolled Courses Label', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'courses' )
				),
			);

			$args = wp_parse_args( $args, $defaults );

			$args['html_id']   = $args['html_id'] . '-' . $args['user_id'];
			$args['html_name'] = $args['html_name'] . '[' . $args['user_id'] . ']';

			parent::__construct( $args );
		}
	}
}
