<?php
/**
 * `wp glossary audit`.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Cli;

use CodingBlackFemales\SemanticGlossary\Audit\Auditor;
use CodingBlackFemales\SemanticGlossary\Entry\Repository;
use CodingBlackFemales\SemanticGlossary\Reference\PostFields;
use WP_CLI;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Report duplicate references, references to deleted entries, and unmarked known terms.
 *
 * `--fix` unmarks later duplicates and strips dead references, keeping their
 * text. It never marks terms: that is an editorial decision.
 *
 * Exits non-zero while duplicate or dead references remain, so CI can gate on
 * it. Unmarked terms are reported but do not fail the run unless --strict.
 *
 * ## OPTIONS
 *
 * [--post=<id>]
 * : Only this post.
 *
 * [--post-type=<type>]
 * : Only posts of this type. Defaults to every indexed type.
 *
 * [--fix]
 * : Unmark later duplicates and strip dead references.
 *
 * [--dry-run]
 * : With --fix, report what would be fixed without saving.
 *
 * [--strict]
 * : Also exit non-zero when unmarked terms are found.
 *
 * [--fields=<fields>]
 * : Comma-separated fields.
 *
 * [--format=<format>]
 * : Output format.
 * ---
 * default: table
 * options:
 *   - table
 *   - json
 *   - csv
 *   - yaml
 *   - ids
 *   - count
 * ---
 *
 * ## EXAMPLES
 *
 *     wp glossary audit --post-type=sfwd-lessons
 *     wp glossary audit --fix --dry-run
 */
final class AuditCommand {

	/**
	 * Default columns.
	 */
	const FIELDS = array( 'post_id', 'post_title', 'issue', 'entry_id', 'text', 'action' );


	/**
	 * Run the audit.
	 *
	 * @param string[]             $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$fix     = ! empty( $assoc_args['fix'] );
		$dry_run = ! empty( $assoc_args['dry-run'] );
		$items   = array();

		foreach ( Posts::select( $assoc_args ) as $post ) {
			$items = array_merge( $items, self::audit_post( $post, $fix, $dry_run ) );
		}

		Output::items( $assoc_args, $items, self::FIELDS );

		if ( self::fails( $items, ! empty( $assoc_args['strict'] ) ) ) {
			WP_CLI::halt( 1 );
		}
	}


	/**
	 * Audit (and optionally fix) one post.
	 *
	 * @param WP_Post $post    Post.
	 * @param bool    $fix     Whether to fix.
	 * @param bool    $dry_run Whether to only report fixes.
	 * @return array<int, array<string, mixed>>
	 */
	private static function audit_post( WP_Post $post, bool $fix, bool $dry_run ): array {
		$findings = Auditor::audit( $post->post_content, Repository::instance(), PostFields::ignored( $post->ID ) );

		if ( $findings === array() ) {
			return array();
		}

		$fixed = $fix && Auditor::has_fixable( $findings ) && ( $dry_run || self::save_fix( $post ) );

		return array_map(
			fn ( array $finding ): array => array(
				'post_id'    => $post->ID,
				'post_title' => $post->post_title,
				'issue'      => $finding['type'],
				'entry_id'   => $finding['entry_id'],
				'text'       => $finding['text'],
				'action'     => self::action( $finding['type'], $fixed, $dry_run ),
			),
			$findings
		);
	}


	/**
	 * Save the fixed content.
	 *
	 * @param WP_Post $post Post.
	 */
	private static function save_fix( WP_Post $post ): bool {
		$result = Auditor::fix( $post->post_content, Repository::instance() );

		$saved = wp_update_post(
			array(
				'ID'           => $post->ID,
				'post_content' => wp_slash( $result['html'] ),
			),
			true
		);

		if ( is_wp_error( $saved ) ) {
			/* translators: 1: post ID, 2: error */
			WP_CLI::warning( sprintf( __( 'Could not fix post %1$d: %2$s', 'cbf-semantic-glossary' ), $post->ID, $saved->get_error_message() ) );
			return false;
		}

		return true;
	}


	/**
	 * What happened to a finding.
	 *
	 * @param string $type    Finding type.
	 * @param bool   $fixed   Whether the post was (or would be) fixed.
	 * @param bool   $dry_run Whether this is a dry run.
	 */
	private static function action( string $type, bool $fixed, bool $dry_run ): string {
		if ( $type === Auditor::UNMARKED || ! $fixed ) {
			return '';
		}

		return $dry_run ? 'would fix' : 'fixed';
	}


	/**
	 * Whether the run should exit non-zero.
	 *
	 * @param array<int, array<string, mixed>> $items  Output rows.
	 * @param bool                             $strict Whether unmarked terms fail the run.
	 */
	private static function fails( array $items, bool $strict ): bool {
		foreach ( $items as $item ) {
			$unfixed = $item['action'] !== 'fixed';
			$counts  = $item['issue'] !== Auditor::UNMARKED || $strict;

			if ( $unfixed && $counts ) {
				return true;
			}
		}

		return false;
	}
}
