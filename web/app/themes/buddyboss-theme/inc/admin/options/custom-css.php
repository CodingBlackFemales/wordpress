<?php
/*
 * Custom CSS
 */
if ( ! function_exists( 'boss_generate_option_css' ) ) {

	function boss_generate_option_css() {
		global $color_schemes;

		$custom_css = array();
		if ( is_customize_preview() ) {
			$custom_css = array();
		} else {
			$custom_css = get_transient( 'buddyboss_theme_compressed_custom_css' );
		}

		if ( ! empty( $custom_css ) && isset( $custom_css['css'] ) ) {

			echo "<style id=\"buddyboss_theme-style\">{$custom_css["css"]}</style>";

			return false;
		}

		$color_presets = $color_schemes['default']['presets'];

		$primary_color                                  = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'accent_color' ) ) ? buddyboss_theme_get_option( 'accent_color' ) : $color_presets['accent_color'];
		$body_background                                = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'body_background' ) ) ? buddyboss_theme_get_option( 'body_background' ) : $color_presets['body_background'];
		$body_blocks                                    = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'body_blocks' ) ) ? buddyboss_theme_get_option( 'body_blocks' ) : $color_presets['body_blocks'];
		$light_background_blocks                        = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'light_background_blocks' ) ) ? buddyboss_theme_get_option( 'light_background_blocks' ) : $color_presets['light_background_blocks'];
		$body_blocks_border                             = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'body_blocks_border' ) ) ? buddyboss_theme_get_option( 'body_blocks_border' ) : $color_presets['body_blocks_border'];
		$buddyboss_theme_group_cover_bg                 = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'buddyboss_theme_group_cover_bg' ) ) ? buddyboss_theme_get_option( 'buddyboss_theme_group_cover_bg' ) : $color_presets['buddyboss_theme_group_cover_bg'];
		$heading_text_color                             = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'heading_text_color' ) ) ? buddyboss_theme_get_option( 'heading_text_color' ) : $color_presets['heading_text_color'];
		$body_text_color                                = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'body_text_color' ) ) ? buddyboss_theme_get_option( 'body_text_color' ) : $color_presets['body_text_color'];
		$alternate_text_color                           = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'alternate_text_color' ) ) ? buddyboss_theme_get_option( 'alternate_text_color' ) : $color_presets['alternate_text_color'];
		$primary_button_background_regular              = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'primary_button_background' )['regular'] ) ? buddyboss_theme_get_option( 'primary_button_background' )['regular'] : $color_presets['primary_button_background']['regular'];
		$primary_button_border_regular                  = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'primary_button_border' )['regular'] ) ? buddyboss_theme_get_option( 'primary_button_border' )['regular'] : $color_presets['primary_button_border']['regular'];
		$primary_button_border_hover                    = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'primary_button_border' )['hover'] ) ? buddyboss_theme_get_option( 'primary_button_border' )['hover'] : $color_presets['primary_button_border']['hover'];
		$primary_button_text_color_regular              = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'primary_button_text_color' )['regular'] ) ? buddyboss_theme_get_option( 'primary_button_text_color' )['regular'] : $color_presets['primary_button_text_color']['regular'];
		$primary_button_background_hover                = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'primary_button_background' )['hover'] ) ? buddyboss_theme_get_option( 'primary_button_background' )['hover'] : $color_presets['primary_button_background']['hover'];
		$primary_button_text_color_hover                = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'primary_button_text_color' )['hover'] ) ? buddyboss_theme_get_option( 'primary_button_text_color' )['hover'] : $color_presets['primary_button_text_color']['hover'];
		$secondary_button_background_regular            = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'secondary_button_background' )['regular'] ) ? buddyboss_theme_get_option( 'secondary_button_background' )['regular'] : $color_presets['secondary_button_background']['regular'];
		$secondary_button_background_hover              = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'secondary_button_background' )['hover'] ) ? buddyboss_theme_get_option( 'secondary_button_background' )['hover'] : $color_presets['secondary_button_background']['hover'];
		$secondary_button_border_regular                = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'secondary_button_border' )['regular'] ) ? buddyboss_theme_get_option( 'secondary_button_border' )['regular'] : $color_presets['secondary_button_border']['regular'];
		$secondary_button_border_hover                  = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'secondary_button_border' )['hover'] ) ? buddyboss_theme_get_option( 'secondary_button_border' )['hover'] : $color_presets['secondary_button_border']['hover'];
		$secondary_button_text_color_regular            = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'secondary_button_text_color' )['regular'] ) ? buddyboss_theme_get_option( 'secondary_button_text_color' )['regular'] : $color_presets['secondary_button_text_color']['regular'];
		$secondary_button_text_color_hover              = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'secondary_button_text_color' )['hover'] ) ? buddyboss_theme_get_option( 'secondary_button_text_color' )['hover'] : $color_presets['secondary_button_text_color']['hover'];
		$header_background                              = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'header_background' ) ) ? buddyboss_theme_get_option( 'header_background' ) : $color_presets['header_background'];
		$header_alternate_background                    = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'header_alternate_background' ) ) ? buddyboss_theme_get_option( 'header_alternate_background' ) : $color_presets['header_alternate_background'];
		$header_links                                   = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'header_links' ) ) ? buddyboss_theme_get_option( 'header_links' ) : $color_presets['header_links'];
		$header_links_hover                             = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'header_links_hover' ) ) ? buddyboss_theme_get_option( 'header_links_hover' ) : $color_presets['header_links_hover'];
		$sidenav_background                             = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'sidenav_background' ) ) ? buddyboss_theme_get_option( 'sidenav_background' ) : $color_presets['sidenav_background'];
		$sidenav_text_color_regular                     = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'sidenav_text_color' )['regular'] ) ? buddyboss_theme_get_option( 'sidenav_text_color' )['regular'] : $color_presets['sidenav_text_color']['regular'];
		$sidenav_text_color_hover                       = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'sidenav_text_color' )['hover'] ) ? buddyboss_theme_get_option( 'sidenav_text_color' )['hover'] : $color_presets['sidenav_text_color']['hover'];
		$sidenav_text_color_active                      = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'sidenav_text_color' )['active'] ) ? buddyboss_theme_get_option( 'sidenav_text_color' )['active'] : $color_presets['sidenav_text_color']['active'];
		$sidenav_menu_background_color_regular          = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'sidenav_menu_background_color' )['regular'] ) ? buddyboss_theme_get_option( 'sidenav_menu_background_color' )['regular'] : $color_presets['sidenav_menu_background_color']['regular'];
		$sidenav_menu_background_color_hover            = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'sidenav_menu_background_color' )['hover'] ) ? buddyboss_theme_get_option( 'sidenav_menu_background_color' )['hover'] : $color_presets['sidenav_menu_background_color']['hover'];
		$sidenav_menu_background_color_active           = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'sidenav_menu_background_color' )['active'] ) ? buddyboss_theme_get_option( 'sidenav_menu_background_color' )['active'] : $color_presets['sidenav_menu_background_color']['active'];
		$sidenav_count_text_color_regular               = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'sidenav_count_text_color' )['regular'] ) ? buddyboss_theme_get_option( 'sidenav_count_text_color' )['regular'] : $color_presets['sidenav_count_text_color']['regular'];
		$sidenav_count_text_color_hover                 = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'sidenav_count_text_color' )['hover'] ) ? buddyboss_theme_get_option( 'sidenav_count_text_color' )['hover'] : $color_presets['sidenav_count_text_color']['hover'];
		$sidenav_count_text_color_active                = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'sidenav_count_text_color' )['active'] ) ? buddyboss_theme_get_option( 'sidenav_count_text_color' )['active'] : $color_presets['sidenav_count_text_color']['active'];
		$sidenav_count_background_color_regular         = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'sidenav_count_background_color' )['regular'] ) ? buddyboss_theme_get_option( 'sidenav_count_background_color' )['regular'] : $color_presets['sidenav_count_background_color']['regular'];
		$sidenav_count_background_color_hover           = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'sidenav_count_background_color' )['hover'] ) ? buddyboss_theme_get_option( 'sidenav_count_background_color' )['hover'] : $color_presets['sidenav_count_background_color']['hover'];
		$sidenav_count_background_color_active          = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'sidenav_count_background_color' )['active'] ) ? buddyboss_theme_get_option( 'sidenav_count_background_color' )['active'] : $color_presets['sidenav_count_background_color']['active'];
		$footer_background                              = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'footer_background' ) ) ? buddyboss_theme_get_option( 'footer_background' ) : $color_presets['footer_background'];
		$footer_widget_background                       = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'footer_widget_background' ) ) ? buddyboss_theme_get_option( 'footer_widget_background' ) : $color_presets['footer_widget_background'];
		$footer_text_color                              = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'footer_text_color' ) ) ? buddyboss_theme_get_option( 'footer_text_color' ) : $color_presets['footer_text_color'];
		$footer_menu_link_color_regular                 = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'footer_menu_link_color' )['regular'] ) ? buddyboss_theme_get_option( 'footer_menu_link_color' )['regular'] : $color_presets['footer_menu_link_color']['regular'];
		$footer_menu_link_color_hover                   = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'footer_menu_link_color' )['hover'] ) ? buddyboss_theme_get_option( 'footer_menu_link_color' )['hover'] : $color_presets['footer_menu_link_color']['hover'];
		$footer_menu_link_color_active                  = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'footer_menu_link_color' )['active'] ) ? buddyboss_theme_get_option( 'footer_menu_link_color' )['active'] : $color_presets['footer_menu_link_color']['active'];
		$admin_screen_bgr_color                         = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'admin_screen_bgr_color' ) ) ? buddyboss_theme_get_option( 'admin_screen_bgr_color' ) : $color_presets['admin_screen_bgr_color'];
		$admin_screen_txt_color                         = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'admin_screen_txt_color' ) ) ? buddyboss_theme_get_option( 'admin_screen_txt_color' ) : $color_presets['admin_screen_txt_color'];
		$login_register_link_color_regular              = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'login_register_link_color' )['regular'] ) ? buddyboss_theme_get_option( 'login_register_link_color' )['regular'] : $color_presets['login_register_link_color']['regular'];
		$login_register_link_color_hover                = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'login_register_link_color' )['hover'] ) ? buddyboss_theme_get_option( 'login_register_link_color' )['hover'] : $color_presets['login_register_link_color']['hover'];
		$login_register_button_background_color_regular = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'login_register_button_background_color' )['regular'] ) ? buddyboss_theme_get_option( 'login_register_button_background_color' )['regular'] : $color_presets['login_register_button_background_color']['regular'];
		$login_register_button_background_color_hover   = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'login_register_button_background_color' )['hover'] ) ? buddyboss_theme_get_option( 'login_register_button_background_color' )['hover'] : $color_presets['login_register_button_background_color']['hover'];
		$login_register_button_border_color_regular     = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'login_register_button_border_color' )['regular'] ) ? buddyboss_theme_get_option( 'login_register_button_border_color' )['regular'] : $color_presets['login_register_button_border_color']['regular'];
		$login_register_button_border_color_hover       = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'login_register_button_border_color' )['hover'] ) ? buddyboss_theme_get_option( 'login_register_button_border_color' )['hover'] : $color_presets['login_register_button_border_color']['hover'];
		$login_register_button_text_color_regular       = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'login_register_button_text_color' )['regular'] ) ? buddyboss_theme_get_option( 'login_register_button_text_color' )['regular'] : $color_presets['login_register_button_text_color']['regular'];
		$login_register_button_text_color_hover         = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'login_register_button_text_color' )['hover'] ) ? buddyboss_theme_get_option( 'login_register_button_text_color' )['hover'] : $color_presets['login_register_button_text_color']['hover'];
		$label_background_color                         = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'label_background_color' ) ) ? buddyboss_theme_get_option( 'label_background_color' ) : $color_presets['label_background_color'];
		$label_text_color                               = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'label_text_color' ) ) ? buddyboss_theme_get_option( 'label_text_color' ) : $color_presets['label_text_color'];
		$tooltip_background                             = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'tooltip_background' ) ) ? buddyboss_theme_get_option( 'tooltip_background' ) : $color_presets['tooltip_background'];
		$tooltip_color                                  = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'tooltip_color' ) ) ? buddyboss_theme_get_option( 'tooltip_color' ) : $color_presets['tooltip_color'];
		$default_notice_color                           = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'default_notice_bg_color' ) ) ? buddyboss_theme_get_option( 'default_notice_bg_color' ) : $color_presets['default_notice_bg_color'];
		$success_color                                  = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'success_notice_bg_color' ) ) ? buddyboss_theme_get_option( 'success_notice_bg_color' ) : $color_presets['success_notice_bg_color'];
		$warning_color                                  = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'warning_notice_bg_color' ) ) ? buddyboss_theme_get_option( 'warning_notice_bg_color' ) : $color_presets['warning_notice_bg_color'];
		$danger_color                                   = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'error_notice_bg_color' ) ) ? buddyboss_theme_get_option( 'error_notice_bg_color' ) : $color_presets['error_notice_bg_color'];
		$admin_login_heading_color                      = bb_theme_is_valid_hex_color( buddyboss_theme_get_option( 'admin_login_heading_color' ) ) ? buddyboss_theme_get_option( 'admin_login_heading_color' ) : $color_presets['admin_login_heading_color'];
		$header_height                                  = buddyboss_theme_get_option( 'header_height' );
		$header_shadow                                  = buddyboss_theme_get_option( 'header_shadow' );
		$header_sticky                                  = buddyboss_theme_get_option( 'header_sticky' );
		$header_lesson_topic                            = get_body_class();
		$button_radius                                  = buddyboss_theme_get_option( 'button_default_radius' );
		$mobile_logo_size                               = buddyboss_theme_get_option( 'mobile_logo_size' );
		$theme_style                                    = buddyboss_theme_get_option( 'theme_template' );
		$custom_typography                              = buddyboss_theme_get_option( 'custom_typography' );

		?>
		<style id="buddyboss_theme-style">

			<?php
			ob_start();

			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			?>

			:root{
				--bb-primary-color: <?php echo $primary_color; ?>;
				--bb-primary-color-rgb: <?php echo join( ', ', hex_2_RGB( $primary_color ) ); ?>;
				--bb-body-background-color: <?php echo $body_background; ?>;
				--bb-body-background-color-rgb: <?php echo join( ', ', hex_2_RGB( $body_background ) ); ?>;
				--bb-content-background-color: <?php echo $body_blocks; ?>;
				--bb-content-alternate-background-color: <?php echo $light_background_blocks; ?>;
				--bb-content-border-color: <?php echo $body_blocks_border; ?>;
				--bb-content-border-color-rgb: <?php echo join( ', ', hex_2_RGB( $body_blocks_border ) ); ?>;
				--bb-cover-image-background-color: <?php echo $buddyboss_theme_group_cover_bg; ?>;
				--bb-headings-color: <?php echo $heading_text_color; ?>;
				--bb-headings-color-rgb: <?php echo join( ', ', hex_2_RGB( $heading_text_color ) ); ?>;
				--bb-body-text-color: <?php echo $body_text_color; ?>;
				--bb-body-text-color-rgb: <?php echo join( ', ', hex_2_RGB( $body_text_color ) ); ?>;
				--bb-alternate-text-color: <?php echo $alternate_text_color; ?>;
				--bb-alternate-text-color-rgb: <?php echo join( ', ', hex_2_RGB( $alternate_text_color ) ); ?>;

				--bb-primary-button-background-regular: <?php echo $primary_button_background_regular; ?>;
				--bb-primary-button-background-hover: <?php echo $primary_button_background_hover; ?>;
				--bb-primary-button-border-regular: <?php echo $primary_button_border_regular; ?>;
				--bb-primary-button-border-hover: <?php echo $primary_button_border_hover; ?>;
				--bb-primary-button-text-regular: <?php echo $primary_button_text_color_regular; ?>;
				--bb-primary-button-text-regular-rgb: <?php echo join( ', ', hex_2_RGB( $primary_button_text_color_regular ) ); ?>;
				--bb-primary-button-text-hover: <?php echo $primary_button_text_color_hover; ?>;
				--bb-primary-button-text-hover-rgb: <?php echo join( ', ', hex_2_RGB( $primary_button_text_color_hover ) ); ?>;
				--bb-secondary-button-background-regular: <?php echo $secondary_button_background_regular; ?>;
				--bb-secondary-button-background-hover: <?php echo $secondary_button_background_hover; ?>;
				--bb-secondary-button-border-regular: <?php echo $secondary_button_border_regular; ?>;
				--bb-secondary-button-border-hover: <?php echo $secondary_button_border_hover; ?>;
				--bb-secondary-button-text-regular: <?php echo $secondary_button_text_color_regular; ?>;
				--bb-secondary-button-text-hover: <?php echo $secondary_button_text_color_hover; ?>;

				--bb-header-background: <?php echo $header_background; ?>;
				--bb-header-alternate-background: <?php echo $header_alternate_background; ?>;
				--bb-header-links: <?php echo $header_links; ?>;
				--bb-header-links-hover: <?php echo $header_links_hover; ?>;

				--bb-header-mobile-logo-size: <?php echo $mobile_logo_size; ?>px;
				--bb-header-height: <?php echo $header_height; ?>px;

				--bb-sidenav-background: <?php echo $sidenav_background; ?>;
				--bb-sidenav-text-regular: <?php echo $sidenav_text_color_regular; ?>;
				--bb-sidenav-text-hover: <?php echo $sidenav_text_color_hover; ?>;
				--bb-sidenav-text-active: <?php echo $sidenav_text_color_active; ?>;
				--bb-sidenav-menu-background-color-regular: <?php echo $sidenav_menu_background_color_regular; ?>;
				--bb-sidenav-menu-background-color-hover: <?php echo $sidenav_menu_background_color_hover; ?>;
				--bb-sidenav-menu-background-color-active: <?php echo $sidenav_menu_background_color_active; ?>;
				--bb-sidenav-count-text-color-regular: <?php echo $sidenav_count_text_color_regular; ?>;
				--bb-sidenav-count-text-color-hover: <?php echo $sidenav_count_text_color_hover; ?>;
				--bb-sidenav-count-text-color-active: <?php echo $sidenav_count_text_color_active; ?>;
				--bb-sidenav-count-background-color-regular: <?php echo $sidenav_count_background_color_regular; ?>;
				--bb-sidenav-count-background-color-hover: <?php echo $sidenav_count_background_color_hover; ?>;
				--bb-sidenav-count-background-color-active: <?php echo $sidenav_count_background_color_active; ?>;

				--bb-footer-background: <?php echo $footer_background; ?>;
				--bb-footer-widget-background: <?php echo $footer_widget_background; ?>;
				--bb-footer-text-color: <?php echo $footer_text_color; ?>;
				--bb-footer-menu-link-color-regular: <?php echo $footer_menu_link_color_regular; ?>;
				--bb-footer-menu-link-color-hover: <?php echo $footer_menu_link_color_hover; ?>;
				--bb-footer-menu-link-color-active: <?php echo $footer_menu_link_color_active; ?>;

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

				--bb-label-background-color: <?php echo $label_background_color; ?>;
				--bb-label-text-color: <?php echo $label_text_color; ?>;

				--bb-tooltip-background: <?php echo $tooltip_background; ?>;
				--bb-tooltip-background-rgb: <?php echo join( ', ', hex_2_RGB( $tooltip_background ) ); ?>;
				--bb-tooltip-color: <?php echo $tooltip_color; ?>;

				--bb-default-notice-color: <?php echo $default_notice_color; ?>;
				--bb-default-notice-color-rgb: <?php echo join( ', ', hex_2_RGB( $default_notice_color ) ); ?>;
				--bb-success-color: <?php echo $success_color; ?>;
				--bb-success-color-rgb: <?php echo join( ', ', hex_2_RGB( $success_color ) ); ?>;
				--bb-warning-color: <?php echo $warning_color; ?>;
				--bb-warning-color-rgb: <?php echo join( ', ', hex_2_RGB( $warning_color ) ); ?>;
				--bb-danger-color: <?php echo $danger_color; ?>;
				--bb-danger-color-rgb: <?php echo join( ', ', hex_2_RGB( $danger_color ) ); ?>;

				--bb-login-custom-heading-color: <?php echo $admin_login_heading_color; ?>;

				--bb-button-radius: <?php echo $button_radius; ?>px;

				<?php
				if ( ! isset( $theme_style ) ) {
					$theme_style = '1';
				}
				?>

				<?php if ( '1' === $theme_style ) { ?>
					--bb-block-radius: 4px;
					--bb-option-radius: 3px;
					--bb-block-radius-inner: 4px;
					--bb-input-radius: 4px;
					--bb-checkbox-radius: 2.7px;
					--bb-primary-button-focus-shadow: none;
					--bb-secondary-button-focus-shadow: none;
					--bb-outline-button-focus-shadow: none;
					--bb-input-focus-shadow: none;
					--bb-input-focus-border-color: var(--bb-content-border-color);
					--bb-label-type-radius: 100px;
					--bb-widget-title-text-transform: uppercase;
				<?php } else { ?>
					--bb-block-radius: 10px;
					--bb-option-radius: 5px;
					--bb-block-radius-inner: 6px;
					--bb-input-radius: 6px;
					--bb-label-type-radius: 6px;
					--bb-checkbox-radius: 5.4px;
					--bb-primary-button-focus-shadow: 0px 0px 1px 2px rgba(0, 0, 0, 0.05), inset 0px 0px 0px 2px rgba(0, 0, 0, 0.08);
					--bb-secondary-button-focus-shadow: 0px 0px 1px 2px rgba(0, 0, 0, 0.05), inset 0px 0px 0px 2px rgba(0, 0, 0, 0.08);
					--bb-outline-button-focus-shadow: 0px 0px 1px 2px rgba(0, 0, 0, 0.05), inset 0px 0px 0px 2px rgba(0, 0, 0, 0.08);
					--bb-input-focus-shadow: 0px 0px 0px 2px rgba(var(--bb-primary-color-rgb), 0.1);
					--bb-input-focus-border-color: var(--bb-primary-color);
					--bb-widget-title-text-transform: none;
				<?php } ?>

			}

			<?php // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<?php
			// Custom Typography heading tags line height.
			if ( $custom_typography ) {
				$headings = array(
					'h1' => buddyboss_theme_get_option( 'boss_h1_font_options' )['font-size'],
					'h2' => buddyboss_theme_get_option( 'boss_h2_font_options' )['font-size'],
					'h3' => buddyboss_theme_get_option( 'boss_h3_font_options' )['font-size'],
					'h4' => buddyboss_theme_get_option( 'boss_h4_font_options' )['font-size'],
					'h5' => buddyboss_theme_get_option( 'boss_h5_font_options' )['font-size'],
					'h6' => buddyboss_theme_get_option( 'boss_h6_font_options' )['font-size'],
				);

				foreach ( $headings as $heading_tag => $font_size ) {
					if ( $font_size ) {
						$size_int    = (int) preg_replace( '/[^0-9]/', '', $font_size );
						$line_height = $size_int > 22 ? 1.2 : 1.4;
						echo esc_attr( $heading_tag ) . ' { line-height: ' . esc_attr( $line_height ) . '; }';
					}
				}
			}
			?>

			.bb-style-primary-bgr-color {
				background-color: <?php echo $primary_color; ?>;
			}

			.bb-style-border-radius {
				border-radius: <?php echo $button_radius; ?>px;
			}

			<?php if ( buddyboss_theme_get_option( 'logo_size' ) ) { ?>
				#site-logo .site-title img {
					max-height: inherit;
				}

				.site-header-container .site-branding {
					min-width: <?php echo buddyboss_theme_get_option( 'logo_size' ); ?>px;
				}

				#site-logo .site-title .bb-logo img,
				#site-logo .site-title img.bb-logo,
				.buddypanel .site-title img {
					width: <?php echo buddyboss_theme_get_option( 'logo_size' ); ?>px;
				}
			<?php } ?>

			<?php
			if ( buddyboss_theme_get_option( 'logo_dark', 'id' ) && buddyboss_theme_get_option( 'logo_dark_switch' ) ) {
				?>
				.site-header-container #site-logo .bb-logo.bb-logo-dark,
				.llms-sidebar.bb-dark-theme .site-header-container #site-logo .bb-logo,
				.site-header-container .ld-focus-custom-logo .bb-logo.bb-logo-dark,
				.bb-custom-ld-focus-mode-enabled:not(.bb-custom-ld-logo-enabled) .site-header-container .ld-focus-custom-logo .bb-logo.bb-logo-dark,
				.bb-dark-theme.bb-custom-ld-focus-mode-enabled:not(.bb-custom-ld-logo-enabled) .site-header-container .ld-focus-custom-logo img,
				.bb-sfwd-aside.bb-dark-theme:not(.bb-custom-ld-logo-enabled) .site-header-container #site-logo .bb-logo,
				.buddypanel .site-branding div img.bb-logo.bb-logo-dark,
				.bb-sfwd-aside.bb-dark-theme .buddypanel .site-branding div img.bb-logo,
				.buddypanel .site-branding h1 img.bb-logo.bb-logo-dark,
				.bb-sfwd-aside.bb-dark-theme .buddypanel .site-branding h1 img.bb-logo{display:none;}

				.llms-sidebar.bb-dark-theme .site-header-container #site-logo .bb-logo.bb-logo-dark,
				.bb-dark-theme.bb-custom-ld-focus-mode-enabled:not(.bb-custom-ld-logo-enabled) .site-header-container .ld-focus-custom-logo .bb-logo.bb-logo-dark,
				.bb-sfwd-aside.bb-dark-theme .site-header-container #site-logo .bb-logo.bb-logo-dark,
				.buddypanel .site-branding div img.bb-logo,
				.bb-sfwd-aside.bb-dark-theme .buddypanel .site-branding div img.bb-logo.bb-logo-dark,
				.buddypanel .site-branding h1 img.bb-logo,
				.bb-sfwd-aside.bb-dark-theme .buddypanel .site-branding h1 img.bb-logo.bb-logo-dark{display:inline;}

			<?php } ?>

			<?php if ( buddyboss_theme_get_option( 'logo_dark', 'id' ) && buddyboss_theme_get_option( 'logo_dark_switch' ) ) { ?>
				#site-logo .site-title img {
					max-height: inherit;
				}

				.llms-sidebar.bb-dark-theme .site-header-container .site-branding,
				.bb-sfwd-aside.bb-dark-theme .site-header-container .site-branding {
					min-width: <?php echo buddyboss_theme_get_option( 'logo_dark_size' ); ?>px;
				}

				.llms-sidebar.bb-dark-theme #site-logo .site-title .bb-logo.bb-logo-dark img,
				.bb-sfwd-aside.bb-dark-theme #site-logo .site-title .bb-logo.bb-logo-dark img,
				.llms-sidebar.bb-dark-theme #site-logo .site-title img.bb-logo.bb-logo-dark,
				.bb-sfwd-aside.bb-dark-theme #site-logo .site-title img.bb-logo.bb-logo-dark,
				.bb-custom-ld-focus-mode-enabled .site-header-container .ld-focus-custom-logo .bb-logo.bb-logo-dark,
				.bb-sfwd-aside.bb-dark-theme .buddypanel .site-branding div img.bb-logo.bb-logo-dark {
					width: <?php echo buddyboss_theme_get_option( 'logo_dark_size' ); ?>px;
				}
			<?php } ?>

			<?php if ( buddyboss_theme_get_option( 'mobile_logo_dark', 'id' ) && buddyboss_theme_get_option( 'mobile_logo_dark_switch' ) ) { ?>
				.llms-sidebar.bb-dark-theme .site-title img.bb-mobile-logo.bb-mobile-logo-dark,
				.bb-sfwd-aside.bb-dark-theme:not(.bb-custom-ld-logo-enabled) .site-title img.bb-mobile-logo.bb-mobile-logo-dark {
					display: inline;
				}
				.site-title img.bb-mobile-logo.bb-mobile-logo-dark,
				.llms-sidebar.bb-dark-theme .site-title img.bb-mobile-logo,
				.bb-sfwd-aside.bb-dark-theme:not(.bb-custom-ld-logo-enabled) .site-title img.bb-mobile-logo {
					display: none;
				}
			<?php } ?>

			<?php if ( buddyboss_theme_get_option( 'mobile_logo_dark_size' ) && buddyboss_theme_get_option( 'mobile_logo_dark_switch' ) ) { ?>
				.llms-sidebar.bb-dark-theme .site-title img.bb-mobile-logo.bb-mobile-logo-dark,
				.bb-sfwd-aside.bb-dark-theme .site-title img.bb-mobile-logo.bb-mobile-logo-dark {
					width: <?php echo buddyboss_theme_get_option( 'mobile_logo_dark_size' ); ?>px;
				}
			<?php } ?>

			<?php if ( buddyboss_theme_get_option( 'mobile_logo_size' ) ) { ?>
				.site-title img.bb-mobile-logo {
					width: <?php echo buddyboss_theme_get_option( 'mobile_logo_size' ); ?>px;
				}
				<?php
			}
			if ( buddyboss_theme_get_option( 'footer_logo_size' ) ) {
				?>
				.footer-logo img {
					max-width: <?php echo buddyboss_theme_get_option( 'footer_logo_size' ); ?>px;
				}
			<?php } ?>

			.site-header-container #site-logo .bb-logo img,
			.site-header-container #site-logo .site-title img.bb-logo,
			.site-title img.bb-mobile-logo {
				<?php
				if ( $header_height ) {
					echo 'max-height:' . $header_height . 'px';
				} else {
					echo 'max-height: 76px;';
				}
				?>
			}

			<?php if ( empty( $header_shadow ) ) { ?>
				.site-header,
				.sticky-header .site-header:not(.has-scrolled) {
					-webkit-box-shadow: none;
					-moz-box-shadow: none;
					box-shadow: none;
				}
			<?php } ?>

			<?php
			if (
				(
					in_array( 'single-sfwd-lessons', $header_lesson_topic, true ) ||
					in_array( 'single-sfwd-topic', $header_lesson_topic, true ) ||
					in_array( 'single-sfwd-quiz', $header_lesson_topic, true )
				) && empty( $header_sticky )
			) {
				?>
				.bb-sfwd-aside .site-header.has-scrolled {
					box-shadow: 0 1px 0 0 rgba(0, 0, 0, 0.05), 0 5px 10px 0 rgba(0, 0, 0, 0.15);
				}

				.bb-sfwd-aside .site-content  {
					<?php
					if ( $header_height ) {
						echo 'padding-top:' . $header_height . 'px !important';
					} else {
						echo 'padding-top: 76px !important;';
					}
					?>
				}
			<?php } ?>

			<?php if ( ! empty( $header_sticky ) ) { ?>
				.sticky-header .site-content,
				body.buddypress.sticky-header .site-content,
				.bb-buddypanel.sticky-header .site-content,
				.single-sfwd-quiz.bb-buddypanel.sticky-header .site-content,
				.single-sfwd-lessons.bb-buddypanel.sticky-header .site-content,
				.single-sfwd-topic.bb-buddypanel.sticky-header .site-content {
					<?php
					if ( $header_height ) {
						echo 'padding-top:' . $header_height . 'px';
					} else {
						echo 'padding-top: 76px;';
					}
					?>
				}
			<?php } ?>

			.site-header .site-header-container,
			.header-search-wrap,
			.header-search-wrap input.search-field,
			.header-search-wrap form.search-form {
				height: <?php echo $header_height; ?>px;
			}

			.sticky-header .bp-feedback.bp-sitewide-notice {
				top: <?php echo $header_height; ?>px;
			}

			@media screen and (max-width: 767px) {
				.bb-mobile-header {
					height: <?php echo $header_height; ?>px;
				}

				#learndash-content .lms-topic-sidebar-wrapper .lms-topic-sidebar-data {
					height: calc(90vh - <?php echo $header_height; ?>px);
				}
			}

			/* Tooltips */

			[data-balloon]:after,
			[data-bp-tooltip]:after {
				background-color: <?php echo color2rgba( $tooltip_background, 1 ); ?>;
				box-shadow: none;
			}

			[data-balloon]:before,
			[data-bp-tooltip]:before {
				background:no-repeat url("data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http://www.w3.org/2000/svg%22%20width%3D%2236px%22%20height%3D%2212px%22%3E%3Cpath%20fill%3D%22<?php echo color2rgba( $tooltip_background, 1 ); ?>%22%20transform%3D%22rotate(0)%22%20d%3D%22M2.658,0.000%20C-13.615,0.000%2050.938,0.000%2034.662,0.000%20C28.662,0.000%2023.035,12.002%2018.660,12.002%20C14.285,12.002%208.594,0.000%202.658,0.000%20Z%22/%3E%3C/svg%3E");
				background-size: 100% auto;
			}

			[data-bp-tooltip][data-bp-tooltip-pos="right"]:before,
			[data-balloon][data-balloon-pos='right']:before {
				background:no-repeat url("data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http://www.w3.org/2000/svg%22%20width%3D%2212px%22%20height%3D%2236px%22%3E%3Cpath%20fill%3D%22<?php echo color2rgba( $tooltip_background, 1 ); ?>%22%20transform%3D%22rotate(90 6 6)%22%20d%3D%22M2.658,0.000%20C-13.615,0.000%2050.938,0.000%2034.662,0.000%20C28.662,0.000%2023.035,12.002%2018.660,12.002%20C14.285,12.002%208.594,0.000%202.658,0.000%20Z%22/%3E%3C/svg%3E");
				background-size: 100% auto;
			}

			[data-bp-tooltip][data-bp-tooltip-pos="left"]:before,
			[data-balloon][data-balloon-pos='left']:before {
				background:no-repeat url("data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http://www.w3.org/2000/svg%22%20width%3D%2212px%22%20height%3D%2236px%22%3E%3Cpath%20fill%3D%22<?php echo color2rgba( $tooltip_background, 1 ); ?>%22%20transform%3D%22rotate(-90 18 18)%22%20d%3D%22M2.658,0.000%20C-13.615,0.000%2050.938,0.000%2034.662,0.000%20C28.662,0.000%2023.035,12.002%2018.660,12.002%20C14.285,12.002%208.594,0.000%202.658,0.000%20Z%22/%3E%3C/svg%3E");
				background-size: 100% auto;
			}

			[data-bp-tooltip][data-bp-tooltip-pos="down-left"]:before,
			[data-bp-tooltip][data-bp-tooltip-pos="down"]:before,
			[data-balloon][data-balloon-pos='down']:before {
				background:no-repeat url("data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http://www.w3.org/2000/svg%22%20width%3D%2236px%22%20height%3D%2212px%22%3E%3Cpath%20fill%3D%22<?php echo color2rgba( $tooltip_background, 1 ); ?>%22%20transform%3D%22rotate(180 18 6)%22%20d%3D%22M2.658,0.000%20C-13.615,0.000%2050.938,0.000%2034.662,0.000%20C28.662,0.000%2023.035,12.002%2018.660,12.002%20C14.285,12.002%208.594,0.000%202.658,0.000%20Z%22/%3E%3C/svg%3E");
				background-size: 100% auto;
			}

		<?php

		$css = ob_get_contents();
		// Remove comments
		$css = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css );
		// Remove space after colons
		$css = str_replace( ': ', ':', $css );
		// Remove whitespace
		$css = str_replace( array( "\r\n", "\r", "\n", "\t", '  ', '    ', '    ' ), '', $css );

		ob_end_clean();

		echo $css;

		if ( ! is_array( $custom_css ) ) {
			$custom_css = array();
		}
		$custom_css['css'] = $css;

		?>

		</style>
		<?php

		// save processed css.
		set_transient( 'buddyboss_theme_compressed_custom_css', $custom_css );

	}

	/* Add Action */
	add_action( 'wp_head', 'boss_generate_option_css', 99 );
}

