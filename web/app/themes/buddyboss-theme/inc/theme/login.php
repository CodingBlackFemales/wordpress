<?php

function buddyboss_is_login_page() {
	return in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) );
}

$rx_custom_login = buddyboss_theme_get_option( 'boss_custom_login' );
if ( $rx_custom_login ) {
	add_action( 'login_enqueue_scripts', 'buddyboss_login_enqueue_scripts' );
}

function buddyboss_login_enqueue_scripts() {
	$rtl_css      = is_rtl() ? '-rtl' : '';
	$minified_css = buddyboss_theme_get_option( 'boss_minified_css' );
	$mincss       = $minified_css ? '.min' : '';

	$enable_private_network = '1'; // Default NO i.e. 1

	// Check if Platform plugin is active.
	if ( function_exists( 'bp_get_option' ) ) {
		$enable_private_network = bp_get_option( 'bp-enable-private-network' );
	}

	// Icons.
	$mincss = buddyboss_theme_get_option( 'boss_minified_css' ) ? '.min' : '';
	// don't enqueue icons if BuddyBoss Platform 1.4.0 or higher is activated.
	if ( ! function_exists( 'buddypress' ) || ( function_exists( 'buddypress' ) && defined( 'BP_PLATFORM_VERSION' ) && version_compare( BP_PLATFORM_VERSION, '1.4.0', '<' ) ) ) {
		wp_enqueue_style( 'buddyboss-theme-icons-map', get_template_directory_uri() . '/assets/css/icons-map' . $mincss . '.css', '', buddyboss_theme()->version() );
		wp_enqueue_style( 'buddyboss-theme-icons', get_template_directory_uri() . '/assets/icons/css/bb-icons' . $mincss . '.css', '', buddyboss_theme()->version() );
	}

	wp_enqueue_style( 'buddyboss-theme-login', get_template_directory_uri() . '/assets/css' . $rtl_css . '/login' . $mincss . '.css', '', buddyboss_theme()->version() );

	wp_enqueue_style( 'buddyboss-theme-fonts', get_template_directory_uri() . '/assets/fonts/fonts.css', '', buddyboss_theme()->version() );

	if ( '0' === $enable_private_network ) {
		wp_enqueue_style( 'buddyboss-theme-login-magnific-popup', get_template_directory_uri() . '/assets/css/vendors/magnific-popup.min.css', '', buddyboss_theme()->version() );
	}
	// wp_enqueue_script( 'buddyboss-theme-login-js', get_template_directory_uri() . '/assets/js/login.js', array( 'jquery' ), buddyboss_theme()->version(), true );
}

add_filter( 'login_redirect', 'buddyboss_redirect_previous_page', 10, 3 );

function buddyboss_redirect_previous_page( $redirect_to, $request, $user ) {
	if ( buddyboss_theme()->buddypress_helper()->is_active() ) {

		$bp_pages = false;

		// Check if Platform plugin is active.
		if ( function_exists( 'bp_get_option' ) ) {
			$bp_pages = bp_get_option( 'bp-pages' );
		}

		$activate_page_id = ! empty( $bp_pages ) && isset( $bp_pages['activate'] ) ? $bp_pages['activate'] : null;

		if ( (int) $activate_page_id <= 0 ) {
			return $redirect_to;
		}

		$activate_page = get_post( $activate_page_id );

		if ( empty( $activate_page ) || empty( $activate_page->post_name ) ) {
			return $redirect_to;
		}

		$activate_page_slug = $activate_page->post_name;

		if ( strpos( $request, '/' . $activate_page_slug ) !== false ) {
			$redirect_to = home_url();
		}
	}

	// Check if redirect to url is admin url.
		$admin_url_info       = wp_parse_url( admin_url() );
		$redirect_to_url_info = wp_parse_url( $redirect_to );
		// Check by the url path.
	if ( isset( $admin_url_info['path'] ) && isset( $redirect_to_url_info['path'] ) && $redirect_to_url_info['path'] === $admin_url_info['path'] ) {
		// Redirect url is admin url. So set it to home page.
		$redirect_to = home_url();
	}

	$request = wp_get_referer();

	if ( ! $request ) {
		return $redirect_to;
	}

	// redirect for native mobile app
	if ( ! is_user_logged_in() && wp_is_mobile() ) {
		$path = wp_parse_url( $request );

		if ( isset( $path['query'] ) && ! empty( $path['query'] ) ) {
			parse_str( $path['query'], $output );

			$redirect_to = ( isset( $output ) && isset( $output['redirect_to'] ) && '' !== $output['redirect_to'] ) ? $output['redirect_to'] : $redirect_to;
			return $redirect_to;
		}
	}

	$req_parts        = explode( '/', $request );
	$req_part         = array_pop( $req_parts );
	$url_arr          = array();
	$url_query_string = array();
	if ( substr( $req_part, 0, 3 ) == 'wp-' ) {
		$url_query_string = wp_parse_url( $request );

		if ( isset( $url_query_string['query'] ) && ! empty( $url_query_string['query'] ) ) {
			parse_str( $url_query_string['query'], $url_arr );
			$redirect_to = ( isset( $url_arr ) && isset( $url_arr['redirect_to'] ) && '' !== $url_arr['redirect_to'] ) ? $url_arr['redirect_to'] : $redirect_to;

			return $redirect_to;
		} else {
			return $redirect_to;
		}
	}

	$redirect_to = str_replace( array( '?loggedout=true', '&loggedout=true' ), '', $redirect_to );

	return $redirect_to;
}

