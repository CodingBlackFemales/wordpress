/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { createReduxStore, register } from '@wordpress/data';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import config from './config';

export const STORE_NAME = 'cbf/glossary';

const termsPath = `/${ config.namespace }/terms`;

/**
 * Entry lookups made in the same tick are fetched in one request.
 */
let pending = new Set();
let flush = null;

function loadBatched( id ) {
	pending.add( id );

	if ( ! flush ) {
		flush = new Promise( ( resolve ) => {
			window.setTimeout( () => {
				const ids = [ ...pending ];
				pending = new Set();
				flush = null;
				resolve(
					apiFetch( {
						path: addQueryArgs( termsPath, {
							include: ids,
							per_page: 100,
						} ),
					} ).then( ( entries ) => ( { ids, entries } ) )
				);
			}, 0 );
		} );
	}

	return flush;
}

const DEFAULT_STATE = { entries: {}, missing: {} };

function reducer( state = DEFAULT_STATE, action ) {
	switch ( action.type ) {
		case 'RECEIVE_ENTRIES': {
			const entries = { ...state.entries };
			const missing = { ...state.missing };
			action.entries.forEach( ( entry ) => {
				entries[ entry.id ] = entry;
				delete missing[ entry.id ];
			} );
			return { entries, missing };
		}
		case 'MARK_MISSING': {
			const missing = { ...state.missing };
			action.ids.forEach( ( id ) => ( missing[ id ] = true ) );
			return { ...state, missing };
		}
	}
	return state;
}

const actions = {
	receiveEntries: ( entries ) => ( { type: 'RECEIVE_ENTRIES', entries } ),

	markMissing: ( ids ) => ( { type: 'MARK_MISSING', ids } ),

	/**
	 * Create an entry; resolves to the saved entry.
	 *
	 * @param {Object} data Forms and definition (Markdown).
	 */
	createEntry:
		( data ) =>
		async ( { dispatch } ) => {
			const entry = await apiFetch( {
				path: termsPath,
				method: 'POST',
				data,
			} );
			dispatch.receiveEntries( [ entry ] );
			return entry;
		},

	/**
	 * Re-fetch entries, e.g. after they were edited in another tab.
	 *
	 * @param {number[]} ids Entry IDs.
	 */
	refreshEntries:
		( ids ) =>
		async ( { dispatch } ) => {
			if ( ! ids.length ) {
				return;
			}
			const entries = await apiFetch( {
				path: addQueryArgs( termsPath, {
					include: ids,
					per_page: 100,
				} ),
			} );
			dispatch.receiveEntries( entries );
			const found = new Set( entries.map( ( entry ) => entry.id ) );
			dispatch.markMissing( ids.filter( ( id ) => ! found.has( id ) ) );
		},
};

const selectors = {
	/**
	 * An entry, or undefined while loading. Missing entries stay undefined;
	 * check isMissing().
	 *
	 * @param {Object} state Store state.
	 * @param {number} id    Entry ID.
	 */
	getEntry: ( state, id ) => state.entries[ id ],

	isMissing: ( state, id ) => !! state.missing[ id ],
};

const resolvers = {
	getEntry:
		( id ) =>
		async ( { dispatch } ) => {
			if ( ! id ) {
				return;
			}
			const { ids, entries } = await loadBatched( id );
			const found = new Set( entries.map( ( entry ) => entry.id ) );
			dispatch.receiveEntries( entries );
			dispatch.markMissing(
				ids.filter( ( each ) => ! found.has( each ) )
			);
		},
};

export const store = createReduxStore( STORE_NAME, {
	reducer,
	actions,
	selectors,
	resolvers,
} );

register( store );

/**
 * Search entries; resolves to entries with usage counts.
 *
 * @param {string} search Query.
 * @param {number} limit  Maximum results.
 */
export function searchEntries( search, limit = 8 ) {
	return apiFetch( {
		path: addQueryArgs( termsPath, { search, per_page: limit } ),
	} );
}

/**
 * Audit unsaved content on the server.
 *
 * @param {string}   content Serialised post content.
 * @param {number[]} ignored Entries not to report.
 */
export function auditContent( content, ignored ) {
	return apiFetch( {
		path: `/${ config.namespace }/audit`,
		method: 'POST',
		data: { content, ignored },
	} );
}
