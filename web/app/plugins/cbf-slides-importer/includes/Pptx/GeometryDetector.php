<?php
/**
 * Geometry-based multi-column layout detection.
 *
 * PHP port of slides_to_learndash/slide_geometry.py.
 *
 * UNIT NOTE (P0.4): PhpPresentation returns shape offsets/dimensions in
 * PIXELS (96 DPI, not EMU). python-pptx constants converted:
 *   ROW_OVERLAP_MIN    = 0.40  (dimensionless — unchanged)
 *   MIN_COL_GAP        = 4 px  (was MIN_COLUMN_GAP_EMU = 36_000 ÷ 9525)
 *   MAX_X_OVERLAP      = 5 px  (was ~45_000 ÷ 9525)
 *
 * @class   Pptx\GeometryDetector
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Pptx;

use PhpOffice\PhpPresentation\Shape\RichText;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeometryDetector class.
 */
final class GeometryDetector {

	// Pixel thresholds (derived from python-pptx EMU values ÷ 9525).
	const ROW_OVERLAP_MIN  = 0.40;
	const MAX_X_OVERLAP_PX = 5;
	const MIN_COL_GAP_PX   = 4;

	/**
	 * Shapes whose top-edge (t) sits at or above this fraction of the slide height
	 * are treated as footer elements and excluded from content extraction.
	 *
	 * Calibrated against the Session 07 deck (960×540 px):
	 *   - CBF icon          t=475  (87.9 %)
	 *   - sldNum            t=479  (88.7 %)
	 *   - copyright text    t=511  (94.6 %)
	 *   - body shapes       t=96–113 (≤21 %)
	 */
	const FOOTER_TOP_RATIO = 0.87;

	/**
	 * Placeholder types that are always footer elements (slide number, footer
	 * text, date/time) and excluded regardless of position.
	 */
	const FOOTER_PLACEHOLDER_TYPES = array( 'sldNum', 'ftr', 'dt' );


	/**
	 * Build the ordered list of content blocks for a slide.
	 *
	 * Each block is either a LinearBlock (single text column) or a
	 * ColumnsBlock (two or more horizontally adjacent shapes in the same row).
	 *
	 * Title placeholder shapes and footer-zone shapes are excluded.
	 *
	 * @param  object $slide           PhpPresentation slide object.
	 * @param  int    $slide_width_px  Slide width in pixels (from Parser).
	 * @param  int    $slide_height_px Slide height in pixels (used for footer cutoff).
	 * @return array<array> Ordered array of LinearBlock|ColumnsBlock arrays.
	 */
	public static function build_content_blocks( object $slide, int $slide_width_px, int $slide_height_px = 0 ): array {
		$shapes = self::collect_content_shapes( $slide, $slide_height_px );

		if ( empty( $shapes ) ) {
			return array();
		}

		// Sort shapes top-to-bottom, left-to-right.
		usort(
			$shapes,
			fn( $a, $b ) => $a['t'] !== $b['t'] ? $a['t'] <=> $b['t'] : $a['l'] <=> $b['l']
		);

		// Group into rows by vertical overlap.
		$rows = self::group_into_rows( $shapes );

		$blocks = array();
		foreach ( $rows as $row ) {
			if ( count( $row ) === 1 ) {
				$blocks[] = array(
					'type'   => 'linear',
					'shapes' => $row,
				);
				continue;
			}

			// Within a row, check if the shapes are genuinely side-by-side columns.
			$cols = self::split_row_into_columns( $row );
			if ( count( $cols ) > 1 ) {
				$blocks[] = array(
					'type'    => 'columns',
					'columns' => $cols,
					'widths'  => self::column_width_percentages( $cols, $slide_width_px ),
				);
			} else {
				// Shapes overlap horizontally — treat as stacked single column.
				foreach ( $row as $shape ) {
					$blocks[] = array(
						'type' => 'linear',
						'shapes' => array( $shape ),
					);
				}
			}
		}

		return $blocks;
	}


	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * Collect all content shapes from a slide as bounding boxes.
	 *
	 * Excluded:
	 *  - Title/centre-title placeholder shapes (handled by extract_title).
	 *  - Footer placeholder types: sldNum, ftr, dt.
	 *  - Any shape whose top-edge sits in the footer zone (≥ FOOTER_TOP_RATIO
	 *    of the slide height), covering copyright text-boxes and the CBF icon.
	 *
	 * @param object $slide           PhpPresentation slide.
	 * @param int    $slide_height_px Slide height in px (0 = skip position filter).
	 * @return array<array{l:int,t:int,w:int,h:int,shape:object}>
	 */
	private static function collect_content_shapes( object $slide, int $slide_height_px ): array {
		$footer_cutoff = $slide_height_px > 0
			? (int) round( $slide_height_px * self::FOOTER_TOP_RATIO )
			: PHP_INT_MAX;

		$excluded_ph_types = array_merge( array( 'title', 'ctrTitle' ), self::FOOTER_PLACEHOLDER_TYPES );

		$shapes = array();
		foreach ( $slide->getShapeCollection() as $shape ) {
			if ( $shape instanceof RichText ) {
				try {
					$ph = $shape->getPlaceholder();
					if ( $ph && in_array( $ph->getType(), $excluded_ph_types, true ) ) {
						continue;
					}
				} catch ( \Throwable $e ) {
				}
			}

			$l = $shape->getOffsetX();
			$t = $shape->getOffsetY();
			$w = $shape->getWidth();
			$h = $shape->getHeight();

			if ( $l === null || $w <= 0 || $h <= 0 ) {
				continue;
			}

			// Skip shapes in the footer zone (copyright text boxes, icons, etc.).
			if ( (int) $t >= $footer_cutoff ) {
				continue;
			}

			$shapes[] = array(
				'l'     => (int) $l,
				't'     => (int) $t,
				'w'     => (int) $w,
				'h'     => (int) $h,
				'shape' => $shape,
			);
		}
		return $shapes;
	}


