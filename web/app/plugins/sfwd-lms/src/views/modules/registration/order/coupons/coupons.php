<?php
/**
 * Registration - Coupon area.
 *
 * @since 4.16.0
 * @version 4.16.0
 *
 * @var bool     $is_user_logged_in Whether the user is logged in.
 * @var Product  $product           Product data.
 * @var Template $this              The Template object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Product;
use LearnDash\Core\Template\Template;

$can_paynow = $product->is_price_type_paynow() && $is_user_logged_in;

if ( ! $can_paynow ) {
	return;
}

$this->template( 'modules/registration/order/coupons/form' );
$this->template( 'modules/registration/order/coupons/coupon' );
