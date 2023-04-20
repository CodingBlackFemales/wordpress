<?php
/**
 * LearnDash Theme Loader.
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-ld-themes-register.php';

// Register your themes.
require_once __DIR__ . '/legacy/index.php';
require_once __DIR__ . '/ld30/index.php';
