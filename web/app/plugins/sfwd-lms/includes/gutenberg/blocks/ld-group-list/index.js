/**
 * LearnDash Block ld-group-list
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
	ldlms_get_per_page,
} from '../ldlms.js';

/**
 * Internal block libraries
 */
import { __, _x, sprintf} from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, TextControl, ToggleControl, PanelRow } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { useMemo } from "@wordpress/element";

const block_key   = 'learndash/ld-group-list';
const block_title = sprintf(
	// translators: placeholder: Group.
	_x('LearnDash %s List', 'placeholder: Group', 'learndash'), ldlms_get_custom_label('group')
);

registerBlockType(block_key, {
	title: block_title,
	description: sprintf(
		// translators: placeholder: Groups.
		_x("This block shows a list of %s.", "placeholder: Groups", "learndash"),
		ldlms_get_custom_label("groups")
	),
	icon: "list-view",
	category: "learndash-blocks",
	example: {
		attributes: {
			example_show: 1,
		},
	},
	supports: {
		customClassName: false,
	},
	attributes: {
		orderby: {
			type: "string",
			default: "ID",
		},
		order: {
			type: "string",
			default: "DESC",
		},
		per_page: {
			type: "string",
			default: "",
		},
		mygroups: {
			type: "string",
			default: "",
		},
		status: {
			type: "array",
			default: ["not_started", "in_progress", "completed"],
		},
		show_content: {
			type: "boolean",
			default: true,
		},
		show_thumbnail: {
			type: "boolean",
			default: true,
		},
		group_category_name: {
			type: "string",
			default: "",
		},
		group_cat: {
			type: "string",
			default: "",
		},
		group_categoryselector: {
			type: "boolean",
			default: false,
		},
		group_tag: {
			type: "string",
			default: "",
		},
		group_tag_id: {
			type: "string",
			default: "",
		},
		category_name: {
			type: "string",
			default: "",
		},
		cat: {
			type: "string",
			default: "",
		},
		categoryselector: {
			type: "boolean",
			default: false,
		},
		tag: {
			type: "string",
			default: "",
		},
		tag_id: {
			type: "string",
			default: "",
		},
		course_grid: {
			type: "boolean",
		},
		progress_bar: {
			type: "boolean",
			default: false,
		},
		col: {
			type: "integer",
			default:
				ldlms_settings["plugins"]["learndash-course-grid"]["enabled"][
					"col_default"
				] || 3,
		},
		price_type: {
			type: "array",
			default: ["free", "paynow", "subscribe", "closed"],
		},
		preview_show: {
			type: "boolean",
			default: true,
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
	edit: function (props) {
		const {
			attributes: {
				orderby,
				order,
				per_page,
				mygroups,
				status,
				show_content,
				show_thumbnail,
				group_category_name,
				group_cat,
				group_categoryselector,
				group_tag,
				group_tag_id,
				category_name,
				cat,
				categoryselector,
				tag,
				tag_id,
				course_grid,
				progress_bar,
				col,
				preview_user_id,
				preview_show,
				example_show,
				price_type,
			},
			setAttributes,
		} = props;

		let field_show_content = "";
		let field_show_thumbnail = "";
		let panel_course_grid_section = "";

		let course_grid_default = true;
		if (
			ldlms_settings["plugins"]["learndash-course-grid"]["enabled"] === true
		) {
			if (
				typeof course_grid !== "undefined" &&
				(course_grid == true || course_grid == false)
			) {
				course_grid_default = course_grid;
			}

			let course_grid_section_open = false;
			if (course_grid_default == true) {
				course_grid_section_open = true;
			}
			panel_course_grid_section = (
				<PanelBody
					title={__('Grid Settings', 'learndash')}
					initialOpen={course_grid_section_open}
				>
					<ToggleControl
						label={
							__("Show Grid",
							"learndash")
						}
						checked={!!course_grid_default}
						onChange={(course_grid) => setAttributes({ course_grid })}
					/>
					<ToggleControl
						label={__("Show Progress Bar", "learndash")}
						checked={!!progress_bar}
						onChange={(progress_bar) => setAttributes({ progress_bar })}
					/>
					<RangeControl
						label={__("Columns", "learndash")}
						value={
							col ||
							ldlms_settings["plugins"]["learndash-course-grid"]["enabled"][
								"col_default"
							]
						}
						min={1}
						max={
							ldlms_settings["plugins"]["learndash-course-grid"]["enabled"][
								"col_max"
							]
						}
						step={1}
						onChange={(col) => setAttributes({ col })}
					/>
				</PanelBody>
			);
		}

		//if (course_grid !== true) {
		field_show_content = (
			<ToggleControl
				label={__("Show Content", "learndash")}
				checked={!!show_content}
				onChange={(show_content) => setAttributes({ show_content })}
			/>
		);

		field_show_thumbnail = (
			<ToggleControl
				label={__("Show Thumbnail", "learndash")}
				checked={!!show_thumbnail}
				onChange={(show_thumbnail) => setAttributes({ show_thumbnail })}
			/>
		);
		//}

		let panel_groups_not_public = "";
		if (ldlms_settings["settings"]["groups_cpt"]["public"] === "") {
			panel_groups_not_public = (
				<PanelBody
					title={__("Warning", "learndash")}
					opened={true}
				>
					<TextControl
						help={sprintf(
							// translators: placeholders: Groups, Groups.
							_x(
								"%1$s are not public, please visit the %2$s Settings page and set them to Public to enable access on the front end.",
								"placeholders: Groups, Groups",
								"learndash"
							),
							ldlms_get_custom_label("groups"),
							ldlms_get_custom_label("groups")
						)}
						value={""}
						type={"hidden"}
						className={"notice notice-error"}
					/>
				</PanelBody>
			);
		}

		const panelbody_header = (
			<PanelBody
				className="learndash-block-controls-panel learndash-block-controls-panel-ld-group-list"
				title={__("Settings", "learndash")}
			>
				<SelectControl
					key="orderby"
					label={__("Order by", "learndash")}
					value={orderby}
					options={[
						{
							label: __("ID - Order by post id. (default)", "learndash"),
							value: "ID",
						},
						{
							label: __("Title - Order by post title", "learndash"),
							value: "title",
						},
						{
							label: __("Date - Order by post date", "learndash"),
							value: "date",
						},
						{
							label: __("Menu - Order by Page Order Value", "learndash"),
							value: "menu_order",
						},
					]}
					onChange={(orderby) => setAttributes({ orderby })}
				/>
				<SelectControl
					key="order"
					label={__("Order", "learndash")}
					value={order}
					options={[
						{
							label: __(
								"DESC - highest to lowest values (default)",
								"learndash"
							),
							value: "DESC",
						},
						{
							label: __("ASC - lowest to highest values", "learndash"),
							value: "ASC",
						},
					]}
					onChange={(order) => setAttributes({ order })}
				/>
				<TextControl
					label={sprintf(
						// translators: placeholder: Groups.
						_x("%s per page", "placeholder: Groups", "learndash"),
						ldlms_get_custom_label("groups")
					)}
					help={sprintf(
						// translators: placeholder: default per page.
						_x(
							"Leave empty for default (%d) or 0 to show all items.",
							"placeholder: default per page",
							"learndash"
						),
						ldlms_get_per_page("per_page")
					)}
					value={per_page || ""}
					type={"number"}
					onChange={function (new_per_page) {
						if (new_per_page != "" && new_per_page < 0) {
							setAttributes({ per_page: "0" });
						} else {
							setAttributes({ per_page: new_per_page });
						}
					}}
				/>

				<SelectControl
					multiple
					key="price_type"
					label={sprintf(
						// translators: placeholder: Group Access Mode(s).
						_x(
							"%s Access Mode(s)",
							"placeholder: Group Access Mode(s)",
							"learndash"
						),
						ldlms_get_custom_label("group")
					)}
					help={__(
							"Ctrl+click to deselect selected items.",
							"learndash"
					)}
					value={price_type}
					options={[
						{
							label: __("Free", "learndash"),
							value: "free",
						},
						{
							label: __("Buy Now", "learndash"),
							value: "paynow",
						},
						{
							label: __("Recurring", "learndash"),
							value: "subscribe",
						},
						{
							label: __("Closed", "learndash"),
							value: "closed",
						},
					]}
					onChange={(price_type) => setAttributes({ price_type })}
				/>

				<SelectControl
					key="mygroups"
					label={sprintf(
						// translators: placeholder: Groups.
						_x("My %s", "placeholder: Groups", "learndash"),
						ldlms_get_custom_label("groups")
					)}
					value={mygroups}
					options={[
						{
							label: sprintf(
								// translators: placeholder: Groups.
								_x("Show All %s (default)", "placeholder: Groups", "learndash"),
								ldlms_get_custom_label("groups")
							),
							value: "",
						},
						{
							label: sprintf(
								// translators: placeholder: Groups.
								_x("Show Enrolled %s only", "placeholder: Groups", "learndash"),
								ldlms_get_custom_label("groups")
							),
							value: "enrolled",
						},
						{
							label: sprintf(
								// translators: placeholder: Groups.
								_x(
									"Show not-Enrolled %s only",
									"placeholder: Groups",
									"learndash"
								),
								ldlms_get_custom_label("Groups")
							),
							value: "not-enrolled",
						},
					]}
					onChange={(mygroups) => setAttributes({ mygroups })}
				/>
				{"enrolled" === mygroups && (
					<SelectControl
						multiple
						key="status"
						label={sprintf(
							// translators: placeholder: Groups.
							_x("Enrolled %s Status", "placeholder: Groups", "learndash"),
							ldlms_get_custom_label("groups")
						)}
						value={status}
						options={[
							{
								label: __("Not Started", "learndash"),
								value: "not_started",
							},
							{
								label: __("In Progress", "learndash"),
								value: "in_progress",
							},
							{
								label: __("Completed", "learndash"),
								value: "completed",
							},
						]}
						onChange={(status) => setAttributes({ status })}
					/>
				)}
				{field_show_content}
				{field_show_thumbnail}
			</PanelBody>
		);

		let panel_group_category_section = "";
		if (
			ldlms_settings["settings"]["groups_taxonomies"]["ld_group_category"] ===
			"yes"
		) {
			let panel_group_category_section_open = false;
			if (group_category_name != "" || group_cat != "") {
				panel_group_category_section_open = true;
			}
			panel_group_category_section = (
				<PanelBody
				title={sprintf(
						// translators: placeholder: Group.
						_x("%s Category Settings", "placeholder: Group", "learndash"),
						ldlms_get_custom_label("group")
					)}
					initialOpen={panel_group_category_section_open}
				>
					<TextControl
						label={sprintf(
							// translators: placeholder: Group.
							_x("%s Category Slug", "placeholder: Group", "learndash"),
							ldlms_get_custom_label("group")
						)}
						help={sprintf(
							// translators: placeholder: Groups.
							_x(
								"shows %s with mentioned category slug.",
								"placeholder: Groups",
								"learndash"
							),
							ldlms_get_custom_label("groups")
						)}
						value={group_category_name || ""}
						onChange={(group_category_name) =>
							setAttributes({ group_category_name })
						}
					/>

					<TextControl
						label={sprintf(
							// translators: placeholder: Group.
							_x("%s Category ID", "placeholder: Group", "learndash"),
							ldlms_get_custom_label("group")
						)}
						help={sprintf(
							// translators: placeholder: Groups.
							_x(
								"shows %s with mentioned category ID.",
								"placeholder: Groups",
								"learndash"
							),
							ldlms_get_custom_label("groups")
						)}
						value={group_cat || ""}
						type={"number"}
						onChange={function (new_group_cat) {
							if (new_group_cat != "" && new_group_cat < 0) {
								setAttributes({ group_cat: "0" });
							} else {
								setAttributes({ group_cat: new_group_cat });
							}
						}}
					/>
					<ToggleControl
						label={sprintf(
							// translators: placeholder: Group.
							_x("%s Category Selector", "placeholder: Group", "learndash"),
							ldlms_get_custom_label("group")
						)}
						help={sprintf(
							// translators: placeholder: Groups.
							_x(
								"shows a %s category dropdown.",
								"placeholder: Groups",
								"learndash"
							),
							ldlms_get_custom_label("groups")
						)}
						checked={!!group_categoryselector}
						onChange={(group_categoryselector) =>
							setAttributes({ group_categoryselector })
						}
					/>
				</PanelBody>
			);
		}

		let panel_group_tag_section = "";
		if (
			ldlms_settings["settings"]["groups_taxonomies"]["ld_group_tag"] === "yes"
		) {
			let panel_group_tag_section_open = false;
			if (group_tag != "" || group_tag_id != "") {
				panel_group_tag_section_open = true;
			}
			panel_group_tag_section = (
				<PanelBody
				title={sprintf(
						// translators: placeholder: Group.
						_x("%s Tag Settings", "placeholder: Group", "learndash"),
						ldlms_get_custom_label("group")
					)}
					initialOpen={panel_group_tag_section_open}
				>
					<TextControl
						label={sprintf(
							// translators: placeholder: Group.
							_x("%s Tag Slug", "placeholder: Group", "learndash"),
							ldlms_get_custom_label("group")
						)}
						help={sprintf(
							// translators: placeholder: Groups.
							_x(
								"shows %s with mentioned tag slug.",
								"placeholder: Groups",
								"learndash"
							),
							ldlms_get_custom_label("groups")
						)}
						value={group_tag || ""}
						onChange={(group_tag) => setAttributes({ group_tag })}
					/>

					<TextControl
						label={sprintf(
							// translators: placeholder: Group.
							_x("%s Tag ID", "placeholder: Group", "learndash"),
							ldlms_get_custom_label("group")
						)}
						help={sprintf(
							// translators: placeholder: Groups.
							_x(
								"shows %s with mentioned tag ID.",
								"placeholder: Groups",
								"learndash"
							),
							ldlms_get_custom_label("groups")
						)}
						value={group_tag_id || ""}
						type={"number"}
						onChange={function (new_group_tag_id) {
							if (new_group_tag_id != "" && new_group_tag_id < 0) {
								setAttributes({ group_tag_id: "0" });
							} else {
								setAttributes({ group_tag_id: new_group_tag_id });
							}
						}}
					/>
				</PanelBody>
			);
		}

		let panel_wp_category_section = "";
		if (
			ldlms_settings["settings"]["groups_taxonomies"]["wp_post_category"] ===
			"yes"
		) {
			let panel_wp_category_section_open = false;
			if (category_name != "" || cat != "") {
				panel_wp_category_section_open = true;
			}
			panel_wp_category_section = (
				<PanelBody
					title={__("WP Category Settings", "learndash")}
					initialOpen={panel_wp_category_section_open}
				>
					<TextControl
						label={__("WP Category Slug", "learndash")}
						help={sprintf(
							// translators: placeholder: Groups.
							_x(
								"shows %s with mentioned WP Category slug.",
								"placeholder: Groups",
								"learndash"
							),
							ldlms_get_custom_label("groups")
						)}
						value={category_name || ""}
						onChange={(category_name) => setAttributes({ category_name })}
					/>

					<TextControl
						label={sprintf(
							// translators: placeholder: Group.
							_x("%s Category ID", "placeholder: Group", "learndash"),
							ldlms_get_custom_label("group")
						)}
						help={sprintf(
							// translators: placeholder: Groups.
							_x(
								"shows %s with mentioned category ID.",
								"placeholder: Groups",
								"learndash"
							),
							ldlms_get_custom_label("groups")
						)}
						value={cat || ""}
						type={"number"}
						onChange={function (new_cat) {
							if (new_cat != "" && new_cat < 0) {
								setAttributes({ cat: "0" });
							} else {
								setAttributes({ cat: new_cat });
							}
						}}
					/>
					<ToggleControl
						label={__("WP Category Selector", "learndash")}
						help={__("shows a WP category dropdown.", "learndash")}
						checked={!!categoryselector}
						onChange={(categoryselector) => setAttributes({ categoryselector })}
					/>
				</PanelBody>
			);
		}

		let panel_wp_tag_section = "";
		if (
			ldlms_settings["settings"]["groups_taxonomies"]["wp_post_tag"] === "yes"
		) {
			let panel_wp_tag_section_open = false;
			if (tag != "" || tag_id != "") {
				panel_wp_tag_section_open = true;
			}
			panel_wp_tag_section = (
				<PanelBody
					title={__("WP Tag Settings", "learndash")}
					initialOpen={panel_wp_tag_section_open}
				>
					<TextControl
						label={__("WP Tag Slug", "learndash")}
						help={sprintf(
							// translators: placeholder: Groups.
							_x(
								"shows %s with mentioned WP tag slug.",
								"placeholder: Groups",
								"learndash"
							),
							ldlms_get_custom_label("groups")
						)}
						value={tag || ""}
						onChange={(tag) => setAttributes({ tag })}
					/>

					<TextControl
						label={__("WP Tag ID", "learndash")}
						help={sprintf(
							// translators: placeholder: Groups.
							_x(
								"shows %s with mentioned WP tag ID.",
								"placeholder: Groups",
								"learndash"
							),
							ldlms_get_custom_label("groups")
						)}
						value={tag_id || ""}
						type={"number"}
						onChange={function (new_tag_id) {
							if (new_tag_id != "" && new_tag_id < 0) {
								setAttributes({ tag_id: "0" });
							} else {
								setAttributes({ tag_id: new_tag_id });
							}
						}}
					/>
				</PanelBody>
			);
		}

		const panel_preview = (
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
		);

		const inspectorControls = (
			<InspectorControls key="controls">
				{panel_groups_not_public}
				{panelbody_header}
				{panel_course_grid_section}
				{panel_group_category_section}
				{panel_group_tag_section}
				{panel_wp_category_section}
				{panel_wp_tag_section}
				{panel_preview}
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
