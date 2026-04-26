<?php
/**
 * View: PayPal Standard - Migration Products List.
 *
 * @since 4.25.3
 * @version 4.25.3
 *
 * @var Template  $this     Current instance of template engine rendering this template.
 * @var Product[] $products The products.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
use LearnDash\Core\Models\Product;

if ( empty( $products ) ) {
	return;
}

?>
<ul class="ld-paypal-standard__migration-products">
	<?php foreach ( $products as $product ) : ?>
		<li>
			<a href="<?php echo esc_url( $product->get_permalink() ); ?>">
				<?php echo wp_kses_post( $product->get_title() ); ?>
			</a>
		</li>
	<?php endforeach; ?>
</ul>
