<?php
/**
 * LearnDash Admin Import Settings.
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
	trait_exists( 'Learndash_Admin_Import_Export_Settings' ) &&
	! class_exists( 'Learndash_Admin_Import_Settings' )
) {
	/**
	 * Class LearnDash Admin Import Settings.
	 *
	 * @since 4.3.0
	 */
	class Learndash_Admin_Import_Settings extends Learndash_Admin_Import {
		use Learndash_Admin_Import_Export_Settings;

		/**
		 * Fields with media ids.
		 *
		 * @since 4.3.0
		 *
		 * @var string[][]
		 */
		private $fields_with_media_id;

		/**
		 * Fields containing media urls.
		 *
		 * @since 4.3.0
		 *
		 * @var string[][]
		 */
		private $fields_containing_media_urls;

		/**
		 * Constructor.
		 *
		 * @since 4.3.0
		 * @since 4.5.0   Changed the $logger param to the `Learndash_Import_Export_Logger` class.
		 *
		 * @param string                              $home_url     The previous home url.
		 * @param Learndash_Admin_Import_File_Handler $file_handler File Handler class instance.
		 * @param Learndash_Import_Export_Logger      $logger       Logger class instance.
		 *
		 * @return void
		 */
		public function __construct(
			string $home_url,
			Learndash_Admin_Import_File_Handler $file_handler,
			Learndash_Import_Export_Logger $logger
		) {
			$this->fields_with_media_id         = $this->get_fields_with_media_id();
			$this->fields_containing_media_urls = $this->get_fields_containing_media_urls();

			parent::__construct( $home_url, $file_handler, $logger );
		}

		/**
		 * Saves global settings.
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
				$this->processed_items_count++;

				foreach ( $section['fields'] as $field_key => &$field_value ) {
					$field_value = $this->map_field_value( $section['name'], $field_key, $field_value );
				}

				LearnDash_Settings_Section::set_section_settings_all( $section['name'], $section['fields'] );

				$this->imported_items_count++;
			}
		}

		/**
		 * Maps the meta value.
		 *
		 * @since 4.3.0
		 *
		 * @param string $section_name Section name.
		 * @param string $field_key    Meta key.
		 * @param mixed  $field_value  Meta value.
		 *
		 * @return mixed
		 */
		protected function map_field_value( string $section_name, string $field_key, $field_value ) {
			if ( ! $field_value ) {
				return $field_value;
			}

			if ( 'LearnDash_Settings_Section_Registration_Pages' === $section_name ) {
				return $this->get_new_post_id_by_old_post_id( $field_value );
			}

			if (
				isset( $this->fields_with_media_id[ $section_name ] ) &&
				in_array( $field_key, $this->fields_with_media_id[ $section_name ], true )
			) {
				return $this->get_new_post_id_by_old_post_id( $field_value );
			}

			if (
				isset( $this->fields_containing_media_urls[ $section_name ] ) &&
				in_array( $field_key, $this->fields_containing_media_urls[ $section_name ], true )
			) {
				$field_value = $this->replace_media_from_content( $field_value );
			}

			if ( is_string( $field_value ) ) {
				$field_value = str_replace( $this->home_url_previous, $this->home_url_current, $field_value );
			}

			return $field_value;
		}
	}
}
