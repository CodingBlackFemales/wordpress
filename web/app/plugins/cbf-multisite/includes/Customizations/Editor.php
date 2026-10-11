<?php
/**
 * Classic editor fixes
 *
 * @package     CodingBlackFemales/Multisite/Customizations
 * @version     1.0.0
 */

namespace CodingBlackFemales\Multisite\Customizations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom classic editor class.
 */
class Editor {
	/**
	 * Hook in methods.
	 *
	 * @return void
	 */
	public static function hooks() {
		add_action( 'wp_enqueue_editor', array( __CLASS__, 'enqueue_shortcode_script' ) );
	}

	/**
	 * Loads `wp.shortcode` wherever TinyMCE is loaded.
	 *
	 * Core's `editor` script calls `wp.shortcode.regexp()` when switching to the
	 * Visual tab, but doesn't declare `shortcode` as a dependency. In wp-admin it
	 * arrives via `media-upload`; front-end editors without a media library, such
	 * as BuddyPages, throw a TypeError for any content containing a shortcode.
	 *
	 * @param array $to_load Whether TinyMCE and Quicktags are being loaded.
	 * @return void
	 */
	public static function enqueue_shortcode_script( $to_load ) {
		if ( ! empty( $to_load['tinymce'] ) ) {
			wp_enqueue_script( 'shortcode' );
		}
	}
}
