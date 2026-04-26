<?php
/**
 * View: Course Accordion Header - Heading.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @package LearnDash\Core
 */

?>
<h2 class="ld-accordion__heading">
	<?php
	printf(
		// translators: placeholder: Course.
		esc_html_x( '%s Content', 'placeholder: Course', 'learndash' ),
		LearnDash_Custom_Label::get_label( 'course' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
	);
	?>
</h2>
