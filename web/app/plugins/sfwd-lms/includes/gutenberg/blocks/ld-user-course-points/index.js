/**
 * LearnDash Block ld-user-course-points
 *
 * @since 2.5.9
 * @package LearnDash
 */

/**
 * LearnDash block functions
 */
import { ldlms_get_post_edit_meta, ldlms_get_custom_label } from "../ldlms.js";

/**
 * Internal block libraries
 */
import { __, _x, sprintf } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl, PanelRow } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { useMemo } from "@wordpress/element";

const block_key = 'learndash/ld-user-course-points';
const block_title = sprintf(
	// translators: placeholder: Course.
	_x('LearnDash User %s Points', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course')
)

registerBlockType(
	block_key,
	{
		title: block_title,
		description: sprintf(
			// translators: placeholder: Course.
			_x('This block shows the earned %s points for the user.', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course') ),
		icon: 'chart-area',
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
				default: '',
			},
			preview_show: {
				type: 'boolean',
				default: 1
			},
			preview_user_id: {
				type: 'string',
			},
			editing_post_meta: {
				type: 'object',
			}
		},
		edit: props => {
			const { attributes: { user_id, preview_show, preview_user_id },
				setAttributes } = props;

			const inspectorControls = (
				<InspectorControls key="controls">
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

					return (
						<ServerSideRender
							block={block_key}
							attributes={attributes}
							key={block_key}
							EmptyResponsePlaceholder={empty_response_placeholder_function}
						/>
					);
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
			// Delete preview_user_id from props to prevent it being saved.
			delete (props.attributes.preview_user_id);
		},
	},
);
