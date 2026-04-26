<?php
/**
 * LearnDash Admin Import/Export.
 *
 * @since 4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Admin_Import_Export' ) ) {
	/**
	 * Class LearnDash Admin Import/Export.
	 *
	 * @since 4.3.0
	 */
	class Learndash_Admin_Import_Export {
		const SCHEDULER_EXPORT_GROUP_NAME = 'export';
		const SCHEDULER_IMPORT_GROUP_NAME = 'import';

		const EXPORT_PATH = LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-import-export/export/';
		const IMPORT_PATH = LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-import-export/import/';
		const COMMON_PATH = LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-import-export/common/';

		/**
		 * Demo content path.
		 *
		 * @since 4.10.0
		 *
		 * @var string
		 */
		private const DEMO_CONTENT_PATH = LEARNDASH_LMS_PLUGIN_DIR . 'assets/demo/demo-content.zip';

		/**
		 * Admin import handler.
		 *
		 * @since 4.10.0
		 *
		 * @var Learndash_Admin_Import_Handler|null
		 */
		protected static $admin_import_handler;

		/**
		 * Inits.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		public static function init(): void {
			self::init_common_classes();
			self::init_import_classes();
			self::init_export_classes();
		}

		/**
		 * Imports demo content.
		 *
		 * @since 4.10.0
		 *
		 * @param string $content_zip_path Content zip path. Defaults to LearnDash demo content.
		 *
		 * @return true|WP_Error True on success. WP_Error if an error occurred.
		 */
		public static function import_demo_content( string $content_zip_path = '' ) {
			if ( empty( $content_zip_path ) ) {
				$content_zip_path = self::DEMO_CONTENT_PATH;
			}

			return self::create_import_handler()->enqueue_import_task( $content_zip_path );
		}

		/**
		 * Creates and returns the import handler.
		 *
		 * @since 4.10.0
		 *
		 * @return Learndash_Admin_Import_Handler
		 */
		public static function create_import_handler(): Learndash_Admin_Import_Handler {
			if ( self::$admin_import_handler instanceof Learndash_Admin_Import_Handler ) {
				return self::$admin_import_handler;
			}

			$import_logger = new Learndash_Import_Export_Logger( Learndash_Import_Export_Logger::$log_type_import );

			add_filter(
				'learndash_loggers',
				function( array $loggers ) use ( $import_logger ): array {
					$loggers[] = $import_logger;

					return $loggers;
				}
			);

			self::$admin_import_handler = new Learndash_Admin_Import_Handler(
				new Learndash_Admin_Import_File_Handler(),
				new Learndash_Admin_Action_Scheduler( self::SCHEDULER_IMPORT_GROUP_NAME ),
				$import_logger
			);

			return self::$admin_import_handler;
		}

		/**
		 * Inits utils classes.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		protected static function init_common_classes():void {
			require_once self::COMMON_PATH . 'class-learndash-admin-import-export-handler.php';
			require_once self::COMMON_PATH . 'class-learndash-admin-import-export-file-handler.php';
			require_once self::COMMON_PATH . 'trait-learndash-admin-import-export-post-type-settings.php';
			require_once self::COMMON_PATH . 'trait-learndash-admin-import-export-settings.php';
			require_once self::COMMON_PATH . 'trait-learndash-admin-import-export-taxonomies.php';
			require_once self::COMMON_PATH . 'trait-learndash-admin-import-export-posts.php';
			require_once self::COMMON_PATH . 'trait-learndash-admin-import-export-proquiz.php';
			require_once self::COMMON_PATH . 'trait-learndash-admin-import-export-pages.php';
			require_once self::COMMON_PATH . 'trait-learndash-admin-import-export-users.php';
			require_once self::COMMON_PATH . 'trait-learndash-admin-import-export-user-activity.php';
			require_once self::COMMON_PATH . 'trait-learndash-admin-import-export-media.php';
			require_once self::COMMON_PATH . 'trait-learndash-admin-import-export-utils.php';
		}

		/**
		 * Inits import classes.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		protected static function init_import_classes(): void {
			require_once self::IMPORT_PATH . 'class-learndash-admin-import-mapper.php';
			require_once self::IMPORT_PATH . 'class-learndash-admin-import-handler.php';
			require_once self::IMPORT_PATH . 'class-learndash-admin-import-file-handler.php';
			require_once self::IMPORT_PATH . 'class-learndash-admin-import.php';
			require_once self::IMPORT_PATH . 'class-learndash-admin-import-post-type-settings.php';
			require_once self::IMPORT_PATH . 'class-learndash-admin-import-settings.php';
			require_once self::IMPORT_PATH . 'class-learndash-admin-import-taxonomies.php';
			require_once self::IMPORT_PATH . 'class-learndash-admin-import-posts.php';
			require_once self::IMPORT_PATH . 'class-learndash-admin-import-proquiz.php';
			require_once self::IMPORT_PATH . 'class-learndash-admin-import-proquiz-statistics.php';
			require_once self::IMPORT_PATH . 'class-learndash-admin-import-pages.php';
			require_once self::IMPORT_PATH . 'class-learndash-admin-import-users.php';
			require_once self::IMPORT_PATH . 'class-learndash-admin-import-user-activity.php';
			require_once self::IMPORT_PATH . 'class-learndash-admin-import-media.php';
			require_once self::IMPORT_PATH . 'class-learndash-admin-import-associations-handler.php';

			self::create_import_handler();
		}

		/**
		 * Inits export classes.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		protected static function init_export_classes(): void {
			require_once self::EXPORT_PATH . 'interface-learndash-admin-export-has-media.php';
			require_once self::EXPORT_PATH . 'class-learndash-admin-export-mapper.php';
			require_once self::EXPORT_PATH . 'class-learndash-admin-export-handler.php';
			require_once self::EXPORT_PATH . 'class-learndash-admin-export-file-handler.php';
			require_once self::EXPORT_PATH . 'class-learndash-admin-export.php';
			require_once self::EXPORT_PATH . 'class-learndash-admin-export-chunkable.php';
			require_once self::EXPORT_PATH . 'class-learndash-admin-export-configuration.php';
			require_once self::EXPORT_PATH . 'class-learndash-admin-export-taxonomies.php';
			require_once self::EXPORT_PATH . 'class-learndash-admin-export-post-type-settings.php';
			require_once self::EXPORT_PATH . 'class-learndash-admin-export-settings.php';
			require_once self::EXPORT_PATH . 'class-learndash-admin-export-taxonomies.php';
			require_once self::EXPORT_PATH . 'class-learndash-admin-export-posts.php';
			require_once self::EXPORT_PATH . 'class-learndash-admin-export-proquiz.php';
			require_once self::EXPORT_PATH . 'class-learndash-admin-export-pages.php';
			require_once self::EXPORT_PATH . 'class-learndash-admin-export-users.php';
			require_once self::EXPORT_PATH . 'class-learndash-admin-export-user-activity.php';

			$export_logger = new Learndash_Import_Export_Logger( Learndash_Import_Export_Logger::$log_type_export );
			add_filter(
				'learndash_loggers',
				function( array $loggers ) use ( $export_logger ): array {
					$loggers[] = $export_logger;

					return $loggers;
				}
			);

			new Learndash_Admin_Export_Handler(
				new Learndash_Admin_Export_File_Handler(),
				new Learndash_Admin_Action_Scheduler( self::SCHEDULER_EXPORT_GROUP_NAME ),
				$export_logger
			);
		}
	}
}
