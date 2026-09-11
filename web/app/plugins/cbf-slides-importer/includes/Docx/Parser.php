<?php
/**
 * DOCX parser — turns a Word document into the format-neutral ParsedDeck IR.
 *
 * A word-processing document has no slides, so the importer's "slide" unit maps
 * onto heading-delimited sections: every heading at the document's shallowest
 * heading depth starts a new section, and deeper headings become h3–h6 inside
 * it. That keeps the whole downstream pipeline — classification, slide-map
 * overrides, block rendering — working unchanged.
 *
 * Implementation notes:
 * - PhpWord returns run text HTML-escaped (`&amp;`, `&#039;`), so every string
 *   read out of an element is passed through html_entity_decode().
 * - Google Docs exports use <w:br> for line breaks rather than separate
 *   paragraphs, so a TextRun is split on TextBreak into one IR paragraph per
 *   visual line; without that, whole sections collapse into single paragraphs.
 * - Those exports also flatten bullet lists into literal "- " prefixes, which
 *   Ir::split_bullet_marker() recovers.
 *
 * @class   Docx\Parser
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Docx;

use CodingBlackFemales\SlidesImporter\Document\Ir;
use CodingBlackFemales\SlidesImporter\Document\ParserFactory;
use CodingBlackFemales\SlidesImporter\Utils;
use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\Image;
use PhpOffice\PhpWord\Element\Link;
use PhpOffice\PhpWord\Element\ListItem;
use PhpOffice\PhpWord\Element\ListItemRun;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextBreak;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Title;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\Style\ListItem as ListItemStyle;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parser class.
 *
 * Emits the same ParsedDeck shape as Pptx\Parser, with:
 * - `layout_name`  = the section heading's Word style name (Heading1, Heading2…),
 *                    so the existing heading_layout_regex config still applies.
 * - `is_hidden`    = always false; Word has no hidden-slide equivalent.
 * - `is_cover`     = true only for a leading title heading with no body of its
 *                    own, mirroring a deck's title slide.
 * - slide_width_px / slide_height_px = 0; a document has no page geometry that
 *                    would make column detection meaningful.
 */
final class Parser {

	/** Word style names are "Heading1".."Heading9"; this extracts the depth. */
	const HEADING_STYLE_PATTERN = '/^heading\s*(\d)$/i';

	/**
	 * Maximum image bytes to write per document.
	 *
	 * Word embeds full-resolution originals; a guard keeps a pathological
	 * document from filling the job's temp directory.
	 */
	const MAX_IMAGE_BYTES = 104857600; // 100 MB.


	/**
	 * Parse a DOCX file into a ParsedDeck array.
	 *
	 * @param  string $docx_path   Absolute path to the DOCX file.
	 * @param  string $img_out_dir Absolute path to write extracted images.
	 * @return array|WP_Error      ParsedDeck array or WP_Error on failure.
	 */
	public static function parse( string $docx_path, string $img_out_dir ): array|WP_Error {
		if ( ! is_file( $docx_path ) ) {
			return new WP_Error( 'cbf_si_docx_missing', 'DOCX file not found: ' . esc_html( basename( $docx_path ) ) );
		}

		try {
			$doc         = IOFactory::load( $docx_path, 'Word2007' );
			$elements    = self::flatten_elements( $doc );
			$split_depth = self::split_depth( $elements );
			$sections    = self::split_into_sections( $elements, $split_depth );
			$numbering   = Numbering::read( $docx_path );

			$slides = array();
			foreach ( $sections as $idx => $section ) {
				$slides[] = self::build_slide(
					$section,
					array(
						'index'       => $idx,
						'img_dir'     => $img_out_dir,
						'split_depth' => $split_depth,
						'numbering'   => $numbering,
					)
				);
			}

			if ( empty( $slides ) ) {
				return new WP_Error(
					'cbf_si_docx_empty',
					__( 'The Word document contains no readable content.', 'cbf-slides-importer' )
				);
			}

			$slides = self::mark_cover( $slides );

			return array(
				'source_format'   => ParserFactory::FORMAT_DOCX,
				'unit_label'      => ParserFactory::unit_label( ParserFactory::FORMAT_DOCX ),
				'slide_width_px'  => 0,
				'slide_height_px' => 0,
				'slides'          => $slides,
			);

		} catch ( \Throwable $e ) {
			Utils::log(
				'DOCX parse error.',
				array(
					'file' => basename( $docx_path ),
					'msg'  => $e->getMessage(),
				)
			);
			return new WP_Error( 'cbf_si_parse_error', 'Failed to parse DOCX: ' . $e->getMessage() );
		}
	}


