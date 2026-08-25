<?php
/**
 * PPTX parser — orchestrates PhpPresentation to extract slide data.
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
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Pptx;

use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Shape\RichText;
use PhpOffice\PhpPresentation\Shape\Drawing;
use CodingBlackFemales\SlidesImporter\Utils;
use WP_Error;
use ZipArchive;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parser class.
 *
 * Returns a structured ParsedDeck array consumed by BlockRenderer and
 * LearnDashImporter.
 *
 * ParsedDeck shape:
 * {
 *   slide_width_px:  int,
 *   slide_height_px: int,
 *   slides: ParsedSlide[]
 * }
 *
 * ParsedSlide shape:
 * {
 *   index:        int,       // 0-based
 *   slide_number: int,       // 1-based
 *   layout_name:  string,
 *   is_hidden:    bool,
 *   title:        string,
 *   content:      ContentBlock[]   // LinearBlock | ColumnsBlock
 *   images:       ImageBlob[]
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
				$parsed_slides[] = self::parse_slide( $slide, $idx, $pptx_path, $img_out_dir, $slide_width_px );
			}

			return array(
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
	 * @param object $slide         PhpPresentation slide object.
	 * @param int    $idx           0-based slide index.
	 * @param string $pptx_path     Path to PPTX (for ZipArchive hidden-slide check).
	 * @param string $img_out_dir   Directory to write extracted images.
	 * @param int    $slide_width_px Slide width in pixels (for column detection).
	 * @return array ParsedSlide array.
	 */
	private static function parse_slide( object $slide, int $idx, string $pptx_path, string $img_out_dir, int $slide_width_px ): array {
		$layout_name = '';
		try {
			$layout      = $slide->getSlideLayout();
			$layout_name = $layout ? (string) $layout->getLayoutName() : '';
		} catch ( \Throwable $e ) {
		}

		$is_hidden = self::is_hidden_slide( $pptx_path, $idx );
		$title     = self::extract_title( $slide );
		$images    = self::extract_images( $slide, $idx, $img_out_dir );
		$content   = GeometryDetector::build_content_blocks( $slide, $slide_width_px );

		return array(
			'index'       => $idx,
			'slide_number' => $idx + 1,
			'layout_name' => $layout_name,
			'is_hidden'   => $is_hidden,
			'is_cover'    => ( $idx === 0 ),
			'title'       => $title,
			'content'     => $content,
			'images'      => $images,
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
	 * Extract the slide title from the title placeholder shape.
	 *
	 * Falls back to the first non-body RichText shape's text if no
	 * title placeholder is found.
	 *
	 * @param  object $slide PhpPresentation slide.
	 * @return string
	 */
	private static function extract_title( object $slide ): string {
		foreach ( $slide->getShapeCollection() as $shape ) {
			if ( ! ( $shape instanceof RichText ) ) {
				continue;
			}
			try {
				$ph = $shape->getPlaceholder();
				if ( $ph && in_array( $ph->getType(), array( 'title', 'ctrTitle' ), true ) ) {
					return self::shape_plain_text( $shape );
				}
			} catch ( \Throwable $e ) {
			}
		}
		return '';
	}


	/**
	 * Extract image blobs from all Drawing shapes on a slide.
	 *
	 * Images are written to $img_out_dir and returned as an array of paths.
	 * Uses Drawing\Gd::getContents() — confirmed by P0.4 probe.
	 *
	 * @param  object $slide       PhpPresentation slide.
	 * @param  int    $slide_idx   0-based slide index (for filename prefix).
	 * @param  string $img_out_dir Destination directory.
	 * @return array<array{path: string, ext: string}> Extracted image metadata.
	 */
	private static function extract_images( object $slide, int $slide_idx, string $img_out_dir ): array {
		$images  = array();
		$img_num = 0;

		foreach ( $slide->getShapeCollection() as $shape ) {
			$cls = get_class( $shape );
			if ( stripos( $cls, 'Drawing' ) === false ) {
				continue;
			}

			try {
				$blob = $shape->getContents();
				$ext  = method_exists( $shape, 'getExtension' ) ? $shape->getExtension() : 'png';

				if ( empty( $blob ) ) {
					continue;
				}

				$img_num++;
				$filename = sprintf( 'slide_%03d_img_%02d.%s', $slide_idx + 1, $img_num, $ext );
				$dest     = trailingslashit( $img_out_dir ) . $filename;

				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				file_put_contents( $dest, $blob );

				$images[] = array(
					'path'     => $dest,
					'filename' => $filename,
					'ext'      => $ext,
				);
			} catch ( \Throwable $e ) {
				Utils::log(
					'Image extraction error.',
					array(
						'slide' => $slide_idx + 1,
						'msg' => $e->getMessage(),
					)
				);
			}
		}

		return $images;
	}


	/**
	 * Concatenate all plain text from a RichText shape.
	 *
	 * @param RichText $shape
	 * @return string
	 */
	private static function shape_plain_text( RichText $shape ): string {
		$text = '';
		foreach ( $shape->getParagraphs() as $para ) {
			foreach ( $para->getRichTextElements() as $run ) {
				if ( method_exists( $run, 'getText' ) ) {
					$text .= $run->getText();
				}
			}
		}
		return trim( $text );
	}
}
