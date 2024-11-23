<?php
namespace BBElementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Core\Schemes;
use Elementor\Group_Control_Border;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * @since 1.1.0
 */
class BBP_Forums_Activity extends Widget_Base {

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
		return 'bbp-forums-activity';
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
		return __( 'Forums Activity', 'buddyboss-theme' );
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
		return 'eicon-archive';
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
			'section_content_layout',
			[
				'label'     => esc_html__( 'Layout', 'buddyboss-theme' ),
			]
		);

		$this->add_control(
			'switch_forum_title',
			[
				'label'   => esc_html__( 'Show Forum Title', 'buddyboss-theme' ),
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

		$this->add_control(
			'switch_excerpt',
			[
				'label'   => esc_html__( 'Show Excerpt', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->add_control(
			'switch_excerpt_icon',
			[
				'label'   => esc_html__( 'Show Reply Icon', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
				'condition' => [
					'switch_excerpt' => 'yes',
				],
			]
		);

		$this->add_control(
			'switch_link',
			[
				'label'   => esc_html__( 'Show Link', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
				'separator' => 'before',
			]
		);

		$this->add_control(
			'button_text',
			[
				'label' => __( 'Button Text', 'buddyboss-theme' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'default' => __( 'View Discussion', 'buddyboss-theme' ),
				'placeholder' => __( 'Enter button text', 'buddyboss-theme' ),
				'label_block' => true,
				'condition' => [
					'switch_link' => 'yes',
				],
			]
		);

		$this->add_control(
			'switch_my_discussions',
			[
				'label'   => esc_html__( 'My Discussions Button', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
				'separator' => 'before',
			]
		);

		$this->add_control(
			'my_discussions_button_text',
			[
				'label' => __( 'Button Text', 'buddyboss-theme' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'default' => __( 'View My Discussions', 'buddyboss-theme' ),
				'placeholder' => __( 'Enter button text', 'buddyboss-theme' ),
				'label_block' => true,
				'condition' => [
					'switch_my_discussions' => 'yes',
				],
			]
		);

		$this->add_control(
			'no_forums_paragraph_text',
			[
				'label' => __( 'No Forums Paragraph Text', 'buddyboss-theme' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'default' => __( 'You don\'t have any discussions yet.', 'buddyboss-theme' ),
				'placeholder' => __( 'Enter no forums paragraph text', 'buddyboss-theme' ),
				'label_block' => true,
				'separator' => 'before'
			]
		);

		$this->add_control(
			'no_forums_button_text',
			[
				'label' => __( 'No Forums Button Text', 'buddyboss-theme' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'default' => __( 'Explore Forums', 'buddyboss-theme' ),
				'placeholder' => __( 'Enter no forums button text', 'buddyboss-theme' ),
				'label_block' => true
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_box',
			[
				'label'     => esc_html__( 'Box', 'buddyboss-theme' ),
				'tab'       => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'        => 'box_border',
				'label'       => __( 'Border', 'buddyboss-theme' ),
				'placeholder' => '1px',
				'default'     => '1px',
				'selector'    => '{{WRAPPER}} .bb-forums-activity',
				'separator'   => 'before',
			]
		);

		$this->add_control(
			'box_border_radius',
			[
				'label'      => __( 'Border Radius', 'buddyboss-theme' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default' => [
					'top' => '4',
					'right' => '4',
					'bottom' => '4',
					'left' => '4',
				],
				'selectors'  => [
					'{{WRAPPER}} .bb-forums-activity' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'background_color',
				'label' => __( 'Background', 'buddyboss-theme' ),
				'types' => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .bb-forums-activity',
			]
		);

		$this->add_control(
			'box_padding',
			[
				'label'      => __( 'Padding', 'buddyboss-theme' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default' => [
					'top' => '20',
					'right' => '20',
					'bottom' => '20',
					'left' => '20',
				],
				'selectors'  => [
					'{{WRAPPER}} .bb-forums-activity' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_content',
			[
				'label'     => __( 'Content', 'buddyboss-theme' ),
				'tab'       => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'separator_forum_title',
			[
				'label'     => __( 'Forum Title', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'forum_title_color',
			[
				'label'     => __( 'Color', 'buddyboss-theme' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bb-fa__forum-title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'forum_title_typography',
				'label'    => __( 'Typography', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .bb-fa__forum-title',
			)
		);

		$this->add_control(
			'forum_title_spacing',
			[
				'label'   => __( 'Spacing', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SLIDER,
				'default' => [
					'size' => 0,
				],
				'range' => [
					'px' => [
						'min'  => 0,
						'max'  => 50,
						'step' => 1,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .bb-fa__forum-title' => 'margin-bottom: {{SIZE}}px;',
				],
			]
		);

		$this->add_control(
			'separator_topic_title',
			[
				'label'     => __( 'Topic Title', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'topic_title_color',
			[
				'label'     => __( 'Color', 'buddyboss-theme' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bb-fa__topic-title h2' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'topic_title_typography',
				'label'    => __( 'Typography', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .bb-fa__topic-title h2',
			)
		);

		$this->add_control(
			'topic_title_spacing',
			[
				'label'   => __( 'Spacing', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SLIDER,
				'default' => [
					'size' => 10,
				],
				'range' => [
					'px' => [
						'min'  => 0,
						'max'  => 50,
						'step' => 1,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .bb-fa__topic-title h2' => 'margin-bottom: {{SIZE}}px;',
				],
			]
		);

		$this->add_control(
			'separator_meta',
			[
				'label'     => __( 'Meta Data', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'meta_color',
			[
				'label'     => __( 'Color', 'buddyboss-theme' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bb-fa__meta span' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'meta_typography',
				'label'    => __( 'Typography', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .bb-fa__meta span',
			)
		);

		$this->add_control(
			'meta_spacing',
			[
				'label'   => __( 'Spacing', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SLIDER,
				'default' => [
					'size' => 20,
				],
				'range' => [
					'px' => [
						'min'  => 0,
						'max'  => 50,
						'step' => 1,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .bb-fa__meta' => 'margin-bottom: {{SIZE}}px;',
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
					'{{WRAPPER}} .bb-fa__excerpt' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'excerpt_typography',
				'label'    => __( 'Typography', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .bb-fa__excerpt',
			)
		);

		$this->add_control(
			'excerpt_spacing',
			[
				'label'   => __( 'Spacing', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SLIDER,
				'default' => [
					'size' => 20,
				],
				'range' => [
					'px' => [
						'min'  => 0,
						'max'  => 50,
						'step' => 1,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .bb-fa__excerpt' => 'margin-bottom: {{SIZE}}px;',
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
					'{{WRAPPER}} .bb-fa__link a' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'button_bgr_color',
			array(
				'label'     => __( 'Background Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bb-fa__link a' => 'background-color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'fa_button_border_color',
			array(
				'label'     => __( 'Border Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bb-fa__link a' => 'border-color: {{VALUE}}',
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
					'{{WRAPPER}} .bb-fa__link a:hover' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'button_bgr_color_hover',
			array(
				'label'     => __( 'Background Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bb-fa__link a:hover' => 'background-color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'fa_button_border_color_hover',
			array(
				'label'     => __( 'Border Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bb-fa__link a:hover' => 'border-color: {{VALUE}}',
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
				'selector' => '{{WRAPPER}} .bb-fa__link a',
			)
		);

		$this->add_responsive_control(
			'alignment',
			[
				'label' => __( 'Button Alignment', 'buddyboss-theme' ),
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
				'default' => 'right',
				'prefix_class' => 'elementor-cta-%s-falign-',
			]
		);

		$this->add_control(
			'button_padding',
			[
				'label'      => __( 'Button Padding', 'buddyboss-theme' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default' => [
					'top' => '4',
					'right' => '20',
					'bottom' => '4',
					'left' => '20',
				],
				'selectors'  => [
					'{{WRAPPER}} .bb-fa__link a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
				'selector'    => '{{WRAPPER}} .bb-fa__link a',
				'separator'   => 'before',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_my_discussions',
			[
				'label'     => esc_html__( 'My Discussions Button', 'buddyboss-theme' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'switch_my_discussions' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'my_alignment',
			[
				'label' => __( 'Button Alignment', 'buddyboss-theme' ),
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
				'default' => 'right',
				'prefix_class' => 'elementor-cta-%s-fa-my-align-',
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
					'{{WRAPPER}} .bb-forums-activity-btn a.bb-forums-activity-btn__link' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'button_my_bgr_color',
			array(
				'label'     => __( 'Background Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bb-forums-activity-btn a.bb-forums-activity-btn__link' => 'background-color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'button_fa_border_color',
			array(
				'label'     => __( 'Border Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bb-forums-activity-btn a.bb-forums-activity-btn__link' => 'border-color: {{VALUE}}',
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
					'{{WRAPPER}} .bb-forums-activity-btn a.bb-forums-activity-btn__link:hover' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'button_my_bgr_color_hover',
			array(
				'label'     => __( 'Background Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bb-forums-activity-btn a.bb-forums-activity-btn__link:hover' => 'background-color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'button_fa_border_color_hover',
			array(
				'label'     => __( 'Border Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bb-forums-activity-btn a.bb-forums-activity-btn__link:hover' => 'border-color: {{VALUE}}',
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
				'selector' => '{{WRAPPER}} .bb-forums-activity-btn a.bb-forums-activity-btn__link',
			)
		);

		$this->add_control(
			'button_my_padding',
			[
				'label'      => __( 'Button Padding', 'buddyboss-theme' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default' => [
					'top' => '2',
					'right' => '15',
					'bottom' => '2',
					'left' => '15',
				],
				'selectors'  => [
					'{{WRAPPER}} .bb-forums-activity-btn a.bb-forums-activity-btn__link' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
				'selector'    => '{{WRAPPER}} .bb-forums-activity-btn a.bb-forums-activity-btn__link',
				'separator'   => 'before',
			]
		);

		$this->add_control(
			'button_my_spacing',
			[
				'label'   => __( 'Spacing', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SLIDER,
				'default' => [
					'size' => 50,
				],
				'range' => [
					'px' => [
						'min'  => 30,
						'max'  => 100,
						'step' => 1,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .bb-forums-activity-btn' => 'top: -{{SIZE}}px;',
				],
			]
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

		$settings            = $this->get_settings_for_display();
		$forums_link         = trailingslashit( bp_loggedin_user_domain() . BP_FORUMS_SLUG );
		$my_discussions_link = trailingslashit( $forums_link . bbp_get_topic_archive_slug() );

		?>


		<div class="bb-forums-activity-wrapper <?php echo ( $settings['switch_my_discussions'] ) ? 'bb-forums-activity-wrapper--ismy' : ''; ?>">

			<?php if ( $settings['switch_my_discussions'] && is_user_logged_in() ) { ?>
				<div class="bb-forums-activity-btn">
					<a class="bb-forums-activity-btn__link" href="<?php echo esc_url( $my_discussions_link ); ?>"><?php echo wp_kses_post( $settings['my_discussions_button_text'] ); ?><i class="bb-icon-l bb-icon-angle-right"></i></a>
				</div>
			<?php } ?>

			<div class="bb-forums-activity">

			<?php
			if ( is_user_logged_in() ) {
				$current_user_id = get_current_user_id();

				$query = bbp_has_topics(
					array(
						'author'         => $current_user_id,
						'orderby'        => 'date',
						'order'          => 'DESC',
						'posts_per_page' => 1,
					)
				);

				if ( $query ) {


					// Determine user to use
					if ( bp_displayed_user_id() ) {
						$user_domain = bp_displayed_user_domain();
					} elseif ( bp_loggedin_user_domain() ) {
						$user_domain = bp_loggedin_user_domain();
					} else {
						return;
					}

					// User link
					$my_discussion_link = trailingslashit( $user_domain . bbp_get_root_slug() );
					while ( bbp_topics() ) :
						bbp_the_topic();

						$forum_title                = bbp_get_forum_title( bbp_get_topic_forum_id() );
						$topic_title                = bbp_get_topic_title( bbp_get_topic_id() );
						$topic_reply_count          = bbp_get_topic_reply_count( bbp_get_topic_id() );
						$get_last_reply_id          = bbp_get_topic_last_reply_id();
						$get_last_reply_author_name = bbp_get_reply_author_display_name( $get_last_reply_id );
						$get_last_reply_since       = bbp_get_topic_last_active_time( bbp_get_topic_id() );
						$get_discussion_link        = bbp_get_topic_permalink( bbp_get_topic_id() );
						$get_last_reply_excerpt     = bbp_get_reply_excerpt( $get_last_reply_id, 50 );


						?>
						<div class="bb-fa bb-fa--item">

							<?php if ($settings['switch_forum_title']) : ?>
								<div class="bb-fa__forum-title"><?php echo $forum_title; ?></div>
							<?php endif; ?>
							<div class="bb-fa__topic-title"><h2><?php echo $topic_title; ?></h2></div>
							<?php if ($settings['switch_meta']) : ?>
								<div class="bb-fa__meta">
									<span class="bb-fa__meta-count"><?php echo $topic_reply_count; ?> <?php _e( 'repl' . ($topic_reply_count != 1 ? 'ies' : 'y'), 'buddyboss-theme' ); ?> </span>
									<span class="bs-separator">·</span>
									<span class="bb-fa__meta-who"><?php echo $get_last_reply_author_name; ?> <?php _e( 'replied', 'buddyboss-theme' ); ?> </span>
									<span class="bb-fa__meta-when"><?php echo $get_last_reply_since; ?></span>
								</div>
							<?php endif; ?>
							<?php if ($settings['switch_excerpt']) : ?>
								<div class="bb-fa__excerpt <?php echo (!empty($get_last_reply_excerpt)) ? 'is-excerpt' : 'is-empty'; ?> <?php echo ( $settings['switch_excerpt_icon'] ) ? 'is-link' : 'no-link'; ?>">
									<?php
                                    if ($settings['switch_excerpt_icon']) :
	                                    $get_last_reply_id = bbp_get_topic_last_reply_id( bbp_get_topic_id() );
	                                    if ( bbp_is_topic( $get_last_reply_id ) ) {
		                                    add_filter( 'bbp_get_topic_reply_link', 'bb_theme_elementor_topic_link_attribute_change', 9999, 3 );
		                                    echo bbp_get_topic_reply_link( array(
			                                    'id' => $get_last_reply_id,
			                                    'reply_text' => ''
		                                    ));
		                                    remove_filter( 'bbp_get_topic_reply_link', 'bb_theme_elementor_topic_link_attribute_change', 9999, 3 );
		                                    // If post is a reply, print the reply admin links instead.
	                                    } else {
		                                    add_filter( 'bbp_get_reply_to_link', 'bb_theme_elementor_reply_link_attribute_change', 9999, 3 );
		                                    echo bbp_get_reply_to_link( array(
			                                    'id' => $get_last_reply_id,
			                                    'reply_text' => ''
		                                    ));
		                                    remove_filter( 'bbp_get_reply_to_link', 'bb_theme_elementor_reply_link_attribute_change', 9999, 3 );
	                                    }
                                    endif; ?>
									<?php echo $get_last_reply_excerpt; ?>
								</div>
							<?php endif; ?>
							<?php if ($settings['switch_link']) : ?>
								<div class="bb-fa__link"><a href="<?php echo $get_discussion_link; ?>"><?php echo $settings['button_text']; ?></a></div>
							<?php endif; ?>

						</div>

					<?php

					endwhile;

				} else { ?>

					<div class="bb-no-data bb-no-data--fa-activity">
						<img class="bb-no-data__image" src="<?php echo get_template_directory_uri(); ?>/assets/images/svg/dfy-no-data-icon03.svg" alt="Forums Activity" />
						<div class="bb-no-data__msg"><?php echo esc_html( $settings['no_forums_paragraph_text'] ); ?></div>
						<?php if( '' !== $settings['no_forums_button_text']) { ?>
							<a href="<?php echo home_url(bbp_get_root_slug()); ?>" class="bb-no-data__link"><?php echo esc_html( $settings['no_forums_button_text'] ); ?></a>
						<?php } ?>
					</div>

				<?php }

			} else { ?>

				<div class="bb-no-data bb-no-data--fa-activity">
					<img class="bb-no-data__image" src="<?php echo get_template_directory_uri(); ?>/assets/images/svg/dfy-no-data-icon03.svg" alt="Forums Activity" />
					<div class="bb-no-data__msg"><?php _e( 'You are not logged in.', 'buddyboss-theme' ); ?></div>
					<?php if( '' !== $settings['no_forums_button_text']) { ?>
						<a href="<?php echo home_url(bbp_get_root_slug()); ?>" class="bb-no-data__link"><?php echo $settings['no_forums_button_text']; ?></a>
					<?php } ?>
				</div>

			<?php } ?>

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
