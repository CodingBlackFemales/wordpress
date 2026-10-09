<?php
/**
 * `wp glossary render`.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Cli;

use CodingBlackFemales\SemanticGlossary\Block\GlossaryBlock;
use CodingBlackFemales\SemanticGlossary\Render\ContentFilter;
use CodingBlackFemales\SemanticGlossary\Render\Options;
use CodingBlackFemales\SemanticGlossary\Render\PostGlossary;
use WP_CLI;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Print a post's front-end HTML, for diffing in tests and checking semantic output without a browser.
 *
 * Renders as the post's own page would: references resolved, and the
 * automatic glossary appended when the site setting applies.
 *
 * ## OPTIONS
 *
 * <post-id>
 * : Post to render.
 *
 * [--glossary-only]
 * : Print only the glossary, using the post's Glossary block settings when it has one.
 *
 * ## EXAMPLES
 *
 *     wp glossary render 123 > lesson.html
 *     wp glossary render 123 --glossary-only
 */
final class RenderCommand {

	/**
	 * Render.
	 *
	 * @param string[]             $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		global $post;

		$post = Posts::one( (int) $args[0] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- the_content() needs the global post.
		setup_postdata( $post );

		if ( ! empty( $assoc_args['glossary-only'] ) ) {
			WP_CLI::line( PostGlossary::render( $post, self::options( $post ), $post->ID ) );
			return;
		}

		ContentFilter::force_main_post( $post->ID );
		WP_CLI::line( (string) apply_filters( 'the_content', $post->post_content ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core hook.
		ContentFilter::force_main_post( 0 );
	}


	/**
	 * Options from the post's first Glossary block, or the defaults.
	 *
	 * @param WP_Post $post Post.
	 */
	private static function options( WP_Post $post ): Options {
		foreach ( parse_blocks( $post->post_content ) as $block ) {
			if ( $block['blockName'] === GlossaryBlock::NAME ) {
				return Options::from_block( $block['attrs'], 'glossary-heading' );
			}
		}

		return Options::from_block( array(), 'glossary-heading' );
	}
}
