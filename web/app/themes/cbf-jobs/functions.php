<?php
/**
 * @package CBF Jobs
 * The parent theme functions are located at /onepress-theme/inc/theme/functions.php
 * Add your own functions at the bottom of this file.
 */


/****************************** THEME SETUP ******************************/

/**
 * Sets up theme for translation
 *
 * @since CBF Jobs 1.0.0
 */
function cbf_jobs_theme_languages() {
	/**
	  * Makes child theme available for translation.
	  * Translations can be added into the /languages/ directory.
	  */

	// Translate text from the PARENT theme.
	load_theme_textdomain( 'onepress-theme', get_stylesheet_directory() . '/languages' );

	// Translate text from the CHILD theme only.
	// Change 'onepress-theme' instances in all child theme files to 'onepress-theme-child'.
	// load_theme_textdomain( 'onepress-theme-child', get_stylesheet_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'cbf_jobs_theme_languages' );

/**
 * Enqueues scripts and styles for child theme front-end.
 *
 * @since CBF Jobs Theme  1.0.0
 */
function cbf_jobs_theme_scripts_styles() {
	/**
	   * Scripts and Styles loaded by the parent theme can be unloaded if needed
	   * using wp_deregister_script or wp_deregister_style.
	   *
	   * See the WordPress Codex for more information about those functions:
	   * http://codex.wordpress.org/Function_Reference/wp_deregister_script
	   * http://codex.wordpress.org/Function_Reference/wp_deregister_style
	   **/
	$version = wp_get_theme( 'cbf-jobs' )->get( 'Version' );

	// Styles
	wp_deregister_style( 'onepress-fa' );
	wp_deregister_style( 'onepress-bootstrap' );
	wp_enqueue_style( 'onepress-fa', 'https://codingblackfemales.com/vendor/fontawesome-free/css/all.min.css', false, $version );
	wp_enqueue_style( 'onepress-bootstrap', 'https://codingblackfemales.com/vendor/bootstrap/css/bootstrap.min.css', false, $version );
	wp_enqueue_style( 'cbf-jobs-css', get_stylesheet_directory_uri() . '/assets/css/custom.css', false, $version );
	wp_enqueue_style( 'cbf-style', 'https://codingblackfemales.com/css/agency.css', false, $version );

	// Javascript
	wp_enqueue_script( 'cbf-jobs-js', get_stylesheet_directory_uri() . '/assets/js/custom.js', false, $version );
}
add_action( 'wp_enqueue_scripts', 'cbf_jobs_theme_scripts_styles', 9999 );


/****************************** CUSTOM FUNCTIONS ******************************/

// Add your own custom functions here