	// ── Sectioning ────────────────────────────────────────────────────────────

	/**
	 * Collect every top-level element across all Word sections into one list.
	 *
	 * Word sections are page-layout breaks, not content structure, so they are
	 * flattened away before heading-based sectioning is applied.
	 *
	 * @param  object $doc PhpWord document.
	 * @return array Element objects in document order.
	 */
	private static function flatten_elements( object $doc ): array {
		$elements = array();
		foreach ( $doc->getSections() as $section ) {
			foreach ( $section->getElements() as $element ) {
				$elements[] = $element;
			}
		}
		return $elements;
	}


	/**
	 * Choose the heading depth that divides the document into sections.
	 *
	 * The shallowest depth that occurs more than once wins: a document whose
	 * only Heading1 is its title splits on Heading2 instead, which is what makes
	 * "Part 1 / Part 2" become sections rather than collapsing the whole file
	 * into one. When no depth repeats, the shallowest depth present is used and
	 * the document simply becomes a single section.
	 *
	 * @param  array $elements Flattened element list.
	 * @return int Heading depth to split on, or 0 when the document has none.
	 */
	private static function split_depth( array $elements ): int {
		$counts = self::count_heading_depths( $elements );

		if ( empty( $counts ) ) {
			return 0;
		}

		ksort( $counts );

		foreach ( $counts as $depth => $count ) {
			if ( $count > 1 ) {
				return (int) $depth;
			}
		}

		return (int) array_key_first( $counts );
	}


	/**
	 * Tally how many headings the document has at each depth.
	 *
	 * @param  array $elements Flattened element list.
	 * @return array<int, int> Heading count keyed by depth.
	 */
	private static function count_heading_depths( array $elements ): array {
		$counts = array();

		foreach ( $elements as $element ) {
			if ( ! ( $element instanceof Title ) ) {
				continue;
			}
			$depth = self::title_depth( $element );
			if ( $depth > 0 ) {
				$counts[ $depth ] = ( $counts[ $depth ] ?? 0 ) + 1;
			}
		}

		return $counts;
	}


	/**
	 * Split the flattened element list into heading-delimited sections.
	 *
	 * Content appearing before the first split heading becomes an untitled
	 * leading section so nothing is dropped.
	 *
	 * @param  array $elements    Flattened element list.
	 * @param  int   $split_depth Heading depth that starts a new section (0 = never split).
	 * @return array<array{title: string, style: string, elements: array}>
	 */
	private static function split_into_sections( array $elements, int $split_depth ): array {
		$sections = array();
		$current  = array(
			'title'    => '',
			'style'    => '',
			'elements' => array(),
		);

		foreach ( $elements as $element ) {
			// A heading above the split depth that opens the document is its
			// title page: adopt it as the leading section's title so mark_cover()
			// can recognise and drop it, rather than leaving a stray H2 that
			// repeats the lesson's own title.
			if ( empty( $sections )
				&& $current['title'] === ''
				&& empty( $current['elements'] )
				&& $element instanceof Title
				&& self::title_depth( $element ) > 0
				&& self::title_depth( $element ) < $split_depth ) {
				$current['title'] = self::title_text( $element );
				$current['style'] = self::title_style_name( $element );
				continue;
			}

			$is_split = $split_depth > 0
				&& $element instanceof Title
				&& self::title_depth( $element ) === $split_depth;

			if ( ! $is_split ) {
				$current['elements'][] = $element;
				continue;
			}

			// Only keep a leading section that actually holds content.
			if ( $current['title'] !== '' || ! empty( $current['elements'] ) ) {
				$sections[] = $current;
			}

			$current = array(
				'title'    => self::title_text( $element ),
				'style'    => self::title_style_name( $element ),
				'elements' => array(),
			);
		}

		if ( $current['title'] !== '' || ! empty( $current['elements'] ) ) {
			$sections[] = $current;
		}

		return $sections;
	}


