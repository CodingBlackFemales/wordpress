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
class BBP_Forums extends Widget_Base {

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
		return 'bbp-forums';
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
		return __( 'Forums', 'buddyboss-theme' );
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
		return 'eicon-post-list';
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
			'forums_count',
			[
				'label'   => esc_html__( 'Forums Count', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SLIDER,
				'default' => [
					'size' => 5,
				],
				'range' => [
					'px' => [
						'min'  => 1,
						'max'  => 20,
						'step' => 1,
					],
				],
			]
		);

		$this->add_control(
			'row_space',
			[
				'label'   => esc_html__( 'Row Space', 'buddyboss-theme' ),
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
					'{{WRAPPER}} .bb-forums__list > li' => 'margin-bottom: {{SIZE}}px;padding-bottom: {{SIZE}}px',
				],
			]
		);

		$this->add_control(
			'switch_more',
			[
				'label'   => esc_html__( 'Show All Forums Link', 'buddyboss-theme' ),
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

		$this->add_control(
			'switch_meta_replies',
			[
				'label'   => esc_html__( 'Show Meta Replies', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
				'condition' => [
					'switch_meta' => 'yes',
				],
			]
		);

		$this->add_control(
			'switch_last_reply',
			[
				'label'   => esc_html__( 'Show Last Reply', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
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

		$this->add_control(
			'heading_text',
			[
				'label' => __( 'Heading Text', 'buddyboss-theme' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'default' => __( 'Forums', 'buddyboss-theme' ),
				'placeholder' => __( 'Enter heading text', 'buddyboss-theme' ),
				'label_block' => true
			]
		);

		$this->add_control(
			'forum_link_text',
			[
				'label' => __( 'Forum Link Text', 'buddyboss-theme' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'default' => __( 'All Forums', 'buddyboss-theme' ),
				'placeholder' => __( 'Enter forum link text', 'buddyboss-theme' ),
				'label_block' => true,
				'condition' => [
					'switch_more' => 'yes',
				]
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
				'selector'    => '{{WRAPPER}} .bb-forums',
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
					'{{WRAPPER}} .bb-forums' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'background_color',
				'label' => __( 'Background', 'buddyboss-theme' ),
				'types' => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .bb-forums',
			]
		);

		$this->add_control(
			'separator_all',
			[
				'label'     => __( 'All Forums Link', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'extra_color',
			[
				'label'     => __( 'All Forums Link Color', 'buddyboss-theme' ),
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
			'avatar_size',
			[
				'label'     => __( 'Size', 'buddyboss-theme' ),
				'type'      => Controls_Manager::SLIDER,
				'default' => [
					'size' => 52,
				],
				'range' => [
					'px' => [
						'min'  => 20,
						'max'  => 100,
						'step' => 1,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .bb-forums__avatar' => 'flex: 0 0 {{SIZE}}px;',
					'{{WRAPPER}} .bb-forums__avatar img.avatar' => 'width: {{SIZE}}px;',
					'{{WRAPPER}} .bb-forums__avatar img.avatar' => 'max-width: {{SIZE}}px;',
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
				'selector'    => '{{WRAPPER}} .bb-forums__avatar img.avatar',
				'separator'   => 'before',
			]
		);

		$this->add_control(
			'avatar_border_radius',
			[
				'label'      => __( 'Border Radius', 'buddyboss-theme' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .bb-forums__avatar img.avatar' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden;',
				],
			]
		);

		$this->add_control(
			'avatar_opacity',
			[
				'label'   => __( 'Opacity (%)', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SLIDER,
				'default' => [
					'size' => 1,
				],
				'range' => [
					'px' => [
						'max'  => 1,
						'min'  => 0.10,
						'step' => 0.01,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .bb-forums__avatar img.avatar' => 'opacity: {{SIZE}};',
				],
			]
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
					'{{WRAPPER}} .bb-forums__avatar'  => 'margin-right: {{SIZE}}{{UNIT}}',
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

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_title',
				'label'    => __( 'Typography Title', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .item-title a',
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
				'label'     => __( 'Title/Links Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#122B46',
				'selectors' => array(
					'{{WRAPPER}} .item-title a' => 'color: {{VALUE}}',
					'{{WRAPPER}} .bb-forums__ww .bs-replied a' => 'color: {{VALUE}}',
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
				'label'     => __( 'Title/Links Color', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#007CFF',
				'selectors' => array(
					'{{WRAPPER}} .item-title a:hover' => 'color: {{VALUE}}',
					'{{WRAPPER}} .bb-forums__ww .bs-replied a:hover' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_content',
				'label'    => __( 'Typography Content', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .item-meta > div,{{WRAPPER}} .bb-forums__item .bb-forums__ww .bs-replied > a.bbp-author-link span',
			)
		);

		$this->add_control(
			'meta_color',
			[
				'label'     => __( 'Meta Data Color', 'buddyboss-theme' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#A3A5A9',
				'selectors' => [
					'{{WRAPPER}} .item-meta' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'reply_color',
			[
				'label'     => __( 'Last Reply Color', 'buddyboss-theme' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bs-last-reply' => 'color: {{VALUE}};',
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

		$settings = $this->get_settings_for_display();

		$args = array(
			'order' => 'DESC', 
			'posts_per_page'        => esc_attr($settings['forums_count']['size']),
			'max_num_pages'   => esc_attr($settings['forums_count']['size']),
		); ?>

		<div class="bb-forums <?php echo ( !bbp_has_topics( $args ) ) ? 'bb-forums--blank' : ''; ?>">

			<?php if ( bbp_has_topics( $args ) ) : ?>

				<div class="bb-block-header flex align-items-center">
					<div class="bb-block-header__title"><h3><?php echo esc_html( $settings['heading_text'] ); ?></h3></div>
					<?php if ($settings['switch_more']) : ?>
						<div class="bb-block-header__extra push-right">
						<?php if( '' != $settings['forum_link_text'] ) { ?>
							<a href="<?php echo home_url(bbp_get_root_slug()); ?>" class="count-more"><?php echo esc_html( $settings['forum_link_text'] ); ?><i class="bb-icon-l bb-icon-angle-right"></i></a>
						<?php } ?>
						</div>
					<?php endif; ?>
				</div>

				<?php do_action( 'bbp_template_before_topics_loop' ); ?>

				<div class="bbel-list-flow">
					<ul class="bb-forums__list bbp-topics1 bs-item-list bs-forums-items list-view">

						<?php while ( bbp_topics() ) : bbp_the_topic(); ?>

							<li>
								<?php $class = bbp_is_topic_open() ? '' : 'closed'; ?>
								<div class="bb-forums__item">
									
									<div class="flex">
										<?php if ($settings['switch_avatar']) : ?>
											<div class="bb-forums__avatar">
												<?php echo bbp_get_topic_author_link( array( 'size' => '180' ) ); ?>
											</div>
										<?php endif; ?>

										<div class="item">
											<div class="item-title">
												<a class="bbp-topic-permalink" href="<?php bbp_topic_permalink(); ?>"><?php bbp_topic_title(); ?></a>
											</div>
											
											<div class="item-meta bb-reply-meta">
												<?php if ($settings['switch_meta']) : ?>
													<div class="bb-forums__ww">
														<span class="bs-replied">
															<?php
															$bbp_author_link = str_replace('&nbsp;', '', bbp_author_link( array( 'post_id' => bbp_get_topic_last_active_id(), 'size' => 1 ) ));
															?>
															<span class="bbp-topic-freshness-author"><?php //echo $bbp_author_link; ?></span> <?php _e('replied', 'buddyboss-theme'); ?> <?php bbp_topic_freshness_link(); ?>
														</span>
														<?php if ($settings['switch_meta_replies']) : ?>
															<span class="bs-voices-wrap">
																<?php
																	$voice_count = bbp_get_topic_voice_count( bbp_get_topic_id() );
																	$voice_text = $voice_count > 1 ? __('Members', 'buddyboss-theme') : __('Member', 'buddyboss-theme');

																	$topic_reply_count = bbp_get_topic_reply_count( bbp_get_topic_id() );
																	$topic_post_count = bbp_get_topic_post_count( bbp_get_topic_id() );
																	$topic_reply_text = '';
																?>
																<span class="bs-voices"><?php bbp_topic_voice_count(); ?> <?php echo $voice_text; ?></span>
																<span class="bs-separator">&middot;</span>
																<span class="bs-replies"><?php 
																		bbp_topic_reply_count();
																		$topic_reply_text = 1 !== (int) $topic_reply_count ? esc_html__( ' Replies', 'buddyboss-theme' ) : esc_html__( ' Reply', 'buddyboss-theme' );
																		echo esc_html( $topic_reply_text );
																	?>
																</span>
															<?php endif; ?>
														</span>
													</div>
												<?php endif; ?>
												<?php if ($settings['switch_last_reply']) : ?>
												<div class="bb-forums__last-reply">
													<?php
													$get_last_reply_id = bbp_get_topic_last_reply_id( bbp_get_topic_id() );
													?>
													<span class="bs-last-reply <?php echo (!empty(bbp_get_reply_excerpt( $get_last_reply_id ))) ? '' : 'is-empty'; ?>">
														<?php
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
														?>
														<?php echo bbp_get_reply_excerpt( $get_last_reply_id, 90 ); ?>
													</span>
												</div>
												<?php endif; ?>
											</div>
										</div>
									</div>

								</div>
							</li>

						<?php endwhile; ?>

					</ul><!-- #bbp-forum-<?php bbp_forum_id(); ?> -->
				</div>

				<?php do_action( 'bbp_template_after_topics_loop' ); ?>

			<?php else : ?>

				<div class="bb-no-data bb-no-data--forums">
					<img class="bb-no-data__image" src="<?php echo get_template_directory_uri(); ?>/assets/images/svg/dfy-no-data-icon02.svg" alt="Forums" />
					<?php bbp_get_template_part( 'feedback', 'no-topics' ); ?>
					<a href="<?php echo home_url(bbp_get_root_slug()); ?>" class="bb-no-data__link"><?php _e( 'Start a Discussion', 'buddyboss-theme' ); ?></a>
				</div>

			<?php endif; ?>

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
