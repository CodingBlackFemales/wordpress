<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! defined( 'BB_LMS_WIDGET' ) ) exit; // Exit if accessed outside widget

$this->start_controls_section(
    'section_style_courses',
    array(
        'label' => __( 'Courses', 'buddyboss-theme' ),
        'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
    )
);

$this->add_control(
    'columns_num',
    array(
        'label'   => __( 'Columns', 'buddyboss-theme' ),
        'type'    => \Elementor\Controls_Manager::SELECT,
        'default' => 'default',
        'options' => array(
            'default'  => __( 'Default', 'buddyboss-theme' ),
            '1' => __( '1', 'buddyboss-theme' ),
            '2' => __( '2', 'buddyboss-theme' ),
            '3' => __( '3', 'buddyboss-theme' ),
            '4' => __( '4', 'buddyboss-theme' ),
        ),
        'condition' => [
            'skin_style' => 'classic',
        ],
    )
);

$this->add_control(
    'switch_media',
    [
        'label'   => esc_html__( 'Show Media', 'buddyboss-theme' ),
        'type'    => \Elementor\Controls_Manager::SWITCHER,
        'default' => 'yes',
    ]
);

$this->add_responsive_control(
    'content_v_position',
    [
        'label' => __( 'Content Position', 'buddyboss-theme' ),
        'type' => \Elementor\Controls_Manager::CHOOSE,
        'label_block' => false,
        'options' => [
            'top' => [
                'title' => __( 'Top', 'buddyboss-theme' ),
                'icon' => 'eicon-v-align-top',
            ],
            'bottom' => [
                'title' => __( 'Bottom', 'buddyboss-theme' ),
                'icon' => 'eicon-v-align-bottom',
            ],
        ],
        'default' => 'bottom',
        'prefix_class' => 'elementor-cta-%s-content-v-align-',
        'condition' => [
            'skin_style' => 'cover',
        ],
    ]
);

$this->add_responsive_control(
    'avatar_v_position',
    [
        'label' => __( 'Footer Position', 'buddyboss-theme' ),
        'type' => \Elementor\Controls_Manager::CHOOSE,
        'label_block' => false,
        'options' => [
            'top' => [
                'title' => __( 'Top', 'buddyboss-theme' ),
                'icon' => 'eicon-v-align-top',
            ],
            'bottom' => [
                'title' => __( 'Bottom', 'buddyboss-theme' ),
                'icon' => 'eicon-v-align-bottom',
            ],
        ],
        'default' => 'top',
        'prefix_class' => 'elementor-cta-%s-footer-v-align-',
        'condition' => [
            'skin_style' => 'cover',
            'content_v_position' => 'top',
        ],
    ]
);

$this->add_control(
    'image_ratio',
    array(
        'label'      => __( 'Image Ratio', 'buddyboss-theme' ),
        'type'       => \Elementor\Controls_Manager::SLIDER,
        'size_units' => array( '%' ),
        'range'      => array(
            '%' => array(
                'min'  => 20,
                'max'  => 100,
                'step' => 1,
            ),
        ),
        'default'    => array(
            'unit' => '%',
            'size' => 52,
        ),
        'selectors'  => array(
            '{{WRAPPER}} .bb-course-items .bb-cover-wrap' => 'padding-top: {{SIZE}}{{UNIT}};',
			'{{WRAPPER}} .bb-course-cover--videoTile .fluid-width-video-wrapper' => 'padding-top: {{SIZE}}{{UNIT}} !important;',
        ),
        'condition' => [
            'skin_style' => 'classic',
        ],
    )
);

$this->add_control(
    'switch_status',
    [
        'label'   => esc_html__( 'Show Status', 'buddyboss-theme' ),
        'type'    => \Elementor\Controls_Manager::SWITCHER,
        'default' => 'yes',
        'condition' => [
            'switch_media' => 'yes',
            'skin_style' => 'classic',
        ],
    ]
);

$this->add_control(
    'separator_style_progress',
    array(
        'label'     => __( 'Progress', 'buddyboss-theme' ),
        'type'      => \Elementor\Controls_Manager::HEADING,
        'separator' => 'before',
    )
);

$this->add_control(
    'switch_progress',
    [
        'label'   => esc_html__( 'Show Progress', 'buddyboss-theme' ),
        'type'    => \Elementor\Controls_Manager::SWITCHER,
        'default' => 'yes',
    ]
);

