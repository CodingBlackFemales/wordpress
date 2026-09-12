<?php
/**
 * Reconstructs positioned, styled text blocks from a PDF page.
 *
 * A PDF carries no paragraph structure — only glyphs at coordinates — so this
 * class rebuilds it: glyph chunks are grouped into lines by baseline, lines into
 * blocks by proximity and alignment, and wrapped lines are rejoined into
 * paragraphs. The result is the same positioned TextBox IR that PhpPresentation
 * shapes produce, so Document\BlockLayout handles both identically.
 *
 * PdfParser is configured with setDataTmFontInfoHasToBeIncluded(), which makes
 * getDataTm() return the font id and size alongside each chunk's text matrix.
 * That is the only source of font information available per chunk, and it is
 * what drives bold/italic/monospace detection (via the embedded font name) and
 * heading detection (via the effective size).
 *
 * COORDINATES: getDataTm() reports PDF user space — origin bottom-left, y up,
 * units of points. Everything emitted here is converted to top-left origin,
 * y down, in pixels at 96 DPI, matching the PPTX parser's units.
 *
 * @class   Pdf\TextExtractor
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Pdf;

use CodingBlackFemales\SlidesImporter\Document\Ir;
use Smalot\PdfParser\Page;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TextExtractor class.
 */
final class TextExtractor {

	/** Points-to-pixels conversion: 96 DPI screen pixels per 72 DPI point. */
	const PX_PER_PT = 96 / 72;

	/**
	 * Two glyph chunks share a line when their baselines differ by less than
	 * this fraction of the larger font size.
	 */
	const BASELINE_TOLERANCE = 0.35;

	/**
	 * How far a chunk's advance may exceed the line's typical per-character
	 * advance before the space it represents is re-inserted.
	 *
	 * Font metrics are not reachable through PdfParser's public API, so the
	 * line's own median advance per character stands in for the average glyph
	 * width. Comparing against that rather than a fixed fraction of the font
	 * size is what keeps wide capitals ("What Are…") and deliberately tracked
	 * text from being broken apart, while still recovering the spaces in PDFs
	 * that position words instead of drawing space glyphs.
	 */
	const SPACE_EXCESS_RATIO = 0.35;

	/**
	 * The excess threshold applied when the preceding chunk is a single glyph.
	 *
	 * One character gives no averaging, so ordinary width variation between a
	 * narrow "i" and a wide "W" looks like a gap. Only a clearly oversized step
	 * counts as a space there. The trade-off is that a PDF which positions every
	 * glyph individually *and* omits space glyphs keeps its words run together;
	 * exporters that place glyphs one at a time draw real spaces, so that
	 * combination does not arise in practice.
	 */
	const SPACE_EXCESS_RATIO_SINGLE = 0.9;

	/**
	 * Fallback per-character advance, as a fraction of the font size, for lines
	 * too short to yield a meaningful median.
	 */
	const GLYPH_WIDTH_RATIO = 0.5;

	/**
	 * Consecutive lines join the same block when their vertical gap is under
	 * this multiple of the line height.
	 */
	const BLOCK_GAP_RATIO = 1.6;

	/**
	 * Consecutive lines join the same block only when their horizontal extents
	 * overlap by at least this fraction of the narrower line.
	 */
	const BLOCK_OVERLAP_MIN = 0.35;

	/**
	 * A line whose right edge reaches within this fraction of the block's width
	 * of the block's right edge is treated as wrapped, so the following line
	 * continues the same paragraph.
	 */
	const WRAP_EDGE_RATIO = 0.15;

	/**
	 * A paragraph is a heading when its font size is at least this multiple of
	 * the page's body size.
	 */
	const HEADING_SIZE_RATIO = 1.25;

	/**
	 * A page whose monospaced share reaches this fraction is treated as being
	 * set in a monospaced body font, disabling code detection for that page.
	 */
	const MONO_PAGE_MAX_SHARE = 0.5;

	/** A heading candidate longer than this many characters is treated as body text. */
	const HEADING_MAX_CHARS = 120;

	/**
	 * Fraction of the page height within which the largest text is taken as the
	 * page title.
	 */
	const TITLE_ZONE_RATIO = 0.42;

	/** The page title must be at least this multiple of the body size. */
	const TITLE_SIZE_RATIO = 1.3;


