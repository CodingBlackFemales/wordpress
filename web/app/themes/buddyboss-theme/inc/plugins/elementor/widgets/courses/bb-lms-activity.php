<?php

namespace BBElementor\Widgets;

use BuddyBossTheme\LearndashHelper;
use BuddyBossTheme\LifterLMSHelper;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Scheme_Typography;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Background;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! defined( 'BB_LMS_WIDGET' ) ) {
	define( 'BB_LMS_WIDGET', true );
} // Prevent loading templates outside of this widget

/**
 * Class BB_Lms_Activity
 * @package BBElementor\Widgets
 */
class BB_Lms_Activity extends Widget_Base {

	/**
	 * Lifter LMS slug
	 */
	static public $LMS_LIFTER_SHORT_SLUG = 'llms';

	/**
	 * LearnDash LMS slug
	 */
	static public $LMS_LEARNDASH_SLUG = 'ld';

	/**
	 * @var string Selected plugin for widget
	 */
	public $selected_plugin;

	/**
	 * Retrieve the widget name.
	 * @since  1.1.0
	 * @access public
	 * @return string Widget name.
	 */
	public function get_name() {
		/**
		 * Below slug use for All LMS plugins. Currently we are not going to update to make it as generic as existing client already using this widget.
		 * This slug will not display or use on Frontend. This is only use by elementor to identify widget.
		 * In future if we want to update it then we need to write migration script to update following `_elementor_data` and `_elementor_controls_usage` meta using migration script.
		 */
		return 'ld-activity';
	}

	/**
	 * Retrieve the widget title.
	 * @since  1.1.0
	 * @access public
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Course Activity', 'buddyboss-theme' );
	}

	/**
	 * Retrieve the widget icon.
	 * @since  1.1.0
	 * @access public
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-checkbox';
	}

	/**
	 * Retrieve the list of categories the widget belongs to.
	 * Used to determine where to display the widget in the editor.
	 * Note that currently Elementor supports only one category.
	 * When multiple categories passed, Elementor uses the first one.
	 * @since  1.1.0
	 * @access public
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'buddyboss-elements' ];
	}

	/**
	 * Retrieve the list of scripts the widget depended on.
	 * Used to set scripts dependencies required to run the widget.
	 * @since  1.0.0
	 * @access public
	 * @return array Widget scripts dependencies.
	 */
	public function get_script_depends() {
		return array( 'elementor-bb-frontend' );
	}

