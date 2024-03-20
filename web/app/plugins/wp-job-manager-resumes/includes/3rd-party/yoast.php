<?php
/**
 * Adds additional compatibility with Yoast SEO.
 *
 * @package wp-job-manager-resumes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Yoast SEO will by default include the `resume` post type because it is flagged as public.

/**
 * Skip resume listings in sitemap generation if the setting is enabled.
 *
 * @param array  $url  Array of URL parts.
 * @param string $type URL type.
 * @param object $post Post object.
 * @return array|bool False if we're skipping
 */
function resume_manager_yoast_discourage_search_index( $url, $type, $post ) {
	if ( 'resume' === $post->post_type ) {
		return false;
	}

	return $url;
}
if ( resume_manager_discourage_resume_search_indexing() ) {
	add_action( 'wpseo_sitemap_entry', 'resume_manager_yoast_discourage_search_index', 10, 3 );
}

/**
 * Removes the webpage graph pieces from the schema collector.
 *
 * @param array  $pieces  The current graph pieces.
 * @param string $context The current context.
 *
 * @return array The remaining graph pieces.
 */
function remove_webpage_from_schema( $pieces, $context ) {
	return array_filter(
		$pieces,
		function( $piece ) {
			return ! $piece instanceof \Yoast\WP\SEO\Generators\Schema\WebPage;
		}
	);
}