if ( ! function_exists( 'boss_generate_option_bp_css' ) ) {

	function boss_generate_option_bp_css() {

		if ( is_customize_preview() ) {
			$custom_css = '';
		} else {
			$custom_css = get_transient( 'buddyboss_theme_compressed_bp_custom_css' );
		}

		if ( ! empty( $custom_css ) && isset( $custom_css['css'] ) ) {

			echo "
            <style id=\"buddyboss_theme-bp-style\">
                {$custom_css["css"]}
            </style>
            ";

			return false;

		}

		$admin_login_background_switch = buddyboss_theme_get_option( 'admin_login_background_switch' );
		$admin_login_background_media  = buddyboss_theme_get_option( 'admin_login_background_media' );
		$admin_login_overlay_opacity   = buddyboss_theme_get_option( 'admin_login_overlay_opacity' );
		$admin_logoimg                 = buddyboss_theme_get_option( 'admin_logo_media' );
		$admin_logowidth               = buddyboss_theme_get_option( 'admin_logo_width' );

		?>
		<style id="buddyboss_theme-bp-style">

		<?php ob_start(); ?>
			<?php
			if ( function_exists( 'buddypress' ) && defined( 'BP_PLATFORM_VERSION' ) && version_compare( BP_PLATFORM_VERSION, '1.8.5', '>' ) ) {
				?>
				#buddypress #header-cover-image.has-default,
				#buddypress #header-cover-image.has-default .guillotine-window img,
				.bs-group-cover.has-default a {
					background-color: <?php echo buddyboss_theme_get_option( 'buddyboss_theme_group_cover_bg' ); ?>;
				}

			<?php } else { ?>

				#buddypress #header-cover-image,
				#buddypress #header-cover-image .guillotine-window img,
				.bs-group-cover a {
					background-color: <?php echo buddyboss_theme_get_option( 'buddyboss_theme_group_cover_bg' ); ?>;
				}
				<?php
			}
			?>
							

			<?php
			if ( $admin_login_background_switch ) {
				if ( $admin_login_background_media['url'] ) {
					?>
					.login-split {
						background-image: url(<?php echo $admin_login_background_media['url']; ?>);
						background-size: cover;
						background-position: 50% 50%;
					}
					<?php
				}
			}
			if ( $admin_login_overlay_opacity ) {
				?>
				body.buddypress.register.login-split-page .login-split .split-overlay,
				body.buddypress.activation.login-split-page .login-split .split-overlay {
					opacity: <?php echo $admin_login_overlay_opacity / 100; ?>;
				}
				<?php
			}

			if ( ! empty( $admin_logoimg['url'] ) ) {
				?>
				body.buddypress.register .register-section-logo img,
				body.buddypress.activation .activate-section-logo img {
					width: <?php echo $admin_logowidth; ?>px;
				}
				<?php
			}
			?>
		<?php

		$css = ob_get_contents();
		// Remove comments
		$css = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css );
		// Remove space after colons
		$css = str_replace( ': ', ':', $css );
		// Remove whitespace
		$css = str_replace( array( "\r\n", "\r", "\n", "\t", '  ', '    ', '    ' ), '', $css );

		ob_end_clean();

		echo $css;

		if ( ! is_array( $custom_css ) ) {
			$custom_css = array();
		}
		$custom_css['css'] = $css;

		?>

		</style>
		<?php

		// save processed css.
		set_transient( 'buddyboss_theme_compressed_bp_custom_css', $custom_css );

	}

	/* Add Action */
	if ( function_exists( 'bp_is_active' ) ) {
		add_action( 'wp_head', 'boss_generate_option_bp_css', 99 );
	}
}

