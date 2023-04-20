<?php
/**
 * Onboarding Coupons Template.
 *
 * Displayed when no entities were added to help the user.
 *
 * @since 4.1.0
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="ld-onboarding-screen">
	<div class="ld-onboarding-main">
		<span class="dashicons dashicons-welcome-add-page"></span>
		<h2>
			<?php
			printf(
				// translators: placeholder: Coupons.
				esc_html_x( 'You don\'t have any %s yet', 'placeholder: Coupons', 'learndash' ),
				LearnDash_Custom_Label::label_to_lower( 'coupons' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
			);
			?>
		</h2>
		<p>
			<?php
			printf(
				// translators: placeholder: %1$s: Coupons, %2$s: Courses, %3$s: Groups.
				esc_html_x(
					'%1$s offer an easy way for you to deliver %2$s and %3$s discounts!',
					'placeholder: %1$s: Coupons, %2$s: Courses, %3$s: Groups',
					'learndash'
				),
				LearnDash_Custom_Label::get_label( 'coupons' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
				LearnDash_Custom_Label::label_to_lower( 'courses' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
				LearnDash_Custom_Label::label_to_lower( 'groups' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
			);
			?>
		</p>
		<a
			href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::COUPON ) ) ); ?>"
			class="button button-secondary"
		>
			<span class="dashicons dashicons-plus-alt"></span>
			<?php
			printf(
				// translators: placeholder: Coupon.
				esc_html_x( 'Add your first %s', 'placeholder: Coupon', 'learndash' ),
				LearnDash_Custom_Label::label_to_lower( LDLMS_Post_Types::COUPON ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
			);
			?>
		</a>
	</div> <!-- .ld-onboarding-main -->

</section> <!-- .ld-onboarding-screen -->
