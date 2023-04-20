<?php
/**
 * LearnDash LD30 Displays a user's profile quizzes listing.
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ld-table-list ld-quiz-list">
	<div class="ld-table-list-header ld-primary-background">
		<div class="ld-table-list-title">
			<?php echo LearnDash_Custom_Label::get_label( 'quizzes' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output ?>
		</div> <!--/.ld-table-list-title-->
		<div class="ld-table-list-columns">
		<?php

		/**
		 * Filters user profile quiz list columns.
		 *
		 * @since 3.0.0
		 *
		 * @param array $quiz_columns An array of quiz list column details array. Column details array can have keys for id and label.
		 */
		$columns = apply_filters(
			'learndash-profile-quiz-list-columns',
			array(
				array(
					'id'    => 'certificate',
					'label' => __( 'Certificate', 'learndash' ),
				),
				array(
					'id'    => 'scores',
					'label' => __( 'Score', 'learndash' ),
				),
				array(
					'id'    => 'stats',
					'label' => __( 'Statistics', 'learndash' ),
				),
				array(
					'id'    => 'date',
					'label' => __( 'Date', 'learndash' ),
				),
			)
		);
		foreach ( $columns as $column ) :
			?>
			<div class="<?php echo esc_attr( 'ld-table-list-column ld-column-' . $column['id'] ); ?>">
				<?php echo esc_html( $column['label'] ); ?>
			</div>
		<?php endforeach; ?>
		</div>
	</div> <!--/.ld-table-list-header-->

	<div class="ld-table-list-items">
		<?php
		foreach ( $quiz_attempts[ $course_id ] as $k => $quiz_attempt ) :

			learndash_get_template_part(
				'shortcodes/profile/quiz-row.php',
				array(
					'user_id'           => $user_id,
					'quiz_attempt'      => $quiz_attempt,
					'course_id'         => $course_id,
					'quiz_list_columns' => $columns,
				),
				true
			);

		endforeach;
		?>
	</div> <!--/.ld-table-list-items-->

	<div class="ld-table-list-footer"></div>

</div> <!--/.ld-quiz-list-->
