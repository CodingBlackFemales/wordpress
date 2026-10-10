<?php
/**
 * Register the `wp glossary course` commands.
 *
 * @package CodingBlackFemales/SemanticGlossaryLearnDash
 */

namespace CodingBlackFemales\SemanticGlossaryLearnDash\Cli;

use WP_CLI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Commands.
 *
 * Extends core's `wp glossary` namespace. Registered only once core and
 * LearnDash are both active.
 */
final class Commands {

	/**
	 * Register commands. Only called when WP_CLI is defined.
	 */
	public static function register(): void {
		WP_CLI::add_command( 'glossary course', CourseCommand::class );
	}
}
