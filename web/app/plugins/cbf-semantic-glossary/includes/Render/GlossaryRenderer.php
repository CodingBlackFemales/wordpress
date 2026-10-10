<?php
/**
 * Render a glossary.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Render;

use CodingBlackFemales\SemanticGlossary\Entry\Definition;
use CodingBlackFemales\SemanticGlossary\Entry\Entry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GlossaryRenderer.
 *
 * Output shape:
 *
 *     <section class="glossary-section" aria-labelledby="…">
 *       <h2 id="…" class="glossary-heading">Glossary</h2>
 *       <nav class="glossary-index" aria-label="Glossary index"><ol>…</ol></nav>
 *       <dl class="glossary">
 *         <div class="glossary-entry" id="glossary-v">
 *           <dt><dfn id="dfn-…"><abbr title="Version control system">VCS</abbr></dfn> (Version control system)
 *             <a href="#ref-…" class="glossary-backlink" aria-label="Back to where VCS is used">↩</a></dt>
 *           <dt class="glossary-alt"><abbr title="Source code management">SCM</abbr> (Source code management)</dt>
 *           <dd>…</dd>
 *         </div>
 *       </dl>
 *     </section>
 *
 * One `<div>` per entry; several `<dt>` share one `<dd>`. Only the primary
 * `<dt>` carries the `<dfn>`, the anchor and the back-link. "Also:" and the separators between
 * alternatives come from the stylesheet, not the markup.
 */
final class GlossaryRenderer {

	/**
	 * The back-link glyph, forced to text presentation so it never renders as an emoji.
	 */
	const BACK_LINK = "\u{21A9}\u{FE0E}";


	/**
	 * Render a glossary; an empty string when there is nothing to list.
	 *
	 * @param GlossaryItem[]       $items   Ordered items.
	 * @param Options              $options Presentation.
	 * @param array<string, mixed> $context Passed to filters; integrations use it to tell glossaries apart.
	 */
	public static function render( array $items, Options $options, array $context = array() ): string {
		if ( $items === array() ) {
			return '';
		}

		$context = array_merge( $context, array( 'options' => $options ) );

		/**
		 * Filters markup placed between the glossary heading and the A–Z index.
		 *
		 * Empty by default. Integrations use it for an introduction or controls
		 * (a course glossary's lesson filter, say).
		 *
		 * @param string               $html    Markup to insert.
		 * @param GlossaryItem[]       $items   Ordered items.
		 * @param array<string, mixed> $context Render context, including 'options'.
		 */
		$before = (string) apply_filters( 'glossary_html_before_index', '', $items, $context );

		/**
		 * Filters markup placed after the glossary's `<dl>`, inside its section.
		 *
		 * @param string               $html    Markup to insert.
		 * @param GlossaryItem[]       $items   Ordered items.
		 * @param array<string, mixed> $context Render context, including 'options'.
		 */
		$after = (string) apply_filters( 'glossary_html_after_list', '', $items, $context );

		$html = sprintf(
			'<section class="glossary-section" aria-labelledby="%1$s"><h%2$d id="%1$s" class="glossary-heading">%3$s</h%2$d>%4$s%5$s%6$s%7$s</section>',
			esc_attr( $options->heading_id ),
			$options->level,
			wp_kses( $options->heading, Definition::ALLOWED_HTML ),
			$before,
			self::index( $items, $options ),
			self::list( $items, $options, $context ),
			$after
		);

		/**
		 * Filters the complete glossary markup.
		 *
		 * @param string               $html    Glossary markup.
		 * @param GlossaryItem[]       $items   Ordered items.
		 * @param array<string, mixed> $context Render context, including 'options'.
		 */
		return apply_filters( 'glossary_html', $html, $items, $context );
	}


	/**
	 * Whether the A–Z index is shown for this many items.
	 *
	 * @param int     $count   Number of items.
	 * @param Options $options Presentation.
	 */
	public static function shows_index( int $count, Options $options ): bool {
		return $options->show_index && $count >= $options->index_min_entries;
	}


	/**
	 * The A–Z index, or nothing.
	 *
	 * Only letters with at least one entry are listed.
	 *
	 * @param GlossaryItem[] $items   Ordered items.
	 * @param Options        $options Presentation.
	 */
	private static function index( array $items, Options $options ): string {
		if ( ! self::shows_index( count( $items ), $options ) ) {
			return '';
		}

		$links = array();
		foreach ( self::first_of_each_letter( $items ) as $item ) {
			$links[] = sprintf( '<li><a href="#%s">%s</a></li>', esc_attr( $item->letter_anchor() ), esc_html( $item->letter ) );
		}

		return sprintf(
			'<nav class="glossary-index" aria-label="%s"><ol>%s</ol></nav>',
			esc_attr__( 'Glossary index', 'cbf-semantic-glossary' ),
			implode( '', $links )
		);
	}


