<?php
/**
 * Site-level settings.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings.
 */
final class Settings {

	/**
	 * Option holding every setting.
	 */
	const OPTION = 'cbf_glossary_settings';

	/**
	 * Default minimum number of entries before a glossary gets an A–Z index.
	 */
	const INDEX_MIN_ENTRIES = 8;

	/**
	 * Largest accepted minimum, to keep the setting within reason.
	 */
	const INDEX_MIN_LIMIT = 100;


	/**
	 * Settings with their defaults.
	 *
	 * @return array{auto_append: bool, index_min_entries: int}
	 */
	public static function defaults(): array {
		return array(
			// Append a Glossary to posts that reference terms but have no block.
			'auto_append'       => true,
			// Entries a glossary needs before it gets an A–Z index, unless a
			// Glossary block sets its own.
			'index_min_entries' => self::INDEX_MIN_ENTRIES,
		);
	}


	/**
	 * Current settings, defaults filled in.
	 *
	 * @return array{auto_append: bool, index_min_entries: int}
	 */
	public static function all(): array {
		$saved = get_option( self::OPTION, array() );
		return array_merge( self::defaults(), is_array( $saved ) ? $saved : array() );
	}


	/**
	 * Whether a glossary is appended automatically.
	 */
	public static function auto_append(): bool {
		return (bool) self::all()['auto_append'];
	}


	/**
	 * Default minimum number of entries before a glossary gets an A–Z index.
	 *
	 * Applies to automatic glossaries, the shortcode, and Glossary blocks that
	 * do not set their own minimum.
	 */
	public static function index_min_entries(): int {
		/**
		 * Filters the default number of entries a glossary needs before it gets
		 * an A–Z index.
		 *
		 * Short glossaries would otherwise show a row of two or three letters.
		 * A Glossary block's own minimum takes precedence over this.
		 *
		 * @param int $minimum Minimum from the site setting. Default 8.
		 */
		return self::clamp_index_min( apply_filters( 'glossary_index_min_entries', self::all()['index_min_entries'] ) );
	}


	/**
	 * A valid index minimum: a whole number from 1 to INDEX_MIN_LIMIT.
	 *
	 * @param mixed $value Requested minimum.
	 */
	public static function clamp_index_min( $value ): int {
		return max( 1, min( self::INDEX_MIN_LIMIT, (int) $value ) );
	}


	/**
	 * Clean submitted settings.
	 *
	 * @param mixed $input Raw option value.
	 * @return array{auto_append: bool, index_min_entries: int}
	 */
	public static function sanitise( $input ): array {
		$input = is_array( $input ) ? $input : array();

		return array(
			'auto_append'       => ! empty( $input['auto_append'] ),
			'index_min_entries' => self::clamp_index_min( $input['index_min_entries'] ?? self::INDEX_MIN_ENTRIES ),
		);
	}
}
