/**
 * LearnDash Block ld-quizinfo
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
import { PanelBody, PanelRow, SelectControl, TextControl, ToggleControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { useMemo } from "@wordpress/element";

const block_key   = 'learndash/ld-quizinfo';
const block_title = sprintf(
	// translators: placeholder: Quiz.
	_x( 'LearnDash %s Info [quizinfo]', 'placeholder: Quiz', 'learndash' ), ldlms_get_custom_label( 'quiz' )
);
registerBlockType(block_key, {
	title: block_title,
	description: sprintf(
		// translators: placeholder: Quiz.
		_x(
			"This block displays %s related information",
			"placeholder: Quiz",
			"learndash"
		),
		ldlms_get_custom_label("quiz")
	),
	icon: "analytics",
	category: "learndash-blocks",
	supports: {
		customClassName: false,
	},
	attributes: {
		show: {
			type: "string",
			default: "quiz_title",
		},
		quiz_id: {
			type: "string",
			default: "",
		},
		user_id: {
			type: "string",
			default: "",
		},
		format: {
			type: "string",
		},
		field_id: {
			type: "string",
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
				quiz_id,
				user_id,
				timestamp,
				show,
				format,
				field_id,
				preview_show,
				preview_user_id,
			},
			className,
			setAttributes,
		} = props;

		const field_show_field = (
			<SelectControl
				key="show"
				value={show || "quiz_title"}
				label={__("Show", "learndash")}
				options={[
					{
						label: sprintf(
							// translators: placeholder: Quiz.
							_x("%s Title", "placeholder: Quiz", "learndash"),
							ldlms_get_custom_label("quiz")
						),
						value: "quiz_title",
					},
					{
						label: sprintf(
							// translators: placeholder: Quiz.
							_x("%s Score", "placeholder: Quiz", "learndash"),
							ldlms_get_custom_label("quiz")
						),
						value: "score",
					},
					{
						label: sprintf(
							// translators: placeholder: Quiz.
							_x("%s Count", "placeholder: Quiz", "learndash"),
							ldlms_get_custom_label("quiz")
						),
						value: "count",
					},
					{
						label: sprintf(
							// translators: placeholder: Quiz.
							_x("%s Pass", "placeholder: Quiz", "learndash"),
							ldlms_get_custom_label("quiz")
						),
						value: "pass",
					},
					{
						label: sprintf(
							// translators: placeholder: Quiz.
							_x("%s Timestamp", "placeholder: Quiz", "learndash"),
							ldlms_get_custom_label("quiz")
						),
						value: "timestamp",
					},
					{
						label: sprintf(
							// translators: placeholder: Quiz.
							_x("%s Points", "placeholder: Quiz", "learndash"),
							ldlms_get_custom_label("quiz")
						),
						value: "points",
					},
					{
						label: sprintf(
							// translators: placeholder: Quiz.
							_x("%s Total Points", "placeholder: Quiz", "learndash"),
							ldlms_get_custom_label("quiz")
						),
						value: "total_points",
					},
					{
						label: sprintf(
							// translators: placeholder: Quiz.
							_x("%s Percentage", "placeholder: Quiz", "learndash"),
							ldlms_get_custom_label("quiz")
						),
						value: "percentage",
					},
					{
						label: sprintf(
							// translators: placeholder: Course.
							_x("%s Title", "placeholder: Course", "learndash"),
							ldlms_get_custom_label("course")
						),
						value: "course_title",
					},
					{
						label: sprintf(
							// translators: placeholder: Quiz.
							_x("%s Time Spent", "placeholder: Quiz", "learndash"),
							ldlms_get_custom_label("quiz")
						),
						value: "timespent",
					},
					{
						label: sprintf(
							// translators: placeholder: Quiz.
							_x("%s Form Field", "placeholder: Quiz", "learndash"),
							ldlms_get_custom_label("quiz")
						),
						value: "field",
					},
				]}
				onChange={(show) => setAttributes({ show })}
			/>
		);

		let field_custom_field_id = "";
		if (show == "field") {
			field_custom_field_id = (
				<TextControl
					label={__("Custom Field ID", "learndash")}
					help={sprintf(
						// translators: placeholder: Quiz.
						_x(
							"The Field ID is shown on the %s Custom Fields table.",
							"placeholder: Quiz",
							"learndash"
						),
						ldlms_get_custom_label("quiz")
					)}
					value={field_id || ""}
					onChange={(field_id) => setAttributes({ field_id })}
				/>
			);
		}

		let field_format_field = "";
		if (show == "timestamp" || show == "field") {
			field_format_field = (
				<TextControl
					label={__("Format", "learndash")}
					help={__(
						'This can be used to change the date format. Default: F j, Y, g:i a.',
						"learndash"
					)}
					value={format || ""}
					onChange={(format) => setAttributes({ format })}
				/>
			);
		}

		const inspectorControls = (
			<InspectorControls key="controls">
				<PanelBody title={__("Settings", "learndash")}>
					{field_show_field}
					{field_custom_field_id}
					{field_format_field}
					<TextControl
						label={sprintf(
							// translators: placeholder: Quiz.
							_x("%s ID", "placeholder: Quiz", "learndash"),
							ldlms_get_custom_label("quiz")
						)}
						help={sprintf(
							// translators: placeholders: Quiz, Quiz.
							_x(
								"Enter a single %1$s ID. Leave blank if used within a %2$s or Certificate.",
								"placeholders: Quiz, Quiz",
								"learndash"
							),
							ldlms_get_custom_label("quiz"),
							ldlms_get_custom_label("quiz")
						)}
						value={quiz_id || ""}
						type={"number"}
						onChange={function (new_quiz_id) {
							if (new_quiz_id != "" && new_quiz_id < 0) {
								setAttributes({ quiz_id: "0" });
							} else {
								setAttributes({ quiz_id: new_quiz_id });
							}
						}}
					/>
					<TextControl
						label={__("User ID", "learndash")}
						help={sprintf(
							// translators: placeholder: Quiz.
							_x(
								"Enter a single User ID. Leave blank if used within a %s or Certificate.",
								"placeholder: Quiz",
								"learndash"
							),
							ldlms_get_custom_label("quiz")
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
					<TextControl
						label={__("Attempt timestamp", "learndash")}
						help={sprintf(
							// translators: placeholder: Quiz.
							_x(
								'Single %s attempt timestamp. See WP user profile "#" link on attempt row. Leave blank to use latest attempt or within a Certificate.',
								"placeholder: Quiz",
								"learndash"
							),
							ldlms_get_custom_label("quiz")
						)}
						value={timestamp || ""}
						onChange={function (new_timestamp) {
							if (
								new_timestamp.length &&
								new_timestamp.startsWith("data:quizinfo:", 0)
							) {
								var input_value_parts = new_timestamp.split(":");
								if (input_value_parts.length > 2) {
									var field_id = "";
									for (
										let index = 2;
										index < input_value_parts.length;
										index++
									) {
										if (field_id == "") {
											if (input_value_parts[index] == "quiz") {
												field_id = "quiz_id";
											} else if (input_value_parts[index] == "user") {
												field_id = "user_id";
											} else if (input_value_parts[index] == "time") {
												field_id = "time";
											}
											continue;
										} else {
											if (field_id == "quiz_id") {
												setAttributes({ quiz_id: input_value_parts[index] });
											} else if (field_id == "user_id") {
												setAttributes({ user_id: input_value_parts[index] });
											} else if (field_id == "time") {
												setAttributes({ timestamp: input_value_parts[index] });
											}
											field_id = "";
											continue;
										}
									}
								}
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
						onChange={function (new_preview_user_id) {
							if (new_preview_user_id != "" && new_preview_user_id < 0) {
								setAttributes({ preview_user_id: "0" });
							} else {
								setAttributes({ preview_user_id: new_preview_user_id });
							}
						}}
					/>
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
