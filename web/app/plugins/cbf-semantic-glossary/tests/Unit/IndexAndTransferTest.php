<?php
/**
 * @package CodingBlackFemales/SemanticGlossary
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SemanticGlossary\Tests\Unit;

use Codeception\Test\Unit;
use CodingBlackFemales\SemanticGlossary\Cli\FormEdits;
use CodingBlackFemales\SemanticGlossary\Entry\Forms;
use CodingBlackFemales\SemanticGlossary\Entry\Search;
use CodingBlackFemales\SemanticGlossary\Entry\Transfer;
use CodingBlackFemales\SemanticGlossary\Reference\IndexRows;
use CodingBlackFemales\SemanticGlossary\Reference\Scanner;
use CodingBlackFemales\SemanticGlossary\Render\Options;
use CodingBlackFemales\SemanticGlossary\Settings;
use CodingBlackFemales\SemanticGlossary\Tests\Support\Entries;
use InvalidArgumentException;

/**
 * Covers index rows, search, import/export, WP-CLI form options and glossary options.
 */
final class IndexAndTransferTest extends Unit {

	public function testIndexRowsRecordOrderFirstMentionsFormsAndGlossaryOnlyEntries(): void {
		$html = Entries::ref( 1, 'SCM' ) . Entries::ref( 3, 'branches' ) . Entries::ref( 1, 'VCS', true ) . '<span class="glossary-ref">x</span>';

		$rows = IndexRows::build( Scanner::scan( $html ), array( 3, 2, 2, 0 ), Entries::standard() );

		$this->assertSame(
			array(
				array( 1, 0, 'SCM', 1, 1, 1, 0 ),
				array( 3, 1, 'branches', null, 1, 1, 0 ),
				array( 1, 2, 'VCS', 0, 1, 0, 1 ),
				array( 2, 5, '', null, 0, 1, 0 ),
			),
			array_map( 'array_values', $rows )
		);
	}

	public function testSearchMatchesEveryFormAndRanksExactFirst(): void {
		$entries = Entries::standard()->all_published();

		$this->assertSame( array( 1 ), array_map( fn ( $e ) => $e->id, Search::rank( $entries, 'scm' ) ) );
		$this->assertSame( array( 2, 1, 5, 3 ), array_map( fn ( $e ) => $e->id, Search::rank( $entries, 'r' ) ) );
		$this->assertSame( array( 2 ), array_map( fn ( $e ) => $e->id, Search::rank( $entries, 'repo' ) ) );
		$this->assertCount( 2, Search::rank( $entries, '', 2 ) );
	}

	public function testJsonRoundTrip(): void {
		$records = Transfer::records( array( Entries::vcs(), Entries::repository() ) );

		$this->assertSame( $records, Transfer::import( Transfer::export( $records, Transfer::JSON ), Transfer::JSON ) );
	}

	public function testCsvRoundTripWithQuotesCommasAndNewlines(): void {
		$records                  = Transfer::records( array( Entries::vcs(), Entries::branch() ) );
		$records[1]['definition'] = "Line one, \"quoted\"\nline two.";

		$csv = Transfer::export( $records, Transfer::CSV );

		$this->assertStringStartsWith( "slug,term,abbr,alternatives,definition\n", $csv );
		$this->assertSame( $records, Transfer::import( $csv, Transfer::CSV ) );
	}

	public function testCsvColumnsMayBeReorderedOrMissing(): void {
		$records = Transfer::import( "\xEF\xBB\xBFTerm,Definition\nHEAD,Points at a branch\n\n", Transfer::CSV );

		$this->assertSame(
			array(
				array(
					'slug'       => '',
					'forms'      => array(
						array(
							'term' => 'HEAD',
							'abbr' => '',
						),
					),
					'definition' => 'Points at a branch',
				),
			),
			$records
		);
	}

	public function testImportRejectsRecordsWithoutATerm(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Record 2 has no term.' );

		Transfer::import( '[{"forms":[{"term":"A"}]},{"slug":"b","forms":[]}]', Transfer::JSON );
	}

	public function testImportRejectsMalformedFiles(): void {
		$this->expectException( InvalidArgumentException::class );
		Transfer::import( '{"not":"a list"}', Transfer::JSON );
	}

	public function testFormatFollowsTheExtension(): void {
		$this->assertSame( Transfer::CSV, Transfer::format_for( 'out/Glossary.CSV' ) );
		$this->assertSame( Transfer::JSON, Transfer::format_for( 'glossary.json' ) );
		$this->assertSame( Transfer::JSON, Transfer::format_for( 'glossary' ) );
	}

