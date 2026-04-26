<?php
/**
 * LearnDash Binary Selector Group Courses Enroll.
 *
 * @since 3.2.0
 *
 * @package LearnDash\Settings
 */

use LearnDash\Core\Validations\Validators\Metaboxes\Group_Courses_Auto_Enroll;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'Learndash_Binary_Selector_Group_Courses_Enroll' ) ) && ( class_exists( 'Learndash_Binary_Selector_Posts' ) ) ) {

	/**
	 *  Class LearnDash Binary Selector Group Courses Enroll.
	 *
	 * @since 3.2.0
	 * @uses Learndash_Binary_Selector_Posts
	 */
	class Learndash_Binary_Selector_Group_Courses_Enroll extends Learndash_Binary_Selector_Posts {

		/**
		 * Public constructor for class
		 *
		 * @since 3.2.0
		 *
		 * @param array $args Array of arguments for class.
		 */
		public function __construct( $args = array() ) {

			$this->selector_class = get_class( $this );

			$defaults = array(
				'group_id'           => 0,
				'post_type'          => 'sfwd-courses',
				'html_title'         => '<h3>' . sprintf(
					// translators: placeholder: Group, Courses.
					esc_html_x( '%1$s %2$s Auto-enroll', 'placeholder: Group, Courses', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'group' ),
					LearnDash_Custom_Label::get_label( 'courses' )
				) . '</h3>',
				'html_id'            => 'learndash_group_courses_enroll',
				'html_class'         => 'learndash_group_courses_enroll',
				'html_name'          => 'learndash_group_courses_enroll',
				'search_label_left'  => sprintf(
					// translators: placeholders: Group, Courses.
					esc_html_x( 'Search All %1$s %2$s', 'placeholders: Group, Courses', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'group' ),
					LearnDash_Custom_Label::get_label( 'courses' )
				),
				'search_label_right' => sprintf(
					// translators: placeholders: Group, Courses.
					esc_html_x( 'Search Assigned %1$s %2$s', 'placeholders: Group, Courses', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'group' ),
					LearnDash_Custom_Label::get_label( 'courses' )
				),
			);

			$args = wp_parse_args( $args, $defaults );

			$args['html_id']   = $args['html_id'] . '-' . $args['group_id'];
			$args['html_name'] = $args['html_name'] . '[' . $args['group_id'] . ']';

			$this->server_side_validator           = new Group_Courses_Auto_Enroll( $args['group_id'] );
			$this->server_side_validation_field_id = Group_Courses_Auto_Enroll::$field_courses_auto_enroll;

			parent::__construct( $args );
		}
	}
}