	/**
	 * Build one ParsedSlide from a document section.
	 *
	 * @param  array $section Section from split_into_sections().
	 * @param  array $ctx     Document context: index, img_dir, split_depth, numbering.
	 * @return array ParsedSlide.
	 */
	private static function build_slide( array $section, array $ctx ): array {
		$idx        = $ctx['index'];
		$paragraphs = array();
		$images     = array();

		foreach ( $section['elements'] as $element ) {
			self::convert_element( $element, $paragraphs, $images, $ctx );
		}

		$paragraphs = self::drop_empty_items( $paragraphs );

		return array(
			'index'        => $idx,
			'slide_number' => $idx + 1,
			'layout_name'  => $section['style'],
			'is_hidden'    => false,
			'is_cover'     => false,
			'title'        => $section['title'],
			'content'      => self::to_content_blocks( $paragraphs ),
			'images'       => $images,
		);
	}


	/**
	 * Drop blank paragraphs while leaving whole blocks alone.
	 *
	 * A section's item list mixes IR paragraphs with ready-made content blocks
	 * (tables). Those blocks carry no `runs`, so passing the mixed list through
	 * Ir::drop_empty() would silently discard every table.
	 *
	 * @param  array $items Paragraphs interleaved with content blocks.
	 * @return array Filtered items, re-indexed.
	 */
	private static function drop_empty_items( array $items ): array {
		$kept = array();

		foreach ( $items as $item ) {
			if ( isset( $item['type'] ) || trim( Ir::plain_text( $item ) ) !== '' ) {
				$kept[] = $item;
			}
		}

		return $kept;
	}


	/**
	 * Flag the first section as a cover when it is a bare document title.
	 *
	 * A leading heading with no body of its own is the document's title page —
	 * excluding it keeps the lesson content from opening with an H2 that merely
	 * repeats the lesson's own title. A leading heading that does carry content
	 * is kept, because dropping it would lose real material.
	 *
	 * @param  array $slides ParsedSlide arrays.
	 * @return array ParsedSlide arrays with is_cover possibly set on the first.
	 */
	private static function mark_cover( array $slides ): array {
		if ( count( $slides ) < 2 ) {
			return $slides;
		}

		$first = $slides[0];
		if ( $first['title'] !== '' && empty( $first['content'] ) && empty( $first['images'] ) ) {
			$slides[0]['is_cover'] = true;
		}

		return $slides;
	}


	/**
	 * Wrap a section's paragraphs into content blocks.
	 *
	 * Table blocks are already complete blocks and pass through; runs of
	 * paragraphs between them collapse into a single linear block.
	 *
	 * @param  array $paragraphs Paragraph arrays, possibly interleaved with
	 *                           pre-built table blocks.
	 * @return array ContentBlock arrays.
	 */
	private static function to_content_blocks( array $paragraphs ): array {
		$blocks = array();
		$buffer = array();

		foreach ( $paragraphs as $item ) {
			if ( ( $item['type'] ?? '' ) === 'table' ) {
				if ( ! empty( $buffer ) ) {
					$blocks[] = Ir::linear( $buffer );
					$buffer   = array();
				}
				$blocks[] = $item;
				continue;
			}
			$buffer[] = $item;
		}

		if ( ! empty( $buffer ) ) {
			$blocks[] = Ir::linear( $buffer );
		}

		return $blocks;
	}


	// ── Element conversion ────────────────────────────────────────────────────

