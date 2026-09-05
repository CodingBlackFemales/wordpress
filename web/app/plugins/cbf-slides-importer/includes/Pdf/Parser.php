<?php
/**
 * PDF parser — turns a PDF into the format-neutral ParsedDeck IR.
 *
 * PDFs reaching this importer are overwhelmingly slide decks exported to PDF, so
 * a page maps onto a slide: page 1 is the cover, each page contributes a title
 * and a set of positioned content blocks, and the shared footer band is dropped.
 * Text-heavy documents parse through the same path — they simply produce pages
 * with no distinct title.
 *
 * The reconstruction work lives in the two collaborators: TextExtractor rebuilds
 * paragraphs from glyph positions, ImageExtractor recovers image placement from
 * the content stream.
 *
 * @class   Pdf\Parser
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Pdf;

use CodingBlackFemales\SlidesImporter\Document\BlockLayout;
use CodingBlackFemales\SlidesImporter\Document\ParserFactory;
use CodingBlackFemales\SlidesImporter\Utils;
use Smalot\PdfParser\Config;
use Smalot\PdfParser\Page;
use Smalot\PdfParser\Parser as PdfParser;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parser class.
 *
 * Emits the same ParsedDeck shape as Pptx\Parser, with:
 * - `layout_name` = '', since a PDF carries no layout names. Slide-type
 *                   auto-detection by regex therefore never fires, and editors
 *                   set page types through the slide-map overrides instead.
 * - `is_hidden`   = always false; PDFs have no hidden pages.
 */
final class Parser {

	/** Points-to-pixels conversion: 96 DPI screen pixels per 72 DPI point. */
	const PX_PER_PT = 96 / 72;

	/** US Letter fallback, in points, for pages that declare no media box. */
	const DEFAULT_PAGE_WIDTH_PT  = 612.0;
	const DEFAULT_PAGE_HEIGHT_PT = 792.0;

	/**
	 * Upper bound on pages parsed from a single document.
	 *
	 * Reconstructing text from glyph positions is the most expensive step in the
	 * importer; this keeps a mistakenly-uploaded book from exhausting the PHP
	 * time limit inside a cron run.
	 */
	const MAX_PAGES = 400;


	/**
	 * Parse a PDF file into a ParsedDeck array.
	 *
	 * @param  string $pdf_path    Absolute path to the PDF file.
	 * @param  string $img_out_dir Absolute path to write extracted images.
	 * @return array|WP_Error      ParsedDeck array or WP_Error on failure.
	 */
	public static function parse( string $pdf_path, string $img_out_dir ): array|WP_Error {
		if ( ! is_file( $pdf_path ) ) {
			return new WP_Error( 'cbf_si_pdf_missing', 'PDF file not found: ' . esc_html( basename( $pdf_path ) ) );
		}

		try {
			$document = ( new PdfParser( array(), self::config() ) )->parseFile( $pdf_path );
			$pages    = $document->getPages();

			if ( empty( $pages ) ) {
				return new WP_Error( 'cbf_si_pdf_empty', 'The PDF contains no readable pages.' );
			}

			if ( count( $pages ) > self::MAX_PAGES ) {
				return new WP_Error(
					'cbf_si_pdf_too_long',
					sprintf(
						/* translators: %d: maximum supported page count */
						__( 'The PDF has more pages than the importer can process (limit: %d).', 'cbf-slides-importer' ),
						self::MAX_PAGES
					)
				);
			}

			// The first page's box sizes the deck; mixed-size PDFs are rare and
			// per-page geometry is still measured against each page's own box.
			[ $width_pt, $height_pt ] = self::page_size( $pages[0] );

			$slides = array();
			foreach ( $pages as $index => $page ) {
				$slides[] = self::parse_page( $page, $index, $img_out_dir );
			}

			return array(
				'source_format'   => ParserFactory::FORMAT_PDF,
				'unit_label'      => ParserFactory::unit_label( ParserFactory::FORMAT_PDF ),
				'slide_width_px'  => (int) round( $width_pt * self::PX_PER_PT ),
				'slide_height_px' => (int) round( $height_pt * self::PX_PER_PT ),
				'slides'          => $slides,
			);

		} catch ( \Throwable $e ) {
			Utils::log(
				'PDF parse error.',
				array(
					'file' => basename( $pdf_path ),
					'msg'  => $e->getMessage(),
				)
			);
			return new WP_Error( 'cbf_si_parse_error', 'Failed to parse PDF: ' . $e->getMessage() );
		}
	}


	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * Build the PdfParser configuration this parser depends on.
	 *
	 * Font info in getDataTm() output is what makes bold, monospace and heading
	 * detection possible, and it is off by default.
	 */
	private static function config(): Config {
		$config = new Config();
		$config->setDataTmFontInfoHasToBeIncluded( true );
		return $config;
	}


