<?php
/**
 * The sidebar containing the main widget area
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package BuddyBoss_Theme
 */

$featured_img_style = buddyboss_theme_get_option( 'blog_featured_img' );

if ( !is_active_sidebar( 'sidebar' ) || ( $featured_img_style == "full-fi" && is_single() )  || ( $featured_img_style == "full-fi-invert" && is_single() ) || wp_job_manager_is_post_type() ) {
	return;
}
?>

<?php if ( function_exists( 'buddyboss_is_woocommerce' ) && ! buddyboss_is_woocommerce() ) { ?>

	<div id="secondary" class="widget-area sm-grid-1-1">
	
		<?php dynamic_sidebar( 'sidebar' ); ?>
	
	</div><!-- #secondary -->

<?php } else { ?>

		<?php if ( is_active_sidebar( 'woo_sidebar' ) ) { ?>
				
			<?php get_sidebar( 'woocommerce' ); ?>

		<?php } ?>

<?php } ?>