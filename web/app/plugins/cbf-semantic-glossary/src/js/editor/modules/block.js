/**
 * The Glossary block's editor UI.
 *
 * Read-only apart from its heading: the list is built from the post's
 * references and rendered by PHP on the front end. This preview mirrors the
 * front-end markup closely enough for the front-end stylesheet to apply.
 */

/**
 * WordPress dependencies
 */
import {
	InspectorControls,
	RichText,
	store as blockEditorStore,
	useBlockProps,
} from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import * as components from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { RawHTML, useMemo } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';
import { info } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import config, { BLOCK_NAME } from './config';
import { glossaryIcon } from './icons';
import { displayTerm, indexLetter, sortEntries } from './matching';
import { collectReferences } from './references';
import { store } from './store';

const { Icon, PanelBody, RangeControl, Spinner, TextControl, ToggleControl } =
	components;

// WordPress 7.1 still ships these only under their experimental names; prefer
// the stable export once it exists.
const ToggleGroupControl =
	components.ToggleGroupControl ||
	components.__experimentalToggleGroupControl;
const ToggleGroupControlOption =
	components.ToggleGroupControlOption ||
	components.__experimentalToggleGroupControlOption;

/**
 * Entries the post's glossary will list, sorted as on the front end.
 */
function useGlossaryEntries() {
	const { ids, inline } = useSelect( ( select ) => {
		const references = collectReferences(
			select( blockEditorStore ).getBlocks()
		);
		const inlineIds = [
			...new Set( references.map( ( ref ) => ref.entryId ) ),
		].filter( Boolean );
		const extra =
			select( editorStore ).getEditedPostAttribute( 'glossary' )?.extra ||
			[];

		return {
			ids: [ ...new Set( [ ...inlineIds, ...extra ] ) ].join(),
			inline: inlineIds.join(),
		};
	}, [] );

	return useSelect(
		( select ) => {
			const list = ids ? ids.split( ',' ).map( Number ) : [];
			const loaded = list.map( ( id ) => select( store ).getEntry( id ) );
			const loading = list.some(
				( id, index ) =>
					! loaded[ index ] && ! select( store ).isMissing( id )
			);

			return {
				loading,
				inlineIds: inline ? inline.split( ',' ).map( Number ) : [],
				entries: sortEntries(
					loaded.filter( ( entry ) => entry?.status === 'publish' )
				),
			};
		},
		[ ids, inline ]
	);
}

