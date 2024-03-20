/* global job_manager_alerts */

jQuery( document ).ready( function ( $ ) {
	const wpjm_alerts_select2_args = {
		minimumResultsForSearch: 10,
		width: '100%',
	};
	if ( 1 === parseInt( job_manager_alerts.is_rtl, 10 ) ) {
		wpjm_alerts_select2_args.dir = 'rtl';
	}

	if ( $.isFunction( $.fn.select2 ) ) {
		$( '.job-manager-enhanced-select:visible' ).select2(
			wpjm_alerts_select2_args
		);
	} else if ( $.isFunction( $.fn.chosen ) ) {
		$( '.job-manager-enhanced-select:visible' ).chosen();
	}

	$( '.job-alerts-action-delete' ).click( function () {
		// eslint-disable-next-line no-alert
		const answer = confirm( job_manager_alerts.i18n_confirm_delete );

		if ( answer ) {
			return true;
		}

		return false;
	} );
} );

window.job_manager_alerts = window.job_manager_alerts || {};

/**
 * Open the Add Alert modal and apply the search terms for the alert to be added.
 *
 * @param {HTMLDialogElement} modal
 * @param {HTMLAnchorElement} link
 * @param {Event}             event
 */
window.job_manager_alerts.open_add_alert_modal = function (
	modal,
	link,
	event
) {
	if ( ! modal ) {
		return true;
	}

	event.preventDefault();

	modal.showModal();
	const alertForm = modal.querySelector( 'form' );
	const searchInputs = document.querySelector( '.job-alert-search-terms' );

	if ( ! alertForm || ! searchInputs ) {
		return;
	}

	searchInputs.querySelectorAll( '*' ).forEach( ( input ) => input.remove() );

	const alertData = new URL( link?.href )?.searchParams;

	for ( const [ name, value ] of alertData ) {
		const input = Object.assign( document.createElement( 'input' ), {
			type: 'hidden',
			name,
			value,
		} );
		searchInputs.appendChild( input );
	}

	const alertName = alertData?.get( 'alert_name' )?.toString();
	const keywordEl = alertForm.querySelector( '.job-alert-keyword' );
	if ( keywordEl ) {
		keywordEl.innerText = alertName ?? '';
	}
};