	/**
	 * Extract a page's text as positioned boxes plus its title.
	 *
	 * @param  Page  $page             PdfParser page.
	 * @param  float $page_width_pt    Page width in points.
	 * @param  float $page_height_pt   Page height in points.
	 * @param  int   $footer_cutoff_px Top-edge pixel offset below which text is
	 *                                 treated as page furniture and dropped.
	 * @return array{title: string, boxes: array} Page title and TextBox arrays.
	 */
	public static function extract( Page $page, float $page_width_pt, float $page_height_pt, int $footer_cutoff_px ): array {
		$chunks = self::read_chunks( $page );
		if ( empty( $chunks ) ) {
			return array(
				'title' => '',
				'boxes' => array(),
			);
		}

		$lines = self::group_into_lines( $chunks, $page_height_pt );
		$lines = self::drop_footer_lines( $lines, $footer_cutoff_px );
		if ( empty( $lines ) ) {
			return array(
				'title' => '',
				'boxes' => array(),
			);
		}

		$body_size = self::body_size( $lines );
		$title     = self::take_title( $lines, $page_height_pt, $body_size );

		// A page whose body font is itself monospaced (some decks set all their
		// text in Consolas) gives the font name no power to identify code, so
		// nothing on it is promoted to a code block.
		if ( ! self::mono_is_distinctive( $lines ) ) {
			$lines = self::clear_mono( $lines );
		}

		return array(
			'title' => $title,
			'boxes' => self::build_boxes( $lines, $body_size ),
		);
	}


	// ── Stage 1: glyph chunks ─────────────────────────────────────────────────

	/**
	 * Read every positioned text chunk from a page.
	 *
	 * @param  Page $page PdfParser page.
	 * @return array<array{x: float, y: float, size: float, font: string, text: string}>
	 */
	private static function read_chunks( Page $page ): array {
		$fonts  = $page->getFonts();
		$chunks = array();

		foreach ( $page->getDataTm() as $entry ) {
			$chunk = self::to_chunk( $entry, $fonts );
			if ( $chunk !== null ) {
				$chunks[] = $chunk;
			}
		}

		return $chunks;
	}


	/**
	 * Convert one getDataTm() entry into a positioned chunk.
	 *
	 * The entry is [ text matrix, text, font id, font size ]; the font id and
	 * size are only present because the parser is configured with
	 * setDataTmFontInfoHasToBeIncluded().
	 *
	 * @param  array $entry getDataTm() entry.
	 * @param  array $fonts The page's fonts, keyed by id.
	 * @return array|null Chunk, or null when the entry carries no text.
	 */
	private static function to_chunk( array $entry, array $fonts ): ?array {
		$matrix = $entry[0] ?? null;
		$text   = (string) ( $entry[1] ?? '' );

		if ( ! is_array( $matrix ) || $text === '' ) {
			return null;
		}

		return array(
			'x'    => (float) $matrix[4],
			'y'    => (float) $matrix[5],
			'size' => self::chunk_size( $entry, $matrix ),
			'font' => self::font_name( $fonts, $entry[2] ?? null ),
			'text' => $text,
		);
	}


	/**
	 * Resolve a chunk's on-page font size in points.
	 *
	 * The declared size is in text space; the text matrix's vertical scale
	 * converts it to points on the page.
	 *
	 * @param  array $entry  getDataTm() entry.
	 * @param  array $matrix The entry's text matrix.
	 * @return float Size in points; 1.0 when the entry declares none.
	 */
	private static function chunk_size( array $entry, array $matrix ): float {
		$size = (float) ( $entry[3] ?? 0 ) * abs( (float) $matrix[3] );
		return $size > 0 ? $size : 1.0;
	}


	/**
	 * Look up a font's name by the id a chunk was drawn with.
	 *
	 * @param  array $fonts   The page's fonts, keyed by id.
	 * @param  mixed $font_id Font id from the chunk, if any.
	 * @return string Font name, or '' when unknown.
	 */
	private static function font_name( array $fonts, $font_id ): string {
		if ( $font_id === null || ! isset( $fonts[ $font_id ] ) ) {
			return '';
		}
		return (string) $fonts[ $font_id ]->getName();
	}


	// ── Stage 2: lines ────────────────────────────────────────────────────────