$this->add_control(
    'course_progress_bgr',
    array(
        'label'     => __( 'Background', 'buddyboss-theme' ),
        'type'      => \Elementor\Controls_Manager::COLOR,
        'selectors' => array(
            '{{WRAPPER}} .llms-progress .llms-progress-bar' => 'background-color: {{VALUE}}',
        ),
        'condition' => [
            'switch_progress' => 'yes',
        ],
    )
);

$this->add_control(
    'course_progress_color',
    array(
        'label'     => __( 'Active Color', 'buddyboss-theme' ),
        'type'      => \Elementor\Controls_Manager::COLOR,
        'selectors' => array(
            '{{WRAPPER}} .llms-progress .progress-bar-complete' => 'background-color: {{VALUE}} !important',
        ),
        'condition' => [
            'switch_progress' => 'yes',
        ],
    )
);

$this->add_control(
    'course_progress_text_color',
    array(
        'label'     => __( 'Color', 'buddyboss-theme' ),
        'type'      => \Elementor\Controls_Manager::COLOR,
        'selectors' => array(
            '{{WRAPPER}} .llms-progress .progress__indicator' => 'color: {{VALUE}}',
        ),
        'condition' => [
            'switch_progress' => 'yes',
        ],
    )
);

$this->add_control(
    'course_progress_size',
    array(
        'label'      => __( 'Height', 'buddyboss-theme' ),
        'type'       => \Elementor\Controls_Manager::SLIDER,
        'size_units' => array( 'px' ),
        'range'      => array(
            'px' => array(
                'min'  => 1,
                'max'  => 20,
                'step' => 1,
            ),
        ),
        'default'    => array(
            'unit' => 'px',
            'size' => 4,
        ),
        'selectors'  => array(
            '{{WRAPPER}} .llms-progress .llms-progress-bar' => 'height: {{SIZE}}{{UNIT}};',
            '{{WRAPPER}} .llms-progress .progress-bar-complete' => 'height: {{SIZE}}{{UNIT}};',
        ),
        'condition' => [
            'switch_progress' => 'yes',
        ],
    )
);

$this->add_group_control(
    \Elementor\Group_Control_Typography::get_type(),
    array(
        'name'     => 'typography_progress',
        'label'    => __( 'Typography', 'buddyboss-theme' ),
        'selector' => '{{WRAPPER}} .llms-progress .progress__indicator',
        'condition' => [
            'switch_progress' => 'yes',
        ],
    )
);

$this->add_control(
    'separator_style_title',
    array(
        'label'     => __( 'Title', 'buddyboss-theme' ),
        'type'      => \Elementor\Controls_Manager::HEADING,
        'separator' => 'before',
    )
);

$this->add_control(
    'course_title_color',
    array(
        'label'     => __( 'Color', 'buddyboss-theme' ),
        'type'      => \Elementor\Controls_Manager::COLOR,
        'selectors' => array(
            '{{WRAPPER}} .bb-course-title a' => 'color: {{VALUE}} !important',
        ),
    )
);

$this->add_group_control(
    \Elementor\Group_Control_Typography::get_type(),
    array(
        'name'     => 'typography_course_title',
        'label'    => __( 'Typography', 'buddyboss-theme' ),
        'selector' => '{{WRAPPER}} .bb-course-title a',
    )
);

$this->add_control(
    'course_title_space',
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
            'size' => 8,
        ),
        'selectors'  => array(
            '{{WRAPPER}} .bb-course-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
            '#page {{WRAPPER}} .bb-course-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
        ),
    )
);

$this->add_control(
    'separator_footer',
    array(
        'label'     => __( 'Footer', 'buddyboss-theme' ),
        'type'      => \Elementor\Controls_Manager::HEADING,
        'separator' => 'before',
    )
);

$this->add_control(
    'switch_price',
    [
        'label'   => esc_html__( 'Show Price', 'buddyboss-theme' ),
        'type'    => \Elementor\Controls_Manager::SWITCHER,
        'default' => 'yes',
    ]
);

