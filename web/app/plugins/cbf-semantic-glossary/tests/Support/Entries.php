<?php
/**
 * In-memory entries for the unit suite.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SemanticGlossary\Tests\Support;

use CodingBlackFemales\SemanticGlossary\Entry\Entry;
use CodingBlackFemales\SemanticGlossary\Entry\EntrySource;

/**
 * Entries.
 *
 * An EntrySource over a fixed set, plus the entries the issue's examples use.
 */
final class Entries implements EntrySource {

	/**
	 * Entries by ID.
	 *
	 * @var array<int, Entry>
	 */
	private array $entries = array();


	/**
	 * Constructor.
	 *
	 * @param Entry ...$entries Entries.
	 */
	public function __construct( Entry ...$entries ) {
		foreach ( $entries as $entry ) {
			$this->entries[ $entry->id ] = $entry;
		}
	}


	/**
	 * {@inheritDoc}
	 *
	 * @param int[] $ids Entry IDs.
	 * @return array<int, Entry>
	 */
	public function find_many( array $ids ): array {
		return array_intersect_key( $this->entries, array_flip( $ids ) );
	}


	/**
	 * {@inheritDoc}
	 *
	 * @return array<int, Entry>
	 */
	public function all_published(): array {
		return array_filter( $this->entries, fn ( Entry $entry ): bool => $entry->is_published() );
	}


	/**
	 * The set used across the suite.
	 */
	public static function standard(): self {
		return new self( self::vcs(), self::repository(), self::branch(), self::commit(), self::gitignore() );
	}


	/**
	 * Version control system: the issue's three-form example.
	 */
	public static function vcs(): Entry {
		return new Entry(
			1,
			'version-control-system',
			array(
				array(
					'term' => 'Version control system',
					'abbr' => 'VCS',
				),
				array(
					'term' => 'Source code management',
					'abbr' => 'SCM',
				),
				array(
					'term' => 'Revision control',
					'abbr' => '',
				),
			),
			'A tool to keep track of changes made to software over time.'
		);
	}


	/**
	 * Repository, with a lower-case abbreviation.
	 */
	public static function repository(): Entry {
		return new Entry(
			2,
			'repository',
			array(
				array(
					'term' => 'Repository',
					'abbr' => 'repo',
				),
			),
			'A <code>.git</code> directory holding the project and its history.'
		);
	}


	/**
	 * Branch: a term with no abbreviation.
	 */
	public static function branch(): Entry {
		return new Entry(
			3,
			'branch',
			array(
				array(
					'term' => 'Branch',
					'abbr' => '',
				),
			),
			'A named line of development.'
		);
	}


	/**
	 * Commit: a draft, so references to it must not render.
	 */
	public static function commit(): Entry {
		return new Entry(
			4,
			'commit',
			array(
				array(
					'term' => 'Commit',
					'abbr' => '',
				),
			),
			'A snapshot.',
			'draft'
		);
	}


	/**
	 * .gitignore: a term whose initial is not a letter.
	 */
	public static function gitignore(): Entry {
		return new Entry(
			5,
			'gitignore',
			array(
				array(
					'term' => '.gitignore',
					'abbr' => '',
				),
			),
			'Files Git should not track.'
		);
	}


	/**
	 * A reference span as the editor stores it.
	 *
	 * @param int    $id   Entry ID.
	 * @param string $text Inner HTML.
	 * @param bool   $abbr Whether "Render as abbreviation" is on.
	 */
	public static function ref( int $id, string $text, bool $abbr = false ): string {
		return sprintf(
			'<span class="glossary-ref" data-glossary-id="%d"%s>%s</span>',
			$id,
			$abbr ? ' data-glossary-abbr="true"' : '',
			$text
		);
	}
}