	/**
	 * Convert one PhpWord element, appending to the paragraph and image lists.
	 *
	 * @param object $element    PhpWord element.
	 * @param array  $paragraphs Accumulated paragraphs (and table blocks).
	 * @param array  $images     Accumulated image metadata.
	 * @param array  $ctx        Document context: index, img_dir, split_depth, numbering.
	 */
	private static function convert_element( object $element, array &$paragraphs, array &$images, array $ctx ): void {
		if ( $element instanceof Image ) {
			self::collect_image( $element, $images, $ctx );
			return;
		}

		// ListItemRun extends TextRun, so it has to be matched before the plain
		// run branch or every list item would import as an ordinary paragraph.
		if ( $element instanceof TextRun && ! ( $element instanceof ListItemRun ) ) {
			foreach ( self::convert_text_run( $element, $images, $ctx ) as $para ) {
				$paragraphs[] = $para;
			}
			return;
		}

		$converted = self::convert_block_element( $element, $ctx );
		if ( $converted !== null ) {
			$paragraphs[] = $converted;
		}
	}


	/**
	 * Convert the elements that map to exactly one paragraph or block.
	 *
	 * @param  object $element PhpWord element.
	 * @param  array  $ctx     Document context.
	 * @return array|null Paragraph or ContentBlock, or null when unsupported.
	 */
	private static function convert_block_element( object $element, array $ctx ): ?array {
		if ( $element instanceof Title ) {
			return Ir::text_para(
				Ir::KIND_HEADING,
				self::title_text( $element ),
				array( 'level' => self::heading_level( $element, $ctx ) )
			);
		}

		if ( $element instanceof ListItem || $element instanceof ListItemRun ) {
			return self::convert_list_item( $element, $ctx );
		}

		if ( $element instanceof Table ) {
			return self::convert_table( $element, $ctx );
		}

		if ( $element instanceof Text ) {
			return self::finalise_paragraph( array( self::convert_text( $element ) ) );
		}

		return null;
	}


	/**
	 * Extract an image element and append its metadata.
	 *
	 * @param Image $element PhpWord image element.
	 * @param array $images  Accumulated image metadata.
	 * @param array $ctx     Document context.
	 */
	private static function collect_image( Image $element, array &$images, array $ctx ): void {
		$image = self::extract_image( $element, $ctx['index'], count( $images ) + 1, $ctx['img_dir'] );
		if ( $image !== null ) {
			$images[] = $image;
		}
	}


	/**
	 * Map a Word heading depth to an output heading level.
	 *
	 * Levels are relative to the depth the document was split on: a section's own
	 * heading becomes the H2 that BlockRenderer emits from the slide title, so
	 * the next depth down starts at H3 regardless of whether the document nests
	 * from Heading1 or Heading2.
	 *
	 * @param  Title $title Heading element.
	 * @param  array $ctx   Document context.
	 * @return int Heading level, clamped to 2–6.
	 */
	private static function heading_level( Title $title, array $ctx ): int {
		$relative = self::title_depth( $title ) - (int) $ctx['split_depth'];
		return min( 6, max( 2, 2 + $relative ) );
	}


	/**
	 * Convert a TextRun into one IR paragraph per visual line.
	 *
	 * Google Docs exports separate lines with <w:br> inside a single run, so
	 * splitting on TextBreak is what recovers the document's real line structure.
	 * Inline images inside a run are extracted rather than dropped.
	 *
	 * @param  AbstractContainer $run    PhpWord TextRun.
	 * @param  array             $images Accumulated image metadata.
	 * @param  array             $ctx    Document context.
	 * @return array Paragraph arrays.
	 */
	private static function convert_text_run( AbstractContainer $run, array &$images, array $ctx ): array {
		$paragraphs = array();
		$runs       = array();

		foreach ( $run->getElements() as $child ) {
			if ( $child instanceof TextBreak ) {
				$paragraphs[] = self::finalise_paragraph( $runs );
				$runs         = array();
				continue;
			}

			if ( $child instanceof Image ) {
				self::collect_image( $child, $images, $ctx );
				continue;
			}

			$converted = self::convert_inline( $child );
			if ( $converted !== null ) {
				$runs[] = $converted;
			}
		}

		$paragraphs[] = self::finalise_paragraph( $runs );

		return array_values( array_filter( $paragraphs ) );
	}


