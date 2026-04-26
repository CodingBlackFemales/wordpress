<?php
/**
 * View: Progress Bar Last Activity.
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

$last_activity = $progress_bar->get_last_activity();

if ( ! $last_activity ) {
	return;
}

?>

<div class="ld-progress-bar__last-activity">
	<?php
	echo esc_html(
		sprintf(
			/* translators: %s: Last activity date. */
			__( 'Last activity: %s', 'learndash' ),
			learndash_adjust_date_time_display( $last_activity->completed_timestamp, 'M j, Y' )
		)
	);
	?>
</div>
