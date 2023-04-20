<?php
/**
 * LearnDash LD30 Displays a lesson not available message.
 *
 * Available Variables:
 *
 * $user_id   :   The current user ID
 * $course_id :   The current course ID
 *
 * $lesson    :   The current lesson
 *
 * $topics    :   An array of the associated topics
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<span class="ld-status ld-status-waiting ld-tertiary-background">
	<?php
	printf(
		// translators: placeholder: Date when lesson will be available.
		esc_html_x( 'Available on %s', 'placeholder: Date when lesson will be available', 'learndash' ),
		esc_html( $lesson_access_from_date )
	);
	?>
</span>
