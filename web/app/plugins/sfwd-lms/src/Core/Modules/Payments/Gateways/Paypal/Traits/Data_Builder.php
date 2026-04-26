<?php
/**
 * Trait for PayPal data builder shared methods.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal\Traits;

use LearnDash\Core\Utilities\Cast;
use DateTime;
use DateTimeZone;
use DateInterval;

/**
 * Trait for PayPal data builder shared utility methods.
 *
 * @since 4.25.0
 */
trait Data_Builder {
	/**
	 * Returns a formatted text limited to 127 characters.
	 *
	 * @since 4.25.0
	 *
	 * @param string $text                 The text to format.
	 * @param int    $max_character_length The maximum character length.
	 *
	 * @return string
	 */
	protected function trim_text( string $text, int $max_character_length = 127 ): string {
		$ellipsis        = '...';
		$truncate_length = $max_character_length - strlen( $ellipsis );

		$text = $this->sanitize_text( $text );

		if ( strlen( $text ) <= $max_character_length ) {
			return $text;
		}

		// Cut the text to the desired length.
		$truncated_text = substr( $text, 0, $truncate_length );

		// Find the last space within the truncated text.
		$last_space = strrpos( $truncated_text, ' ' );

		// Cut the text at the last space to avoid cutting in the middle of a word.
		if ( $last_space !== false ) {
			$truncated_text = substr( $truncated_text, 0, $last_space );
		}

		// Add an ellipsis at the end.
		$truncated_text .= $ellipsis;

		return $truncated_text;
	}

	/**
	 * Gets the frequency interval unit for PayPal billing cycles.
	 *
	 * @since 4.25.0
	 *
	 * @param string $duration_length Duration length (day, week, month, year).
	 *
	 * @return string PayPal frequency interval unit.
	 */
	protected function get_frequency_interval_unit( string $duration_length ): string {
		$mapping = [
			'D' => 'DAY',
			'W' => 'WEEK',
			'M' => 'MONTH',
			'Y' => 'YEAR',
		];

		return $mapping[ $duration_length ] ?? 'MONTH';
	}

	/**
	 * Calculates the start date for regular billing cycles after trial.
	 *
	 * @since 4.25.0
	 *
	 * @param int    $trial_duration_value  Trial duration value.
	 * @param string $trial_duration_length Trial duration length.
	 *
	 * @return DateTime
	 */
	protected function calculate_start_date( int $trial_duration_value, string $trial_duration_length ): DateTime {
		$start_date = new DateTime( 'now', new DateTimeZone( 'UTC' ) );

		$start_date->add( new DateInterval( "P{$trial_duration_value}{$trial_duration_length}" ) );

		return $start_date;
	}

	/**
	 * Formats a price to 2 decimal places.
	 *
	 * @since 4.25.0
	 *
	 * @param float $price Price.
	 *
	 * @return string Formatted price.
	 */
	protected function format_price( float $price ): string {
		return number_format( $price, 2, '.', '' );
	}

	/**
	 * Sanitizes text, replacing whitespace and a few other characters with single spaces.
	 *
	 * @since 4.25.0
	 *
	 * @param string $text Text to clean.
	 *
	 * @return string Cleaned text.
	 */
	protected function sanitize_text( string $text ): string {
		// Remove zero-width characters and other invisible characters.
		$invisible_chars = [
			"\xE2\x80\x8B", // Zero width space.
			"\xE2\x80\x8C", // Zero width non-joiner.
			"\xE2\x80\x8D", // Zero width joiner.
			"\xEF\xBB\xBF", // Byte order mark.
			"\xC2\xA0",     // Non-breaking space.
		];

		foreach ( $invisible_chars as $char ) {
			$text = str_replace( $char, '', $text );
		}

		// Remove HTML entities completely (no spaces).
		$text = Cast::to_string( preg_replace( '/&.+?;/', '', $text ) );

		// Remove Unicode escape sequences.
		$text = Cast::to_string( preg_replace( '/\\\\u[0-9a-fA-F]{4}/', '', $text ) );

		// Convert dots to spaces.
		$text = str_replace( '.', ' ', $text );

		// Convert forward slash to space.
		$text = str_replace( '/', ' ', $text );

		// Convert tabs and newlines to spaces first.
		$text = str_replace( [ "\t", "\n", "\r" ], ' ', $text );

		// Convert other control characters (0x00-0x08, 0x0B-0x1F, 0x7F-0x9F) to spaces.
		$text = Cast::to_string( preg_replace( '/[\x00-\x08\x0B-\x1F\x7F-\x9F]/', ' ', $text ) );

		// Remove all characters except letters, numbers, spaces, hyphens, and commas.
		$text = Cast::to_string( preg_replace( '/[^a-zA-Z0-9 \-,]/', '', $text ) );

		// Normalize multiple spaces to single spaces.
		$text = Cast::to_string( preg_replace( '/ +/', ' ', $text ) );

		// Trim whitespace.
		$text = trim( $text );

		return $text;
	}
}
