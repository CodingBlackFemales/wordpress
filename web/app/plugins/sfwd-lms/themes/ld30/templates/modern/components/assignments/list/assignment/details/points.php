<?php
/**
 * View: Assignment Points.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Models\Lesson|Models\Topic $model      The lesson or topic model.
 * @var Models\Assignment          $assignment The assignment.
 * @var Template                   $this       Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models;
use LearnDash\Core\Template\Template;

if (
	! $assignment->is_approved()
	|| ! $model->get_assignment_points_maximum()
) {
	return;
}

?>

<div class="ld-assignments__list-item-points">
	<?php
	echo esc_html(
		sprintf(
			/* translators: placeholder: Awarded points, Max points. */
			_x( 'Score %1$s/%2$s points', 'Assignment points', 'learndash' ),
			$assignment->get_points_awarded(),
			$model->get_assignment_points_maximum()
		)
	);
	?>
</div>