	/**
	 * Group glyph chunks into lines sharing a baseline.
	 *
	 * @param  array $chunks         Chunks from read_chunks().
	 * @param  float $page_height_pt Page height in points (for the y flip).
	 * @return array Line arrays.
	 */
	private static function group_into_lines( array $chunks, float $page_height_pt ): array {
		// Sort top-to-bottom (descending PDF y), then left-to-right.
		usort(
			$chunks,
			static function ( array $a, array $b ): int {
				$dy = $b['y'] <=> $a['y'];
				return $dy !== 0 ? $dy : ( $a['x'] <=> $b['x'] );
			}
		);

		$lines   = array();
		$current = array();

		foreach ( $chunks as $chunk ) {
			if ( empty( $current ) ) {
				$current = array( $chunk );
				continue;
			}

			$reference = $current[0];
			$tolerance = max( $reference['size'], $chunk['size'] ) * self::BASELINE_TOLERANCE;

			if ( abs( $chunk['y'] - $reference['y'] ) <= $tolerance ) {
				$current[] = $chunk;
				continue;
			}

			$lines[] = self::assemble_line( $current, $page_height_pt );
			$current = array( $chunk );
		}

		if ( ! empty( $current ) ) {
			$lines[] = self::assemble_line( $current, $page_height_pt );
		}

		return array_values(
			array_filter(
				$lines,
				static fn( ?array $line ): bool => $line !== null
			)
		);
	}


	/**
	 * Assemble one line's chunks into text, runs and a bounding box.
	 *
	 * @param  array $chunks         Chunks sharing a baseline.
	 * @param  float $page_height_pt Page height in points.
	 * @return array|null Line array, or null when the line holds only whitespace.
	 */
	private static function assemble_line( array $chunks, float $page_height_pt ): ?array {
		usort( $chunks, static fn( array $a, array $b ): int => $a['x'] <=> $b['x'] );

		$glyph_width = self::median_glyph_width( $chunks );

		$runs       = array();
		$text       = '';
		$previous   = null;
		$max_size   = 0.0;
		$mono_chars = 0;
		$all_chars  = 0;
		$left       = $chunks[0]['x'];
		$baseline   = $chunks[0]['y'];

		foreach ( $chunks as $chunk ) {
			if ( $previous !== null && self::needs_space( $previous, $chunk, $text, $glyph_width ) ) {
				$text  .= ' ';
				$runs[] = Ir::run( ' ' );
			}

			$chars       = mb_strlen( trim( $chunk['text'] ) );
			$all_chars  += $chars;
			$mono_chars += Ir::is_mono_font( $chunk['font'] ) ? $chars : 0;

			$text    .= $chunk['text'];
			$runs[]   = self::chunk_run( $chunk );
			$max_size = max( $max_size, $chunk['size'] );
			$previous = $chunk;
		}

		if ( trim( $text ) === '' ) {
			return null;
		}

		$last  = $chunks[ count( $chunks ) - 1 ];
		$right = $last['x'] + mb_strlen( $last['text'] ) * $glyph_width;

		// PDF space is y-up from the bottom; convert the baseline to a top edge.
		$top = $page_height_pt - $baseline - $max_size * 0.8;

		return array(
			'text'       => $text,
			'runs'       => self::merge_runs( $runs ),
			'size'       => $max_size,
			'mono'       => $all_chars > 0 && $mono_chars === $all_chars,
			'mono_chars' => $mono_chars,
			'all_chars'  => $all_chars,
			'l'          => $left * self::PX_PER_PT,
			't'          => $top * self::PX_PER_PT,
			'w'          => max( 1.0, $right - $left ) * self::PX_PER_PT,
			'h'          => max( 1.0, $max_size * 1.2 ) * self::PX_PER_PT,
		);
	}


	/**
	 * Estimate the typical advance per character on a line.
	 *
	 * Chunks are usually single glyphs, so the median distance between
	 * consecutive chunk origins — normalised by the number of characters in the
	 * earlier chunk — approximates the line's average glyph width whether the
	 * exporter emitted one glyph, one word or one whole line per operator.
	 *
	 * @param  array $chunks Chunks on one line, ordered left to right.
	 * @return float Advance per character, in points.
	 */
	private static function median_glyph_width( array $chunks ): float {
		$advances = array();

		for ( $i = 1; $i < count( $chunks ); $i++ ) {
			$chars = max( 1, mb_strlen( $chunks[ $i - 1 ]['text'] ) );
			$step  = ( $chunks[ $i ]['x'] - $chunks[ $i - 1 ]['x'] ) / $chars;
			if ( $step > 0 ) {
				$advances[] = $step;
			}
		}

		if ( empty( $advances ) ) {
			return $chunks[0]['size'] * self::GLYPH_WIDTH_RATIO;
		}

		sort( $advances );
		return $advances[ intdiv( count( $advances ), 2 ) ];
	}


