/**
 * LearnDash Block ld-student
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
	ldlms_get_integer_value
} from '../ldlms.js';

/**
 * Internal block libraries
 */
import { __, _x, sprintf } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	TextControl,
	ToggleControl,
} from "@wordpress/components";

const block_key   = 'learndash/ld-student';
const block_title = __('LearnDash Student', 'learndash');

registerBlockType(block_key, {
	title: block_title,
	description: sprintf(
		// translators: placeholder: Course.
		_x(
			"This block shows the content if the user is enrolled in the %s.",
			"placeholders: Course",
			"learndash"
		),
		ldlms_get_custom_label("course")
	),
	icon: "welcome-learn-more",
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
		group_id: {
			type: "string",
			default: "",
		},
		user_id: {
			type: "string",
			default: "",
		},
		autop: {
			type: "boolean",
			default: true,
		},
	},
	edit: (props) => {
		const {
			attributes: { display_type, course_id, group_id, user_id, autop },
			className,
			setAttributes,
		} = props;

		var display_type_control;
		var post_id_controls;

		display_type_control = (
			<SelectControl
				key="display_type"
				label={__("Display Type", "learndash")}
				value={display_type}
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
				help={sprintf(
					// translators: placeholders: Course, Group.
					_x(
						"Leave blank to show the default %1$s or %2$s content table.",
						"placeholders: Course, Group",
						"learndash"
					),
					ldlms_get_custom_label("course"),
					ldlms_get_custom_label("group")
				)}
				onChange={(display_type) => setAttributes({ display_type })}
			/>
		);

		if ("sfwd-courses" === display_type) {
			setAttributes({ group_id: "" });
			post_id_controls = (
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
					}}				/>
			);
		} else if ("groups" === display_type) {
			setAttributes({ course_id: "" });
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
					}}				/>
			);
		}

		const inspectorControls = (
			<InspectorControls key="controls">
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
						}}					/>
					<ToggleControl
						label={__("Auto Paragraph", "learndash")}
						checked={!!autop}
						onChange={(autop) => setAttributes({ autop })}
					/>
				</PanelBody>
			</InspectorControls>
		);

		let preview_display_type = display_type;
		if (preview_display_type === "") {
			let editing_post_meta = ldlms_get_post_edit_meta();
			if ("undefined" !== typeof editing_post_meta.post_type) {
				if (editing_post_meta.post_type === "sfwd-courses") {
					preview_display_type = "sfwd-courses";
				} else if (editing_post_meta.post_type === "groups") {
					preview_display_type = "groups";
				}
			}
		}

		let ld_block_error_message = "";
		if ("sfwd-courses" === preview_display_type) {
			let preview_course_id = ldlms_get_integer_value(course_id);

			if (preview_course_id === 0) {
				preview_course_id = ldlms_get_post_edit_meta("course_id");
				preview_course_id = ldlms_get_integer_value(preview_course_id);

				if (preview_course_id == 0) {
					ld_block_error_message = sprintf(
						// translators: placeholders: Course, Course.
						_x(
							"%1$s ID is required when not used within a %2$s.",
							"placeholders: Course, Course",
							"learndash"
						),
						ldlms_get_custom_label("course"),
						ldlms_get_custom_label("course")
					);
				}
			}
		} else if ("groups" === preview_display_type) {
			let preview_group_id = ldlms_get_integer_value(group_id);

			if (preview_group_id === 0) {
				preview_group_id = ldlms_get_post_edit_meta("post_id");
				preview_group_id = ldlms_get_integer_value(preview_group_id);

				if (preview_group_id == 0) {
					ld_block_error_message = sprintf(
						// translators: placeholders: Group, Group.
						_x(
							"%1$s ID is required when not used within a %2$s.",
							"placeholders: Group, Group",
							"learndash"
						),
						ldlms_get_custom_label("group"),
						ldlms_get_custom_label("group")
					);
				}
			}
		}

		if (ld_block_error_message.length) {
			ld_block_error_message = (
				<span className="learndash-block-error-message">
					{ld_block_error_message}
				</span>
			);
		}

		const outputBlock = (
			<div className={className} key="learndash/ld-student">
				<span className="learndash-inner-header">{block_title}</span>
				<div className="learndash-block-inner">
					{ld_block_error_message}
					<InnerBlocks />
				</div>
			</div>
		);

		return [inspectorControls, outputBlock];
	},

	save: (props) => {
		return <InnerBlocks.Content />;
	},
});
