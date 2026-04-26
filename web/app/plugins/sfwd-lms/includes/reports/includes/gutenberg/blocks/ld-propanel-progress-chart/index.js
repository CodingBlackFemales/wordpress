/* eslint-disable -- TODO: Fix linting issues */

/**
 * LearnDash ProPanel progress-chart Block
 *
 * @since 4.17.0
 *
 * @package LearnDash
 */

/**
 * ProPanel block functions
 */

/**
 * Internal block libraries
 */

import './index.scss';

const { __, _x, sprintf } = wp.i18n;
const {
	registerBlockType,
} = wp.blocks;

const {
	useBlockProps,
	InspectorControls
} = wp.blockEditor;

const {
	PanelBody,
	ToggleControl,
	Disabled,
	SelectControl,
	__experimentalNumberControl,
} = wp.components;

const NumberControl = __experimentalNumberControl;

import { PostDropdown } from '../lib/post-dropdown';
import { UserDropdown } from '../lib/user-dropdown';

import ProgressChart from '../lib/progress-chart-render';

const title = _x('LearnDash Progress Chart', 'learndash');

registerBlockType(
	'ld-propanel/ld-propanel-progress-chart',
	{
		title: title,
		description: __('Displays user progress; not started, in progress, and categories of percent complete.', 'learndash'),
		icon: 'chart-pie',
		category: 'ld-propanel-blocks',
		keywords: [ 'progress', 'chart' ],
		supports: {
			customClassName: false,
		},
		apiVersion: 3,
		attributes: {
			preview_show: {
				type: 'boolean',
				default: true
			},
			filter_groups: {
				type: 'int',
				default: 0
			},
			filter_courses: {
				type: 'int',
				default: 0
			},
			filter_users: {
				type: 'int',
				default: 0
			},
			filter_status: {
				type: 'string',
				default: ''
			},
			display_chart: {
				type: 'string',
				default: ''
			},
		},
		example: {
			attributes: {
				preview_show: true,
				filter_groups: 0,
				filter_courses: 0,
				filter_users: 0,
				filter_status: '',
				display_chart: ''
			},
		},
		edit: function( props ) {
			const {
				attributes: {
					preview_show,
					filter_groups,
					filter_courses,
					filter_users,
					filter_status,
					display_chart,
				},
				setAttributes,
				clientId
			} = props;
			const blockProps = useBlockProps();

			const panel_settings = (

				<PanelBody
					title={ __( 'Settings', 'learndash' ) }
					initialOpen={true}
				>

					<PostDropdown
						key="filter_groups"
						label={
							sprintf(
								// translators: placeholder: Filter Courses.
								__(
									'Filter %s',
									'learndash'
								),
								learndash.customLabel.get( 'groups' )
							)
						}
						placeholder={
							sprintf(
								// translators: placeholder: Type to search for a Course...
								__(
									'Type to search for a %s...',
									'learndash'
								),
								learndash.customLabel.get( 'group' )
							)
						}
						additional={
							{
								postType: 'groups',
							}
						}
						value={ filter_groups || '' }
						onChange={ ( filter_groups ) => setAttributes( { filter_groups } ) }
					/>

					<PostDropdown
						key="filter_courses"
						label={
							sprintf(
								// translators: placeholder: Filter Courses.
								__(
									'Filter %s',
									'learndash'
								),
								learndash.customLabel.get( 'courses' )
							)
						}
						placeholder={
							sprintf(
								// translators: placeholder: Type to search for a Course...
								__(
									'Type to search for a %s...',
									'learndash'
								),
								learndash.customLabel.get( 'course' )
							)
						}
						additional={
							{
								postType: 'sfwd-courses',
							}
						}
						value={ filter_courses || '' }
						onChange={ ( filter_courses ) => setAttributes( { filter_courses } ) }
					/>

					<UserDropdown
						key="filter_users"
						label={ __( 'Filter Users', 'learndash' ) }
						placeholder={ __( 'Type to search for a User...', 'learndash' ) }
						value={ filter_users || '' }
						onChange={ ( filter_users ) => setAttributes( { filter_users } ) }
					/>

					<SelectControl
						key='filter_status'
						label={
							sprintf(
								// translators: placeholder: Filter Course Status.
								__(
									'Filter %s Status',
									'learndash'
								),
								learndash.customLabel.get( 'course' )
							)
						}
						value={filter_status}
						onChange={ ( filter_status ) => setAttributes( { filter_status } ) }
						options={ [
							{
								label: __( 'All Statuses', 'learndash' ),
								value: '',
							},
							{
								label: __( 'Not Started', 'learndash' ),
								value: 'not-started',
							},
							{
								label: __( 'In Progress', 'learndash' ),
								value: 'in-progress',
							},
							{
								label: __( 'Completed', 'learndash' ),
								value: 'completed',
							},
						] }
					/>

					<SelectControl
						key='display_chart'
						label={ __( 'Display Chart', 'learndash' ) }
						value={display_chart}
						onChange={ ( display_chart ) => setAttributes( { display_chart } ) }
						options={ [
							{
								label: __( 'Stacked', 'learndash' ),
								value: '',
							},
							{
								label: __( 'Side by Side', 'learndash' ),
								value: 'side-by-side',
							},
						] }
					/>

				</PanelBody>

			);

			const panel_preview = (
				<PanelBody
					title={__('Preview', 'learndash')}
					initialOpen={false}
				>
					<ToggleControl
						label={__('Show Preview', 'learndash')}
						checked={!!preview_show}
						onChange={preview_show => setAttributes({ preview_show })}
					/>
				</PanelBody>
			);

			const inspectorControls = (
				<InspectorControls>
					{ panel_settings }
					{ panel_preview }
				</InspectorControls>
			);

			function do_serverside_render( attributes ) {
				if ( attributes.preview_show == true ) {
					let template = 'progress-chart-data',
						filters = {
							type: 'course',
							id: '',
							courseStatus: attributes.filter_status,
							groups: attributes.filter_groups || '',
							courses: attributes.filter_courses || '',
							users: attributes.filter_users || '',
						};

					if ( filters.groups ) {
						filters.type = 'group';
						filters.id = filters.groups;
					} else if ( filters.courses ) {
						filters.id = filters.courses;
					} else if ( filters.users ) {
						filters.type = 'user';
						filters.id = filters.users;
					}

					let className = 'ld-propanel-widget ld-propanel-widget-progress-chart';

					if ( display_chart ) {
						className += ' ' + display_chart;
					}

					// Remove display_chart so that it doesn't trigger a refresh when changed.
					const passedAttributes = Object.fromEntries(
						Object.entries(
							attributes
						).filter( ( [ key, value ] ) => key !== 'display_chart' )
					);

					return (
						<div className={ 'learndash-block-inner' }>
							<div data-ld-widget-type={ 'progress-chart' } className={ className }>
								<ProgressChart
									block="ld-propanel/ld-propanel-progress-chart"
									attributes={
										// Pass Client ID through so that we can target the correct element to draw our Charts on.
										Object.assign(
											{
												clientId: clientId
											},
											passedAttributes
										)
									}
									// GET is the default, but just to help ensure future-proofing
									httpMethod='GET'
									urlQueryArgs={
										// Pass attributes through to the GET request at the top-level to better re-use the existing Ajax logic for Shortcodes
										Object.assign(
											{
												template: template,
												filters: filters,
												container_type: 'shortcode',
												nonce: ld_propanel_settings.nonce
											},
											passedAttributes
					 					)
									}
								/>
							</div>
						</div>
					);

				} else {
					// translators: %s is the title for the Block.
					return __( 'Toggle the Preview setting in the sidebar to see the %s in the editor.', 'learndash' ).replace( '%s', title );
				}
			}

			return (
				<div { ...blockProps }>
					{ inspectorControls }
					<Disabled>
						{ do_serverside_render( props.attributes ) }
					</Disabled>
				</div>
			);
		},
		save: props => {
			// Delete meta from props to prevent it being saved.
			delete (props.attributes.meta);
		}
	},
);
