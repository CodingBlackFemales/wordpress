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

const title = _x('LearnDash Reports Overview', 'learndash');

registerBlockType(
	'ld-propanel/ld-propanel-overview',
	{
		title: title,
		description: __('Displays the four overview widgets from LearnDash reports; Total Students, Courses, Assignments Pending, Essays Pending.', 'learndash'),
		icon: 'grid-view',
		category: 'ld-propanel-blocks',
		keywords: [ 'overview', 'course', 'student', 'assignment', 'essay' ],
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
					{ panel_preview }
				</InspectorControls>
			);

			function do_serverside_render( attributes ) {
				if ( attributes.preview_show == true ) {
					return (
						<div className={ 'learndash-block-inner' }>
							<div data-ld-widget-type={ 'overview' } className={ 'ld-propanel-widget ld-propanel-widget-overview' }>
								<ServerSideRender
									block="ld-propanel/ld-propanel-overview"
									attributes={ attributes }
									// GET is the default, but just to help ensure future-proofing
									httpMethod='GET'
									urlQueryArgs={
										// Pass attributes through to the GET request at the top-level to better re-use the existing Ajax logic for Shortcodes
										Object.assign(
											{
												template: 'overview',
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
