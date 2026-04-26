<?php
/**
 * Registration - Return to course/group.
 *
 * @since 4.16.0
 * @version 4.16.0
 *
 * @var Product $product Product data.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Product;

?>
<div class="ld-registration-order__return-wrapper">
	<div class="ld-registration-order__return">
		<a href="<?php echo esc_url( $product->get_permalink() ); ?>">
			<span class="dashicons dashicons-arrow-left-alt2"></span><?php echo esc_html( $product->get_title() ); ?>
		</a>
	</div>
</div>
