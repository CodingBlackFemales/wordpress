/**
 * The "Glossary term" rich-text format.
 *
 * Stores only the entry ID on the selected text:
 * `<span class="glossary-ref" data-glossary-id="42">text</span>`. Everything
 * else (the anchor, whether it renders as an abbreviation, whether it is the
 * first mention) is resolved when the post is rendered, so editing an entry
 * updates every post that references it.
 */

/**
 * WordPress dependencies
 */
import {
	RichTextShortcut,
	RichTextToolbarButton,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import {
	Button,
	CheckboxControl,
	Icon,
	Popover,
	Spinner,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	RawHTML,
	createInterpolateElement,
	useEffect,
	useState,
} from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import {
	caution,
	check,
	closeSmall,
	info,
	pencil,
	postList,
} from '@wordpress/icons';
import {
	applyFormat,
	create,
	getTextContent,
	insert,
	isCollapsed,
	registerFormatType,
	removeFormat,
	slice,
	useAnchor,
} from '@wordpress/rich-text';

/**
 * Internal dependencies
 */
import config, { FORMAT_NAME, REF_CLASS } from './config';
import { glossaryIcon } from './icons';
import { matchForm } from './matching';
import { collectReferences } from './references';
import { store } from './store';
import TermForm from './term-form';
import TermSearch, { NoCreateNotice } from './term-search';

const title = __( 'Glossary term', 'cbf-semantic-glossary' );

const settings = {
	title,
	tagName: 'span',
	className: REF_CLASS,
	attributes: {
		id: 'data-glossary-id',
		abbr: 'data-glossary-abbr',
	},
	edit: Edit,
};

/**
 * The extent of the reference around the caret.
 *
 * @param {Object} value Rich-text value.
 * @return {{start:number,end:number,id:string,abbr:boolean}|null} Boundary, or null outside a reference.
 */
function findBoundary( value ) {
	const { formats, start } = value;
	const at = formats[ start ]?.some( ( f ) => f.type === FORMAT_NAME )
		? start
		: start - 1;
	const format = formats[ at ]?.find( ( f ) => f.type === FORMAT_NAME );

	if ( ! format ) {
		return null;
	}

	const id = format.attributes?.id;
	const same = ( index ) =>
		formats[ index ]?.some(
			( f ) => f.type === FORMAT_NAME && f.attributes?.id === id
		);

	let from = at;
	let to = at;
	while ( from > 0 && same( from - 1 ) ) {
		from--;
	}
	while ( to < formats.length && same( to ) ) {
		to++;
	}

	return {
		start: from,
		end: to,
		id,
		abbr: format.attributes?.abbr === 'true',
	};
}

/**
 * Whether the same entry is referenced earlier in this rich-text value.
 *
 * @param {Object} value    Rich-text value.
 * @param {Object} boundary Current reference.
 * @return {boolean} Whether an earlier reference exists.
 */
function hasEarlierInValue( value, boundary ) {
	return value.formats
		.slice( 0, boundary.start )
		.some( ( formats ) =>
			formats?.some(
				( f ) =>
					f.type === FORMAT_NAME && f.attributes?.id === boundary.id
			)
		);
}

/**
 * The format's toolbar button, shortcut and popover.
 *
 * @param {Object}   props            Format edit props.
 * @param {boolean}  props.isActive   Whether the caret or selection is inside a reference.
 * @param {Object}   props.value      Rich-text value.
 * @param {Function} props.onChange   Replace the rich-text value.
 * @param {Function} props.onFocus    Return focus to the rich text.
 * @param {Object}   props.contentRef Ref to the editable element.
 */
function Edit( { isActive, value, onChange, onFocus, contentRef } ) {
	const [ mode, setMode ] = useState( null );
	const [ dismissed, setDismissed ] = useState( null );
	const boundary = isActive ? findBoundary( value ) : null;
	const boundaryKey = boundary
		? `${ boundary.start }:${ boundary.id }`
		: null;

	// Reopen for a different reference; stay closed for a dismissed one.
	useEffect( () => {
		if ( boundaryKey !== dismissed ) {
			setDismissed( null );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ boundaryKey ] );

	const showExisting = boundary && mode === null && dismissed !== boundaryKey;
	const isOpen = mode !== null || showExisting;

	const anchor = useAnchor( {
		editableContentElement: contentRef.current,
		settings: { ...settings, isActive },
	} );

	const open = () => setMode( boundary ? 'existing' : 'search' );
	const close = () => {
		setMode( null );
		setDismissed( boundaryKey );
	};

	const selectedText = boundary
		? getTextContent( slice( value, boundary.start, boundary.end ) )
		: getTextContent( slice( value ) ).trim();

	/**
	 * Reference an entry from the selection, the current reference, or (with
	 * nothing selected) a newly inserted copy of its term.
	 *
	 * @param {Object}  entry      Entry.
	 * @param {boolean} renderAbbr Force the abbreviation shape.
	 */
	const apply = ( entry, renderAbbr = false ) => {
		const format = {
			type: FORMAT_NAME,
			attributes: {
				id: String( entry.id ),
				...( renderAbbr ? { abbr: 'true' } : {} ),
			},
		};

		if ( boundary ) {
			onChange(
				applyFormat( value, format, boundary.start, boundary.end )
			);
		} else if ( isCollapsed( value ) ) {
			const inserted = insert( value, create( { text: entry.term } ) );
			onChange(
				applyFormat(
					inserted,
					format,
					value.start,
					value.start + entry.term.length
				)
			);
		} else {
			onChange( applyFormat( value, format ) );
		}

		setMode( null );
		onFocus();
	};

	const unmark = () => {
		onChange(
			removeFormat( value, FORMAT_NAME, boundary.start, boundary.end )
		);
		setMode( null );
		onFocus();
	};

	return (
		<>
			<RichTextShortcut
				type="primaryShift"
				character="g"
				onUse={ open }
			/>
			{ /* The "link" slot sits outside the "More" dropdown, so the button
			     appears beside Link rather than hidden in the overflow menu. */ }
			<RichTextToolbarButton
				name="link"
				icon={ glossaryIcon }
				title={ title }
				onClick={ open }
				isActive={ isActive }
				shortcutType="primaryShift"
				shortcutCharacter="g"
			/>
			{ isOpen && (
				<Popover
					anchor={ anchor }
					placement="bottom-start"
					offset={ 8 }
					focusOnMount={ mode === null ? false : 'firstElement' }
					onClose={ close }
					shift
					className="cbf-glossary-popover"
				>
					{ ( mode === 'search' ||
						( mode === null && ! boundary ) ) && (
						<div className="cbf-glossary-popover__section">
							<TermSearch
								label={ title }
								initialQuery={ selectedText }
								onSelect={ ( entry ) => apply( entry ) }
								onCreate={
									config.canCreate
										? () => setMode( 'create' )
										: undefined
								}
							/>
							{ ! config.canCreate && <NoCreateNotice /> }
						</div>
					) }
					{ mode === 'create' && (
						<TermForm
							selectedText={ selectedText }
							onBack={ () => setMode( 'search' ) }
							onCancel={ close }
							onCreated={ ( entry ) => apply( entry ) }
						/>
					) }
					{ boundary && ( mode === null || mode === 'existing' ) && (
						<ExistingReference
							boundary={ boundary }
							value={ value }
							text={ selectedText }
							onToggleAbbr={ ( entry, checked ) =>
								apply( entry, checked )
							}
							onChange={ () => setMode( 'search' ) }
							onUnmark={ unmark }
						/>
					) }
				</Popover>
			) }
		</>
	);
}

/**
 * The popover for a marked term: what it is, how it will render, and actions.
 *
 * @param {Object}   props
 * @param {Object}   props.boundary     Current reference.
 * @param {Object}   props.value        Rich-text value.
 * @param {string}   props.text         Referenced text.
 * @param {Function} props.onToggleAbbr Called with (entry, checked).
 * @param {Function} props.onChange     Switch to search ("Change term").
 * @param {Function} props.onUnmark     Remove the reference.
 */
function ExistingReference( {
	boundary,
	value,
	text,
	onToggleAbbr,
	onChange,
	onUnmark,
} ) {
	const id = parseInt( boundary.id, 10 );
	const { entry, missing, isFirst } = useSelect(
		( select ) => {
			const editor = select( blockEditorStore );
			const clientId = editor.getSelectedBlockClientId();
			const firstRef = collectReferences( editor.getBlocks() ).find(
				( ref ) => ref.entryId === id
			);

			return {
				entry: select( store ).getEntry( id ),
				missing: select( store ).isMissing( id ),
				isFirst:
					( ! firstRef || firstRef.clientId === clientId ) &&
					! hasEarlierInValue( value, boundary ),
			};
		},
		[ id, value, boundary ]
	);
	const { refreshEntries } = useDispatch( store );

	// The entry may have been edited in another tab since it was loaded.
	useEffect( () => {
		refreshEntries( [ id ] );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ id ] );

	if ( missing ) {
		return (
			<div className="cbf-glossary-popover__section">
				<p className="cbf-glossary-status is-warning">
					<Icon icon={ caution } size={ 18 } />
					{ __(
						'This entry has been deleted, so the text renders as plain text.',
						'cbf-semantic-glossary'
					) }
				</p>
				<ReferenceActions onChange={ onChange } onUnmark={ onUnmark } />
			</div>
		);
	}

	if ( ! entry ) {
		return (
			<div className="cbf-glossary-popover__section">
				<Spinner />
			</div>
		);
	}

	const match = matchForm( text, entry.forms );
	const isAbbr = match?.type === 'abbr';
	const plainLinkHelp = match
		? __(
				'Matches a term, so it renders as a plain link unless this is on.',
				'cbf-semantic-glossary'
			)
		: __(
				'Matches no form exactly, so it renders as a plain link unless this is on.',
				'cbf-semantic-glossary'
			);
	const shape =
		isAbbr || boundary.abbr
			? `<a href="#${ entry.anchor }"><abbr>`
			: `<a href="#${ entry.anchor }">`;

	return (
		<div className="cbf-glossary-existing">
			<div className="cbf-glossary-popover__section">
				<div className="cbf-glossary-existing__entry">
					<Icon icon={ glossaryIcon } size={ 20 } />
					<div>
						<div className="cbf-glossary-existing__name">
							{ entry.term }
							{ entry.abbr && ` (${ entry.abbr })` }
						</div>
						<RawHTML className="cbf-glossary-existing__definition">
							{ entry.definition }
						</RawHTML>
					</div>
				</div>

				{ entry.status !== 'publish' && (
					<p className="cbf-glossary-status is-unpublished">
						<Icon icon={ info } size={ 16 } />
						{ __(
							'Not published yet: renders as plain text until this term is published.',
							'cbf-semantic-glossary'
						) }
					</p>
				) }
				{ entry.status === 'publish' && isFirst && (
					<p className="cbf-glossary-status is-success">
						<Icon icon={ check } size={ 16 } />
						<span>
							{ createInterpolateElement(
								__(
									'First mention in this post · renders as <code/>',
									'cbf-semantic-glossary'
								),
								{ code: <code>{ shape }</code> }
							) }
						</span>
					</p>
				) }
				{ ! isFirst && (
					<p className="cbf-glossary-status is-warning">
						<Icon icon={ caution } size={ 16 } />
						{ __(
							'Not the first mention in this post, so it renders as plain text.',
							'cbf-semantic-glossary'
						) }
					</p>
				) }

				<CheckboxControl
					__nextHasNoMarginBottom
					label={ __(
						'Render as abbreviation',
						'cbf-semantic-glossary'
					) }
					help={
						isAbbr
							? sprintf(
									/* translators: %s: the expansion */
									__(
										'Matches an abbreviation; expands to “%s”.',
										'cbf-semantic-glossary'
									),
									entry.forms[ match.form ].term
								)
							: plainLinkHelp
					}
					checked={ isAbbr || boundary.abbr }
					disabled={ isAbbr }
					onChange={ ( checked ) => onToggleAbbr( entry, checked ) }
				/>
			</div>

			<ReferenceActions
				editLink={ entry.edit_link }
				onChange={ onChange }
				onUnmark={ onUnmark }
			/>
		</div>
	);
}

/**
 * Edit entry / Change term / Unmark.
 *
 * "Unmark" removes this reference only; the entry and other posts are untouched.
 *
 * @param {Object}   props
 * @param {string}   [props.editLink] Entry edit screen URL, when the user may edit it.
 * @param {Function} props.onChange   Switch to search.
 * @param {Function} props.onUnmark   Remove the reference.
 */
function ReferenceActions( { editLink, onChange, onUnmark } ) {
	return (
		<div className="cbf-glossary-actions">
			{ editLink && (
				<Button
					icon={ pencil }
					href={ editLink }
					target="_blank"
					rel="noreferrer"
				>
					{ __( 'Edit entry', 'cbf-semantic-glossary' ) }
				</Button>
			) }
			<Button icon={ postList } onClick={ onChange }>
				{ __( 'Change term', 'cbf-semantic-glossary' ) }
			</Button>
			<Button icon={ closeSmall } isDestructive onClick={ onUnmark }>
				{ __( 'Unmark', 'cbf-semantic-glossary' ) }
			</Button>
		</div>
	);
}

registerFormatType( FORMAT_NAME, settings );
