<?php
/**
 * Course Builder Helpers.
 *
 * Used to provide proper data to Course Builder app.
 *
 * @since 3.0.0
 * @package LearnDash\Builder
 */

namespace LearnDash\Admin\CourseBuilderHelpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gets the course data for the course builder.
 *
 * @since 3.4.0
 *
 * @param array $data The data passed down to the front-end.
 *
 * @return array The data passed down to the front-end.
 */
function learndash_get_course_data( $data ) {

	$data['post_statuses'] = learndash_get_step_post_statuses();

	if ( ( defined( 'LEARNDASH_COURSE_FUNCTIONS_LEGACY' ) ) && ( true === LEARNDASH_COURSE_FUNCTIONS_LEGACY ) ) { // @phpstan-ignore-line
		return \learndash_get_course_data_legacy( $data );
	}

	global $pagenow, $typenow;

	$output_lessons = array();
	$output_quizzes = array();
	$sections       = array();

	if ( ( 'post.php' === $pagenow ) && ( learndash_get_post_type_slug( 'course' ) === $typenow ) ) {
		$course_id = get_the_ID();
		if ( ! empty( $course_id ) ) {
			// Get a list of lessons to loop.
			$lessons        = learndash_course_get_lessons(
				$course_id,
				array(
					'return_type' => 'WP_Post',
					'per_page'    => 0,
				)
			);
			$output_lessons = array();
			$lesson_topics  = array();

			if ( ( is_array( $lessons ) ) && ( ! empty( $lessons ) ) ) {
				// Loop course's lessons.
				foreach ( $lessons as $lesson_post ) {
					if ( ! is_a( $lesson_post, 'WP_Post' ) ) {
						continue;
					}

					// Get lesson's topics.
					$topics = learndash_course_get_topics(
						$course_id,
						$lesson_post->ID,
						array(
							'return_type' => 'WP_Post',
							'per_page'    => 0,
						)
					);

					$output_topics = array();

					if ( ( is_array( $topics ) ) && ( ! empty( $topics ) ) ) {
						// Loop Topics.
						foreach ( $topics as $topic_post ) {
							if ( ! is_a( $topic_post, 'WP_Post' ) ) {
								continue;
							}

							// Get Topic's Quizzes.
							$topic_quizzes = learndash_course_get_quizzes(
								$course_id,
								$topic_post->ID,
								array(
									'return_type' => 'WP_Post',
									'per_page'    => 0,
								)
							);

							$output_topic_quizzes = array();

							if ( ( is_array( $topic_quizzes ) ) && ( ! empty( $topic_quizzes ) ) ) {
								// Loop Topic's Quizzes.
								foreach ( $topic_quizzes as $quiz_post ) {
									if ( ! is_a( $quiz_post, 'WP_Post' ) ) {
										continue;
									}

									$output_topic_quizzes[] = array(
										'ID'          => $quiz_post->ID,
										'expanded'    => true,
										'post_title'  => $quiz_post->post_title,
										'post_status' => learndash_get_step_post_status_slug( $quiz_post ),
										'type'        => $quiz_post->post_type,
										'url'         => learndash_get_step_permalink( $quiz_post->ID, $course_id ),
										'edit_link'   => get_edit_post_link( $quiz_post->ID, '' ),
										'tree'        => array(),
									);
								}
							}

							$output_topics[] = array(
								'ID'          => $topic_post->ID,
								'expanded'    => true,
								'post_title'  => $topic_post->post_title,
								'post_status' => learndash_get_step_post_status_slug( $topic_post ),
								'type'        => $topic_post->post_type,
								'url'         => learndash_get_step_permalink( $topic_post->ID, $course_id ),
								'edit_link'   => get_edit_post_link( $topic_post->ID, '' ),
								'tree'        => $output_topic_quizzes,
							);
						}
					}

					// Get lesson's quizzes.
					$lesson_quizzes = learndash_course_get_quizzes(
						$course_id,
						$lesson_post->ID,
						array(
							'return_type' => 'WP_Post',
							'per_page'    => 0,
						)
					);
					$output_quizzes = array();

					if ( ( is_array( $lesson_quizzes ) ) && ( ! empty( $lesson_quizzes ) ) ) {
						// Loop lesson's quizzes.
						foreach ( $lesson_quizzes as $quiz_post ) {
							if ( ! is_a( $quiz_post, 'WP_Post' ) ) {
								continue;
							}

							$output_quizzes[] = array(
								'ID'          => $quiz_post->ID,
								'expanded'    => true,
								'post_title'  => $quiz_post->post_title,
								'post_status' => learndash_get_step_post_status_slug( $quiz_post ),
								'type'        => $quiz_post->post_type,
								'url'         => learndash_get_step_permalink( $quiz_post->ID, $course_id ),
								'edit_link'   => get_edit_post_link( $quiz_post->ID, '' ),
								'tree'        => array(),
							);
						}
					}

					// Output lesson with child tree.
					$output_lessons[] = array(
						'ID'            => $lesson_post->ID,
						'expanded'      => false,
						'post_title'    => $lesson_post->post_title,
						'post_status'   => learndash_get_step_post_status_slug( $lesson_post ),
						'type'          => $lesson_post->post_type,
						'url'           => learndash_get_step_permalink( $lesson_post->ID, $course_id ),
						'edit_link'     => get_edit_post_link( $lesson_post->ID, '' ),
						'tree'          => array_merge( $output_topics, $output_quizzes ),
						'sample_lesson' => learndash_is_sample( $lesson_post->ID ),
					);
				}
			}

			// Get a list of course (global) quizzes.
			$global_quizzes = learndash_course_get_quizzes(
				$course_id,
				$course_id,
				array(
					'return_type' => 'WP_Post',
					'per_page'    => 0,
				)
			);
			$output_quizzes = array();

			if ( ( is_array( $global_quizzes ) ) && ( ! empty( $global_quizzes ) ) ) {
				foreach ( $global_quizzes as $quiz_post ) {
					if ( ! is_a( $quiz_post, 'WP_Post' ) ) {
						continue;
					}

					$quiz_post_status = $quiz_post->post_status;
					if ( ( 'publish' === $quiz_post->post_status ) && ( ! empty( $quiz_post->post_password ) ) ) {
						$quiz_post_status = 'password';
					}

					$output_quizzes[] = array(
						'ID'          => $quiz_post->ID,
						'expanded'    => true,
						'post_title'  => $quiz_post->post_title,
						'post_status' => $quiz_post_status,
						'type'        => $quiz_post->post_type,
						'url'         => learndash_get_step_permalink( $quiz_post->ID, $course_id ),
						'edit_link'   => get_edit_post_link( $quiz_post->ID, '' ),
						'tree'        => array(),
					);
				}
			}

			// Merge sections at Outline.
			$sections_raw = get_post_meta( $course_id, 'course_sections', true );
			$sections     = ! empty( $sections_raw ) ? json_decode( $sections_raw ) : array();

			if ( ( is_array( $sections ) ) && ( ! empty( $sections ) ) ) {
				foreach ( $sections as $section ) {
					if ( ! is_object( $section ) ) {
						continue;
					}

					if ( ( ! property_exists( $section, 'ID' ) ) || ( empty( $section->ID ) ) ) {
						continue;
					}

					if ( ! property_exists( $section, 'order' ) ) {
						continue;
					}

					if ( ( ! property_exists( $section, 'post_title' ) ) || ( empty( $section->post_title ) ) ) {
						continue;
					}

					if ( ( ! property_exists( $section, 'type' ) ) || ( empty( $section->type ) ) ) {
						continue;
					}

					array_splice( $output_lessons, (int) $section->order, 0, array( $section ) );
				}
			}
		}
	}

	// Output data.
	$data['outline'] = array(
		'lessons'  => $output_lessons,
		'quizzes'  => $output_quizzes,
		'sections' => $sections,
	);

	return $data;
}
