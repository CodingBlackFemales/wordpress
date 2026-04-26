<?php
/**
 * View: Course Accordion - Header.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var Template $this Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

?>
<div
	class="ld-accordion__header"
	data-accordion-header="true"
>
	<?php $this->template( 'modern/course/accordion/header/heading' ); ?>

	<?php $this->template( 'modern/course/accordion/header/expand-all' ); ?>
</div>
