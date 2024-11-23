<?php
/**
 * LearnDash LD30 Displays the login modal
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Added logic to only load the modal template once.
 */
global $login_model_load_once;
if ( true === (bool) $login_model_load_once ) {
	return false;
}
$login_model_load_once = true;

if ( is_multisite() ) {
	$can_register = users_can_register_signup_filter();
} else {
	$can_register = get_option( 'users_can_register' );
}

?>

<div class="ld-modal ld-login-modal<?php echo esc_attr( $can_register ? ' ld-can-register' : '' ); ?>">

	<span class="ld-modal-closer ld-icon ld-icon-delete"></span>

	<div class="ld-login-modal-login">
		<div class="ld-login-modal-wrapper">
			<?php
			/**
			 * Fires before the modal heading.
			 *
			 * @since 3.0.0
			 */
			do_action( 'learndash-login-modal-heading-before' );
			?>
				<?php
				$logo_id = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'login_logo' );
				if ( $logo_id ) :
					?>
					<div class="ld-login-modal-branding">
						<img src="<?php echo esc_url( wp_get_attachment_url( $logo_id ) ); ?>" alt="<?php echo esc_attr( get_post_meta( $logo_id, '_wp_attachment_image_alt', true ) ); ?>">
					</div>
				<?php endif; ?>
			<div class="ld-modal-heading">
				<?php echo esc_html_e( 'Login', 'buddyboss-theme' ); ?>
			</div>
			<?php
			/**
			 * Action to add custom content after the modal heading
			 *
			 * @since 3.0
			 */
			do_action( 'learndash-login-modal-heading-after' );

			if ( in_array( get_post_type(), learndash_get_post_types( 'course' ), true ) ) {
				?>
				<div class="ld-modal-text">
					<?php
					echo sprintf(
					// translators: placeholder: course.
						esc_html_x( 'Accessing this %s requires a login. Please enter your credentials below!', 'placeholder: course', 'buddyboss-theme' ),
						esc_html( learndash_get_custom_label_lower( 'course' ) )
					);
					?>
				</div>
				<?php
			}

			/**
			 * Fires after the modal text.
			 *
			 * @since 3.0.0
			 */
			do_action( 'learndash-login-modal-text-after' );

			if ( isset( $_GET['login'] ) && 'failed' === $_GET['login'] ) :

				learndash_get_template_part(
					'modules/alert.php',
					array(
						'type'    => 'warning',
						'icon'    => 'alert',
						'message' => __( 'Incorrect username or password. Please try again', 'buddyboss-theme' ),
					),
					true
				);

				/**
				 * Fires after the modal alert.
				 *
				 * @since 3.0.0
				 */
				do_action( 'learndash-login-modal-alert-after' );

			elseif ( isset( $_GET['ld-resetpw'] ) && 'true' === $_GET['ld-resetpw'] ) :

				learndash_get_template_part(
					'modules/alert.php',
					array(
						'type'    => 'warning',
						'icon'    => 'alert',
						'message' => __( 'Please check your email for the password reset link.', 'buddyboss-theme' ),
					),
					true
				);

					/** This action is documented in themes/ld30/templates/modules/login-modal.php */
					do_action( 'learndash-login-modal-alert-after' );

			endif;
			?>
			<div class="ld-login-modal-form">

				<?php
				/**
				 * Fires before the modal form.
				 *
				 * @since 3.0.0
				 */
				do_action( 'learndash-login-modal-form-before' );

				// Add a filter for validation returns.
				add_filter( 'login_form_top', 'learndash_add_login_field_top' );

				// Just so users can supply their own args if desired.
				$login_form_args = array();

				/**
				 * Remove the query string param '?login=failed' and hash '#login' from previous
				 * login failed attempt. This way on success the user is returned back to the course
				 * and not shown the login form again.
				 */
				$login_form_args['redirect'] = remove_query_arg( 'login' );
				$login_form_args['redirect'] = str_replace( '#login', '', $login_form_args['redirect'] );

				/**
				 * Filters list of login form arguments to be used in wp_login_form.
				 *
				 * @since 3.0.0
				 *
				 * @param array $login_form_args An Array of login form arguments to be used in wp_login_form.
				 */
				$login_form_args = apply_filters( 'learndash-login-form-args', $login_form_args );

				wp_login_form( $login_form_args );

				/**
				 * Fires after the modal form.
				 *
				 * @since 3.0.0
				 */
				do_action( 'learndash-login-modal-form-after' );

				$lost_password_url = remove_query_arg( 'login', get_permalink() );
				$lost_password_url = add_query_arg( 'ld-resetpw', 'true', $lost_password_url );
				$lost_password_url = learndash_add_login_hash( $lost_password_url );
				$lost_password_url = wp_lostpassword_url( $lost_password_url );
				if ( learndash_reset_password_is_enabled() ) {
					$lost_password_url = get_permalink( learndash_get_reset_password_page_id() );
				}
				?>
				<a class="ld-forgot-password-link" href="<?php echo esc_url( $lost_password_url ); ?>"><?php esc_html_e( 'Lost Your Password?', 'buddyboss-theme' ); ?></a>

				<?php

				/**
				 * Fires after the modal form.
				 *
				 * @since 3.0.0
				 */
				do_action( 'learndash-login-modal-after' );
				?>

			</div> <!--/.ld-login-modal-form-->
		</div> <!--/.ld-login-modal-wrapper-->
	</div> <!--/.ld-login-modal-login-->

	<?php
	if ( $can_register ) :

		// Set the default register_url to show the inline registration form.
		$register_url = '#ld-user-register';

		// New since LD 3.6.0 are we using the new Registration page?
		if ( ! is_multisite() ) {
			$ld_registration_page_id = (int) LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Registration_Pages', 'registration' );

			if ( ! empty( $ld_registration_page_id ) ) {
				$register_url = get_permalink( $ld_registration_page_id );
				if ( in_array( get_post_type(), learndash_get_post_type_slug( array( 'course', 'group' ) ), true ) ) {
					// If we are showing on a Course or Group we inlcude the 'ld_register_id' query string param.
					$register_url = add_query_arg( 'ld_register_id', get_the_ID(), $register_url );
				} elseif ( get_the_ID() === $ld_registration_page_id ) {
					// If we are showing on the new Registration page we make sure to include the query argument.
					if ( isset( $_GET['ld_register_id'] ) ) {
						$register_url = add_query_arg( 'ld_register_id', absint( $_GET['ld_register_id'] ), $register_url );
					}
				}
			}
		}

		/**
		 * Filters the LearnDash login modal registration URL.
		 *
		 * @since 3.1.0
		 *
		 * @param string $register_url Login modal registration url.
		 */
		$register_url = apply_filters( 'learndash_login_model_register_url', $register_url );
		/**
		 * Filters the LearnDash login modal registration header text.
		 *
		 * @since 3.1.0
		 *
		 * @param string $registration_header Login modal registration header text.
		 */
		$register_header = apply_filters( 'learndash_login_model_register_header', esc_html__( 'Register', 'buddyboss-theme' ) );
		/**
		 * Filters the LearnDash login modal registration text.
		 *
		 * @since 3.1.0
		 *
		 * @param string $registration_text   Login Modal registration text.
		 */
		$register_text     = apply_filters( 'learndash_login_model_register_text', esc_html__( 'Don\'t have an account? Register one!', 'buddyboss-theme' ) );
		$errors_conditions = learndash_login_error_conditions();
		?>
		<div class="ld-login-modal-register">
			<div class="ld-login-modal-wrapper">
				<div class="ld-content">
					<?php
					/**
					 * Fires before the register modal heading.
					 *
					 * @since 3.0.0
					 */
					do_action( 'learndash-register-modal-heading-before' );
					?>
					<div class="ld-modal-heading">
						<?php echo esc_html( $register_header ); ?>
					</div>
					<?php
					/**
					 * Fires after the register modal heading.
					 *
					 * @since 3.0.0
					 */
					do_action( 'learndash-register-modal-heading-after' );
					?>

					<div class="ld-modal-text"><?php echo esc_html( $register_text ); ?></div>
					<?php
					/**
					 * Fires after the register modal heading.
					 *
					 * @since 3.0.0
					 */
					do_action( 'learndash-register-modal-text-after' );

					$errors = array(
						'has_errors' => false,
						'message'    => '',
					);

					foreach ( $errors_conditions as $param => $message ) {
						if ( isset( $_GET[ $param ] ) ) {
							$errors['has_errors'] = true;
							if ( ! empty( $errors['message'] ) ) {
								$errors['message'] .= '<br />';
							}
							$errors['message'] .= $message;
						}
					}

					$errors_conditions = apply_filters(
						'learndash-registration-errors',
						array(
							'empty_username'   => __( 'Registration requires a username.', 'buddyboss-theme' ),
							'empty_email'      => __( 'Registration requires a valid email.', 'buddyboss-theme' ),
							'invalid_username' => __( 'Invalid username.', 'buddyboss-theme' ),
							'invalid_email'    => __( 'Invalid email.', 'buddyboss-theme' ),
						)
					);


					if ( $errors['has_errors'] ) :
						learndash_get_template_part(
							'modules/alert.php',
							array(
								'type'    => 'warning',
								'icon'    => 'alert',
								'message' => $errors['message'],
							),
							true
						);

						/**
						 * Fires after the register modal errors.
						 *
						 * @since 3.0.0
						 *
						 * @param array $errors An array of error details.
						 */
						do_action( 'learndash-register-modal-errors-after', $errors );

					elseif ( isset( $_GET['ld-registered'] ) && 'true' === $_GET['ld-registered'] ) :

						learndash_get_template_part(
							'modules/alert.php',
							array(
								'type'    => 'success',
								'icon'    => 'alert',
								'message' => __( 'Registration successful, please check your email to set your password.', 'buddyboss-theme' ),
							),
							true
						);

						/**
						 * Fires after the register modal errors.
						 *
						 * @since 3.0.0
						 *
						 * @param array $errors An array of error details.
						 */
						do_action( 'learndash-register-successful-after', $errors );

					endif;

					if ( '#ld-user-register' === $register_url ) {
						/**
						 * Filters the LearnDash Login modal register button CSS class.
						 *
						 * @since 3.1.0
						 *
						 * @param string $register_button_class Register button CSS class.
						 */
						$register_button_class = apply_filters( 'learndash_login_model_register_button_class', 'ld-js-register-account' );
					} else {

						/** This filter is documented in themes/ld30/templates/modules/login-modal.php */
						$register_button_class = apply_filters( 'learndash_login_model_register_button_class', '' );
					}
					?>

					<a href="<?php echo esc_url( $register_url ); ?>" class="ld-button ld-button-reverse <?php echo esc_attr( $register_button_class ); ?>"><?php echo esc_html_e( 'Register an Account', 'buddyboss-theme' ); ?></a>

					<?php
					/**
					 * Fires after the register modal heading.
					 *
					 * @since 3.0.0
					 */
					do_action( 'learndash-register-modal-registration-link-after' );
					?>

				</div> <!--/.ld-content-->
				<?php
				/**
				 * Only if we are showing the LD register form.
				 */
				if ( '#ld-user-register' === $register_url ) {
					?>
					<div id="ld-user-register" class="ld-hide">
					<?php
					if ( has_action( 'learndash_register_modal_register_form_override' ) ) {
						/**
						 * Allow for replacement of the default LearnDash Registration form
						 *
						 * @since 3.2.0
						 */
						do_action( 'learndash_register_modal_register_form_override' );
					} else {
						/**
						 * Fires before the register modal heading.
						 *
						 * @since 3.0.0
						 */
						do_action( 'learndash-register-modal-register-form-before' );
						if ( is_multisite() ) {
							$register_action_url = network_site_url( 'wp-signup.php' );
							$field_name_login    = 'user_name';
							$field_name_email    = 'user_email';
						} else {
							$register_action_url = site_url( 'wp-login.php?action=register', 'login_post' );
							$field_name_login    = 'user_login';
							$field_name_email    = 'user_email';
						}
						?>
					<form name="registerform" id="registerform" action="<?php echo esc_url( $register_action_url ); ?>" method="post" novalidate="novalidate">
						<p>
							<label for="user_reg_login"><?php esc_html_e( 'Username', 'buddyboss-theme' ); ?><br />
							<input type="text" name="user_login" id="user_reg_login" class="input" value="" size="20" /></label>
						</p>
						<p>
							<label for="user_reg_email"><?php esc_html_e( 'Email', 'buddyboss-theme' ); ?><br />
							<input type="email" name="user_email" id="user_reg_email" class="input" value="" size="25" /></label>
						</p>
						<?php
						if ( is_multisite() ) {
							signup_nonce_fields();
							?>
							<input type="hidden" name="signup_for" value="user" />
							<input type="hidden" name="stage" value="validate-user-signup" />
							<input type="hidden" name="blog_id" value="<?php echo get_current_blog_id(); ?>" />
							<?php

							/**
							 * Fires at the end of the user registration form on the site sign-up form.
							 *
							 * @since 3.0.0
							 *
							 * @param WP_Error $errors A WP_Error object containing 'user_name' or 'user_email' errors.
							 */
							do_action( 'signup_extra_fields', '' );
						} else {
							/** This filter is documented in https://developer.wordpress.org/reference/hooks/register_form/ */
							do_action( 'register_form' );
						}

						/**
						 * Fires inside the registration form.
						 *
						 * @since 3.0.0
						 */
						do_action( 'learndash_register_form' );

						$post_type = get_post_type( get_the_ID() );

						if ( in_array( $post_type, learndash_get_post_types( 'course' ), true ) ) {
							$course_id = learndash_get_course_id( get_the_ID() );

							/** This filter is documented in themes/ld30/includes/login-register-functions.php */
							if ( ( ! empty( $course_id ) ) && ( in_array( learndash_get_setting( $course_id, 'course_price_type' ), array( 'free' ) ) ) && ( apply_filters( 'learndash_registration_form_include_course', true, $course_id ) ) ) {
								?>
								<input name="learndash-registration-form-post" value="<?php echo absint( $course_id ); ?>" type="hidden" />
								<?php
								wp_nonce_field( 'learndash-registration-form-post-' . absint( $course_id ) . '-nonce', 'learndash-registration-form-post-nonce' );
							}
						} elseif ( in_array( $post_type, array( learndash_get_post_type_slug( 'group' ) ), true ) ) {
							$group_id = get_the_ID();

							/** This filter is documented in themes/ld30/includes/login-register-functions.php */
							if ( ( ! empty( $group_id ) ) && ( in_array( learndash_get_setting( $group_id, 'group_price_type' ), array( 'free' ) ) ) && ( apply_filters( 'learndash_registration_form_include_group', true, $group_id ) ) ) {
								?>
								<input name="learndash-registration-form-post" value="<?php echo absint( $group_id ); ?>" type="hidden" />
								<?php
								wp_nonce_field( 'learndash-registration-form-post-' . absint( $group_id ) . '-nonce', 'learndash-registration-form-post-nonce' );
							}
						}

						$redirect_to_url = remove_query_arg( array_keys( $errors_conditions ), get_permalink() );
						if ( ! is_multisite() ) {
							$redirect_to_url = add_query_arg( 'ld-registered', 'true', $redirect_to_url );
						}
						$redirect_to_url = learndash_add_login_hash( $redirect_to_url );

						?>
						<input name="learndash-registration-form" value="<?php echo esc_attr( wp_create_nonce( 'learndash-registration-form' ) ); ?>" type="hidden">
						<input name="redirect_to" type="hidden" value="<?php echo esc_url( $redirect_to_url ); ?>">
						<p id="reg_passmail"><?php esc_html_e( 'Registration confirmation will be emailed to you.', 'buddyboss-theme' ); ?></p>
						<br class="clear" />
						<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Register', 'buddyboss-theme' ); ?>" /></p>
					</form>
						<?php
						/**
						 * Fires before the register modal heading.
						 *
						 * @since 3.0.0
						 */
						do_action( 'learndash-register-modal-register-form-after' );
					}
					?>
				</div> <!--/#ld-user-register-->
					<?php
				}

				/**
				 * Fires before the register modal heading.
				 *
				 * @since 3.0.0
				 */
				do_action( 'learndash-register-modal-register-wrapper-after' );
				?>
			</div> <!--/.ld-login-modal-wrapper-->
		</div> <!--/.ld-login-modal-register-->
	<?php endif; ?>

</div> <!--/.ld-modal-->
