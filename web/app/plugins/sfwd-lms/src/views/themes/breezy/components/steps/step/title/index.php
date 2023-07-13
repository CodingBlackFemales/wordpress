<?php
/**
 * View: Step Title.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @since 4.6.0
 *
 * @var Step     $step  Step.
 * @var int      $depth Depth of the step.
 * @var Template $this  Current Instance of template engine rendering this template.
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
?>
<div class="ld-steps__title-wrapper">
	<?php $this->template( 'components/steps/step/title/icon' ); ?>

	<?php if ( 0 === $depth ) : ?>
		<h4 id="ld-steps-heading-<?php echo esc_attr( (string) $step->get_id() ); ?>" class="ld-steps__title ld-steps__title--parent">
			<?php echo esc_html( $step->get_title() ); ?>
		</h4>
	<?php else : ?>
		<h5 id="ld-steps-heading-<?php echo esc_attr( (string) $step->get_id() ); ?>" class="ld-steps__title ld-steps__title--child">
			<?php echo esc_html( $step->get_title() ); ?>
		</h5>
	<?php endif; ?>
</div>
