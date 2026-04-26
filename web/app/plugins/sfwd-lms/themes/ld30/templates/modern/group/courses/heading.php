<?php
/**
 * View: Group Courses Heading.
 *
 * @since 4.22.0
 * @version 4.22.0
 *
 * @package LearnDash\Core
 */

?>
<h2 class="ld-group__courses-heading">
	<?php
	echo esc_html(
		sprintf(
			// translators: placeholder: Courses.
			_x( 'Included %s', 'placeholder: Courses', 'learndash' ),
			LearnDash_Custom_Label::get_label( 'courses' )
		)
	);
	?>
</h2>
