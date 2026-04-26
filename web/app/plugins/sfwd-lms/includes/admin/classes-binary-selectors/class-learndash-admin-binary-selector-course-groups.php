<?php
/**
 * LearnDash Binary Selector Course Groups.
 *
 * @since 2.2.1
 *
 * @package LearnDash\Settings
 */

use LearnDash\Core\Validations\Validators\Metaboxes\Course_Groups;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'Learndash_Binary_Selector_Course_Groups' ) ) && ( class_exists( 'Learndash_Binary_Selector_Posts' ) ) ) {
	/**
	 *  Class LearnDash Binary Selector Course Groups.
	 *
	 * @since 2.2.1
	 *
	 * @uses Learndash_Binary_Selector_Posts
	 */
	class Learndash_Binary_Selector_Course_Groups extends Learndash_Binary_Selector_Posts {

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
				'course_id'          => 0,
				'post_type'          => 'groups',
				'html_title'         => '<h3>' . sprintf(
					// translators: placeholders: Groups, Course.
					esc_html_x( '%1$s Using %2$s', 'placeholders: Groups, Course', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'groups' ),
					LearnDash_Custom_Label::get_label( 'course' )
				) . '</h3>',
				'html_id'            => 'learndash_course_groups',
				'html_class'         => 'learndash_course_groups',
				'html_name'          => 'learndash_course_groups',
				'search_label_left'  => sprintf(
					// translators: Groups.
					esc_html_x( 'Search All %s', 'placeholder: Groups', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'groups' )
				),
				'search_label_right' => sprintf(
					// translators: placeholders: Course, Groups.
					esc_html_x( 'Search %1$s %2$s', 'placeholders: Course, Groups', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'course' ),
					LearnDash_Custom_Label::get_label( 'groups' )
				),
			);

			$args = wp_parse_args( $args, $defaults );

			$args['html_id']   = $args['html_id'] . '-' . $args['course_id'];
			$args['html_name'] = $args['html_name'] . '[' . $args['course_id'] . ']';

			parent::__construct( $args );

			$this->server_side_validator           = new Course_Groups();
			$this->server_side_validation_field_id = Course_Groups::$field_groups;
		}
	}
}
