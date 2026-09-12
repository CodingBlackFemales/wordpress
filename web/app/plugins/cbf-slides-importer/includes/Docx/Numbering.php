<?php
/**
 * Resolves a Word document's list numbering definitions.
 *
 * PhpWord's Word2007 reader records which numbering definition a list item
 * belongs to but leaves its list type unresolved, so a numbered list is
 * indistinguishable from a bulleted one through the object model alone. The
 * answer lives in word/numbering.xml, which this class reads directly.
 *
 * Worksheets and exercise sheets — the documents this importer is most often
 * pointed at — lean heavily on numbered questions, so getting <ol> rather than
 * <ul> is worth the extra read.
 *
 * @class   Docx\Numbering
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Docx;

use CodingBlackFemales\SlidesImporter\Utils;
use DOMDocument;
use DOMXPath;
use ZipArchive;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Numbering class.
 *
 * Produces a map of numbering id → indent level → OOXML number format, e.g.
 * `[ 6 => [ 0 => 'decimal', 1 => 'lowerLetter' ] ]`.
 */
final class Numbering {

	/** WordprocessingML namespace URI. */
	const NS_W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

	/** Zip entry holding the numbering definitions. */
	const ENTRY = 'word/numbering.xml';

	/**
	 * Number formats that render as a bullet rather than a counter.
	 *
	 * Every other format — decimal, lowerLetter, upperRoman and friends —
	 * implies an ordered list.
	 */
	const BULLET_FORMATS = array( 'bullet', 'none' );


	/**
	 * Read a document's numbering definitions.
	 *
	 * A document with no lists has no numbering.xml at all, which is why every
	 * failure path here returns an empty map rather than an error: lists simply
	 * fall back to being unordered.
	 *
	 * @param  string $docx_path Absolute path to the DOCX file.
	 * @return array<int, array<int, string>> Number format by numbering id and level.
	 */
	public static function read( string $docx_path ): array {
		$xml = self::read_entry( $docx_path );
		if ( $xml === '' ) {
			return array();
		}

		$xpath = self::xpath_for( $xml );
		if ( $xpath === null ) {
			return array();
		}

		return self::map_num_ids( $xpath, self::abstract_formats( $xpath ) );
	}


	/**
	 * Return true when a list item belongs to a numbered list.
	 *
	 * @param  array    $map    Map from read().
	 * @param  int|null $num_id The item's numbering id.
	 * @param  int      $level  The item's indent level.
	 * @return bool
	 */
	public static function is_ordered( array $map, ?int $num_id, int $level ): bool {
		if ( $num_id === null || ! isset( $map[ $num_id ] ) ) {
			return false;
		}

		$levels = $map[ $num_id ];
		$format = $levels[ $level ] ?? ( $levels[0] ?? '' );

		return $format !== '' && ! in_array( $format, self::BULLET_FORMATS, true );
	}


	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * Read numbering.xml out of the DOCX archive.
	 *
	 * @param  string $docx_path Absolute path to the DOCX file.
	 * @return string XML, or '' when the entry is absent or unreadable.
	 */
	private static function read_entry( string $docx_path ): string {
		$zip = new ZipArchive();
		if ( $zip->open( $docx_path ) !== true ) {
			return '';
		}

		$xml = $zip->getFromName( self::ENTRY );
		$zip->close();

		return $xml === false ? '' : $xml;
	}


	/**
	 * Parse numbering XML into a namespace-aware XPath context.
	 *
	 * @param  string $xml Numbering XML.
	 * @return DOMXPath|null Null when the XML will not parse.
	 */
	private static function xpath_for( string $xml ): ?DOMXPath {
		$previous = libxml_use_internal_errors( true );
		$doc      = new DOMDocument();
		$loaded   = $doc->loadXML( $xml );

		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( ! $loaded ) {
			Utils::log( 'DOCX numbering.xml could not be parsed.' );
			return null;
		}

		$xpath = new DOMXPath( $doc );
		$xpath->registerNamespace( 'w', self::NS_W );

		return $xpath;
	}


	/**
	 * Collect the per-level number format of every abstract numbering definition.
	 *
	 * @param  DOMXPath $xpath Numbering document.
	 * @return array<string, array<int, string>> Format by abstract id and level.
	 */
	private static function abstract_formats( DOMXPath $xpath ): array {
		$formats = array();

		foreach ( $xpath->query( '//w:abstractNum' ) as $abstract ) {
			$id = $abstract->getAttributeNS( self::NS_W, 'abstractNumId' );
			if ( $id === '' ) {
				continue;
			}
			$formats[ $id ] = self::level_formats( $xpath, $abstract );
		}

		return $formats;
	}


	/**
	 * Read one abstract definition's number format at each indent level.
	 *
	 * @param  DOMXPath $xpath    Numbering document.
	 * @param  object   $abstract The w:abstractNum element.
	 * @return array<int, string> Format keyed by level.
	 */
	private static function level_formats( DOMXPath $xpath, object $abstract ): array {
		$levels = array();

		foreach ( $xpath->query( './w:lvl', $abstract ) as $lvl ) {
			$format = $xpath->evaluate( 'string(./w:numFmt/@w:val)', $lvl );
			if ( $format !== '' ) {
				$levels[ (int) $lvl->getAttributeNS( self::NS_W, 'ilvl' ) ] = $format;
			}
		}

		return $levels;
	}


	/**
	 * Resolve each concrete numbering id to its abstract definition's formats.
	 *
	 * @param  DOMXPath $xpath     Numbering document.
	 * @param  array    $abstracts Formats by abstract id.
	 * @return array<int, array<int, string>> Format by numbering id and level.
	 */
	private static function map_num_ids( DOMXPath $xpath, array $abstracts ): array {
		$map = array();

		foreach ( $xpath->query( '//w:num' ) as $num ) {
			$num_id      = $num->getAttributeNS( self::NS_W, 'numId' );
			$abstract_id = $xpath->evaluate( 'string(./w:abstractNumId/@w:val)', $num );

			if ( $num_id !== '' && isset( $abstracts[ $abstract_id ] ) ) {
				$map[ (int) $num_id ] = $abstracts[ $abstract_id ];
			}
		}

		return $map;
	}
}
