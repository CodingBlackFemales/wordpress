<?php
/**
 * View: Lesson Accordion - Header.
 *
 * @since 4.24.0
 * @version 4.24.0
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
	<?php $this->template( 'modern/lesson/accordion/header/heading' ); ?>

	<?php $this->template( 'modern/lesson/accordion/header/expand-all' ); ?>
</div>
