/**
 * LearnDash Block ld-profile
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
import { __, _x, sprintf } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl, ToggleControl, PanelRow } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { useMemo } from "@wordpress/element";

const block_key   = 'learndash/ld-profile';
const block_title = __( 'LearnDash Profile', 'learndash' );

registerBlockType(
	block_key,
	{
		title: block_title,
		description: sprintf(
			// translators: placeholders: Courses, Course, Quiz.
			_x("Displays user's enrolled %1$s, %2$s progress, %3$s scores, and achieved certificates.", 'placeholders: Courses, Course, Quiz', 'learndash'), ldlms_get_custom_label('courses'), ldlms_get_custom_label('course'), ldlms_get_custom_label('quiz') ),
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
			per_page: {
				type: 'string',
				default: '',
			},
			orderby: {
				type: 'string',
				default: 'ID'
			},
			order: {
				type: 'string',
				default: 'DESC'
			},
			course_points_user: {
				type: 'boolean',
				default: 1
			},
			expand_all: {
				type: 'boolean',
				default: 0
			},
			profile_link: {
				type: 'boolean',
				default: 1
			},
			show_header: {
				type: 'boolean',
				default: 1
			},
			show_search: {
				type: 'boolean',
				default: 1
			},
			show_quizzes: {
				type: 'boolean',
				default: 1
			},
			preview_show: {
				type: 'boolean',
				default: 1
			},
			preview_user_id: {
				type: 'string',
				default: '',
			},
			example_show: {
				type: 'boolean',
				default: 0
			},
			quiz_num: {
				type: 'string',
				default: '',
			},
			editing_post_meta: {
				type: 'object',
			}
		},
		edit: function( props ) {
			const { attributes: { per_page, orderby, order, course_points_user, expand_all, profile_link, show_header, show_search, show_quizzes, preview_user_id, preview_show, quiz_num, example_show },
				setAttributes } = props;

			const inspectorControls = (
				<InspectorControls key="controls">
					<PanelBody title={__("Settings", "learndash")}>
						<TextControl
							label={sprintf(
								// translators: placeholder: Courses.
								_x("%s per page", "placeholder: Courses", "learndash"),
								ldlms_get_custom_label("courses")
							)}
							help={sprintf(
								// translators: placeholder: per_page.
								_x(
									"Leave empty for default (%d) or 0 to show all items.",
									"placeholder: per_page",
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
						<TextControl
							label={sprintf(
								// translators: placeholders: Quiz, Course.
								_x(
									"%1$s attempts per %2$s",
									"placeholders: Quiz, Course",
									"learndash"
								),
								ldlms_get_custom_label("quiz"),
								ldlms_get_custom_label("course")
							)}
							help={sprintf(
								// translators: placeholder: per_page.
								_x(
									"Leave empty for default (%d) or 0 to show all attempts.",
									"placeholder: per_page",
									"learndash"
								),
								ldlms_get_per_page("per_page")
							)}
							value={quiz_num || ""}
							type={"number"}
							onChange={function (new_quiz_num) {
								if (new_quiz_num != "" && new_quiz_num < 0) {
									setAttributes({ quiz_num: "0" });
								} else {
									setAttributes({ quiz_num: new_quiz_num });
								}
							}}
						/>
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
						<ToggleControl
							label={__("Show Search", "learndash")}
							checked={!!show_search}
							onChange={(show_search) => setAttributes({ show_search })}
							help={__("LD30 template only", "learndash")}
						/>
						<ToggleControl
							label={__("Show Profile Header", "learndash")}
							checked={!!show_header}
							onChange={(show_header) => setAttributes({ show_header })}
						/>
						<ToggleControl
							label={sprintf(
								// translators: placeholder: Course.
								_x("Show Earned %s Points", "placeholder: Course", "learndash"),
								ldlms_get_custom_label("course")
							)}
							checked={!!course_points_user}
							onChange={(course_points_user) =>
								setAttributes({ course_points_user })
							}
						/>
						<ToggleControl
							label={__("Show Profile Link", "learndash")}
							checked={!!profile_link}
							onChange={(profile_link) => setAttributes({ profile_link })}
						/>
						<ToggleControl
							label={sprintf(
								// translators: placeholder: Quiz.
								_x("Show User %s Attempts", "placeholder: Quiz", "learndash"),
								ldlms_get_custom_label("quiz")
							)}
							checked={!!show_quizzes}
							onChange={(show_quizzes) => setAttributes({ show_quizzes })}
						/>
						<ToggleControl
							label={sprintf(
								// translators: placeholder: Course.
								_x(
									"Expand All %s Sections",
									"placeholder: Course",
									"learndash"
								),
								ldlms_get_custom_label("course")
							)}
							checked={!!expand_all}
							onChange={(expand_all) => setAttributes({ expand_all })}
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
			delete (props.attributes.editing_post_meta);
		}
	},
);
