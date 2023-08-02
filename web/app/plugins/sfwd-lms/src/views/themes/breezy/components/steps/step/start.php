<?php
/**
 * View: Step Start.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Step     $step        Step.
 * @var int      $depth       Depth.
 * @var bool     $is_enrolled Whether the user is enrolled.
 * @var Template $this        Current Instance of template engine rendering this template.
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
use LearnDash\Core\Template\Template;

$aria_label_button = sprintf(
	// translators: placeholder: Step title.
	_x( 'Toggle %s', 'placeholder: Step title', 'learndash' ),
	esc_html( $step->get_title() )
);

$aria_label_link = sprintf(
	// translators: placeholder: Step title.
	esc_html_x( 'Go to %s', 'placeholder: Step title', 'learndash' ),
	esc_html( $step->get_title() )
);

$classes  = 'ld-steps__info ';
$classes .= 0 === $depth ? 'ld-steps__info--parent' : 'ld-steps__info--child';
?>
<?php if ( 0 === $depth && $step->get_steps_number() > 0 ) : ?>
	<button
		class="<?php echo esc_attr( $classes ); ?>"
		type="button"
		aria-expanded="false"
		aria-controls="ld-steps-list-<?php echo esc_attr( (string) $step->get_id() ); ?>"
		aria-label="<?php echo esc_attr( $aria_label_button ); ?>"
	>
<?php elseif ( $is_enrolled ) : ?>
	<a
		class="<?php echo esc_attr( $classes ); ?>"
		href="<?php echo esc_attr( esc_url( $step->get_url() ) ); ?>"
		aria-label="<?php echo esc_attr( $aria_label_link ); ?>"
	>
<?php else : ?>
	<div class="<?php echo esc_attr( $classes ); ?> ld-steps__info--inactive">
<?php endif; ?>
