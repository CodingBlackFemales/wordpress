/* eslint-disable -- TODO: Fix linting issues */

import { Component } from '@wordpress/element';
import { BaseControl } from '@wordpress/components';
import { AsyncPaginate } from 'react-select-async-paginate';
import './searchable-dropdown.scss'

/**
 * React Component to create a searchable Select field for items
 * This hits a REST API to paginate the results based on a search term, so this should be safe on large sites
 *
 * @since 4.17.0
 */
class SearchableDropdown extends Component {

	constructor() {

        super( ...arguments );

        if ( new.target === SearchableDropdown ) {
            throw new TypeError( 'You need to extend the ' + new.target.name + ' class to use it' );
        }

        if ( this.getDefaultValue === undefined ) {
            throw new TypeError( 'You must define the getDefaultValue asynchronous method in your Class' );
        }

        if ( this.loadOptions === undefined ) {
            throw new TypeError( 'You must define the loadOptions asynchronous method in your Class' );
        }

		this.state = {};

	}

	/**
	 * Set the default value within the State at this point in the React Life Cycle
	 * This way we can set it in the AsyncPaginate Component during the render step
	 *
	 * @since 4.17.0
	 *
	 * @return {void}
	 */
	componentDidMount() {

		var promise = this.getDefaultValue( this.props.value );

		promise.then( ( value ) => {
			this.setState( { defaultValue: value } );
		} );

	}

	componentDidUpdate() {

	}

	componentWillUnmount() {

	}

    /**
     * Generates a UUID to use for an ID for accessibility reasons
     *
     * @since 4.17.0
	 *
     * @return {string}  UUID
     */
    generateUUID() {

        // Timestamp
        var d = new Date().getTime();

        // Time in microseconds since page-load or 0 if unsupported.
        var d2 = ( ( typeof performance !== 'undefined' ) && performance.now && ( performance.now() * 1000 ) ) || 0;

        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace( /[xy]/g, function( c ) { // cspell:disable-line

            // random number between 0 and 16
            var r = Math.random() * 16;

            if ( d > 0 ) {

                // Use timestamp until depleted.
                r = ( d + r ) % 16 | 0;
                d = Math.floor( d / 16 );

            }
            else {

                // Use microseconds since page-load if supported.
                r = ( d2 + r ) % 16 | 0;
                d2 = Math.floor( d2 / 16 );

            }

            return ( c === 'x' ? r : ( r & 0x3 | 0x8 ) ).toString( 16 );

        });

    }

    /**
     * Render method for the component
     *
     * @since 4.17.0
     * @return  {Component}
     */
	render() {

		const {
            onChange,
            label,
            placeholder,
            description,
			additional
		} = this.props;

        let descriptionDOM;

        if ( description ) {
            descriptionDOM = <p className="description">{description}</p>;
        }

        let id = this.constructor.name.toLowerCase() + '-' + this.generateUUID();

		return (
			<BaseControl id={ id } label={ label } className={"ld-propanel-searchable-dropdown"}>

                {descriptionDOM}

				<AsyncPaginate
					value={ this.state.defaultValue }
					onChange={ ( option ) => {

                        // Update the chosen value within the State so that we can update it.
						this.setState( { defaultValue: option } );

                        if ( onChange ) {
                            return onChange( ( option ) ? option.value.toString() : null );
                        }

                        return;

					} }
					cacheOptions
					placeholder={ ( placeholder ) ? placeholder : '' }
					loadOptions={ this.loadOptions }
					isMulti={false}
                    isClearable={true}
                    id={ id }
					additional={ additional }
				/>
			</BaseControl>
		);

	}

}

export {
    SearchableDropdown
};