if ( ! function_exists( 'boss_generate_option_forums_css' ) ) {
	function boss_generate_option_forums_css() {

		if ( is_customize_preview() ) {
			$custom_css = '';
		} else {
			$custom_css = get_transient( 'buddyboss_theme_compressed_forums_custom_css' );
		}

		if ( ! empty( $custom_css ) && isset( $custom_css['css'] ) ) {

			echo "
            <style id=\"buddyboss_theme-forums-style\">
                {$custom_css["css"]}
            </style>
            ";

			return false;

		}

		?>
		<style id="buddyboss_theme-forums-style">

		<?php ob_start(); ?>					
			/* Headings link color */			

			.bbpress .widget_display_forums > ul.bb-sidebar-forums > li a:before {
				border-color: <?php echo textToColor( bbp_get_topic_forum_title() ); ?>;
			}

			.bbpress .widget_display_forums > ul.bb-sidebar-forums > li a:before {
				background-color: <?php echo color2rgba( textToColor( bbp_get_topic_forum_title() ), 0.5 ); ?>;
			}

		<?php

		$css = ob_get_contents();
		// Remove comments
		$css = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css );
		// Remove space after colons
		$css = str_replace( ': ', ':', $css );
		// Remove whitespace
		$css = str_replace( array( "\r\n", "\r", "\n", "\t", '  ', '    ', '    ' ), '', $css );

		ob_end_clean();

		echo $css;
		if ( ! is_array( $custom_css ) ) {
			$custom_css = array();
		}
		$custom_css['css'] = $css;

		?>

		</style>
		<?php

		// save processed css.
		set_transient( 'buddyboss_theme_compressed_forums_custom_css', $custom_css );

	}

	/* Add Action */
	if ( class_exists( 'bbPress' ) ) {
		add_action( 'wp_head', 'boss_generate_option_forums_css', 99 );
	}
}

