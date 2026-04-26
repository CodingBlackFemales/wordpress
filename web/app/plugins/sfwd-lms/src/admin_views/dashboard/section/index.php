<?php
/**
 * View: Dashboard Section.
 *
 * Tailwind.config contains some safe list classes for this view.
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
	! $section->has_sections()
	&& ! $section->has_widgets()
) {
	return;
}
?>
<div class="ld-dashboard-section ld-col-span-<?php echo esc_attr( (string) $section->get_size( Section::$screen_small ) ); ?> md:ld-col-span-<?php echo esc_attr( (string) $section->get_size( Section::$screen_medium ) ); ?> lg:ld-col-span-<?php echo esc_attr( (string) $section->get_size() ); ?>">
	<?php $this->template( 'dashboard/section/header' ); ?>

	<?php if ( $section->has_sections() ) : ?>
		<div class="ld-dashboard-section__sections">
			<?php foreach ( $section->get_sections() as $child_section ) : ?>
				<?php $this->template( 'dashboard/section', [ 'section' => $child_section ] ); ?>
			<?php endforeach; ?>
		</div>
	<?php elseif ( $section->has_widgets() ) : ?>
		<?php $this->template( 'dashboard/section/widgets' ); ?>
	<?php endif; ?>
</div>
