<?php
/**
 * Registration - Forgot password form.
 *
 * @since 4.16.0
 * @version 4.16.0
 *
 * @var string               $action                         The action.
 * @var string               $do_password_reset              The do password reset.
 * @var string               $form_nonce                     The form nonce.
 * @var string               $form_post_nonce                The form post nonce.
 * @var string               $form_width                     The form width.
 * @var bool                 $show_set_password_form         Whether to show the set password form.
 * @var array<string, mixed> $status                         The status.
 * @var string               $user_login                     The user login.
 * @var string               $wp_login_form_html             The WP login form HTML.
 * @var string               $wrapper_class                  The wrapper class.
 * @var Template             $this                           The Template object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

if ( is_numeric( $form_width ) ) {
	$form_width .= 'px';
}

$form_width = empty( $form_width ) ? '' : 'width: ' . esc_attr( $form_width ) . ';';

?>
<div class="<?php echo esc_attr( $wrapper_class ); ?> ld-registration__outer-wrapper">
	<div class="learndash-registration-wrapper" style="<?php echo esc_attr( $form_width ); ?>" >
		<div
			class="ld-registration__wrapper ld-registration__wrapper--login"
			data-js="learndash-view"
			data-learndash-breakpoints="<?php echo esc_attr( $this->get_breakpoints_json() ); ?>"
			data-learndash-breakpoint-pointer="<?php echo esc_attr( $this->get_breakpoint_pointer() ); ?>"
		>
			<div class="ld-registration__form">
				<?php if ( $status ) : ?>
					<?php
					learndash_get_template_part(
						'modules/alert.php',
						array(
							'type'    => $status['type'],
							'icon'    => 'alert',
							'message' => $status['message'],
							'role'    => 'alert',
						),
						true
					);
					?>
				<?php endif; ?>

				<?php learndash_login_failed_alert(); ?>

				<?php if ( is_user_logged_in() ) : ?>
					<?php $this->template( 'modules/registration/login/already-logged-in', [ 'is_registration_enabled' => false ] ); ?>
				<?php elseif ( $show_set_password_form ) : ?>
					<div class="ld-registration__reset-password-container">
						<h2 class="ld-registration__heading">
							<?php esc_html_e( 'Reset Password', 'learndash' ); ?>
						</h2>
						<form action="" method="POST" class="ld-form ld-registration__reset-password-form">
							<label for="reset_password"><?php esc_html_e( 'Set new password *', 'learndash' ); ?></label>
							<div class="ld-form__field-wrapper ld-form__field-password-wrapper">
								<input
									type="password"
									name="reset_password"
									id="password"
									class="ld-form__field ld-form__field--needs-password-strength ld-form__field-reset_password"
									aria-describedby="ld-password-strength__meter"
								/>
								<button
									type="button"
									class="ld-button ld-button--border ld-button__password-visibility-toggle ld--ignore-inline-css"
									aria-label="<?php esc_attr_e( 'Toggle password visibility', 'learndash' ); ?>"
									aria-live="polite"
								>
									<?php esc_html_e( 'Show', 'learndash' ); ?>
								</button>
							</div>
							<p class="ld-password-strength__hint">
								<?php esc_html_e( 'A medium-strength password is required. Tip: Try at least 12 characters long containing letters, numbers, and special characters.', 'learndash' ); ?>
							</p>
							<input type="hidden" name="user_login" id="user_login" value="<?php echo esc_attr( $user_login ); ?>" />
							<?php wp_nonce_field( 'learndash-reset-password-form-post-nonce', 'learndash-reset-password-form-post-nonce' ); ?>
							<input
								type="submit"
								value="<?php esc_html_e( 'Reset Password', 'learndash' ); ?>"
								class="button button-primary button-large wp-element-button"
							/>
						</form>
					</div>
				<?php elseif ( $action === 'prevent_reset' ) : ?>
					<?php
					// Password reset key is invalid here, don't allow them to reset the password and just show an error message.
					?>
				<?php elseif ( $do_password_reset === 'true' ) : ?>
					<?php $this->template( 'modules/registration/login/form', [ 'is_registration_enabled' => false ] ); ?>
				<?php else : ?>
					<div class="ld-registration__forgot-password-container">
						<h2 class="ld-registration__heading">
							<?php esc_html_e( 'Forgot Password', 'learndash' ); ?>
						</h2>
						<form action="" method="POST" class="ld-form ld-registration__forgot-password-form">
							<label for="user_login"><?php esc_html_e( 'Username or Email Address *', 'learndash' ); ?></label>
							<div class="ld-form__field-wrapper ld-form__field-user_login-wrapper">
								<input
									type="text"
									name="user_login"
									id="user_login"
									autocapitalize="off"
									autocomplete="off"
									class="ld-form__field ld-form__field-user_login"
								/>
							</div>
							<?php wp_nonce_field( 'learndash-reset-password-form-nonce', 'learndash-reset-password-form-nonce' ); ?>
							<input
								type="submit"
								value="<?php esc_html_e( 'Reset Password', 'learndash' ); ?>"
								class="button button-primary button-large wp-element-button"
							/>
						</form>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
<?php
$this->template( 'components/breakpoints', [ 'is_initial_load' => true ] );
