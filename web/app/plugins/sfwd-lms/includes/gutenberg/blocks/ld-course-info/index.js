/**
 * LearnDash Block ld-course-info
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
	ldlms_get_per_page
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

const block_key   = 'learndash/ld-course-info';
const block_title = sprintf(
	// translators: placeholder: Course.
	_x('LearnDash %s Info [ld_course_info]', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course')
);

registerBlockType(
	block_key,
	{
		title: block_title,
		description: sprintf(
			// translators: placeholder: Courses.
			_x('This block shows the %s and progress for the user.', 'placeholder: Courses', 'learndash'), ldlms_get_custom_label('course') ),
		icon: 'analytics',
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
				default: 0,
			},
			registered_show: {
				type: 'boolean',
				//default: true
			},
			registered_show_thumbnail: {
				type: 'boolean',
				default: true
			},
			registered_num: {
				type: 'string',
				default: '',
			},
			registered_orderby: {
				type: 'string',
				default: 'title'
			},
			registered_order: {
				type: 'string',
				default: 'ASC'
			},
			progress_show: {
				type: 'boolean',
				//default: true
			},
			progress_num: {
				type: 'string',
				default: '',
			},
			progress_orderby: {
				type: 'string',
				default: 'title'
			},
			progress_order: {
				type: 'string',
				default: 'ASC'
			},
			quiz_show: {
				type: 'boolean',
				//default: true
			},
			quiz_num: {
				type: 'string',
				default: '',
			},
			quiz_orderby: {
				type: 'string',
				default: 'taken'
			},
			quiz_order: {
				type: 'string',
				default: 'DESC'
			},
			preview_show: {
				type: 'boolean',
				default: true
			},
			preview_user_id: {
				type: 'string',
				default: '',
			},
			example_show: {
				type: 'boolean',
				default: 0
			},
			editing_post_meta: {
				type: 'object',
			}
		},
		edit: function( props ) {
			const { attributes: { user_id, registered_show, registered_show_thumbnail, registered_num, registered_orderby, registered_order, progress_show, progress_num, progress_orderby, progress_order, quiz_show, quiz_num, quiz_orderby, quiz_order, preview_user_id, preview_show },
				setAttributes } = props;

			if ( typeof registered_show === "undefined" ) {
				setAttributes({ registered_show: true });
			}
			if ( typeof progress_show === "undefined" ) {
				setAttributes({ progress_show: true });
			}
			if ( typeof quiz_show === "undefined" ) {
				setAttributes({ quiz_show: true });
			}

			if ( ( registered_show === false ) && ( progress_show === false ) && ( quiz_show === false ) ) {
				setAttributes({ registered_show: true });
				setAttributes({ progress_show: true });
				setAttributes({ quiz_show: true });
			}

			const panelbody_header = (
				<PanelBody
					title={__('Settings', 'learndash')}
				>
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

					<ToggleControl
						label={sprintf(
							// translators: placeholder: Courses.
							_x('Show Registered %s', 'placeholder: Courses', 'learndash'), ldlms_get_custom_label('courses') ) }
						checked={!!registered_show}
						onChange={registered_show => setAttributes({ registered_show })}
					/>
					<ToggleControl
						label={sprintf(
							// translators: placeholder: Course.
							_x('Show %s Progress', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course') ) }
						checked={!!progress_show}
						onChange={progress_show => setAttributes({ progress_show })}
					/>
					<ToggleControl
						label={sprintf(
							// translators: placeholder: Quiz.
							_x('Show %s Attempts', 'placeholder: Quiz', 'learndash'), ldlms_get_custom_label('quiz'))}
						checked={!!quiz_show}
						onChange={quiz_show => setAttributes({ quiz_show })}
					/>
				</PanelBody>
			);

			var panelbody_registered = ('');
			if ( registered_show === true ) {
				panelbody_registered = (
					<PanelBody
					title={sprintf(
							// translators: placeholder: Courses.
							_x('Registered %s', 'placeholder: Courses', 'learndash'), ldlms_get_custom_label('courses') ) }
						initialOpen={false}
					>
						<ToggleControl
							label={__('Show Thumbnail', 'learndash')}
							checked={!!registered_show_thumbnail}
							onChange={registered_show_thumbnail => setAttributes({ registered_show_thumbnail })}
						/>
						<TextControl
							label={__('per page', 'learndash')}
							help={sprintf(
								// translators: placeholder: per_page.
								_x('Leave empty for default (%d) or 0 to show all items.', 'placeholder: per_page', 'learndash'), ldlms_get_per_page('per_page'))}
							value={registered_num || ''}
							min={0}
							max={100}
							type={'number'}
							onChange={ function( new_registered_num ) {
								if ( new_registered_num != "" && new_registered_num < 0 ) {
									setAttributes({ registered_num: "0" });
								} else {
									setAttributes({ registered_num: new_registered_num });
								}
							}}						/>
						<SelectControl
							key="registered_orderby"
							label={__('Order by', 'learndash')}
							value={registered_orderby}
							options={[
								{
									label: __('Title - Order by post title (default)', 'learndash'),
									value: 'title',
								},
								{
									label: __('ID - Order by post id', 'learndash'),
									value: 'ID',
								},
								{
									label: __('Date - Order by post date', 'learndash'),
									value: 'date',
								},
								{
									label: __('Menu - Order by Page Order Value', 'learndash'),
									value: 'menu_order',
								}
							]}
							onChange={registered_orderby => setAttributes({ registered_orderby })}
						/>
						<SelectControl
							key="registered_order"
							label={__('Order', 'learndash')}
							value={registered_order}
							options={[
								{
									label: __('ASC - lowest to highest values (default)', 'learndash'),
									value: 'ASC',
								},
								{
									label: __('DESC - highest to lowest values', 'learndash'),
									value: 'DESC',
								},
							]}
							onChange={registered_order => setAttributes({ registered_order })}
						/>
					</PanelBody>
				)
			}

			var panelbody_progress = ('');
			if (progress_show === true) {
				panelbody_progress = (
					<PanelBody
					title={sprintf(
							// translators: placeholder: Course.
							_x('%s Progress', 'placeholder: Course', 'learndash'), ldlms_get_custom_label('course') ) }
						initialOpen={false}
					>
						<TextControl
							label={__('per page', 'learndash')}
							help={sprintf(
								// translators: placeholder: progress_num.
								_x('Leave empty for default (%d) or 0 to show all items.', 'placeholder: progress_num', 'learndash'), ldlms_get_per_page('progress_num' ) ) }
							value={progress_num || ''}
							min={0}
							max={100}
							type={'number'}
							onChange={ function( new_progress_num ) {
								if ( new_progress_num != "" && new_progress_num < 0 ) {
									setAttributes({ progress_num: "0" });
								} else {
									setAttributes({ progress_num: new_progress_num });
								}
							}}						/>
						<SelectControl
							key="progress_orderby"
							label={__('Order by', 'learndash')}
							value={progress_orderby}
							options={[
								{
									label: __('Title - Order by post title (default)', 'learndash'),
									value: 'title',
								},
								{
									label: __('ID - Order by post id', 'learndash'),
									value: 'ID',
								},
								{
									label: __('Date - Order by post date', 'learndash'),
									value: 'date',
								},
								{
									label: __('Menu - Order by Page Order Value', 'learndash'),
									value: 'menu_order',
								}
							]}
							onChange={progress_orderby => setAttributes({ progress_orderby })}
						/>
						<SelectControl
							key="progress_order"
							label={__('Order', 'learndash')}
							value={progress_order}
							options={[
								{
									label: __('ASC - lowest to highest values (default)', 'learndash'),
									value: 'ASC',
								},
								{
									label: __('DESC - highest to lowest values', 'learndash'),
									value: 'DESC',
								},
							]}
							onChange={progress_order => setAttributes({ progress_order })}
						/>
					</PanelBody>
				);
			}

			var panelbody_quiz = ('');
			if ( quiz_show === true ) {
				panelbody_quiz = (
					<PanelBody
					title={sprintf(
							// translators: placeholder: Quiz.
							_x('%s Attempts', 'placeholder: Quiz', 'learndash'), ldlms_get_custom_label('quiz') ) }
						initialOpen={false}
					>
						<TextControl
							label={__('per page', 'learndash')}
							help={sprintf(
								// translators: placeholder: quiz_num.
								_x('Leave empty for default (%d) or 0 to show all items.', 'placeholder: quiz_num', 'learndash'), ldlms_get_per_page('quiz_num') ) }
							value={quiz_num || ''}
							min={0}
							max={100}
							type={'number'}
							onChange={ function( new_quiz_num ) {
								if ( new_quiz_num != "" && new_quiz_num < 0 ) {
									setAttributes({ quiz_num: "0" });
								} else {
									setAttributes({ quiz_num: new_quiz_num });
								}
							}}						/>
						<SelectControl
							key="quiz_orderby"
							label={__('Order by', 'learndash')}
							value={quiz_orderby}
							options={[
								{
									label: __('Date Taken (default) - Order by date taken', 'learndash'),
									value: 'taken',
								},
								{
									label: __('Title - Order by post title', 'learndash'),
									value: 'title',
								},
								{
									label: __('ID - Order by post id. (default)', 'learndash'),
									value: 'ID',
								},								{
									label: __('Date - Order by post date', 'learndash'),
									value: 'date',
								},
								{
									label: __('Menu - Order by Page Order Value', 'learndash'),
									value: 'menu_order',
								}
							]}
							onChange={quiz_orderby => setAttributes({ quiz_orderby })}
						/>
						<SelectControl
							key="quiz_order"
							label={__('Order', 'learndash')}
							value={quiz_order}
							options={[
								{
									label: __('DESC - highest to lowest values (default)', 'learndash'),
									value: 'DESC',
								},
								{
									label: __('ASC - lowest to highest values', 'learndash'),
									value: 'ASC',
								},
							]}
							onChange={quiz_order => setAttributes({ quiz_order })}
						/>
					</PanelBody>
				);
			}

			const inspectorControls = (
				<InspectorControls key="controls">
					{panelbody_header}
					{panelbody_registered}
					{panelbody_progress}
					{panelbody_quiz}

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
