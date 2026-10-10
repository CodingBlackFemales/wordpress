<?php
/**
 * A glossary entry.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Entry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Immutable view of one `glossary_term` post.
 *
 * Holds no WordPress objects, so the renderers and the audit can be exercised
 * without a database. The Repository is the only thing that builds these from
 * posts.
 */
final class Entry {

	/**
	 * Prefix of the anchor on the glossary's primary `<dt>`.
	 */
	const DFN_PREFIX = 'dfn-';

	/**
	 * Prefix of the anchor on an entry's first inline reference.
	 */
	const REF_PREFIX = 'ref-';

	/**
	 * Constructor.
	 *
	 * @param int                                       $id         Post ID.
	 * @param string                                    $slug       Post slug; the anchor is derived from it.
	 * @param array<int, array{term:string,abbr:string}> $forms      Name pairs, canonical first.
	 * @param string                                    $definition Definition HTML.
	 * @param string                                    $status     Post status.
	 */
	public function __construct(
		public readonly int $id,
		public readonly string $slug,
		public readonly array $forms,
		public readonly string $definition = '',
		public readonly string $status = 'publish'
	) {}


	/**
	 * The canonical term, shown in the glossary.
	 */
	public function term(): string {
		return $this->forms[0]['term'] ?? '';
	}


	/**
	 * The canonical abbreviation, or an empty string.
	 */
	public function abbr(): string {
		return $this->forms[0]['abbr'] ?? '';
	}


	/**
	 * What the glossary leads with: the abbreviation when there is one.
	 *
	 * This is also the sort key and the source of the A–Z index letter, so
	 * "VCS" sorts under V rather than under "Version control system".
	 */
	public function display(): string {
		return $this->abbr() !== '' ? $this->abbr() : $this->term();
	}


	/**
	 * Fragment identifier of the glossary definition.
	 */
	public function anchor(): string {
		return self::DFN_PREFIX . $this->slug;
	}


	/**
	 * Fragment identifier of the first inline reference.
	 */
	public function ref_anchor(): string {
		return self::REF_PREFIX . $this->slug;
	}


	/**
	 * Whether references to this entry should render.
	 */
	public function is_published(): bool {
		return $this->status === 'publish';
	}


	/**
	 * Every form after the canonical one.
	 *
	 * @return array<int, array{term:string,abbr:string}>
	 */
	public function alternatives(): array {
		return array_slice( $this->forms, 1 );
	}


	/**
	 * Which form some referenced text corresponds to.
	 *
	 * Abbreviations win over terms, so text that is both is treated as an
	 * abbreviation. Text matching neither (an inflection, say) returns null.
	 *
	 * @param string $text Plain text of the reference.
	 * @return array{form:int, type:string}|null
	 */
	public function match( string $text ): ?array {
		$needle = self::fold( $text );

		foreach ( array( 'abbr', 'term' ) as $type ) {
			foreach ( $this->forms as $index => $form ) {
				if ( $form[ $type ] !== '' && self::fold( $form[ $type ] ) === $needle ) {
					return array(
						'form' => $index,
						'type' => $type,
					);
				}
			}
		}

		return null;
	}


	/**
	 * The expansion an `<abbr>` around this text should carry.
	 *
	 * An abbreviation expands to the term on its own row ("SCM" to "Source
	 * code management"), never to the entry name. Text that matches no
	 * abbreviation, but is forced to render as one, expands to the canonical
	 * term.
	 *
	 * @param string $text Plain text of the reference.
	 */
	public function expansion_for( string $text ): string {
		$match = $this->match( $text );

		if ( $match !== null && $match['type'] === 'abbr' ) {
			return $this->forms[ $match['form'] ]['term'];
		}

		return $this->term();
	}


	/**
	 * Whether text matches one of this entry's abbreviations.
	 *
	 * @param string $text Plain text of the reference.
	 */
	public function is_abbreviation( string $text ): bool {
		$match = $this->match( $text );
		return $match !== null && $match['type'] === 'abbr';
	}


	/**
	 * A plain array for REST responses, CLI output and the public API.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'id'         => $this->id,
			'slug'       => $this->slug,
			'anchor'     => $this->anchor(),
			'term'       => $this->term(),
			'abbr'       => $this->abbr(),
			'display'    => $this->display(),
			'forms'      => $this->forms,
			'definition' => $this->definition,
			'status'     => $this->status,
		);
	}


	/**
	 * Case-fold text for comparison.
	 *
	 * @param string $text Text to fold.
	 */
	private static function fold( string $text ): string {
		return mb_strtolower( trim( $text ) );
	}
}
