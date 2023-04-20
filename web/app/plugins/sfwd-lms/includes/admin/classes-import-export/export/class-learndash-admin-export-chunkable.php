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
	class_exists( 'Learndash_Admin_Export' ) &&
	! class_exists( 'Learndash_Admin_Export_Chunkable' )
) {
	/**
	 * Class LearnDash Admin Export.
	 *
	 * @since 4.3.0
	 */
	abstract class Learndash_Admin_Export_Chunkable extends Learndash_Admin_Export {
		const CHUNK_SIZE_ROWS  = 500;
		const CHUNK_SIZE_MEDIA = 50;

		/**
		 * Offset for the query.
		 *
		 * @since 4.3.0
		 *
		 * @var int
		 */
		protected $offset_rows = 0;

		/**
		 * Offset for the media query.
		 *
		 * @since 4.3.0
		 *
		 * @var int
		 */
		protected $offset_media = 0;

		/**
		 * Returns rows chunk size.
		 *
		 * @since 4.3.0
		 * @since 4.4.1 Deprecated `chunk_size` argument.
		 *
		 * @param mixed $chunk_size Deprecated argument.
		 *
		 * @return int
		 */
		protected function get_chunk_size_rows( $chunk_size = null ): int {
			if ( ! is_null( $chunk_size ) ) {
				_deprecated_argument( 'chunk_size', '4.4.1', 'Passing chunk_size to get_chunk_size_rows is deprecated and has no effect.' );
			}

			/**
			 * Filters the rows chunk size.
			 *
			 * @since 4.3.0
			 *
			 * @param int $chunk_size Chunk size.
			 *
			 * @return int Chunk size.
			 */
			return (int) apply_filters( 'learndash_export_chunk_size_rows', static::CHUNK_SIZE_ROWS );
		}

		/**
		 * Returns rows chunk size.
		 *
		 * @since 4.3.0
		 *
		 * @return int
		 */
		protected function get_chunk_size_media(): int {
			/**
			 * Filters the media chunk size.
			 *
			 * @since 4.3.0
			 *
			 * @param int $chunk_size Chunk size.
			 *
			 * @return int Chunk size.
			 */
			return (int) apply_filters( 'learndash_export_chunk_size_media', static::CHUNK_SIZE_MEDIA );
		}

		/**
		 * Increments rows offset by chunk size.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		protected function increment_offset_rows(): void {
			$this->offset_rows += $this->get_chunk_size_rows();
		}

		/**
		 * Increments media offset by chunk size.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		protected function increment_offset_media(): void {
			$this->offset_media += $this->get_chunk_size_media();
		}
	}
}
