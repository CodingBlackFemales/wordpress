<?php
/**
 * Print a LearnDash course's structure in course order: sections (modules),
 * lessons, topics and quizzes, with post IDs and statuses.
 *
 * Run with WP-CLI from the repository root:
 *   wp @dev-academy eval-file .agents/skills/cbf-slides-migration/scripts/course_structure.php <course-id>
 *
 * Uses LearnDash's course-steps API, so it includes lessons shared from other
 * courses (Shared Course Steps), which a `course_id` meta query misses.
 *
 * @package CBF
 */

if ( ! defined( 'ABSPATH' ) || ! function_exists( 'learndash_course_get_steps_by_type' ) ) {
	fwrite( STDERR, "Run this with `wp eval-file` on a site with LearnDash active.\n" );
	return;
}

$course_id = isset( $args[0] ) ? absint( $args[0] ) : 0;
if ( ! $course_id || 'sfwd-courses' !== get_post_type( $course_id ) ) {
	fwrite( STDERR, "Usage: wp eval-file course_structure.php <course-id>\n" );
	return;
}

$cbf_line = static function ( $depth, $label, $post_id ) {
	printf(
		"%s- %s %d [%s] %s\n",
		str_repeat( '  ', $depth ),
		$label,
		$post_id,
		get_post_status( $post_id ),
		html_entity_decode( get_the_title( $post_id ), ENT_QUOTES )
	);
};

printf( "# %s (%d)\n\n", html_entity_decode( get_the_title( $course_id ), ENT_QUOTES ), $course_id );

// Sections are keyed by the ID of the first lesson they contain.
$sections = function_exists( 'learndash_30_get_course_sections' ) ? learndash_30_get_course_sections( $course_id ) : array();
$lessons  = learndash_course_get_steps_by_type( $course_id, 'sfwd-lessons' );
$counts   = array(
	'lessons' => count( $lessons ),
	'topics'  => 0,
	'quizzes' => 0,
);

foreach ( $lessons as $position => $lesson_id ) {
	if ( isset( $sections[ $lesson_id ] ) ) {
		printf( "\n## %s\n\n", html_entity_decode( $sections[ $lesson_id ]->post_title, ENT_QUOTES ) );
	}
	$cbf_line( 0, sprintf( 'Lesson %d:', $position + 1 ), $lesson_id );

	foreach ( (array) learndash_get_topic_list( $lesson_id, $course_id ) as $topic ) {
		$cbf_line( 1, 'Topic', $topic->ID );
		++$counts['topics'];
		foreach ( (array) learndash_get_lesson_quiz_list( $topic->ID, null, $course_id ) as $quiz ) {
			$cbf_line( 2, 'Quiz', $quiz['post']->ID );
			++$counts['quizzes'];
		}
	}
	foreach ( (array) learndash_get_lesson_quiz_list( $lesson_id, null, $course_id ) as $quiz ) {
		$cbf_line( 1, 'Quiz', $quiz['post']->ID );
		++$counts['quizzes'];
	}
}

$course_quizzes = (array) learndash_get_course_quiz_list( $course_id );
if ( $course_quizzes ) {
	echo "\n## Course quizzes\n\n";
	foreach ( $course_quizzes as $quiz ) {
		$cbf_line( 0, 'Quiz', $quiz['post']->ID );
		++$counts['quizzes'];
	}
}

printf( "\n%d lessons, %d topics, %d quizzes.\n", $counts['lessons'], $counts['topics'], $counts['quizzes'] );
