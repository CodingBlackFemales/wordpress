<?php
/**
 * Format-neutral intermediate representation (IR) for parsed documents.
 *
 * Every source parser (PPTX, PDF, DOCX) emits the same IR so that classification
 * and block rendering stay format-agnostic.  Only the parsers know about
 * PhpPresentation, PhpWord or PdfParser objects; nothing downstream does.
 *
 * The IR is built entirely from scalars and arrays, which means a ParsedDeck can
 * be JSON-encoded without loss — unlike the shape objects the PPTX parser used
 * to embed.
 *
 * ─ Shapes ────────────────────────────────────────────────────────────────────
 *
 * Run:
 *   { text: string, bold: bool, italic: bool, underline: bool,
 *     strike: bool, mono: bool, link: string }
 *
 * Paragraph:
 *   { kind: 'paragraph'|'heading'|'bullet'|'code',
 *     runs: Run[], size: float|null, level: int, ordered: bool }
 *
 *   - `level` is the heading level (2–6) for headings and the indent depth
 *     (0-based) for bullets. Ignored for other kinds.
 *   - `ordered` marks a bullet as a numbered-list item.
 *
 * TextBox (input to BlockLayout):
 *   { l: int, t: int, w: int, h: int, paragraphs: Paragraph[] }
 *
 * ContentBlock (output of BlockLayout, input to BlockRenderer):
 *   { type: 'linear',  paragraphs: Paragraph[] }
 *   { type: 'columns', columns: Paragraph[][], widths: int[] }
 *   { type: 'table',   rows: Paragraph[][][], header: bool }
 *
 * @class   Document\Ir
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Document;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ir class.
 *
 * A namespace of small constructors and predicates. Parsers call these instead
 * of hand-rolling array literals so the IR stays consistent across formats.
 */
final class Ir {

	/** Paragraph kinds. */
	const KIND_PARAGRAPH = 'paragraph';
	const KIND_HEADING   = 'heading';
	const KIND_BULLET    = 'bullet';
	const KIND_CODE      = 'code';

	/**
	 * Substrings that identify a monospaced font by name.
	 *
	 * Matched case-insensitively against the font family, after stripping any
	 * PDF subset prefix (e.g. "QHIUOC+Consolas" → "Consolas"). Monospaced text
	 * is rendered as a code block rather than a paragraph.
	 */
	const MONO_FONT_HINTS = array(
		'mono',
		'consol',
		'courier',
		'menlo',
		'monaco',
		'inconsolata',
		'cascadia',
		'andale',
		'lucida console',
		'ibm plex mono',
		'source code',
		'fira code',
		'jetbrains',
		'operator',
		'hack',
		'terminal',
	);

	/**
	 * Leading glyphs that mark a plain-text line as a bullet item.
	 *
	 * Applies to formats that flatten list structure into literal text — PDF
	 * content streams and DOCX runs exported from Google Docs both do this.
	 *
	 * These are unambiguous and stand alone, because PDF exporters routinely
	 * draw the bullet hard against the text with no separating space.
	 */
	const BULLET_GLYPHS = array( '•', '●', '◦', '‣', '▪', '▫', '·', '∙', '»', '›' );

	/**
	 * Leading characters that mark a bullet only when followed by whitespace.
	 *
	 * These double as ordinary punctuation, so "well-known" and "3*4" must not
	 * be mistaken for list items.
	 */
	const BULLET_PUNCTUATION = array( '-', '–', '—', '*' );


	/**
	 * Build a text run.
	 *
	 * @param  string $text  Run text (unescaped).
	 * @param  array  $flags Any of: bold, italic, underline, strike, mono (bool); link (string).
	 * @return array Run.
	 */
	public static function run( string $text, array $flags = array() ): array {
		return array(
			'text'      => $text,
			'bold'      => ! empty( $flags['bold'] ),
			'italic'    => ! empty( $flags['italic'] ),
			'underline' => ! empty( $flags['underline'] ),
			'strike'    => ! empty( $flags['strike'] ),
			'mono'      => ! empty( $flags['mono'] ),
			'link'      => isset( $flags['link'] ) ? (string) $flags['link'] : '',
		);
	}