/**
 * Register page - change register message text
 */
function change_register_message( $message ) {
	$confirm_admin_email_page = false;
	if ( $GLOBALS['pagenow'] === 'wp-login.php' && ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] === 'confirm_admin_email' ) {
		$confirm_admin_email_page = true;
	}

	if ( strpos( $message, 'Register For This Site' ) !== false && $confirm_admin_email_page === false ) {
		$newMessage = __( 'Create an account', 'buddyboss-theme' );
		$login_url  = sprintf( '<a href="%s">%s</a>', esc_url( wp_login_url() ), __( 'Log in', 'buddyboss-theme' ) );
		return '<div class="login-heading"><p class="message register bs-register-message">' . $newMessage . '</p><span>' . $login_url . '</span></div>';
	} else {
		return $message;
	}
}

add_action( 'login_message', 'change_register_message' );

/**
 * Login page - login scripts
 */
function buddyboss_login_scripts() {
	$rx_logoimg = buddyboss_theme_get_option( 'admin_logo_media' );
	$rx_title   = get_bloginfo();
	?>
	<script>
		jQuery( document ).ready( function () {
			jQuery( '#loginform label[for="user_login"]' ).attr( 'id', 'user_label' );
			jQuery( '#loginform label[for="user_pass"]' ).attr( 'id', 'pass_label' );
			jQuery( '#registerform label[for="user_login"]' ).attr( 'id', 'user_label_register' );
			jQuery( '#registerform label[for="user_email"]' ).attr( 'id', 'email_label_register' );
			jQuery( '#lostpasswordform label[for="user_login"]' ).attr( 'id', 'user_label_lost' );

			var $label_user_login = jQuery( 'label#user_label' );
			$label_user_login.html( '<span class="screen-reader-text">' + $label_user_login.text() + '</span>' );

			var $label_user_pass = jQuery( 'label#pass_label' );
			$label_user_pass.html( '<span class="screen-reader-text">' + $label_user_pass.text() + '</span>' );

			var $label_user_register = jQuery( 'label#user_label_register' );
			$label_user_register.html( $label_user_register.find( 'input' ) );

			var $label_email_register = jQuery( 'label#email_label_register' );
			$label_email_register.html( $label_email_register.find( 'input' ) );

			var $label_user_lost = jQuery( 'label#user_label_lost' );
			$label_user_lost.html( '<span class="screen-reader-text">' + $label_user_lost.text() + '</span>' );

			var loginform_user_login = '<?php esc_html_e( 'Email Address', 'buddyboss-theme' ); ?>';
			var loginform_user_pass = '<?php esc_html_e( 'Password', 'buddyboss-theme' ); ?>';

			jQuery( '#loginform #user_login' ).attr( 'placeholder', jQuery( '<div/>' ).html( loginform_user_login ).text() );
			jQuery( '#loginform #user_pass' ).attr( 'placeholder', jQuery( '<div/>' ).html( loginform_user_pass ).text() );

			var registerform_user_login = '<?php esc_html_e( 'Username', 'buddyboss-theme' ); ?>';
			var registerform_user_email = '<?php esc_html_e( 'Email', 'buddyboss-theme' ); ?>';

			jQuery( '#registerform #user_login' ).attr( 'placeholder', jQuery( '<div/>' ).html( registerform_user_login ).text() );
			jQuery( '#registerform #user_email' ).attr( 'placeholder', jQuery( '<div/>' ).html( registerform_user_email ).text() );

			var lostpasswordform_user_login = '<?php esc_html_e( 'Email Address', 'buddyboss-theme' ); ?>';
			var resetpassform_pass1 = '<?php echo apply_filters( THEME_HOOK_PREFIX . 'password_field_text_placeholder', __( 'Add new password', 'buddyboss-theme' ) ); ?>';
			var resetpassform_pass2 = '<?php echo apply_filters( THEME_HOOK_PREFIX . 're_type_password_field_text_placeholder', __( 'Retype new password', 'buddyboss-theme' ) ); ?>';

			jQuery( '#lostpasswordform #user_login' ).attr( 'placeholder', jQuery( '<div/>' ).html( lostpasswordform_user_login ).text() );
			jQuery( '#resetpassform #pass1' ).attr( 'placeholder', jQuery( '<div/>' ).html( resetpassform_pass1 ).text() );
			jQuery( '#resetpassform #bs-pass2' ).attr( 'placeholder', jQuery( '<div/>' ).html( resetpassform_pass2 ).text() );

            jQuery( '.login.bb-login p.message.reset-pass' ).text( "<?php esc_html_e( 'Reset Password', 'buddyboss-theme' ); ?>" );
            jQuery( '.login.login-action-lostpassword.bb-login #login > p.message' ).html( '<?php echo sprintf( '<div>%1$s</div><p class="message">%2$s</p>', esc_html__( 'Forgot your password?', 'buddyboss-theme' ), esc_html__( 'Please enter your email address. You will receive an email with instructions on how to reset your password.', 'buddyboss-theme' ) ); ?>' );

            jQuery( '.login.login-action-lostpassword.bb-login #lostpasswordform input#wp-submit' ).attr( 'value', '<?php esc_html_e( 'Request reset link', 'buddyboss-theme' ); ?>' );
            jQuery( '.login.login-action-rp.bb-login #resetpassform input#wp-submit' ).attr( 'value', '<?php esc_html_e( 'Save', 'buddyboss-theme' ); ?>' );
            if(!jQuery('#resetpassform').length) {
                jQuery( '.login.login-action-resetpass.bb-login p#backtoblog' ).prepend( "<span class='bs-pass-update-msg'><?php esc_html_e( 'Password has been updated', 'buddyboss-theme' ); ?></span>" );
            }

            var $signIn = jQuery( '.login.login-action-lostpassword.bb-login #login > p#nav > a' ).first().addClass( 'bs-sign-in' ).text( `<?php _e( 'Back to sign in', 'buddyboss-theme' ); ?>` );
            jQuery( 'form#lostpasswordform' ).append( $signIn );

			jQuery( '.login #loginform label#pass_label' ).append( "<span class='label-switch'></span>" );

			var $forgetMeNot = jQuery( '.login.bb-login p.forgetmenot' );
			var $lostMeNot = jQuery( '.login.bb-login p.lostmenot' );
			jQuery( $lostMeNot ).before( $forgetMeNot );

			jQuery( document ).on( 'click', '.login .label-switch', function ( e ) {
				var $this = jQuery( this );
				var $input = $this.closest( 'label' ).find( 'input#user_pass' );
				$this.toggleClass( "bb-eye" );
				if ( $this.hasClass( 'bb-eye' ) ) {
					$input.attr( "type", "text" );
				} else {
					$input.attr( "type", "password" );
				}
			} );

			var signinCheckboxes = function() {
				// Checkbox Styling
				jQuery('input[type=checkbox]#rememberme').each(function() {
					var $this = jQuery(this);
					$this.addClass('checkbox');
					jQuery('<span class="checkbox"></span>').insertAfter($this);
					if ($this.is(':checked')) {
						$this.next('span.checkbox').addClass('on');
					};
					$this.fadeTo(0,0);
					$this.change(function(){
						$this.next('span.checkbox').toggleClass('on');
					});
				});
			};
			signinCheckboxes();

			var weakPasswordCheckboxes = function() {
				// Checkbox Styling
				jQuery('input[type=checkbox]#pw-weak').each(function() {
					var $this = jQuery(this);
					$this.addClass('checkbox');
					jQuery('<span class="checkbox"></span>').insertAfter($this);
					if ($this.is(':checked')) {
						$this.next('span.checkbox').addClass('on');
					};
					$this.fadeTo(0,0);
					$this.change(function(){
						$this.next('span.checkbox').toggleClass('on');
					});
				});
			};
			weakPasswordCheckboxes();

			var loginLogoImage = function() {
				jQuery('.login.bb-login #login > h1 > a').each(function() {
					var $this = jQuery(this);
					var bg = $this.css('background-image');
					bgLogo = bg.replace('url(','').replace(')','').replace(/\"/gi, "");
					<?php
					if ( function_exists( 'buddypress' ) && isset( buddypress()->buddyboss ) ) {
						$enable_private_network = bp_get_option( 'bp-enable-private-network' );
						if ( '0' === $enable_private_network ) {
							?>
							$this.append( '<img class="bs-cs-login-logo private-on" src="' + bgLogo + '" />' );
							jQuery('#login h1 a img').unwrap();
							<?php
						} else {
							?>
							$this.append( '<img class="bs-cs-login-logo" src="' + bgLogo + '" />' );
							<?php
						}
					} else {
						?>
						$this.append( '<img class="bs-cs-login-logo" src="' + bgLogo + '" />' );
						<?php
					}
					?>
				});
			};

			var loginLogoTitle = function() {
				jQuery('.login.bb-login #login > h1 > a').each(function() {
					var $this = jQuery(this);
					<?php
					if ( function_exists( 'buddypress' ) && isset( buddypress()->buddyboss ) ) {
						$enable_private_network = bp_get_option( 'bp-enable-private-network' );
						if ( '0' === $enable_private_network ) {
							?>
							$this.addClass('bb-login-title').append( '<span class="bs-cs-login-title private-on"><?php echo $rx_title; ?></span>' );
							jQuery('#login h1 a span').unwrap();
							<?php
						} else {
							?>
							$this.addClass('bb-login-title').append( '<span class="bs-cs-login-title"><?php echo $rx_title; ?></span>' );
							<?php
						}
					} else {
						?>
						$this.addClass('bb-login-title').append( '<span class="bs-cs-login-title"><?php echo $rx_title; ?></span>' );
						<?php
					}
					?>

				});
			};
			<?php if ( ! empty( $rx_logoimg['url'] ) ) { ?>
				loginLogoImage();
			<?php } else { ?>
				loginLogoTitle();
			<?php } ?>

			var loginHeight = function() {

				jQuery( 'body.login.login-split-page #login' ).each(function() {
					var $loginH = jQuery( 'body.login.login-split-page #login' ).height();
					var $winH = jQuery( window ).height();

					if ( $loginH > $winH ) {
						jQuery( 'body.login.login-split-page' ).addClass('login-exh');
					} else {
						jQuery( 'body.login.login-split-page' ).removeClass('login-exh');
					}
				});
			};
			loginHeight();

			// Re-position WP Language Switcher below Login Form
			var langSwitchPosition = function() {
				var languageSwitch = jQuery( '.language-switcher' );
				jQuery( 'body.login.login-split-page #login' ).append( languageSwitch );
			}

			langSwitchPosition();

			var resetTogglePw = function() {

				jQuery( document ).on( 'click', '.button-reset-hide-pw', function ( e ) {
					var $this = jQuery( this );
					var $input = $this.closest( '.user-bs-pass2-wrap' ).find( 'input#bs-pass2' );
					var $icon = $this.find( 'i' );
					
					if ( $input.prop( 'type' ) === 'password' ) {
						$input.prop( 'type', 'text' );
						$icon.addClass( 'bb-icon-eye-slash' ).removeClass( 'bb-icon-eye' );
					} else {
						$input.prop( 'type', 'password' );
						$icon.addClass( 'bb-icon-eye' ).removeClass( 'bb-icon-eye-slash' );
					}
				} );
			};
			resetTogglePw();

			if( jQuery( '#login .bs-cs-login-logo' ).length ) {
				jQuery( '.bs-cs-login-logo' ).load( function() {
					loginHeight();
					langSwitchPosition();
				});
			}

			jQuery( window ).on( 'resize', function () {
				loginHeight();
				langSwitchPosition();
			} );

		} )
	</script>
	<?php
}


/**
 * Custom Login Link
 *
 * @since Boss 1.0.0
 */
function change_wp_login_url() {

	if ( function_exists( 'buddypress' ) && isset( buddypress()->buddyboss ) ) {
		$enable_private_network = bp_get_option( 'bp-enable-private-network' );

		if ( '0' === $enable_private_network ) {
			return '#';
		}
	}
	return home_url();
}

function change_wp_login_title() {
	get_option( 'blogname' );
}

add_filter( 'login_headerurl', 'change_wp_login_url' );
add_filter( 'login_headertext', 'change_wp_login_title' );


/**
 * Login page - heading and register link
 */
if ( ! function_exists( 'signin_login_message' ) ) {

	function signin_login_message( $message ) {
		$home_url                 = get_bloginfo( 'url' );
		$confirm_admin_email_page = false;
		if ( $GLOBALS['pagenow'] === 'wp-login.php' && ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] === 'confirm_admin_email' ) {
			$confirm_admin_email_page = true;
		}

		if ( buddyboss_theme_get_option( 'boss_custom_login' ) && $confirm_admin_email_page === false ) {
			if ( empty( $message ) ) {
				if ( get_option( 'users_can_register' ) ) {
					$registration_url = sprintf( '<a href="%s">%s</a>', esc_url( wp_registration_url() ), __( 'Create an Account', 'buddyboss-theme' ) );
					return sprintf(
						'<div class="login-heading"><h2>%s</h2><span>%s</span></div>',
						__( 'Sign in', 'buddyboss-theme' ),
						apply_filters( 'register', $registration_url )
					);
				} else {
					return sprintf(
						'<div class="login-heading"><h2>%s</h2></div>',
						__( 'Sign in', 'buddyboss-theme' )
					);
				}
			} else {
				return $message;
			}
		} else {
			return $message;
		}
	}

	add_filter( 'login_message', 'signin_login_message' );
}


