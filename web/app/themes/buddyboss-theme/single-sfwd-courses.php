<?php
/**
 * The template for displaying single sfwd course
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
	if ( have_posts() ) :
		
		do_action( THEME_HOOK_PREFIX . '_template_parts_content_top' );

		while ( have_posts() ) :
			the_post();

			if ( 'draft' == get_post_status() ){
				do_action( THEME_HOOK_PREFIX . '_single_template_part_content', get_post_type() );
			} else {
				the_content();

				// If comments are open or we have at least one comment, load up the comment template.
				if ( comments_open() || get_comments_number() ) :
					comments_template();
				endif;
			}

		endwhile; // End of the loop.
	endif;
	?>

	</main><!-- #main -->
</div><!-- #primary -->

<?php get_footer();
