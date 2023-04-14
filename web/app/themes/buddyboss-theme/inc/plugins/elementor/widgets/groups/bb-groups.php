<?php

namespace BBElementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Core\Schemes;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! defined( 'BB_GROUPS_WIDGET' ) ) {
	define( 'BB_GROUPS_WIDGET', true );
} // Prevent loading templates outside of this widget

/**
 * @since 1.1.0
 */
class BB_Groups extends Widget_Base {

	/**
	 * Retrieve the widget name.
	 *
	 * @since  1.1.0
	 *
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'bb-groups';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @since  1.1.0
	 *
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Groups', 'buddyboss-theme' );
	}

	/**
	 * Retrieve the widget icon.
	 *
	 * @since  1.1.0
	 *
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-toggle';
	}

	/**
	 * Retrieve the list of categories the widget belongs to.
	 *
	 * Used to determine where to display the widget in the editor.
	 *
	 * Note that currently Elementor supports only one category.
	 * When multiple categories passed, Elementor uses the first one.
	 *
	 * @since  1.1.0
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
	 * @since  1.0.0
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
	 * @since  1.1.0
	 *
	 * @access protected
	 */
	protected function register_controls() {

		$this->start_controls_section(
			'section_content_layout',
			[
				'label' => esc_html__( 'Layout', 'buddyboss-theme' ),
			]
		);

		$this->add_control(
			'groups_order',
			[
				'label'   => esc_html__( 'Default Groups Order', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'active',
				'options' => [
					'newest'  => esc_html__( 'Newest', 'buddyboss-theme' ),
					'popular' => esc_html__( 'Popular', 'buddyboss-theme' ),
					'active'  => esc_html__( 'Active', 'buddyboss-theme' ),
				],
			]
		);

		if ( true === bp_disable_group_type_creation() ) {
			$this->add_control(
				'group_types', array(
				'label'    => esc_html__( 'Group Types', 'buddyboss-theme' ),
				'type'     => \Elementor\Controls_Manager::SELECT2,
				'multiple' => true,
				'options'  => $this->bb_theme_elementor_group_types(),
			) );
		}

		$this->add_control(
			'groups_count',
			[
				'label'   => esc_html__( 'Groups Count', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SLIDER,
				'default' => [
					'size' => 5,
				],
				'range'   => [
					'px' => [
						'min'  => 1,
						'max'  => 20,
						'step' => 1,
					],
				],
			]
		);

		$this->add_control(
			'switch_more',
			[
				'label'   => esc_html__( 'Show All Groups Link', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->add_control(
			'switch_filter',
			[
				'label'   => esc_html__( 'Show Filter Types', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->add_control(
			'switch_avatar',
			[
				'label'   => esc_html__( 'Show Avatar', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->add_control(
			'switch_meta',
			[
				'label'   => esc_html__( 'Show Meta Data', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'Content', 'buddyboss-theme' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'heading_text',
			[
				'label'       => esc_html__( 'Heading Text', 'buddyboss-theme' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => [
					'active' => true,
				],
				'default'     => esc_html__( 'Groups', 'buddyboss-theme' ),
				'placeholder' => esc_html__( 'Enter heading text', 'buddyboss-theme' ),
				'label_block' => true
			]
		);

		$this->add_control(
			'groups_link_text',
			[
				'label'       => esc_html__( 'Groups Link Text', 'buddyboss-theme' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => [
					'active' => true,
				],
				'default'     => esc_html__( 'All Groups', 'buddyboss-theme' ),
				'placeholder' => esc_html__( 'Enter groups link text', 'buddyboss-theme' ),
				'label_block' => true,
				'condition'   => [
					'switch_more' => 'yes',
				]
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_box',
			[
				'label' => esc_html__( 'Box', 'buddyboss-theme' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'        => 'box_border',
				'label'       => esc_html__( 'Border', 'buddyboss-theme' ),
				'placeholder' => '1px',
				'default'     => '1px',
				'selector'    => '{{WRAPPER}} .bb-groups',
				'separator'   => 'before',
			]
		);

		$this->add_control(
			'box_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'buddyboss-theme' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default'    => [
					'top'    => '4',
					'right'  => '4',
					'bottom' => '4',
					'left'   => '4',
				],
				'selectors'  => [
					'{{WRAPPER}} .bb-groups' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name'     => 'background_color',
				'label'    => esc_html__( 'Background', 'buddyboss-theme' ),
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .bb-groups',
			]
		);

		$this->add_control(
			'separator_all',
			[
				'label'     => esc_html__( 'All Groups Link', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'extra_color',
			[
				'label'     => esc_html__( 'All Groupss Link Color', 'buddyboss-theme' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bb-block-header__extra a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_avatar',
			[
				'label'     => esc_html__( 'Avatar', 'buddyboss-theme' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'switch_avatar' => 'yes',
				],
			]
		);

		$this->add_control(
			'avatar_width',
			[
				'label'     => esc_html__( 'Size', 'buddyboss-theme' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => [
					'size' => 40,
				],
				'range'     => [
					'px' => [
						'min'  => 20,
						'max'  => 200,
						'step' => 1,
					],
				],
				'selectors' => [
					'{{WRAPPER}} #groups-list .item-avatar' => 'flex: 0 0 {{SIZE}}px;',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'avatar_shadow',
				'label'    => esc_html__( 'Shadow', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} #groups-list .item-avatar a',
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'        => 'avatar_border',
				'label'       => esc_html__( 'Border', 'buddyboss-theme' ),
				'placeholder' => '1px',
				'default'     => '1px',
				'selector'    => '{{WRAPPER}} #groups-list .item-avatar img',
				'separator'   => 'before',
			]
		);

		$this->add_control(
			'avatar_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'buddyboss-theme' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} #groups-list .item-avatar img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'avatar_opacity',
			[
				'label'     => esc_html__( 'Opacity (%)', 'buddyboss-theme' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => [
					'size' => 1,
				],
				'range'     => [
					'px' => [
						'max'  => 1,
						'min'  => 0.10,
						'step' => 0.01,
					],
				],
				'selectors' => [
					'{{WRAPPER}} #groups-list .item-avatar img' => 'opacity: {{SIZE}};',
				],
			]
		);

		$this->add_control(
			'avatar_spacing',
			[
				'label'     => esc_html__( 'Spacing', 'buddyboss-theme' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => [
					'size' => 15,
				],
				'range'     => [
					'px' => [
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} #groups-list .item-avatar' => 'margin-right: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_content',
			[
				'label' => esc_html__( 'Content', 'buddyboss-theme' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_title',
				'label'    => esc_html__( 'Typography Title', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} #groups-list .item-title a',
			)
		);

		$this->add_control(
			'title_item_color',
			array(
				'label'     => esc_html__( 'Title Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} #groups-list .item-title a' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_meta',
				'label'    => esc_html__( 'Typography Meta Data', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} #groups-list span.activity',
			)
		);

		$this->add_control(
			'meta_item_color',
			array(
				'label'     => esc_html__( 'Meta Data Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} #groups-list span.activity' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'separator_filter_types',
			[
				'label'     => esc_html__( 'Filter Types', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'        => 'filter_border',
				'label'       => esc_html__( 'Border', 'buddyboss-theme' ),
				'placeholder' => '1px',
				'default'     => '1px',
				'selector'    => '{{WRAPPER}} .bb-groups div.item-options',
				'separator'   => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_filters',
				'label'    => esc_html__( 'Typography', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} div.item-options a',
			)
		);

		$this->start_controls_tabs(
			'filter_tabs'
		);

		$this->start_controls_tab(
			'filter_normal_tab',
			array(
				'label' => esc_html__( 'Normal', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'filter_normal_color',
			array(
				'label'     => esc_html__( 'Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} div.item-options a:not(.selected)' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'filter_active_tab',
			array(
				'label' => esc_html__( 'Active', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'filter_active_color',
			array(
				'label'     => esc_html__( 'Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} div.item-options .selected' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'filter_active_border',
			array(
				'label'     => esc_html__( 'Border Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} div.item-options .selected' => 'border-bottom-color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'filter_hover_tab',
			array(
				'label' => esc_html__( 'Hover', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'filter_hover_color',
			array(
				'label'     => esc_html__( 'Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} div.item-options a:hover' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

	}

	public function bb_theme_elementor_group_types() {

		$group_types      = bp_groups_get_group_types( array(), 'objects' );
		$group_types_data = array();
		foreach ( $group_types as $group_type ) :
			if ( ! empty( $group_type->name ) ) {
				$group_types_data[ $group_type->name ] = $group_type->labels['singular_name'];
			}
		endforeach;

		return $group_types_data;

	}

	/**
	 * Render the widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since  1.1.0
	 *
	 * @access protected
	 */
	protected function render() {

		$settings = $this->get_settings_for_display();
		$type     = $settings['groups_order'];
		$user_id  = apply_filters( 'bp_group_widget_user_id', '0' );

		$templatePath = ELEMENTOR_BB__DIR__ . '/widgets/groups/templates/bb-groups-template.php';

		if ( file_exists( $templatePath ) ) {
			require $templatePath;
		}

	}

	/**
	 * Render the widget output in the editor.
	 *
	 * Written as a Backbone JavaScript template and used to generate the live preview.
	 *
	 * @since  1.1.0
	 *
	 * @access protected
	 */
	/*protected function _content_template() {

	}*/
}
