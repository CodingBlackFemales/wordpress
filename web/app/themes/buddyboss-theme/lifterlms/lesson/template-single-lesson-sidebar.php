<?php

if ( ! is_singular( 'llms_quiz' ) && ! is_singular( 'llms_assignment' ) ):
	global $post;
endif;

if ( is_singular( 'llms_quiz' ) || is_singular( 'llms_assignment' ) ):
	$assignment = llms_get_post( get_the_ID() );
	$lessonID   = $assignment->get( 'lesson_id' );
	$post       = get_post( $lessonID );
endif;

$lesson                 = new LLMS_Lesson( $post );
$side_panel_state_class = '';

if ( ( isset( $_COOKIE['lessonpanel'] ) && 'closed' === $_COOKIE['lessonpanel'] ) ) {
	$side_panel_state_class = 'lms-topic-sidebar-close';
}

$users_per_page             = apply_filters( 'buddyboss_llms_get_course_participants', 5 );
$course_id                  = buddyboss_theme()->lifterlms_helper()->bb_lifterlms_get_parent_course( $lesson );
$enrolled_users             = buddyboss_theme()->lifterlms_helper()->bb_theme_llms_get_users_for_course( $course_id, 1, $users_per_page );
$total_enrolled_users_count = $enrolled_users['total'];
$total_enrolled_users_data  = $enrolled_users['data'];

?>

