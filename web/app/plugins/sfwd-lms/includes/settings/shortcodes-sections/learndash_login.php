<?php
/**
 * LearnDash Shortcode Section for Login [learndash_login].
 *
 * @since 3.0.7
 * @package LearnDash\Settings\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Shortcodes_Section' ) ) && ( ! class_exists( 'LearnDash_Shortcodes_Section_learndash_login' ) ) ) {
	/**
	 * Class LearnDash Shortcode Section for Login [learndash_login].
	 *
	 * @since 3.0.7
	 */
	class LearnDash_Shortcodes_Section_learndash_login extends LearnDash_Shortcodes_Section /* phpcs:ignore PEAR.NamingConventions.ValidClassName.Invalid */ {

		/**
		 * Public constructor for class.
		 *
		 * @since 3.0.7
		 *
		 * @param array $fields_args Field Args.
		 */
		public function __construct( $fields_args = array() ) {
			$this->fields_args = $fields_args;

			$this->shortcodes_section_key         = 'learndash_login';
			$this->shortcodes_section_title       = esc_html__( 'LearnDash Login', 'learndash' );
			$this->shortcodes_section_type        = 1;
			$this->shortcodes_section_description = esc_html__( 'This shortcode adds the login button on any page', 'learndash' );

			parent::__construct();
		}

		/**
		 * Initialize the shortcode fields.
		 *
		 * @since 3.0.7
		 */
		public function init_shortcodes_section_fields() {
			$this->shortcodes_option_fields = array(
				'login_description'  => array(
					'id'         => $this->shortcodes_section_key . '_login_description',
					'name'       => 'login_description',
					'type'       => 'html',
					'label'      => '',
					'label_none' => true,
					'input_full' => true,
					'value'      => wpautop( esc_html__( 'Controls the Login functionality.', 'learndash' ) ),
				),
				'login_url'          => array(
					'id'        => $this->shortcodes_section_key . '_login_url',
					'name'      => 'login_url',
					'type'      => 'text',
					'label'     => esc_html__( 'Login URL', 'learndash' ),
					'value'     => '',
					'help_text' => esc_html__( 'Override default login URL', 'learndash' ),
				),
				'login_label'        => array(
					'id'        => $this->shortcodes_section_key . '_login_label',
					'name'      => 'login_label',
					'type'      => 'text',
					'label'     => esc_html__( 'Login Label', 'learndash' ),
					'value'     => '',
					'help_text' => esc_html__( 'Override default label "Login"', 'learndash' ),
				),
				'login_placement'    => array(
					'id'        => $this->shortcodes_section_key . '_login_placement',
					'name'      => 'login_placement',
					'type'      => 'select',
					'label'     => esc_html__( 'Login Icon Placement', 'learndash' ),
					'help_text' => esc_html__( 'Login Icon Placement', 'learndash' ),
					'value'     => '',
					'options'   => array(
						''      => esc_html__( 'Left - To left of label', 'learndash' ),
						'right' => esc_html__( 'Right - To right of label', 'learndash' ),
						'none'  => esc_html__( 'None - No icon', 'learndash' ),
					),
				),
				'login_button'       => array(
					'id'        => $this->shortcodes_section_key . '_login_button',
					'name'      => 'login_button',
					'type'      => 'select',
					'label'     => esc_html__( 'Login Displayed as', 'learndash' ),
					'help_text' => esc_html__( 'Display as Button or link', 'learndash' ),
					'value'     => 'button',
					'options'   => array(
						''     => esc_html__( 'Button', 'learndash' ),
						'link' => esc_html__( 'Link', 'learndash' ),
					),
				),
				'logout_description' => array(
					'id'         => $this->shortcodes_section_key . '_logout_description',
					'name'       => 'logout_description',
					'type'       => 'html',
					'label'      => '',
					'label_none' => true,
					'input_full' => true,
					'value'      => wpautop( esc_html__( 'Controls the Logout functionality.', 'learndash' ) ),
				),
				'logout_url'         => array(
					'id'        => $this->shortcodes_section_key . '_logout_url',
					'name'      => 'logout_url',
					'type'      => 'text',
					'label'     => esc_html__( 'Logout URL Redirect', 'learndash' ),
					'value'     => '',
					'help_text' => esc_html__( 'Override default logout URL.', 'learndash' ),
				),
				'logout_label'       => array(
					'id'        => $this->shortcodes_section_key . '_logout_label',
					'name'      => 'logout_label',
					'type'      => 'text',
					'label'     => esc_html__( 'Logout Label', 'learndash' ),
					'value'     => '',
					'help_text' => esc_html__( 'Override default label "Logout"', 'learndash' ),
				),
				'logout_placement'   => array(
					'id'        => $this->shortcodes_section_key . '_logout_placement',
					'name'      => 'logout_placement',
					'type'      => 'select',
					'label'     => esc_html__( 'Logout Icon Placement', 'learndash' ),
					'help_text' => esc_html__( 'Logout Icon Placement', 'learndash' ),
					'value'     => '',
					'options'   => array(
						'left' => esc_html__( 'Left - To left of label', 'learndash' ),
						''     => esc_html__( 'Right - To right of label', 'learndash' ),
						'none' => esc_html__( 'None - No icon', 'learndash' ),
					),
				),
				'logout_button'      => array(
					'id'        => $this->shortcodes_section_key . '_logout_button',
					'name'      => 'logout_button',
					'type'      => 'select',
					'label'     => esc_html__( 'Logout Displayed as Button', 'learndash' ),
					'help_text' => esc_html__( 'Display as Button or link', 'learndash' ),
					'value'     => 'button',
					'options'   => array(
						''     => esc_html__( 'Button', 'learndash' ),
						'link' => esc_html__( 'Link', 'learndash' ),
					),
				),
			);

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
			$this->shortcodes_option_fields = apply_filters( 'learndash_settings_fields', $this->shortcodes_option_fields, $this->shortcodes_section_key );

			parent::init_shortcodes_section_fields();
		}
	}
}
