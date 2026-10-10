<?php
/**
 * Apply imported records.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Cli;

use CodingBlackFemales\SemanticGlossary\Entry\Markdown;
use CodingBlackFemales\SemanticGlossary\Entry\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Importer.
 */
final class Importer {

	const CREATED = 'created';
	const UPDATED = 'updated';
	const SKIPPED = 'skipped';

	/**
	 * Count of records per action.
	 *
	 * @var array<string, int>
	 */
	private $counts = array(
		self::CREATED => 0,
		self::UPDATED => 0,
		self::SKIPPED => 0,
	);


	/**
	 * Constructor.
	 *
	 * @param bool $update  Overwrite existing entries.
	 * @param bool $dry_run Report without saving.
	 */
	public function __construct(
		private readonly bool $update,
		private readonly bool $dry_run
	) {}


	/**
	 * Apply one record.
	 *
	 * @param array{slug:string, forms:array<int, array{term:string,abbr:string}>, definition:string} $record Record.
	 * @return array{slug:string, action:string, id:int|string}
	 */
	public function apply( array $record ): array {
		$slug     = sanitize_title( $record['slug'] !== '' ? $record['slug'] : $record['forms'][0]['term'] );
		$existing = Repository::instance()->find( $slug );
		$action   = $this->action_for( $existing !== null );
		$id       = $existing !== null ? $existing->id : 0;

		if ( $action !== self::SKIPPED && ! $this->dry_run ) {
			$id = $this->save( $record, $slug, $id );
		}

		++$this->counts[ $action ];

		return array(
			'slug'   => $slug,
			'action' => $this->label( $action ),
			'id'     => $id > 0 ? $id : '',
		);
	}


	/**
	 * What to do with a record.
	 *
	 * @param bool $exists Whether an entry with its slug exists.
	 */
	private function action_for( bool $exists ): string {
		if ( ! $exists ) {
			return self::CREATED;
		}
		return $this->update ? self::UPDATED : self::SKIPPED;
	}


	/**
	 * Save a record, exiting on failure.
	 *
	 * @param array{forms:array<int, array{term:string,abbr:string}>, definition:string} $record Record.
	 * @param string                                                                     $slug   Slug.
	 * @param int                                                                        $id     Existing entry ID, or 0.
	 */
	private function save( array $record, string $slug, int $id ): int {
		return (int) Output::or_error(
			Repository::instance()->save(
				array(
					'forms'      => $record['forms'],
					'definition' => Markdown::to_definition( $record['definition'] ),
					'slug'       => $slug,
				),
				$id
			)
		);
	}


	/**
	 * Report label for an action.
	 *
	 * @param string $action Action.
	 */
	private function label( string $action ): string {
		return $this->dry_run && $action !== self::SKIPPED ? 'would be ' . $action : $action;
	}


	/**
	 * One-line summary.
	 */
	public function summary(): string {
		return sprintf(
			/* translators: 1: prefix ("Dry run: " or nothing), 2: created count, 3: updated count, 4: skipped count */
			__( '%1$s%2$d created, %3$d updated, %4$d skipped.', 'cbf-semantic-glossary' ),
			$this->dry_run ? __( 'Dry run: ', 'cbf-semantic-glossary' ) : '',
			$this->counts[ self::CREATED ],
			$this->counts[ self::UPDATED ],
			$this->counts[ self::SKIPPED ]
		);
	}
}
