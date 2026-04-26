/**
 * FilterPanelBody component
 *
 * @since 4.21.4
 */

/**
 * Internal block libraries
 */
import { Component } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	TextControl,
	ToggleControl,
	SelectControl,
	BaseControl,
} from '@wordpress/components';

class FilterPanelBody extends Component {
	// eslint-disable-next-line no-useless-constructor
	constructor( props ) {
		super( props );
	}

	render() {
		const {
			context,
			// eslint-disable-next-line camelcase
			course_grid_id,
			search,
			taxonomies,
			price,
			// eslint-disable-next-line camelcase
			price_min,
			// eslint-disable-next-line camelcase
			price_max,
			setAttributes,
		} = this.props;

		// eslint-disable-next-line camelcase
		let search_key = 'search';
		// eslint-disable-next-line camelcase
		let taxonomies_key = 'taxonomies';
		// eslint-disable-next-line camelcase
		let price_key = 'price';
		// eslint-disable-next-line camelcase
		let price_min_key = 'price_min';
		// eslint-disable-next-line camelcase
		let price_max_key = 'price_max';

		// eslint-disable-next-line eqeqeq
		if ( context == 'page' ) {
			// eslint-disable-next-line camelcase
			search_key = 'filter_search';
			// eslint-disable-next-line camelcase
			taxonomies_key = 'filter_taxonomies';
			// eslint-disable-next-line camelcase
			price_key = 'filter_price';
			// eslint-disable-next-line camelcase
			price_min_key = 'filter_price_min';
			// eslint-disable-next-line camelcase
			price_max_key = 'filter_price_max';
		}

		// eslint-disable-next-line camelcase
		const taxonomies_options =
			// eslint-disable-next-line camelcase, no-undef
			LearnDash_Course_Grid_Block_Editor.taxonomies;

		return (
			<PanelBody
				title={ __( 'Filter', 'learndash' ) }
				// eslint-disable-next-line eqeqeq
				initialOpen={ context == 'page' ? false : true }
			>
				{ context === 'widget' && (
					<TextControl
						label={ __( 'Course Grid ID', 'learndash' ) }
						help={ __(
							'Course grid ID the filter is for.',
							'learndash'
						) }
						// eslint-disable-next-line camelcase
						value={ course_grid_id || '' }
						type={ 'text' }
						// eslint-disable-next-line camelcase, no-shadow
						onChange={ ( course_grid_id ) =>
							// eslint-disable-next-line camelcase
							setAttributes( { course_grid_id } )
						}
					/>
				) }
				<ToggleControl
					label={ __( 'Search', 'learndash' ) }
					checked={ search }
					// eslint-disable-next-line no-shadow
					onChange={ ( search ) => {
						// eslint-disable-next-line camelcase
						const search_obj = {
							// eslint-disable-next-line camelcase
							[ search_key ]: search,
						};

						setAttributes( search_obj );
					} }
				/>
				<BaseControl>
					<SelectControl
						multiple
						label={ __( 'Taxonomies', 'learndash' ) }
						help={ __(
							'Hold ctrl on Windows or cmd on Mac to select multiple values.',
							'learndash'
						) }
						// eslint-disable-next-line camelcase
						options={ taxonomies_options }
						value={ taxonomies || [] }
						// eslint-disable-next-line no-shadow
						onChange={ ( taxonomies ) => {
							// eslint-disable-next-line camelcase
							const taxonomies_obj = {
								// eslint-disable-next-line camelcase
								[ taxonomies_key ]: taxonomies,
							};

							setAttributes( taxonomies_obj );
						} }
					/>
				</BaseControl>
				<ToggleControl
					label={ __( 'Price', 'learndash' ) }
					checked={ price }
					// eslint-disable-next-line no-shadow
					onChange={ ( price ) => {
						// eslint-disable-next-line camelcase
						const price_obj = {
							// eslint-disable-next-line camelcase
							[ price_key ]: price,
						};

						setAttributes( price_obj );
					} }
				/>
				<BaseControl>
					<TextControl
						label={ __( 'Price Min', 'learndash' ) }
						className={ 'left' }
						// eslint-disable-next-line camelcase
						value={ price_min || 0 }
						type={ 'number' }
						// eslint-disable-next-line camelcase, no-shadow
						onChange={ ( price_min ) => {
							// eslint-disable-next-line camelcase
							const price_min_obj = {
								// eslint-disable-next-line camelcase
								[ price_min_key ]: price_min,
							};

							setAttributes( price_min_obj );
						} }
					/>
					<TextControl
						label={ __( 'Price Max', 'learndash' ) }
						className={ 'right' }
						// eslint-disable-next-line camelcase
						value={ price_max || 0 }
						type={ 'number' }
						// eslint-disable-next-line camelcase, no-shadow
						onChange={ ( price_max ) => {
							// eslint-disable-next-line camelcase
							const price_max_obj = {
								// eslint-disable-next-line camelcase
								[ price_max_key ]: price_max,
							};

							setAttributes( price_max_obj );
						} }
					/>
					<div style={ { clear: 'both' } }></div>
				</BaseControl>
			</PanelBody>
		);
	}
}

export default FilterPanelBody;
