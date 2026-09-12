<?php
/**
 * Extracts placed images from a PDF page.
 *
 * PdfParser exposes a page's image XObjects but not where they were drawn, and
 * position is what distinguishes real content from template furniture such as a
 * logo repeated on every page. So the page's content stream is walked directly,
 * tracking the graphics-state matrix stack, to recover each `Do` invocation's
 * placement rectangle.
 *
 * Only the four operators that affect image placement are interpreted — `q`,
 * `Q`, `cm` and `Do`. Everything else in the stream is skipped, which keeps this
 * far smaller than a general content-stream interpreter while still producing
 * correct rectangles for the transforms exporters actually emit.
 *
 * @class   Pdf\ImageExtractor
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Pdf;

use CodingBlackFemales\SlidesImporter\Utils;
use Smalot\PdfParser\Page;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ImageExtractor class.
 */
final class ImageExtractor {

	/** Points-to-pixels conversion: 96 DPI screen pixels per 72 DPI point. */
	const PX_PER_PT = 96 / 72;

	/** Characters PDF treats as whitespace between tokens. */
	const WHITESPACE = " \t\r\n\f\0";

	/** The identity transformation matrix, and the initial graphics state. */
	const IDENTITY_MATRIX = array( 1.0, 0.0, 0.0, 1.0, 0.0, 0.0 );

	/** Images smaller than this many pixels on either side are ignored as rules or icons. */
	const MIN_SIDE_PX = 24;

	/** Upper bound on bytes written for a single image. */
	const MAX_IMAGE_BYTES = 33554432; // 32 MB.

	/**
	 * Magic-byte prefixes for the image formats that can be written straight to
	 * disk. A PDF image stream whose filter is DCTDecode, JPXDecode or one of
	 * the pass-through image filters already holds a complete image file.
	 */
	const MAGIC_EXTENSIONS = array(
		"\xFF\xD8\xFF"                 => 'jpg',
		"\x89PNG\r\n\x1A\n"            => 'png',
		'GIF87a'                       => 'gif',
		'GIF89a'                       => 'gif',
		'BM'                           => 'bmp',
		"\x00\x00\x00\x0CjP  "         => 'jp2',
	);


	/**
	 * Extract a page's placed images to disk.
	 *
	 * @param  Page   $page             PdfParser page.
	 * @param  int    $page_index       0-based page index (filename prefix).
	 * @param  float  $page_height_pt   Page height in points.
	 * @param  int    $footer_cutoff_px Top-edge pixel offset below which images
	 *                                  are treated as page furniture.
	 * @param  string $img_out_dir      Destination directory.
	 * @return array<array{path: string, filename: string, ext: string}>
	 */
	public static function extract( Page $page, int $page_index, float $page_height_pt, int $footer_cutoff_px, string $img_out_dir ): array {
		$placements = self::read_placements( $page, $page_height_pt );
		if ( empty( $placements ) ) {
			return array();
		}

		$xobjects = $page->getXObjects();
		$images   = array();
		$seen     = array();

		foreach ( $placements as $placement ) {
			if ( ! self::is_content_image( $placement, $xobjects, $footer_cutoff_px ) ) {
				continue;
			}

			$written = self::write_unseen( $xobjects[ $placement['name'] ], $seen, $page_index, count( $images ) + 1, $img_out_dir );
			if ( $written !== null ) {
				$images[] = $written;
			}
		}

		return $images;
	}


	/**
	 * Write an XObject's image unless the same bytes were already written.
	 *
	 * The same XObject is often drawn more than once on a page, and a repeat
	 * would otherwise import as a duplicate attachment.
	 *
	 * @param  object $xobject     PdfParser XObject.
	 * @param  array  $seen        Hashes already written, updated in place.
	 * @param  int    $page_index  0-based page index.
	 * @param  int    $img_num     1-based image counter within the page.
	 * @param  string $img_out_dir Destination directory.
	 * @return array|null Image metadata, or null when nothing was written.
	 */
	private static function write_unseen( object $xobject, array &$seen, int $page_index, int $img_num, string $img_out_dir ): ?array {
		$blob = self::image_bytes( $xobject );
		if ( $blob === null ) {
			return null;
		}

		$hash = md5( $blob );
		if ( isset( $seen[ $hash ] ) ) {
			return null;
		}
		$seen[ $hash ] = true;

		return self::write_image( $blob, $page_index, $img_num, $img_out_dir );
	}


