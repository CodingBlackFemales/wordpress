<?php
/**
 * LearnDash Shortcode Section for Group [ld_group].
 *
 * @since 2.4.0
 * @package LearnDash\Settings\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Shortcodes_Section' ) ) && ( ! class_exists( 'LearnDash_Shortcodes_Section_ld_group' ) ) ) {
	/**
	 * Class LearnDash Shortcode Section for Group [ld_group].
	 */
	class LearnDash_Shortcodes_Section_ld_group extends LearnDash_Shortcodes_Section /* phpcs:ignore PEAR.NamingConventions.ValidClassName.Invalid */ {

		/**
		 * Public constructor for class.
		 *
		 * @param array $fields_args Field Args.
		 */
		public function __construct( $fields_args = array() ) {
			$this->fields_args = $fields_args;

			$this->shortcodes_section_key         = 'ld_group';
			$this->shortcodes_section_title       = learndash_get_custom_label( 'group' );
			$this->shortcodes_section_type        = 2;
			$this->shortcodes_section_description = sprintf(
				// translators: group.
				esc_html_x( 'This shortcode shows the content if the user is enrolled in a specific %s.', 'placeholder: group', 'learndash' ),
				learndash_get_custom_label_lower( 'group' )
			);

			parent::__construct();
		}

		/**
		 * Initialize the shortcode fields.
		 */
		public function init_shortcodes_section_fields() {
			$this->shortcodes_option_fields = array(
				'message'  => array(
					'id'        => $this->shortcodes_section_key . '_message',
					'name'      => 'message',
					'type'      => 'textarea',
					'label'     => esc_html__( 'Message shown to user', 'learndash' ),
					'help_text' => esc_html__( 'Message shown to user', 'learndash' ),
					'value'     => '',
					'required'  => 'required',
				),
				'group_id' => array(
					'id'        => $this->shortcodes_section_key . '_group_id',
					'name'      => 'group_id',
					'type'      => 'number',
					// translators: group.
					'label'     => sprintf( esc_html_x( '%s ID', 'placeholder: group', 'learndash' ), learndash_get_custom_label( 'group' ) ),
					'help_text' => sprintf(
						// translators: group, group.
						esc_html_x( 'Enter single %1$s ID. Leave blank for any %2$s.', 'placeholder: group, group', 'learndash' ),
						learndash_get_custom_label_lower( 'group' ),
						learndash_get_custom_label_lower( 'group' )
					),
					'value'     => '',
					'class'     => 'small-text',
				),
				'user_id'  => array(
					'id'        => $this->shortcodes_section_key . '_user_id',
					'name'      => 'user_id',
					'type'      => 'number',
					'label'     => esc_html__( 'User ID', 'learndash' ),
					'help_text' => esc_html__( 'Enter specific User ID. Leave blank for current User.', 'learndash' ),
					'value'     => '',
					'class'     => 'small-text',
				),
				'autop'    => array(
					'id'        => $this->shortcodes_section_key . 'autop',
					'name'      => 'autop',
					'type'      => 'select',
					'label'     => esc_html__( 'Auto Paragraph', 'learndash' ),
					'help_text' => esc_html__( 'Format shortcode content into proper paragraphs.', 'learndash' ),
					'value'     => 'true',
					'options'   => array(
						''      => esc_html__( 'Yes (default)', 'learndash' ),
						'false' => esc_html__( 'No', 'learndash' ),
					),
				),
			);

			if ( ( ! isset( $this->fields_args['post_type'] ) ) || ( 'groups' != $this->fields_args['post_type'] ) ) {
				$this->shortcodes_option_fields['group_id']['required']  = 'required';
				$this->shortcodes_option_fields['group_id']['help_text'] = sprintf(
					// translators: placeholder: group.
					esc_html_x( 'Enter single %s ID.', 'placeholder: group', 'learndash' ),
					learndash_get_custom_label_lower( 'group' )
				);
			}

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
			$this->shortcodes_option_fields = apply_filters( 'learndash_settings_fields', $this->shortcodes_option_fields, $this->shortcodes_section_key );

			parent::init_shortcodes_section_fields();
		}
	}
}
