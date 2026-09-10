<?php
/**
 * @package CodingBlackFemales/SlidesImporter
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SlidesImporter\Tests\Unit;

use Codeception\Test\Unit;
use CodingBlackFemales\SlidesImporter\Bulk\DriveUrl;

/**
 * Covers Drive URL classification.
 *
 * Every URL shape below was observed in the real curriculum spreadsheet the
 * migration CSV is derived from; only the file IDs are synthetic, so no real
 * Drive identifiers are committed.
 */
final class DriveUrlTest extends Unit {

	/** A plausible Drive file ID. */
	private const ID = '1H0p6qErPpdywFEfqM2LeoLsH8P-z9xjfp7cbKQpSlFw';

	/**
	 * Importable sources yield a file ID and the right kind.
	 *
	 * @dataProvider importableUrls
	 *
	 * @param string $url  URL to parse.
	 * @param string $kind Expected kind.
	 */
	public function testImportableSources( string $url, string $kind ): void {
		$result = DriveUrl::parse( $url );

		$this->assertTrue( $result['importable'], $url );
		$this->assertSame( $kind, $result['kind'] );
		$this->assertSame( self::ID, $result['file_id'] );
		$this->assertSame( '', $result['reason'] );
		$this->assertTrue( DriveUrl::is_importable( $result ) );
	}

	/** @return array<string, array{string, string}> */
	public static function importableUrls(): array {
		$id = self::ID;

		return array(
			'slides'                 => array( "https://docs.google.com/presentation/d/{$id}/edit", DriveUrl::KIND_SLIDES ),
			'slides with deep link'  => array( "https://docs.google.com/presentation/d/{$id}/edit#slide=id.g123_0_1", DriveUrl::KIND_SLIDES ),
			'slides with usp'        => array( "https://docs.google.com/presentation/d/{$id}/edit?usp=sharing", DriveUrl::KIND_SLIDES ),
			'slides with many params' => array( "https://docs.google.com/presentation/d/{$id}/edit?usp=sharing&ouid=1&rtpof=true&sd=true", DriveUrl::KIND_SLIDES ),
			'doc'                    => array( "https://docs.google.com/document/d/{$id}/edit", DriveUrl::KIND_DOC ),
			'doc with tab param'     => array( "https://docs.google.com/document/d/{$id}/edit?tab=t.0", DriveUrl::KIND_DOC ),
			'drive file'             => array( "https://drive.google.com/file/d/{$id}/view?usp=sharing", DriveUrl::KIND_FILE ),
			'drive open'             => array( "https://drive.google.com/open?id={$id}", DriveUrl::KIND_FILE ),
			'drive uc'               => array( "https://drive.google.com/uc?id={$id}", DriveUrl::KIND_FILE ),
			'bare id'                => array( $id, DriveUrl::KIND_FILE ),
			'trailing whitespace'    => array( "  https://docs.google.com/document/d/{$id}/edit  ", DriveUrl::KIND_DOC ),
		);
	}

	/**
	 * The scheme is tolerated rather than policed.
	 *
	 * The real material contains at least one http:// link; rejecting it would
	 * lose a row for no benefit, since the ID is what matters.
	 */
	public function testHttpSchemeIsAccepted(): void {
		$result = DriveUrl::parse( 'http://docs.google.com/presentation/d/' . self::ID . '/edit' );

		$this->assertTrue( $result['importable'] );
		$this->assertSame( self::ID, $result['file_id'] );
	}

	/**
	 * Unsupported sources are recognised and explained, not silently dropped.
	 *
	 * @dataProvider rejectedUrls
	 *
	 * @param string $url      URL to parse.
	 * @param string $kind     Expected kind.
	 * @param string $fragment Text the reason must contain.
	 */
	public function testRejectedSources( string $url, string $kind, string $fragment ): void {
		$result = DriveUrl::parse( $url );

		$this->assertFalse( $result['importable'], $url );
		$this->assertSame( $kind, $result['kind'] );
		$this->assertStringContainsString( $fragment, $result['reason'] );
		$this->assertFalse( DriveUrl::is_importable( $result ) );
	}

	/** @return array<string, array{string, string, string}> */
	public static function rejectedUrls(): array {
		$id = self::ID;

		return array(
			'google form'      => array( "https://docs.google.com/forms/d/{$id}/edit", DriveUrl::KIND_FORM, 'Google Form' ),
			'google form http' => array( "http://docs.google.com/forms/d/{$id}/edit", DriveUrl::KIND_FORM, 'Google Form' ),
			'google sheet'     => array( "https://docs.google.com/spreadsheets/d/{$id}/edit", DriveUrl::KIND_SHEET, 'Google Sheet' ),
			'github repo'      => array( 'https://github.com/cbfacademy/Introduction-to-DBT', DriveUrl::KIND_NOT_DRIVE, 'not a Google Drive link' ),
			'external course'  => array( 'https://www.netacad.com/courses/introduction-to-cybersecurity', DriveUrl::KIND_NOT_DRIVE, 'not a Google Drive link' ),
			'already on lms'   => array( 'https://academy.codingblackfemales.com/courses/system-setup/', DriveUrl::KIND_NOT_DRIVE, 'not a Google Drive link' ),
			'drive folder'     => array( "https://drive.google.com/drive/folders/{$id}", DriveUrl::KIND_FOLDER, 'Drive folder, not a file' ),
			'empty'            => array( '', DriveUrl::KIND_EMPTY, 'No source link' ),
			'whitespace only'  => array( "   \t ", DriveUrl::KIND_EMPTY, 'No source link' ),
			'plain text'       => array( 'Introduction to the Command Line', DriveUrl::KIND_UNPARSEABLE, 'No Drive file ID' ),
			'ftp scheme'       => array( "ftp://drive.google.com/file/d/{$id}/view", DriveUrl::KIND_UNPARSEABLE, 'No Drive file ID' ),
		);
	}

	/**
	 * A Form is rejected on its path segment, not on its ID.
	 *
	 * Forms share the exact `/d/FILE_ID/` shape as Slides and Docs, so a parser
	 * that merely hunts for an ID would accept all twelve of them and fail
	 * opaquely at download instead of explaining the problem up front.
	 */
	public function testFormIsRejectedDespiteHavingAValidId(): void {
		$form   = DriveUrl::parse( 'https://docs.google.com/forms/d/' . self::ID . '/edit' );
		$slides = DriveUrl::parse( 'https://docs.google.com/presentation/d/' . self::ID . '/edit' );

		$this->assertSame( $slides['file_id'], self::ID, 'Both URLs carry the same ID' );
		$this->assertFalse( $form['importable'] );
		$this->assertTrue( $slides['importable'] );
	}

	/** A word that is not an ID is not mistaken for one. */
	public function testShortTokenIsNotTreatedAsABareId(): void {
		$this->assertSame( DriveUrl::KIND_UNPARSEABLE, DriveUrl::parse( 'Slides' )['kind'] );
	}
}
