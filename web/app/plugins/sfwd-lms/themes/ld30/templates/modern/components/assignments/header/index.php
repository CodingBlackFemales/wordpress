<?php
/**
 * View: Assignments Header.
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
<div class="ld-assignments__header">
	<?php $this->template( 'modern/components/assignments/header/title' ); ?>

	<?php $this->template( 'modern/components/assignments/header/count' ); ?>
</div>
