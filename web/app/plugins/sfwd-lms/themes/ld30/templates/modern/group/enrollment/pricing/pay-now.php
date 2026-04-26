<?php
/**
 * View: Group Pricing Pay Now.
 *
 * @since 4.22.0
 * @version 4.22.0
 *
 * @var Product $product Product model.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Product;

?>
<div class="ld-enrollment__pricing ld-enrollment__pricing--pay-now">
	<span class="ld-enrollment__pricing-label">
		<?php echo esc_html__( 'Price', 'learndash' ); ?>
	</span>

	<span class="ld-enrollment__pricing-price">
		<?php echo esc_html( $product->get_display_price() ); ?>
	</span>
</div>
