<?php
/**
 * Onboarding Topics Template.
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
			// translators: placeholder: Topics.
			esc_html_x( 'You don\'t have any %s yet', 'placeholder: Topics', 'learndash' ),
			\LearnDash_Custom_Label::get_label( 'topics' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
		);
		?>
		</h2>
		<p>
		<?php
				printf(
					// translators: placeholder: %1$s: Lessons, %2$s: Course, %3$s: Topics, %4$s: Topics, %5$s: Course, %6$s: Lesson.
					esc_html_x( 'When you have %1$s in your %2$s, you can break them up into separate %3$s. You can add %4$s using the %5$s Builder, or you can create them individually and assign them to a %6$s later.', 'placeholder: %1$s: Lessons, %2$s: Course, %3$s: Topics, %4$s: Topics, %5$s: Course, %6$s: Lesson', 'learndash' ),
					\LearnDash_Custom_Label::get_label( 'lessons' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
					\LearnDash_Custom_Label::get_label( 'course' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
					\LearnDash_Custom_Label::get_label( 'topics' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
					\LearnDash_Custom_Label::get_label( 'topics' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
					\LearnDash_Custom_Label::get_label( 'course' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
					\LearnDash_Custom_Label::get_label( 'lesson' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
				);
				?>
		</p>
		<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=sfwd-topic' ) ); ?>" class="button button-secondary">
			<span class="dashicons dashicons-plus-alt"></span>
			<?php
			printf(
				// translators: placeholder: Topic.
				esc_html_x( 'Add your first %s', 'placeholder: Topic', 'learndash' ),
				\LearnDash_Custom_Label::get_label( 'topic' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
			);
			?>
		</a>
	</div> <!-- .ld-onboarding-main -->

	<div class="ld-onboarding-more-help">
		<div class="ld-onboarding-row">
			<div class="ld-onboarding-col">
				<h3>
				<?php
				printf(
					// translators: placeholder: Topics.
					esc_html_x( 'Creating %s', 'placeholder: Topics', 'learndash' ),
					\LearnDash_Custom_Label::get_label( 'topics' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
				);
				?>
				</h3>
				<div class="ld-bootcamp__embed">
					<iframe width="560" height="315" src="https://www.youtube.com/embed/PD1KKzdakHw" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
				</div>
			</div>
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
						<li><a href="https://www.learndash.com/support/docs/core/topics/" target="_blank" rel="noopener noreferrer">
						<?php
							echo sprintf(
								// translators: placeholder: Topics.
								esc_html_x( '%s Documentation (only available in English)', 'placeholder: Topics', 'learndash' ),
								\LearnDash_Custom_Label::get_label( 'topics' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
							);
							?>
						</a></li>
					</ul>
			</div>
		</div>
	</div> <!-- .ld-onboarding-more-help -->

</section> <!-- .ld-onboarding-screen -->
