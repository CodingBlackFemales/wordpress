<?php
/**
 * View: Dashboard Section Hint.
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

$hint = $section->get_hint();

if ( empty( $hint ) ) {
	return;
}
?>
<div class="ld-dashboard-section__hint ld-group">
	<div class="ld-dashboard-section__hint-tooltip">
		<span class="ld-dashboard-section__hint-tooltip__arrow"></span>

		<span class="ld-dashboard-section__hint-tooltip__content">
			<?php echo wp_kses( $hint, Section::get_hint_supported_html_tags() ); ?>
		</span>
	</div>

	<span class="ld-dashboard-section__hint-tooltip__sign"></span>
</div>