if ( ! function_exists( 'boss_generate_option_learndash_css' ) ) {
	function boss_generate_option_learndash_css() {

		if ( is_customize_preview() ) {
			$custom_css = '';
		} else {
			$custom_css = get_transient( 'buddyboss_theme_compressed_learndash_custom_css' );
		}

		if ( ! empty( $custom_css ) && isset( $custom_css['css'] ) ) {

			echo "
            <style id=\"buddyboss_theme-learndash-style\">
                {$custom_css["css"]}
            </style>
            ";

			return false;

		}

		$button_radius = buddyboss_theme_get_option( 'button_default_radius' );
		$header_height = buddyboss_theme_get_option( 'header_height' );
		$is_admin_bar  = is_admin_bar_showing() ? 32 : 0;
		?>

		<style id="buddyboss_theme-learndash-style">

		<?php ob_start(); ?>
			.learndash-wrapper .bb-ld-tabs #learndash-course-content {
				top: -<?php echo $header_height + $is_admin_bar + 10; ?>px;
			}

			html[dir="rtl"] .learndash_next_prev_link a.next-link,
			html[dir="rtl"] .learndash_next_prev_link span.next-link {
				border-radius: <?php echo $button_radius; ?>px 0 0 <?php echo $button_radius; ?>px;
			}

			html[dir="rtl"] .learndash_next_prev_link a.prev-link,
			html[dir="rtl"] .learndash_next_prev_link span.prev-link {
				border-radius: 0 <?php echo $button_radius; ?>px <?php echo $button_radius; ?>px 0;
			}

		<?php

		$css = ob_get_contents();
		// Remove comments
		$css = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css );
		// Remove space after colons
		$css = str_replace( ': ', ':', $css );
		// Remove whitespace
		$css = str_replace( array( "\r\n", "\r", "\n", "\t", '  ', '    ', '    ' ), '', $css );

		ob_end_clean();

		echo $css;

		if ( ! is_array( $custom_css ) ) {
			$custom_css = array();
		}
		$custom_css['css'] = $css;

		?>

		</style>
		<?php

		// save processed css.
		set_transient( 'buddyboss_theme_compressed_learndash_custom_css', $custom_css );

	}

	/* Add Action */
	if ( class_exists( 'SFWD_LMS' ) ) {
		add_action( 'wp_head', 'boss_generate_option_learndash_css', 99 );
	}
}

