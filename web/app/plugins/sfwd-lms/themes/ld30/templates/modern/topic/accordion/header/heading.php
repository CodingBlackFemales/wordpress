<?php
/**
 * View: Topic Accordion Header - Heading.
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
			// translators: placeholder: Topic.
			_x( '%s Content', 'placeholder: Topic', 'learndash' ),
			LearnDash_Custom_Label::get_label( 'topic' )
		)
	);
	?>
</h2>