	/**
	 * Register the widget controls.
	 * Adds different input fields to allow the user to change and customize
	 * the widget settings.
	 * @since  1.1.0
	 * @access protected
	 */
	protected function register_controls() {
		$shortName = $this->get_active_plugin_short_name();

		if ( empty( $shortName ) ) {
			return;
		}

		$lms_plugins = $this->get_active_plugin_list();

		// CONTROLS GENERAL
		$this->start_controls_section(
			'section_content',
			[
				'label' => __( 'Content', 'buddyboss-theme' ),
			]
		);

		// Don't display any Setting if multiple LMS plugin activated
		if ( count( $lms_plugins ) > 1 ) {
			$this->add_control(
				'lms_plugin_notice',
				array(
					'label'           => __( '', 'buddyboss-theme' ),
					'type'            => \Elementor\Controls_Manager::RAW_HTML,
					'raw'             => sprintf( __( 'Warning: Detected multiple LMS plugins active ( %s ) <br/>Please do not UPDATE the template otherwise LMS widgets settings will be deleted. <br/>Please deactivate either of the LMS plugin and try again.', 'buddyboss-theme' ), implode( ', ', $lms_plugins ) ),
					'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
				)
			);
		} else {

			$this->add_control(
				'lms_selector',
				array(
					'label'           => __( 'LMS', 'buddyboss-theme' ),
					'type'            => \Elementor\Controls_Manager::TEXT,
					'default'         => $shortName,
					'content_classes' => 'bb-hide',
				)
			);

			$this->add_control(
				'no_of_course',
				[
					'label'       => __( 'Number of courses', 'buddyboss-theme' ),
					'type'        => \Elementor\Controls_Manager::NUMBER,
					'min'         => 1,
					'max'         => 10,
					'default'     => '2'
				]
			);

			$this->add_control(
				'switch_media',
				[
					'label'   => esc_html__( 'Show Media', 'buddyboss-theme' ),
					'type'    => Controls_Manager::SWITCHER,
					'default' => 'yes',
				]
			);

			$this->add_control(
				'switch_progress',
				[
					'label'     => esc_html__( 'Show Progress Bar', 'buddyboss-theme' ),
					'type'      => Controls_Manager::SWITCHER,
					'default'   => 'yes',
					'condition' => [
						'switch_media' => 'yes',
					],
				]
			);

			$this->add_control(
				'switch_course',
				[
					'label'   => esc_html__( 'Show Course Title', 'buddyboss-theme' ),
					'type'    => Controls_Manager::SWITCHER,
					'default' => 'yes',
				]
			);

			$this->add_control(
				'switch_excerpt',
				[
					'label'   => esc_html__( 'Show Excerpt', 'buddyboss-theme' ),
					'type'    => Controls_Manager::SWITCHER,
					'default' => 'yes',
				]
			);

			$this->add_control(
				'switch_dots',
				[
					'label'     => esc_html__( 'Show Dots', 'buddyboss-theme' ),
					'type'      => Controls_Manager::SWITCHER,
					'default'   => 'yes',
					'condition' => [
						'no_of_course' => [ 2, 3, 4, 5, 6, 7, 8, 9, 10 ],
					],
				]
			);

			$this->add_control(
				'switch_link',
				[
					'label'     => esc_html__( 'Show Link', 'buddyboss-theme' ),
					'type'      => Controls_Manager::SWITCHER,
					'default'   => 'yes',
					'separator' => 'before',
				]
			);

			$this->add_control(
				'button_text',
				[
					'label'       => __( 'Button Text', 'buddyboss-theme' ),
					'type'        => Controls_Manager::TEXT,
					'dynamic'     => [
						'active' => false,
					],
					'default'     => __( 'Continue Course', 'buddyboss-theme' ),
					'placeholder' => __( 'Enter button text', 'buddyboss-theme' ),
					'label_block' => true,
					'condition'   => [
						'switch_link' => 'yes',
					],
				]
			);

			$this->add_control(
				'switch_my_courses',
				[
					'label'     => esc_html__( 'My Courses Button', 'buddyboss-theme' ),
					'type'      => Controls_Manager::SWITCHER,
					'default'   => 'yes',
					'separator' => 'before',
				]
			);

			$this->add_control(
				'my_courses_button_text',
				[
					'label'       => __( 'Button Text', 'buddyboss-theme' ),
					'type'        => Controls_Manager::TEXT,
					'dynamic'     => [
						'active' => false,
					],
					'default'     => __( 'View My Courses', 'buddyboss-theme' ),
					'placeholder' => __( 'Enter button text', 'buddyboss-theme' ),
					'label_block' => true,
					'condition'   => [
						'switch_my_courses' => 'yes',
					],
				]
			);

			$this->add_control(
				'switch_my_courses_link',
				[
					'label'       => esc_html__( 'My Courses Custom Link', 'buddyboss-theme' ),
					'type'        => Controls_Manager::SWITCHER,
					'default'     => 'no',
				]
			);

			$this->add_control(
				'my_courses_link',
				[
					'label'       => __( 'Custom Link', 'buddyboss-theme' ),
					'type'        => Controls_Manager::URL,
					'default'     => [ 'url' => '' ],
					'dynamic'     => [
						'active' => false,
					],
					'placeholder' => __( 'https://your-link.com', 'buddyboss-theme' ),
					'condition'   => [
						'switch_my_courses_link' => 'yes',
					],
				]
			);

			$this->add_control(
				'no_courses_paragraph_text',
				[
					'label'       => __( 'No Courses Paragraph Text', 'buddyboss-theme' ),
					'type'        => Controls_Manager::TEXT,
					'dynamic'     => [
						'active' => false,
					],
					'default'     => __( 'You don\'t have any ongoing courses.', 'buddyboss-theme' ),
					'placeholder' => __( 'Enter no courses paragraph text', 'buddyboss-theme' ),
					'label_block' => true,
					'separator'   => 'before',
				]
			);

			$this->add_control(
				'no_courses_button_text',
				[
					'label'       => __( 'No Courses Button Text', 'buddyboss-theme' ),
					'type'        => Controls_Manager::TEXT,
					'dynamic'     => [
						'active' => false,
					],
					'default'     => __( 'Explore Courses', 'buddyboss-theme' ),
					'placeholder' => __( 'Enter no courses button text', 'buddyboss-theme' ),
					'label_block' => true,
				]
			);

			$this->add_control(
				'switch_explore_link',
				[
					'label'       => esc_html__( 'Explore Courses Custom Link', 'buddyboss-theme' ),
					'type'        => Controls_Manager::SWITCHER,
					'default'     => 'no',
				]
			);

			$this->add_control(
				'explore_courses_link',
				[
					'label'       => __( 'Custom Link', 'buddyboss-theme' ),
					'type'        => Controls_Manager::URL,
					'default'     => [ 'url' => '' ],
					'dynamic'     => [
						'active' => false,
					],
					'placeholder' => __( 'https://your-link.com', 'buddyboss-theme' ),
					'condition'   => [
						'switch_explore_link' => 'yes',
					],
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
					'label'       => __( 'Border', 'buddyboss-theme' ),
					'placeholder' => '1px',
					'default'     => '1px',
					'selector'    => '{{WRAPPER}} .bb-la-block',
					'separator'   => 'before',
				]
			);

			$this->add_control(
				'box_border_radius',
				[
					'label'      => __( 'Border Radius', 'buddyboss-theme' ),
					'type'       => Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', '%' ],
					'default'    => [
						'top'    => '4',
						'right'  => '4',
						'bottom' => '4',
						'left'   => '4',
					],
					'selectors'  => [
						'{{WRAPPER}} .bb-la-block'                                                         => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
						'{{WRAPPER}} .bb-ldactivity .thumbnail-container img'                              => 'border-radius: {{TOP}}{{UNIT}} 0 0 {{LEFT}}{{UNIT}};',
						'{{WRAPPER}} .bb-ldactivity .thumbnail-container'                                  => 'border-radius: {{TOP}}{{UNIT}} 0 0 {{LEFT}}{{UNIT}};',
						'{{WRAPPER}} .bb-la-composer.bb-la--isslick:after'                                 => 'border-radius: 0 {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} 0;',
						'{{WRAPPER}} .bb-ldactivity .bb-la__media:after'                                   => 'border-radius: {{TOP}}{{UNIT}} 0 0 {{LEFT}}{{UNIT}};',
						'@media( max-width: 544px ) { {{WRAPPER}} .bb-ldactivity .thumbnail-container img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} 0 0 };',
						'@media( max-width: 544px ) { {{WRAPPER}} .bb-ldactivity .thumbnail-container'     => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} 0 0 };',
						'@media( max-width: 544px ) { {{WRAPPER}} .bb-ldactivity .bb-la__media:after'      => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} 0 0 };',
					],
				]
			);

			$this->add_group_control(
				\Elementor\Group_Control_Background::get_type(),
				[
					'name'     => 'background_color',
					'label'    => __( 'Background', 'buddyboss-theme' ),
					'types'    => [ 'classic', 'gradient' ],
					'selector' => '{{WRAPPER}} .bb-la-block',
				]
			);

			$this->end_controls_section();

			$this->start_controls_section(
				'section_style_content',
				[
					'label' => __( 'Content', 'buddyboss-theme' ),
					'tab'   => Controls_Manager::TAB_STYLE,
				]
			);

			$this->add_control(
				'content_padding',
				[
					'label'      => __( 'Padding', 'buddyboss-theme' ),
					'type'       => Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', '%' ],
					'default'    => [
						'top'    => '20',
						'right'  => '20',
						'bottom' => '20',
						'left'   => '20',
					],
					'selectors'  => [
						'{{WRAPPER}} .bb-ldactivity .bb-la__body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
				]
			);

			$this->add_control(
				'separator_course_media',
				[
					'label'     => __( 'Media Overlay', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
				]
			);

			$this->add_group_control(
				Group_Control_Background::get_type(),
				[
					'name'     => 'media_overlay',
					'label'    => __( 'Overlay', 'buddyboss-theme' ),
					'types'    => [ 'classic', 'gradient' ],
					'selector' => '{{WRAPPER}} .bb-ldactivity .bb-la__media:after',
				]
			);

			$this->add_control(
				'separator_course_title',
				[
					'label'     => __( 'Course Title', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
				]
			);

			$this->add_control(
				'course_title_color',
				[
					'label'     => __( 'Color', 'buddyboss-theme' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .bb-la__parent' => 'color: {{VALUE}};',
					],
				]
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => 'course_title_typography',
					'label'    => __( 'Typography', 'buddyboss-theme' ),
					'selector' => '{{WRAPPER}} .bb-la__parent',
				)
			);

			$this->add_control(
				'course_title_spacing',
				[
					'label'     => __( 'Spacing', 'buddyboss-theme' ),
					'type'      => Controls_Manager::SLIDER,
					'default'   => [
						'size' => 0,
					],
					'range'     => [
						'px' => [
							'min'  => 0,
							'max'  => 50,
							'step' => 1,
						],
					],
					'selectors' => [
						'{{WRAPPER}} .bb-la__parent' => 'margin-bottom: {{SIZE}}px;',
					],
				]
			);

			$this->add_control(
				'separator_title',
				[
					'label'     => __( 'Title', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
				]
			);

			$this->add_control(
				'title_color',
				[
					'label'     => __( 'Color', 'buddyboss-theme' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .bb-la__title h2' => 'color: {{VALUE}};',
					],
				]
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => 'title_typography',
					'label'    => __( 'Typography', 'buddyboss-theme' ),
					'selector' => '{{WRAPPER}} .bb-la__title h2',
				)
			);

			$this->add_control(
				'title_spacing',
				[
					'label'     => __( 'Spacing', 'buddyboss-theme' ),
					'type'      => Controls_Manager::SLIDER,
					'default'   => [
						'size' => 20,
					],
					'range'     => [
						'px' => [
							'min'  => 0,
							'max'  => 50,
							'step' => 1,
						],
					],
					'selectors' => [
						'{{WRAPPER}} .bb-la__title h2' => 'margin-bottom: {{SIZE}}px;',
					],
				]
			);

			$this->add_control(
				'separator_excerpt',
				[
					'label'     => __( 'Excerpt', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
				]
			);

			$this->add_control(
				'excerpt_color',
				[
					'label'     => __( 'Color', 'buddyboss-theme' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .bb-la__excerpt' => 'color: {{VALUE}};',
					],
				]
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => 'excerpt_typography',
					'label'    => __( 'Typography', 'buddyboss-theme' ),
					'selector' => '{{WRAPPER}} .bb-la__excerpt',
				)
			);

			$this->add_control(
				'excerpt_spacing',
				[
					'label'     => __( 'Spacing', 'buddyboss-theme' ),
					'type'      => Controls_Manager::SLIDER,
					'default'   => [
						'size' => 20,
					],
					'range'     => [
						'px' => [
							'min'  => 0,
							'max'  => 50,
							'step' => 1,
						],
					],
					'selectors' => [
						'{{WRAPPER}} .bb-la__excerpt' => 'margin-bottom: {{SIZE}}px;',
					],
				]
			);

			$this->end_controls_section();

			$this->start_controls_section(
				'section_style_button',
				[
					'label'     => __( 'Button', 'buddyboss-theme' ),
					'tab'       => Controls_Manager::TAB_STYLE,
					'condition' => [
						'switch_link' => 'yes',
					],
				]
			);

			$this->start_controls_tabs(
				'button_tabs'
			);

			$this->start_controls_tab(
				'button_normal_tab',
				array(
					'label' => __( 'Normal', 'buddyboss-theme' ),
				)
			);

			$this->add_control(
				'button_color',
				array(
					'label'     => __( 'Color', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bb-la__link a' => 'color: {{VALUE}}',
					),
				)
			);

			$this->add_control(
				'button_bgr_color',
				array(
					'label'     => __( 'Background Color', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bb-la__link a' => 'background-color: {{VALUE}}',
					),
				)
			);

			$this->add_control(
				'la_button_border_color',
				array(
					'label'     => __( 'Border Color', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bb-la__link a' => 'border-color: {{VALUE}}',
					),
				)
			);

			$this->end_controls_tab();

			$this->start_controls_tab(
				'button_hover_tab',
				array(
					'label' => __( 'Hover', 'buddyboss-theme' ),
				)
			);

			$this->add_control(
				'button_color_hover',
				array(
					'label'     => __( 'Color', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bb-la__link a:hover' => 'color: {{VALUE}}',
					),
				)
			);

			$this->add_control(
				'button_bgr_color_hover',
				array(
					'label'     => __( 'Background Color', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bb-la__link a:hover' => 'background-color: {{VALUE}}',
					),
				)
			);

			$this->add_control(
				'la_button_border_color_hover',
				array(
					'label'     => __( 'Border Color', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bb-la__link a:hover' => 'border-color: {{VALUE}}',
					),
				)
			);

			$this->end_controls_tab();

			$this->end_controls_tabs();

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => 'button_typography',
					'label'    => __( 'Typography', 'buddyboss-theme' ),
					'selector' => '{{WRAPPER}} .bb-la__link a',
				)
			);

			$this->add_responsive_control(
				'alignment',
				[
					'label'        => __( 'Button Alignment', 'buddyboss-theme' ),
					'type'         => Controls_Manager::CHOOSE,
					'label_block'  => false,
					'options'      => [
						'left'   => [
							'title' => __( 'Left', 'buddyboss-theme' ),
							'icon'  => 'eicon-h-align-left',
						],
						'center' => [
							'title' => __( 'Center', 'buddyboss-theme' ),
							'icon'  => 'eicon-h-align-center',
						],
						'right'  => [
							'title' => __( 'Right', 'buddyboss-theme' ),
							'icon'  => 'eicon-h-align-right',
						],
					],
					'default'      => 'left',
					'prefix_class' => 'elementor-cta-%s-falign-',
				]
			);

			$this->add_control(
				'button_padding',
				[
					'label'      => __( 'Button Padding', 'buddyboss-theme' ),
					'type'       => Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', '%' ],
					'default'    => [
						'top'    => '4',
						'right'  => '20',
						'bottom' => '4',
						'left'   => '20',
					],
					'selectors'  => [
						'{{WRAPPER}} .bb-la__link a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
				]
			);

			$this->add_group_control(
				Group_Control_Border::get_type(),
				[
					'name'        => 'button_border',
					'label'       => __( 'Button Border', 'buddyboss-theme' ),
					'placeholder' => '1px',
					'default'     => '1px',
					'selector'    => '{{WRAPPER}} .bb-la__link a',
					'separator'   => 'before',
				]
			);

			$this->end_controls_section();

			$this->start_controls_section(
				'section_style_nav',
				[
					'label' => __( 'Navigation', 'buddyboss-theme' ),
					'tab'   => Controls_Manager::TAB_STYLE,
				]
			);

			$this->add_control(
				'switch_overlap',
				[
					'label'       => esc_html__( 'Course Overlap', 'buddyboss-theme' ),
					'description' => esc_html__( 'Show courses/lessons overlapped.', 'buddyboss-theme' ),
					'type'        => Controls_Manager::SWITCHER,
					'default'     => 'yes',
				]
			);

			$this->add_control(
				'separator_nav_arrows',
				[
					'label'     => __( 'Arrows', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
				]
			);

			$this->add_control(
				'arrows_position',
				array(
					'label'      => __( 'Position', 'buddyboss-theme' ),
					'type'       => Controls_Manager::SLIDER,
					'size_units' => array( 'px' ),
					'range'      => array(
						'px' => array(
							'min'  => - 50,
							'max'  => 50,
							'step' => 1,
						),
					),
					'default'    => array(
						'unit' => 'px',
						'size' => - 21,
					),
					'selectors'  => array(
						'{{WRAPPER}} .bb-la .slick-arrow.bb-slide-prev' => 'left: {{SIZE}}{{UNIT}};',
						'{{WRAPPER}} .bb-la .slick-arrow.bb-slide-next' => 'right: {{SIZE}}{{UNIT}};',
					),
				)
			);

			$this->add_control(
				'arrows_size',
				array(
					'label'      => __( 'Size', 'buddyboss-theme' ),
					'type'       => Controls_Manager::SLIDER,
					'size_units' => array( 'px' ),
					'range'      => array(
						'px' => array(
							'min'  => 20,
							'max'  => 50,
							'step' => 1,
						),
					),
					'default'    => array(
						'unit' => 'px',
						'size' => 42,
					),
					'selectors'  => array(
						'{{WRAPPER}} .bb-la .slick-arrow' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
						'{{WRAPPER}} .slick-arrow i'      => 'line-height: {{SIZE}}{{UNIT}};',
					),
				)
			);

			$this->start_controls_tabs(
				'arrows_nav'
			);

			$this->start_controls_tab(
				'arrows_normal_nav',
				array(
					'label' => __( 'Normal', 'buddyboss-theme' ),
				)
			);

			$this->add_control(
				'arrow_color',
				array(
					'label'     => __( 'Color', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bb-la .slick-arrow i' => 'color: {{VALUE}}',
					),
				)
			);

			$this->add_control(
				'arrow_bgr_color',
				array(
					'label'     => __( 'Background Color', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bb-la .slick-arrow' => 'background-color: {{VALUE}}',
					),
				)
			);

			$this->end_controls_tab();

			$this->start_controls_tab(
				'arrow_hover_nav',
				array(
					'label' => __( 'Hover', 'buddyboss-theme' ),
				)
			);

			$this->add_control(
				'arrow_color_hover',
				array(
					'label'     => __( 'Color', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bb-la .slick-arrow:hover i' => 'color: {{VALUE}}',
					),
				)
			);

			$this->add_control(
				'arrow_bgr_color_hover',
				array(
					'label'     => __( 'Background Color', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bb-la .slick-arrow:hover' => 'background-color: {{VALUE}}',
					),
				)
			);

			$this->end_controls_tab();

			$this->end_controls_tabs();

			$this->add_group_control(
				Group_Control_Box_Shadow::get_type(),
				array(
					'name'     => 'arrows_shadow',
					'label'    => __( 'Shadow', 'buddyboss-theme' ),
					'selector' => '{{WRAPPER}} .bb-la .slick-arrow',
				)
			);

			$this->add_control(
				'separator_nav_dots',
				[
					'label'     => __( 'Dots', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
					'condition' => [
						'switch_dots'  => 'yes',
						'no_of_course' => [ 2, 3, 4, 5, 6, 7, 8, 9, 10 ],
					],
				]
			);

			$this->add_control(
				'dots_active_color',
				[
					'label'     => __( 'Active Color', 'buddyboss-theme' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .bb-ldactivity ul.slick-dots li.slick-active button' => 'background-color: {{VALUE}};',
					],
					'condition' => [
						'switch_dots'  => 'yes',
						'no_of_course' => [ 2, 3, 4, 5, 6, 7, 8, 9, 10 ],
					],
				]
			);

			$this->add_control(
				'dots_inactive_color',
				[
					'label'     => __( 'Inactive Color', 'buddyboss-theme' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .bb-ldactivity ul.slick-dots li:not(.slick-active) button' => 'background-color: {{VALUE}};',
					],
					'condition' => [
						'switch_dots'  => 'yes',
						'no_of_course' => [ 2, 3, 4, 5, 6, 7, 8, 9, 10 ],
					],
				]
			);

			$this->add_control(
				'dot_size',
				array(
					'label'      => __( 'Size', 'buddyboss-theme' ),
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
						'size' => 30,
					),
					'selectors'  => array(
						'{{WRAPPER}} .bb-ldactivity ul.slick-dots button' => 'width: {{SIZE}}{{UNIT}};',
					),
					'condition'  => [
						'switch_dots'  => 'yes',
						'no_of_course' => [ 2, 3, 4, 5, 6, 7, 8, 9, 10 ],
					],
				)
			);

			$this->add_responsive_control(
				'dots_alignment',
				[
					'label'        => __( 'Alignment', 'buddyboss-theme' ),
					'type'         => Controls_Manager::CHOOSE,
					'label_block'  => false,
					'options'      => [
						'left'   => [
							'title' => __( 'Left', 'buddyboss-theme' ),
							'icon'  => 'eicon-h-align-left',
						],
						'center' => [
							'title' => __( 'Center', 'buddyboss-theme' ),
							'icon'  => 'eicon-h-align-center',
						],
						'right'  => [
							'title' => __( 'Right', 'buddyboss-theme' ),
							'icon'  => 'eicon-h-align-right',
						],
					],
					'default'      => 'left',
					'prefix_class' => 'dots-%s-align-',
					'condition'    => [
						'switch_dots'  => 'yes',
						'no_of_course' => [ 2, 3, 4, 5, 6, 7, 8, 9, 10 ],
					],
				]
			);

			$this->end_controls_section();

			$this->start_controls_section(
				'section_style_progress',
				[
					'label'     => __( 'Progress Bar', 'buddyboss-theme' ),
					'tab'       => Controls_Manager::TAB_STYLE,
					'condition' => [
						'switch_media'    => 'yes',
						'switch_progress' => 'yes',
					],
				]
			);

			$this->add_responsive_control(
				'progress_alignment',
				[
					'label'        => __( 'Alignment', 'buddyboss-theme' ),
					'type'         => Controls_Manager::CHOOSE,
					'label_block'  => false,
					'options'      => [
						'left'  => [
							'title' => __( 'Left', 'buddyboss-theme' ),
							'icon'  => 'eicon-h-align-left',
						],
						'right' => [
							'title' => __( 'Right', 'buddyboss-theme' ),
							'icon'  => 'eicon-h-align-right',
						],
					],
					'default'      => 'left',
					'prefix_class' => 'elementor-cta-%s-ldprogress-',
				]
			);

			$this->add_control(
				'switch_value',
				[
					'label'   => esc_html__( 'Show Progress Value', 'buddyboss-theme' ),
					'type'    => Controls_Manager::SWITCHER,
					'default' => 'yes',
				]
			);

			$this->add_control(
				'switch_tooltip',
				[
					'label'   => esc_html__( 'Show Tooltip', 'buddyboss-theme' ),
					'type'    => Controls_Manager::SWITCHER,
					'default' => 'yes',
				]
			);

			$this->add_control(
				'progress_color',
				[
					'label'     => __( 'Active Color', 'buddyboss-theme' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .bb-progress .bb-progress-circle' => 'border-color: {{VALUE}};',
					],
				]
			);

			$this->add_control(
				'border_color',
				[
					'label'     => __( 'Border Color', 'buddyboss-theme' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .bb-lms-progress-wrap--ld-activity .bb-progress:after' => 'border-color: {{VALUE}};',
					],
				]
			);

			$this->add_control(
				'value_color',
				[
					'label'     => __( 'Progress Value Color', 'buddyboss-theme' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} .bb-lms-progress-wrap--ld-activity .bb-progress__value' => 'color: {{VALUE}};',
					],
				]
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => 'typography_progress_value',
					'label'    => __( 'Typography Progress Value', 'buddyboss-theme' ),
					'selector' => '{{WRAPPER}} .bb-lms-progress-wrap--ld-activity .bb-progress__value',
				)
			);

			$this->end_controls_section();

			$this->start_controls_section(
				'section_style_my_courses',
				[
					'label'     => esc_html__( 'My Courses Button', 'buddyboss-theme' ),
					'tab'       => Controls_Manager::TAB_STYLE,
					'condition' => [
						'switch_my_courses' => 'yes',
					],
				]
			);

			$this->add_responsive_control(
				'my_alignment',
				[
					'label'        => __( 'Button Alignment', 'buddyboss-theme' ),
					'type'         => Controls_Manager::CHOOSE,
					'label_block'  => false,
					'options'      => [
						'left'   => [
							'title' => __( 'Left', 'buddyboss-theme' ),
							'icon'  => 'eicon-h-align-left',
						],
						'center' => [
							'title' => __( 'Center', 'buddyboss-theme' ),
							'icon'  => 'eicon-h-align-center',
						],
						'right'  => [
							'title' => __( 'Right', 'buddyboss-theme' ),
							'icon'  => 'eicon-h-align-right',
						],
					],
					'default'      => 'right',
					'prefix_class' => 'elementor-cta-%s-la-my-align-',
				]
			);

			$this->start_controls_tabs(
				'button_my_tabs'
			);

			$this->start_controls_tab(
				'button_my_normal_tab',
				array(
					'label' => __( 'Normal', 'buddyboss-theme' ),
				)
			);

			$this->add_control(
				'button_my_color',
				array(
					'label'     => __( 'Color', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bb-la-activity-btn a.bb-la-activity-btn__link' => 'color: {{VALUE}}',
					),
				)
			);

			$this->add_control(
				'button_my_bgr_color',
				array(
					'label'     => __( 'Background Color', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bb-la-activity-btn a.bb-la-activity-btn__link' => 'background-color: {{VALUE}}',
					),
				)
			);

			$this->add_control(
				'button_ld_border_color',
				array(
					'label'     => __( 'Border Color', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bb-la-activity-btn a.bb-la-activity-btn__link' => 'border-color: {{VALUE}}',
					),
				)
			);

			$this->end_controls_tab();

			$this->start_controls_tab(
				'button_my_hover_tab',
				array(
					'label' => __( 'Hover', 'buddyboss-theme' ),
				)
			);

			$this->add_control(
				'button_my_color_hover',
				array(
					'label'     => __( 'Color', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bb-la-activity-btn a.bb-la-activity-btn__link:hover' => 'color: {{VALUE}}',
					),
				)
			);

			$this->add_control(
				'button_my_bgr_color_hover',
				array(
					'label'     => __( 'Background Color', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bb-la-activity-btn a.bb-la-activity-btn__link:hover' => 'background-color: {{VALUE}}',
					),
				)
			);

			$this->add_control(
				'button_ld_border_color_hover',
				array(
					'label'     => __( 'Border Color', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bb-la-activity-btn a.bb-la-activity-btn__link:hover' => 'border-color: {{VALUE}}',
					),
				)
			);

			$this->end_controls_tab();

			$this->end_controls_tabs();

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => 'button_my_typography',
					'label'    => __( 'Typography', 'buddyboss-theme' ),
					'selector' => '{{WRAPPER}} .bb-la-activity-btn a.bb-la-activity-btn__link',
				)
			);

			$this->add_control(
				'button_my_padding',
				[
					'label'      => __( 'Button Padding', 'buddyboss-theme' ),
					'type'       => Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', '%' ],
					'default'    => [
						'top'    => '2',
						'right'  => '15',
						'bottom' => '2',
						'left'   => '15',
					],
					'selectors'  => [
						'{{WRAPPER}} .bb-la-activity-btn a.bb-la-activity-btn__link' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
				]
			);

			$this->add_group_control(
				Group_Control_Border::get_type(),
				[
					'name'        => 'button_my_border',
					'label'       => __( 'Button Border', 'buddyboss-theme' ),
					'placeholder' => '1px',
					'default'     => '1px',
					'selector'    => '{{WRAPPER}} .bb-la-activity-btn a.bb-la-activity-btn__link',
					'separator'   => 'before',
				]
			);

			$this->add_control(
				'button_my_spacing',
				[
					'label'     => __( 'Spacing', 'buddyboss-theme' ),
					'type'      => Controls_Manager::SLIDER,
					'default'   => [
						'size' => 50,
					],
					'range'     => [
						'px' => [
							'min'  => 30,
							'max'  => 100,
							'step' => 1,
						],
					],
					'selectors' => [
						'{{WRAPPER}} .bb-la-activity-btn' => 'top: -{{SIZE}}px;',
					],
				]
			);
		}

		$this->end_controls_section();
		// END CONTROLS GENERAL
	}

	/**
	 * Render the widget output on the frontend.
	 * Written in PHP and used to generate the final HTML.
	 * @since  1.1.0
	 * @access protected
	 */
	protected function render() {
		global $wpdb;

		$lms_plugins = $this->get_active_plugin_list();
		if ( count( $lms_plugins ) > 1 ) {
			return false;
		}

		$settings  = $this->get_settings();
		$shortName = $this->get_active_plugin_short_name();

		if ( ! empty( $settings['lms_selector'] ) ) {
			$shortName = $settings['lms_selector'];
		}
		$this->selected_plugin = $shortName;

		if ( ! $this->check_active_plugin() ) {
			return false;
		}

		$helper    = $this->get_active_plugin_helper();
		$name      = $this->get_active_plugin_name();
		$nameLower = strtolower( $name );

		$templatePath = ELEMENTOR_BB__DIR__ . '/widgets/courses/templates/' . $nameLower . '-activity-template.php';

		if ( file_exists( $templatePath ) ) {
			require $templatePath;
		}
	}

	/**
	 * @return string
	 */
	private function get_active_plugin_short_name() {
		if ( class_exists( '\BuddyBossTheme\LifterLMSHelper' ) && class_exists( LifterLMSHelper::LMS_CLASS ) ) {
			return LifterLMSHelper::LMS_SHORT_NAME;
		}

		if ( class_exists( '\BuddyBossTheme\LearndashHelper' ) && class_exists( LearndashHelper::LMS_CLASS ) ) {
			return LearndashHelper::LMS_SHORT_NAME;
		}

		return '';
	}

	/**
	 * @return string
	 */
	private function get_active_plugin_name() {
		if ( self::$LMS_LIFTER_SHORT_SLUG === $this->selected_plugin ) {
			return LifterLMSHelper::LMS_NAME;
		}

		if ( self::$LMS_LEARNDASH_SLUG === $this->selected_plugin ) {
			return LearndashHelper::LMS_NAME;
		}

		return '';
	}

	/**
	 * @param string $shortName
	 *
	 * @return bool
	 */
	private function check_active_plugin() {
		if ( self::$LMS_LIFTER_SHORT_SLUG === $this->selected_plugin ) {
			return class_exists( '\BuddyBossTheme\LifterLMSHelper' ) && class_exists( LifterLMSHelper::LMS_CLASS );
		}

		if ( self::$LMS_LEARNDASH_SLUG === $this->selected_plugin ) {
			return class_exists( '\BuddyBossTheme\LearndashHelper' ) && class_exists( LearndashHelper::LMS_CLASS );
		}

		return false;
	}

	/**
	 * @return string
	 */
	private function get_active_plugin_list() {
		$plugins = array();
		if ( class_exists( '\BuddyBossTheme\LifterLMSHelper' ) && class_exists( LifterLMSHelper::LMS_CLASS ) ) {
			$plugins[] = LifterLMSHelper::LMS_NAME;
		}

		if ( class_exists( '\BuddyBossTheme\LearndashHelper' ) && class_exists( LearndashHelper::LMS_CLASS ) ) {
			$plugins[] = LearndashHelper::LMS_NAME;
		}

		return $plugins;
	}

	/**
	 * @return string
	 */
	private function get_active_plugin_post_type() {
		if ( self::$LMS_LIFTER_SHORT_SLUG === $this->selected_plugin ) {
			return LifterLMSHelper::LMS_POST_TYPE;
		}

		if ( self::$LMS_LEARNDASH_SLUG === $this->selected_plugin ) {
			return LearndashHelper::LMS_POST_TYPE;
		}

		return '';
	}

	/**
	 * @return string
	 */
	private function get_active_plugin_widget_name() {
		if ( self::$LMS_LIFTER_SHORT_SLUG === $this->selected_plugin ) {
			return LifterLMSHelper::LMS_WIDGET_NAME_ACTIVITY;
		}

		if ( self::$LMS_LEARNDASH_SLUG === $this->selected_plugin ) {
			return LearndashHelper::LMS_WIDGET_NAME_ACTIVITY;
		}

		return 'bb-no-lms-active';
	}

	/**
	 * @return string
	 */
	private function get_active_plugin_view_option() {
		if ( self::$LMS_LIFTER_SHORT_SLUG === $this->selected_plugin ) {
			return LifterLMSHelper::LMS_VIEW_OPTION;
		}

		if ( self::$LMS_LEARNDASH_SLUG === $this->selected_plugin ) {
			return LearndashHelper::LMS_VIEW_OPTION;
		}

		return 'bb-no-lms-active';
	}

	/**
	 * @return \BuddyBossTheme\LifterLMSHelper|\BuddyBossTheme\LearndashHelper|null|bool
	 */
	private function get_active_plugin_helper() {
		if ( ( empty( $this->selected_plugin ) && class_exists( '\BuddyBossTheme\LifterLMSHelper' ) && class_exists( LifterLMSHelper::LMS_CLASS ) )
		     || self::$LMS_LIFTER_SHORT_SLUG == $this->selected_plugin ) {
			/**
			 * @var \BuddyBossTheme\LifterLMSHelper
			 */
			return buddyboss_theme()->lifterlms_helper();
		}

		if ( ( empty( $this->selected_plugin ) && class_exists( '\BuddyBossTheme\LearndashHelper' ) && class_exists( LearndashHelper::LMS_CLASS ) )
		     || self::$LMS_LEARNDASH_SLUG == $this->selected_plugin ) {
			/**
			 * @var \BuddyBossTheme\LearndashHelper
			 */
			return buddyboss_theme()->learndash_helper();
		}

		return NULL;
	}

}