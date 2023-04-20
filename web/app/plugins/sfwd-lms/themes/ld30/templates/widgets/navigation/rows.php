<?php
/**
 * LearnDash LD30 Displays the course navigation widget row.
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30\Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! empty( $lessons ) ) :

	$sections = learndash_30_get_course_sections( $course_id );
	$i        = 0;

	foreach ( $lessons as $course_lesson ) :

		$all_topics = learndash_topic_dots( $course_lesson['post']->ID, false, 'array' );

		/** This filter is documented in themes/ld30/includes/helpers.php */
		$topic_pager_args = apply_filters(
			'ld30_ajax_topic_pager_args',
			array(
				'course_id' => $course_id,
				'lesson_id' => $course_lesson['post']->ID,
			)
		);

		$lesson_topics = learndash_process_lesson_topics_pager( $all_topics, $topic_pager_args );

		learndash_get_template_part(
			'widgets/navigation/lesson-row.php',
			array(
				'count'           => $i,
				'sections'        => $sections,
				'lesson'          => $course_lesson,
				'course_id'       => $course_id,
				'user_id'         => $user_id,
				'lesson_topics'   => $lesson_topics,
				'widget_instance' => $widget_instance,
				'has_access'      => $has_access,
			),
			true
		);

		$i++;
	endforeach;
endif;

/**
 * Should we show quizzes in the course navigation based on pagination?
 */
$show_course_quizzes = true;

if ( isset( $course_pager_results['pager'] ) && ! empty( $course_pager_results['pager'] ) ) {
	$show_course_quizzes = ( absint( $course_pager_results['pager']['paged'] ) === absint( $course_pager_results['pager']['total_pages'] ) ? true : false );
}

if ( isset( $widget_instance['show_course_quizzes'] ) && true !== (bool) $widget_instance['show_course_quizzes'] ) {
	$show_course_quizzes = false;
}

if ( true == $show_course_quizzes ) :
	$course_quiz_list = learndash_get_course_quiz_list( $course_id, get_current_user_id() );

	if ( ! empty( $course_quiz_list ) ) :
		foreach ( $course_quiz_list as $quiz ) :

			learndash_get_template_part(
				'widgets/navigation/quiz-row.php',
				array(
					'quiz'      => $quiz,
					'user_id'   => $user_id,
					'course_id' => $course_id,
					'context'   => 'course',
				),
				true
			);

		endforeach;
	endif;

endif;

if ( isset( $course_pager_results['pager'] ) ) :
	learndash_get_template_part(
		'modules/pagination.php',
		array(
			'pager_results' => $course_pager_results['pager'],
			'pager_context' => 'course_lessons',
			'course_id'     => $course_id,
		),
		true
	);
endif;


