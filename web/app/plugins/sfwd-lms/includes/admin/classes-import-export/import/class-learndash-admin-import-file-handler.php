<?php
/**
 * LearnDash Admin Import File Handler.
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
	! class_exists( 'Learndash_Admin_Import_File_Handler' )
) {
	/**
	 * Class LearnDash Admin Import File Handler.
	 *
	 * @since 4.3.0
	 */
	class Learndash_Admin_Import_File_Handler extends Learndash_Admin_Import_Export_File_Handler {
		const IMPORT_DIRECTORY = 'learndash' . DIRECTORY_SEPARATOR . 'import';

		/**
		 * Init.
		 *
		 * @since 4.3.0
		 *
		 * @throws Exception If the "uploads" directory is not writable.
		 *
		 * @return void
		 */
		protected function init(): void {
			$this->create_work_directory( self::IMPORT_DIRECTORY );
		}

		/**
		 * Unzips an archive and saves to the import folder.
		 *
		 * @since 4.3.0
		 *
		 * @param ZipArchive $zip_archive Archive.
		 *
		 * @throws Exception If the "uploads" directory is not writable.
		 *
		 * @return string Working directory.
		 */
		public function unzip( ZipArchive $zip_archive ): string {
			if ( empty( $this->work_dir ) ) {
				$this->init();
			}

			$zip_archive->extractTo( $this->work_dir );

			return $this->work_dir;
		}

		/**
		 * Sets the working directory.
		 *
		 * @since 4.3.0
		 *
		 * @param string $path Path.
		 *
		 * @return void
		 */
		public function set_working_directory( string $path ): void {
			if ( empty( $path ) || ! is_dir( $path ) ) {
				return;
			}

			$this->work_dir = $path;

			$files = array_diff( scandir( $path ), array( '.', '..' ) );

			foreach ( $files as $file ) {
				$this->files[ $file ] = $path . DIRECTORY_SEPARATOR . $file;
			}
		}

		/**
		 * Allows to read the file by lines with a foreach loop.
		 *
		 * @since 4.3.0
		 *
		 * @param string $file_path File path.
		 *
		 * @return Generator
		 */
		public function get_items( string $file_path ): Generator {
			if ( ! file_exists( $file_path ) || 0 === filesize( $file_path ) ) {
				return;
			}

			$file = fopen( $file_path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen

			if ( ! $file ) {
				return;
			}

			// phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
			while ( false !== ( $line = fgets( $file ) ) ) {
				$decoded_line = json_decode( $line, true );

				/**
				 * Filters decoded file line data for files that are being imported per line.
				 *
				 * @since 4.3.0
				 *
				 * @param array  $decoded_line Decoded file line data.
				 * @param string $file_path    File path.
				 *
				 * @return array Decoded file line data.
				 */
				yield apply_filters( 'learndash_import_decoded_file_line_data', $decoded_line, $file_path );
			}

			fclose( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
		}
	}
}
