<?php
/**
 * Displays the course progress widget.
 *
 * @since 2.1.0
 *
 * @package LearnDash\Templates\Legacy\Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<dd class="course_progress" title='
<?php
printf(
	// translators: placeholders: completed steps, total steps.
	esc_html_x( '%1$d out of %2$d steps completed', 'placeholders: completed steps, total steps', 'learndash' ),
	$completed,
	$total
);
?>
'>
	<div class="course_progress_blue" style='width: <?php echo esc_attr( $percentage ); ?>%;'>
</dd>
