<?php
/**
 * LearnDash `[quizinfo]` shortcode processing.
 *
 * @since 2.1.0
 *
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the `[quizinfo]` shortcode output.
 *
 * Shortcode that displays the requested quiz information.
 *
 * @global boolean $learndash_shortcode_used
 *
 * @since 2.1.0
 *
 * @param array  $attr {
 *    An array of shortcode attributes.
 *
 *    @type string     $show     The quiz info field to display. Default empty.
 *    @type int|string $user_id  User ID. Default empty.
 *    @type int|string $quiz     Quiz ID. Default empty.
 *    @type int|string $time     Timestamp. Default empty.
 *    @type string     $field_id ID of the field. Default empty.
 *    @type string     $format   Date display format. Default 'F j, Y, g:i a'.
 * }
 * @param string $content The shortcode content. Default empty.
 * @param string $shortcode_slug The shortcode slug. Default 'quizinfo'.
 *
 * @return string The `ld_quiz_complete` shortcode output.
 */
function learndash_quizinfo( $attr = array(), $content = '', $shortcode_slug = 'quizinfo' ) {
	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;

	$shortcode_atts = shortcode_atts(
		array(
			/** [score], [count], [pass], [rank], [timestamp], [pro_quizid], [points], [total_points], [percentage], [timespent]. */
			'show'     => 'quiz_title',
			'user_id'  => '',
			'quiz'     => '',
			'time'     => '',
			'field_id' => '',
			'format'   => 'F j, Y, g:i a',
		),
		$attr
	);

	/** This filter is documented in includes/shortcodes/ld_course_resume.php */
	$shortcode_atts = apply_filters( 'learndash_shortcode_atts', $shortcode_atts, $shortcode_slug );

	$time_range_start = 0;
	$time_range_end   = 0;

	if ( ( isset( $shortcode_atts['time'] ) ) && ( '' !== $shortcode_atts['time'] ) ) {
		$shortcode_atts['time'] = absint( $shortcode_atts['time'] );
	}

	extract( $shortcode_atts ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

	$time      = ( empty( $time ) && isset( $_REQUEST['time'] ) ) ? absint( $_REQUEST['time'] ) : $time; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$show      = ( empty( $show ) && isset( $_REQUEST['show'] ) ) ? esc_attr( $_REQUEST['show'] ) : $show; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	$quiz      = ( empty( $quiz ) && isset( $_REQUEST['quiz'] ) ) ? absint( $_REQUEST['quiz'] ) : $quiz; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$user_id   = ( empty( $user_id ) && isset( $_REQUEST['user_id'] ) ) ? absint( $_REQUEST['user_id'] ) : $user_id; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$course_id = ( empty( $course_id ) && isset( $_REQUEST['course_id'] ) ) ? absint( $_REQUEST['course_id'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$field_id  = ( empty( $field_id ) && isset( $_REQUEST['field_id'] ) ) ? absint( $_REQUEST['field_id'] ) : $field_id; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();

		/**
		 * Added logic to allow admin and group_leader to view certificate from other users.
		 *
		 * @since 2.3.0
		 */
		$post_type = '';
		if ( get_query_var( 'post_type' ) ) {
			$post_type = get_query_var( 'post_type' );
		}

		if ( 'sfwd-certificates' === $post_type ) {
			if ( ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) && ( ( isset( $_GET['user'] ) ) && ( ! empty( $_GET['user'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$user_id = intval( $_GET['user'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
		}
	}

	if ( empty( $quiz ) || empty( $user_id ) || empty( $show ) ) {
		return '';
	}

	$shortcode_atts['time']      = $time;
	$shortcode_atts['show']      = $show;
	$shortcode_atts['quiz']      = $quiz;
	$shortcode_atts['user_id']   = $user_id;
	$shortcode_atts['course_id'] = $course_id;
	$shortcode_atts['field_id']  = $field_id;

	$quizinfo = get_user_meta( $user_id, '_sfwd-quizzes', true );

	$selected_quizinfo  = '';
	$selected_quizinfo2 = '';

	foreach ( $quizinfo as $quiz_i ) {

		if ( ( isset( $quiz_i['time'] ) ) && ( ! empty( $time ) ) && ( absint( $quiz_i['time'] ) == $time ) && ( absint( $quiz_i['quiz'] ) == absint( $quiz ) ) ) {
			$selected_quizinfo = $quiz_i;
			break;
		}

		if ( ( ! empty( $time_range_start ) ) && ! ( empty( $time_range_end ) ) ) {
			if ( ( isset( $quiz_i['time'] ) ) && ( $quiz_i['time'] >= $time_range_start ) && ( $quiz_i['time'] <= $time_range_end ) && ( $quiz_i['quiz'] == $quiz ) ) {
				$selected_quizinfo2 = $quiz_i;
				break;
			}
		}

		if ( $quiz_i['quiz'] == $quiz ) {
			$selected_quizinfo2 = $quiz_i;
		}
	}

	$selected_quizinfo = empty( $selected_quizinfo ) ? $selected_quizinfo2 : $selected_quizinfo;

	if ( ! is_array( $selected_quizinfo ) ) {
		$selected_quizinfo = array();
	}

	switch ( $show ) {
		case 'timestamp':
			if ( ( isset( $selected_quizinfo['time'] ) ) && ( ! empty( $selected_quizinfo['time'] ) ) ) {
				$selected_quizinfo['timestamp'] = learndash_adjust_date_time_display( $selected_quizinfo['time'], $format );
			} else {
				$selected_quizinfo['timestamp'] = '';
			}
			break;

		case 'percentage':
			if ( empty( $selected_quizinfo['percentage'] ) ) {
				$selected_quizinfo['percentage'] = empty( $selected_quizinfo['count'] ) ? 0 : $selected_quizinfo['score'] * 100 / $selected_quizinfo['count'];
			}
			break;

		case 'pass':
			if ( ( isset( $selected_quizinfo['pass'] ) ) && ( ! empty( $selected_quizinfo['pass'] ) ) ) {
				$selected_quizinfo['pass'] = esc_html__( 'Yes', 'learndash' );
			} else {
				$selected_quizinfo['pass'] = esc_html__( 'No', 'learndash' );
			}
			break;

		case 'quiz_title':
			if ( ( ! empty( $quiz ) ) && ( get_post_type( $quiz ) == learndash_get_post_type_slug( 'quiz' ) ) ) {
				$selected_quizinfo['quiz_title'] = get_the_title( $quiz );
			} else {
				$selected_quizinfo['quiz_title'] = '';
			}
			break;

		case 'course_title':
			if ( ( isset( $selected_quizinfo['course'] ) ) && ( ! empty( $selected_quizinfo['course'] ) ) ) {
				$course_id = intval( $selected_quizinfo['course'] );
			} else {
				$course_id = learndash_get_setting( $quiz, 'course' );
			}
			if ( ( ! empty( $course_id ) ) && ( get_post_type( $course_id ) == learndash_get_post_type_slug( 'course' ) ) ) {
				$selected_quizinfo['course_title'] = get_the_title( $course_id );
			} else {
				$selected_quizinfo['course_title'] = '';
			}
			break;

		case 'timespent':
			if ( ( isset( $selected_quizinfo['timespent'] ) ) && ( ! empty( $selected_quizinfo['timespent'] ) ) ) {
				$selected_quizinfo['timespent'] = learndash_seconds_to_time( $selected_quizinfo['timespent'] );
			}
			break;

		case 'field':
			if ( ! empty( $field_id ) ) {
				if ( ( isset( $selected_quizinfo['pro_quizid'] ) ) && ( ! empty( $selected_quizinfo['pro_quizid'] ) ) ) {
					$form_mapper        = new WpProQuiz_Model_FormMapper();
					$quiz_form_elements = $form_mapper->fetch( $selected_quizinfo['pro_quizid'] );
					if ( ! empty( $quiz_form_elements ) ) {
						foreach ( $quiz_form_elements as $quiz_form_element ) {
							if ( absint( $field_id ) == absint( $quiz_form_element->getFormId() ) ) {
								$selected_quizinfo[ $show ] = '';

								if ( ( isset( $selected_quizinfo['statistic_ref_id'] ) ) && ( ! empty( $selected_quizinfo['statistic_ref_id'] ) ) ) {
									$statistic_ref_mapper = new WpProQuiz_Model_StatisticRefMapper();
									$statistic_ref_data   = $statistic_ref_mapper->fetchAllByRef( $selected_quizinfo['statistic_ref_id'] );
									if ( ( $statistic_ref_data ) && ( is_a( $statistic_ref_data, 'WpProQuiz_Model_StatisticRefModel' ) ) ) {
										$form_data = $statistic_ref_data->getFormData();
										if ( isset( $form_data[ $field_id ] ) ) {
											$selected_quizinfo[ $show ] = $quiz_form_element->getValue( $form_data[ $field_id ] );
											if ( WpProQuiz_Model_Form::FORM_TYPE_DATE === $quiz_form_element->getType() ) {
												$selected_quizinfo[ $show ] = date_i18n( $format, strtotime( $selected_quizinfo[ $show ] ) );
											}
										}
									}
								}
								break;
							}
						}
					}
				}
			}
			break;

	}

	if ( isset( $selected_quizinfo[ $show ] ) ) {
		/**
		 * Filters quizinfo shortcode output.
		 *
		 * @since 2.1.0
		 * @since 3.1.4 Added $selected_quizinfo param.
		 *
		 * @param string $shortcode_output     The output of quizinfo shortcode.
		 * @param array  $shortcode_attributes An array of shortcode attributes.
		 * @param array  $selected_quizinfo    Quiz item array used for processing.
		 */
		return apply_filters( 'learndash_quizinfo', $selected_quizinfo[ $show ], $shortcode_atts, $selected_quizinfo );
	} else {
		/** This filter is documented in includes/quiz/ld-quiz-info-shortcode.php */
		return apply_filters( 'learndash_quizinfo', '', $shortcode_atts, $selected_quizinfo );
	}
}
add_shortcode( 'quizinfo', 'learndash_quizinfo', 10, 3 );
