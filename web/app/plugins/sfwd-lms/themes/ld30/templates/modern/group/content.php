<?php
/**
 * View: Group Content.
 *
 * @since 4.22.0
 * @version 4.22.0
 *
 * @var Template $this Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

?>
<main class="ld-layout__content">
	<?php $this->template( 'modern/components/tabs' ); ?>

	<?php $this->template( 'modern/group/courses' ); ?>
</main>
