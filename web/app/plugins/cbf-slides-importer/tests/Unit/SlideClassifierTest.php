<?php
/**
 * @package CodingBlackFemales/SlidesImporter
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SlidesImporter\Tests\Unit;

use Codeception\Test\Unit;
use CodingBlackFemales\SlidesImporter\Document\SlideClassifier;

/**
 * Covers the precedence rules that decide what happens to each unit of content.
 */
final class SlideClassifierTest extends Unit {

	/**
	 * Build a minimal deck of units for classification.
	 *
	 * @param array<int, array<string, mixed>> $overrides Per-unit field overrides.
	 * @return array ParsedDeck.
	 */
	private function deck( array $overrides = array() ): array {
		$slides = array();

		for ( $index = 0; $index < 4; $index++ ) {
			$slides[] = array_merge(
				array(
					'index'        => $index,
					'slide_number' => $index + 1,
					'layout_name'  => 'TITLE_AND_BODY',
					'is_hidden'    => false,
					'is_cover'     => ( $index === 0 ),
					'title'        => 'Unit ' . ( $index + 1 ),
					'content'      => array(),
					'images'       => array(),
				),
				$overrides[ $index ] ?? array()
			);
		}

		return array( 'slides' => $slides );
	}

	/**
	 * Read the classified type of every unit.
	 *
	 * @param array $deck Classified deck.
	 * @return string[]
	 */
	private function types( array $deck ): array {
		return array_column( $deck['slides'], 'slide_type' );
	}

	/** The first unit is a cover, and the rest default to body content. */
	public function testDefaultClassification(): void {
		$this->assertSame(
			array( 'cover', 'body', 'body', 'body' ),
			$this->types( SlideClassifier::classify( $this->deck() ) )
		);
	}

	/** A hidden unit is excluded regardless of anything else. */
	public function testHiddenUnitsAreExcluded(): void {
		$deck = $this->deck( array( 2 => array( 'is_hidden' => true ) ) );

		$this->assertSame( 'hidden', $this->types( SlideClassifier::classify( $deck ) )[2] );
	}

	/** A layout matching the regex starts a new topic. */
	public function testHeadingRegexMatchesLayoutName(): void {
		$deck = $this->deck( array( 1 => array( 'layout_name' => 'SECTION_HEADER' ) ) );

		$types = $this->types( SlideClassifier::classify( $deck, 'SECTION_HEADER' ) );
		$this->assertSame( 'heading', $types[1] );
		$this->assertSame( 'body', $types[2] );
	}

	/** The regex is anchored, so a partial layout name does not match. */
	public function testHeadingRegexIsAnchored(): void {
		$deck = $this->deck( array( 1 => array( 'layout_name' => 'SECTION_HEADER_ALT' ) ) );

		$this->assertSame( 'body', $this->types( SlideClassifier::classify( $deck, 'SECTION_HEADER' ) )[1] );
	}

	/** Layout matching ignores case. */
	public function testHeadingRegexIgnoresCase(): void {
		$deck = $this->deck( array( 1 => array( 'layout_name' => 'section_header' ) ) );

		$this->assertSame( 'heading', $this->types( SlideClassifier::classify( $deck, 'SECTION_HEADER' ) )[1] );
	}

	/** A malformed regex must not fatal; units simply stay as body content. */
	public function testInvalidRegexIsSurvivable(): void {
		$this->assertSame(
			array( 'cover', 'body', 'body', 'body' ),
			$this->types( SlideClassifier::classify( $this->deck(), '([unclosed' ) )
		);
	}

	/** An editor's override beats layout auto-detection. */
	public function testOverrideBeatsHeadingRegex(): void {
		$deck = $this->deck( array( 1 => array( 'layout_name' => 'SECTION_HEADER' ) ) );

		$types = $this->types( SlideClassifier::classify( $deck, 'SECTION_HEADER', array( 2 => 'hidden' ) ) );
		$this->assertSame( 'hidden', $types[1] );
	}

	/** Overrides are keyed by the 1-based number shown in the UI. */
	public function testOverridesUseOneBasedNumbers(): void {
		$types = $this->types( SlideClassifier::classify( $this->deck(), '', array( 3 => 'heading' ) ) );

		$this->assertSame( 'body', $types[1], 'Unit 2 is untouched' );
		$this->assertSame( 'heading', $types[2], 'Unit 3 is the one overridden' );
	}

	/** A cover cannot be overridden into content. */
	public function testCoverCannotBeOverridden(): void {
		$types = $this->types( SlideClassifier::classify( $this->deck(), '', array( 1 => 'body' ) ) );

		$this->assertSame( 'cover', $types[0] );
	}

	/** A hidden unit cannot be overridden back into content. */
	public function testHiddenCannotBeOverridden(): void {
		$deck  = $this->deck( array( 2 => array( 'is_hidden' => true ) ) );
		$types = $this->types( SlideClassifier::classify( $deck, '', array( 3 => 'body' ) ) );

		$this->assertSame( 'hidden', $types[2] );
	}

	/** An unrecognised override value is ignored rather than stored. */
	public function testUnknownOverrideValueIsIgnored(): void {
		$types = $this->types( SlideClassifier::classify( $this->deck(), '', array( 2 => 'nonsense' ) ) );

		$this->assertSame( 'body', $types[1] );
	}

	/** Classification adds a type to every unit without dropping any. */
	public function testEveryUnitIsClassified(): void {
		$deck = SlideClassifier::classify( $this->deck() );

		$this->assertCount( 4, $deck['slides'] );
		foreach ( $deck['slides'] as $slide ) {
			$this->assertArrayHasKey( 'slide_type', $slide );
		}
	}
}
