<?php
/**
 * View: Pricing.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Product  $product Product model.
 * @var WP_User  $user    User.
 * @var Template $this    Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

use LearnDash\Core\Models\Product;
use LearnDash\Core\Template\Template;

if ( $product->is_price_type_open() ) {
	return;
}
?>
<section class="ld-pricing" aria-labelledby="pricing-heading">
	<h2 id="pricing-heading" class="ld-pricing__heading">
		<?php echo esc_html__( 'Price', 'learndash' ); ?>
	</h2>

	<?php
	if ( $product->is_price_type_paynow() ) {
		$this->template( 'components/pricing/buy' );
	} elseif ( $product->is_price_type_subscribe() && $product->has_trial() ) {
		$this->template( 'components/pricing/subscribe-with-trial' );
	} elseif ( $product->is_price_type_subscribe() && ! $product->has_trial() ) {
		$this->template( 'components/pricing/subscribe-without-trial' );
	} elseif ( $product->is_price_type_closed() ) {
		$this->template( 'components/pricing/closed' );
	} elseif ( $product->is_price_type_free() ) {
		$this->template( 'components/pricing/free' );
	}
	?>
</section>
