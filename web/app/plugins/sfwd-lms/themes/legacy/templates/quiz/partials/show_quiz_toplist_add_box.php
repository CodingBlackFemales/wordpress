<?php
/**
 * Displays Quiz Toplist Add Box
 *
 * Available Variables:
 *
 * @var object $quiz_view WpProQuiz_View_FrontQuiz instance.
 * @var object $quiz      WpProQuiz_Model_Quiz instance.
 * @var array  $shortcode_atts Array of shortcode attributes to create the Quiz.
 *
 * @since 3.2.0
 *
 * @package LearnDash\Templates\Legacy\Quiz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wpProQuiz_addToplist" style="display: none;">
	<?php
		echo wp_kses_post(
			SFWD_LMS::get_template(
				'learndash_quiz_messages',
				array(
					'quiz_post_id' => $quiz->getID(),
					'context'      => 'quiz_toplist_results_message',
					'message'      => '<span style="font-weight: bold;">' . sprintf(
						// translators: placeholder: quiz.
						esc_html_x( 'Would you like to submit your %s result to the leaderboard?', 'placeholder: quiz', 'learndash' ),
						learndash_get_custom_label_lower( 'quiz' )
					) . '</span>',
				)
			)
		);
		?>
	<div style="margin-top: 6px;">
		<div class="wpProQuiz_addToplistMessage"
				style="display: none;"><?php esc_html_e( 'Loading', 'learndash' ); ?></div>
		<div class="wpProQuiz_addBox">
			<div>
				<span>
					<label>
						<?php esc_html_e( 'Name', 'learndash' ); ?>: <input type="text" placeholder="<?php esc_html_e( 'Name', 'learndash' ); ?>" name="wpProQuiz_toplistName" maxlength="15" size="16" style="width: 150px;">
					</label>
					<label>
						<?php esc_html_e( 'E-Mail', 'learndash' ); ?>: <input type="email" placeholder="<?php esc_html_e( 'E-Mail', 'learndash' ); ?>" name="wpProQuiz_toplistEmail" size="20" style="width: 150px;">
					</label>
				</span>

				<div style="margin-top: 5px;">
					<label>
						<?php esc_html_e( 'Captcha', 'learndash' ); ?>: <input type="text" name="wpProQuiz_captcha" size="8" style="width: 50px;">
					</label>
					<input type="hidden" name="wpProQuiz_captchaPrefix" value="0">
					<img alt="captcha" src="" class="wpProQuiz_captchaImg" style="vertical-align: middle;">
				</div>
			</div>
			<input class="wpProQuiz_button2" type="submit" value="<?php esc_html_e( 'Send', 'learndash' ); ?>" name="wpProQuiz_toplistAdd">
		</div>
	</div>
</div>
