<?php
/**
 * View: Assignment.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Template $this Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Assignment;
use LearnDash\Core\Template\Template;

?>

<div class="ld-assignments__list-item">
	<?php $this->template( 'modern/components/assignments/list/assignment/title' ); ?>

	<?php $this->template( 'modern/components/assignments/list/assignment/actions' ); ?>
</div>