	/**
	 * Convert an inline child element to an IR run.
	 *
	 * @param  object $child PhpWord element inside a run or list item.
	 * @return array|null Run, or null when the element carries no text.
	 */
	private static function convert_inline( object $child ): ?array {
		if ( $child instanceof Link ) {
			return self::convert_link( $child );
		}

		if ( $child instanceof Text ) {
			return self::convert_text( $child );
		}

		return null;
	}


	/**
	 * Turn a list of runs into a paragraph, classifying its kind.
	 *
	 * Recognises literal bullet markers ("- item") that exports leave in the
	 * text, and promotes a fully-monospaced line to a code paragraph.
	 *
	 * @param  array $runs Run arrays.
	 * @return array|null Paragraph, or null when the line is blank.
	 */
	private static function finalise_paragraph( array $runs ): ?array {
		$runs = array_values( array_filter( $runs ) );
		if ( empty( $runs ) ) {
			return null;
		}

		$text = '';
		$mono = true;
		foreach ( $runs as $run ) {
			$text .= $run['text'];
			$mono  = $mono && ! empty( $run['mono'] );
		}

		if ( trim( $text ) === '' ) {
			return null;
		}

		if ( $mono ) {
			return Ir::para( Ir::KIND_CODE, $runs );
		}

		$bullet = Ir::split_bullet_marker( $text );
		if ( $bullet !== null ) {
			return Ir::para( Ir::KIND_BULLET, self::strip_leading( $runs, strlen( $text ) - strlen( $bullet['text'] ) ) );
		}

		return Ir::para( Ir::KIND_PARAGRAPH, $runs );
	}


	/**
	 * Remove a fixed number of leading characters from a run list.
	 *
	 * Used to drop a literal bullet marker while keeping the formatting of the
	 * runs that follow it.
	 *
	 * @param  array $runs  Run arrays.
	 * @param  int   $count Number of leading bytes to remove.
	 * @return array Run arrays.
	 */
	private static function strip_leading( array $runs, int $count ): array {
		$out = array();
		foreach ( $runs as $run ) {
			if ( $count <= 0 ) {
				$out[] = $run;
				continue;
			}
			$len = strlen( $run['text'] );
			if ( $len <= $count ) {
				$count -= $len;
				continue;
			}
			$run['text'] = substr( $run['text'], $count );
			$count       = 0;
			$out[]       = $run;
		}
		return array_values( $out );
	}


	/**
	 * Convert a PhpWord Text element to an IR run.
	 *
	 * @param  Text $text PhpWord text element.
	 * @return array Run.
	 */
	private static function convert_text( Text $text ): array {
		return Ir::run( self::decode( (string) $text->getText() ), self::font_flags( $text->getFontStyle() ) );
	}


	/**
	 * Convert a PhpWord Link element to a linked IR run.
	 *
	 * @param  Link $link PhpWord link element.
	 * @return array Run.
	 */
	private static function convert_link( Link $link ): array {
		$text  = (string) $link->getText();
		$label = self::decode( $text !== '' ? $text : (string) $link->getSource() );
		$flags = self::font_flags( $link->getFontStyle() );
		$flags['link'] = self::decode( (string) $link->getSource() );
		return Ir::run( $label, $flags );
	}


	/**
	 * Convert a list item (bulleted or numbered) to an IR bullet paragraph.
	 *
	 * @param  object $element ListItem or ListItemRun.
	 * @param  array  $ctx     Document context.
	 * @return array Paragraph.
	 */
	private static function convert_list_item( object $element, array $ctx ): array {
		$runs = self::list_item_runs( $element );

		if ( empty( $runs ) ) {
			$runs[] = Ir::run( '' );
		}

		$level = max( 0, (int) $element->getDepth() );

		return Ir::para(
			Ir::KIND_BULLET,
			$runs,
			array(
				'level'   => $level,
				'ordered' => Numbering::is_ordered( $ctx['numbering'] ?? array(), self::num_id( $element ), $level ),
			)
		);
	}


