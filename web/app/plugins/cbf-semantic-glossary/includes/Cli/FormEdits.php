<?php
/**
 * Apply `wp glossary term` form options to an entry's forms.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Cli;

use CodingBlackFemales\SemanticGlossary\Entry\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FormEdits.
 *
 * Kept apart from the command so the option semantics can be unit tested:
 *
 * - --term and --abbr change the canonical form;
 * - --form replaces every alternative;
 * - --add-form appends alternatives, --remove-form drops them by term.
 *
 * WP-CLI collects a repeated option into an array, and a valueless one into
 * `true`; values() flattens all of those to a list of strings.
 */
final class FormEdits {

	/**
	 * The forms after applying the options.
	 *
	 * @param array<int, array{term:string,abbr:string}> $forms      Current forms.
	 * @param array<string, mixed>                       $assoc_args Associative arguments.
	 * @return array<int, array{term:string,abbr:string}>
	 */
	public static function apply( array $forms, array $assoc_args ): array {
		$canonical = $forms[0] ?? array(
			'term' => '',
			'abbr' => '',
		);

		if ( isset( $assoc_args['term'] ) ) {
			$canonical['term'] = self::scalar( $assoc_args['term'] );
		}

		if ( isset( $assoc_args['abbr'] ) ) {
			$canonical['abbr'] = self::scalar( $assoc_args['abbr'] );
		}

		$alternatives = isset( $assoc_args['form'] ) ? self::values( $assoc_args, 'form' ) : array_slice( $forms, 1 );
		$result       = array_merge( array( $canonical ), $alternatives, self::values( $assoc_args, 'add-form' ) );

		foreach ( self::values( $assoc_args, 'remove-form' ) as $term ) {
			$result = Forms::remove( $result, $term );
		}

		return Forms::normalise( $result );
	}


	/**
	 * Every value given for an option, as strings.
	 *
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @param string               $key        Option name.
	 * @return string[]
	 */
	public static function values( array $assoc_args, string $key ): array {
		if ( ! isset( $assoc_args[ $key ] ) ) {
			return array();
		}

		$values = is_array( $assoc_args[ $key ] ) ? $assoc_args[ $key ] : array( $assoc_args[ $key ] );

		return array_values( array_filter( array_map( array( self::class, 'scalar' ), $values ), 'strlen' ) );
	}


	/**
	 * An option value as a string; a valueless flag is an empty string.
	 *
	 * @param mixed $value Option value.
	 */
	private static function scalar( $value ): string {
		return is_bool( $value ) ? '' : trim( (string) $value );
	}
}
