<?php
/**
 * View: Progress Bar Meter Percentage.
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

<div class="ld-progress-bar__meter-percentage">
	<?php printf( '%d%%', esc_html( Cast::to_string( $progress_bar->get_completion_percentage() ) ) ); ?>
</div>
