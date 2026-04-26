<?php
/**
 * View: Assignment Actions.
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
<div class="ld-assignments__list-item-actions">
	<?php $this->template( 'modern/components/assignments/list/assignment/actions/comments' ); ?>

	<?php $this->template( 'modern/components/assignments/list/assignment/actions/download' ); ?>

	<?php $this->template( 'modern/components/assignments/list/assignment/actions/delete' ); ?>
</div>
