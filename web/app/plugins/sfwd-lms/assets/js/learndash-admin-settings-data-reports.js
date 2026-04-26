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
			const container = jQuery( this ).parents(
				'.ld-global-header-new-settings'
			);
			const dataNonce = jQuery( this ).attr( 'data-nonce' );
			const dataSlug = jQuery( this ).attr( 'data-slug' );

			// Store original button HTML to use later.
			if ( ! button.data( 'original-html' ) ) {
				button.data( 'original-html', button.html() );
			}

			button.prepend( loadingIcon );

			jQuery( '.learndash-settings-page-wrap' ).prepend(
				'<div class="notice notice-info ld-reports-notice ld-reports-notice--loading">' +
					'<p>' +
					loadingIcon.clone().attr( 'aria-hidden', 'true' )[ 0 ]
						.outerHTML +
					'<span class="ld-reports-notice__text">' +
					wp.i18n.__(
						'Your report is being processed for export. Do not close or navigate away from this page until the report is ready.',
						'learndash'
					) +
					'</span>' +
					'</p>' +
					'</div>'
			);

			// disable all other buttons
			jQuery( 'button.learndash-data-reports-button' ).prop(
				'disabled',
				true
			);

			// Hide all download buttons
			jQuery(
				'table#learndash-data-reports a.learndash-data-reports-download-link'
			).hide();

			const postData = {
				action: 'learndash-data-reports',
				data: {
					init: 1,
					slug: dataSlug,
					nonce: dataNonce,
				},
			};

			learndash_data_reports_do_ajax( postData, container );
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

// eslint-disable-next-line camelcase
function learndash_data_reports_do_ajax( postData, container ) {
	if ( typeof postData === 'undefined' || postData === '' ) {
		return false;
	}

	jQuery.ajax( {
		type: 'POST',
		url: ajaxurl,
		dataType: 'json',
		cache: false,
		data: postData,
		complete() {
			// Remove loading icon and restore original button text
			jQuery( 'button.learndash-data-reports-button' ).each( function () {
				if ( jQuery( this ).data( 'original-html' ) ) {
					jQuery( this ).html(
						jQuery( this ).data( 'original-html' )
					);
				}
			} );
			// Re-enable the buttons.
			jQuery( 'button.learndash-data-reports-button' ).prop(
				'disabled',
				false
			);

			jQuery( '.ld-reports-notice--loading' ).remove();
		},
		success( replyData ) {
			if (
				typeof replyData === 'undefined' ||
				typeof replyData.data === 'undefined'
			) {
				return;
			}

			let totalCount = 0;
			if ( typeof replyData.data.total_count !== 'undefined' ) {
				totalCount = parseInt( replyData.data.total_count );
			}

			let resultCount = 0;
			if ( typeof replyData.data.result_count !== 'undefined' ) {
				resultCount = parseInt( replyData.data.result_count );
			}

			if ( resultCount < totalCount ) {
				postData.data = replyData.data;
				learndash_data_reports_do_ajax( postData, container );
			} else if (
				typeof replyData.data.report_download_link !== 'undefined' &&
				replyData.data.report_download_link !== ''
			) {
				// Remove loading icon and restore original button text
				jQuery( 'button.learndash-data-reports-button' ).each(
					function () {
						if ( jQuery( this ).data( 'original-html' ) ) {
							jQuery( this ).html(
								jQuery( this ).data( 'original-html' )
							);
						}
					}
				);
				// Re-enable the buttons.
				jQuery( 'button.learndash-data-reports-button' ).prop(
					'disabled',
					false
				);

				const checkIcon = jQuery( '.ld-svgicon__check--reports' )
					.clone()
					.removeClass( 'hidden' );

				jQuery( '.learndash-settings-page-wrap' ).prepend(
					'<div class="notice notice-success is-dismissible ld-reports-notice ld-reports-notice--ready">' +
						'<p>' +
						checkIcon[ 0 ].outerHTML +
						'<span class="ld-reports-notice__text">' +
						wp.i18n.__(
							'Your report export is now completed.',
							'learndash'
						) +
						'</span>' +
						'</p>' +
						'<button type="button" class="notice-dismiss"><span class="screen-reader-text">' +
						'<span class="screen-reader-text">' +
						wp.i18n.__( 'Dismiss this notice.', 'learndash' ) +
						'</span>' +
						'</button>' +
						'</div>'
				);

				window.location.href = replyData.data.report_download_link;
			}
		},
	} );
}
