<?php
/**
 * This class provides an easy way to log everything.
 *
 * @since 4.5.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Logger' ) ) {
	/**
	 * Logger class.
	 *
	 * @since 4.5.0
	 */
	abstract class Learndash_Logger {
		private const PATH_ID                   = 'learndash_logs';
		private const PATH                      = 'learndash' . DIRECTORY_SEPARATOR . 'logs';
		private const EXTENSION                 = '.log';
		private const SUFFIXES_META_KEY         = 'learndash_log_suffixes';
		private const DEFAULT_MAX_SIZE_IN_BYTES = 10485760; // 10MB.

		/**
		 * Instructions to protect the path against direct access.
		 *
		 * @since 4.5.0
		 *
		 * @var string
		 */
		private static $file_protection_instructions = '';

		/**
		 * Log file stream.
		 *
		 * @since 4.5.0
		 *
		 * @var resource|null|false
		 */
		private $log_stream = null;

		/**
		 * List on initialized loggers.
		 *
		 * @since 4.5.0
		 *
		 * @var Learndash_Logger[]
		 */
		private static $loggers = array();

		/**
		 * Returns the logger instance if available.
		 *
		 * @since 4.5.0
		 *
		 * @param string $name Logger name.
		 *
		 * @return Learndash_Logger|null
		 */
		public static function get_instance( string $name ): ?self {
			return self::$loggers[ $name ] ?? null;
		}

		/**
		 * Logger destructor.
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		public function __destruct() {
			if ( is_resource( $this->log_stream ) ) {
				fclose( $this->log_stream ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
			}
		}

		/**
		 * Inits a logger.
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		final public function init(): void {
			self::$loggers[ $this->get_name() ] = $this;
		}

		/**
		 * Returns the label.
		 *
		 * @since 4.5.0
		 *
		 * @return string
		 */
		abstract public function get_label(): string;

		/**
		 * Returns the name.
		 *
		 * @since 4.5.0
		 *
		 * @return string
		 */
		abstract public function get_name(): string;

		/**
		 * Creates the logger resource if needed.
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		private function maybe_create_resource() {
			if ( is_resource( $this->log_stream ) ) {
				return;
			}

			try {
				$this->maybe_rotate_file();

				$this->log_stream = fopen( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
					self::get_log_path( $this->get_name() ),
					'a'
				);
			} catch ( Exception $e ) {
				return;
			}
		}

		/**
		 * Rotates the log file if needed.
		 *
		 * @return void
		 */
		private function maybe_rotate_file() {
			$log_path = self::get_log_path( $this->get_name() );
			if ( ! is_file( $log_path ) ) {
				return;
			}

			$file_size = filesize( $log_path );
			if ( ! $file_size ) {
				return;
			}

			/**
			 * Filters the maximum log file size in bytes.
			 *
			 * @since 4.5.0
			 *
			 * @param int              $max_size         Maximum log file size in bytes.
			 * @param Learndash_Logger $learndash_logger Logger instance.
			 */
			$max_file_size = apply_filters( 'learndash_logger_max_file_size', self::DEFAULT_MAX_SIZE_IN_BYTES, $this );
			if ( $file_size < $max_file_size ) {
				return;
			}

			// calculate the new size.
			$new_file_size = min( $max_file_size, $file_size * 0.9 ); // reduce the file size by 10%.

			// create a temporary file to save the new data.
			$temp_path = $log_path . '.tmp';
			$temp_file = fopen( $temp_path, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen

			if ( ! $temp_file ) {
				return;
			}

			// copy the data from the log file to the temporary file skipping the first part.
			$log_file = fopen( $log_path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen

			if ( ! $log_file ) {
				return;
			}

			fseek( $log_file, (int) ( $file_size - $new_file_size ) );
			while ( ! feof( $log_file ) ) {
				if ( $content = fgets( $log_file ) ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.Found,Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
					fwrite( $temp_file, $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
				}
			}

			// move the temporary file to the log file.
			rename( $temp_path, $log_path );

			fclose( $temp_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
			fclose( $log_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
		}

		/**
		 * Writes to the log file.
		 *
		 * @since 4.5.0
		 *
		 * @param string $formatted_message Formatted message.
		 * @param string $prefix            Prefix. Default empty.
		 *
		 * @return void
		 */
		private function write( string $formatted_message, string $prefix = '' ): void {
			try {
				if ( ! $this->is_enabled() ) {
					return;
				}
			} catch ( Exception $e ) {
				return;
			}

			$this->maybe_create_resource();
			if ( ! is_resource( $this->log_stream ) ) {
				return;
			}

			if ( ! empty( $prefix ) ) {
				$formatted_message = '[' . $prefix . '] ' . $formatted_message;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
			fwrite( $this->log_stream, current_time( 'Y-m-d H:i:s: ' ) . $formatted_message . PHP_EOL );
		}

		/**
		 * Writes an information to the log file.
		 *
		 * @since 4.5.0
		 *
		 * @param string $message Message.
		 * @param string $prefix  Prefix. Optional.
		 *
		 * @return void
		 */
		public function info( string $message, string $prefix = '' ): void {
			$this->write( $message, $prefix );
		}

		/**
		 * Writes an error to the log file.
		 *
		 * @since 4.5.0
		 *
		 * @param string $message Message.
		 * @param string $prefix Prefix. Optional.
		 *
		 * @return void
		 */
		public function error( string $message, string $prefix = '' ): void {
			$this->write( 'Error: ' . $message, $prefix );
		}

		/**
		 * Returns the download URL.
		 *
		 * @since 4.5.0
		 *
		 * @return string
		 */
		public function get_download_url(): string {
			return Learndash_Admin_File_Download_Handler::get_download_url(
				self::PATH_ID,
				basename( $this->get_log_path( $this->get_name() ) )
			);
		}

		/**
		 * Checks if the logger file exists.
		 *
		 * @since 4.5.0
		 *
		 * @return bool True if the logger file exists, false otherwise.
		 */
		public function log_exists(): bool {
			return file_exists( $this->get_log_path( $this->get_name() ) );
		}

		/**
		 * Returns the log content.
		 *
		 * @since 4.5.0
		 *
		 * @return string
		 */
		public function get_content(): string {
			$log_path = $this->get_log_path( $this->get_name() );

			if ( ! file_exists( $log_path ) ) {
				return '';
			}

			$content = file_get_contents( $log_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

			if ( false === $content ) {
				return '';
			}

			return $content;
		}

		/**
		 * Deletes the log content.
		 *
		 * @since 4.5.0
		 *
		 * @return bool
		 */
		public function delete_content(): bool {
			$log_path = $this->get_log_path( $this->get_name() );

			if ( ! file_exists( $log_path ) ) {
				return true;
			}

			return unlink( $log_path );
		}

		/**
		 * Returns the path to the log file in the "uploads" directory.
		 *
		 * @param string $file_name Log file name. Default empty.
		 *
		 * @since 4.5.0
		 *
		 * @return string
		 */
		private static function get_log_path( string $file_name = '' ): string {
			$log_path = trailingslashit( wp_upload_dir()['basedir'] ) . self::PATH;

			if ( ! empty( $file_name ) ) {
				$logs_suffixes = (array) get_option( self::SUFFIXES_META_KEY, array() );

				if ( ! isset( $logs_suffixes[ $file_name ] ) ) {
					$logs_suffixes[ $file_name ] = uniqid( '', true );
					update_option( self::SUFFIXES_META_KEY, $logs_suffixes );
				}

				$log_path .= DIRECTORY_SEPARATOR . $file_name . $logs_suffixes[ $file_name ] . self::EXTENSION;
			}

			return $log_path;
		}

		/**
		 * Initialize the log directory
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		public static function init_log_directory() {
			$log_dir = self::get_log_path();

			if ( ! is_dir( $log_dir ) ) {
				wp_mkdir_p( $log_dir );
			}

			learndash_put_directory_index_file( trailingslashit( $log_dir ) . 'index.php' );

			Learndash_Admin_File_Download_Handler::register_file_path( self::PATH_ID, $log_dir );

			self::$file_protection_instructions = Learndash_Admin_File_Download_Handler::try_to_protect_file_path( $log_dir );
		}

		/**
		 * Returns the file protection instructions.
		 *
		 * @since 4.5.0
		 *
		 * @return string
		 */
		public static function get_file_protection_instructions(): string {
			return self::$file_protection_instructions;
		}

		/**
		 * Gets loggers select. Keys are logger names and values are logger labels.
		 *
		 * @since 4.5.0
		 *
		 * @return array<string,string>
		 */
		public static function get_select_list(): array {
			$result = array();

			foreach ( self::$loggers as $logger ) {
				$result[ $logger->get_name() ] = $logger->get_label();
			}

			return $result;
		}

		/**
		 * Returns true if the logger is enabled, false otherwise.
		 *
		 * @since 4.5.0
		 *
		 * @throws Exception If the call is too early.
		 *
		 * @return bool
		 */
		public function is_enabled(): bool {
			if ( empty( self::$loggers ) ) {
				throw new Exception( 'The loggers are not initialized yet.' );
			}

			$enabled = LearnDash_Settings_Section::get_section_setting(
				'LearnDash_Settings_Section_Logs',
				$this->get_name()
			);

			return 'yes' === $enabled;
		}
	}
}
