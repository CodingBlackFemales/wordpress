<?php
/**
 * LearnDash cloning utilities
 *
 * Used to cloning LearnDash custom posts.
 *
 * @since 4.2.0
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// create a new action scheduler group.
$ldlms_cloning_scheduler = new Learndash_Admin_Action_Scheduler( 'cloning' );

// load cloning classes.
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-cloning/class-learndash-admin-cloning.php';
Learndash_Admin_Cloning::init_classes( $ldlms_cloning_scheduler );
