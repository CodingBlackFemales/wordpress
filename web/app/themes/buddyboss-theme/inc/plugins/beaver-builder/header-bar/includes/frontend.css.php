/**
 * This file should contain frontend styles that
 * will be applied to individual module instances.
 *
 * You have access to three variables in this file:
 *
 * $module An instance of your module class.
 * $id The module's ID.
 * $settings The module's settings.
 *
 */

.site-header--beaver-builder .user-wrap {
	display: <?php echo esc_attr( $settings->profile_dropdown ); ?>;
}

.site-header--beaver-builder .bb-separator {
	display: <?php echo esc_attr( $settings->element_separator ); ?>;
}

.site-header--beaver-builder .header-search-link {
	display: <?php echo esc_attr( $settings->search_icon_switch ); ?>;
}

.site-header--beaver-builder #header-messages-dropdown-elem {
	display: <?php echo esc_attr( $settings->messages_icon_switch ); ?>;
}

.site-header--beaver-builder #header-notifications-dropdown-elem {
	display: <?php echo esc_attr( $settings->notifications_icon_switch ); ?>;
}

.site-header--beaver-builder .header-cart-link-wrap {
	display: <?php echo esc_attr( $settings->cart_icon_switch ); ?>;
}

.site-header--beaver-builder .user-link img {
	max-width: <?php echo esc_attr( $settings->avatar_size ); ?>px;
}

.site-header--beaver-builder .user-link img {
	border-style: <?php echo esc_attr( $settings->avatar_border_style ); ?>;
}

.site-header--beaver-builder .user-link img {
	border-width: <?php echo esc_attr( $settings->avatar_border_size ); ?>px;
}

.site-header--beaver-builder .user-link img {
	border-radius: <?php echo esc_attr( $settings->avatar_border_radius ); ?>%;
}

.site-header--beaver-builder .user-link img {
	border-color: #<?php echo esc_attr( $settings->avatar_border_color ); ?>;
}

.site-header--beaver-builder .bb-separator {
	width: <?php echo esc_attr( $settings->separator_width ); ?>px;
	background: #<?php echo esc_attr( $settings->separator_color ); ?>;
}

.site-header--beaver-builder .sub-menu a:not(.user-link) {
	font-size: <?php echo esc_attr( $settings->dropdown_font_size ); ?>px;
}

.site-header--beaver-builder .user-link .user-name {
	<?php if ( $settings->user_name_font['family'] != 'Default' ) : ?>
		font-family: "<?php echo esc_attr( $settings->user_name_font['family'] ); ?>";
		font-weight: <?php echo esc_attr( $settings->user_name_font['weight'] ); ?>;
	<?php endif; ?>
}

.site-header--beaver-builder .sub-menu a:not(.user-link),
.site-header--beaver-builder .sub-menu a span.user-mention {
	<?php if ( $settings->dropdown_font['family'] != 'Default' ) : ?>
		font-family: "<?php echo esc_attr( $settings->dropdown_font['family'] ); ?>";
		font-weight: <?php echo esc_attr( $settings->dropdown_font['weight'] ); ?>;
	<?php endif; ?>
}

.site-header--beaver-builder .header-aside .sub-menu,
.site-header--beaver-builder .header-aside .wrapper li .wrapper,
.site-header--beaver-builder .user-wrap-container .sub-menu .ab-sub-wrapper .ab-submenu,
.site-header--beaver-builder .header-aside .wrapper li .wrapper:before {
	background-color: #<?php echo esc_attr( $settings->dropdown_bg_color ); ?>;
}

.site-header--beaver-builder .header-aside .sub-menu .ab-submenu a:hover {
	background-color: transparent;
}

.site-header--beaver-builder .user-wrap > a.user-link span.user-name, 
.site-header--beaver-builder #header-aside .user-wrap > a.user-link i {
	color: #<?php echo esc_attr( $settings->user_name_color ); ?>;
}

.site-header--beaver-builder .user-wrap > a.user-link:hover span.user-name, 
.site-header--beaver-builder #header-aside .user-wrap > a.user-link:hover i {
	color: #<?php echo esc_attr( $settings->user_name_color_hover ); ?>;
}

.site-header--beaver-builder .user-wrap .sub-menu a.user-link span.user-name {
	color: #<?php echo esc_attr( $settings->dropdown_user_name_color ); ?>;
}

