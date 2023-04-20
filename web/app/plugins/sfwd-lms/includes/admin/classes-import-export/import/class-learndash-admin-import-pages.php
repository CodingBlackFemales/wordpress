<?php
/**
 * LearnDash Admin Import Pages.
 *
 * @since 4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	class_exists( 'Learndash_Admin_Import_Posts' ) &&
	trait_exists( 'Learndash_Admin_Import_Export_Pages' ) &&
	! class_exists( 'Learndash_Admin_Import_Pages' )
) {
	/**
	 * Class LearnDash Admin Import Pages.
	 *
	 * @since 4.3.0
	 */
	class Learndash_Admin_Import_Pages extends Learndash_Admin_Import_Posts {
		use Learndash_Admin_Import_Export_Pages;

		/**
		 * Constructor.
		 *
		 * @since 4.3.0
		 * @since 4.5.0   Changed the $logger param to the `Learndash_Import_Export_Logger` class.
		 *
		 * @param int                                 $user_id      User ID. All posts are attached to this user.
		 * @param string                              $home_url     The previous home url.
		 * @param Learndash_Admin_Import_File_Handler $file_handler File Handler class instance.
		 * @param Learndash_Import_Export_Logger      $logger       Logger class instance.
		 *
		 * @return void
		 */
		public function __construct(
			int $user_id,
			string $home_url,
			Learndash_Admin_Import_File_Handler $file_handler,
			Learndash_Import_Export_Logger $logger
		) {
			parent::__construct( 'page', $user_id, $home_url, $file_handler, $logger );
		}
	}
}
