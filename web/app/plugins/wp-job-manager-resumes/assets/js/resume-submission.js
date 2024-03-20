/* global resume_manager_resume_submission */

jQuery( document ).ready( function ( $ ) {
	$( '.resume-manager-add-row' ).click( function () {
		const $wrap = $( this ).closest( '.field' );
		let max_index = 0;

		$wrap.find( 'input.repeated-row-index' ).each( function () {
			if ( parseInt( $( this ).val() ) > max_index ) {
				max_index = parseInt( $( this ).val() );
			}
		} );

		const html = $( this )
			.data( 'row' )
			.replace( /%%repeated-row-index%%/g, max_index + 1 );
		$( this ).before( html );

		$( this )
			.prev()
			.find( 'input.job-manager-datepicker' )
			.trigger( 'wpJobManagerFieldAdded' );

		return false;
	} );
	$( '#submit-resume-form' ).on(
		'click',
		'.resume-manager-remove-row',
		function () {
			if (
				// eslint-disable-next-line no-alert
				confirm( resume_manager_resume_submission.i18n_confirm_remove )
			) {
				$( this ).closest( 'div.resume-manager-data-row' ).remove();
			}
			return false;
		}
	);
	$( '#submit-resume-form' ).on(
		'click',
		'.job-manager-remove-uploaded-file',
		function () {
			$( this ).closest( '.job-manager-uploaded-file' ).remove();
			return false;
		}
	);
	$(
		'.fieldset-candidate_experience .field, .fieldset-candidate_education .field, .fieldset-links .field'
	).sortable( {
		items: '.resume-manager-data-row',
		cursor: 'move',
		axis: 'y',
		scrollSensitivity: 40,
		forcePlaceholderSize: true,
		helper: 'clone',
		opacity: 0.65,
	} );

	// Confirm navigation
	let confirm_nav = false;

	if ( $( 'form#resume_preview' ).length ) {
		confirm_nav = true;
	}
	$( 'form#submit-resume-form' ).on( 'change', 'input', function () {
		confirm_nav = true;
	} );
	$( 'form#submit-resume-form, form#resume_preview' ).submit( function () {
		confirm_nav = false;
		return true;
	} );
	$( window ).bind( 'beforeunload', function ( event ) {
		if ( confirm_nav ) {
			event.preventDefault();
			event.returnValue = resume_manager_resume_submission.i18n_navigate;
			return resume_manager_resume_submission.i18n_navigate;
		}
	} );
} );
