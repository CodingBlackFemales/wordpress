<?php
/**
 * View: Progress Bar Meter Label.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Progress_Bar $progress_bar Progress Bar.
 * @var ?string      $label_steps  Label for steps.
 * @var Template     $this         Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Progression\Bar as Progress_Bar;
use LearnDash\Core\Template\Template;

if ( empty( $label_steps ) ) {
	$label_steps = __( 'Steps', 'learndash' );
}

?>

<div class="ld-progress-bar__meter-label">
	<?php
	echo esc_html(
		sprintf(
			// translators: placeholders: completed steps, total steps, label for steps.
			__( '%1$d/%2$d %3$s', 'learndash' ),
			$progress_bar->get_completed_step_count(),
			$progress_bar->get_total_step_count(),
			$label_steps
		)
	);
	?>

	<span class="screen-reader-text">
		<?php
		echo esc_html_x(
			'completed',
			'Progress bar completed steps label. Screen reader only. Example: 1/10 Steps completed',
			'learndash'
		);
		?>
	</span>
</div>
