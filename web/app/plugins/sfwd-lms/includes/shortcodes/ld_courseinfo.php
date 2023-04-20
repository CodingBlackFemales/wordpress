<?php
/**
 * LearnDash `[courseinfo]` shortcode processing.
 *
 * @since 2.1.0
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the `[courseinfo]` shortcode output.
 *
 * Shortcode that displays the requested course information.
 *
 * @global boolean $learndash_shortcode_used
 *
 * @since 2.1.0
 *
 * @param array  $attr {
 *    An array of shortcode attributes.
 *
 *    @type string     $show           The course info field to display. Default 'course_title'.
 *    @type int|string $user_id        User ID. Default empty.
 *    @type int|string $course_id      Course ID. Default empty.
 *    @type int|string $format         Date display format. Default 'F j, Y, g:i a'.
 *    @type int|string $seconds_format Seconds format. Default 'time'.
 *    @type int        $decimals       The number of decimal points. Default 2.
 * }
 * @param string $content The shortcode content. Default empty.
 * @param string $shortcode_slug The shortcode slug. Default 'courseinfo'.
 *
 * @return string The `courseinfo` shortcode output.
 */
function learndash_courseinfo( $attr = array(), $content = '', $shortcode_slug = 'courseinfo' ) {
	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;

	$shortcode_atts = shortcode_atts(
		array(
			'show'           => 'course_title',
			'user_id'        => '',
			'course_id'      => '',
			'format'         => 'F j, Y, g:i a',
			'seconds_format' => 'time',
			'decimals'       => 2,
		),
		$attr
	);

	$shortcode_atts['course_id'] = ! empty( $shortcode_atts['course_id'] ) ? $shortcode_atts['course_id'] : '';
	if ( '' === $shortcode_atts['course_id'] ) {
		if ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$shortcode_atts['course_id'] = intval( $_GET['course_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} else {
			$shortcode_atts['course_id'] = learndash_get_course_id();
		}
	}

	$shortcode_atts['user_id'] = ! empty( $shortcode_atts['user_id'] ) ? $shortcode_atts['user_id'] : '';
	if ( '' === $shortcode_atts['user_id'] ) {
		if ( ( isset( $_GET['user_id'] ) ) && ( ! empty( $_GET['user_id'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$shortcode_atts['user_id'] = intval( $_GET['user_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
	}

	/** This filter is documented in includes/shortcodes/ld_course_resume.php */
	$shortcode_atts = apply_filters( 'learndash_shortcode_atts', $shortcode_atts, $shortcode_slug );

	if ( empty( $shortcode_atts['user_id'] ) ) {
		$shortcode_atts['user_id'] = get_current_user_id();

		/**
		 * Added logic to allow admin and group_leader to view certificate from other users.
		 *
		 * @since 2.3.0
		 */
		$post_type = '';
		if ( get_query_var( 'post_type' ) ) {
			$post_type = get_query_var( 'post_type' );
		}

		if ( 'sfwd-certificates' == $post_type ) {
			if ( ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) && ( ( isset( $_GET['user'] ) ) && ( ! empty( $_GET['user'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$shortcode_atts['user_id'] = intval( $_GET['user'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
		}
	}

	if ( empty( $shortcode_atts['course_id'] ) || empty( $shortcode_atts['user_id'] ) ) {
		/**
		 * Filters courseinfo shortcode output.
		 *
		 * @param string $shortcode_output     The output of courseinfo shortcode.
		 * @param array  $shortcode_attributes An array of shortcode attributes.
		 */
		return apply_filters( 'learndash_courseinfo', '', $shortcode_atts );
	}

	$shortcode_atts['show'] = strtolower( $shortcode_atts['show'] );

	switch ( $shortcode_atts['show'] ) {
		case 'course_title':
			$shortcode_atts[ $shortcode_atts['show'] ] = '';

			$course = get_post( $shortcode_atts['course_id'] );
			if ( ( $course ) && ( is_a( $course, 'WP_Post' ) ) ) {
				$shortcode_atts[ $shortcode_atts['show'] ] = $course->post_title;
			}

			/** This filter is documented in includes/quiz/ld-quiz-info-shortcode.php */
			return apply_filters( 'learndash_courseinfo', $shortcode_atts[ $shortcode_atts['show'] ], $shortcode_atts );

		case 'course_url':
			$shortcode_atts[ $shortcode_atts['show'] ] = '';

			$course = get_post( $shortcode_atts['course_id'] );
			if ( ( $course ) && ( is_a( $course, 'WP_Post' ) ) ) {
				$shortcode_atts[ $shortcode_atts['show'] ] = get_permalink( $shortcode_atts['course_id'] );
			}

			/** This filter is documented in includes/quiz/ld-quiz-info-shortcode.php */
			return apply_filters( 'learndash_courseinfo', $shortcode_atts[ $shortcode_atts['show'] ], $shortcode_atts );

		case 'course_price_type':
			$shortcode_atts[ $shortcode_atts['show'] ] = '';

			$course = get_post( $shortcode_atts['course_id'] );
			if ( ( $course ) && ( is_a( $course, 'WP_Post' ) ) ) {
				$shortcode_atts[ $shortcode_atts['show'] ] = learndash_get_setting( $shortcode_atts['course_id'], 'course_price_type' );
			}

			/** This filter is documented in includes/quiz/ld-quiz-info-shortcode.php */
			return apply_filters( 'learndash_courseinfo', $shortcode_atts[ $shortcode_atts['show'] ], $shortcode_atts );

		case 'course_price':
			$shortcode_atts[ $shortcode_atts['show'] ] = '';

			$course = get_post( $shortcode_atts['course_id'] );
			if ( ( $course ) && ( is_a( $course, 'WP_Post' ) ) ) {
				$shortcode_atts[ $shortcode_atts['show'] ] = learndash_get_setting( $shortcode_atts['course_id'], 'course_price' );
			}

			/** This filter is documented in includes/quiz/ld-quiz-info-shortcode.php */
			return apply_filters( 'learndash_courseinfo', $shortcode_atts[ $shortcode_atts['show'] ], $shortcode_atts );

		case 'course_users_count':
			$shortcode_atts[ $shortcode_atts['show'] ] = 0;

			$course = get_post( $shortcode_atts['course_id'] );
			if ( ( $course ) && ( is_a( $course, 'WP_Post' ) ) ) {
				$course_users_query = learndash_get_users_for_course( $shortcode_atts['course_id'] );
				if ( is_a( $course_users_query, 'WP_User_Query' ) ) {
					$shortcode_atts[ $shortcode_atts['show'] ] = absint( $course_users_query->total_users );
				}
			}

			/** This filter is documented in includes/quiz/ld-quiz-info-shortcode.php */
			return apply_filters( 'learndash_courseinfo', $shortcode_atts[ $shortcode_atts['show'] ], $shortcode_atts );

		case 'user_course_time':
			$shortcode_atts[ $shortcode_atts['show'] ] = 0;

			if ( ! empty( $shortcode_atts['user_id'] ) ) {
				$activity_query_args             = array(
					'post_types'     => learndash_get_post_type_slug( 'course' ),
					'activity_types' => 'course',
					'per_page'       => 1,
					'page'           => 1,
				);
				$activity_query_args['user_ids'] = $shortcode_atts['user_id'];
				$activity_query_args['post_ids'] = $shortcode_atts['course_id'];

				$user_courses_reports = learndash_reports_get_activity( $activity_query_args );
				if ( ! empty( $user_courses_reports['results'] ) ) {
					$activity_started   = 0;
					$activity_completed = 0;
					foreach ( $user_courses_reports['results'] as $course_activity ) {

						if ( ( property_exists( $course_activity, 'activity_started' ) ) && ( ! empty( $course_activity->activity_started ) ) ) {
							$activity_started = $course_activity->activity_started;
						}
						if ( ( property_exists( $course_activity, 'activity_completed' ) ) && ( ! empty( $course_activity->activity_completed ) ) ) {
							$activity_completed = $course_activity->activity_completed;
						} elseif ( ( property_exists( $course_activity, 'activity_updated' ) ) && ( ! empty( $course_activity->activity_updated ) ) ) {
							$activity_completed = $course_activity->activity_updated;
						}
						// There should only be one user+course entry. But just in case we break out of our loop here.
						break;
					}

					if ( ( ! empty( $activity_started ) ) && ( ! empty( $activity_completed ) ) ) {
						$shortcode_atts[ $shortcode_atts['show'] ] = absint( $activity_completed ) - absint( $activity_started );
					}
				}
			}

			if ( 'time' === $shortcode_atts['seconds_format'] ) {

				/** This filter is documented in includes/quiz/ld-quiz-info-shortcode.php */
				return apply_filters( 'learndash_courseinfo', learndash_seconds_to_time( $shortcode_atts[ $shortcode_atts['show'] ] ), $shortcode_atts );
			} else {

				/** This filter is documented in includes/quiz/ld-quiz-info-shortcode.php */
				return apply_filters( 'learndash_courseinfo', $shortcode_atts[ $shortcode_atts['show'] ], $shortcode_atts );
			}
			break;

		case 'cumulative_score':
		case 'cumulative_points':
		case 'cumulative_total_points':
		case 'cumulative_percentage':
		case 'cumulative_timespent':
		case 'cumulative_count':
			$shortcode_atts[ $shortcode_atts['show'] ] = '';

			$field    = str_replace( 'cumulative_', '', $shortcode_atts['show'] );
			$quizdata = get_user_meta( $shortcode_atts['user_id'], '_sfwd-quizzes', true );
			$quizzes  = learndash_course_get_steps_by_type( intval( $shortcode_atts['course_id'] ), 'sfwd-quiz' );
			if ( empty( $quizzes ) ) {
				/** This filter is documented in includes/quiz/ld-quiz-info-shortcode.php */
				return apply_filters( 'learndash_courseinfo', 0, $shortcode_atts );
			}

			/**
			 * Filters Quizzes to be included in calculations.
			 *
			 * @since 3.1.2
			 * @param array $quizzes        Array of Quiz IDs to be processed.
			 * @param array $shortcode_atts Array of shortcode attributes.
			 * @return array of Quiz IDs.
			*/
			$quizzes = apply_filters( 'learndash_courseinfo_quizzes', $quizzes, $shortcode_atts );

			$scores = array();

			if ( ( ! empty( $quizdata ) ) && ( is_array( $quizdata ) ) ) {
				foreach ( $quizdata as $data ) {
					if ( ( is_array( $quizzes ) ) && ( ( in_array( $data['quiz'], $quizzes ) ) ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						if ( ( ! isset( $data['course'] ) ) || ( intval( $data['course'] ) == intval( $shortcode_atts['course_id'] ) ) ) {
							if ( empty( $scores[ $data['quiz'] ] ) || $scores[ $data['quiz'] ] < $data[ $field ] ) {
								$scores[ $data['quiz'] ] = $data[ $field ];
							}
						}
					}
				}
			}

			if ( empty( $scores ) || ! count( $scores ) ) {
				/** This filter is documented in includes/quiz/ld-quiz-info-shortcode.php */
				return apply_filters( 'learndash_courseinfo', 0, $shortcode_atts );
			}

			$sum = 0;

			foreach ( $scores as $score ) {
				$sum += $score;
			}

			$return = number_format( $sum / count( $scores ), $shortcode_atts['decimals'] );

			$shortcode_atts[ $shortcode_atts['show'] ] = $return;

			if ( 'timespent' == $field ) {
				if ( 'time' === $shortcode_atts['seconds_format'] ) {
					/** This filter is documented in includes/quiz/ld-quiz-info-shortcode.php */
					return apply_filters( 'learndash_courseinfo', learndash_seconds_to_time( $shortcode_atts[ $shortcode_atts['show'] ] ), $shortcode_atts );
				} else {
					/** This filter is documented in includes/quiz/ld-quiz-info-shortcode.php */
					return apply_filters( 'learndash_courseinfo', $shortcode_atts[ $shortcode_atts['show'] ], $shortcode_atts );
				}
			} else {
				/** This filter is documented in includes/quiz/ld-quiz-info-shortcode.php */
				return apply_filters( 'learndash_courseinfo', $shortcode_atts[ $shortcode_atts['show'] ], $shortcode_atts );
			}
			break;

		case 'aggregate_percentage':
		case 'aggregate_score':
		case 'aggregate_points':
		case 'aggregate_total_points':
		case 'aggregate_timespent':
		case 'aggregate_count':
			$shortcode_atts[ $shortcode_atts['show'] ] = '';

			$field    = substr_replace( $shortcode_atts['show'], '', 0, 10 );
			$quizdata = get_user_meta( $shortcode_atts['user_id'], '_sfwd-quizzes', true );
			$quizzes  = learndash_course_get_steps_by_type( intval( $shortcode_atts['course_id'] ), 'sfwd-quiz' );
			if ( empty( $quizzes ) ) {
				/** This filter is documented in includes/quiz/ld-quiz-info-shortcode.php */
				return apply_filters( 'learndash_courseinfo', 0, $shortcode_atts );
			}

			/**
			 * Filters Quizzes to be included in calculations.
			 *
			 * @since 3.1.2
			 * @param array $quizzes        Array of Quiz IDs to be processed.
			 * @param array $shortcode_atts Array of shortcode attributes.
			 * @return array of Quiz IDs.
			*/
			$quizzes = apply_filters( 'learndash_courseinfo_quizzes', $quizzes, $shortcode_atts );

			$scores = array();

			if ( ( ! empty( $quizdata ) ) && ( is_array( $quizdata ) ) ) {
				foreach ( $quizdata as $data ) {
					if ( ( is_array( $quizzes ) ) && ( ( in_array( $data['quiz'], $quizzes ) ) ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						if ( ( empty( $scores[ $data['quiz'] ] ) || $scores[ $data['quiz'] ] < $data[ $field ] ) ) {
							if ( ( ! isset( $data['course'] ) ) || ( intval( $data['course'] ) == intval( $shortcode_atts['course_id'] ) ) ) {
								$scores[ $data['quiz'] ] = $data[ $field ];
							}
						}
					}
				}
			}

			if ( empty( $scores ) || ! count( $scores ) ) {
				/** This filter is documented in includes/quiz/ld-quiz-info-shortcode.php */
				return apply_filters( 'learndash_courseinfo', 0, $shortcode_atts );
			}

			$sum = 0;

			foreach ( $scores as $score ) {
				$sum += $score;
			}

			$return                                    = number_format( $sum, $shortcode_atts['decimals'] );
			$shortcode_atts[ $shortcode_atts['show'] ] = $return;

			if ( 'timespent' == $field ) {
				if ( 'time' === $shortcode_atts['seconds_format'] ) {
					/** This filter is documented in includes/quiz/ld-quiz-info-shortcode.php */
					return apply_filters( 'learndash_courseinfo', learndash_seconds_to_time( $shortcode_atts[ $shortcode_atts['show'] ] ), $shortcode_atts['show'] );
				} else {
					/** This filter is documented in includes/quiz/ld-quiz-info-shortcode.php */
					return apply_filters( 'learndash_courseinfo', $shortcode_atts[ $shortcode_atts['show'] ], $shortcode_atts['show'] );
				}
			} else {
				/** This filter is documented in includes/quiz/ld-quiz-info-shortcode.php */
				return apply_filters( 'learndash_courseinfo', $shortcode_atts[ $shortcode_atts['show'] ], $shortcode_atts );
			}

		case 'completed_on':
			$shortcode_atts[ $shortcode_atts['show'] ] = '';

			$completed_on = get_user_meta( $shortcode_atts['user_id'], 'course_completed_' . $shortcode_atts['course_id'], true );

			if ( empty( $completed_on ) ) {
				$completed_on = learndash_user_get_course_completed_date( $shortcode_atts['user_id'], $shortcode_atts['course_id'] );
				if ( empty( $completed_on ) ) {
					/** This filter is documented in includes/quiz/ld-quiz-info-shortcode.php */
					return apply_filters( 'learndash_courseinfo', '-', $shortcode_atts );
				}
			}

			$shortcode_atts[ $shortcode_atts['show'] ] = $completed_on;

			/** This filter is documented in includes/quiz/ld-quiz-info-shortcode.php */
			return apply_filters( 'learndash_courseinfo', learndash_adjust_date_time_display( $completed_on, $shortcode_atts['format'] ), $shortcode_atts );

		case 'enrolled_on':
			$shortcode_atts[ $shortcode_atts['show'] ] = '';

			$enrolled_on = learndash_user_group_enrolled_to_course_from( $shortcode_atts['user_id'], $shortcode_atts['course_id'] );
			if ( empty( $enrolled_on ) ) {
				$enrolled_on = get_user_meta( $shortcode_atts['user_id'], 'course_' . $shortcode_atts['course_id'] . '_access_from', true );
			}

			if ( empty( $enrolled_on ) ) {

				/** This filter is documented in includes/quiz/ld-quiz-info-shortcode.php */
				return apply_filters( 'learndash_courseinfo', '-', $shortcode_atts );
			}

			$shortcode_atts[ $shortcode_atts['show'] ] = $enrolled_on;

			/** This filter is documented in includes/quiz/ld-quiz-info-shortcode.php */
			return apply_filters( 'learndash_courseinfo', learndash_adjust_date_time_display( $enrolled_on, $shortcode_atts['format'] ), $shortcode_atts );

		case 'course_points':
			$shortcode_atts[ $shortcode_atts['show'] ] = '';

			$course_points                             = learndash_get_course_points( $shortcode_atts['course_id'], $shortcode_atts['decimals'] );
			$course_points                             = number_format( $course_points, $shortcode_atts['decimals'] );
			$shortcode_atts[ $shortcode_atts['show'] ] = $course_points;

			/** This filter is documented in includes/quiz/ld-quiz-info-shortcode.php */
			return apply_filters( 'learndash_courseinfo', $course_points, $shortcode_atts );

		case 'user_course_points':
			$shortcode_atts[ $shortcode_atts['show'] ] = '';

			$user_course_points                        = learndash_get_user_course_points( $shortcode_atts['user_id'] );
			$user_course_points                        = number_format( $user_course_points, $shortcode_atts['decimals'] );
			$shortcode_atts[ $shortcode_atts['show'] ] = $user_course_points;

			/** This filter is documented in includes/quiz/ld-quiz-info-shortcode.php */
			return apply_filters( 'learndash_courseinfo', $user_course_points, $shortcode_atts );

		default:
			/** This filter is documented in includes/quiz/ld-quiz-info-shortcode.php */
			return apply_filters( 'learndash_courseinfo', '', $shortcode_atts );
	}
}
add_shortcode( 'courseinfo', 'learndash_courseinfo', 10, 3 );
