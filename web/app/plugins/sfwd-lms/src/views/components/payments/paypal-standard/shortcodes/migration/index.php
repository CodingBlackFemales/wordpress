<?php
/**
 * View: PayPal Standard - Migration.
 *
 * @since 4.25.3
 * @version 4.25.3
 *
 * @var Template             $this      Current instance of template engine rendering this template.
 * @var array<string,string> $countries The countries.
 * @var Product[]            $products  The products.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
use LearnDash\Core\Models\Product;

if ( empty( $products ) ) {
	return;
}

?>
<div class="ld-paypal-standard__migration">
	<?php $this->template( 'components/payments/paypal-standard/shortcodes/migration/instructions.php' ); ?>

	<?php $this->template( 'components/payments/paypal-standard/shortcodes/migration/products-list.php' ); ?>

	<?php $this->template( 'components/payments/paypal-standard/shortcodes/migration/payment-form.php' ); ?>

	<?php $this->template( 'components/payments/paypal-standard/shortcodes/migration/actions.php' ); ?>
</div>
