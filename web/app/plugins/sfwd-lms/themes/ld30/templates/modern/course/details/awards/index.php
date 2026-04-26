<?php
/**
 * View: Course Details - Awards.
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

if ( ! $course->has_awards() ) {
	return;
}
?>
<div class="ld-details__section ld-details__section--awards">
	<span
		aria-level="3"
		class="ld-details__heading"
		role="heading"
	>
		<?php esc_html_e( 'Completion Awards', 'learndash' ); ?>
	</span>

	<?php $this->template( 'modern/course/details/awards/points' ); ?>

	<?php $this->template( 'modern/course/details/awards/certificate' ); ?>
</div>
