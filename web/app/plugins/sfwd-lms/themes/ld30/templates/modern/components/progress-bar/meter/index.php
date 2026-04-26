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

if ( $progress_bar->is_complete() ) {
	return;
}

?>

<div class="ld-progress-bar__meter">
	<?php $this->template( 'modern/components/progress-bar/meter/percentage' ); ?>

	<?php $this->template( 'modern/components/progress-bar/meter/bar' ); ?>

	<?php $this->template( 'modern/components/progress-bar/meter/label' ); ?>
</div>