	/**
	 * Build a paragraph.
	 *
	 * @param  string $kind  One of the KIND_* constants.
	 * @param  array  $runs  Run arrays.
	 * @param  array  $attrs Any of: size (float|null), level (int), ordered (bool).
	 * @return array Paragraph.
	 */
	public static function para( string $kind, array $runs, array $attrs = array() ): array {
		return array(
			'kind'    => $kind,
			'runs'    => array_values( $runs ),
			'size'    => isset( $attrs['size'] ) ? (float) $attrs['size'] : null,
			'level'   => isset( $attrs['level'] ) ? (int) $attrs['level'] : 0,
			'ordered' => ! empty( $attrs['ordered'] ),
		);
	}


	/**
	 * Build a paragraph holding a single unstyled run.
	 *
	 * @param  string $kind  Paragraph kind.
	 * @param  string $text  Paragraph text.
	 * @param  array  $attrs Paragraph attributes (see para()).
	 * @return array Paragraph.
	 */
	public static function text_para( string $kind, string $text, array $attrs = array() ): array {
		return self::para( $kind, array( self::run( $text ) ), $attrs );
	}


	/**
	 * Build a positioned text box for BlockLayout.
	 *
	 * @param  float $l          Left edge in px.
	 * @param  float $t          Top edge in px.
	 * @param  float $w          Width in px.
	 * @param  float $h          Height in px.
	 * @param  array $paragraphs Paragraph arrays.
	 * @return array TextBox.
	 */
	public static function box( float $l, float $t, float $w, float $h, array $paragraphs ): array {
		return array(
			'l'          => (int) round( $l ),
			't'          => (int) round( $t ),
			'w'          => (int) round( $w ),
			'h'          => (int) round( $h ),
			'paragraphs' => array_values( $paragraphs ),
		);
	}


	/** Build a single-column content block. */
	public static function linear( array $paragraphs ): array {
		return array(
			'type'       => 'linear',
			'paragraphs' => array_values( $paragraphs ),
		);
	}


	/**
	 * Build a multi-column content block.
	 *
	 * @param  array $columns Array of Paragraph[] — one entry per column.
	 * @param  array $widths  Column widths as percentages of the slide width.
	 * @return array ContentBlock.
	 */
	public static function columns( array $columns, array $widths ): array {
		return array(
			'type'    => 'columns',
			'columns' => array_values( $columns ),
			'widths'  => array_values( $widths ),
		);
	}


	/**
	 * Build a table content block.
	 *
	 * @param  array $rows   Rows of cells, each cell a Paragraph[].
	 * @param  bool  $header Whether the first row is a header row.
	 * @return array ContentBlock.
	 */
	public static function table( array $rows, bool $header = false ): array {
		return array(
			'type'   => 'table',
			'rows'   => array_values( $rows ),
			'header' => $header,
		);
	}


	/**
	 * Concatenate a paragraph's runs into plain text.
	 *
	 * @param  array $para Paragraph.
	 * @return string
	 */
	public static function plain_text( array $para ): string {
		$text = '';
		foreach ( $para['runs'] ?? array() as $run ) {
			$text .= $run['text'] ?? '';
		}
		return $text;
	}


	/**
	 * Concatenate a list of paragraphs into plain text, one line per paragraph.
	 *
	 * @param  array $paragraphs Paragraph arrays.
	 * @return string
	 */
	public static function plain_text_all( array $paragraphs ): string {
		$lines = array();
		foreach ( $paragraphs as $para ) {
			$lines[] = self::plain_text( $para );
		}
		return trim( implode( "\n", $lines ) );
	}