	public function testCliFormOptions(): void {
		$forms = Entries::vcs()->forms;

		$this->assertSame(
			'Version control system:VCS|Source code management:SCM|Revision control',
			$this->pack( FormEdits::apply( $forms, array() ) )
		);
		$this->assertSame(
			'Version control:VC|Git',
			$this->pack(
				FormEdits::apply(
					$forms,
					array(
						'term' => 'Version control',
						'abbr' => 'VC',
						'form' => 'Git',
					)
				)
			)
		);
		$this->assertSame(
			'Version control system|Source code management:SCM|Revision control|Git:G|Hg',
			$this->pack(
				FormEdits::apply(
					$forms,
					array(
						'abbr'     => true,
						'add-form' => array( 'Git:G', 'Hg' ),
					)
				)
			)
		);
		$this->assertSame(
			'Version control system:VCS',
			$this->pack( FormEdits::apply( $forms, array( 'remove-form' => array( 'revision control', 'Source code management' ) ) ) )
		);
	}

	public function testShortcodeOptions(): void {
		$options = Options::from_shortcode(
			array(
				'heading'      => 'Terms',
				'level'        => '3',
				'alternatives' => 'no',
				'index'        => 'false',
			),
			'h'
		);

		$this->assertSame( array( 'Terms', 3, false, true, false, 'h' ), array( $options->heading, $options->level, $options->show_alternatives, $options->back_links, $options->show_index, $options->heading_id ) );
		$this->assertSame( 2, Options::from_shortcode( array( 'level' => '6' ), 'h' )->level );
		$this->assertSame( 'Glossary', Options::from_shortcode( '', 'h' )->heading );
		$this->assertSame( 4, Options::from_block( array( 'level' => 4 ), 'h' )->level );
	}

	public function testIndexMinimumFallsBackToTheSiteSetting(): void {
		$this->assertSame( 8, Options::from_block( array(), 'h' )->index_min_entries );

		$GLOBALS['cbf_glossary_test_options'][ Settings::OPTION ] = array( 'index_min_entries' => 5 );
		$this->assertSame( 5, Options::from_block( array(), 'h' )->index_min_entries );
		$this->assertSame( 5, Options::from_shortcode( array( 'index_min' => '' ), 'h' )->index_min_entries );

		add_filter( 'glossary_index_min_entries', fn ( $minimum ) => $minimum + 1 );
		$this->assertSame( 6, Options::from_block( array(), 'h' )->index_min_entries );
	}

	public function testBlockAndShortcodeIndexMinimumOverrideTheSiteSetting(): void {
		$GLOBALS['cbf_glossary_test_options'][ Settings::OPTION ] = array( 'index_min_entries' => 5 );

		$this->assertSame( 3, Options::from_block( array( 'indexMinEntries' => 3 ), 'h' )->index_min_entries );
		$this->assertSame( 12, Options::from_shortcode( array( 'index_min' => '12' ), 'h' )->index_min_entries );
		$this->assertSame( 1, Options::from_block( array( 'indexMinEntries' => 0 ), 'h' )->index_min_entries );
		$this->assertSame( Settings::INDEX_MIN_LIMIT, Options::from_block( array( 'indexMinEntries' => 1000 ), 'h' )->index_min_entries );
	}

	public function testCopiesKeepTheIndexMinimum(): void {
		$options = new Options( 'G', 3, true, true, true, 'h', 4 );

		$this->assertSame( 4, $options->without_back_links()->index_min_entries );
		$this->assertSame( array( 'h-2', 4 ), array( $options->with_heading_id( 'h-2' )->heading_id, $options->with_heading_id( 'h-2' )->index_min_entries ) );
	}

	public function testSettingsAreSanitised(): void {
		$this->assertSame(
			array(
				'auto_append'       => false,
				'index_min_entries' => 1,
			),
			Settings::sanitise( array( 'index_min_entries' => '-4' ) )
		);
		$this->assertSame( 8, Settings::sanitise( 'junk' )['index_min_entries'] );
	}

	/**
	 * Forms as shorthand, for compact assertions.
	 *
	 * @param array<int, array{term:string,abbr:string}> $forms Forms.
	 */
	private function pack( array $forms ): string {
		return Forms::pack( $forms );
	}
}
