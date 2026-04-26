<?php
/**
 * Registration - Coupon form.
 *
 * @version 4.16.0
 * @since 4.16.0
 *
 * @var Product  $product     Product data.
 * @var int      $register_id ID of the course or group.
 * @var Template $this        The Template object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Product;
use LearnDash\Core\Template\Template;

if (
	! $product->can_be_purchased()
	|| ! learndash_active_coupons_exist()
) {
	return;
}
?>
<div class="ld-registration-order__coupon-form-wrapper">
	<form
		class="ld-form ld-form__coupon"
		id="apply-coupon-form"
		data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-coupon-nonce' ) ); ?>"
		data-post-id="<?php echo absint( $register_id ); ?>"
	>
		<label for="coupon_field"><?php esc_html_e( 'Have a coupon?', 'learndash' ); ?></label>
		<div class="ld-form__field-wrapper ld-form__field-coupon_field-wrapper">
			<input
				type="text"
				id="coupon-field"
				name="coupon_field"
				class="ld-form__field ld-form__field-coupon_field"
				placeholder="<?php esc_html_e( 'Coupon Code', 'learndash' ); ?>"
			/>
			<button
				type="submit"
				class="ld-button ld-button--secondary ld-button--border ld-button__coupon-apply ld--ignore-inline-css"
				aria-label="<?php esc_attr_e( 'Apply coupon', 'learndash' ); ?>"
			>
				<?php esc_html_e( 'Apply', 'learndash' ); ?>
			</button>
		</div>
	</form>
</div>
