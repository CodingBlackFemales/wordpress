<?php
/**
 * LearnDash LD30 Displays a user's profile quizzes listing.
 *
 * @since 3.0.0
 * @version 4.21.3
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div
	aria-label="<?php esc_attr_e( 'Quizzes', 'learndash' ); ?>"
	class="ld-table-list ld-quiz-list"
	role="table"
>
	<div
		class="ld-table-list-header ld-primary-background"
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
				<?php echo LearnDash_Custom_Label::get_label( 'quizzes' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output ?>
			</div> <!--/.ld-table-list-title-->

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
				<div
					class="<?php echo esc_attr( 'ld-table-list-column ld-column-' . $column['id'] ); ?>"
					role="columnheader"
				>
					<?php echo esc_html( $column['label'] ); ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div> <!--/.ld-table-list-header-->

	<div
		class="ld-table-list-items"
		role="rowgroup"
	>
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

	<div
		class="ld-table-list-footer"
		role="rowgroup"
	>
	</div>

</div> <!--/.ld-quiz-list-->
