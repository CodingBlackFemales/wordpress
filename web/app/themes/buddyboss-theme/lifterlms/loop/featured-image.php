<?php
/**
 * Display a Featured Image on the Loop Tile
 *
 * @package LifterLMS/Templates
 *
 * @since  Unknown
 * @version 3.35.0
 */

defined( 'ABSPATH' ) || exit;

global $post;

// short circuit if the featured video tile option is enabled for a course.
if ( 'course' === $post->post_type ) {
	$course = llms_get_post( $post );
	if ( 'yes' === $course->get( 'tile_featured_video' ) && $course->get( 'video_embed' ) ) {
		return;
	}
}
?>

<div class="bb-cover-wrap bb-cover-wrap--llms">
	
	<?php
	if ( is_courses() ) {
		$progress     = buddyboss_theme()->lifterlms_helper()->boss_theme_progress_course( get_the_ID() );
		$status       = __( 'Complete', 'buddyboss-theme' );
		$status_class = ' ld-status-complete';

		if (
			is_nan( $progress ) ||
			0 === $progress
		) {
			$status       = __( 'Start Course', 'buddyboss-theme' );
			$status_class = ' ld-status-progress';
		} elseif ( $progress < 100 ) {
			$status       = __( 'In Progress', 'buddyboss-theme' );
			$status_class = ' ld-status-progress ';
		}
		?>

		<div class="ld-status ld-primary-background <?php echo esc_attr( $status_class ); ?>">
			<?php echo esc_html( $status ); ?>
		</div>

	<?php } ?>

	<?php
	if ( has_post_thumbnail( $post->ID ) ) {
		echo llms_featured_img( $post->ID, 'full' );
	} elseif ( llms_placeholder_img_src() ) {
		echo '';
	}
	?>

</div>