	/**
	 * Decide whether a word space belongs between two adjacent chunks.
	 *
	 * @param array $previous    The preceding chunk.
	 * @param array $chunk       The chunk about to be appended.
	 * @param string $text       Line text accumulated so far.
	 * @param float $glyph_width Median advance per character on this line.
	 */
	private static function needs_space( array $previous, array $chunk, string $text, float $glyph_width ): bool {
		if ( $chunk['text'] === '' || $glyph_width <= 0 ) {
			return false;
		}

		// Never double up on a space the content stream already drew.
		if ( preg_match( '/\s$/', $text ) || preg_match( '/^\s/', $chunk['text'] ) ) {
			return false;
		}

		$chars    = mb_strlen( $previous['text'] );
		$excess   = ( $chunk['x'] - $previous['x'] ) - $chars * $glyph_width;
		$ratio    = $chars > 1 ? self::SPACE_EXCESS_RATIO : self::SPACE_EXCESS_RATIO_SINGLE;

		return $excess > $glyph_width * $ratio;
	}


	/**
	 * Return true when a monospaced font actually distinguishes code on a page.
	 *
	 * Font name is the only code signal a PDF offers, so it is only trustworthy
	 * when the page's dominant text is set in something else. Decks that put all
	 * their body copy in Consolas are common enough that, without this check,
	 * every slide would import as one long code block.
	 *
	 * Counting is done over characters rather than lines so that a bullet glyph
	 * drawn in a proportional font does not make a monospaced line look mixed.
	 *
	 * @param  array $lines Line arrays.
	 * @return bool
	 */
	private static function mono_is_distinctive( array $lines ): bool {
		$mono = 0;
		$all  = 0;

		foreach ( $lines as $line ) {
			$all  += $line['all_chars'];
			$mono += $line['mono_chars'];
		}

		return $all > 0 && ( $mono / $all ) < self::MONO_PAGE_MAX_SHARE;
	}


	/**
	 * Clear the monospace flag on every line of a page.
	 *
	 * @param  array $lines Line arrays.
	 * @return array Line arrays.
	 */
	private static function clear_mono( array $lines ): array {
		foreach ( $lines as &$line ) {
			$line['mono'] = false;
		}
		unset( $line );
		return $lines;
	}


	/**
	 * Build an IR run for one glyph chunk, styled from its font name.
	 *
	 * @param  array $chunk Chunk array.
	 * @return array Run.
	 */
	private static function chunk_run( array $chunk ): array {
		$flags         = Ir::font_style_flags( $chunk['font'] );
		$flags['mono'] = false; // Applied at paragraph level as a code block.
		return Ir::run( $chunk['text'], $flags );
	}


	/**
	 * Merge adjacent runs that share identical formatting.
	 *
	 * PDFs emit one chunk per glyph, so without this every character becomes its
	 * own run and the rendered HTML fills with single-letter <strong> tags.
	 *
	 * @param  array $runs Run arrays.
	 * @return array Run arrays.
	 */
	private static function merge_runs( array $runs ): array {
		$merged = array();

		foreach ( $runs as $run ) {
			$last = count( $merged ) - 1;
			if ( $last >= 0 && self::same_formatting( $merged[ $last ], $run ) ) {
				$merged[ $last ]['text'] .= $run['text'];
				continue;
			}
			$merged[] = $run;
		}

		return $merged;
	}


	/**
	 * Return true when two runs carry the same formatting flags.
	 *
	 * @param array $a First run.
	 * @param array $b Second run.
	 */
	private static function same_formatting( array $a, array $b ): bool {
		foreach ( array( 'bold', 'italic', 'underline', 'strike', 'mono', 'link' ) as $key ) {
			if ( $a[ $key ] !== $b[ $key ] ) {
				return false;
			}
		}
		return true;
	}


