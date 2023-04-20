<?php
/**
 * LearnDash Admin Import/Export Pro Quiz.
 *
 * @since 4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! trait_exists( 'Learndash_Admin_Import_Export_Proquiz' ) ) {
	/**
	 * Trait LearnDash Admin Import/Export Pro Quiz.
	 *
	 * @since 4.3.0
	 */
	trait Learndash_Admin_Import_Export_Proquiz {
		/**
		 * Returns the file name.
		 *
		 * @since 4.3.0
		 *
		 * @return string The file name.
		 */
		protected function get_file_name(): string {
			return 'proquiz';
		}
	}
}
