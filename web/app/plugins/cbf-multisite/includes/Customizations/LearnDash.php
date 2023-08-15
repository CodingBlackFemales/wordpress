<?php
/**
 * LearnDash integration
 * Logic taken from app/plugins/sfwd_lms/includes/admin/classes-data-reports-actions/class-learndash-admin-data-reports-user-quizzes.php
 *
 * @package     CodingBlackFemales/Multisite/Customizations
 * @version     1.0.0
 */

namespace CodingBlackFemales\Multisite\Customizations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LearnDash\Core\Models\Course;

/**
 * Custom LearnDash integration class.
 */
class LearnDash {
	/**
	 * Data headers
	 *
	 * @var array $data_headers
	 */
	private static $data_headers = array();

	/**
	 * Set Report Headers
	 *
	 * @since 2.3.0
	 */
	protected static function set_report_headers() {
		self::$data_headers['activity_id']   = array(
			'label'   => esc_html__( 'activity_id', 'learndash' ),
			'default' => '',
			'display' => array( 'CodingBlackFemales\Multisite\Customizations\LearnDash', 'report_column' ),
		);
		self::$data_headers['user_id']   = array(
			'label'   => esc_html__( 'user_id', 'learndash' ),
			'default' => '',
			'display' => array( 'CodingBlackFemales\Multisite\Customizations\LearnDash', 'report_column' ),
		);
		self::$data_headers['user_name'] = array(
			'label'   => esc_html__( 'name', 'learndash' ),
			'default' => '',
			'display' => array( 'CodingBlackFemales\Multisite\Customizations\LearnDash', 'report_column' ),
		);
		self::$data_headers['user_email'] = array(
			'label'   => esc_html__( 'email', 'learndash' ),
			'default' => '',
			'display' => array( 'CodingBlackFemales\Multisite\Customizations\LearnDash', 'report_column' ),
		);
		self::$data_headers['quiz_id']    = array(
			'label'   => esc_html__( 'quiz_id', 'learndash' ),
			'default' => '',
			'display' => array( 'CodingBlackFemales\Multisite\Customizations\LearnDash', 'report_column' ),
		);
		self::$data_headers['quiz_title'] = array(
			'label'   => esc_html__( 'quiz_title', 'learndash' ),
			'default' => '',
			'display' => array( 'CodingBlackFemales\Multisite\Customizations\LearnDash', 'report_column' ),
		);
		self::$data_headers['quiz_score'] = array(
			'label'   => esc_html__( 'score', 'learndash' ),
			'default' => '0',
			'display' => array( 'CodingBlackFemales\Multisite\Customizations\LearnDash', 'report_column' ),
		);
		self::$data_headers['quiz_total'] = array(
			'label'   => esc_html__( 'total', 'learndash' ),
			'default' => '0',
			'display' => array( 'CodingBlackFemales\Multisite\Customizations\LearnDash', 'report_column' ),
		);
		self::$data_headers['quiz_date']  = array(
			'label'   => esc_html__( 'date', 'learndash' ),
			'default' => '',
			'display' => array( 'CodingBlackFemales\Multisite\Customizations\LearnDash', 'report_column' ),
		);
		self::$data_headers['quiz_points'] = array(
			'label'   => esc_html__( 'points', 'learndash' ),
			'default' => '0',
			'display' => array( 'CodingBlackFemales\Multisite\Customizations\LearnDash', 'report_column' ),
		);
		self::$data_headers['quiz_points_total'] = array(
			'label'   => esc_html__( 'points_total', 'learndash' ),
			'default' => '0',
			'display' => array( 'CodingBlackFemales\Multisite\Customizations\LearnDash', 'report_column' ),
		);
		self::$data_headers['quiz_percentage'] = array(
			'label'   => esc_html__( 'percentage', 'learndash' ),
			'default' => '0',
			'display' => array( 'CodingBlackFemales\Multisite\Customizations\LearnDash', 'report_column' ),
		);
		self::$data_headers['quiz_time_spent'] = array(
			'label'   => esc_html__( 'time_spent', 'learndash' ),
			'default' => '0',
			'display' => array( 'CodingBlackFemales\Multisite\Customizations\LearnDash', 'report_column' ),
		);
		self::$data_headers['quiz_passed'] = array(
			'label'   => esc_html__( 'passed', 'learndash' ),
			'default' => esc_html_x( 'NO', 'Quiz Passed Report label: NO', 'learndash' ),
			'display' => array( 'CodingBlackFemales\Multisite\Customizations\LearnDash', 'report_column' ),
		);
		self::$data_headers['course_id'] = array(
			'label'   => esc_html__( 'course_id', 'learndash' ),
			'default' => '',
			'display' => array( 'CodingBlackFemales\Multisite\Customizations\LearnDash', 'report_column' ),
		);
		self::$data_headers['course_title'] = array(
			'label'   => esc_html__( 'course_title', 'learndash' ),
			'default' => '',
			'display' => array( 'CodingBlackFemales\Multisite\Customizations\LearnDash', 'report_column' ),
		);
	}

