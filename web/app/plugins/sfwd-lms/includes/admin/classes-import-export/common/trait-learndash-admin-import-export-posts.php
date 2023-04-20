<?php
/**
 * LearnDash Admin Import/Export Posts.
 *
 * @since 4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! trait_exists( 'Learndash_Admin_Import_Export_Posts' ) ) {
	/**
	 * Trait LearnDash Admin Import/Export Posts.
	 *
	 * @since 4.3.0
	 */
	trait Learndash_Admin_Import_Export_Posts {
		/**
		 * Post Type.
		 *
		 * @since 4.3.0
		 *
		 * @var string
		 */
		protected $post_type;

		/**
		 * User ID.
		 *
		 * @since 4.3.0
		 *
		 * @var int
		 */
		protected $user_id;

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

			return 'post_type_' . $post_type_name;
		}

		/**
		 * Returns the list of learndash settings fields that can contains media.
		 *
		 * @since 4.3.0
		 *
		 * @return array The list of learndash settings fields that can contains media.
		 */
		protected function get_learndash_fields_with_media(): array {
			switch ( LDLMS_Post_Types::get_post_type_key( $this->post_type ) ) {
				case LDLMS_Post_Types::COURSE:
					return array( 'course_materials' );
				case LDLMS_Post_Types::LESSON:
					return array( 'lesson_materials', 'lesson_video_url' );
				case LDLMS_Post_Types::TOPIC:
					return array( 'topic_materials' );
				case LDLMS_Post_Types::GROUP:
					return array( 'group_materials' );
				case LDLMS_Post_Types::EXAM:
					return array( 'message_passed', 'message_failed' );
				default:
					return array();
			}
		}
	}
}