	/**
	 * The `<dl>`.
	 *
	 * @param GlossaryItem[]       $items   Ordered items.
	 * @param Options              $options Presentation.
	 * @param array<string, mixed> $context Render context.
	 */
	private static function list( array $items, Options $options, array $context ): string {
		$targets = self::shows_index( count( $items ), $options ) ? self::first_of_each_letter( $items ) : array();
		$entries = '';

		foreach ( $items as $item ) {
			$entries .= self::entry( $item, $options, in_array( $item, $targets, true ), $context );
		}

		$html = '<dl class="glossary">' . $entries . '</dl>';

		/**
		 * Filters the glossary's `<dl>`.
		 *
		 * @param string               $html    The `<dl>` markup.
		 * @param GlossaryItem[]       $items   Ordered items.
		 * @param array<string, mixed> $context Render context, including 'options'.
		 */
		return apply_filters( 'glossary_list_html', $html, $items, $context );
	}


	/**
	 * One entry's `<div>`.
	 *
	 * @param GlossaryItem         $item      Item.
	 * @param Options              $options   Presentation.
	 * @param bool                 $is_target Whether the A–Z index links here.
	 * @param array<string, mixed> $context   Render context.
	 */
	private static function entry( GlossaryItem $item, Options $options, bool $is_target, array $context ): string {
		$entry = $item->entry;
		$terms = self::primary_term( $entry, self::back_link( $item, $options, $context ) );

		if ( $options->show_alternatives ) {
			foreach ( $entry->alternatives() as $form ) {
				$terms .= '<dt class="glossary-alt">' . self::name( $form ) . '</dt>';
			}
		}

		$html = sprintf(
			'<div class="glossary-entry"%s>%s<dd>%s</dd></div>',
			$is_target ? ' id="' . esc_attr( $item->letter_anchor() ) . '"' : '',
			$terms,
			Definition::layout( Definition::sanitise( $entry->definition ) )
		);

		/**
		 * Filters one entry's markup in a glossary.
		 *
		 * Integrations use this to decorate entries (an "Introduced in…" link,
		 * say) without re-implementing rendering.
		 *
		 * @param string               $html    The entry's `<div>`.
		 * @param Entry                $entry   The entry.
		 * @param array<string, mixed> $context Render context, including 'options' and 'item'.
		 */
		return apply_filters( 'glossary_entry_html', $html, $entry, array_merge( $context, array( 'item' => $item ) ) );
	}


	/**
	 * The primary `<dt>`, holding the `<dfn>`, the anchor and the back-link.
	 *
	 * @param Entry  $entry     Entry.
	 * @param string $back_link Back-link markup, or an empty string.
	 */
	private static function primary_term( Entry $entry, string $back_link ): string {
		$form = $entry->forms[0];
		$name = $form['abbr'] !== ''
			? '<abbr title="' . esc_attr( $form['term'] ) . '">' . esc_html( $form['abbr'] ) . '</abbr>'
			: esc_html( $form['term'] );

		$expansion = $form['abbr'] !== '' ? ' (' . esc_html( $form['term'] ) . ')' : '';

		$back_link = $back_link !== '' ? ' ' . $back_link : '';

		return '<dt><dfn id="' . esc_attr( $entry->anchor() ) . '">' . $name . '</dfn>' . $expansion . $back_link . '</dt>';
	}


	/**
	 * A form's name: the abbreviation leads when there is one.
	 *
	 * @param array{term:string,abbr:string} $form Form.
	 */
	private static function name( array $form ): string {
		if ( $form['abbr'] === '' ) {
			return esc_html( $form['term'] );
		}

		return '<abbr title="' . esc_attr( $form['term'] ) . '">' . esc_html( $form['abbr'] ) . '</abbr> (' . esc_html( $form['term'] ) . ')';
	}


	/**
	 * The back-link, or an empty string.
	 *
	 * Only rendered when the glossary sits in the same post as the reference
	 * (callers turn the option off otherwise) and the entry is marked inline.
	 *
	 * @param GlossaryItem         $item    Item.
	 * @param Options              $options Presentation.
	 * @param array<string, mixed> $context Render context.
	 */
	private static function back_link( GlossaryItem $item, Options $options, array $context ): string {
		if ( ! $options->back_links || ! $item->has_inline ) {
			return '';
		}

		/**
		 * Filters where an entry's back-link points.
		 *
		 * Defaults to the first reference on the same page. A glossary listing
		 * entries from other posts (a course glossary, say) points it at the
		 * post where the entry is first referenced.
		 *
		 * @param string               $href    Link target.
		 * @param Entry                $entry   The entry.
		 * @param array<string, mixed> $context Render context, including 'options' and 'item'.
		 */
		$href = (string) apply_filters( 'glossary_back_link_href', '#' . $item->entry->ref_anchor(), $item->entry, array_merge( $context, array( 'item' => $item ) ) );

		return sprintf(
			'<a href="%s" class="glossary-backlink" aria-label="%s">%s</a>',
			str_starts_with( $href, '#' ) ? esc_attr( $href ) : esc_url( $href ),
			/* translators: %s: the glossary term */
			esc_attr( sprintf( __( 'Back to where %s is used', 'cbf-semantic-glossary' ), $item->entry->display() ) ),
			self::BACK_LINK
		);
	}


	/**
	 * The first item of each index letter.
	 *
	 * @param GlossaryItem[] $items Ordered items.
	 * @return GlossaryItem[]
	 */
	private static function first_of_each_letter( array $items ): array {
		$first = array();
		foreach ( $items as $item ) {
			$first[ $item->letter ] ??= $item;
		}
		return array_values( $first );
	}
}
