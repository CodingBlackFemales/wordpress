<?php
/**
 * Apply PublishPress Permissions to LearnDash's own edit and delete checks.
 *
 * LearnDash registers its content types (courses, lessons, topics, quizzes,
 * questions, exams, certificates) with the capability type "course", so their
 * per-post meta capabilities are edit_course and delete_course rather than
 * edit_post and delete_post. LearnDash's REST controllers check those directly.
 *
 * Permissions applies Specific Permissions (such as "edit this one course")
 * only to the meta capabilities it knows, which are edit_post and delete_post.
 * A role granted one course can therefore open it in the editor, which checks
 * edit_post, but the editor's REST request for the course, which LearnDash
 * checks with edit_course, is refused, and the editor never loads.
 *
 * Mapping LearnDash's meta capabilities to the same operations, as Permissions
 * does for bbPress's, makes both checks agree.
 *
 * @package CBF
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'presspermit_meta_caps',
	static fn ( array $meta_caps ): array => array_merge(
		$meta_caps,
		array(
			'edit_course'   => 'edit',
			'delete_course' => 'delete',
		)
	)
);
