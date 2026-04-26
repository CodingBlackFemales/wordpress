<?php
/**
 * Quiz creation AI submit button component.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Modules\AI\Quiz_Creation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="ld-w-full">
	<button type="submit" class="button button-primary !ld-flex !ld-ml-auto">
		<?php
		echo wp_sprintf(
			// translators: Lessons label.
			esc_html__( 'Create %s', 'learndash' ),
			esc_html( learndash_get_custom_label( 'questions' ) )
		);
		?>
	</button>
</div>