	/**
	 * Handles display formatting of report column value.
	 *
	 * @since 2.3.0
	 *
	 * @param int|string $column_value Report column value.
	 * @param string     $column_key   Column key.
	 * @param object     $report_item  Report Item.
	 * @param WP_User    $report_user  WP_User object.
	 *
	 * @return mixed $column_value;
	 */
	// phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded, Generic.Metrics.NestingLevel.MaxExceeded
	protected static function report_column( $column_value, $column_key, $report_item, $report_user ) {

		switch ( $column_key ) {
			case 'user_id':
				if ( $report_user instanceof \WP_User ) {
					$column_value = $report_user->ID;
				}
				break;

			case 'user_name':
				if ( $report_user instanceof \WP_User ) {
					$column_value = $report_user->display_name;
					$column_value = str_replace( '’', "'", $column_value );
				}
				break;

			case 'user_email':
				if ( $report_user instanceof \WP_User ) {
					$column_value = $report_user->user_email;
				}
				break;

			case 'quiz_id':
				if ( property_exists( $report_item, 'post_id' ) ) {
					$column_value = intval( $report_item->post_id );
				}
				break;

			case 'quiz_title':
				if ( property_exists( $report_item, 'post_title' ) ) {
					$column_value = $report_item->post_title;
					$column_value = str_replace( '’', "'", $column_value );
				}
				break;

			case 'quiz_rank':
				if ( ( property_exists( $report_item, 'activity_meta' ) ) && ( ! empty( $report_item->activity_meta ) ) ) {
					if ( ( isset( $report_item->activity_meta['rank'] ) ) && ( ! empty( $report_item->activity_meta['rank'] ) ) ) {
						$column_value = $report_item->activity_meta['rank'];
					}
				}
				break;

			case 'quiz_score':
				if ( ( property_exists( $report_item, 'activity_meta' ) ) && ( ! empty( $report_item->activity_meta ) ) ) {
					if ( ( isset( $report_item->activity_meta['score'] ) ) && ( ! empty( $report_item->activity_meta['score'] ) ) ) {
						$column_value = intval( $report_item->activity_meta['score'] );
					} else {
						$column_value = 0;
					}
				}
				break;

			case 'quiz_total':
				if ( ( property_exists( $report_item, 'activity_meta' ) ) && ( ! empty( $report_item->activity_meta ) ) ) {
					if ( ( isset( $report_item->activity_meta['question_show_count'] ) ) && ( ! empty( $report_item->activity_meta['question_show_count'] ) ) ) {
						$column_value = intval( $report_item->activity_meta['question_show_count'] );
					} elseif ( ( isset( $report_item->activity_meta['count'] ) ) && ( ! empty( $report_item->activity_meta['count'] ) ) ) {
						$column_value = intval( $report_item->activity_meta['count'] );
					}
				}
				break;

			case 'quiz_date':
				if ( ( property_exists( $report_item, 'activity_completed' ) ) && ( ! empty( $report_item->activity_completed ) ) ) {
					$column_value = learndash_adjust_date_time_display( $report_item->activity_completed, 'Y-m-d' );
				}
				break;

			case 'quiz_points':
				if ( ( property_exists( $report_item, 'activity_meta' ) ) && ( ! empty( $report_item->activity_meta ) ) ) {
					if ( ( isset( $report_item->activity_meta['points'] ) ) && ( ! empty( $report_item->activity_meta['points'] ) ) ) {
						$column_value = intval( $report_item->activity_meta['points'] );
					}
				}
				break;

			case 'quiz_points_total':
				if ( ( property_exists( $report_item, 'activity_meta' ) ) && ( ! empty( $report_item->activity_meta ) ) ) {
					if ( ( isset( $report_item->activity_meta['total_points'] ) ) && ( ! empty( $report_item->activity_meta['total_points'] ) ) ) {
						$column_value = intval( $report_item->activity_meta['total_points'] );
					}
				}
				break;

			case 'quiz_percentage':
				if ( ( property_exists( $report_item, 'activity_meta' ) ) && ( ! empty( $report_item->activity_meta ) ) ) {
					if ( ( isset( $report_item->activity_meta['percentage'] ) ) && ( ! empty( $report_item->activity_meta['percentage'] ) ) ) {
						$column_value = round( floatval( $report_item->activity_meta['percentage'] ), 2 );
					}
				}
				break;

			case 'quiz_time_spent':
				if ( ( property_exists( $report_item, 'activity_meta' ) ) && ( ! empty( $report_item->activity_meta ) ) ) {
					if ( ( isset( $report_item->activity_meta['timespent'] ) ) && ( ! empty( $report_item->activity_meta['timespent'] ) ) ) {

						$timespent    = abs( round( $report_item->activity_meta['timespent'] ) );
						$column_value = '';

						if ( $timespent > 86400 ) {
							if ( ! empty( $column_value ) ) { // @phpstan-ignore-line
								$column_value .= ' ';
							}
							$column_value .= floor( $timespent / 86400 ) . 'd';
							$timespent    %= 86400;
						}

						if ( $timespent > 3600 ) {
							if ( ! empty( $column_value ) ) {
								$column_value .= ' ';
							}
							$column_value .= floor( $timespent / 3600 ) . 'h';
							$timespent    %= 3600;
						}

						if ( $timespent > 60 ) {
							if ( ! empty( $column_value ) ) {
								$column_value .= ' ';
							}
							$column_value .= floor( $timespent / 60 ) . 'm';
							$timespent    %= 60;
						}

						if ( $timespent > 0 ) {
							if ( ! empty( $column_value ) ) {
								$column_value .= ' ';
							}
							$column_value .= $timespent . 's';
						}
					}
				}
				break;

			case 'quiz_passed':
				if ( ( property_exists( $report_item, 'activity_meta' ) ) && ( ! empty( $report_item->activity_meta ) ) ) {
					if ( ( isset( $report_item->activity_meta['pass'] ) ) && ( $report_item->activity_meta['pass'] == 1 ) ) {
						$column_value = esc_html_x( 'YES', 'Quiz Passed Report label: YES', 'learndash' );
					}
				}
				break;

			case 'course_id':
				if ( property_exists( $report_item, 'activity_course_id' ) ) {
					$course_id = intval( $report_item->activity_course_id );
					if ( ! empty( $course_id ) ) {
						$column_value = $course_id;
					} else {
						$column_value = '';
					}
				}
				break;

			case 'course_title':
				if ( property_exists( $report_item, 'activity_course_id' ) ) {
					$course_id = intval( $report_item->activity_course_id );
					if ( ! empty( $course_id ) ) {
						$column_value = get_the_title( $course_id );
					} else {
						$column_value = '';
					}
				}
				break;

			case 'activity_id':
				if ( property_exists( $report_item, 'activity_id' ) ) {
					$activity_id = intval( $report_item->activity_id );
					if ( ! empty( $activity_id ) ) {
						$column_value = $activity_id;
					} else {
						$column_value = '';
					}
				}
				break;

			default:
				break;
		}

		return $column_value;
	}

