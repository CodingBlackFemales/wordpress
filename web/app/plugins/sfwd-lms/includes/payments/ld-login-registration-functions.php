<?php
/**
 * Functions related to login/registration functions
 *
 * @since 3.6.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LearnDash\Core\Modules\Registration\Terms_Privacy_Agreement\Terms_Privacy_Agreement;
use LearnDash\Core\Models\Product;
use LearnDash\Core\Template\Template;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\Assets\Assets;
use StellarWP\Learndash\StellarWP\SuperGlobals\SuperGlobals;

/**
 * Returns the registration appearance setting.
 *
 * @since 4.16.0
 *
 * @return string
 */
function learndash_registration_variation(): string {
	$registration_appearance = 'classic';

	if ( LearnDash_Settings_Section_General_Appearance::get_setting( 'registration_enabled' ) === 'yes' ) {
		$registration_appearance = 'modern';
	}

	return $registration_appearance;
}

/**
 * LearnDash LD30 Shows registration form for user registration
 *
 * @since 3.6.0
 *
 * @param array $attr Array of attributes for shortcode.
 */
function learndash_registration_output( $attr = array() ) {
	if ( learndash_registration_variation() === LearnDash_Theme_Register_LD30::$variation_modern ) {
		learndash_registration_output_modern( $attr );
		return;
	}

	$attr_defaults = array(
		'width' => 0,
	);
	$attr          = shortcode_atts( $attr_defaults, $attr );

	$form_width = $attr['width'];

	if ( is_multisite() ) {
		$learndash_can_register = users_can_register_signup_filter();
	} else {
		$learndash_can_register = get_option( 'users_can_register' );
	}

	$learndash_errors_conditions = learndash_login_error_conditions();

	$active_template_key = LearnDash_Theme_Register::get_active_theme_key();

	?>

	<div class="<?php echo ( 'ld30' === $active_template_key ) ? esc_attr( learndash_the_wrapper_class() ) : 'learndash-wrapper'; ?>">

	<div id="learndash-registration-wrapper" <?php echo ( ! empty( $form_width ) ) ? 'style="width: ' . esc_attr( $form_width ) . ';"' : ''; ?>>

	<?php
	if ( isset( $_GET['ld-registered'] ) && 'true' === $_GET['ld-registered'] ) {
		learndash_get_template_part(
			'modules/alert.php',
			array(
				'type'    => 'success',
				'icon'    => 'alert',
				'message' => __( 'Registration successful.', 'learndash' ),
			),
			true
		);

		/**
		 * Fires after the register modal errors.
		 *
		 * @since 3.6.0
		 */
		do_action( 'learndash_registration_successful_after' );
	}

	if ( isset( $_GET['ld_register_id'] ) && '0' < $_GET['ld_register_id'] ) :
		$register_id = absint( $_GET['ld_register_id'] );

		$post_type = get_post_type( $register_id );

		/**
		 * Product object.
		 *
		 * @var Product|null $product
		 */
		$product = Product::find( $register_id );

		if ( LDLMS_Post_Types::get_post_type_slug( 'course' ) === $post_type ) {
			$course_pricing = learndash_get_course_price( $register_id );
		} elseif ( learndash_get_post_type_slug( 'group' ) === $post_type ) {
			$course_pricing = learndash_get_group_price( $register_id );
		} else {
			esc_html_e( 'Invalid Course or Group', 'learndash' );
			return;
		}

		if ( ! $product ) {
			esc_html_e( 'Invalid product', 'learndash' );
			return;
		}

		$course_pricing['price'] = learndash_get_price_as_float( $course_pricing['price'] );

		if ( ! empty( $course_pricing['trial_price'] ) ) {
			$course_pricing['trial_price'] = learndash_get_price_as_float( $course_pricing['trial_price'] );
		}

		$attached_coupon_dto = array();
		if ( is_user_logged_in() && learndash_post_has_attached_coupon( $register_id, get_current_user_id() ) ) {
			$attached_coupon_dto = learndash_get_attached_coupon_data( $register_id, get_current_user_id() );
		}
		?>

		<div class="order-overview">
			<p class="order-heading">
				<?php esc_html_e( 'Order Overview', 'learndash' ); ?>
			</p>

			<p class="purchase-title">
				<?php echo esc_html( get_the_title( $register_id ) ); ?>
			</p>

			<?php
			if (
				is_user_logged_in()
				&& (
					(
						learndash_is_course_post( $register_id )
						&& sfwd_lms_has_access( $register_id, get_current_user_id() )
					)
					|| (
						learndash_is_group_post( $register_id )
						&& learndash_is_user_in_group( get_current_user_id(), $register_id )
					)
				)
			) {
				printf(
					// translators: placeholder: You already have access to Course/Group - Click here to visit.
					esc_html_x(
						'You already have access to %1$s - %2$s',
						'placeholder: You already have access to Course/Group - Click here to visit',
						'learndash'
					),
					esc_html( get_the_title( $register_id ) ),
					'<a href="' . esc_url( get_permalink( $register_id ) ) . '">' . esc_html__( 'Click here to visit', 'learndash' ) . '</a>'
				);
			} else {
				if ( 'paynow' === $course_pricing['type'] && is_user_logged_in() ) :
					?>
					<div id="coupon-alerts">
						<div class="coupon-alert coupon-alert-success" style="display: none">
							<?php
							learndash_get_template_part(
								'modules/alert.php',
								array(
									'type'    => 'success',
									'icon'    => 'alert',
									'message' => ' ',
								),
								true
							);
							?>
						</div>
						<div class="coupon-alert coupon-alert-warning" style="display: none">
							<?php
							learndash_get_template_part(
								'modules/alert.php',
								array(
									'type'    => 'warning',
									'icon'    => 'alert',
									'message' => ' ',
								),
								true
							);
							?>
						</div>
					</div>
				<?php endif; ?>

				<div class="purchase-rows">
					<?php if ( 'subscribe' === $course_pricing['type'] && ! empty( $course_pricing['trial_interval'] ) && ! empty( $course_pricing['trial_frequency'] ) ) : ?>
						<div class="purchase-row">
							<span class="purchase-label">
								<?php esc_html_e( 'Trial', 'learndash' ); ?>
							</span>

							<span class="purchase-field-price">
								<?php echo esc_html( learndash_get_price_formatted( $course_pricing['trial_price'] ? $course_pricing['trial_price'] : 0 ) ); ?>

								<?php echo esc_html__( ' for ', 'learndash' ) . absint( $course_pricing['trial_interval'] ) . ' ' . esc_html( $course_pricing['trial_frequency'] ); ?>
							</span>
						</div>
					<?php endif; ?>

					<div class="purchase-row" id="price-row">
						<span class="purchase-label">
							<?php esc_html_e( 'Price', 'learndash' ); ?>
						</span>

						<span class="purchase-value">
							<?php
							echo esc_html(
								( 'free' === $course_pricing['type'] || 'open' === $course_pricing['type'] )
									? __( 'Free', 'learndash' )
									: learndash_get_price_formatted( $course_pricing['price'] )
							);

							if ( ! empty( $course_pricing['interval'] ) ) {
								echo esc_html__( ' every ', 'learndash' ) . absint( $course_pricing['interval'] ) . ' ' . esc_html( $course_pricing['frequency'] );

								if ( ! empty( $course_pricing['repeats'] ) ) {
									echo esc_html__( ' for ', 'learndash' ) . absint( $course_pricing['interval'] ) * absint( $course_pricing['repeats'] ) . ' ' . esc_html( $course_pricing['repeat_frequency'] );
								}
							}
							?>
						</span>
					</div>
				</div>

				<?php if ( 'paynow' === $course_pricing['type'] && is_user_logged_in() ) : ?>
					<?php if ( learndash_active_coupons_exist() && $product->can_be_purchased() ) : ?>
						<form
							class="coupon-form"
							id="apply-coupon-form"
							data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-coupon-nonce' ) ); ?>"
							data-post-id="<?php echo esc_attr( (string) $register_id ); ?>"
						>
							<input type="text" id="coupon-field" placeholder="<?php esc_html_e( 'Coupon', 'learndash' ); ?>" />
							<input type="submit" value="<?php esc_html_e( 'Apply Coupon', 'learndash' ); ?>" />
						</form>
					<?php endif; ?>

					<div class="totals" id="totals" style="display: <?php echo ! empty( $attached_coupon_dto ) ? 'block' : 'none'; ?>">
						<span class="order-heading">
							<?php esc_html_e( 'Totals', 'learndash' ); ?>
						</span>

						<div class="purchase-rows">
							<div class="purchase-row" id="subtotal-row">
								<span class="purchase-label">
									<?php esc_html_e( 'Subtotal', 'learndash' ); ?>
								</span>
								<span class="purchase-value">
									<?php echo esc_html( learndash_get_price_formatted( $course_pricing['price'] ) ); ?>
								</span>
							</div>

							<div
								class="purchase-row"
								id="coupon-row"
								style="<?php echo esc_attr( empty( $attached_coupon_dto ) ? 'display: none' : '' ); ?>"
							>
								<span class="purchase-label">
									<?php esc_html_e( 'Coupon: ', 'learndash' ); ?>
									<span>
										<?php
										if ( ! empty( $attached_coupon_dto ) ) {
											echo esc_html( $attached_coupon_dto->code );
										}
										?>
									</span>
								</span>
								<span class="purchase-value">
									<form
										id="remove-coupon-form"
										data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-coupon-nonce' ) ); ?>"
										data-post-id="<?php echo esc_attr( (string) $register_id ); ?>"
									>
										<span>
											<?php
											if ( ! empty( $attached_coupon_dto ) ) {
												echo esc_html( learndash_get_price_formatted( $attached_coupon_dto->discount ) );
											}
											?>
										</span>
										<input type="submit" class="button-small" value="<?php esc_html_e( 'Remove', 'learndash' ); ?>" />
									</form>
								</span>
							</div>

							<?php
							$total = (string) $product->get_final_price();
							?>

							<div
								class="purchase-row"
								data-supports-coupon="<?php echo esc_attr( (string) $product->supports_coupon() ); ?>"
								data-total="<?php echo esc_attr( $total ); ?>"
								id="total-row"
							>
								<span class="purchase-label">
									<?php esc_html_e( 'Total', 'learndash' ); ?>
								</span>
								<span class="purchase-value">
									<?php
									echo esc_html( learndash_get_price_formatted( $total ) );
									?>
								</span>
							</div>
						</div>
					</div>
				<?php endif; ?>

				<?php
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( isset( $_GET['ld-registered'] ) || is_user_logged_in() ) {
					echo learndash_payment_buttons( $register_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}

				// translators: placeholder: Return to Course/Group.
				echo '<span class="order-overview-return">' . sprintf( esc_html_x( 'Return to %s', 'placeholder: Return to Course/Group.', 'learndash' ), '<a href="' . esc_html( get_permalink( absint( $_GET['ld_register_id'] ) ) ) . '">' . esc_html( get_the_title( absint( $_GET['ld_register_id'] ) ) ) . '</a></p>' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
			?>
		</div>
	<?php endif; ?>

	<?php
	$registration_page_id = (int) LearnDash_Settings_Section::get_section_setting(
		'LearnDash_Settings_Section_Registration_Pages',
		'registration'
	);

	$preview_show = Cast::to_string( SuperGlobals::get_var( [ 'attributes', 'preview_show' ], '' ) );

	if (
		'true' === sanitize_text_field( $preview_show ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		|| ! is_user_logged_in()
	) {
		$register_id   = absint( SuperGlobals::get_get_var( 'ld_register_id', 0 ) );
		$registered_id = '?ld_register_id=' . $register_id;
		$checkout_page = get_permalink( $registration_page_id ) . ( ! empty( $register_id ) ? $registered_id : '' );

		/**
		 * Filters login link on registration form.
		 *
		 * @since 4.4.0
		 *
		 * @param string $registration_login_link_redirect The location to redirect the login link to
		 */
		$registration_login_link_redirect = apply_filters( 'learndash_registration_login_link_redirect', '' );

		/**
		 * Url to redirect to after logging in through registration form login form. Notice this runs through the wp_safe_redirect function ( https://developer.wordpress.org/reference/functions/wp_safe_redirect/ )
		 *
		 * @since 4.4.0
		 *
		 * @param string $registration_login_form_redirect The location the user is redirected to after logging into their account
		 */
		$registration_login_form_redirect = apply_filters( 'learndash_registration_login_form_redirect', '' );

		// translators: placeholder: Message above registration form if user logged out.
		echo '<p class="registration-login">' . sprintf( esc_html_x( 'Already have an account? %1$s', 'placeholder: Message above registration form if user logged out.', 'learndash' ), '<a class="registration-login-link" href="' . esc_attr( $registration_login_link_redirect ) . '">' . esc_html__( 'Log In', 'learndash' ) . '</a>' ) . '</p>';

		learndash_login_failed_alert();

		echo '<div class="registration-login-form" style="display: none;">' . wp_login_form(
			array(
				'echo'     => false,
				'redirect' => ! empty( $registration_login_form_redirect ) ? $registration_login_form_redirect : $checkout_page,
			)
		) . '</div>';

		if ( learndash_reset_password_is_enabled() ) {
			// translators: placeholder: Forgot password link below login form.
			echo '<p class="show-password-reset-link" style="display: none;">' . sprintf( esc_html_x( 'Forgot password? %s', 'placeholder: Forgot password link below login form.', 'learndash' ), '<a href="' . esc_attr( Cast::to_string( get_permalink( learndash_get_reset_password_page_id() ) ) ) . '">' . esc_html__( 'Click here to reset it.', 'learndash' ) . '</a>' ) . '</p>';
		}

		if ( $learndash_can_register ) :
			echo '<p class="show-register-form" style="display: none;"><a href="">' . esc_html__( 'Show registration form', 'learndash' ) . '</a></p>';

			if ( has_action( 'learndash_registration_form_override' ) ) {
				/**
				* Allow for replacement of the default LearnDash Registration form
				*
				* @since 3.6.0
				*/
				do_action( 'learndash_registration_form_override' );
			} else {
				/**
				* Fires before the registration form heading.
				*
				* @since 3.6.0
				*/
				do_action( 'learndash_registration_form_before' );
				if ( is_multisite() ) {
					$learndash_register_action_url = network_site_url( 'wp-signup.php' );
					$learndash_field_name_login    = 'user_name';
					$learndash_field_name_email    = 'user_email';
				} else {
					$learndash_register_action_url = site_url( 'wp-login.php?action=register', 'login_post' );
					$learndash_field_name_login    = 'user_login';
					$learndash_field_name_email    = 'user_email';
				}

				$learndash_errors = array(
					'has_errors' => false,
					'message'    => '',
				);

				foreach ( $learndash_errors_conditions as $learndash_param => $learndash_message ) {
					if ( isset( $_GET[ $learndash_param ] ) ) {
						$learndash_errors['has_errors'] = true;
						if ( ! empty( $learndash_errors['message'] ) ) {
							$learndash_errors['message'] .= '<br />';
						}
						$learndash_errors['message'] .= $learndash_message;
					}
				}

				if ( $learndash_errors['has_errors'] ) :
					learndash_get_template_part(
						'modules/alert.php',
						array(
							'type'    => 'warning',
							'icon'    => 'alert',
							'message' => $learndash_errors['message'],
						),
						true
					);

						/**
						 * Fires after the register modal errors.
						 *
						 * @since 3.6.0
						 *
						 * @param array $errors An array of error details.
						 */
						do_action( 'learndash_registration_errors_after', $learndash_errors );

				endif;
				?>
				<form name="learndash_registerform" id="learndash_registerform" class="ldregister" action="<?php echo esc_url( $learndash_register_action_url ); ?>" method="post">
				<?php
				/**
				 * Fires before the loop when displaying the registration form fields
				 *
				 * @since 3.6.0
				 */
				do_action( 'learndash_registration_form_fields_before' );

				$learndash_registration_fields = LearnDash_Settings_Section_Registration_Fields::get_section_settings_all();
				$learndash_fields_order        = $learndash_registration_fields['fields_order'];

				foreach ( $learndash_fields_order as $learndash_field ) {
					$learndash_required = ( 'yes' === $learndash_registration_fields[ $learndash_field . '_required' ] ) ? 'aria-required="true"' : '';
					if ( 'username' === $learndash_field ) {
						$learndash_name_field = $learndash_field_name_login;
					} elseif ( 'email' === $learndash_field ) {
						$learndash_name_field = $learndash_field_name_email;
					} else {
						$learndash_name_field = $learndash_field;
					}
					if ( 'yes' === $learndash_registration_fields[ $learndash_field . '_enabled' ] ) {
						echo '<p class="learndash-registration-field learndash-registration-field-' . esc_attr( $learndash_field ) . ' ' . ( ! empty( $learndash_required ) ? 'learndash-required' : '' ) . '"><label for="' . esc_attr( $learndash_field ) . '">' . esc_html( $learndash_registration_fields[ $learndash_field . '_label' ] ) . ( ! empty( $learndash_required ) ? ' <span class="learndash-required-field">*</span>' : '' ) . '</label>
						<input ' . esc_attr( $learndash_required ) . ' type="' . ( 'password' === $learndash_field ? 'password' : 'text' ) . '" id="' . esc_attr( $learndash_field ) . '" name="' . esc_attr( $learndash_name_field ) . '" value="' . esc_attr( sanitize_text_field( Cast::to_string( SuperGlobals::get_get_var( $learndash_name_field, '' ) ) ) ) . '" /></p>';
						if ( 'password' === $learndash_field ) {
							echo '<p class="learndash-registration-field learndash-registration-field-confirm' . esc_attr( $learndash_field ) . ' ' . ( ! empty( $learndash_required ) ? 'learndash-required' : '' ) . '"><label for="confirm_password">' . esc_html__( 'Confirm Password', 'learndash' ) . ( ! empty( $learndash_required ) ? ' <span class="learndash-required-field">*</span>' : '' ) . '</label><input ' . esc_attr( $learndash_required ) . ' type="password" id="confirm_password" name="confirm_password" /></p>';
						}
					}
				}

				/**
				 * Fires after the loop when displaying the registration form fields
				 *
				 * @since 3.6.0
				 */
				do_action( 'learndash_registration_form_fields_after' );

				$register_id               = absint( SuperGlobals::get_var( 'ld_register_id', 0 ) );
				$learndash_redirect_to_url = remove_query_arg(
					array_keys( $learndash_errors_conditions ), // @phpstan-ignore-line -- It's string[].
					get_permalink()
				);

				if ( ! is_multisite() ) {
					$learndash_redirect_to_url = add_query_arg(
						array(
							'ld-registered'  => 'true',
							'ld_register_id' => $register_id,
						),
						$learndash_redirect_to_url
					);
				}

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
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( $learndash_redirect_to_url ); ?>" />
				<p><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Register', 'learndash' ); ?>" /></p>
			</form>
				<?php
				/**
				 * Fires after the registration form heading.
				 *
				 * @since 3.6.0
				 */
				do_action( 'learndash_registration_form_after' );
			}
		endif;
	} elseif ( ! isset( $_GET['ld-registered'] ) && ! isset( $_GET['ld_register_id'] ) ) {
		$current_user = wp_get_current_user();
		printf(
		// translators: placeholders: Current Logged In Username, WP Logout Link.
			esc_html_x( 'Hello %1$s, looks like you\'re already logged in. Want to sign in as a different user? %2$s', 'placeholder: Current Logged In Username, WP Logout Link.', 'learndash' ),
			esc_html( $current_user->user_login ),
			'<a href="' . esc_url( wp_logout_url( get_permalink( $registration_page_id ) ) ) . '">' . esc_html__( 'Log Out', 'learndash' ) . '</a>'
		);
	}

	echo '</div></div>';

	learndash_registerform_password_strength_data();
}

/**
 * LearnDash LD30 Shows registration form for user registration.
 *
 * @since 4.16.0
 *
 * @param array<string, mixed> $attr Array of attributes for shortcode.
 *
 * @return void
 */
function learndash_registration_output_modern( array $attr = [] ): void {
	$attr_defaults = [
		'width' => 0,
	];

	$attr                 = shortcode_atts( $attr_defaults, $attr );
	$active_template_key  = \LearnDash_Theme_Register::get_active_theme_key();
	$wrapper_class        = ( 'ld30' === $active_template_key ) ? esc_attr( learndash_get_wrapper_class() ) : 'learndash-wrapper';
	$ld_registered        = SuperGlobals::get_get_var( 'ld-registered', null );
	$register_id          = absint( SuperGlobals::get_get_var( 'ld_register_id', 0 ) );
	$preview_show         = SuperGlobals::get_var( [ 'attributes', 'preview_show' ], null );
	$current_user         = wp_get_current_user();
	$current_user_id      = get_current_user_id();
	$is_user_logged_in    = is_user_logged_in();
	$registration_page_id = (int) \LearnDash_Settings_Section::get_section_setting(
		'LearnDash_Settings_Section_Registration_Pages',
		'registration'
	);

	if ( is_multisite() ) {
		$learndash_can_register = users_can_register_signup_filter();
	} else {
		$learndash_can_register = get_option( 'users_can_register' );
	}

	// Set some default values to pass to the template. They get overwritten
	// contextually below.
	$product                        = null;
	$course_pricing                 = [];
	$attached_coupon_dto            = [];
	$has_access_already             = false;
	$checkout_page                  = null;
	$registration_errors            = [];
	$login_link_redirect            = '';
	$wp_login_form_html             = '';
	$error_conditions               = [];
	$register_action_url            = '';
	$field_name_login               = '';
	$field_name_email               = '';
	$has_registration_form_override = has_action( 'learndash_registration_form_override' );
	$registration_fields_order      = [];
	$registration_fields            = [];
	$registration_redirect_to_url   = '';

	if (
		$register_id
		&& $register_id > 0
	) {
		$post_type = get_post_type( $register_id );

		/**
		 * Product object.
		 *
		 * @var Product|null $product
		 */
		$product = Product::find( $register_id );

		if ( LDLMS_Post_Types::get_post_type_slug( 'course' ) === $post_type ) {
			$course_pricing = learndash_get_course_price( $register_id );
		} elseif ( learndash_get_post_type_slug( 'group' ) === $post_type ) {
			$course_pricing = learndash_get_group_price( $register_id );
		} else {
			esc_html_e( 'Invalid Course or Group', 'learndash' );
			return;
		}

		if ( ! $product ) {
			esc_html_e( 'Invalid product', 'learndash' );
			return;
		}

		$course_pricing['price'] = learndash_get_price_as_float( $course_pricing['price'] );

		if ( ! empty( $course_pricing['trial_price'] ) ) {
			$course_pricing['trial_price'] = learndash_get_price_as_float( $course_pricing['trial_price'] );
		}

		$current_user_id   = get_current_user_id();
		$is_user_logged_in = is_user_logged_in();

		$attached_coupon_dto = null;
		if (
			$is_user_logged_in
			&& learndash_post_has_attached_coupon( $register_id, $current_user_id )
		) {
			$attached_coupon_dto = learndash_get_attached_coupon_data( $register_id, $current_user_id );
		}

		$current_user_id    = get_current_user_id();
		$is_user_logged_in  = is_user_logged_in();
		$has_access_already = $product->user_has_access( $current_user_id );
	}

	if (
		$preview_show === 'true'
		|| ! $is_user_logged_in
	) {
		$checkout_page = get_permalink( $registration_page_id );

		if ( $register_id ) {
			$checkout_page = add_query_arg( 'ld_register_id', $register_id, $checkout_page );
		}

		/**
		 * Filters login link on registration form.
		 *
		 * @since 4.4.0
		 *
		 * @param string $login_link_redirect The location to redirect the login link to
		 */
		$login_link_redirect = Cast::to_string( apply_filters( 'learndash_registration_login_link_redirect', '' ) );

		$wp_login_form_html = learndash_get_login_form_html( Cast::to_string( $checkout_page ) );

		if (
			$learndash_can_register
			&& ! $has_registration_form_override
		) {
			$error_conditions = learndash_login_error_conditions();

			if ( is_multisite() ) {
				$register_action_url = network_site_url( 'wp-signup.php' );
				$field_name_login    = 'user_name';
				$field_name_email    = 'user_email';
			} else {
				$register_action_url = site_url( 'wp-login.php?action=register', 'login_post' );
				$field_name_login    = 'user_login';
				$field_name_email    = 'user_email';
			}

			$registration_errors = array(
				'has_errors' => false,
				'message'    => '',
			);

			$error_conditions_keys = [];

			foreach ( $error_conditions as $learndash_param => $learndash_message ) {
				$param_value = SuperGlobals::get_get_var( (string) $learndash_param, null );

				if ( $param_value !== null ) {
					$registration_errors['has_errors'] = true;
					if ( ! empty( $registration_errors['message'] ) ) {
						$registration_errors['message'] .= '<br />';
					}
					$registration_errors['message'] .= $learndash_message;
				}

				if ( ! is_string( $learndash_param ) ) {
					continue;
				}

				$error_conditions_keys[] = $learndash_param;

				$registration_fields       = LearnDash_Settings_Section_Registration_Fields::get_section_settings_all();
				$registration_fields_order = $registration_fields['fields_order'];

				$registration_redirect_to_url = remove_query_arg(
					$error_conditions_keys,
					get_permalink()
				);

				if ( ! is_multisite() ) {
					$registration_redirect_to_url = add_query_arg(
						array(
							'ld-registered'  => 'true',
							'ld_register_id' => $register_id,
						),
						$registration_redirect_to_url
					);
				}
			}
		}
	}

	Assets::instance()->enqueue_group( 'learndash-registration' );
	wp_enqueue_style( 'dashicons' );

	$total = $product ? (string) $product->get_final_price() : '';

	Template::show_template(
		'modules/registration/registration',
		[
			'active_template_key'            => $active_template_key,
			'attached_coupon_dto'            => $attached_coupon_dto,
			'is_registration_enabled'        => $learndash_can_register,
			'checkout_page'                  => $checkout_page,
			'course_pricing'                 => $course_pricing,
			'current_user'                   => $current_user,
			'current_user_id'                => $current_user_id,
			'error_conditions'               => $error_conditions,
			'field_name_email'               => $field_name_email,
			'field_name_login'               => $field_name_login,
			'has_access_already'             => $has_access_already,
			'has_registration_form_override' => $has_registration_form_override,
			'is_registered'                  => $ld_registered === 'true',
			'is_user_logged_in'              => $is_user_logged_in,
			'form_width'                     => $attr['width'],
			'ld_registered'                  => $ld_registered,
			'wp_login_form_html'             => $wp_login_form_html,
			'login_link_redirect'            => $login_link_redirect,
			'price'                          => $product ? $product->get_display_price() : '',
			'preview_show'                   => $preview_show,
			'product'                        => $product,
			'register_action_url'            => $register_action_url,
			'register_id'                    => $register_id,
			'registration_errors'            => $registration_errors,
			'registration_fields_order'      => $registration_fields_order,
			'registration_fields'            => $registration_fields,
			'registration_redirect_to_url'   => $registration_redirect_to_url,
			'registration_page_id'           => $registration_page_id,
			'trial_price'                    => $product ? $product->get_display_trial_price() : '',
			'wrapper_class'                  => $wrapper_class,
			'total'                          => $total,
			'terms_settings'                 => Terms_Privacy_Agreement::get_settings(),
			'is_terms_enabled'               => Terms_Privacy_Agreement::is_terms_enabled(),
			'is_privacy_enabled'             => Terms_Privacy_Agreement::is_privacy_enabled(),
		]
	);

	learndash_registerform_password_strength_data();
}

/**
 * Returns the login form HTML.
 *
 * @since 4.16.0
 *
 * @param string $redirect_url URL to redirect to after logging in.
 *
 * @return string
 */
function learndash_get_login_form_html( string $redirect_url = '' ): string {
	/**
	 * Url to redirect to after logging in through registration form login form. Notice this runs through the wp_safe_redirect function ( https://developer.wordpress.org/reference/functions/wp_safe_redirect/ )
	 *
	 * @since 4.4.0
	 *
	 * @param string $login_form_redirect The location the user is redirected to after logging into their account
	 */
	$login_form_redirect = Cast::to_string( apply_filters( 'learndash_registration_login_form_redirect', $redirect_url ) );

	$reset_password_is_enabled = learndash_reset_password_is_enabled();
	$wp_login_form_html        = wp_login_form(
		[
			'echo'           => false,
			'redirect'       => $login_form_redirect,
			'label_username' => esc_html__( 'Username or Email Address *', 'learndash' ),
			'label_password' => esc_html__( 'Password *', 'learndash' ),
		]
	);

	if ( $reset_password_is_enabled ) {
		ob_start();
		?>
		<p class="ld-registration__forgot-password">
			<a href="<?php echo esc_attr( Cast::to_string( get_permalink( learndash_get_reset_password_page_id() ) ) ); ?>">
				<?php echo esc_html_x( 'Forgot password?', 'placeholder: Forgot password link below login form.', 'learndash' ); ?>
			</a>
		</p>
		<?php
		/**
		 * Filters the forgot password HTML.
		 *
		 * @since 4.16.0
		 *
		 * @param string $forgot_password_html Forgot password HTML.
		 *
		 * @return string
		 */
		$forgot_password_html = Cast::to_string( apply_filters( 'learndash_registration_forgot_password_html', (string) ob_get_clean() ) );

		$wp_login_form_html = Cast::to_string( preg_replace( '!<p class="login-submit">!', $forgot_password_html . "\n" . '<p class="login-submit">', $wp_login_form_html ) );

		if ( preg_match( '!<p class="login-remember">!', $wp_login_form_html ) ) {
			$wp_login_form_html = Cast::to_string( preg_replace( '!<p class="login-remember">!', '<div class="ld-registration__login_options_wrapper"><p class="login-remember">', $wp_login_form_html ) );
			$wp_login_form_html = Cast::to_string( preg_replace( '!<p class="login-submit">!', '</div><p class="login-submit">', $wp_login_form_html ) );
		}
	}

	$wp_login_form_html = Cast::to_string( preg_replace( '!<form name!', '<form class="ld-form" name', $wp_login_form_html ) );
	$wp_login_form_html = Cast::to_string( preg_replace( '!class="input"!', 'class="input ld-form__field"', $wp_login_form_html ) );
	$wp_login_form_html = Cast::to_string( preg_replace( '!class="button button-primary"!', 'class="button button-primary wp-element-button"', $wp_login_form_html ) );

	/**
	 * Filters the WP login form HTML.
	 *
	 * @since 4.16.0
	 *
	 * @param string $wp_login_form_html WP login form HTML.
	 *
	 * @return string
	 */
	return Cast::to_string( apply_filters( 'learndash_registration_wp_login_form_html', $wp_login_form_html ) );
}

/**
 * Displays a success message after a user successfully registers.
 *
 * @since 4.16.0
 *
 * @return void
 */
function learndash_output_registration_success_alert() {
	/**
	 * Fires before the register success alert.
	 *
	 * @since 4.16.0
	 */
	do_action( 'learndash_registration_successful_before' );

	learndash_get_template_part(
		'modules/alert.php',
		[
			'icon'    => 'alert',
			'message' => __( 'Registration successful.', 'learndash' ),
			'role'    => 'alert',
			'type'    => 'success',
		],
		true
	);

	/**
	 * Fires after the register success alert.
	 *
	 * @since 3.6.0
	 */
	do_action( 'learndash_registration_successful_after' );
}

/**
 * Returns the appropriate login URL based on the active LearnDash theme and settings.
 *
 * For sites using the LD30 theme with Login & Registration enabled, it returns '#login'
 * and loads the login modal HTML.
 * Otherwise, it returns the WordPress login URL with a redirect back to the current page.
 *
 * @since 3.6.0
 * @since 4.21.0 Added the `learndash_login_url` filter.
 *
 * @return string Login URL - Either '#login' for modal or WordPress login page URL.
 */
function learndash_get_login_url() {
	$active_template_key = LearnDash_Theme_Register::get_active_theme_key();
	$login_mode_enabled  = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'login_mode_enabled' );

	if (
		'ld30' === $active_template_key
		&& 'yes' === $login_mode_enabled
	) {
		learndash_load_login_modal_html();
		$learndash_login_url = '#login';
	} else {
		$learndash_login_url = wp_login_url( get_permalink( get_the_ID() ) );
	}

	/** This filter is documented in themes/ld30/includes/shortcodes.php. */
	return apply_filters( 'learndash_login_url', $learndash_login_url, 'login', [] );
}

/**
 * Checks whether the New User Registration email is enabled or not
 *
 * @since 3.6.0
 *
 * @return boolean True if option is enabled
 */
function learndash_new_user_email_enabled() {
	$enabled = LearnDash_Settings_Section_Emails_New_User_Registration::get_section_settings_all();
	if ( 'on' === $enabled['enabled'] ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Grabs email subject/message for the new user register email
 *
 * @since 3.6.0
 *
 * @param array  $wp_new_user_notification_email Email content for new user registration.
 * @param object $user WP_User Object.
 * @param string $blogname Title of the current site.
 *
 * @return array Array of email data to be sent
 */
function learndash_emails_content_new_user( $wp_new_user_notification_email = '', $user = '', $blogname = '' ) {
	$email_setting = LearnDash_Settings_Section_Emails_New_User_Registration::get_section_settings_all();
	if ( 'on' === $email_setting['enabled'] ) {
		$placeholders = array(
			'{user_login}'   => $user->user_login,
			'{first_name}'   => $user->user_firstname,
			'{last_name}'    => $user->user_lastname,
			'{display_name}' => $user->display_name,
			'{user_email}'   => $user->user_email,

			'{post_title}'   => isset( $_REQUEST['ld_register_id'] ) ? get_the_title( absint( $_REQUEST['ld_register_id'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended.
			'{post_url}'     => isset( $_REQUEST['ld_register_id'] ) ? get_permalink( absint( $_REQUEST['ld_register_id'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended.

			'{site_title}'   => $blogname,
			'{site_url}'     => wp_parse_url( home_url(), PHP_URL_HOST ),
		);
		/**
		 * Filters new registration email placeholders.
		 *
		 * @param array $placeholders Array of email placeholders and values.
		 * @param int   $user_id      User ID.
		 */
		$placeholders = apply_filters( 'learndash_registration_email_placeholders', $placeholders, $user->ID );

		/**
		 * Filters registration email subject.
		 *
		 * @param string $email_subject Email subject text.
		 * @param int    $user_id       User ID.
		 */
		$email_setting['subject'] = apply_filters( 'learndash_registration_email_subject', $email_setting['subject'], $user->ID );
		if ( ! empty( $email_setting['subject'] ) ) {
			$wp_new_user_notification_email['subject'] = learndash_emails_parse_placeholders( $email_setting['subject'], $placeholders );
		}

		/**
		 * Filters registration email message.
		 *
		 * @param string $email_message Email message text.
		 * @param int    $user_id       User ID.
		 */
		$email_setting['message'] = apply_filters( 'learndash_registration_email_message', $email_setting['message'], $user->ID );
		if ( ! empty( $email_setting['message'] ) ) {
			$email_setting['message'] = learndash_emails_parse_placeholders( $email_setting['message'], $placeholders );
			if ( 'text/html' === $email_setting['content_type'] ) {
				$email_setting['message'] = wpautop( stripcslashes( $email_setting['message'] ) );
			} else {
				$email_setting['message'] = esc_html( wp_strip_all_tags( wptexturize( $email_setting['message'] ) ) );
			}
			$wp_new_user_notification_email['message'] = $email_setting['message'];
		}

		if ( 'text/html' === $email_setting['content_type'] ) {
			$wp_new_user_notification_email['headers'] = 'Content-Type: ' . $email_setting['content_type'] . ' charset=' . get_option( 'blog_charset' );

			add_filter(
				'wp_mail_content_type',
				function () {
					return 'text/html';
				}
			);
		}
	}
	return $wp_new_user_notification_email;
}

/**
 * Validates that the password and confirm password fields match in the registration form
 *
 * @since 3.6.0
 *
 * @param WP_Error $errors A WP_Error object containing any errors encountered during registration.
 *
 * @return WP_Error
 */
function learndash_registration_form_validate( WP_Error $errors ) {
	if ( isset( $_POST['ld_register_id'] ) ) {
		if ( ( isset( $_POST['learndash-registration-form'] ) ) && ( wp_verify_nonce( $_POST['learndash-registration-form'], 'learndash-registration-form' ) ) ) {
			$learndash_registration_fields = LearnDash_Settings_Section_Registration_Fields::get_section_settings_all();

			$first_name = '';
			if ( isset( $_POST['first_name'] ) ) {
				$first_name = sanitize_text_field( $_POST['first_name'] );
			}
			if ( 'yes' === $learndash_registration_fields['first_name_enabled'] && 'yes' === $learndash_registration_fields['first_name_required'] && empty( $first_name ) ) {
				$errors->add( 'required_first_name', __( 'Registration requires a first name.', 'learndash' ) );
			}

			$last_name = '';
			if ( isset( $_POST['last_name'] ) ) {
				$last_name = sanitize_text_field( $_POST['last_name'] );
			}
			if ( 'yes' === $learndash_registration_fields['last_name_enabled'] && 'yes' === $learndash_registration_fields['last_name_required'] && empty( $last_name ) ) {
				$errors->add( 'required_last_name', __( 'Registration requires a last name.', 'learndash' ) );
			}

			$password           = '';
			$confirmed_password = '';
			if ( isset( $_POST['password'] ) ) {
				$password = $_POST['password'];
			}
			if ( 'yes' === $learndash_registration_fields['password_required'] && empty( $password ) ) {
				$errors->add( 'empty_password', __( 'Registration requires a password.', 'learndash' ) );
			}
			if ( isset( $_POST['confirm_password'] ) ) {
				$confirmed_password = $_POST['confirm_password'];
			}

			if (
				learndash_registration_variation() === LearnDash_Theme_Register_LD30::$variation_classic
				&& $password !== $confirmed_password
			) {
				$errors->add( 'confirm_password', __( 'Passwords do not match.', 'learndash' ) );
			}
		}
	}

	return $errors;
}
/** This filter is documented in https://developer.wordpress.org/reference/hooks/registration_errors/ */
add_filter( 'registration_errors', 'learndash_registration_form_validate' );

/**
 * Utility function to check the registration form course_id.
 *
 * @since 3.1.2
 *
 * @return int|false $course_id Valid course_id if valid otherwise false.
 */
function learndash_validation_registration_form_redirect_to() {
	if ( ( isset( $_POST['learndash-registration-form'] ) ) && ( wp_verify_nonce( $_POST['learndash-registration-form'], 'learndash-registration-form' ) ) || ( isset( $_POST['learndash-login-form'] ) ) && ( wp_verify_nonce( $_POST['learndash-login-form'], 'learndash-login-form' ) ) ) {
		if ( ( isset( $_POST['redirect_to'] ) ) && ( ! empty( $_POST['redirect_to'] ) ) ) {
			return esc_url_raw( $_POST['redirect_to'] );
		}
	}
	return false;
}

/**
 * Handles user registration failure.
 *
 * Fires on `register_post` hook.
 * From this function we capture the failed registration errors and send the user
 * back to the registration form part of the LD login modal.
 *
 * @since 3.1.1.1
 *
 * @param string $sanitized_user_login User entered login (sanitized).
 * @param string $user_email           User entered email.
 * @param array  $errors               Array of registration errors.
 */
function learndash_user_register_error( $sanitized_user_login, $user_email, $errors ) {
	$redirect_url = learndash_validation_registration_form_redirect_to();
	if ( $redirect_url ) {
		$redirect_url = remove_query_arg( 'ld-registered', $redirect_url );

		/** This filter is documented in https://developer.wordpress.org/reference/hooks/registration_errors/ */
		$errors = apply_filters( 'registration_errors', $errors, $sanitized_user_login, $user_email ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- It's a WP core filter.

		// This if check is copied from register_new_user function of wp-login.php.
		if ( ( $errors->has_errors() ) && ( $errors->get_error_code() ) ) {
			$has_errors = true;

			$learndash_registration_fields = LearnDash_Settings_Section_Registration_Fields::get_section_settings_all();
			$learndash_fields_order        = $learndash_registration_fields['fields_order'];

			if ( is_multisite() ) {
				$learndash_register_action_url        = network_site_url( 'wp-signup.php' );
				$learndash_learndash_field_name_login = 'user_name';
				$learndash_field_name_email           = 'user_email';
			} else {
				$learndash_register_action_url = site_url( 'wp-login.php?action=register', 'login_post' );
				$learndash_field_name_login    = 'user_login';
				$learndash_field_name_email    = 'user_email';
			}

			$field_array = array();
			foreach ( $learndash_fields_order as $learndash_field ) {
				if ( 'username' === $learndash_field ) {
					$learndash_name_field = $learndash_field_name_login;
				} elseif ( 'email' === $learndash_field ) {
					$learndash_name_field = $learndash_field_name_email;
				} else {
					$learndash_name_field = $learndash_field;
				}
				if ( 'yes' === $learndash_registration_fields[ $learndash_field . '_enabled' ] && 'password' !== $learndash_field ) {
					$learndash_field                      = sanitize_text_field( $_POST[ $learndash_name_field ] );
					$field_array[ $learndash_name_field ] = $learndash_field;
				}
			}

			$redirect_url = add_query_arg( $field_array, $redirect_url );

			// add error codes to custom redirection URL one by one.
			foreach ( $errors->errors as $e => $m ) {
				$redirect_url = add_query_arg( $e, '1', $redirect_url );
			}

			$login_mode_enabled      = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'login_mode_enabled' );
			$ld_registration_page_id = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Registration_Pages', 'registration' );

			// If we are NOT using our registration form...
			if ( ! isset( $_POST['ld_register_id'] ) ) {
				if ( 'yes' === $login_mode_enabled ) {
					// We add the '#login' hash.
					$redirect_url = learndash_add_login_hash( $redirect_url );
				}
			}

			/**
			 * Filters URL that a user should be redirected when there is an error while registration.
			 *
			 * @since 3.1.1.1
			 *
			 * @param string  $redirect_url The URL to be redirected when there are errors.
			 */
			$redirect_url = apply_filters( 'learndash_registration_error_url', $redirect_url );
			if ( ! empty( $redirect_url ) ) {
				// add finally, redirect to your custom page with all errors in attributes.
				learndash_safe_redirect( $redirect_url );
			}
		} elseif ( isset( $_POST['ld_register_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- legacy code.
			if ( empty( $_POST['ld_register_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- legacy code.
				// We set the 'redirect_to' only if there are not errors in the registration data.
				$ld_registration_success_id  = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Registration_Pages', 'registration_success' );
				$ld_registration_success_id  = absint( $ld_registration_success_id );
				$ld_registration_success_url = get_permalink( $ld_registration_success_id );
				if ( ! empty( $ld_registration_success_url ) ) {
					$_POST['redirect_to'] = $ld_registration_success_url;
				}
			}
		}
	}
}
add_action( 'register_post', 'learndash_user_register_error', 99, 3 );

/**
 * Updates user course data on user login.
 *
 * Fires on `authenticate` hook.
 *
 * @since 3.0.7
 *
 * @param WP_User $user     WP_User object if success. wp_error is error.
 * @param string  $username Login form entered user login.
 * @param string  $password Login form entered user password.
 *
 * @return WP_User|WP_Error Returns WP_User if a valid user object is passed.
 */
function learndash_authenticate( $user, $username, $password ) {
	if ( ( $user ) && ( is_a( $user, 'WP_User' ) ) ) {
		/**
		 * If the user started from a Course and registered then once they
		 * go through the password setup they will login. The login form
		 * could be the default WP login, the LD course modal or some other
		 * plugin. During the registration if the captured course ID is saved
		 * in the user meta we enroll that user into that course.
		 */
		$registered_post_id = get_user_meta( $user->ID, '_ld_registered_post', true );
		if ( '' !== $registered_post_id ) {
			delete_user_meta( $user->ID, '_ld_registered_post' );
		}
		$registered_post_id = absint( $registered_post_id );
		if ( ! empty( $registered_post_id ) ) {
			if ( in_array( get_post_type( $registered_post_id ), array( learndash_get_post_type_slug( 'course' ) ), true ) ) {
				ld_update_course_access( $user->ID, $registered_post_id );
			} elseif ( in_array( get_post_type( $registered_post_id ), array( learndash_get_post_type_slug( 'group' ) ), true ) ) {
				ld_update_group_access( $user->ID, $registered_post_id );
			}
		}

		/**
		 * If the user login is coming from a LD course then we enroll the
		 * user into the course. This helps save a step for the user.
		 */
		$login_post_id = learndash_validation_login_form_course();
		$login_post_id = absint( $login_post_id );
		if ( ! empty( $login_post_id ) ) {
			if ( in_array( get_post_type( $login_post_id ), array( learndash_get_post_type_slug( 'course' ) ), true ) ) {
				ld_update_course_access( $user->ID, $login_post_id );
			} elseif ( in_array( get_post_type( $login_post_id ), array( learndash_get_post_type_slug( 'group' ) ), true ) ) {
				ld_update_group_access( $user->ID, $login_post_id );
			}
		}
	} elseif ( ( is_wp_error( $user ) ) && ( $user->has_errors() ) ) {
		/**
		 * This is here instead of learndash_login_failed() because WP
		 * handles 'empty_username', 'empty_password' conditions different
		 * then invalid values.
		 *
		 * See logic in wp_authenticate()
		 */
		$redirect_to = learndash_validation_registration_form_redirect_to();
		if ( $redirect_to ) {
			$ignore_codes = array( 'empty_username', 'empty_password' );

			if ( is_wp_error( $user ) && in_array( $user->get_error_code(), $ignore_codes, true ) ) {
				$redirect_to = add_query_arg( 'login', 'failed', $redirect_to );
				$redirect_to = learndash_add_login_hash( $redirect_to );
				learndash_safe_redirect( $redirect_to );
			}
		}
	}

	return $user;
}
add_filter( 'authenticate', 'learndash_authenticate', 99, 3 );

/**
 * Handles the login fail scenario from WP.
 *
 * Fires on `wp_login_failed` hook.
 * Note for 'empty_username', 'empty_password' error conditions this action
 * will not be called. Those conditions are handled in learndash_authenticate()
 * if the user logged in via the LD modal.
 *
 * @since 3.0.0
 *
 * @param string $username Login name from login form process. Not used.
 */
function learndash_login_failed( $username = '' ) {
	$redirect_to = learndash_validation_registration_form_redirect_to();
	if ( $redirect_to ) {
		$redirect_to = add_query_arg( 'login', 'failed', $redirect_to );
		$redirect_to = learndash_add_login_hash( $redirect_to );
		learndash_safe_redirect( $redirect_to );
	}
}
add_action( 'wp_login_failed', 'learndash_login_failed', 1, 1 );

/**
 * Gets the login form course ID.
 *
 * @since 3.1.2
 *
 * @return int|false $course_id Valid course_id if valid otherwise false.
 */
function learndash_validation_login_form_course() {
	if ( ( isset( $_POST['learndash-login-form'] ) ) && ( wp_verify_nonce( $_POST['learndash-login-form'], 'learndash-login-form' ) ) ) {
		if ( ( isset( $_POST['learndash-login-form-post'] ) ) && ( ! empty( $_POST['learndash-login-form-post'] ) ) ) {
			$post_id = absint( $_POST['learndash-login-form-post'] );
			if ( ( isset( $_POST['learndash-login-form-post-nonce'] ) ) && ( wp_verify_nonce( $_POST['learndash-login-form-post-nonce'], 'learndash-login-form-post-' . $post_id . '-nonce' ) ) ) {
				if ( in_array( get_post_type( $post_id ), array( learndash_get_post_type_slug( 'course' ) ), true ) ) {
					/** This filter is documented in themes/ld30/includes/login-register-functions.php */
					if ( ( ! empty( $post_id ) ) && ( apply_filters( 'learndash_login_form_include_course', true, $post_id ) ) ) {
						return absint( $post_id );
					}
				} elseif ( in_array( get_post_type( $post_id ), array( learndash_get_post_type_slug( 'group' ) ), true ) ) {
					/** This filter is documented in themes/ld30/includes/login-register-functions.php */
					if ( ( ! empty( $post_id ) ) && ( apply_filters( 'learndash_login_form_include_group', true, $post_id ) ) ) {
						return absint( $post_id );
					}
				}
			}
		}
	}
	return false;
}

/**
 * Handles user registration success.
 *
 * Fires on `user_register` hook.
 * When the user registers it if was from a Course we capture that for later
 * when the user goes through the password set logic. After the password set
 * we can redirect the user to the course. See learndash_password_reset()
 * function.
 *
 * @since 3.1.2
 *
 * @param integer $user_id The Registers user ID.
 */
function learndash_register_user_success( $user_id = 0 ) {
	if ( ! empty( $user_id ) ) {
		if ( learndash_new_user_email_enabled() ) {
			add_filter( 'wp_new_user_notification_email', 'learndash_emails_content_new_user', 30, 3 );
			add_filter( 'wp_mail_from', 'learndash_emails_from_email' );
			add_filter( 'wp_mail_from_name', 'learndash_emails_from_name' );
		}
		$post_id = learndash_validation_registration_form_course();
		if ( isset( $_POST['ld_register_id'] ) ) {
			if ( isset( $_POST['first_name'] ) ) {
				$first_name = sanitize_text_field( $_POST['first_name'] );
				if ( ! empty( $first_name ) ) {
					update_user_meta( $user_id, 'first_name', $first_name );
				}
			}
			if ( isset( $_POST['last_name'] ) ) {
				$last_name = sanitize_text_field( $_POST['last_name'] );
				if ( ! empty( $last_name ) ) {
					update_user_meta( $user_id, 'last_name', $last_name );
				}
			}
			if ( isset( $_POST['password'] ) ) {
				$password           = $_POST['password'];
				$confirmed_password = $_POST['confirm_password'];
				if ( ! empty( $password ) && ! empty( $confirmed_password ) ) {
					wp_set_password( $password, $user_id );
				}
			}
			update_user_meta( $user_id, 'ld_register_form', time() );
		}
		if ( ! empty( $post_id ) ) {
			add_user_meta( $user_id, '_ld_registered_post', absint( $post_id ) );
		}

		if ( ( isset( $_POST['learndash-registration-form'] ) ) && ( wp_verify_nonce( $_POST['learndash-registration-form'], 'learndash-registration-form' ) ) && isset( $password ) ) {
			wp_set_current_user( $user_id );
			wp_set_auth_cookie( $user_id );
		}
	}
}
add_action( 'user_register', 'learndash_register_user_success', 10, 1 );

/**
 * Utility function to check and return the registration form course_id.
 *
 * @since 3.1.2
 *
 * @return int|false $course_id Valid course_id if valid otherwise false.
 */
function learndash_validation_registration_form_course() {
	if ( ( isset( $_POST['learndash-registration-form'] ) ) && ( wp_verify_nonce( $_POST['learndash-registration-form'], 'learndash-registration-form' ) ) ) {
		if ( ( isset( $_POST['learndash-registration-form-post'] ) ) && ( ! empty( $_POST['learndash-registration-form-post'] ) ) ) {
			$post_id = absint( $_POST['learndash-registration-form-post'] );
			if ( ! empty( $post_id ) ) {
				if ( ! in_array( get_post_type( $post_id ), array( learndash_get_post_type_slug( 'course' ) ), true ) ) {
					/**
					 * Filters whether to allow user registration from the course.
					 *
					 * @since 3.1.0
					 *
					 * @param boolean $include_course whether to allow user registration from the course.
					 * @param int     $post_id      Course ID.
					 */
					if ( ( ! empty( $post_id ) ) && ( apply_filters( 'learndash_registration_form_include_course', true, $post_id ) ) ) {
						if ( ( isset( $_POST['learndash-registration-form-post-nonce'] ) ) && ( wp_verify_nonce( $_POST['learndash-registration-form-post-nonce'], 'learndash-registration-form-post-' . $post_id . '-nonce' ) ) ) {
							return absint( $post_id );
						}
					}
				} elseif ( ! in_array( get_post_type( $post_id ), array( learndash_get_post_type_slug( 'group' ) ), true ) ) {
					/**
					 * Filters whether to allow user registration from the group.
					 *
					 * @since 3.2.0
					 *
					 * @param boolean $include_group whether to allow user registration from the group.
					 * @param int     $post_id      Course ID.
					 */
					if ( ( ! empty( $post_id ) ) && ( apply_filters( 'learndash_registration_form_include_group', true, $post_id ) ) ) {
						if ( ( isset( $_POST['learndash-registration-form-post-nonce'] ) ) && ( wp_verify_nonce( $_POST['learndash-registration-form-post-nonce'], 'learndash-registration-form-post-' . $post_id . '-nonce' ) ) ) {
							return absint( $post_id );
						}
					}
				}
			}
		}
	}
	return false;
}

/**
 * PASSWORD RESET FUNCTIONS
 */

/**
 * Variable to capture the user from the reset password. This var
 * is used in the learndash_password_reset_login_url() function to
 * redirect the user back to the origin.
 */
global $ld_password_reset_user;
$ld_password_reset_user = ''; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

/**
 * Handles password reset logic.
 *
 * Called after the user updates new password.
 *
 * @since 3.1.2
 *
 * @global WP_User $ld_password_reset_user Global password reset user.
 *
 * @param WP_User $user     WP_User object.
 * @param string  $new_pass New Password.
 */
function learndash_password_reset( $user, $new_pass ) {
	if ( $user ) {
		global $ld_password_reset_user;
		$ld_password_reset_user = $user; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

		add_filter( 'login_url', 'learndash_password_reset_login_url', 30, 3 );
	}
}
add_action( 'password_reset', 'learndash_password_reset', 30, 2 );

/**
 * Handles password reset logic.
 *
 * Fires on `login_url` hook.
 *
 * @since 3.1.2
 *
 * @global WP_User $ld_password_reset_user Global password reset user.
 *
 * @param string         $login_url    Current login_url.
 * @param string         $redirect     Query string redirect_to parameter and value.
 * @param boolean|string $force_reauth Whether to force re-authentication.
 *
 * @return string Returns login URL.
 */
function learndash_password_reset_login_url( $login_url = '', $redirect = '', $force_reauth = '' ) {
	global $ld_password_reset_user;

	if ( ( isset( $_GET['action'] ) ) && ( 'resetpass' === $_GET['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No nonces on public facing login forms
		if ( ( ! empty( $login_url ) ) && ( empty( $redirect ) ) ) {
			$user = $ld_password_reset_user;
			if ( ( $user ) && ( is_a( $user, 'WP_User' ) ) ) {
				$ld_login_url = get_user_meta( $user->ID, '_ld_lostpassword_redirect_to', true );
				delete_user_meta( $user->ID, '_ld_lostpassword_redirect_to' );
				if ( ! empty( $ld_login_url ) ) {
					$login_url = esc_url( $ld_login_url );
				} else {
					$registered_post_id = get_user_meta( $user->ID, '_ld_registered_post', true );

					if ( ! empty( $registered_post_id ) ) {
						$registered_post_url = get_permalink( $registered_post_id );
						$registered_post_url = learndash_add_login_hash( $registered_post_url );
						$login_url           = esc_url( $registered_post_url );
					}
				}
			}
		}
	}

	return $login_url;
}
/**
 * Stores the password reset redirect_to URL.
 *
 * Fires on `login_form_lostpassword` hook.
 *
 * When the user clicks the password reset on the LD login popup we capture the
 * 'redirect_to' URL. This is done at step 2 of the password reset process after
 * the user has enter their username/email.
 *
 * The user will then receive an email from WP with a link to reset the
 * password. Once the user has created a new password they will be shown a
 * login link. That login URL will be the stored 'redirect_to' user meta value.
 * See the function learndash_password_reset_login_url() for that stage of the
 * processing.
 *
 * @since 3.1.1.1
 */
function learndash_login_form_lostpassword() {
	if ( isset( $_POST['learndash-registration-form'], $_REQUEST['redirect_to'] ) &&
		wp_verify_nonce( $_POST['learndash-registration-form'], 'learndash-registration-form' ) &&
		! empty( $_REQUEST['redirect_to'] ) ) {
		$redirect_to = esc_url( $_REQUEST['redirect_to'] );

		// Only if the 'redirect_to' link contains our parameter.
		if ( false !== strpos( $redirect_to, 'ld-resetpw=true' ) ) { // cspell:disable-line.
			if ( isset( $_POST['user_login'] ) && is_string( $_POST['user_login'] ) ) {
				$user_login = wp_unslash( $_POST['user_login'] );
				$user       = get_user_by( 'login', $user_login );
				if ( ( $user ) && ( is_a( $user, 'WP_User' ) ) ) {
					/**
					 * We remove the 'ld-resetpw' part because we don't want to trigger // cspell:disable-line.
					 * the login modal showing the password has been reset again.
					 */
					$redirect_to = remove_query_arg( 'ld-resetpw', $redirect_to ); // cspell:disable-line.

					/**
					 * Store the redirect URL in user meta. This will be retrieved in
					 * the function learndash_password_reset_login_url().
					 */
					update_user_meta( $user->ID, '_ld_lostpassword_redirect_to', $redirect_to );
				}
			}
		}
	}
}
add_action( 'login_form_lostpassword', 'learndash_login_form_lostpassword', 30 );


/**
 * Adds '#login' to the end of a the login URL.
 *
 * Used throughout the LD30 login model and processing functions.
 *
 * @since 3.1.2
 *
 * @param string $url URL to check and append hash.
 *
 * @return string Returns URL after adding login hash.
 */
function learndash_add_login_hash( $url = '' ) {
	if ( strpos( $url, '#login' ) === false ) {
		$url .= '#login';
	}

	return $url;
}

/**
 * Gets an array of login error conditions.
 *
 * @since 3.1.2
 *
 * @param bool $return_keys True to return keys of conditions only. Default false.
 *
 * @return array<string|int,string> Returns an array of login error conditions.
 */
function learndash_login_error_conditions( bool $return_keys = false ): array {
	$registration_errors = array(
		'username_exists'     => __( 'Registration username exists.', 'learndash' ),
		'email_exists'        => __( 'Registration email exists.', 'learndash' ),
		'empty_username'      => __( 'Registration requires a username.', 'learndash' ),
		'empty_email'         => __( 'Registration requires a valid email.', 'learndash' ),
		'invalid_username'    => __( 'Invalid username.', 'learndash' ),
		'invalid_email'       => __( 'Invalid email.', 'learndash' ),
		'empty_password'      => __( 'Registration requires a password.', 'learndash' ),
		'confirm_password'    => __( 'Passwords do not match.', 'learndash' ),
		'required_first_name' => __( 'Registration requires a first name.', 'learndash' ),
		'required_last_name'  => __( 'Registration requires a last name', 'learndash' ),
	);

	/**
	 * Filters a list of user registration errors.
	 *
	 * @since 3.0.0
	 * @deprecated 4.5.0
	 *
	 * @param array<string,string> $registration_errors An array of registration errors and descriptions.
	 */
	$registration_errors = apply_filters_deprecated(
		'learndash-registration-errors',
		array( $registration_errors ),
		'4.5.0',
		'learndash_registration_errors'
	);

	/**
	 * Filters a list of user registration errors.
	 *
	 * @since 4.5.0
	 *
	 * @param array<string,string> $registration_errors An array of registration errors and descriptions.
	 */
	$registration_errors = apply_filters( 'learndash_registration_errors', $registration_errors );

	if ( true === $return_keys ) {
		return array_keys( $registration_errors );
	}

	return $registration_errors;
}

/**
 * Defines data for the password strength meter on registration form
 *
 * @since 3.6.1
 */
function learndash_registerform_password_strength_data() {
	wp_enqueue_script(
		'learndash-password-strength-meter',
		LEARNDASH_LMS_PLUGIN_URL . 'assets/js/learndash-password-strength-meter.js',
		array( 'jquery', 'password-strength-meter' ),
		LEARNDASH_VERSION,
		true
	);

	$params = array();

	/**
	 * Filters the minimum password strength for the registration form
	 *
	 * @since 3.6.1
	 *
	 * @param int $min_password_strength Minimum password strength value
	 */
	$params['min_password_strength'] = apply_filters( 'learndash_min_password_strength', 3 );

	/**
	 * Additional text to show user defining password strength
	 *
	 * @since 3.6.1
	 *
	 * @param string $password_strength_hint Text that displays next to password strength rating.
	 */
	$params['i18n_password_error'] = esc_attr__( 'Please enter a stronger password.', 'learndash' );

	/**
	 * Additional text displayed below the password strength rating section to explain further
	 *
	 * @since 3.6.1
	 *
	 * @param string Message to display to user with additional information to help choose a better password
	 */
	$params['i18n_password_hint'] = esc_attr__( 'Tip: Try at least 12 characters long containing letters, numbers, and special characters.', 'learndash' );

	/**
	 * Controls disabling registration form submission
	 *
	 * @since 3.6.1
	 *
	 * @param bool $prevent_registration Whether to prevent the registration form submission with a weak password strength. Default true.
	 */
	$params['stop_register'] = apply_filters( 'learndash_weak_password_stop_register', true );

	wp_localize_script( 'learndash-password-strength-meter', 'learndash_password_strength_meter_params', $params );
}

/**
 * Returns true if the password reset page is enabled.
 *
 * @since 4.4.0
 *
 * @return bool
 */
function learndash_reset_password_is_enabled(): bool {
	$reset_password_page_id = (int) LearnDash_Settings_Section::get_section_setting(
		'LearnDash_Settings_Section_Registration_Pages',
		'reset_password'
	);

	return $reset_password_page_id > 0;
}

/**
 * Returns the reset password page ID or 0 if not set.
 *
 * @since 4.4.0
 *
 * @return int
 */
function learndash_get_reset_password_page_id(): int {
	if ( ! learndash_reset_password_is_enabled() ) {
		return 0;
	}

	return (int) LearnDash_Settings_Section::get_section_setting(
		'LearnDash_Settings_Section_Registration_Pages',
		'reset_password'
	);
}

/**
 * LearnDash LD30 Shows reset password form
 *
 * @since 4.4.0
 *
 * @param array $attr Array of attributes for shortcode.
 *
 * @return void
 */
function learndash_reset_password_output( $attr = array() ): void {
	if ( learndash_registration_variation() === LearnDash_Theme_Register_LD30::$variation_modern ) {
		learndash_reset_password_output_modern( $attr );
		return;
	}

	$attr_defaults       = array( 'width' => 0 );
	$attr                = shortcode_atts( $attr_defaults, $attr );
	$form_width          = $attr['width'];
	$active_template_key = LearnDash_Theme_Register::get_active_theme_key();
	?>
<div class="<?php echo ( 'ld30' === $active_template_key ) ? esc_attr( learndash_the_wrapper_class() ) : 'learndash-wrapper'; ?>">
	<?php
	if ( isset( $_GET['action'] ) && 'rp' === $_GET['action'] ) {
		$key        = ( isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '' );
		$user       = ( isset( $_GET['login'] ) ? get_user_by( 'login', sanitize_text_field( wp_unslash( $_GET['login'] ) ) ) : '' );
		$key_verify = learndash_reset_password_verification( $user, $key );
		if ( 'WP_Error' === get_class( $key_verify ) ) {
			$status['message'] = esc_html__( 'Invalid key, please check your reset password link and try again.', 'learndash' );
			$status['type']    = 'warning';
			$status['action']  = 'prevent_reset';
		}
	}
	if (
		isset( $_POST['user_login'] )
		&& ! empty( $_POST['learndash-reset-password-form-nonce'] )
		&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['learndash-reset-password-form-nonce'] ) ), 'learndash-reset-password-form-nonce' )
		&& ! isset( $_POST['reset_password'] )
	) {
		$status = learndash_reset_password_email_send();
	}
	if (
		isset( $_POST['user_login'] )
		&& isset( $_POST['reset_password'] )
		&& ! empty( $_POST['learndash-reset-password-form-post-nonce'] )
		&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['learndash-reset-password-form-post-nonce'] ) ), 'learndash-reset-password-form-post-nonce' )
	) {
		$user = get_user_by( 'login', sanitize_text_field( wp_unslash( $_POST['user_login'] ) ) );

		if ( $user ) {
			$key = sanitize_text_field( wp_unslash( $_GET['key'] ?? '' ) );

			if ( learndash_reset_password_verification( $user, $key ) instanceof WP_Error ) {
				$status['message'] = esc_html__( 'Invalid user, please check your reset password link and try again.', 'learndash' );
				$status['type']    = 'warning';
			}
		}
	}
	if ( isset( $_GET['password_reset'] ) && 'true' === $_GET['password_reset'] && ! isset( $_POST['user_login'] ) && ! isset( $_GET['login'] ) ) {
		$status['message'] = esc_html__( 'Password reset, please log into your account.', 'learndash' );
		$status['type']    = 'success';
	}
	?>
	<div class="<?php echo ( 'ld30' === $active_template_key ) ? esc_attr( learndash_get_wrapper_class() ) : 'learndash-wrapper'; ?>">
		<div id="learndash-reset-password-wrapper" <?php echo ( ! empty( $form_width ) ) ? 'style="width: ' . esc_attr( $form_width ) . 'px;"' : ''; ?>>
			<?php
			if ( ! empty( $status ) ) {
				learndash_get_template_part(
					'modules/alert.php',
					array(
						'type'    => $status['type'],
						'icon'    => 'alert',
						'message' => $status['message'],
					),
					true
				);
			}

			learndash_login_failed_alert();

			if ( isset( $_GET['action'] ) && 'rp' === $_GET['action'] && ! isset( $status ) ) {
				?>
				<form action="" method="POST">
					<p>
						<label for="reset_password"><?php esc_html_e( 'Set new password', 'learndash' ); ?></label>
						<input type="password" name="reset_password" id="user_new_password" />
						<input type="hidden" name="user_login" id="user_login" value="<?php echo ( isset( $_GET['login'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['login'] ) ) ) : '' ); ?>" />
						<?php
						wp_nonce_field( 'learndash-reset-password-form-post-nonce', 'learndash-reset-password-form-post-nonce' );
						?>
					</p>
					<input type="submit" value="<?php esc_html_e( 'Reset Password', 'learndash' ); ?>"/>
				</form>
				<?php
			} elseif ( isset( $status['action'] ) && 'prevent_reset' === $status['action'] ) {
				// Password reset key is invalid here, don't allow them to reset the password and just show an error message.
				echo '';
			} elseif ( isset( $_GET['password_reset'] ) && 'true' === $_GET['password_reset'] ) {
				wp_login_form(
					[
						'redirect' => learndash_get_reset_password_success_page_id() > 0
							? (string) get_permalink( learndash_get_reset_password_success_page_id() )
							: home_url(),
					]
				);
			} else {
				?>
				<form action="" method="POST">
					<p>
						<label for="reset_password"><?php esc_html_e( 'Username or Email Address', 'learndash' ); ?></label>
						<input type="text" name="user_login" id="user_login" autocapitalize="off" autocomplete="off" />
						<?php
						wp_nonce_field( 'learndash-reset-password-form-nonce', 'learndash-reset-password-form-nonce' );
						?>
					</p>
					<input type="submit" value="<?php esc_html_e( 'Reset Password', 'learndash' ); ?>"/>
				</form>
				<?php
			}
			?>
		</div>
	</div>
	<?php
}

/**
 * LearnDash LD30 Shows reset password form.
 *
 * @since 4.16.0
 *
 * @param array<string, mixed> $attr Array of attributes for shortcode.
 *
 * @return void
 */
function learndash_reset_password_output_modern( array $attr = [] ): void {
	$attr_defaults       = [ 'width' => 0 ];
	$attr                = shortcode_atts( $attr_defaults, $attr );
	$form_width          = $attr['width'];
	$active_template_key = LearnDash_Theme_Register::get_active_theme_key();

	$wrapper_class        = ( 'ld30' === $active_template_key ) ? esc_attr( learndash_get_wrapper_class() ) : 'learndash-wrapper';
	$action               = SuperGlobals::get_get_var( 'action', null );
	$do_password_reset    = SuperGlobals::get_get_var( 'password_reset', null );
	$form_nonce           = Cast::to_string( SuperGlobals::get_post_var( 'learndash-reset-password-form-nonce', null ) );
	$form_post_nonce      = Cast::to_string( SuperGlobals::get_post_var( 'learndash-reset-password-form-post-nonce', null ) );
	$reset_password       = SuperGlobals::get_post_var( 'reset_password', null );
	$login                = Cast::to_string( SuperGlobals::get_var( 'login', null ) );
	$user                 = null;
	$submitted_user_login = Cast::to_string( SuperGlobals::get_post_var( 'user_login', null ) );
	$key                  = Cast::to_string( SuperGlobals::get_get_var( 'key', '' ) );
	$status               = [];
	$wp_login_form_html   = '';

	if ( $login ) {
		$user = get_user_by( 'login', $login );
	}

	if (
		$action === 'rp'
		&& $user instanceof WP_User
	) {
		$key_verify = learndash_reset_password_verification( $user, $key );

		if ( 'WP_Error' === get_class( $key_verify ) ) {
			$status['message'] = esc_html__( 'Invalid key, please check your reset password link and try again.', 'learndash' );
			$status['type']    = 'warning';
			$status['action']  = 'prevent_reset';
		}
	}

	if (
		$submitted_user_login
		&& ! empty( $form_nonce )
		&& wp_verify_nonce( $form_nonce, 'learndash-reset-password-form-nonce' )
		&& ! $reset_password
	) {
		$status = learndash_reset_password_email_send();
	}

	if (
		$submitted_user_login
		&& $reset_password
		&& ! empty( $form_post_nonce )
		&& wp_verify_nonce( $form_post_nonce, 'learndash-reset-password-form-post-nonce' )
		&& $user
		&& learndash_reset_password_verification( $user, $key ) instanceof WP_Error
	) {
		$status['message'] = esc_html__( 'Invalid user, please check your reset password link and try again.', 'learndash' );
		$status['type']    = 'warning';
	}

	if (
		$do_password_reset === 'true'
		&& ! $submitted_user_login
		&& $login
	) {
		$status['message'] = esc_html__( 'Password reset, please log into your account.', 'learndash' );
		$status['type']    = 'success';
	}

	if ( $do_password_reset === 'true' ) {
		$redirect_url = learndash_get_reset_password_success_page_id() > 0
			? (string) get_permalink( learndash_get_reset_password_success_page_id() )
			: home_url();

		$wp_login_form_html = learndash_get_login_form_html( $redirect_url );
	}

	Assets::instance()->enqueue_group( 'learndash-registration' );

	Template::show_template(
		'modules/registration/login/forgot-password.php',
		[
			'action'                 => $action,
			'do_password_reset'      => $do_password_reset,
			'form_nonce'             => $form_nonce,
			'form_post_nonce'        => $form_post_nonce,
			'form_width'             => $form_width,
			'reset_password'         => $reset_password,
			'show_set_password_form' => $action === 'rp' && ! $status,
			'status'                 => $status,
			'user_login'             => empty( $submitted_user_login ) ? $login : $submitted_user_login,
			'wp_login_form_html'     => $wp_login_form_html,
			'wrapper_class'          => $wrapper_class,
		]
	);

	learndash_registerform_password_strength_data();
}

/**
 * LearnDash Reset Password Email Send
 *
 * @since 4.4.0
 *
 * @return array $status Status type and message of email send success
 */
function learndash_reset_password_email_send(): array {
	$user_login = ! empty( $_POST['user_login'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		? trim( sanitize_text_field( wp_unslash( $_POST['user_login'] ) ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		: '';

	if ( strpos( $user_login, '@' ) ) {
		$user_data = get_user_by( 'email', $user_login );
	} else {
		$user_data = get_user_by( 'login', $user_login );
	}

	if ( ! $user_data ) {
		$status['message'] = esc_html__( 'If an account with that username or email address exists, an email has been sent with password reset instructions.', 'learndash' );
		$status['type']    = 'success';
		return $status;
	}

	$status['message'] = esc_html__( 'If an account with that username or email address exists, an email has been sent with password reset instructions.', 'learndash' );
	$status['type']    = 'success';
	wp_mail( $user_data->user_email, esc_html__( 'Password Reset', 'learndash' ), learndash_reset_password_email_message( $user_data ) );
	return $status;
}

/**
 * LearnDash Reset Password Email Message
 *
 * @since 4.4.0
 *
 * @param WP_User $user_data  WP_User object.
 *
 * @return string $message Content of reset password email message
 */
function learndash_reset_password_email_message( $user_data ): string {
	if ( is_multisite() ) {
		$site_name = get_network()->site_name;
	} else {
		/*
		 * The blogname option is escaped with esc_html on the way into the database
		 * in sanitize_option. We want to reverse this for the plain text arena of emails.
		 */
		$site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}
	$user_login         = $user_data->user_login;
	$reset_password_url = add_query_arg(
		array(
			'action' => 'rp',
			'key'    => get_password_reset_key( $user_data ),
			'login'  => rawurlencode( $user_login ),
		),
		get_permalink( learndash_get_reset_password_page_id() )
	);

	$message = esc_html__( 'Someone has requested a password reset for the following account:', 'learndash' ) . "\r\n\r\n";
	/* translators: %s: Site name. */
	$message .= sprintf( esc_html__( 'Site Name: %s', 'learndash' ), $site_name ) . "\r\n\r\n";
	/* translators: %s: User login. */
	$message .= sprintf( esc_html__( 'Username: %s', 'learndash' ), $user_login ) . "\r\n\r\n";
	$message .= esc_html__( 'If this was a mistake, ignore this email and nothing will happen.', 'learndash' ) . "\r\n\r\n";
	$message .= esc_html__( 'To reset your password, visit the following address:', 'learndash' ) . "\r\n\r\n";
	$message .= $reset_password_url . "\r\n\r\n";

	/**
	 * Filter the reset password email message.
	 *
	 * @since 4.4.0
	 *
	 * @param string $message Reset password email message content.
	 */
	return apply_filters( 'learndash_reset_password_email_message', $message );
}

/**
 * Reset password verification
 *
 * @since 4.4.0
 *
 * @param WP_User $user  WP_User object.
 * @param string  $key   Reset password activation key.
 *
 * @return WP_User|WP_Error WP_User object on success or WP_Error object on invalid/expired key.
 */
function learndash_reset_password_verification( $user, $key ) {
	return check_password_reset_key( $key, $user->user_login );
}

/**
 * Set new password for user from reset password process
 *
 * @since 4.4.0
 *
 * @param WP_User $user  WP_User object.
 * @param string  $new_password New password for user.
 *
 * @return void
 */
function learndash_reset_password_set_user_new_password( $user, $new_password ): void {
	reset_password( $user, $new_password );

	/**
	 * Fires after the user password has been updated
	 *
	 * @since 4.4.0
	 */
	do_action( 'learndash_reset_password_success' );

	$permalink = remove_query_arg( 'action', get_permalink() );
	$permalink = add_query_arg( 'password_reset', 'true', $permalink );

	learndash_safe_redirect( $permalink );
}

/**
 * Display alert message if user login fails.
 *
 * @since 4.4.0
 *
 * @return void
 */
function learndash_login_failed_alert(): void {
	$login_failed = ( isset( $_GET['login'] ) && 'failed' === $_GET['login'] ? true : false );
	if ( isset( $_GET['login'] ) && 'failed' === $_GET['login'] ) :
		echo '<div class="learndash-login-failed-alert">';
		learndash_get_template_part(
			'modules/alert.php',
			array(
				'type'    => 'warning',
				'icon'    => 'alert',
				'message' => __( 'Incorrect username or password. Please try again', 'learndash' ),
				'role'    => 'alert',
			),
			true
		);
		echo '</div>';
	endif;
}

/**
 * Returns true if the registration page is set, false otherwise.
 *
 * @since 4.4.0
 *
 * @return bool
 */
function learndash_registration_page_is_set(): bool {
	if (
		is_multisite()
		|| ! learndash_is_active_theme( 'ld30' )
		|| 'yes' !== LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'login_mode_enabled' )
	) {
		return false;
	}

	return learndash_registration_page_get_id() > 0;
}

if ( ! function_exists( 'learndash_registration_page_get_id' ) ) {
	/**
	 * Returns the registration page ID or 0.
	 *
	 * @since 4.5.0
	 *
	 * @return int
	 */
	function learndash_registration_page_get_id(): int {
		return (int) LearnDash_Settings_Section::get_section_setting(
			'LearnDash_Settings_Section_Registration_Pages',
			'registration'
		);
	}
}

/**
 * Builds the registration page URL for a given product ID.
 *
 * @since 4.21.0
 *
 * @param int $product_id Product ID.
 *
 * @return string Empty string if the registration page is not set, otherwise the URL.
 */
function learndash_registration_page_build_url( int $product_id ): string {
	$page_id = learndash_registration_page_get_id();

	if ( ! $page_id ) {
		return '';
	}

	return add_query_arg( 'ld_register_id', $product_id, get_permalink( $page_id ) );
}

/**
 * Returns the reset password success page ID or 0 if not set.
 *
 * @since 4.8.0
 *
 * @return int
 */
function learndash_get_reset_password_success_page_id(): int {
	$page_id = LearnDash_Settings_Section::get_section_setting(
		'LearnDash_Settings_Section_Registration_Pages',
		'reset_password_success'
	);

	return Cast::to_int( $page_id );
}

/**
 * Processes the password reset redirect.
 *
 * @since 4.12.0
 *
 * @return void
 */
function learndash_process_password_reset_redirect(): void {
	if (
		empty( $_GET['key'] )
		|| empty( $_POST['user_login'] )
		|| empty( $_POST['reset_password'] )
		|| empty( $_POST['learndash-reset-password-form-post-nonce'] )
		|| ! wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['learndash-reset-password-form-post-nonce'] ) ),
			'learndash-reset-password-form-post-nonce'
		)
	) {
		return;
	}

	$user = get_user_by(
		'login',
		sanitize_text_field( wp_unslash( $_POST['user_login'] ) )
	);

	if ( ! $user instanceof WP_User ) {
		return;
	}

	$key = sanitize_text_field( wp_unslash( $_GET['key'] ) );

	if ( learndash_reset_password_verification( $user, $key ) instanceof WP_Error ) {
		return;
	}

	$new_password = sanitize_text_field( wp_unslash( $_POST['reset_password'] ) );

	learndash_reset_password_set_user_new_password( $user, $new_password );
}

add_action( 'init', 'learndash_process_password_reset_redirect' );
