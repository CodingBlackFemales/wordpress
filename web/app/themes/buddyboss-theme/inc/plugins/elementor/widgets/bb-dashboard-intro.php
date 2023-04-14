<?php
namespace BBElementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Core\Schemes;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * @since 1.1.0
 */
class BBP_Dashboard_Intro extends Widget_Base {

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
		return 'bbp-dashboard-prior';
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
		return __( 'Dashboard Intro', 'buddyboss-theme' );
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
		return 'eicon-icon-box';
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
			'section_content_content',
			[
				'label'     => esc_html__( 'Content', 'buddyboss-theme' ),
			]
		);

		$this->add_responsive_control(
			'layout',
			[
				'label' => __( 'Position', 'buddyboss-theme' ),
				'type' => Controls_Manager::CHOOSE,
				'label_block' => false,
				'options' => [
					'left' => [
						'title' => __( 'Left', 'buddyboss-theme' ),
						'icon' => 'eicon-h-align-left',
					],
					'above' => [
						'title' => __( 'Above', 'buddyboss-theme' ),
						'icon' => 'eicon-v-align-top',
					],
					'right' => [
						'title' => __( 'Right', 'buddyboss-theme' ),
						'icon' => 'eicon-h-align-right',
					],
				],
				'prefix_class' => 'elementor-cta-%s-dash-intro-',
			]
		);

		$this->add_control(
			'separator_content',
			[
				'label'     => __( 'Description', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'heading',
			[
				'label' => __( 'Greeting & Description', 'buddyboss-theme' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'default' => __( 'Welcome', 'buddyboss-theme' ),
				'placeholder' => __( 'Enter greeting text', 'buddyboss-theme' ),
				'label_block' => true,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'description',
			[
				'label' => __( 'Description', 'buddyboss-theme' ),
				'type' => Controls_Manager::TEXTAREA,
				'dynamic' => [
					'active' => true,
				],
				'default' => __( 'to your Member Dashboard', 'buddyboss-theme' ),
				'placeholder' => __( 'Enter your introductory text', 'buddyboss-theme' ),
				'separator' => 'none',
				'rows' => 5,
				'show_label' => false,
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_avatar',
			[
				'label'     => esc_html__( 'Avatar', 'buddyboss-theme' ),
				'tab'       => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'avatar_size',
			[
				'label'     => __( 'Size', 'buddyboss-theme' ),
				'type'      => Controls_Manager::SLIDER,
				'default' => [
					'size' => 150,
				],
				'range' => [
					'px' => [
						'min'  => 20,
						'max'  => 200,
						'step' => 1,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .bb-dash__avatar' => 'flex: 0 0 {{SIZE}}px;',
					'{{WRAPPER}} .bb-dash__avatar img' => 'max-width: {{SIZE}}px; width: {{SIZE}}px;',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'        => 'avatar_border',
				'label'       => __( 'Border', 'buddyboss-theme' ),
				'placeholder' => '1px',
				'default'     => '1px',
				'selector'    => '{{WRAPPER}} .bb-dash__avatar img',
				'separator'   => 'before',
			]
		);

		$this->add_control(
			'avatar_padding',
			[
				'label' => __( 'Padding', 'buddyboss-theme' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'default' => [
					'top' => '3',
					'right' => '3',
					'bottom' => '3',
					'left' => '3',
					'isLinked' => true,
				],
				'selectors' => [
					'{{WRAPPER}} .bb-dash__avatar img' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'avatar_border_radius',
			[
				'label'      => __( 'Border Radius', 'buddyboss-theme' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ '%', 'px' ],
				'default' => [
					'top' => '4',
					'right' => '4',
					'bottom' => '4',
					'left' => '4',
				],
				'selectors'  => [
					'{{WRAPPER}} .bb-dash__avatar img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'avatar_shadow',
				'label'    => __( 'Shadow', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .bb-dash__avatar img',
			)
		);

		$this->add_control(
			'avatar_spacing',
			[
				'label' => __( 'Spacing', 'buddyboss-theme' ),
				'type'  => Controls_Manager::SLIDER,
				'default' => [
					'size' => 15,
				],
				'range' => [
					'px' => [
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .bb-dash__avatar'  => 'margin-right: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_content',
			[
				'label'     => esc_html__( 'Content', 'buddyboss-theme' ),
				'tab'       => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'greeting_color',
			[
				'label'     => __( 'Greeting Color', 'buddyboss-theme' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1E2132',
				'selectors' => [
					'{{WRAPPER}} .bb-dash__prior' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'info_color',
			[
				'label'     => __( 'Description Color', 'buddyboss-theme' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#5A5A5A',
				'selectors' => [
					'{{WRAPPER}} .bb-dash__brief' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_greeting',
				'label'    => __( 'Typography Greeting', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .bb-dash__prior .bb-dash__intro',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_name',
				'label'    => __( 'Typography Name', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .bb-dash__prior .bb-dash__name',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_info',
				'label'    => __( 'Typography Description', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .bb-dash__brief',
			)
		);

		$this->end_controls_section();

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

		$current_user = wp_get_current_user();
		$display_name =  function_exists( 'bp_core_get_user_displayname' ) ? bp_core_get_user_displayname( $current_user->ID ) : $current_user->display_name;

		// IF user not logged in then return and display nothing.
		if ( !is_user_logged_in() ) {
			return;
		}
		?>

		<div class="bb-dash">

			<div class="flex align-items-center">
				<div class="bb-dash__avatar"><?php echo get_avatar( get_current_user_id(), 300 ); ?></div>
				<div class="bb-dash__intro">
					<h2 class="bb-dash__prior">
						<span class="bb-dash__intro"><?php echo $settings['heading']; ?></span>
						<span class="bb-dash__name"><?php echo $display_name; ?></span>
					</h2>
					<div class="bb-dash__brief"><?php echo $settings['description']; ?></div>
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
