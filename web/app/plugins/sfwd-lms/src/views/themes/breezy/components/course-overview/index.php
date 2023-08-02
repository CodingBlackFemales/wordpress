<?php
/**
 * View: Course Overview. Used in a sidebar on course step pages.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * TODO: I'm not sure about the name.
 *
 * @var Course|null $course Course model.
 * @var WP_User     $user   Current User.
 * @var Template    $this   Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

use LearnDash\Core\Models\Course;
use LearnDash\Core\Template\Template;

if ( ! $course ) {
	return;
}
?>
<section class="ld-course-overview" aria-labelledby="ld-course-overview-heading">
	<?php $this->template( 'components/course-overview/heading' ); ?>

	<?php
	$this->template(
		'components/progress-bar',
		[
			'value' => $course->get_progress_percentage( $user ),
			'label' => sprintf(
				// translators: placeholders: completed steps number, total steps number, steps label.
				esc_html_x( '%1$d/%2$d %3$s', 'placeholders: completed steps number, total steps number, steps label', 'learndash' ),
				esc_html( (string) $course->get_completed_steps_number( $user ) ), // TODO: Refactor later when we have a decision.
				esc_html( (string) $course->get_total_steps_number() ), // TODO: Refactor later when we have a decision.
				esc_html__( 'Steps', 'learndash' ) // TODO: Refactor later when we have a decision.
			),
		]
	);
	?>

	<?php // TODO: Add when ready. ?>
	<p>Here will be a nice overview of the course</p>
</section>
