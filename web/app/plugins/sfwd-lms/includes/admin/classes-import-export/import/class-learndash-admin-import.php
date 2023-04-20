<?php
/**
 * LearnDash Admin Import.
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
	! class_exists( 'Learndash_Admin_Import' )
) {
	/**
	 * Class LearnDash Admin Import.
	 *
	 * @since 4.3.0
	 */
	abstract class Learndash_Admin_Import {
		use Learndash_Admin_Import_Export_Utils;

		const META_KEY_IMPORTED_FROM_POST_ID = 'learndash_imported_from_post_id';
		const META_KEY_IMPORTED_FROM_URL     = 'learndash_imported_from_url';
		const META_KEY_IMPORTED_FROM_USER_ID = 'learndash_imported_from_user_id';
		const META_KEY_IMPORTED_FROM_TERM_ID = 'learndash_imported_from_term_id';

		const TRANSIENT_KEY_STATISTIC_REF_IDS = 'learndash_statistic_ref_ids';

		/**
		 * File Handler class instance.
		 *
		 * @since 4.3.0
		 *
		 * @var Learndash_Admin_Import_File_Handler
		 */
		protected $file_handler;

		/**
		 * Logger class instance.
		 *
		 * @since 4.3.0
		 * @since 4.5.0   Changed to the `Learndash_Import_Export_Logger` class.
		 *
		 * @var Learndash_Import_Export_Logger
		 */
		protected $logger;

		/**
		 * The previous home url.
		 *
		 * @since 4.3.0
		 *
		 * @var string
		 */
		protected $home_url_previous;

		/**
		 * The current home url.
		 *
		 * @since 4.3.0
		 *
		 * @var string
		 */
		protected $home_url_current;

		/**
		 * Array of new media data indexed by old media URL.
		 *
		 * @since 4.3.0
		 *
		 * @var array
		 */
		protected $new_media_data = array();

		/**
		 * Number of processed items.
		 *
		 * @since 4.3.0
		 *
		 * @var int
		 */
		protected $processed_items_count = 0;

		/**
		 * Number of imported items.
		 * Only top level items are counted. So if it's a post with 2 terms, it will be increased by 1 only.
		 *
		 * @since 4.3.0
		 *
		 * @var int
		 */
		protected $imported_items_count = 0;

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
			$this->file_handler      = $file_handler;
			$this->logger            = $logger;
			$this->home_url_previous = $home_url;
			$this->home_url_current  = home_url();

			$this->logger->log_object(
				get_class( $this ),
				get_object_vars( $this ),
				'Initiated'
			);
		}

		/**
		 * Imports.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		public function import_data(): void {
			$this->import();

			$processed = absint( $this->processed_items_count );
			$imported  = absint( $this->imported_items_count );

			$this->logger->log_object(
				get_class( $this ),
				get_object_vars( $this ),
				'',
				"processed {$processed} record(s), imported {$imported} record(s)"
			);
		}

		/**
		 * Imports.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		abstract protected function import(): void;

		/**
		 * Returns the file name.
		 *
		 * @since 4.3.0
		 *
		 * @return string
		 */
		abstract protected function get_file_name(): string;

		/**
		 * Resets global variables that grow out of control during imports.
		 * Copied from the WP_Importer base class.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		public static function clear_wpdb_query_cache(): void {
			global $wpdb;

			$wpdb->queries = array();
		}

		/**
		 * Returns old_user_id => new_user_id hash.
		 *
		 * @since 4.3.0
		 *
		 * @return array
		 */
		public static function get_old_user_id_new_user_id_hash(): array {
			global $wpdb;

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared;
			$sql = $wpdb->prepare(
				"SELECT user_id, meta_value FROM $wpdb->usermeta WHERE meta_key = %s",
				self::META_KEY_IMPORTED_FROM_USER_ID
			);

			$result = array_column(
				$wpdb->get_results( $sql, ARRAY_A ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				'user_id',
				'meta_value' // Old User ID.
			);

			$result[0] = 0; // For guests.

			return $result;
		}

		/**
		 * Returns the file path.
		 *
		 * @since 4.3.0
		 *
		 * @return string
		 */
		protected function get_file_path(): string {
			return $this->file_handler->get_file_path_by_name(
				$this->get_file_name()
			);
		}

		/**
		 * Returns the media file path.
		 *
		 * @since 4.3.0
		 *
		 * @param string $filename File name.
		 *
		 * @return string
		 */
		protected function get_media_file_path( string $filename ): string {
			return $this->file_handler->get_media_file_path_by_name(
				$filename
			);
		}

		/**
		 * Returns a new post id by an old post id or null if not found.
		 *
		 * @since 4.3.0
		 *
		 * @param int $post_id Post ID.
		 *
		 * @return int|null
		 */
		public static function get_new_post_id_by_old_post_id( int $post_id ): ?int {
			if ( 0 === $post_id ) {
				return $post_id;
			}

			return self::get_post_id_by_meta( self::META_KEY_IMPORTED_FROM_POST_ID, $post_id );
		}

		/**
		 * Returns a new post id by an old media url or null if not found.
		 *
		 * @since 4.3.0
		 *
		 * @param string $media_url The media url.
		 *
		 * @return int|null
		 */
		protected function get_new_media_id_by_old_media_url( string $media_url ): ?int {
			return $this->get_post_id_by_meta( self::META_KEY_IMPORTED_FROM_URL, $media_url );
		}

		/**
		 * Returns a new post id by meta key and value or null if not found.
		 *
		 * @since 4.3.0
		 *
		 * @param string $meta_key   Meta key to search by.
		 * @param mixed  $meta_value Meta value to search by.
		 *
		 * @return int|null
		 */
		protected static function get_post_id_by_meta( string $meta_key, $meta_value ): ?int {
			global $wpdb;

			// define the correct meta_value placeholder.
			$placeholder = '%s';
			if ( is_numeric( $meta_value ) ) {
				$placeholder = '%d';
				$meta_value  = intval( $meta_value );
			}

			$value = $wpdb->get_var(
				// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT post_id FROM `$wpdb->postmeta` WHERE meta_key = %s AND meta_value = $placeholder ORDER BY post_id DESC",
					$meta_key,
					$meta_value
				)
			);

			return is_null( $value ) ? null : intval( $value );
		}

		/**
		 * Replaces media URLs in content with new related media URLs.
		 *
		 * @since 4.3.0
		 *
		 * @param string $string The content.
		 *
		 * @return string The content with media URLs replaced with new related ones.
		 */
		protected function replace_media_from_content( string $string ): string {
			if ( empty( $string ) ) {
				return $string;
			}

			$blocks = parse_blocks( $string );

			// block content processing.
			if (
				! empty( $blocks ) &&
				( count( $blocks ) > 1 || '' !== trim( (string) $blocks[0]['blockName'] ) )
			) {
				// changing the media content of the blocks.
				$this->replace_media_from_blocks( $blocks );

				return serialize_blocks( $blocks );
			}

			// classic content processing.
			$extracted_media_urls = $this->extract_media_urls_from_content( $string );

			return $this->replace_media_from_media_urls(
				$string,
				$extracted_media_urls[0],
				$extracted_media_urls[1]
			);
		}

		/**
		 * Extracts media URLs from content.
		 *
		 * @since 4.3.0
		 *
		 * @param string $string The content string.
		 *
		 * @return array An array with media urls and related context. (0 => media urls, 1 => media context)
		 */
		private function extract_media_urls_from_content( string $string ): array {
			$media_urls    = array();
			$media_context = array();

			if ( empty( $string ) ) {
				return array( $media_urls, $media_context );
			}

			if ( preg_match_all( '/(\[caption.*)?<img[^>]+src="([^"]+)"[^>]*>(.*\/caption\])?/', $string, $matches ) ) {
				$media_urls    = array_merge( $media_urls, $matches[2] );
				$media_context = array_merge( $media_context, $matches[0] );
			}

			if ( preg_match_all( '/<a[^>]+href="([^"]+)"[^>]*>/', $string, $matches ) ) {
				$media_urls    = array_merge( $media_urls, $matches[1] );
				$media_context = array_merge( $media_context, $matches[0] );
			}

			if ( preg_match_all( '/<(audio|video)[^>]+src="([^"]+)"[^>]*><\/(audio|video)>/', $string, $matches ) ) {
				$media_urls    = array_merge( $media_urls, $matches[2] );
				$media_context = array_merge( $media_context, $matches[0] );
			}

			if ( preg_match_all( '/<video[^>]+poster="([^"]+)"[^"]/', $string, $matches ) ) {
				$media_urls    = array_merge( $media_urls, $matches[1] );
				$media_context = array_merge( $media_context, $matches[0] );
			}

			if ( preg_match_all( '/\[(audio|video)[^]]+mp?.="([^"]+)"[]]*\[\/(audio|video)]/', $string, $matches ) ) {
				$media_urls    = array_merge( $media_urls, $matches[2] );
				$media_context = array_merge( $media_context, $matches[0] );
			}

			return array( $media_urls, $media_context );
		}

		/**
		 * Replaces media URLs in the blocks.
		 *
		 * @since 4.3.0
		 *
		 * @param array $blocks The blocks array.
		 *
		 * @return void
		 */
		private function replace_media_from_blocks( array &$blocks ): void {
			if ( empty( $blocks ) ) {
				return;
			}

			$media_blocks = $this->get_media_blocks();

			foreach ( $blocks as &$block ) {
				$block_name = trim( (string) $block['blockName'] );

				if ( empty( $block['blockName'] ) && ! empty( $block['innerHTML'] ) ) {
					// Classic content block.
					$this->replace_block_content_media_urls( $block );
				} elseif (
					isset( $media_blocks[ $block_name ] ) &&
					! empty( $block['attrs'][ $media_blocks[ $block_name ] ] )
				) {
					// Media block.

					$old_media_id = $block['attrs'][ $media_blocks[ $block_name ] ];
					$new_media_id = $this->get_new_post_id_by_old_post_id( $old_media_id );

					// change the block content.
					if ( ! empty( $new_media_id ) ) {
						$block['attrs'][ $media_blocks[ $block_name ] ] = $new_media_id;

						// url attribute.
						if ( isset( $block['attrs']['url'] ) ) {
							$block['attrs']['url'] = wp_get_attachment_url( $new_media_id );
						}

						// href attribute.
						if ( isset( $block['attrs']['href'] ) ) {
							$block['attrs']['href'] = wp_get_attachment_url( $new_media_id );
						}

						$this->replace_block_content_media_urls( $block );
					}
				}

				// Inner blocks.
				$this->replace_media_from_blocks( $block['innerBlocks'] );
			}
		}

		/**
		 * Replaces content based on extracted media URLs.
		 *
		 * @since 4.3.0
		 *
		 * @param string $string        The content.
		 * @param array  $media_urls    The media URLs array.
		 * @param array  $media_context The media URLs context array.
		 *
		 * @return string Content with replaced media URLs.
		 */
		private function replace_media_from_media_urls(
			string $string,
			array $media_urls,
			array $media_context
		): string {
			if ( empty( $string ) || empty( $media_urls ) ) {
				return $string;
			}

			$current_media_index = -1;

			foreach ( $media_urls as $media_url ) {
				$current_media_index++;

				// get new media id.
				$media_url_without_dimensions = preg_replace(
					'/-([0-9][0-9]*[xX][0-9][0-9]*|scaled)\./',
					'.',
					$media_url
				);

				if ( ! isset( $this->new_media_data[ $media_url_without_dimensions ] ) ) {
					$new_post_id = $this->get_new_media_id_by_old_media_url( $media_url_without_dimensions );
					if ( empty( $new_post_id ) ) {
						continue; // no new media found.
					}

					$this->new_media_data[ $media_url_without_dimensions ] = array(
						'new_post_id' => $new_post_id,
						'new_url'     => wp_get_attachment_url( $new_post_id ),
					);
				}

				// define the new media url.
				$new_media_url = $this->new_media_data[ $media_url_without_dimensions ]['new_url'];

				if ( $media_url !== $media_url_without_dimensions ) {
					if ( preg_match( '/(-[0-9][0-9]*[xX][0-9][0-9]*)(\.[^"]+)/', $media_url, $matches ) ) {
						// change the end of the media url to the dimensions end.
						$new_media_url = str_replace( $matches[2], $matches[0], $new_media_url );
					}
				}

				// replace url in context.
				$new_media_context = str_replace( $media_url, $new_media_url, $media_context[ $current_media_index ] );

				// replace classes and ids in context.
				$new_post_id       = $this->new_media_data[ $media_url_without_dimensions ]['new_post_id'];
				$new_media_context = preg_replace(
					'/wp-image-[0-9]*/',
					"wp-image-$new_post_id",
					$new_media_context
				);
				$new_media_context = preg_replace(
					'/attachment_[0-9]*/',
					"attachment_$new_post_id",
					$new_media_context
				);

				// replace de context in the string.
				$string = str_replace( $media_context[ $current_media_index ], $new_media_context, $string );
			}

			return $string;
		}

		/**
		 * Returns a decoded json data.
		 *
		 * @since 4.3.0
		 *
		 * @return array
		 */
		protected function load_and_decode_file(): array {
			$file_path = $this->get_file_path();

			if ( ! file_exists( $file_path ) || 0 === filesize( $file_path ) ) {
				return array();
			}

			$decoded_file_data = wp_json_file_decode(
				$file_path,
				array(
					'associative' => true,
				)
			);

			/**
			 * Filters decoded file data for files that are being imported as a whole.
			 *
			 * @since 4.3.0
			 *
			 * @param array  $decoded_file_data Decoded file data.
			 * @param string $file_path         File path.
			 *
			 * @return array Decoded file data.
			 */
			return apply_filters( 'learndash_import_decoded_file_data', $decoded_file_data, $file_path );
		}

		/**
		 * Returns lines of the imported file.
		 *
		 * @since 4.3.0
		 *
		 * @return Generator
		 */
		protected function get_file_lines(): Generator {
			return $this->file_handler->get_items(
				$this->get_file_path()
			);
		}

		/**
		 * Replaces media urls in block's HTML and content.
		 *
		 * @since 4.3.0
		 *
		 * @param array $block Gutenberg block.
		 *
		 * @return void
		 */
		private function replace_block_content_media_urls( array &$block ): void {
			// Inner HTML.
			if ( ! empty( $block['innerHTML'] ) ) {
				$extracted_media_urls = $this->extract_media_urls_from_content( $block['innerHTML'] );
				$block['innerHTML']   = $this->replace_media_from_media_urls(
					$block['innerHTML'],
					$extracted_media_urls[0],
					$extracted_media_urls[1]
				);
			}

			// Inner content.
			if ( is_array( $block['innerContent'] ) ) {
				foreach ( $block['innerContent'] as &$inner_content ) {
					if ( empty( $inner_content ) ) {
						continue;
					}

					$extracted_media_urls = $this->extract_media_urls_from_content( $inner_content );
					$inner_content        = $this->replace_media_from_media_urls(
						$inner_content,
						$extracted_media_urls[0],
						$extracted_media_urls[1]
					);
				}
			}
		}
	}
}