	/**
	 * Group shapes into rows based on vertical overlap.
	 *
	 * A shape joins an existing row if it has >= ROW_OVERLAP_MIN vertical
	 * overlap with at least one shape already in that row.
	 *
	 * @param array $shapes Sorted shape bounding-box arrays.
	 * @return array<array> List of row arrays (each row is an array of shapes).
	 */
	private static function group_into_rows( array $shapes ): array {
		$rows = array();

		foreach ( $shapes as $shape ) {
			$placed = false;
			foreach ( $rows as &$row ) {
				foreach ( $row as $existing ) {
					if ( self::v_overlap_ratio( $shape, $existing ) >= self::ROW_OVERLAP_MIN ) {
						$row[]  = $shape;
						$placed = true;
						break 2;
					}
				}
			}
			unset( $row );

			if ( ! $placed ) {
				$rows[] = array( $shape );
			}
		}

		return $rows;
	}


	/**
	 * Within a row, split shapes into separate column groups.
	 *
	 * Returns an array of column groups (each a list of shapes).
	 * If all shapes overlap horizontally they are returned as a single group.
	 *
	 * @param array $row Shape bounding-box arrays.
	 * @return array<array>
	 */
	private static function split_row_into_columns( array $row ): array {
		// Sort row left-to-right.
		usort( $row, fn( $a, $b ) => $a['l'] <=> $b['l'] );

		$columns    = array( array( $row[0] ) );
		$col_rights = array( $row[0]['l'] + $row[0]['w'] );

		for ( $i = 1; $i < count( $row ); $i++ ) {
			$shape = $row[ $i ];
			// Check against all existing column right-edges for a gap.
			$found = false;
			foreach ( $col_rights as $idx => $right ) {
				$h_overlap = max( 0, min( $right, $shape['l'] + $shape['w'] ) - max( $col_rights[ $idx ] - ( $columns[ $idx ][ count( $columns[ $idx ] ) - 1 ]['w'] ), $shape['l'] ) );
				$gap       = $shape['l'] - $right;

				if ( $h_overlap < self::MAX_X_OVERLAP_PX && ( $gap >= self::MIN_COL_GAP_PX || $h_overlap === 0 ) ) {
					// This shape is to the right of column $idx — new column.
					$columns[]    = array( $shape );
					$col_rights[] = $shape['l'] + $shape['w'];
					$found        = true;
					break;
				}
			}

			if ( ! $found ) {
				// Overlaps an existing column — append to the rightmost column.
				$last_idx         = count( $columns ) - 1;
				$columns[ $last_idx ][] = $shape;
				$col_rights[ $last_idx ] = max( $col_rights[ $last_idx ], $shape['l'] + $shape['w'] );
			}
		}

		return $columns;
	}


	/**
	 * Compute each column's width as a percentage of the slide width.
	 *
	 * @param array $columns        Array of column groups.
	 * @param int   $slide_width_px Slide width in pixels.
	 * @return array<int> Percentages (one per column).
	 */
	private static function column_width_percentages( array $columns, int $slide_width_px ): array {
		if ( $slide_width_px <= 0 ) {
			return array_fill( 0, count( $columns ), 50 );
		}

		$widths = array();
		foreach ( $columns as $col ) {
			$max_w = max( array_column( $col, 'w' ) );
			$widths[] = (int) round( 100 * $max_w / $slide_width_px );
		}
		return $widths;
	}


	/**
	 * Vertical overlap ratio between two bounding boxes.
	 *
	 * Returns overlap_height / min(a.h, b.h).
	 * 1.0 means one shape is fully contained vertically within the other.
	 * 0.0 means no vertical overlap.
	 *
	 * @param array $a Shape bounding box {l,t,w,h}.
	 * @param array $b Shape bounding box {l,t,w,h}.
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
