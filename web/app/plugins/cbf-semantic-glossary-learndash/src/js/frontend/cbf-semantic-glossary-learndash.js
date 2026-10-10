/**
 * The course glossary's lesson filter.
 *
 * A multi-select combobox over the lessons the learner can open. The server
 * renders the full list for this learner; this only hides and shows entries
 * already on the page, by their `data-lesson`, and keeps the A–Z index
 * pointing at visible entries. No requests are made.
 *
 * Pattern: https://uxpatterns.dev/patterns/forms/multi-select-input
 */

/**
 * WordPress dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';

const ALL = 'all';

/**
 * "{n} lessons selected · {m} terms", as LessonFilter::status() builds it.
 *
 * @param {number} lessons Lessons selected.
 * @param {number} terms   Entries shown.
 * @return {string} Status text.
 */
function statusText( lessons, terms ) {
	return sprintf(
		/* translators: 1: "N lessons selected", 2: "N terms" */
		__( '%1$s · %2$s', 'cbf-semantic-glossary-learndash' ),
		sprintf(
			/* translators: %d: number of lessons */
			_n(
				'%d lesson selected',
				'%d lessons selected',
				lessons,
				'cbf-semantic-glossary-learndash'
			),
			lessons
		),
		sprintf(
			/* translators: %d: number of terms */
			_n(
				'%d term',
				'%d terms',
				terms,
				'cbf-semantic-glossary-learndash'
			),
			terms
		)
	);
}

/**
 * Wire up one glossary's filter.
 *
 * @param {HTMLElement} filter The filter's wrapper.
 */
