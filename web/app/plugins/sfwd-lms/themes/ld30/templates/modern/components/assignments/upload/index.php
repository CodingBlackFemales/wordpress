<?php
/**
 * View: Assignments Upload.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Models\Lesson|Models\Topic $model       The lesson or topic model.
 * @var WP_User                    $user        The user.
 * @var Models\Assignment[]        $assignments The uploaded assignments.
 * @var Template                   $this        Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models;
use LearnDash\Core\Template\Template;

if ( $model->get_submittable_assignments_number( $user ) <= 0 ) {
	return;
}

?>
<div class="ld-assignments__upload">
	<?php $this->template( 'modern/components/assignments/upload/heading' ); ?>

	<?php $this->template( 'modern/components/assignments/upload/form' ); ?>
</div>
