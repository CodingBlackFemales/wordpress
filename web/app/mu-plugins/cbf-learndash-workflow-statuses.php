<?php
/**
 * Show course steps in PublishPress workflow statuses in course listings.
 *
 * LearnDash loads a course's lessons, topics and quizzes for the front end
 * with a query limited to WordPress's built-in post statuses. Steps in a
 * PublishPress Statuses workflow status, such as Ready or In Progress, are
 * dropped from the course listing for everyone, even though the course
 * builder shows them, while steps in Draft or Pending still appear.
 *
 * This adds the pre-publication workflow statuses to that query, so they're
 * treated like Draft and Pending. Revision statuses are left out, because
 * revisions are separate posts. Who can see a step is unchanged: LearnDash
 * still drops any unpublished step the viewer can't read.
 *
 * @package CBF
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'learndash_course_steps_post_status_keys',
	static function ( $keys ) {
		if ( ! is_array( $keys ) ) {
			return $keys;
		}

		$statuses = get_post_stati(
			array(
				'internal' => false,
				'_builtin' => false,
			),
			'objects'
		);

		foreach ( $statuses as $key => $status ) {
			if ( ! empty( $status->moderation ) && empty( $status->for_revision ) ) {
				$keys[] = $key;
			}
		}

		return array_values( array_unique( $keys ) );
	}
);
