<?php
/**
 * View: Pricing Buy.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Product $product Product model.
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
?>
<span class="ld-pricing__main-price">
	<span class="ld-pricing__amount">
		<?php echo esc_html( $product->get_display_price() ); ?>
	</span>
</span>
