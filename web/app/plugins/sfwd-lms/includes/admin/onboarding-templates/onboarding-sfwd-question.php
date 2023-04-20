<?php
/**
 * Onboarding Questions Template.
 *
 * Displayed when no entities were added to help the user.
 *
 * @since 3.0.0
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="ld-onboarding-screen">
	<div class="ld-onboarding-main">
		<span class="dashicons dashicons-welcome-add-page"></span>
		<h2>
		<?php
		printf(
			// translators: placeholder: Questions.
			esc_html_x( 'You don\'t have any %s yet', 'placeholder: Questions', 'learndash' ),
			\LearnDash_Custom_Label::get_label( 'questions' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
		);
		?>
		</h2>
		<p>
		<?php
				printf(
					// translators: placeholder: %1$s: Questions, %2$s: Quiz, %3$s: Questions, %4$s: Quiz.
					esc_html_x( 'You can add %1$s when you create a %2$s, or you can choose to add %3$s at any time and add them to a %4$s later.', 'placeholder: %1$s: Questions, %2$s: Quiz, %3$s: Questions, %4$s: Quiz', 'learndash' ),
					\LearnDash_Custom_Label::get_label( 'questions' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
					\LearnDash_Custom_Label::get_label( 'quiz' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
					\LearnDash_Custom_Label::get_label( 'questions' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
					\LearnDash_Custom_Label::get_label( 'quiz' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
				);
				?>
		</p>
		<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=sfwd-question' ) ); ?>" class="button button-secondary">
			<span class="dashicons dashicons-plus-alt"></span>
			<?php
			printf(
				// translators: placeholder: Question.
				esc_html_x( 'Add your first %s', 'placeholder: Question', 'learndash' ),
				\LearnDash_Custom_Label::get_label( 'question' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
			);
			?>
		</a>
	</div> <!-- .ld-onboarding-main -->

	<div class="ld-onboarding-more-help">
		<div class="ld-onboarding-row">
			<div class="ld-onboarding-col">
				<h3><?php esc_html_e( 'Related help and documentation', 'learndash' ); ?></h3>
				<ul>
					<li><a href="https://www.learndash.com/support/docs/core/courses/course-builder/" target="_blank" rel="noopener noreferrer">
					<?php
							echo sprintf(
								// translators: placeholder: Course.
								esc_html_x( '%s Builder [Article] (only available in English)', 'placeholder: Course', 'learndash' ),
								\LearnDash_Custom_Label::get_label( 'course' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
							);
							?>
						</a></li>
					<li><a href="https://www.learndash.com/support/docs/core/quizzes/questions/#quiz-question-types" target="_blank" rel="noopener noreferrer">
					<?php
							echo sprintf(
								// translators: placeholder: Quiz, Question.
								esc_html_x( '%1$s %2$s Types [Article] (only available in English)', 'placeholder: Quiz, Question', 'learndash' ),
								\LearnDash_Custom_Label::get_label( 'quiz' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
								\LearnDash_Custom_Label::get_label( 'question' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
							);
							?>
						</a></li>
					<li><a href="https://www.learndash.com/support/docs/core/quizzes/questions/" target="_blank" rel="noopener noreferrer">
					<?php
							echo sprintf(
								// translators: placeholder: Questions.
								esc_html_x( '%s  Documentation (only available in English)', 'placeholder: Questions', 'learndash' ),
								\LearnDash_Custom_Label::get_label( 'questions' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
							);
							?>
						</a></li>
				</ul>
			</div>
		</div>
	</div> <!-- .ld-onboarding-more-help -->

</section> <!-- .ld-onboarding-screen -->
