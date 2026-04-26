<?php
/**
 * View: Assignments Form File Input.
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
<div class="ld-assignments__upload-form-file-input-wrapper">
	<label
		class="ld-assignments__upload-form-file-label"
		for="ld-assignments__upload-form-file-input"
		role="button"
	>
		<div class="ld-assignments__upload-form-file-label-content">
			<span class="ld-assignments__upload-form-upload-choose-file">
				<?php echo esc_html_e( 'Choose file', 'learndash' ); ?>
			</span>

			<span class="ld-assignments__upload-form-drop-text">
				<?php echo esc_html_e( 'or drop your file here.', 'learndash' ); ?>
			</span>

			<span class="ld-assignments__upload-form-file-name">
				<?php echo esc_html_e( 'No file chosen', 'learndash' ); ?><span class="screen-reader-text">.</span>
			</span>
		</div>

		<?php $this->template( 'modern/components/assignments/upload/form/max-upload-size' ); ?>
	</label>

	<input
		accept="<?php echo esc_attr( implode( ',', $model->get_supported_assignment_file_mime_types() ) ); ?>"
		aria-hidden="true"
		class="ld-assignments__upload-form-file-input"
		id="ld-assignments__upload-form-file-input"
		name="uploadfiles[]"
		required="required"
		type="file"
	/>
</div>
