<?php
/**
 * View: Step Link.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Step     $step        Step.
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

use LearnDash\Core\Template\Template;
use LearnDash\Core\Template\Steps\Step;

if ( ! $is_enrolled || 0 === $step->get_steps_number() ) {
	return;
}

$label = sprintf(
	// translators: placeholder: Step type label.
	esc_html_x( 'Open %s', 'placeholder: Step type label', 'learndash' ),
	esc_html( $step->get_type_label() )
);
?>
<li class="ld-steps__item ld-steps__item--link">
	<a class="ld-steps__link" href="<?php echo esc_attr( esc_url( $step->get_url() ) ); ?>">
		<?php echo esc_html( $label ); ?>
		<?php $this->template( 'components/icons/caret-right', [ 'classes' => [ 'ld-icon--sm' ] ] ); ?>
	</a>
</li>
