<?php
/**
 * LearnDash Binary Selector Group Courses Access Extending.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	! class_exists( 'Learndash_Binary_Selector_Group_Courses_Access_Extending' )
	&& class_exists( 'Learndash_Binary_Selector_Posts' )
) {
	/**
	 *  Class LearnDash Binary Selector Group Courses Access Extending.
	 *
	 * @since 4.8.0
	 */
	class Learndash_Binary_Selector_Group_Courses_Access_Extending extends Learndash_Binary_Selector_Posts {
		/**
		 * Constructor.
		 *
		 * @since 4.8.0
		 *
		 * @param array<mixed> $args Array of arguments for class.
		 */
		public function __construct( $args = array() ) {
			$this->selector_class = get_class( $this );

			$defaults = array(
				'group_id'           => 0,
				'post_type'          => LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::COURSE ),
				'html_id'            => 'group_courses_to_extend_access',
				'html_class'         => 'group_courses_to_extend_access',
				'html_name'          => 'group_courses_to_extend_access',
				'search_label_left'  => sprintf(
					// translators: placeholders: Group, Courses.
					esc_html_x( 'Search All %1$s %2$s', 'placeholders: Group, Courses', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'group' ),
					LearnDash_Custom_Label::get_label( 'courses' )
				),
				'search_label_right' => sprintf(
					// translators: placeholders: Courses.
					esc_html_x( 'Search %1$s That Will Be Affected', 'placeholders: Courses', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'courses' )
				),
			);

			$args = wp_parse_args( $args, $defaults );

			parent::__construct( $args );
		}
	}
}