/**
 * Buddyboss theme custom styling
 */
if ( ! function_exists( 'boss_generate_option_custom_css' ) ) {

	function boss_generate_option_custom_css() {

		global $post;

		$fullscreen_page_padding = false;

		if ( ! empty( $post ) ) {
			$fullscreen_page_padding = get_post_meta( $post->ID, '_wp_page_padding', true );
		}

		$admin_bar_offset = is_admin_bar_showing() ? 67 : 21;
		?>

		<style id="buddyboss_theme-custom-style">

		<?php ob_start(); ?>

		<?php if ( $fullscreen_page_padding ) { ?>
			.page-template-page-fullscreen.page-id-<?php echo $post->ID; ?> .site-content {
				padding: <?php echo $fullscreen_page_padding; ?>px;
			}
		<?php } ?>

		a.bb-close-panel i {
			top: <?php echo $admin_bar_offset; ?>px;
		}


		<?php

		$css = ob_get_contents();
		// Remove comments
		$css = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css );
		// Remove space after colons
		$css = str_replace( ': ', ':', $css );
		// Remove whitespace
		$css = str_replace( array( "\r\n", "\r", "\n", "\t", '  ', '    ', '    ' ), '', $css );

		ob_end_clean();

		echo $css;
		?>

		</style>
		<?php

	}

	/* Add Action */
	add_action( 'wp_head', 'boss_generate_option_custom_css', 99 );
}


