<?php
/**
 * Geometry-based multi-column layout detection.
 *
 * Originally a PHP port of slides_to_learndash/slide_geometry.py that walked
 * PhpPresentation shapes directly. It now operates on format-neutral TextBox
 * arrays (see Document\Ir) so the same row/column analysis serves both the PPTX
 * parser and the PDF parser.
 *
 * UNIT NOTE: all coordinates are PIXELS at 96 DPI. PhpPresentation reports shape
 * geometry in those units already; the PDF parser converts points (72 DPI) by
 * multiplying by 4/3. python-pptx EMU constants were converted by ÷ 9525:
 *   ROW_OVERLAP_MIN    = 0.40  (dimensionless — unchanged)
 *   MIN_COL_GAP        = 4 px  (was MIN_COLUMN_GAP_EMU = 36_000 ÷ 9525)
 *   MAX_X_OVERLAP      = 5 px  (was ~45_000 ÷ 9525)
 *
 * @class   Document\BlockLayout
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Document;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BlockLayout class.
 */
final class BlockLayout {

	// Pixel thresholds (derived from python-pptx EMU values ÷ 9525).
	const ROW_OVERLAP_MIN  = 0.40;
	const MAX_X_OVERLAP_PX = 5;
	const MIN_COL_GAP_PX   = 4;

	/**
	 * Boxes whose top-edge (t) sits at or below this fraction of the slide height
	 * are treated as footer elements and excluded from content extraction.
	 *
	 * Calibrated against the Session 07 deck (960×540 px):
	 *   - CBF icon          t=475  (87.9 %)
	 *   - sldNum            t=479  (88.7 %)
	 *   - copyright text    t=511  (94.6 %)
	 *   - body shapes       t=96–113 (≤21 %)
	 *
	 * The same ratio holds for the PDF exports of those decks once their point
	 * coordinates are scaled to pixels.
	 */
	const FOOTER_TOP_RATIO = 0.87;


	/**
	 * Build the ordered list of content blocks from positioned text boxes.
	 *
	 * Each block is either a linear block (single text column) or a columns
	 * block (two or more horizontally adjacent boxes in the same row).
	 *
	 * @param  array $boxes          TextBox arrays (see Document\Ir::box()).
	 * @param  int   $slide_width_px Slide width in pixels, for column widths.
	 * @return array<array> Ordered ContentBlock arrays.
	 */
	public static function build_blocks( array $boxes, int $slide_width_px ): array {
		$boxes = array_values(
			array_filter(
				$boxes,
				static fn( array $box ): bool => ! empty( $box['paragraphs'] )
			)
		);

		if ( empty( $boxes ) ) {
			return array();
		}

		// Sort boxes top-to-bottom, left-to-right.
		usort(
			$boxes,
			fn( $a, $b ) => $a['t'] !== $b['t'] ? $a['t'] <=> $b['t'] : $a['l'] <=> $b['l']
		);

		$blocks = array();

		foreach ( self::group_into_rows( $boxes ) as $row ) {
			foreach ( self::row_blocks( $row, $slide_width_px ) as $block ) {
				$blocks[] = $block;
			}
		}

		return $blocks;
	}


	/**
	 * Turn one row of boxes into content blocks.
	 *
	 * A row becomes a columns block only when its boxes are genuinely
	 * side-by-side; boxes that overlap horizontally are stacked instead.
	 *
	 * @param  array $row            TextBoxes sharing a row.
	 * @param  int   $slide_width_px Slide width in pixels.
	 * @return array ContentBlock arrays.
	 */
	private static function row_blocks( array $row, int $slide_width_px ): array {
		if ( count( $row ) === 1 ) {
			return array( Ir::linear( $row[0]['paragraphs'] ) );
		}

		$cols = self::split_row_into_columns( $row );
		if ( count( $cols ) > 1 ) {
			return array(
				Ir::columns(
					array_map( array( __CLASS__, 'merge_paragraphs' ), $cols ),
					self::column_width_percentages( $cols, $slide_width_px )
				),
			);
		}

		$blocks = array();
		foreach ( $row as $box ) {
			$blocks[] = Ir::linear( $box['paragraphs'] );
		}

		return $blocks;
	}


	/**
	 * Return the top-edge pixel offset below which content is treated as footer.
	 *
	 * @param  int $slide_height_px Slide height in pixels (0 = no cutoff).
	 * @return int Cutoff in pixels; PHP_INT_MAX when the height is unknown.
	 */
	public static function footer_cutoff( int $slide_height_px ): int {
		return $slide_height_px > 0
			? (int) round( $slide_height_px * self::FOOTER_TOP_RATIO )
			: PHP_INT_MAX;
	}


	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * Flatten a column group's boxes into a single ordered paragraph list.
	 *
	 * @param  array $column Array of TextBox arrays.
	 * @return array Paragraph arrays.
	 */
	private static function merge_paragraphs( array $column ): array {
		$paragraphs = array();
		foreach ( $column as $box ) {
			foreach ( $box['paragraphs'] as $para ) {
				$paragraphs[] = $para;
			}
		}
		return $paragraphs;
	}


