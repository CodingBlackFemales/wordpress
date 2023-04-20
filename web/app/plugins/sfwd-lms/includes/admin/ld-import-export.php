<?php
/**
 * LearnDash import/export utilities
 *
 * @since 4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// load import/export classes.
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-import-export/class-learndash-admin-import-export.php';
Learndash_Admin_Import_Export::init();
