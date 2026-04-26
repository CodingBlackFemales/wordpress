<?php
/**
 * LearnDash LD30 Displays a user's profile quiz row.
 *
 * @since 3.0.0
 * @version 4.25.4
 *
 * @package LearnDash\Templates\LD30
 */

use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\Arrays\Arr;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Defaults for fallbacks.
$certificate_link = null;
$score            = null;
$stats            = '<span aria-label="' . esc_attr__( 'The statistic is not available.', 'learndash' ) .'">--</span>';

/**
 * Set the quiz status and certificate link (if applicable)
 */
if ( ( isset( $quiz_attempt['certificate']['certificateLink'] ) ) && ( ! empty( $quiz_attempt['certificate']['certificateLink'] ) ) ) {
	$certificate_link = $quiz_attempt['certificate']['certificateLink'];
} else {
	$certificate_link = '';
}

$status = empty( $quiz_attempt['pass'] ) ? 'failed' : 'passed';

/**
 * Populate the score variables
 */
$score = round( $quiz_attempt['percentage'], 2 ) . '%';

/**
 * Populate the stats variable
 */
if ( get_current_user_id() === absint( $user_id ) || learndash_is_admin_user() || learndash_is_group_leader_user() ) :

	if ( ! isset( $quiz_attempt['statistic_ref_id'] ) || empty( $quiz_attempt['statistic_ref_id'] ) ) {
		$quiz_attempt['statistic_ref_id'] = learndash_get_quiz_statistics_ref_for_quiz_attempt( $user_id, $quiz_attempt );
	}

	if (
		! empty( $quiz_attempt['statistic_ref_id'] )
		&& isset( $quiz_attempt['post'] )
		&& $quiz_attempt['post'] instanceof WP_Post
	) {
		/** This filter is documented in themes/ld30/templates/quiz/partials/attempt.php */
		if ( apply_filters( 'show_user_profile_quiz_statistics', get_post_meta( $quiz_attempt['post']->ID, '_viewProfileStatistics', true ), $user_id, $quiz_attempt, basename( __FILE__ ) ) ) {
			$stats = sprintf(
				'
				<a
					aria-label="' . sprintf(
						// translators: %s: quiz label.
						esc_attr__(
							'View the statistics of the %s attempt.',
							'learndash'
						),
						learndash_get_custom_label_lower( 'quiz' )
					) . '"
					class="user_statistic"
					data-statistic-nonce="%s"
					data-user-id="%s"
					data-quiz-id="%s"
					data-ref-id="%s"
					href="#"
				>
					<span class="ld-icon ld-icon-assignment"></span> %s
				</a>
				',
				wp_create_nonce( 'statistic_nonce_' . $quiz_attempt['statistic_ref_id'] . '_' . get_current_user_id() . '_' . $user_id ),
				esc_attr( $user_id ),
				esc_attr( $quiz_attempt['pro_quizid'] ),
				esc_attr( Cast::to_int( $quiz_attempt['statistic_ref_id'] ) ),
				esc_html__( 'View', 'learndash' )
			);
		}
	}

endif;

// Quiz title and link...
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
$quiz_title = ! empty( $quiz_attempt['post']->post_title ) ? apply_filters( 'the_title', $quiz_attempt['post']->post_title, $quiz_attempt['post']->ID ) : get_the_title( $quiz_attempt['quiz'] );


$quiz_link = ! empty( $quiz_attempt['post']->ID ) ? learndash_get_step_permalink( intval( $quiz_attempt['post']->ID ), $course_id ) : '#'; ?>

<div
	class="ld-table-list-item <?php echo esc_attr( $status ); ?>"
	role="row"
