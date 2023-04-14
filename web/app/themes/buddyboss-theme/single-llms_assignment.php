<?php
/**
 * The template for displaying single llms assignment
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package BuddyBoss_Theme
 */

get_header();
?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main">

			<?php
            
            while ( have_posts() ) :
				the_post();
				?>
				
				<div id="lifterlms-content" class="container-full">
					<div class="bb-grid grid">
						<?php
							llms_get_template( 'lesson/template-single-lesson-sidebar.php' );

							llms_get_template( 'assignment/template-single-assignment-content.php' );
						?>
					</div>
				</div>
			<?php

			endwhile; // End of the loop.
       
			?>

		</main><!-- #main -->
	</div><!-- #primary -->

<?php
get_footer();