<div class="lifter-topic-sidebar-wrapper <?php echo esc_attr( $side_panel_state_class ); ?>">

    <div class="lifter-topic-sidebar-data">

        <div class="lifter-topic-sidebar-course-navigation">
            <div class="ld-course-navigation">

	            <?php if ( is_singular( 'llms_quiz' ) || is_singular( 'llms_assignment' ) ): ?>

		            <a title="<?php echo esc_html( get_the_title( $lessonID ) ); ?>" href="<?php echo esc_url( get_permalink( $lessonID ) ); ?>" class="course-entry-link">
	                      <span>
	                           <i class="bb-icons bb-icon-chevron-left"></i>
	                           <?php esc_html_e( 'Back to Lesson', 'buddyboss-theme' ); ?>
	                      </span>
		            </a>
		            <h2 class="course-entry-title"><?php echo esc_html( get_the_title( $lessonID ) ); ?></h2>
	            <?php else: ?>

                    <a title="<?php echo esc_attr( get_the_title( $course_id ) ); ?>" href="<?php echo esc_url( get_permalink( $course_id ) ); ?>" class="course-entry-link">
	                      <span>
	                           <i class="bb-icons bb-icon-chevron-left"></i>
	                           <?php esc_html_e( 'Back to Course', 'buddyboss-theme' ); ?>
	                      </span>
		            </a>
		            <h2 class="course-entry-title"><?php echo esc_html( get_the_title( $course_id ) ); ?></h2>
	            <?php endif; ?>
            </div>
        </div>


        <div class="lifter-topic-sidebar-progress">
			<?php
			$progress = buddyboss_theme()->lifterlms_helper()->boss_theme_progress_course( $course_id );
			$progress = round( $progress, 2 );

			echo  buddyboss_theme()->lifterlms_helper()->lifterlms_course_progress_bar( $progress, false, false, true ); ?>
		</div>

		<?php if ( buddyboss_theme_get_option( 'lifterlms_lesson_list' ) ) : ?>
			<div class="lifterlms-lessions-list">
				<?php
				$course    = new LLMS_Course( $course_id );
				$sections  = $course->get_sections();
				$lesson_id = get_the_ID();
				?>

				<div class="llms-syllabus-wrapper">
					<?php if ( ! $sections ) : ?><?php _e( 'This course does not have any sections.', 'buddyboss-theme' ); ?><?php else : ?>
						<?php foreach ( $sections as $section ) : ?>

							<?php if ( apply_filters( 'llms_display_outline_section_titles', true ) ) : ?>
								<h3 class="llms-h3 llms-section-title"><?php echo get_the_title( $section->get( 'id' ) ); ?></h3>
							<?php endif; ?>

							<?php $lessons = $section->get_lessons(); ?>
							<?php if ( $lessons ) : ?>
								<?php foreach ( $lessons as $lesson ) : ?>
									<div class="lifterlms_lesson_holder <?php if ( $lesson->id == $lesson_id ) { echo esc_attr( "current_title" ); } ?>">
										<?php
										llms_get_template( 'course/lesson-preview.php',
											array(
												'lesson'        => $lesson,
												'total_lessons' => count( $lessons ),
											) );
										?>
									</div>
								<?php endforeach; ?>
							<?php else : ?>
								<div class="llms-lesson-preview--blank">
									<?php _e( 'This section does not have any lessons.', 'buddyboss-theme' ); ?>
								</div>
							<?php endif; ?>
						<?php endforeach; ?>
					<?php endif; ?>
				</div> <!-- .llms-syllabus-wrapper -->
			</div> <!-- .lifterlms-lessions-list -->
		<?php endif; ?>

        <?php
        if ( (int) $total_enrolled_users_count > 0 && buddyboss_theme_get_option( 'lifterlms_course_participant' ) ) {
            ?>
            <div class="llms-course-members-list">
                <h4 class="llms-course-sidebar-heading"><?php _e( 'Participants', 'buddyboss-theme' ); ?><span class="llms-count"><?php echo $total_enrolled_users_count; ?></span></h4>
                <input type="hidden" name="buddyboss_theme_llms_course_participants_course_id" id="buddyboss_theme_llms_course_participants_course_id" value="<?php echo esc_attr( $course_id ); ?>">
                <div class="bb-course-member-wrap">
                    <ul class="course-members-list">
				        <?php
				        $count = 0;
				        foreach( $total_enrolled_users_data as $course_member ) :
					        if ( $count > 4 ) {
						        break;
					        }
					        ?>
                            <li>
						        <?php
						        $user_link = buddyboss_theme()->lifterlms_helper()->bb_llms_get_user_link( (int) $course_member->user_id );
						        ?>
                                <a href="<?php echo esc_url( $user_link ); ?>">

                                    <img class="round" src="<?php echo esc_url( get_avatar_url( (int) $course_member->user_id, array( 'size' => 96 ) ) ); ?>" alt="" />
							        <?php
							        if ( class_exists( 'BuddyPress' ) ) { ?>
                                        <span><?php echo bp_core_get_user_displayname( (int) $course_member->user_id ); ?></span>
								        <?php
							        } else { ?>
								        <?php $course_member = get_userdata( (int) $course_member->user_id ); ?>
                                        <span><?php echo $course_member->display_name; ?></span>
								        <?php
							        } ?>
                                </a>
                            </li>
				        <?php
				        endforeach;
				        ?>
                    </ul>
                    <ul class="course-members-list course-members-list-extra">
                    </ul>
			        <?php
			        if( $total_enrolled_users_count > 5 ) {
				        ?>
                        <a href="javascript:void(0);" class="list-members-extra list-members-extra--llms lme-more--llms">
                            <span class="members-count-g"></span> <?php _e( 'Show more', 'buddyboss-theme' ); ?><i class="bb-icon-l bb-icon-angle-down"></i>
                        </a>
				        <?php
			        }
			        ?>
                </div>
            </div>
            <?php
        }
        ?>

		<?php if(is_active_sidebar('llms_lesson_widgets_side')) { ?>
			<div class="lifter-sidebar-widgets">
				<ul>
					<?php dynamic_sidebar('llms_lesson_widgets_side') ?>
				</ul>
			</div>
		<?php } ?>


	</div> <!-- .lifter-topic-sidebar-data -->

</div> <!-- .lifter-topic-sidebar-wrapper -->





