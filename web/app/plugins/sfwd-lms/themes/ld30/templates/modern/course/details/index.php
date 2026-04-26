<?php
/**
 * View: Course Details.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var Course   $course Course model.
 * @var Template $this   Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Course;
use LearnDash\Core\Template\Template;

if (
	! $course->has_requirements()
	&& ! $course->has_awards()
	&& ! $course->has_steps()
) {
	return;
}
?>
<div class="ld-details">
	<?php $this->template( 'modern/course/details/requirements' ); ?>

	<?php $this->template( 'modern/course/details/awards' ); ?>

	<?php $this->template( 'modern/course/details/includes' ); ?>
</div>
