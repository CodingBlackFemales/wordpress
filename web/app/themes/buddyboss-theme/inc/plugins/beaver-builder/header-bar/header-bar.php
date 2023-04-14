<?php

/**
 * @class BBFLHeaderBarModule
 */
class BBFLHeaderBarModule extends FLBuilderModule {
	/**
	 * Constructor function for the module. You must pass the
	 * name, description, dir and url in an array to the parent class.
	 *
	 * @method __construct
	 */
	public function __construct() {
		parent::__construct(
			array(
				'name'          => __( 'Header Bar', 'buddyboss-theme' ),
				'description'   => __( 'Header Bar BuddyBoss module.', 'buddyboss-theme' ),
				'category'      => __( 'BuddyBoss', 'buddyboss-theme' ),
				'dir'           => BEAVER_BB__DIR . 'header-bar/',
				'url'           => BEAVER_BB__URL . 'header-bar/',
				'editor_export' => true, // Defaults to true and can be omitted.
				'enabled'       => true, // Defaults to true and can be omitted.
			)
		);
	}
}

/**
 * Register the form with its fields
 */
FLBuilder::register_settings_form(
	'headerbar_field',
	array(
		'title' => __( 'Header Bar Field', 'buddyboss-theme' ),
		'tabs'  => array(
			'general' => array(
				'title'    => __( 'General', 'buddyboss-theme' ),
				'sections' => array(
					'question' => array(
						'title'  => __( 'Question', 'buddyboss-theme' ),
						'fields' => array(
							'question' => array(
								'type'  => 'text',
								'label' => '',
							),
						),
					),
					'answer'   => array(
						'title'  => __( 'Answer', 'buddyboss-theme' ),
						'fields' => array(
							'answer' => array(
								'type'          => 'editor',
								'label'         => '',
								'media_buttons' => false,
								'rows'          => 4,
							),
						),
					),
				),
			),
		),
	)
);

/**
 * Register the module and its form settings.
 */