function initFilter( filter ) {
	const section = filter.closest( '.glossary-section' );
	const control = filter.querySelector( '[role="combobox"]' );
	const input = filter.querySelector( '.course-glossary-filter__input' );
	const chips = filter.querySelector( '.course-glossary-filter__chips' );
	const listbox = filter.querySelector( '[role="listbox"]' );
	const status = filter.querySelector( '.course-glossary-filter__status' );
	const allOption = listbox.querySelector( `[data-value="${ ALL }"]` );
	const options = [
		...listbox.querySelectorAll(
			'[role="option"]:not([data-value="all"])'
		),
	];
	const entries = [ ...section.querySelectorAll( '.glossary-entry' ) ];
	const indexNav = section.querySelector( '.glossary-index' );
	const indexLinks = indexNav ? [ ...indexNav.querySelectorAll( 'a' ) ] : [];
	const list = section.querySelector( 'dl.glossary' );
	const empty = section.querySelector( '.course-glossary-empty' );

	const selected = new Set(
		options.map( ( option ) => option.dataset.value )
	);
	let active = null;

	const titleOf = ( option ) =>
		option.querySelector( '.course-glossary-filter__text' ).textContent;

	const visibleOptions = () =>
		[ allOption, ...options ].filter( ( option ) => ! option.hidden );

	function setActive( option ) {
		active?.classList.remove( 'is-active' );
		active = option;

		if ( option ) {
			option.classList.add( 'is-active' );
			option.scrollIntoView( { block: 'nearest' } );
			input.setAttribute( 'aria-activedescendant', option.id );
		} else {
			input.removeAttribute( 'aria-activedescendant' );
		}
	}

	function open() {
		listbox.hidden = false;
		control.setAttribute( 'aria-expanded', 'true' );
	}

	function close() {
		listbox.hidden = true;
		control.setAttribute( 'aria-expanded', 'false' );
		setActive( null );
	}

	function renderChips() {
		chips.replaceChildren(
			...options
				.filter( ( option ) => selected.has( option.dataset.value ) )
				.map( ( option ) => {
					const chip = document.createElement( 'li' );
					const label = document.createElement( 'span' );
					const remove = document.createElement( 'button' );

					chip.className = 'course-glossary-filter__chip';
					label.textContent = titleOf( option );
					remove.type = 'button';
					remove.className = 'course-glossary-filter__remove';
					remove.dataset.value = option.dataset.value;
					remove.setAttribute(
						'aria-label',
						sprintf(
							/* translators: %s: lesson title */
							__(
								'Remove %s',
								'cbf-semantic-glossary-learndash'
							),
							titleOf( option )
						)
					);
					remove.textContent = '×';

					chip.append( label, remove );
					return chip;
				} )
		);
	}

	/**
	 * Point each index letter at its first visible entry, hiding letters with none.
	 */
	function updateIndex() {
		let letters = 0;

		for ( const link of indexLinks ) {
			const anchor = link.hash.slice( 1 );
			const letter = link.textContent.trim();
			const target = entries.find(
				( entry ) => ! entry.hidden && entry.dataset.letter === letter
			);

			link.closest( 'li' ).hidden = ! target;

			if ( target && target.id !== anchor ) {
				section
					.querySelector( `[id="${ anchor }"]` )
					?.removeAttribute( 'id' );
				target.id = anchor;
			}

			letters += target ? 1 : 0;
		}

		if ( indexNav ) {
			indexNav.hidden = letters === 0;
		}
	}

	function apply() {
		let shown = 0;

		for ( const entry of entries ) {
			entry.hidden = ! selected.has( entry.dataset.lesson );
			shown += entry.hidden ? 0 : 1;
		}

		for ( const option of options ) {
			option.setAttribute(
				'aria-selected',
				String( selected.has( option.dataset.value ) )
			);
		}
		allOption.setAttribute(
			'aria-selected',
			String( selected.size === options.length )
		);

		if ( list ) {
			list.hidden = shown === 0;
		}
		if ( empty ) {
			empty.hidden = selected.size > 0;
		}

		updateIndex();
		renderChips();
		status.textContent = statusText( selected.size, shown );
	}

	function toggle( value ) {
		if ( value === ALL ) {
			const all = selected.size === options.length;
			options.forEach( ( option ) =>
				all
					? selected.delete( option.dataset.value )
					: selected.add( option.dataset.value )
			);
		} else if ( selected.has( value ) ) {
			selected.delete( value );
		} else {
			selected.add( value );
		}

		apply();
	}

	function search() {
		const query = input.value.trim().toLowerCase();

		allOption.hidden = query !== '';
		for ( const option of options ) {
			option.hidden = ! titleOf( option ).toLowerCase().includes( query );
		}

		if ( active?.hidden ) {
			setActive( null );
		}
	}

	function move( step ) {
		const items = visibleOptions();
		const index = items.indexOf( active );
		const next =
			index === -1
				? items[ step > 0 ? 0 : items.length - 1 ]
				: items[ ( index + step + items.length ) % items.length ];

		open();
		setActive( next || null );
	}

	function onKeydown( event ) {
		switch ( event.key ) {
			case 'ArrowDown':
				event.preventDefault();
				move( 1 );
				break;
			case 'ArrowUp':
				event.preventDefault();
				move( -1 );
				break;
			case 'Enter':
				if ( active && ! listbox.hidden ) {
					event.preventDefault();
					toggle( active.dataset.value );
				}
				break;
			case 'Escape':
				if ( ! listbox.hidden ) {
					event.preventDefault();
					close();
				}
				break;
			case 'Backspace':
				if ( input.value === '' && selected.size > 0 ) {
					const last = options
						.filter( ( option ) =>
							selected.has( option.dataset.value )
						)
						.pop();
					toggle( last.dataset.value );
				}
				break;
		}
	}

	input.addEventListener( 'keydown', onKeydown );
	input.addEventListener( 'input', () => {
		search();
		open();
	} );
	input.addEventListener( 'focus', open );

	control.addEventListener( 'click', ( event ) => {
		const remove = event.target.closest(
			'.course-glossary-filter__remove'
		);

		if ( remove ) {
			toggle( remove.dataset.value );
			input.focus();
			return;
		}

		input.focus();
		open();
	} );

	// Keep focus in the input, so the list stays open across selections.
	listbox.addEventListener( 'mousedown', ( event ) =>
		event.preventDefault()
	);
	listbox.addEventListener( 'click', ( event ) => {
		const option = event.target.closest( '[role="option"]' );

		if ( option ) {
			setActive( option );
			toggle( option.dataset.value );
		}
	} );

	document.addEventListener( 'pointerdown', ( event ) => {
		if ( ! filter.contains( event.target ) ) {
			close();
		}
	} );
	filter.addEventListener( 'focusout', ( event ) => {
		if ( ! filter.contains( event.relatedTarget ) ) {
			close();
		}
	} );

	filter.hidden = false;
	apply();
}

function init() {
	document
		.querySelectorAll( '[data-course-glossary-filter]' )
		.forEach( initFilter );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
