<?php
/**
 * Deprecated. This Text Domain has been absorbed into this plugin.
 *
 * Translation class file for the ld_propanel Text Domain.
 *
 * @since 4.17.0
 * @deprecated 4.17.0
 *
 * @package LearnDash\Deprecated
 */

_deprecated_file( __FILE__, '4.17.0' );

if (
	! class_exists( 'LearnDash_Translations' )
	|| class_exists( 'LearnDash_Settings_Section_Translations_Learndash_ProPanel' )
) {
	return;
}

/**
 * Translation class.
 *
 * @since 4.17.0
 * @deprecated 4.17.0
 */
class LearnDash_Settings_Section_Translations_Learndash_ProPanel extends LearnDash_Settings_Section {
	/**
	 * Project slug.
	 *
	 * Must match the plugin text domain.
	 *
	 * @since 4.17.0
	 * @deprecated 4.17.0
	 *
	 * @var string
	 */
	private $project_slug = 'ld_reports';

	/**
	 * Flag if the translation has been registered.
	 *
	 * @since 4.17.0
	 * @deprecated 4.17.0
	 *
	 * @var boolean
	 */
	private $registered = false;

	/**
	 * Constructor.
	 *
	 * @since 4.17.0
	 * @deprecated 4.17.0
	 */
	protected function __construct() {
		_deprecated_constructor( __CLASS__, '4.17.0' );

		// This will match the page slug in LearnDash. Should not need to be changed.
		$this->settings_page_id = 'learndash_lms_translations';

		// Used within the Settings API to uniquely identify this section.
		$this->settings_section_key = 'settings_translations_' . $this->project_slug;

		// Section label/header.
		$this->settings_section_label = __( 'LearnDash ProPanel', 'ld_propanel' ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- Deprecated class.

		if ( class_exists( 'LearnDash_Translations' ) ) {
			if ( method_exists( 'LearnDash_Translations', 'register_translation_slug' ) ) {
				$this->registered = true;
				LearnDash_Translations::register_translation_slug( $this->project_slug, LD_PP_PLUGIN_DIR . 'languages' );
			}
		}

		parent::__construct();
	}

	/**
	 * Add translation meta box.
	 *
	 * @since 4.17.0
	 * @deprecated 4.17.0
	 *
	 * @param string $settings_screen_id LearnDash settings screen ID.
	 *
	 * @return void
	 */
	public function add_meta_boxes( $settings_screen_id = '' ) {
		_deprecated_function( __METHOD__, '4.17.0' );

		if ( ( $settings_screen_id == $this->settings_screen_id ) && ( $this->registered === true ) ) {
			parent::add_meta_boxes( $settings_screen_id );
		}
	}

	/**
	 * Output meta box.
	 *
	 * @since 4.17.0
	 * @deprecated 4.17.0
	 *
	 * @return void
	 */
	public function show_meta_box() {
		_deprecated_function( __METHOD__, '4.17.0' );

		$ld_translations = new LearnDash_Translations( $this->project_slug );
		$ld_translations->show_meta_box();
	}
}
