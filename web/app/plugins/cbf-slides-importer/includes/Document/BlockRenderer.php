<?php
/**
 * Converts parsed document IR to WordPress Gutenberg block HTML.
 *
 * Port of slides_to_learndash/blocks.py + rich_text.py, generalised to the
 * format-neutral IR emitted by every source parser (see Document\Ir), so PPTX,
 * PDF and DOCX imports all share one renderer.
 *
 * @class   Document\BlockRenderer
 * @version 1.1.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Document;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BlockRenderer class.
 *
 * Renders a ParsedDeck (with slide_type applied) to block HTML:
 *  - lesson_html  : all body-slide content merged into one block string
 *
 * Used for both 'lesson-only' (sfwd-lessons) and 'topic' (sfwd-topic) import
 * modes — the rendered HTML is identical; only the LearnDash post type differs.
 *
 * Block types produced (matching python-pptx pipeline output):
 *  wp:paragraph, wp:heading (h2–h6), wp:list / wp:list-item,
 *  wp:code, wp:columns / wp:column, wp:table, wp:image
 */
final class BlockRenderer {

	/** Grouping keys used when collecting consecutive paragraphs for rendering. */
	const GROUP_CODE   = 'code';
	const GROUP_SINGLE = 'single';

	/**
	 * Inline tags applied to a run, keyed by the IR flag that turns each on.
	 *
	 * Order matters: each wraps the result of the previous, so <code> ends up
	 * innermost and the emphasis tags nest outwards around it.
	 */
	const RUN_TAGS = array(
		'mono'      => 'code',
		'bold'      => 'strong',
		'italic'    => 'em',
		'underline' => 'u',
		'strike'    => 's',
	);

	/**
	 * Render a full classified deck to block HTML.
	 *
	 * Both 'lesson-only' and 'topic' modes merge all body slides into a single
	 * HTML block (the difference is only in the LearnDash post type created at
	 * import time).
	 *
	 * @param  array  $classified_deck ParsedDeck with slide_type set.
	 * @param  string $mode            'lesson-only' or 'topic'.
	 * @param  bool   $slide_headings  Whether to emit an H2 for each slide title.
	 * @param  string $media_base_url  Base URL for embedded images (WP attachment URLs
	 *                                 added after import; placeholder during preview).
	 * @return array{lesson_html: string, topics: array}
	 */
	public static function render( array $classified_deck, string $mode = 'lesson-only', bool $slide_headings = true, string $media_base_url = '' ): array {
		$slides = $classified_deck['slides'] ?? array();

		return array(
			'lesson_html' => self::render_lesson_only( $slides, $slide_headings, $media_base_url ),
			'topics'      => array(),
		);
	}


	// ── Rendering modes ───────────────────────────────────────────────────────

	/**
	 * Merge all body slides into a single lesson content string.
	 *
	 * Contiguous slides that share the same title (common with Google Slides
	 * progressive-reveal exports) emit only one H2 heading — for the first
	 * slide in each run of identical titles.
	 *
	 * @param array  $slides
	 * @param bool   $slide_headings
	 * @param string $media_base_url
	 * @return string WP block HTML.
	 */
	private static function render_lesson_only( array $slides, bool $slide_headings, string $media_base_url ): string {
		$parts      = array();
		$prev_title = null;

		foreach ( $slides as $slide ) {
			if ( in_array( $slide['slide_type'], array( 'cover', 'hidden' ), true ) ) {
				continue;
			}

			$title        = $slide['title'] ?? '';
			$is_duplicate = $title !== '' && $title === $prev_title;
			$parts[]      = self::render_slide( $slide, $slide_headings && ! $is_duplicate, $media_base_url );

			if ( $title !== '' ) {
				$prev_title = $title;
			}
		}

		return implode( "\n", array_filter( $parts ) );
	}


	// ── Slide → block HTML ────────────────────────────────────────────────────

