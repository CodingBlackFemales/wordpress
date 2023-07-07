<?php
/**
 * View: Steps.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var string   $content Content HTML.
 * @var Template $this    Current Instance of template engine rendering this template.
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

if ( empty( $content ) ) {
	return;
}
?>
<section aria-labelledby="ld-steps-heading" class="ld-steps">
	<?php $this->template( 'components/steps/heading' ); ?>

	<?php // TODO: We need a way to show sections for courses with sections. ?>

	<div class="ld-steps__sections">
		<?php $this->template( 'components/steps/list/start' ); ?>
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the template. ?>
		<?php $this->template( 'components/steps/list/end' ); ?>
	</div>

	<?php $this->template( 'components/steps/pagination' ); ?>
</section>
