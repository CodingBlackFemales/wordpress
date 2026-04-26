<?php
/**
 * View: Profile Saved Cards - Single Card - Cardholder Name.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var Card $card The card model.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Commerce\Card;

?>
<div class="ld-profile__saved-card-cell">
	<div class="ld-profile__saved-card-cell-label">
		<?php esc_html_e( 'Name', 'learndash' ); ?>
	</div>

	<div class="ld-profile__saved-card-cell-value">
		<?php echo esc_html( $card->get_holder_name() ); ?>
	</div>
</div>
