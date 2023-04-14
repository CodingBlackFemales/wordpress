<div id="lifterlms-page-content">
    <div class="lifterlms-content-body">
        <div class="lifterlms-wrapper">
            <div id="lifterlms-lesson-header">
                <div id="lifterlms-lesson-header">
                    <div class="bb-ld-info-bar">
                        <div class="ld-lesson-status">
                            <div class="ld-breadcrumbs">
                                <div class="ld-breadcrumbs-segments">
									<?php
									$assignment   = llms_get_post( get_the_ID() );
									$lessonID     = $assignment->get( 'lesson_id' );
									$post_object  = get_post( $lessonID );
									$lesson       = new LLMS_Lesson( $post_object );
									$course_id    = buddyboss_theme()->lifterlms_helper()->bb_lifterlms_get_parent_course( $lesson );
									$lesson_title = get_the_title();
									?>
                                    <span>
                                        <a href="<?php echo get_permalink( $course_id ); ?>">
                                            <?php echo get_the_title( $course_id ); ?>
                                        </a>
                                    </span>
                                    <span>
                                        <a href="<?php echo get_permalink( $lessonID ); ?>">
                                            <?php echo get_the_title( $lessonID ); ?>
                                        </a>
                                    </span>
                                    <span>
                                        <a href="<?php echo get_permalink( $assignment ); ?>">
                                            <?php echo $assignment->title; ?>
                                        </a>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex bb-position">
                        <div class="lifterlms-lesson-position">
							<?php
							$course          = new LLMS_Course( $course_id );
							$courses_lessons = $course->get_lessons( 'ids' );
							array_unshift( $courses_lessons, '' );
							unset( $courses_lessons[0] );
							$all_lesson_count  = 0;
							$number_of_lessons = 1;
							$current_lesson    = 0;

							if ( ! empty( $courses_lessons ) ) {
								$all_lesson_count = count( $courses_lessons );
								foreach ( $courses_lessons as $courses_lesson ) {
									if ( $lessonID == $courses_lesson ) {
										$current_lesson = $number_of_lessons;
									}
									$number_of_lessons ++;
								}
							}
							?>
                            <span class="bb-pages">
                                <?php _e( 'Lesson', 'buddyboss-theme' ); echo ' ' . $current_lesson; ?>
                                <span class="bb-total">
                                    <?php _e( 'of', 'buddyboss-theme' ); echo ' ' . $all_lesson_count; ?>
                                </span>
                                <?php _e( ', Assignment', 'buddyboss-theme' ); ?>
                            </span>
                        </div>
                        <div class="lifterlms-lesson-nav">
                            <div class="bb-ld-status">
								<?php
								$progress = buddyboss_theme()->lifterlms_helper()->boss_theme_progress_course( $course_id );
								if ( $progress == 100 ) {
									?>
									<div class="ld-status ld-status-complete ld-secondary-background"><?php _e( 'Complete', 'buddyboss-theme' ); ?></div>
								<?php
								} else { ?>
									<div class="ld-status ld-status-progress ld-primary-background"><?php _e( 'In Progress', 'buddyboss-theme' ); ?></div>
								<?php
								} ?>
                            </div>
                            <div class="lifterlms_next_prev_link">
								<?php
								$lesson  = new LLMS_Lesson( $lessonID );
								$prev_id = $lesson->get_previous_lesson();
								$next_id = $lesson->get_next_lesson();
								if ( ! $prev_id ) {
									$prev_id = buddyboss_theme()->lifterlms_helper()->bb_lifterlms_get_parent_course( $lesson );
								}
								if ( ! $next_id ) {
									$next_id = buddyboss_theme()->lifterlms_helper()->bb_lifterlms_get_parent_course( $lesson );
								}
								?>

                                <a href="<?php echo get_permalink( $prev_id ); ?>" class="prev-link" rel="prev">
                                    <span class="meta-nav" data-balloon-pos="up" data-balloon="<?php _e( 'Previous', 'buddyboss-theme' ); ?>">&larr;</span>
                                </a>

                                <a href="<?php echo get_permalink( $next_id ); ?>" class="next-link" rel="next">
                                    <span class="meta-nav" data-balloon-pos="up" data-balloon="<?php _e( 'Next', 'buddyboss-theme' ); ?>">&rarr;</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="lifterlms-header-title">
                        <h1><?php echo $lesson_title; ?></h1>
                    </div>
					<?php
					$post_author_id = get_post_field( 'post_author', get_the_ID() );
					$args           = wp_parse_args( $args,
						array(
							'avatar'      => true,
							'avatar_size' => 32,
							'user_id'     => $post_author_id,
						) );

					$name = get_the_author_meta( 'display_name', $args['user_id'] );
					if ( $args['avatar'] ) {
						$img = get_avatar( $args['user_id'],
							$args['avatar_size'],
							apply_filters( 'lifterlms_author_avatar_placeholder', '' ),
							$name );
					} else {
						$img = '';
					}
					?>
                    <div class="lifterlms-header-instructor">
						<?php
						$lifterlms_course_author = buddyboss_theme_get_option( 'lifterlms_course_author' );
						$lifterlms_course_date   = buddyboss_theme_get_option( 'lifterlms_course_date' );
						if ( ( isset( $lifterlms_course_author ) && ( $lifterlms_course_author == 1 ) ) || ( isset( $lifterlms_course_date ) && ( $lifterlms_course_date == 1 ) ) ) :
							?>
                            <div class="bb-about-instructor bb_single_meta_pfx">
                                <div class="flex">
									<?php
									$user_link = buddyboss_theme()->lifterlms_helper()->bb_llms_get_user_link( get_the_author_meta( 'ID' ) );

									if ( isset( $lifterlms_course_author ) && ( $lifterlms_course_author == 1 ) ) : ?>
                                        <div class="bb-avatar-wrap">
                                            <a href="<?php echo $user_link; ?>">
												<?php echo $img; ?>
                                            </a>
                                        </div>
									    <?php
                                    endif;
                                    ?>

                                    <div class="bb-content-wrap">
                                        <h5>
											<?php if ( isset( $lifterlms_course_author ) && ( $lifterlms_course_author == 1 ) ) : ?>
                                                <a href="<?php echo $user_link; ?>"><?php echo $name; ?></a>
											<?php endif; ?>

											<?php if ( isset( $lifterlms_course_author ) && ( $lifterlms_course_author == 1 ) && isset( $lifterlms_course_date ) && ( $lifterlms_course_date == 1 ) ) : ?>
                                                <span class="meta-saperator">&middot;</span>
											<?php endif; ?>

											<?php if ( isset( $lifterlms_course_date ) && ( $lifterlms_course_date == 1 ) ) : ?>
                                                <span class="bb-about-instructor-date"><?php echo get_the_date(); ?></span>
											<?php endif; ?>
                                        </h5>
                                    </div>

                                </div>
                            </div>
						<?php
                        endif; ?>
                    </div>
                </div>
                <div class="lifterlms_content_wrap">
                    <div class="entry-content">
						<?php
						the_content( sprintf( wp_kses( /* translators: %s: Name of current post. Only visible to screen readers */ __( 'Continue reading<span class="screen-reader-text"> "%s"</span>',
								'buddyboss-theme' ),
								array(
									'span' => array(
										'class' => array(),
									),
								) ),
								get_the_title() ) );
						?>
                    </div><!-- .entry-content -->
                </div>
            </div>
        </div>
    </div>
