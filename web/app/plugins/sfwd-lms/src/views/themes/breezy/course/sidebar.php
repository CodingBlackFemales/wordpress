<?php
/**
 * View: Course Sidebar.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Course   $course      Course model.
 * @var WP_User  $user        Current User.
 * @var bool     $is_enrolled An indicator if the user is enrolled in the course.
 * @var Template $this        Current Instance of template engine rendering this template.
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
?>
<aside class="ld-layout__sidebar">
	<div class="ld-layout__sidebar__content">
		<?php
		if ( $is_enrolled ) {
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
		} else {
			$this->template( 'components/pricing' );

			$this->template( 'components/enrollment-button' );
		}
		?>

		<?php $this->template( 'components/instructors' ); ?>
	</div>
</aside>
