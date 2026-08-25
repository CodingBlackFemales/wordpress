<?php
/**
 * Converts parsed slide data to WordPress Gutenberg block HTML.
 *
 * Port of slides_to_learndash/blocks.py + rich_text.py.
 *
 * @class   Pptx\BlockRenderer
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Pptx;

use PhpOffice\PhpPresentation\Shape\RichText;
use PhpOffice\PhpPresentation\Shape\RichText\Paragraph;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BlockRenderer class.
 *
 * Renders a ParsedDeck (with slide_type applied) to two HTML strings:
 *  - lesson_html  : all body-slide content merged into one block string
 *  - topics_html  : array of {title, html} for each heading-type slide group
 *
 * Block types produced (matching python-pptx pipeline output):
 *  wp:paragraph, wp:heading (h2–h4), wp:list / wp:list-item,
 *  wp:code, wp:columns / wp:column, wp:image
 */
final class BlockRenderer {

	/**
	 * Render a full classified deck to lesson/topic HTML strings.
	 *
	 * @param  array  $classified_deck ParsedDeck with slide_type set.
	 * @param  string $mode            'lesson-only' or 'lesson-with-topics'.
	 * @param  bool   $slide_headings  Whether to emit an H2 for each slide title.
	 * @param  string $media_base_url  Base URL for embedded images (WP attachment URLs
	 *                                 added after import; placeholder during preview).
	 * @return array{lesson_html: string, topics: array}
	 */
	public static function render( array $classified_deck, string $mode = 'lesson-only', bool $slide_headings = true, string $media_base_url = '' ): array {
		$slides = $classified_deck['slides'] ?? array();

		if ( $mode === 'lesson-with-topics' ) {
			return self::render_lesson_with_topics( $slides, $slide_headings, $media_base_url );
		}

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


	/**
	 * Split body slides into topics at each heading-type slide.
	 *
	 * Slides before the first heading go into the lesson only.
	 *
	 * Contiguous body slides with the same title emit only one H2 heading.
	 * Heading-type slides (which become LearnDash Topics) also participate in
	 * the dedup tracking, so a body slide whose title matches the preceding
	 * topic header is not given a redundant H2.
	 *
	 * @param array  $slides
	 * @param bool   $slide_headings
	 * @param string $media_base_url
	 * @return array{lesson_html: string, topics: array}
	 */
	private static function render_lesson_with_topics( array $slides, bool $slide_headings, string $media_base_url ): array {
		$lesson_parts  = array();
		$topics        = array();
		$current_topic = null;
		$prev_title    = null;

		foreach ( $slides as $slide ) {
			$type = $slide['slide_type'];

			if ( in_array( $type, array( 'cover', 'hidden', 'section' ), true ) ) {
				continue;
			}

			if ( $type === 'heading' ) {
				// Save previous topic if exists.
				if ( $current_topic !== null ) {
					$topics[] = $current_topic;
				}
				$heading_title = $slide['title'] ?? '';
				$current_topic = array(
					'title' => $heading_title,
					'parts' => array(),
				);
				// Heading slides become Topic headers — their title is tracked so
				// the first body slide in the topic does not repeat it as an H2.
				if ( $heading_title !== '' ) {
					$prev_title = $heading_title;
				}
				continue;
			}

			// 'body' slide.
			$title        = $slide['title'] ?? '';
			$is_duplicate = $title !== '' && $title === $prev_title;
			$html         = self::render_slide( $slide, $slide_headings && ! $is_duplicate, $media_base_url );

			if ( $title !== '' ) {
				$prev_title = $title;
			}

			if ( $current_topic === null ) {
				$lesson_parts[] = $html;
			} else {
				$current_topic['parts'][] = $html;
			}
		}

		if ( $current_topic !== null ) {
			$topics[] = $current_topic;
		}

		// Collapse topics' parts into html.
		$rendered_topics = array();
		foreach ( $topics as $t ) {
			$rendered_topics[] = array(
				'title' => $t['title'],
				'html'  => implode( "\n", array_filter( $t['parts'] ) ),
			);
		}

		return array(
			'lesson_html' => implode( "\n", array_filter( $lesson_parts ) ),
			'topics'      => $rendered_topics,
		);
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

		// Render content blocks (linear or columns).
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
	 * Render a LinearBlock or ColumnsBlock to HTML.
	 *
	 * @param  array  $block
	 * @param  string $media_base_url
	 * @return string
	 */
	private static function render_content_block( array $block, string $media_base_url ): string {
		if ( $block['type'] === 'columns' ) {
			return self::render_columns_block( $block, $media_base_url );
		}

		// linear — single shape.
		$shape = $block['shapes'][0]['shape'] ?? null;
		if ( ! $shape || ! ( $shape instanceof RichText ) ) {
			return '';
		}

		return self::render_rich_text( $shape );
	}


	/**
	 * Render a multi-column layout block.
	 *
	 * @param  array  $block
	 * @param  string $media_base_url
	 * @return string wp:columns / wp:column block HTML.
	 */
	private static function render_columns_block( array $block, string $media_base_url ): string {
		$col_htmls = array();

		foreach ( $block['columns'] as $col_idx => $col_shapes ) {
			$col_parts = array();
			foreach ( $col_shapes as $shape_entry ) {
				$shape = $shape_entry['shape'];
				if ( $shape instanceof RichText ) {
					$col_parts[] = self::render_rich_text( $shape );
				}
			}
			$col_content = implode( "\n", array_filter( $col_parts ) );
			$col_htmls[] = "<!-- wp:column -->\n<div class=\"wp-block-column\">\n{$col_content}\n</div>\n<!-- /wp:column -->";
		}

		$inner = implode( "\n", $col_htmls );
		return "<!-- wp:columns -->\n<div class=\"wp-block-columns\">\n{$inner}\n</div>\n<!-- /wp:columns -->";
	}


	/**
	 * Render all paragraphs from a RichText shape to WP block HTML.
	 *
	 * @param  RichText $shape
	 * @return string
	 */
	private static function render_rich_text( RichText $shape ): string {
		$paras = $shape->getParagraphs();
		if ( empty( $paras ) ) {
			return '';
		}

		$parts    = array();
		$list_buf = array();

		$flush_list = function () use ( &$list_buf, &$parts ) {
			if ( ! empty( $list_buf ) ) {
				$items   = implode( "\n", array_map( fn( $i ) => "<!-- wp:list-item --><li>{$i}</li><!-- /wp:list-item -->", $list_buf ) );
				$parts[] = "<!-- wp:list -->\n<ul class=\"wp-block-list\">\n{$items}\n</ul>\n<!-- /wp:list -->";
				$list_buf = array();
			}
		};

		foreach ( $paras as $para ) {
			$classified = self::classify_paragraph( $para );
			if ( $classified['kind'] === 'empty' ) {
				continue;
			}

			if ( $classified['kind'] === 'bullet' ) {
				$list_buf[] = $classified['html'];
				continue;
			}

			$flush_list();

			if ( $classified['kind'] === 'heading' ) {
				$level   = $classified['size'] >= 36 ? 3 : 4;
				$parts[] = self::block_heading( $classified['html'], $level );
			} elseif ( $classified['kind'] === 'code' ) {
				$parts[] = "<!-- wp:code -->\n<pre class=\"wp-block-code\"><code>" . esc_html( $classified['text'] ) . "</code></pre>\n<!-- /wp:code -->";
			} else {
				if ( trim( $classified['html'] ) !== '' ) {
					$parts[] = "<!-- wp:paragraph -->\n<p>{$classified['html']}</p>\n<!-- /wp:paragraph -->";
				}
			}
		}

		$flush_list();

		return implode( "\n", array_filter( $parts ) );
	}


	// ── Paragraph classification ──────────────────────────────────────────────

	/**
	 * Classify a single paragraph and return its rendered HTML.
	 *
	 * Mirrors rich_text.py paragraph classification logic.
	 *
	 * @param  Paragraph $para
	 * @return array{kind:string, html:string, text:string, size:int|null}
	 */
	private static function classify_paragraph( Paragraph $para ): array {
		$runs = $para->getRichTextElements();
		if ( empty( $runs ) ) {
			return array(
				'kind' => 'empty',
				'html' => '',
				'text' => '',
				'size' => null,
			);
		}

		$plain_text   = '';
		$html_parts   = array();
		$is_bold      = false;
		$is_italic    = false;
		$font_size    = null;
		$is_monospace = false;
		$has_bullet   = false;

		$bullet = $para->getBulletStyle();
		if ( $bullet && method_exists( $bullet, 'getBulletType' ) && $bullet->getBulletType() !== 'none' ) {
			$has_bullet = true;
		}

		foreach ( $runs as $run ) {
			if ( ! method_exists( $run, 'getText' ) ) {
				continue;
			}

			$text = $run->getText();
			$plain_text .= $text;

			$font       = method_exists( $run, 'getFont' ) ? $run->getFont() : null;
			$bold       = $font ? $font->isBold() : false;
			$italic     = $font ? $font->isItalic() : false;
			$underline  = $font
				? ( $font->getUnderline() !== \PhpOffice\PhpPresentation\Style\Font::UNDERLINE_NONE )
				: false;
			$strike     = $font ? $font->isStrikethrough() : false;
			$run_size   = $font ? $font->getSize() : null;
			$run_mono   = false; // TODO: detect code font by name comparison in Phase 2.

			if ( $bold ) {
				$is_bold    = true;
			}
			if ( $italic ) {
				$is_italic  = true;
			}
			if ( $run_mono ) {
				$is_monospace = true;
			}
			if ( $run_size !== null && $font_size === null ) {
				$font_size = $run_size;
			}

			$html = esc_html( $text );
			if ( $run_mono ) {
				$html = '<code>' . $html . '</code>';
			}
			if ( $bold ) {
				$html = '<strong>' . $html . '</strong>';
			}
			if ( $italic ) {
				$html = '<em>' . $html . '</em>';
			}
			if ( $underline ) {
				$html = '<u>' . $html . '</u>';
			}
			if ( $strike ) {
				$html = '<s>' . $html . '</s>';
			}

			$html_parts[] = $html;
		}

		if ( trim( $plain_text ) === '' ) {
			return array(
				'kind' => 'empty',
				'html' => '',
				'text' => '',
				'size' => null,
			);
		}

		$html = implode( '', $html_parts );

		// Determine paragraph kind.
		$kind = 'paragraph';
		if ( $is_monospace ) {
			$kind = 'code';
		} elseif ( $has_bullet ) {
			$kind = 'bullet';
		} elseif ( $font_size !== null && $font_size >= 22 ) {
			$kind = 'heading';
		}

		return array(
			'kind' => $kind,
			'html' => $html,
			'text' => $plain_text,
			'size' => $font_size,
		);
	}


	// ── Block helpers ─────────────────────────────────────────────────────────

	/**
	 * Produce a wp:heading block.
	 *
	 * @param string $html  Already-escaped inner HTML.
	 * @param int    $level Heading level (2–4).
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
	 * @param  array  $img            Image metadata array from Parser.
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
