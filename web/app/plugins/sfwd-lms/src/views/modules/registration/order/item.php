<?php
/**
 * Registration - Order item.
 *
 * @version 4.16.0
 * @since 4.16.0
 *
 * @var Learndash_Coupon_DTO|null $attached_coupon_dto Coupon data.
 * @var string                    $price               Original price.
 * @var Product                   $product             Product data.
 * @var int                       $register_id         ID of the course or group.
 * @var Template                  $this                The Template object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Product;
use LearnDash\Core\Template\Template;

$interval_message = $product->get_interval_message();
?>
<div class="ld-registration-order__item">
	<div class="ld-registration-order__item-details">
		<div class="ld-registration-order__item-title-wrapper">
			<div class="ld-registration-order__item-type">
				<?php echo esc_html( $product->get_type_label() ); ?>
			</div>
			<span class="ld-registration-order__item-title">
						<?php echo esc_html( $product->get_title() ); ?>
					</span>
			<?php if ( $product->has_trial() ) : ?>
				<span class="ld-registration-order__item-trial-marker">
							<?php esc_html_e( 'Trial Price', 'learndash' ); ?>
						</span>
			<?php endif; ?>
			<?php if ( ! empty( $interval_message ) ) : ?>
				<div class="ld-registration-order__item-interval" aria-label="<?php esc_html_e( 'Interval', 'learndash' ); ?>">
					<?php echo esc_html( $interval_message ); ?>
				</div>
			<?php endif; ?>
		</div>
		<div class="ld-registration-order__item-price" aria-label="<?php esc_html_e( 'Price', 'learndash' ); ?>">
			<div class="ld-registration-order__item-price-original" aria-label="<?php esc_html_e( 'Original Price', 'learndash' ); ?>">
				<?php echo esc_html( $price ); ?>
			</div>
			<div class="ld-registration-order__item-price-value" aria-live="polite">
				<?php echo esc_html( $price ); ?>
			</div>
		</div>
	</div>
</div>
