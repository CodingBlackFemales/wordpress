<?php
/**
 * View: Dashboard Section Title.
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

$section_title = $section->get_title();

if ( empty( $section_title ) ) {
	return;
}
?>
<span class="ld-dashboard-section__title">
	<?php echo esc_html( $section_title ); ?>
</span>
