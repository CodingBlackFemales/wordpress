<?php
/**
 * Public API for integrations.
 *
 * Loaded by Composer's autoloader, so these are available once the plugin is
 * active. Integrations should still guard with function_exists().
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

use CodingBlackFemales\SemanticGlossary\Reference\Usage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'glossary_get_terms_for_posts' ) ) {
	/**
	 * Glossary entries referenced across some posts.
	 *
	 * Each entry appears once, with the post where it first appears (posts are
	 * walked in the order given) and the form used there. Entries added to a
	 * post's glossary without an inline reference are included with `inline`
	 * false and `text` and `form` null.
	 *
	 * Each item holds: id, slug, anchor, term, abbr, display, forms, definition,
	 * status, post_id, text, form ({term, abbr} or null when the text matched no
	 * form exactly, e.g. an inflection) and inline.
	 *
	 * @param int[] $post_ids Post IDs, in the order that defines "first".
	 * @return array<int, array<string, mixed>>
	 */
	function glossary_get_terms_for_posts( array $post_ids ): array {
		return Usage::terms_for_posts( $post_ids );
	}
}
