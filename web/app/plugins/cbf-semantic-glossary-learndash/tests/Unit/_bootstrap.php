<?php
/**
 * Unit-suite bootstrap.
 *
 * Loads core's WordPress stubs and this plugin's extras, then core's
 * autoloader (the course glossary renders through core's classes), then this
 * plugin's. The stubs go first: they define ABSPATH, without which every
 * plugin file's guard clause would `exit` and end the run silently.
 *
 * @package CodingBlackFemales/SemanticGlossaryLearnDash
 */

declare( strict_types=1 );

$core = dirname( __DIR__, 3 ) . '/cbf-semantic-glossary';

require_once $core . '/tests/Support/wordpress-stubs.php';
require_once dirname( __DIR__ ) . '/Support/wordpress-stubs.php';
require_once $core . '/vendor/autoload.php';
require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';
