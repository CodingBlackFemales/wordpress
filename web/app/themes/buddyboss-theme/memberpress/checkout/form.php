<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
do_action( 'mepr-above-checkout-form', $product->ID ); ?>

<div class="mp_wrapper">
	<form class="mepr-signup-form mepr-form" method="post" action="<?php echo esc_url( $_SERVER['REQUEST_URI'] ) . '#mepr_jump'; ?>" enctype="multipart/form-data" novalidate>
		<input type="hidden" id="mepr_process_signup_form" name="mepr_process_signup_form" value="<?php echo esc_attr( 'Y' ); ?>"/>
		<input type="hidden" id="mepr_product_id" name="mepr_product_id" value="<?php echo esc_attr( $product->ID ); ?>"/>

		<?php
		if ( MeprUtils::is_user_logged_in() ) :
			?>
			<input type="hidden" name="logged_in_purchase" value="1"/>
			<?php
			wp_nonce_field( 'logged_in_purchase', 'mepr_checkout_nonce' );
		endif;

		if ( ( $product->register_price_action != 'hidden' ) && MeprHooks::apply_filters( 'mepr_checkout_show_terms', true, $product ) ) :
			?>
			<div class="mp-form-row mepr_bold mepr_price">
				<?php $price_label = ( $product->is_one_time_payment() ? _x( 'Price:', 'ui', 'buddyboss-theme' ) : _x( 'Terms:', 'ui', 'buddyboss-theme' ) ); ?>
				<label><?php echo esc_html( $price_label ); ?></label>
				<div class="mepr_price_cell">
					<?php MeprProductsHelper::display_invoice( $product, $mepr_coupon_code ); ?>
				</div>
			</div>
			<?php
		endif;
		?>

		<div class="bb-mp-checkout-details">
			<?php
			MeprHooks::do_action( 'mepr-checkout-before-name', $product->ID );
			if ( ( ! MeprUtils::is_user_logged_in() || ( MeprUtils::is_user_logged_in() && $mepr_options->show_fields_logged_in_purchases ) ) && $mepr_options->show_fname_lname ) :
				?>
				<div class="mp-form-row mepr_first_name">
					<div class="mp-form-label">
						<label>
						<?php
						echo esc_html_x( 'First Name:', 'ui', 'buddyboss-theme' );
							echo ( $mepr_options->require_fname_lname ) ? '*' : '';
						?>
							</label>
						<span class="cc-error"><?php echo esc_html_x( 'First Name Required', 'ui', 'buddyboss-theme' ); ?></span>
					</div>
					<input type="text" name="user_first_name" id="user_first_name" class="mepr-form-input" value="<?php echo esc_attr( $first_name_value ); ?>" <?php echo ( $mepr_options->require_fname_lname ) ? esc_attr( 'required' ) : ''; ?> />
				</div>
				<div class="mp-form-row mepr_last_name">
					<div class="mp-form-label">
						<label>
							<?php
							echo esc_html_x( 'Last Name:', 'ui', 'buddyboss-theme' );
							echo ( $mepr_options->require_fname_lname ) ? '*' : '';
							?>
						</label>
						<span class="cc-error"><?php echo esc_html_x( 'Last Name Required', 'ui', 'buddyboss-theme' ); ?></span>
					</div>
					<input type="text" name="user_last_name" id="user_last_name" class="mepr-form-input" value="<?php echo esc_attr( $last_name_value ); ?>" <?php echo ( $mepr_options->require_fname_lname ) ? esc_attr( 'required' ) : ''; ?> />
				</div>
				<?php
			else : /* this is here to avoid validation issues */
				?>
				<input type="hidden" name="user_first_name" id="user_first_name" value="<?php echo esc_attr( $first_name_value ); ?>"/>
				<input type="hidden" name="user_last_name" id="user_last_name" value="<?php echo esc_attr( $last_name_value ); ?>"/>
				<?php
			endif;

			if ( ! MeprUtils::is_user_logged_in() || ( MeprUtils::is_user_logged_in() && $mepr_options->show_fields_logged_in_purchases ) ) {
				MeprUsersHelper::render_custom_fields( $product, 'signup' );
			}

			if ( MeprUtils::is_user_logged_in() ) :
				?>
				<input type="hidden" name="user_email" id="user_email" value="<?php echo esc_attr( stripslashes( $mepr_current_user->user_email ) ); ?>"/>
				<?php
			else :
				?>
				<input type="hidden" class="mepr-geo-country" name="mepr-geo-country" value=""/>

				<?php
				if ( ! $mepr_options->username_is_email ) :
					?>
					<div class="mp-form-row mepr_username">
						<div class="mp-form-label">
							<label><?php echo esc_html_x( 'Username:*', 'ui', 'buddyboss-theme' ); ?></label>
							<span class="cc-error"><?php echo esc_html_x( 'Invalid Username', 'ui', 'buddyboss-theme' ); ?></span>
						</div>
						<input type="text" name="user_login" id="user_login" class="mepr-form-input" value="<?php echo ( isset( $user_login ) ) ? esc_attr( stripslashes( $user_login ) ) : ''; ?>" required/>
					</div>
					<?php
				endif;
				?>
				<div class="mp-form-row mepr_email">
					<div class="mp-form-label">
						<label><?php echo esc_html_x( 'Email:*', 'ui', 'buddyboss-theme' ); ?></label>
						<span class="cc-error"><?php echo esc_html_x( 'Invalid Email', 'ui', 'buddyboss-theme' ); ?></span>
					</div>
					<input type="email" name="user_email" id="user_email" class="mepr-form-input" value="<?php echo ( isset( $user_email ) ) ? esc_attr( stripslashes( $user_email ) ) : ''; ?>" required/>
				</div>
				<?php
				MeprHooks::do_action( 'mepr-after-email-field' ); // Deprecated.
				MeprHooks::do_action( 'mepr-checkout-after-email-field', $product->ID );
				if ( $mepr_options->disable_checkout_password_fields === false ) :
					?>
					<div class="mp-form-row mepr_password">
						<div class="mp-form-label">
							<label><?php echo esc_html_x( 'Password:*', 'ui', 'buddyboss-theme' ); ?></label>
							<span class="cc-error"><?php echo esc_html_x( 'Invalid Password', 'ui', 'buddyboss-theme' ); ?></span>
						</div>
						<input type="password" name="mepr_user_password" id="mepr_user_password" class="mepr-form-input mepr-password" value="<?php echo ( isset( $mepr_user_password ) ) ? esc_attr( stripslashes( $mepr_user_password ) ) : ''; ?>" required/>
					</div>
					<div class="mp-form-row mepr_password_confirm">
						<div class="mp-form-label">
							<label><?php echo esc_html_x( 'Password Confirmation:*', 'ui', 'buddyboss-theme' ); ?></label>
							<span class="cc-error"><?php echo esc_html_x( 'Password Confirmation Doesn\'t Match', 'ui', 'buddyboss-theme' ); ?></span>
						</div>
						<input type="password" name="mepr_user_password_confirm" id="mepr_user_password_confirm" class="mepr-form-input mepr-password-confirm" value="<?php echo ( isset( $mepr_user_password_confirm ) ) ? esc_attr( stripslashes( $mepr_user_password_confirm ) ) : ''; ?>" required/>
					</div>
					<?php
				endif;
				MeprHooks::do_action( 'mepr-after-password-fields' ); // Deprecated.
				MeprHooks::do_action( 'mepr-checkout-after-password-fields', $product->ID );
			endif;
			MeprHooks::do_action( 'mepr-before-coupon-field' ); // Deprecated.
			MeprHooks::do_action( 'mepr-checkout-before-coupon-field', $product->ID );
			if ( $product->adjusted_price() > 0.00 || ! empty( $product->plan_code ) ) :
				if ( $mepr_options->coupon_field_enabled ) :
					?>
					<a class="have-coupon-link" data-prdid="<?php echo esc_attr( $product->ID ); ?>" href="">
						<?php echo MeprCouponsHelper::show_coupon_field_link_content( $mepr_coupon_code ); ?>
					</a>
					<div class="mp-form-row mepr_coupon mepr_coupon_<?php echo esc_attr( $product->ID ); ?> mepr-hidden">
						<div class="mp-form-label">
							<label><?php echo esc_html_x( 'Coupon Code:', 'ui', 'buddyboss-theme' ); ?></label>
							<span class="mepr-coupon-loader mepr-hidden">
								<img src="<?php echo esc_url( includes_url( 'js/thickbox/loadingAnimation.gif' ) ); ?>" width="100" height="10"/>
							</span>
							<span class="cc-error"><?php echo esc_html_x( 'Invalid Coupon', 'ui', 'buddyboss-theme' ); ?></span>
						</div>
						<input type="text" id="mepr_coupon_code-<?php echo esc_attr( $product->ID ); ?>" class="mepr-form-input mepr-coupon-code" name="mepr_coupon_code" value="<?php echo ( isset( $mepr_coupon_code ) ) ? esc_attr( stripslashes( $mepr_coupon_code ) ) : ''; ?>" data-prdid="<?php echo esc_attr( $product->ID ); ?>"/>
					</div>
					<?php
				else :
					?>
					<input type="hidden" id="mepr_coupon_code-<?php echo esc_attr( $product->ID ); ?>" name="mepr_coupon_code" value="<?php echo ( isset( $mepr_coupon_code ) ) ? esc_attr( stripslashes( $mepr_coupon_code ) ) : ''; ?>"/>
					<?php
				endif;
				$active_pms = $product->payment_methods();
				$pms        = $product->payment_methods();

				echo MeprOptionsHelper::payment_methods_dropdown( 'mepr_payment_method', $active_pms );
			endif;

			if ( $mepr_options->require_tos ) :
				?>
				<div class="mp-form-row mepr_tos">
					<label for="mepr_agree_to_tos" class="mepr-checkbox-field mepr-form-input" required>
						<input type="checkbox" name="mepr_agree_to_tos" id="mepr_agree_to_tos" <?php checked( isset( $mepr_agree_to_tos ) ); ?> />
						<a href="<?php echo stripslashes( $mepr_options->tos_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo stripslashes( $mepr_options->tos_title ); ?></a>*
					</label>
				</div>
				<?php
			endif;

			$privacy_page_link = MeprAppHelper::privacy_policy_page_link();
			if ( $mepr_options->require_privacy_policy && $privacy_page_link ) :
				?>
				<div class="mp-form-row">
					<label for="mepr_agree_to_privacy_policy" class="mepr-checkbox-field mepr-form-input" required>
						<input type="checkbox" name="mepr_agree_to_privacy_policy" id="mepr_agree_to_privacy_policy"/>
						<?php echo preg_replace( '/%(.*)%/', '<a href="' . esc_url( $privacy_page_link ) . '" target="_blank">$1</a>', __( $mepr_options->privacy_policy_title, 'buddyboss-theme' ) ); ?>
					</label>
				</div>
				<?php
			endif;
			MeprHooks::do_action( 'mepr-user-signup-fields' ); // Deprecated.
			MeprHooks::do_action( 'mepr-checkout-before-submit', $product->ID );
			?>
			<div class="mp-form-submit">
				<input type="submit" class="mepr-submit" value="<?php echo esc_attr( stripslashes( $product->signup_button_text ) ); ?>"/>
				<img src="<?php echo esc_url( admin_url( 'images/loading.gif' ) ); ?>" style="display: none;" class="mepr-loading-gif"/>
				<?php MeprView::render( '/shared/has_errors', get_defined_vars() ); ?>
			</div>
		</div>
	</form>
</div>
