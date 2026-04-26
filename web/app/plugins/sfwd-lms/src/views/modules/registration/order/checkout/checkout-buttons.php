<?php
/**
 * Registration - Checkout buttons section.
 *
 * @since 4.16.0
 * @version 4.25.0
 *
 * @var array<string, string> $buttons Checkout buttons.
 * @var Template              $this    The Template object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

$buttons_count = count( $buttons );

?>
<div class="ld-registration-order__checkout-buttons">
	<?php foreach ( $buttons as $button_key => $button ) : ?>
		<?php
		// If there is only one button, we may need to show the gateway details html.
		if ( $buttons_count < 2 ) :
			?>
			<?php
			$this->template(
				'modules/registration/order/checkout/checkout-button-gateway-details',
				[
					'button_html' => $button,
					'button_key'  => $button_key,
				]
			);
			?>
		<?php endif; ?>

		<?php
		$this->template(
			'modules/registration/order/checkout/checkout-button',
			[
				'button_html' => $button,
				'button_key'  => $button_key,
			]
		);
		?>
	<?php endforeach; ?>
</div>
