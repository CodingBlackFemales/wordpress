<?php
/**
 * Registration - order overview section.
 *
 * @since 4.16.0
 * @version 4.21.3
 *
 * @var Learndash_Coupon_DTO|null       $attached_coupon_dto      Coupon data.
 * @var array<string, int|float|string> $course_pricing           Course or group pricing data.
 * @var int                             $current_user_id          Current user ID.
 * @var bool                            $has_access_already       Whether the user has access to the course or group.
 * @var bool                            $is_registered            Whether the user is registered.
 * @var bool                            $is_user_logged_in        Whether the user is logged in.
 * @var Product                         $product                  Product data.
 * @var int                             $register_id              ID of the course or group.
 * @var Template                        $this                     The Template object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Product;
use LearnDash\Core\Template\Template;

$should_show_pay_buttons = $is_registered || $is_user_logged_in;
?>
<?php $this->template( 'modules/registration/order/coupons/alerts' ); ?>
<div class="ld-registration-order">

	<?php if ( $is_user_logged_in ) : ?>
		<h2 class="ld-registration-order__heading">
			<?php esc_html_e( 'Order Details', 'learndash' ); ?>
		</h2>
	<?php else : ?>
		<h3 class="ld-registration-order__heading">
			<?php esc_html_e( 'Order Details', 'learndash' ); ?>
		</h3>
	<?php endif; ?>

	<?php if ( $has_access_already ) : ?>
		<div class="ld-registration-order__already-access">
			<?php
			printf(
				// translators: placeholder: You already have access to Course/Group.
				esc_html_x(
					'You already have access to %1$s',
					'placeholder: You already have access to Course/Group',
					'learndash'
				),
				esc_html( $product->get_title() )
			);
			?>
		</div>
	<?php else : ?>
		<div class="ld-registration-order__items <?php echo esc_attr( ! empty( $attached_coupon_dto ) ? 'ld-registration-order__items--with-coupon' : '' ); ?>">
			<?php $this->template( 'modules/registration/order/item' ); ?>
			<?php $this->template( 'modules/registration/order/coupons/coupons' ); ?>
			<?php $this->template( 'modules/registration/order/totals' ); ?>
		</div>

		<?php
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $should_show_pay_buttons ) {
			echo learndash_payment_buttons( $register_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
	<?php endif; ?>
</div>
