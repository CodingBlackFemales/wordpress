<?php
/**
 * Registration - Checkout form section.
 *
 * @since 4.16.0
 * @version 4.16.0
 *
 * @var array<string, string> $buttons Checkout buttons.
 * @var Template              $this    The Template object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

?>
<div class="ld-form__field-group ld-registration-order__checkout">
	<?php $this->template( 'modules/registration/order/checkout/checkout-select' ); ?>
	<?php $this->template( 'modules/registration/order/checkout/checkout-buttons' ); ?>
</div>
