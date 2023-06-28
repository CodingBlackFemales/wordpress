<?php
/**
 * View: Progress Bar.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var int      $value  Progress bar value.
 * @var string   $label  Progress bar label.
 * @var Template $this   Current Instance of template engine rendering this template.
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
<div
	class="ld-progress-bar"
	role="progressbar"
	aria-valuemin="0"
	aria-valuemax="100"
	aria-valuenow="<?php echo esc_attr( (string) $value ); ?>"
>
	<?php $this->template( 'components/progress-bar/heading' ); ?>

	<?php $this->template( 'components/progress-bar/bar' ); ?>

	<div class="ld-progress-bar__stats">
		<div class="ld-progress-bar__label">
			<?php
			echo sprintf(
				// translators: placeholder: Progress percentage.
				esc_html_x( '%1$s Completed', 'placeholder: Progress percentage', 'learndash' ),
				esc_html( $value . '%' )
			);
			?>
		</div>

		<?php if ( ! empty( $label ) ) : ?>
			<div class="ld-progress-bar__label ld-progress-bar__label--secondary">
				<?php echo esc_html( $label ); ?>
			</div>
		<?php endif; ?>
	</div>
</div>
