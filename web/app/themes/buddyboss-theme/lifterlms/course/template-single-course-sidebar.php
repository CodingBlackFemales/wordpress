<?php

global $post;

$is_enrolled                = false;
$current_user_id            = get_current_user_id();
$course_id                  = $post->ID;
$course_video_embed         = get_post_meta( $course_id, '_llms_video_embed', true );
$course_audio_embed         = get_post_meta( $course_id, '_llms_audio_embed', true );
$enrolled_users             = buddyboss_theme()->lifterlms_helper()->bb_theme_llms_get_users_for_course( $course_id, 1, 5 );
$total_enrolled_users_count = $enrolled_users['total'];
$total_enrolled_users_data  = $enrolled_users['data'];
$file_info                  = pathinfo( $course_video_embed );

$product = new LLMS_Product( $course_id );
$course = new LLMS_Course( $post );

?>

<div class="bb-single-course-sidebar bb-preview-wrap">
	<div class="bb-llms-sticky-sidebar">
		<div class="widget bb-enroll-widget">
			<div class="bb-enroll-widget flex-1 push-right">
				<div class="bb-course-preview-wrap bb-thumbnail-preview">
					<?php
                    if ( ! empty( $course_video_embed ) ) { ?>
                        <div class="bb-preview-course-link-wrap">
                            <div class="thumbnail-container">
								<div class="bb-course-video-overlay">
									<div>
										<span class="bb-course-play-btn-wrapper"><span class="bb-course-play-btn"></span></span>
										<div><?php _e( 'Preview this course', 'buddyboss-theme' ); ?></div>
									</div>
                                </div>
                                <?php
									if ( has_post_thumbnail() ) {
                                        the_post_thumbnail();
									}
                                ?>
                            </div>
						</div>
						<?php
                    } else { ?>
						<div class="bb-preview-course-link-wrap">
							<div class="thumbnail-container">
								<?php if ( has_post_thumbnail() ) {
									the_post_thumbnail( 'full' );
								} ?>
							</div>
						</div>
					<?php 
					} 
					?>
				</div>
			</div>
			
			<div class="bb-course-preview-content">
				<?php if ( buddyboss_theme_get_option( 'lifterlms_course_participant' ) ) { ?>
					<div class="bb-course-member-wrap flex align-items-center">
						<span class="bb-course-members">
							<?php
							$count = 0;
							foreach ( $total_enrolled_users_data as $course_member ) :
								if ( $count > 2 ) {
									break;
								} ?>
								<img class="round" src="<?php echo get_avatar_url( (int) $course_member->user_id, array( 'size' => 96 ) ); ?>"alt=""/><?php
								$count ++;
							endforeach; ?>
						</span>

						<?php
						if ( $total_enrolled_users_count > 3 ) { ?>
							<span class="members">
								<span class="members-count-g">
									<?php
									_e( '+', 'buddyboss-theme' );
									echo $total_enrolled_users_count - 3;
									?>
								</span>
								<?php
								_e( 'enrolled', 'buddyboss-theme' );
								?>
							</span>
							<?php
						}
						?>
					</div>
				<?php } ?>

				<div class="lifterlms_pricing_button">
					<?php if ( $product->get_access_plans() && ! llms_is_user_enrolled( $current_user_id, $course_id ) && has_block( 'llms/pricing-table' ) ) { ?>

						<?php if ( 'yes' === $course->get( 'enrollment_period' ) ) { ?>
							
						<?php } elseif ( ! $course->has_capacity() ) { ?>
							<div class="llms-notice llms-error llms-notice---sidebar">
								<?php _e( 'Enrollment has closed because the maximum number of allowed students has been reached.', 'buddyboss-theme' ); ?>
							</div>
						<?php } else { ?>
							<a href="#" class="button llms-button-action link-to-llms-access-plans"><?php _e( 'See Plans', 'buddyboss-theme' ); ?></a>
						<?php } ?>
						
					<?php } ?>

				</div>

				<?php
				$course_length     = $course->get( 'length' );
				$course_difficulty = $course->get_difficulty();
				$course_tracks     = get_the_term_list( $post->ID, 'course_track' );
				$course_cats       = get_the_term_list( $post->ID, 'course_cat' );
				$course_tags       = get_the_term_list( $post->ID, 'course_tag' );

				if ( ! empty( $course_length ) || ! empty( $course_difficulty ) || ! empty( $course_tracks ) || ! empty( $course_cats ) || ! empty( $course_tags ) ) {
					?>
                    <div class="lifterlms_course_information">
                        <h3>
                        <?php
                            _e( 'Course Information', 'buddyboss-theme' );
                        ?>
                        </h3>
                        <div class="llms-meta-info">
                        <?php
                        if ( ! empty( $course_length ) ) {
                            lifterlms_template_single_length();
                        }

                        if ( ! empty( $course_difficulty ) ) {
	                        $terms = get_the_terms( $post->ID, 'course_difficulty' );
	                        $term  = wp_list_pluck( $terms, 'name' );
	                        ?>
                            <div class="llms-meta llms-difficulty">
                                <p><?php printf( __( 'Difficulty: <span class="difficulty">%s</span>', 'buddyboss-theme' ), implode( ', ', $term ) ); ?></p>
                            </div>
                            <?php
                        }

                        if ( ! empty( $course_tracks ) ) {
                            lifterlms_template_single_course_tracks();
                        }

                        if ( ! empty( $course_cats ) ) {
                            lifterlms_template_single_course_categories();
                        }

                        if ( ! empty( $course_tags ) ) {
                            lifterlms_template_single_course_tags();
                        }
                        ?>
                        </div>
                    </div>
				<?php
				}
				?>
			</div>
		</div>
		<?php
        if ( is_active_sidebar( 'llms_course_widgets_side' ) ) { ?>
			<ul class="lifter-sidebar-widgets">
				<?php dynamic_sidebar( 'llms_course_widgets_side' ); ?>
			</ul>
		<?php
		}
        ?>
	</div>
</div>

<div class="bb-modal bb_course_video_details mfp-hide">
	<?php
	if ( $course_video_embed !== '' ) :
		if ( wp_oembed_get( $course_video_embed ) ) :

			?>
		<?php echo wp_oembed_get( $course_video_embed ); ?><?php elseif ( isset( $file_info['extension'] ) && $file_info['extension'] === 'mp4' ) : ?>
			<video width="100%" controls>
				<source src="<?php echo $course_video_embed; ?>" type="video/mp4">
				<?php _e( 'Your browser does not support HTML5 video.', 'buddyboss-theme' ); ?>
			</video>
				<?php
		else :
			_e( 'Video format is not supported, use Youtube video or MP4 format.', 'buddyboss-theme' );
		endif;
	endif;
	?>
</div>

