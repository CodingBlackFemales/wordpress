<?php
/**
 * LearnDash Admin Export.
 *
 * @since 4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	trait_exists( 'Learndash_Admin_Import_Export_Utils' ) &&
	! class_exists( 'Learndash_Admin_Export' )
) {
	/**
	 * Class LearnDash Admin Export.
	 *
	 * @since 4.3.0
	 */
	abstract class Learndash_Admin_Export {
		use Learndash_Admin_Import_Export_Utils;

		const CHUNK_SIZE_ROWS  = 500;
		const CHUNK_SIZE_MEDIA = 50;

		/**
		 * File Handler class instance.
		 *
		 * @since 4.3.0
		 *
		 * @var Learndash_Admin_Export_File_Handler
		 */
		private $file_handler;

		/**
		 * Logger class instance.
		 *
		 * @since 4.3.0
		 * @since 4.5.0   Changed to the `Learndash_Import_Export_Logger` class.
		 *
		 * @var Learndash_Import_Export_Logger
		 */
		private $logger;

		/**
		 * Constructor.
		 *
		 * @since 4.3.0
		 * @since 4.5.0   Changed the $logger param to the `Learndash_Import_Export_Logger` class.
		 *
		 * @param Learndash_Admin_Export_File_Handler $file_handler File Handler class instance.
		 * @param Learndash_Import_Export_Logger      $logger       Logger class instance.
		 *
		 * @return void
		 */
		public function __construct(
			Learndash_Admin_Export_File_Handler $file_handler,
			Learndash_Import_Export_Logger $logger
		) {
			$this->file_handler = $file_handler;
			$this->logger       = $logger;

			$this->logger->log_object(
				get_class( $this ),
				get_object_vars( $this ),
				'Initiated'
			);
		}

		/**
		 * Returns data to export.
		 *
		 * @since 4.3.0
		 *
		 * @return string
		 */
		abstract public function get_data(): string;

		/**
		 * Returns the export file name.
		 *
		 * @since 4.3.0
		 *
		 * @return string The export file name.
		 */
		abstract protected function get_file_name(): string;

		/**
		 * Generates the export file.
		 *
		 * @since 4.3.0
		 *
		 * @throws Exception If unable to close the export file.
		 *
		 * @return void
		 */
		public function generate_export_file(): void {
			$this->file_handler->open(
				$this->get_file_name_with_extension( $this->get_file_name() )
			);

			$exported_items_count = 0;

			if ( $this instanceof Learndash_Admin_Export_Chunkable ) {
				// phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
				while ( ! empty( $data = $this->get_data() ) ) {
					$this->file_handler->add_content( $data );

					$exported_items_count += count( explode( PHP_EOL, $data ) ) - 1;
				}
			} else {
				$data = $this->get_data();
				$this->file_handler->add_content( $data );

				$exported_items_count += count( explode( PHP_EOL, $data ) );
			}

			$this->logger->log_object(
				get_class( $this ),
				get_object_vars( $this ),
				'',
				"exported $exported_items_count record(s)"
			);

			$this->file_handler->close();
		}

		/**
		 * Exports related media files.
		 *
		 * @since 4.3.0
		 *
		 * @throws Exception If unable to copy the media file.
		 *
		 * @return void
		 */
		public function export_media_files(): void {
			if ( ! $this instanceof Learndash_Admin_Export_Has_Media ) {
				return;
			}

			$exported_media_count = 0;

			if ( $this instanceof Learndash_Admin_Export_Chunkable ) {
				// phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
				while ( ! empty( $media = $this->get_media() ) ) {
					foreach ( $media as $media_id ) {
						$this->file_handler->add_media_file( $media_id );
						$exported_media_count++;
					}
				}
			} else {
				foreach ( $this->get_media() as $media_id ) {
					$this->file_handler->add_media_file( $media_id );
					$exported_media_count++;
				}
			}

			$this->logger->log_object(
				get_class( $this ),
				get_object_vars( $this ),
				'',
				"exported $exported_media_count media(s)"
			);

			// legacy media export.
			if ( $this instanceof Learndash_Admin_Export_Posts ) {
				if ( learndash_get_post_type_slug( 'assignment' ) === $this->post_type ) {
					// assignments.
					$this->file_handler->add_raw_folder( 'assignments' );
				} elseif ( learndash_get_post_type_slug( 'essay' ) === $this->post_type ) {
					// essays.
					$this->file_handler->add_raw_folder( 'essays' );
				}
			}
		}

		/**
		 * Returns the full file name with the extension.
		 *
		 * @since 4.3.0
		 *
		 * @param string $name File name.
		 *
		 * @return string
		 */
		protected function get_file_name_with_extension( string $name ): string {
			return $name . $this->file_handler::FILE_EXTENSION;
		}

		/**
		 * Get a list of media IDs to export for a given content.
		 *
		 * @since 4.3.0
		 *
		 * @param string $string The content.
		 *
		 * @return array An array of media IDs.
		 */
		protected function get_media_ids_from_string( string $string ): array {
			$media_ids = array();

			if ( empty( $string ) ) {
				return $media_ids;
			}

			$blocks = parse_blocks( $string );

			// block content processing.
			if (
				! empty( $blocks ) &&
				( count( $blocks ) > 1 || '' !== trim( $blocks[0]['blockName'] ) )
			) {
				return $this->get_media_ids_from_blocks( $blocks );
			}

			// classic content processing.
			$media_urls = array();

			if ( preg_match_all( '/<img[^>]+src="([^"]+)"[^>]*>/', $string, $matches ) ) {
				$media_urls = array_merge( $media_urls, $matches[1] );
			}

			if ( preg_match_all( '/<a[^>]+href="([^"]+)"[^>]*>/', $string, $matches ) ) {
				$media_urls = array_merge( $media_urls, $matches[1] );
			}

			if ( preg_match_all( '/<video[^>]+poster="([^"]+)"[^>]*>/', $string, $matches ) ) {
				$media_urls = array_merge( $media_urls, $matches[1] );
			}

			if ( preg_match_all( '/\[(video|audio)[^]]+mp?.="([^"]+)"[]]*\[\/(video|audio)]/', $string, $matches ) ) {
				$media_urls = array_merge( $media_urls, $matches[2] );
			}

			return $this->get_media_ids_from_media_urls( $media_urls );
		}

		/**
		 * Returns media IDs for given blocks.
		 *
		 * @since 4.3.0
		 *
		 * @param array $blocks The blocks array.
		 *
		 * @return array An array of media IDs.
		 */
		private function get_media_ids_from_blocks( array $blocks ): array {
			if ( empty( $blocks ) ) {
				return array();
			}

			$media_blocks = $this->get_media_blocks();

			$media_ids = array();

			foreach ( $blocks as $block ) {
				$block_name = trim( (string) $block['blockName'] );

				// A classic content block or a video block containing a poster.
				if (
					( empty( $block_name ) || 'core/video' === $block_name )
					&& ! empty( $block['innerHTML'] )
				) {
					$media_ids = array_merge(
						$media_ids,
						$this->get_media_ids_from_string( $block['innerHTML'] )
					);
				}

				// Media block with a media attribute.
				if (
					isset( $media_blocks[ $block_name ] ) &&
					! empty( $block['attrs'][ $media_blocks[ $block_name ] ] )
				) {
					$media_ids[] = $block['attrs'][ $media_blocks[ $block_name ] ];
				}

				// Inner blocks.
				$media_ids = array_merge(
					$media_ids,
					$this->get_media_ids_from_blocks( $block['innerBlocks'] )
				);
			}

			return $media_ids;
		}

		/**
		 * Returns media IDs for given media URLs.
		 *
		 * @since 4.3.0
		 *
		 * @param array $media_urls The media URLs array.
		 *
		 * @return array An array of media IDs.
		 */
		private function get_media_ids_from_media_urls( array $media_urls ): array {
			$media_ids = array();

			if ( empty( $media_urls ) ) {
				return $media_ids;
			}

			// remove image dimensions.
			$media_urls = preg_replace(
				'/-([0-9][0-9]*[xX][0-9][0-9]*)\./',
				'.',
				$media_urls
			);
			$media_urls = array_unique( $media_urls );

			foreach ( $media_urls as $media_url ) {
				$media_id = attachment_url_to_postid( $media_url );

				if ( ! empty( $media_id ) ) {
					$media_ids[] = $media_id;
				}
			}

			return $media_ids;
		}
	}
}
