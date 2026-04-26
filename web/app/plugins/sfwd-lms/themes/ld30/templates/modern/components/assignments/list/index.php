<?php
/**
 * View: Assignments List.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Assignment[] $assignments The uploaded assignments.
 * @var Template     $this        Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Assignment;
use LearnDash\Core\Template\Template;

if ( empty( $assignments ) ) {
	return;
}

?>
<div class="ld-assignments__list">
	<?php foreach ( $assignments as $assignment ) : ?>
		<?php
			$this->template(
				'modern/components/assignments/list/assignment',
				[
					'assignment' => $assignment,
				]
			);
		?>
	<?php endforeach; ?>
</div>
