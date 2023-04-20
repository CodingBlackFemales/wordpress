<?php
/**
 * LearnDash Binary Selector Leader Groups.
 *
 * @since 2.2.1
 * @package LearnDash\Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'Learndash_Binary_Selector_Leader_Groups' ) ) && ( class_exists( 'Learndash_Binary_Selector_Posts' ) ) ) {
	/**
	 * Class LearnDash Binary Selector Leader Groups.
	 *
	 * @since 2.2.1
	 * @uses Learndash_Binary_Selector_Posts
	 */
	class Learndash_Binary_Selector_Leader_Groups extends Learndash_Binary_Selector_Posts {

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
				'post_type'          => 'groups',
				'html_title'         => '<h3>' . sprintf(
					// translators: Groups.
					esc_html_x( 'Leader of %s', 'placeholder: Groups', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'groups' )
				) . '</h3>',
				'html_id'            => 'learndash_leader_groups',
				'html_class'         => 'learndash_leader_groups',
				'html_name'          => 'learndash_leader_groups',
				'search_label_left'  => sprintf(
					// translators: Groups.
					esc_html_x( 'Search All %s', 'placeholder: Groups', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'groups' )
				),
				'search_label_right' => sprintf(
					// translators: Groups.
					esc_html_x( 'Search Leader %s', 'placeholder: Groups', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'groups' )
				),
			);

			$args = wp_parse_args( $args, $defaults );

			$args['html_id']   = $args['html_id'] . '-' . $args['user_id'];
			$args['html_name'] = $args['html_name'] . '[' . $args['user_id'] . ']';

			parent::__construct( $args );
		}
	}
}
