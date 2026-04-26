<?php
/**
 * View: PayPal Checkout - Card Logos.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var Template $this Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
?>
<div class="ld-paypal-checkout__card-logos">
	<?php $this->template( 'components/icons/visa.php' ); ?>
	<?php $this->template( 'components/icons/mastercard.php' ); ?>
	<?php $this->template( 'components/icons/unionpay.php' ); ?>
	<?php $this->template( 'components/icons/amex.php' ); ?>
	<?php $this->template( 'components/icons/jcb.php' ); ?>
	<?php $this->template( 'components/icons/discover.php' ); ?>
</div>
