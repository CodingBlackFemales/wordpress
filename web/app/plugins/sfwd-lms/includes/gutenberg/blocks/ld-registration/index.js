/**
 * LearnDash Block ld-registration
 *
 * @since 3.6.0
 * @package LearnDash
 */

/**
 * LearnDash block functions
 */
import {
	ldlms_get_post_edit_meta
} from '../ldlms.js';

/**
 * Internal block libraries
 */
import { __, _x, sprintf} from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { useMemo } from "@wordpress/element";

const block_key   = 'learndash/ld-registration';
const block_title = __( 'LearnDash Registration', 'learndash' );

registerBlockType(
	block_key,
	{
		title: block_title,
		description: __( 'Shows the registration form', 'learndash' ),
		icon: 'id-alt',
		category: 'learndash-blocks',
		example: {
			attributes: {
				example_show: 1,
			},
		},
		supports: {
			customClassName: false,
		},
		attributes: {
			width: {
				type: 'string',
			},
			example_show: {
				type: 'boolean',
				default: 1
			},
			preview_show: {
				type: 'boolean',
				default: true
			},
			editing_post_meta: {
				type: 'object'
			}
		},
		edit: function( props ) {
			const { attributes: { preview_show, example_show, width },
				setAttributes } = props;

			const inspectorControls = (
				<InspectorControls key="controls">
					<PanelBody
						title={ __( 'Styling', 'learndash' ) }
						initialOpen={ true }
					>
						<TextControl
							label={__('Form Width', 'learndash')}
							help={__('Sets the width of the registration form.', 'learndash')}
							value={width || ''}
							type={'string'}
							onChange={width => setAttributes({ width })}
						/>
					</PanelBody>
					<PanelBody
						title={ __( 'Preview', 'learndash' ) }
						initialOpen={ false }
					>
						<ToggleControl
							label={ __('Show Preview', 'learndash') }
							checked={ !!preview_show }
							onChange={ preview_show => setAttributes( { preview_show } ) }
						/>
					</PanelBody>
				</InspectorControls>
			);

			function get_default_message() {
				return sprintf(
					// translators: placeholder: block_title.
					_x('%s block output shown here', 'placeholder: block_title', 'learndash'), block_title
				);
			}

			function empty_response_placeholder_function(props) {
				return get_default_message();
			}

			function do_serverside_render( attributes ) {
				if ( attributes.preview_show == true ) {
					// We add the meta so the server knowns what is being edited.
					attributes.editing_post_meta = ldlms_get_post_edit_meta();

					return <ServerSideRender
						block={block_key}
						attributes={ attributes }
						key={block_key}
						EmptyResponsePlaceholder={ empty_response_placeholder_function }
					/>
				} else {
					return get_default_message();
				}
			}

			return [
				inspectorControls,
				useMemo(() => do_serverside_render(props.attributes), [props.attributes]),
			];
		},

		save: props => {
			delete (props.attributes.example_show);
			delete(props.attributes.editing_post_meta);
		}
	},
);
