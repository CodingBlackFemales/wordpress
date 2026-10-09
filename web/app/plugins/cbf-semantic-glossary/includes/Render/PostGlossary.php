<?php
/**
 * A post's glossary.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Render;

use CodingBlackFemales\SemanticGlossary\Block\GlossaryBlock;
use CodingBlackFemales\SemanticGlossary\Entry\Repository;
use CodingBlackFemales\SemanticGlossary\Reference\PostFields;
use CodingBlackFemales\SemanticGlossary\Reference\Scanner;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PostGlossary.
 *
 * Works out which entries a post's glossary lists (everything it marks inline,
 * plus anything added without an inline reference) from the post's content at
 * render time, so a preview or an unsaved index never shows a stale list.
 */
final class PostGlossary {

	/**
	 * Headings rendered so far in this request, to keep their IDs unique.
	 *
	 * @var int
	 */
	private static $count = 0;


	/**
	 * Render a post's glossary.
	 *
	 * @param WP_Post $post            The post whose references are listed.
	 * @param Options $options         Presentation; its heading ID is made unique here.
	 * @param int     $context_post_id The post being viewed. Back-links are only
	 *                                 rendered when it is the same post, since the
	 *                                 references they point at are only on that page.
	 */
	public static function render( WP_Post $post, Options $options, int $context_post_id ): string {
		$options = self::with_unique_heading_id( $options );

		if ( $context_post_id !== $post->ID ) {
			$options = $options->without_back_links();
		}

		$data    = self::entries( $post );
		$context = array(
			'post_id'         => $post->ID,
			'context_post_id' => $context_post_id,
		);

		/**
		 * Filters the entries a post's glossary lists, before ordering.
		 *
		 * @param \CodingBlackFemales\SemanticGlossary\Entry\Entry[] $entries Entries.
		 * @param array<string, mixed>                               $context Render context: post_id, context_post_id.
		 */
		$entries = (array) apply_filters( 'glossary_entries', $data['entries'], $context );

		return GlossaryRenderer::render( GlossaryBuilder::items( $entries, $data['inline_ids'] ), $options, $context );
	}


	/**
	 * Entries a post's glossary lists.
	 *
	 * @param WP_Post $post Post.
	 * @return array{entries: \CodingBlackFemales\SemanticGlossary\Entry\Entry[], inline_ids: int[]}
	 */
	public static function entries( WP_Post $post ): array {
		$inline_ids = Scanner::entry_ids( Scanner::scan( $post->post_content ) );
		$all_ids    = array_values( array_unique( array_merge( $inline_ids, PostFields::extra( $post->ID ) ) ) );

		return array(
			'entries'    => array_values( Repository::instance()->find_many( $all_ids ) ),
			'inline_ids' => $inline_ids,
		);
	}


	/**
	 * Whether a post references anything, inline or glossary-only.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function has_references( WP_Post $post ): bool {
		return Scanner::scan( $post->post_content ) !== array() || PostFields::extra( $post->ID ) !== array();
	}


	/**
	 * Whether a post already places its glossary itself.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function has_glossary( WP_Post $post ): bool {
		return has_block( GlossaryBlock::NAME, $post ) || has_shortcode( $post->post_content, GlossaryBlock::SHORTCODE );
	}


	/**
	 * Give each glossary on a page its own heading ID.
	 *
	 * @param Options $options Presentation.
	 */
	private static function with_unique_heading_id( Options $options ): Options {
		++self::$count;

		if ( self::$count === 1 ) {
			return $options;
		}

		return $options->with_heading_id( $options->heading_id . '-' . self::$count );
	}
}
