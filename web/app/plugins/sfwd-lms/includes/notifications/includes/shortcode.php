<?php
/**
 * Shortcode functions
 *
 * @since 5.2.0
 *
 * @package LearnDash\Notifications
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Register shortcode
 */
function learndash_notifications_register_shortcode() {
	add_shortcode( 'ld_notifications', 'learndash_notifications_shortcode_init' );
}

add_action( 'init', 'learndash_notifications_register_shortcode', 1 );

/**
 * ld_notifications shortcode callback
 *
 * @param array       $atts    Shortcode attributes.
 * @param string|null $content Shortcode content.
 *
 * @return string          Shortcode final result
 */
function learndash_notifications_shortcode_init( $atts, $content = '' ) {
	// Get global variable set in learndash_notifications_send_notifications()
	global $ld_notifications_shortcode_data;
	$data = $ld_notifications_shortcode_data;

	$result = '';

	$shortcode = 'ld_notifications';
	$atts      = shortcode_atts(
		[
			'field'  => '',
			'show'   => '',
			'format' => '',
		],
		$atts,
		$shortcode
	);

	if ( empty( $data ) || empty( $atts['field'] ) || empty( $atts['show'] ) ) {
		return '';
	}

	$show = strtolower( $atts['show'] );

	if ( $atts['field'] == 'user' ) {
		$u = get_user_by( 'id', $data['user_id'] );

		if ( false !== $u ) {
			switch ( $show ) {
				case 'username':
					$result = $u->user_login;
					break;

				case 'email':
					$result = $u->user_email;
					break;

				case 'display_name':
					$result = $u->display_name;
					break;

				case 'first_name':
					$result = $u->first_name;
					break;

				case 'last_name':
					$result = $u->last_name;
					break;

				default:
					$result = get_user_meta( $data['user_id'], $show, true );
					break;
			}
		}
	} elseif ( $atts['field'] == 'group' ) {
		$group = get_post( $data['group_id'] );

		switch ( $show ) {
			case 'title':
				$result = $group->post_title;
				break;

			case 'url':
				$result = get_permalink( $group );
				break;
		}
	} elseif ( $atts['field'] == 'course' ) {
		if ( isset( $data['course_ids'] ) ) {
			// this is the cases for x days trigger, only support title and URL.
			$ret = [];
			foreach ( $data['course_ids'] as $course_id ) {
				$course = get_post( $course_id );
				if ( ! is_object( $course ) ) {
					continue;
				}
				switch ( $show ) {
					case 'title':
						$ret[] = $course->post_title;
						break;
					case 'url':
						$ret[] = get_permalink( $course );
				}
			}
			$result = implode( ', ', $ret );
		} else {
			$course = get_post( $data['course_id'] );

			switch ( $show ) {
				case 'title':
					$result = $course->post_title;
					break;

				case 'url':
					$result = get_permalink( $data['course_id'] );
					break;

				case 'completed_on':
					$atts = shortcode_atts(
						[
							'format' => $format = 'F j, Y, g:i a',
						],
						$atts,
						$shortcode
					);

					$atts['format'] = ! empty( $atts['format'] ) ? $atts['format'] : $format;

					$completed_on = get_user_meta( $data['user_id'], 'course_completed_' . $data['course_id'], true );

					if ( empty( $completed_on ) ) {
						return '-';
					}

					if ( ! empty( $timezone_string = get_option( 'timezone_string' ) ) ) {
						date_default_timezone_set( $timezone_string );
					}

					$result = date( $atts['format'], $completed_on );
					break;

				case 'cumulative_score':
				case 'cumulative_points':
				case 'cumulative_total_points':
				case 'cumulative_percentage':
				case 'cumulative_timespent':
				case 'cumulative_count':
					$field    = str_replace( 'cumulative_', '', $show );
					$quizdata = get_user_meta( $data['user_id'], '_sfwd-quizzes', true );
					global $wpdb;

					$quizzes = $wpdb->get_col(
						$wpdb->prepare(
							"SELECT post_id FROM `{$wpdb->postmeta}` WHERE ( meta_key = 'course_id' AND meta_value = %d ) OR ( meta_key = %s AND meta_value = %d )",
							$data['course_id'],
							'ld_course_' . $data['course_id'],
							$data['course_id']
						)
					);

					$quizzes = array_unique( $quizzes );

					if ( empty( $quizzes ) ) {
						$result = 0;
						break;
					}

					$scores = [];

					if ( ( ! empty( $quizdata ) ) && ( is_array( $quizdata ) ) ) {
						foreach ( $quizdata as $data ) {
							if ( in_array( $data['quiz'], $quizzes ) ) {
								if ( empty( $scores[ $data['quiz'] ] ) || $scores[ $data['quiz'] ] < $data[ $field ] ) {
									$scores[ $data['quiz'] ] = $data[ $field ];
								}
							}
						}
					}

					if ( empty( $scores ) || ! count( $scores ) ) {
						$result = 0;
						break;
					}

					$sum = 0;

					foreach ( $scores as $score ) {
						$sum += $score;
					}

					$result = $sum / count( $scores );

					if ( $field == 'timespent' ) {
						// The $result must be integer before passed to
						// learndash_seconds_to_time()
						$result = learndash_seconds_to_time( intval( $result ) );
					} else {
						$result = number_format( $result, 2 );
					}
					break;

				case 'aggregate_percentage':
				case 'aggregate_score':
				case 'aggregate_points':
				case 'aggregate_total_points':
				case 'aggregate_timespent':
				case 'aggregate_count':
					$field    = substr_replace( $show, '', 0, 10 );
					$quizdata = get_user_meta( $data['user_id'], '_sfwd-quizzes', true );
					global $wpdb;

					$quizzes = $wpdb->get_col(
						$wpdb->prepare(
							"SELECT post_id FROM `{$wpdb->postmeta}` WHERE ( meta_key = 'course_id' AND meta_value = %d ) OR ( meta_key = %s AND meta_value = %d )",
							$data['course_id'],
							'ld_course_' . $data['course_id'],
							$data['course_id']
						)
					);

					$quizzes = array_unique( $quizzes );

					if ( empty( $quizzes ) ) {
						$result = 0;
						break;
					}

					$scores = [];

					if ( ( ! empty( $quizdata ) ) && ( is_array( $quizdata ) ) ) {
						foreach ( $quizdata as $data ) {
							if ( in_array( $data['quiz'], $quizzes ) ) {
								if ( empty( $scores[ $data['quiz'] ] ) || $scores[ $data['quiz'] ] < $data[ $field ] ) {
									$scores[ $data['quiz'] ] = $data[ $field ];
								}
							}
						}
					}

					if ( empty( $scores ) || ! count( $scores ) ) {
						$result = 0;
						break;
					}

					$sum = 0;

					foreach ( $scores as $score ) {
						$sum += $score;
					}

					$result = $sum;

					if ( $field == 'timespent' ) {
						// The $result must be integer before passed to
						// learndash_seconds_to_time()
						$result = learndash_seconds_to_time( intval( $result ) );
					} else {
						$result = number_format( $result, 2 );
					}

					break;
			}
		} // End switch( $show )
	} elseif ( $atts['field'] == 'lesson' ) { // End if $atts['field'] == course
		$lesson = get_post( $data['lesson_id'] );
		if ( is_object( $lesson ) ) {
			switch ( $atts['show'] ) {
				case 'title':
					$result = $lesson->post_title;
					break;

				case 'url':
					$result             = learndash_get_step_permalink(
						$step_id        = $data['lesson_id'],
						$step_course_id = $data['course_id']
					);
					break;
			}
		}
	} elseif ( $atts['field'] == 'topic' ) { // End if $atts['field'] == lesson
		$topic = get_post( $data['topic_id'] );
		if ( is_object( $topic ) ) {
			switch ( $atts['show'] ) {
				case 'title':
					$result = $topic->post_title;
					break;

				case 'url':
					$result = get_permalink( $data['topic_id'] );
					$result = learndash_get_step_permalink( $data['topic_id'], $data['course_id'] );
					break;
			}
		}
	} elseif ( $atts['field'] == 'quiz' ) { // End if $atts['field'] == topic
		if ( empty( $data['user_id'] ) ) {
			$data['user_id'] = get_current_user_id();
		}

		if ( empty( $data['quiz_id'] ) || empty( $data['user_id'] ) || empty( $show ) ) {
			$result = '';
		}

		$quizinfo = get_user_meta( $data['user_id'], '_sfwd-quizzes', true );
		if ( ! is_array( $quizinfo ) ) {
			$quizinfo = [];
		}
		foreach ( $quizinfo as $quiz_i ) {
			if ( $quiz_i['quiz'] == $data['quiz_id'] ) {
				$selected_quizinfo = $quiz_i;
			}
		}

		switch ( $show ) {
			case 'url':
				$selected_quizinfo['url'] = get_permalink( $data['quiz_id'] );
				break;

			case 'timestamp':
				$atts = shortcode_atts(
					[
						'format' => 'Y-m-d H:i:s',
					],
					$atts,
					$shortcode
				);

				/**
				 * Updated to LearnDash Core code change
				 *
				 * @link https://learndash.atlassian.net/browse/LEARNDASH-4188
				 */
				$selected_quizinfo['timestamp'] = learndash_adjust_date_time_display(
					$selected_quizinfo['time'],
					$atts['format']
				);
				break;

			case 'percentage':
				if ( empty( $selected_quizinfo['percentage'] ) ) {
					$selected_quizinfo['percentage'] = empty( $selected_quizinfo['count'] ) ? 0 : $selected_quizinfo['score'] * 100 / $selected_quizinfo['count'];
				}

				break;

			case 'pass':
				$selected_quizinfo['pass'] = ! empty( $selected_quizinfo['pass'] ) ? __(
					'Yes',
					'learndash'
				) : __(
					'No',
					'learndash'
				);
				break;

			case 'quiz_title':
				$quiz_post = get_post( $data['quiz_id'] );

				if ( ! empty( $quiz_post->post_title ) ) {
					$selected_quizinfo['quiz_title'] = $quiz_post->post_title;
				}

				break;

			case 'course_title':
				$course_id = learndash_get_setting( $data['quiz_id'], 'course' );
				$course    = get_post( $course_id );

				if ( ! empty( $course->post_title ) ) {
					$selected_quizinfo['course_title'] = $course->post_title;
				}

				break;

			case 'timespent':
				$selected_quizinfo['timespent'] = isset( $selected_quizinfo['timespent'] ) ? learndash_seconds_to_time( $selected_quizinfo['timespent'] ) : '';
				break;

			// Categories field output mimics setCategoryOverview private method from wpProQuiz_Controller_Quiz_Completed class
			case 'categories':
				$quiz_mapper     = new WpProQuiz_Model_QuizMapper();
				$category_mapper = new WpProQuiz_Model_CategoryMapper();

				$quiz       = $quiz_mapper->fetch( $data['quiz_result']['pro_quizid'] );
				$categories = $category_mapper->fetchByQuiz( $quiz );

				$cats_result = $data['quiz_result']['cats'];

				// Empty data passed because the class requires a parameter
				$quiz_completed = new WpProQuiz_Controller_QuizCompleted( [] );
				$method         = new ReflectionMethod(
					WpProQuiz_Controller_QuizCompleted::class,
					'setCategoryOverview'
				);
				$method->setAccessible( true );
				$selected_quizinfo['categories'] = $method->invokeArgs(
					$quiz_completed,
					[
						$cats_result,
						$categories,
					]
				);
				break;
		}

		if ( isset( $selected_quizinfo[ $show ] ) ) {
			$result = $selected_quizinfo[ $show ];
		} else {
			$result = '';
		}
	} elseif ( $atts['field'] == 'essay' ) { // End if $atts['field'] == quiz
		if ( ! isset( $data['user_id'] ) || ! isset( $data['question_id'] ) ) {
			return;
		}

		$questionMapper = new WpProQuiz_Model_QuestionMapper();
		$question       = $questionMapper->fetchById( intval( $data['question_id'] ) );

		switch ( $atts['show'] ) {
			case 'points_earned':
				$question_data = get_user_meta( $data['user_id'], '_sfwd-quizzes', true );
				if ( ! is_array( $question_data ) ) {
					$question_data = [];
				}
				foreach ( $question_data as $q ) {
					if ( isset( $q['graded'] ) && is_array( $q['graded'] ) ) {
						foreach ( $q['graded'] as $question_id => $q_data ) {
							if ( $question_id == $data['question_id'] ) {
								$result = $q_data['points_awarded'];
								continue 2;
							}
						}
					}
				}
				break;

			case 'points_total':
				$result = $question->getPoints();
				break;
		}
	} elseif ( $atts['field'] == 'assignment' ) { // End essay field
		$assignment = get_post( $data['assignment_id'] );

		switch ( $atts['show'] ) {
			case 'title':
				$result = $assignment->post_title;
				break;

			case 'file_name':
				$result = get_post_meta( $data['assignment_id'], 'file_name', true );
				break;

			case 'file_link':
				$result = get_post_meta( $data['assignment_id'], 'file_link', true );
				break;

			case 'lesson_title':
				$result = get_post_meta( $data['assignment_id'], 'lesson_title', true );
				break;

			case 'lesson_type':
				$result = get_post_meta( $data['assignment_id'], 'lesson_type', true );
				break;
		}
	} // End if $atts['field'] == assignment

	unset( $ld_notifications_shortcode_data );

	return apply_filters( 'learndash_notifications_shortcode_output', $result, $atts, $data );
}
