<?php
/**
 * Fixture and temp-directory helper for the unit suite.
 *
 * @package CodingBlackFemales/SlidesImporter
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SlidesImporter\Tests\Support\Helper;

use Codeception\Module;
use Dotenv\Dotenv;

/**
 * Fixtures module.
 *
 * Gives each test a clean image-output directory (parsers write extracted
 * images to disk) and resolves the committed binary fixtures.
 */
class Fixtures extends Module {

	/**
	 * Setting that points at a directory of real decks and documents.
	 *
	 * The committed fixtures are synthetic and small. Point this at a folder of
	 * genuine CBF files to additionally run the corpus test, which parses
	 * everything it finds and asserts the pipeline neither errors nor warns.
	 *
	 * Read from the environment first, then from a .env file in the plugin root,
	 * so it can be set per-invocation or left configured on a machine. With
	 * neither, CORPUS_DEFAULT_DIR is used if it holds anything.
	 */
	public const CORPUS_ENV = 'CBF_SI_FIXTURE_DIR';

	/**
	 * Where a local corpus is looked for when nothing is configured.
	 *
	 * Relative to the plugin root and gitignored, so real course material can
	 * be dropped there without any risk of committing it.
	 */
	public const CORPUS_DEFAULT_DIR = 'tests/assets';

	/** @var string|null Per-test image output directory. */
	private $image_dir;

	/**
	 * Create a fresh image directory before each test.
	 *
	 * @param \Codeception\TestInterface $test Current test.
	 */
	public function _before( \Codeception\TestInterface $test ): void {
		$this->image_dir = sys_get_temp_dir() . '/cbf-si-test-' . bin2hex( random_bytes( 6 ) );
		mkdir( $this->image_dir, 0755, true );
	}

	/**
	 * Remove the image directory after each test.
	 *
	 * @param \Codeception\TestInterface $test Current test.
	 */
	public function _after( \Codeception\TestInterface $test ): void {
		if ( $this->image_dir !== null ) {
			self::remove( $this->image_dir );
		}
		$this->image_dir = null;
	}

	/**
	 * Delete a directory tree.
	 *
	 * Deliberately independent of Utils::rmdir_recursive(): test scaffolding
	 * should not depend on the code it is exercising.
	 *
	 * @param string $dir Directory to remove.
	 */
	private static function remove( string $dir ): void {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		foreach ( array_diff( (array) scandir( $dir ), array( '.', '..' ) ) as $entry ) {
			$path = $dir . '/' . $entry;
			is_dir( $path ) ? self::remove( $path ) : unlink( $path );
		}

		rmdir( $dir );
	}


	/**
	 * A writable directory for a parser to extract images into.
	 */
	public function imageDir(): string {
		return (string) $this->image_dir;
	}

	/**
	 * Files written into the current test's image directory.
	 *
	 * @return string[] File names, sorted.
	 */
	public function extractedImages(): array {
		$files = array_map( 'basename', (array) glob( $this->imageDir() . '/*' ) );
		sort( $files );
		return $files;
	}

	/**
	 * Absolute path to a committed binary fixture.
	 *
	 * @param string $name File name under tests/_data/bin.
	 */
	public function fixture( string $name ): string {
		return __DIR__ . '/../../_data/bin/' . $name;
	}

	/**
	 * Real documents to parse in addition to the committed fixtures.
	 *
	 * @return string[] Absolute paths; empty when no corpus directory is set.
	 */
	public function corpus(): array {
		$dir = $this->corpusDir();
		if ( $dir === null ) {
			return array();
		}

		$found = array();
		foreach ( array( 'pptx', 'pdf', 'docx' ) as $extension ) {
			$found = array_merge( $found, (array) glob( $dir . '/*.' . $extension ) );
		}
		sort( $found );

		return $found;
	}


	/**
	 * Resolve the configured corpus directory.
	 *
	 * A real environment variable wins over the .env file, so a one-off run can
	 * override a machine's standing configuration. A relative path is resolved
	 * against the plugin root, which is where the test commands are run from.
	 *
	 * @return string|null Absolute path, or null when unset or not a directory.
	 */
	public function corpusDir(): ?string {
		$value = getenv( self::CORPUS_ENV );

		if ( $value === false || $value === '' ) {
			$value = self::dotenv()[ self::CORPUS_ENV ] ?? '';
		}

		$value = trim( (string) $value );
		if ( $value === '' ) {
			$value = self::CORPUS_DEFAULT_DIR;
		}

		$path = self::isAbsolute( $value ) ? $value : self::pluginRoot() . '/' . $value;
		$real = realpath( $path );

		return ( $real !== false && is_dir( $real ) ) ? $real : null;
	}


	/**
	 * Values from the plugin's .env file, if it has one.
	 *
	 * Parsed rather than loaded into the environment, so running the suite can
	 * never leak settings into anything else the process goes on to do.
	 *
	 * @return array<string, string>
	 */
	private static function dotenv(): array {
		static $values = null;

		if ( $values !== null ) {
			return $values;
		}

		$values = array();
		if ( is_readable( self::pluginRoot() . '/.env' ) ) {
			$values = Dotenv::parse( (string) file_get_contents( self::pluginRoot() . '/.env' ) );
		}

		return $values;
	}


	/** Absolute path to the plugin root. */
	private static function pluginRoot(): string {
		return dirname( __DIR__, 3 );
	}


	/**
	 * Whether a path is absolute, on either path separator convention.
	 *
	 * @param string $path Candidate path.
	 */
	private static function isAbsolute( string $path ): bool {
		return str_starts_with( $path, '/' ) || (bool) preg_match( '#^[A-Za-z]:[\\/]#', $path );
	}
}
