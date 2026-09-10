<?php
/**
 * PPTX parser — orchestrates PhpPresentation to extract slide data.
 *
 * Emits the format-neutral ParsedDeck IR (see Document\Ir) shared with the PDF
 * and DOCX parsers; PhpPresentation objects never leave this namespace.
 *
 * Key implementation notes from P0.4 probe:
 * - PhpPresentation returns shape offsets/dimensions in PIXELS (96 DPI), not EMU.
 *   Divide python-pptx EMU constants by 9525 when porting thresholds.
 * - Slide dimensions: $prs->getLayout()->getCX('px') and getCY('px').
 * - Hidden slides: read via ZipArchive (ppt/slides/slideN.xml, check show attr).
 * - Images: Drawing\Gd shapes — use $shape->getContents() for bytes,
 *   $shape->getExtension() for file extension.
 * - Title placeholder: $shape->getPlaceholder()->getType() === 'title'|'ctrTitle'.
 * - Text paragraphs: $shape->getParagraphs() (NOT getActiveParagraphs()).
 *
 * @class   Pptx\Parser
 * @version 1.1.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Pptx;

use CodingBlackFemales\SlidesImporter\Document\BlockLayout;
use CodingBlackFemales\SlidesImporter\Document\ParserFactory;
use CodingBlackFemales\SlidesImporter\Utils;
use PhpOffice\PhpPresentation\IOFactory;
use WP_Error;
use ZipArchive;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parser class.
 *
 * Returns a structured ParsedDeck array consumed by Document\BlockRenderer and
 * Import\LearnDashImporter. See Document\Ir for the full IR contract.
 *
 * ParsedDeck shape:
 * {
 *   source_format:   'pptx',
 *   slide_width_px:  int,
 *   slide_height_px: int,
 *   unit_label:      string,   // UI noun for one entry in `slides`
 *   slides: ParsedSlide[]
 * }
 *
 * ParsedSlide shape:
 * {
 *   index:        int,       // 0-based
 *   slide_number: int,       // 1-based
 *   layout_name:  string,
 *   is_hidden:    bool,
 *   is_cover:     bool,
 *   title:        string,
 *   content:      ContentBlock[]   // linear | columns | table
 *   images:       ImageMeta[]
 * }
 */
final class Parser {

	/**
	 * Parse a PPTX file and return structured slide data.
	 *
	 * Slide 1 (cover) is included in the output but flagged so callers can
	 * skip it for content generation (matching python-pptx pipeline behaviour).
	 *
	 * @param  string $pptx_path   Absolute path to the PPTX file.
	 * @param  string $img_out_dir Absolute path to write extracted images.
	 * @return array|WP_Error      ParsedDeck array or WP_Error on failure.
	 */
	public static function parse( string $pptx_path, string $img_out_dir ): array|WP_Error {
		if ( ! is_file( $pptx_path ) ) {
			return new WP_Error( 'cbf_si_pptx_missing', 'PPTX file not found: ' . esc_html( basename( $pptx_path ) ) );
		}

		try {
			$prs    = IOFactory::load( $pptx_path );
			$slides = $prs->getAllSlides();

			$slide_width_px  = (int) round( $prs->getLayout()->getCX( 'px' ) );
			$slide_height_px = (int) round( $prs->getLayout()->getCY( 'px' ) );

			$parsed_slides = array();
			foreach ( $slides as $idx => $slide ) {
				$parsed_slides[] = self::parse_slide( $slide, $idx, $pptx_path, $img_out_dir, $slide_width_px, $slide_height_px );
			}

			return array(
				'source_format'   => ParserFactory::FORMAT_PPTX,
				'unit_label'      => ParserFactory::unit_label( ParserFactory::FORMAT_PPTX ),
				'slide_width_px'  => $slide_width_px,
				'slide_height_px' => $slide_height_px,
				'slides'          => $parsed_slides,
			);

		} catch ( \Throwable $e ) {
			Utils::log(
				'PPTX parse error.',
				array(
					'file' => basename( $pptx_path ),
					'msg' => $e->getMessage(),
				)
			);
			return new WP_Error( 'cbf_si_parse_error', 'Failed to parse PPTX: ' . $e->getMessage() );
		}
	}


	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * Parse a single slide.
	 *
	 * @param object $slide           PhpPresentation slide object.
	 * @param int    $idx             0-based slide index.
	 * @param string $pptx_path       Path to PPTX (for ZipArchive hidden-slide check).
	 * @param string $img_out_dir     Directory to write extracted images.
	 * @param int    $slide_width_px  Slide width in pixels (for column detection).
	 * @param int    $slide_height_px Slide height in pixels (for footer cutoff).
	 * @return array ParsedSlide array.
	 */
	private static function parse_slide( object $slide, int $idx, string $pptx_path, string $img_out_dir, int $slide_width_px, int $slide_height_px ): array {
		$layout_name = '';
		try {
			$layout      = $slide->getSlideLayout();
			$layout_name = $layout ? (string) $layout->getLayoutName() : '';
		} catch ( \Throwable $e ) {
		}

		return array(
			'index'        => $idx,
			'slide_number' => $idx + 1,
			'layout_name'  => $layout_name,
			'is_hidden'    => self::is_hidden_slide( $pptx_path, $idx ),
			'is_cover'     => ( $idx === 0 ),
			'title'        => ShapeReader::extract_title( $slide ),
			'content'      => ShapeReader::build_content_blocks( $slide, $slide_width_px, $slide_height_px ),
			'images'       => self::extract_images( $slide, $idx, $img_out_dir, $slide_height_px ),
		);
	}


