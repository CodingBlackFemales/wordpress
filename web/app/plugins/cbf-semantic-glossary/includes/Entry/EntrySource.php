<?php
/**
 * Where renderers get entries from.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Entry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Entry lookup.
 *
 * The Repository implements this against the database. Keeping the renderers
 * and the audit behind this interface is what lets the unit suite run them
 * against an in-memory set.
 */
interface EntrySource {

	/**
	 * Entries by ID, whatever their status.
	 *
	 * IDs with no entry behind them are simply absent from the result, which is
	 * how callers detect references to deleted entries.
	 *
	 * @param int[] $ids Entry IDs.
	 * @return array<int, Entry> Keyed by ID.
	 */
	public function find_many( array $ids ): array;


	/**
	 * Every published entry.
	 *
	 * @return array<int, Entry> Keyed by ID.
	 */
	public function all_published(): array;
}