function Edit( { attributes, setAttributes } ) {
	const {
		heading,
		level,
		showAlternatives,
		backLinks,
		showIndex,
		indexMinEntries,
	} = attributes;
	const indexMin = indexMinEntries ?? config.indexMinEntries;
	const { entries, inlineIds, loading } = useGlossaryEntries();
	const HeadingTag = `h${ level }`;
	const indexed = showIndex && entries.length >= indexMin;
	const letters = useMemo(
		() => [
			...new Set(
				entries.map( ( e ) => indexLetter( displayTerm( e ) ) )
			),
		],
		[ entries ]
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'cbf-semantic-glossary' ) }>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Heading text', 'cbf-semantic-glossary' ) }
						value={ heading }
						onChange={ ( value ) =>
							setAttributes( { heading: value } )
						}
					/>
					<ToggleGroupControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						isBlock
						label={ __( 'Heading level', 'cbf-semantic-glossary' ) }
						help={ __(
							'Pick the level that keeps the page outline valid: one below the heading this glossary sits under.',
							'cbf-semantic-glossary'
						) }
						value={ level }
						onChange={ ( value ) =>
							setAttributes( { level: Number( value ) } )
						}
					>
						{ [ 2, 3, 4 ].map( ( option ) => (
							<ToggleGroupControlOption
								key={ option }
								value={ option }
								label={ `H${ option }` }
							/>
						) ) }
					</ToggleGroupControl>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Show alternative terms',
							'cbf-semantic-glossary'
						) }
						checked={ showAlternatives }
						onChange={ ( value ) =>
							setAttributes( { showAlternatives: value } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Back-links to first mention',
							'cbf-semantic-glossary'
						) }
						checked={ backLinks }
						onChange={ ( value ) =>
							setAttributes( { backLinks: value } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Show A–Z index',
							'cbf-semantic-glossary'
						) }
						help={ sprintf(
							/* translators: %d: minimum number of entries */
							__(
								'Shown once the glossary has at least %d entries.',
								'cbf-semantic-glossary'
							),
							indexMin
						) }
						checked={ showIndex }
						onChange={ ( value ) =>
							setAttributes( { showIndex: value } )
						}
					/>
					{ showIndex && (
						<RangeControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __(
								'Minimum entries for the index',
								'cbf-semantic-glossary'
							) }
							help={
								indexMinEntries === undefined
									? sprintf(
											/* translators: %d: the site-wide default */
											__(
												'Using the site default (%d), set in Glossary › Settings.',
												'cbf-semantic-glossary'
											),
											config.indexMinEntries
										)
									: __(
											'Reset to use the site default.',
											'cbf-semantic-glossary'
										)
							}
							value={ indexMinEntries }
							initialPosition={ config.indexMinEntries }
							min={ 1 }
							max={ 50 }
							allowReset
							resetFallbackValue={ undefined }
							onChange={ ( value ) =>
								setAttributes( { indexMinEntries: value } )
							}
						/>
					) }
				</PanelBody>
				{ config.autoAppend && (
					<p className="cbf-glossary-inspector-note">
						{ __(
							'Site setting: posts that reference terms but have no Glossary block get one appended automatically.',
							'cbf-semantic-glossary'
						) }
					</p>
				) }
			</InspectorControls>

			<div { ...useBlockProps( { className: 'cbf-glossary-block' } ) }>
				<div className="cbf-glossary-block__label">
					<Icon icon={ glossaryIcon } size={ 16 } />
					{ sprintf(
						/* translators: %d: number of terms */
						_n(
							'Auto-populated · %d term',
							'Auto-populated · %d terms',
							entries.length,
							'cbf-semantic-glossary'
						),
						entries.length
					) }
				</div>

				<section className="glossary-section">
					<RichText
						tagName={ HeadingTag }
						className="glossary-heading"
						value={ heading }
						allowedFormats={ [] }
						withoutInteractiveFormatting
						placeholder={ __(
							'Glossary',
							'cbf-semantic-glossary'
						) }
						aria-label={ __(
							'Glossary heading',
							'cbf-semantic-glossary'
						) }
						onChange={ ( value ) =>
							setAttributes( { heading: value } )
						}
					/>

					{ indexed && (
						<nav
							className="glossary-index"
							aria-label={ __(
								'Glossary index',
								'cbf-semantic-glossary'
							) }
						>
							<ol>
								{ letters.map( ( letter ) => (
									<li key={ letter }>
										<span>{ letter }</span>
									</li>
								) ) }
							</ol>
						</nav>
					) }

					{ loading && <Spinner /> }

					{ ! loading && entries.length === 0 && (
						<p className="cbf-glossary-block__empty">
							{ __(
								'No terms referenced yet. Select some text and use the Glossary term button (⌘⇧G); the term appears here.',
								'cbf-semantic-glossary'
							) }
						</p>
					) }

					{ entries.length > 0 && (
						<dl className="glossary">
							{ entries.map( ( entry ) => (
								<PreviewEntry
									key={ entry.id }
									entry={ entry }
									showAlternatives={ showAlternatives }
									backLink={
										backLinks &&
										inlineIds.includes( entry.id )
									}
								/>
							) ) }
						</dl>
					) }
				</section>

				<p className="cbf-glossary-block__note">
					<Icon icon={ info } size={ 16 } />
					{ __(
						'Definitions are edited from the entry, not here. Only the heading text is editable in place.',
						'cbf-semantic-glossary'
					) }
				</p>
			</div>
		</>
	);
}

/**
 * One entry, as GlossaryRenderer outputs it.
 *
 * @param {Object}  props
 * @param {Object}  props.entry            Entry.
 * @param {boolean} props.showAlternatives Whether to list alternative forms.
 * @param {boolean} props.backLink         Whether to show the back-link.
 */
function PreviewEntry( { entry, showAlternatives, backLink } ) {
	const [ primary, ...alternatives ] = entry.forms;

	return (
		<div className="glossary-entry">
			<dt>
				<dfn>
					<FormName form={ primary } expand={ false } />
				</dfn>
				{ primary.abbr && ` (${ primary.term })` }
				{ backLink && (
					<span className="glossary-backlink" aria-hidden="true">
						{ ' ↩\uFE0E' }
					</span>
				) }
			</dt>
			{ showAlternatives &&
				alternatives.map( ( form, index ) => (
					<dt className="glossary-alt" key={ index }>
						<FormName form={ form } expand />
					</dt>
				) ) }
			<dd>
				<RawHTML>{ layoutDefinition( entry.definition ) }</RawHTML>
			</dd>
		</div>
	);
}

/**
 * Lay a definition out as Definition::layout() does on the front end: a lone
 * paragraph is unwrapped.
 *
 * @param {string} html Definition HTML.
 * @return {string} HTML.
 */
function layoutDefinition( html = '' ) {
	const out = html.trim();
	const lone = out.match( /^<p>([\s\S]*)<\/p>$/ );

	return lone && ( out.match( /<p>/g ) || [] ).length === 1
		? lone[ 1 ].trim()
		: out;
}

/**
 * A form's name: the abbreviation leads when there is one.
 *
 * @param {Object}  props
 * @param {Object}  props.form   Form.
 * @param {boolean} props.expand Whether to follow an abbreviation with its term.
 */
function FormName( { form, expand } ) {
	if ( ! form.abbr ) {
		return form.term;
	}

	return (
		<>
			<abbr title={ form.term }>{ form.abbr }</abbr>
			{ expand && ` (${ form.term })` }
		</>
	);
}

registerBlockType( BLOCK_NAME, {
	icon: glossaryIcon,
	edit: Edit,
	save: () => null,
} );