	/**
	 * Detect hidden slides via ZipArchive (OOXML show attribute).
	 *
	 * Mirrors python-pptx slide._element.get('show') behaviour.
	 * The show="0" attribute on <p:sld> marks a slide as hidden in PowerPoint.
	 *
	 * @param string $pptx_path Absolute path to PPTX.
	 * @param int    $idx       0-based slide index.
	 */
	private static function is_hidden_slide( string $pptx_path, int $idx ): bool {
		$zip = new ZipArchive();
		if ( $zip->open( $pptx_path ) !== true ) {
			return false;
		}
		$xml = $zip->getFromName( 'ppt/slides/slide' . ( $idx + 1 ) . '.xml' );
		$zip->close();

		if ( $xml === false ) {
			return false;
		}

		if ( preg_match( '/<p:sld[^>]+show=["\']([^"\']*)["\']/', $xml, $m ) ) {
			return $m[1] === '0' || strtolower( $m[1] ) === 'false';
		}

		return false;
	}


	/**
	 * Extract image blobs from all Drawing shapes on a slide.
	 *
	 * Images are written to $img_out_dir and returned as an array of paths.
	 * Uses Drawing\Gd::getContents() — confirmed by P0.4 probe.
	 *
	 * Drawing shapes in the footer zone (e.g. the CBF logo that appears on every
	 * slide) are skipped using the same footer threshold as BlockLayout.
	 *
	 * @param  object $slide           PhpPresentation slide.
	 * @param  int    $slide_idx       0-based slide index (for filename prefix).
	 * @param  string $img_out_dir     Destination directory.
	 * @param  int    $slide_height_px Slide height in pixels (0 = skip footer filter).
	 * @return array<array{path: string, filename: string, ext: string}> Extracted image metadata.
	 */
	private static function extract_images( object $slide, int $slide_idx, string $img_out_dir, int $slide_height_px = 0 ): array {
		$images        = array();
		$img_num       = 0;
		$footer_cutoff = BlockLayout::footer_cutoff( $slide_height_px );

		foreach ( $slide->getShapeCollection() as $shape ) {
			if ( ! self::is_content_image( $shape, $footer_cutoff ) ) {
				continue;
			}

			$image = self::save_image( $shape, $slide_idx, $img_num + 1, $img_out_dir );

			if ( $image !== null ) {
				$img_num++;
				$images[] = $image;
			}
		}

		return $images;
	}


	/**
	 * Whether a shape is an image that belongs in the imported content.
	 *
	 * Drawings in the footer band are furniture — the CBF logo sits there on
	 * every slide — so they are excluded on the same threshold as text.
	 *
	 * @param  object $shape         PhpPresentation shape.
	 * @param  int    $footer_cutoff Y position at which the footer band starts.
	 * @return bool
	 */
	private static function is_content_image( object $shape, int $footer_cutoff ): bool {
		if ( stripos( get_class( $shape ), 'Drawing' ) === false ) {
			return false;
		}

		$shape_top = $shape->getOffsetY();

		return $shape_top === null || (int) $shape_top < $footer_cutoff;
	}


	/**
	 * Write one drawing's bytes to disk.
	 *
	 * @param  object $shape       PhpPresentation drawing shape.
	 * @param  int    $slide_idx   0-based slide index, for the filename.
	 * @param  int    $img_num     1-based image number within the slide.
	 * @param  string $img_out_dir Destination directory.
	 * @return array{path: string, filename: string, ext: string}|null Null when there is nothing to write.
	 */
	private static function save_image( object $shape, int $slide_idx, int $img_num, string $img_out_dir ): ?array {
		try {
			$blob = $shape->getContents();

			if ( empty( $blob ) ) {
				return null;
			}

			$ext      = method_exists( $shape, 'getExtension' ) ? $shape->getExtension() : 'png';
			$filename = sprintf( 'slide_%03d_img_%02d.%s', $slide_idx + 1, $img_num, $ext );
			$dest     = trailingslashit( $img_out_dir ) . $filename;

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $dest, $blob );

			return array(
				'path'     => $dest,
				'filename' => $filename,
				'ext'      => $ext,
			);
		} catch ( \Throwable $e ) {
			Utils::log(
				'Image extraction error.',
				array(
					'slide' => $slide_idx + 1,
					'msg'   => $e->getMessage(),
				)
			);

			return null;
		}
	}
}
