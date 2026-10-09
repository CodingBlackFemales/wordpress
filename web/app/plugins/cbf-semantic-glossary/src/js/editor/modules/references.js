/**
 * Find and change references across the post's blocks.
 *
 * A reference is stored as `<span class="glossary-ref" data-glossary-id="…">`
 * inside a block's rich-text attribute. The sidebar and the Glossary block need
 * them in document order (block order, then order within the block), which is
 * what "first mention" is measured against.
 */

/**
 * WordPress dependencies
 */
import { getBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { REF_CLASS } from './config';

const REF_SELECTOR = `span.${ REF_CLASS }[data-glossary-id]`;

/**
 * Not preceded or followed by a word character.
 */
const BEFORE = '(?<![\\p{L}\\p{N}_])';
const AFTER = '(?![\\p{L}\\p{N}_])';

/**
 * The HTML of a rich-text attribute value, whichever shape it is in.
 *
 * @param {string|Object} value Attribute value: a string, or RichTextData.
 * @return {string|null} HTML, or null for anything else.
 */
export function htmlOf( value ) {
	if ( typeof value === 'string' ) {
		return value;
	}
	if ( value && typeof value.toHTMLString === 'function' ) {
		return value.toHTMLString();
	}
	return null;
}

/**
 * Names of a block's rich-text attributes.
 *
 * @param {Object} block Block.
 * @return {string[]} Attribute names.
 */
function richTextAttributes( block ) {
	const attributes = getBlockType( block.name )?.attributes || {};

	return Object.keys( attributes ).filter( ( key ) =>
		[ 'rich-text', 'html' ].includes( attributes[ key ].source )
	);
}

/**
 * Call a function for every block, depth first, in document order.
 *
 * @param {Object[]} blocks Blocks.
 * @param {Function} fn     Callback.
 */
export function walkBlocks( blocks, fn ) {
	blocks.forEach( ( block ) => {
		fn( block );
		walkBlocks( block.innerBlocks || [], fn );
	} );
}

/**
 * Parse an HTML fragment into a detached body element.
 *
 * DOMParser documents never run scripts, so this is safe for any content.
 *
 * @param {string} html Fragment.
 * @return {HTMLElement} Body holding the fragment.
 */
function parse( html ) {
	return new window.DOMParser().parseFromString(
		`<!doctype html><body>${ html }</body>`,
		'text/html'
	).body;
}

/**
 * References in one attribute's HTML, cached by the HTML itself.
 *
 * The sidebar recollects references whenever the blocks change, which is on
 * every keystroke; only the block being typed in has new HTML.
 */
const parsedCache = new Map();
const CACHE_LIMIT = 500;

function referencesIn( html ) {
	if ( ! parsedCache.has( html ) ) {
		if ( parsedCache.size >= CACHE_LIMIT ) {
			parsedCache.delete( parsedCache.keys().next().value );
		}
		parsedCache.set(
			html,
			[ ...parse( html ).querySelectorAll( REF_SELECTOR ) ].map(
				( element ) => ( {
					entryId: parseInt( element.dataset.glossaryId, 10 ) || 0,
					text: element.textContent.trim(),
					renderAbbr: element.dataset.glossaryAbbr === 'true',
				} )
			)
		);
	}

	return parsedCache.get( html );
}

/**
 * Every reference, in document order.
 *
 * @param {Object[]} blocks Blocks from the block editor.
 * @return {Array<Object>} References: clientId, blockName, blockNumber,
 *                         attribute, index (within the attribute), entryId,
 *                         text, renderAbbr and position.
 */
export function collectReferences( blocks ) {
	const references = [];
	let blockNumber = 0;

	walkBlocks( blocks, ( block ) => {
		blockNumber++;

		richTextAttributes( block ).forEach( ( attribute ) => {
			const html = htmlOf( block.attributes[ attribute ] );
			if ( ! html || ! html.includes( REF_CLASS ) ) {
				return;
			}

			referencesIn( html ).forEach( ( found, index ) =>
				references.push( {
					clientId: block.clientId,
					blockName: block.name,
					blockNumber,
					attribute,
					index,
					...found,
				} )
			);
		} );
	} );

	return references.map( ( reference, position ) => ( {
		...reference,
		position,
	} ) );
}

/**
 * Group references by entry, keeping document order.
 *
 * @param {Array<Object>} references From collectReferences().
 * @return {Map<number, Array<Object>>} Entry ID to its references.
 */
export function groupByEntry( references ) {
	const groups = new Map();

	references.forEach( ( reference ) => {
		if ( ! groups.has( reference.entryId ) ) {
			groups.set( reference.entryId, [] );
		}
		groups.get( reference.entryId ).push( reference );
	} );

	return groups;
}

/**
 * Unwrap some references, keeping their text.
 *
 * @param {Object[]}      blocks     Blocks from the block editor.
 * @param {Array<Object>} references References to unwrap, from collectReferences( blocks ).
 * @return {Array<{clientId:string, attributes:Object}>} Attribute updates to apply.
 */
export function unwrapReferences( blocks, references ) {
	const byAttribute = new Map();

	references.forEach( ( reference ) => {
		const key = `${ reference.clientId }\u0000${ reference.attribute }`;
		if ( ! byAttribute.has( key ) ) {
			byAttribute.set( key, { ...reference, indexes: new Set() } );
		}
		byAttribute.get( key ).indexes.add( reference.index );
	} );

	const blocksById = new Map();
	walkBlocks( blocks, ( block ) => blocksById.set( block.clientId, block ) );

	return [ ...byAttribute.values() ].map(
		( { clientId, attribute, indexes } ) => {
			const body = parse(
				htmlOf( blocksById.get( clientId ).attributes[ attribute ] )
			);

			[ ...body.querySelectorAll( REF_SELECTOR ) ].forEach(
				( element, index ) => {
					if ( indexes.has( index ) ) {
						element.replaceWith( ...element.childNodes );
					}
				}
			);

			return { clientId, attributes: { [ attribute ]: body.innerHTML } };
		}
	);
}

/**
 * Mark the first unmarked occurrence of some text.
 *
 * Text already inside a reference is skipped, as is text split across inline
 * elements (it is left for the editor to mark by hand).
 *
 * @param {Object[]} blocks  Blocks from the block editor.
 * @param {number}   entryId Entry to reference.
 * @param {string}   text    Exact text to find, as the audit reported it.
 * @return {{clientId:string, attributes:Object}|null} Attribute update, or null when not found.
 */
export function markFirstMention( blocks, entryId, text ) {
	const pattern = new RegExp(
		BEFORE + escapeRegExp( text ).replace( /\s+/g, '\\s+' ) + AFTER,
		'u'
	);
	let update = null;

	walkBlocks( blocks, ( block ) => {
		if ( update ) {
			return;
		}

		richTextAttributes( block ).some( ( attribute ) => {
			const html = htmlOf( block.attributes[ attribute ] );
			const body = html ? parse( html ) : null;

			if ( body && wrapFirstMatch( body, pattern, entryId ) ) {
				update = {
					clientId: block.clientId,
					attributes: { [ attribute ]: body.innerHTML },
				};
			}
			return update !== null;
		} );
	} );

	return update;
}

/**
 * Wrap the first match in a text node outside any reference.
 *
 * @param {HTMLElement} body    Parsed attribute.
 * @param {RegExp}      pattern Text to find.
 * @param {number}      entryId Entry ID.
 * @return {boolean} Whether a match was wrapped.
 */
function wrapFirstMatch( body, pattern, entryId ) {
	const walker = body.ownerDocument.createTreeWalker(
		body,
		window.NodeFilter.SHOW_TEXT
	);

	for ( let node = walker.nextNode(); node; node = walker.nextNode() ) {
		const match = node.parentElement?.closest( `.${ REF_CLASS }` )
			? null
			: pattern.exec( node.data );

		if ( match ) {
			const target = node.splitText( match.index );
			target.splitText( match[ 0 ].length );

			const span = body.ownerDocument.createElement( 'span' );
			span.className = REF_CLASS;
			span.dataset.glossaryId = String( entryId );
			target.replaceWith( span );
			span.appendChild( target );
			return true;
		}
	}

	return false;
}

/**
 * Escape text for use in a regular expression.
 *
 * @param {string} text Text.
 * @return {string} Escaped text.
 */
function escapeRegExp( text ) {
	return text.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
}

/**
 * Select a block and scroll it into view in the editor canvas.
 *
 * @param {Function} selectBlock The block editor's selectBlock action.
 * @param {string}   clientId    Block to show.
 */
export function showBlock( selectBlock, clientId ) {
	selectBlock( clientId );

	const canvas =
		document.querySelector( 'iframe[name="editor-canvas"]' )
			?.contentDocument || document;

	canvas
		.getElementById( `block-${ clientId }` )
		?.scrollIntoView( { block: 'center', behavior: 'smooth' } );
}
