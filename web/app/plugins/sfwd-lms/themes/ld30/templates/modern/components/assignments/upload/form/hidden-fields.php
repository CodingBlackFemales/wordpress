<?php
/**
 * View: Assignments Form Hidden Fields.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Models\Lesson|Models\Topic $model The lesson or topic model.
 * @var WP_User                    $user  User.
 * @var Template                   $this  Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models;
use LearnDash\Core\Utilities\Cast;
use LearnDash\Core\Template\Template;

?>
<div class="ld-assignments__upload-form-hidden-fields">
	<input
		name="course_id"
		type="hidden"
		value="<?php echo esc_attr( Cast::to_string( $model->get_course_id() ) ); ?>"
	/>

	<input
		name="uploadfile"
		type="hidden"
		value="<?php echo esc_attr( wp_create_nonce( 'uploadfile_' . $user->ID . '_' . $model->get_id() ) ); ?>"
	/>

	<input
		name="post"
		type="hidden"
		value="<?php echo esc_attr( Cast::to_string( $model->get_id() ) ); ?>"
	/>
</div>
