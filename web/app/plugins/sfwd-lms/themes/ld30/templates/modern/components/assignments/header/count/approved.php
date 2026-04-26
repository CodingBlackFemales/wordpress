<?php
/**
 * View: Assignments Header Count Approved.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Models\Lesson|Models\Topic $model       The lesson or topic model.
 * @var Models\Assignment[]        $assignments The uploaded assignments.
 * @var Template                   $this        Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models;
use LearnDash\Core\Template\Template;

if ( empty( $assignments ) ) {
	return;
}

?>
<span class="ld-assignments__header-count-approved">
	<?php
	echo esc_html(
		sprintf(
			// translators: %1$d: Approved count, %2$d: Submitted count.
			__( '%1$d/%2$d approved', 'learndash' ),
			$model->get_approved_assignments_number(),
			count( $assignments )
		)
	);
	?>
</span>
