<?php
/**
 * Hook rendering into post content.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Render;

use CodingBlackFemales\SemanticGlossary\Assets;
use CodingBlackFemales\SemanticGlossary\Entry\Repository;
use CodingBlackFemales\SemanticGlossary\Reference\Scanner;
use CodingBlackFemales\SemanticGlossary\Settings;
use CodingBlackFemales\SemanticGlossary\Utils;
use WP_Post;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ContentFilter.
 *
 * Runs on `the_content` after blocks and shortcodes have rendered (priority
 * 20), so it sees the final HTML in document order. That is what "first
 * mention" is measured against.
 */
final class ContentFilter {

	/**
	 * Style handle; themes can dequeue it.
	 */
	const STYLE = 'cbf-semantic-glossary';

	/**
	 * Priority on `the_content`; after do_blocks (9), wpautop (10) and do_shortcode (11).
	 */
	const PRIORITY = 20;

	/**
	 * Post treated as the main content regardless of the query, for WP-CLI.
	 *
	 * @var int
	 */
	private static $forced_post_id = 0;


	/**
	 * Register hooks.
	 */
	public static function hooks(): void {
		add_action( 'init', array( __CLASS__, 'register_style' ) );
		add_filter( 'the_content', array( __CLASS__, 'filter' ), self::PRIORITY );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_enqueue' ) );
	}


	/**
	 * Register the front-end stylesheet.
	 *
	 * Registered on init (not only when needed) so the Glossary block can name
	 * it in block.json and the editor canvas loads it too.
	 */
	public static function register_style(): void {
		Assets::register_style( self::STYLE, 'css/frontend/cbf-semantic-glossary.css' );
	}


	/**
	 * Render references, and append a glossary when the site asks for one.
	 *
	 * @param string $content Rendered post content.
	 */
	public static function filter( $content ) {
		if ( ! is_string( $content ) ) {
			return $content;
		}

		$post   = get_post();
		$append = $post instanceof WP_Post && self::should_append( $post );

		if ( ! $append && stripos( $content, Scanner::CLASS_NAME ) === false ) {
			return $content;
		}

		$content = InlineRenderer::render( $content, Repository::instance() );

		if ( $append ) {
			$content .= PostGlossary::render( $post, Options::from_block( array(), 'glossary-heading' ), $post->ID );
		}

		wp_enqueue_style( self::STYLE );

		return $content;
	}


	/**
	 * Enqueue the stylesheet in the head when the page will need it.
	 *
	 * filter() also enqueues it, which covers content rendered outside the main
	 * post (it is then printed in the footer).
	 */
	public static function maybe_enqueue(): void {
		$post = is_singular() ? get_queried_object() : null;

		if ( $post instanceof WP_Post && PostGlossary::has_references( $post ) ) {
			wp_enqueue_style( self::STYLE );
		}
	}


	/**
	 * Treat a post as the main content, for `wp glossary render`.
	 *
	 * @param int $post_id Post ID, or 0 to stop.
	 */
	public static function force_main_post( int $post_id ): void {
		self::$forced_post_id = $post_id;
	}


	/**
	 * Whether to append a glossary to this content.
	 *
	 * Only for the main post of a singular view: not excerpts, not posts in a
	 * list, not content rendered for some other purpose. And only when the post
	 * references something but places no glossary itself.
	 *
	 * @param WP_Post $post Post being rendered.
	 */
	private static function should_append( WP_Post $post ): bool {
		if ( ! Settings::auto_append() || ! self::is_main_post( $post ) ) {
			return false;
		}

		return PostGlossary::has_references( $post ) && ! PostGlossary::has_glossary( $post );
	}


	/**
	 * Whether a post is the page's main content.
	 *
	 * @param WP_Post $post Post being rendered.
	 */
	private static function is_main_post( WP_Post $post ): bool {
		if ( self::$forced_post_id !== 0 ) {
			return self::$forced_post_id === $post->ID;
		}

		if ( doing_filter( 'get_the_excerpt' ) || doing_filter( 'wp_trim_excerpt' ) || Utils::is_request( 'admin' ) ) {
			return false;
		}

		return is_singular() && get_queried_object_id() === $post->ID;
	}
}
