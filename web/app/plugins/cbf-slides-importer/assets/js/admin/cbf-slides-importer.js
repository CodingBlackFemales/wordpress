/**
 * CBF Slides Importer — Admin JS
 *
 * Implements the Drive file picker and import job UI using vanilla JS +
 * the Google Picker API. The Phase-3 React SPA will replace this file;
 * the REST endpoints and data shape it relies on remain unchanged.
 *
 * Globals injected by Admin\Assets::add_scripts() via wp_localize_script:
 *   cbf_slides_importer_admin_params.rest_url  — REST namespace base (cbf-si/v1/)
 *   cbf_slides_importer_admin_params.nonce     — wp_rest nonce for X-WP-Nonce header
 *   cbf_slides_importer_admin_params.ajax_url  — (reserved for legacy calls)
 *
 * @package CodingBlackFemales/SlidesImporter
 */

/* global cbf_slides_importer_admin_params, google, gapi */
( function () {
	'use strict';

	// ── Bootstrap ─────────────────────────────────────────────────────────────

	document.addEventListener( 'DOMContentLoaded', function () {
		const app = document.getElementById( 'cbf-si-app' );
		if ( ! app ) {
			return;
		}
		CbfSiApp.init( app );
	} );

	// ── REST helper ───────────────────────────────────────────────────────────

	const Api = {
		/**
		 * Fetch a cbf-si REST endpoint with the wp_rest nonce.
		 *
		 * @param {string} path   Relative to rest_url (e.g. 'drive/picker-config').
		 * @param {object} [opts] fetch() options override.
		 * @returns {Promise<any>}
		 */
		fetch( path, opts = {} ) {
			const url = cbf_slides_importer_admin_params.rest_url + path;
			return fetch( url, {
				headers: {
					'X-WP-Nonce': cbf_slides_importer_admin_params.nonce,
					'Content-Type': 'application/json',
					...( opts.headers || {} ),
				},
				...opts,
			} ).then( async ( res ) => {
				const json = await res.json();
				if ( ! res.ok ) {
					throw new Error( json.message || `HTTP ${ res.status }` );
				}
				return json;
			} );
		},

		pickerConfig() {
			return this.fetch( 'drive/picker-config' );
		},

		createJob( driveFileId, deckName ) {
			return this.fetch( 'jobs', {
				method: 'POST',
				body: JSON.stringify( { drive_file_id: driveFileId, deck_name: deckName } ),
			} );
		},

		getJob( id ) {
			return this.fetch( `jobs/${ id }` );
		},

		listJobs( page = 1 ) {
			return this.fetch( `jobs?page=${ page }&per_page=20` );
		},

		triggerImport( id ) {
			return this.fetch( `jobs/${ id }/import`, { method: 'POST' } );
		},

		cancelJob( id ) {
			return this.fetch( `jobs/${ id }/cancel`, { method: 'POST' } );
		},
	};

	// ── Google Picker ─────────────────────────────────────────────────────────

	const Picker = {
		_config: null,
		_gapiReady: false,

		/**
		 * Load the Google API client library, then open the picker.
		 */
		open( onSelected ) {
			Api.pickerConfig()
				.then( ( cfg ) => {
					this._config = cfg;
					if ( this._gapiReady ) {
						this._buildAndShow( onSelected );
					} else {
						gapi.load( 'picker', () => {
							this._gapiReady = true;
							this._buildAndShow( onSelected );
						} );
					}
				} )
				.catch( ( err ) => {
					CbfSiApp.showError( 'Could not load Drive picker: ' + err.message );
				} );
		},

		_buildAndShow( onSelected ) {
			const { access_token, folder_id } = this._config;

			// Show only Google Slides presentations inside the configured folder.
			const view = new google.picker.DocsView( google.picker.ViewId.PRESENTATIONS )
				.setParent( folder_id )
				.setIncludeFolders( false );

			const picker = new google.picker.PickerBuilder()
				.addView( view )
				.setOAuthToken( access_token )
				.setTitle( 'Select a slide deck to import' )
				.setCallback( ( data ) => {
					if ( data.action === google.picker.Action.PICKED ) {
						const doc = data.docs[ 0 ];
						onSelected( doc.id, doc.name );
					}
				} )
				.build();

			picker.setVisible( true );
		},
	};

	// ── Main app ──────────────────────────────────────────────────────────────

	const CbfSiApp = {
		_root: null,
		_jobListEl: null,
		_statusEl: null,
		_pollTimers: {},

		init( root ) {
			this._root = root;
			this._render();
			this._loadJobs();

			// Show success notice if redirected back from OAuth.
			const params = new URLSearchParams( window.location.search );
			if ( params.get( 'oauth' ) === 'success' ) {
				this.showNotice( '✓ Google Drive connected successfully.', 'success' );
			}
		},

		// ── Render skeleton ────────────────────────────────────────────────────

		_render() {
			this._root.innerHTML = `
				<div id="cbf-si-notices"></div>

				<div class="cbf-si-actions" style="margin:16px 0;">
					<button id="cbf-si-pick-btn" class="button button-primary button-large">
						⇪ Choose Slide Deck from Drive
					</button>
				</div>

				<h2 style="margin-top:24px;">Import Jobs</h2>
				<div id="cbf-si-job-list">
					<p class="cbf-si-loading">Loading…</p>
				</div>
			`;

			document.getElementById( 'cbf-si-pick-btn' )
				.addEventListener( 'click', () => this._openPicker() );

			this._jobListEl  = document.getElementById( 'cbf-si-job-list' );
			this._statusEl   = document.getElementById( 'cbf-si-notices' );
		},

		// ── Picker flow ────────────────────────────────────────────────────────

		_openPicker() {
			const btn = document.getElementById( 'cbf-si-pick-btn' );
			btn.disabled = true;
			btn.textContent = 'Loading picker…';

			Picker.open( ( fileId, fileName ) => {
				btn.disabled = false;
				btn.textContent = '⇪ Choose Slide Deck from Drive';
				this._confirmAndCreate( fileId, fileName );
			} );

			// Re-enable button if picker is dismissed without selection.
			setTimeout( () => {
				btn.disabled = false;
				btn.textContent = '⇪ Choose Slide Deck from Drive';
			}, 30000 );
		},

		_confirmAndCreate( fileId, fileName ) {
			if ( ! window.confirm( `Import "${ fileName }" into LearnDash?` ) ) {
				return;
			}
			this.showNotice( `Creating import job for "${ fileName }"…` );

			Api.createJob( fileId, fileName )
				.then( ( job ) => {
					this.showNotice( `✓ Job #${ job.id } queued — downloading and parsing…`, 'success' );
					this._loadJobs();
					this._pollJob( job.id );
				} )
				.catch( ( err ) => this.showError( 'Could not create job: ' + err.message ) );
		},

		// ── Job list ───────────────────────────────────────────────────────────

		_loadJobs() {
			Api.listJobs()
				.then( ( jobs ) => this._renderJobList( jobs ) )
				.catch( ( err ) => {
					this._jobListEl.innerHTML = `<p class="cbf-si-error">Could not load jobs: ${ this._esc( err.message ) }</p>`;
				} );
		},

		_renderJobList( jobs ) {
			if ( ! jobs.length ) {
				this._jobListEl.innerHTML = '<p>No import jobs yet. Choose a slide deck above to get started.</p>';
				return;
			}

			const rows = jobs.map( ( j ) => this._jobRow( j ) ).join( '' );
			this._jobListEl.innerHTML = `
				<table class="wp-list-table widefat fixed striped" style="margin-top:8px;">
					<thead>
						<tr>
							<th style="width:50px">#</th>
							<th>Deck name</th>
							<th style="width:130px">Status</th>
							<th style="width:160px">Created</th>
							<th style="width:220px">Actions</th>
						</tr>
					</thead>
					<tbody>${ rows }</tbody>
				</table>
				<p style="margin-top:8px;">
					<button class="button" id="cbf-si-refresh-btn">↻ Refresh</button>
				</p>
			`;

			document.getElementById( 'cbf-si-refresh-btn' )
				.addEventListener( 'click', () => this._loadJobs() );

			this._bindJobActions();
		},

		_jobRow( j ) {
			const badge   = this._statusBadge( j.status );
			const created = new Date( j.created_at + 'Z' ).toLocaleString();
			const actions = this._jobActions( j );
			return `<tr id="cbf-si-job-${ j.id }">
				<td>${ j.id }</td>
				<td>${ this._esc( j.deck_name || j.drive_file_id ) }</td>
				<td>${ badge }</td>
				<td>${ created }</td>
				<td>${ actions }</td>
			</tr>`;
		},

		_statusBadge( status ) {
			const colours = {
				pending:     '#888',
				downloading: '#0073aa',
				parsing:     '#0073aa',
				parsed:      '#00a32a',
				importing:   '#f0ad4e',
				done:        '#00a32a',
				failed:      '#d63638',
			};
			const c = colours[ status ] || '#888';
			return `<span style="display:inline-block;padding:2px 8px;border-radius:3px;background:${ c };color:#fff;font-size:12px;">${ status }</span>`;
		},

		_jobActions( j ) {
			const btns = [];

			if ( j.status === 'parsed' ) {
				btns.push( `<button class="button button-primary button-small" data-action="import" data-id="${ j.id }">Import into LearnDash</button>` );
			}
			if ( j.status === 'pending' ) {
				btns.push( `<button class="button button-small" data-action="cancel" data-id="${ j.id }">Cancel</button>` );
			}
			if ( j.status === 'done' && j.created_post_ids ) {
				try {
					const ids = JSON.parse( j.created_post_ids );
					if ( ids.length ) {
						btns.push( `<span style="color:#00a32a;font-size:12px;">✓ ${ ids.length } post${ ids.length > 1 ? 's' : '' } created</span>` );
					}
				} catch ( e ) {}
			}
			if ( j.status === 'failed' && j.error_message ) {
				btns.push( `<span style="color:#d63638;font-size:12px;" title="${ this._esc( j.error_message ) }">✗ ${ this._esc( j.error_message.substring( 0, 40 ) ) }…</span>` );
			}

			const html = `<span data-job-actions="${ j.id }">${ btns.join( ' ' ) }</span>`;

			// Wire events after insertion (delegated on the list container).
			return html;
		},

		// ── Job actions (delegated) ────────────────────────────────────────────

		_bindJobActions() {
			this._jobListEl.removeEventListener( 'click', this._onJobAction );
			this._onJobAction = ( e ) => {
				const btn = e.target.closest( '[data-action]' );
				if ( ! btn ) {
					return;
				}
				const action = btn.dataset.action;
				const id     = parseInt( btn.dataset.id, 10 );

				if ( action === 'import' ) {
					this._doImport( id );
				} else if ( action === 'cancel' ) {
					this._doCancel( id );
				}
			};
			this._jobListEl.addEventListener( 'click', this._onJobAction );
		},

		_doImport( id ) {
			if ( ! window.confirm( 'Import this deck into LearnDash now? This will create lesson/topic posts.' ) ) {
				return;
			}
			Api.triggerImport( id )
				.then( () => {
					this.showNotice( `✓ Import triggered for job #${ id }`, 'success' );
					this._pollJob( id );
					this._loadJobs();
				} )
				.catch( ( err ) => this.showError( 'Import failed: ' + err.message ) );
		},

		_doCancel( id ) {
			Api.cancelJob( id )
				.then( () => {
					this.showNotice( `Job #${ id } cancelled.` );
					this._loadJobs();
				} )
				.catch( ( err ) => this.showError( 'Cancel failed: ' + err.message ) );
		},

		// ── Polling ────────────────────────────────────────────────────────────

		_pollJob( id ) {
			clearTimeout( this._pollTimers[ id ] );
			const TERMINAL = new Set( [ 'done', 'failed', 'parsed' ] );
			const poll = () => {
				Api.getJob( id ).then( ( job ) => {
					this._loadJobs(); // refresh entire list (keeps it simple)
					if ( ! TERMINAL.has( job.status ) ) {
						this._pollTimers[ id ] = setTimeout( poll, 3000 );
					} else if ( job.status === 'parsed' ) {
						this.showNotice( `✓ Job #${ id } parsed — review the preview then click "Import into LearnDash".`, 'success' );
					} else if ( job.status === 'done' ) {
						this.showNotice( `✓ Job #${ id } complete!`, 'success' );
					} else if ( job.status === 'failed' ) {
						this.showError( `Job #${ id } failed: ${ job.error_message || 'unknown error' }` );
					}
				} ).catch( () => {
					// Network blip — retry.
					this._pollTimers[ id ] = setTimeout( poll, 5000 );
				} );
			};
			this._pollTimers[ id ] = setTimeout( poll, 3000 );
		},

		// ── Notices ────────────────────────────────────────────────────────────

		showNotice( msg, type = 'info' ) {
			const colours = { info: '#0073aa', success: '#00a32a', error: '#d63638' };
			const c = colours[ type ] || colours.info;
			this._statusEl.innerHTML = `
				<div class="notice" style="border-left-color:${ c };padding:8px 12px;margin:8px 0;">
					<p>${ this._esc( msg ) }</p>
				</div>`;
		},

		showError( msg ) {
			this.showNotice( msg, 'error' );
		},

		_esc( str ) {
			return String( str )
				.replace( /&/g, '&amp;' )
				.replace( /</g, '&lt;' )
				.replace( />/g, '&gt;' )
				.replace( /"/g, '&quot;' );
		},
	};

} )();
