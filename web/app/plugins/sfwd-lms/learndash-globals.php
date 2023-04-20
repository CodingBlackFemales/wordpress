<?php
/**
 * LearnDash global variables.
 *
 * @since 4.5.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Globals that hold CPT's and Pages to be set up
 */
global $learndash_taxonomies, $learndash_pages, $learndash_question_types;

$learndash_taxonomies = array(
	'ld_course_category',
	'ld_course_tag',
	'ld_lesson_category',
	'ld_lesson_tag',
	'ld_topic_category',
	'ld_topic_tag',
	'ld_quiz_category',
	'ld_quiz_tag',
	'ld_question_category',
	'ld_question_tag',
	'ld_group_category',
	'ld_group_tag',
);

$learndash_pages = array(
	'group_admin_page',
	'learndash-lms-reports',
);

// This is a global variable which is set in any of the shortcode handler functions.
// The purpose is to let the plugin know when and if the any of the shortcodes were used.
global $learndash_shortcode_used;
$learndash_shortcode_used = false;

global $learndash_shortcode_atts;
$learndash_shortcode_atts = array();

/**
 * Metaboxes registered for settings pages etc.
 */
global $learndash_metaboxes;
$learndash_metaboxes = array();

global $learndash_assets_loaded;
$learndash_assets_loaded            = array();
$learndash_assets_loaded['styles']  = array();
$learndash_assets_loaded['scripts'] = array();
