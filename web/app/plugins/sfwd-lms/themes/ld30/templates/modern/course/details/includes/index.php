<?php
/**
 * View: Course Details - Includes.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var Course   $course Course model.
 * @var Template $this   Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
use LearnDash\Core\Models\Course;

if ( ! $course->has_steps() ) {
	return;
}
?>
<div class="ld-details__section ld-details__section--includes">
	<span
		aria-level="3"
		class="ld-details__heading"
		role="heading"
	>
		<?php esc_html_e( 'Includes', 'learndash' ); ?>
	</span>

	<?php $this->template( 'modern/course/details/includes/lessons' ); ?>

	<?php $this->template( 'modern/course/details/includes/topics' ); ?>

	<?php $this->template( 'modern/course/details/includes/quizzes' ); ?>
</div>
