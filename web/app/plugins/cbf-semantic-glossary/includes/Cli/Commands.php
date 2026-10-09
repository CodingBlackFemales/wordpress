<?php
/**
 * Register the `wp glossary` commands.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Cli;

use WP_CLI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Commands.
 *
 * Every list or get command takes `--format=table|json|csv|yaml|ids` and
 * `--fields`; every mutating command takes `--dry-run`. Validation failures
 * exit non-zero, so the commands are safe to run from CI and migrations.
 */
final class Commands {

	/**
	 * Register commands. Only called when WP_CLI is defined.
	 */
	public static function register(): void {
		WP_CLI::add_command( 'glossary term', TermCommand::class );
		WP_CLI::add_command( 'glossary refs', RefsCommand::class );
		WP_CLI::add_command( 'glossary audit', AuditCommand::class );
		WP_CLI::add_command( 'glossary block', BlockCommand::class );
		WP_CLI::add_command( 'glossary render', RenderCommand::class );
	}
}
