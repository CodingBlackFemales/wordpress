/**
 * LearnDash Block ld-courseinfo
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
import { __, _x, sprintf} from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl, ToggleControl, PanelRow } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { useMemo } from "@wordpress/element";

const block_key   = 'learndash/ld-courseinfo';
const block_title = sprintf(
	// translators: placeholder: Course.
	_x( 'LearnDash %s Info [courseinfo]', 'placeholder: Course', 'learndash' ), ldlms_get_custom_label( 'course' )
);
registerBlockType(
	block_key,
	{
		title: block_title,
		description: sprintf(
			// translators: placeholder: Course.
			_x('This block displays %s related information', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course') ),
		icon: 'analytics',
		category: 'learndash-blocks',
		supports: {
			customClassName: false,
		},
		attributes: {
			show: {
				type: 'string',
			},
			course_id: {
				type: 'string',
				default: '',
			},
			user_id: {
				type: 'string',
				default: '',
			},
			format: {
				type: 'string',
			},
			seconds_format: {
				type: 'string',
			},
			decimals: {
				type: 'string',
			},
			preview_show: {
				type: 'boolean',
				default: 1
			},
			preview_user_id: {
				type: 'string',
				default: '',
			},
			editing_post_meta: {
				type: 'object'
			}
		},
		edit: props => {
			const { attributes: { course_id, show, user_id, format, seconds_format, decimals, preview_show, preview_user_id },
				className, setAttributes } = props;

			const field_show = (
				<SelectControl
					key="show"
					value={show || 'course_title'}
					label={__('Show', 'learndash')}
					options={[
						{
							label: sprintf(
								// translators: placeholder: Course.
								_x('%s Title', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course')),
							value: 'course_title',
						},
						{
							label: sprintf(
								// translators: placeholder: Course.
								_x('%s URL', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course')),
							value: 'course_url',
						},
						{
							label: sprintf(
								// translators: placeholder: Course.
								_x('%s Points', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course')),
							value: 'course_points',
						},
						{
							label: sprintf(
								// translators: placeholder: Course.
								_x('%s Price', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course')),
							value: 'course_price',
						},
						{
							label: sprintf(
								// translators: placeholder: Course.
								_x('%s Price Type', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course')),
							value: 'course_price_type',
						},
						{
							label: sprintf(
								// translators: placeholder: Course.
								_x('%s Enrolled Users Count', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course')),
							value: 'course_users_count',
						},
						{
							label: sprintf(
								// translators: placeholder: Course.
								_x('Total User %s Points', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course')),
							value: 'user_course_points',
						},
						{
							label: sprintf(
								// translators: placeholder: Course.
								_x('Total User %s Time', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course')),
							value: 'user_course_time',
						},
						{
							label: sprintf(
								// translators: placeholder: Course.
								_x('%s Completed On (date)', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course')),
							value: 'completed_on',
						},
						{
							label: sprintf(
								// translators: placeholder: Course.
								_x('%s Enrolled On (date)', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course')),
							value: 'enrolled_on',
						},
						{
							label: sprintf(
								// translators: placeholder: Quizzes.
								_x('Cumulative %s Score', 'placeholder: Quizzes', 'learndash'), ldlms_get_custom_label('quizzes')),
							value: 'cumulative_score',
						},
						{
							label: sprintf(
								// translators: placeholder: Quizzes.
								_x('Cumulative %s Points', 'placeholder: Quizzes', 'learndash'), ldlms_get_custom_label('quizzes')),
							value: 'cumulative_points',
						},
						{
							label: sprintf(
								// translators: placeholder: Quizzes.
								_x('Possible Cumulative %s Total Points', 'placeholder: Quizzes', 'learndash'), ldlms_get_custom_label('quizzes')),
							value: 'cumulative_total_points',
						},
						{
							label: sprintf(
								// translators: placeholder: Quizzes.
								_x('Cumulative %s Percentage', 'placeholder: Quizzes', 'learndash'), ldlms_get_custom_label('quizzes')),
							value: 'cumulative_percentage',
						},
						{
							label: sprintf(
								// translators: placeholder: Quizzes.
								_x('Cumulative %s Time Spent', 'placeholder: Quizzes', 'learndash'), ldlms_get_custom_label('quizzes')),
							value: 'cumulative_timespent',
						},
						{
							label: sprintf(
								// translators: placeholder: Quizzes.
								_x('Aggregate %s Percentage', 'placeholder: Quizzes', 'learndash'), ldlms_get_custom_label('quizzes')),
							value: 'aggregate_percentage',
						},
						{
							label: sprintf(
								// translators: placeholder: Quizzes.
								_x('Aggregate %s Score', 'placeholder: Quizzes', 'learndash'), ldlms_get_custom_label('quizzes')),
							value: 'aggregate_score',
						},
						{
							label: sprintf(
								// translators: placeholder: Quizzes.
								_x('Aggregate %s Points', 'placeholder: Quizzes', 'learndash'), ldlms_get_custom_label('quizzes')),
							value: 'aggregate_points',
						},
						{
							label: sprintf(
								// translators: placeholder: Quizzes.
								_x('Possible Aggregate %s Total Points', 'placeholder: Quizzes', 'learndash'), ldlms_get_custom_label('quizzes')),
							value: 'aggregate_total_points',
						},
						{
							label: sprintf(
								// translators: placeholder: Quizzes.
								_x('Aggregate %s Time Spent', 'placeholder: Quizzes', 'learndash'), ldlms_get_custom_label('quizzes')),
							value: 'aggregate_timespent',
						},
					]}
					onChange={show => setAttributes({ show })}
				/>
			);

			const field_course_id = (
				<TextControl
					label={sprintf(
						// translators: placeholder: Course.
						_x('%s ID', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course'))}
					help={sprintf(
						// translators: placeholders: Course, Course.
						_x('Enter single %1$s ID. Leave blank if used within a %2$s or certificate.', 'placeholders: Course, Course', 'learndash'), ldlms_get_custom_label('course'), ldlms_get_custom_label('course'))}
					value={course_id || ''}
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

			let field_user_id = '';

			if ( ['user_course_points', 'user_course_time', 'completed_on', 'enrolled_on', 'cumulative_score', 'cumulative_points', 'cumulative_total_points', 'cumulative_percentage', 'cumulative_timespent', 'aggregate_percentage', 'aggregate_score', 'aggregate_points', 'aggregate_total_points', 'aggregate_timespent'].includes(show) ) {
				field_user_id = (
					<TextControl
						label={__('User ID', 'learndash')}
						help={__('Enter specific User ID. Leave blank for current User.', 'learndash')}
						value={user_id || ''}
						type={'number'}
						onChange={ function( new_user_id ) {
							if ( new_user_id != "" && new_user_id < 0 ) {
								setAttributes({ user_id: "0" });
							} else {
								setAttributes({ user_id: new_user_id });
							}
						}}					/>
				);
			}

			let field_format = '';
			if ( (show == 'completed_on') || (show == 'enrolled_on') ) {
				field_format = (
					<TextControl
						label={__('Format', 'learndash')}
						help={__('This can be used to change the date format. Default: "F j, Y, g:i a.', 'learndash')}
						value={format || ''}
						onChange={format => setAttributes({ format })}
					/>
				);
			}

			let field_seconds_format = '';
			if (show == 'user_course_time') {
				field_seconds_format = (
					<SelectControl
						key="seconds_format"
						value={seconds_format}
						label={__('Seconds Format', 'learndash')}
						options={[
							{
								label: __('Time - 20min 49sec', 'learndash'),
								value: 'time',
							},
							{
								label: __('Seconds - 1436', 'learndash'),
								value: 'seconds',
							},
						]}
						onChange={seconds_format => setAttributes({ seconds_format })}
					/>
				);
			}

			let field_decimals = '';
			if ( (show == 'course_points') || (show == 'user_course_points') ) {
				field_decimals = (
					<TextControl
						label={__('Decimals', 'learndash')}
						help={__('Number of decimal places to show. Default is 2.', 'learndash')}
						value={decimals || ''}
						type={'number'}
						onChange={ function( new_decimals ) {
							if ( new_decimals != "" && new_decimals < 0 ) {
								setAttributes({ decimals: "0" });
							} else {
								setAttributes({ decimals: new_decimals });
							}
						}}					/>
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
					<PanelBody
						title={ __( 'Settings', 'learndash' ) }
					>
						{ field_course_id }
						{ field_user_id }
						{ field_show }
						{ field_format }
						{field_seconds_format}
						{ field_decimals }
					</PanelBody>
					{ panel_preview }
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
		save: function (props) {
			delete (props.attributes.example_show);
			delete(props.attributes.editing_post_meta);
		}
	},
);