$this->add_control(
    'price_color',
    array(
        'label'     => __( 'Price Color', 'buddyboss-theme' ),
        'type'      => \Elementor\Controls_Manager::COLOR,
        'selectors' => array(
            '{{WRAPPER}} .lifterlms-price' => 'color: {{VALUE}}',
        ),
        'condition' => [
            'switch_price' => 'yes',
        ],
    )
);

$this->add_control(
    'free_color',
    array(
        'label'     => __( 'Free Label Color', 'buddyboss-theme' ),
        'type'      => \Elementor\Controls_Manager::COLOR,
        'selectors' => array(
            '{{WRAPPER}} .llms-meta-aplans--hasFree .lifterlms-price' => 'color: {{VALUE}}',
        ),
        'condition' => [
            'switch_price' => 'yes',
        ],
    )
);

$this->add_group_control(
    \Elementor\Group_Control_Typography::get_type(),
    array(
        'name'     => 'typography_price',
        'label'    => __( 'Price Typography', 'buddyboss-theme' ),
        'selector' => '{{WRAPPER}} .lifterlms-price',
        'condition' => [
            'switch_price' => 'yes',
        ],
    )
);

$this->add_control(
    'switch_enroll',
    [
        'label'   => esc_html__( 'Show Enroll Button', 'buddyboss-theme' ),
        'type'    => \Elementor\Controls_Manager::SWITCHER,
        'default' => 'yes',
    ]
);

$this->add_control(
    'enroll_color',
    array(
        'label'     => __( 'Enroll Button Color', 'buddyboss-theme' ),
        'type'      => \Elementor\Controls_Manager::COLOR,
        'selectors' => array(
            '{{WRAPPER}} .llms-meta-aplans__btn a' => 'color: {{VALUE}}',
        ),
        'condition' => [
            'switch_enroll' => 'yes',
        ],
    )
);

$this->add_group_control(
    \Elementor\Group_Control_Typography::get_type(),
    array(
        'name'     => 'typography_enroll',
        'label'    => __( 'Enroll Button Typography', 'buddyboss-theme' ),
        'selector' => '{{WRAPPER}} .llms-meta-aplans__btn a',
        'condition' => [
            'switch_enroll' => 'yes',
        ],
    )
);

$this->add_control(
    'switch_time',
    [
        'label'   => esc_html__( 'Show Timestamps', 'buddyboss-theme' ),
        'type'    => \Elementor\Controls_Manager::SWITCHER,
        'default' => 'yes',
    ]
);

$this->add_control(
    'meta_tag_color',
    array(
        'label'     => __( 'Meta Tags Color', 'buddyboss-theme' ),
        'type'      => \Elementor\Controls_Manager::COLOR,
        'selectors' => array(
            '{{WRAPPER}} .llms-meta-aplans__smTag' => 'color: {{VALUE}}',
        ),
        'condition' => [
            'switch_time' => 'yes',
        ],
    )
);

$this->add_group_control(
    \Elementor\Group_Control_Typography::get_type(),
    array(
        'name'     => 'meta_tag_typography',
        'label'    => __( 'Meta Tags Typography', 'buddyboss-theme' ),
        'selector' => '{{WRAPPER}} .llms-meta-aplans__smTag',
        'condition' => [
            'switch_time' => 'yes',
        ],
    )
);

$this->add_control(
    'meta_data_color',
    array(
        'label'     => __( 'Time Color', 'buddyboss-theme' ),
        'type'      => \Elementor\Controls_Manager::COLOR,
        'selectors' => array(
            '{{WRAPPER}} .llms-meta-aplans__inDate' => 'color: {{VALUE}}',
        ),
        'condition' => [
            'switch_time' => 'yes',
        ],
    )
);

$this->add_group_control(
    \Elementor\Group_Control_Typography::get_type(),
    array(
        'name'     => 'meta_data_typography',
        'label'    => __( 'Time Typography', 'buddyboss-theme' ),
        'selector' => '{{WRAPPER}} .llms-meta-aplans__inDate',
        'condition' => [
            'switch_time' => 'yes',
        ],
    )
);

$this->end_controls_section();

$this->start_controls_section(
    'section_style_box',
    array(
        'label' => __( 'Box', 'buddyboss-theme' ),
        'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
    )
);

