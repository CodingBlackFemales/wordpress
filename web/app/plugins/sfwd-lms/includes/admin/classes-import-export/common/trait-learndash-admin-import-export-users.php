<?php
/**
 * LearnDash Admin Import/Export Users.
 *
 * @since 4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! trait_exists( 'Learndash_Admin_Import_Export_Users' ) ) {
	/**
	 * Trait LearnDash Admin Import/Export Users.
	 *
	 * @since 4.3.0
	 */
	trait Learndash_Admin_Import_Export_Users {
		/**
		 * If we process with progress or not.
		 *
		 * @since 4.3.0
		 *
		 * @var bool
		 */
		protected $with_progress;

		/**
		 * Returns the file name.
		 *
		 * @since 4.3.0
		 *
		 * @return string The file name.
		 */
		protected function get_file_name(): string {
			return 'user';
		}
	}
}
