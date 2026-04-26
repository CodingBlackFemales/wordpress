<?php
/**
 * Quiz creation AI form header component.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Modules\AI\Quiz_Creation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="ld-flex ld-mt-6">
	<h1 class="ld-text-4xl">
		<?php
		echo wp_sprintf(
			// translators: Quiz label.
			esc_html__( 'Create %1$s from AI.', 'learndash' ),
			esc_html( learndash_get_custom_label( 'quiz' ) )
		);
		?>
	</h1>
</div>
<div class="ld-flex ld-mt-6">
	<p class="ld-text-xl">
		<?php
		echo wp_sprintf(
			// translators: 1$: course label, 2$: questions label.
			esc_html__( 'Utilize the latest AI technology to create your %1$s %2$s and answers.', 'learndash' ),
			esc_html( learndash_get_custom_label_lower( 'quiz' ) ),
			esc_html( learndash_get_custom_label_lower( 'questions' ) )
		);
		?>
	</p>
</div>