$this->add_control(
    'box_ratio',
    array(
        'label'      => __( 'Box Size', 'buddyboss-theme' ),
        'type'       => \Elementor\Controls_Manager::SLIDER,
        'size_units' => array( 'px' ),
        'range'      => array(
            'px' => array(
                'min'  => 150,
                'max'  => 1000,
                'step' => 1,
            ),
        ),
        'default'    => array(
            'unit' => 'px',
            'size' => 250,
        ),
        'selectors'  => array(
            '{{WRAPPER}} .lifterlms-course-list--cover .bb-course-items.grid-view .bb-course-item-wrap' => 'height: {{SIZE}}{{UNIT}};',
        ),
        'condition' => [
            'skin_style' => 'cover',
        ],
    )
);

$this->add_control(
    'box_padding',
    [
        'label'      => __( 'Padding', 'buddyboss-theme' ),
        'type'       => \Elementor\Controls_Manager::DIMENSIONS,
        'size_units' => [ 'px', '%' ],
        'default' => [
            'top' => '20',
            'right' => '20',
            'bottom' => '20',
            'left' => '20',
        ],
        'selectors'  => [
            '{{WRAPPER}} .lifterlms-course-list--cover .bb-card-course-details' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
        ],
        'condition' => [
            'skin_style' => 'cover',
        ],
    ]
);

$this->add_control(
    'box_background',
    array(
        'label'     => __( 'Background Color', 'buddyboss-theme' ),
        'type'      => \Elementor\Controls_Manager::COLOR,
        'selectors' => array(
            '{{WRAPPER}} .bb-course-items .bb-cover-list-item' => 'background-color: {{VALUE}}',
        ),
        'condition' => [
            'skin_style' => 'classic',
        ],
    )
);

$this->add_group_control(
    \Elementor\Group_Control_Background::get_type(),
    [
        'name' => 'box_media_background',
        'label' => __( 'Media Background', 'buddyboss-theme' ),
        'types' => [ 'classic', 'gradient' ],
        'selector' => '{{WRAPPER}} .bb-cover-wrap',
    ]
);

$this->add_control(
    'box_overlay_background',
    array(
        'label'     => __( 'Overlay Background Color', 'buddyboss-theme' ),
        'type'      => \Elementor\Controls_Manager::COLOR,
        'selectors' => array(
            '{{WRAPPER}} .lifterlms-course-list--cover .bb-course-items .bb-cover-wrap:after' => 'background-color: {{VALUE}}',
        ),
        'condition' => [
            'skin_style' => 'cover',
        ],
    )
);

$this->add_group_control(
    \Elementor\Group_Control_Border::get_type(),
    [
        'name'        => 'box_border',
        'label'       => __( 'Border', 'buddyboss-theme' ),
        'placeholder' => '1px',
        'default'     => '1px',
        'selector'    => '{{WRAPPER}} .bb-course-items .bb-cover-list-item',
    ]
);

$this->add_control(
    'box_border_radius',
    [
        'label'      => __( 'Border Radius', 'buddyboss-theme' ),
        'type'       => \Elementor\Controls_Manager::DIMENSIONS,
        'size_units' => [ 'px', '%' ],
        'default' => [
            'top' => '4',
            'right' => '4',
            'bottom' => '4',
            'left' => '4',
        ],
        'selectors'  => [
            '{{WRAPPER}} .bb-course-items .bb-cover-list-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			'{{WRAPPER}} .bb-cover-wrap--llms' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} 0 0;',
			'{{WRAPPER}} .bb-course-items.list-view .bb-course-item-wrap .bb-cover-list-item .bb-course-cover .bb-cover-wrap' => 'border-radius: {{TOP}}{{UNIT}} 0 0 {{LEFT}}{{UNIT}};',
			'{{WRAPPER}} .bb-course-items .bb-course-cover--videoTile' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} 0 0;',
			'{{WRAPPER}} .bb-course-items.list-view .bb-course-cover--videoTile' => 'border-radius: {{TOP}}{{UNIT}} 0 0 {{LEFT}}{{UNIT}};',
        ],
    ]
);

$this->add_group_control(
    \Elementor\Group_Control_Box_Shadow::get_type(),
    array(
        'name'     => 'box_border_shadow',
        'label'    => __( 'Box Shadow', 'buddyboss-theme' ),
        'selector' => '{{WRAPPER}} .bb-course-items .bb-cover-list-item',
    )
);

$this->end_controls_section();

?>