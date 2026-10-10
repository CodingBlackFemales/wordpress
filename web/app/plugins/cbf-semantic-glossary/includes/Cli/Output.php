<?php
/**
 * Shared WP-CLI output helpers.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Cli;

use WP_CLI;
use WP_CLI\Formatter;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Output.
 */
final class Output {

	/**
	 * Print rows in the requested format.
	 *
	 * `ids` prints the `id` (or, failing that, `post_id`) column, space-separated.
	 *
	 * @param array<string, mixed>              $assoc_args     Associative arguments.
	 * @param array<int, array<string, mixed>>  $items          Rows.
	 * @param string[]                          $default_fields Columns when --fields is not given.
	 */
	public static function items( array $assoc_args, array $items, array $default_fields ): void {
		if ( ( $assoc_args['format'] ?? '' ) === 'ids' ) {
			$column = in_array( 'id', $default_fields, true ) ? 'id' : 'post_id';
			WP_CLI::line( implode( ' ', array_unique( array_column( $items, $column ) ) ) );
			return;
		}

		( new Formatter( $assoc_args, $default_fields ) )->display_items( $items );
	}


	/**
	 * Print one item in the requested format.
	 *
	 * @param array<string, mixed> $assoc_args     Associative arguments.
	 * @param array<string, mixed> $item           Item.
	 * @param string[]             $default_fields Fields when --fields is not given.
	 */
	public static function item( array $assoc_args, array $item, array $default_fields ): void {
		( new Formatter( $assoc_args, $default_fields ) )->display_item( $item );
	}


	/**
	 * Every value given for a repeatable option.
	 *
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @param string               $key        Option name.
	 * @return string[]
	 */
	public static function values( array $assoc_args, string $key ): array {
		return FormEdits::values( $assoc_args, $key );
	}


	/**
	 * Unwrap a result, exiting with its message when it is an error.
	 *
	 * @param mixed $result Result that may be a WP_Error.
	 * @return mixed
	 */
	public static function or_error( $result ) {
		if ( $result instanceof WP_Error ) {
			WP_CLI::error( $result );
		}
		return $result;
	}


	/**
	 * Yes or no, for boolean columns.
	 *
	 * @param mixed $value Truthy or falsy.
	 */
	public static function yes_no( $value ): string {
		return $value ? 'yes' : 'no';
	}
}
