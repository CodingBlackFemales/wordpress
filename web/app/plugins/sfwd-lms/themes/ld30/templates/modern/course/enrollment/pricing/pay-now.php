<?php
/**
 * View: Course Pricing Pay Now.
 *
 * @since 4.21.0
 * @version 4.21.3
 *
 * @var Product $product Product model.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Product;

?>
<section class="ld-enrollment__pricing ld-enrollment__pricing--pay-now">
	<h2 class="ld-enrollment__pricing-label" id="ld-enrollment__pricing-label">
		<?php echo esc_html__( 'Price', 'learndash' ); ?>
	</h2>

	<span class="ld-enrollment__pricing-price">
		<?php echo esc_html( $product->get_display_price() ); ?>
	</span>
</section>
