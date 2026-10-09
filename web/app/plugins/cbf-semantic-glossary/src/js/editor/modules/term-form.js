/**
 * WordPress dependencies
 */
import { Button, Icon, Notice } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { useDispatch } from '@wordpress/data';
import { createInterpolateElement, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { chevronLeft, closeSmall, info, plus } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { looksLikeAbbreviation, matchForm } from './matching';
import { store } from './store';

const EMPTY_FORM = { term: '', abbr: '' };

/**
 * How the selected text will render, in words.
 *
 * Editors never choose markup directly; this tells them what they will get.
 *
 * @param {string} text  Selected text.
 * @param {Array}  forms Forms as typed so far.
 * @return {Element|null} Notice content.
 */
function renderingNote( text, forms ) {
	if ( ! text ) {
		return null;
	}

	const match = matchForm( text, forms );

	if ( match?.type === 'abbr' ) {
		return createInterpolateElement(
			sprintf(
				/* translators: %s: the selected text */
				__(
					'Selected text “%s” matches an abbreviation, so it will render as <code/> linked to this entry.',
					'cbf-semantic-glossary'
				),
				text
			),
			{ code: <code>&lt;abbr&gt;</code> }
		);
	}

	if ( match?.type === 'term' ) {
		return sprintf(
			/* translators: %s: the selected text */
			__(
				'Selected text “%s” matches a term, so it will render as a link to this entry.',
				'cbf-semantic-glossary'
			),
			text
		);
	}

	return sprintf(
		/* translators: %s: the selected text */
		__(
			'Selected text “%s” matches no form exactly (an inflection, say), so it will render as a link to this entry.',
			'cbf-semantic-glossary'
		),
		text
	);
}

/**
 * Create a glossary entry from the editor.
 *
 * The definition is written in a small Markdown subset (`code`, **bold**,
 * *italic*, [links](url)), which the server converts; the toolbar buttons
 * insert the markers around the selection.
 *
 * @param {Object}   props
 * @param {string}   props.selectedText Text being marked; prefilled into Term or Abbreviation.
 * @param {Function} props.onBack       Return to search.
 * @param {Function} props.onCancel     Close without creating.
 * @param {Function} props.onCreated    Called with the new entry.
 */
export default function TermForm( {
	selectedText = '',
	onBack,
	onCancel,
	onCreated,
} ) {
	const asAbbr = looksLikeAbbreviation( selectedText );
	const [ forms, setForms ] = useState( [
		{
			term: asAbbr ? '' : selectedText,
			abbr: asAbbr ? selectedText : '',
		},
	] );
	const [ definition, setDefinition ] = useState( '' );
	const [ error, setError ] = useState( null );
	const [ saving, setSaving ] = useState( false );
	const textarea = useRef();
	const { createEntry } = useDispatch( store );
	const id = useInstanceId( TermForm, 'cbf-glossary-form' );

	const setForm = ( index, key, value ) =>
		setForms( ( current ) =>
			current.map( ( form, i ) =>
				i === index ? { ...form, [ key ]: value } : form
			)
		);

	const wrap = ( marker ) => {
		const element = textarea.current;
		const { selectionStart: start, selectionEnd: end } = element;
		const next =
			definition.slice( 0, start ) +
			marker +
			definition.slice( start, end ) +
			marker +
			definition.slice( end );

		setDefinition( next );
		window.requestAnimationFrame( () => {
			element.focus();
			element.setSelectionRange(
				start + marker.length,
				end + marker.length
			);
		} );
	};

	const submit = async ( event ) => {
		event.preventDefault();

		if ( ! forms[ 0 ].term.trim() ) {
			setError( __( 'A term is required.', 'cbf-semantic-glossary' ) );
			return;
		}

		const named = forms.filter( ( form ) => form.term.trim() );

		setSaving( true );
		setError( null );
		try {
			onCreated( await createEntry( { forms: named, definition } ) );
		} catch ( failure ) {
			setError(
				failure?.message ||
					__(
						'The term could not be saved.',
						'cbf-semantic-glossary'
					)
			);
			setSaving( false );
		}
	};

	const note = renderingNote( selectedText, forms );

	return (
		<form className="cbf-glossary-form" onSubmit={ submit }>
			<div className="cbf-glossary-popover__header">
				<Button
					icon={ chevronLeft }
					label={ __( 'Back to search', 'cbf-semantic-glossary' ) }
					onClick={ onBack }
					size="small"
				/>
				<span className="cbf-glossary-popover__title">
					{ __( 'New glossary term', 'cbf-semantic-glossary' ) }
				</span>
			</div>

			<div className="cbf-glossary-form__body">
				{ error && (
					<Notice status="error" isDismissible={ false }>
						{ error }
					</Notice>
				) }

				{ forms.map( ( form, index ) => (
					<div className="cbf-glossary-form__pair" key={ index }>
						<div className="cbf-glossary-form__field">
							<label
								className="cbf-glossary-label"
								htmlFor={ `${ id }-term-${ index }` }
							>
								{ index === 0
									? __( 'Term', 'cbf-semantic-glossary' )
									: __(
											'Alternative term',
											'cbf-semantic-glossary'
										) }
							</label>
							<input
								id={ `${ id }-term-${ index }` }
								type="text"
								value={ form.term }
								required={ index === 0 }
								onChange={ ( event ) =>
									setForm( index, 'term', event.target.value )
								}
							/>
						</div>
						<div className="cbf-glossary-form__field">
							<label
								className="cbf-glossary-label"
								htmlFor={ `${ id }-abbr-${ index }` }
							>
								{ __(
									'Abbreviation',
									'cbf-semantic-glossary'
								) }
							</label>
							<input
								id={ `${ id }-abbr-${ index }` }
								type="text"
								value={ form.abbr }
								onChange={ ( event ) =>
									setForm( index, 'abbr', event.target.value )
								}
							/>
						</div>
						{ index > 0 && (
							<Button
								className="cbf-glossary-form__remove"
								icon={ closeSmall }
								label={ __(
									'Remove alternative term',
									'cbf-semantic-glossary'
								) }
								size="small"
								onClick={ () =>
									setForms( ( current ) =>
										current.filter(
											( _, i ) => i !== index
										)
									)
								}
							/>
						) }
					</div>
				) ) }

				<p className="cbf-glossary-help">
					{ __(
						'The term is shown in the glossary. Abbreviation is optional.',
						'cbf-semantic-glossary'
					) }
				</p>

				<Button
					variant="link"
					icon={ plus }
					className="cbf-glossary-form__add"
					onClick={ () =>
						setForms( [ ...forms, { ...EMPTY_FORM } ] )
					}
				>
					{ __( 'Add alternative term', 'cbf-semantic-glossary' ) }
				</Button>

				<div className="cbf-glossary-form__field">
					<label
						className="cbf-glossary-label"
						htmlFor={ `${ id }-definition` }
					>
						{ __( 'Definition', 'cbf-semantic-glossary' ) }
					</label>
					<div className="cbf-glossary-form__editor">
						<div
							className="cbf-glossary-form__toolbar"
							role="toolbar"
							aria-label={ __(
								'Definition formatting',
								'cbf-semantic-glossary'
							) }
						>
							<Button
								size="small"
								label={ __( 'Bold', 'cbf-semantic-glossary' ) }
								onClick={ () => wrap( '**' ) }
							>
								<strong>B</strong>
							</Button>
							<Button
								size="small"
								label={ __(
									'Italic',
									'cbf-semantic-glossary'
								) }
								onClick={ () => wrap( '*' ) }
							>
								<em>I</em>
							</Button>
							<Button
								size="small"
								label={ __(
									'Inline code',
									'cbf-semantic-glossary'
								) }
								onClick={ () => wrap( '`' ) }
							>
								<code>&lt;/&gt;</code>
							</Button>
						</div>
						<textarea
							id={ `${ id }-definition` }
							ref={ textarea }
							rows={ 3 }
							value={ definition }
							aria-describedby={ `${ id }-definition-help` }
							onChange={ ( event ) =>
								setDefinition( event.target.value )
							}
						/>
					</div>
					<p
						id={ `${ id }-definition-help` }
						className="cbf-glossary-help"
					>
						{ __(
							'Supports `code`, **bold**, *italic* and [links](https://example.com).',
							'cbf-semantic-glossary'
						) }
					</p>
				</div>

				{ note && (
					<div className="cbf-glossary-note">
						<Icon icon={ info } size={ 16 } />
						<span>{ note }</span>
					</div>
				) }
			</div>

			<div className="cbf-glossary-popover__footer">
				<Button variant="secondary" onClick={ onCancel }>
					{ __( 'Cancel', 'cbf-semantic-glossary' ) }
				</Button>
				<Button
					variant="primary"
					type="submit"
					isBusy={ saving }
					disabled={ saving }
				>
					{ selectedText
						? __( 'Create and apply', 'cbf-semantic-glossary' )
						: __( 'Create', 'cbf-semantic-glossary' ) }
				</Button>
			</div>
		</form>
	);
}
