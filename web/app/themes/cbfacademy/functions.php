<?php
/**
 * @package CBF Academy
 * The parent theme functions are located at /buddyboss-theme/inc/theme/functions.php
 * Add your own functions at the bottom of this file.
 */


/****************************** THEME SETUP ******************************/

/**
 * Sets up theme for translation
 *
 * @since CBF Academy 1.0.0
 */
function cbfacademy_theme_languages()
{
  /**
   * Makes child theme available for translation.
   * Translations can be added into the /languages/ directory.
   */

  // Translate text from the PARENT theme.
  load_theme_textdomain( 'buddyboss-theme', get_stylesheet_directory() . '/languages' );

  // Translate text from the CHILD theme only.
  // Change 'buddyboss-theme' instances in all child theme files to 'buddyboss-theme-child'.
  // load_theme_textdomain( 'buddyboss-theme-child', get_stylesheet_directory() . '/languages' );

}
add_action( 'after_setup_theme', 'cbfacademy_theme_languages' );

/**
 * Enqueues scripts and styles for child theme front-end.
 *
 * @since CBF Academy Theme  1.0.0
 */
function cbfacademy_theme_scripts_styles()
{
  /**
   * Scripts and Styles loaded by the parent theme can be unloaded if needed
   * using wp_deregister_script or wp_deregister_style.
   *
   * See the WordPress Codex for more information about those functions:
   * http://codex.wordpress.org/Function_Reference/wp_deregister_script
   * http://codex.wordpress.org/Function_Reference/wp_deregister_style
   **/

  // Styles
  wp_enqueue_style( 'cbfacademy-css', get_stylesheet_directory_uri().'/assets/css/custom.css' );

  // Javascript
  wp_enqueue_script( 'cbfacademy-js', get_stylesheet_directory_uri().'/assets/js/custom.js' );
}
add_action( 'wp_enqueue_scripts', 'cbfacademy_theme_scripts_styles', 9999 );


/****************************** CUSTOM FUNCTIONS ******************************/

// Add your own custom functions here

/**
 * Sets the correct database table prefix for BuddyBoss, based on the current subsite
 *
 * @param  string $base_prefix The base table prefix (e.g. `wp_`).
 * @return string
 */
function cbfacademy_bp_core_get_table_prefix( $base_prefix ) {
  if(is_multisite()){
    $blog_id = get_current_blog_id();
    $base_prefix .= "{$blog_id}_";
  }

  return $base_prefix;
}
add_filter( 'bp_core_get_table_prefix', 'cbfacademy_bp_core_get_table_prefix' );

?>