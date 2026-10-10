<?php
/**
 * Rank entries against a search string.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Entry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Search.
 *
 * The editor's search-as-you-type matches every form, not just the post title,
 * so typing "SCM" finds "Version control system". Glossaries are small enough
 * (hundreds of entries, not tens of thousands) to rank in PHP.
 */
final class Search {

	/**
	 * Score for a form equal to the query.
	 */
	const EXACT = 3;

	/**
	 * Score for a form starting with the query.
	 */
	const PREFIX = 2;

	/**
	 * Score for a form containing the query.
	 */
	const CONTAINS = 1;


	/**
	 * Entries matching a query, best first.
	 *
	 * Ties are broken alphabetically by canonical term. An empty query lists
	 * everything alphabetically.
	 *
	 * @param Entry[] $entries Candidates.
	 * @param string  $query   Search text.
	 * @param int     $limit   Maximum results; 0 for no limit.
	 * @return Entry[]
	 */
	public static function rank( array $entries, string $query, int $limit = 0 ): array {
		$needle = mb_strtolower( trim( $query ) );
		$scored = array();

		foreach ( $entries as $entry ) {
			$score = $needle === '' ? self::CONTAINS : self::score( $entry, $needle );
			if ( $score > 0 ) {
				$scored[] = array( $score, $entry );
			}
		}

		usort( $scored, array( self::class, 'compare' ) );
		$ranked = array_column( $scored, 1 );

		return $limit > 0 ? array_slice( $ranked, 0, $limit ) : $ranked;
	}


	/**
	 * Best score of any of an entry's forms.
	 *
	 * @param Entry  $entry  Candidate.
	 * @param string $needle Lower-cased query.
	 */
	private static function score( Entry $entry, string $needle ): int {
		$best = 0;

		foreach ( $entry->forms as $form ) {
			foreach ( $form as $name ) {
				$best = max( $best, self::score_name( mb_strtolower( $name ), $needle ) );
			}
		}

		return $best;
	}


	/**
	 * Score one name.
	 *
	 * @param string $name   Lower-cased term or abbreviation.
	 * @param string $needle Lower-cased query.
	 */
	private static function score_name( string $name, string $needle ): int {
		if ( $name === '' ) {
			return 0;
		}

		if ( $name === $needle ) {
			return self::EXACT;
		}

		$at = mb_strpos( $name, $needle );
		if ( $at === false ) {
			return 0;
		}

		return $at === 0 ? self::PREFIX : self::CONTAINS;
	}


	/**
	 * Order scored pairs: higher score first, then alphabetically.
	 *
	 * @param array{0:int,1:Entry} $a First.
	 * @param array{0:int,1:Entry} $b Second.
	 */
	private static function compare( array $a, array $b ): int {
		return array( $b[0], mb_strtolower( $a[1]->term() ) ) <=> array( $a[0], mb_strtolower( $b[1]->term() ) );
	}
}
