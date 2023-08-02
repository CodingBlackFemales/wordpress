/* global resume_manager_candidate_dashboard */

jQuery( document ).ready( function ( $ ) {
	$( '.candidate-dashboard-action-delete' ).click( function () {
		// eslint-disable-next-line no-alert
		const answer = confirm(
			resume_manager_candidate_dashboard.i18n_confirm_delete
		);

		if ( answer ) return true;

		return false;
	} );
} );
