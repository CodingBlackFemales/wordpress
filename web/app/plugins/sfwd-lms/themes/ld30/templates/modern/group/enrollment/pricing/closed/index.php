<?php
/**
 * View: Group Pricing Closed.
 *
 * @since 4.22.0
 * @version 4.22.0
 *
 * @var Product  $product Product model.
 * @var Template $this    Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
use LearnDash\Core\Models\Product;

if ( ! empty( $product->get_pricing_settings()['price'] ) ) {
	$this->template( 'modern/group/enrollment/pricing/closed/with-price' );
} elseif ( empty( $product->get_setting( 'custom_button_url' ) ) ) {
	$this->template( 'modern/group/enrollment/pricing/closed/restricted' );
}
