/**
 * WordPress dependencies
 */
import { Icon, Spinner } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { useDispatch } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';
import { plus, search as searchIcon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { glossaryIcon } from './icons';
import { plainText } from './matching';
import { searchEntries, store } from './store';

/**
 * Every name of an entry other than its canonical term.
 *
 * @param {Object} entry Entry.
 * @return {string[]} Names.
 */
function otherNames( entry ) {
	return entry.forms
		.flatMap( ( form, index ) =>
			index === 0 ? [ form.abbr ] : [ form.term, form.abbr ]
		)
		.filter( Boolean );
}

/**
 * Search-as-you-type over every form of every entry.
 *
 * A combobox: arrow keys move through the results (and the "Create" action
 * pinned at the bottom), Enter chooses.
 *
 * @param {Object}   props
 * @param {string}   props.initialQuery Prefilled query, usually the selected text.
 * @param {string}   props.label        Field label.
 * @param {Function} props.onSelect     Called with the chosen entry.
 * @param {Function} [props.onCreate]   Called with the query; omit to hide "Create".
 * @param {number[]} [props.exclude]    Entry IDs not to offer.
 */
export default function TermSearch( {
	initialQuery = '',
	label,
	onSelect,
	onCreate,
	exclude = [],
} ) {
	const [ query, setQuery ] = useState( initialQuery );
	const [ results, setResults ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ active, setActive ] = useState( 0 );
	const { receiveEntries } = useDispatch( store );
	const id = useInstanceId( TermSearch, 'cbf-glossary-search' );

	useEffect( () => {
		let cancelled = false;
		setLoading( true );

		const timer = window.setTimeout( () => {
			searchEntries( query )
				.then( ( entries ) => {
					if ( cancelled ) {
						return;
					}
					receiveEntries( entries );
					setResults(
						entries.filter(
							( entry ) => ! exclude.includes( entry.id )
						)
					);
					setActive( 0 );
				} )
				.catch( () => ! cancelled && setResults( [] ) )
				.finally( () => ! cancelled && setLoading( false ) );
		}, 200 );

		return () => {
			cancelled = true;
			window.clearTimeout( timer );
		};
		// eslint-disable-next-line react-hooks/exhaustive-deps -- exclude is read once per query.
	}, [ query ] );

	const showCreate = !! onCreate && query.trim() !== '';
	const count = results.length + ( showCreate ? 1 : 0 );

	const choose = ( index ) => {
		if ( index < results.length ) {
			onSelect( results[ index ] );
		} else if ( showCreate ) {
			onCreate( query.trim() );
		}
	};

	const onKeyDown = ( event ) => {
		if ( ! count ) {
			return;
		}
		if ( event.key === 'ArrowDown' || event.key === 'ArrowUp' ) {
			event.preventDefault();
			const step = event.key === 'ArrowDown' ? 1 : -1;
			setActive( ( current ) => ( current + step + count ) % count );
		} else if ( event.key === 'Enter' ) {
			event.preventDefault();
			choose( active );
		}
	};

	return (
		<div className="cbf-glossary-search">
			<label htmlFor={ `${ id }-input` } className="cbf-glossary-label">
				{ label }
			</label>
			<div className="cbf-glossary-search__field">
				<Icon icon={ searchIcon } size={ 20 } />
				<input
					id={ `${ id }-input` }
					type="text"
					role="combobox"
					aria-autocomplete="list"
					aria-expanded={ count > 0 }
					aria-controls={ `${ id }-listbox` }
					aria-activedescendant={
						count > 0 ? `${ id }-option-${ active }` : undefined
					}
					value={ query }
					onChange={ ( event ) => setQuery( event.target.value ) }
					onKeyDown={ onKeyDown }
					autoComplete="off"
					// eslint-disable-next-line jsx-a11y/no-autofocus -- the popover exists to take this input.
					autoFocus
				/>
				{ loading && <Spinner /> }
			</div>

			<ul
				id={ `${ id }-listbox` }
				role="listbox"
				aria-label={ __( 'Matching terms', 'cbf-semantic-glossary' ) }
				className="cbf-glossary-search__results"
			>
				{ results.map( ( entry, index ) => (
					// The input owns keyboard interaction (aria-activedescendant); options are clickable too.
					// eslint-disable-next-line jsx-a11y/click-events-have-key-events
					<li
						key={ entry.id }
						id={ `${ id }-option-${ index }` }
						role="option"
						aria-selected={ index === active }
						className="cbf-glossary-search__result"
						onMouseDown={ ( event ) => event.preventDefault() }
						onMouseEnter={ () => setActive( index ) }
						onClick={ () => choose( index ) }
					>
						<Icon icon={ glossaryIcon } size={ 20 } />
						<span className="cbf-glossary-search__body">
							<span className="cbf-glossary-search__name">
								{ entry.term }
								{ entry.status !== 'publish' && (
									<span className="cbf-glossary-tag">
										{ __(
											'not published',
											'cbf-semantic-glossary'
										) }
									</span>
								) }
								{ otherNames( entry ).length > 0 && (
									<span className="cbf-glossary-muted">
										{ ' · ' }
										{ sprintf(
											/* translators: %s: other names for the term */
											__(
												'also: %s',
												'cbf-semantic-glossary'
											),
											otherNames( entry ).join( ', ' )
										) }
									</span>
								) }
							</span>
							<span className="cbf-glossary-search__definition">
								{ plainText( entry.definition ) }
							</span>
						</span>
						<span className="cbf-glossary-search__usage">
							{ sprintf(
								/* translators: %d: number of posts */
								_n(
									'%d post',
									'%d posts',
									entry.usage,
									'cbf-semantic-glossary'
								),
								entry.usage
							) }
						</span>
					</li>
				) ) }
				{ showCreate && (
					// eslint-disable-next-line jsx-a11y/click-events-have-key-events
					<li
						id={ `${ id }-option-${ results.length }` }
						role="option"
						aria-selected={ active === results.length }
						className="cbf-glossary-search__create"
						onMouseDown={ ( event ) => event.preventDefault() }
						onMouseEnter={ () => setActive( results.length ) }
						onClick={ () => choose( results.length ) }
					>
						<Icon icon={ plus } size={ 20 } />
						{ sprintf(
							/* translators: %s: search text */
							__(
								'Create new term “%s”',
								'cbf-semantic-glossary'
							),
							query.trim()
						) }
					</li>
				) }
			</ul>

			{ ! loading && ! results.length && ! showCreate && (
				<p className="cbf-glossary-search__empty">
					{ __( 'No matching terms.', 'cbf-semantic-glossary' ) }
				</p>
			) }
		</div>
	);
}

/**
 * Shown in place of "Create" to users who cannot create entries.
 */
export function NoCreateNotice() {
	return (
		<p className="cbf-glossary-muted cbf-glossary-search__note">
			{ __(
				'Only Editors can add new terms to the glossary.',
				'cbf-semantic-glossary'
			) }
		</p>
	);
}
