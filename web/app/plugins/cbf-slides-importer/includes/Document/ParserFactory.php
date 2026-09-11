<?php
/**
 * Selects the right source parser for a document and describes what the plugin
 * accepts.
 *
 * Every fact about supported formats — extensions, MIME types, Drive export
 * behaviour, the noun the UI uses for one unit of content — is declared here so
 * adding a fourth format means touching one table rather than hunting through
 * the REST controllers, the job runner and the admin JS.
 *
 * @class   Document\ParserFactory
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Document;

use CodingBlackFemales\SlidesImporter\Docx\Parser as DocxParser;
use CodingBlackFemales\SlidesImporter\Pdf\Parser as PdfParser;
use CodingBlackFemales\SlidesImporter\Pptx\Parser as PptxParser;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ParserFactory class.
 */
final class ParserFactory {

	/** Supported source formats, keyed by file extension. */
	const FORMAT_PPTX = 'pptx';
	const FORMAT_PDF  = 'pdf';
	const FORMAT_DOCX = 'docx';

	/**
	 * Google Drive MIME types for native editor files, which must be exported
	 * to a binary format before they can be parsed.
	 */
	const GOOGLE_SLIDES_MIME = 'application/vnd.google-apps.presentation';
	const GOOGLE_DOCS_MIME   = 'application/vnd.google-apps.document';

	/**
	 * The format table.
	 *
	 * Each entry declares:
	 *  - parser      : class exposing parse( string $path, string $img_dir ).
	 *  - mime        : canonical MIME type for the binary file.
	 *  - label       : short human name used in UI copy and error messages.
	 *  - unit        : singular noun for one unit of content in this format.
	 *  - unit_plural : plural of `unit`.
	 *  - google_mime : Drive MIME type of the native editor file that exports to
	 *                  this format, or '' when the format has no native editor.
	 *  - export_url  : printf template for Drive's direct export endpoint, used
	 *                  as a fallback when the API's export size cap is hit.
	 */
	const FORMATS = array(
		self::FORMAT_PPTX => array(
			'parser'      => PptxParser::class,
			'mime'        => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'label'       => 'PowerPoint',
			'unit'        => 'slide',
			'unit_plural' => 'slides',
			'google_mime' => self::GOOGLE_SLIDES_MIME,
			'export_url'  => 'https://docs.google.com/presentation/d/%s/export/pptx',
		),
		self::FORMAT_DOCX => array(
			'parser'      => DocxParser::class,
			'mime'        => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'label'       => 'Word',
			'unit'        => 'section',
			'unit_plural' => 'sections',
			'google_mime' => self::GOOGLE_DOCS_MIME,
			'export_url'  => 'https://docs.google.com/document/d/%s/export?format=docx',
		),
		self::FORMAT_PDF  => array(
			'parser'      => PdfParser::class,
			'mime'        => 'application/pdf',
			'label'       => 'PDF',
			'unit'        => 'page',
			'unit_plural' => 'pages',
			'google_mime' => '',
			'export_url'  => '',
		),
	);


	/**
	 * Parse a document, dispatching on its file extension.
	 *
	 * @param  string $path        Absolute path to the source file.
	 * @param  string $img_out_dir Absolute path to write extracted images.
	 * @return array|WP_Error      ParsedDeck array or WP_Error on failure.
	 */
	public static function parse( string $path, string $img_out_dir ): array|WP_Error {
		$format = self::detect_format( $path );

		if ( $format === null ) {
			return new WP_Error(
				'cbf_si_unsupported_format',
				sprintf(
					/* translators: %s: comma-separated list of supported file extensions */
					__( 'Unsupported file type. Supported formats: %s.', 'cbf-slides-importer' ),
					self::extension_list()
				)
			);
		}

		$parser = self::FORMATS[ $format ]['parser'];

		return $parser::parse( $path, $img_out_dir );
	}


	/**
	 * Identify a file's source format from its extension.
	 *
	 * @param  string $path File path or name.
	 * @return string|null Format key, or null when unsupported.
	 */
	public static function detect_format( string $path ): ?string {
		$ext = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
		return isset( self::FORMATS[ $ext ] ) ? $ext : null;
	}


