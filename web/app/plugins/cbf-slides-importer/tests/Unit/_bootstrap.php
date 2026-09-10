<?php
/**
 * Unit-suite bootstrap.
 *
 * Loads the plugin's autoloader and the WordPress stubs, so the Document, Pptx,
 * Pdf and Docx classes can be exercised without a WordPress installation.
 *
 * ABSPATH has to be defined before any plugin file is autoloaded: each one ends
 * its guard clause with `exit`, which would end the test run with no PHP error
 * and no output.
 *
 * @package CodingBlackFemales/SlidesImporter
 */

declare( strict_types=1 );

require dirname( __DIR__, 2 ) . '/vendor/autoload.php';
require dirname( __DIR__ ) . '/Support/wordpress-stubs.php';
