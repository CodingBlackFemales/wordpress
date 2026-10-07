<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.ValidVariableName,WordPress.NamingConventions.ValidFunctionName,WordPress.NamingConventions.ValidHookName
class WpProQuiz_View_QuestionOverall extends WpProQuiz_View_View {

	public function show() {
		global $learndash_question_types;

		if ( isset( $_GET['post_id'] ) ) {
			$post_id = absint( $_GET['post_id'] );
		} else {
			$post_id = 0;
		}
		?>
<style>
.wpProQuiz_questionCopy {
	padding: 20px;
	background-color: rgb(223, 238, 255);
	border: 1px dotted;
	margin-top: 10px;
	display: none;
}
</style>
<div id="wpProQuiz_nonce" data-nonce="<?php echo esc_attr( wp_create_nonce( 'wpProQuiz_nonce' ) ); ?>" style="display:none;"></div>
<div class="wrap wpProQuiz_questionOverall">
	<h1><?php echo LearnDash_Custom_Label::get_label( 'quiz' ); ?>: <?php echo wp_kses_post( $this->quiz->getName() ); ?></h1> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output ?>
	<div id="sortMsg" class="updated" style="display: none;"><p><strong>
		<?php
		printf(
			// translators: placeholder: Questions.
			esc_html_x( '%s sorted', 'placeholder: Questions', 'learndash' ),
			LearnDash_Custom_Label::get_label( 'questions' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
		);
		?>
		</strong></p></div>
	<br>
	<p>
		<?php if ( current_user_can( 'wpProQuiz_edit_quiz' ) ) { ?>
		<a class="button-secondary" href="admin.php?page=ldAdvQuiz&module=question&action=addEdit&quiz_id=<?php echo absint( $this->quiz->getId() ); ?>&post_id=<?php echo absint( $post_id ); ?>">
			<?php
			printf(
			// translators: placeholder: Question.
				esc_html_x( 'Add %s', 'placeholder: Question', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'questions' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
			);
			?>
		</a>
		<?php } ?>
	</p>
	<table class="wp-list-table widefat">
		<thead>
			<tr>
				<th scope="col" style="width: 50px;"></th>
				<th scope="col"><?php esc_html_e( 'Name', 'learndash' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Type', 'learndash' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Category', 'learndash' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Points', 'learndash' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			$index  = 1;
			$points = 0;

			if ( count( $this->question ) ) {

				foreach ( $this->question as $question ) {
					$points += $question->getPoints();

					?>
				<tr id="wpProQuiz_questionId_<?php echo absint( $question->getId() ); ?>">
					<th><?php echo esc_html( $index++ ); ?></th>
					<td>
						<strong>
						<?php
						if ( current_user_can( 'wpProQuiz_edit_quiz' ) ) {
							$edit_link = add_query_arg(
								array(
									'page'       => 'ldAdvQuiz',
									'module'     => 'question',
									'action'     => 'addEdit',
									'quiz_id'    => $this->quiz->getId(),
									'questionId' => $question->getId(),
									'post_id'    => $post_id,
								),
								admin_url( 'admin.php' )
							);
							?>
							<a href="<?php echo esc_url( $edit_link ); ?>"><?php } ?><?php echo wp_kses_post( $question->getTitle() ); ?>
							<?php
							if ( current_user_can( 'wpProQuiz_edit_quiz' ) ) {
								?>
								</a><?php } ?></strong>
							<div class="row-actions">
							<?php if ( current_user_can( 'wpProQuiz_edit_quiz' ) ) { ?>
								<span><a href="admin.php?page=ldAdvQuiz&module=question&action=addEdit&quiz_id=<?php echo absint( $this->quiz->getId() ); ?>&questionId=<?php echo absint( $question->getId() ); ?>&post_id=<?php echo absint( $post_id ); ?>"><?php esc_html_e( 'Edit', 'learndash' ); ?></a></span>
							<?php } if ( current_user_can( 'wpProQuiz_delete_quiz' ) ) { ?>
							<span>
								<a style="color: red;" class="wpProQuiz_delete" href="admin.php?page=ldAdvQuiz&module=question&action=delete&quiz_id=<?php echo absint( $this->quiz->getId() ); ?>&id=<?php echo absint( $question->getId() ); ?>&post_id=<?php echo absint( $post_id ); ?>&question-delete-nonce=
								<?php
								echo esc_attr( wp_create_nonce( 'question-delete-nonce-' . absint( $question->getId() ) ) );
								?>
								"><?php esc_html_e( 'Delete', 'learndash' ); ?></a> |
							</span>
							<?php } if ( current_user_can( 'wpProQuiz_edit_quiz' ) ) { ?>
							<span>
								<a class="wpProQuiz_move" href="#" style="cursor:move;"><?php esc_html_e( 'Move', 'learndash' ); ?></a>
							</span>
							<?php } ?>
						</div>
					</td>
					<td>
						<?php
							$question_type = $question->getAnswerType();
						if ( isset( $learndash_question_types[ $question_type ] ) ) {
							echo esc_html( $learndash_question_types[ $question_type ] );
						}
						?>
					</td>
					<td>
						<?php echo esc_html( $question->getCategoryName() ); ?>
					</td>
					<td><?php echo esc_html( $question->getPoints() ); ?></td>
				</tr>
					<?php
				}
			} else {
				?>
				<tr>
					<td colspan="5" style="text-align: center; font-weight: bold; padding: 10px;"><?php esc_html_e( 'No data available', 'learndash' ); ?></td>
				</tr>
				<?php
			}
			?>
		</tbody>
		<tfoot>
			<tr>
				<th></th>
				<th style="font-weight: bold;"><?php esc_html_e( 'Total', 'learndash' ); ?></th>
				<th></th>
				<th></th>
				<th style="font-weight: bold;"><?php echo esc_html( $points ); ?></th>
			</tr>
		</tfoot>
	</table>
	<p>
		<?php
		/**
		 * Fires before quiz questions buttons.
		 */
		do_action( 'learndash_questions_buttons_before' );
		?>
		<?php if ( current_user_can( 'wpProQuiz_edit_quiz' ) ) { ?>
		<a class="button-secondary" href="admin.php?page=ldAdvQuiz&module=question&action=addEdit&quiz_id=<?php echo absint( $this->quiz->getId() ); ?>&post_id=<?php echo absint( $post_id ); ?>">
			<?php
			printf(
				// translators: placeholder: question
				esc_html_x( 'Add %s', 'placeholder: question', 'learndash' ),
				learndash_get_custom_label( 'question' )
			);
			?>
		</a>
		<a class="button-secondary" href="#" id="wpProQuiz_saveSort"><?php esc_html_e( 'Save order', 'learndash' ); ?></a>
		<a class="button-secondary" href="#" id="wpProQuiz_questionCopy">
			<?php
			// translators: placeholder: questions, quiz.
			printf( esc_html_x( 'Copy %1$s from another %2$s', 'placeholder: questions, quiz', 'learndash' ), learndash_get_custom_label_lower( 'questions' ), learndash_get_custom_label_lower( 'quiz' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
			?>
		</a>
		<?php } ?>
		<?php
		/**
		 * Fires after quiz questions buttons.
		 */
		do_action( 'learndash_questions_buttons_after' );
		?>
	</p>
		<?php
		/**
		 * Fires before quiz questions toolbox.
		 */
		do_action( 'learndash_questions_toolbox_before' );
		?>
	<div class="wpProQuiz_questionCopy">
		<form action="admin.php?page=ldAdvQuiz&module=question&quiz_id=<?php echo absint( $this->quiz->getId() ); ?>&action=copy_question" method="POST">
			<h2 style="margin-top: 0;">
			<?php
			// translators: placeholder: questions, quiz.
			echo sprintf( esc_html_x( 'Copy %1$s from another %2$s', 'placeholder: questions, quiz', 'learndash' ), learndash_get_custom_label_lower( 'questions' ), learndash_get_custom_label_lower( 'quiz' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
			?>
			</h2>
			<p>
			<?php
			// translators: placeholders: questions, quiz, quiz.
			echo sprintf( esc_html_x( 'Here you can copy %1$s from another %2$s into this %3$s. (Multiple selection enabled)', 'placeholders: questions, quiz, quiz', 'learndash' ), esc_html( learndash_get_custom_label_lower( 'questions' ) ), esc_html( learndash_get_custom_label_lower( 'quiz' ) ), esc_html( learndash_get_custom_label_lower( 'quiz' ) ) );
			?>
			</p>

			<div style="padding: 20px; display: none;" id="loadDataImg">
				<img alt="load" src="<?php echo esc_url( admin_url( '/images/wpspin_light.gif' ) ); ?>" />
				<?php esc_html_e( 'Loading', 'learndash' ); ?>
			</div>

			<div style="padding: 10px;">
				<select name="copyIds[]" size="15" multiple="multiple" style="min-width: 200px; display: none;" id="questionCopySelect">
				</select>
			</div>

			<?php // translators: placeholder: questions ?>
			<input class="button-primary" name="questionCopy" value="<?php sprintf( esc_html_x( 'Copy %s', 'placeholder: questions', 'learndash' ), learndash_get_custom_label_lower( 'questions' ) ); ?>" type="submit">
		</form>
	</div>
</div>
		<?php
	}
}
