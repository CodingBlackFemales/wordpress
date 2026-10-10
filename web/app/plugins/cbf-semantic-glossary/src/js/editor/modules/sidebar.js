/**
 * The "Glossary" document sidebar.
 */

/**
 * WordPress dependencies
 */
import { store as blockEditorStore } from '@wordpress/block-editor';
import { createBlock, getBlockType } from '@wordpress/blocks';
import { Button, Icon, PanelBody } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	PluginSidebar,
	PluginSidebarMoreMenuItem,
	store as editorStore,
} from '@wordpress/editor';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';
import {
	check,
	chevronRight,
	closeSmall,
	info,
	plus,
	caution,
} from '@wordpress/icons';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import config, { BLOCK_NAME } from './config';
import { glossaryIcon } from './icons';
import {
	collectReferences,
	groupByEntry,
	markFirstMention,
	showBlock,
	unwrapReferences,
	walkBlocks,
} from './references';
import { auditContent, store } from './store';
import TermSearch from './term-search';
import TermForm from './term-form';

const SIDEBAR = 'cbf-glossary';
const NO_FIELD = { extra: [], ignored: [] };

/**
 * Post-level glossary data: entries listed without an inline reference, and
 * entries not to suggest. Saved with the post as the `glossary` REST field.
 */
function usePostField() {
	const field = useSelect(
		( select ) =>
			select( editorStore ).getEditedPostAttribute( 'glossary' ) ||
			NO_FIELD,
		[]
	);
	const { editPost } = useDispatch( editorStore );

	return [
		{ extra: field.extra || [], ignored: field.ignored || [] },
		( changes ) => editPost( { glossary: { ...field, ...changes } } ),
	];
}

/**
 * Unmarked known terms, from the server's audit of the unsaved content.
 *
 * @param {Object[]} blocks  Current blocks (the audit reruns when they change).
 * @param {number[]} ignored Entries not to report.
 */
function useUnmarkedTerms( blocks, ignored ) {
	const [ findings, setFindings ] = useState( [] );
	const getContent = useSelect(
		( select ) => select( editorStore ).getEditedPostContent,
		[]
	);
	const { receiveEntries } = useDispatch( store );

	const ignoredKey = ignored.join();

	// A plain timeout rather than useDebounce: the sidebar re-renders whenever
	// entries load, and a debounced function rebuilt per render is cancelled
	// before it ever fires.
	useEffect( () => {
		let cancelled = false;
		const timer = window.setTimeout( () => {
			auditContent(
				getContent(),
				ignoredKey ? ignoredKey.split( ',' ).map( Number ) : []
			)
				.then( ( result ) => {
					if ( cancelled ) {
						return;
					}
					receiveEntries( result.entries );
					setFindings(
						result.findings.filter(
							( finding ) => finding.type === 'unmarked'
						)
					);
				} )
				.catch( () => ! cancelled && setFindings( [] ) );
		}, 1500 );

		return () => {
			cancelled = true;
			window.clearTimeout( timer );
		};
	}, [ blocks, ignoredKey, getContent, receiveEntries ] );

	return findings;
}

/**
 * Entries by ID, loading any not yet fetched.
 *
 * @param {number[]} ids Entry IDs.
 */
function useEntries( ids ) {
	const key = ids.join();

	return useSelect(
		( select ) =>
			( key ? key.split( ',' ).map( Number ) : [] ).map( ( id ) => ( {
				id,
				entry: select( store ).getEntry( id ),
				missing: select( store ).isMissing( id ),
			} ) ),
		[ key ]
	);
}

/**
 * A name to show for an entry: its term, or a placeholder while loading.
 *
 * @param {Object|undefined} entry   Entry.
 * @param {boolean}          missing Whether it is deleted.
 */
function entryName( entry, missing ) {
	if ( missing ) {
		return __( '(deleted term)', 'cbf-semantic-glossary' );
	}
	return entry ? entry.term : '…';
}