	/**
	 * Return true when a placement is real page content rather than furniture.
	 *
	 * Rejects anything drawn in the footer band — where template logos live —
	 * and anything too small on screen to be worth importing, such as a rule or
	 * a bullet drawn as an image.
	 *
	 * @param  array $placement        Placement rectangle from read_placements().
	 * @param  array $xobjects         The page's XObjects, keyed by name.
	 * @param  int   $footer_cutoff_px Footer cutoff in pixels from the page top.
	 * @return bool
	 */
	private static function is_content_image( array $placement, array $xobjects, int $footer_cutoff_px ): bool {
		if ( ! isset( $xobjects[ $placement['name'] ] ) ) {
			return false;
		}

		return $placement['top'] < $footer_cutoff_px
			&& $placement['width'] >= self::MIN_SIDE_PX
			&& $placement['height'] >= self::MIN_SIDE_PX;
	}


	// ── Content stream ────────────────────────────────────────────────────────

	/**
	 * Find every image draw on a page and the rectangle it occupies.
	 *
	 * @param  Page  $page           PdfParser page.
	 * @param  float $page_height_pt Page height in points.
	 * @return array<array{name: string, top: float, width: float, height: float}>
	 */
	private static function read_placements( Page $page, float $page_height_pt ): array {
		$stream = self::content_stream( $page );
		if ( $stream === '' ) {
			return array();
		}

		$state = array(
			'ctm'        => self::IDENTITY_MATRIX,
			'stack'      => array(),
			'placements' => array(),
		);
		$operands = array();

		foreach ( self::tokenize( $stream ) as $token ) {
			// Numbers and names are operands; they accumulate until an operator
			// consumes them.
			if ( is_float( $token ) ) {
				$operands[] = $token;
				continue;
			}

			if ( is_array( $token ) ) {
				$operands[] = $token[0];
				continue;
			}

			$state    = self::apply_operator( $state, (string) $token, $operands, $page_height_pt );
			$operands = array();
		}

		return $state['placements'];
	}


	/**
	 * Apply one content-stream operator to the graphics state.
	 *
	 * Only the operators that affect image placement are interpreted; every
	 * other operator simply consumes its operands and leaves the state alone.
	 *
	 * @param  array  $state          { ctm: array, stack: array, placements: array }.
	 * @param  string $operator       Operator token.
	 * @param  array  $operands       Operands collected since the last operator.
	 * @param  float  $page_height_pt Page height in points.
	 * @return array Updated state.
	 */
	private static function apply_operator( array $state, string $operator, array $operands, float $page_height_pt ): array {
		if ( $operator === 'q' ) {
			$state['stack'][] = $state['ctm'];
			return $state;
		}

		if ( $operator === 'Q' ) {
			$state['ctm'] = array_pop( $state['stack'] ) ?? self::IDENTITY_MATRIX;
			return $state;
		}

		if ( $operator === 'cm' && count( $operands ) >= 6 ) {
			$state['ctm'] = self::multiply( array_map( 'floatval', array_slice( $operands, -6 ) ), $state['ctm'] );
			return $state;
		}

		$name = end( $operands );
		if ( $operator === 'Do' && is_string( $name ) && $name !== '' ) {
			$state['placements'][] = self::placement( $name, $state['ctm'], $page_height_pt );
		}

		return $state;
	}


	/**
	 * Read a page's decoded content stream.
	 *
	 * @param  Page $page PdfParser page.
	 * @return string Decoded stream, or '' when unavailable.
	 */
	private static function content_stream( Page $page ): string {
		try {
			$contents = $page->get( 'Contents' );
			if ( ! $contents ) {
				return '';
			}

			$content = $contents->getContent();
			return is_array( $content ) ? self::join_sections( $content ) : (string) $content;
		} catch ( \Throwable $e ) {
			Utils::log( 'PDF content stream read failed.', array( 'msg' => $e->getMessage() ) );
		}

		return '';
	}


	/**
	 * Concatenate a page whose content stream is split across several objects.
	 *
	 * @param  array $sections Content stream parts.
	 * @return string
	 */
	private static function join_sections( array $sections ): string {
		$stream = '';

		foreach ( $sections as $section ) {
			$stream .= is_object( $section ) ? (string) $section->getContent() : (string) $section;
		}

		return $stream;
	}


