<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @link    https://codex.wordpress.org/Creating_an_Error_404_Page
 *
 * @package BuddyBoss_Theme
 */
get_header();

$title               = sprintf( _x( '%s', '404 page title', 'buddyboss-theme' ), buddyboss_theme_get_option( '404_title' ) );
$desc                = sprintf( _x( '%s', '404 page description', 'buddyboss-theme' ), buddyboss_theme_get_option( '404_desc' ) );
$img                 = buddyboss_theme_get_option( '404_image' );
$featured_image_type = buddyboss_theme_get_option( '404_featured_image' );
$button_check        = buddyboss_theme_get_option( '404_button_switch' );
$button_text         = sprintf( _x( '%s', '404 page button text', 'buddyboss-theme' ), buddyboss_theme_get_option( '404_button_text' ) );
$button_link         = buddyboss_theme_get_option( '404_button_link' );
?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main">

			<section class="error-404 not-found text-center">
				<header class="page-header">
					<h1 class="page-title"><?php echo wp_kses_post( $title ); ?></h1>
					<p class="desc"><?php echo wp_kses_post( $desc ); ?></p>
				</header><!-- .page-header -->

				<div class="page-content">
					<?php
					if ( ! empty( $featured_image_type ) && 'theme_2_0' === $featured_image_type ) {
						?>
						<figure class="bb-img-404 bb-img-404--theme-2-0">
							<?php echo bb_theme_get_404_svg_code( 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</figure>
						<?php
					} elseif ( ! empty( $featured_image_type ) && 'theme_1_0' === $featured_image_type ) {
						?>
						<figure class="bb-img-404  bb-img-404--theme-1-0">
							<?php echo '<img src="' . get_template_directory_uri() . '/assets/images/svg/404-img.svg" alt="404" />'; ?>
						</figure>
						<?php
					} elseif ( ! empty( $featured_image_type ) && 'custom' === $featured_image_type && is_array( $img ) && isset( $img['url'] ) ) {
						?>
						<figure class="bb-img-404 bb-img-404--custom">
							<img src="<?php echo esc_url( $img['url'] ); ?>" alt="<?php echo isset( $img['alt'] ) ? esc_attr( $img['alt'] ) : ''; ?>" />
						</figure>
						<?php

					}
					if ( $button_check && ( ! empty( $button_text ) || ! empty( $button_link ) ) ) {
						echo '<p><a class="button" href="' . esc_url( $button_link ) . '">' . wp_kses_post( $button_text ) . '</a></p>';
					}
					?>
				</div><!-- .page-content -->
			</section><!-- .error-404 -->

		</main><!-- #main -->
	</div><!-- #primary -->

<?php
get_footer();