FLBuilder::register_module(
	'BBFLHeaderBarModule',
	array(
		'general' => array(
			'title'    => __( 'General', 'buddyboss-theme' ),
			'sections' => array(
				'items' => array(
					'title'  => __( 'Content', 'buddyboss-theme' ),
					'fields' => array(
						'profile_dropdown'          => array(
							'type'    => 'select',
							'label'   => __( 'Profile Dropdown', 'buddyboss-theme' ),
							'default' => 'inline-block',
							'options' => array(
								'inline-block' => __( 'Show', 'buddyboss-theme' ),
								'none'         => __( 'Hide', 'buddyboss-theme' ),
							),
						),
						'element_separator'         => array(
							'type'    => 'select',
							'label'   => __( 'Separator', 'buddyboss-theme' ),
							'default' => 'inline-block',
							'options' => array(
								'inline-block' => __( 'Show', 'buddyboss-theme' ),
								'none'         => __( 'Hide', 'buddyboss-theme' ),
							),
						),
						'search_icon_switch'        => array(
							'type'    => 'select',
							'label'   => __( 'Search', 'buddyboss-theme' ),
							'default' => 'flex',
							'options' => array(
								'flex' => __( 'Show', 'buddyboss-theme' ),
								'none' => __( 'Hide', 'buddyboss-theme' ),
							),
						),
						'messages_icon_switch'      => ( function_exists( 'bp_is_active' ) && bp_is_active( 'messages' ) ) ? array(
							'type'    => 'select',
							'label'   => __( 'Messages', 'buddyboss-theme' ),
							'default' => 'inline-block',
							'options' => array(
								'inline-block' => __( 'Show', 'buddyboss-theme' ),
								'none'         => __( 'Hide', 'buddyboss-theme' ),
							),
						) :
						'',
						'notifications_icon_switch' => ( function_exists( 'bp_is_active' ) && bp_is_active( 'notifications' ) ) ? array(
							'type'    => 'select',
							'label'   => __( 'Notifications', 'buddyboss-theme' ),
							'default' => 'inline-block',
							'options' => array(
								'inline-block' => __( 'Show', 'buddyboss-theme' ),
								'none'         => __( 'Hide', 'buddyboss-theme' ),
							),
						) :
						'',
						'cart_icon_switch'          => class_exists( 'WooCommerce' ) ? array(
							'type'    => 'select',
							'label'   => __( 'Cart', 'buddyboss-theme' ),
							'default' => 'inline-block',
							'options' => array(
								'inline-block' => __( 'Show', 'buddyboss-theme' ),
								'none'         => __( 'Hide', 'buddyboss-theme' ),
							),
						) :
						'',
					),
				),
				'icons' => array(
					'title'  => __( 'Icons', 'buddyboss-theme' ),
					'fields' => array(
						'search_icon'        => array(
							'type'        => 'icon',
							'label'       => __( 'Search Icon', 'buddyboss-theme' ),
							'show_remove' => true,
							'preview'     => array(
								'type' => 'none',
							),
						),
						'messages_icon'      => ( function_exists( 'bp_is_active' ) && bp_is_active( 'messages' ) ) ? array(
							'type'        => 'icon',
							'label'       => __( 'Messages Icon', 'buddyboss-theme' ),
							'show_remove' => true,
							'preview'     => array(
								'type' => 'none',
							),
						) :
						'',
						'notifications_icon' => ( function_exists( 'bp_is_active' ) && bp_is_active( 'notifications' ) ) ? array(
							'type'        => 'icon',
							'label'       => __( 'Notifications Icon', 'buddyboss-theme' ),
							'show_remove' => true,
							'preview'     => array(
								'type' => 'none',
							),
						) :
						'',
						'cart_icon'          => class_exists( 'WooCommerce' ) ? array(
							'type'        => 'icon',
							'label'       => __( 'Cart Icon', 'buddyboss-theme' ),
							'show_remove' => true,
							'preview'     => array(
								'type' => 'none',
							),
						) :
						'',
					),
				),
			),
		),
		'style'   => array(
			'title'    => __( 'Style', 'buddyboss-theme' ),
			'sections' => array(
				'general'             => array(
					'title'  => __( 'Layout', 'buddyboss-theme' ),
					'fields' => array(
						'module_align'    => array(
							'type'    => 'align',
							'label'   => __( 'Alignment', 'buddyboss-theme' ),
							'default' => 'right',
							'values'  => array(
								'left'   => __( 'left', 'buddyboss-theme' ),
								'center' => __( 'center', 'buddyboss-theme' ),
								'right'  => __( 'right', 'buddyboss-theme' ),
							),
						),
						'spacing'         => array(
							'type'     => 'unit',
							'label'    => __( 'Space Between', 'buddyboss-theme' ),
							'default'  => '10',
							'sanitize' => 'absint',
							'units'    => array( 'px' ),
							'slider'   => array(
								'min'  => 5,
								'max'  => 50,
								'step' => 1,
							),
							'preview'  => array(
								'type'     => 'css',
								'selector' => '.site-header--beaver-builder .header-aside-inner > *:not(.bb-separator), .site-header--beaver-builder .header-aside-inner > #header-messages-dropdown-elem, .site-header--beaver-builder .header-aside-inner > #header-notifications-dropdown-elem',
								'property' => 'padding',
							),
						),
						'separator_width' => array(
							'type'     => 'unit',
							'label'    => __( 'Separator Width', 'buddyboss-theme' ),
							'default'  => '1',
							'sanitize' => 'absint',
							'units'    => array( 'px' ),
							'slider'   => array(
								'min'  => 1,
								'max'  => 10,
								'step' => 1,
							),
							'preview'  => array(
								'type'     => 'css',
								'selector' => '.site-header--beaver-builder .bb-separator',
								'property' => 'width',
							),
						),
						'separator_color' => array(
							'type'       => 'color',
							'label'      => __( 'Separator Color', 'buddyboss-theme' ),
							'default'    => '#DEDFE1',
							'show_reset' => true,
							'show_alpha' => false,
						),
					),
				),
				'icons'               => array(
					'title'  => __( 'Icons', 'buddyboss-theme' ),
					'fields' => array(
						'icons_size'               => array(
							'type'     => 'unit',
							'label'    => __( 'Icons Size', 'buddyboss-theme' ),
							'default'  => '21',
							'sanitize' => 'absint',
							'units'    => array( 'px' ),
							'slider'   => array(
								'min'  => 15,
								'max'  => 40,
								'step' => 1,
							),
							'preview'  => array(
								'type'     => 'css',
								'selector' => '.site-header--beaver-builder .header-aside i:not(.bb-icon-angle-down)',
								'property' => 'font-size',
							),
						),
						'icons_color'              => array(
							'type'        => 'color',
							'connections' => array( 'color' ),
							'label'       => __( 'Icons Color', 'buddyboss-theme' ),
							'default'     => '#939597',
							'show_reset'  => true,
							'preview'     => array(
								'type'      => 'css',
								'selector'  => '.site-header--beaver-builder .header-aside .header-search-link i, .site-header--beaver-builder .header-aside .messages-wrap > a i, .site-header--beaver-builder .header-aside span[data-balloon="Notifications"] i, .site-header--beaver-builder .header-aside a.header-cart-link i',
								'property'  => 'color',
								'important' => true,
							),
						),
						'search_icon_color'        => array(
							'type'        => 'color',
							'connections' => array( 'color' ),
							'label'       => __( 'Search Icon Color', 'buddyboss-theme' ),
							'default'     => '#939597',
							'show_reset'  => true,
							'preview'     => array(
								'type'      => 'css',
								'selector'  => '.site-header--beaver-builder #header-aside.header-aside .header-search-link i',
								'property'  => 'color',
								'important' => true,
							),
						),
						'messages_icon_color'      => ( function_exists( 'bp_is_active' ) && bp_is_active( 'messages' ) ) ? array(
							'type'        => 'color',
							'connections' => array( 'color' ),
							'label'       => __( 'Messages Icon Color', 'buddyboss-theme' ),
							'default'     => '#939597',
							'show_reset'  => true,
							'preview'     => array(
								'type'      => 'css',
								'selector'  => '.site-header--beaver-builder #header-aside.header-aside .messages-wrap > a i',
								'property'  => 'color',
								'important' => true,
							),
						) :
						'',
						'notifications_icon_color' => ( function_exists( 'bp_is_active' ) && bp_is_active( 'notifications' ) ) ? array(
							'type'        => 'color',
							'connections' => array( 'color' ),
							'label'       => __( 'Notifications Icon Color', 'buddyboss-theme' ),
							'default'     => '#939597',
							'show_reset'  => true,
							'preview'     => array(
								'type'      => 'css',
								'selector'  => '.site-header--beaver-builder #header-aside.header-aside span[data-balloon="Notifications"] i',
								'property'  => 'color',
								'important' => true,
							),
						) :
						'',
						'cart_icon_color'          => class_exists( 'WooCommerce' ) ? array(
							'type'        => 'color',
							'connections' => array( 'color' ),
							'label'       => __( 'Cart Icon Color', 'buddyboss-theme' ),
							'default'     => '#939597',
							'show_reset'  => true,
							'preview'     => array(
								'type'      => 'css',
								'selector'  => '.site-header--beaver-builder #header-aside.header-aside a.header-cart-link i',
								'property'  => 'color',
								'important' => true,
							),
						) :
						'',
					),
				),
				'profile_nav'         => array(
					'title'  => __( 'Profile Navigation', 'buddyboss-theme' ),
					'fields' => array(
						'avatar_size'          => array(
							'type'     => 'unit',
							'label'    => __( 'Avatar Size', 'buddyboss-theme' ),
							'default'  => '36',
							'sanitize' => 'absint',
							'units'    => array( 'px' ),
							'slider'   => array(
								'min'  => 25,
								'max'  => 50,
								'step' => 1,
							),
							'preview'  => array(
								'type'     => 'css',
								'selector' => '.site-header--beaver-builder .user-link img',
								'property' => 'max-width',
							),
						),
						'avatar_border_style'  => array(
							'type'    => 'select',
							'label'   => __( 'Avatar Border Style', 'buddyboss-theme' ),
							'default' => 'none',
							'options' => array(
								'none'   => __( 'None', 'buddyboss-theme' ),
								'solid'  => __( 'Solid', 'buddyboss-theme' ),
								'dotted' => __( 'Dotted', 'buddyboss-theme' ),
								'dashed' => __( 'Dashed', 'buddyboss-theme' ),
								'double' => __( 'Double', 'buddyboss-theme' ),
							),
						),
						'avatar_border_color'  => array(
							'type'        => 'color',
							'connections' => array( 'color' ),
							'label'       => __( 'Avatar Border Color', 'buddyboss-theme' ),
							'default'     => '#939597',
							'show_reset'  => true,
							'preview'     => array(
								'type'      => 'css',
								'selector'  => '.site-header--beaver-builder .user-link img',
								'property'  => 'border-color',
								'important' => true,
							),
						),
						'avatar_border_size'   => array(
							'type'     => 'unit',
							'label'    => __( 'Avatar Border Width', 'buddyboss-theme' ),
							'default'  => '1',
							'sanitize' => 'absint',
							'units'    => array( 'px' ),
							'slider'   => array(
								'min'  => 1,
								'max'  => 5,
								'step' => 1,
							),
							'preview'  => array(
								'type'     => 'css',
								'selector' => '.site-header--beaver-builder .user-link img',
								'property' => 'border-width',
							),
						),
						'avatar_border_radius' => array(
							'type'     => 'unit',
							'label'    => __( 'Avatar Border Radius', 'buddyboss-theme' ),
							'default'  => '50',
							'sanitize' => 'absint',
							'units'    => array( '%' ),
							'slider'   => array(
								'min'  => 0,
								'max'  => 50,
								'step' => 1,
							),
							'preview'  => array(
								'type'     => 'css',
								'selector' => '.site-header--beaver-builder .user-link img',
								'property' => 'border-radius',
							),
						),
						'user_name_font'       => array(
							'type'    => 'font',
							'label'   => __( 'Font Display Name', 'buddyboss-theme' ),
							'default' => array(
								'family' => 'Default',
								'weight' => '500',
							),
						),
						'user_name_color'  => array(
							'type'        => 'color',
							'connections' => array( 'color' ),
							'label'       => __( 'Display Name Color', 'buddyboss-theme' ),
							'default'     => '#122b46',
							'show_reset'  => true,
							'preview'     => array(
								'type'      => 'css',
								'selector'  => '.site-header--beaver-builder .user-wrap > a.user-link span.user-name, .site-header--beaver-builder #header-aside .user-wrap > a.user-link i',
								'property'  => 'color',
								'important' => true,
							),
						),
						'user_name_color_hover'  => array(
							'type'        => 'color',
							'connections' => array( 'color' ),
							'label'       => __( 'Display Name Hover Color', 'buddyboss-theme' ),
							'default'     => '#007CFF',
							'show_reset'  => true,
							'preview'     => array(
								'type'      => 'css',
								'selector'  => '.site-header--beaver-builder .user-wrap > a.user-link:hover span.user-name, .site-header--beaver-builder #header-aside .user-wrap > a.user-link:hover i',
								'property'  => 'color',
								'important' => true,
							),
						),
					),
				),
				'typography'          => array(
					'title'  => __( 'Profile Dropdown', 'buddyboss-theme' ),
					'fields' => array(
						'dropdown_font'       => array(
							'type'    => 'font',
							'label'   => __( 'Font', 'buddyboss-theme' ),
							'default' => array(
								'family' => 'Default',
								'weight' => '500',
							),
						),
						'dropdown_font_size'  => array(
							'type'     => 'unit',
							'label'    => __( 'Font Size', 'buddyboss-theme' ),
							'default'  => '13',
							'sanitize' => 'absint',
							'units'    => array( 'px' ),
							'slider'   => array(
								'min'  => 1,
								'max'  => 50,
								'step' => 1,
							),
							'preview'  => array(
								'type'     => 'css',
								'selector' => '.site-header--beaver-builder .sub-menu a:not(.user-link)',
								'property' => 'font-size',
							),
						),
						'dropdown_user_name_color'  => array(
							'type'        => 'color',
							'connections' => array( 'color' ),
							'label'       => __( 'Display Name Color', 'buddyboss-theme' ),
							'default'     => '#122b46',
							'show_reset'  => true,
							'preview'     => array(
								'type'      => 'css',
								'selector'  => '.site-header--beaver-builder .user-wrap .sub-menu a.user-link span.user-name',
								'property'  => 'color',
								'important' => true,
							),
						),
						'dropdown_user_name_color_hover'  => array(
							'type'        => 'color',
							'connections' => array( 'color' ),
							'label'       => __( 'Display Name Hover Color', 'buddyboss-theme' ),
							'default'     => '#007CFF',
							'show_reset'  => true,
							'preview'     => array(
								'type'      => 'css',
								'selector'  => '.site-header--beaver-builder .user-wrap .sub-menu a.user-link:hover span.user-name',
								'property'  => 'color',
								'important' => true,
							),
						),
						'dropdown_bg_color'   => array(
							'type'       => 'color',
							'label'      => __( 'Profile Dropdown Background Color', 'buddyboss-theme' ),
							'default'    => '#ffffff',
							'show_reset' => true,
							'show_alpha' => false,
						),
						'menu_bg_color'       => array(
							'type'       => 'color',
							'label'      => __( 'Menu Item Background Color', 'buddyboss-theme' ),
							'default'    => 'transparent',
							'show_reset' => true,
							'show_alpha' => false,
						),
						'menu_bg_hover_color' => array(
							'type'       => 'color',
							'label'      => __( 'Menu Item Hover Background Color', 'buddyboss-theme' ),
							'default'    => '#ffffff',
							'show_reset' => true,
							'show_alpha' => false,
						),
						'menu_color'          => array(
							'type'       => 'color',
							'label'      => __( 'Menu Item Color', 'buddyboss-theme' ),
							'default'    => '#939597',
							'show_reset' => true,
							'show_alpha' => false,
						),
						'menu_hover_color'    => array(
							'type'       => 'color',
							'label'      => __( 'Menu Item Hover Color', 'buddyboss-theme' ),
							'default'    => '#939597',
							'show_reset' => true,
							'show_alpha' => false,
						),
					),
				),
				'typography-tooltips' => array(
					'title'  => __( 'Tooltips', 'buddyboss-theme' ),
					'fields' => array(
						'tooltips_font'      => array(
							'type'    => 'font',
							'label'   => __( 'Font', 'buddyboss-theme' ),
							'default' => array(
								'family' => 'Default',
								'weight' => '500',
							),
						),
						'tooltips_font_size' => array(
							'type'     => 'unit',
							'label'    => __( 'Font Size', 'buddyboss-theme' ),
							'default'  => '13',
							'sanitize' => 'absint',
							'units'    => array( 'px' ),
							'slider'   => array(
								'min'  => 1,
								'max'  => 50,
								'step' => 1,
							),
							'preview'  => array(
								'type'     => 'css',
								'selector' => '.site-header--beaver-builder [data-balloon]:after',
								'property' => 'font-size',
							),
						),
					),
				),
				'sign-out' => array(
					'title'  => __( 'Logged Out', 'buddyboss-theme' ),
					'fields' => array(
						'signout_font'      => array(
							'type'    => 'font',
							'label'   => __( 'Font', 'buddyboss-theme' ),
							'default' => array(
								'family' => 'Default',
								'weight' => '500',
							),
						),
						'sign_in_color'  => array(
							'type'        => 'color',
							'connections' => array( 'color' ),
							'label'       => __( 'Sign In Color', 'buddyboss-theme' ),
							'show_reset'  => true,
							'preview'     => array(
								'type'      => 'css',
								'selector'  => '.site-header--beaver-builder .bb-header-buttons a.signin-button',
								'property'  => 'color',
								'important' => true,
							),
						),
						'sign_in_color_hover'  => array(
							'type'        => 'color',
							'connections' => array( 'color' ),
							'label'       => __( 'Sign In Hover Color', 'buddyboss-theme' ),
							'show_reset'  => true,
							'preview'     => array(
								'type'      => 'css',
								'selector'  => '.site-header--beaver-builder .bb-header-buttons a.signin-button:hover',
								'property'  => 'color',
								'important' => true,
							),
						),
						'sign_up_color'  => array(
							'type'        => 'color',
							'connections' => array( 'color' ),
							'label'       => __( 'Sign Up Color', 'buddyboss-theme' ),
							'show_reset'  => true,
							'preview'     => array(
								'type'      => 'css',
								'selector'  => '.site-header--beaver-builder .bb-header-buttons a.signup.button',
								'property'  => 'color',
								'important' => true,
							),
						),
						'sign_up_color_hover'  => array(
							'type'        => 'color',
							'connections' => array( 'color' ),
							'label'       => __( 'Sign Up Hover Color', 'buddyboss-theme' ),
							'show_reset'  => true,
							'preview'     => array(
								'type'      => 'css',
								'selector'  => '.site-header--beaver-builder .bb-header-buttons a.signup.button:hover',
								'property'  => 'color',
								'important' => true,
							),
						),
						'sign_up_bgr_color'  => array(
							'type'        => 'color',
							'connections' => array( 'color' ),
							'label'       => __( 'Sign Up Background Color', 'buddyboss-theme' ),
							'show_reset'  => true,
							'preview'     => array(
								'type'      => 'css',
								'selector'  => '.site-header--beaver-builder .bb-header-buttons a.signup.button',
								'property'  => 'background-color',
								'important' => true,
							),
						),
						'sign_up_bgr_color_hover'  => array(
							'type'        => 'color',
							'connections' => array( 'color' ),
							'label'       => __( 'Sign Up Hover Background Color', 'buddyboss-theme' ),
							'show_reset'  => true,
							'preview'     => array(
								'type'      => 'css',
								'selector'  => '.site-header--beaver-builder .bb-header-buttons a.signup.button:hover',
								'property'  => 'background-color',
								'important' => true,
							),
						),
						'sign_up_border_radius' => array(
							'type'     => 'unit',
							'label'    => __( 'Sign Up Border Radius', 'buddyboss-theme' ),
							'default'  => '100',
							'sanitize' => 'absint',
							'units'    => array( 'px' ),
							'slider'   => array(
								'min'  => 0,
								'max'  => 100,
								'step' => 1,
							),
							'preview'  => array(
								'type'     => 'css',
								'selector' => '.site-header--beaver-builder .bb-header-buttons a.signup.button',
								'property' => 'border-radius',
							),
						),
					),
				),
			),
		),
	)
);