/**
 * Login page - custom classes
 */
if ( ! function_exists( 'custom_login_classes' ) ) {

	add_filter( 'login_body_class', 'custom_login_classes' );

	function custom_login_classes( $classes ) {
		$rx_custom_login = buddyboss_theme_get_option( 'boss_custom_login' );

		$rx_admin_background = buddyboss_theme_get_option( 'admin_login_background_switch' );

		// BuddyBoss theme template class.
		$template_type  = '1';
		$template_type  = apply_filters( 'bb_template_type', $template_type );
		$template_class = 'bb-template-v' . $template_type;

		if ( $rx_custom_login ) {
			if ( ( $GLOBALS['pagenow'] === 'wp-login.php' ) && $rx_admin_background ) {
				$classes[] = 'login-split-page bb-login ' . $template_class;
				return $classes;
			} else {
				$classes[] = 'bb-login ' . $template_class;
				return $classes;
			}
		} else {
			$classes[] = '';
			return $classes;
		}
	}
}

/**
 * Login page - custom styling
 */
if ( ! function_exists( 'login_custom_head' ) ) {

	function login_custom_head() {
		global $color_schemes;

		$color_presets                      = $color_schemes['default']['presets'];
		$rx_admin_login_background_switch   = buddyboss_theme_get_option( 'admin_login_background_switch' );
		$rx_admin_login_background_text     = buddyboss_theme_get_option( 'admin_login_background_text' );
		$rx_admin_login_background_textarea = buddyboss_theme_get_option( 'admin_login_background_textarea' );
		$rx_admin_login_heading_color       = buddyboss_theme_get_option( 'admin_login_heading_color' );
		$rx_admin_login_overlay_opacity     = buddyboss_theme_get_option( 'admin_login_overlay_opacity' );

		if ( $rx_admin_login_background_switch ) {
			echo '<div class="login-split"><div class="login-split__entry">';
			if ( $rx_admin_login_background_text ) {
				echo '<h1>';
				echo wp_kses_post( sprintf( esc_html__( '%s', 'buddyboss-theme' ), $rx_admin_login_background_text ) );
				echo '</h1>';
			}
			if ( $rx_admin_login_background_textarea ) {
				echo '<p>';
				echo stripslashes( $rx_admin_login_background_textarea );
				echo '</p>';
			}
			echo '</div><div class="split-overlay"></div></div>';
		}

		$rx_logoimg                                     = buddyboss_theme_get_option( 'admin_logo_media' );
		$rx_logowidth                                   = buddyboss_theme_get_option( 'admin_logo_width' );
		$rx_login_background_media                      = buddyboss_theme_get_option( 'admin_login_background_media' );
		$rx_success_color                               = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'success_notice_bg_color' ) ) ? buddyboss_theme_get_option( 'success_notice_bg_color' ) : $color_presets['success_notice_bg_color'];
		$rx_warning_color                               = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'warning_notice_bg_color' ) ) ? buddyboss_theme_get_option( 'warning_notice_bg_color' ) : $color_presets['warning_notice_bg_color'];
		$buddyboss_custom_font                          = buddyboss_theme_get_option( 'custom_typography' );
		$buddyboss_body_font                            = buddyboss_theme_get_option( 'boss_body_font_family' );
		$buddyboss_h1_font                              = buddyboss_theme_get_option( 'boss_h1_font_options' );
		$buddyboss_h2_font                              = buddyboss_theme_get_option( 'boss_h2_font_options' );
		$primary_color                                  = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'accent_color' ) ) ? buddyboss_theme_get_option( 'accent_color' ) : $color_presets['accent_color'];
		$body_background                                = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'body_background' ) ) ? buddyboss_theme_get_option( 'body_background' ) : $color_presets['body_background'];
		$body_blocks                                    = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'body_blocks' ) ) ? buddyboss_theme_get_option( 'body_blocks' ) : $color_presets['body_blocks'];
		$light_background_blocks                        = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'light_background_blocks' ) ) ? buddyboss_theme_get_option( 'light_background_blocks' ) : $color_presets['light_background_blocks'];
		$body_blocks_border                             = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'body_blocks_border' ) ) ? buddyboss_theme_get_option( 'body_blocks_border' ) : $color_presets['body_blocks_border'];
		$buddyboss_theme_group_cover_bg                 = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'buddyboss_theme_group_cover_bg' ) ) ? buddyboss_theme_get_option( 'buddyboss_theme_group_cover_bg' ) : $color_presets['buddyboss_theme_group_cover_bg'];
		$heading_text_color                             = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'heading_text_color' ) ) ? buddyboss_theme_get_option( 'heading_text_color' ) : $color_presets['heading_text_color'];
		$body_text_color                                = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'body_text_color' ) ) ? buddyboss_theme_get_option( 'body_text_color' ) : $color_presets['body_text_color'];
		$alternate_text_color                           = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'alternate_text_color' ) ) ? buddyboss_theme_get_option( 'alternate_text_color' ) : $color_presets['alternate_text_color'];
		$admin_screen_bgr_color                         = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'admin_screen_bgr_color' ) ) ? buddyboss_theme_get_option( 'admin_screen_bgr_color' ) : $color_presets['admin_screen_bgr_color'];
		$admin_screen_txt_color                         = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'admin_screen_txt_color' ) ) ? buddyboss_theme_get_option( 'admin_screen_txt_color' ) : $color_presets['admin_screen_txt_color'];

		$primary_button_background_regular              = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'primary_button_background' )['regular'] ) ? buddyboss_theme_get_option( 'primary_button_background' )['regular'] : $color_presets['primary_button_background']['regular'];
		$primary_button_background_hover                = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'primary_button_background' )['hover'] ) ? buddyboss_theme_get_option( 'primary_button_background' )['hover'] : $color_presets['primary_button_background']['hover'];
		$primary_button_border_regular                  = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'primary_button_border' )['regular'] ) ? buddyboss_theme_get_option( 'primary_button_border' )['regular'] : $color_presets['primary_button_border']['regular'];
		$primary_button_border_hover                    = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'primary_button_border' )['hover'] ) ? buddyboss_theme_get_option( 'primary_button_border' )['hover'] : $color_presets['primary_button_border']['hover'];
		$primary_button_text_color_regular              = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'primary_button_text_color' )['regular'] ) ? buddyboss_theme_get_option( 'primary_button_text_color' )['regular'] : $color_presets['primary_button_text_color']['regular'];
		$primary_button_text_color_hover                = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'primary_button_text_color' )['hover'] ) ? buddyboss_theme_get_option( 'primary_button_text_color' )['hover'] : $color_presets['primary_button_text_color']['hover'];
		$secondary_button_background_regular            = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'secondary_button_background' )['regular'] ) ? buddyboss_theme_get_option( 'secondary_button_background' )['regular'] : $color_presets['secondary_button_background']['regular'];
		$secondary_button_background_hover              = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'secondary_button_background' )['hover'] ) ? buddyboss_theme_get_option( 'secondary_button_background' )['hover'] : $color_presets['secondary_button_background']['hover'];
		$secondary_button_border_regular                = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'secondary_button_border' )['regular'] ) ? buddyboss_theme_get_option( 'secondary_button_border' )['regular'] : $color_presets['secondary_button_border']['regular'];
		$secondary_button_border_hover                  = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'secondary_button_border' )['hover'] ) ? buddyboss_theme_get_option( 'secondary_button_border' )['hover'] : $color_presets['secondary_button_border']['hover'];
		$secondary_button_text_color_regular            = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'secondary_button_text_color' )['regular'] ) ? buddyboss_theme_get_option( 'secondary_button_text_color' )['regular'] : $color_presets['secondary_button_text_color']['regular'];
		$secondary_button_text_color_hover              = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'secondary_button_text_color' )['hover'] ) ? buddyboss_theme_get_option( 'secondary_button_text_color' )['hover'] : $color_presets['secondary_button_text_color']['hover'];

		$login_register_link_color_regular              = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'login_register_link_color' )['regular'] ) ? buddyboss_theme_get_option( 'login_register_link_color' )['regular'] : $color_presets['login_register_link_color']['regular'];
		$login_register_link_color_hover                = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'login_register_link_color' )['hover'] ) ? buddyboss_theme_get_option( 'login_register_link_color' )['hover'] : $color_presets['login_register_link_color']['hover'];
		$login_register_button_background_color_regular = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'login_register_button_background_color' )['regular'] ) ? buddyboss_theme_get_option( 'login_register_button_background_color' )['regular'] : $color_presets['login_register_button_background_color']['regular'];
		$login_register_button_background_color_hover   = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'login_register_button_background_color' )['hover'] ) ? buddyboss_theme_get_option( 'login_register_button_background_color' )['hover'] : $color_presets['login_register_button_background_color']['hover'];
		$login_register_button_border_color_regular     = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'login_register_button_border_color' )['regular'] ) ? buddyboss_theme_get_option( 'login_register_button_border_color' )['regular'] : $color_presets['login_register_button_border_color']['regular'];
		$login_register_button_border_color_hover       = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'login_register_button_border_color' )['hover'] ) ? buddyboss_theme_get_option( 'login_register_button_border_color' )['hover'] : $color_presets['login_register_button_border_color']['hover'];
		$login_register_button_text_color_regular       = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'login_register_button_text_color' )['regular'] ) ? buddyboss_theme_get_option( 'login_register_button_text_color' )['regular'] : $color_presets['login_register_button_text_color']['regular'];
		$login_register_button_text_color_hover         = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'login_register_button_text_color' )['hover'] ) ? buddyboss_theme_get_option( 'login_register_button_text_color' )['hover'] : $color_presets['login_register_button_text_color']['hover'];

		$default_notice_color                           = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'default_notice_bg_color' ) ) ? buddyboss_theme_get_option( 'default_notice_bg_color' ) : $color_presets['default_notice_bg_color'];
		$success_color                                  = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'success_notice_bg_color' ) ) ? buddyboss_theme_get_option( 'success_notice_bg_color' ) : $color_presets['success_notice_bg_color'];
		$warning_color                                  = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'warning_notice_bg_color' ) ) ? buddyboss_theme_get_option( 'warning_notice_bg_color' ) : $color_presets['warning_notice_bg_color'];
		$danger_color                                   = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'error_notice_bg_color' ) ) ? buddyboss_theme_get_option( 'error_notice_bg_color' ) : $color_presets['error_notice_bg_color'];

		$button_radius                                  = buddyboss_theme_get_option( 'button_default_radius' );
		$theme_style                                    = buddyboss_theme_get_option( 'theme_template' );

		echo '<style>';
		?>
		:root{
			--bb-primary-color: <?php echo $primary_color; ?>;
			--bb-primary-color-rgb: <?php echo join( ', ', hex_2_RGB( $primary_color ) ); ?>;
			--bb-body-background-color: <?php echo $body_background; ?>;
			--bb-content-background-color: <?php echo $body_blocks; ?>;
			--bb-content-alternate-background-color: <?php echo $light_background_blocks; ?>;
			--bb-content-border-color: <?php echo $body_blocks_border; ?>;
			--bb-content-border-color-rgb: <?php echo join( ', ', hex_2_RGB( $body_blocks_border ) ); ?>;
			--bb-cover-image-background-color: <?php echo $buddyboss_theme_group_cover_bg; ?>;
			--bb-headings-color: <?php echo $heading_text_color; ?>;
			--bb-body-text-color: <?php echo $body_text_color; ?>;
			--bb-alternate-text-color: <?php echo $alternate_text_color; ?>;
			--bb-alternate-text-color-rgb: <?php echo join( ', ', hex_2_RGB( $alternate_text_color ) ); ?>;

			--bb-primary-button-background-regular: <?php echo $primary_button_background_regular; ?>;
			--bb-primary-button-background-hover: <?php echo $primary_button_background_hover; ?>;
			--bb-primary-button-border-regular: <?php echo $primary_button_border_regular; ?>;
			--bb-primary-button-border-hover: <?php echo $primary_button_border_hover; ?>;
			--bb-primary-button-border-hover-rgb: <?php echo join( ', ', hex_2_RGB( $primary_button_border_hover ) ); ?>;
			--bb-primary-button-text-regular: <?php echo $primary_button_text_color_regular; ?>;
			--bb-primary-button-text-regular-rgb: <?php echo join( ', ', hex_2_RGB( $primary_button_text_color_regular ) ); ?>;
			--bb-primary-button-text-hover: <?php echo $primary_button_text_color_hover; ?>;
			--bb-primary-button-text-hover-rgb: <?php echo join( ', ', hex_2_RGB( $primary_button_text_color_hover ) ); ?>;
			--bb-secondary-button-background-regular: <?php echo $secondary_button_background_regular; ?>;
			--bb-secondary-button-background-hover: <?php echo $secondary_button_background_hover; ?>;
			--bb-secondary-button-border-regular: <?php echo $secondary_button_border_regular; ?>;
			--bb-secondary-button-border-hover: <?php echo $secondary_button_border_hover; ?>;
			--bb-secondary-button-border-hover-rgb:  <?php echo join( ', ', hex_2_RGB( $secondary_button_border_hover ) ); ?>;
			--bb-secondary-button-text-regular: <?php echo $secondary_button_text_color_regular; ?>;
			--bb-secondary-button-text-hover: <?php echo $secondary_button_text_color_hover; ?>;

			--bb-admin-screen-bgr-color: <?php echo $admin_screen_bgr_color; ?>;
			--bb-admin-screen-txt-color: <?php echo $admin_screen_txt_color; ?>;
			--bb-login-register-link-color-regular: <?php echo $login_register_link_color_regular; ?>;
			--bb-login-register-link-color-hover: <?php echo $login_register_link_color_hover; ?>;
			--bb-login-register-button-background-color-regular: <?php echo $login_register_button_background_color_regular; ?>;
			--bb-login-register-button-background-color-hover: <?php echo $login_register_button_background_color_hover; ?>;
			--bb-login-register-button-border-color-regular: <?php echo $login_register_button_border_color_regular; ?>;
			--bb-login-register-button-border-color-hover: <?php echo $login_register_button_border_color_hover; ?>;
			--bb-login-register-button-text-color-regular: <?php echo $login_register_button_text_color_regular; ?>;
			--bb-login-register-button-text-color-hover: <?php echo $login_register_button_text_color_hover; ?>;

			--bb-default-notice-color: <?php echo $default_notice_color; ?>;
			--bb-default-notice-color-rgb: <?php echo join( ', ', hex_2_RGB( $default_notice_color ) ); ?>;
			--bb-success-color: <?php echo $success_color; ?>;
			--bb-success-color-rgb: <?php echo join( ', ', hex_2_RGB( $success_color ) ); ?>;
			--bb-warning-color: <?php echo $warning_color; ?>;
			--bb-warning-color-rgb: <?php echo join( ', ', hex_2_RGB( $warning_color ) ); ?>;
			--bb-danger-color: <?php echo $danger_color; ?>;
			--bb-danger-color-rgb: <?php echo join( ', ', hex_2_RGB( $danger_color ) ); ?>;

			--bb-login-custom-heading-color: <?php echo $rx_admin_login_heading_color; ?>;

			--bb-button-radius: <?php echo $button_radius; ?>px;

			<?php
			if ( ! isset( $theme_style ) ) {
				$theme_style = '1';
			}
			?>

			<?php if ( '1' === $theme_style ) { ?>
				--bb-block-radius: 4px;
				--bb-block-radius-inner: 4px;
				--bb-input-radius: 4px;
				--bb-checkbox-radius: 2.7px;
				--bb-primary-button-focus-shadow: none;
				--bb-secondary-button-focus-shadow: none;
				--bb-outline-button-focus-shadow: none;
				--bb-input-focus-shadow: none;
				--bb-input-focus-border-color: var(--bb-content-border-color);
			<?php } else { ?>
				--bb-block-radius: 10px;
				--bb-block-radius-inner: 6px;
				--bb-input-radius: 6px;
				--bb-checkbox-radius: 5.4px;
				--bb-primary-button-focus-shadow: 0px 0px 0px 2px rgba(var(--bb-primary-button-border-hover-rgb), 0.1);
				--bb-secondary-button-focus-shadow: 0px 0px 0px 2px rgba(var(--bb-secondary-button-border-hover-rgb), 0.1);
				--bb-outline-button-focus-shadow: 0px 0px 0px 2px rgba(var(--bb-content-border-color-rgb), 0.1);
				--bb-input-focus-shadow: 0px 0px 0px 2px rgba(var(--bb-primary-color-rgb), 0.1);
				--bb-input-focus-border-color: var(--bb-primary-color);
			<?php } ?>
		}
		<?php
		if ( '1' == $buddyboss_custom_font ) {
			if ( ! empty( $buddyboss_body_font['font-family'] ) ) {
				?>
				body, body.rtl {
				font-family: <?php echo $buddyboss_body_font['font-family']; ?>
				}
				<?php
			}

			if ( ! empty( $buddyboss_h1_font['font-family'] ) ) {
				?>
				h1, .rtl h1 {
				font-family: <?php echo $buddyboss_h1_font['font-family']; ?>
				}
				<?php
			}

			if ( ! empty( $buddyboss_h2_font['font-family'] ) ) {
				?>
				h2, .rtl h2 {
				font-family: <?php echo $buddyboss_h2_font['font-family']; ?>
				}
				<?php
			}
		}

		if ( ! empty( $rx_logoimg['url'] ) ) {
			?>
			.login h1 a,
			.login .wp-login-logo a {
			background-image: url(<?php echo $rx_logoimg['url']; ?>);
			background-size: contain;
			<?php
			if ( $rx_logowidth ) {
				echo 'width:' . $rx_logowidth . 'px;';
			}
			?>
			}

			.login #login h1 img.bs-cs-login-logo.private-on {
			<?php
			if ( $rx_logowidth ) {
				echo 'width:' . $rx_logowidth . 'px;';
			}
			?>
			}
			<?php
		}
		if ( $rx_admin_login_background_switch && $rx_login_background_media ) {
			?>
			.login-split {
			background-image: url(<?php echo $rx_login_background_media['url']; ?>);
			background-size: cover;
			background-position: 50% 50%;
			}
			<?php
		}
		if ( $danger_color ) {
			?>
			.login.bb-login #pass-strength-result.short,
			.login.bb-login #pass-strength-result.bad {
			background-color: <?php echo $danger_color; ?>;
			border-color: <?php echo $danger_color; ?>;
			}
			<?php
		}
		if ( $rx_success_color ) {
			?>
			.login.bb-login #pass-strength-result.strong {
			background-color: <?php echo $rx_success_color; ?>;
			border-color: <?php echo $rx_success_color; ?>;
			}
			<?php
		}
		if ( $rx_warning_color ) {
			?>
			.login.bb-login #pass-strength-result.good {
			background-color: <?php echo $rx_warning_color; ?>;
			border-color: <?php echo $rx_warning_color; ?>;
			}
			<?php
		}
		if ( $rx_admin_login_overlay_opacity ) {
			?>
			body.login.login-split-page .login-split .split-overlay {
			opacity: <?php echo $rx_admin_login_overlay_opacity / 100; ?>;
			}
			<?php
		}
		echo '</style>';
	}
}


