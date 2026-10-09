<?php
/**
 * Unit-suite bootstrap.
 *
 * Loads the WordPress stubs, then the plugin's autoloader.
 *
 * The stubs go first: they define ABSPATH, and the autoloader includes the
 * plugin's functions.php straight away, whose guard clause would otherwise
 * `exit` and end the run with no PHP error and no output.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

declare( strict_types=1 );

require dirname( __DIR__ ) . '/Support/wordpress-stubs.php';
require dirname( __DIR__, 2 ) . '/vendor/autoload.php';
