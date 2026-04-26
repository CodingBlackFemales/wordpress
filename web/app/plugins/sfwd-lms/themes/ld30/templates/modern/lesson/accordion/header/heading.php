<?php
/**
 * View: Lesson Accordion Header - Heading.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @package LearnDash\Core
 */

?>
<h2 class="ld-accordion__heading">
	<?php
	echo esc_html(
		sprintf(
			// translators: placeholder: Lesson.
			_x( '%s Content', 'placeholder: Lesson', 'learndash' ),
			LearnDash_Custom_Label::get_label( 'lesson' )
		)
	);
	?>
</h2>