	/**
	 * Render a single slide to WP block HTML.
	 *
	 * @param  array  $slide
	 * @param  bool   $slide_headings
	 * @param  string $media_base_url
	 * @return string
	 */
	private static function render_slide( array $slide, bool $slide_headings, string $media_base_url ): string {
		$parts = array();

		// Emit slide title as H2 if requested and title is non-empty.
		if ( $slide_headings && ! empty( $slide['title'] ) ) {
			$parts[] = self::block_heading( esc_html( $slide['title'] ), 2 );
		}

		// Render content blocks (linear, columns or table).
		foreach ( $slide['content'] as $block ) {
			$parts[] = self::render_content_block( $block, $media_base_url );
		}

		// Render images (at end of slide).
		foreach ( $slide['images'] as $img ) {
			$parts[] = self::render_image_block( $img, $media_base_url );
		}

		return implode( "\n", array_filter( $parts ) );
	}


	/**
	 * Render one content block to HTML.
	 *
	 * @param  array  $block          ContentBlock (linear, columns or table).
	 * @param  string $media_base_url Base URL for embedded images.
	 * @return string
	 */
	private static function render_content_block( array $block, string $media_base_url ): string {
		switch ( $block['type'] ?? 'linear' ) {
			case 'columns':
				return self::render_columns_block( $block );
			case 'table':
				return self::render_table_block( $block );
			default:
				return self::render_paragraphs( $block['paragraphs'] ?? array() );
		}
	}


	/**
	 * Render a multi-column layout block.
	 *
	 * @param  array $block ContentBlock of type 'columns'.
	 * @return string wp:columns / wp:column block HTML.
	 */
	private static function render_columns_block( array $block ): string {
		$col_htmls = array();

		foreach ( $block['columns'] as $col_paragraphs ) {
			$col_content = self::render_paragraphs( $col_paragraphs );
			$col_htmls[] = "<!-- wp:column -->\n<div class=\"wp-block-column\">\n{$col_content}\n</div>\n<!-- /wp:column -->";
		}

		$inner = implode( "\n", $col_htmls );
		return "<!-- wp:columns -->\n<div class=\"wp-block-columns\">\n{$inner}\n</div>\n<!-- /wp:columns -->";
	}


	/**
	 * Render a table block.
	 *
	 * Cell content is flattened to inline HTML — Gutenberg's core table block
	 * does not accept nested blocks inside cells.
	 *
	 * @param  array $block ContentBlock of type 'table'.
	 * @return string wp:table block HTML.
	 */
	private static function render_table_block( array $block ): string {
		$rows = $block['rows'] ?? array();
		if ( empty( $rows ) ) {
			return '';
		}

		$inner = self::render_table_rows( $rows, ! empty( $block['header'] ) );

		return "<!-- wp:table -->\n<figure class=\"wp-block-table\"><table>{$inner}</table></figure>\n<!-- /wp:table -->";
	}


	/**
	 * Render a table's rows into thead/tbody markup.
	 *
	 * @param  array $rows       Rows of cells, each cell a Paragraph[].
	 * @param  bool  $has_header Whether the first row is a header row.
	 * @return string
	 */
	private static function render_table_rows( array $rows, bool $has_header ): string {
		$head      = '';
		$body_rows = array();

		foreach ( $rows as $index => $row ) {
			$is_header = $has_header && $index === 0;
			$cells     = self::render_table_cells( $row, $is_header ? 'th' : 'td' );

			if ( $is_header ) {
				$head = "<thead><tr>{$cells}</tr></thead>";
			} else {
				$body_rows[] = "<tr>{$cells}</tr>";
			}
		}

		$body = empty( $body_rows ) ? '' : '<tbody>' . implode( '', $body_rows ) . '</tbody>';

		return $head . $body;
	}


	/**
	 * Render one table row's cells.
	 *
	 * @param  array  $row Cells, each a Paragraph[].
	 * @param  string $tag Cell tag, 'th' or 'td'.
	 * @return string
	 */
	private static function render_table_cells( array $row, string $tag ): string {
		$cells = '';

		foreach ( $row as $cell_paragraphs ) {
			$lines = array();
			foreach ( $cell_paragraphs as $para ) {
				$html = self::inline_html( $para );
				if ( trim( $html ) !== '' ) {
					$lines[] = $html;
				}
			}
			$cells .= "<{$tag}>" . implode( '<br>', $lines ) . "</{$tag}>";
		}

		return $cells;
	}


