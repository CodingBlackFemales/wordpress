/**
 * LearnDash Block ld-course-grid
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
	InspectorAdvancedControls,
	useBlockProps,
} from '@wordpress/block-editor';
import { Fragment } from '@wordpress/element';
import {
	Panel,
	PanelBody,
	TextControl,
	ToggleControl,
	SelectControl,
	// eslint-disable-next-line no-unused-vars
	Button,
	BaseControl,
	Disabled,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import FilterPanelBody from '../components/filter-panel-body.js';
import ColorPalette from '../components/color-palette.js';
import { select } from '@wordpress/data';

// eslint-disable-next-line camelcase
function generate_unique_id() {
	const id =
		Date.now().toString( 36 ) + Math.random().toString( 36 ).substr( 2 );
	return 'ld-cg-' + id.substr( 0, '10' );
}

// eslint-disable-next-line camelcase
function is_block_id_reserved( id, clientId ) {
	const blocksClientIds =
		select( 'core/block-editor' ).getClientIdsWithDescendants();
	return blocksClientIds.some( ( _clientId ) => {
		const { id: _id } =
			select( 'core/block-editor' ).getBlockAttributes( _clientId );
		return clientId !== _clientId && id === _id;
	} );
}

registerBlockType( 'learndash/ld-course-grid', {
	title: __( 'LearnDash Course Grid', 'learndash' ),
	description: __( 'Build LearnDash course grid easily.', 'learndash' ),
	icon: 'grid-view',
	category: 'learndash-blocks',
	supports: {
		customClassName: true,
	},
	apiVersion: 3,
	attributes: {
		// Query
		post_type: {
			type: 'string',
			// eslint-disable-next-line camelcase, no-undef
			default: LearnDash_Course_Grid_Block_Editor.is_learndash_active
				? 'sfwd-courses'
				: 'post',
		},
		per_page: {
			type: 'string',
			default: 9,
		},
		orderby: {
			type: 'string',
			default: 'ID',
		},
		order: {
			type: 'string',
			default: 'DESC',
		},
		taxonomies: {
			type: 'string',
			default: '',
		},
		enrollment_status: {
			type: 'string',
			default: '',
		},
		progress_status: {
			type: 'string',
			default: '',
		},
		// Elements
		thumbnail: {
			type: 'boolean',
			default: 1,
		},
		thumbnail_size: {
			type: 'string',
			default: 'course-thumbnail',
		},
		ribbon: {
			type: 'boolean',
			default: 1,
		},
		content: {
			type: 'boolean',
			default: 1,
		},
		title: {
			type: 'boolean',
			default: 1,
		},
		title_clickable: {
			type: 'boolean',
			default: 1,
		},
		description: {
			type: 'boolean',
			default: 1,
		},
		description_char_max: {
			type: 'string',
			default: 120,
		},
		post_meta: {
			type: 'boolean',
			default: 1,
		},
		button: {
			type: 'boolean',
			default: 1,
		},
		pagination: {
			type: 'string',
			default: 'button',
		},
		grid_height_equal: {
			type: 'boolean',
			default: 0,
		},
		progress_bar: {
			type: 'boolean',
			default: 0,
		},
		filter: {
			type: 'boolean',
			default: 1,
		},
		// Templates
		skin: {
			type: 'string',
			default: 'grid',
		},
		card: {
			type: 'string',
			default: 'grid-1',
		},
		columns: {
			type: 'string',
			default: 3,
		},
		min_column_width: {
			type: 'string',
			default: 250,
		},
		items_per_row: {
			type: 'string',
			default: 5,
		},
		// Styles
		font_family_title: {
			type: 'string',
		},
		font_family_description: {
			type: 'string',
			default: '',
		},
		font_size_title: {
			type: 'string',
			default: '',
		},
		font_size_description: {
			type: 'string',
			default: '',
		},
		font_color_title: {
			type: 'string',
			default: '',
		},
		font_color_description: {
			type: 'string',
			default: '',
		},
		background_color_title: {
			type: 'string',
			default: '',
		},
		background_color_description: {
			type: 'string',
			default: '',
		},
		background_color_ribbon: {
			type: 'string',
			default: '',
		},
		font_color_ribbon: {
			type: 'string',
			default: '',
		},
		background_color_icon: {
			type: 'string',
			default: '',
		},
		font_color_icon: {
			type: 'string',
			default: '',
		},
		background_color_button: {
			type: 'string',
			default: '',
		},
		font_color_button: {
			type: 'string',
			default: '',
		},
		// Misc
		id: {
			type: 'string',
			default: '',
		},
		preview_show: {
			type: 'boolean',
			default: 1,
		},
		display_state: {
			type: 'object',
			default: {},
		},
		// Filter
		filter_search: {
			type: 'boolean',
			default: 1,
		},
		filter_taxonomies: {
			type: 'array',
			default: [ 'category', 'post_tag' ],
		},
		filter_price: {
			type: 'boolean',
			default: 1,
		},
		filter_price_min: {
			type: 'string',
			default: 0,
		},
		filter_price_max: {
			type: 'string',
			default: 1000,
		},
	},

	edit: ( props ) => {
		const {
			attributes: {
				// eslint-disable-next-line camelcase
				post_type,
				// eslint-disable-next-line camelcase
				per_page,
				orderby,
				order,
				taxonomies,
				// eslint-disable-next-line camelcase
				enrollment_status,
				// eslint-disable-next-line camelcase
				progress_status,
				thumbnail,
				// eslint-disable-next-line camelcase
				thumbnail_size,
				ribbon,
				content,
				title,
				// eslint-disable-next-line camelcase
				title_clickable,
				description,
				// eslint-disable-next-line camelcase
				description_char_max,
				// eslint-disable-next-line camelcase
				post_meta,
				button,
				pagination,
				// eslint-disable-next-line camelcase
				grid_height_equal,
				// eslint-disable-next-line camelcase
				progress_bar,
				filter,
				skin,
				card,
				columns,
				// eslint-disable-next-line camelcase
				min_column_width,
				// eslint-disable-next-line camelcase
				items_per_row,
				// eslint-disable-next-line camelcase
				font_family_title,
				// eslint-disable-next-line camelcase
				font_family_description,
				// eslint-disable-next-line camelcase
				font_size_title,
				// eslint-disable-next-line camelcase
				font_size_description,
				// eslint-disable-next-line camelcase
				font_color_title,
				// eslint-disable-next-line camelcase
				font_color_description,
				// eslint-disable-next-line camelcase
				background_color_title,
				// eslint-disable-next-line camelcase
				background_color_description,
				// eslint-disable-next-line camelcase
				background_color_ribbon,
				// eslint-disable-next-line camelcase
				font_color_ribbon,
				// eslint-disable-next-line camelcase
				background_color_icon,
				// eslint-disable-next-line camelcase
				font_color_icon,
				// eslint-disable-next-line camelcase
				background_color_button,
				// eslint-disable-next-line camelcase
				font_color_button,
				// Misc
				id,
				// eslint-disable-next-line camelcase
				display_state,
				// eslint-disable-next-line camelcase
				preview_show,
				// eslint-disable-next-line camelcase
				filter_search,
				// eslint-disable-next-line camelcase
				filter_taxonomies,
				// eslint-disable-next-line camelcase
				filter_price,
				// eslint-disable-next-line camelcase
				filter_price_min,
				// eslint-disable-next-line camelcase
				filter_price_max,
			},
			// eslint-disable-next-line no-unused-vars
			className,
			clientId,
			setAttributes,
		} = props;

		const blockProps = useBlockProps();

		if ( ! id || id === '' ) {
			// eslint-disable-next-line camelcase
			const temp_id = generate_unique_id();
			// eslint-disable-next-line camelcase
			setAttributes( { id: temp_id } );
		} else if ( is_block_id_reserved( id, clientId ) ) {
			// eslint-disable-next-line camelcase
			const new_id = generate_unique_id();
			// eslint-disable-next-line camelcase
			setAttributes( { id: new_id } );
		}

		// eslint-disable-next-line camelcase, no-undef
		const post_type_options = LearnDash_Course_Grid_Block_Editor.post_types;

		// eslint-disable-next-line camelcase
		const pagination_options =
			// eslint-disable-next-line camelcase, no-undef
			LearnDash_Course_Grid_Block_Editor.paginations;

		// eslint-disable-next-line camelcase, no-undef
		const skins = LearnDash_Course_Grid_Block_Editor.skins;
		// eslint-disable-next-line camelcase, no-undef
		const cards = LearnDash_Course_Grid_Block_Editor.cards;

		// eslint-disable-next-line camelcase
		const skin_options = [],
			// eslint-disable-next-line camelcase
			skin_disabled_fields = {};
		// eslint-disable-next-line no-shadow
		for ( const id in skins ) {
			if ( Object.hasOwnProperty.call( skins, id ) ) {
				const element = {
					label: skins[ id ].label,
					value: skins[ id ].slug,
				};

				// eslint-disable-next-line camelcase
				skin_options.push( element );

				if ( Object.hasOwnProperty.call( skins[ id ], 'disable' ) ) {
					// eslint-disable-next-line camelcase
					skin_disabled_fields[ skins[ id ].slug ] =
						skins[ id ].disable;
				}
			}
		}

		// eslint-disable-next-line camelcase
		const card_options = [],
			// eslint-disable-next-line camelcase
			card_values = [],
			// eslint-disable-next-line camelcase
			skin_cards = {},
			// eslint-disable-next-line camelcase
			card_disabled_fields = {};
		// eslint-disable-next-line no-shadow
		for ( const id in cards ) {
			if ( Object.hasOwnProperty.call( cards, id ) ) {
				if ( Object.hasOwnProperty.call( cards[ id ], 'disable' ) ) {
					// eslint-disable-next-line camelcase
					card_disabled_fields[ cards[ id ] ] = cards[ id ].disable;
				}

				if ( Object.hasOwnProperty.call( cards[ id ], 'skins' ) ) {
					// eslint-disable-next-line camelcase
					cards[ id ].skins.forEach( function ( temp_skin ) {
						// eslint-disable-next-line camelcase
						skin_cards[ temp_skin ] = skin_cards[ temp_skin ] || [];

						// eslint-disable-next-line camelcase
						skin_cards[ temp_skin ].push( id );
					} );
				}

				if (
					typeof cards[ id ].skins !== 'undefined' &&
					cards[ id ].skins.indexOf( skin ) > -1
				) {
					const element = {
						label: cards[ id ].label,
						value: id,
					};

					// eslint-disable-next-line camelcase
					card_options.push( element );
					// eslint-disable-next-line camelcase
					card_values.push( id );
				}
			}
		}

		// eslint-disable-next-line camelcase
		const thumbnail_size_options =
			// eslint-disable-next-line camelcase, no-undef
			LearnDash_Course_Grid_Block_Editor.image_sizes;

		// eslint-disable-next-line camelcase, no-undef
		const orderby_options = LearnDash_Course_Grid_Block_Editor.orderby;

		// eslint-disable-next-line camelcase
		const order_options = [
			{ label: __( 'Ascending', 'learndash' ), value: 'ASC' },
			{ label: __( 'Descending', 'learndash' ), value: 'DESC' },
		];

		// eslint-disable-next-line camelcase
		const enrollment_status_options = [
			{ value: '', label: __( 'All', 'learndash' ) },
			{ value: 'enrolled', label: __( 'Enrolled', 'learndash' ) },
			{ value: 'not-enrolled', label: __( 'Not Enrolled', 'learndash' ) },
		];

		// eslint-disable-next-line camelcase
		const progress_status_options = [
			{ value: '', label: __( 'All', 'learndash' ) },
			{ value: 'completed', label: __( 'Completed', 'learndash' ) },
			{ value: 'in_progress', label: __( 'In Progress', 'learndash' ) },
			{ value: 'not_started', label: __( 'Not Started', 'learndash' ) },
		];

		selectSkin( props );

		const inspectorControls = (
			<Fragment key={ 'learndash-course-grid-settings' }>
				<InspectorControls key="controls">
					<Panel className={ 'learndash-course-grid-panel' }>
						<PanelBody
							title={ __( 'Template', 'learndash' ) }
							initialOpen={ true }
						>
							<BaseControl
								className={
									// eslint-disable-next-line camelcase
									typeof display_state.skin !== 'undefined' &&
									// eslint-disable-next-line camelcase
									! display_state.skin
										? 'hide'
										: 'show'
								}
							>
								<SelectControl
									label={ __( 'Skin', 'learndash' ) }
									// eslint-disable-next-line camelcase
									options={ skin_options }
									value={ skin || '' }
									// eslint-disable-next-line no-shadow
									onChange={ ( skin ) => {
										setAttributes( { skin } );
										selectSkin( props );
									} }
								/>
							</BaseControl>
							<BaseControl
								className={
									// eslint-disable-next-line camelcase
									typeof display_state.card !== 'undefined' &&
									// eslint-disable-next-line camelcase
									! display_state.card
										? 'hide'
										: 'show'
								}
							>
								<SelectControl
									label={ __( 'Card', 'learndash' ) }
									// eslint-disable-next-line camelcase
									options={ card_options }
									value={ card || '' }
									// eslint-disable-next-line no-shadow
									onChange={ ( card ) => {
										setAttributes( { card } );
									} }
								/>
							</BaseControl>
							<TextControl
								label={ __( 'Columns', 'learndash' ) }
								value={ columns || '' }
								type={ 'number' }
								// eslint-disable-next-line no-shadow
								onChange={ ( columns ) =>
									setAttributes( { columns } )
								}
								className={
									// eslint-disable-next-line camelcase
									typeof display_state.columns !==
										// eslint-disable-next-line camelcase
										'undefined' && ! display_state.columns
										? 'hide'
										: 'show'
								}
							/>
							{ [ 'grid', 'masonry' ].indexOf( skin ) > -1 && (
								<TextControl
									label={ __(
										'Min Column Width (in pixel)',
										'learndash'
									) }
									// eslint-disable-next-line camelcase
									value={ min_column_width }
									type={ 'number' }
									help={ __(
										'If column width reach value lower than this, the grid columns number will automatically be adjusted on display.',
										'learndash'
									) }
									// eslint-disable-next-line camelcase, no-shadow
									onChange={ ( min_column_width ) =>
										// eslint-disable-next-line camelcase
										setAttributes( { min_column_width } )
									}
									className={
										// eslint-disable-next-line camelcase
										typeof display_state.min_column_width !==
											'undefined' &&
										// eslint-disable-next-line camelcase
										! display_state.min_column_width
											? 'hide'
											: 'show'
									}
								/>
							) }
							<TextControl
								label={ __( 'Items Per Row', 'learndash' ) }
								help={ __(
									'Number of items per row. Certain skins use this to customize the design.',
									'learndash'
								) }
								// eslint-disable-next-line camelcase
								value={ items_per_row || '' }
								type={ 'number' }
								// eslint-disable-next-line camelcase, no-shadow
								onChange={ ( items_per_row ) =>
									// eslint-disable-next-line camelcase
									setAttributes( { items_per_row } )
								}
								className={
									// eslint-disable-next-line camelcase
									typeof display_state.items_per_row !==
										'undefined' &&
									// eslint-disable-next-line camelcase
									! display_state.items_per_row
										? 'hide'
										: 'show'
								}
							/>
						</PanelBody>
						<PanelBody
							title={ __( 'Query', 'learndash' ) }
							initialOpen={ false }
						>
							<BaseControl
								className={
									// eslint-disable-next-line camelcase
									typeof display_state.post_type !==
										// eslint-disable-next-line camelcase
										'undefined' && ! display_state.post_type
										? 'hide'
										: 'show'
								}
							>
								<SelectControl
									label={ __( 'Post Type', 'learndash' ) }
									// eslint-disable-next-line camelcase
									options={ post_type_options }
									// eslint-disable-next-line camelcase
									value={ post_type || '' }
									// eslint-disable-next-line camelcase, no-shadow
									onChange={ ( post_type ) =>
										// eslint-disable-next-line camelcase
										setAttributes( { post_type } )
									}
								/>
							</BaseControl>
							<TextControl
								label={ __( 'Posts per page', 'learndash' ) }
								help={ __(
									'Enter 0 show all items.',
									'learndash'
								) }
								// eslint-disable-next-line camelcase
								value={ per_page || '' }
								type={ 'number' }
								// eslint-disable-next-line camelcase, no-shadow
								onChange={ ( per_page ) =>
									// eslint-disable-next-line camelcase
									setAttributes( { per_page } )
								}
								className={
									// eslint-disable-next-line camelcase
									typeof display_state.per_page !==
										// eslint-disable-next-line camelcase
										'undefined' && ! display_state.per_page
										? 'hide'
										: 'show'
								}
							/>
							<BaseControl
								className={
									// eslint-disable-next-line camelcase
									typeof display_state.orderby !==
										// eslint-disable-next-line camelcase
										'undefined' && ! display_state.orderby
										? 'hide'
										: 'show'
								}
							>
								<SelectControl
									label={ __( 'Order By', 'learndash' ) }
									// eslint-disable-next-line camelcase
									options={ orderby_options }
									value={ orderby || '' }
									// eslint-disable-next-line no-shadow
									onChange={ ( orderby ) =>
										setAttributes( { orderby } )
									}
								/>
							</BaseControl>
							<BaseControl
								className={
									// eslint-disable-next-line camelcase
									typeof display_state.order !==
										// eslint-disable-next-line camelcase
										'undefined' && ! display_state.order
										? 'hide'
										: 'show'
								}
							>
								<SelectControl
									label={ __( 'Order', 'learndash' ) }
									// eslint-disable-next-line camelcase
									options={ order_options }
									value={ order || '' }
									// eslint-disable-next-line no-shadow
									onChange={ ( order ) =>
										setAttributes( { order } )
									}
								/>
							</BaseControl>
							<TextControl
								label={ __( 'Taxonomies', 'learndash' ) }
								help={
									__( 'Format:', 'learndash' ) +
									' taxonomy1:term1,term2; taxonomy2:term1,term2;'
								}
								value={ taxonomies || '' }
								// eslint-disable-next-line no-shadow
								onChange={ ( taxonomies ) =>
									setAttributes( { taxonomies } )
								}
								className={
									// eslint-disable-next-line camelcase
									typeof display_state.taxonomies !==
										'undefined' &&
									// eslint-disable-next-line camelcase
									! display_state.taxonomies
										? 'hide'
										: 'show' + ' taxonomies'
								}
							/>
							{ [ 'sfwd-courses', 'groups' ].indexOf(
								post_type
							) > -1 && (
								<BaseControl
									className={
										// eslint-disable-next-line camelcase
										typeof display_state.enrollment_status !==
											'undefined' &&
										// eslint-disable-next-line camelcase
										! display_state.enrollment_status
											? 'hide'
											: 'show'
									}
								>
									<SelectControl
										label={ __(
											'Enrollment Status',
											'learndash'
										) }
										// eslint-disable-next-line camelcase
										options={ enrollment_status_options }
										// eslint-disable-next-line camelcase
										value={ enrollment_status }
										// eslint-disable-next-line camelcase, no-shadow
										onChange={ ( enrollment_status ) =>
											setAttributes( {
												// eslint-disable-next-line camelcase
												enrollment_status,
											} )
										}
									/>
								</BaseControl>
							) }
							{ [ 'sfwd-courses' ].indexOf( post_type ) > -1 &&
								// eslint-disable-next-line camelcase, eqeqeq
								enrollment_status == 'enrolled' && (
									<BaseControl
										className={
											// eslint-disable-next-line camelcase
											typeof display_state.progress_status !==
												'undefined' &&
											// eslint-disable-next-line camelcase
											! display_state.progress_status
												? 'hide'
												: 'show'
										}
									>
										<SelectControl
											label={ __(
												'Progress Status',
												'learndash'
											) }
											// eslint-disable-next-line camelcase
											options={ progress_status_options }
											// eslint-disable-next-line camelcase
											value={ progress_status }
											// eslint-disable-next-line camelcase, no-shadow
											onChange={ ( progress_status ) =>
												setAttributes( {
													// eslint-disable-next-line camelcase
													progress_status,
												} )
											}
										/>
									</BaseControl>
								) }
						</PanelBody>
						<PanelBody
							title={ __( 'Elements', 'learndash' ) }
							initialOpen={ false }
						>
							{ cards[ card ].elements.indexOf( 'thumbnail' ) >
								-1 && (
								<ToggleControl
									label={ __( 'Thumbnail', 'learndash' ) }
									checked={ thumbnail }
									// eslint-disable-next-line no-shadow
									onChange={ ( thumbnail ) =>
										setAttributes( { thumbnail } )
									}
									className={
										// eslint-disable-next-line camelcase
										typeof display_state.thumbnail !==
											'undefined' &&
										// eslint-disable-next-line camelcase
										! display_state.thumbnail
											? 'hide'
											: 'show'
									}
								/>
							) }
							{ cards[ card ].elements.indexOf( 'thumbnail' ) >
								-1 &&
								thumbnail && (
									<BaseControl
										className={
											// eslint-disable-next-line camelcase
											typeof display_state.thumbnail_size !==
												'undefined' &&
											// eslint-disable-next-line camelcase
											! display_state.thumbnail_size
												? 'hide'
												: 'show'
										}
									>
										<SelectControl
											label={ __(
												'Thumbnail Size',
												'learndash'
											) }
											// eslint-disable-next-line camelcase
											options={ thumbnail_size_options }
											// eslint-disable-next-line camelcase
											value={ thumbnail_size || '' }
											// eslint-disable-next-line camelcase, no-shadow
											onChange={ ( thumbnail_size ) =>
												setAttributes( {
													// eslint-disable-next-line camelcase
													thumbnail_size,
												} )
											}
										/>
									</BaseControl>
								) }
							{ cards[ card ].elements.indexOf( 'ribbon' ) >
								-1 && (
								<ToggleControl
									label={ __( 'Ribbon', 'learndash' ) }
									checked={ ribbon }
									// eslint-disable-next-line no-shadow
									onChange={ ( ribbon ) =>
										setAttributes( { ribbon } )
									}
									className={
										// eslint-disable-next-line camelcase
										typeof display_state.ribbon !==
											'undefined' &&
										// eslint-disable-next-line camelcase
										! display_state.ribbon
											? 'hide'
											: 'show'
									}
								/>
							) }
							{ cards[ card ].elements.indexOf( 'content' ) >
								-1 && (
								<ToggleControl
									label={ __( 'Content', 'learndash' ) }
									help={ __(
										'Content includes elements in the area outside of the thumbnail.',
										'learndash'
									) }
									checked={ content }
									// eslint-disable-next-line no-shadow
									onChange={ ( content ) =>
										setAttributes( { content } )
									}
									className={
										// eslint-disable-next-line camelcase
										typeof display_state.content !==
											'undefined' &&
										// eslint-disable-next-line camelcase
										! display_state.content
											? 'hide'
											: 'show'
									}
								/>
							) }
							{ cards[ card ].elements.indexOf( 'title' ) >
								-1 && (
								<ToggleControl
									label={ __( 'Title', 'learndash' ) }
									checked={ title }
									// eslint-disable-next-line no-shadow
									onChange={ ( title ) =>
										setAttributes( { title } )
									}
									className={
										// eslint-disable-next-line camelcase
										typeof display_state.title !==
											// eslint-disable-next-line camelcase
											'undefined' && ! display_state.title
											? 'hide'
											: 'show'
									}
								/>
							) }
							{ cards[ card ].elements.indexOf( 'title' ) > -1 &&
								title && (
									<ToggleControl
										label={ __(
											'Clickable Title',
											'learndash'
										) }
										// eslint-disable-next-line camelcase
										checked={ title_clickable }
										// eslint-disable-next-line camelcase, no-shadow
										onChange={ ( title_clickable ) =>
											// eslint-disable-next-line camelcase
											setAttributes( { title_clickable } )
										}
										className={
											// eslint-disable-next-line camelcase
											typeof display_state.title_clickable !==
												'undefined' &&
											// eslint-disable-next-line camelcase
											! display_state.title_clickable
												? 'hide'
												: 'show'
										}
									/>
								) }
							{ cards[ card ].elements.indexOf( 'description' ) >
								-1 && (
								<ToggleControl
									label={ __( 'Description', 'learndash' ) }
									checked={ description }
									// eslint-disable-next-line no-shadow
									onChange={ ( description ) =>
										setAttributes( { description } )
									}
									className={
										// eslint-disable-next-line camelcase
										typeof display_state.description !==
											'undefined' &&
										// eslint-disable-next-line camelcase
										! display_state.description
											? 'hide'
											: 'show'
									}
								/>
							) }
							{ cards[ card ].elements.indexOf( 'description' ) >
								-1 &&
								description && (
									<TextControl
										label={ __(
											'Max Description Character Count',
											'learndash'
										) }
										// eslint-disable-next-line camelcase
										value={ description_char_max || '' }
										type={ 'number' }
										// eslint-disable-next-line camelcase, no-shadow
										onChange={ ( description_char_max ) => {
											setAttributes( {
												// eslint-disable-next-line camelcase
												description_char_max,
											} );
										} }
									/>
								) }
							{ cards[ card ].elements.indexOf( 'post_meta' ) >
								-1 && (
								<ToggleControl
									label={ __( 'Meta', 'learndash' ) }
									// eslint-disable-next-line camelcase
									checked={ post_meta }
									// eslint-disable-next-line camelcase, no-shadow
									onChange={ ( post_meta ) =>
										// eslint-disable-next-line camelcase
										setAttributes( { post_meta } )
									}
									className={
										// eslint-disable-next-line camelcase
										typeof display_state.post_meta !==
											'undefined' &&
										// eslint-disable-next-line camelcase
										! display_state.post_meta
											? 'hide'
											: 'show'
									}
								/>
							) }
							{ cards[ card ].elements.indexOf( 'button' ) >
								-1 && (
								<ToggleControl
									label={ __( 'Button', 'learndash' ) }
									checked={ button }
									// eslint-disable-next-line no-shadow
									onChange={ ( button ) =>
										setAttributes( { button } )
									}
									className={
										// eslint-disable-next-line camelcase
										typeof display_state.button !==
											'undefined' &&
										// eslint-disable-next-line camelcase
										! display_state.button
											? 'hide'
											: 'show'
									}
								/>
							) }
							<ToggleControl
								label={ __( 'Progress Bar', 'learndash' ) }
								help={ __(
									'Available for LearnDash course and group.',
									'learndash'
								) }
								// eslint-disable-next-line camelcase
								checked={ progress_bar }
								// eslint-disable-next-line camelcase, no-shadow
								onChange={ ( progress_bar ) =>
									// eslint-disable-next-line camelcase
									setAttributes( { progress_bar } )
								}
								className={
									// eslint-disable-next-line camelcase
									typeof display_state.progress_bar !==
										'undefined' &&
									// eslint-disable-next-line camelcase
									! display_state.progress_bar
										? 'hide'
										: 'show'
								}
							/>
							<BaseControl
								className={
									// eslint-disable-next-line camelcase
									typeof display_state.pagination !==
										'undefined' &&
									// eslint-disable-next-line camelcase
									! display_state.pagination
										? 'hide'
										: 'show'
								}
							>
								<SelectControl
									label={ __( 'Pagination', 'learndash' ) }
									// eslint-disable-next-line camelcase
									options={ pagination_options }
									value={ pagination || '' }
									// eslint-disable-next-line no-shadow
									onChange={ ( pagination ) =>
										setAttributes( { pagination } )
									}
								/>
							</BaseControl>
							<ToggleControl
								label={ __( 'Filter', 'learndash' ) }
								checked={ filter }
								// eslint-disable-next-line no-shadow
								onChange={ ( filter ) => {
									setAttributes( { filter } );
								} }
								className={
									// eslint-disable-next-line camelcase
									typeof display_state.filter !==
										// eslint-disable-next-line camelcase
										'undefined' && ! display_state.filter
										? 'hide'
										: 'show'
								}
							/>
						</PanelBody>
						{ filter && (
							<FilterPanelBody
								context={ 'page' }
								course_grid_id={ id }
								// eslint-disable-next-line camelcase
								search={ filter_search }
								// eslint-disable-next-line camelcase
								taxonomies={ filter_taxonomies }
								// eslint-disable-next-line camelcase
								price={ filter_price }
								// eslint-disable-next-line camelcase
								price_min={ filter_price_min }
								// eslint-disable-next-line camelcase
								price_max={ filter_price_max }
								setAttributes={ setAttributes }
							/>
						) }
						<PanelBody
							title={ __( 'Styles', 'learndash' ) }
							initialOpen={ false }
						>
							{ skin === 'grid' && (
								<div className="grid-style">
									<h3>{ __( 'Grid', 'learndash' ) }</h3>
									<ToggleControl
										label={ __(
											'Equal Grid Height',
											'learndash'
										) }
										// eslint-disable-next-line camelcase
										checked={ grid_height_equal }
										// eslint-disable-next-line camelcase, no-shadow
										onChange={ ( grid_height_equal ) =>
											setAttributes( {
												// eslint-disable-next-line camelcase
												grid_height_equal,
											} )
										}
										className={
											// eslint-disable-next-line camelcase
											typeof display_state.grid_height_equal !==
												'undefined' &&
											// eslint-disable-next-line camelcase
											! display_state.grid_height_equal
												? 'hide'
												: 'show'
										}
									/>
								</div>
							) }
							{ cards[ card ].elements.indexOf( 'title' ) > -1 &&
								title && (
									<Fragment key={ 'title-styles' }>
										<h3>
											{ __( 'Heading', 'learndash' ) }
										</h3>
										<TextControl
											label={ __(
												'Heading Font Family',
												'learndash'
											) }
											// eslint-disable-next-line camelcase
											value={ font_family_title || '' }
											// eslint-disable-next-line camelcase, no-shadow
											onChange={ ( font_family_title ) =>
												setAttributes( {
													// eslint-disable-next-line camelcase
													font_family_title,
												} )
											}
											className={
												// eslint-disable-next-line camelcase
												typeof display_state.font_family_title !==
													'undefined' &&
												// eslint-disable-next-line camelcase
												! display_state.font_family_title
													? 'hide'
													: 'show'
											}
										/>
										<TextControl
											label={ __(
												'Heading Font Size',
												'learndash'
											) }
											help={ __(
												'Accepts full format, e.g. 18px, 2rem',
												'learndash'
											) }
											// eslint-disable-next-line camelcase
											value={ font_size_title || '' }
											// eslint-disable-next-line camelcase, no-shadow
											onChange={ ( font_size_title ) =>
												setAttributes( {
													// eslint-disable-next-line camelcase
													font_size_title,
												} )
											}
											className={
												// eslint-disable-next-line camelcase
												typeof display_state.font_size_title !==
													'undefined' &&
												// eslint-disable-next-line camelcase
												! display_state.font_size_title
													? 'hide'
													: 'show'
											}
										/>
										<ColorPalette
											name={ 'font_color_title' }
											// eslint-disable-next-line camelcase
											value={ font_color_title }
											label={ __(
												'Heading Font Color',
												'learndash'
											) }
											// eslint-disable-next-line camelcase
											display_state={ display_state }
											setAttributes={ setAttributes }
										/>
										<ColorPalette
											name={ 'background_color_title' }
											// eslint-disable-next-line camelcase
											value={ background_color_title }
											label={ __(
												'Heading Background Color',
												'learndash'
											) }
											// eslint-disable-next-line camelcase
											display_state={ display_state }
											setAttributes={ setAttributes }
										/>
									</Fragment>
								) }
							{ cards[ card ].elements.indexOf( 'description' ) >
								-1 &&
								description && (
									<Fragment key={ 'description-styles' }>
										<h3>
											{ __( 'Description', 'learndash' ) }
										</h3>
										<TextControl
											label={ __(
												'Description Font Family',
												'learndash'
											) }
											value={
												// eslint-disable-next-line camelcase
												font_family_description || ''
											}
											onChange={ (
												// eslint-disable-next-line camelcase, no-shadow
												font_family_description
											) =>
												setAttributes( {
													// eslint-disable-next-line camelcase
													font_family_description,
												} )
											}
											className={
												// eslint-disable-next-line camelcase
												typeof display_state.font_family_description !==
													'undefined' &&
												// eslint-disable-next-line camelcase
												! display_state.font_family_description
													? 'hide'
													: 'show'
											}
										/>
										<TextControl
											label={ __(
												'Description Font Size',
												'learndash'
											) }
											help={ __(
												'Accepts full format, e.g. 18px, 2rem',
												'learndash'
											) }
											value={
												// eslint-disable-next-line camelcase
												font_size_description || ''
											}
											onChange={ (
												// eslint-disable-next-line camelcase, no-shadow
												font_size_description
											) =>
												setAttributes( {
													// eslint-disable-next-line camelcase
													font_size_description,
												} )
											}
											className={
												// eslint-disable-next-line camelcase
												typeof display_state.font_size_description !==
													'undefined' &&
												// eslint-disable-next-line camelcase
												! display_state.font_size_description
													? 'hide'
													: 'show'
											}
										/>
										<ColorPalette
											name={ 'font_color_description' }
											// eslint-disable-next-line camelcase
											value={ font_color_description }
											label={ __(
												'Description Font Color',
												'learndash'
											) }
											// eslint-disable-next-line camelcase
											display_state={ display_state }
											setAttributes={ setAttributes }
										/>
										<ColorPalette
											name={
												'background_color_description'
											}
											value={
												// eslint-disable-next-line camelcase
												background_color_description
											}
											label={ __(
												'Description Background Color',
												'learndash'
											) }
											// eslint-disable-next-line camelcase
											display_state={ display_state }
											setAttributes={ setAttributes }
										/>
									</Fragment>
								) }
							<h3>{ __( 'Elements', 'learndash' ) }</h3>
							{ cards[ card ].elements.indexOf( 'ribbon' ) > -1 &&
								ribbon && (
									<Fragment key={ 'ribbon-styles' }>
										<ColorPalette
											name={ 'font_color_ribbon' }
											// eslint-disable-next-line camelcase
											value={ font_color_ribbon }
											label={ __(
												'Ribbon Font Color',
												'learndash'
											) }
											// eslint-disable-next-line camelcase
											display_state={ display_state }
											setAttributes={ setAttributes }
										/>
										<ColorPalette
											name={ 'background_color_ribbon' }
											// eslint-disable-next-line camelcase
											value={ background_color_ribbon }
											label={ __(
												'Ribbon Background Color',
												'learndash'
											) }
											// eslint-disable-next-line camelcase
											display_state={ display_state }
											setAttributes={ setAttributes }
										/>
									</Fragment>
								) }
							{ cards[ card ].elements.indexOf( 'icon' ) > -1 && (
								<Fragment key={ 'icon-styles' }>
									<ColorPalette
										name={ 'font_color_icon' }
										// eslint-disable-next-line camelcase
										value={ font_color_icon }
										label={ __(
											'Icon Color',
											'learndash'
										) }
										// eslint-disable-next-line camelcase
										display_state={ display_state }
										setAttributes={ setAttributes }
									/>
									<ColorPalette
										name={ 'background_color_icon' }
										// eslint-disable-next-line camelcase
										value={ background_color_icon }
										label={ __(
											'Icon Background Color',
											'learndash'
										) }
										// eslint-disable-next-line camelcase
										display_state={ display_state }
										setAttributes={ setAttributes }
									/>
								</Fragment>
							) }
							{ cards[ card ].elements.indexOf( 'button' ) > -1 &&
								button && (
									<Fragment key={ 'button-styles' }>
										<ColorPalette
											name={ 'font_color_button' }
											// eslint-disable-next-line camelcase
											value={ font_color_button }
											label={ __(
												'Button Font Color',
												'learndash'
											) }
											// eslint-disable-next-line camelcase
											display_state={ display_state }
											setAttributes={ setAttributes }
										/>
										<ColorPalette
											name={ 'background_color_button' }
											// eslint-disable-next-line camelcase
											value={ background_color_button }
											label={ __(
												'Button Background Color',
												'learndash'
											) }
											// eslint-disable-next-line camelcase
											display_state={ display_state }
											setAttributes={ setAttributes }
										/>
									</Fragment>
								) }
						</PanelBody>
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
				<InspectorAdvancedControls>
					<TextControl
						label={ __( 'ID' ) }
						help={ __(
							'Unique ID for CSS styling purpose.',
							'learndash'
						) }
						value={ id || '' }
						// eslint-disable-next-line no-shadow
						onChange={ ( id ) => setAttributes( { id } ) }
						className={
							// eslint-disable-next-line camelcase
							typeof display_state.id !== 'undefined' &&
							// eslint-disable-next-line camelcase
							! display_state.id
								? 'hide'
								: 'show'
						}
					/>
				</InspectorAdvancedControls>
			</Fragment>
		);

		// eslint-disable-next-line camelcase
		function do_serverside_render( attributes ) {
			// eslint-disable-next-line eqeqeq
			if ( attributes.preview_show == true ) {
				return (
					<ServerSideRender
						block="learndash/ld-course-grid"
						attributes={ attributes }
						key="learndash/ld-course-grid"
					/>
				);
			}
			return __(
				'[learndash_course_grid] shortcode output shown here',
				'learndash'
			);
		}

		// eslint-disable-next-line no-shadow
		function selectSkin( props ) {
			const {
				// eslint-disable-next-line no-unused-vars
				attributes = {
					skin,
					card,
					// eslint-disable-next-line camelcase
					display_state,
				},
				// eslint-disable-next-line no-shadow
				setAttributes,
			} = props;

			// eslint-disable-next-line camelcase
			let disabled_fields = [];
			// eslint-disable-next-line camelcase
			if ( typeof skin_disabled_fields[ skin ] !== 'undefined' ) {
				// eslint-disable-next-line camelcase
				disabled_fields = skin_disabled_fields[ skin ];
			}

			// eslint-disable-next-line camelcase, no-undef
			LearnDash_Course_Grid_Block_Editor.editor_fields.forEach(
				( field ) => {
					// eslint-disable-next-line camelcase
					const temp_display_state = display_state;
					// eslint-disable-next-line camelcase
					temp_display_state[ field ] = true;

					setAttributes( {
						// eslint-disable-next-line camelcase
						display_state: temp_display_state,
					} );
				}
			);

			// eslint-disable-next-line camelcase
			disabled_fields.forEach( ( field ) => {
				// eslint-disable-next-line camelcase
				const temp_display_state = display_state;
				// eslint-disable-next-line camelcase
				temp_display_state[ field ] = false;

				setAttributes( {
					// eslint-disable-next-line camelcase
					display_state: temp_display_state,
				} );
			} );

			if (
				// eslint-disable-next-line camelcase, eqeqeq
				card_values.indexOf( card ) == -1 &&
				Object.prototype.hasOwnProperty.call( skin_cards, 'skin' ) &&
				// eslint-disable-next-line camelcase
				Object.prototype.hasOwnProperty.call( skin_cards[ skin ], 0 )
			) {
				// eslint-disable-next-line camelcase
				let temp_card = card;
				// eslint-disable-next-line camelcase
				temp_card = skin_cards[ skin ][ 0 ];

				setAttributes( {
					// eslint-disable-next-line camelcase
					card: temp_card,
				} );
			}
		}

		// eslint-disable-next-line no-unused-vars
		function setDisplayState( key, value ) {
			// eslint-disable-next-line camelcase, no-shadow
			const { display_state } = props.attributes;

			// eslint-disable-next-line camelcase
			display_state[ key ] = value;

			setAttributes( {
				// eslint-disable-next-line camelcase
				display_state,
			} );
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
