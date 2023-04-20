<?php
/**
 * Customizations to wp editor for LearnDash
 *
 * All functions currently are customizations for custom certificate implementations
 *
 * @since 2.1.0
 *
 * @package LearnDash\TinyMCE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds hooks for TinyMCE customization on edit screen.
 *
 * Fires on `load-post.php` and `load-post-new.php` hook.
 *
 * @since 2.1.0
 */
function learndash_mce_init() {
	$screen = get_current_screen();
	if ( ( $screen ) && ( $screen instanceof WP_Screen ) ) {
		if ( ( 'post' === $screen->base ) && ( 'sfwd-certificates' === $screen->post_type ) ) {
			add_filter( 'tiny_mce_before_init', 'learndash_wp_tiny_mce_before_init' );
			add_filter( 'mce_css', 'learndash_filter_mce_css' );
		}
	}
}

/**
 * Changes hook in LD v2.3 to only hook into the load of post.php and post-new.php
 */
add_action( 'load-post.php', 'learndash_mce_init' );
add_action( 'load-post-new.php', 'learndash_mce_init' );

/**
 * Loads editor styles for LearnDash.
 *
 * Fires on `mce_css` hook.
 * We need to add the LD custom CSS to the function parameter. Not replace it
 * see https://codex.wordpress.org/Plugin_API/Filter_Reference/mce_css
 *
 * @since 2.1.0
 *
 * @param string $mce_css Optional. Comma-delimited list of stylesheets. Default empty.
 *
 * @return string Comma-delimited list of stylesheets.
 */
function learndash_filter_mce_css( $mce_css = '' ) {
	$ld_mce_css = plugins_url( 'assets/css/sfwd_editor.css', LEARNDASH_LMS_PLUGIN_DIR . 'index.php' );
	if ( ! empty( $ld_mce_css ) ) {
		if ( ! empty( $mce_css ) ) {
			$mce_css .= ',';
		}
		$mce_css .= $ld_mce_css;
	}
	return $mce_css;
}

/**
 * Loads the certificate image as the background for the visual editor.
 *
 * Fires on `tiny_mce_before_init` hook.
 *
 * @todo  confirm intent of function and if it's still needed
 *        not currently functional
 *
 * @since 2.1.0
 *
 * @param array $init_array The tinymce editor settings.
 *
 * @return array The tinymce editor settings.
 */
function learndash_wp_tiny_mce_before_init( $init_array ) {
	if ( isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post_id = absint( $_GET['post'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	} else {
		$post_id = get_the_id();
	}

	if ( ! empty( $post_id ) ) {
		$img_path = learndash_get_thumb_url( $post_id );
		if ( ! empty( $img_path ) ) {
			$init_array['setup'] = <<<JS
[function(ed) {
    ed.onInit.add(function(ed, e) {
		var w = jQuery("#content_ifr").width();
		var editorId = ed.getParam("fullscreen_editor_id") || ed.id;
		jQuery("#content_ifr").contents().find("#tinymce").css
		({"background-image":"url($img_path)"
		});

		if(editorId == 'wp_mce_fullscreen'){
		jQuery("#wp_mce_fullscreen_ifr").contents().find("#tinymce").css
		({"background-image":"url($img_path)"
		});
		}
    });

}][0]
JS;
		}
	}
	return $init_array;
}



/**
 * Gets the featured image URL for the post.
 *
 * @since 2.1.0
 *
 * @param int    $post_id Optional. Post ID. Default 0.
 * @param string $size    Optional. The size of the image. Default 'full'.
 *
 * @return string The featured image url.
 */
function learndash_get_thumb_url( $post_id = 0, $size = 'full' ) {

	if ( ( ! empty( $post_id ) ) && ( has_post_thumbnail( $post_id ) ) ) {
		$image_src_array = wp_get_attachment_image_src( get_post_thumbnail_id(), $size );
		if ( ( ! empty( $image_src_array ) ) && ( is_array( $image_src_array ) ) && ( ! empty( $image_src_array[0] ) ) ) {
			return $image_src_array[0];
		}
	}
	return '';
}
