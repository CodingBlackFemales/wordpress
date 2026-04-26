<?php
/**
 * View: Profile Saved Cards - Single Card - Actions.
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
<div class="ld-profile__saved-card-actions">
	<button
		class="ld-profile__saved-card-action ld-profile__saved-card-action--remove-card"
		data-card-id="<?php echo esc_attr( $card->get_id() ); ?>"
		data-gateway-id="<?php echo esc_attr( $card->get_gateway_id() ); ?>"
		type="button"
	>
		<?php esc_html_e( 'Remove', 'learndash' ); ?>
	</button>
</div>
