<?php
/**
 * Registration - Coupon output.
 *
 * @since 4.16.0
 * @version 4.16.0
 *
 * @var Learndash_Coupon_DTO|null $attached_coupon_dto Coupon data.
 * @var int                       $register_id         ID of the course or group.
 * @var Template                  $this                The Template object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

?>
<div class="ld-coupon__wrapper" id="ld-coupon-totals">
	<div class="ld-coupon">
		<div class="ld-coupon__details-wrapper" id="coupon-row">
			<span class="ld-coupon__label-wrapper">
				<span class="ld-coupon__label" aria-label="<?php esc_html_e( 'Coupon', 'learndash' ); ?>">
					<?php $this->template( 'components/icons/coupon', [ 'classes' => [ 'ld-coupon__label--coupon-icon' ] ] ); ?>
					<span class="ld-coupon__label-text" id="ld-coupon-label">
						<?php if ( ! empty( $attached_coupon_dto ) ) : ?>
							<?php echo esc_html( $attached_coupon_dto->code ); ?>
						<?php endif; ?>
					</span>
				</span>
				<form
					class="ld-coupon__remove-form"
					id="remove-coupon-form"
					aria-labelledby="ld-coupon-label"
					aria-describedby="ld-coupon-value"
					data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-coupon-nonce' ) ); ?>"
					data-post-id="<?php echo esc_attr( (string) $register_id ); ?>"
				>
					<button
						type="submit"
						class="button-small ld-coupon__remove"
						aria-label="<?php esc_html_e( 'Remove coupon', 'learndash' ); ?>"
					>
						<?php $this->template( 'components/icons/close', [ 'classes' => [ 'ld-coupon__remove--close-icon' ] ] ); ?>
					</button>
				</form>
			</span>
		</div>

		<div class="ld-coupon__value" id="ld-coupon-value" aria-live="polite">
			<?php if ( ! empty( $attached_coupon_dto ) ) : ?>
				(-<?php echo esc_html( learndash_get_price_formatted( $attached_coupon_dto->discount ) ); ?>)
			<?php endif; ?>
		</div>
	</div>
</div>
