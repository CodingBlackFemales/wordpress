<?php
/**
 * Registration - Login form.
 *
 * @since 4.16.0
 * @version 4.16.0
 *
 * @var bool   $is_registration_enabled Whether registration is enabled.
 * @var string $wp_login_form_html      Login form HTML.
 * @var string $register_action_url     Register link.
 *
 * @package LearnDash\Core
 */

?>
<div class="ld-registration__login">
	<h2 class="ld-registration__heading ld-registration-register__heading">
		<?php esc_html_e( 'Log In', 'learndash' ); ?>
	</h2>

	<p class="ld-registration__p">
		<?php if ( $is_registration_enabled ) : ?>
			<?php
			echo esc_html_x( 'Are you a new user?', 'placeholder: Message above login form if user can register.', 'learndash' );
			?>
			<a class="ld-registration__register-link" role="button" href="<?php echo esc_url( $register_action_url ); ?>">
				<?php esc_html_e( 'Register', 'learndash' ); ?>
			</a>
		<?php endif; ?>
	</p>
</div>
<div class="ld-registration__login-form">
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- All necessary data is already escaped.
	echo $wp_login_form_html;
	?>
</div>
