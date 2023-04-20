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

			$import_logger = new Learndash_Import_Export_Logger( Learndash_Import_Export_Logger::$log_type_import );
			add_filter(
				'learndash_loggers',
				function( array $loggers ) use ( $import_logger ): array {
					$loggers[] = $import_logger;

					return $loggers;
				}
			);

			new Learndash_Admin_Import_Handler(
				new Learndash_Admin_Import_File_Handler(),
				new Learndash_Admin_Action_Scheduler( self::SCHEDULER_IMPORT_GROUP_NAME ),
				$import_logger
			);
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
