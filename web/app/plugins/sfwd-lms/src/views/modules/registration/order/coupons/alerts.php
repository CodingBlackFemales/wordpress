<?php
/**
 * Registration - Coupon alerts.
 *
 * @since 4.16.0
 * @version 4.16.0
 *
 * @var bool    $is_user_logged_in Whether the user is logged in.
 * @var Product $product           Product data.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Product;

if (
	! $product->is_price_type_paynow()
	&& $is_user_logged_in
) {
	return;
}
?>
<div id="coupon-alerts" class="ld-coupon__alerts">
	<div class="coupon-alert coupon-alert-success ld-coupon__alert ld-coupon__alert--success" aria-live="polite" style="display: none">
		<?php
		learndash_get_template_part(
			'modules/alert.php',
			[
				'type'    => 'success',
				'icon'    => 'alert',
				'message' => ' ',
			],
			true
		);
		?>
	</div>
	<div class="coupon-alert coupon-alert-warning ld-coupon__alert ld-coupon__alert--warning" aria-live="polite" style="display: none">
		<?php
		learndash_get_template_part(
			'modules/alert.php',
			[
				'type'    => 'warning',
				'icon'    => 'alert',
				'message' => ' ',
				'role'    => 'alert',
			],
			true
		);
		?>
	</div>
</div>
