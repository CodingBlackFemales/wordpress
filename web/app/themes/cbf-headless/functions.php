<?php
/**
 * @package CBF Headless
 */

/**
 * Theme setup
 */
function headless_redirect_theme_setup() {
	// Register custom menu locations
	register_nav_menus(
		array(
			'primary' => esc_html__( 'Primary Menu', 'cbf-headless' ),
			'footer-menu-1' => esc_html__( 'Footer Menu 1', 'cbf-headless' ),
			'footer-menu-2' => esc_html__( 'Footer Menu 2', 'cbf-headless' ),
			'footer-menu-3' => esc_html__( 'Footer Menu 3', 'cbf-headless' ),
			'footer-menu-4' => esc_html__( 'Footer Menu 4', 'cbf-headless' ),
			'footer-menu-socials' => esc_html__( 'Footer Socials', 'cbf-headless' ),
		)
	);
}

/**
 * Redirect all front-end requests to the front-end URL
 */
function headless_redirect_all_requests() {
	if ( ! is_admin() ) { // Make sure we don't redirect admin area requests
		wp_redirect( CBF_FRONTEND_URL, 301 ); // Redirect with a 301 Moved Permanently HTTP status code

		exit;
	}
}

add_action( 'after_setup_theme', 'headless_redirect_theme_setup' );
add_action( 'template_redirect', 'headless_redirect_all_requests' );
