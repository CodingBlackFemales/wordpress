<?php
/**
 * The template for displaying single sfwd topic
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package BuddyBoss_Theme
 */

get_header();
?>

<div id="primary" class="content-area bb-grid-cell">
	<main id="main" class="site-main">

		<?php
		while ( have_posts() ) :
			the_post();

			do_action( THEME_HOOK_PREFIX . '_single_template_part_content', get_post_type() );

		endwhile; // End of the loop.
		?>

	</main><!-- #main -->
</div><!-- #primary -->

<?php
get_footer();