>
	<div class="ld-table-list-item-preview">
		<div class="ld-table-list-columns">
			<div
				class="ld-table-list-title"
				role="rowheader"
			>
				<a
					aria-label="<?php echo sprintf(
						// translators: %s: quiz label.
						esc_attr__( 'Go to the %s page.', 'learndash' ),
						learndash_get_custom_label_lower( 'quiz' )
					); ?>"
					href="<?php echo esc_url( $quiz_link ); ?>"
				>
					<?php echo wp_kses_post( learndash_status_icon( $status, 'sfwd-quiz' ) ); ?>
					<span>
						<?php echo wp_kses_post( $quiz_title ); ?>
					</span>
				</a>
			</div> <!--/.ld-table-list-title-->

			<?php

			if ( $certificate_link && ! empty( $certificate_link ) ) {
				$certificate_link = '<a class="ld-certificate-link" href="' . $certificate_link . '" target="_new" aria-label="' . __( 'View certificate.', 'learndash' ) . '"><span class="ld-icon ld-icon-certificate"></span></a>';
			}

			/**
			 * Filters LearnDash profile quiz column list.
			 *
			 * @since 3.0.0
			 *
			 * @param array $quiz_columns      An array of quiz columns list.
			 * @param array $quiz_attempt      This is the quiz attempt array read from the user meta.
			 * @param array $quiz_list_columns An array of quiz list columns data.
			 */
			$quiz_columns = apply_filters(
				'learndash_profile_quiz_columns',
				array(
					'certificate' => array(
						'id'      => $quiz_list_columns[0]['id'],
						'label'   => $quiz_list_columns[0]['label'],
						'content' => $certificate_link,
						'class'   => '',
					),
					'score'       => array(
						'id'      => $quiz_list_columns[1]['id'],
						'label'   => $quiz_list_columns[1]['label'],
						'content' => $score,
						'class'   => '',
					),
					'stats'       => array(
						'id'      => $quiz_list_columns[2]['id'],
						'label'   => $quiz_list_columns[2]['label'],
						'content' => $stats,
						'class'   => '',
					),
					'date'        => array(
						'id'      => $quiz_list_columns[3]['id'],
						'label'   => $quiz_list_columns[3]['label'],
						'content' => learndash_adjust_date_time_display( $quiz_attempt['time'] ),
						'class'   => '',
					),
				),
				$quiz_attempt,
				$quiz_list_columns
			);
			foreach ( $quiz_columns as $column ) :
				?>
				<div
					class="<?php echo esc_attr( 'ld-table-list-column ld-table-list-column-' . $column['id'] . ' ' . $column['class'] ); ?>"
					role="cell"
					tabindex="0"
				>
					<span class="ld-column-label"><?php echo wp_kses_post( $column['label'] ); ?>: </span>
					<?php echo wp_kses_post( $column['content'] ); ?>
				</div>
			<?php endforeach; ?>

		</div>
	</div> <!--/.ld-table-list-item-preview-->

	<?php
	$essays = (array) Arr::get( $quiz_attempt, 'graded', [] );

	// Filters out essays that are not in the `not_graded` or `graded` status.
	$essays = array_filter( $essays, static function ( $essay ) {
		if (
			! is_array( $essay )
			|| empty( $essay['post_id'] )
		) {
			return false;
		}

		return in_array(
			get_post_status( Cast::to_int( $essay['post_id'] ) ),
			[ 'not_graded', 'graded' ],
			true
		);
	} );

	if ( ! empty( $essays ) ) :
		?>
		<div
			class="ld-table-list-item-expanded"
			role="cell"
		>
			<div
				aria-label="<?php esc_attr_e( 'Essays', 'learndash' ); ?>"
				class="ld-table-list ld-essay-list"
				role="table"
			>
				<div
					class="ld-table-list-header"
					role="rowgroup"
				>
					<div
						class="ld-table-list-columns"
						role="row"
					>
						<div
							class="ld-table-list-title"
							role="columnheader"
						>
							<?php echo esc_html_e( 'Essays', 'learndash' ); ?>
						</div> <!--/.ld-table-list-title-->

						<?php

						/**
						 * Filters essay column heading details.
						 *
						 * @since 3.0.0
						 *
						 * @param array $column_headings An array of essay column heading details array. Heading details array can have keys for id and label.
						 */
						$columns = apply_filters(
							'learndash-essay-column-headings',
							array(
								array(
									'id'    => 'comments',
									'label' => __( 'Comments', 'learndash' ),
								),
								array(
									'id'    => 'status',
									'label' => __( 'Status', 'learndash' ),
								),
								array(
									'id'    => 'points',
									'label' => __( 'Points', 'learndash' ),
								),
							)
						);
						foreach ( $columns as $column ) :
							?>
							<div
								class="<?php echo esc_attr( 'ld-table-list-column ld-table-list-column-' . $column['id'] ); ?>"
								role="columnheader"
							>
								<?php echo esc_html( $column['label'] ); ?>
							</div>
							<?php
						endforeach;
						?>
					</div> <!--/.ld-table-list-columns-->
				</div> <!--/.ld-table-list-header-->

				<div
					class="ld-table-list-items"
					role="rowgroup"
				>
					<?php
					foreach ( $essays as $essay_array ) :
						if (
							! is_array( $essay_array )
							|| empty( $essay_array['post_id'] )
						) {
							continue;
						}

						$essay = get_post( Cast::to_int( $essay_array['post_id'] ) );

						learndash_get_template_part(
							'shortcodes/profile/essay-row.php',
							array(
								'essay'     => $essay,
								'user_id'   => $user_id,
								'course_id' => $course_id,
							),
							true
						);

					endforeach;
					?>
				</div> <!--/.ld-table-list-items-->
			</div> <!--/.ld-essay-list-->
		</div> <!--/.ld-table-list-item-expanded-->
	<?php endif; ?>
</div> <!--/.ld-table-list-item-->
