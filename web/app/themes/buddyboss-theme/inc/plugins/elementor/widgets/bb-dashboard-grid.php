<?php
namespace BBElementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Core\Schemes;
use Elementor\Group_Control_Border;
use Elementor\Repeater;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Box_Shadow;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * @since 1.1.0
 */
class BBP_Dashboard_Grid extends Widget_Base {

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
		return 'bbp-dashboard-grid';
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
		return __( 'Dashboard Grid', 'buddyboss-theme' );
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
		return 'eicon-gallery-grid';
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
			'section_list',
			[
				'label' => __( 'Grid', 'buddyboss-theme' ),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);

		$repeater = new Repeater();

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
			'switch_description_redo',
			[
				'label'   => esc_html__( 'Show Description', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$repeater->add_control(
			'description',
			[
				'label' => __( 'Description', 'buddyboss-theme' ),
				'type' => Controls_Manager::WYSIWYG,
				'default' => '',
				'dynamic' => [
					'active' => true,
				],
				'conditions' => [
					'terms' => [
						[
							'name' => 'switch_description_redo',
							'value' => 'yes',
						],
					],
				],
			]
		);

		$repeater->add_control(
			'switch_tooltip_redo',
			[
				'label'   => esc_html__( 'Show Tooltip', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$repeater->add_control(
			'item_tooltip',
			[
				'label' => __( 'Tooltip', 'buddyboss-theme' ),
				'type' => Controls_Manager::TEXTAREA,
				'default' => '',
				'dynamic' => [
					'active' => true,
				],
				'conditions' => [
					'terms' => [
						[
							'name' => 'switch_tooltip_redo',
							'value' => 'yes',
						],
					],
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

		$this->add_control(
			'grid_list',
			[
				'label' => __( 'Grid Items', 'buddyboss-theme' ),
				'type' => Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
				'default' => [
					[
						'title' => __( 'First item on the list', 'buddyboss-theme' ),
						'description'  => __( 'Lorem ipsum dolor sit amet consectetur adipiscing elit dolor.', 'buddyboss-theme' ),
						'item_tooltip' => __( 'Lorem ipsum dolor sit amet consectetur adipiscing elit dolor', 'buddyboss-theme' ),
						//'link' => [ 'url' => '' ],
					],
					[
						'title' => __( 'Second item on the list', 'buddyboss-theme' ),
						'description'  => __( 'Lorem ipsum dolor sit amet consectetur adipiscing elit dolor.', 'buddyboss-theme' ),
						'item_tooltip' => __( 'Lorem ipsum dolor sit amet consectetur adipiscing elit dolor', 'buddyboss-theme' ),
						//'link' => [ 'url' => '' ],
					],
					[
						'title' => __( 'Third item on the list', 'buddyboss-theme' ),
						'description'  => __( 'Lorem ipsum dolor sit amet consectetur adipiscing elit dolor.', 'buddyboss-theme' ),
						'item_tooltip' => __( 'Lorem ipsum dolor sit amet consectetur adipiscing elit dolor', 'buddyboss-theme' ),
						//'link' => [ 'url' => '' ],
					],
				],
				'title_field' => '{{{ title }}}',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_content',
			[
				'label' => __( 'Content', 'buddyboss-theme' ),
				'tab' => Controls_Manager::TAB_CONTENT,
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
				'default' => 'above',
				'prefix_class' => 'elementor-cta-%s-dash-grid-',
			]
		);

		$this->add_control(
			'switch_ico',
			[
				'label'   => esc_html__( 'Show Icon Link', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->add_control(
			'switch_separator',
			[
				'label'   => esc_html__( 'Show Separator', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
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
				'selector'    => '{{WRAPPER}} .bb-dash-grid',
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
					'{{WRAPPER}} .bb-dash-grid' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'box_background',
				'label' => __( 'Background', 'buddyboss-theme' ),
				'types' => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .bb-dash-grid',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'box_shadow',
				'label'    => __( 'Box Shadow', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .bb-dash-grid',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_image',
			[
				'label'     => esc_html__( 'Image', 'buddyboss-theme' ),
				'tab'       => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'image_layout',
			[
				'label'   => __( 'Layout', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'square',
				'options' => [
					'square' => __( 'Square', 'buddyboss-theme' ),
					'rectangular' => __( 'Rectangular', 'buddyboss-theme' ),
				],
			]
		);

		$this->add_control(
			'image_size',
			[
				'label'     => __( 'Size', 'buddyboss-theme' ),
				'type'      => Controls_Manager::SLIDER,
				'default' => [
					'size' => 100,
				],
				'range' => [
					'px' => [
						'min'  => 20,
						'max'  => 200,
						'step' => 1,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .bb-dash-grid__image.square img' => 'max-width: {{SIZE}}px; width: {{SIZE}}px; height: {{SIZE}}px;',
					'{{WRAPPER}} .bb-dash-grid__image.rectangular img' => 'max-width: {{SIZE}}px; width: {{SIZE}}px;',
				],
				'condition' => [
					'image_layout' => 'square',
				],
			]
		);

		$this->add_control(
			'image_ratio',
			array(
				'label'      => __( 'Image Ratio', 'buddyboss-theme' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array(
					'%' => array(
						'min'  => 10,
						'max'  => 100,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => '%',
					'size' => 50,
				),
				'selectors'  => array(
					'{{WRAPPER}} .bb-dash-grid__block .bb-dash-grid__image.rectangular' => 'padding-top: {{SIZE}}{{UNIT}};',
				),
				'condition' => [
					'image_layout' => 'rectangular',
				],
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'        => 'image_border',
				'label'       => __( 'Border', 'buddyboss-theme' ),
				'placeholder' => '1px',
				'default'     => '1px',
				'selector'    => '{{WRAPPER}} .bb-dash-grid__image img',
				'separator'   => 'before',
			]
		);

		$this->add_control(
			'image_border_radius',
			[
				'label'      => __( 'Border Radius', 'buddyboss-theme' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ '%', 'px' ],
				'default' => [
					'top' => '0',
					'right' => '0',
					'bottom' => '0',
					'left' => '0',
				],
				'selectors'  => [
					'{{WRAPPER}} .bb-dash-grid__image img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'image_spacing',
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
					'{{WRAPPER}}.elementor-cta--dash-grid-above .bb-dash-grid__image'  => 'margin-bottom: {{SIZE}}{{UNIT}}',
					'{{WRAPPER}}.elementor-cta--dash-grid-left .bb-dash-grid__image'  => 'margin-right: {{SIZE}}{{UNIT}}',
					'{{WRAPPER}}.elementor-cta--dash-grid-right .bb-dash-grid__image'  => 'margin-left: {{SIZE}}{{UNIT}}',
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

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_title',
				'label'    => __( 'Typography Title', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .bb-dash-grid__title h2',
			)
		);

		$this->start_controls_tabs(
			'title_tabs'
		);

		$this->start_controls_tab(
			'title_normal_tab',
			array(
				'label' => __( 'Normal', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'title_item_color',
			array(
				'label'     => __( 'Title Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#122B46',
				'selectors' => array(
					'{{WRAPPER}} .bb-dash-grid__title h2' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'title_hover_tab',
			array(
				'label' => __( 'Hover', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'title_item_color_hover',
			array(
				'label'     => __( 'Title Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#007CFF',
				'selectors' => array(
					'{{WRAPPER}} .bb-dash-grid__link:hover .bb-dash-grid__title h2' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'title_spacing',
			[
				'label' => __( 'Title Spacing', 'buddyboss-theme' ),
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
					'{{WRAPPER}} .bb-dash-grid__title h2'  => 'margin-bottom: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'separator_info',
			array(
				'label'     => __( 'Description', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'info_color',
			array(
				'label'     => __( 'Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bb-dash-grid__info p' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_info',
				'label'    => __( 'Typography Description', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .bb-dash-grid__info p',
			)
		);

		$this->add_control(
			'info_spacing',
			[
				'label' => __( 'Description Spacing', 'buddyboss-theme' ),
				'type'  => Controls_Manager::SLIDER,
				'default' => [
					'size' => 15,
				],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .bb-dash-grid__info p'  => 'margin-bottom: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'separator_icon_link',
			array(
				'label'     => __( 'Icon Link', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'icon_link_color',
			array(
				'label'     => __( 'Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} a.bb-dash-grid__link' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'icon_link_border',
			array(
				'label'     => __( 'Border Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} a.bb-dash-grid__link i' => 'border-color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'icon_link_background',
			array(
				'label'     => __( 'Background Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} a.bb-dash-grid__link i' => 'background-color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'separator_tooltips',
			array(
				'label'     => __( 'Tooltips', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'tooltip_position',
			[
				'label'   => __( 'Tooltips Position', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'up',
				'options' => [
					'up' => __( 'Top', 'buddyboss-theme' ),
					'down' => __( 'Down', 'buddyboss-theme' ),
				],
			]
		);

		$this->add_control(
			'tooltips_padding',
			[
				'label'      => __( 'Padding', 'buddyboss-theme' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default' => [
					'top' => '15',
					'right' => '15',
					'bottom' => '15',
					'left' => '15',
				],
				'selectors'  => [
					'{{WRAPPER}} .bb-dash-grid__inner[data-balloon][data-balloon-pos="up"][data-balloon-visible]:after' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .bb-dash-grid__inner[data-balloon][data-balloon-pos="up"]:after' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'tooltips_radius',
			array(
				'label'      => __( 'Border Radius', 'buddyboss-theme' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 20,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 6,
				),
				'selectors'  => array(
					'{{WRAPPER}} .bb-dash-grid__inner[data-balloon][data-balloon-pos="up"][data-balloon-visible]:after' => 'border-radius: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .bb-dash-grid__inner[data-balloon][data-balloon-pos="up"]:after' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'tooltips_position',
			array(
				'label'      => __( 'Vertical Position', 'buddyboss-theme' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array(
					'%' => array(
						'min'  => 50,
						'max'  => 100,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => '%',
					'size' => 100,
				),
				'selectors'  => array(
					'{{WRAPPER}} .bb-dash-grid__inner[data-balloon][data-balloon-pos="up"][data-balloon-visible]:after' => 'bottom: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .bb-dash-grid__inner[data-balloon][data-balloon-pos="up"]:after' => 'bottom: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .bb-dash-grid__inner[data-balloon][data-balloon-pos="up"][data-balloon-visible]:before' => 'bottom: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .bb-dash-grid__inner[data-balloon][data-balloon-pos="up"]:before' => 'bottom: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .bb-dash-grid__inner[data-balloon][data-balloon-pos="down"][data-balloon-visible]:after' => 'top: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .bb-dash-grid__inner[data-balloon][data-balloon-pos="down"]:after' => 'top: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .bb-dash-grid__inner[data-balloon][data-balloon-pos="down"][data-balloon-visible]:before' => 'top: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .bb-dash-grid__inner[data-balloon][data-balloon-pos="down"]:before' => 'top: {{SIZE}}{{UNIT}};'
				),
			)
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
			$image_src = wp_get_attachment_image_src( $image_id, 'medium' );
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
		$grid_count = count( $settings['grid_list'] );
		?>

		<div class="bb-dash-grid">
			<div class="bb-dash-grid__frame bb-dash-grid__cols-<?php echo esc_attr( $grid_count ); ?> flex flex-wrap">

			<?php $this->add_render_attribute('bb-grid-tip', 'data-balloon-pos', $settings['tooltip_position'] ); ?>

			<?php $this->add_render_attribute('bb-grid-image', 'class', 'bb-dash-grid__image ' . $settings['image_layout'] ); ?>

			<?php foreach ( $settings['grid_list'] as $item ) : ?>
				
				<?php if ( ! empty( $item['title'] ) ) : ?>

					<div class="bb-dash-grid__block <?php if ( $settings['switch_separator'] ) { echo "bb-dash-grid__sep"; } ?>">

						<?php if ( ! empty( $item['link']['url'] ) ) : ?><a class="bb-dash-grid__link" href="<?php echo $item['link']['url']; ?>" target="<?php echo ( $item['link']['is_external'] == 'on' ) ? '_blank' : ''; ?>" rel="<?php echo ( $item['link']['nofollow'] == 'on' ) ? 'nofollow' : ''; ?>"><?php endif; ?>
						
							<div class="bb-dash-grid__inner <?php if ( $settings['switch_separator'] ) { echo "is-sep"; } ?>" <?php if ( ! empty( $item['item_tooltip'] ) && $item['switch_tooltip_redo'] ) { echo $this->get_render_attribute_string('bb-grid-tip') . 'data-balloon="' . $item['item_tooltip'] . '"'; } ?>>	

								<?php if ( ! empty( $item['image']['url'] ) ) : ?>
									<div <?php echo $this->get_render_attribute_string('bb-grid-image'); ?>>
										<?php echo $this->render_image( $item, $settings ); ?>
									</div>
								<?php endif; ?>
								<div class="bb-dash-grid__body">
									<div class="bb-dash-grid__title">
										<?php if ( ! empty( $item['title'] ) ) : ?>
											<h2><?php echo $item['title']; ?></h2>
										<?php endif; ?>
									</div>
									<?php if ( ! empty( $item['description'] ) && $item['switch_description_redo'] ) : ?>
										<div class="bb-dash-grid__info">
											<p><?php echo $item['description']; ?></p>
										</div>
									<?php endif; ?>
									<?php if ( $settings['switch_ico'] ) : ?>
										<span class="bb-dash-grid__ico"><i class="bb-icon-l bb-icon-angle-right"></i></span>
									<?php endif; ?>
								</div>

							</div>

						<?php if ( ! empty( $item['link']['url'] ) ) : ?></a><?php endif; ?>

					</div>

				<?php endif; ?>
			<?php endforeach; ?>
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
