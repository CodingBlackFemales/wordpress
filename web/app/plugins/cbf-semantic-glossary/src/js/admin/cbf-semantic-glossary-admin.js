/**
 * Glossary entry edit screen: add and remove alternative-form rows.
 */

const root = document.querySelector( '[data-glossary-forms]' );
const template = document.querySelector( '[data-glossary-form-template]' );
const add = document.querySelector( '[data-glossary-form-add]' );

if ( root && template && add ) {
	const rows = root.querySelector( '[data-glossary-forms-rows]' );

	// Row 0 is the canonical form, outside this table, so new rows start at 1
	// and never reuse an index already on the page.
	let next =
		Math.max(
			0,
			...[
				...rows.querySelectorAll( 'input[name^="glossary_forms["]' ),
			].map( ( input ) =>
				parseInt( input.name.match( /\[(\d+)\]/ )?.[ 1 ] || '0', 10 )
			)
		) + 1;

	add.addEventListener( 'click', () => {
		const html = template.innerHTML.replace( /__i__/g, String( next++ ) );
		rows.insertAdjacentHTML( 'beforeend', html );
		rows.lastElementChild.querySelector( 'input' ).focus();
	} );

	rows.addEventListener( 'click', ( event ) => {
		const remove = event.target.closest( '[data-glossary-form-remove]' );
		if ( ! remove ) {
			return;
		}

		const row = remove.closest( '[data-glossary-form-row]' );
		const focusTarget =
			row.nextElementSibling?.querySelector( 'input' ) ||
			row.previousElementSibling?.querySelector( 'input' ) ||
			add;

		row.remove();
		focusTarget.focus();
	} );
}