	/**
	 * Render an ordered list of IR paragraphs to WP block HTML.
	 *
	 * Paragraphs are first grouped so that a run of bullets becomes one wp:list
	 * and a run of code lines one wp:code, rather than a block each.
	 *
	 * @param  array $paragraphs Paragraph arrays.
	 * @return string
	 */
	private static function render_paragraphs( array $paragraphs ): string {
		$parts = array();

		foreach ( self::group_paragraphs( $paragraphs ) as $group ) {
			$parts[] = self::render_group( $group );
		}

		return implode( "\n", array_filter( $parts ) );
	}


	/**
	 * Collect paragraphs into consecutive same-kind groups.
	 *
	 * Blank paragraphs are dropped here, which also stops an empty line in the
	 * middle of a list from splitting it in two.
	 *
	 * @param  array $paragraphs Paragraph arrays.
	 * @return array<array{key: string, paras: array}>
	 */
	private static function group_paragraphs( array $paragraphs ): array {
		$groups = array();

		foreach ( $paragraphs as $para ) {
			if ( trim( Ir::plain_text( $para ) ) === '' ) {
				continue;
			}
			$groups = self::append_to_groups( $groups, $para );
		}

		return $groups;
	}


	/**
	 * Add a paragraph to the open group, or start a new one.
	 *
	 * @param  array $groups Groups collected so far.
	 * @param  array $para   Paragraph to place.
	 * @return array Updated groups.
	 */
	private static function append_to_groups( array $groups, array $para ): array {
		$key  = self::group_key( $para );
		$last = count( $groups ) - 1;

		if ( $last >= 0 && $groups[ $last ]['key'] === $key && $key !== self::GROUP_SINGLE ) {
			$groups[ $last ]['paras'][] = $para;
			return $groups;
		}

		$groups[] = array(
			'key'   => $key,
			'paras' => array( $para ),
		);

		return $groups;
	}


	/**
	 * Return the grouping key for a paragraph.
	 *
	 * Bullets separate by list type so an ordered list never absorbs an
	 * unordered one; everything else stands alone.
	 *
	 * @param  array $para Paragraph.
	 * @return string
	 */
	private static function group_key( array $para ): string {
		$kind = $para['kind'] ?? Ir::KIND_PARAGRAPH;

		if ( $kind === Ir::KIND_CODE ) {
			return self::GROUP_CODE;
		}

		if ( $kind === Ir::KIND_BULLET ) {
			return empty( $para['ordered'] ) ? 'ul' : 'ol';
		}

		return self::GROUP_SINGLE;
	}


	/**
	 * Render one paragraph group to block HTML.
	 *
	 * @param  array $group Group from group_paragraphs().
	 * @return string
	 */
	private static function render_group( array $group ): string {
		switch ( $group['key'] ) {
			case self::GROUP_CODE:
				return self::render_code_block( $group['paras'] );
			case 'ul':
			case 'ol':
				return self::render_list_block( $group['paras'], $group['key'] );
			default:
				return self::render_standalone( $group['paras'][0] );
		}
	}


	/**
	 * Render a run of bullet paragraphs as a single wp:list block.
	 *
	 * @param  array  $paragraphs Bullet paragraphs.
	 * @param  string $tag        'ul' or 'ol'.
	 * @return string
	 */
	private static function render_list_block( array $paragraphs, string $tag ): string {
		$items = array();

		foreach ( $paragraphs as $para ) {
			$items[] = '<!-- wp:list-item --><li>' . self::inline_html( $para ) . '</li><!-- /wp:list-item -->';
		}

		$inner = implode( "\n", $items );
		$attrs = $tag === 'ol' ? ' {"ordered":true}' : '';

		return "<!-- wp:list{$attrs} -->\n<{$tag} class=\"wp-block-list\">\n{$inner}\n</{$tag}>\n<!-- /wp:list -->";
	}


	/**
	 * Render a run of code paragraphs as a single wp:code block.
	 *
	 * @param  array $paragraphs Code paragraphs, one per source line.
	 * @return string
	 */
	private static function render_code_block( array $paragraphs ): string {
		$lines = array();

		foreach ( $paragraphs as $para ) {
			$lines[] = Ir::plain_text( $para );
		}

		$code = esc_html( implode( "\n", $lines ) );

		return "<!-- wp:code -->\n<pre class=\"wp-block-code\"><code>{$code}</code></pre>\n<!-- /wp:code -->";
	}


