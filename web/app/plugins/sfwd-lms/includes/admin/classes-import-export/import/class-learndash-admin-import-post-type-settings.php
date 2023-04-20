<?php
/**
 * LearnDash Admin Import Post Type Settings.
 *
 * @since 4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	class_exists( 'Learndash_Admin_Import' ) &&
	trait_exists( 'Learndash_Admin_Import_Export_Post_Type_Settings' ) &&
	! class_exists( 'Learndash_Admin_Import_Post_Type_Settings' )
) {
	/**
	 * Class LearnDash Admin Import Post Type Settings.
	 *
	 * @since 4.3.0
	 */
	class Learndash_Admin_Import_Post_Type_Settings extends Learndash_Admin_Import {
		use Learndash_Admin_Import_Export_Post_Type_Settings;

		/**
		 * Constructor.
		 *
		 * @since 4.3.0
		 * @since 4.5.0   Changed the $logger param to the `Learndash_Import_Export_Logger` class.
		 *
		 * @param string                              $post_type    Post Type.
		 * @param string                              $home_url     The previous home url.
		 * @param Learndash_Admin_Import_File_Handler $file_handler File Handler class instance.
		 * @param Learndash_Import_Export_Logger      $logger       Logger class instance.
		 *
		 * @return void
		 */
		public function __construct(
			string $post_type,
			string $home_url,
			Learndash_Admin_Import_File_Handler $file_handler,
			Learndash_Import_Export_Logger $logger
		) {
			$this->post_type = $post_type;

			parent::__construct( $home_url, $file_handler, $logger );
		}

		/**
		 * Saves post type settings.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		protected function import(): void {
			$sections = $this->load_and_decode_file();

			if ( empty( $sections ) ) {
				return;
			}

			foreach ( $sections as $section ) {
				LearnDash_Settings_Section::set_section_settings_all(
					$section['name'],
					$section['fields']
				);

				$this->processed_items_count++;
				$this->imported_items_count++;
			}
		}
	}
}
