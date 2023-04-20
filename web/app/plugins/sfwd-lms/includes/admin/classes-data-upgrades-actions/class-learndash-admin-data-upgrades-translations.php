<?php
/**
 * LearnDash Data Upgrades for Translations.
 *
 * @since 2.5.5
 * @package LearnDash\Data_Upgrades
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'Learndash_Admin_Data_Upgrades' ) ) && ( ! class_exists( 'Learndash_Admin_Data_Upgrades_Translations' ) ) ) {

	/**
	 * Class LearnDash Data Upgrades for Translations.
	 *
	 * @since 2.5.5
	 * @uses Learndash_Admin_Data_Upgrades
	 */
	class Learndash_Admin_Data_Upgrades_Translations extends Learndash_Admin_Data_Upgrades {

		/**
		 * Protected constructor for class
		 *
		 * @since 2.5.5
		 */
		protected function __construct() {
			$this->data_slug = 'translations';
			parent::__construct();
			add_action( 'init', array( $this, 'upgrade_translations' ) );
			parent::register_upgrade_action();
		}

		/**
		 * Update the LearnDash Translations
		 * Checks to see if settings needs to be updated.
		 *
		 * @since 2.5.5
		 */
		public function upgrade_translations() {
			if ( is_admin() ) {
				$translations_installed = $this->get_data_settings( 'translations_installed' );
				if ( ( defined( 'LEARNDASH_ACTIVATED' ) && LEARNDASH_ACTIVATED ) || ( ! $translations_installed ) ) {
					$this->download_translations();
					$this->set_data_settings( 'translations_installed', time() );
				}
			}
		}

		/**
		 * Download the translations from glotpress server.
		 *
		 * @since 2.5.5
		 */
		public function download_translations() {
			$wp_installed_languages = get_available_languages();
			if ( ! in_array( 'en_US', $wp_installed_languages, true ) ) {
				$wp_installed_languages = array_merge( array( 'en_US' ), $wp_installed_languages );
			}

			if ( ! empty( $wp_installed_languages ) ) {
				LearnDash_Translations::get_available_translations( 'learndash', true );
				foreach ( $wp_installed_languages as $locale ) {
					$reply = LearnDash_Translations::install_translation( 'learndash', $locale );
				}
			}
		}

		// End of functions.
	}
}

add_action(
	'learndash_data_upgrades_init',
	function() {
		Learndash_Admin_Data_Upgrades_Translations::add_instance();
	}
);
