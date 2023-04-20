/**
 * LearnDash Block ld-user-groups
 *
 * @since 2.5.9
 * @package LearnDash
 */

/**
 * LearnDash block functions
 */
 import {
	ldlms_get_post_edit_meta,
	ldlms_get_custom_label,
} from '../ldlms.js';

/**
 * Internal block libraries
 */
import { __, _x, sprintf } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, PanelRow, TextControl, ToggleControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { useMemo } from "@wordpress/element";

const block_key   = 'learndash/ld-user-groups';
const block_title = sprintf(
	// translators: placeholder: Groups.
	_x('LearnDash User %s', 'placeholder: Groups', 'learndash'), ldlms_get_custom_label('groups')
);
registerBlockType(
	block_key,
	{
		title: block_title,
		description: sprintf(
			// translators: placeholder: Groups.
			_x( 'This block displays the list of %s users are assigned to as users or leaders.', 'placeholder: Groups', 'learndash'), ldlms_get_custom_label('groups')),
		icon: 'groups',
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
			user_id: {
				type: 'string',
				default: ''
			},
			preview_show: {
				type: 'boolean',
				default: 1
			},
			preview_user_id: {
				type: 'string',
			},
			editing_post_meta: {
				type: 'object'
			}
		},
		edit: function (props) {
			const { attributes: { user_id, preview_user_id, preview_show },
				setAttributes } = props;

				let panel_groups_not_public = '';
				if ( ldlms_settings['settings']['groups_cpt']['public'] === '' ) {
					panel_groups_not_public = (
						<PanelBody
							title={__('Warning', 'learndash')}
							opened={true}
						>
							<TextControl
								help={sprintf(
									// translators: placeholders: Groups, Groups.
									_x('%1$s are not public, please visit the %2$s Settings page and set them to Public to enable access on the front end.', 'placeholders: Groups, Groups', 'learndash'), ldlms_get_custom_label('groups'), ldlms_get_custom_label('groups'))}
								value={''}
								type={'hidden'}
								className={'notice notice-error'}
							/>
						</PanelBody>
					)
				}

			const inspectorControls = (
				<InspectorControls key="controls">
					{panel_groups_not_public}
					<PanelBody title={__("Settings", "learndash")}>
						<TextControl
							label={__("User ID", "learndash")}
							help={__(
								"Enter specific User ID. Leave blank for current User.",
								"learndash"
							)}
							value={user_id || ""}
							type={"number"}
							onChange={function (new_user_id) {
								if (new_user_id != "" && new_user_id < 0) {
									setAttributes({ user_id: "0" });
								} else {
									setAttributes({ user_id: new_user_id });
								}
							}}
						/>
					</PanelBody>
					<PanelBody title={__("Preview", "learndash")} initialOpen={false}>
						<ToggleControl
							label={__("Show Preview", "learndash")}
							checked={!!preview_show}
							onChange={(preview_show) => setAttributes({ preview_show })}
						/>
						<PanelRow className="learndash-block-error-message">
							{__("Preview settings are not saved.", "learndash")}
						</PanelRow>
						<TextControl
							label={__("Preview User ID", "learndash")}
							help={__("Enter a User ID to test preview", "learndash")}
							value={preview_user_id || ""}
							type={"number"}
							onChange={function (preview_new_user_id) {
								if (preview_new_user_id != "" && preview_new_user_id < 0) {
									setAttributes({ preview_user_id: "0" });
								} else {
									setAttributes({ preview_user_id: preview_new_user_id });
								}
							}}
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

			function do_serverside_render(attributes) {
				if (attributes.preview_show == true) {
					// We add the meta so the server knowns what is being edited.
					attributes.editing_post_meta = ldlms_get_post_edit_meta();

					return <ServerSideRender
						block={block_key}
						attributes={attributes}
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
			delete (props.attributes.editing_post_meta);
		}
	},
);
