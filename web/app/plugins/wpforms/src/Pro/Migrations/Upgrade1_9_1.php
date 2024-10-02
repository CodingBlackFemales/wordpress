<?php

namespace WPForms\Pro\Migrations;

use WPForms\Migrations\UpgradeBase;
use WPForms\Helpers\File;
use WPForms\Pro\Robots;

/**
 * Class upgrade for 1.9.1 release.
 *
 * @since 1.9.1
 */
class Upgrade1_9_1 extends UpgradeBase {

	/**
	 * The "Disallow" line of WPForms rule.
	 *
	 * @since 1.9.1
	 *
	 * @var string
	 */
	private $disallow_line = '';

	/**
	 * Path to the robots.txt file.
	 *
	 * @since 1.9.1
	 *
	 * @var string
	 */
	const ROBOTS_TXT_PATH = ABSPATH . 'robots.txt';

	/**
	 * Attempt to resolve a possible issue with the physical robots.txt file.
	 *
	 * @since 1.9.1
	 *
	 * @return bool|null Upgrade result:
	 *                    true - the upgrade completed successfully,
	 *                    false - in the case of failure,
	 *                    null - upgrade started but not yet finished (background task).
	 */
	public function run() {

		$this->run_robots_migration();

		return true;
	}

	/**
	 * Attempt to resolve a possible issue with the physical robots.txt file.
	 *
	 * @since 1.9.1
	 *
	 * @return void
	 */
	private function run_robots_migration() { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		// There is no physical robots.txt file.
		// Complete the migration silently.
		if ( ! File::exists( self::ROBOTS_TXT_PATH ) ) {
			return;
		}

		// Get the robots.txt content.
		$content = File::get_contents( self::ROBOTS_TXT_PATH );

		// The file cannot be read.
		if ( $content === false ) {
			wpforms_log(
				'robots.txt',
				'The file could not be read. This is usually due to file permissions.',
				[
					'type'  => 'log',
					'force' => true,
				]
			);

			return;
		}

		// The file is empty.
		// Complete the migration silently.
		if ( empty( $content ) ) {
			return;
		}

		$robots_instance = new Robots();

		// Everything is okay.
		// The User-agent line exists above the Disallow line.
		if ( strpos( $content, $robots_instance->get_rule_block( false ) ) !== false ) {
			return;
		}

		$this->disallow_line = 'Disallow: ' . $robots_instance->get_upload_root();

		/**
		 * Caused by third-party plugin.
		 * There are a couple of cases when the WPForms rule makes the robots.txt file invalid.
		 * We attempt to resolve them through this migration.
		 */

		// 1) The Disallow line is placed in the first line on the file.
		if ( strpos( $content, $this->disallow_line ) === 0 ) {
			$success = $this->replace_rule( ltrim( $robots_instance->get_rule_block(), PHP_EOL ), $content );

			wpforms_log(
				'robots.txt',
				$success ? 'WPForms rule block has been corrected.' : 'WPForms rule block could not be corrected.',
				[
					'type'  => 'log',
					'force' => true,
				]
			);

			return;
		}

		// This is a content of the line above the Disallow line.
		$above_line = $this->get_above_disallow_line( $content );

		// 2) The line above the Disallow line is empty, or it's a comment.
		if (
			is_string( $above_line ) &&
			( $above_line === '' || strpos( $above_line, '#' ) === 0 )
		) {
			$success = $this->replace_rule( $robots_instance->get_rule_block(), $content );

			wpforms_log(
				'robots.txt',
				$success ? 'WPForms rule block has been corrected.' : 'WPForms rule block could not be corrected.',
				[
					'type'  => 'log',
					'force' => true,
				]
			);
		}
	}

	/**
	 * Replace a single WPForms disallow line by the whole rule block.
	 *
	 * @since 1.9.1
	 *
	 * @param string $replace The replacement value that replaces found search values.
	 * @param string $content The string being searched and replaced on.
	 *
	 * @return bool True on success, false on failure.
	 */
	private function replace_rule( string $replace, string $content ): bool {

		// Create a backup for the current version of the robots.txt file.
		// Better safe than sorry.
		File::copy( self::ROBOTS_TXT_PATH, ABSPATH . sprintf( 'robots-backup-%s.txt', time() ) );

		// Replace a single disallowed line by the whole rule block.
		$new_content = str_replace( $this->disallow_line, $replace, $content );

		// Update the robots.txt file.
		return File::put_contents( self::ROBOTS_TXT_PATH, $new_content );
	}

	/**
	 * Retrieve a line above the Disallow line.
	 *
	 * @since 1.9.1
	 *
	 * @param string $content File content.
	 *
	 * @return false|string
	 */
	private function get_above_disallow_line( string $content ) {

		$lines                  = array_map( 'trim', explode( PHP_EOL, $content ) );
		$disallow_line_position = array_search( $this->disallow_line, $lines, true );

		// There is no WPForms rule in the robots.txt file.
		// Even though it's an unexpected case, most likely the user decided to remove WPForms rule, for some reason.
		// We respect it and won't do anything in this case.
		if ( ! $disallow_line_position ) {
			return false;
		}

		// This is a content of the line above the Disallow line.
		return $lines[ $disallow_line_position - 1 ];
	}
}