	/**
	 * Group boxes into rows based on vertical overlap.
	 *
	 * A box joins an existing row if it has >= ROW_OVERLAP_MIN vertical overlap
	 * with at least one box already in that row.
	 *
	 * @param  array $boxes Sorted TextBox arrays.
	 * @return array<array> List of rows (each row an array of TextBoxes).
	 */
	private static function group_into_rows( array $boxes ): array {
		$rows = array();

		foreach ( $boxes as $box ) {
			$index = self::find_row_for( $rows, $box );

			if ( $index === null ) {
				$rows[] = array( $box );
				continue;
			}

			$rows[ $index ][] = $box;
		}

		return $rows;
	}


	/**
	 * Find the row a box belongs to, if any.
	 *
	 * @param  array $rows Rows collected so far.
	 * @param  array $box  TextBox to place.
	 * @return int|null Row index, or null when the box starts a new row.
	 */
	private static function find_row_for( array $rows, array $box ): ?int {
		foreach ( $rows as $index => $row ) {
			foreach ( $row as $existing ) {
				if ( self::v_overlap_ratio( $box, $existing ) >= self::ROW_OVERLAP_MIN ) {
					return $index;
				}
			}
		}

		return null;
	}


	/**
	 * Within a row, split boxes into separate column groups.
	 *
	 * Returns an array of column groups (each a list of boxes). If all boxes
	 * overlap horizontally they are returned as a single group.
	 *
	 * @param  array $row TextBox arrays.
	 * @return array<array>
	 */
	private static function split_row_into_columns( array $row ): array {
		// Sort row left-to-right.
		usort( $row, fn( $a, $b ) => $a['l'] <=> $b['l'] );

		$columns    = array( array( $row[0] ) );
		$col_rights = array( $row[0]['l'] + $row[0]['w'] );

		for ( $i = 1; $i < count( $row ); $i++ ) {
			$box   = $row[ $i ];
			$found = false;

			foreach ( $col_rights as $idx => $right ) {
				$last      = $columns[ $idx ][ count( $columns[ $idx ] ) - 1 ];
				$h_overlap = max( 0, min( $right, $box['l'] + $box['w'] ) - max( $col_rights[ $idx ] - $last['w'], $box['l'] ) );
				$gap       = $box['l'] - $right;

				if ( $h_overlap < self::MAX_X_OVERLAP_PX && ( $gap >= self::MIN_COL_GAP_PX || $h_overlap === 0 ) ) {
					// This box is to the right of column $idx — start a new column.
					$columns[]    = array( $box );
					$col_rights[] = $box['l'] + $box['w'];
					$found        = true;
					break;
				}
			}

			if ( ! $found ) {
				// Overlaps an existing column — append to the rightmost column.
				$last_idx                = count( $columns ) - 1;
				$columns[ $last_idx ][]  = $box;
				$col_rights[ $last_idx ] = max( $col_rights[ $last_idx ], $box['l'] + $box['w'] );
			}
		}

		return $columns;
	}


	/**
	 * Compute each column's width as a percentage of the slide width.
	 *
	 * @param  array $columns        Array of column groups.
	 * @param  int   $slide_width_px Slide width in pixels.
	 * @return array<int> Percentages (one per column).
	 */
	private static function column_width_percentages( array $columns, int $slide_width_px ): array {
		if ( $slide_width_px <= 0 ) {
			return array_fill( 0, count( $columns ), (int) round( 100 / max( 1, count( $columns ) ) ) );
		}

		$widths = array();
		foreach ( $columns as $col ) {
			$max_w    = max( array_column( $col, 'w' ) );
			$widths[] = (int) round( 100 * $max_w / $slide_width_px );
		}
		return $widths;
	}


	/**
	 * Vertical overlap ratio between two bounding boxes.
	 *
	 * Returns overlap_height / min(a.h, b.h).
	 * 1.0 means one box is fully contained vertically within the other.
	 * 0.0 means no vertical overlap.
	 *
	 * @param array $a Bounding box {l,t,w,h}.
	 * @param array $b Bounding box {l,t,w,h}.
	 */
	private static function v_overlap_ratio( array $a, array $b ): float {
		$ov = min( $a['t'] + $a['h'], $b['t'] + $b['h'] ) - max( $a['t'], $b['t'] );
		if ( $ov <= 0 ) {
			return 0.0;
		}
		$min_h = min( $a['h'], $b['h'] );
		return $min_h > 0 ? $ov / $min_h : 0.0;
	}
}
