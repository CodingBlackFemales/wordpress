<?php
/**
 * View: Group Enrollment Join Section.
 *
 * @since 4.22.0
 * @version 4.22.0
 *
 * @var Product  $product Product model.
 * @var WP_User  $user    WP_User object.
 * @var Template $this    Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Product;
use LearnDash\Core\Template\Template;

// If a product has ended, we don't show the join button.
if ( $product->has_ended() ) {
	return;
}

// If a product is full, we don't show the join button.
if ( $product->get_seats_available() === 0 ) {
	return;
}

// If the user is already enrolled, we don't show the join button.
if ( $product->is_pre_ordered( $user ) ) {
	return;
}

$payment_buttons = learndash_payment_buttons( $product->get_post() );

// If there are no payment buttons and the user is logged in, we don't need to show the join container.
if (
	empty( $payment_buttons )
	&& $user->exists()
) {
	return;
}

?>
<div class="ld-enrollment__join">
	<?php
	$this->template(
		'modern/group/enrollment/join/button',
		[
			'payment_buttons' => $payment_buttons,
		]
	);
	?>

	<?php
	$this->template(
		'modern/group/enrollment/join/login-link',
		[
			'payment_buttons' => $payment_buttons,
		]
	);
	?>
</div>
