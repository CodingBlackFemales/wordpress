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
		$redirect_url = CBF_FRONTEND_URL;

		if ( is_page() ) {
			$post = get_post();
			$redirect_url = build_url( CBF_FRONTEND_URL, $post->post_name );
		}

		wp_redirect( $redirect_url, 301 );

		exit;
	}
}

function build_url( $parts ) {
	if ( ! is_array( $parts ) ) {
		$parts = func_get_args();

		if ( count( $parts ) < 2 ) {
			throw new \RuntimeException( 'build_url() should take array as a single argument or more than one argument' );
		}
	} elseif ( count( $parts ) == 0 ) {
		return '';
	} elseif ( count( $parts ) == 1 ) {
		return $parts[0];
	}

	foreach ( $parts as $path ) {
		$url[] = rtrim( $path, '/' );
	}

	return implode( '/', $url );
}

add_action( 'after_setup_theme', 'headless_redirect_theme_setup' );
add_action( 'template_redirect', 'headless_redirect_all_requests' );
