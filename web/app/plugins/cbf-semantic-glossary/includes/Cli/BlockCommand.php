<?php
/**
 * `wp glossary block`.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Cli;

use CodingBlackFemales\SemanticGlossary\Block\GlossaryBlock;
use CodingBlackFemales\SemanticGlossary\Render\PostGlossary;
use WP_CLI;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manage Glossary blocks in posts.
 */
final class BlockCommand {

	/**
	 * Append a Glossary block to posts that reference terms but have none.
	 *
	 * The bulk equivalent of the "Automatic glossary" setting, for sites that
	 * would rather have the block in the content. Posts without block markup
	 * get the `[glossary]` shortcode instead.
	 *
	 * ## OPTIONS
	 *
	 * [--post-type=<type>]
	 * : Only posts of this type.
	 *
	 * [--dry-run]
	 * : List the posts that would change without saving.
	 *
	 * ## EXAMPLES
	 *
	 *     wp glossary block ensure --post-type=sfwd-lessons --dry-run
	 *
	 * @param string[]             $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function ensure( array $args, array $assoc_args ): void {
		$dry_run = ! empty( $assoc_args['dry-run'] );
		$changed = 0;

		foreach ( Posts::with_references( array( 'post-type' => $assoc_args['post-type'] ?? '' ) ) as $post ) {
			if ( PostGlossary::has_glossary( $post ) ) {
				continue;
			}

			self::append( $post, $dry_run );
			++$changed;
		}

		WP_CLI::success(
			sprintf(
				/* translators: 1: prefix ("Dry run: " or nothing), 2: number of posts */
				_n( '%1$s%2$d post updated.', '%1$s%2$d posts updated.', $changed, 'cbf-semantic-glossary' ),
				$dry_run ? __( 'Dry run: ', 'cbf-semantic-glossary' ) : '',
				$changed
			)
		);
	}


	/**
	 * Append the block, or the shortcode to classic content.
	 *
	 * @param WP_Post $post    Post.
	 * @param bool    $dry_run Whether to only report.
	 */
	private static function append( WP_Post $post, bool $dry_run ): void {
		/* translators: 1: post ID, 2: post title */
		WP_CLI::log( sprintf( __( 'Post %1$d: %2$s', 'cbf-semantic-glossary' ), $post->ID, $post->post_title ) );

		if ( $dry_run ) {
			return;
		}

		$addition = has_blocks( $post->post_content )
			? "\n\n<!-- wp:" . GlossaryBlock::NAME . ' /-->'
			: "\n\n[" . GlossaryBlock::SHORTCODE . ']';

		Output::or_error(
			wp_update_post(
				array(
					'ID'           => $post->ID,
					'post_content' => wp_slash( rtrim( $post->post_content ) . $addition ),
				),
				true
			)
		);
	}
}