	/**
	 * Read the runs out of a list item.
	 *
	 * ListItemRun holds inline children like an ordinary run; plain ListItem
	 * wraps a single Text element.
	 *
	 * @param  object $element ListItem or ListItemRun.
	 * @return array Run arrays.
	 */
	private static function list_item_runs( object $element ): array {
		$runs = array();

		if ( $element instanceof ListItemRun ) {
			foreach ( $element->getElements() as $child ) {
				$converted = self::convert_inline( $child );
				if ( $converted !== null ) {
					$runs[] = $converted;
				}
			}
			return $runs;
		}

		if ( method_exists( $element, 'getTextObject' ) ) {
			$runs[] = self::convert_text( $element->getTextObject() );
		}

		return $runs;
	}


	/**
	 * Read the numbering definition id a list item belongs to.
	 *
	 * @param  object $element ListItem or ListItemRun.
	 * @return int|null Numbering id, or null when the item declares none.
	 */
	private static function num_id( object $element ): ?int {
		$style = $element->getStyle();

		if ( ! ( $style instanceof ListItemStyle ) || ! method_exists( $style, 'getNumId' ) ) {
			return null;
		}

		$num_id = $style->getNumId();

		return $num_id === null ? null : (int) $num_id;
	}


	/**
	 * Convert a Word table into an IR table block.
	 *
	 * The first row is treated as a header when every one of its cells is bold —
	 * the convention Word documents use in practice, since a table style's
	 * header flag is not exposed by PhpWord's reader.
	 *
	 * @param  Table $table PhpWord table element.
	 * @param  array $ctx   Document context.
	 * @return array ContentBlock of type 'table'.
	 */
	private static function convert_table( Table $table, array $ctx ): array {
		$rows = array();

		foreach ( $table->getRows() as $row ) {
			$cells = array();
			foreach ( $row->getCells() as $cell ) {
				$cells[] = self::convert_table_cell( $cell, $ctx );
			}
			$rows[] = $cells;
		}

		return Ir::table( $rows, self::is_header_row( $rows[0] ?? array() ) );
	}


	/**
	 * Convert one table cell to a paragraph list.
	 *
	 * Nested tables and images are skipped: Gutenberg's core table block takes
	 * only inline content in its cells, so there is nowhere to put them.
	 *
	 * @param  object $cell PhpWord table cell.
	 * @param  array  $ctx  Document context.
	 * @return array Paragraph arrays.
	 */
	private static function convert_table_cell( object $cell, array $ctx ): array {
		$paragraphs = array();
		$ignored    = array();
		$cell_ctx   = array_merge( $ctx, array( 'img_dir' => '' ) );

		foreach ( $cell->getElements() as $element ) {
			if ( $element instanceof Table || $element instanceof Image ) {
				continue;
			}
			self::convert_element( $element, $paragraphs, $ignored, $cell_ctx );
		}

		return Ir::drop_empty(
			array_filter( $paragraphs, static fn( $para ) => ( $para['type'] ?? '' ) !== 'table' )
		);
	}


