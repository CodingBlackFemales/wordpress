<?php
/**
 * View: Steps Section Heading.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Step $step Steps.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

use LearnDash\Core\Template\Steps\Step;

if ( ! $step->is_section() ) {
	return;
}

$section_title = $step->get_title();

if ( empty( $section_title ) ) {
	return;
}
?>
<h5 class="ld-steps__section-title">
	<?php echo esc_html( $section_title ); ?>
</h5>
