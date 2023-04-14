<?php

namespace BBElementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Scheme_Typography;
use Elementor\Scheme_Color;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Border;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Prevent loading templates outside of this widget.
if ( ! defined( 'BB_HEADER_BAR_WIDGET' ) ) {
	define( 'BB_HEADER_BAR_WIDGET', true );
}

/**
 * Elementor Header Bar
 *
 * Elementor widget for header bar.
 *
 * @since 1.0.0
 */
class Header_Bar extends Widget_Base {

	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 * @since  1.0.0
	 *
	 * @access public
	 */
	public function get_name() {
		return 'header-bar';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @return string Widget title.
	 * @since  1.0.0
	 *
	 * @access public
	 */
	public function get_title() {
		return __( 'Header Bar', 'buddyboss-theme' );
	}

	/**
	 * Retrieve the widget icon.
	 *
	 * @return string Widget icon.
	 * @since  1.0.0
	 *
	 * @access public
	 */
	public function get_icon() {
		return 'eicon-select';
	}

	/**
	 * Retrieve the list of categories the widget belongs to.
	 *
	 * Used to determine where to display the widget in the editor.
	 *
	 * Note that currently Elementor supports only one category.
	 * When multiple categories passed, Elementor uses the first one.
	 *
	 * @return array Widget categories.
	 * @since  1.0.0
	 *
	 * @access public
	 */
	public function get_categories() {
		return array( 'buddyboss-elements' );
	}

	/**
	 * Retrieve the list of scripts the widget depended on.
	 *
	 * Used to set scripts dependencies required to run the widget.
	 *
	 * @return array Widget scripts dependencies.
	 * @since  1.0.0
	 *
	 * @access public
	 */
	public function get_script_depends() {
		return array( 'elementor-bb-frontend' );
	}

	/**
	 * Return nav menu items.
	 *
	 * @return array
	 */
	private function get_menus() {
		$menus = wp_get_nav_menus();

		$options = array();

		foreach ( $menus as $menu ) {
			$options[ $menu->slug ] = $menu->name;
		}

		return $options;
	}

	/**
	 * Register the widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since  1.0.0
	 *
	 * @access protected
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Content', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'switch_logo',
			array(
				'label' => esc_html__( 'Show Logo Image', 'buddyboss-theme' ),
				'type'  => Controls_Manager::SWITCHER,
			)
		);

		$this->add_control(
			'switch_nav',
			array(
				'label' => esc_html__( 'Show Navigation', 'buddyboss-theme' ),
				'type'  => Controls_Manager::SWITCHER,
			)
		);

		$menus = $this->get_menus();

		if ( ! empty( $menus ) ) {
			$this->add_control(
				'menu_marker',
				array(
					'label'        => __( 'Menu', 'buddyboss-theme' ),
					'type'         => Controls_Manager::SELECT,
					'options'      => $menus,
					'default'      => array_keys( $menus )[0],
					'save_default' => true,
					'separator'    => 'after',
					'condition'    => array(
						'switch_nav' => 'yes',
					),
				)
			);
		} else {
			$this->add_control(
				'menu_marker',
				array(
					'type'            => Controls_Manager::RAW_HTML,
					'raw'             => '<strong>' . __( 'There are no menus available.', 'buddyboss-theme' ) . '</strong><br>' . sprintf( __( 'Start by creating one <a href="%s" target="_blank">here</a>.', 'buddyboss-theme' ), admin_url( 'nav-menus.php?action=edit&menu=0' ) ),
					'separator'       => 'after',
					'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
					'condition'       => array(
						'switch_nav' => 'yes',
					),
				)
			);
		}

		$this->add_control(
			'switch_bar',
			array(
				'label'   => esc_html__( 'Show Header Bar', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_responsive_control(
			'logo_position',
			array(
				'label'        => esc_html__( 'Logo Position', 'buddyboss-theme' ),
				'type'         => Controls_Manager::CHOOSE,
				'label_block'  => false,
				'options'      => array(
					'left'  => array(
						'title' => esc_html__( 'Left', 'buddyboss-theme' ),
						'icon'  => 'eicon-h-align-left',
					),
					'right' => array(
						'title' => esc_html__( 'Right', 'buddyboss-theme' ),
						'icon'  => 'eicon-h-align-right',
					),
				),
				'default'      => 'left',
				'prefix_class' => 'elementor-element--logo-position-',
				'condition'    => array(
					'switch_logo' => 'yes',
					'switch_nav'  => 'yes',
					'switch_bar!' => 'yes',
				),
			)
		);

		$this->add_responsive_control(
			'logo_position_full',
			array(
				'label'        => __( 'Logo Position', 'buddyboss-theme' ),
				'type'         => Controls_Manager::CHOOSE,
				'label_block'  => false,
				'options'      => array(
					'left'   => array(
						'title' => __( 'Left', 'buddyboss-theme' ),
						'icon'  => 'eicon-h-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'Center', 'buddyboss-theme' ),
						'icon'  => 'eicon-h-align-center',
					),
					'right'  => array(
						'title' => __( 'Right', 'buddyboss-theme' ),
						'icon'  => 'eicon-h-align-right',
					),
				),
				'default'      => 'left',
				'prefix_class' => 'elementor-element--logo-position-full-',
				'condition'    => array(
					'switch_logo' => 'yes',
					'switch_nav'  => 'yes',
					'switch_bar'  => 'yes',
				),
			)
		);

		$this->add_control(
			'profile_dropdown',
			array(
				'label'        => esc_html__( 'Profile Dropdown', 'buddyboss-theme' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'buddyboss-theme' ),
				'label_off'    => esc_html__( 'Off', 'buddyboss-theme' ),
				'return_value' => 'inline-block',
				'default'      => 'inline-block',
				'selectors'    => array(
					'{{WRAPPER}} .user-wrap' => 'display: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'element_separator',
			array(
				'label'        => esc_html__( 'Separator', 'buddyboss-theme' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'buddyboss-theme' ),
				'label_off'    => esc_html__( 'Off', 'buddyboss-theme' ),
				'return_value' => 'inline-block',
				'default'      => 'inline-block',
				'selectors'    => array(
					'{{WRAPPER}} .bb-separator' => 'display: {{VALUE}};',
				),
			)
		);

		if ( buddyboss_theme_get_option( 'desktop_component_opt_multi_checkbox', 'desktop_header_search' ) ) :
			$this->add_control(
				'search_icon_switch',
				array(
					'label'        => esc_html__( 'Search', 'buddyboss-theme' ),
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'On', 'buddyboss-theme' ),
					'label_off'    => esc_html__( 'Off', 'buddyboss-theme' ),
					'return_value' => 'flex',
					'default'      => 'flex',
					'selectors'    => array(
						'{{WRAPPER}} .header-search-link' => 'display: {{VALUE}};',
					),
				)
			);
		endif;

		if (
			function_exists( 'bp_is_active' ) &&
			bp_is_active( 'messages' ) &&
			buddyboss_theme_get_option( 'desktop_component_opt_multi_checkbox', 'desktop_messages' )
		) :
			$this->add_control(
				'messages_icon_switch',
				array(
					'label'        => esc_html__( 'Messages', 'buddyboss-theme' ),
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'On', 'buddyboss-theme' ),
					'label_off'    => esc_html__( 'Off', 'buddyboss-theme' ),
					'return_value' => 'inline-block',
					'default'      => 'inline-block',
					'selectors'    => array(
						'{{WRAPPER}} #header-messages-dropdown-elem' => 'display: {{VALUE}};',
					),
				)
			);
		endif;

		if (
			function_exists( 'bp_is_active' ) &&
			bp_is_active( 'notifications' ) &&
			buddyboss_theme_get_option( 'desktop_component_opt_multi_checkbox', 'desktop_notifications' )
		) :
			$this->add_control(
				'notifications_icon_switch',
				array(
					'label'        => esc_html__( 'Notifications', 'buddyboss-theme' ),
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'On', 'buddyboss-theme' ),
					'label_off'    => esc_html__( 'Off', 'buddyboss-theme' ),
					'return_value' => 'inline-block',
					'default'      => 'inline-block',
					'selectors'    => array(
						'{{WRAPPER}} #header-notifications-dropdown-elem' => 'display: {{VALUE}};',
					),
				)
			);
		endif;

		if (
			class_exists( 'WooCommerce' ) &&
			buddyboss_theme_get_option( 'desktop_component_opt_multi_checkbox', 'desktop_shopping_cart' )
		) :
			$this->add_control(
				'cart_icon_switch',
				array(
					'label'        => esc_html__( 'Cart', 'buddyboss-theme' ),
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'On', 'buddyboss-theme' ),
					'label_off'    => esc_html__( 'Off', 'buddyboss-theme' ),
					'return_value' => 'inline-block',
					'default'      => 'inline-block',
					'selectors'    => array(
						'{{WRAPPER}} .header-cart-link-wrap' => 'display: {{VALUE}};',
					),
				)
			);
		endif;

		if ( class_exists( 'SFWD_LMS' ) ) :
			$this->add_control(
				'dark_icon_switch',
				array(
					'label'        => esc_html__( 'Dark Mode', 'buddyboss-theme' ),
					'description'  => esc_html__( 'Show "dark mode" toggle icon on a single lesson/topic page.', 'buddyboss-theme' ),
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'On', 'buddyboss-theme' ),
					'label_off'    => esc_html__( 'Off', 'buddyboss-theme' ),
					'return_value' => 'flex',
					'default'      => 'flex',
					'selectors'    => array(
						'{{WRAPPER}} #bb-toggle-theme' => 'display: {{VALUE}};',
					),
				)
			);

			$this->add_control(
				'sidebartoggle_icon_switch',
				array(
					'label'        => esc_html__( 'Sidebar Toggle', 'buddyboss-theme' ),
					'description'  => esc_html__( 'Show "sidebar" toggle icon on a single lesson/topic page.', 'buddyboss-theme' ),
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'On', 'buddyboss-theme' ),
					'label_off'    => esc_html__( 'Off', 'buddyboss-theme' ),
					'return_value' => 'flex',
					'default'      => 'flex',
					'selectors'    => array(
						'{{WRAPPER}} .header-minimize-link' => 'display: {{VALUE}};',
						'{{WRAPPER}} .header-maximize-link' => 'display: {{VALUE}};',
					),
				)
			);
		endif;

		$this->end_controls_section();

		$this->start_controls_section(
			'section_icons',
			array(
				'label' => __( 'Icons', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'search_icon',
			array(
				'label'                  => esc_html__( 'Search Icon', 'buddyboss-theme' ),
				'description'            => esc_html__( 'Replace default search icon with one of your choice.', 'buddyboss-theme' ),
				'type'                   => \Elementor\Controls_Manager::ICONS,
				'skin'                   => 'inline',
				'exclude_inline_options' => array(
					'svg',
				),
			)
		);

		if ( function_exists( 'bp_is_active' ) && bp_is_active( 'messages' ) ) :
			$this->add_control(
				'messages_icon',
				array(
					'label'                  => esc_html__( 'Messages Icon', 'buddyboss-theme' ),
					'description'            => esc_html__( 'Replace default messages icon with one of your choice.', 'buddyboss-theme' ),
					'type'                   => \Elementor\Controls_Manager::ICONS,
					'skin'                   => 'inline',
					'exclude_inline_options' => array(
						'svg',
					),
				)
			);
		endif;

		if ( function_exists( 'bp_is_active' ) && bp_is_active( 'notifications' ) ) :
			$this->add_control(
				'notifications_icon',
				array(
					'label'                  => esc_html__( 'Notifications Icon', 'buddyboss-theme' ),
					'description'            => esc_html__( 'Replace default notifications icon with one of your choice.', 'buddyboss-theme' ),
					'type'                   => \Elementor\Controls_Manager::ICONS,
					'skin'                   => 'inline',
					'exclude_inline_options' => array(
						'svg',
					),
				)
			);
		endif;

		if ( class_exists( 'WooCommerce' ) ) :
			$this->add_control(
				'cart_icon',
				array(
					'label'                  => esc_html__( 'Cart Icon', 'buddyboss-theme' ),
					'description'            => esc_html__( 'Replace default cart icon with one of your choice.', 'buddyboss-theme' ),
					'type'                   => \Elementor\Controls_Manager::ICONS,
					'skin'                   => 'inline',
					'exclude_inline_options' => array(
						'svg',
					),
				)
			);
		endif;

		if ( class_exists( 'SFWD_LMS' ) ) :
			$this->add_control(
				'dark_icon',
				array(
					'label'                  => esc_html__( 'Dark Mode Icon', 'buddyboss-theme' ),
					'description'            => esc_html__( 'Replace default dark mode icon with one of your choice.', 'buddyboss-theme' ),
					'type'                   => \Elementor\Controls_Manager::ICONS,
					'skin'                   => 'inline',
					'exclude_inline_options' => array(
						'svg',
					),
				)
			);

			$this->add_control(
				'sidebartoggle_icon',
				array(
					'label'                  => esc_html__( 'Toggle Sidebar Icon', 'buddyboss-theme' ),
					'description'            => esc_html__( 'Replace default toggle sidebar icon with one of your choice.', 'buddyboss-theme' ),
					'type'                   => \Elementor\Controls_Manager::ICONS,
					'skin'                   => 'inline',
					'exclude_inline_options' => array(
						'svg',
					),
				)
			);
		endif;

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_nav',
			array(
				'label'     => __( 'Navigation', 'buddyboss-theme' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'switch_nav' => 'yes',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_nav',
				'label'    => esc_html__( 'Typography', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .primary-menu > li > a',
			)
		);

		$this->start_controls_tabs(
			'nav_color_tabs'
		);

		$this->start_controls_tab(
			'nav_color_normal_tab',
			array(
				'label' => esc_html__( 'Normal', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'nav_item_color',
			array(
				'label'     => esc_html__( 'Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .primary-menu > li > a' => 'color: {{VALUE}}',
					'{{WRAPPER}} .primary-menu > .menu-item-has-children:not(.hideshow):after' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'nav_color_active_tab',
			array(
				'label' => esc_html__( 'Active', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'nav_item_color_active',
			array(
				'label'     => esc_html__( 'Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .primary-menu > .current-menu-item > a' => 'color: {{VALUE}}',
					'{{WRAPPER}} .primary-menu .current_page_item > a'   => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'nav_color_hover_tab',
			array(
				'label' => esc_html__( 'Hover', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'nav_item_color_hover',
			array(
				'label'     => esc_html__( 'Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .primary-menu > li > a:hover' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'sub_menu',
			array(
				'label'     => esc_html__( 'Sub Menu', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_sub_nav',
				'label'    => esc_html__( 'Typography', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .site-header .main-navigation .sub-menu a',
			)
		);

		$this->start_controls_tabs(
			'sub_nav_color_tabs'
		);

		$this->start_controls_tab(
			'sub_nav_color_normal_tab',
			array(
				'label' => esc_html__( 'Normal', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'sub_nav_item_color',
			array(
				'label'     => esc_html__( 'Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .site-header .main-navigation .sub-menu a' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'sub_nav_color_active_tab',
			array(
				'label' => esc_html__( 'Active', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'sub_nav_item_color_active',
			array(
				'label'     => esc_html__( 'Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .site-header .sub-menu .main-navigation .current-menu-item > a' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'sub_nav_color_hover_tab',
			array(
				'label' => esc_html__( 'Hover', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'sub_nav_item_color_hover',
			array(
				'label'     => esc_html__( 'Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .site-header .main-navigation .sub-menu a:hover' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_layout',
			array(
				'label'     => esc_html__( 'Header Bar Layout', 'buddyboss-theme' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'switch_bar' => 'yes',
				),
			)
		);

		$this->add_control(
			'content_align',
			array(
				'label'     => esc_html__( 'Alignment', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array(
						'title' => esc_html__( 'Left', 'buddyboss-theme' ),
						'icon'  => 'fa fa-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'Center', 'buddyboss-theme' ),
						'icon'  => 'fa fa-align-center',
					),
					'right'  => array(
						'title' => esc_html__( 'Right', 'buddyboss-theme' ),
						'icon'  => 'fa fa-align-right',
					),
				),
				'default'   => 'right',
				'toggle'    => true,
				'condition' => array(
					'switch_logo!' => 'yes',
					'switch_nav!'  => 'yes',
				),
			)
		);

		$this->add_control(
			'space_between',
			array(
				'label'      => esc_html__( 'Space Between', 'buddyboss-theme' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 5,
						'max'  => 50,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 10,
				),
				'selectors'  => array(
					'{{WRAPPER}} .header-aside-inner > *:not(.bb-separator)' => 'padding: 0 {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} #header-messages-dropdown-elem'             => 'padding: 0 {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} #header-notifications-dropdown-elem'        => 'padding: 0 {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'separator',
			array(
				'label'     => esc_html__( 'Separator', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'separator_width',
			array(
				'label'      => esc_html__( 'Separator Width', 'buddyboss-theme' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 1,
						'max'  => 10,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 1,
				),
				'selectors'  => array(
					'{{WRAPPER}} .bb-separator' => 'width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'separator_color',
			array(
				'label'     => esc_html__( 'Separator Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(0, 0, 0, 0.1)',
				'selectors' => array(
					'{{WRAPPER}} .bb-separator' => 'background-color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'tooltips_options',
			array(
				'label'     => esc_html__( 'Tooltips', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_tooltips',
				'label'    => esc_html__( 'Typography Tooltips', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} [data-balloon]:after',
			)
		);

		$this->add_control(
			'counter_options',
			array(
				'label'     => esc_html__( 'Counter', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'count_bgcolor',
			array(
				'label'     => esc_html__( 'Counter Background Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#EF3E46',
				'selectors' => array(
					'{{WRAPPER}} .notification-wrap span.count' => 'background-color: {{VALUE}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'counter_shadow',
				'label'    => esc_html__( 'Counter Shadow', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .notification-wrap span.count',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_icons',
			array(
				'label'     => esc_html__( 'Icons', 'buddyboss-theme' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'switch_bar' => 'yes',
				),
			)
		);

		$this->add_control(
			'icons_size',
			array(
				'label'      => esc_html__( 'Icons Size', 'buddyboss-theme' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 15,
						'max'  => 40,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 21,
				),
				'selectors'  => array(
					'{{WRAPPER}} .header-aside .header-search-link i'                => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .header-aside .messages-wrap > a i'                 => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .header-aside span[data-balloon="Notifications"] i' => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .header-aside a.header-cart-link i'                 => 'font-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			array(
				'name'     => 'icons_shadow',
				'label'    => esc_html__( 'Icons Shadow', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .header-aside i:not(.bb-icon-angle-down)',
			)
		);

		$this->add_control(
			'separator_icons',
			array(
				'label'     => esc_html__( 'Icons Colors', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'icons_color',
			array(
				'label'     => esc_html__( 'All Icons', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#939597',
				'selectors' => array(
					'{{WRAPPER}} #header-aside.header-aside .header-search-link i'                => 'color: {{VALUE}}',
					'{{WRAPPER}} #header-aside.header-aside .messages-wrap > a i'                 => 'color: {{VALUE}}',
					'{{WRAPPER}} #header-aside.header-aside span[data-balloon="Notifications"] i' => 'color: {{VALUE}}',
					'{{WRAPPER}} #header-aside.header-aside a.header-cart-link i'                 => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'search_icon_color',
			array(
				'label'     => esc_html__( 'Search Icon', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => array(
					'{{WRAPPER}} .header-aside .header-search-link i' => 'color: {{VALUE}} !important',
				),
			)
		);

		if ( function_exists( 'bp_is_active' ) && bp_is_active( 'messages' ) ) :
			$this->add_control(
				'messages_icon_color',
				array(
					'label'     => esc_html__( 'Messages Icon', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'default'   => '',
					'selectors' => array(
						'{{WRAPPER}} .header-aside .messages-wrap > a i' => 'color: {{VALUE}} !important',
					),
				)
			);
		endif;

		if ( function_exists( 'bp_is_active' ) && bp_is_active( 'notifications' ) ) :
			$this->add_control(
				'notifications_icon_color',
				array(
					'label'     => esc_html__( 'Notifications Icon', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'default'   => '',
					'selectors' => array(
						'{{WRAPPER}} .header-aside span[data-balloon="Notifications"] i' => 'color: {{VALUE}} !important',
					),
				)
			);
		endif;

		if ( class_exists( 'WooCommerce' ) ) :
			$this->add_control(
				'cart_icon_color',
				array(
					'label'     => esc_html__( 'Cart Icon', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'default'   => '',
					'selectors' => array(
						'{{WRAPPER}} .header-aside a.header-cart-link i' => 'color: {{VALUE}} !important',
					),
				)
			);
		endif;

		if ( class_exists( 'SFWD_LMS' ) ) :
			$this->add_control(
				'dark_icon_color',
				array(
					'label'     => esc_html__( 'Dark Icon', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'default'   => '',
					'selectors' => array(
						'{{WRAPPER}} .header-aside a#bb-toggle-theme i' => 'color: {{VALUE}} !important',
					),
				)
			);

			$this->add_control(
				'sidebartoggle_icon_color',
				array(
					'label'     => esc_html__( 'Sidebar Toggle Icon', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'default'   => '',
					'selectors' => array(
						'{{WRAPPER}} .header-aside a.course-toggle-view i' => 'color: {{VALUE}} !important',
					),
				)
			);
		endif;

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_profile',
			array(
				'label'     => esc_html__( 'Profile Navigation', 'buddyboss-theme' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'switch_bar' => 'yes',
				),
			)
		);

		$this->add_control(
			'separator_user_name',
			array(
				'label'     => esc_html__( 'Display Name', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_user_link',
				'label'    => esc_html__( 'Typography Display Name', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .site-header--elementor .user-wrap a span.user-name',
			)
		);

		$this->start_controls_tabs(
			'color_name_tabs'
		);

		$this->start_controls_tab(
			'color_name_normal_tab',
			array(
				'label' => esc_html__( 'Normal', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'user_name_item_color',
			array(
				'label'     => esc_html__( 'Display Name Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .site-header--elementor .user-wrap > a.user-link span.user-name'  => 'color: {{VALUE}}',
					'{{WRAPPER}} .site-header--elementor #header-aside .user-wrap > a.user-link i' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'color_name_hover_tab',
			array(
				'label' => esc_html__( 'Hover', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'user_name_item_color_hover',
			array(
				'label'     => esc_html__( 'Display Name Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#007CFF',
				'selectors' => array(
					'{{WRAPPER}} .site-header--elementor .user-wrap > a.user-link:hover span.user-name'  => 'color: {{VALUE}}',
					'{{WRAPPER}} .site-header--elementor #header-aside .user-wrap > a.user-link:hover i' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'separator_avatar',
			array(
				'label'     => esc_html__( 'Avatar', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'avatar_size',
			array(
				'label'      => esc_html__( 'Width', 'buddyboss-theme' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 25,
						'max'  => 50,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 36,
				),
				'selectors'  => array(
					'{{WRAPPER}} .user-link img' => 'max-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'avatar_border_style',
			array(
				'label'   => esc_html__( 'Border Style', 'buddyboss-theme' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'none',
				'options' => array(
					'solid'  => __( 'Solid', 'buddyboss-theme' ),
					'dashed' => __( 'Dashed', 'buddyboss-theme' ),
					'dotted' => __( 'Dotted', 'buddyboss-theme' ),
					'double' => __( 'Double', 'buddyboss-theme' ),
					'none'   => __( 'None', 'buddyboss-theme' ),
				),
			)
		);

		$this->add_control(
			'avatar_border_width',
			array(
				'label'      => esc_html__( 'Border Width', 'buddyboss-theme' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 1,
						'max'  => 5,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 1,
				),
				'selectors'  => array(
					'{{WRAPPER}} .user-link img' => 'border-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'avatar_border_color',
			array(
				'label'     => esc_html__( 'Border Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#939597',
				'selectors' => array(
					'{{WRAPPER}} .user-link img' => 'border-color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'avatar_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'buddyboss-theme' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array(
					'%' => array(
						'min'  => 0,
						'max'  => 50,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => '%',
					'size' => 50,
				),
				'selectors'  => array(
					'{{WRAPPER}} .user-link img' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'separator_dropdown',
			array(
				'label'     => esc_html__( 'Dropdown', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'separator_dropdown_user_name',
			array(
				'label'     => esc_html__( 'Display Name', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->start_controls_tabs(
			'color_dropdown_name_tabs'
		);

		$this->start_controls_tab(
			'color_dropdown_name_normal_tab',
			array(
				'label' => esc_html__( 'Normal', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'dropdown_user_name_item_color',
			array(
				'label'     => esc_html__( 'Display Name Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#122b46',
				'selectors' => array(
					'{{WRAPPER}}  .site-header--elementor .user-wrap .sub-menu a.user-link span.user-name' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'color_dropdown_name_hover_tab',
			array(
				'label' => esc_html__( 'Hover', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'dropdown_user_name_item_color_hover',
			array(
				'label'     => esc_html__( 'Display Name Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#007CFF',
				'selectors' => array(
					'{{WRAPPER}}  .site-header--elementor .user-wrap .sub-menu a.user-link:hover span.user-name' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_menu',
				'label'    => esc_html__( 'Typography Menu', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .site-header--elementor .sub-menu a:not(.user-link), {{WRAPPER}} .site-header--elementor .sub-menu a span.user-mention',
			)
		);

		$this->add_control(
			'dropdown_bgcolor',
			array(
				'label'     => esc_html__( 'Dropdown Background Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .site-header .sub-menu' => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .user-wrap-container > .sub-menu:before' => 'border-color: {{VALUE}} {{VALUE}} transparent transparent',
					'{{WRAPPER}} .header-aside .wrapper li .wrapper' => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .user-wrap-container .sub-menu .ab-sub-wrapper .ab-submenu' => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .header-aside .wrapper li .wrapper:before' => 'background: {{VALUE}}',
				),
			)
		);

		$this->start_controls_tabs(
			'dropdown_menu_tabs'
		);

		$this->start_controls_tab(
			'dropdown_normal_tab',
			array(
				'label' => esc_html__( 'Normal', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'dropdown_menu_item_bgcolor',
			array(
				'label'     => esc_html__( 'Menu Item Background Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'transparent',
				'selectors' => array(
					'{{WRAPPER}} .site-header .header-aside .sub-menu a' => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .site-header .sub-menu .ab-submenu a'   => 'background-color: transparent',
				),
			)
		);

		$this->add_control(
			'dropdown_menu_item_color',
			array(
				'label'     => esc_html__( 'Menu Item Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#939597',
				'selectors' => array(
					'{{WRAPPER}} .site-header .header-aside .sub-menu a'               => 'color: {{VALUE}}',
					'{{WRAPPER}} .site-header .header-aside .sub-menu a .user-mention' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'dropdown_hover_tab',
			array(
				'label' => esc_html__( 'Hover', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'dropdown_menu_item_bgcolor_hover',
			array(
				'label'     => esc_html__( 'Menu Item Background Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .site-header .header-aside .sub-menu a:hover' => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .site-header .sub-menu .ab-submenu a:hover'   => 'background-color: transparent',
				),
			)
		);

		$this->add_control(
			'dropdown_menu_item_color_hover',
			array(
				'label'     => esc_html__( 'Menu Item Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#939597',
				'selectors' => array(
					'{{WRAPPER}} .site-header .header-aside .sub-menu a:hover'               => 'color: {{VALUE}}',
					'{{WRAPPER}} .site-header .header-aside .sub-menu a:hover .user-mention' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_signout',
			array(
				'label'     => esc_html__( 'Logged Out', 'buddyboss-theme' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'switch_bar' => 'yes',
				),
			)
		);

		$this->add_control(
			'separator_sign_in',
			array(
				'label'     => esc_html__( 'Sign In', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_sign_in',
				'label'    => esc_html__( 'Typography Sign In', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .site-header--elementor .bb-header-buttons a.signin-button',
			)
		);

		$this->start_controls_tabs(
			'color_signin_tabs'
		);

		$this->start_controls_tab(
			'color_signin_normal_tab',
			array(
				'label' => esc_html__( 'Normal', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'signin_item_color',
			array(
				'label'     => esc_html__( 'Sign In Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}}  .site-header--elementor .bb-header-buttons a.signin-button' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'color_signin_hover_tab',
			array(
				'label' => esc_html__( 'Hover', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'signin_item_color_hover',
			array(
				'label'     => esc_html__( 'Sign In Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}}  .site-header--elementor .bb-header-buttons a.signin-button:hover' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'separator_sign_up',
			array(
				'label'     => esc_html__( 'Sign Up', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_sign_up',
				'label'    => esc_html__( 'Typography Sign Up', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .site-header--elementor .bb-header-buttons a.signup',
			)
		);

		$this->start_controls_tabs(
			'color_signup_tabs'
		);

		$this->start_controls_tab(
			'color_signup_normal_tab',
			array(
				'label' => esc_html__( 'Normal', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'signup_item_color',
			array(
				'label'     => esc_html__( 'Sign Up Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .site-header--elementor .bb-header-buttons a.signup' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'signup_item_bgr_color',
			array(
				'label'     => esc_html__( 'Sign Up Background Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .site-header--elementor .bb-header-buttons a.signup' => 'background-color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'color_signup_hover_tab',
			array(
				'label' => esc_html__( 'Hover', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'signup_item_color_hover',
			array(
				'label'     => esc_html__( 'Sign Up Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .site-header--elementor .bb-header-buttons a.signup:hover' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'signup_item_bgr_color_hover',
			array(
				'label'     => esc_html__( 'Sign Up Background Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .site-header--elementor .bb-header-buttons a.signup:hover' => 'background-color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'        => 'signup_border',
				'label'       => esc_html__( 'Border', 'buddyboss-theme' ),
				'placeholder' => '1px',
				'default'     => '1px',
				'selector'    => '{{WRAPPER}} .site-header--elementor .bb-header-buttons a.signup',
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'signup_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'buddyboss-theme' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .site-header--elementor .bb-header-buttons a.signup' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

	}

	/**
	 * Render the widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since  1.0.0
	 *
	 * @access protected
	 */
	protected function render() {
		$settings = $this->get_settings();

		$template_path = ELEMENTOR_BB__DIR__ . '/widgets/header-bar/templates/bb-header-bar-template.php';

		if ( file_exists( $template_path ) ) {
			require $template_path;
		}

	}

}