function GlossarySidebar() {
	const blocks = useSelect(
		( select ) => select( blockEditorStore ).getBlocks(),
		[]
	);
	const { updateBlockAttributes, selectBlock, insertBlock } =
		useDispatch( blockEditorStore );
	const [ field, setField ] = usePostField();
	const [ adding, setAdding ] = useState( null );

	const references = useMemo( () => collectReferences( blocks ), [ blocks ] );
	const groups = useMemo( () => groupByEntry( references ), [ references ] );
	const inlineIds = [ ...groups.keys() ].filter( Boolean );
	const extraIds = field.extra.filter( ( id ) => ! groups.has( id ) );
	const entries = useEntries( [ ...inlineIds, ...extraIds ] );
	const byId = Object.fromEntries( entries.map( ( e ) => [ e.id, e ] ) );
	const unmarked = useUnmarkedTerms( blocks, field.ignored );

	const glossaryBlock = useMemo( () => {
		let found = null;
		walkBlocks( blocks, ( block ) => {
			found = found || ( block.name === BLOCK_NAME ? block : null );
		} );
		return found;
	}, [ blocks ] );

	const applyUpdates = ( updates ) =>
		updates.forEach( ( { clientId, attributes } ) =>
			updateBlockAttributes( clientId, attributes )
		);

	const duplicates = [ ...groups.entries() ].filter(
		( [ id, refs ] ) => refs.length > 1 && byId[ id ]?.entry
	);
	const dead = references.filter(
		( ref ) => ! ref.entryId || byId[ ref.entryId ]?.missing
	);
	// Unpublished terms (drafts in review) render as plain text until published. Nothing
	// for the author to fix, so they are listed but not counted.
	const unpublished = [ ...groups.entries() ]
		.filter(
			( [ id ] ) =>
				byId[ id ]?.entry && byId[ id ].entry.status !== 'publish'
		)
		.map( ( [ id, refs ] ) => ( {
			entry: byId[ id ].entry,
			ref: refs[ 0 ],
		} ) );
	const attentionCount = duplicates.length + dead.length + unmarked.length;

	return (
		<>
			<PanelBody
				title={
					<>
						{ __( 'Needs attention', 'cbf-semantic-glossary' ) }
						{ attentionCount > 0 && (
							<span className="cbf-glossary-badge">
								{ attentionCount }
							</span>
						) }
					</>
				}
				initialOpen
			>
				{ attentionCount === 0 && (
					<p className="cbf-glossary-muted">
						{ __( 'Nothing to fix.', 'cbf-semantic-glossary' ) }
					</p>
				) }

				{ duplicates.map( ( [ id, refs ] ) => (
					<Attention key={ `dup-${ id }` } tone="warning">
						<p>
							{ sprintf(
								/* translators: 1: term, 2: number of references */
								__(
									'%1$s is marked %2$d times. Only the first mention should link.',
									'cbf-semantic-glossary'
								),
								entryName( byId[ id ]?.entry ),
								refs.length
							) }
						</p>
						<div className="cbf-glossary-attention__actions">
							<Button
								variant="link"
								onClick={ () =>
									applyUpdates(
										unwrapReferences(
											blocks,
											refs.slice( 1 )
										)
									)
								}
							>
								{ _n(
									'Unmark later mention',
									'Unmark later mentions',
									refs.length - 1,
									'cbf-semantic-glossary'
								) }
							</Button>
							<Button
								variant="link"
								className="is-secondary"
								onClick={ () =>
									showBlock( selectBlock, refs[ 1 ].clientId )
								}
							>
								{ __( 'Show', 'cbf-semantic-glossary' ) }
							</Button>
						</div>
					</Attention>
				) ) }

				{ dead.map( ( ref ) => (
					<Attention key={ `dead-${ ref.position }` } tone="warning">
						<p>
							{ sprintf(
								/* translators: %s: referenced text */
								__(
									'“%s” refers to a deleted term, so it renders as plain text.',
									'cbf-semantic-glossary'
								),
								ref.text
							) }
						</p>
						<div className="cbf-glossary-attention__actions">
							<Button
								variant="link"
								onClick={ () =>
									applyUpdates(
										unwrapReferences( blocks, [ ref ] )
									)
								}
							>
								{ __( 'Unmark', 'cbf-semantic-glossary' ) }
							</Button>
							<Button
								variant="link"
								className="is-secondary"
								onClick={ () =>
									showBlock( selectBlock, ref.clientId )
								}
							>
								{ __( 'Show', 'cbf-semantic-glossary' ) }
							</Button>
						</div>
					</Attention>
				) ) }

				{ unpublished.map( ( { entry, ref } ) => (
					<Attention key={ `unpublished-${ entry.id }` } tone="info">
						<p>
							{ sprintf(
								/* translators: %s: term */
								__(
									'%s isn’t published yet, so it renders as plain text until it is.',
									'cbf-semantic-glossary'
								),
								entry.term
							) }
						</p>
						<div className="cbf-glossary-attention__actions">
							{ entry.edit_link && (
								<Button
									variant="link"
									href={ entry.edit_link }
									target="_blank"
									rel="noreferrer"
								>
									{ __(
										'Open term',
										'cbf-semantic-glossary'
									) }
								</Button>
							) }
							<Button
								variant="link"
								className="is-secondary"
								onClick={ () =>
									showBlock( selectBlock, ref.clientId )
								}
							>
								{ __( 'Show', 'cbf-semantic-glossary' ) }
							</Button>
						</div>
					</Attention>
				) ) }

				{ unmarked.map( ( finding ) => (
					<UnmarkedTerm
						key={ `unmarked-${ finding.entry_id }` }
						finding={ finding }
						onMark={ () => {
							const update = markFirstMention(
								blocks,
								finding.entry_id,
								finding.text
							);
							if ( update ) {
								applyUpdates( [ update ] );
								showBlock( selectBlock, update.clientId );
							}
						} }
						onIgnore={ () =>
							setField( {
								ignored: [ ...field.ignored, finding.entry_id ],
							} )
						}
					/>
				) ) }
			</PanelBody>

			<PanelBody
				title={ sprintf(
					/* translators: %d: number of terms */
					__( 'In this post · %d', 'cbf-semantic-glossary' ),
					inlineIds.length + extraIds.length
				) }
				initialOpen
			>
				<p className="cbf-glossary-muted">
					{ __( 'In document order.', 'cbf-semantic-glossary' ) }
				</p>
				<ol className="cbf-glossary-terms">
					{ inlineIds.map( ( id, index ) => (
						<InPostTerm
							key={ id }
							number={ index + 1 }
							item={ byId[ id ] }
							refs={ groups.get( id ) }
							onJump={ () =>
								showBlock(
									selectBlock,
									groups.get( id )[ 0 ].clientId
								)
							}
						/>
					) ) }
					{ extraIds.map( ( id ) => (
						<li
							key={ id }
							className="cbf-glossary-terms__item is-extra"
						>
							<span className="cbf-glossary-terms__number">
								–
							</span>
							<span className="cbf-glossary-terms__body">
								<span className="cbf-glossary-terms__name">
									{ entryName(
										byId[ id ]?.entry,
										byId[ id ]?.missing
									) }
									<span className="cbf-glossary-tag">
										{ __(
											'no inline reference',
											'cbf-semantic-glossary'
										) }
									</span>
								</span>
								<span className="cbf-glossary-muted">
									{ __(
										'Listed in glossary only',
										'cbf-semantic-glossary'
									) }
								</span>
							</span>
							<Button
								icon={ closeSmall }
								size="small"
								label={ __(
									'Remove from glossary',
									'cbf-semantic-glossary'
								) }
								onClick={ () =>
									setField( {
										extra: field.extra.filter(
											( each ) => each !== id
										),
									} )
								}
							/>
						</li>
					) ) }
				</ol>

				{ adding === null && (
					<Button
						variant="link"
						icon={ plus }
						onClick={ () => setAdding( 'search' ) }
					>
						{ __(
							'Add term without inline reference',
							'cbf-semantic-glossary'
						) }
					</Button>
				) }
				{ adding === 'search' && (
					<div className="cbf-glossary-add">
						<TermSearch
							label={ __(
								'Add to this post’s glossary',
								'cbf-semantic-glossary'
							) }
							exclude={ [ ...inlineIds, ...extraIds ] }
							onSelect={ ( entry ) => {
								setField( {
									extra: [ ...field.extra, entry.id ],
								} );
								setAdding( null );
							} }
							onCreate={
								config.canCreate
									? () => setAdding( 'create' )
									: undefined
							}
						/>
						<Button
							variant="tertiary"
							onClick={ () => setAdding( null ) }
						>
							{ __( 'Cancel', 'cbf-semantic-glossary' ) }
						</Button>
					</div>
				) }
				{ adding === 'create' && (
					<div className="cbf-glossary-add">
						<TermForm
							onBack={ () => setAdding( 'search' ) }
							onCancel={ () => setAdding( null ) }
							onCreated={ ( entry ) => {
								setField( {
									extra: [ ...field.extra, entry.id ],
								} );
								setAdding( null );
							} }
						/>
					</div>
				) }
			</PanelBody>

			<div className="cbf-glossary-sidebar__footer">
				{ glossaryBlock ? (
					<>
						<span className="cbf-glossary-status is-success">
							<Icon icon={ check } size={ 16 } />
							{ __(
								'Glossary block present',
								'cbf-semantic-glossary'
							) }
						</span>
						<Button
							variant="link"
							onClick={ () =>
								showBlock( selectBlock, glossaryBlock.clientId )
							}
						>
							{ __( 'Go to block', 'cbf-semantic-glossary' ) }
						</Button>
					</>
				) : (
					<>
						<span className="cbf-glossary-status">
							<Icon icon={ info } size={ 16 } />
							{ config.autoAppend
								? __(
										'No Glossary block; one is added at the end automatically.',
										'cbf-semantic-glossary'
									)
								: __(
										'No Glossary block in this post.',
										'cbf-semantic-glossary'
									) }
						</span>
						{ getBlockType( BLOCK_NAME ) && (
							<Button
								variant="link"
								onClick={ () =>
									insertBlock( createBlock( BLOCK_NAME ) )
								}
							>
								{ __(
									'Insert block',
									'cbf-semantic-glossary'
								) }
							</Button>
						) }
					</>
				) }
			</div>
		</>
	);
}

