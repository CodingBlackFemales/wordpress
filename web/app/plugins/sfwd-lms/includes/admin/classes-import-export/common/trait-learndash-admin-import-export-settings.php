<?php
/**
 * LearnDash Admin Import/Export Settings.
 *
 * @since 4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! trait_exists( 'Learndash_Admin_Import_Export_Settings' ) ) {
	/**
	 * Trait LearnDash Admin Import/Export Settings.
	 *
	 * @since 4.3.0
	 */
	trait Learndash_Admin_Import_Export_Settings {
		/**
		 * Returns the file name.
		 *
		 * @since 4.3.0
		 *
		 * @return string The file name.
		 */
		protected function get_file_name(): string {
			return 'settings';
		}

		/**
		 * Returns the fields which values are media ids.
		 *
		 * @since 4.3.0
		 *
		 * @return string[][] Media fields.
		 */
		protected function get_fields_with_media_id(): array {
			return array(
				'LearnDash_Settings_Theme_LD30' => array( 'login_logo' ),
				'LearnDash_Settings_Section_Emails_Purchase_Invoice' => array( 'company_logo' ),
			);
		}

		/**
		 * Returns the fields which values are strings that may contain media urls.
		 *
		 * @since 4.3.0
		 *
		 * @return string[][] Media fields.
		 */
		protected function get_fields_containing_media_urls(): array {
			return array(
				'LearnDash_Settings_Section_Emails_Course_Purchase_Success' => array( 'message' ),
				'LearnDash_Settings_Section_Emails_Group_Purchase_Success'  => array( 'message' ),
				'LearnDash_Settings_Section_Emails_New_User_Registration'   => array( 'message' ),
				'LearnDash_Settings_Section_Emails_Purchase_Invoice'        => array( 'message' ),
			);
		}
	}
}