	/**
	 * Drop paragraphs whose runs contain no non-whitespace text.
	 *
	 * @param  array $paragraphs Paragraph arrays.
	 * @return array Filtered paragraphs, re-indexed.
	 */
	public static function drop_empty( array $paragraphs ): array {
		$kept = array();
		foreach ( $paragraphs as $para ) {
			if ( trim( self::plain_text( $para ) ) !== '' ) {
				$kept[] = $para;
			}
		}
		return $kept;
	}


	/**
	 * Return true when a font family name looks monospaced.
	 *
	 * Strips a PDF subset prefix ("ABCDEF+Consolas") and any style suffix before
	 * matching against MONO_FONT_HINTS.
	 *
	 * @param  string $font_name Raw font family name.
	 * @return bool
	 */
	public static function is_mono_font( string $font_name ): bool {
		$name = strtolower( self::base_font_name( $font_name ) );
		if ( $name === '' ) {
			return false;
		}
		foreach ( self::MONO_FONT_HINTS as $hint ) {
			if ( str_contains( $name, $hint ) ) {
				return true;
			}
		}
		return false;
	}


	/**
	 * Strip a PDF subset prefix from a font name.
	 *
	 * PDF embeds subset fonts as "ABCDEF+FamilyName"; the six-letter tag carries
	 * no meaning for us.
	 *
	 * @param  string $font_name Raw font name.
	 * @return string
	 */
	public static function base_font_name( string $font_name ): string {
		return (string) preg_replace( '/^[A-Z]{6}\+/', '', trim( $font_name ) );
	}


	/**
	 * Infer bold/italic flags from a font name's style suffix.
	 *
	 * PDF gives no explicit weight — the embedded font name is the only signal
	 * (e.g. "Montserrat-Bold", "ArialMT,BoldItalic").
	 *
	 * @param  string $font_name Raw font name.
	 * @return array{bold: bool, italic: bool}
	 */
	public static function font_style_flags( string $font_name ): array {
		$name = strtolower( self::base_font_name( $font_name ) );
		return array(
			'bold'   => (bool) preg_match( '/(bold|black|heavy|semibold|demibold)/', $name ),
			'italic' => (bool) preg_match( '/(italic|oblique)/', $name ),
		);
	}


	/**
	 * Split a leading bullet marker off a line of text.
	 *
	 * Returns null when the line is not a bullet. A hyphen is only treated as a
	 * marker when followed by whitespace, so "well-known" is left alone.
	 *
	 * @param  string $text Line of plain text.
	 * @return array{text: string, ordered: bool}|null
	 */
	public static function split_bullet_marker( string $text ): ?array {
		$trimmed = ltrim( $text );
		if ( $trimmed === '' ) {
			return null;
		}

		foreach ( self::BULLET_GLYPHS as $marker ) {
			if ( str_starts_with( $trimmed, $marker ) ) {
				return self::bullet_result( substr( $trimmed, strlen( $marker ) ) );
			}
		}

		return self::split_punctuation_marker( $trimmed );
	}


	/**
	 * Split a leading punctuation marker off a line, if one is there.
	 *
	 * @param  string $trimmed Line with leading whitespace already removed.
	 * @return array{text: string, ordered: bool}|null
	 */
	private static function split_punctuation_marker( string $trimmed ): ?array {
		foreach ( self::BULLET_PUNCTUATION as $marker ) {
			if ( ! str_starts_with( $trimmed, $marker ) ) {
				continue;
			}

			$rest = substr( $trimmed, strlen( $marker ) );
			if ( $rest !== '' && ! preg_match( '/^\s/', $rest ) ) {
				continue;
			}

			return self::bullet_result( $rest );
		}

		return null;
	}


	/**
	 * Package the text that follows a bullet marker.
	 *
	 * @param  string $rest Text after the marker.
	 * @return array{text: string, ordered: bool}|null Null when nothing follows it.
	 */
	private static function bullet_result( string $rest ): ?array {
		$text = ltrim( $rest );
		if ( $text === '' ) {
			return null;
		}
		return array(
			'text'    => $text,
			'ordered' => false,
		);
	}
}
