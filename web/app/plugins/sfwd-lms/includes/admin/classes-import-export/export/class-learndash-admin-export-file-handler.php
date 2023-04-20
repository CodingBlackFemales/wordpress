<?php
/**
 * LearnDash Admin Export File Handler.
 *
 * @since 4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	class_exists( 'Learndash_Admin_Import_Export_File_Handler' ) &&
	! class_exists( 'Learndash_Admin_Export_File_Handler' )
) {
	/**
	 * Class LearnDash Admin Export File Handler.
	 *
	 * @since 4.3.0
	 */
	class Learndash_Admin_Export_File_Handler extends Learndash_Admin_Import_Export_File_Handler {
		const EXPORT_DIRECTORY             = 'learndash' . DIRECTORY_SEPARATOR . 'export';
		const LEARNDASH_EXPORT_ZIP_PATH_ID = 'learndash_export_zip';

		/**
		 * Folders to include in the zip archive.
		 *
		 * @var array
		 */
		private $raw_folders = array();

		/**
		 * Media already added to the zip archive.
		 *
		 * @var array
		 */
		private $inserted_media = array();

		/**
		 * Zip directory path.
		 *
		 * @since 4.3.0
		 *
		 * @var string
		 */
		private $zip_dir_path;

		/**
		 * Zip file url.
		 *
		 * @since 4.3.0
		 *
		 * @var string
		 */
		private $zip_archive_url;

		/**
		 * Zip file path.
		 *
		 * @since 4.3.0
		 *
		 * @var string
		 */
		private $zip_archive_path;

		/**
		 * File stream.
		 *
		 * @since 4.3.0
		 *
		 * @var resource
		 */
		private $file_stream;

		/**
		 * Constructor.
		 *
		 * @since 4.3.0.1
		 */
		public function __construct() {
			parent::__construct();

			Learndash_Admin_File_Download_Handler::register_file_path(
				self::LEARNDASH_EXPORT_ZIP_PATH_ID,
				$this->get_export_zip_directory()
			);
		}

		/**
		 * Init.
		 *
		 * @since 4.3.0
		 *
		 * @throws Exception If the "uploads" directory is not writable.
		 *
		 * @return void
		 */
		public function init(): void {
			$this->create_work_directory( self::EXPORT_DIRECTORY );
			$this->create_media_directory();
		}

		/**
		 * Opens or creates an export file in the work dir.
		 *
		 * @since 4.3.0
		 *
		 * @param string $file File name.
		 *
		 * @throws Exception If the file cannot be created.
		 *
		 * @return void
		 */
		public function open( string $file ): void {
			if ( empty( $this->work_dir ) ) {
				$this->init();
			}

			$file_path = $this->work_dir . DIRECTORY_SEPARATOR . $file;

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
			$file_stream = fopen( $file_path, 'a' );

			if ( ! $file_stream ) {
				throw new Exception( __( 'Unable to create the files to export.', 'learndash' ) );
			}

			$this->files[ $file ] = $file_path;
			$this->file_stream    = $file_stream;
		}

		/**
		 * Adds data to the current file.
		 *
		 * @since 4.3.0
		 *
		 * @param string $data Data.
		 *
		 * @throws Exception If the data cannot be added.
		 *
		 * @return void
		 */
		public function add_content( string $data ): void {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
			$result = fwrite( $this->file_stream, $data );

			if ( false === $result ) {
				throw new Exception( __( 'Unable to add data to the export file.', 'learndash' ) );
			}
		}

		/**
		 * Copy the media file into the specific work dir.
		 *
		 * @since 4.3.0
		 *
		 * @param int $media_id The media ID.
		 *
		 * @throws Exception If the media file cannot be copied.
		 *
		 * @return void
		 */
		public function add_media_file( int $media_id ): void {
			if ( empty( $this->work_dir ) ) {
				$this->init();
			}

			// skip if already added.
			if ( isset( $this->inserted_media[ $media_id ] ) ) {
				return;
			}

			$media_src_path = get_attached_file( $media_id );
			if ( ! $media_src_path || ! file_exists( $media_src_path ) ) {
				return;
			}

			$file_name = basename( $media_src_path );
			if ( isset( $this->files[ self::MEDIA_NAME . DIRECTORY_SEPARATOR . $file_name ] ) ) {
				return;
			}

			$dest_path = $this->work_dir . DIRECTORY_SEPARATOR . self::MEDIA_NAME . DIRECTORY_SEPARATOR . $file_name;

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_copy
			$result = copy( $media_src_path, $dest_path );

			if ( ! $result ) {
				throw new Exception( __( 'Unable to copy the media file.', 'learndash' ) );
			}

			$this->files[ self::MEDIA_NAME . DIRECTORY_SEPARATOR . $file_name ] = $dest_path;

			// Add info to the media.ld file.

			$media_post = get_post( $media_id );

			$media_data = array(
				'ID'             => $media_id,
				'post_title'     => $media_post->post_title,
				'post_content'   => $media_post->post_content,
				'post_excerpt'   => $media_post->post_excerpt,
				'post_name'      => $media_post->post_name,
				'post_mime_type' => $media_post->post_mime_type,
				'filename'       => $file_name,
				'url'            => $media_post->guid,
			);

			/**
			 * Filters the media object to export.
			 *
			 * @since 4.3.0
			 *
			 * @param array $media_data Media object.
			 *
			 * @return array Media object.
			 */
			$media_data = apply_filters( 'learndash_export_media_object', $media_data );

			$this->open( self::MEDIA_NAME . self::FILE_EXTENSION );
			$this->add_content( wp_json_encode( $media_data ) . PHP_EOL );
			$this->inserted_media[ $media_id ] = true;
			$this->close();
		}

		/**
		 * Adds a folder to be copied to the zip file.
		 *
		 * @since 4.3.0
		 *
		 * @param string $relative_path Relative path based on the WP uploads directory.
		 *
		 * @return void
		 */
		public function add_raw_folder( string $relative_path ) {
			$this->raw_folders[] = $relative_path;
		}

		/**
		 * Closes the current file's stream.
		 *
		 * @since 4.3.0
		 *
		 * @throws Exception If the stream cannot be closed.
		 *
		 * @return void
		 */
		public function close(): void {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
			$result = fclose( $this->file_stream );

			if ( false === $result ) {
				throw new Exception( __( 'Unable to close the export file.', 'learndash' ) );
			}
		}

		/**
		 * Returns the export zip directory path.
		 *
		 * @since 4.3.0.1
		 *
		 * @return string The export zip directory path.
		 */
		private function get_export_zip_directory(): string {
			$upload_dir = wp_upload_dir();
			$zip_dir    = DIRECTORY_SEPARATOR . self::EXPORT_DIRECTORY . DIRECTORY_SEPARATOR . 'zips';

			return $upload_dir['basedir'] . $zip_dir;
		}

		/**
		 * Init Zip.
		 *
		 * @since 4.3.0
		 *
		 * @throws Exception If the "uploads" directory is not writable.
		 */
		public function init_zip() {
			// Set the zip file dir.
			$this->zip_dir_path = $this->get_export_zip_directory();

			$zip_dir_status = wp_mkdir_p( $this->zip_dir_path );

			if ( ! $zip_dir_status ) {
				throw new Exception( __( 'Unable to create the zip directory.', 'learndash' ) );
			}

			learndash_put_directory_index_file( trailingslashit( $this->zip_dir_path ) . 'index.php' );

			// check if the zip file already exists.
			$zip_files = glob( $this->zip_dir_path . DIRECTORY_SEPARATOR . '*.zip' );

			if ( ! empty( $zip_files ) ) {
				$this->zip_archive_path = $zip_files[0];
				$this->zip_archive_url  = Learndash_Admin_File_Download_Handler::get_download_url(
					self::LEARNDASH_EXPORT_ZIP_PATH_ID,
					basename( $this->zip_archive_path )
				);
			} else {
				$zip_file_name          = 'learndash-export-' . gmdate( 'Ymd' ) . '-' . uniqid() . '.zip';
				$this->zip_archive_path = $this->zip_dir_path . DIRECTORY_SEPARATOR . $zip_file_name;
				$this->zip_archive_url  = Learndash_Admin_File_Download_Handler::get_download_url(
					self::LEARNDASH_EXPORT_ZIP_PATH_ID,
					basename( $zip_file_name )
				);
			}
		}

		/**
		 * Generates the zip file.
		 *
		 * @since 4.3.0
		 *
		 * @throws Exception If the zip file cannot be generated.
		 *
		 * @return void
		 */
		public function generate_zip_archive(): void {
			if ( empty( $this->work_dir ) ) {
				$this->init();
			}

			if ( empty( $this->zip_dir_path ) ) {
				$this->init_zip();
			}

			$zip = new ZipArchive();

			$result = $zip->open( $this->zip_archive_path, ZipArchive::CREATE | ZipArchive::OVERWRITE );
			if ( true !== $result ) {
				throw new Exception( __( 'Unable to create the zip file.', 'learndash' ) );
			}

			// add files to the zip.
			foreach ( $this->files as $name => $path ) {
				$result = $zip->addFile( $path, $name );
				if ( true !== $result ) {
					throw new Exception( __( 'Unable to add file to the zip file.', 'learndash' ) );
				}
			}

			// Add raw folders to the zip.

			$upload_dir = wp_upload_dir();

			foreach ( $this->raw_folders as $path ) {
				// get all files in the folder.
				$files = glob(
					$upload_dir['basedir'] . DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR . '*'
				);

				if ( ! empty( $files ) ) {
					foreach ( $files as $file ) {
						$file_name = basename( $file );
						if ( 'index.php' === $file_name ) { // skip index.php.
							continue;
						}
						$result = $zip->addFile( $file, $path . DIRECTORY_SEPARATOR . $file_name );
						if ( true !== $result ) {
							throw new Exception( __( 'Unable to add file to the zip file.', 'learndash' ) );
						}
					}
				}
			}

			$result = $zip->close();

			if ( true !== $result ) {
				throw new Exception( __( 'Unable to save the zip file.', 'learndash' ) );
			}

			$this->remove_directory_recursively( $this->work_dir );

			/**
			 * Fires after export zip archive is created.
			 *
			 * @since 4.3.0
			 */
			do_action( 'learndash_export_archive_created' );
		}

		/**
		 * Returns true if the zip archive exist.
		 *
		 * @since 4.3.0
		 *
		 * @return bool
		 */
		public function zip_archive_exists(): bool {
			if ( empty( $this->zip_dir_path ) ) {
				try {
					$this->init_zip();
				} catch ( Exception $e ) {
					return false;
				}
			}

			return file_exists( $this->zip_archive_path );
		}

		/**
		 * Deletes the zip archive.
		 *
		 * @since 4.3.0
		 *
		 * @throws Exception If unable to create the zip directory.
		 *
		 * @return void
		 */
		public function delete_zip_archive(): void {
			if ( empty( $this->zip_dir_path ) ) {
				$this->init_zip();
			}

			$this->remove_directory_recursively( $this->zip_dir_path );
		}

		/**
		 * Returns the zip time created.
		 *
		 * @since 4.3.0
		 *
		 * @return string
		 */
		public function get_zip_archive_time_created(): string {
			if ( ! $this->zip_archive_exists() ) {
				return '';
			}

			$wp_date_time_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

			return wp_date(
				$wp_date_time_format,
				filemtime( $this->zip_archive_path )
			);
		}

		/**
		 * Returns the zip url.
		 *
		 * @since 4.3.0
		 *
		 * @return string
		 */
		public function get_zip_archive_url(): string {
			if ( empty( $this->zip_dir_path ) ) {
				try {
					$this->init_zip();
				} catch ( Exception $e ) {
					return '';
				}
			}

			return $this->zip_archive_url;
		}
	}
}
