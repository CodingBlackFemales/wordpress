<?php
namespace BBElementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Core\Schemes;
use Elementor\Group_Control_Border;
use Elementor\Modules\DynamicTags\Module as TagsModule;
use Elementor\Embed;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * @since 1.1.0
 */
class BB_Review extends Widget_Base {

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
		return 'bb-review';
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
		return __( 'Reviews', 'buddyboss-theme' );
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
		return 'eicon-rating';
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
			'section_content_layout',
			[
				'label'     => esc_html__( 'Layout', 'buddyboss-theme' ),
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
			'switch_rating',
			[
				'label'   => esc_html__( 'Show Rating', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->add_control(
			'switch_title',
			[
				'label'   => esc_html__( 'Show Heading', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->add_control(
			'switch_avatar',
			[
				'label'   => esc_html__( 'Show Avatar', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'no',
			]
		);

		$this->add_control(
			'switch_who',
			[
				'label'   => esc_html__( 'Show Name', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->add_control(
			'switch_who_title',
			[
				'label'   => esc_html__( 'Show Author Title', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->add_control(
			'switch_date',
			[
				'label'   => esc_html__( 'Show Date', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_content_media',
			[
				'label'     => esc_html__( 'Media', 'buddyboss-theme' ),
				'condition' => [
					'switch_media' => 'yes',
				],
			]
		);

		$this->add_control(
			'media_type',
			[
				'type' => Controls_Manager::CHOOSE,
				'label' => __( 'Type', 'buddyboss-theme' ),
				'default' => 'image',
				'options' => [
					'image' => [
						'title' => __( 'Image', 'buddyboss-theme' ),
						'icon' => 'eicon-image-bold',
					],
					'video' => [
						'title' => __( 'Video', 'buddyboss-theme' ),
						'icon' => 'eicon-video-camera',
					],
				],
				'toggle' => false,
			]
		);

		$this->add_control(
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

		$this->add_control(
			'video_type',
			[
				'label' => __( 'Source', 'buddyboss-theme' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'youtube',
				'options' => [
					'youtube' => __( 'YouTube', 'buddyboss-theme' ),
					'vimeo' => __( 'Vimeo', 'buddyboss-theme' ),
				],
				'condition' => [
					'media_type' => 'video',
				],
			]
		);

		$this->add_control(
			'youtube_url',
			[
				'label' => __( 'Link', 'buddyboss-theme' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
					'categories' => [
						TagsModule::POST_META_CATEGORY,
						TagsModule::URL_CATEGORY,
					],
				],
				'placeholder' => __( 'Enter your URL', 'buddyboss-theme' ) . ' (YouTube)',
				'default' => 'https://www.youtube.com/watch?v=XHOmBV4js_E',
				'label_block' => true,
				'condition' => [
					'media_type' => 'video',
					'video_type' => 'youtube',
				],
			]
		);

		$this->add_control(
			'vimeo_url',
			[
				'label' => __( 'Link', 'buddyboss-theme' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
					'categories' => [
						TagsModule::POST_META_CATEGORY,
						TagsModule::URL_CATEGORY,
					],
				],
				'placeholder' => __( 'Enter your URL', 'buddyboss-theme' ) . ' (Vimeo)',
				'default' => 'https://vimeo.com/235215203',
				'label_block' => true,
				'condition' => [
					'media_type' => 'video',
					'video_type' => 'vimeo',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_content_video_options',
			[
				'label'     => esc_html__( 'Video Options', 'buddyboss-theme' ),
				'condition' => [
					'media_type' => 'video',
				],
			]
		);

		$this->add_control(
			'autoplay',
			[
				'label' => __( 'Autoplay', 'buddyboss-theme' ),
				'type' => Controls_Manager::SWITCHER,
			]
		);

		$this->add_control(
			'mute',
			[
				'label' => __( 'Mute', 'buddyboss-theme' ),
				'type' => Controls_Manager::SWITCHER,
			]
		);

		$this->add_control(
			'loop',
			[
				'label' => __( 'Loop', 'buddyboss-theme' ),
				'type' => Controls_Manager::SWITCHER,
			]
		);

		$this->add_control(
			'controls',
			[
				'label' => __( 'Player Controls', 'buddyboss-theme' ),
				'type' => Controls_Manager::SWITCHER,
				'label_off' => __( 'Hide', 'buddyboss-theme' ),
				'label_on' => __( 'Show', 'buddyboss-theme' ),
				'default' => 'yes',
				'condition' => [
					'video_type!' => 'vimeo',
				],
			]
		);

		$this->add_control(
			'modestbranding',
			[
				'label' => __( 'Modest Branding', 'buddyboss-theme' ),
				'type' => Controls_Manager::SWITCHER,
				'condition' => [
					'video_type' => [ 'youtube' ],
					'controls' => 'yes',
				],
			]
		);

		$this->add_control(
			'color',
			[
				'label' => __( 'Controls Color', 'buddyboss-theme' ),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'condition' => [
					'video_type' => [ 'vimeo' ],
				],
			]
		);

		// YouTube.
		$this->add_control(
			'yt_privacy',
			[
				'label' => __( 'Privacy Mode', 'buddyboss-theme' ),
				'type' => Controls_Manager::SWITCHER,
				'description' => __( 'When you turn on privacy mode, YouTube won\'t store information about visitors on your website unless they play the video.', 'buddyboss-theme' ),
				'condition' => [
					'video_type' => 'youtube',
				],
			]
		);

		// Vimeo.
		$this->add_control(
			'vimeo_title',
			[
				'label' => __( 'Intro Title', 'buddyboss-theme' ),
				'type' => Controls_Manager::SWITCHER,
				'label_off' => __( 'Hide', 'buddyboss-theme' ),
				'label_on' => __( 'Show', 'buddyboss-theme' ),
				'default' => 'yes',
				'condition' => [
					'video_type' => 'vimeo',
				],
			]
		);

		$this->add_control(
			'vimeo_portrait',
			[
				'label' => __( 'Intro Portrait', 'buddyboss-theme' ),
				'type' => Controls_Manager::SWITCHER,
				'label_off' => __( 'Hide', 'buddyboss-theme' ),
				'label_on' => __( 'Show', 'buddyboss-theme' ),
				'default' => 'yes',
				'condition' => [
					'video_type' => 'vimeo',
				],
			]
		);

		$this->add_control(
			'vimeo_byline',
			[
				'label' => __( 'Intro Byline', 'buddyboss-theme' ),
				'type' => Controls_Manager::SWITCHER,
				'label_off' => __( 'Hide', 'buddyboss-theme' ),
				'label_on' => __( 'Show', 'buddyboss-theme' ),
				'default' => 'yes',
				'condition' => [
					'video_type' => 'vimeo',
				],
			]
		);

		$this->add_control(
			'play_text',
			[
				'label' => __( 'Play Button Text', 'buddyboss-theme' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'default' => 'Play',
				'placeholder' => __( 'Enter play button text', 'buddyboss-theme' ),
				'label_block' => true,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'play_color',
			array(
				'label'     => __( 'Play Button Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bb-review__image-overlay .media-ctrl' => 'background-color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_rating',
			[
				'label' => __( 'Rating', 'buddyboss-theme' ),
				'condition' => [
					'switch_rating' => 'yes',
				],
			]
		);

		$this->add_control(
			'scope',
			[
				'label' => __( 'Scope', 'buddyboss-theme' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'5' => '0-5',
					'10' => '0-10',
				],
				'default' => '5',
			]
		);

		$this->add_control(
			'rating',
			[
				'label' => __( 'Rating', 'buddyboss-theme' ),
				'type' => Controls_Manager::NUMBER,
				'min' => 0,
				'max' => 10,
				'step' => 0.1,
				'default' => 5,
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'star_style',
			[
				'label' => __( 'Star Style', 'buddyboss-theme' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'solid' => [
						'title' => __( 'Solid', 'buddyboss-theme' ),
						'icon' => 'eicon-star',
					],
					'outline' => [
						'title' => __( 'Outline', 'buddyboss-theme' ),
						'icon' => 'eicon-star-o',
					],
				],
				'default' => 'solid',
			]
		);

		$this->end_controls_section();
		
		$this->start_controls_section(
			'section_content_content',
			[
				'label'     => esc_html__( 'Content', 'buddyboss-theme' ),
			]
		);

		$this->add_control(
			'title',
			[
				'label' => __( 'Title', 'buddyboss-theme' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'default' => '',
				'placeholder' => __( 'Enter title text', 'buddyboss-theme' ),
				'label_block' => true,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'review',
			[
				'label' => __( 'Review', 'buddyboss-theme' ),
				'type' => Controls_Manager::TEXTAREA,
				'dynamic' => [
					'active' => true,
				],
				'default' => '',
				'placeholder' => __( 'Enter your review text', 'buddyboss-theme' ),
				'separator' => 'none',
				'rows' => 5,
				'label_block' => true,
			]
		);

		$this->add_control(
			'avatar',
			[
				'label' => __( 'Avatar', 'buddyboss-theme' ),
				'type' => Controls_Manager::MEDIA,
				'default' => [],
				'dynamic' => [
					'active' => true,
				],
				'condition' => [
					'switch_avatar' => 'yes',
				],
			]
		);

		$this->add_control(
			'who',
			[
				'label' => __( 'Name', 'buddyboss-theme' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'default' => '',
				'placeholder' => __( 'Enter author name', 'buddyboss-theme' ),
				'label_block' => true,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'who_title',
			[
				'label' => __( 'Title', 'buddyboss-theme' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'default' => '',
				'placeholder' => __( 'Enter author title', 'buddyboss-theme' ),
				'label_block' => true,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'date',
			[
				'label' => __( 'Date', 'buddyboss-theme' ),
				'type' => Controls_Manager::DATE_TIME,
				'default' => gmdate( 'Y-m-d H:i', strtotime( '+1 month' ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ),
				'picker_options' => [
					'enableTime' => false,
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_media',
			[
				'label'     => esc_html__( 'Media', 'buddyboss-theme' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'switch_media' => 'yes',
					'media_type' => 'image',
				],
			]
		);

		$this->add_control(
			'media_style',
			array(
				'label'   => __( 'Media Width', 'buddyboss-theme' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'default',
				'options' => array(
					'default'  => __( 'Default', 'buddyboss-theme' ),
					'square' => __( 'Square', 'buddyboss-theme' ),
				),
			)
		);

		$this->add_control(
			'media_size',
			array(
				'label'      => __( 'Media Size', 'buddyboss-theme' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array(
					'px' => array(
						'min'  => 10,
						'max'  => 100,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => '%',
					'size' => 100,
				),
				'selectors'  => array(
					'{{WRAPPER}} .bb-review__image' => 'max-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'media_spacing',
			array(
				'label'      => __( 'Media Spacing', 'buddyboss-theme' ),
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
					'size' => 15,
				),
				'selectors'  => array(
					'{{WRAPPER}} .bb-review__media' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'        => 'media_border',
				'label'       => __( 'Border', 'buddyboss-theme' ),
				'placeholder' => '1px',
				'default'     => '1px',
				'selector'    => '{{WRAPPER}} .bb-review__media img',
				'separator'   => 'before',
			]
		);

		$this->add_control(
			'media_border_radius',
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
					'{{WRAPPER}} .bb-review__media img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_rating',
			[
				'label'     => esc_html__( 'Rating', 'buddyboss-theme' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'switch_rating' => 'yes',
				],
			]
		);

		$this->add_control(
			'rate_bgr_color',
			array(
				'label'     => __( 'Background Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bb-star-rating > span' => 'background-color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'rate_fill_color',
			array(
				'label'     => __( 'Star Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bb-star-rating i:before' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'rate_blank_color',
			array(
				'label'     => __( 'Blank Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bb-star-rating' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'rate_padding',
			[
				'label'      => __( 'Padding', 'buddyboss-theme' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default' => [
					'top' => '4',
					'right' => '4',
					'bottom' => '4',
					'left' => '4',
				],
				'selectors'  => [
					'{{WRAPPER}} .bb-star-rating > span' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'rate_spacing',
			array(
				'label'      => __( 'Stars Spacing', 'buddyboss-theme' ),
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
					'size' => 3,
				),
				'selectors'  => array(
					'{{WRAPPER}} .bb-star-rating > span' => 'margin-right: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'rate_radius',
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
					'size' => 4,
				),
				'selectors'  => array(
					'{{WRAPPER}} .bb-star-rating > span' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
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
				'selector' => '{{WRAPPER}} .bb-review__title h3',
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => __( 'Title Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bb-review__title h3' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_excerpt',
				'label'    => __( 'Typography Excerpt', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .bb-review__excerpt',
			)
		);

		$this->add_control(
			'excerpt_color',
			array(
				'label'     => __( 'Excerpt Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bb-review__excerpt' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_footer',
			[
				'label'     => esc_html__( 'Footer', 'buddyboss-theme' ),
				'tab'       => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'footer_position',
			[
				'label' => __( 'Position', 'buddyboss-theme' ),
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
				'prefix_class' => 'elementor-cta-%s-footer-align-',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_name',
				'label'    => __( 'Typography Name', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .bb-review__who',
			)
		);

		$this->add_control(
			'name_color',
			array(
				'label'     => __( 'Name Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bb-review__who' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_author_title',
				'label'    => __( 'Typography Author Title', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .bb-review__who-title',
			)
		);

		$this->add_control(
			'author_title_color',
			array(
				'label'     => __( 'Author Title Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bb-review__who-title' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_date',
				'label'    => __( 'Typography Date', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .bb-review__when',
			)
		);

		$this->add_control(
			'date_color',
			array(
				'label'     => __( 'Date Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bb-review__when' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'avatar_size',
			array(
				'label'      => __( 'Avatar Size', 'buddyboss-theme' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 20,
						'max'  => 200,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 50,
				),
				'selectors'  => array(
					'{{WRAPPER}} .bb-review__avatar' => 'flex: 0 0 {{SIZE}}{{UNIT}}; max-width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .bb-review__avatar img' => 'width: {{SIZE}}{{UNIT}}; max-width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
				'condition' => [
					'switch_avatar' => 'yes',
				],
			)
		);

		$this->add_control(
			'avatar_spacing',
			array(
				'label'      => __( 'Avatar Spacing', 'buddyboss-theme' ),
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
					'{{WRAPPER}}.elementor-cta--footer-align-left .bb-review__avatar' => 'margin-right: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}}.elementor-cta--footer-align-right .bb-review__avatar' => 'margin-left: {{SIZE}}{{UNIT}};',
				),
				'condition' => [
					'switch_avatar' => 'yes',
				],
			)
		);

		$this->add_control(
			'avatar_border_radius',
			[
				'label'      => __( 'Avatar Border Radius', 'buddyboss-theme' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ '%', 'px' ],
				'default' => [
					'top' => '100',
					'right' => '100',
					'bottom' => '100',
					'left' => '100',
				],
				'selectors'  => [
					'{{WRAPPER}} .bb-review__avatar img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition' => [
					'switch_avatar' => 'yes',
				],
			]
		);

		$this->end_controls_section();

	}

	private function date_to_iso( $date ) {
		$time = strtotime( $date );

		return gmdate( 'F j, Y', $time );
	}

	protected function get_rating() {
		$settings = $this->get_settings_for_display();
		$scope = (int) $settings['scope'];
		$rating = (float) $settings['rating'] > $scope ? $scope : $settings['rating'];

		return [ $rating, $scope ];
	}

	protected function render_rating( $icon ) {
		$rating_data = $this->get_rating();
		$rating = (float) $rating_data[0];
		$floored_rating = floor( $rating );
		$stars_html = '';

		for ( $stars = 1.0; $stars <= $rating_data[1]; $stars++ ) {
			if ( $stars <= $floored_rating ) {
				$stars_html .= '<span><i class="bb-icon-f bb-icon-star">' . $icon . '</i></span>';
			} elseif ( $floored_rating + 1 === $stars && $rating !== $floored_rating ) {
				$stars_html .= '<span><i class="bb-star-' . ( $rating - $floored_rating ) * 10 . '">' . $icon . '</i></span>';
			} else {
				$stars_html .= '<span><i class="bb-star-blank">' . $icon . '</i></span>';
			}
		}

		return $stars_html;
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
		if ( $settings['switch_media'] ) {
			$settings_image_url = $settings['image']['url'];
			$settings_media_style = $settings['media_style'];
		}
		$settings_title = $settings['title'];
		$settings_review = $settings['review'];
		if ( $settings['switch_avatar'] ) {
			$settings_avatar_url = $settings['avatar']['url'];
		}
		$settings_who = $settings['who'];
		$settings_who_title = $settings['who_title'];
		$settings_date = $settings['date'];
		$date = $this->date_to_iso( $settings_date );

		$icon = '&#xe90b;';

		if ( $settings['media_type'] == 'video' ) {
			$settings_play_text = $settings['play_text'];
			$video_url = $settings[ $settings['video_type'] . '_url' ];
			$embed_params = $this->get_embed_params();
			$embed_options = $this->get_embed_options();
			$video_html = Embed::get_embed_html( $video_url, $embed_params, $embed_options );
		}

		if ( 'outline' === $settings['star_style'] ) {
			$icon = '&#xe8cd;';
		}

		$this->add_render_attribute( 'icon_cover', [
			'class' => 'bb-star-rating',
			'itemscope' => '',
			'itemprop' => 'reviewRating',
		] );

		$stars = '<div ' . $this->get_render_attribute_string( 'icon_cover' ) . '>' . $this->render_rating( $icon ) . '</div>';
		?>

		<div class="bb-review">

			<?php if ( ! empty( $settings_image_url ) && $settings['switch_media'] && $settings['media_type'] == 'image' ) : ?>
				<div class="bb-review__media media-<?php echo $settings_media_style; ?>">
					<div class="bb-review__image">
						<div class="media-container"><img src="<?php echo $settings_image_url; ?>" /></div>
					</div>
				</div>
			<?php elseif ( $settings['switch_media'] && $settings['media_type'] == 'video' ) : ?>
				<div class="bb-review__media media-video">
					<?php if ( ! empty( $settings_image_url ) ) : ?>
						<div class="bb-review__image-overlay" style="background-image: url(<?php echo $settings_image_url; ?>);">
							<div class="media-ctrl">
								<i class="bb-icon-rf bb-icon-play"></i>
								<?php if ( ! empty( $settings_play_text ) ) { echo $settings_play_text; } ?>
							</div>
						</div>
					<?php endif; ?>
					<div class="bb-review__video fluid-width-video-wrapper">
						<?php echo $video_html; ?>
					</div>
				</div>
			<?php endif; ?>
			
			<?php if ( $settings['switch_rating'] ) : ?><div class="bb-review__rate"><?php echo $stars; ?></div><?php endif; ?>

			<div class="bb-review__body">
				<?php if ( $settings['switch_title'] ) : ?><div class="bb-review__title"><h3><?php echo $settings_title; ?></h3></div><?php endif; ?>
				<div class="bb-review__excerpt"><?php echo $settings_review; ?></div>
			</div>

			<div class="bb-review__footer flex align-items-center">
				<?php if ( ! empty( $settings_avatar_url ) && $settings['switch_avatar'] ) : ?>
					<div class="bb-review__avatar"><img src="<?php echo $settings_avatar_url; ?>" alt="<?php echo $settings_who; ?>" /></div>
				<?php endif; ?>
				<div class="bb-review__ww">
					<?php if ( ! empty( $settings_who ) && $settings['switch_who'] ) : ?>
						<div class="bb-review__who"><?php echo $settings_who; ?></div>
					<?php endif; ?>
					<?php if ( ! empty( $settings_who_title ) && $settings['switch_who_title'] ) : ?>
						<div class="bb-review__who-title"><?php echo $settings_who_title; ?></div>
					<?php endif; ?>
					<?php if ( ! empty( $settings_date ) && $settings['switch_date'] ) : ?>
						<div class="bb-review__when"><?php echo $date; ?></div>
					<?php endif; ?>
				</div>
			</div>

		</div>

		<?php

	}

	/**
	 * Get embed params.
	 *
	 * Retrieve video widget embed parameters.
	 * @return array Video embed parameters.
	 */
	public function get_embed_params() {
		$settings = $this->get_settings_for_display();

		$params = [];

		if ( $settings['autoplay'] ) {
			$params['autoplay'] = '1';
		}

		$params_dictionary = [];

		if ( 'youtube' === $settings['video_type'] ) {
			$params_dictionary = [
				'loop',
				'controls',
				'mute',
				'modestbranding',
			];

			if ( $settings['loop'] ) {
				$video_properties = Embed::get_video_properties( $settings['youtube_url'] );

				$params['playlist'] = $video_properties['video_id'];
			}

			$params['wmode'] = 'opaque';
		} elseif ( 'vimeo' === $settings['video_type'] ) {
			$params_dictionary = [
				'loop',
				'mute' => 'muted',
				'vimeo_title' => 'title',
				'vimeo_portrait' => 'portrait',
				'vimeo_byline' => 'byline',
			];

			$params['color'] = str_replace( '#', '', $settings['color'] );

			$params['autopause'] = '0';
		}

		foreach ( $params_dictionary as $key => $param_name ) {
			$setting_name = $param_name;

			if ( is_string( $key ) ) {
				$setting_name = $key;
			}

			$setting_value = $settings[ $setting_name ] ? '1' : '0';

			$params[ $param_name ] = $setting_value;
		}

		return $params;
	}

	/**
	 * Get embed options
	 * @access private
	 */
	private function get_embed_options() {
		$settings = $this->get_settings_for_display();

		$embed_options = [];

		if ( 'youtube' === $settings['video_type'] ) {
			$embed_options['privacy'] = $settings['yt_privacy'];
		}

		return $embed_options;
	}
}
