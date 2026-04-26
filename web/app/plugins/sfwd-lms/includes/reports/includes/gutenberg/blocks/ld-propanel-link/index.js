/* eslint-disable -- TODO: Fix linting issues */

/**
 * LearnDash ProPanel Link Block
 *
 * @since 4.17.0
 *
 * @package LearnDash
 */

/**
 * ProPanel block functions
 */

/**
 * Internal block libraries
 */
const { __, _x, sprintf } = wp.i18n;
const {
	registerBlockType,
} = wp.blocks;

const {
	useBlockProps,
	InspectorControls,
	RichText
} = wp.blockEditor;

const {
	PanelBody
} = wp.components;

const {
	useRef
} = wp.element;

const title = _x('LearnDash Full Report Link', 'learndash');

registerBlockType(
	'ld-propanel/ld-propanel-link',
	{
		title: title,
		description: __('Use this block to place a link on the page for the full LearnDash Report.', 'learndash'),
		icon: 'welcome-widgets-menus',
		category: 'ld-propanel-blocks',
		keywords: [ 'link' ],
		supports: {
			customClassName: false,
		},
		apiVersion: 3,
		attributes: {
			content: {
				type: 'string',
				default: __( 'Show LearnDash Reports Full Page', 'learndash' ),
				source: "html",
				__experimentalRole: "content"
			},
		},
		example: {
			attributes: {
				content: __( 'Show LearnDash Reports Full Page', 'learndash' )
			}
		},
		edit: function( props ) {
			const {
				attributes: {
					content
				},
				setAttributes
			} = props;

			const ref = useRef( null );

			const handleClick = () => {
				ref.current.focus();
			};

			const blockProps = useBlockProps( {
				onClick: handleClick
			} );

			const inspectorControls = (
				<InspectorControls>
					<PanelBody
						title=""
						initialOpen={ true }
					>
						{ __( "To change the link's text, click on it in the editor and change it to your preference.", 'learndash' ) }
					</PanelBody>
				</InspectorControls>
			);

			return (
				<div { ...blockProps }>
					{ inspectorControls }
					<div className={ 'learndash-block-inner' }>
						<div data-ld-widget-type={ 'link' } className={ 'ld-propanel-widget ld-propanel-widget-link' }>
							<RichText
								identifier="content"
								tagName="a"
								value={ content }
								ref={ ref }
								onFocus={ ( event ) => event.currentTarget.setSelectionRange( event.currentTarget.value.length, event.currentTarget.value.length ) }
								onChange={ ( content ) => setAttributes( { content } ) }
								allowedFormats={
									// All the default Formats outside of core/link, since you can't put a link within a link.
									[
										'core/bold',
										'core/code',
										'core/italic',
										'core/image',
										'core/strikethrough',
										'core/underline',
										'core/subscript',
										'core/superscript',
										'core/keyboard'
									]
								}
							/>
						</div>
					</div>
				</div>
			);
		},
		save: function( props ) {
			const {
				attributes: {
					content
				},
			} = props;

			return (
				<RichText.Content value={ content } />
			);
		}
	},
);
