<?php
/**
 * View: Dashboard Section Widgets.
 *
 * @since 4.9.0
 * @version 4.9.0
 *
 * @var Section  $section Section.
 * @var Template $this    Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Dashboards\Sections\Section;
use LearnDash\Core\Template\Template;
?>
<div class="ld-dashboard-section__widgets">
	<?php foreach ( $section->get_widgets()->all() as $widget ) : ?>
		<?php $widget->render(); ?>
	<?php endforeach; ?>
</div>
