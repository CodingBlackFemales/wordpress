<?php
if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Translations_Learndash_Zapier' ) ) ) {
	class LearnDash_Settings_Section_Translations_Learndash_Zapier extends LearnDash_Settings_Section {

		// Must match the Text Domain
		private $project_slug = 'learndash-zapier';
		private $registered = false;

		function __construct() {
			$this->settings_page_id                 = 'learndash_lms_translations';

			// Used within the Settings API to uniquely identify this section
			$this->settings_section_key             = 'settings_translations_' . $this->project_slug;

			// Section label/header
			$this->settings_section_label           = __( 'LearnDash Samcart', 'learndash-zapier' );

			// Class LearnDash_Translations add LD v2.5.0
			if ( class_exists( 'LearnDash_Translations' ) ) {
				// Method register_translation_slug add LD v2.5.5
				if ( method_exists( 'LearnDash_Translations', 'register_translation_slug' ) ) {
					$this->registered = true;
					LearnDash_Translations::register_translation_slug( $this->project_slug, LEARNDASH_ZAPIER_PLUGIN_PATH . 'languages' );
				}
			}

			parent::__construct();
		}

		function add_meta_boxes( $settings_screen_id = '' ) {
			if ( ( $settings_screen_id == $this->settings_screen_id ) && ( $this->registered === true ) ) {
				parent::add_meta_boxes( $settings_screen_id );
			}
		}

		function show_meta_box() {
			$ld_translations = new Learndash_Translations( $this->project_slug );
			$ld_translations->show_meta_box();
		}
	}
	add_action(
		'init',
		function() {
			LearnDash_Settings_Section_Translations_Learndash_Zapier::add_section_instance();
		}
	);
}
