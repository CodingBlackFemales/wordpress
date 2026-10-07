<?php
/**
 * Deprecated Notifications translations settings section class file.
 *
 * The add-on is part of LearnDash core now and its strings use the `learndash` text domain, which
 * the LearnDash translations settings section already covers, so this section is no longer
 * registered.
 *
 * @since 5.2.0
 * @deprecated 5.2.0
 *
 * @package LearnDash\Notifications\Deprecated
 */

_deprecated_file( __FILE__, '5.2.0' );

if (
	! class_exists( 'LearnDash_Settings_Section' )
	|| class_exists( 'LearnDash_Settings_Section_Translations_Learndash_Notifications' )
) {
	return;
}

/**
 * Deprecated translations settings section class.
 *
 * Kept for backward compatibility if in any case the class is referred directly.
 *
 * @since 5.2.0
 * @deprecated 5.2.0
 */
class LearnDash_Settings_Section_Translations_Learndash_Notifications extends LearnDash_Settings_Section {
	/**
	 * Project slug.
	 *
	 * Must match the old Text Domain.
	 *
	 * @since 5.2.0
	 * @deprecated 5.2.0
	 *
	 * @var string
	 */
	private $project_slug = 'learndash-notifications';

	/**
	 * Flag if the translation has been registered.
	 *
	 * @since 5.2.0
	 * @deprecated 5.2.0
	 *
	 * @var boolean
	 */
	private $registered = false;

	/**
	 * Constructor.
	 *
	 * @since 5.2.0
	 * @deprecated 5.2.0
	 */
	public function __construct() {
		_deprecated_constructor( __CLASS__, '5.2.0' );

		// This will match the page slug in LearnDash. Should not need to be changed.
		$this->settings_page_id = 'learndash_lms_translations';

		// Used within the Settings API to uniquely identify this section.
		$this->settings_section_key = 'settings_translations_' . $this->project_slug;

		// Section label/header.
		$this->settings_section_label = __( 'LearnDash Notifications', 'learndash' );

		// Class LearnDash_Translations add LD v2.5.0.
		if ( class_exists( 'LearnDash_Translations' ) ) {
			// Method register_translation_slug add LD v2.5.5.
			if ( method_exists( 'LearnDash_Translations', 'register_translation_slug' ) ) {
				$this->registered = true;
				LearnDash_Translations::register_translation_slug( $this->project_slug, LEARNDASH_NOTIFICATIONS_PLUGIN_PATH . 'languages' );
			}
		}

		parent::__construct();
	}

	/**
	 * Add translation meta box.
	 *
	 * @since 5.2.0
	 * @deprecated 5.2.0
	 *
	 * @param string $settings_screen_id LearnDash settings screen ID.
	 *
	 * @return void
	 */
	public function add_meta_boxes( $settings_screen_id = '' ) {
		_deprecated_function( __METHOD__, '5.2.0' );

		if ( $settings_screen_id === $this->settings_screen_id && $this->registered === true ) {
			parent::add_meta_boxes( $settings_screen_id );
		}
	}

	/**
	 * Output meta box.
	 *
	 * @since 5.2.0
	 * @deprecated 5.2.0
	 *
	 * @return void
	 */
	public function show_meta_box() {
		_deprecated_function( __METHOD__, '5.2.0' );

		$ld_translations = new LearnDash_Translations( $this->project_slug );
		$ld_translations->show_meta_box();
	}
}
