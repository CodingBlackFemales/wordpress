<?php
/**
 * View: Assignments Header Count.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Models\Lesson|Models\Topic $model The lesson or topic model.
 * @var Template                   $this  Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models;
use LearnDash\Core\Template\Template;

if ( $model->get_maximum_assignments_number() <= 0 ) {
	return;
}
?>
<div class="ld-assignments__header-count">
	<?php $this->template( 'modern/components/assignments/header/count/approved' ); ?>

	<?php $this->template( 'modern/components/assignments/header/count/separator' ); ?>

	<?php $this->template( 'modern/components/assignments/header/count/remaining' ); ?>
</div>
