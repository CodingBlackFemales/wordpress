<?php
/**
 * View: Course Pricing.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var Product  $product Product model.
 * @var Template $this    Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Product;
use LearnDash\Core\Template\Template;

// If a product has ended, we don't show the pricing.
if ( $product->has_ended() ) {
	return;
}

// If a product is full, we don't show the pricing.
if ( $product->get_seats_available() === 0 ) {
	return;
}

if ( $product->is_price_type_paynow() ) {
	$this->template( 'modern/course/enrollment/pricing/pay-now' );
} elseif ( $product->is_price_type_subscribe() ) {
	$this->template( 'modern/course/enrollment/pricing/recurring' );
} elseif ( $product->is_price_type_closed() ) {
	$this->template( 'modern/course/enrollment/pricing/closed' );
} elseif ( $product->is_price_type_free() ) {
	$this->template( 'modern/course/enrollment/pricing/free' );
}