/**
 * Login page - custom forget password link
 */
if ( ! function_exists( 'login_custom_form' ) ) {

	add_action( 'login_form', 'login_custom_form' );

	function login_custom_form() {
		$rx_custom_login = buddyboss_theme_get_option( 'boss_custom_login' );

		if ( $rx_custom_login ) {
			?>
			<p class="lostmenot"><a href="<?php echo wp_lostpassword_url(); ?>"><?php esc_html_e('Forgot Password?', 'buddyboss-theme'); ?></a></p>
			<?php
		}
	}
}


function buddyboss_theme_login_load() {
	$rx_custom_login = buddyboss_theme_get_option( 'boss_custom_login' );

	if ( $rx_custom_login ) {
		add_action( 'login_head', 'buddyboss_login_scripts', 150 );
		add_action( 'login_head', 'login_custom_head', 150 );

		/**
		 * Confirm New Login Password
		 */
		add_action( 'resetpass_form', function( $user )
		{ ?> <div class="user-bs-pass2-wrap">
            <p><label for="bs-pass2"><?php esc_html_e( 'Retype new password', 'buddyboss-theme' ) ?></label></p>
            <input type="password" name="bs-pass2" id="bs-pass2" class="input"
                   size="20" value="" autocomplete="off" />
						<button type="button" class="button button-secondary wp-hide-pw hide-if-no-js button-reset-hide-pw" data-toggle="0" aria-label="Show password">
							<i class="bb-icon-l bb-icon-eye"></i>
					</button>
        </div> <?php
		} );

		add_action( 'validate_password_reset', function( $errors )
		{
			if ( isset( $_POST['pass1'] ) && $_POST['pass1'] != $_POST['bs-pass2'] )
				$errors->add( 'password_reset_mismatch', __( 'The passwords do not match.', 'buddyboss-theme' ) );
		} );

		add_action( 'login_enqueue_scripts', function ()
		{
			if ( ! wp_script_is( 'jquery', 'done' ) ) {
				wp_enqueue_script( 'jquery' );
			}
			wp_add_inline_script( 'jquery-migrate', 'jQuery(document).ready(function(){ jQuery( "#pass1" ).data( "reveal", 0 ); });' );
		}, 1 );
	}
}
add_action( 'init', 'buddyboss_theme_login_load' );
