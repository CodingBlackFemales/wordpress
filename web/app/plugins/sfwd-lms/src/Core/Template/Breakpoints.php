<?php
/**
 * LearnDash breakpoints class.
 *
 * @since 4.16.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Template;

/**
 * A class to handle breakpoint definitions and pointers.
 *
 * @since 4.16.0
 */
class Breakpoints {
	/**
	 * Template breakpoints.
	 *
	 * @since 4.16.0
	 *
	 * @var array<string, int>
	 */
	public static $breakpoints = [
		'mobile'      => 375,
		'tablet'      => 420,
		'extra-small' => 600,
		'small'       => 720,
		'medium'      => 960,
		'large'       => 1240,
		'wide'        => 1440,
	];

	/**
	 * Generate a unique breakpoint pointer.
	 *
	 * @since 4.16.0
	 *
	 * @return string
	 */
	public static function get_pointer() {
		return wp_generate_uuid4();
	}

	/**
	 * Get template breakpoints.
	 *
	 * @since 4.16.0
	 *
	 * @return array<string, int>
	 */
	public static function get() {
		return static::$breakpoints;
	}
}