	/**
	 * Drop lines that sit in the page's footer zone.
	 *
	 * @param  array $lines            Line arrays.
	 * @param  int   $footer_cutoff_px Cutoff in pixels from the page top.
	 * @return array Line arrays.
	 */
	private static function drop_footer_lines( array $lines, int $footer_cutoff_px ): array {
		return array_values(
			array_filter(
				$lines,
				static fn( array $line ): bool => $line['t'] < $footer_cutoff_px
			)
		);
	}


	// ── Stage 3: blocks and paragraphs ────────────────────────────────────────

	/**
	 * Determine the page's body font size as the most common line size.
	 *
	 * Sizes are bucketed to one decimal place so that near-identical sizes from
	 * rounding do not split the mode.
	 *
	 * @param  array $lines Line arrays.
	 * @return float Body font size in points.
	 */
	private static function body_size( array $lines ): float {
		$counts = array();
		foreach ( $lines as $line ) {
			$key            = (string) round( $line['size'], 1 );
			$counts[ $key ] = ( $counts[ $key ] ?? 0 ) + mb_strlen( $line['text'] );
		}

		if ( empty( $counts ) ) {
			return 0.0;
		}

		arsort( $counts );
		return (float) array_key_first( $counts );
	}


	/**
	 * Remove and return the page's title line.
	 *
	 * The title is the largest text in the top zone of the page, provided it is
	 * meaningfully larger than the body text. Slide-style PDFs always have one;
	 * dense text pages usually do not, and get an empty title.
	 *
	 * @param  array $lines          Line arrays, modified in place.
	 * @param  float $page_height_pt Page height in points.
	 * @param  float $body_size      Body font size in points.
	 * @return string Title text, or '' when the page has no distinct title.
	 */
	private static function take_title( array &$lines, float $page_height_pt, float $body_size ): string {
		$zone_px   = $page_height_pt * self::PX_PER_PT * self::TITLE_ZONE_RATIO;
		$min_size  = $body_size * self::TITLE_SIZE_RATIO;
		$best_idx  = null;
		$best_size = 0.0;

		foreach ( $lines as $idx => $line ) {
			if ( $line['t'] > $zone_px || $line['size'] < $min_size || $line['mono'] ) {
				continue;
			}
			if ( $line['size'] > $best_size ) {
				$best_size = $line['size'];
				$best_idx  = $idx;
			}
		}

		if ( $best_idx === null ) {
			return '';
		}

		$title = trim( $lines[ $best_idx ]['text'] );
		unset( $lines[ $best_idx ] );
		$lines = array_values( $lines );

		return $title;
	}


	/**
	 * Group lines into positioned blocks of paragraphs.
	 *
	 * @param  array $lines     Line arrays in reading order.
	 * @param  float $body_size Body font size in points.
	 * @return array TextBox arrays.
	 */
	private static function build_boxes( array $lines, float $body_size ): array {
		$boxes = array();

		foreach ( self::group_into_blocks( $lines ) as $block ) {
			$paragraphs = self::block_paragraphs( $block, $body_size );
			if ( empty( $paragraphs ) ) {
				continue;
			}

			$left   = min( array_column( $block, 'l' ) );
			$top    = min( array_column( $block, 't' ) );
			$right  = max( array_map( static fn( array $l ): float => $l['l'] + $l['w'], $block ) );
			$bottom = max( array_map( static fn( array $l ): float => $l['t'] + $l['h'], $block ) );

			$boxes[] = Ir::box( $left, $top, $right - $left, $bottom - $top, $paragraphs );
		}

		return $boxes;
	}


	/**
	 * Group consecutive lines into blocks by vertical proximity and alignment.
	 *
	 * @param  array $lines Line arrays in reading order.
	 * @return array<array> List of blocks, each an array of lines.
	 */
	private static function group_into_blocks( array $lines ): array {
		$blocks  = array();
		$current = array();

		foreach ( $lines as $line ) {
			if ( empty( $current ) ) {
				$current = array( $line );
				continue;
			}

			$previous = $current[ count( $current ) - 1 ];
			if ( self::lines_adjoin( $previous, $line ) ) {
				$current[] = $line;
				continue;
			}

			$blocks[] = $current;
			$current  = array( $line );
		}

		if ( ! empty( $current ) ) {
			$blocks[] = $current;
		}

		return $blocks;
	}


