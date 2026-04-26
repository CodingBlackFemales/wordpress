<?php
/**
 * View: Course Content.
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
<main class="ld-layout__content">
	<?php $this->template( 'modern/components/tabs' ); ?>

	<?php $this->template( 'modern/course/accordion' ); ?>
</main>
