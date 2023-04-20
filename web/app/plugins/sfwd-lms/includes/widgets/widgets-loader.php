<?php
/**
 * LearnDash Widgets Loader.
 *
 * @since 3.2.0
 * @package LearnDash\Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// This filter will parse the text of the widget for shortcodes.
add_filter( 'widget_text', 'do_shortcode' );
add_filter( 'widget_text_content', 'do_shortcode' );
add_filter( 'widget_custom_html', 'do_shortcode' );
add_filter( 'widget_custom_html_content', 'do_shortcode' );

require_once __DIR__ . '/ld_certificates.php';
require_once __DIR__ . '/ld_course_info.php';
require_once __DIR__ . '/ld_course_navigation.php';
require_once __DIR__ . '/ld_course_progress.php';
require_once __DIR__ . '/ld_course.php';
require_once __DIR__ . '/ld_lesson.php';
require_once __DIR__ . '/ld_quiz.php';
require_once __DIR__ . '/ld_transactions.php';
require_once __DIR__ . '/ld_user_status.php';
require_once __DIR__ . '/learndash_replace_widgets_alert.php';
