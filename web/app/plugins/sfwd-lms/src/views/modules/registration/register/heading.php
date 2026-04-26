<?php
/**
 * Registration - Register heading.
 *
 * @since 4.16.0
 * @version 4.16.0
 *
 * @var bool   $is_registration_enabled Whether registration is enabled.
 * @var string $login_link_redirect     Login link redirect.
 *
 * @since 4.16.0
 *
 * @package LearnDash\Core
 */

?>
<div class="ld-registration__register">
	<h2 class="ld-registration__heading ld-registration-register__heading">
		<?php if ( $is_registration_enabled ) : ?>
			<?php esc_html_e( 'Register', 'learndash' ); ?>
		<?php else : ?>
			<?php esc_html_e( 'Log In', 'learndash' ); ?>
		<?php endif; ?>
	</h2>

	<p class="ld-registration__p">
		<?php if ( $is_registration_enabled ) : ?>
			<?php
			echo esc_html_x( 'Already have an account?', 'placeholder: Message above registration form if user logged out.', 'learndash' );
			?>
			<a class="ld-registration__login-link" role="button" href="<?php echo esc_url( $login_link_redirect ); ?>">
				<?php esc_html_e( 'Log In', 'learndash' ); ?>
			</a>
		<?php else : ?>
			<?php
			echo esc_html_x( 'Log in or contact a Site Administrator to be registered.', 'placeholder: Message above registration form if user logged out.', 'learndash' );
			?>
		<?php endif; ?>
	</p>
	<?php if ( ! $is_registration_enabled ) : ?>
		<a class="button button-link ld-registration__login-link ld-registration__login-button" role="button" href="<?php echo esc_url( $login_link_redirect ); ?>">
			<?php esc_html_e( 'Log In', 'learndash' ); ?>
		</a>
	<?php endif; ?>
</div>
