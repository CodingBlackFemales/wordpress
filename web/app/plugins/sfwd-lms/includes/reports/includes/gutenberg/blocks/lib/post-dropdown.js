/* eslint-disable -- TODO: Fix linting issues */

import { SearchableDropdown } from './searchable-dropdown';
import apiFetch from '@wordpress/api-fetch';

/**
 * React Component to create a searchable Select field for Posts
 * This hits a REST API to paginate the results based on a search term, so this should be safe on large sites
 *
 * @since 4.17.0
 */
class PostDropdown extends SearchableDropdown {

	constructor() {

		super( ...arguments );

		const {
            postType
		} = this.props;

	}

    /**
     * Asynchronous function to grab the default value
     * This is necessary since we only store the ID, but we also need the Post Title
     *
     * @since 4.17.0
     *
     * @param {integer} postID Post ID.
     *
     * @return {object} Object representing the Option.
     */
    async getDefaultValue( postID ) {

        if ( ! postID ) {
            return null;
        }

        let result = await apiFetch( {
            path: '/ld-propanel/v1/gutenberg-get-post?id=' + postID,
            method: 'GET',
        } ).then( response => {

            if ( response.post.length <= 0 ) return null;

            return response.post;

        } ).catch( error => {

            console.error( error );

            return {};

        } );

        return result;

    }

    /**
     * Loads the options from our API endpoint based on the search term
     *
     * @since 4.17.0
     *
     * @param {string} search        Search Term.
     * @param {array}  loadedOptions Array of Option Objects that have been loaded already. Important for paginating the results.
     * @param {object} additional    Additional data passed through to the AsyncPaginate Component. We use this for the Post Type.
     *
     * @return {Promise|Object} Promise for the API call or the results object itself.
     */
    async loadOptions( search, loadedOptions, additional ) {

        return apiFetch( {
            path: '/ld-propanel/v1/gutenberg-get-posts?s=' + search + '&post_type=' + additional.postType + '&offset=' + loadedOptions.length,
            method: 'GET',
        } ).then( response => {

            return response;

        } ).catch( error => {

            console.error( error );

            return {
                options: [],
                hasMore: false
            };

        } );

    }

}

export {
    PostDropdown
};