	/**
	 * Split a content stream into numbers, names and operators.
	 *
	 * Strings, dictionaries and inline images are not needed for placement and
	 * are skipped wholesale so their contents can never be mistaken for
	 * operators.
	 *
	 * @param  string $stream Decoded content stream.
	 * @return array<float|string|array{0: string}> Tokens in stream order.
	 */
	private static function tokenize( string $stream ): array {
		$tokens = array();
		$length = strlen( $stream );
		$i      = 0;

		while ( $i < $length ) {
			$skipped = self::skip_non_token( $stream, $i );
			$i       = $skipped ?? self::read_token( $stream, $i, $tokens );
		}

		return $tokens;
	}


	/**
	 * Skip over stream content that yields no token.
	 *
	 * @param  string $stream Content stream.
	 * @param  int    $i      Current offset.
	 * @return int|null Offset past the skipped content, or null when a token
	 *                  starts at $i.
	 */
	private static function skip_non_token( string $stream, int $i ): ?int {
		$char = $stream[ $i ];

		// Whitespace, and the bracket characters that only delimit structures
		// this scanner does not need to understand.
		if ( strpos( self::WHITESPACE . '<>[]{}', $char ) !== false ) {
			return $i + 1;
		}

		if ( $char === '%' ) {
			return self::skip_to( $stream, $i, "\r\n" );
		}

		if ( $char === '(' ) {
			return self::skip_literal_string( $stream, $i );
		}

		return null;
	}


	/**
	 * Read one name, number or operator token and append it.
	 *
	 * @param  string $stream  Content stream.
	 * @param  int    $i       Offset of the token's first character.
	 * @param  array  $tokens  Token list, appended to in place.
	 * @return int Offset just past the token.
	 */
	private static function read_token( string $stream, int $i, array &$tokens ): int {
		if ( $stream[ $i ] === '/' ) {
			$end      = self::scan_token_end( $stream, $i + 1 );
			$tokens[] = array( substr( $stream, $i + 1, $end - $i - 1 ) );
			return $end;
		}

		$end  = self::scan_token_end( $stream, $i );
		$word = substr( $stream, $i, $end - $i );

		if ( $word === '' ) {
			return $end + 1;
		}

		$tokens[] = is_numeric( $word ) ? (float) $word : $word;

		// An inline image's binary data would tokenize as garbage; skip to EI.
		return $word === 'BI' ? self::skip_inline_image( $stream, $end ) : $end;
	}


	/**
	 * Advance to the first delimiter at or after an offset.
	 *
	 * @param  string $stream Content stream.
	 * @param  int    $i      Starting offset.
	 * @return int Offset of the delimiter, or the stream length.
	 */
	private static function scan_token_end( string $stream, int $i ): int {
		$length = strlen( $stream );

		while ( $i < $length && ! self::is_delimiter( $stream[ $i ] ) ) {
			$i++;
		}

		return $i;
	}


	/**
	 * Return true when a character ends a PDF token.
	 *
	 * @param string $char Single character.
	 */
	private static function is_delimiter( string $char ): bool {
		return strpos( self::WHITESPACE . '()<>[]{}/%', $char ) !== false;
	}


	/**
	 * Advance past the next occurrence of any of the given characters.
	 *
	 * @param  string $stream Content stream.
	 * @param  int    $i      Current offset.
	 * @param  string $chars  Characters to stop at.
	 * @return int New offset.
	 */
	private static function skip_to( string $stream, int $i, string $chars ): int {
		$length = strlen( $stream );
		while ( $i < $length && strpos( $chars, $stream[ $i ] ) === false ) {
			$i++;
		}
		return $i;
	}


	/**
	 * Advance past a balanced literal string starting at an opening paren.
	 *
	 * @param  string $stream Content stream.
	 * @param  int    $i      Offset of the '('.
	 * @return int Offset just past the closing ')'.
	 */
	private static function skip_literal_string( string $stream, int $i ): int {
		$length = strlen( $stream );
		$depth  = 0;

		while ( $i < $length ) {
			$char = $stream[ $i ];
			if ( $char === '\\' ) {
				$i += 2;
				continue;
			}
			if ( $char === '(' ) {
				$depth++;
			} elseif ( $char === ')' ) {
				$depth--;
				if ( $depth === 0 ) {
					return $i + 1;
				}
			}
			$i++;
		}

		return $i;
	}


	/**
	 * Advance past an inline image's binary data to its EI marker.
	 *
	 * @param  string $stream Content stream.
	 * @param  int    $i      Offset just past the BI operator.
	 * @return int Offset just past 'EI'.
	 */
	private static function skip_inline_image( string $stream, int $i ): int {
		$end = strpos( $stream, 'EI', $i );
		return $end === false ? strlen( $stream ) : $end + 2;
	}


