<?php
/**
 * View: Assignments Header Count Remaining.
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

?>
<span class="ld-assignments__header-count-remaining">
	<?php
	echo esc_html(
		sprintf(
			// translators: %1$d: Approved count, %2$d: Total count.
			_n(
				'%1$d upload remaining (of %2$d total)',
				'%1$d uploads remaining (of %2$d total)',
				$model->get_submittable_assignments_number(),
				'learndash'
			),
			$model->get_submittable_assignments_number(),
			$model->get_maximum_assignments_number()
		)
	);
	?>
</span>
