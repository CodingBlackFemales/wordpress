<?php
/**
 * LearnDash Admin Import/Export Base File Handler.
 *
 * @since 4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Admin_Import_Export_File_Handler' ) ) {
	/**
	 * Class LearnDash Admin Import/Export Base File Handler.
	 *
	 * @since 4.3.0
	 */
	abstract class Learndash_Admin_Import_Export_File_Handler {
		const MEDIA_NAME = 'media';

		const FILE_EXTENSION = '.ld';

		/**
		 * Folder path to store the files to be processed.
		 *
		 * @since 4.3.0
		 *
		 * @var string
		 */
		protected $work_dir;

		/**
		 * Array of files to process.
		 *
		 * @since 4.3.0
		 *
		 * @var array
		 */
		protected $files = array();

		/**
		 * Constructor.
		 */
		public function __construct() {
			// change the wp error handler to process errors properly.
			add_filter( 'wp_die_handler', array( $this, 'handle_import_export_error' ) );
		}

		/**
		 * Processes errors for import/export.
		 *
		 * @since 4.3.0
		 *
		 * @return string Error handler.
		 */
		public function handle_import_export_error(): string {
			if ( ! empty( $this->work_dir ) && is_dir( $this->work_dir ) ) {
				$this->remove_directory_recursively( $this->work_dir );
			}

			return '_default_wp_die_handler';
		}

		/**
		 * Returns the file path.
		 *
		 * @since 4.3.0
		 *
		 * @param string $filename File name.
		 *
		 * @return string
		 */
		public function get_file_path_by_name( string $filename ): string {
			$file_name_with_extension = $filename . self::FILE_EXTENSION;

			return $this->files[ $file_name_with_extension ] ?? '';
		}

		/**
		 * Returns the media directory path.
		 *
		 * @since 4.3.0
		 *
		 * @param string $filename File name.
		 *
		 * @return string
		 */
		public function get_media_file_path_by_name( string $filename ): string {
			return $this->work_dir . DIRECTORY_SEPARATOR . self::MEDIA_NAME . DIRECTORY_SEPARATOR . $filename;
		}

		/**
		 * Deletes a directory recursively.
		 *
		 * @since 4.3.0
		 *
		 * @param string $path Directory path.
		 *
		 * @return void
		 */
		public function remove_directory_recursively( string $path ): void {
			if ( empty( $path ) || ! is_dir( $path ) ) {
				return;
			}

			$files = array_diff( scandir( $path ), array( '.', '..' ) );

			foreach ( $files as $file ) {
				if ( is_dir( $path . DIRECTORY_SEPARATOR . $file ) ) {
					$this->remove_directory_recursively( $path . DIRECTORY_SEPARATOR . $file );
				} else {
					unlink( $path . DIRECTORY_SEPARATOR . $file );
				}
			}

			rmdir( $path );
		}

		/**
		 * Creates a directory to store the files.
		 *
		 * @since 4.3.0
		 *
		 * @param string $directory Directory.
		 *
		 * @throws Exception If the directory cannot be created.
		 */
		protected function create_work_directory( string $directory ): void {
			$upload_dir = wp_upload_dir();

			if ( ! empty( $upload_dir['error'] ) ) {
				throw new Exception(
					sprintf(
						// translators: %s: upload path error.
						__( 'Unable to create the files: %s', 'learndash' ),
						$upload_dir['error']
					)
				);
			}
			$base_path = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . $directory;

			$this->work_dir = $base_path . DIRECTORY_SEPARATOR . uniqid();

			$path_status = wp_mkdir_p( $this->work_dir );

			if ( ! $path_status ) {
				throw new Exception( __( 'Unable to create the files.', 'learndash' ) );
			}

			learndash_put_directory_index_file( trailingslashit( $base_path ) . 'index.php' );
		}

		/**
		 * Creates a directory to store the media files.
		 *
		 * @since 4.3.0
		 *
		 * @throws Exception If the directory cannot be created.
		 */
		protected function create_media_directory(): void {
			$media_dir_path = $this->work_dir . DIRECTORY_SEPARATOR . self::MEDIA_NAME;

			$path_status = wp_mkdir_p( $media_dir_path );

			if ( ! $path_status ) {
				throw new Exception( __( 'Unable to create the media directory.', 'learndash' ) );
			}
		}
	}
}
