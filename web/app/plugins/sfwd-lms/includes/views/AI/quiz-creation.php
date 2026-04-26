<?php
/**
 * Quiz creation AI form view file.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Modules\AI\Quiz_Creation
 *
 * @var string                              $api_key         ChatGPT API key.
 * @var array<string, string>               $question_types  LearnDash answer types in key label pair.
 * @var array<string, array<string, mixed>> $form_fields     Quiz Creation AI form fields.
 * @var string                              $ai_settings_url LearnDash AI settings page URL.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="ld-container ld-mx-auto">
	<div class="ld-flex ld-flex-wrap ld-flex-col ld-items-center">
		<?php
		SFWD_LMS::get_view(
			'AI/quiz-creation/components/header',
			[],
			true
		);
		?>
		<div class="ld-flex ld-mt-10 ld-w-1/2 ld-justify-center">
			<?php if ( empty( $api_key ) ) : ?>
				<?php
				$ld_error = wp_sprintf(
					// translators: HTML tags.
					esc_html__( '%1$sClick here%2$s to enter your OpenAI API key.', 'learndash' ),
					'<a class="ld-underline ld-text-[#2271b1]" href="' . esc_url( $ai_settings_url ) . '">',
					'</a>'
				);

				SFWD_LMS::get_view(
					'AI/quiz-creation/components/error',
					[
						'ld_error' => $ld_error,
					],
					true
				);
				?>
			<?php else : ?>
				<div class="ld-w-full">
					<?php
					SFWD_LMS::get_view(
						'AI/quiz-creation/components/form',
						[
							'form_fields' => $form_fields,
						],
						true
					);
					?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
