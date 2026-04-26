<?php
/**
 * View: Assignments.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var bool                       $has_access Whether the user has access to the course.
 * @var WP_User                    $user       The user.
 * @var Models\Lesson|Models\Topic $model      The lesson or topic model.
 * @var Template                   $this       Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models;
use LearnDash\Core\Template\Template;

if (
	! $has_access
	|| ! $user->exists()
	|| ! $model->requires_assignments()
) {
	return;
}

?>
<div class="ld-assignments">
	<?php $this->template( 'modern/components/assignments/header' ); ?>

	<?php $this->template( 'modern/components/assignments/list' ); ?>

	<?php $this->template( 'modern/components/assignments/upload' ); ?>
</div>
