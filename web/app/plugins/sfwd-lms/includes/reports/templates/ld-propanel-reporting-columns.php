<?php
/**
 * Learndash ProPanel Download Course Activity Rows Template
 *
 * @since 4.17.0
 * @version 4.17.0
 *
 * Available variables:
 *
 * @var $activity array of Objects based on returned values from the LearnDash core reporting
 * function learndash_report_course_users_progress(), A typical object structure will be something
 * like the following. But this is filterable. See LearnDash core docs for details.
 *
 *  activity<pre>stdClass Object
 *  (
 *      [user_id] => 6
 *      [user_display_name] => subscriber three
 *      [user_email] => subscriber_3@test.com
 *      [post_id] => 53
 *      [post_title] => Course
 *      [post_type] => sfwd-courses
 *      [activity_id] => 29
 *      [activity_type] => course
 *      [activity_started] => 1471529028
 *      [activity_completed] =>
 *      [activity_status] => 0
 *      [activity_started_formatted] => August 18, 2016 10:03:48
 *      [activity_meta] => Array
 *          (
 *              [steps_total] => 6
 *              [steps_completed] => 4
 *              [last_id] => 132
 *              [steps_last_id] => 72
 *          )
 *
 *  )
 *
 * @var $header_key string header to be user for column output. This should match
 * your output logic switch/case logic in this template.
 *
 * @package LearnDash
 */

defined( 'ABSPATH' ) || exit;

if ( $data_slug == 'user-courses' ) {
	switch ( $header_key ) {
		case 'course_started_on':
			if ( ( property_exists( $activity, 'activity_started' ) ) && ( ! empty( $activity->activity_started ) ) ) {
				$header_output = learndash_adjust_date_time_display( $activity->activity_started, 'Y-m-d' );
			}
			break;

		case 'course_total_time_on':
			$course_time_begin = 0;
			$course_time_end   = 0;

			if ( ( property_exists( $activity, 'activity_started' ) ) && ( ! empty( $activity->activity_started ) ) ) {
				$course_time_begin = $activity->activity_started;
			}

			if ( ( property_exists( $activity, 'activity_updated' ) ) && ( ! empty( $activity->activity_updated ) ) ) {
				$course_time_end = $activity->activity_updated;
			}

			if ( property_exists( $activity, 'activity_status' ) ) {
				if ( $activity->activity_status == true ) {
					if ( ( property_exists( $activity, 'activity_completed' ) ) && ( ! empty( $activity->activity_completed ) ) ) {
						// $course_time_end = learndash_adjust_date_time_display( $activity->activity_completed, 'Y-m-d' );
						$course_time_end = $activity->activity_completed;
					}
				}
			}

			if ( ( ! empty( $course_time_begin ) ) && ( ! empty( $course_time_end ) ) ) {
				$course_time_diff = $course_time_end - $course_time_begin;
				if ( $course_time_diff > 0 ) {
					if ( $course_time_diff > 86400 ) {
						if ( ! empty( $header_output ) ) {
							$header_output .= ' ';
						}
						$header_output    .= floor( $course_time_diff / 86400 ) . 'd';
						$course_time_diff %= 86400;
					}

					if ( $course_time_diff > 3600 ) {
						if ( ! empty( $header_output ) ) {
							$header_output .= ' ';
						}
						$header_output    .= floor( $course_time_diff / 3600 ) . 'h';
						$course_time_diff %= 3600;
					}

					if ( $course_time_diff > 60 ) {
						if ( ! empty( $header_output ) ) {
							$header_output .= ' ';
						}
						$header_output    .= floor( $course_time_diff / 60 ) . 'm';
						$course_time_diff %= 60;
					}

					if ( $course_time_diff > 0 ) {
						if ( ! empty( $header_output ) ) {
							$header_output .= ' ';
						}
						$header_output .= $course_time_diff . 's';
					}
				}
			}
			break;

		case 'course_last_step_id':
			if ( ( $activity ) && ( property_exists( $activity, 'activity_meta' ) ) && ( is_array( $activity->activity_meta ) ) ) {
				if ( ( isset( $activity->activity_meta['steps_last_id'] ) ) && ( ! empty( $activity->activity_meta['steps_last_id'] ) ) ) {
					$header_output = $activity->activity_meta['steps_last_id'];
				}
			}
			break;

		case 'course_last_step_type':
			if ( ( property_exists( $activity, 'activity_meta' ) ) && ( is_array( $activity->activity_meta ) ) ) {
				if ( ( isset( $activity->activity_meta['steps_last_id'] ) ) && ( ! empty( $activity->activity_meta['steps_last_id'] ) ) ) {
					$last_step_post = get_post( $activity->activity_meta['steps_last_id'] );
					if ( $last_step_post instanceof WP_Post ) {
						switch ( $last_step_post->post_type ) {
							case 'sfwd-courses':
								$header_output = LearnDash_Custom_Label::get_label( 'course' );
								break;

							case 'sfwd-lessons':
								$header_output = LearnDash_Custom_Label::get_label( 'lesson' );
								break;

							case 'sfwd-topic':
								$header_output = LearnDash_Custom_Label::get_label( 'topic' );
								break;

							case 'sfwd-quiz':
								$header_output = LearnDash_Custom_Label::get_label( 'quiz' );
								break;

							default:
								$header_output = '';
								break;
						}
					}
				}
			}
			break;

		case 'course_last_step_title':
			if ( ( property_exists( $activity, 'activity_meta' ) ) && ( is_array( $activity->activity_meta ) ) ) {
				if ( ( isset( $activity->activity_meta['steps_last_id'] ) ) && ( ! empty( $activity->activity_meta['steps_last_id'] ) ) ) {
					$step_title    = get_the_title( $activity->activity_meta['steps_last_id'] );
					$header_output = preg_replace( '/&#?[a-z0-9]+;/i', '', $step_title );
				}
			}
			break;

		case 'last_login_date':
			if ( property_exists( $activity, 'user_id' ) ) {
				$header_output = learndash_adjust_date_time_display( get_user_meta( intval( $activity->user_id ), 'learndash-last-login', true ), 'Y-m-d' );
			}
			break;

		default:
			break;
	}

	$header_output = apply_filters( 'learndash_report_column_item', $header_output, $header_key, $activity, $report_user, $data_slug );
}
