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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! defined( 'BB_LMS_WIDGET' ) ) {
	define( 'BB_LMS_WIDGET', true );
} // Prevent loading templates outside of this widget

/**
 * Class BB_Lms_Courses
 * @package BBElementor\Widgets
 */
class BB_Lms_Courses extends Widget_Base {

	/**
	 * Lifter LMS slug
	 */
	static public $LMS_LIFTER_SLUG = 'llms';

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
		 * Below slug use for All LMS plugins. Currently we are not going to update to make it as generic as existing client already using this widget block.
		 * This slug will not display or use on Frontend. This is only use by elementor to identify widget.
		 * In future if we want to update it then we need to write migration script to update following `_elementor_data` and `_elementor_controls_usage` meta using migration script.
		 */
		return 'ld-courses';
	}

	/**
	 * Retrieve the widget title.
	 * @since  1.1.0
	 * @access public
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Course Grid', 'buddyboss-theme' );
	}

	/**
	 * Retrieve the widget icon.
	 * @since  1.1.0
	 * @access public
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-posts-grid';
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
		return array( 'buddyboss-elements' );
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
		$helper      = $this->get_active_plugin_helper();

		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Content', 'buddyboss-theme' ),
			)
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
				'skin_style',
				array(
					'label'   => __( 'Skin', 'buddyboss-theme' ),
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => 'classic',
					'options' => array(
						'classic' => __( 'Classic', 'buddyboss-theme' ),
						'cover'   => __( 'Cover', 'buddyboss-theme' ),
					),
				)
			);

			$cat_slug    = $this->get_active_plugin_category();
			$this->add_control( 'categories', array(
				'label'    => __( 'Course Categories', 'buddyboss-theme' ),
				'type'     => \Elementor\Controls_Manager::SELECT2,
				'multiple' => true,
				'options'  => $this->get_active_plugin_term_list( $cat_slug ),
				'default'  => array(),
			) );

			$tag_slug    = $this->get_active_plugin_tag();
			$this->add_control( 'tags', array(
				'label'    => __( 'Course Tags', 'buddyboss-theme' ),
				'type'     => \Elementor\Controls_Manager::SELECT2,
				'multiple' => true,
				'options'  => $this->get_active_plugin_term_list( $tag_slug ),
				'default'  => array(),
			) );

			$this->add_control(
				'posts_per_page',
				array(
					'label'   => __( 'Posts Per Page', 'buddyboss-theme' ),
					'type'    => Controls_Manager::NUMBER,
					'default' => 8,
				)
			);

			$this->add_control(
				'switch_featured_row',
				array(
					'label'     => esc_html__( 'Show Featured Row', 'buddyboss-theme' ),
					'type'      => Controls_Manager::SWITCHER,
					'default'   => 'yes',
					'condition' => array(
						'skin_style' => 'cover',
					),
				)
			);

			$this->add_control(
				'switch_heading',
				array(
					'label'   => esc_html__( 'Title', 'buddyboss-theme' ),
					'type'    => Controls_Manager::SWITCHER,
					'default' => 'no',
				)
			);

			$this->add_control(
				'switch_search',
				array(
					'label'   => esc_html__( 'Search Courses', 'buddyboss-theme' ),
					'type'    => Controls_Manager::SWITCHER,
					'default' => 'no',
				)
			);

			$this->add_control(
				'switch_courses_nav',
				array(
					'label'   => esc_html__( 'Courses Navigation', 'buddyboss-theme' ),
					'type'    => Controls_Manager::SWITCHER,
					'default' => 'no',
				)
			);

			$this->add_control(
				'switch_pagination',
				array(
					'label'   => esc_html__( 'Pagination', 'buddyboss-theme' ),
					'type'    => Controls_Manager::SWITCHER,
					'default' => 'no',
				)
			);

			$this->add_control(
				'separator_filters',
				array(
					'label'     => __( 'Filters', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
				)
			);

			$this->add_control(
				'orderby_filter',
				array(
					'label'        => __( 'Order by Filter', 'buddyboss-theme' ),
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => __( 'Show', 'buddyboss-theme' ),
					'label_off'    => __( 'Hide', 'buddyboss-theme' ),
					'return_value' => 'on',
					'default'      => 'on',
				)
			);

			if ( '' !== trim( $helper->print_categories_options() ) ) {
				$this->add_control(
					'category_filter',
					array(
						'label'        => __( 'Category Filter', 'buddyboss-theme' ),
						'type'         => Controls_Manager::SWITCHER,
						'label_on'     => __( 'Show', 'buddyboss-theme' ),
						'label_off'    => __( 'Hide', 'buddyboss-theme' ),
						'return_value' => 'on',
						'default'      => 'on',
						'condition' => [
							'categories' => array(),
							'tags'       => array(),
						],
					)
				);
			}

			$this->add_control(
				'instructors_filter',
				array(
					'label'        => __( 'Instructors Filter', 'buddyboss-theme' ),
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => __( 'Show', 'buddyboss-theme' ),
					'label_off'    => __( 'Hide', 'buddyboss-theme' ),
					'return_value' => 'on',
					'default'      => 'on',
				)
			);

			$this->add_control(
				'grid_filter',
				array(
					'label'        => __( 'Grid Filter', 'buddyboss-theme' ),
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => __( 'Show', 'buddyboss-theme' ),
					'label_off'    => __( 'Hide', 'buddyboss-theme' ),
					'return_value' => 'on',
					'default'      => 'on',
					'condition'    => array(
						'skin_style' => 'classic',
					),
				)
			);

			$this->end_controls_section();

			$this->start_controls_section(
				'section_style_header',
				array(
					'label' => __( 'Header', 'buddyboss-theme' ),
					'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
					'conditions' => array(
						'relation' => 'or',
						'terms' => [
							['name' => 'switch_heading', 'operator' => '===', 'value' => 'yes'],
							['name' => 'switch_search', 'operator' => '===', 'value' => 'yes'],
							['name' => 'switch_courses_nav', 'operator' => '===', 'value' => 'yes'],
						],
					),
				)
			);

			$this->add_control(
				'separator_style_heading',
				array(
					'label'     => __( 'Title', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
					'condition' => array(
						'switch_heading' => 'yes',
					),
				)
			);

			$this->add_control(
				'course_heading_color',
				array(
					'label'     => __( 'Color', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bb-courses-header .bb-title' => 'color: {{VALUE}}',
					),
					'condition' => array(
						'switch_heading' => 'yes',
					),
				)
			);

			$this->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				array(
					'name'      => 'typography_course_heading',
					'label'     => __( 'Typography', 'buddyboss-theme' ),
					'selector'  => '{{WRAPPER}} .bb-courses-header .bb-title',
					'condition' => array(
						'switch_heading' => 'yes',
					),
				)
			);

			$this->add_control(
				'course_heading_space',
				array(
					'label'      => __( 'Spacing', 'buddyboss-theme' ),
					'type'       => \Elementor\Controls_Manager::SLIDER,
					'size_units' => array( 'px' ),
					'range'      => array(
						'px' => array(
							'min'  => 0,
							'max'  => 100,
							'step' => 1,
						),
					),
					'default'    => array(
						'unit' => 'px',
						'size' => 25,
					),
					'selectors'  => array(
						'{{WRAPPER}} .bb-courses-header' => 'margin-bottom: {{SIZE}}{{UNIT}};',
					),
					'condition'  => array(
						'switch_heading' => 'yes',
					),
				)
			);

			$this->add_control(
				'separator_style_navigation',
				array(
					'label'     => __( 'Navigation', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
					'condition' => array(
						'switch_courses_nav' => 'yes',
					),
				)
			);

			$this->add_responsive_control(
				'nav_alignment',
				array(
					'label'        => __( 'Alignment', 'buddyboss-theme' ),
					'type'         => \Elementor\Controls_Manager::CHOOSE,
					'label_block'  => false,
					'options'      => array(
						'left'   => array(
							'title' => __( 'Left', 'buddyboss-theme' ),
							'icon'  => 'eicon-h-align-left',
						),
						'center' => array(
							'title' => __( 'Center', 'buddyboss-theme' ),
							'icon'  => 'eicon-h-align-center',
						),
						'right'  => array(
							'title' => __( 'Right', 'buddyboss-theme' ),
							'icon'  => 'eicon-h-align-right',
						),
					),
					'default'      => 'left',
					'prefix_class' => 'lms-nav-%s-align-',
					'condition'    => array(
						'switch_courses_nav' => 'yes',
					),
				)
			);

			$this->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				array(
					'name'      => 'typography_courses_nav',
					'label'     => __( 'Typography', 'buddyboss-theme' ),
					'selector'  => '{{WRAPPER}} .bp-navs ul li a',
					'condition' => array(
						'switch_courses_nav' => 'yes',
					),
				)
			);

			$this->start_controls_tabs(
				'nav_tabs',
				array(
					'condition' => array(
						'switch_courses_nav' => 'yes',
					),
				)
			);

			$this->start_controls_tab(
				'nav_active_tab',
				array(
					'label' => __( 'Active', 'buddyboss-theme' ),
				)
			);

			$this->add_control(
				'nav_color_active',
				array(
					'label'     => __( 'Color', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bp-navs ul li.selected a' => 'color: {{VALUE}}',
					),
				)
			);

			$this->add_control(
				'count_bgr_color_active',
				array(
					'label'     => __( 'Counter Background', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bb-courses-directory .bp-navs .selected .count' => 'background-color: {{VALUE}}',
					),
				)
			);

			$this->add_control(
				'nav_border_active',
				array(
					'label'     => __( 'Border Color', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bp-navs ul li.selected a' => 'border-bottom-color: {{VALUE}}',
					),
				)
			);

			$this->end_controls_tab();

			$this->start_controls_tab(
				'nav_normal_tab',
				array(
					'label' => __( 'Normal', 'buddyboss-theme' ),
				)
			);

			$this->add_control(
				'nav_color',
				array(
					'label'     => __( 'Color', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bp-navs ul li:not(.selected) a' => 'color: {{VALUE}}',
					),
				)
			);

			$this->add_control(
				'count_bgr_color',
				array(
					'label'     => __( 'Counter Background', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bp-navs ul li:not(.selected) .count' => 'background-color: {{VALUE}}',
					),
				)
			);

			$this->end_controls_tab();

			$this->start_controls_tab(
				'nav_hover_tab',
				array(
					'label' => __( 'Hover', 'buddyboss-theme' ),
				)
			);

			$this->add_control(
				'nav_color_hover',
				array(
					'label'     => __( 'Color', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bp-navs ul li:not(.selected) a:hover' => 'color: {{VALUE}}',
					),
				)
			);

			$this->add_control(
				'count_bgr_color_hover',
				array(
					'label'     => __( 'Counter Background', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bp-navs ul li:not(.selected) a:hover .count' => 'background-color: {{VALUE}}',
					),
				)
			);

			$this->end_controls_tab();

			$this->end_controls_tabs();

			$this->add_control(
				'nav_space',
				array(
					'label'      => __( 'Spacing', 'buddyboss-theme' ),
					'type'       => \Elementor\Controls_Manager::SLIDER,
					'size_units' => array( 'px' ),
					'range'      => array(
						'px' => array(
							'min'  => 0,
							'max'  => 100,
							'step' => 1,
						),
					),
					'default'    => array(
						'unit' => 'px',
						'size' => 30,
					),
					'separator'  => 'before',
					'selectors'  => array(
						'{{WRAPPER}} .bb-courses-directory .bp-navs li' => 'padding-right: {{SIZE}}{{UNIT}};',
					),
					'condition'  => array(
						'switch_courses_nav' => 'yes',
					),
				)
			);

			$this->add_control(
				'nav_counter_space',
				array(
					'label'      => __( 'Counter Spacing', 'buddyboss-theme' ),
					'type'       => \Elementor\Controls_Manager::SLIDER,
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
						'size' => 3,
					),
					'selectors'  => array(
						'{{WRAPPER}} .bp-navs ul li .count' => 'margin-left: {{SIZE}}{{UNIT}};',
					),
					'condition'  => array(
						'switch_courses_nav' => 'yes',
					),
				)
			);

			$this->add_control(
				'separator_style_search',
				array(
					'label'     => __( 'Search', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
					'condition' => array(
						'switch_search' => 'yes',
					),
				)
			);

			$this->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				array(
					'name'      => 'typography_search',
					'label'     => __( 'Typography', 'buddyboss-theme' ),
					'selector'  => '{{WRAPPER}} .bs-dir-search input[type=text]',
					'condition' => array(
						'switch_search' => 'yes',
					),
				)
			);

			$this->add_control(
				'search_color',
				array(
					'label'     => __( 'Color', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bs-dir-search input[type=text]' => 'color: {{VALUE}}',
					),
					'condition' => array(
						'switch_search' => 'yes',
					),
				)
			);

			$this->add_control(
				'search_bgr_color',
				array(
					'label'     => __( 'Background Color', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bs-search-form' => 'background-color: {{VALUE}}',
					),
					'condition' => array(
						'switch_search' => 'yes',
					),
				)
			);

			$this->add_group_control(
				\Elementor\Group_Control_Border::get_type(),
				array(
					'name'        => 'search_border',
					'label'       => __( 'Border', 'buddyboss-theme' ),
					'placeholder' => '1px',
					'default'     => '1px',
					'selector'    => '{{WRAPPER}} .bs-search-form',
					'condition'   => array(
						'switch_search' => 'yes',
					),
				)
			);

			$this->add_control(
				'search_border_radius',
				array(
					'label'      => __( 'Border Radius', 'buddyboss-theme' ),
					'type'       => \Elementor\Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'default'    => array(
						'top'    => '100',
						'right'  => '100',
						'bottom' => '100',
						'left'   => '100',
					),
					'selectors'  => array(
						'{{WRAPPER}} .bs-search-form' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
					'condition'  => array(
						'switch_search' => 'yes',
					),
				)
			);

			$this->end_controls_section();

			$this->lms_controls_switch();

			$this->start_controls_section(
				'section_style_pagination',
				array(
					'label'     => __( 'Pagination', 'buddyboss-theme' ),
					'tab'       => Controls_Manager::TAB_STYLE,
					'condition' => array(
						'switch_pagination' => 'yes',
					),
				)
			);

			$this->add_responsive_control(
				'alignment',
				array(
					'label'        => __( 'Alignment', 'buddyboss-theme' ),
					'type'         => Controls_Manager::CHOOSE,
					'label_block'  => false,
					'options'      => array(
						'left'   => array(
							'title' => __( 'Left', 'buddyboss-theme' ),
							'icon'  => 'eicon-h-align-left',
						),
						'center' => array(
							'title' => __( 'Center', 'buddyboss-theme' ),
							'icon'  => 'eicon-h-align-center',
						),
						'right'  => array(
							'title' => __( 'Right', 'buddyboss-theme' ),
							'icon'  => 'eicon-h-align-right',
						),
					),
					'default'      => 'right',
					'prefix_class' => 'pagination-cta-%s-align-',
				)
			);

			$this->add_control(
				'switch_pagination_arrows',
				array(
					'label'   => esc_html__( 'Pagination Arrows', 'buddyboss-theme' ),
					'type'    => Controls_Manager::SWITCHER,
					'default' => 'yes',
				)
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => 'typography_pagination',
					'label'    => __( 'Typography', 'buddyboss-theme' ),
					'selector' => '{{WRAPPER}} .bb-lms-pagination > *, {{WRAPPER}} .bb-lms-pagination a.next.page-numbers:before, {{WRAPPER}} .bb-lms-pagination a.prev.page-numbers:before',
				)
			);

			$this->add_control(
				'size',
				array(
					'label'      => __( 'Size', 'buddyboss-theme' ),
					'type'       => Controls_Manager::SLIDER,
					'size_units' => array( 'px' ),
					'range'      => array(
						'px' => array(
							'min'  => 5,
							'max'  => 100,
							'step' => 1,
						),
					),
					'default'    => array(
						'unit' => 'px',
						'size' => 25,
					),
					'selectors'  => array(
						'{{WRAPPER}} .bb-lms-pagination .page-numbers' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; line-height: {{SIZE}}{{UNIT}};',
					),
				)
			);

			$this->add_control(
				'space_between',
				array(
					'label'      => __( 'Space between', 'buddyboss-theme' ),
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
						'{{WRAPPER}} .bb-lms-pagination > *' => 'margin-right: {{SIZE}}{{UNIT}};',
						'.bb-template-v2 {{WRAPPER}} .bb-lms-pagination a.page-numbers:not(.prev):not(.next)' => 'margin-right: {{SIZE}}{{UNIT}};',
					),
				)
			);

			$this->add_control(
				'separator_page_numbers',
				array(
					'label'     => __( 'Page Numbers', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
				)
			);

			$this->start_controls_tabs(
				'pagination_tabs'
			);

			$this->start_controls_tab(
				'pagination_normal_tab',
				array(
					'label' => __( 'Normal', 'buddyboss-theme' ),
				)
			);

			$this->add_control(
				'pagination_color',
				array(
					'label'     => __( 'Color', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bb-lms-pagination > a:not(.next):not(.prev)' => 'color: {{VALUE}}',
						'.bb-template-v2 {{WRAPPER}} .bb-lms-pagination > a.next' => 'color: {{VALUE}}',
						'.bb-template-v2 {{WRAPPER}} .bb-lms-pagination > a.prev' => 'color: {{VALUE}}',
					),
				)
			);

			$this->add_control(
				'pagination_bgr_color',
				array(
					'label'     => __( 'Background Color', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bb-lms-pagination > a:not(.next):not(.prev)' => 'background-color: {{VALUE}}',
						'.bb-template-v2 {{WRAPPER}} .bb-lms-pagination > a.next' => 'background-color: {{VALUE}}',
						'.bb-template-v2 {{WRAPPER}} .bb-lms-pagination > a.prev' => 'background-color: {{VALUE}}',
					),
				)
			);

			$this->end_controls_tab();

			$this->start_controls_tab(
				'pagination_hover_tab',
				array(
					'label' => __( 'Hover', 'buddyboss-theme' ),
				)
			);

			$this->add_control(
				'pagination_color_hover',
				array(
					'label'     => __( 'Color', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bb-lms-pagination > a:not(.next):not(.prev):hover' => 'color: {{VALUE}}',
					),
				)
			);

			$this->add_control(
				'pagination_bgr_color_hover',
				array(
					'label'     => __( 'Background Color', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bb-lms-pagination > a:not(.next):not(.prev):hover' => 'background-color: {{VALUE}}',
					),
				)
			);

			$this->end_controls_tab();

			$this->end_controls_tabs();

			$this->add_control(
				'active_color',
				array(
					'label'     => __( 'Current Page Color', 'buddyboss-theme' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bb-lms-pagination > span.page-numbers:not(.dots)' => 'color: {{VALUE}};',
					),
					'separator' => 'before',
				)
			);

			$this->add_control(
				'active_bgr',
				array(
					'label'     => __( 'Current Page Background Color', 'buddyboss-theme' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bb-lms-pagination > span.page-numbers:not(.dots)' => 'background-color: {{VALUE}};',
					),
				)
			);

			$this->add_control(
				'page_num_radius',
				array(
					'label'      => __( 'Border Radius', 'buddyboss-theme' ),
					'type'       => Controls_Manager::SLIDER,
					'size_units' => array( 'px' ),
					'range'      => array(
						'px' => array(
							'min'  => 0,
							'max'  => 100,
							'step' => 1,
						),
					),
					'default'    => array(
						'unit' => 'px',
						'size' => 6,
					),
					'selectors'  => array(
						'{{WRAPPER}} .bb-lms-pagination > .page-numbers' => 'border-radius: {{SIZE}}{{UNIT}};',
					),
				)
			);

			$this->add_control(
				'separator_page_arrows',
				array(
					'label'     => __( 'Page Arrows', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
				)
			);

			$this->start_controls_tabs(
				'pagination_arrows'
			);

			$this->start_controls_tab(
				'pagination_arrows_normal_tab',
				array(
					'label' => __( 'Normal', 'buddyboss-theme' ),
				)
			);

			$this->add_control(
				'pagination_arrows_color',
				array(
					'label'     => __( 'Color', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bb-lms-pagination > a.next' => 'color: {{VALUE}}',
						'{{WRAPPER}} .bb-lms-pagination > a.prev' => 'color: {{VALUE}}',
					),
				)
			);

			$this->end_controls_tab();

			$this->start_controls_tab(
				'pagination_arrows_hover_tab',
				array(
					'label' => __( 'Hover', 'buddyboss-theme' ),
				)
			);

			$this->add_control(
				'pagination_arrows_color_hover',
				array(
					'label'     => __( 'Color', 'buddyboss-theme' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .bb-lms-pagination > a.next:hover' => 'color: {{VALUE}}',
						'{{WRAPPER}} .bb-lms-pagination > a.prev:hover' => 'color: {{VALUE}}',
					),
				)
			);

			$this->end_controls_tab();

			$this->end_controls_tabs();
		}

		$this->end_controls_section();
	}

	private function lms_controls_switch() {

		$name      = $this->get_active_plugin_name();
		$nameLower = strtolower( $name );

		$controlsPath = ELEMENTOR_BB__DIR__ . '/widgets/courses/controls/' . $nameLower . '-courses-controls.php';

		if ( file_exists( $controlsPath ) ) {
			require $controlsPath;
		}

	}

	/**
	 * Render the widget output on the frontend.
	 * Written in PHP and used to generate the final HTML.
	 * @since  1.1.0
	 * @access protected
	 */
	protected function render() {
		global $post;

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

		$current_page_url = get_permalink( $post->ID );
		$helper           = $this->get_active_plugin_helper();
		$name             = $this->get_active_plugin_name();
		$nameLower        = strtolower( $name );

		$course_box_border = isset( $settings['box_border_style'] ) ? $settings['box_border_style'] : '';
		$settings_skin     = isset( $settings['skin_style'] ) ? $settings['skin_style'] : 'classic';
		$course_cols       = isset( $settings['columns_num'] ) ? $settings['columns_num'] : 'default';
		$posts_per_page    = $this->get_settings( 'posts_per_page' );
		$current_page      = get_query_var( 'paged', ! empty( $_GET['current_page'] ) ? absint( $_GET['current_page'] ) : 1 );
		$category          = isset( $settings['categories'] ) ? $settings['categories'] : '';
		$tags              = isset( $settings['tags'] ) ? $settings['tags'] : '';

		add_action( 'pre_get_posts', array(
			$helper,
			'filter_query_ajax_get_courses',
		), 999 );

		$query_args = array(
			'post_type'      => $this->get_active_plugin_post_type(),
			'posts_per_page' => $posts_per_page,
			'paged'          => $current_page,
		);

		/**
		 * Below $tax_query will user with `get_my_courses_count` function also.
		 */
		$tax_query = array();
		if ( ! empty( $category ) || ! empty( $tags ) ) {
			$tax_query = array(
				'relation' => 'AND',
			);

			if ( ! empty( $category ) ) {
				$tax_query[] = array(
					'taxonomy' => $this->get_active_plugin_category(),
					'field'    => 'id',
					'terms'    => $category,
				);
			}

			if ( ! empty( $tags ) ) {
				$tax_query[] = array(
					'taxonomy' => $this->get_active_plugin_tag(),
					'field'    => 'id',
					'terms'    => $tags,
				);
			}

			$query_args['tax_query'] = $tax_query;
		}
		$query = new \WP_Query( $query_args );

		$view = get_option( $this->get_active_plugin_view_option(), 'grid' );

		// Same settings for each LMS
		$this->add_render_attribute( 'ld-switch', 'class', $nameLower . '-course-list ' . $nameLower . '-course-list--elementor' );
		$this->add_render_attribute( 'ld-switch', 'class', $nameLower . '-course-list--' . $settings_skin );

		if ( !$settings['switch_heading'] ) {
			$this->add_render_attribute( 'ld-switch', 'class', 'noTitle' );
		}

		if ( !$settings['switch_search'] ) {
			$this->add_render_attribute( 'ld-switch', 'class', 'noSearch' );
		}

		if ( !$settings['switch_courses_nav'] ) {
			$this->add_render_attribute( 'ld-switch', 'class', 'noCourseNavigation' );
		}

		if ( !$settings['switch_pagination'] ) {
			$this->add_render_attribute( 'ld-switch', 'class', 'noPagination' );
		}

		if ( $settings['switch_featured_row'] ) {
			$this->add_render_attribute( 'ld-switch', 'class', $nameLower . '-course-list--featured' );
		}

		if ( isset( $settings['switch_progress'] ) && ! $settings['switch_progress'] ) {
			$this->add_render_attribute( 'ld-switch', 'class', 'noProgress' );
		}

		if ( isset( $settings['switch_price'] ) && ! $settings['switch_price'] ) {
			$this->add_render_attribute( 'ld-switch', 'class', 'noPrice' );
		}

		if ( 'llms' === $shortName ) {
			if ( isset( $settings['switch_enroll'] ) && ! $settings['switch_enroll'] ) {
				$this->add_render_attribute( 'ld-switch', 'class', 'noEnroll' );
			}

			if ( isset( $settings['switch_time'] ) && ! $settings['switch_time'] ) {
				$this->add_render_attribute( 'ld-switch', 'class', 'noTimestamp' );
			}
		}

		$this->add_render_attribute( $shortName . '-pagination-switch', 'class', 'bb-lms-pagination all' );

		if ( isset( $settings['switch_pagination_arrows'] ) && ! $settings['switch_pagination_arrows'] ) {
			$this->add_render_attribute( $shortName . '-pagination-switch', 'class', 'noPrevNext' );
		}

		// Specific settings for each LMS
		if ( 'ld' === $shortName ) {
			if ( isset( $settings['switch_author'] ) && ! $settings['switch_author'] ) {
				$this->add_render_attribute( $shortName . '-switch', 'class', 'noMeta' );
			}

			if ( isset( $settings['switch_excerpt'] ) && ! $settings['switch_excerpt'] ) {
				$this->add_render_attribute( $shortName . '-switch', 'class', 'noExcerpt' );
			}
		}

		if ( 'llms' === $shortName ) {
			$this->add_render_attribute( 'course-dir-list', 'class', 'course-dir-list bs-dir-list columns-' . $course_cols . '' );

			if ( isset( $settings['switch_media'] ) && $settings['switch_media'] ) {
				$this->add_render_attribute( 'course-dir-list', 'class', 'course-dir-list--media' );
			} else {
				$this->add_render_attribute( 'course-dir-list', 'class', 'course-dir-list--hidemedia' );
			}

			if ( isset( $settings['switch_status'] ) && $settings['switch_status'] ) {
				$this->add_render_attribute( 'course-dir-list', 'class', 'course-dir-list--status' );
			} else {
				$this->add_render_attribute( 'course-dir-list', 'class', 'course-dir-list--hidestatus' );
			}
		}

		$templatePath = ELEMENTOR_BB__DIR__ . '/widgets/courses/templates/' . $nameLower . '-courses-template.php';

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
	 * @return bool
	 */
	private function is_lifter_block(){
		return ( ( empty( $this->selected_plugin ) && class_exists( '\BuddyBossTheme\LifterLMSHelper' ) && class_exists( LifterLMSHelper::LMS_CLASS ) )
		         || self::$LMS_LIFTER_SLUG == $this->selected_plugin );
	}

	/**
	 * @return bool
	 */
	private function is_learndash_block(){
		return ( ( empty( $this->selected_plugin ) && class_exists( '\BuddyBossTheme\LearndashHelper' ) && class_exists( LearndashHelper::LMS_CLASS )  )
		         || self::$LMS_LEARNDASH_SLUG == $this->selected_plugin );
	}

	/**
	 * @return string
	 */
	private function get_active_plugin_name() {
		if ( $this->is_lifter_block() ) {
			return LifterLMSHelper::LMS_NAME;
		}

		if ( $this->is_learndash_block() ) {
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
		if ( $this->is_lifter_block() ) {
			return class_exists( '\BuddyBossTheme\LifterLMSHelper' ) && class_exists( LifterLMSHelper::LMS_CLASS );
		}

		if ( $this->is_learndash_block() ) {
			return class_exists( '\BuddyBossTheme\LearndashHelper' ) && class_exists( LearndashHelper::LMS_CLASS );
		}

		return false;
	}

	/**
	 * @return string
	 */
	private function get_active_plugin_list() {
		$plugins = array();
		if ( $this->is_lifter_block() ) {
			$plugins[] = LifterLMSHelper::LMS_NAME;
		}

		if ( $this->is_learndash_block() ) {
			$plugins[] = LearndashHelper::LMS_NAME;
		}

		return $plugins;
	}

	/**
	 * @return string
	 */
	private function get_active_plugin_post_type() {
		if ( $this->is_lifter_block() ) {
			return LifterLMSHelper::LMS_POST_TYPE;
		}

		if ( $this->is_learndash_block() ) {
			return LearndashHelper::LMS_POST_TYPE;
		}

		return '';
	}

	/**
	 * @return array
	 */
	private function get_active_plugin_term_list( $taxonomy = '' ) {

		if ( ! empty( $taxonomy ) ) {
			$categories = get_terms( [
				'taxonomy' => $taxonomy,
			] );

			if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
				$categories = array_column( $categories, 'name', 'term_id' );

				return $categories;
			}
		}
		return array();
	}

	/**
	 * @return string
	 */
	private function get_active_plugin_category() {
		$taxonomy = '';

		if ( $this->is_lifter_block() ) {
			$taxonomy = LifterLMSHelper::LMS_CATEGORY_SLUG;
		}

		if ( $this->is_learndash_block() ) {
			$taxonomy = LearndashHelper::LMS_CATEGORY_SLUG;
		}

		return $taxonomy;
	}

	private function get_active_plugin_tag() {
		$taxonomy = '';
		if ( $this->is_lifter_block() ) {
			$taxonomy = LifterLMSHelper::LMS_TAG_SLUG;
		}

		if ( $this->is_learndash_block() ) {
			$taxonomy = LearndashHelper::LMS_TAG_SLUG;
		}

		return $taxonomy;
	}

	/**
	 * @return string
	 */
	private function get_active_plugin_widget_name() {
		if ( $this->is_lifter_block() ) {
			return LifterLMSHelper::LMS_WIDGET_NAME_COURSES;
		}

		if ( $this->is_learndash_block() ) {
			return LearndashHelper::LMS_WIDGET_NAME_COURSES;
		}

		return 'bb-no-lms-active';
	}

	/**
	 * @return string
	 */
	private function get_active_plugin_view_option() {
		if ( $this->is_lifter_block() ) {
			return LifterLMSHelper::LMS_VIEW_OPTION;
		}

		if ( $this->is_learndash_block() ) {
			return LearndashHelper::LMS_VIEW_OPTION;
		}

		return 'bb-no-lms-active';
	}

	/**
	 * @return \BuddyBossTheme\LifterLMSHelper|\BuddyBossTheme\LearndashHelper|null|bool
	 */
	private function get_active_plugin_helper() {
		if ( $this->is_lifter_block() ) {
			/**
			 * @var \BuddyBossTheme\LifterLMSHelper
			 */
			return buddyboss_theme()->lifterlms_helper();
		}

		if ( $this->is_learndash_block() ) {
			/**
			 * @var \BuddyBossTheme\LearndashHelper
			 */
			return buddyboss_theme()->learndash_helper();
		}

		return NULL;
	}

}