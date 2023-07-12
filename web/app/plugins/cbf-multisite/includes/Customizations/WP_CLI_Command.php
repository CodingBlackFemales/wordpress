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
	 * [--verbose]
	 * : Shows verbose output.
	 */
	public function export_quiz_results( $args, $assoc_args ) {
		$verbose = array_key_exists( 'verbose', $assoc_args );

		if ( ! empty( $args ) ) {
			WP_CLI::error( 'Command syntax: wp cbf export-quiz-results' );
		} else {
			$results = LearnDash::get_results();

			if ( $verbose ) {
				foreach ( $results as $result ) {
					WP_CLI::line( print_r( $result ) );
				}
			}

			WP_CLI::line( 'Retrieved ' . count( $results ) . ' quiz activities from Learndash.' );

			$responses = Airtable::insert_quiz_activities( $results );

			if ( $verbose ) {
				foreach ( $responses as $response ) {
					WP_CLI::line( print_r( $response ) );
				}
			}

			WP_CLI::line( 'Inserted ' . count( $responses ) . ' quiz activities to Airtable.' );
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
