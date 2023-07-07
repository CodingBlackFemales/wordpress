<?php
/**
 * WP-CLI integration
 *
 * @package     CodingBlackFemales/Multisite/Customizations
 * @version     1.0.0
 */

namespace CodingBlackFemales\Multisite\Customizations;
use WP_CLI;

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) ) {
	exit;
}

/**
 * Custom WP-CLI integration class.
 */
/**
 * Just a few sample commands to learn how WP-CLI works
 */
class WP_CLI_Command extends \WP_CLI_Command {
	/**
	 * Exports quiz results to Airtable.
	 * ## OPTIONS
	 *
	 * [--wponly]
	 * : Shows only WP version info, omitting the plugin one.
	 */
	public function export_quiz_results( $args, $assoc_args ) {
		if ( ! empty( $args ) ) {
			WP_CLI::error( 'Command syntax: wp cbf export-quiz-results' );
		} else {
			$courses = LearnDash::get_results();

			WP_CLI::line( implode( ',', $courses[0] ) );
		}
	}

	/**
	 * Hook in methods.
	 */
	public static function hooks() {
		if ( function_exists( 'learndash_get_report_user_ids' ) ) {
			WP_CLI::add_command( 'cbf', 'CodingBlackFemales\Multisite\Customizations\WP_CLI_Command' );
		}
	}
}
