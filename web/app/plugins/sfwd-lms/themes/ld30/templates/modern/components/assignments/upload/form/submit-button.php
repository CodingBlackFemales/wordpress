<?php
/**
 * View: Assignments Form Submit Button.
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
<button
	class="ld-assignments__upload-form-submit"
	disabled="true"
	type="submit"
>
	<?php echo esc_html_e( 'Upload', 'learndash' ); ?>

	<?php
	$this->template(
		'components/icons/upload',
		[
			'is_aria_hidden' => true,
			'classes'        => [ 'ld-assignments__upload-form-submit-icon' ],
		]
	);
	?>
</button>