	/**
	 * Sorts an array of associative arrays by a specified key.
	 *
	 * @param array $array The array to be sorted.
	 * @param string     $key   The associative array key to be sorted against.
	 *
	 * @return array $array;
	 */
	protected static function sort_nested_array( $array, $key ) {
		usort(
			$array,
			function( $a, $b ) use ( $key ) {
				// phpcs:ignore PHPCompatibility.Operators.NewOperators.t_spaceshipFound
				return $a[ $key ] <=> $b[ $key ];
			}
		);

		return $array;
	}

	/**
	 * Returns all quiz activity results.
	 *
	 * @return array $array;
	 */
	// phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded, Generic.Metrics.NestingLevel.MaxExceeded
	public static function get_results() {
		$course_progress_data = array();
		$transient_data = array();
		$transient_data['posts_ids'] = '';
		// phpcs:ignore PHPCompatibility.Operators.NewOperators.t_coalesceFound
		$transient_data['users_ids'] = learndash_get_report_user_ids() ?? array();
		$transient_data['total_users'] = count( $transient_data['users_ids'] );
		$activity_query_args = array(
			'post_types'      => 'sfwd-quiz',
			'activity_types'  => 'quiz',
			'activity_status' => array( 'IN_PROGRESS', 'COMPLETED' ),
			'orderby_order'   => 'users.display_name, posts.post_title ASC',
			'date_format'     => 'F j, Y H:i:s',
			'per_page'        => '',
			'time_start'      => '',
			'time_end'        => '',
		);

		if ( empty( self::$data_headers ) ) {
			self::set_report_headers();
		}

		foreach ( $transient_data['users_ids'] as $user_id_idx => $user_id ) {
			unset( $transient_data['users_ids'][ $user_id_idx ] );
			$report_user = get_user_by( 'id', $user_id );

			if ( $report_user !== false ) {
				$activity_query_args['user_ids'] = array( $user_id );

				if ( ( isset( $transient_data['posts_ids'] ) ) && ( ! empty( $transient_data['posts_ids'] ) ) ) {
					$post_ids                        = $transient_data['posts_ids'];
					$activity_query_args['post_ids'] = $post_ids;
				}

				if ( ( isset( $transient_data['course_ids'] ) ) && ( ! empty( $transient_data['course_ids'] ) ) ) {
					$activity_query_args['course_ids'] = $transient_data['course_ids'];
				}

				if ( ( isset( $transient_data['time_start'] ) ) && ( ! empty( $transient_data['time_start'] ) ) ) {
					$activity_query_args['time_start'] = esc_attr( $transient_data['time_start'] );
				}

				if ( ( isset( $transient_data['time_end'] ) ) && ( ! empty( $transient_data['time_end'] ) ) ) {
					$activity_query_args['time_end'] = esc_attr( $transient_data['time_end'] );
				}

				$user_courses_reports = learndash_reports_get_activity( $activity_query_args );
				if ( ! empty( $user_courses_reports['results'] ) ) {
					foreach ( $user_courses_reports['results'] as $result ) {

						/**
						 * Added LD 3.2.0 - PP-204
						 * Missing Activity meta data. As a secondary pull from the user quiz meta.
						 */
						if ( ( ( ! property_exists( $result, 'activity_meta' ) ) || ( empty( $result->activity_meta ) ) ) && ! empty( $user_quiz_meta ) ) {
							foreach ( $user_quiz_meta as $user_meta_item ) {
								if ( ( absint( $result->post_id ) === absint( $user_meta_item['quiz'] ) ) && ( absint( $result->activity_updated ) === absint( $user_meta_item['time'] ) ) && ( absint( $result->activity_started ) === absint( $user_meta_item['started'] ) ) ) {
									$result->activity_meta = $user_meta_item;
									break;
								}
							}
						}
					}

					$row = array();

					foreach ( self::$data_headers as $header_key => $header_data ) {

						if ( ( isset( $header_data['display'] ) ) && ( ! empty( $header_data['display'] ) ) && ( is_callable( $header_data['display'] ) ) ) {
							$row[ $header_key ] = call_user_func_array(
								$header_data['display'],
								array(
									$header_data['default'],
									$header_key,
									$result,
									$report_user,
								)
							);
						} elseif ( ( isset( $header_data['default'] ) ) && ( ! empty( $header_data['default'] ) ) ) {
							$row[ $header_key ] = $header_data['default'];
						} else {
							$row[ $header_key ] = '';
						}
					}

					if ( ! empty( $row ) ) {
						$course_progress_data[] = $row;
					}
				}
			}
		}

		return self::sort_nested_array( $course_progress_data, 'activity_id' );
	}
}
