<?php
/**
 * The Glossary block and its shortcode.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Block;

use CodingBlackFemales\SemanticGlossary\Render\Options;
use CodingBlackFemales\SemanticGlossary\Render\PostGlossary;
use CodingBlackFemales\SemanticGlossary\Utils;
use WP_Block;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GlossaryBlock.
 *
 * Placing the block is how an editor positions the glossary; there is no
 * separate "insert glossary" setting. The block is dynamic: it saves nothing
 * but its settings and is rendered from the post's references every time.
 */
final class GlossaryBlock {

	/**
	 * Block name.
	 */
	const NAME = 'cbf/glossary';

	/**
	 * Shortcode, for the Classic editor.
	 */
	const SHORTCODE = 'glossary';


	/**
	 * Register hooks.
	 */
	public static function hooks(): void {
		// After ContentFilter registers the stylesheet the block names.
		add_action( 'init', array( __CLASS__, 'register' ), 20 );
	}


	/**
	 * Register the block and the shortcode.
	 */
	public static function register(): void {
		register_block_type(
			Utils::plugin_path() . '/blocks/glossary',
			array( 'render_callback' => array( __CLASS__, 'render' ) )
		);

		add_shortcode( self::SHORTCODE, array( __CLASS__, 'shortcode' ) );
	}


	/**
	 * Block render callback.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param string               $content    Inner content (none).
	 * @param WP_Block|null        $block      Block instance.
	 */
	public static function render( array $attributes, string $content = '', $block = null ): string {
		$post_id = $block instanceof WP_Block && ! empty( $block->context['postId'] ) ? (int) $block->context['postId'] : (int) get_the_ID();
		$post    = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			return '';
		}

		$html = PostGlossary::render( $post, Options::from_block( $attributes, 'glossary-heading' ), (int) get_the_ID() );

		if ( $html === '' ) {
			return '';
		}

		return '<div ' . get_block_wrapper_attributes() . '>' . $html . '</div>';
	}


	/**
	 * `[glossary heading="Glossary" level="3"]`.
	 *
	 * Also accepts alternatives, backlinks and index (yes/no).
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public static function shortcode( $atts ): string {
		$post = get_post();

		if ( ! $post instanceof WP_Post ) {
			return '';
		}

		return PostGlossary::render( $post, Options::from_shortcode( $atts, 'glossary-heading' ), $post->ID );
	}
}