	/**
	 * Return true when two consecutive lines belong to the same block.
	 *
	 * @param array $a Earlier line.
	 * @param array $b Following line.
	 */
	private static function lines_adjoin( array $a, array $b ): bool {
		if ( $a['mono'] !== $b['mono'] ) {
			return false;
		}

		$gap = $b['t'] - ( $a['t'] + $a['h'] );
		if ( $gap > max( $a['h'], $b['h'] ) * self::BLOCK_GAP_RATIO ) {
			return false;
		}

		$overlap = min( $a['l'] + $a['w'], $b['l'] + $b['w'] ) - max( $a['l'], $b['l'] );
		$narrow  = min( $a['w'], $b['w'] );

		return $narrow > 0 && ( $overlap / $narrow ) >= self::BLOCK_OVERLAP_MIN;
	}


	/**
	 * Convert a block's lines into IR paragraphs, rejoining wrapped lines.
	 *
	 * @param  array $block     Lines in one block.
	 * @param  float $body_size Body font size in points.
	 * @return array Paragraph arrays.
	 */
	private static function block_paragraphs( array $block, float $body_size ): array {
		$block_right = max( array_map( static fn( array $l ): float => $l['l'] + $l['w'], $block ) );
		$block_left  = min( array_column( $block, 'l' ) );
		$wrap_margin = max( 1.0, ( $block_right - $block_left ) * self::WRAP_EDGE_RATIO );

		$paragraphs = array();
		$pending    = null;

		foreach ( $block as $line ) {
			$bullet = $line['mono'] ? null : Ir::split_bullet_marker( $line['text'] );
			$starts = $bullet !== null || self::starts_enumerated_item( $line['text'] );

			if ( $pending !== null && ! $starts && self::continues_paragraph( $pending, $line ) ) {
				$pending = self::append_line( $pending, $line );
				continue;
			}

			if ( $pending !== null ) {
				$paragraphs[] = self::finalise( $pending, $body_size );
			}

			$pending = self::start_paragraph( $line, $bullet, $wrap_margin, $block_right );
		}

		if ( $pending !== null ) {
			$paragraphs[] = self::finalise( $pending, $body_size );
		}

		return array_values( array_filter( $paragraphs ) );
	}


	/**
	 * Return true when a line opens a numbered item such as "3. Apply …".
	 *
	 * Such a line never continues the previous one, even when that line ran to
	 * the block's right edge. The number is left in the text rather than turned
	 * into an ordered list, because a PDF gives no way to tell a real list from
	 * a sentence that happens to start with a figure.
	 *
	 * @param string $text Line text.
	 */
	private static function starts_enumerated_item( string $text ): bool {
		return (bool) preg_match( '/^\s*\(?\d{1,3}[.)]\s/', $text );
	}


	/**
	 * Begin a pending paragraph from a line.
	 *
	 * @param  array      $line        Line array.
	 * @param  array|null $bullet      Result of Ir::split_bullet_marker(), if any.
	 * @param  float      $wrap_margin Distance from the block's right edge that
	 *                                 still counts as a full-width line.
	 * @param  float      $block_right Block's right edge in pixels.
	 * @return array Pending-paragraph state.
	 */
	private static function start_paragraph( array $line, ?array $bullet, float $wrap_margin, float $block_right ): array {
		$runs = $line['runs'];
		if ( $bullet !== null ) {
			$runs = self::strip_marker( $runs, $line['text'], $bullet['text'] );
		}

		return array(
			'runs'        => $runs,
			'text'        => $bullet !== null ? $bullet['text'] : $line['text'],
			'bullet'      => $bullet !== null,
			'mono'        => $line['mono'],
			'size'        => $line['size'],
			'wrapped'     => ( $line['l'] + $line['w'] ) >= ( $block_right - $wrap_margin ),
			'wrap_margin' => $wrap_margin,
			'block_right' => $block_right,
		);
	}