/**
 * LifterLMS Custom Styling
 */
if ( ! function_exists( 'boss_generate_option_lifterLMS_css' ) ) {
	function boss_generate_option_lifterLMS_css() {

		if ( is_customize_preview() ) {
			$custom_css = '';
		} else {
			$custom_css = get_transient( 'buddyboss_theme_compressed_lifterLMS_custom_css' );
		}

		if ( ! empty( $custom_css ) && isset( $custom_css['css'] ) ) {

			echo "
            <style id=\"buddyboss_theme-lifterLMS-style\">
                {$custom_css["css"]}
            </style>
            ";

			return false;

		}

		?>
		<style id="buddyboss_theme-lifterLMS-style">

		<?php ob_start(); ?>

			/* Buttons */
			.single-llms_quiz #llms-quiz-header:before {
				content: "<?php echo __( 'Quiz Progress', 'buddyboss-theme' ); ?>";
			}

		<?php

		$css = ob_get_contents();
		// Remove comments
		$css = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css );
		// Remove space after colons
		$css = str_replace( ': ', ':', $css );
		// Remove whitespace
		$css = str_replace( array( "\r\n", "\r", "\n", "\t", '  ', '    ', '    ' ), '', $css );

		ob_end_clean();

		echo $css;
		if ( ! is_array( $custom_css ) ) {
			$custom_css = array();
		}
		$custom_css['css'] = $css;

		?>

		</style>
		<?php

		// save processed css.
		set_transient( 'buddyboss_theme_compressed_lifterLMS_custom_css', $custom_css );

	}

	/* Add Action */
	if ( class_exists( 'lifterLMS' ) ) {
		add_action( 'wp_head', 'boss_generate_option_lifterLMS_css', 99 );
	}
}
