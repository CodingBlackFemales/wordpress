/**
 * Mirrors of the server's naming rules, for live hints in the editor.
 *
 * The server stays the authority: these only drive what the editor *says*
 * will happen (Entry::match() and GlossaryBuilder in PHP decide what does).
 */

/**
 * Which form some text corresponds to: abbreviations first, then terms.
 *
 * @param {string}                           text  Referenced text.
 * @param {Array<{term:string,abbr:string}>} forms Entry forms.
 * @return {{form:number,type:'abbr'|'term'}|null} Match, or null for an inflection.
 */
export function matchForm( text, forms = [] ) {
	const needle = text.trim().toLowerCase();

	for ( const type of [ 'abbr', 'term' ] ) {
		const index = forms.findIndex(
			( form ) =>
				form[ type ] && form[ type ].trim().toLowerCase() === needle
		);
		if ( index !== -1 ) {
			return { form: index, type };
		}
	}

	return null;
}

/**
 * Whether text looks like an abbreviation: two or more capitals or digits,
 * optionally with a trailing plural "s" ("VCS", "PRs", "2FA").
 *
 * @param {string} text Selected text.
 * @return {boolean} Whether to prefill it as an abbreviation.
 */
export function looksLikeAbbreviation( text ) {
	return /^[A-Z0-9][A-Z0-9.&-]*[A-Z0-9]s?$/.test( text.trim() );
}

/**
 * What the glossary leads with: the abbreviation when there is one.
 *
 * @param {{forms:Array<{term:string,abbr:string}>}} entry Entry.
 * @return {string} Displayed term.
 */
export function displayTerm( entry ) {
	const [ first = { term: '', abbr: '' } ] = entry.forms || [];
	return first.abbr || first.term;
}

/**
 * The A–Z index group of a displayed term: a capital letter, or "#".
 *
 * @param {string} display Displayed term.
 * @return {string} Index group.
 */
export function indexLetter( display ) {
	const first = display
		.trim()
		.charAt( 0 )
		.normalize( 'NFD' )
		.replace( /[̀-ͯ]/g, '' )
		.toUpperCase();

	return /^[A-Z]$/.test( first ) ? first : '#';
}

/**
 * Sort entries as the glossary does: "#" first, then naturally by displayed term.
 *
 * @param {Array<Object>} entries Entries.
 * @return {Array<Object>} A sorted copy.
 */
export function sortEntries( entries ) {
	const collator = new Intl.Collator( undefined, {
		numeric: true,
		sensitivity: 'base',
	} );

	return [ ...entries ].sort( ( a, b ) => {
		const groupA = indexLetter( displayTerm( a ) ) === '#' ? 0 : 1;
		const groupB = indexLetter( displayTerm( b ) ) === '#' ? 0 : 1;

		return (
			groupA - groupB ||
			collator.compare( displayTerm( a ), displayTerm( b ) ) ||
			a.id - b.id
		);
	} );
}

/**
 * Plain text of some HTML, for one-line previews.
 *
 * @param {string} html Markup.
 * @return {string} Text.
 */
export function plainText( html = '' ) {
	const doc = new window.DOMParser().parseFromString( html, 'text/html' );
	return ( doc.body.textContent || '' ).replace( /\s+/g, ' ' ).trim();
}
