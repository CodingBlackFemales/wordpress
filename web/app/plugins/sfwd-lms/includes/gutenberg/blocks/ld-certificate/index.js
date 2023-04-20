/**
 * LearnDash Block ld-certificate
 *
 * @since 3.2
 * @package LearnDash
 */

/**
 * LearnDash block functions
 */
import {
	ldlms_get_post_edit_meta,
	ldlms_get_custom_label,
	ldlms_get_block_legacy_support_panel,
} from "../ldlms.js";

/**
 * Internal block libraries
 */
import { __, _x, sprintf} from '@wordpress/i18n';
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

const block_key   = 'learndash/ld-certificate';
const block_title = __('LearnDash Certificate', 'learndash');

registerBlockType(block_key, {
	title: block_title,
	description: __(
		"This shortcode shows a Certificate download link.",
		"learndash"
	),
	icon: "welcome-learn-more",
	category: "learndash-blocks",
	supports: {
		customClassName: false,
	},
	example: {
		attributes: {
			example_show: 1,
		},
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
		quiz_id: {
			type: "string",
			default: "",
		},
		user_id: {
			type: "string",
			default: "",
		},
		display_as: {
			type: "string",
			default: "",
		},
		label: {
			type: "string",
			default: "",
		},
		class_html: {
			type: "string",
			default: "",
		},
		context: {
			type: "string",
			default: "",
		},
		callback: {
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
		example_show: {
			type: "boolean",
			default: 0,
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
				group_id,
				quiz_id,
				user_id,
				display_as,
				label,
				class_html,
				context,
				callback,
				preview_show,
				preview_user_id,
				example_show,
			},
			title,
			className,
			setAttributes,
		} = props;

		var display_type_control;
		var post_id_controls;
		var button_controls;

		// If the block is used on a Course or Group we set the 'display_as' value to 'banner'.
		if (display_as == "") {
			if (
				ldlms_get_post_edit_meta("post_type") == "sfwd-courses" ||
				ldlms_get_post_edit_meta("post_type") == "groups"
			) {
				setAttributes({ display_as: "banner" });
			}
		}

		display_type_control = (
			<SelectControl
				key="display_type"
				label={__("Display Type", "learndash")}
				value={display_type}
				help={sprintf(
					// translators: placeholders: Course, Group, Quiz.
					_x(
						"Require if not used within a %1$s, %2$s, or %3$s. Or to override default display.",
						"placeholders: Course, Group, Quiz",
						"learndash"
					),
					ldlms_get_custom_label("course"),
					ldlms_get_custom_label("group"),
					ldlms_get_custom_label("quiz")
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
					{
						label: ldlms_get_custom_label("quiz"),
						value: "sfwd-quiz",
					},
				]}
				onChange={(display_type) => setAttributes({ display_type })}
			/>
		);

		if ("sfwd-courses" === display_type) {
			setAttributes({ group_id: "" });
			setAttributes({ quiz_id: "" });
			post_id_controls = (
				<TextControl
					label={sprintf(
						// translators: placeholder: Course.
						_x("%s ID", "placeholder: Course", "learndash"),
						ldlms_get_custom_label("course")
					)}
					help={sprintf(
						// translators: placeholder: Course.
						_x(
							"Enter single %s ID.",
							"placeholder: Course",
							"learndash"
						),
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
					}}
				/>
			);

		} else if ("groups" === display_type) {
			setAttributes({ course_id: "" });
			setAttributes({ quiz_id: "" });
			post_id_controls = (
				<TextControl
					label={sprintf(
						// translators: placeholder: Group.
						_x("%s ID", "placeholder: Group", "learndash"),
						ldlms_get_custom_label("group")
					)}
					help={sprintf(
						// translators: placeholder: Group.
						_x(
							"Enter single %s ID.",
							"placeholder: Group",
							"learndash"
						),
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

		} else if ("sfwd-quiz" === display_type) {
			setAttributes({ group_id: "" });
			post_id_controls = (
				<React.Fragment>
					<TextControl
						label={sprintf(
							// translators: placeholder: Quiz.
							_x("%s ID", "placeholder: Quiz", "learndash"),
							ldlms_get_custom_label("quiz")
						)}
						help={sprintf(
							// translators: placeholder: Quiz.
							_x(
								"Enter single %s ID.",
								"placeholder: Quiz",
								"learndash"
							),
							ldlms_get_custom_label("quiz")
						)}
						value={quiz_id || ""}
						type={'number'}
						onChange={ function( new_quiz_id ) {
							if ( new_quiz_id != "" && new_quiz_id < 0 ) {
								setAttributes({ quiz_id: "0" });
							} else {
								setAttributes({ quiz_id: new_quiz_id });
							}
						}}					/>
					<TextControl
						label={sprintf(
							// translators: placeholder: Course.
							_x("%s ID", "placeholder: Course", "learndash"),
							ldlms_get_custom_label("course")
						)}
						help={sprintf(
							// translators: placeholders: Course, Quiz, Course.
							_x(
								"Enter single %1$s ID. Required if %2$s is within a %3$s",
								"placeholders: Course, Quiz, Course",
								"learndash"
							),
							ldlms_get_custom_label("course"),
							ldlms_get_custom_label("quiz"),
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
				</React.Fragment>
			);
		}

		if (display_as == "button") {
			button_controls = (
				<React.Fragment>
					<TextControl
						label={__("Label", "learndash")}
						help={__("Label for link shown to user", "learndash")}
						value={label || ""}
						onChange={(label) => setAttributes({ label })}
					/>
					<TextControl
						label={__("Class", "learndash")}
						help={__("HTML class for link element", "learndash")}
						value={class_html || ""}
						onChange={(class_html) => setAttributes({ class_html })}
					/>
					<TextControl
						label={__("Context", "learndash")}
						help={__(
							"User defined value to be passed into shortcode handler",
							"learndash"
						)}
						value={context || ""}
						onChange={(context) => setAttributes({ context })}
					/>
					<TextControl
						label={__("Callback", "learndash")}
						help={__(
							"Custom callback function to be used instead of default output",
							"learndash"
						)}
						value={callback || ""}
						onChange={(callback) => setAttributes({ callback })}
					/>
				</React.Fragment>
			);
		}

		const inspectorControls = (
			<InspectorControls key="controls">
				{ldlms_get_block_legacy_support_panel()}
				<PanelBody title={
						__( 'Settings', 'learndash' )
					}>
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
				</PanelBody>
				<PanelBody title={__("Advanced", "learndash")} initialOpen={false}>
					<SelectControl
						key="display_as"
						label={__("Displayed as", "learndash")}
						help={__("Display as Button or Banner", "learndash")}
						value={display_as || "button"}
						options={[
							{
								label: __("Button", "learndash"),
								value: "button",
							},
							{
								label: sprintf(
									// translators: placeholders: Course, Group.
									_x(
										"Banner (%1$s or %2$s only)",
										"placeholders: Course, Group",
										"learndash"
									),
									ldlms_get_custom_label("course"),
									ldlms_get_custom_label("group")
								),
								value: "banner",
							},
						]}
						onChange={(display_as) => setAttributes({ display_as })}
					/>
					{button_controls}
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

	save: (props) => {
		delete props.attributes.example_show;
		delete props.attributes.editing_post_meta;
	},
});
