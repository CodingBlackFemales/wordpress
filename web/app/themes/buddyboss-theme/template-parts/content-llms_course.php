<?php
/**
 * Template part for displaying courses
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package BuddyBoss_Theme
 */

global $post;
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<?php
    if ( ! is_single() || is_related_posts() ) { ?>
	    <div class="post-inner-wrap">
		<?php
    }

		if ( ( ! is_single() || is_related_posts() ) && function_exists( 'buddyboss_theme_entry_header' ) ) {
			buddyboss_theme_entry_header( $post );
		}
		?>

		<div class="llms-course-entry-content-wrap">

			<?php if ( ! is_singular() || is_related_posts() ) { ?>
				<div class="entry-content">
					<?php the_excerpt(); ?>
				</div>
			<?php }

            if ( is_single() && ! is_related_posts() ) { ?>
				<div class="bb-vw-container bb-llms-banner">

					<?php
                    $course_cover_photo = false;

					if ( class_exists( '\BuddyBossTheme\BuddyBossMultiPostThumbnails' ) ) {
						$course_cover_photo = \BuddyBossTheme\BuddyBossMultiPostThumbnails::get_post_thumbnail_url(
							'course',
							'cover-course-image'
						);
					}

					if ( ! empty( $course_cover_photo ) ) {
						?>
                        <img src="<?php echo $course_cover_photo; ?>" alt="<?php echo get_the_title( get_the_ID() ); ?>" class="banner-img wp-post-image"/>
						<?php
					} ?>

					<div class="bb-course-banner-info container">

                        <div class="flex flex-wrap">

						    <div class="bb-course-banner-inner">

							    <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>

							    <?php if ( has_excerpt() ) { ?>
                                    <div class="bb-course-excerpt">
                                        <?php echo get_the_excerpt(); ?>
                                    </div>
							    <?php } ?>
                                <div class="bb-course-points">
                                    <a class="anchor-course-points" href="#lifter-course-content">
                                        <?php echo __( 'View course details', 'buddyboss-theme' ); ?>
                                        <i class="bb-icon-l bb-icon-angle-down"></i>
                                    </a>
                                </div>

                                <div class="bb-course-single-meta flex align-items-center">
                                    <?php
                                    $lifterlms_course_author = buddyboss_theme_get_option( 'lifterlms_course_author' );
                                    $lifterlms_course_date   = buddyboss_theme_get_option( 'lifterlms_course_date' );

                                    if ( isset( $lifterlms_course_author ) && ( 1 === (int) $lifterlms_course_author ) ) :

                                        $user_link = buddyboss_theme()->lifterlms_helper()->bb_llms_get_user_link( get_the_author_meta( 'ID' ) );
                                        ?>

                                            <a href="<?php echo $user_link; ?>">
                                            <?php echo get_avatar( get_the_author_meta( 'ID' ), 80 ); ?>
                                                <span class="author-name"><?php the_author(); ?></span>
                                            </a>

                                        <?php
                                    endif;

                                    if ( isset( $lifterlms_course_author ) && ( 1 === (int) $lifterlms_course_author ) && isset( $lifterlms_course_date ) && ( 1 === (int) $lifterlms_course_date ) ) : ?>

                                        <span class="meta-saperator">&middot;</span>

                                        <?php
                                    endif;

                                    if ( isset( $lifterlms_course_date ) && ( 1 === (int) $lifterlms_course_date ) ) : ?>
                                        <span class="course-date"><?php echo get_the_date(); ?></span>
                                        <?php
                                    endif; ?>
                                </div>
						    </div>
					    </div>
					</div>
				</div>
			<?php
			} ?>

			<div class="bb-grid">
				<div id="lifter-course-content" class="bb-llms-content-wrap">
					<?php
					if ( is_singular() && ! is_related_posts() ) {
						the_content(
							sprintf(
								wp_kses(
									/* translators: %s: Name of current post. Only visible to screen readers */
									__( 'Continue reading<span class="screen-reader-text"> "%s"</span>', 'buddyboss-theme' ),
									array(
										'span' => array(
											'class' => array(),
										),
									)
								),
								get_the_title()
							)
						);

						?>
                        <div class="lifter-comment">
                        <?php

                            /**
                             * If comments are open or we have at least one comment, load up the comment template.
                             */
                            if ( comments_open() || get_comments_number() ) :
                                comments_template();
                            endif;
                            ?>
                        </div>
                        <?php
					}
					?>
				</div>

				<?php
                    // Single course sidebar
					llms_get_template( 'course/template-single-course-sidebar.php' );
				?>

			</div>
		</div>

		<?php if ( ! is_single() || is_related_posts() ) { ?>
	</div><!--Close '.post-inner-wrap'-->
<?php } ?>

</article><!-- #post-<?php the_ID(); ?> -->
