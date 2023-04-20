<?php
/**
 * Displays the Quiz Progress rows
 * Available Variables:
 * none
 *
 * @since 2.1.0
 *
 * @package LearnDash\Templates\Legacy\Quiz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php foreach ( $quizzes as $k => $v ) : ?>
	<?php $quiz = get_post( $v['quiz'] ); ?>
	<?php
	if ( ( ! ( $quiz instanceof WP_Post ) ) || ( $quiz->post_type != 'sfwd-quiz' ) ) {
		if ( ( isset( $v['pro_quizid'] ) ) && ( ! empty( $v['pro_quizid'] ) ) ) {
			$quiz_post_id = learndash_get_quiz_id_by_pro_quiz_id( intval( $v['pro_quizid'] ) );
			if ( ! empty( $quiz_post_id ) ) {
				$quiz = get_post( $quiz_post_id );
			}
		}
	}

	if ( ( ! ( $quiz instanceof WP_Post ) ) || ( $quiz->post_type != 'sfwd-quiz' ) ) {
		continue;
	}

	$certificateLink       = '';
	$certificate_threshold = 0;

	if ( ! isset( $v['has_graded'] ) ) {
		$v['has_graded'] = false;
	}

	$c = learndash_certificate_details( $v['quiz'], $user_id );
	if ( ( isset( $c['certificateLink'] ) ) && ( ! empty( $c['certificateLink'] ) ) ) {
		$certificateLink = $c['certificateLink'];
	}

	if ( ( isset( $c['certificate_threshold'] ) ) && ( '' !== $c['certificate_threshold'] ) ) {
		$certificate_threshold = $c['certificate_threshold'];
	}

	$passstatus = isset( $v['pass'] ) ? ( ( $v['pass'] == 1 ) ? 'green' : 'red' ) : '';
	?>

	<?php $quiz_title = ! empty( $quiz->post_title ) ? $quiz->post_title : @$v['quiz_title']; ?>
	<?php
		$quiz_course_id = 0;
	if ( isset( $v['course'] ) ) {
		$quiz_course_id = intval( $v['course'] );
	} else {
		$quiz_course_id = learndash_get_course_id( $quiz, true );
	}
	?>


	<?php if ( ! empty( $quiz_title ) ) : ?>
		<p id="ld-quiz-<?php echo $v['time']; ?>">
			<strong><a href="<?php echo esc_url( learndash_get_step_permalink( $quiz->ID, $quiz_course_id ) ); ?>"><?php echo $quiz_title; ?></a></strong>
			<?php
			if ( ( isset( $v['course'] ) ) && ( intval( $v['course'] ) != learndash_get_course_id( $quiz, true ) ) ) {
				$quiz_course_title = get_the_title( $v['course'] );
				if ( ! empty( $quiz_course_title ) ) {
					echo ' - <a href="' . esc_url( get_the_permalink( $v['course'] ) ) . '">' . wp_kses_post( $quiz_course_title ) . '</a>';
				}
			}
			?>
			<?php echo isset( $v['percentage'] ) ? " - <span style='color:" . $passstatus . "'>" . $v['percentage'] . '%</span>' : ''; ?>
			<?php
			if ( ( ( get_current_user_id() == $user_id ) || ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) && ( ! empty( $certificateLink ) ) ) {
				if ( ( ( isset( $v['pass'] ) ) && ( 1 == $v['pass'] ) ) &&
					( ( isset( $v['percentage'] ) && $v['percentage'] >= ( $certificate_threshold * 100 ) ) ) ||
					( ( isset( $v['count'] ) ) && ( intval( $v['count'] ) ) && ( isset( $v['score'] ) ) && ( intval( $v['score'] ) ) &&
						( ( intval( $v['score'] ) / intval( $v['count'] ) ) >= ( $certificate_threshold * 100 ) )
					)
				) {
					$certificateLink = add_query_arg( array( 'time' => $v['time'] ), $certificateLink );
					?>
						 - <a href='<?php echo esc_url( $certificateLink ); ?>' target='_blank'><?php echo esc_html__( 'Certificate', 'learndash' ); ?></a>
					<?php
				}
			}

			if ( ( get_current_user_id() == $user_id ) || ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) {
				if ( ( ! isset( $v['statistic_ref_id'] ) ) || ( empty( $v['statistic_ref_id'] ) ) ) {
					$v['statistic_ref_id'] = learndash_get_quiz_statistics_ref_for_quiz_attempt( $user_id, $v );
				}

				if ( ( isset( $v['statistic_ref_id'] ) ) && ( ! empty( $v['statistic_ref_id'] ) ) ) {
					/** This filter is documented in themes/ld30/templates/quiz/partials/attempt.php */
					if ( apply_filters(
						'show_user_profile_quiz_statistics',
						get_post_meta( $v['quiz'], '_viewProfileStatistics', true ),
						$user_id,
						$v,
						basename( __FILE__ )
					) ) {
						?>
							<a class="user_statistic" data-statistic_nonce="<?php echo esc_attr( wp_create_nonce( 'statistic_nonce_' . $v['statistic_ref_id'] . '_' . get_current_user_id() . '_' . $user_id ) ); ?>" data-user_id="<?php echo $user_id; ?>" data-quiz_id="<?php echo esc_attr( $v['pro_quizid'] ); ?>" data-ref_id="<?php echo intval( $v['statistic_ref_id'] ); ?>" href="#"><?php esc_html_e( 'Statistics', 'learndash' ); ?></a>
							<?php
					}
				}
			}

			if ( isset( $v['m_edit_by'] ) ) {
				$manual_edit_user = get_user_by( 'id', $v['m_edit_by'] );
				if ( $manual_edit_user instanceof WP_User ) {
					$manual_edit_str = sprintf(
						// translators: placeholders: User, Date.
						_x( 'Manual Edit by: %1$s on %2$s', 'placeholders: User, Date', 'learndash' ),
						$manual_edit_user->display_name,
						/** This filter is documented in includes/ld-misc-functions.php */
						date_i18n( apply_filters( 'learndash_date_time_formats', get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ), $v['m_edit_time'] + get_option( 'gmt_offset' ) * 3600 )
					);

					?>
					<abbr title="<?php echo esc_attr( $manual_edit_str ); ?>"><?php esc_html_e( '(m)', 'learndash' ); ?></abbr>
					<?php
				}
			}
			?>
			<?php
			if ( current_user_can( 'wpProQuiz_edit_quiz' ) ) {
				?>
				<a href="<?php echo esc_url( add_query_arg( 'course_id', $quiz_course_id, get_edit_post_link( $quiz->ID ) ) ); ?>"><?php echo esc_html_x( '(edit)', 'profile edit quiz link label', 'learndash' ); ?></a>
				<?php if ( learndash_show_user_course_complete( $user_id ) ) { ?>
				<a class="remove-quiz" data-quiz-user-id="<?php echo (int) $user_id; ?>" data-quiz-nonce="<?php echo esc_attr( wp_create_nonce( 'remove_quiz_' . $user_id . '_' . $v['quiz'] . '_' . $v['time'] ) ); ?>" href="#" title="
																	 <?php
																		// translators: placeholder: quiz.
																		echo sprintf( esc_html_x( 'remove this %s item', 'placeholder: quiz', 'learndash' ), esc_html( learndash_get_custom_label_lower( 'quiz' ) ) )
																		?>
					"><?php echo esc_html_x( '(remove)', 'profile remove quiz link label', 'learndash' ); ?></a>
					<?php
				}
			}
			?>
			<br/>

			<?php
			if ( ( true === $v['has_graded'] ) && ( isset( $v['graded'] ) ) && ( is_array( $v['graded'] ) ) && ( ! empty( $v['graded'] ) ) ) {
				foreach ( $v['graded'] as $quiz_question_id => $graded ) {

					if ( isset( $graded['post_id'] ) ) {

						$graded_post = get_post( $graded['post_id'] );
						if ( $graded_post instanceof WP_Post ) {

							if ( $graded['status'] == 'graded' ) {
								$graded_color = ' color: green;';
							} else {
								$graded_color = ' color: red;';
							}

							$post_status_object_label = get_post_status_object( $graded['status'] )->label;

							echo /* $post_type_object_label_name .': '. */ get_the_title( $graded['post_id'] ) . ', ' . esc_html__( 'Status', 'learndash' ) . ': <span style="' . $graded_color . '">' . esc_html( $post_status_object_label ) . '</span>, ' . esc_html__( 'Points', 'learndash' ) . ': ' . esc_html( $graded['points_awarded'] );

							if ( current_user_can( 'edit_essays' ) ) {
								echo ' <a target="_blank" href="' . esc_url( get_edit_post_link( $graded['post_id'] ) ) . '">' . esc_html__( 'edit', 'learndash' ) . '</a>';
							}
							echo ' <a target="_blank" href="' . esc_url( get_permalink( $graded['post_id'] ) ) . '">' . esc_html__( 'view', 'learndash' ) . '</a>';

							echo ' <a target="_blank" href="' . esc_url( get_permalink( $graded['post_id'] ) ) . '#comments">' . esc_html__( 'comments', 'learndash' ) . ' ' . esc_html( get_comments_number( $graded['post_id'] ) ) . '</a>';
							echo '<br />';
						}
					}
				}
			}
			?>


			<?php if ( isset( $v['rank'] ) && is_numeric( $v['rank'] ) ) : ?>
				<?php echo esc_html__( 'Rank: ', 'learndash' ); ?> <?php echo esc_html( $v['rank'] ); ?>,
			<?php endif; ?>

			<?php echo esc_html__( 'Score ', 'learndash' ); ?><?php echo esc_html( $v['score'] ); ?> <?php echo esc_html__( ' out of ', 'learndash' ); ?> <?php
			if ( ( isset( $v['question_show_count'] ) ) && ( ! empty( $v['question_show_count'] ) ) ) {
				echo esc_html( $v['question_show_count'] );
			} else {
				echo esc_html( $v['count'] );
			}
			?>
			<?php echo esc_html__( ' question(s)', 'learndash' ); ?>

			<?php if ( isset( $v['points'] ) && isset( $v['total_points'] ) ) : ?>
				<?php echo esc_html__( ' . Points: ', 'learndash' ); ?> <?php echo esc_html( $v['points'] ); ?>/<?php echo esc_html( $v['total_points'] ); ?>
			<?php endif; ?>

			<?php echo esc_html__( ' on ', 'learndash' ); ?>
			<?php
			/** This filter is documented in includes/ld-misc-functions.php */
			echo date_i18n( apply_filters( 'learndash_date_time_formats', get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ), $v['time'] + get_option( 'gmt_offset' ) * 3600 );

			if ( is_admin() ) {
				echo ' <a onClick="return false;" title="' . esc_html__( 'copy link to use in [quizinfo] block.', 'learndash' ) . '" href="data:quizinfo:quiz:' . absint( $quiz->ID ) . ':user:' . absint( $user_id ) . ':time:' . absint( $v['time'] ) . '">' . esc_html__( '#', 'learndash' ) . '</a>';
			}

			/**
			 * Filters content to be echoed after course info shortcode item.
			 *
			 * @todo filter doesn't make sense, change to action?
			 *
			 * @since 2.1.0
			 *
			 * @param string  $content            The content to be echoed after course info item.
			 * @param WP_Post $quiz               The `WP_Post` quiz object.
			 * @param array   $quiz_progress_data An array of quiz data.
			 * @param int     $user_id            User ID.
			 */
			echo apply_filters( 'course_info_shortcode_after_item', '', $quiz, $v, $user_id );
			?>
		</p>
	<?php endif; ?>
<?php endforeach; ?>
