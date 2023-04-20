<?php
/**
 * LearnDash Admin Import Media.
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
	trait_exists( 'Learndash_Admin_Import_Export_Media' ) &&
	! class_exists( 'Learndash_Admin_Import_Media' )
) {
	/**
	 * Class LearnDash Admin Import Settings.
	 *
	 * @since 4.3.0
	 */
	class Learndash_Admin_Import_Media extends Learndash_Admin_Import {
		use Learndash_Admin_Import_Export_Media;

		/**
		 * Saves media.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		protected function import(): void {
			// loads media files if not loaded yet.
			if ( ! function_exists( 'media_handle_sideload' ) ) {
				require_once ABSPATH . 'wp-admin/includes/media.php';
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/image.php';
			}

			foreach ( $this->get_file_lines() as $item ) {
				$this->processed_items_count++;

				$attachment_id = media_handle_sideload(
					array(
						'name'     => $item['filename'],
						'tmp_name' => $this->get_media_file_path( $item['filename'] ),
					),
					0,
					$item['post_title'],
					array(
						'post_status'    => 'inherit',
						'post_content'   => $item['post_content'],
						'post_excerpt'   => $item['post_excerpt'],
						'post_mime_type' => $item['post_mime_type'],
					)
				);

				if ( is_wp_error( $attachment_id ) ) {
					continue;
				}

				$this->imported_items_count++;

				update_post_meta(
					$attachment_id,
					Learndash_Admin_Import::META_KEY_IMPORTED_FROM_POST_ID,
					$item['ID']
				);
				update_post_meta(
					$attachment_id,
					Learndash_Admin_Import::META_KEY_IMPORTED_FROM_URL,
					$item['url']
				);
			}
		}
	}
}
