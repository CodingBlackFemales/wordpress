<?php
namespace BBElementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Core\Schemes;
use Elementor\Group_Control_Border;
use Elementor\Repeater;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * @since 1.1.0
 */
class BB_Tabs extends Widget_Base {

	/**
	 * Retrieve the widget name.
	 *
	 * @since 1.1.0
	 *
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'bb-tabs';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @since 1.1.0
	 *
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Tabbed Content', 'buddyboss-theme' );
	}

	/**
	 * Retrieve the widget icon.
	 *
	 * @since 1.1.0
	 *
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-tabs';
	}

	/**
	 * Retrieve the list of categories the widget belongs to.
	 *
	 * Used to determine where to display the widget in the editor.
	 *
	 * Note that currently Elementor supports only one category.
	 * When multiple categories passed, Elementor uses the first one.
	 *
	 * @since 1.1.0
	 *
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'buddyboss-elements' ];
	}

	/**
	 * Retrieve the list of scripts the widget depended on.
	 *
	 * Used to set scripts dependencies required to run the widget.
	 *
	 * @return array Widget scripts dependencies.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_script_depends() {
		return array( 'elementor-bb-frontend' );
	}

	/**
	 * Register the widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 1.1.0
	 *
	 * @access protected
	 */
	protected function register_controls() {
		
		$this->start_controls_section(
			'section_content_tabs',
			[
				'label'     => esc_html__( 'Tabs', 'buddyboss-theme' ),
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'tab_title',
			[
				'label' => __( 'Tab', 'buddyboss-theme' ),
				'type' => Controls_Manager::TEXT,
				'default' => '',
				'label_block' => 'true',
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$repeater->add_control(
			'title',
			[
				'label' => __( 'Title', 'buddyboss-theme' ),
				'type' => Controls_Manager::TEXT,
				'default' => '',
				'label_block' => 'true',
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$repeater->add_control(
			'item_excerpt',
			[
				'label' => __( 'Excerpt', 'buddyboss-theme' ),
				'type' => Controls_Manager::WYSIWYG,
				'default' => __( '', 'buddyboss-theme' ),
				'placeholder' => __( 'Type your description here', 'buddyboss-theme' ),
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$repeater->add_control(
			'image',
			[
				'label' => __( 'Image', 'buddyboss-theme' ),
				'type' => Controls_Manager::MEDIA,
				'default' => [],
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$repeater->add_control(
			'link',
			[
				'label' => __( 'Link', 'buddyboss-theme' ),
				'type' => Controls_Manager::URL,
				'default' => [ 'url' => '' ],
				'dynamic' => [
					'active' => true,
				],
				'placeholder' => __( 'https://your-link.com', 'buddyboss-theme' ),
			]
		);

		$repeater->add_control(
			'link_text',
			[
				'label' => __( 'Link Text', 'buddyboss-theme' ),
				'type' => Controls_Manager::TEXT,
				'default' => '',
				'label_block' => 'true',
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'tab_list',
			[
				'label' => __( 'Items', 'buddyboss-theme' ),
				'type' => Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
				'default' => [
					[
						'tab_title' => __( 'Tab #1', 'buddyboss-theme' ),
						'title' => __( 'Title #1', 'buddyboss-theme' ),
						'item_excerpt' => __( 'Sed augue ipsum, egestas nec, vestibulum et, malesuada adipiscing, dui. Vestibulum dapibus nunc ac augue. Aenean tellus metus, bibendum sed, posuere ac, mattis non, nunc. Aenean imperdiet. Aenean imperdiet.', 'buddyboss-theme' ),
						'link' => [ 'url' => '' ],
						'link_text' => __( 'Learn more', 'buddyboss-theme' ),
					],
					[
						'tab_title' => __( 'Tab #2', 'buddyboss-theme' ),
						'title' => __( 'Title #2', 'buddyboss-theme' ),
						'item_excerpt' => __( 'Nunc egestas, augue at pellentesque laoreet, felis eros vehicula leo, at malesuada velit leo quis pede. Proin viverra, ligula sit amet ultrices semper, ligula arcu tristique sapien, a accumsan nisi mauris ac eros. Sed aliquam ultrices mauris. Morbi nec metus. Donec mi odio, faucibus at, scelerisque quis, convallis in, nisi.', 'buddyboss-theme' ),
						'link' => [ 'url' => '' ],
						'link_text' => __( 'Learn more', 'buddyboss-theme' ),
					],
					[
						'tab_title' => __( 'Tab #3', 'buddyboss-theme' ),
						'title' => __( 'Title #3', 'buddyboss-theme' ),
						'item_excerpt' => __( 'Morbi mattis ullamcorper velit. Sed hendrerit. Suspendisse enim turpis, dictum sed, iaculis a, condimentum nec, nisi. Vestibulum facilisis, purus nec pulvinar iaculis, ligula mi congue nunc, vitae euismod ligula urna in dolor. Cras dapibus.', 'buddyboss-theme' ),
						'link' => [ 'url' => '' ],
						'link_text' => __( 'Learn more', 'buddyboss-theme' ),
					],
				],
				'title_field' => '{{{ title }}}',
			]
		);

		$this->add_control(
			'separator_tabs_type',
			array(
				'label'     => __( 'Type', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'tabs_style',
			array(
				'label'   => __( 'Style', 'buddyboss-theme' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'style1',
				'options' => array(
					'style1'  => __( 'Style 1', 'buddyboss-theme' ),
					'style2' => __( 'Style 2', 'buddyboss-theme' ),
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_style1',
			[
				'label'     => esc_html__( 'Style', 'buddyboss-theme' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'tabs_style' => 'style1',
				],
			]
		);

		$this->add_control(
			'switch_arrows',
			[
				'label'   => esc_html__( 'Show Navigation Arrows', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->add_control(
			'switch_dots',
			[
				'label'   => esc_html__( 'Show Navigation Dots', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->add_control(
			'separator_content1',
			array(
				'label'     => __( 'Content', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'media1_position',
			[
				'label' => __( 'Media Position', 'buddyboss-theme' ),
				'type' => Controls_Manager::CHOOSE,
				'label_block' => false,
				'options' => [
					'left' => [
						'title' => __( 'Left', 'buddyboss-theme' ),
						'icon' => 'eicon-h-align-left',
					],
					'right' => [
						'title' => __( 'Right', 'buddyboss-theme' ),
						'icon' => 'eicon-h-align-right',
					],
				],
				'default' => 'right',
				'prefix_class' => 'elementor-cta-%s-meadia-align-',
			]
		);

		$this->add_control(
			'media1_ratio',
			array(
				'label'      => __( 'Media Ratio', 'buddyboss-theme' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array(
					'%' => array(
						'min'  => 10,
						'max'  => 150,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => '%',
					'size' => 75,
				),
				'selectors'  => array(
					'{{WRAPPER}} .bb-tabs__image .media-container' => 'padding-top: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'media1_width',
			array(
				'label'      => __( 'Media Width', 'buddyboss-theme' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array(
					'%' => array(
						'min'  => 25,
						'max'  => 75,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => '%',
					'size' => 49,
				),
				'selectors'  => array(
					'{{WRAPPER}} .tabs-wrapper--style1 .bb-tabs__ismedia .bb-tabs__image' => 'flex: 0 0 {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'content1_padding',
			[
				'label'      => __( 'Content Padding', 'buddyboss-theme' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default' => [
					'top' => '50',
					'right' => '50',
					'bottom' => '50',
					'left' => '50',
				],
				'selectors'  => [
					'{{WRAPPER}} .tabs-wrapper--style1 .bb-tabs__body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'content1_bgr_color',
				'label' => __( 'Background', 'buddyboss-theme' ),
				'types' => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .tabs-wrapper--style1 .bb-tabs__body',
			]
		);

		$this->add_control(
			'separator_tabs1',
			array(
				'label'     => __( 'Tabs', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'tabs1_style',
			array(
				'label'   => __( 'Style', 'buddyboss-theme' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'outline',
				'options' => array(
					'outline'  => __( 'Outline', 'buddyboss-theme' ),
					'underline' => __( 'Underline', 'buddyboss-theme' ),
				),
				'condition' => [
					'tabs_style' => 'style1',
				],
			)
		);

		$this->add_responsive_control(
			'tabs1_alignment',
			[
				'label' => __( 'Alignment', 'buddyboss-theme' ),
				'type' => Controls_Manager::CHOOSE,
				'label_block' => false,
				'options' => [
					'left' => [
						'title' => __( 'Left', 'buddyboss-theme' ),
						'icon' => 'eicon-h-align-left',
					],
					'center' => [
						'title' => __( 'Center', 'buddyboss-theme' ),
						'icon' => 'eicon-h-align-center',
					],
					'right' => [
						'title' => __( 'Right', 'buddyboss-theme' ),
						'icon' => 'eicon-h-align-right',
					],
				],
				'default' => 'center',
				'prefix_class' => 'elementor-cta-%s-talign-',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_tabs1',
				'label'    => __( 'Typography', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .tabs-wrapper--style1 .bb-tabs__nav .bb-tabs__nav-title',
			)
		);

		$this->add_control(
			'tabs1_color',
			array(
				'label'     => __( 'Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .tabs-wrapper--style1 .bb-tabs__nav .bb-tabs__nav-title' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'        => 'tabs1_border',
				'label'       => __( 'Tabs Border', 'buddyboss-theme' ),
				'placeholder' => '1px',
				'default'     => '1px',
				'selector'    => '{{WRAPPER}} .tabs-wrapper--style1 .bb-tabs__nav .slick-current .bb-tabs__nav-title',
			]
		);

		$this->add_control(
			'tabs1_border_radius',
			[
				'label'      => __( 'Border Radius', 'buddyboss-theme' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default' => [
					'top' => '30',
					'right' => '30',
					'bottom' => '30',
					'left' => '30',
				],
				'selectors'  => [
					'{{WRAPPER}} .tabs-wrapper--style1 .bb-tabs__nav .slick-current .bb-tabs__nav-title' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition' => [
					'tabs1_style' => 'outline',
				],
			]
		);

		$this->add_control(
			'tabs1_padding',
			[
				'label'      => __( 'Tabs Padding', 'buddyboss-theme' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default' => [
					'top' => '5',
					'right' => '20',
					'bottom' => '5',
					'left' => '20',
				],
				'selectors'  => [
					'{{WRAPPER}} .tabs-wrapper--style1 .bb-tabs__nav .bb-tabs__nav-title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'tabs1_spacing',
			array(
				'label'      => __( 'Tabs Spacing', 'buddyboss-theme' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 50,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 10,
				),
				'selectors'  => array(
					'{{WRAPPER}} .tabs-wrapper--style1 .bb-tabs__nav-item' => 'margin: 0 {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'separator_title1',
			array(
				'label'     => __( 'Title', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_title1',
				'label'    => __( 'Typography', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .tabs-wrapper--style1 .bb-tabs__title h3',
			)
		);

		$this->add_control(
			'color_title1',
			array(
				'label'     => __( 'Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .tabs-wrapper--style1 .bb-tabs__title h3' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'separator_excerpt1',
			array(
				'label'     => __( 'Excerpt', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_excerpt1',
				'label'    => __( 'Typography', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .tabs-wrapper--style1 .bb-tabs__excerpt',
			)
		);

		$this->add_control(
			'color_excerpt1',
			array(
				'label'     => __( 'Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .tabs-wrapper--style1 .bb-tabs__excerpt' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'separator_button1',
			array(
				'label'     => __( 'Button', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_button1',
				'label'    => __( 'Typography', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .tabs-wrapper--style1 a.bb-tabs__link',
			)
		);

		$this->start_controls_tabs(
			'button1_tabs'
		);

		$this->start_controls_tab(
			'button1_normal_tab',
			array(
				'label' => __( 'Normal', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'button1_color',
			array(
				'label'     => __( 'Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .tabs-wrapper--style1 a.bb-tabs__link' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'button1_bgr_color',
			array(
				'label'     => __( 'Background Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .tabs-wrapper--style1 a.bb-tabs__link' => 'background-color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'button1_hover_tab',
			array(
				'label' => __( 'Hover', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'button1_color_hover',
			array(
				'label'     => __( 'Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .tabs-wrapper--style1 a.bb-tabs__link:hover' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'button1_bgr_color_hover',
			array(
				'label'     => __( 'Background Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .tabs-wrapper--style1 a.bb-tabs__link:hover' => 'background-color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'button1_border_radius',
			[
				'label'      => __( 'Border Radius', 'buddyboss-theme' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default' => [
					'top' => '0',
					'right' => '0',
					'bottom' => '0',
					'left' => '0',
				],
				'selectors'  => [
					'{{WRAPPER}} .tabs-wrapper--style1 a.bb-tabs__link' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'button1_padding',
			[
				'label'      => __( 'Padding', 'buddyboss-theme' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default' => [
					'top' => '8',
					'right' => '15',
					'bottom' => '8',
					'left' => '15',
				],
				'selectors'  => [
					'{{WRAPPER}} .tabs-wrapper--style1 a.bb-tabs__link' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'separator_navigation1',
			array(
				'label'     => __( 'Navigation', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'navigation1_normal_color',
			array(
				'label'     => __( 'Navigation Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bb-tabs__run .slick-arrow' => 'background-color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'navigation1_color',
			array(
				'label'     => __( 'Navigation Accent Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bb-tabs__run ul.slick-dots .slick-active button' => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .bb-tabs__run .slick-arrow:hover' => 'background-color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'arrows_position1',
			array(
				'label'      => __( 'Horizontal Position', 'buddyboss-theme' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => -100,
						'max'  => 100,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => -15,
				),
				'selectors'  => array(
					'{{WRAPPER}} .slick-arrow.bb-slide-prev' => 'left: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .slick-arrow.bb-slide-next' => 'right: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'arrows_position2',
			array(
				'label'      => __( 'Vertical Position', 'buddyboss-theme' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => '%',
					'size' => 50,
				),
				'selectors'  => array(
					'{{WRAPPER}} .slick-arrow' => 'top: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_style2',
			[
				'label'     => esc_html__( 'Style', 'buddyboss-theme' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'tabs_style' => 'style2',
				],
			]
		);

		$this->add_control(
			'separator_content2',
			array(
				'label'     => __( 'Content', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'media2_position',
			[
				'label' => __( 'Media Position', 'buddyboss-theme' ),
				'type' => Controls_Manager::CHOOSE,
				'label_block' => false,
				'options' => [
					'left' => [
						'title' => __( 'Left', 'buddyboss-theme' ),
						'icon' => 'eicon-h-align-left',
					],
					'right' => [
						'title' => __( 'Right', 'buddyboss-theme' ),
						'icon' => 'eicon-h-align-right',
					],
				],
				'default' => 'left',
				'prefix_class' => 'elementor-cta-%s-meadia-align-',
			]
		);

		$this->add_control(
			'media2_ratio',
			array(
				'label'      => __( 'Media Ratio', 'buddyboss-theme' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array(
					'%' => array(
						'min'  => 10,
						'max'  => 150,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => '%',
					'size' => 75,
				),
				'selectors'  => array(
					'{{WRAPPER}} .bb-tabs__image .media-container' => 'padding-top: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'media2_width',
			array(
				'label'      => __( 'Media Width', 'buddyboss-theme' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array(
					'%' => array(
						'min'  => 25,
						'max'  => 75,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => '%',
					'size' => 50,
				),
				'selectors'  => array(
					'{{WRAPPER}} .tabs-wrapper--style2 .bb-tabs__ismedia .bb-tabs__image' => 'flex: 0 0 {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'content2_v_position',
			[
				'label' => __( 'Vertical Position', 'buddyboss-theme' ),
				'type' => Controls_Manager::CHOOSE,
				'label_block' => false,
				'options' => [
					'top' => [
						'title' => __( 'Top', 'buddyboss-theme' ),
						'icon' => 'eicon-v-align-top',
					],
					'center' => [
						'title' => __( 'Center', 'buddyboss-theme' ),
						'icon' => 'eicon-v-align-middle',
					],
					'bottom' => [
						'title' => __( 'Bottom', 'buddyboss-theme' ),
						'icon' => 'eicon-v-align-bottom',
					],
				],
				'default' => 'center',
				'prefix_class' => 'elementor-cta-%s-content-v-align-',
			]
		);

		$this->add_control(
			'content2_padding',
			[
				'label'      => __( 'Content Padding', 'buddyboss-theme' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default' => [
					'top' => '40',
					'right' => '40',
					'bottom' => '40',
					'left' => '40',
				],
				'selectors'  => [
					'{{WRAPPER}} .tabs-wrapper--style2 .bb-tabs__body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'content2_bgr_color',
				'label' => __( 'Background', 'buddyboss-theme' ),
				'types' => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .tabs-wrapper--style2',
			]
		);

		$this->add_control(
			'separator_tabs2',
			array(
				'label'     => __( 'Tabs', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'tabs2_h_position',
			[
				'label' => __( 'Horizontal Position', 'buddyboss-theme' ),
				'type' => Controls_Manager::CHOOSE,
				'label_block' => false,
				'options' => [
					'left' => [
						'title' => __( 'Left', 'buddyboss-theme' ),
						'icon' => 'eicon-h-align-left',
					],
					'right' => [
						'title' => __( 'Right', 'buddyboss-theme' ),
						'icon' => 'eicon-h-align-right',
					],
				],
				'default' => 'right',
				'prefix_class' => 'elementor-cta-%s-row-align-',
			]
		);

		$this->add_responsive_control(
			'tabs2_v_position',
			[
				'label' => __( 'Vertical Position', 'buddyboss-theme' ),
				'type' => Controls_Manager::CHOOSE,
				'label_block' => false,
				'options' => [
					'top' => [
						'title' => __( 'Top', 'buddyboss-theme' ),
						'icon' => 'eicon-v-align-top',
					],
					'center' => [
						'title' => __( 'Center', 'buddyboss-theme' ),
						'icon' => 'eicon-v-align-middle',
					],
					'bottom' => [
						'title' => __( 'Bottom', 'buddyboss-theme' ),
						'icon' => 'eicon-v-align-bottom',
					],
				],
				'default' => 'center',
				'prefix_class' => 'elementor-cta-%s-talign-',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_tabs2',
				'label'    => __( 'Typography', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .tabs-wrapper--style2 .bb-tabs__nav-index',
			)
		);

		$this->add_control(
			'tabs2_color',
			array(
				'label'     => __( 'Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .tabs-wrapper--style2 .bb-tabs__nav-index' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'tabs2_size',
			array(
				'label'      => __( 'Size', 'buddyboss-theme' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 10,
						'max'  => 50,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 28,
				),
				'selectors'  => array(
					'{{WRAPPER}} .tabs-wrapper--style2 .bb-tabs__nav-index' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; line-height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'        => 'tabs2_border',
				'label'       => __( 'Tabs Border', 'buddyboss-theme' ),
				'placeholder' => '1px',
				'default'     => '1px',
				'selector'    => '{{WRAPPER}} .tabs-wrapper--style2 .slick-current .bb-tabs__nav-index',
			]
		);

		$this->add_control(
			'tabs2_border_radius',
			[
				'label'      => __( 'Border Radius', 'buddyboss-theme' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default' => [
					'top' => '30',
					'right' => '30',
					'bottom' => '30',
					'left' => '30',
				],
				'selectors'  => [
					'{{WRAPPER}} .tabs-wrapper--style2 .slick-current .bb-tabs__nav-index' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'tabs2_spacing',
			array(
				'label'      => __( 'Tabs Spacing', 'buddyboss-theme' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 50,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 10,
				),
				'selectors'  => array(
					'{{WRAPPER}} .tabs-wrapper--style2 .bb-tabs__nav-item' => 'margin: {{SIZE}}{{UNIT}} 0;',
				),
			)
		);

		$this->add_control(
			'tabs2_bgr_color',
			array(
				'label'     => __( 'Background Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .tabs-wrapper--style2 .bb-tabs__nav' => 'background-color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'tabs2_bgr_border_radius',
			[
				'label'      => __( 'Container Border Radius', 'buddyboss-theme' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default' => [
					'top' => '0',
					'right' => '0',
					'bottom' => '0',
					'left' => '0',
				],
				'selectors'  => [
					'{{WRAPPER}} .tabs-wrapper--style2 .bb-tabs__nav' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'separator_subtitle2',
			array(
				'label'     => __( 'Subtitle', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_subtitle2',
				'label'    => __( 'Typography', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .tabs-wrapper--style2 .bb-tabs__subtitle h6',
			)
		);

		$this->add_control(
			'color_subtitle2',
			array(
				'label'     => __( 'Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .tabs-wrapper--style2 .bb-tabs__subtitle h6' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'separator_title2',
			array(
				'label'     => __( 'Title', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_title2',
				'label'    => __( 'Typography', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .tabs-wrapper--style2 .bb-tabs__title h3',
			)
		);

		$this->add_control(
			'color_title2',
			array(
				'label'     => __( 'Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .tabs-wrapper--style2 .bb-tabs__title h3' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'separator_excerpt2',
			array(
				'label'     => __( 'Excerpt', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_excerpt2',
				'label'    => __( 'Typography', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .tabs-wrapper--style2 .bb-tabs__excerpt',
			)
		);

		$this->add_control(
			'color_excerpt2',
			array(
				'label'     => __( 'Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .tabs-wrapper--style2 .bb-tabs__excerpt' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'separator_button2',
			array(
				'label'     => __( 'Button', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_button2',
				'label'    => __( 'Typography', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .tabs-wrapper--style2 a.bb-tabs__link',
			)
		);

		$this->start_controls_tabs(
			'button2_tabs'
		);

		$this->start_controls_tab(
			'button2_normal_tab',
			array(
				'label' => __( 'Normal', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'button2_color',
			array(
				'label'     => __( 'Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .tabs-wrapper--style2 a.bb-tabs__link' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'button2_border_color',
			array(
				'label'     => __( 'Border Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .tabs-wrapper--style2 a.bb-tabs__link' => 'border-color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'button2_bgr_color',
			array(
				'label'     => __( 'Background Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .tabs-wrapper--style2 a.bb-tabs__link' => 'background-color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'button2_hover_tab',
			array(
				'label' => __( 'Hover', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'button2_color_hover',
			array(
				'label'     => __( 'Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .tabs-wrapper--style2 a.bb-tabs__link:hover' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'button2_border_color_hover',
			array(
				'label'     => __( 'Border Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .tabs-wrapper--style2 a.bb-tabs__link:hover' => 'border-color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'button2_bgr_hover',
			array(
				'label'     => __( 'Background Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .tabs-wrapper--style2 a.bb-tabs__link:hover' => 'background-color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'button2_btm_border',
			array(
				'label'      => __( 'Bottom Border Size', 'buddyboss-theme' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 10,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 2,
				),
				'selectors'  => array(
					'{{WRAPPER}} .tabs-wrapper--style2 a.bb-tabs__link' => 'border-bottom-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'button2_padding',
			[
				'label'      => __( 'Padding', 'buddyboss-theme' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default' => [
					'top' => '2',
					'right' => '0',
					'bottom' => '2',
					'left' => '0',
				],
				'selectors'  => [
					'{{WRAPPER}} .tabs-wrapper--style2 a.bb-tabs__link' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

	}

	private function render_image( $item, $instance ) {
		$image_id = $item['image']['id'];
		if( isset( $instance['image_size_size'] ) ){
			$image_size = $instance['image_size_size'];
		}
		if ( isset( $image_size ) && 'custom' === $image_size ) {
			$image_src = Group_Control_Image_Size::get_attachment_image_src( $image_id, 'image_size', $instance );
		} else {
			$image_src = wp_get_attachment_image_src( $image_id, 'large' );
			$image_src = $image_src[0];
		}

		return sprintf( '<img src="%s" alt="%s" />', $image_src, $item['title'] );
	}

	/**
	 * Render the widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.1.0
	 *
	 * @access protected
	 */
	protected function render() {

		$settings = $this->get_settings_for_display();

		$settings_tabs_style = $settings['tabs_style'];
		$settings_tabs_active_style = $settings['tabs1_style'];

		$this->add_render_attribute( 'tabs-block', 'class', 'bb-tabs__block flex' );
		?>

		<div class="bb-tabs">
			
			<div dir="ltr" class="tabs-wrapper flex tabs-wrapper--<?php echo esc_attr( $settings_tabs_style ); ?> <?php echo ( $settings_tabs_style == 'style1' && $settings['switch_dots'] ) ? 'bb-is-dotted' : 'bb-not-dotted'; ?>">

				<div class="bb-tabs__nav flex bb-tabs__active-<?php echo esc_attr( $settings_tabs_active_style ); ?>" data-num="<?php echo count($settings['tab_list']); ?>">
					<?php foreach ( $settings['tab_list'] as $index=>$item ) : ?>
						<div class="bb-tabs__nav-item">
							<span class="bb-tabs__nav-index"><?php echo $index + 1; ?></span>
							<span class="bb-tabs__nav-title"><?php echo $item['tab_title']; ?></span>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="bb-tabs__run" data-nav="<?php echo ( $settings_tabs_style == 'style1' && $settings['switch_arrows'] ) ? 'true' : 'false'; ?>" data-dots="<?php echo ( $settings_tabs_style == 'style1' && $settings['switch_dots'] ) ? 'true' : 'false'; ?>">
					<?php 
					$tab_count = 0;
					foreach ( $settings['tab_list'] as $item ) : ?>
						<?php if ( ! empty( $item['title'] ) ) : ?>

							<?php if ( ! empty( $item['link']['url'] ) ) {
								$this->add_link_attributes( 'link-wrapper' . $tab_count, $item['link'] );

								$item_tag = 'a';
							} ?>

							<div class="bb-tabs__slide <?php echo ( ! empty( $item['image']['url'] ) ) ? 'bb-tabs__ismedia' : 'bb-tabs__nomedia'; ?>">
								<div <?php echo $this->get_render_attribute_string('tabs-block'); ?>>
									
									<div class="bb-tabs__body">
										<div class="bb-tabs__subtitle"><h6><?php echo $item['tab_title']; ?></h6></div>
										<div class="bb-tabs__title"><h3><?php echo $item['title']; ?></h3></div>
										<div class="bb-tabs__excerpt"><?php echo $item['item_excerpt']; ?></div>
										<?php if ( ! empty( $item['link']['url'] ) && ! empty( $item['link_text'] ) ) : ?>
											<<?php echo $item_tag . ' class="bb-tabs__link" ' . $this->get_render_attribute_string( 'link-wrapper' . $tab_count ); ?>><?php echo $item['link_text']; ?></<?php echo $item_tag; ?>>
										<?php endif; ?>
									</div>

									<?php if ( ! empty( $item['image']['url'] ) ) : ?>
										<div class="bb-tabs__image">
											<div class="media-container"><?php echo $this->render_image( $item, $settings ); ?></div>
										</div>
									<?php endif; ?>

								</div>
							</div>

						<?php endif; ?>
					<?php 
					$tab_count++;
					endforeach; ?>
				</div>

			</div>

		</div>

		<?php

	}

	/**
	 * Render the widget output in the editor.
	 *
	 * Written as a Backbone JavaScript template and used to generate the live preview.
	 *
	 * @since 1.1.0
	 *
	 * @access protected
	 */
	/*protected function _content_template() {
		
	}*/
}
