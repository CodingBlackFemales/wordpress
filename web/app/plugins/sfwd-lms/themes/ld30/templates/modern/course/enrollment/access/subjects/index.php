<?php
/**
 * View: Course Enrollment Access Subjects.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var string[] $subjects Access subjects.
 * @var Template $this     Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

// We don't need to render anything if there are no subjects.
if ( empty( $subjects ) ) {
	return;
}

?>
<div class="ld-enrollment__subjects">
	<?php foreach ( $subjects as $subject ) : ?>
		<?php $this->template( 'modern/course/enrollment/access/subjects/' . $subject ); ?>
	<?php endforeach; ?>
</div>
