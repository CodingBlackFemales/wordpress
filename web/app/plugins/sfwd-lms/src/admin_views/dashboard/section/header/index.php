<?php
/**
 * View: Dashboard Section Header.
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

if (
	empty( $section->get_title() )
	&& empty( $section->get_hint() )
) {
	return;
}
?>
<div class="ld-dashboard-section__header">
	<?php $this->template( 'dashboard/section/header/title' ); ?>

	<?php $this->template( 'dashboard/section/header/hint' ); ?>
</div>
