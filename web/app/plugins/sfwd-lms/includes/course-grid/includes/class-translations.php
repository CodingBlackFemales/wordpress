<?php
/**
 * Legacy course grid translations class file.
 *
 * @since 4.21.4
 * @deprecated 4.21.4
 *
 * @package LearnDash\Core
 */
namespace LearnDash\Course_Grid;

use LearnDash_Settings_Section;
use LearnDash_Translations;

_deprecated_file( __FILE__, '4.21.4' );

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) ) {
	/**
	 * Deprecated legacy course grid translations class.
	 *
	 * @since 4.21.4
	 * @deprecated 4.21.4
	 */
	class Translations extends LearnDash_Settings_Section {
		/**
		 * Deprecated. Text domain slug.
		 *
		 * @since 4.21.4
		 * @deprecated 4.21.4
		 *
		 * @var string
		 */
		private $project_slug = 'learndash';

		/**
		 * Deprecated. Registered flag.
		 *
		 * @since 4.21.4
		 * @deprecated 4.21.4
		 *
		 * @var bool
		 */
		private $registered = false;

		/**
		 * Constructor.
		 *
		 * @since 4.21.4
		 * @deprecated 4.21.4
		 */
		public function __construct() {
			_deprecated_constructor( __CLASS__, '4.21.4' );

			$this->settings_page_id = 'learndash_lms_translations';

			// Used within the Settings API to uniquely identify this section
			$this->settings_section_key = 'settings_translations_' . $this->project_slug;

			// Section label/header
			$this->settings_section_label = __( 'LearnDash LMS - Course Grid', 'learndash' );

			// Class LearnDash_Translations add LD v2.5.0
			if ( class_exists( 'LearnDash_Translations' ) ) {
				// Method register_translation_slug add LD v2.5.5
				if ( method_exists( 'LearnDash_Translations', 'register_translation_slug' ) ) {
					$this->registered = true;
					LearnDash_Translations::register_translation_slug( $this->project_slug, LEARNDASH_COURSE_GRID_PLUGIN_PATH . 'languages' );
				}
			}

			parent::__construct();
		}

		/**
		 * Adds meta boxes.
		 *
		 * @since 4.21.4
		 * @deprecated 4.21.4
		 *
		 * @param string $settings_screen_id Screen ID.
		 *
		 * @return void
		 */
		public function add_meta_boxes( $settings_screen_id = '' ) {
			_deprecated_function( __METHOD__, '4.21.4' );

			if ( ( $settings_screen_id == $this->settings_screen_id ) && ( $this->registered === true ) ) {
				parent::add_meta_boxes( $settings_screen_id );
			}
		}

		/**
		 * Outputs meta box.
		 *
		 * @since 4.21.4
		 * @deprecated 4.21.4
		 *
		 * @return void
		 */
		public function show_meta_box() {
			$ld_translations = new LearnDash_Translations( $this->project_slug );
			$ld_translations->show_meta_box();
		}
	}

	add_action(
		'init',
		function () {
			Translations::add_section_instance();
		}
	);
}
