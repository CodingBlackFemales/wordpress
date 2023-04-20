<?php
/**
 * LearnDash Bulk Edit Actions class.
 *
 * @since 4.2.0
 *
 * @package LearnDash\Bulk_Edit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Admin_Bulk_Edit_Actions' ) ) {
	/**
	 * Learndash Bulk Edit Actions class.
	 *
	 * @since 4.2.0
	 */
	class Learndash_Admin_Bulk_Edit_Actions {
		const BULK_EDIT_ACTION_SCHEDULER_GROUP = 'bulk-edit';

		/**
		 * Array of Bulk edit classes instances.
		 *
		 * @since 4.2.0
		 *
		 * @var Learndash_Admin_Bulk_Edit_Action[]
		 */
		private static $bulk_classes = array();

		/**
		 * Inits the bulk edit classes.
		 *
		 * @since 4.2.0
		 *
		 * @return void
		 */
		public static function init_classes(): void {
			// Include dependencies.
			self::include_dependencies();

			$bulk_classes = array(
				array(
					'file_path'    => 'class-learndash-admin-bulk-edit-action-courses.php',
					'class_name'   => Learndash_Admin_Bulk_Edit_Action_Courses::class,
					'dependencies' => array(
						new LearnDash_Settings_Metabox_Course_Access_Settings(),
					),
				),
				array(
					'file_path'    => 'class-learndash-admin-bulk-edit-action-groups.php',
					'class_name'   => Learndash_Admin_Bulk_Edit_Action_Groups::class,
					'dependencies' => array(
						new LearnDash_Settings_Metabox_Group_Access_Settings(),
					),
				),
				array(
					'file_path'    => 'class-learndash-admin-bulk-edit-action-lessons.php',
					'class_name'   => Learndash_Admin_Bulk_Edit_Action_Lessons::class,
					'dependencies' => array(
						new LearnDash_Settings_Metabox_Lesson_Display_Content(),
					),
				),
			);

			$folder_path = LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-bulk-edit-actions/';

			foreach ( $bulk_classes as $class ) {
				require_once $folder_path . $class['file_path'];

				self::$bulk_classes[ $class['class_name'] ] = new $class['class_name']( ...$class['dependencies'] );
			}

			/**
			 * Filters bulk edit classes.
			 *
			 * @since 4.2.0
			 *
			 * @param array $bulk_classes Bulk Edit Classes.
			 */
			self::$bulk_classes = apply_filters( 'learndash_bulk_edit_classes', self::$bulk_classes );

			self::$bulk_classes = array_filter(
				self::$bulk_classes,
				function( $bulk_class ) {
					return $bulk_class instanceof Learndash_Admin_Bulk_Edit_Action;
				}
			);

			// creating the scheduler instance.
			$bulk_edit_scheduler = new Learndash_Admin_Action_Scheduler( self::BULK_EDIT_ACTION_SCHEDULER_GROUP );

			foreach ( self::$bulk_classes as $class ) {
				$class->init( $bulk_edit_scheduler );
			}
		}

		/**
		 * Returns registered classes.
		 *
		 * @since 4.2.0
		 *
		 * @return Learndash_Admin_Bulk_Edit_Action[]
		 */
		public static function get_classes(): array {
			return self::$bulk_classes;
		}

		/**
		 * Includes dependencies.
		 *
		 * @since 4.2.0
		 *
		 * @return void
		 */
		private static function include_dependencies(): void {
			Learndash_Admin_Filters::include_classes();

			require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-bulk-edit-actions/class-learndash-admin-bulk-edit-action.php';
			require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-bulk-edit-actions/class-learndash-admin-bulk-edit-field.php';

			require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php';
			require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/settings/settings-metaboxes/class-ld-settings-metabox-group-access-settings.php';
			require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/settings/settings-metaboxes/class-ld-settings-metabox-lesson-display-content.php';
		}
	}
}
