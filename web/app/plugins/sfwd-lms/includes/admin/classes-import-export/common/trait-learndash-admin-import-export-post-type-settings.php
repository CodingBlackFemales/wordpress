<?php
/**
 * LearnDash Admin Import/Export Post Type Settings.
 *
 * @since 4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! trait_exists( 'Learndash_Admin_Import_Export_Post_Type_Settings' ) ) {
	/**
	 * Trait LearnDash Admin Import/Export Post Type Settings.
	 *
	 * @since 4.3.0
	 */
	trait Learndash_Admin_Import_Export_Post_Type_Settings {
		/**
		 * Post Type.
		 *
		 * @since 4.3.0
		 *
		 * @var string
		 */
		protected $post_type;

		/**
		 * Returns the file name.
		 *
		 * @since 4.3.0
		 *
		 * @return string The file name.
		 */
		protected function get_file_name(): string {
			$post_type_name = LDLMS_Post_Types::get_post_type_key( $this->post_type );

			if ( empty( $post_type_name ) ) {
				$post_type_name = $this->post_type;
			}

			return 'post_type_settings_' . $post_type_name;
		}
	}
}
