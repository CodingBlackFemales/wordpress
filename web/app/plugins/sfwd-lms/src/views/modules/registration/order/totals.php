<?php
/**
 * Registration - Order Totals.
 *
 * @since 4.16.0
 * @version 4.20.2.1
 *
 * @var Learndash_Coupon_DTO|null       $attached_coupon_dto Coupon data.
 * @var array<string, int|float|string> $course_pricing      Course or group pricing data.
 * @var string                          $price               Original price.
 * @var Product                         $product             Product data.
 * @var int                             $register_id         ID of the course or group.
 * @var string                          $trial_price         Trial price.
 * @var Template                        $this                The Template object.
 * @var float                           $total               Total price after discounts are adjusted.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Product;
use LearnDash\Core\Template\Template;

?>
<div class="ld-registration-order__total">
	<div class="ld-registration-order__total-title">
		<?php esc_html_e( 'Total', 'learndash' ); ?>
	</div>
	<div
		aria-label="<?php esc_html_e( 'Total Price', 'learndash' ); ?>"
		class="ld-registration-order__total-price"
		data-supports-coupon="<?php echo esc_attr( (string) $product->supports_coupon() ); ?>"
		data-total="<?php echo esc_attr( (string) $total ); ?>"
		id="total-row"
	>
		<?php if ( $product->has_trial() ) : ?>
			<?php echo esc_html( $trial_price ); ?>
		<?php else : ?>
			<?php echo esc_html( $price ); ?>
		<?php endif; ?>
	</div>
</div>
