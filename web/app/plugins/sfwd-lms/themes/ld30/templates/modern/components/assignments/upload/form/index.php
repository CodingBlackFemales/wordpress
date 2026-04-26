<?php
/**
 * View: Assignments Form.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Template $this Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

?>
<form
	accept-charset="utf-8"
	action=""
	class="ld-assignments__upload-form"
	enctype="multipart/form-data"
	method="POST"
	name="uploadfile"
>
	<div class="ld-assignments__upload-form-content">
		<?php $this->template( 'modern/components/assignments/upload/form/file-input' ); ?>
	</div>

	<?php $this->template( 'modern/components/assignments/upload/form/submit-button' ); ?>

	<?php $this->template( 'modern/components/assignments/upload/form/hidden-fields' ); ?>
</form>
