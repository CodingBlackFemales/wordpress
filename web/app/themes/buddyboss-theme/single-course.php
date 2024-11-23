<?php
/**
 * The template for displaying single course
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package BuddyBoss_Theme
 */

if ( function_exists( 'buddyboss_is_lifterlms' ) && buddyboss_is_lifterlms() ) {
	get_template_part( 'single-llms', 'course' );
} elseif ( function_exists( 'buddyboss_is_academy' ) && buddyboss_is_academy() ) {
	get_template_part( 'academy/single', 'course' );
} else {
	// This will call for any other post type. i.e - single-course.php
	get_header();
	?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main">

			<?php

			while ( have_posts() ) :
				the_post();

				get_template_part( 'template-parts/content' );

			endwhile; // End of the loop.

			?>

		</main><!-- #main -->
	</div><!-- #primary -->

	<?php
	get_footer();
}
