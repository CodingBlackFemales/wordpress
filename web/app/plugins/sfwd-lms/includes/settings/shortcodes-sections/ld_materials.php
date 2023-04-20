<?php
/**
 * LearnDash Shortcode Section for Materials [ld_materials].
 *
 * @since 4.0.0
 * @package LearnDash\Settings\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Shortcodes_Section' ) ) && ( ! class_exists( 'LearnDash_Shortcodes_Section_ld_materials' ) ) ) {
	/**
	 * Class LearnDash Shortcode Section for Materials [ld_materials]].
	 *
	 * @since 4.0.0
	 */
	class LearnDash_Shortcodes_Section_ld_materials extends LearnDash_Shortcodes_Section /* phpcs:ignore PEAR.NamingConventions.ValidClassName.Invalid */ {

		/**
		 * Public constructor for class.
		 *
		 * @since 4.0.0
		 *
		 * @param array $fields_args Field Args.
		 */
		public function __construct( $fields_args = array() ) {
			$this->fields_args = $fields_args;

			$this->shortcodes_section_key         = 'ld_materials';
			$this->shortcodes_section_title       = esc_html__( 'LearnDash Materials', 'learndash' );
			$this->shortcodes_section_type        = 2;
			$this->shortcodes_section_description = esc_html__( 'This shortcode displays the materials for a specific LearnDash related post.', 'learndash' );

			parent::__construct();
		}

		/**
		 * Initialize the shortcode fields.
		 *
		 * @since 4.0.0
		 */
		public function init_shortcodes_section_fields() {
			$this->shortcodes_option_fields = array(
				'post_id' => array(
					'id'        => $this->shortcodes_section_key . '_message',
					'name'      => 'post_id',
					'type'      => 'number',
					'label'     => esc_html__( 'Post ID', 'learndash' ),
					'help_text' => esc_html__( 'Enter a Post ID of the LearnDash post that you want to display materials for.', 'learndash' ),
					'value'     => '',
				),
				'autop'   => array(
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

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
			$this->shortcodes_option_fields = apply_filters( 'learndash_settings_fields', $this->shortcodes_option_fields, $this->shortcodes_section_key );

			parent::init_shortcodes_section_fields();
		}
	}
}
