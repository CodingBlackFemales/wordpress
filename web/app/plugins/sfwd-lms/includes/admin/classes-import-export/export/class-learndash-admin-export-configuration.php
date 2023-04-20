<?php
/**
 * LearnDash Admin Export Configuration.
 *
 * @since   4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	class_exists( 'Learndash_Admin_Export' ) &&
	! class_exists( 'Learndash_Admin_Export_Configuration' )
) {
	/**
	 * Class LearnDash Admin Export Configuration.
	 *
	 * @since 4.3.0
	 */
	class Learndash_Admin_Export_Configuration extends Learndash_Admin_Export {
		const FILE_NAME = 'configuration';

		/**
		 * Configuration options.
		 *
		 * @since 4.3.0
		 *
		 * @var array
		 */
		private $configuration;

		/**
		 * Constructor.
		 *
		 * @since 4.3.0
		 * @since 4.5.0   Changed the $logger param to the `Learndash_Import_Export_Logger` class.
		 *
		 * @param array                               $options      Export options.
		 * @param Learndash_Admin_Export_File_Handler $file_handler File Handler class instance.
		 * @param Learndash_Import_Export_Logger      $logger       Logger class instance.
		 */
		public function __construct(
			array $options,
			Learndash_Admin_Export_File_Handler $file_handler,
			Learndash_Import_Export_Logger $logger
		) {
			global $wp_version, $wpdb;

			$this->configuration         = $options;
			$this->configuration['info'] = array(
				'ld_version'   => LEARNDASH_VERSION,
				'wp_version'   => $wp_version,
				'db_prefix'    => $wpdb->prefix,
				'is_multisite' => is_multisite(),
				'blog_id'      => get_current_blog_id(),
				'home_url'     => home_url(),
			);

			/**
			 * Filters export configuration options.
			 *
			 * @since 4.3.0
			 *
			 * @param array $configuration Configuration options.
			 *
			 * @return array Configuration options.
			 */
			$this->configuration = apply_filters( 'learndash_export_configuration', $this->configuration );

			$logger->log_options( $this->configuration );

			parent::__construct( $file_handler, $logger );
		}

		/**
		 * Returns the export file name.
		 *
		 * @since 4.3.0
		 *
		 * @return string The export file name.
		 */
		protected function get_file_name(): string {
			return self::FILE_NAME;
		}

		/**
		 * Returns the list of LD settings.
		 *
		 * @since 4.3.0
		 *
		 * @return string
		 */
		public function get_data(): string {
			return wp_json_encode( $this->configuration );
		}
	}
}
