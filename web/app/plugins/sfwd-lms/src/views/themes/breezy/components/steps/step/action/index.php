<?php
/**
 * View: Step Action.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var int      $depth        Depth of the step.
 * @var bool     $has_children Whether the step has children.
 * @var bool     $is_enrolled  Whether the user is enrolled.
 * @var Template $this         Current Instance of template engine rendering this template.
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
?>
<div class="ld-steps__action">
	<?php if ( 0 === $depth && $has_children ) : ?>
		<?php $this->template( 'components/steps/step/action/open' ); ?>
		<?php $this->template( 'components/steps/step/action/closed' ); ?>
	<?php elseif ( $is_enrolled ) : ?>
		<?php $this->template( 'components/steps/step/action/caret' ); ?>
	<?php endif; ?>
</div>