	/**
	 * Identify a source format from a MIME type.
	 *
	 * Recognises both the binary formats and the Google editor types that export
	 * to them, so a Drive pick can be resolved before anything is downloaded.
	 *
	 * @param  string $mime_type MIME type.
	 * @return string|null Format key, or null when unrecognised.
	 */
	public static function format_for_mime( string $mime_type ): ?string {
		$mime_type = strtolower( trim( $mime_type ) );

		foreach ( self::FORMATS as $format => $spec ) {
			if ( $mime_type === $spec['mime'] || ( $spec['google_mime'] !== '' && $mime_type === $spec['google_mime'] ) ) {
				return $format;
			}
		}

		return null;
	}


	/** Return all supported file extensions. */
	public static function extensions(): array {
		return array_keys( self::FORMATS );
	}


	/** Return the canonical MIME type for each supported extension. */
	public static function mime_types(): array {
		return array_map( static fn( array $spec ): string => $spec['mime'], self::FORMATS );
	}


	/**
	 * Return every MIME type the Drive picker should offer.
	 *
	 * Covers both the Google editor types and files already stored in Drive in
	 * one of the binary formats.
	 *
	 * @return array<string>
	 */
	public static function picker_mime_types(): array {
		$types = array();

		foreach ( self::FORMATS as $spec ) {
			if ( $spec['google_mime'] !== '' ) {
				$types[] = $spec['google_mime'];
			}
			$types[] = $spec['mime'];
		}

		return $types;
	}


	/**
	 * Return the value for a file input's `accept` attribute.
	 *
	 * @return string e.g. ".pptx,.docx,.pdf,application/pdf,…"
	 */
	public static function accept_attribute(): string {
		$parts = array();

		foreach ( self::FORMATS as $ext => $spec ) {
			$parts[] = '.' . $ext;
			$parts[] = $spec['mime'];
		}

		return implode( ',', $parts );
	}


	/**
	 * Return a human-readable list of supported extensions.
	 *
	 * @return string e.g. ".pptx, .docx, .pdf"
	 */
	public static function extension_list(): string {
		return implode( ', ', array_map( static fn( string $ext ): string => '.' . $ext, self::extensions() ) );
	}


	/**
	 * Return the singular noun the UI should use for one unit of content.
	 *
	 * PPTX decks have slides, PDFs have pages and Word documents have sections;
	 * the pipeline calls all three "slides" internally, but showing an editor
	 * "Slide 4" for a Word document would be wrong.
	 *
	 * @param  string $format Format key.
	 * @return string
	 */
	public static function unit_label( string $format ): string {
		return self::FORMATS[ $format ]['unit'] ?? 'slide';
	}


	/**
	 * Return the plural noun the UI should use for units of content.
	 *
	 * @param  string $format Format key.
	 * @return string
	 */
	public static function unit_label_plural( string $format ): string {
		return self::FORMATS[ $format ]['unit_plural'] ?? 'slides';
	}


	/**
	 * Return a format's short human name.
	 *
	 * @param  string $format Format key.
	 * @return string
	 */
	public static function format_label( string $format ): string {
		return self::FORMATS[ $format ]['label'] ?? strtoupper( $format );
	}


	/**
	 * Return the Drive direct-export URL template for a format.
	 *
	 * @param  string $format Format key.
	 * @return string Template, or '' when the format has no native editor.
	 */
	public static function export_url_template( string $format ): string {
		return self::FORMATS[ $format ]['export_url'] ?? '';
	}


	/**
	 * Return the Drive MIME type a native editor file must be exported to.
	 *
	 * @param  string $google_mime Drive MIME type of the source file.
	 * @return string|null Export MIME type, or null when the file is not a
	 *                     Google editor file.
	 */
	public static function export_mime_for( string $google_mime ): ?string {
		foreach ( self::FORMATS as $spec ) {
			if ( $spec['google_mime'] !== '' && $spec['google_mime'] === $google_mime ) {
				return $spec['mime'];
			}
		}
		return null;
	}
}