	/**
	 * Return true when every cell in a row is entirely bold.
	 *
	 * @param  array $row Cells, each a Paragraph[].
	 * @return bool
	 */
	private static function is_header_row( array $row ): bool {
		if ( empty( $row ) ) {
			return false;
		}

		foreach ( $row as $cell ) {
			if ( empty( $cell ) || ! self::cell_is_bold( $cell ) ) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Return true when every non-blank run in a cell is bold.
	 *
	 * @param  array $cell Paragraph arrays.
	 * @return bool
	 */
	private static function cell_is_bold( array $cell ): bool {
		foreach ( $cell as $para ) {
			foreach ( $para['runs'] as $run ) {
				if ( trim( $run['text'] ) !== '' && empty( $run['bold'] ) ) {
					return false;
				}
			}
		}

		return true;
	}


	// ── Images ────────────────────────────────────────────────────────────────

	/**
	 * Write an embedded image to the job's image directory.
	 *
	 * @param  Image  $image       PhpWord image element.
	 * @param  int    $section_idx 0-based section index (filename prefix).
	 * @param  int    $img_num     1-based image counter within the section.
	 * @param  string $img_out_dir Destination directory ('' = skip extraction).
	 * @return array|null Image metadata, or null when nothing was written.
	 */
	private static function extract_image( Image $image, int $section_idx, int $img_num, string $img_out_dir ): ?array {
		if ( $img_out_dir === '' ) {
			return null;
		}

		try {
			$blob = $image->getImageStringData();
			if ( empty( $blob ) || strlen( $blob ) > self::MAX_IMAGE_BYTES ) {
				return null;
			}

			$ext      = strtolower( (string) $image->getImageExtension() );
			$ext      = $ext !== '' ? $ext : 'png';
			$filename = sprintf( 'slide_%03d_img_%02d.%s', $section_idx + 1, $img_num, $ext );
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
		} catch ( \Throwable $e ) {
			Utils::log(
				'DOCX image extraction error.',
				array(
					'section' => $section_idx + 1,
					'msg'     => $e->getMessage(),
				)
			);
			return null;
		}
	}


	// ── Small helpers ─────────────────────────────────────────────────────────

	/**
	 * Read a Title element's text, which PhpWord returns as a string or a TextRun.
	 *
	 * @param  Title $title PhpWord title element.
	 * @return string
	 */
	private static function title_text( Title $title ): string {
		$text = $title->getText();

		if ( is_string( $text ) ) {
			return trim( self::decode( $text ) );
		}

		if ( $text instanceof AbstractContainer ) {
			$parts = array();
			foreach ( $text->getElements() as $child ) {
				if ( $child instanceof Text ) {
					$parts[] = self::decode( (string) $child->getText() );
				} elseif ( $child instanceof Link ) {
					$parts[] = self::decode( (string) $child->getText() );
				}
			}
			return trim( implode( '', $parts ) );
		}

		return '';
	}


	/**
	 * Read a Title element's heading depth (1 for Heading1, 2 for Heading2, …).
	 *
	 * @param  Title $title PhpWord title element.
	 * @return int Depth, or 0 when it cannot be determined.
	 */
	private static function title_depth( Title $title ): int {
		$depth = (int) $title->getDepth();
		if ( $depth > 0 ) {
			return $depth;
		}

		if ( preg_match( self::HEADING_STYLE_PATTERN, self::title_style_name( $title ), $m ) ) {
			return (int) $m[1];
		}

		return 0;
	}


	/**
	 * Read a Title element's Word style name (e.g. "Heading2").
	 *
	 * @param  Title $title PhpWord title element.
	 * @return string
	 */
	private static function title_style_name( Title $title ): string {
		$style = $title->getStyle();
		if ( is_string( $style ) ) {
			return $style;
		}
		if ( is_object( $style ) && method_exists( $style, 'getStyleName' ) ) {
			return (string) $style->getStyleName();
		}
		return '';
	}


	/**
	 * Map a PhpWord font style to IR run flags.
	 *
	 * @param  mixed $font Font style object, style-name string, or null.
	 * @return array Flags accepted by Ir::run().
	 */
	private static function font_flags( $font ): array {
		if ( ! ( $font instanceof Font ) ) {
			return array();
		}

		return array(
			'bold'      => (bool) $font->isBold(),
			'italic'    => (bool) $font->isItalic(),
			'underline' => $font->getUnderline() !== Font::UNDERLINE_NONE,
			'strike'    => (bool) ( $font->isStrikethrough() || $font->isDoubleStrikethrough() ),
			'mono'      => Ir::is_mono_font( (string) $font->getName() ),
		);
	}


	/**
	 * Decode the HTML entities PhpWord leaves in element text.
	 *
	 * The Word2007 reader stores run text as it appeared in the XML, so "&amp;"
	 * and "&#039;" arrive verbatim. Decoding here means BlockRenderer's escaping
	 * sees real characters and never double-encodes.
	 *
	 * @param  string $text Raw text from a PhpWord element.
	 * @return string
	 */
	private static function decode( string $text ): string {
		return html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}
}
