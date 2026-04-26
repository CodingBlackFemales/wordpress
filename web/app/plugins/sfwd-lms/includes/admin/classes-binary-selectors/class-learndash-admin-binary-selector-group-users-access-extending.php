<?php
/**
 * LearnDash Binary Selector Group Users Access Extending.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	! class_exists( 'Learndash_Binary_Selector_Group_Users_Access_Extending' )
	&& class_exists( 'Learndash_Binary_Selector_Users' )
) {
	/**
	 * Class LearnDash Binary Selector Group Users Access Extending.
	 *
	 * @since 4.8.0
	 */
	class Learndash_Binary_Selector_Group_Users_Access_Extending extends Learndash_Binary_Selector_Users {
		/**
		 * Constructor.
		 *
		 * @since 4.8.0
		 *
		 * @param mixed[] $args Array of arguments for class.
		 *
		 * @return void
		 */
		public function __construct( $args = array() ) {
			$this->selector_class = get_class( $this );

			$defaults = [
				'group_id'           => 0,
				'html_id'            => 'group_users_to_extend_access',
				'html_name'          => 'group_users_to_extend_access',
				'html_class'         => 'group_users_to_extend_access',
				'search_label_left'  => sprintf(
					// translators: placeholder: Group.
					esc_html_x( 'Search All %s Users', 'Search All Group Users', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'group' )
				),
				'search_label_right' => esc_html__( 'Search Users Who Will Be Affected', 'learndash' ),
			];

			$args = wp_parse_args( $args, $defaults );

			parent::__construct( $args );
		}
	}
}
