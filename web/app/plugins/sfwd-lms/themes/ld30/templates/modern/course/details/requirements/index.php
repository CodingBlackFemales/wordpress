<?php
/**
 * View: Course Details - Requirements.
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

// If there are no requirements, return.
if ( ! $course->has_requirements() ) {
	return;
}

?>
<div class="ld-details__section ld-details__section--requirements">
	<span
		aria-level="3"
		class="ld-details__heading"
		role="heading"
	>
		<?php esc_html_e( 'Requirements', 'learndash' ); ?>
	</span>

	<?php $this->template( 'modern/course/details/requirements/points' ); ?>

	<?php $this->template( 'modern/course/details/requirements/prerequisites' ); ?>
</div>