/**
 * One "Needs attention" item.
 *
 * @param {Object}  props
 * @param {string}  props.tone     "warning" or "info".
 * @param {Element} props.children Message and actions.
 */
function Attention( { tone, children } ) {
	return (
		<div className={ `cbf-glossary-attention is-${ tone }` }>
			<Icon icon={ tone === 'warning' ? caution : info } size={ 18 } />
			<div className="cbf-glossary-attention__body">{ children }</div>
		</div>
	);
}

/**
 * A known term that occurs in the post without being marked.
 *
 * @param {Object}   props
 * @param {Object}   props.finding  Audit finding.
 * @param {Function} props.onMark   Mark the first mention.
 * @param {Function} props.onIgnore Stop suggesting it for this post.
 */
function UnmarkedTerm( { finding, onMark, onIgnore } ) {
	const entry = useSelect(
		( select ) => select( store ).getEntry( finding.entry_id ),
		[ finding.entry_id ]
	);

	return (
		<Attention tone="info">
			<p>
				{ sprintf(
					/* translators: 1: term, 2: text found */
					__(
						'%1$s appears as “%2$s” but isn’t marked.',
						'cbf-semantic-glossary'
					),
					entryName( entry ),
					finding.text
				) }
			</p>
			<div className="cbf-glossary-attention__actions">
				<Button variant="link" onClick={ onMark }>
					{ __( 'Mark first mention', 'cbf-semantic-glossary' ) }
				</Button>
				<Button
					variant="link"
					className="is-secondary"
					onClick={ onIgnore }
				>
					{ __( 'Ignore in this post', 'cbf-semantic-glossary' ) }
				</Button>
			</div>
		</Attention>
	);
}

