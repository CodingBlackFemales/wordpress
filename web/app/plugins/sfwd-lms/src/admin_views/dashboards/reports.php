<?php
/**
 * View: Reports Dashboard.
 *
 * @since 4.17.0
 * @version 4.17.0
 *
 * @var Section  $section    Root section.
 * @var bool     $is_enabled Whether the dashboard is enabled.
 * @var Template $this       Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Dashboards\Sections\Section;
use LearnDash\Core\Template\Template;

if ( ! $is_enabled ) {
	$this->template( 'dashboard/disabled' );
	return;
}

if ( ! $section->has_sections() ) {
	return;
}
?>
<div class="ld-dashboard ld-dashboard--reports">
	<?php foreach ( $section->get_sections() as $child_section ) : ?>
		<?php $this->template( 'dashboard/section', [ 'section' => $child_section ] ); ?>
	<?php endforeach; ?>

	<?php $this->template( 'dashboard/footer' ); ?>
</div>
