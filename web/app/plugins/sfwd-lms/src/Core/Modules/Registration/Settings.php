<?php
/**
 * LearnDash Registration settings class.
 *
 * @since 4.16.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Registration;

use LearnDash_Settings_Section_General_Appearance;

/**
 * Service provider class for registration.
 *
 * @since 4.16.0
 * @deprecated 4.21.0
 */
class Settings {
	/**
	 * Sets the new install appearance.
	 *
	 * This is hooked to the `learndash_initialization_new_install` action and overrides the default
	 * appearance of the registration pages from classic to modern if this is a new install.
	 *
	 * @since 4.16.0
	 * @since 4.21.0 Updating format of the setting, switched to a two button toggle on a different setting page.
	 * @deprecated 4.21.0
	 *
	 * @return void
	 */
	public function action_set_new_install_appearance(): void {
		// Moving this to our Features object to centralize these data handlers.
		_deprecated_function( __METHOD__, '4.21.0', '\LearnDash\Core\Themes\LD30\Modern\Features::action_set_new_install_appearance' );

		LearnDash_Settings_Section_General_Appearance::set_setting(
			'registration_enabled',
			'yes',
		);
	}
}