	/**
	 * Return true when a line continues the pending paragraph rather than
	 * starting a new one.
	 *
	 * A paragraph only continues when its previous line ran to the block's right
	 * edge — the reliable signal that the text wrapped rather than ended. Code
	 * blocks never merge, since their line breaks are significant.
	 *
	 * @param array $pending Pending-paragraph state.
	 * @param array $line    Candidate next line.
	 */
	private static function continues_paragraph( array $pending, array $line ): bool {
		if ( $pending['mono'] || $line['mono'] ) {
			return false;
		}
		if ( ! $pending['wrapped'] ) {
			return false;
		}
		// A size change signals a new paragraph even mid-block.
		return abs( $pending['size'] - $line['size'] ) < 0.6;
	}


	/**
	 * Append a wrapped line to the pending paragraph.
	 *
	 * @param  array $pending Pending-paragraph state.
	 * @param  array $line    Line to append.
	 * @return array Updated pending-paragraph state.
	 */
	private static function append_line( array $pending, array $line ): array {
		$separator = preg_match( '/\s$/', $pending['text'] ) || preg_match( '/^\s/', $line['text'] )
			? array()
			: array( Ir::run( ' ' ) );

		$pending['runs']    = self::merge_runs( array_merge( $pending['runs'], $separator, $line['runs'] ) );
		$pending['text']   .= ( empty( $separator ) ? '' : ' ' ) . $line['text'];
		$pending['wrapped'] = ( $line['l'] + $line['w'] ) >= ( $pending['block_right'] - $pending['wrap_margin'] );
		return $pending;
	}


	/**
	 * Turn a pending paragraph into a finished IR paragraph.
	 *
	 * @param  array $pending   Pending-paragraph state.
	 * @param  float $body_size Body font size in points.
	 * @return array|null Paragraph, or null when it holds only whitespace.
	 */
	private static function finalise( array $pending, float $body_size ): ?array {
		$runs = self::trim_runs( $pending['runs'] );
		if ( empty( $runs ) ) {
			return null;
		}

		return Ir::para( self::paragraph_kind( $pending, $body_size ), $runs, array( 'size' => $pending['size'] ) );
	}


	/**
	 * Classify a pending paragraph.
	 *
	 * @param  array $pending   Pending-paragraph state.
	 * @param  float $body_size Body font size in points.
	 * @return string One of the Ir::KIND_* constants.
	 */
	private static function paragraph_kind( array $pending, float $body_size ): string {
		if ( $pending['mono'] ) {
			return Ir::KIND_CODE;
		}
		if ( $pending['bullet'] ) {
			return Ir::KIND_BULLET;
		}

		$is_large = $body_size > 0 && $pending['size'] >= $body_size * self::HEADING_SIZE_RATIO;
		if ( $is_large && mb_strlen( trim( $pending['text'] ) ) <= self::HEADING_MAX_CHARS ) {
			return Ir::KIND_HEADING;
		}

		return Ir::KIND_PARAGRAPH;
	}


	/**
	 * Remove a bullet marker prefix from a line's runs.
	 *
	 * @param  array  $runs      Run arrays.
	 * @param  string $full_text The line's full text.
	 * @param  string $rest      The text remaining after the marker.
	 * @return array Run arrays.
	 */
	private static function strip_marker( array $runs, string $full_text, string $rest ): array {
		$drop = strlen( $full_text ) - strlen( $rest );
		$out  = array();

		foreach ( $runs as $run ) {
			if ( $drop <= 0 ) {
				$out[] = $run;
				continue;
			}
			$len = strlen( $run['text'] );
			if ( $len <= $drop ) {
				$drop -= $len;
				continue;
			}
			$run['text'] = substr( $run['text'], $drop );
			$drop        = 0;
			$out[]       = $run;
		}

		return array_values( $out );
	}


	/**
	 * Trim leading and trailing whitespace across a run list.
	 *
	 * @param  array $runs Run arrays.
	 * @return array Run arrays; empty when nothing but whitespace remains.
	 */
	private static function trim_runs( array $runs ): array {
		$runs = array_values( $runs );

		while ( ! empty( $runs ) ) {
			$runs[0]['text'] = ltrim( $runs[0]['text'] );
			if ( $runs[0]['text'] !== '' ) {
				break;
			}
			array_shift( $runs );
		}

		while ( ! empty( $runs ) ) {
			$last                = count( $runs ) - 1;
			$runs[ $last ]['text'] = rtrim( $runs[ $last ]['text'] );
			if ( $runs[ $last ]['text'] !== '' ) {
				break;
			}
			array_pop( $runs );
		}

		return $runs;
	}
}