.site-header--beaver-builder .user-wrap .sub-menu a.user-link:hover span.user-name {
	color: #<?php echo esc_attr( $settings->dropdown_user_name_color_hover ); ?>;
}

.site-header--beaver-builder .header-aside .sub-menu a {
	background-color: #<?php echo esc_attr( $settings->menu_bg_color ); ?>;
}

.site-header--beaver-builder .header-aside .sub-menu a:hover {
	background-color: #<?php echo esc_attr( $settings->menu_bg_hover_color ); ?>;
}

.site-header--beaver-builder .header-aside .sub-menu .ab-submenu a,
.site-header--beaver-builder .header-aside .sub-menu .ab-submenu a:hover {
	background-color: transparent;
}

.site-header--beaver-builder .header-aside .sub-menu a,
.site-header--beaver-builder .header-aside .sub-menu a .user-mention {
	color: #<?php echo esc_attr( $settings->menu_color ); ?>;
}

.site-header--beaver-builder .header-aside .sub-menu a:hover,
.site-header--beaver-builder .header-aside .sub-menu a:hover .user-mention {
	color: #<?php echo esc_attr( $settings->menu_hover_color ); ?>;
}

.site-header--beaver-builder .user-wrap-container > .sub-menu:before {
	border-color: #<?php echo esc_attr( $settings->dropdown_bg_color ); ?> #<?php echo esc_attr( $settings->dropdown_bg_color ); ?> transparent transparent;
}

.site-header--beaver-builder [data-balloon]:after {
	font-size: <?php echo esc_attr( $settings->tooltips_font_size ); ?>px;
}

.site-header--beaver-builder [data-balloon]:after {
	<?php if ( $settings->tooltips_font['family'] != 'Default' ) : ?>
		font-family: "<?php echo esc_attr( $settings->tooltips_font['family'] ); ?>";
		font-weight: <?php echo esc_attr( $settings->tooltips_font['weight'] ); ?>;
	<?php endif; ?>
}

.site-header--beaver-builder .header-aside-inner > *:not(.bb-separator),
.site-header--beaver-builder .header-aside-inner > #header-messages-dropdown-elem,
.site-header--beaver-builder .header-aside-inner > #header-notifications-dropdown-elem {
	padding: 0 <?php echo esc_attr( $settings->icons_spacing ); ?>px;
}

.site-header--beaver-builder .header-aside i:not(.bb-icon-angle-down) {
	font-size: <?php echo esc_attr( $settings->icons_size ); ?>px;
}

.site-header--beaver-builder #header-aside.header-aside i:not(.bb-icon-angle-down) {
	color: #<?php echo esc_attr( $settings->icons_color ); ?>;
}

.site-header--beaver-builder .header-mini-cart ul.cart_list li.mini_cart_item > a:not(.remove) {
	display: flex;
	align-items: center;
}

.site-header--beaver-builder .bb-header-buttons a.signup,
.site-header--beaver-builder .bb-header-buttons a.signin-button {
	<?php if ( $settings->signout_font['family'] != 'Default' ) : ?>
		font-family: "<?php echo esc_attr( $settings->signout_font['family'] ); ?>";
		font-weight: <?php echo esc_attr( $settings->signout_font['weight'] ); ?>;
	<?php endif; ?>
}

.site-header--beaver-builder .bb-header-buttons a.signin-button.button {
	color: #<?php echo esc_attr( $settings->sign_in_color ); ?>;
}

.site-header--beaver-builder .bb-header-buttons a.signin-button.button:hover {
	color: #<?php echo esc_attr( $settings->sign_in_color_hover ); ?>;
}

.site-header--beaver-builder .bb-header-buttons a.signup.button {
	color: #<?php echo esc_attr( $settings->sign_up_color ); ?>;
}

.site-header--beaver-builder .bb-header-buttons a.signup.button:hover {
	color: #<?php echo esc_attr( $settings->sign_up_color_hover ); ?>;
}

.site-header--beaver-builder .bb-header-buttons a.signup.button {
	background-color: #<?php echo esc_attr( $settings->sign_up_bgr_color ); ?>;
}

.site-header--beaver-builder .bb-header-buttons a.signup.button:hover {
	background-color: #<?php echo esc_attr( $settings->sign_up_bgr_color_hover ); ?>;
}

.site-header--beaver-builder .bb-header-buttons a.signup.button {
	border-radius: <?php echo esc_attr( $settings->sign_up_border_radius ); ?>px;
}