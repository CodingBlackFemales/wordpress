<?php
/**
 * LearnDash Settings Section for Translations LearnDash Metabox.
 *
 * @since 2.5.2
 * @package LearnDash\Settings\Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Translations_LearnDash' ) ) ) {
	/**
	 * Class LearnDash Settings Section for Translations LearnDash Metabox.
	 *
	 * @since 2.5.2
	 */
	class LearnDash_Settings_Section_Translations_LearnDash extends LearnDash_Settings_Section {

		/**
		 * Must match the Text Domain.
		 *
		 * @var string $project_slug String for project.
		 */
		private $project_slug = 'learndash';

		/**
		 * Protected constructor for class
		 *
		 * @since 2.5.2
		 */
		protected function __construct() {
			$this->settings_page_id = 'learndash_lms_translations';

			$this->setting_option_key = 'learndash';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_translations_' . $this->project_slug;

			// Section label/header.
			$this->settings_section_label = esc_html__( 'LearnDash LMS', 'learndash' );

			$this->load_options = false;

			LearnDash_Translations::register_translation_slug( $this->project_slug, LEARNDASH_LMS_PLUGIN_DIR . 'languages/' );

			parent::__construct();
		}

		/**
		 * Custom function to metabox.
		 *
		 * @since 2.5.2
		 */
		public function show_meta_box() {
			$ld_translations = new LearnDash_Translations( $this->project_slug );
			$ld_translations->show_meta_box();
		}
	}

	add_action(
		'init',
		function() {
			LearnDash_Settings_Section_Translations_LearnDash::add_section_instance();
		},
		1
	);
}