	/**
	 * Render a paragraph that stands on its own — a heading or body paragraph.
	 *
	 * @param  array $para Paragraph.
	 * @return string
	 */
	private static function render_standalone( array $para ): string {
		$html = self::inline_html( $para );

		if ( ( $para['kind'] ?? Ir::KIND_PARAGRAPH ) === Ir::KIND_HEADING ) {
			return self::block_heading( $html, self::heading_level( $para ) );
		}

		return "<!-- wp:paragraph -->\n<p>{$html}</p>\n<!-- /wp:paragraph -->";
	}


	/**
	 * Resolve a heading paragraph's output level.
	 *
	 * Parsers that know the document's heading hierarchy (DOCX) set `level`
	 * explicitly. Parsers that only have font metrics (PPTX, PDF) leave it at 0,
	 * in which case the level is derived from the font size — mirroring the
	 * python-pptx pipeline's 36 pt threshold.
	 *
	 * @param  array $para Heading paragraph.
	 * @return int Heading level, clamped to 2–6.
	 */
	private static function heading_level( array $para ): int {
		$level = (int) ( $para['level'] ?? 0 );
		if ( $level > 0 ) {
			return max( 2, min( 6, $level ) );
		}
		return ( (float) ( $para['size'] ?? 0 ) ) >= 36.0 ? 3 : 4;
	}


	// ── Inline rendering ──────────────────────────────────────────────────────

	/**
	 * Render a paragraph's runs to inline HTML.
	 *
	 * Escaping happens here and nowhere else — every run's text is untrusted
	 * document content.
	 *
	 * @param  array $para Paragraph.
	 * @return string
	 */
	private static function inline_html( array $para ): string {
		$html = '';
		foreach ( $para['runs'] ?? array() as $run ) {
			$html .= self::run_html( $run );
		}
		return $html;
	}


	/**
	 * Render a single run to inline HTML with its formatting applied.
	 *
	 * @param  array $run Run.
	 * @return string
	 */
	private static function run_html( array $run ): string {
		$text = (string) ( $run['text'] ?? '' );
		if ( $text === '' ) {
			return '';
		}

		$html = self::apply_run_tags( esc_html( $text ), $run );
		$href = esc_url( (string) ( $run['link'] ?? '' ) );

		if ( $href !== '' ) {
			$html = '<a href="' . $href . '">' . $html . '</a>';
		}

		return $html;
	}


	/**
	 * Wrap already-escaped text in the inline tags a run's flags call for.
	 *
	 * @param  string $html Escaped text.
	 * @param  array  $run  Run.
	 * @return string
	 */
	private static function apply_run_tags( string $html, array $run ): string {
		// Applied innermost-first, so the emphasis wrappers nest around <code>.
		foreach ( self::RUN_TAGS as $flag => $tag ) {
			if ( ! empty( $run[ $flag ] ) ) {
				$html = "<{$tag}>{$html}</{$tag}>";
			}
		}

		return $html;
	}


	// ── Block helpers ─────────────────────────────────────────────────────────

	/**
	 * Produce a wp:heading block.
	 *
	 * @param string $html  Already-escaped inner HTML.
	 * @param int    $level Heading level (2–6).
	 * @return string
	 */
	private static function block_heading( string $html, int $level = 2 ): string {
		$level = max( 2, min( 6, $level ) );
		return "<!-- wp:heading {\"level\":{$level}} -->\n<h{$level} class=\"wp-block-heading\">{$html}</h{$level}>\n<!-- /wp:heading -->";
	}


	/**
	 * Produce a wp:image block.
	 *
	 * When no $media_base_url is supplied (import path), the src is rendered as
	 * `media/filename.ext` — the exact prefix that ELDBC_Media::rewrite_paths()
	 * scans for and replaces with the WP attachment URL after upload.
	 *
	 * When a real base URL is supplied (preview path), the full URL is used.
	 *
	 * @param  array  $img            Image metadata array from a parser.
	 * @param  string $media_base_url Base URL for media paths (empty = import placeholder).
	 * @return string
	 */
	private static function render_image_block( array $img, string $media_base_url ): string {
		$base = $media_base_url !== '' ? trailingslashit( $media_base_url ) : 'media/';
		$src  = $base . $img['filename'];
		return sprintf(
			"<!-- wp:image -->\n<figure class=\"wp-block-image\"><img src=\"%s\" alt=\"\"/></figure>\n<!-- /wp:image -->",
			esc_attr( $src )
		);
	}
}
