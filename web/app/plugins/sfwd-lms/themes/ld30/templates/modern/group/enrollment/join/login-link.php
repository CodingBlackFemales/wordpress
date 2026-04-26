<?php
/**
 * View: Group Enrollment Login Link.
 *
 * @since 4.22.0
 * @version 4.22.0
 *
 * @var string  $login_url            Login url.
 * @var string  $payment_buttons      Payment Buttons HTML.
 * @var bool    $custom_login_enabled Custom LearnDash login is enabled.
 * @var Product $product              Product model.
 * @var WP_User $user                 Current user.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Product;

// If a user is logged in, we don't need to show the login link.
if ( $user->exists() ) {
	return;
}
?>
<div class="ld-enrollment__login">
	<?php if ( ! empty( $payment_buttons ) ) : ?>
		<span class="ld-enrollment__login-text">
			<?php esc_html_e( 'or', 'learndash' ); ?>
		</span>
	<?php endif; ?>

	<?php if ( $custom_login_enabled ) : ?>
		<button class="ld-enrollment__login-link" data-ld-login-modal-trigger>
			<?php esc_html_e( 'Log In', 'learndash' ); ?>
		</button>
	<?php else : ?>
		<a class="ld-enrollment__login-link" href="<?php echo esc_url( $login_url ); ?>">
			<?php esc_html_e( 'Log In', 'learndash' ); ?>
		</a>
	<?php endif; ?>
</div>
