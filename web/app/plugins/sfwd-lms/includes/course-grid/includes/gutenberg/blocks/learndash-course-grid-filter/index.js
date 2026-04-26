/**
 * LearnDash Block ld-course-grid-filter
 *
 * @since 4.21.4
 */

/**
 * Internal block libraries
 */
// eslint-disable-next-line no-unused-vars
import { __, _x, sprintf } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import {
	InspectorControls,
	// eslint-disable-next-line no-unused-vars
	InspectorAdvancedControls,
	useBlockProps,
} from '@wordpress/block-editor';
import { Fragment } from '@wordpress/element';
import {
	Panel,
	PanelBody,
	// eslint-disable-next-line no-unused-vars
	TextControl,
	ToggleControl,
	// eslint-disable-next-line no-unused-vars
	SelectControl,
	// eslint-disable-next-line no-unused-vars
	ColorPalette,
	// eslint-disable-next-line no-unused-vars
	ColorIndicator,
	// eslint-disable-next-line no-unused-vars
	BaseControl,
	Disabled,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import FilterPanelBody from '../components/filter-panel-body.js';

registerBlockType( 'learndash/ld-course-grid-filter', {
	title: __( 'LearnDash Course Grid Filter', 'learndash' ),
	description: __( 'LearnDash course grid filter widget.', 'learndash' ),
	icon: 'filter',
	category: 'learndash-blocks',
	supports: {
		customClassName: false,
	},
	apiVersion: 3,
	attributes: {
		course_grid_id: {
			type: 'string',
			default: '',
		},
		search: {
			type: 'boolean',
			default: 1,
		},
		taxonomies: {
			type: 'array',
			default: [ 'category', 'post_tag' ],
		},
		price: {
			type: 'boolean',
			default: 1,
		},
		price_min: {
			type: 'string',
			default: 0,
		},
		price_max: {
			type: 'string',
			default: 1000,
		},
		preview_show: {
			type: 'boolean',
			default: 1,
		},
	},

	edit: ( props ) => {
		const {
			attributes: {
				// eslint-disable-next-line camelcase
				course_grid_id,
				search,
				taxonomies,
				price,
				// eslint-disable-next-line camelcase
				price_min,
				// eslint-disable-next-line camelcase
				price_max,
				// eslint-disable-next-line camelcase
				preview_show,
			},
			setAttributes,
		} = props;

		const blockProps = useBlockProps();

		// eslint-disable-next-line camelcase, no-unused-vars
		const taxonomies_options =
			// eslint-disable-next-line camelcase, no-undef
			LearnDash_Course_Grid_Block_Editor.taxonomies;

		const inspectorControls = (
			<Fragment key={ 'learndash-course-grid-filter-settings' }>
				<InspectorControls key="controls">
					<Panel className={ 'learndash-course-grid-filter-panel' }>
						<FilterPanelBody
							context={ 'widget' }
							// eslint-disable-next-line camelcase
							course_grid_id={ course_grid_id }
							search={ search }
							taxonomies={ taxonomies }
							price={ price }
							// eslint-disable-next-line camelcase
							price_min={ price_min }
							// eslint-disable-next-line camelcase
							price_max={ price_max }
							setAttributes={ setAttributes }
						/>
						<PanelBody
							title={ __( 'Preview', 'learndash' ) }
							initialOpen={ false }
						>
							<ToggleControl
								label={ __( 'Show Preview', 'learndash' ) }
								// eslint-disable-next-line camelcase
								checked={ !! preview_show }
								// eslint-disable-next-line camelcase, no-shadow
								onChange={ ( preview_show ) =>
									// eslint-disable-next-line camelcase
									setAttributes( { preview_show } )
								}
							/>
						</PanelBody>
					</Panel>
				</InspectorControls>
			</Fragment>
		);

		// eslint-disable-next-line camelcase
		function do_serverside_render( attributes ) {
			// eslint-disable-next-line eqeqeq
			if ( attributes.preview_show == true ) {
				// We add the meta so the server knowns what is being edited.
				// attributes.meta = ldlms_get_post_edit_meta()

				return (
					<ServerSideRender
						block="learndash/ld-course-grid-filter"
						attributes={ attributes }
						key="learndash/ld-course-grid-filter"
					/>
				);
			}
			return __(
				'[learndash_course_grid_filter] shortcode output shown here',
				'learndash'
			);
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

	// eslint-disable-next-line no-unused-vars
	save: ( props ) => {},
} );
