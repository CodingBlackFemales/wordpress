<?php
/**
 * Filter registry helper for the unit suite.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SemanticGlossary\Tests\Support\Helper;

use Codeception\Module;
use Codeception\TestInterface;

/**
 * Filters module.
 *
 * The stubbed add_filter() and get_option() keep their state in globals; this
 * empties them after each test so nothing set by one test leaks into the next.
 */
class Filters extends Module {

	/**
	 * Clear registered filters and stored options after each test.
	 *
	 * @param TestInterface $test Current test.
	 */
	public function _after( TestInterface $test ): void {
		$GLOBALS['cbf_glossary_test_filters'] = array();
		$GLOBALS['cbf_glossary_test_options'] = array();
	}
}
