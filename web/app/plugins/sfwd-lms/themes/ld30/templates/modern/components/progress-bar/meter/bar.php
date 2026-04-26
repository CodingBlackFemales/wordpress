<?php
/**
 * View: Progress Bar Meter.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Progress_Bar $progress_bar Progress Bar.
 * @var Template     $this         Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Progression\Bar as Progress_Bar;
use LearnDash\Core\Template\Template;
use LearnDash\Core\Utilities\Cast;

?>

<div
	class="ld-progress-bar__meter-background"
	role="progressbar"
	aria-valuenow="<?php echo esc_attr( Cast::to_string( $progress_bar->get_completed_step_count() ) ); ?>"
	aria-valuemin="0"
	aria-valuemax="<?php echo esc_attr( Cast::to_string( $progress_bar->get_total_step_count() ) ); ?>"
>
	<div
		class="ld-progress-bar__meter-foreground"
		style="--bar-width: <?php echo esc_attr( Cast::to_string( $progress_bar->get_completion_percentage() ) ); ?>%;"
	>
	</div>
</div>
