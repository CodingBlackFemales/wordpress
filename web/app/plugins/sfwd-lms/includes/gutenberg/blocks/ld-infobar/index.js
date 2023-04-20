/**
 * LearnDash Block ld-infobar
 *
 * @since 4.0
 * @package LearnDash
 */

/**
 * LearnDash block functions
 */
import {
	ldlms_get_custom_label,
	ldlms_get_post_edit_meta,
	ldlms_get_block_legacy_support_panel,
} from "../ldlms.js";

/**
 * Internal block libraries
 */
import { __, _x } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	TextControl,
	ToggleControl,
	PanelRow,
} from "@wordpress/components";
import ServerSideRender from '@wordpress/server-side-render';
import { useMemo } from "@wordpress/element";

const block_key   = 'learndash/ld-infobar';
const block_title = __( 'LearnDash Infobar', 'learndash' );

registerBlockType(block_key, {
	title: block_title,
	description: __(
		"This block displays an Infobar for a specific LearnDash related post.",
		"learndash"
	),
	icon: "welcome-widgets-menus",
	category: "learndash-blocks",
	supports: {
		customClassName: false,
	},
	attributes: {
		display_type: {
			type: "string",
			default: "",
		},
		course_id: {
			type: "string",
			default: "",
		},
		post_id: {
			type: "string",
			default: "",
		},
		group_id: {
			type: "string",
			default: "",
		},
		user_id: {
			type: "string",
			default: "",
		},
		preview_show: {
			type: "boolean",
			default: 1,
		},
		preview_user_id: {
			type: "string",
			default: "",
		},
		editing_post_meta: {
			type: "object",
		},
	},
	edit: (props) => {
		const {
			attributes: {
				display_type,
				course_id,
				post_id,
				group_id,
				user_id,
				preview_show,
				preview_user_id,
			},
			setAttributes,
		} = props;

		var display_type_control;
		var post_id_controls;

		display_type_control = (
			<SelectControl
				key="display_type"
				label={__("Display Type", "learndash")}
				value={display_type}
				help={sprintf(
					// translators: placeholders: Course, Group.
					_x(
						"Require if not used within a %1$s or %2$s. Or to override default display.",
						"placeholders: Course, Group",
						"learndash"
					),
					ldlms_get_custom_label("course"),
					ldlms_get_custom_label("group")
				)}
				options={[
					{
						label: __("Select a Display Type", "learndash"),
						value: "",
					},
					{
						label: ldlms_get_custom_label("course"),
						value: "sfwd-courses",
					},
					{
						label: ldlms_get_custom_label("group"),
						value: "groups",
					},
				]}
				onChange={(display_type) => setAttributes({ display_type })}
			/>
		);

		if ("sfwd-courses" === display_type) {
			setAttributes({ group_id: "" });
			post_id_controls = (
				<React.Fragment>
					<TextControl
						label={sprintf(
							// translators: placeholder: Course.
							_x("%s ID", "placeholder: Course", "learndash"),
							ldlms_get_custom_label("course")
						)}
						help={sprintf(
							// translators: placeholders: Course, Course.
							_x(
								"Enter single %1$s ID. Leave blank if used within a %2$s.",
								"placeholders: Course, Course",
								"learndash"
							),
							ldlms_get_custom_label("course"),
							ldlms_get_custom_label("course")
						)}
						value={course_id || ""}
						type={'number'}
						onChange={ function( new_course_id ) {
							if ( new_course_id != "" && new_course_id < 0 ) {
								setAttributes({ course_id: "0" });
							} else {
								setAttributes({ course_id: new_course_id });
							}
						}}					/>
					<TextControl
						label={__("Step ID", "learndash")}
						help={sprintf(
							// translators: placeholders: Course, Course.
							_x(
								"Enter single Step ID. Leave blank if used within a %1$s step.",
								"placeholders: Course, Course",
								"learndash"
							),
							ldlms_get_custom_label("course"),
							ldlms_get_custom_label("course")
						)}
						value={post_id || ""}
						type={'number'}
						onChange={ function( new_post_id ) {
							if ( new_post_id != "" && new_post_id < 0 ) {
								setAttributes({ post_id: "0" });
							} else {
								setAttributes({ post_id: new_post_id });
							}
						}}					/>
				</React.Fragment>
			);
		} else if ("groups" === display_type) {
			setAttributes({ course_id: "" });
			setAttributes({ post_id: "" });
			post_id_controls = (
				<TextControl
				label={sprintf(
						// translators: placeholder: Group.
						_x("%s ID", "placeholder: Group", "learndash"),
						ldlms_get_custom_label("group")
					)}
					help={sprintf(
						// translators: placeholders: Group, Group.
						_x(
							"Enter single %1$s ID. Leave blank if used within a %2$s.",
							"placeholders: Group, Group",
							"learndash"
						),
						ldlms_get_custom_label("group"),
						ldlms_get_custom_label("group")
					)}
					value={group_id || ""}
					type={'number'}
					onChange={ function( new_group_id ) {
						if ( new_group_id != "" && new_group_id < 0 ) {
							setAttributes({ group_id: "0" });
						} else {
							setAttributes({ group_id: new_group_id });
						}
					}}					/>
			);
		}

		const inspectorControls = (
			<InspectorControls key="controls">
				{ldlms_get_block_legacy_support_panel()}
				<PanelBody title={__("Settings", "learndash")}>
					{display_type_control}
					{post_id_controls}
					<TextControl
						label={__("User ID", "learndash")}
						help={__(
							"Enter specific User ID. Leave blank for current User.",
							"learndash"
						)}
						value={user_id || ""}
						type={'number'}
						onChange={ function( new_user_id ) {
							if ( new_user_id != "" && new_user_id < 0 ) {
								setAttributes({ user_id: "0" });
							} else {
								setAttributes({ user_id: new_user_id });
							}
						}}						/>
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
						help={__("Enter a User ID for preview.", "learndash")}
						value={preview_user_id || ""}
						type={'number'}
						onChange={ function( preview_new_user_id ) {
							if ( preview_new_user_id != "" && preview_new_user_id < 0 ) {
								setAttributes({ preview_user_id: "0" });
							} else {
								setAttributes({ preview_user_id: preview_new_user_id });
							}
						}}					/>
				</PanelBody>
			</InspectorControls>
		);

		function get_default_message() {
			return sprintf(
				// translators: placeholder: block_title.
				_x(
					"%s block output shown here",
					"placeholder: block_title",
					"learndash"
				),
				block_title
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
	save: function (props) {
		delete props.attributes.example_show;
		delete props.attributes.editing_post_meta;
	},
});
