<?php
/**
 * Template banner override
 */
use LearnDash\Core\Models\Product;

$course_cover_photo = false;
if ( class_exists( '\BuddyBossTheme\BuddyBossMultiPostThumbnails' ) ) {
	$course_cover_photo = \BuddyBossTheme\BuddyBossMultiPostThumbnails::get_post_thumbnail_url(
		'sfwd-courses',
		'course-cover-image'
	);
}

$course        = get_post( $course_id );
$has_access    = sfwd_lms_has_access( $course_id, get_current_user_id() );
$lessons       = learndash_get_course_lessons_list( $course_id );
$lessons       = array_column( $lessons, 'post' );
$ld_permalinks = get_option( 'learndash_settings_permalinks', array() );
$course_slug   = isset( $ld_permalinks['courses'] ) ? $ld_permalinks['courses'] : 'courses';
$product = Product::find( $course_id );

?>
<div class="bb-vw-container bb-learndash-banner">

	<?php if ( ! empty( $course_cover_photo ) ) { ?>
		<img src="<?php echo $course_cover_photo; ?>" alt="<?php the_title_attribute( array( 'post' => $course_id ) ); ?>"
			 class="banner-img wp-post-image"/>
	<?php } ?>

	<div class="bb-course-banner-info container bb-learndash-side-area">
		<div class="flex flex-wrap">
			<div class="bb-course-banner-inner">
				<?php
				if ( taxonomy_exists( 'ld_course_category' ) ) {
					// category.
					$course_cats = get_the_terms( $course->ID, 'ld_course_category' );
					if ( ! empty( $course_cats ) ) {
						?>
						<div class="bb-course-category">
							<?php foreach ( $course_cats as $course_cat ) { ?>
								<span class="course-category-item">
									<a title="<?php echo $course_cat->name; ?>" href="<?php printf( '%s/%s/?search=&filter-categories=%s', home_url(), $course_slug, $course_cat->slug ); ?>">
										<?php echo $course_cat->name; ?>
									</a>
									<span>,</span>
								</span>
							<?php } ?>
						</div>
						<?php
					}
				}
				?>
				<h1 class="entry-title"><?php echo get_the_title( $course_id ); ?></h1>

				<?php if ( has_excerpt( $course_id ) ) { ?>
					<div class="bb-course-excerpt">
						<?php echo get_the_excerpt( $course_id ); ?>
					</div>
				<?php } ?>

				<div class="bb-course-points">
					<a class="anchor-course-points" href="#learndash-course-content">
						<?php echo sprintf( esc_html_x( 'View %s details', 'link: View Course details', 'buddyboss-theme' ), LearnDash_Custom_Label::get_label( 'course' ) ); ?>
						<i class="bb-icon-l bb-icon-angle-down"></i>
					</a>
				</div>

				<?php
				if ( buddyboss_theme_get_option( 'learndash_course_author' ) || buddyboss_theme_get_option( 'learndash_course_date' ) ) {
					$bb_single_meta_pfx = 'bb_single_meta_pfx';
				} else {
					$bb_single_meta_pfx = 'bb_single_meta_off';
				}
				?>

				<div class="bb-course-single-meta flex align-items-center <?php echo $bb_single_meta_pfx; ?>">
					<?php
					if ( buddyboss_theme_get_option( 'learndash_course_author' ) ) {
						if ( class_exists( 'BuddyPress' ) ) {
							?>
							<a href="<?php echo bp_core_get_user_domain( $course->post_author ); ?>">
							<?php
						} else {
							?>
							<a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID', $course->post_author ), get_the_author_meta( 'user_nicename', $course->post_author ) ); ?>">
							<?php
						}
							echo get_avatar( get_the_author_meta( 'email', $course->post_author ), 80 );
						?>
							<span class="author-name"><?php the_author(); ?></span>
						</a>
						<?php
					}

					if ( buddyboss_theme_get_option( 'learndash_course_date' ) ) {
						$course_date = get_the_date();
					}

					if ( $product ) {
						$start_date = $product->get_start_date();
						$end_date = $product->get_end_date();

						if ( $start_date && $end_date ) {
							$date_format = get_option( 'date_format' );
							$course_date = wp_date( $date_format, $start_date ) . ' &mdash; ' . wp_date( $date_format, $end_date );
						}
					}

					if ( $course_date ) {
						?>
						<span class="meta-saperator">&middot;</span>
						<span class="course-date"><?php echo $course_date; ?></span>
						<?php
					}
					?>
				</div>

			</div>
		</div>
	</div>
</div>
