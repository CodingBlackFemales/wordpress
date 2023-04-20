<?php
/**
 * LearnDash Shortcode Section for User Status [learndash_user_status].
 *
 * @since 4.0.0
 * @package LearnDash\Settings\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Shortcodes_Section' ) ) && ( ! class_exists( 'LearnDash_Shortcodes_Section_learndash_user_status' ) ) ) {
	/**
	 * Class LearnDash Shortcode Section for User Status [learndash_user_status].
	 *
	 * @since 4.0.0
	 */
	class LearnDash_Shortcodes_Section_learndash_user_status extends LearnDash_Shortcodes_Section /* phpcs:ignore PEAR.NamingConventions.ValidClassName.Invalid */ {

		/**
		 * Public constructor for class.
		 *
		 * @since 4.0.0
		 *
		 * @param array $fields_args Field Args.
		 */
		public function __construct( $fields_args = array() ) {
			$this->fields_args = $fields_args;

			$this->shortcodes_section_key         = 'learndash_user_status';
			$this->shortcodes_section_title       = esc_html__( 'User Status', 'learndash' );
			$this->shortcodes_section_type        = 2;
			$this->shortcodes_section_description = esc_html__( 'This shortcode displays information of enrolled courses and their progress for a user. Defaults to current logged in user if no ID specified.', 'learndash' );

			parent::__construct();
		}

		/**
		 * Initialize the shortcode fields.
		 *
		 * @since 4.0.0
		 */
		public function init_shortcodes_section_fields() {
			$this->shortcodes_option_fields = array(
				'user_id'             => array(
					'id'        => $this->shortcodes_section_key . '_user_d',
					'name'      => 'user_id',
					'type'      => 'number',
					'label'     => esc_html__( 'User ID', 'learndash' ),
					'help_text' => esc_html__( 'ID of the user to display information for.', 'learndash' ),
					'value'     => '',
				),
				'registered_num'      => array(
					'id'        => $this->shortcodes_section_key . '_registered_num',
					'name'      => 'registered_num',
					'type'      => 'number',
					'label'     => esc_html__( 'Courses Per Page', 'learndash' ),
					'help_text' => esc_html__( 'Number of courses to display per page. Set to 0 for no pagination.', 'learndash' ),
					'value'     => '',
				),
				'registered_order_by' => array(
					'id'      => $this->shortcodes_section_key . '_registered_order_by',
					'name'    => 'registered_order_by',
					'type'    => 'select',
					'label'   => esc_html__( 'Order By', 'learndash' ),
					'value'   => 'title',
					'options' => array(
						'post_title' => esc_html__( 'Title (default)', 'learndash' ),
						'post_id'    => esc_html__( 'No', 'learndash' ),
						'post_date'  => esc_html__( 'Date', 'learndash' ),
						'menu_order' => esc_html__( 'Menu', 'learndash' ),
					),
				),
				'registered_order'    => array(
					'id'      => $this->shortcodes_section_key . '_registered_order',
					'name'    => 'registered_order',
					'type'    => 'select',
					'label'   => esc_html__( 'Order', 'learndash' ),
					'value'   => 'ASC',
					'options' => array(
						'ASC'  => esc_html__( 'ASC (default)', 'learndash' ),
						'DESC' => esc_html__( 'DESC', 'learndash' ),
					),
				),
			);

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
			$this->shortcodes_option_fields = apply_filters( 'learndash_settings_fields', $this->shortcodes_option_fields, $this->shortcodes_section_key );

			parent::init_shortcodes_section_fields();
		}
	}
}
