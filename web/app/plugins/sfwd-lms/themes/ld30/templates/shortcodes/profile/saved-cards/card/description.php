<?php
/**
 * View: Profile Saved Cards - Single Card - Description.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var Card     $card The card model.
 * @var Template $this Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Commerce\Card;
use LearnDash\Core\Template\Template;

?>
<div class="ld-profile__saved-card-cell">
	<div class="ld-profile__saved-card-cell-label ld-profile__saved-card-cell-label--description">
		<div class="ld-profile__saved-card-number">
			<?php echo esc_html( $card->get_masked_number() ); ?>
		</div>

		<div class="ld-profile__saved-card-icon">
			<?php
			if ( ! empty( $card->get_icon_name() ) ) {
				$this->template(
					sprintf(
						'components/icons/%s-small.php',
						$card->get_icon_name()
					),
					[
						'label' => sprintf(
							/* translators: %s: Card icon name. */
							__( 'Card icon: %s', 'learndash' ),
							esc_html( $card->get_icon_name() )
						),
					]
				);
			}
			?>
		</div>
	</div>
</div>
