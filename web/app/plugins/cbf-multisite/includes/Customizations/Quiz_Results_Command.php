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
 * Retrieves and exports Learndash quiz results to Airtable.
 *
 * ## EXAMPLES
 *
 *     # List all quiz results
 *     $ wp cbf quiz_results list
 *     Success: Retrieved 12 quiz activities from Learndash.
 *
 *     # Export recent quiz results to Airtable
 *     $ wp cbf quiz_results export
 *     Success: Exported 12 quiz activities to Airtable.
 */
class Quiz_Results_Command extends \WP_CLI_Command {
	/**
	 * Exports Learndash quiz results to Airtable.
	 *
	 * ## EXAMPLES
	 *
	 *     # Export recent quiz results to Airtable
	 *     $ wp cbf quiz_results export
	 *     Success: Exported 12 quiz activities to Airtable.
	 *
	 * ## OPTIONS
	 *
	 * [--verbose]
	 * : Shows verbose output.
	 */
	public function export( $args, $assoc_args ) {
		$verbose = array_key_exists( 'verbose', $assoc_args );

		if ( ! empty( $args ) ) {
			WP_CLI::error( 'Command syntax: wp cbf export-quiz-results' );
		} else {
			$results = $this->get_results( $verbose, fn() => LearnDash::get_results() );
			$responses = $this->get_results( $verbose, fn() => Airtable::insert_quiz_activities( $results ) );

			WP_CLI::success( 'Exported ' . count( $responses ) . ' quiz activities to Airtable.' );
		}
	}

	/**
	 * Lists Learndash quiz results.
	 *
	 * ## EXAMPLES
	 *
	 *     # List all quiz results
	 *     $ wp cbf quiz_results list
	 *     Success: Retrieved 12 quiz activities from Learndash.
	 *
	 * ## OPTIONS
	 *
	 * [--verbose]
	 * : Shows verbose output.
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$verbose = array_key_exists( 'verbose', $assoc_args );

		if ( ! empty( $args ) ) {
			WP_CLI::error( 'Command syntax: wp cbf export-quiz-results' );
		} else {
			$results = $this->get_results( $verbose, fn() => LearnDash::get_results() );

			WP_CLI::success( 'Retrieved ' . count( $results ) . ' quiz activities from Learndash.' );
		}
	}

	/**
	 * Returns iterable results of a function call.
	 * If verbose mode is enabled, iterates through array and displays each element
	 */
	protected function get_results( $verbose, callable $func ) {
		$results = $func();

		if ( $verbose ) {
			foreach ( $results as $result ) {
				WP_CLI::line( print_r( $result ) );
			}
		}

		return $results;
	}

	/**
	 * Hook in methods.
	 */
	public static function hooks() {
		if ( function_exists( 'learndash_get_report_user_ids' ) ) {
			WP_CLI::add_command( 'cbf quiz_results', self::class );
		}
	}
}
