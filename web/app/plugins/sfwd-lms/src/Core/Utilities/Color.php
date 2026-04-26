<?php
/**
 * LearnDash Color class.
 *
 * @since 4.21.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Utilities;

use function StellarWP\Learndash\SSNepenthe\ColorUtils\ {
	red, green, blue
};

/**
 * A helper class to provide easier ways to interact with colors.
 *
 * @since 4.21.3
 */
class Color {
	/**
	 * Picks the foreground color that has the highest contrast ratio with the background color.
	 *
	 * @since 4.21.3
	 *
	 * @param string $background_hex    Hex color code.
	 * @param string ...$foreground_hex Hex color codes.
	 *
	 * @return string
	 */
	public static function pick_foreground_color( string $background_hex, string ...$foreground_hex ): string {
		// Defaults to the first color.
		$best = $foreground_hex[0] ?? '';

		$contrast = self::get_contrast_ratio( $best, $background_hex );

		foreach ( $foreground_hex as $hex ) {
			$new_contrast = self::get_contrast_ratio( $hex, $background_hex );

			// If the new contrast is less than or equal to the current best, the previous best hex is used.
			if ( $new_contrast <= $contrast ) {
				continue;
			}

			$best     = $hex;
			$contrast = $new_contrast;
		}

		if ( $contrast < 3 ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- This is a development log.
			error_log( "Contrast ratio of {$best} on {$background_hex} is only {$contrast}." );
		}

		return $best;
	}

	/**
	 * Returns the relative luminance of a color.
	 *
	 * @see https://contrastchecker.online/color-relative-luminance-calculator
	 *
	 * @since 4.21.3
	 *
	 * @param string $hex Hex color code.
	 *
	 * @return float
	 */
	private static function get_luminance( string $hex ): float {
		$rgb = [ red( $hex ), green( $hex ), blue( $hex ) ];

		foreach ( $rgb as &$channel ) {
			$ratio = $channel / 255;

			$channel = $ratio < 0.03928
				? $ratio / 12.92
				: pow( ( $ratio + 0.055 ) / 1.055, 2.4 );
		}

		return ( 0.2126 * $rgb[0] ) + ( 0.7152 * $rgb[1] ) + ( 0.0722 * $rgb[2] );
	}

	/**
	 * Returns the contrast ratio between two colors.
	 * The returned ratio is compared to 1. Example: 5.48:1, if the returned value is 5.48.
	 *
	 * @since 4.21.3
	 *
	 * @param string $foreground_hex Hex color code.
	 * @param string $background_hex Hex color code.
	 *
	 * @return float
	 */
	private static function get_contrast_ratio( string $foreground_hex, string $background_hex ): float {
		$luminance_foreground = self::get_luminance( $foreground_hex );
		$luminance_background = self::get_luminance( $background_hex );

		$ratio = ( $luminance_foreground + 0.05 ) / ( $luminance_background + 0.05 );

		// This ensures the contrast ratio is always positive, which is the WCAG standard.
		if ( $luminance_background > $luminance_foreground ) {
			$ratio = 1 / $ratio;
		}

		// WCAG ratios round down.
		return floor( $ratio * 100 ) / 100;
	}
}
