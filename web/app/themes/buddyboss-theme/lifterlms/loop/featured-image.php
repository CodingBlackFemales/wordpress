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

// short circuit if the featured video tile option is enabled for a course
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
		$status       = "Complete";
		$status_class = " ld-status-complete";

		if ( is_nan( $progress ) || ( $progress == 0 ) ) {
			$status       = "Start Course";
			$status_class = " ld-status-progress";
		} else {
			if ( $progress < 100 ):
				$status       = "In Progress";
				$status_class = " ld-status-progress ";
			endif;
		}
		?>

		<div class="ld-status ld-primary-background <?php echo $status_class; ?>"><?php echo sprintf( __( '%s', 'buddyboss-theme' ), $status ); ?></div>

	<?php } ?>

	<?php
	if ( has_post_thumbnail( $post->ID ) ) {
		echo llms_featured_img( $post->ID, 'full' );
	} elseif ( llms_placeholder_img_src() ) {
		echo '';
	}
	?>

</div>