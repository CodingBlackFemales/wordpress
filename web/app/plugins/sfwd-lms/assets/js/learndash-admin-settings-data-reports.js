/* global learndashDataReports */
jQuery( function () {
	const loadingIcon = jQuery( '.ld-svgicon__refresh--reports' )
		.clone()
		.removeClass( 'hidden' );

	// Ensure screen readers don't see the original icon.
	jQuery( '.ld-svgicon__refresh--reports' ).attr( 'aria-hidden', 'true' );

	jQuery( 'button.learndash-data-reports-button' ).on(
		'click',
		function ( e ) {
			e.preventDefault();

			const button = jQuery( this );
			const dataNonce = button.attr( 'data-nonce' );
			const dataSlug = button.attr( 'data-slug' );

			// Store original button HTML to use later.
			if ( ! button.data( 'original-html' ) ) {
				button.data( 'original-html', button.html() );
			}

			button.prepend( loadingIcon );

			learndashDataReportsShowNotice(
				'loading',
				'info',
				loadingIcon.clone().attr( 'aria-hidden', 'true' )[ 0 ]
					.outerHTML +
					'<span class="ld-reports-notice__text">' +
					learndashDataReports.messages.export_queued +
					'</span>' +
					'<span class="ld-reports-notice__percent"></span>'
			);

			jQuery( 'button.learndash-data-reports-button' ).prop(
				'disabled',
				true
			);

			jQuery(
				'table#learndash-data-reports a.learndash-data-reports-download-link'
			).hide();

			// Fire the init request once to queue the background export, then poll the
			// status endpoint for progress. The admin-notice fallback still delivers the
			// download link if the user leaves the page.
			jQuery.ajax( {
				type: 'POST',
				url: ajaxurl,
				dataType: 'json',
				cache: false,
				data: {
					action: 'learndash-data-reports',
					data: {
						init: 1,
						slug: dataSlug,
						nonce: dataNonce,
					},
				},
				error() {
					learndashDataReportsRestoreButtons();
					jQuery( '.ld-reports-notice--loading' ).remove();
					learndashDataReportsShowError(
						learndashDataReports.messages.export_failed_start
					);
				},
				success() {
					// The export is queued and runs in the background. Keep the buttons
					// disabled and poll for progress: the percentage shows in the notice and
					// the CSV downloads automatically once it is ready. The admin-notice
					// fallback still delivers the link if the user leaves the page.
					learndashDataReportsPollStatus( dataSlug, dataNonce );
				},
			} );
		}
	);

	// Notices added in this way are not properly dismissible by default.
	jQuery( document ).on(
		'click',
		'.ld-reports-notice .notice-dismiss',
		function () {
			jQuery( this ).parents( '.ld-reports-notice' ).remove();
		}
	);
} );

/**
 * Builds a dismissible admin notice, prepends it to the settings page, and returns it.
 *
 * Centralizes the notice wrapper and dismiss button so the loading and error notices
 * share the same markup.
 *
 * @since 5.1.6
 *
 * @param {string} modifier   Notice modifier for the ld-reports-notice--* class (e.g. 'loading', 'error').
 * @param {string} noticeType WordPress notice type for the notice-* class (e.g. 'info', 'error').
 * @param {string} bodyHtml   Inner HTML rendered inside the notice paragraph.
 *
 * @return {Object} The jQuery notice element that was prepended.
 */
function learndashDataReportsShowNotice( modifier, noticeType, bodyHtml ) {
	const notice = jQuery(
		'<div class="notice notice-' +
			noticeType +
			' is-dismissible ld-reports-notice ld-reports-notice--' +
			modifier +
			'">' +
			'<p>' +
			bodyHtml +
			'</p>' +
			'<button type="button" class="notice-dismiss"><span class="screen-reader-text">' +
			learndashDataReports.messages.dismiss_notice +
			'</span></button>' +
			'</div>'
	);

	jQuery( '.learndash-settings-page-wrap' ).prepend( notice );

	return notice;
}

/**
 * Restores every export button to its original markup and re-enables the buttons
 * once the init request settles. The in-progress notice is intentionally left in
 * place: the export continues in the background and its outcome is surfaced as an
 * admin notice on the next page load.
 *
 * @since 5.1.6
 *
 * @return {void}
 */
function learndashDataReportsRestoreButtons() {
	jQuery( 'button.learndash-data-reports-button' ).each( function () {
		if ( jQuery( this ).data( 'original-html' ) ) {
			jQuery( this ).html( jQuery( this ).data( 'original-html' ) );
		}
	} );

	jQuery( 'button.learndash-data-reports-button' ).prop( 'disabled', false );
}

/**
 * Prepends a dismissible error notice to the settings page and sets its text to
 * the supplied message.
 *
 * @since 5.1.6
 *
 * @param {string} message Error message to display in the notice.
 *
 * @return {void}
 */
function learndashDataReportsShowError( message ) {
	const notice = learndashDataReportsShowNotice(
		'error',
		'error',
		'<span class="ld-reports-notice__text"></span>'
	);

	notice.find( '.ld-reports-notice__text' ).text( message );
}

/**
 * Polls the read-only export-status endpoint until the export finishes, showing the
 * percentage in the in-progress notice and auto-downloading the CSV when ready.
 *
 * The export buttons stay disabled while polling. If the status can't be read, the
 * buttons are restored and the admin-notice fallback still delivers the link on the
 * next page load.
 *
 * @since 5.1.6
 *
 * @param {string} slug  Report action slug (e.g. user-courses, user-quizzes).
 * @param {string} nonce Per-export nonce used to locate the export and verify the request.
 *
 * @return {void}
 */
function learndashDataReportsPollStatus( slug, nonce ) {
	const poll = function () {
		jQuery.ajax( {
			type: 'POST',
			url: ajaxurl,
			dataType: 'json',
			cache: false,
			data: {
				action: 'learndash_report_export_status',
				slug,
				nonce,
			},
			error() {
				jQuery( '.ld-reports-notice--loading' ).remove();
				learndashDataReportsRestoreButtons();
			},
			success( response ) {
				if ( ! response || ! response.success || ! response.data ) {
					jQuery( '.ld-reports-notice--loading' ).remove();
					learndashDataReportsRestoreButtons();
					return;
				}

				const percent = parseInt( response.data.percent, 10 );
				jQuery(
					'.ld-reports-notice--loading .ld-reports-notice__percent'
				).text( ' (' + percent + '%)' );

				if ( response.data.done ) {
					jQuery( '.ld-reports-notice--loading' ).remove();
					learndashDataReportsRestoreButtons();

					if ( response.data.report_url ) {
						window.location = response.data.report_url;
					}

					return;
				}

				setTimeout( poll, 2000 );
			},
		} );
	};

	poll();
}
