/* eslint-disable -- TODO: Fix linting issues */

/**
 * LearnDash ProPanel Filters Block
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
	InspectorControls
} = wp.blockEditor;

const {
	PanelBody,
	ToggleControl,
	Disabled,
} = wp.components;

import ServerSideRender from '@wordpress/server-side-render';

const title = _x('LearnDash Report Filters', 'learndash');

registerBlockType(
	'ld-propanel/ld-propanel-filters',
	{
		title: title,
		description: __('Displays the filter tools for LearnDash Reports.', 'learndash'),
		icon: 'filter',
		category: 'ld-propanel-blocks',
		keywords: [ 'filter' ],
		supports: {
			customClassName: false,
		},
		apiVersion: 3,
		attributes: {
			preview_show: {
				type: 'boolean',
				default: true
			},
		},
		example: {
			attributes: {
				preview_show: true
			}
		},
		edit: function( props ) {
			const {
				attributes: { preview_show },
				setAttributes
			} = props;
			const blockProps = useBlockProps();

			const panel_preview = (
				<PanelBody
					title={__('Preview', 'learndash')}
					initialOpen={false}
				>
					<ToggleControl
						label={__('Show Preview', 'learndash')}
						checked={!!preview_show}
						onChange={preview_show => setAttributes({ preview_show })}
					/>
				</PanelBody>
			);

			const inspectorControls = (
				<InspectorControls>
					<PanelBody
						title=""
						initialOpen={ true }
					>
						{ __( 'This Block requires one or more of the following other Blocks to be placed on the same page:', 'learndash' ) }
						<ul style={ {
							listStyle: 'disc',
							marginLeft: '1.5em'
						} }>
							<li>
								{ __( 'LearnDash Reporting', 'learndash' ) }
							</li>
							<li>
								{ __( 'LearnDash Activity Report', 'learndash' ) }
							</li>
							<li>
								{ __( 'LearnDash Progress Chart', 'learndash' ) }
							</li>
						</ul>
					</PanelBody>
					{ panel_preview }
				</InspectorControls>
			);

			function do_serverside_render( attributes ) {
				if ( attributes.preview_show == true ) {
					return (
						<div className={ 'learndash-block-inner' }>
							<div data-ld-widget-type={ 'filtering' } className={ 'ld-propanel-widget ld-propanel-widget-filtering' }>
								<ServerSideRender
									block="ld-propanel/ld-propanel-filters"
									attributes={ attributes }
									// GET is the default, but just to help ensure future-proofing
									httpMethod='GET'
									urlQueryArgs={
										// Pass attributes through to the GET request at the top-level to better re-use the existing Ajax logic for Shortcodes
										Object.assign(
											{
												template: 'filtering',
												container_type: 'shortcode'
											},
											attributes
					 					)
									}
								/>
							</div>
						</div>
					);
				} else {
					// translators: %s is the title for the Block.
					return __( 'Toggle the Preview setting in the sidebar to see the %s in the editor.', 'learndash' ).replace( '%s', title );
				}
			}

			return (
				<div { ...blockProps }>
					{ inspectorControls }
					<Disabled>
						{ do_serverside_render( props.attributes ) }
					</Disabled>
				</div>
			);
		},
		save: props => {
			// Delete meta from props to prevent it being saved.
			delete (props.attributes.meta);
		}
	},
);
