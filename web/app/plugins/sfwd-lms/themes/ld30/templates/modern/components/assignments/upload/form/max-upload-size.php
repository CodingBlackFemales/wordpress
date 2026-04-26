<?php
/**
 * View: Assignments Form Max File Size description.
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
use LearnDash\Core\Utilities\Cast;

?>
<span class="ld-assignments__upload-form-file-description ld-assignments__upload-form-file-description--max-file-size">
	<?php
	$this->template(
		'components/icons/info',
		[
			'classes'        => [
				'ld-assignments__upload-form-file-description-icon',
				'ld-assignments__upload-form-file-description-icon--max-file-size',
			],
			'is_aria_hidden' => true,
		]
	);
	?>

	<?php
	echo esc_html(
		sprintf(
			/* translators: placeholder: Maximum file size. */
			__( 'Maximum size: %s', 'learndash' ),
			Cast::to_string( size_format( $model->get_assignment_file_size_limit_in_bytes() ) )
		)
	);
	?>
</span>
