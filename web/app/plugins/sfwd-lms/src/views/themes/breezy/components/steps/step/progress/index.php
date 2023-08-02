<?php
/**
 * View: Step Progress.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Step     $step        Step.
 * @var int      $depth       Step depth.
 * @var bool     $is_enrolled Whether the user is enrolled.
 * @var Template $this        Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

use LearnDash\Core\Template\Steps\Step;
use LearnDash\Core\Template\Template;

if ( ! $is_enrolled ) {
	return;
}

$progress_percentage = $step->get_progress();
?>
<div class="ld-steps__progress">
	<?php
	if ( 0 === $progress_percentage ) {
		$this->template(
			'components/steps/step/progress/status',
			[ 'status' => __( 'Not started', 'learndash' ) ]
		);
	} elseif ( 100 === $progress_percentage ) {
		$this->template(
			'components/steps/step/progress/status',
			[ 'status' => __( 'Completed', 'learndash' ) ]
		);
	} elseif ( 0 === $depth ) {
		$this->template(
			'components/steps/step/progress/status',
			[ 'status' => $progress_percentage . '%' ]
		);

		$this->template( 'components/progress-donut', [ 'value' => $progress_percentage ] );
	}
	?>
</div>