	/**
	 * Parse a single page into a ParsedSlide.
	 *
	 * @param  Page   $page        PdfParser page.
	 * @param  int    $index       0-based page index.
	 * @param  string $img_out_dir Directory to write extracted images into.
	 * @return array ParsedSlide.
	 */
	private static function parse_page( Page $page, int $index, string $img_out_dir ): array {
		[ $width_pt, $height_pt ] = self::page_size( $page );

		$width_px      = (int) round( $width_pt * self::PX_PER_PT );
		$height_px     = (int) round( $height_pt * self::PX_PER_PT );
		$footer_cutoff = BlockLayout::footer_cutoff( $height_px );

		$text   = self::extract_text( $page, $width_pt, $height_pt, $footer_cutoff );
		$images = self::extract_images( $page, $index, $height_pt, $footer_cutoff, $img_out_dir );

		return array(
			'index'        => $index,
			'slide_number' => $index + 1,
			'layout_name'  => '',
			'is_hidden'    => false,
			'is_cover'     => ( $index === 0 ),
			'title'        => $text['title'],
			'content'      => BlockLayout::build_blocks( $text['boxes'], $width_px ),
			'images'       => $images,
		);
	}


	/**
	 * Extract a page's text, degrading to an empty page on failure.
	 *
	 * A single malformed page should not fail the whole import, so parse errors
	 * are logged and the page is imported empty for the editor to notice.
	 *
	 * @param  Page  $page          PdfParser page.
	 * @param  float $width_pt      Page width in points.
	 * @param  float $height_pt     Page height in points.
	 * @param  int   $footer_cutoff Footer cutoff in pixels.
	 * @return array{title: string, boxes: array}
	 */
	private static function extract_text( Page $page, float $width_pt, float $height_pt, int $footer_cutoff ): array {
		try {
			return TextExtractor::extract( $page, $width_pt, $height_pt, $footer_cutoff );
		} catch ( \Throwable $e ) {
			Utils::log( 'PDF page text extraction failed.', array( 'msg' => $e->getMessage() ) );
			return array(
				'title' => '',
				'boxes' => array(),
			);
		}
	}


	/**
	 * Extract a page's images, degrading to none on failure.
	 *
	 * @param  Page   $page          PdfParser page.
	 * @param  int    $index         0-based page index.
	 * @param  float  $height_pt     Page height in points.
	 * @param  int    $footer_cutoff Footer cutoff in pixels.
	 * @param  string $img_out_dir   Destination directory.
	 * @return array Image metadata arrays.
	 */
	private static function extract_images( Page $page, int $index, float $height_pt, int $footer_cutoff, string $img_out_dir ): array {
		if ( $img_out_dir === '' || ! is_dir( $img_out_dir ) ) {
			return array();
		}

		try {
			return ImageExtractor::extract( $page, $index, $height_pt, $footer_cutoff, $img_out_dir );
		} catch ( \Throwable $e ) {
			Utils::log(
				'PDF image extraction failed.',
				array(
					'page' => $index + 1,
					'msg'  => $e->getMessage(),
				)
			);
			return array();
		}
	}


	/**
	 * Read a page's dimensions in points from its MediaBox.
	 *
	 * MediaBox may be inherited from an ancestor Pages node, so the lookup walks
	 * up the page tree before falling back to Letter.
	 *
	 * @param  Page $page PdfParser page.
	 * @return array{0: float, 1: float} Width and height in points.
	 */
	private static function page_size( Page $page ): array {
		$node = $page;

		for ( $depth = 0; $node && $depth < 8; $depth++ ) {
			$box = self::media_box( $node );
			if ( $box !== null ) {
				return $box;
			}
			$node = self::parent_node( $node );
		}

		return array( self::DEFAULT_PAGE_WIDTH_PT, self::DEFAULT_PAGE_HEIGHT_PT );
	}


	/**
	 * Read a MediaBox rectangle off one node of the page tree.
	 *
	 * @param  object $node PdfParser object.
	 * @return array{0: float, 1: float}|null Width and height in points.
	 */
	private static function media_box( object $node ): ?array {
		try {
			$box = $node->get( 'MediaBox' );
			if ( ! $box || ! method_exists( $box, 'getContent' ) ) {
				return null;
			}

			$values = $box->getContent();
			if ( ! is_array( $values ) || count( $values ) < 4 ) {
				return null;
			}

			return self::rect_size( array_map( array( __CLASS__, 'to_float' ), $values ) );
		} catch ( \Throwable $e ) {
			return null;
		}
	}


	/**
	 * Convert a MediaBox rectangle's corners into a width and height.
	 *
	 * @param  array $numbers Rectangle as [x0, y0, x1, y1].
	 * @return array{0: float, 1: float}|null Width and height, or null if degenerate.
	 */
	private static function rect_size( array $numbers ): ?array {
		$width  = abs( $numbers[2] - $numbers[0] );
		$height = abs( $numbers[3] - $numbers[1] );

		return ( $width > 0 && $height > 0 ) ? array( $width, $height ) : null;
	}


	/**
	 * Coerce a PdfParser rectangle element to a float.
	 *
	 * @param  mixed $value Number, or a PdfParser element wrapping one.
	 * @return float
	 */
	private static function to_float( $value ): float {
		if ( is_object( $value ) && method_exists( $value, 'getContent' ) ) {
			return (float) $value->getContent();
		}
		return (float) $value;
	}


	/**
	 * Step up to a node's parent in the page tree.
	 *
	 * @param  object $node PdfParser object.
	 * @return object|null Parent node, or null at the root.
	 */
	private static function parent_node( object $node ): ?object {
		try {
			$parent = $node->get( 'Parent' );
			return is_object( $parent ) && method_exists( $parent, 'get' ) ? $parent : null;
		} catch ( \Throwable $e ) {
			return null;
		}
	}
}
