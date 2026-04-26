<?php
/**
 * View: Course Accordion - Section.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var string   $title Section Title.
 * @var Template $this  Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

if ( empty( $title ) ) {
	return;
}
?>
<span
	aria-level="3"
	class="ld-accordion__subheading"
	role="heading"
>
	<?php echo wp_kses_post( $title ); ?>
</span>
