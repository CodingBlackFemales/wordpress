<?php
/**
 * Registration - Login prompt message.
 *
 * @since 4.16.0
 * @version 4.16.0
 *
 * @var array<int, string>    $error_conditions             Collection of error conditions.
 * @var array<string, mixed>  $registration_errors          Collection of error messages.
 * @var string                $field_name_login             Name of login field.
 * @var string                $field_name_email             Name of email field.
 * @var array<string, string> $registration_fields_order    Collection of fields order.
 * @var string                $login_link_redirect          Login link redirect.
 * @var string                $registration_redirect_to_url URL to redirect to.
 * @var string                $register_action_url          URL to register action.
 * @var int                   $register_id                  Register ID.
 * @var array<string, string> $registration_fields          Collection of registration fields.
 * @var Template              $this                         The Template object.
 * @var bool                  $is_terms_enabled             Flag whether the terms feature is enabled.
 * @var bool                  $is_privacy_enabled           Flag whether the privacy feature is enabled.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
use StellarWP\Learndash\StellarWP\SuperGlobals\SuperGlobals;

if ( $registration_errors['has_errors'] ) {
	learndash_get_template_part(
		'modules/alert.php',
		[
			'type'    => 'warning',
			'icon'    => 'alert',
			'message' => $registration_errors['message'],
			'role'    => 'alert',
		],
		true
	);

	/**
	 * Fires after the register modal errors.
	 *
	 * @since 3.6.0
	 *
	 * @param array $errors An array of error details.
	 */
	do_action( 'learndash_registration_errors_after', $registration_errors );
}
?>
<form
	name="learndash_registerform"
	id="learndash_registerform"
	class="ld-form ld-registration__register-form"
	action="<?php echo esc_url( $register_action_url ); ?>"
	data-learndash-validate="true"
	method="post"
>
	<?php
	/**
	 * Fires before the loop when displaying the registration form fields
	 *
	 * @since 3.6.0
	 */
	do_action( 'learndash_registration_form_fields_before' );

	foreach ( $registration_fields_order as $learndash_field ) {
		$is_enabled  = ( 'yes' === $registration_fields[ $learndash_field . '_enabled' ] );
		$is_required = ( 'yes' === $registration_fields[ $learndash_field . '_required' ] );
		$learndash_required = $is_required ? 'required aria-required="true"' : '';
		if ( 'username' === $learndash_field ) {
			$learndash_name_field = $field_name_login;
		} elseif ( 'email' === $learndash_field ) {
			$learndash_name_field = $field_name_email;
		} else {
			$learndash_name_field = $learndash_field;
		}

		$learndash_value = SuperGlobals::get_get_var( $learndash_name_field, '' );

		if ( ! is_string( $learndash_value ) ) {
			$learndash_value = '';
		}

		if ( $is_enabled ) {
			$wrap_classes = [
				'learndash-registration-field',
				'learndash-registration-field-' . $learndash_field,
			];

			if ( $learndash_required ) {
				$wrap_classes[] = 'learndash-required';
			}

			$field_type = 'text';
			if ( 'password' === $learndash_field ) {
				$field_type = 'password';
			} elseif ( 'email' === $learndash_field ) {
				$field_type = 'email';
			}

			$this->template(
				'components/forms/field',
				[
					'field_id'     => $learndash_field,
					'is_required'  => $is_required,
					'field_label'  => $registration_fields[ $learndash_field . '_label' ],
					'field_name'   => $learndash_name_field,
					'show_meter'   => 'password' === $learndash_field,
					'show_toggle'  => 'password' === $learndash_field,
					'field_type'   => $field_type,
					'field_value'  => $learndash_value,
					'wrap_classes' => $wrap_classes,
				]
			);
			?>
			<?php if ( 'password' === $learndash_field ) : ?>
				<p class="ld-password-strength__hint">
					<?php esc_html_e( 'A medium-strength password is needed to register. Tip: Try at least 12 characters long containing letters, numbers, and special characters.', 'learndash' ); ?>
				</p>
			<?php endif; ?>
			<?php
		}
	}

	if ( $is_privacy_enabled || $is_terms_enabled ) {
		$this->template( 'components/forms/terms-privacy-checkboxes' );
	}

	/**
	 * Fires after the loop when displaying the registration form fields
	 *
	 * @since 3.6.0
	 */
	do_action( 'learndash_registration_form_fields_after' );

	if ( is_multisite() ) {
		signup_nonce_fields();
		?>
		<input type="hidden" name="signup_for" value="user" />
		<input type="hidden" name="stage" value="validate-user-signup" />
		<input type="hidden" name="blog_id" value="<?php echo get_current_blog_id(); ?>" />
		<?php
		/** This filter is documented in https://developer.wordpress.org/reference/hooks/signup_extra_fields/ */
		do_action( 'signup_extra_fields', '' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core hook.
	} else {
		/** This filter is documented in https://developer.wordpress.org/reference/hooks/register_form/ */
		do_action( 'register_form' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core hook.
	}

	/**
	 * Fires inside the registration form.
	 *
	 * @since 3.6.0
	 */
	do_action( 'learndash_registration_form' );
	?>
	<input name="ld_register_id" value="<?php echo absint( $register_id ); ?>" type="hidden" />
	<input type="hidden" name="learndash-registration-form" value="<?php echo esc_attr( wp_create_nonce( 'learndash-registration-form' ) ); ?>" />
	<input type="hidden" name="redirect_to" value="<?php echo esc_url( $registration_redirect_to_url ); ?>" />
	<p class="ld-registration__register-submit-wrapper">
		<input
			type="submit"
			name="wp-submit"
			id="wp-submit-register"
			class="button button-primary button-large wp-element-button"
			value="<?php esc_attr_e( 'Register', 'learndash' ); ?>"
		/>
	</p>
</form>
