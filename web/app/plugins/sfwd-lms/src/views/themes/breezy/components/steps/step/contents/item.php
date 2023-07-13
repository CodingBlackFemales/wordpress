<?php
/**
 * View: Step Contents Item.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var string   $label Label.
 * @var string   $icon  Icon.
 * @var Template $this  Template instance.
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
<div class="ld-steps__contents-item">
	<?php $this->template( 'components/icons/' . $icon, [ 'classes' => [ 'ld-icon--sm' ] ] ); ?>

	<span class="ld-steps__contents-item__label">
		<?php echo esc_html( $label ); ?>
	</span>
</div>