	// ── Geometry ──────────────────────────────────────────────────────────────

	/**
	 * Convert a current transformation matrix into a placement rectangle.
	 *
	 * An image XObject is drawn into the unit square, so the CTM's scale terms
	 * are its on-page size and its translation terms one of its corners.
	 *
	 * @param  string $name           XObject name.
	 * @param  array  $ctm            Current transformation matrix.
	 * @param  float  $page_height_pt Page height in points.
	 * @return array{name: string, top: float, width: float, height: float}
	 */
	private static function placement( string $name, array $ctm, float $page_height_pt ): array {
		// Corners of the transformed unit square (rotation and skew included).
		$xs = array( $ctm[4], $ctm[0] + $ctm[4], $ctm[2] + $ctm[4], $ctm[0] + $ctm[2] + $ctm[4] );
		$ys = array( $ctm[5], $ctm[1] + $ctm[5], $ctm[3] + $ctm[5], $ctm[1] + $ctm[3] + $ctm[5] );

		$top_pt = $page_height_pt - max( $ys );

		return array(
			'name'   => $name,
			'top'    => $top_pt * self::PX_PER_PT,
			'width'  => ( max( $xs ) - min( $xs ) ) * self::PX_PER_PT,
			'height' => ( max( $ys ) - min( $ys ) ) * self::PX_PER_PT,
		);
	}


	/**
	 * Multiply two PDF transformation matrices ($a applied before $b).
	 *
	 * @param  array $a Six-element matrix.
	 * @param  array $b Six-element matrix.
	 * @return array Six-element product matrix.
	 */
	private static function multiply( array $a, array $b ): array {
		return array(
			$a[0] * $b[0] + $a[1] * $b[2],
			$a[0] * $b[1] + $a[1] * $b[3],
			$a[2] * $b[0] + $a[3] * $b[2],
			$a[2] * $b[1] + $a[3] * $b[3],
			$a[4] * $b[0] + $a[5] * $b[2] + $b[4],
			$a[4] * $b[1] + $a[5] * $b[3] + $b[5],
		);
	}


	// ── Image bytes ───────────────────────────────────────────────────────────

	/**
	 * Return an XObject's bytes when they form a complete image file.
	 *
	 * PDF images compressed with DCTDecode or JPXDecode are already JPEG/JPEG
	 * 2000 files. Images stored as raw samples (FlateDecode and friends) would
	 * need re-encoding from their colour space and bit depth, which is out of
	 * scope — those are skipped rather than written as unopenable files.
	 *
	 * @param  object $xobject PdfParser XObject.
	 * @return string|null Image bytes, or null when not directly usable.
	 */
	private static function image_bytes( object $xobject ): ?string {
		try {
			$details = $xobject->getDetails( false );
			if ( ( $details['Subtype'] ?? '' ) !== 'Image' ) {
				return null;
			}

			$content = $xobject->getContent();
			if ( ! is_string( $content ) || $content === '' || strlen( $content ) > self::MAX_IMAGE_BYTES ) {
				return null;
			}

			return self::extension_for( $content ) === null ? null : $content;
		} catch ( \Throwable $e ) {
			return null;
		}
	}


	/**
	 * Identify an image's file extension from its magic bytes.
	 *
	 * @param  string $blob Image bytes.
	 * @return string|null Extension, or null when the bytes are not a known image.
	 */
	private static function extension_for( string $blob ): ?string {
		foreach ( self::MAGIC_EXTENSIONS as $magic => $ext ) {
			if ( str_starts_with( $blob, $magic ) ) {
				return $ext;
			}
		}
		return null;
	}


	/**
	 * Write image bytes into the job's image directory.
	 *
	 * @param  string $blob        Image bytes.
	 * @param  int    $page_index  0-based page index.
	 * @param  int    $img_num     1-based image counter within the page.
	 * @param  string $img_out_dir Destination directory.
	 * @return array|null Image metadata, or null when the write failed.
	 */
	private static function write_image( string $blob, int $page_index, int $img_num, string $img_out_dir ): ?array {
		$ext      = self::extension_for( $blob );
		$filename = sprintf( 'slide_%03d_img_%02d.%s', $page_index + 1, $img_num, $ext );
		$dest     = trailingslashit( $img_out_dir ) . $filename;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( ! file_put_contents( $dest, $blob ) ) {
			return null;
		}

		return array(
			'path'     => $dest,
			'filename' => $filename,
			'ext'      => $ext,
		);
	}
}
