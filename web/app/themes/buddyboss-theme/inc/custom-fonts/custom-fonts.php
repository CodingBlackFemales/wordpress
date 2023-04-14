<?php
/**
 * Exit if accessed directly.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

require_once( get_template_directory() . '/inc/custom-fonts/classes/class-buddyboss-custom-fonts.php' );

/**
 *  BuddyBoss Theme Custom Fonts
 */
function buddyboss_theme_custom_fonts() {
	return \BuddyBossTheme\BuddyBoss_Custom_Fonts::get_instance();
}

buddyboss_theme_custom_fonts();
