<?php
/**
 * The Template for displaying all single courses.
 *
 * @package     LifterLMS/Templates
 *
 * @since       1.0.0
 * @version     3.14.0
 */

defined( 'ABSPATH' ) || exit;
?>
<li <?php post_class( 'llms-loop-item' ); ?>>
    <div class="llms-loop-item-content">

		<?php
		/**
		 * Hook: lifterlms_before_loop_item
		 *
		 * @hooked lifterlms_loop_featured_video - 8
		 * @hooked lifterlms_loop_link_start - 10
		 */
		do_action( 'lifterlms_before_loop_item' );
		?>

		<?php
		/**
		 * Hook: lifterlms_before_loop_item_title
		 *
		 * @hooked lifterlms_template_loop_thumbnail - 10
		 * @hooked lifterlms_template_loop_progress - 15
		 */
		do_action( 'lifterlms_before_loop_item_title' );
		?>

        <div class="bb-card-course-details llms-loop-item-content__body">

			<?php
			$number_of_lessons = count( buddyboss_theme()->lifterlms_helper()->get_course_lessons( get_the_ID() ) );
			?>

            <div class="course-lesson-count">
				<?php
				if( is_courses() ) {

					printf( _n( '%s Lesson', '%s Lessons', $number_of_lessons, 'buddyboss-theme' ),
						number_format_i18n( $number_of_lessons ) );

				}
				?>
            </div>

            <h4 class="bb-course-title llms-loop-title"><?php the_title(); ?></h4>

            <footer class="llms-loop-item-footer">
				<?php
				/**
				 * Hook: lifterlms_after_loop_item_title
				 *
				 * @hooked lifterlms_template_loop_author - 10
				 * @hooked lifterlms_template_loop_length - 15
				 * @hooked lifterlms_template_loop_difficulty - 20
				 *
				 * On Student Dashboard & "Mine" Courses Shortcode
				 * @hooked lifterlms_template_loop_enroll_status - 25
				 * @hooked lifterlms_template_loop_enroll_date - 30
				 */

				do_action( 'lifterlms_after_loop_item_title' );

				$user_progress = buddyboss_theme()->lifterlms_helper()->boss_theme_progress_course( get_the_ID() );
				$user_progress = round( $user_progress, 2 );

				if ( $user_progress != 0 ) {

					echo buddyboss_theme()->lifterlms_helper()->lifterlms_course_progress_bar( $user_progress, false, false, true );

				} else { ?>

                    <div class="bb-course-excerpt">
						<?php echo wp_trim_words( get_the_excerpt( get_the_ID() ), 20 ); ?>
                    </div>

				<?php } ?>

            </footer>

        </div>

		<?php
		/**
		 * Hook: lifterlms_after_loop_item
		 *
		 * @hooked lifterlms_loop_link_end - 5
		 */
		do_action( 'lifterlms_after_loop_item' );
		?>

    </div><!-- .llms-loop-item-content -->
</li><!-- .llms-loop-item -->