/**
 * One entry in "In this post".
 *
 * @param {Object}   props
 * @param {number}   props.number Position in document order.
 * @param {Object}   props.item   { entry, missing }.
 * @param {Array}    props.refs   The entry's references.
 * @param {Function} props.onJump Show the first reference.
 */
function InPostTerm( { number, item, refs, onJump } ) {
	const first = refs[ 0 ];
	const blockTitle =
		getBlockType( first.blockName )?.title || first.blockName;

	return (
		<li className="cbf-glossary-terms__item">
			<span className="cbf-glossary-terms__number">{ number }</span>
			<span className="cbf-glossary-terms__body">
				<span className="cbf-glossary-terms__name">
					{ entryName( item?.entry, item?.missing ) }
					{ item?.entry &&
						first.text.toLowerCase() !==
							item.entry.term.toLowerCase() && (
							<span className="cbf-glossary-muted">
								{ ' · ' }
								{ sprintf(
									/* translators: %s: the text marked */
									__( 'as “%s”', 'cbf-semantic-glossary' ),
									first.text
								) }
							</span>
						) }
				</span>
				<span className="cbf-glossary-muted">
					{ sprintf(
						/* translators: 1: block type, 2: block number */
						__( '%1$s · block %2$d', 'cbf-semantic-glossary' ),
						blockTitle,
						first.blockNumber
					) }
					{ refs.length > 1 &&
						' · ' +
							sprintf(
								/* translators: %d: number of references */
								__(
									'⚠ %d references',
									'cbf-semantic-glossary'
								),
								refs.length
							) }
				</span>
			</span>
			<Button
				icon={ chevronRight }
				size="small"
				label={ __( 'Jump to reference', 'cbf-semantic-glossary' ) }
				onClick={ onJump }
			/>
		</li>
	);
}

registerPlugin( 'cbf-glossary', {
	icon: glossaryIcon,
	render: () => (
		<>
			<PluginSidebarMoreMenuItem target={ SIDEBAR } icon={ glossaryIcon }>
				{ __( 'Glossary', 'cbf-semantic-glossary' ) }
			</PluginSidebarMoreMenuItem>
			<PluginSidebar
				name={ SIDEBAR }
				title={ __( 'Glossary', 'cbf-semantic-glossary' ) }
				icon={ glossaryIcon }
				className="cbf-glossary-sidebar"
			>
				<GlossarySidebar />
			</PluginSidebar>
		</>
	),
} );
