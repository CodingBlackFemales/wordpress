<?php
namespace BBElementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Core\Schemes;
use Elementor\Group_Control_Border;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * @since 1.1.0
 */
class BBP_Activity extends Widget_Base {

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
		return 'bbp-activity';
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
		return __( 'Activity', 'buddyboss-theme' );
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
	 * Retrieve the widget icon.
	 *
	 * @since 1.1.0
	 *
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-time-line';
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
		return array( 'buddyboss-elements' );
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
			array(
				'label' => esc_html__( 'Layout', 'buddyboss-theme' ),
			)
		);

		$this->add_control(
			'activity_count',
			array(
				'label'   => esc_html__( 'Activity Count', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SLIDER,
				'default' => array(
					'size' => 5,
				),
				'range'   => array(
					'px' => array(
						'min'  => 1,
						'max'  => 20,
						'step' => 1,
					),
				),
			)
		);

		$this->add_control(
			'row_space',
			array(
				'label'     => esc_html__( 'Row Space', 'buddyboss-theme' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => array(
					'size' => 15,
				),
				'range'     => array(
					'px' => array(
						'min'  => 0,
						'max'  => 50,
						'step' => 1,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} #buddypress .activity-lists.bp-list .activity-item' => 'margin-bottom: {{SIZE}}px; padding-bottom: {{SIZE}}px',
					//'{{WRAPPER}} #buddypress .activity-lists.bp-list .activity-item' => 'padding-bottom: {{SIZE}}px',
				),
			)
		);

		$this->add_control(
			'switch_more',
			array(
				'label'   => esc_html__( 'Show All Activity Link', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'switch_avatar',
			array(
				'label'   => esc_html__( 'Show Avatar', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'switch_content',
			array(
				'label'   => esc_html__( 'Show Content', 'buddyboss-theme' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'switch_media',
			array(
				'label'     => esc_html__( 'Show Content Media', 'buddyboss-theme' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array(
					'switch_content' => 'yes',
				),
			)
		);

		$this->add_control(
			'switch_actions',
			array(
				'label'     => esc_html__( 'Show Actions', 'buddyboss-theme' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array(
					'switch_content' => 'yes',
				),
			)
		);

		$this->add_control(
			'switch_fav',
			array(
				'label'     => esc_html__( 'Like Button', 'buddyboss-theme' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array(
					'switch_content' => 'yes',
					'switch_actions' => 'yes',
				),
			)
		);

		$this->add_control(
			'switch_comments',
			array(
				'label'     => esc_html__( 'Show Comments', 'buddyboss-theme' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array(
					'switch_content' => 'yes',
					'switch_actions' => 'yes',
				),
			)
		);

		$this->add_control(
			'switch_edit',
			array(
				'label'     => esc_html__( 'Show Edit Activity', 'buddyboss-theme' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array(
					'switch_content' => 'yes',
					'switch_actions' => 'yes',
				),
			)
		);


		$this->add_control(
			'switch_delete',
			array(
				'label'     => esc_html__( 'Show Delete Activity', 'buddyboss-theme' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array(
					'switch_content' => 'yes',
					'switch_actions' => 'yes',
				),
			)
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
				'default' => __( 'Activity', 'buddyboss-theme' ),
				'placeholder' => __( 'Enter heading text', 'buddyboss-theme' ),
				'label_block' => true
			]
		);

		$this->add_control(
			'activity_link_text',
			[
				'label' => __( 'Activity Link Text', 'buddyboss-theme' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'default' => __( 'All Activity', 'buddyboss-theme' ),
				'placeholder' => __( 'Enter activity link text', 'buddyboss-theme' ),
				'label_block' => true,
				'condition' => [
					'switch_more' => 'yes',
				]
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_box',
			array(
				'label' => esc_html__( 'Box', 'buddyboss-theme' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'        => 'box_border',
				'label'       => __( 'Border', 'buddyboss-theme' ),
				'placeholder' => '1px',
				'default'     => '1px',
				'selector'    => '{{WRAPPER}} .bb-activity',
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'box_border_radius',
			array(
				'label'      => __( 'Border Radius', 'buddyboss-theme' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'default'    => array(
					'top'    => '4',
					'right'  => '4',
					'bottom' => '4',
					'left'   => '4',
				),
				'selectors'  => array(
					'{{WRAPPER}} .bb-activity' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			array(
				'name'     => 'background_color',
				'label'    => __( 'Background', 'buddyboss-theme' ),
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .bb-activity',
			)
		);

		$this->add_control(
			'separator_all',
			array(
				'label'     => __( 'All Activity Link', 'buddyboss-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'extra_color',
			array(
				'label'     => __( 'All Activity Link Color', 'buddyboss-theme' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bb-block-header__extra a' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_avatar',
			array(
				'label' => esc_html__( 'Avatar', 'buddyboss-theme' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'avatar_size',
			array(
				'label'     => __( 'Size', 'buddyboss-theme' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => array(
					'size' => 40,
				),
				'range'     => array(
					'px' => array(
						'min'  => 20,
						'max'  => 100,
						'step' => 1,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .bb-activity .activity-list.item-list .activity-item .activity-avatar' => 'flex: 0 0 {{SIZE}}px;',
					'{{WRAPPER}} .bb-activity .activity-list .activity-item div.item-avatar img' => 'max-width: {{SIZE}}px;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'        => 'avatar_border',
				'label'       => __( 'Border', 'buddyboss-theme' ),
				'placeholder' => '1px',
				'default'     => '1px',
				'selector'    => '{{WRAPPER}} .activity-list .activity-item div.item-avatar img',
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'avatar_border_radius',
			array(
				'label'      => __( 'Border Radius', 'buddyboss-theme' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .activity-list .activity-item div.item-avatar img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden;',
				),
			)
		);

		$this->add_control(
			'avatar_opacity',
			array(
				'label'     => __( 'Opacity (%)', 'buddyboss-theme' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => array(
					'size' => 1,
				),
				'range'     => array(
					'px' => array(
						'max'  => 1,
						'min'  => 0.10,
						'step' => 0.01,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .activity-list .activity-item div.item-avatar img' => 'opacity: {{SIZE}};',
				),
			)
		);

		$this->add_control(
			'avatar_spacing',
			array(
				'label'     => __( 'Spacing', 'buddyboss-theme' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => array(
					'size' => 15,
				),
				'range'     => array(
					'px' => array(
						'max' => 100,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .bb-activity .activity-list.item-list .activity-item .activity-avatar' => 'margin-right: {{SIZE}}{{UNIT}}',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_content',
			array(
				'label' => __( 'Content', 'buddyboss-theme' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_header',
				'label'    => __( 'Typography Header', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .activity-header > p, {{WRAPPER}} .activity-header a, {{WRAPPER}} .activity-header .activity-date',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_content',
				'label'    => __( 'Typography Content', 'buddyboss-theme' ),
				'selector' => '{{WRAPPER}} .activity-content .activity-inner',
			)
		);

		$this->add_control(
			'content_color',
			array(
				'label'     => __( 'Content Color', 'buddyboss-theme' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#4D5C6D',
				'selectors' => array(
					'{{WRAPPER}} .activity-content .activity-inner' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

	}

	public function bb_theme_elementor_activity_default_scope( $scope = 'all', $user_id = 0, $group_id = 0 ) {
		$new_scope = array();
		if ( bp_loggedin_user_id() && ( 'all' === $scope || empty( $scope ) ) ) {
			$new_scope[] = 'public';
			if ( bp_is_active( 'group' ) && ! empty( $group_id ) ) {
				$new_scope[] = 'groups';
			} else {
				$new_scope[] = 'just-me';
				if ( empty( $user_id ) ) {
					$new_scope[] = 'public';
				}
				if ( function_exists( 'bp_activity_do_mentions' ) && bp_activity_do_mentions() ) {
					$new_scope[] = 'mentions';
				}
				if ( bp_is_active( 'friends' ) ) {
					$new_scope[] = 'friends';
				}
				if ( bp_is_active( 'groups' ) ) {
					$new_scope[] = 'groups';
				}
				if ( function_exists( 'bp_is_activity_follow_active' ) && bp_is_activity_follow_active() ) {
					$new_scope[] = 'following';
				}
				if ( bp_is_single_activity() && bp_is_active( 'media' ) ) {
					$new_scope[] = 'media';
					$new_scope[] = 'document';
				}
			}
		} elseif ( ! bp_loggedin_user_id() && ( 'all' === $scope || empty( $scope ) ) ) {
			$new_scope[] = 'public';
		}
		$new_scope = array_unique( $new_scope );
		if ( empty( $new_scope ) ) {
			$new_scope = (array) $scope;
		}
		/**
		 * Filter to update default scope.
		 */
		$new_scope = apply_filters( 'bb_theme_elementor_activity_default_scope', $new_scope );
		return implode( ',', $new_scope );
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
	    global $bb_theme_elementor_activity;

		$settings = $this->get_settings_for_display();

		add_filter( 'bp_excerpt_length', array( $this, 'bb_elementor_change_activity_content_excerpt_length' ), 99, 1 );
		// Override parameters for bp_has_activities().
		$args = array(
			'max'        => esc_attr( $settings['activity_count']['size'] ),
			'per_page'   => esc_attr( $settings['activity_count']['size'] ),
		);

		$args['scope'] = $this->bb_theme_elementor_activity_default_scope();

		$has_activity = bp_has_activities( $args );

		if ( bp_is_active( 'activity' ) ) {
			wp_enqueue_script( 'bp-nouveau-activity' );
			wp_enqueue_script( 'bp-nouveau-activity-post-form' );
			bp_get_template_part( 'common/js-templates/activity/form' );
		}

		$media = false;
		if ( bp_is_active( 'media' ) && ( bp_is_profile_media_support_enabled() || bp_is_group_media_support_enabled() || bp_is_forums_media_support_enabled() ) ) {
			$media = true;
		}
		if ( bp_is_active( 'media' ) && ( bp_is_profile_document_support_enabled() || bp_is_group_document_support_enabled() || bp_is_forums_document_support_enabled() ) ) {
			$media = true;
		}

		if ( bp_is_active( 'media' ) && ( bp_is_profiles_gif_support_enabled() || bp_is_groups_gif_support_enabled() || bp_is_forums_gif_support_enabled() ) ) {
			wp_enqueue_script( 'giphy' );
		}

		if ( bp_is_active( 'media' ) && ( bp_is_profiles_emoji_support_enabled() || bp_is_groups_emoji_support_enabled() || bp_is_forums_emoji_support_enabled() ) ) {
			wp_enqueue_script( 'emojionearea' );
			wp_enqueue_style( 'emojionearea' );
		}

		if ( $media ) {
			wp_enqueue_script( 'bp-media-dropzone' );
			wp_enqueue_script( 'bp-nouveau-codemirror' );
			wp_enqueue_script( 'bp-nouveau-codemirror-css' );
			wp_enqueue_script( 'bp-nouveau-media' );
			wp_enqueue_script( 'bp-exif' );
		}

		if ( bp_is_active( 'media' ) && bp_is_active( 'video' ) && ( bp_is_profile_video_support_enabled() || bp_is_group_video_support_enabled() || bp_is_forums_video_support_enabled() ) ) {
			wp_enqueue_style( 'bp-media-videojs-css' );
			wp_enqueue_script( 'bp-media-videojs' );
			wp_enqueue_script( 'bp-media-videojs-seek-buttons' );
			wp_enqueue_script( 'bp-media-videojs-flv' );
			wp_enqueue_script( 'bp-media-videojs-flash' );
			wp_enqueue_script( 'bp-nouveau-video' );
			bp_get_template_part( 'video/theatre' );
		}

		if ( bp_is_active( 'media' ) && ( bp_is_profile_media_support_enabled() || bp_is_group_media_support_enabled() || bp_is_forums_media_support_enabled() ) ) {
			bp_get_template_part( 'media/theatre' );
		}
		if ( bp_is_active( 'media' ) && ( bp_is_profile_document_support_enabled() || bp_is_group_document_support_enabled() || bp_is_forums_document_support_enabled() ) ) {
			bp_get_template_part( 'document/theatre' );
		}

		$this->add_render_attribute( 'actions', 'class', 'activity-actions' );

		if ( $settings['switch_actions'] ) {
			$this->add_render_attribute( 'actions', 'class', 'activity-actions--show' );
		}

		if ( $settings['switch_fav'] ) {
			$this->add_render_attribute( 'actions', 'class', 'activity-actions--fav' );
		}

		if ( $settings['switch_comments'] ) {
			$this->add_render_attribute( 'actions', 'class', 'activity-actions--comment' );
		}

		if ( $settings['switch_edit'] ) {
			$this->add_render_attribute( 'actions', 'class', 'activity-actions--edit' );
		}

		if ( $settings['switch_delete'] ) {
			$this->add_render_attribute( 'actions', 'class', 'activity-actions--delete' );
		}

		$this->add_render_attribute( 'do-state', 'class', 'do-state' );

		if ( $settings['switch_comments'] ) {
			$this->add_render_attribute( 'do-state', 'class', 'is-activity-comments' );
		}

		if ( $settings['switch_fav'] ) {
			$this->add_render_attribute( 'do-state', 'class', 'do-state--show' );
		}

		$bb_theme_elementor_activity = true;
		?>
		<div class="bb-activity <?php echo ( ! $has_activity ) ? 'bb-forums--blank' : ''; ?>">

			<?php if ( $has_activity ) : ?>

				<div class="bb-block-header flex align-items-center">
					<div class="bb-block-header__title"><h3><?php echo esc_html( $settings['heading_text'] ); ?></h3></div>
					<?php if ( $settings['switch_more'] ) : ?>
						<div class="bb-block-header__extra push-right">
							<?php if( '' != $settings['activity_link_text'] ) { ?>
								<a href="<?php bp_activity_directory_permalink(); ?>" class="count-more"><?php echo esc_html( $settings['activity_link_text'] ); ?><i class="bb-icon-l bb-icon-angle-right"></i></a>
							<?php } ?>
						</div>
					<?php endif; ?>
				</div>

				<div class="bbel-list-flow">
					<div class="activity-list item-list">
						<div id="buddypress" class="buddypress-wrap">
							<div class="screen-content">
								<div id="activity-stream" class="activity" data-ajax="false" data-bp-list="activity">
									<ul class="activity-lists item-list bp-list elementor-activity-widget">
										<?php while ( bp_activities() ) : bp_the_activity(); ?>
											<li class="<?php bp_activity_css_class(); ?>" id="activity-<?php bp_activity_id(); ?>" data-bp-activity-id="<?php bp_activity_id(); ?>" data-bp-timestamp="<?php bp_nouveau_activity_timestamp(); ?>" data-bp-activity="<?php ( function_exists('bp_nouveau_edit_activity_data') ) ? bp_nouveau_edit_activity_data() : ''; ?>" >

												<div class="bp-activity-head">
													<?php if ($settings['switch_avatar']) : ?>
														<div class="activity-avatar item-avatar">
															<a href="<?php bp_activity_user_link(); ?>">
																<?php bp_activity_avatar( array( 'type' => 'full' ) ); ?>
															</a>
														</div>
													<?php endif; ?>
													<div class="activity-header">
														<?php bp_activity_action(); ?>
														<p class="activity-date">
                                                            <a href="<?php echo esc_url( bp_activity_get_permalink( bp_get_activity_id() ) ); ?>"><?php echo bp_core_time_since( bp_get_activity_date_recorded() ); ?></a>
															<?php
															if ( function_exists( 'bp_nouveau_activity_is_edited' ) ){
																bp_nouveau_activity_is_edited();
															}
															?>
                                                        </p>
                                                        <?php
														if ( function_exists( 'bb_theme_elementor_bp_nouveau_activity_privacy' ) ) {
															bb_theme_elementor_bp_nouveau_activity_privacy();
														}
														?>
													</div>
												</div>

												<?php if ( bp_nouveau_activity_has_content() && $settings['switch_content'] ) : ?>
													<div class="activity-content <?php echo $settings['switch_media'] ? '' : 'no-media'; ?>">
														<?php bp_nouveau_activity_hook( 'before', 'activity_content' ); ?>
														<?php if ( bp_nouveau_activity_has_content() ) : ?>
															<div class="activity-inner"><?php bp_nouveau_activity_content(); ?></div>
														<?php endif; ?>
														<?php bp_nouveau_activity_hook( 'after', 'activity_content' ); ?>
														<div <?php echo $this->get_render_attribute_string('do-state'); ?>>
															<?php bp_nouveau_activity_state(); ?>
														</div>
														<div <?php echo $this->get_render_attribute_string('actions'); ?>>
															<?php bp_nouveau_activity_entry_buttons(); ?>
														</div>
													</div>
												<?php endif; ?>

												<?php if ( $settings['switch_comments'] ) { ?>

													<?php bp_nouveau_activity_hook( 'before', 'entry_comments' ); ?>


													<?php if ( bp_activity_get_comment_count() || ( is_user_logged_in() && ( bp_activity_can_comment() || bp_is_single_activity() ) ) ) : ?>

														<div class="activity-comments">

															<?php bp_activity_comments(); ?>

															<?php bp_nouveau_activity_comment_form(); ?>

														</div>

													<?php endif; ?>

													<?php bp_nouveau_activity_hook( 'after', 'entry_comments' ); ?>

												<?php } ?>

											</li>
										<?php endwhile; ?>
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div>

			<?php else : ?>

				<div class="bb-no-data bb-no-data--activity">
					<img class="bb-no-data__image" src="<?php echo get_template_directory_uri(); ?>/assets/images/svg/dfy-no-data-icon01.svg" alt="Activity" />
					<?php bp_nouveau_user_feedback( 'activity-loop-none' ); ?>
					<a href="<?php echo esc_url( bp_get_activity_directory_permalink() ); ?>" class="bb-no-data__link"><?php _e( 'Post an Update', 'buddyboss-theme' ); ?></a>
				</div>

			<?php endif; ?>

		</div>
		<?php

		remove_filter( 'bp_excerpt_length', array( $this, 'bb_elementor_change_activity_content_excerpt_length' ), 99, 1 );
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
	/*
	protected function _content_template() {

	}*/

	public function bb_elementor_change_activity_content_excerpt_length( $length ) {
		$length = 135;
		return $length;
	}
}
