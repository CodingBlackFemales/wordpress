<?php
/**
 * LearnDash Binary Selector Course Leaders.
 *
 * @since 2.2.1
 * @package LearnDash\Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'Learndash_Binary_Selector_Group_Leaders' ) ) && ( class_exists( 'Learndash_Binary_Selector_Users' ) ) ) {

	/**
	 * Class LearnDash Binary Selector Course Leaders.
	 *
	 * @since 2.2.1
	 * @uses Learndash_Binary_Selector_Users
	 */
	class Learndash_Binary_Selector_Group_Leaders extends Learndash_Binary_Selector_Users {
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
					esc_html_x( '%s Leaders', 'placeholder: Group', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'group' )
				) . '</h3>',
				'html_id'            => 'learndash_group_leaders',
				'html_class'         => 'learndash_group_leaders',
				'html_name'          => 'learndash_group_leaders',
				'search_label_left'  => sprintf(
					// translators: placeholder: Group.
					esc_html_x( 'Search All %s Leaders', 'placeholder: Group', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'group' )
				),
				'search_label_right' => sprintf(
					// translators: placeholder: Group.
					esc_html_x( 'Search Assigned %s Leaders', 'placeholder: Group', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'group' )
				),
			);

			$args = wp_parse_args( $args, $defaults );

			$args['html_id']   = $args['html_id'] . '-' . $args['group_id'];
			$args['html_name'] = $args['html_name'] . '[' . $args['group_id'] . ']';

			if ( ( ! isset( $args['included_ids'] ) ) || ( empty( $args['included_ids'] ) ) ) {
				$args['role__in'] = array( 'group_leader', 'administrator' );
			}

			parent::__construct( $args );
		}
	}
}
