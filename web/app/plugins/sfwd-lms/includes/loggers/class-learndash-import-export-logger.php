<?php
/**
 * This class provides an easy way to import and export processes.
 *
 * @since 4.5.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Import_Export_Logger' ) && class_exists( 'Learndash_Logger' ) ) {
	/**
	 * Import/Export logger class.
	 *
	 * @since 4.5.0
	 */
	class Learndash_Import_Export_Logger extends Learndash_Logger {
		/**
		 * Log type import.
		 *
		 * @since 4.5.0
		 *
		 * @var string
		 */
		public static $log_type_import = 'importing';

		/**
		 * Log type export.
		 *
		 * @since 4.5.0
		 *
		 * @var string
		 */
		public static $log_type_export = 'exporting';

		/**
		 * Log type. Should be one of the LOG_TYPE_* constants.
		 *
		 * @since 4.5.0
		 *
		 * @var string
		 */
		private $log_type;

		/**
		 * Logger constructor.
		 *
		 * @since 4.5.0
		 *
		 * @param string $log_type Log type. Should be one of the LOG_TYPE_* constants.
		 *
		 * @throws InvalidArgumentException If log type is invalid.
		 *
		 * @return void
		 */
		public function __construct( string $log_type ) {
			if ( ! in_array( $log_type, array( self::$log_type_import, self::$log_type_export ), true ) ) {
				throw new InvalidArgumentException( 'Invalid log type.' );
			}

			$this->log_type = $log_type;
		}

		/**
		 * Returns the label.
		 *
		 * @since 4.5.0
		 *
		 * @return string
		 */
		public function get_label(): string {
			switch ( $this->log_type ) {
				case self::$log_type_import:
					return esc_html__( 'Data Importing', 'learndash' );

				case self::$log_type_export:
					return esc_html__( 'Data Exporting ', 'learndash' );

				default:
					return '';
			}
		}

		/**
		 * Returns the name.
		 *
		 * @since 4.5.0
		 *
		 * @return string
		 */
		public function get_name(): string {
			return 'import_export_' . $this->log_type;
		}

		/**
		 * Maps options and writes to the log file.
		 *
		 * @since 4.5.0
		 *
		 * @param array<string,mixed> $options Options.
		 *
		 * @return void
		 */
		public function log_options( array $options ): void {
			$lines = array(
				'Options:',
			);

			foreach ( $options as $key => $values ) {
				$lines[] = "$key: " . wp_json_encode( $values );
			}

			$this->info(
				implode( PHP_EOL, $lines )
			);
		}

		/**
		 * Maps class name with object vars and writes to the log file.
		 *
		 * @since 4.5.0
		 *
		 * @param string              $classname      Class name.
		 * @param array<string,mixed> $object_vars    Object properties.
		 * @param string              $message_before Message before.
		 * @param string              $message_after  Message after.
		 *
		 * @return void
		 */
		public function log_object(
			string $classname,
			array $object_vars,
			string $message_before = '',
			string $message_after = ''
		): void {
			$object_vars = array_filter(
				$object_vars,
				function ( $value, $key ) {
					return ! is_object( $value ) && ! in_array(
						$key,
						array( 'offset_rows', 'offset_media' ),
						true
					);
				},
				ARRAY_FILTER_USE_BOTH
			);

			$additional_info = '';
			if ( ! empty( $object_vars ) ) {
				$additional_info .= ' with properties' . PHP_EOL;
				$additional_info .= implode(
					' and ',
					array_map(
						function ( string $key, $value ) {
							return "$key: " . wp_json_encode( $value );
						},
						array_keys( $object_vars ),
						array_values( $object_vars )
					)
				);
			}

			if ( ! empty( $message_before ) ) {
				$message_before = $message_before . PHP_EOL;
			}
			if ( ! empty( $message_after ) ) {
				$message_after = PHP_EOL . $message_after;
			}

			$this->info( $message_before . $classname . $additional_info . $message_after );
		}
	}
}
