<?php
/**
 * LearnDash admin filters.
 *
 * @since 4.2.0
 *
 * @package LearnDash\Filters
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Admin_Filters' ) ) {
	/**
	 * Learndash admin filters.
	 *
	 * @since 4.2.0
	 */
	class Learndash_Admin_Filters {
		const TYPE_POST_ID          = 'post_id';
		const TYPE_POST_TITLE       = 'post_title';
		const TYPE_POST_STATUS      = 'post_status';
		const TYPE_META_SWITCH      = 'meta_switch';
		const TYPE_META_SELECT      = 'meta_select';
		const TYPE_META_SELECT_AJAX = 'meta_select_ajax';
		const TYPE_SHARED_STEPS     = 'shared_steps';

		/**
		 * Loads the admin filter classes.
		 *
		 * @since 4.2.0
		 *
		 * @return void
		 */
		public static function include_classes(): void {
			require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-filters/class-learndash-admin-filter-factory.php';

			require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-filters/class-learndash-admin-filter.php';

			require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-filters/class-learndash-admin-filter-post.php';
			require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-filters/class-learndash-admin-filter-post-title.php';
			require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-filters/class-learndash-admin-filter-post-id.php';
			require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-filters/class-learndash-admin-filter-post-status.php';

			require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-filters/class-learndash-admin-filter-meta.php';
			require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-filters/class-learndash-admin-filter-shared-steps.php';
			require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-filters/class-learndash-admin-filter-meta-switch.php';
			require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-filters/class-learndash-admin-filter-meta-select.php';
			require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-filters/class-learndash-admin-filter-meta-select-ajax.php';
		}
	}
}
