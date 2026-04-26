<?php
/**
 * View: Assignments Upload Heading.
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
<span
	role="heading"
	aria-level="3"
	class="ld-assignments__upload-heading"
>
	<?php
	echo esc_html(
		sprintf(
			/* translators: placeholder: Assignment. */
			__( 'Upload %s', 'learndash' ),
			learndash_get_custom_label( 'assignment' )
		)
	);
	?>
</span>
