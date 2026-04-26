<?php
/**
 * View: Course Header.
 *
 * @since 4.21.0
 * @version 4.24.0
 *
 * @var Template $this Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

?>
<div class="ld-layout__header">
	<?php $this->template( 'modern/components/alerts' ); ?>

	<?php $this->template( 'modern/components/progress-bar' ); ?>
</div>
