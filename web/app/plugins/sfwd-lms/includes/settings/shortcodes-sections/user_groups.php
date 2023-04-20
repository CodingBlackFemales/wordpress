<?php
/**
 * LearnDash Shortcode Section for User Groups [user_groups].
 *
 * @since 2.4.0
 * @package LearnDash\Settings\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Shortcodes_Section' ) ) && ( ! class_exists( 'LearnDash_Shortcodes_Section_user_groups' ) ) ) {
	/**
	 * Class LearnDash Shortcode Section for User Groups [user_groups].
	 *
	 * @since 2.4.0
	 */
	class LearnDash_Shortcodes_Section_user_groups extends LearnDash_Shortcodes_Section /* phpcs:ignore PEAR.NamingConventions.ValidClassName.Invalid */ {

		/**
		 * Public constructor for class.
		 *
		 * @since 2.4.0
		 *
		 * @param array $fields_args Field Args.
		 */
		public function __construct( $fields_args = array() ) {
			$this->fields_args = $fields_args;
			$groups_public     = ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Groups_CPT', 'public' ) === '' ) ? learndash_groups_get_not_public_message() : '';

			$this->shortcodes_section_key   = 'user_groups';
			$this->shortcodes_section_title = sprintf(
				// translators: placeholder: Groups.
				esc_html_x( 'User %s', 'placeholder: Groups', 'learndash' ),
				learndash_get_custom_label( 'groups' )
			);
			$this->shortcodes_section_type        = 1;
			$this->shortcodes_section_description = sprintf(
				// translators: placeholder : group.
				esc_html_x( 'This shortcode displays the list of %1$s users are assigned to as users or leaders. %2$s', 'placeholder: Group', 'learndash' ),
				learndash_get_custom_label( 'group' ),
				$groups_public
			);

			parent::__construct();
		}

		/**
		 * Initialize the shortcode fields.
		 *
		 * @since 2.4.0
		 */
		public function init_shortcodes_section_fields() {
			$this->shortcodes_option_fields = array(
				'user_id' => array(
					'id'        => $this->shortcodes_section_key . '_user_id',
					'name'      => 'user_id',
					'type'      => 'number',
					'label'     => esc_html__( 'User ID', 'learndash' ),
					'help_text' => esc_html__( 'Enter specific User ID. Leave blank for current User.', 'learndash' ),
					'value'     => '',
					'class'     => 'small-text',
				),
			);

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
			$this->shortcodes_option_fields = apply_filters( 'learndash_settings_fields', $this->shortcodes_option_fields, $this->shortcodes_section_key );

			parent::init_shortcodes_section_fields();
		}
	}
}
