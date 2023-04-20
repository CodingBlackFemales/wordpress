<?php
/**
 * LearnDash Admin Import/Export Taxonomies.
 *
 * @since 4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! trait_exists( 'Learndash_Admin_Import_Export_Taxonomies' ) ) {
	/**
	 * Trait LearnDash Admin Import/Export Taxonomies.
	 *
	 * @since 4.3.0
	 */
	trait Learndash_Admin_Import_Export_Taxonomies {
		/**
		 * Returns the file name.
		 *
		 * @since 4.3.0
		 *
		 * @return string The file name.
		 */
		protected function get_file_name(): string {
			return 'taxonomies';
		}
	}
}
