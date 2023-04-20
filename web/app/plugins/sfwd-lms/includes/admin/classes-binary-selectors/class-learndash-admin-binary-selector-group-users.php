<?php
/**
 * LearnDash Binary Selector Group Users.
 *
 * @since 2.2.1
 * @package LearnDash\Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'Learndash_Binary_Selector_Group_Users' ) ) && ( class_exists( 'Learndash_Binary_Selector_Users' ) ) ) {

	/**
	 * Class LearnDash Binary Selector Group Users.
	 *
	 * @since 2.2.1
	 * @uses Learndash_Binary_Selector_Users
	 */
	class Learndash_Binary_Selector_Group_Users extends Learndash_Binary_Selector_Users {

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
				'group_id'           => 0,
				'html_title'         => '<h3>' . sprintf(
					// translators: placeholder: Group.
					esc_html_x( '%s Users', 'placeholder: Group', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'group' )
				) . '</h3>',
				'html_id'            => 'learndash_group_users',
				'html_class'         => 'learndash_group_users',
				'html_name'          => 'learndash_group_users',
				'search_label_left'  => sprintf(
					// translators: placeholder: Group.
					esc_html_x( 'Search All %s Users', 'placeholder: Group', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'group' )
				),
				'search_label_right' => sprintf(
					// translators: placeholder: Group.
					esc_html_x( 'Search Assigned %s Users', 'placeholder: Group', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'group' )
				),
			);

			$args = wp_parse_args( $args, $defaults );

			$args['html_id']   = $args['html_id'] . '-' . $args['group_id'];
			$args['html_name'] = $args['html_name'] . '[' . $args['group_id'] . ']';

			parent::__construct( $args );
		}
	}
}
