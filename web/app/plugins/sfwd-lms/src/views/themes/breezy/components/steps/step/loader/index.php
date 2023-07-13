<?php
/**
 * View: Step Loader.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Step     $step           Step.
 * @var int      $children_count Step's children number showing.
 * @var Template $this           Current Instance of template engine rendering this template.
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

if ( $children_count >= $step->get_steps_number() ) {
	return;
}
// TODO: Finish & split more.
?>
<li class="ld-steps__item ld-steps__item--loader">
	<span class="ld-steps__showing">
		<?php esc_html_e( 'Showing', 'learndash' ); ?>

		<span data-steps-showing="<?php echo esc_attr( (string) $children_count ); ?>">
			<?php echo esc_html( (string) $children_count ); ?>
		</span>

		<?php
		echo sprintf(
			// translators: placeholder: number of steps.
			esc_html_x( 'of %1$d', 'placeholder: number of steps', 'learndash' ),
			esc_html( (string) $step->get_steps_number() )
		);
		?>
	</span>

	<div class="ld-steps__loader-wrapper">
		<button	type="button" class="ld-steps__loader__button ld-steps__loader__button--more">
			<?php esc_html_e( 'Show more', 'learndash' ); ?>
			<?php $this->template( 'components/icons/caret-down', [ 'classes' => [ 'ld-icon--sm' ] ] ); ?>
		</button>

		<button type="button" class="ld-steps__loader__button ld-steps__loader__button--less ld-steps__loader__button--hidden">
			<?php esc_html_e( 'Show less', 'learndash' ); ?>
			<?php $this->template( 'components/icons/caret-up', [ 'classes' => [ 'ld-icon--sm' ] ] ); ?>
		</button>
	</div>
</li>
