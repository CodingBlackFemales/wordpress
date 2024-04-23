/* jshint browser: true */
/* global bp, bp_zoom_vars, bp_select2, ZoomMtg */
/* @version 1.0.0 */
window.bp = window.bp || {};

( function( exports, $ ) {

	/**
	 * [Zoom description]
	 *
	 * @type {Object}
	 */
	bp.Zoom = {
		/**
		 * [start description]
		 *
		 * @return {[type]} [description]
		 */
		start: function () {
			this.setupGlobals();

			// Listen to events ("Add hooks!")
			this.addListeners();
		},

		/**
		 * [setupGlobals description]
		 *
		 * @return {[type]} [description]
		 */
		setupGlobals: function () {
			this.bp_zoom_ajax                   = false;
			this.bp_zoom_meeting_container_elem = '#bp-zoom-meeting-container';
			this.bp_zoom_webinar_container_elem = '#bp-zoom-webinar-container';
			this.select2_laguage_text           = 'en';

			this.zoom_languages = [ 'de-DE', 'es-ES', 'en-US', 'fr-FR', 'jp-JP', 'pt-PT', 'ru-RU', 'zh-CN', 'zh-TW', 'ko-KO', 'vi-VN', 'it-IT', 'pl-PL', 'tr-TR' ];
			if ( typeof bp_select2 !== 'undefined' && typeof bp_select2.i18n !== 'undefined' ) {
				this.select2_laguage_text = {
					errorLoading: function () {
						return bp_select2.i18n.errorLoading;
					},
					inputTooLong: function ( e ) {
						var n = e.input.length - e.maximum;
						return bp_select2.i18n.inputTooLong.replace( '%%', n );
					},
					inputTooShort: function ( e ) {
						return bp_select2.i18n.inputTooShort.replace( '%%', (e.minimum - e.input.length) );
					},
					loadingMore: function () {
						return bp_select2.i18n.loadingMore;
					},
					maximumSelected: function ( e ) {
						return bp_select2.i18n.maximumSelected.replace( '%%', e.maximum );
					},
					noResults: function () {
						return bp_select2.i18n.noResults;
					},
					searching: function () {
						return bp_select2.i18n.searching;
					},
					removeAllItems: function () {
						return bp_select2.i18n.removeAllItems;
					}
				};
			} else if ( typeof bp_select2 !== 'undefined' && typeof bp_select2.lang !== 'undefined' ) {
				this.select2_laguage_text = bp_select2.lang;
			}
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {
			// Meetings.
			$( '#meetings-list' ).scroll( 'scroll', this.scrollMeetings.bind( this ) );
			$( '#meetings-list' ).on( 'click', '.load-more a', this.loadMoreMeetings.bind( this ) );
			$( document ).on( 'click', '#meetings-list .meeting-item, #bp-zoom-meeting-cancel-edit', this.loadSingleMeeting.bind( this ) );
			$( document ).on( 'click', '.bp-back-to-meeting-list', this.backToMeetingList.bind( this ) );
			$( document ).on( 'click', '.bp-close-create-meeting-form', this.backToMeetingList.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-create-meeting-button', this.loadCreateMeeting.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-meeting-edit-button', this.loadEditMeeting.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-meeting-occurrence-edit-button', this.openOccurrenceEditPopup.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-meeting-occurrence-delete-button', this.openOccurrenceDeletePopup.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-only-this-meeting-edit', this.loadEditOccurrence.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-all-meeting-edit', this.loadEditMeeting.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-meeting-form-submit', this.updateMeeting.bind( this ) );
			$( document ).on( 'click', '.bp-zoom-delete-meeting', this.deleteMeeting.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-only-this-meeting-delete', this.deleteOnlyThisMeeting.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-all-meeting-delete', this.deleteAllMeetingOccurrences.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-single-meeting .toggle-password', this.togglePassword.bind( this ) );
			$( document ).on( 'click', '.recording-list-row .toggle-password', this.toggleRecordingPassword.bind( this ) );
			$( document ).on( 'click', '#copy-invitation-details', this.copyInvitationDetails.bind( this ) );
			$( document ).on( 'click', '#copy-download-link', this.copyDownloadLink.bind( this ) );
			$( document ).on( 'click', '.play_btn, .bb-shared-screen', this.openRecordingModal.bind( this ) );
			$( document ).on( 'click', '.bb-close-model', this.closeRecordingModal.bind( this ) );
			$( document ).on( 'click', '.meeting-actions-anchor', this.openMeetingActions.bind( this ) );
			$( document ).on( 'submit', '#bp-zoom-meeting-container #bp_zoom_meeting_search_form', this.searchMeetingActions.bind( this ) );
			$( document ).on( 'change', '#bp-zoom-meeting-container #bp-zoom-meeting-recorded-switch-checkbox', this.searchMeetingActions.bind( this ) );
			$( document ).on( 'change', '#bp-zoom-meeting-auto-recording', this.toggleAutoRecording.bind( this ) );
			$( document ).on( 'change', '#bp-zoom-meeting-recurring', this.toggleRecurring.bind( this ) );
			$( document ).on( 'change', '#bp-zoom-meeting-recurrence', this.toggleRecurrence.bind( this ) );
			$( document ).on( 'change', '#bp-zoom-meeting-notification', this.toggleNotification.bind( this ) );
			$( document ).on( 'change', '#bp-zoom-meeting-start-date', this.toggleDates.bind( this ) );
			$( document ).on( 'change', '#bp-zoom-meeting-repeat-interval', this.toggleRepeatInterval.bind( this ) );
			$( document ).on( 'change', '#bp-zoom-meeting-registration', this.toggleRegistration.bind( this ) );
			$( document ).on( 'ready', this.documentReady.bind( this ) );
			$( document ).on( 'bp_ajax_request', this.bp_ajax_request.bind( this ) );
			$( document ).on( 'change', '.bp-zoom-recordings-dates', this.scrollToRecordings.bind( this ) );
			$( document ).on( 'change', '#bp-zoom-meeting-timezone', this.changeTimezone.bind( this ) );
			$( document ).on( 'click', '.join-meeting-in-browser', this.joinMeetingInBrowser.bind( this ) );
			$( document ).on( 'click', '.bb-zoom-setting-tab .bb-zoom-setting-tabs a', this.settingTab.bind( this ) );
			$( document ).on( 'click', '.bb-zoom-setting-tab .bb-zoom-setting-content .copy-toggle .bb-copy-button, .bp-zoom-group-show-instructions #zoom-instruction-container .copy-toggle .bb-copy-button', this.copyText.bind( this ) );

			// Webinars.
			$( '#webinars-list' ).scroll( 'scroll', this.scrollWebinars.bind( this ) );
			$( '#webinars-list' ).on( 'click', '.load-more a', this.loadMoreWebinars.bind( this ) );
			$( document ).on( 'click', '#webinars-list .webinar-item, #bp-zoom-webinar-cancel-edit', this.loadSingleWebinar.bind( this ) );
			$( document ).on( 'click', '.bp-back-to-webinar-list', this.backToWebinarsList.bind( this ) );
			$( document ).on( 'click', '.bp-close-create-webinar-form', this.backToWebinarsList.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-create-webinar-button', this.loadCreateWebinar.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-webinar-edit-button', this.loadEditWebinar.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-webinar-occurrence-edit-button', this.openWebinarOccurrenceEditPopup.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-webinar-occurrence-delete-button', this.openWebinarOccurrenceDeletePopup.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-only-this-webinar-edit', this.loadEditWebinarOccurrence.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-all-webinar-edit', this.loadEditWebinar.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-webinar-form-submit', this.updateWebinar.bind( this ) );
			$( document ).on( 'click', '.bp-zoom-delete-webinar', this.deleteWebinar.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-only-this-webinar-delete', this.deleteOnlyThisWebinar.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-all-webinar-delete', this.deleteAllWebinarOccurrences.bind( this ) );
			$( document ).on( 'click', '#bp-zoom-single-webinar .toggle-password', this.toggleWebinarPassword.bind( this ) );
			// $( document ).on( 'click', '.recording-list-row .toggle-password', this.toggleRecordingPassword.bind( this ) );
			// $( document ).on( 'click', '#copy-download-link', this.copyDownloadLink.bind( this ) );
			// $( document ).on( 'click', '.play_btn, .bb-shared-screen', this.openRecordingModal.bind( this ) );
			// $( document ).on( 'click', '.bb-close-model', this.closeRecordingModal.bind( this ) );
			$( document ).on( 'click', '.webinar-actions-anchor', this.openWebinarActions.bind( this ) );
			$( document ).on( 'submit', '#bp-zoom-webinar-container #bp_zoom_webinar_search_form', this.searchWebinarActions.bind( this ) );
			$( document ).on( 'change', '#bp-zoom-webinar-container #bp-zoom-webinar-recorded-switch-checkbox', this.searchWebinarActions.bind( this ) );
			$( document ).on( 'change', '#bp-zoom-webinar-auto-recording', this.toggleWebinarAutoRecording.bind( this ) );
			$( document ).on( 'change', '#bp-zoom-webinar-recurring', this.toggleWebinarRecurring.bind( this ) );
			$( document ).on( 'change', '#bp-zoom-webinar-recurrence', this.toggleWebinarRecurrence.bind( this ) );
			$( document ).on( 'change', '#bp-zoom-webinar-notification', this.toggleWebinarNotification.bind( this ) );
			$( document ).on( 'change', '#bp-zoom-webinar-start-date', this.toggleWebinarDates.bind( this ) );
			$( document ).on( 'change', '#bp-zoom-webinar-repeat-interval', this.toggleWebinarRepeatInterval.bind( this ) );
			$( document ).on( 'change', '#bp-zoom-webinar-registration', this.toggleWebinarRegistration.bind( this ) );
			$( document ).on( 'change', '#bp-zoom-webinar-timezone', this.changeWebinarTimezone.bind( this ) );
			$( document ).on( 'click', '.join-webinar-in-browser', this.joinWebinarInBrowser.bind( this ) );

			$( document ).on( 'change', '.bb-toggle-switch #bp-zoom-type-switch', this.switchZoomType.bind( this ) );
			this.catchMeetingStartInBrowser();
			this.catchWebinarStartInBrowser();

			$( document ).on( 'click', '#meetings-sync', this.syncGroupMeetings.bind( this ) );
			$( document ).on( 'click', '#webinars-sync', this.syncGroupWebinars.bind( this ) );
			$( document ).on( 'click', '.bb-zoom-setting-content .bb-hide-pw, #zoom-instruction-container .bb-hide-pw', this.togglePasswordTooltip.bind( this ) );

			document.addEventListener( 'keyup', this.checkPressedKey.bind( this ) );

			$( document ).on(
				'click',
				'body.zoom',
				function ( event ) {

					if ( $( event.target ).hasClass( 'meeting-actions-anchor' ) || $( event.target ).parent().hasClass( 'meeting-actions-anchor' ) ) {
						return event;
					} else {
						$( '.meeting-actions-list.open' ).removeClass( 'open' );
					}

					if ( $( event.target ).hasClass( 'webinar-actions-anchor' ) || $( event.target ).parent().hasClass( 'webinar-actions-anchor' ) ) {
						return event;
					} else {
						$( '.webinar-actions-list.open' ).removeClass( 'open' );
					}

				}
			);

			$( document ).on(
				'click',
				'.bp-toggle-meeting-password',
				function ( e ) {
					e.preventDefault();
					var $this  = $( this );
					var $input = $this.next( '#bp-zoom-meeting-password' );
					$this.toggleClass( 'bb-eye' );
					if ( $this.hasClass( 'bb-eye' ) ) {
						$input.attr( 'type', 'text' );
					} else {
						$input.attr( 'type', 'password' );
					}
				}
			);

			$( document ).on(
				'click',
				'.bp-toggle-webinar-password',
				function ( e ) {
					e.preventDefault();
					var $this  = $( this );
					var $input = $this.next( '#bp-zoom-webinar-password' );
					$this.toggleClass( 'bb-eye' );
					if ( $this.hasClass( 'bb-eye' ) ) {
						$input.attr( 'type', 'text' );
					} else {
						$input.attr( 'type', 'password' );
					}
				}
			);

			this.mask_meeting_id();
			this.mask_webinar_id();
		},

		catchMeetingStartInBrowser: function () {
			var zoom_web_meeting = this.getLinkParams( window.location.href, 'wm' );
			var meeting_id       = this.getLinkParams( window.location.href, 'mi' );

			if ( 1 === parseInt( zoom_web_meeting ) && '' !== meeting_id && $( '.join-meeting-in-browser[data-meeting-id="' + meeting_id + '"]' ).length ) {
				$( '.join-meeting-in-browser[data-meeting-id="' + meeting_id + '"]' ).trigger( 'click' );
				setTimeout(
					function () {
						$( '#bp-zoom-dummy-web-div' ).remove();
					},
					500
				);
			}
		},

		catchWebinarStartInBrowser: function () {
			var zoom_web_webinar = this.getLinkParams( window.location.href, 'wm' );
			var webinar_id       = this.getLinkParams( window.location.href, 'wi' );

			if ( 1 === parseInt( zoom_web_webinar ) && '' !== webinar_id && $( '.join-webinar-in-browser[data-webinar-id="' + webinar_id + '"]' ).length ) {
				$( '.join-webinar-in-browser[data-webinar-id="' + webinar_id + '"]' ).trigger( 'click' );
				setTimeout(
					function () {
						$( '#bp-zoom-dummy-web-div' ).remove();
					},
					500
				);
			}
		},

		bp_ajax_request: function() {
			this.triggerCountdowns();
			this.triggerLibsOnForm();
			this.mask_meeting_id();
			this.mask_webinar_id();
		},

		documentReady: function()  {
			this.triggerCountdowns();
			this.triggerLibsOnForm();
			this.triggerFetchRecordings();
			this.triggerFetchWebinarRecordings();
		},

		scrollToRecordings: function ( e ) {
			var target          = $( e.target );
			var scrollToElement = $( '.mfp-content .bp-zoom-block-show-recordings' ).find( '[data-recorded-date="' + target.val() + '"]' );
			if ( scrollToElement.length ) {
				var total_scroll = scrollToElement.outerHeight();
				$( scrollToElement.nextAll() ).each(
					function () {
						total_scroll += $( this ).outerHeight();
					}
				);
				$( '.mfp-content .recording-list-row-wrap' ).animate(
					{
						scrollTop: $( '.mfp-content .recording-list-row-wrap' )[0].scrollHeight - total_scroll
					},
					100
				);
			}
		},

		triggerFetchRecordings:function() {
			var recording_elements = [];
			$( '.bp-zoom-meeting-recording-fetch' ).each(
				function(key,element){
					var meeting_id = $( element ).data( 'meeting-id' );
					recording_elements.push( meeting_id );
				}
			);

			this.fetchRecording( recording_elements,0 );
		},

		triggerFetchWebinarRecordings:function() {
			var recording_elements = [];
			$( '.bp-zoom-webinar-recording-fetch' ).each(
				function(key,element){
					var webinar_id = $( element ).data( 'webinar-id' );
					recording_elements.push( webinar_id );
				}
			);

			this.fetchWebinarRecording( recording_elements,0 );
		},

		fetchRecording: function(recording_elements,index) {
			var self = this;
			if ( typeof recording_elements[index] !== 'undefined' ) {
				var title         = $( document ).find( '#bp-zoom-meeting-recording-' + recording_elements[index] ).data( 'title' );
				var occurrence_id = $( document ).find( '#bp-zoom-meeting-recording-' + recording_elements[index] ).data( 'occurrence-id' );
				var block         = $( document ).find( '#bp-zoom-meeting-recording-' + recording_elements[index] ).data( 'zoom-block' );
				$.ajax(
					{
						type: 'GET',
						url: bp_zoom_vars.ajax_url,
						data: {action: 'zoom_meeting_recordings', meeting_id: recording_elements[index], title: title, block: block,occurrence_id: occurrence_id},
						success: function (response) {
							if ( response.success && typeof response.data !== 'undefined' ) {
								var recording_hidden = $( document ).find( '#bp-zoom-meeting-recording-' + recording_elements[index] );
								recording_hidden.replaceWith( response.data.contents );
								if (typeof jQuery.fn.magnificPopup !== 'undefined') {
									jQuery( '.show-recordings' ).magnificPopup(
										{
											type: 'inline',
											midClick: true,
											callbacks: {
												open: function () {
													bp.Zoom.autoScrollToDateInRecordingPopup();
												},
											}
										}
									);
								}
								// Append recording count into meeting list item on group pages.
								if ( response.data.id > 0 ) {
									var meeting_item = $( '#meetings-list' ).find( 'li.meeting-item[data-id="' + response.data.id + '"]' );
									if ( meeting_item.length ) {
										var recording_count_item = meeting_item.find( '.view-recordings' );
										if ( recording_count_item.length ) {
											if ( response.data.count > 0 ) {
												recording_count_item.replaceWith(
													'<a role="button" href="#" class="button small view-recordings bp-zoom-meeting-view-recordings">\n' +
													'\t\t\t\t\t<svg width="14" height="8" xmlns="http://www.w3.org/2000/svg"><g fill="#FFF" fill-rule="evenodd"><rect width="9.451" height="8" rx="1.451"/><path d="M10.5 1.64v4.753l1.637 1.046a.571.571 0 00.879-.482V1.055a.571.571 0 00-.884-.48L10.5 1.64z"/></g></svg>\n' +
													'\t\t\t\t\t<span class="record-count">' + response.data.count + '</span>\n' +
													'\t\t\t\t</a>'
												);
											} else {
												recording_count_item.remove();
											}
										} else if ( response.data.count > 0 ) {
											meeting_item.find( '.meeting-item-col.meeting-topic' ).append(
												'<a role="button" href="#" class="button small view-recordings bp-zoom-meeting-view-recordings">\n' +
												'\t\t\t\t\t<svg width="14" height="8" xmlns="http://www.w3.org/2000/svg"><g fill="#FFF" fill-rule="evenodd"><rect width="9.451" height="8" rx="1.451"/><path d="M10.5 1.64v4.753l1.637 1.046a.571.571 0 00.879-.482V1.055a.571.571 0 00-.884-.48L10.5 1.64z"/></g></svg>\n' +
												'\t\t\t\t\t<span class="record-count">' + response.data.count + '</span>\n' +
												'\t\t\t\t</a>'
											);
										}
									}
								}
								index = index + 1;
								self.fetchRecording( recording_elements, index );
							}
						}
					}
				);
			}
		},

		fetchWebinarRecording: function(recording_elements,index) {
			var self = this;
			if ( typeof recording_elements[index] !== 'undefined' ) {
				var title         = $( document ).find( '#bp-zoom-webinar-recording-' + recording_elements[index] ).data( 'title' );
				var occurrence_id = $( document ).find( '#bp-zoom-webinar-recording-' + recording_elements[index] ).data( 'occurrence-id' );
				var block         = $( document ).find( '#bp-zoom-webinar-recording-' + recording_elements[index] ).data( 'zoom-block' );
				$.ajax(
					{
						type: 'GET',
						url: bp_zoom_vars.ajax_url,
						data: {action: 'zoom_webinar_recordings', webinar_id: recording_elements[index], title: title, block: block,occurrence_id: occurrence_id},
						success: function (response) {
							if ( response.success && typeof response.data !== 'undefined' ) {
								var recording_hidden = $( document ).find( '#bp-zoom-webinar-recording-' + recording_elements[index] );
								recording_hidden.replaceWith( response.data.contents );
								jQuery( '.show-recordings' ).magnificPopup(
									{
										type: 'inline',
										midClick: true,
										callbacks: {
											open: function () {
												bp.Zoom.autoScrollToDateInRecordingPopup();
											},
										}
									}
								);
								// Append recording count into meeting list item on group pages.
								if ( response.data.id > 0 ) {
									var webinar_item = $( '#webinars-list' ).find( 'li.webinar-item[data-id="' + response.data.id + '"]' );
									if ( webinar_item.length ) {
										var recording_count_item = webinar_item.find( '.view-recordings' );
										if ( recording_count_item.length ) {
											if ( response.data.count > 0 ) {
												recording_count_item.replaceWith(
													'<a role="button" href="#" class="button small view-recordings bp-zoom-webinar-view-recordings">\n' +
													'\t\t\t\t\t<svg width="14" height="8" xmlns="http://www.w3.org/2000/svg"><g fill="#FFF" fill-rule="evenodd"><rect width="9.451" height="8" rx="1.451"/><path d="M10.5 1.64v4.753l1.637 1.046a.571.571 0 00.879-.482V1.055a.571.571 0 00-.884-.48L10.5 1.64z"/></g></svg>\n' +
													'\t\t\t\t\t<span class="record-count">' + response.data.count + '</span>\n' +
													'\t\t\t\t</a>'
												);
											} else {
												recording_count_item.remove();
											}
										} else if ( response.data.count > 0 ) {
											webinar_item.find( '.webinar-item-col.webinar-topic' ).append(
												'<a role="button" href="#" class="button small view-recordings bp-zoom-webinar-view-recordings">\n' +
												'\t\t\t\t\t<svg width="14" height="8" xmlns="http://www.w3.org/2000/svg"><g fill="#FFF" fill-rule="evenodd"><rect width="9.451" height="8" rx="1.451"/><path d="M10.5 1.64v4.753l1.637 1.046a.571.571 0 00.879-.482V1.055a.571.571 0 00-.884-.48L10.5 1.64z"/></g></svg>\n' +
												'\t\t\t\t\t<span class="record-count">' + response.data.count + '</span>\n' +
												'\t\t\t\t</a>'
											);
										}
									}
								}
								index = index + 1;
								self.fetchWebinarRecording( recording_elements, index );
							}
						}
					}
				);
			}
		},

		triggerCountdowns: function() {
			var countdowns = $( '.bp_zoom_countdown' );
			if ( countdowns.length ) {
				countdowns.each(
					function () {
						var _this = $( this );
						var ts    = $( this ).data( 'timer' );
						// var reload = $(this).data('reload');
						ts = parseInt( ts ) * 1000;
						$( this ).bbCountDown(
							{
								timestamp: ts,
								callback: function (days, hours, minutes, seconds) {
									var summaryTime = days + hours + minutes + seconds;
									if (summaryTime === 0) {
										_this.remove();
									}
								}
							}
						);

						setTimeout(
							function() {
								if ( _this.find( '.countDays .position:first' ).text().trim() == '0' ) {
									_this.find( '.countDays .position:first' ).hide();
									_this.find( '.countDays' ).addClass( 'digits-2' );
								} else {
									_this.find( '.countDays' ).addClass( 'digits-3' );
								}
							},
							250
						);

					}
				);
			}
		},

		triggerLibsOnForm: function() {
			$( '.copy-invitation-link' ).magnificPopup(
				{
					type: 'inline',
					midClick: true,
				}
			);

			jQuery( '.show-recordings' ).magnificPopup(
				{
					type: 'inline',
					midClick: true,
					callbacks: {
						open: function () {
							bp.Zoom.autoScrollToDateInRecordingPopup();
						},
					}
				}
			);

			jQuery( '.show-meeting-details' ).magnificPopup(
				{
					type: 'inline',
					midClick: true,
					callbacks: {
						beforeClose: function() {
							if ( this.content.hasClass( 'copy-invitation-popup-block' ) ) {
								$( '.mfp-close' ).show();
							}
						},
					}
				}
			);

			jQuery( '.show-webinar-details' ).magnificPopup(
				{
					type: 'inline',
					midClick: true,
				}
			);

			var meeting_wrapper = $( '#bp-zoom-single-meeting-wrapper' );
			if (typeof jQuery.fn.datetimepicker !== 'undefined') {

				meeting_wrapper.find( '#bp-zoom-meeting-start-date' ).datetimepicker(
					{
						format: 'Y-m-d',
						timepicker: false,
						mask: true,
						minDate: 0,
						yearStart: new Date().getFullYear(),
						defaultDate: new Date(),
						scrollMonth: false,
						scrollTime: false,
						scrollInput: false,
						onSelectDate: function (date,element) {
							meeting_wrapper.find( '#bp-zoom-meeting-end-date-time' ).datetimepicker(
								{
									minDate: element.val(),
								}
							);
						}
					}
				);

				meeting_wrapper.find( '#bp-zoom-meeting-end-date-time' ).datetimepicker(
					{
						format: 'Y-m-d',
						timepicker: false,
						mask: true,
						minDate: 0,
						defaultDate: new Date().setDate( new Date().getDate() + 6 ),
						scrollMonth: false,
						scrollTime: false,
						scrollInput: false,
					}
				);

				meeting_wrapper.find( '#bp-zoom-meeting-start-time' ).datetimepicker(
					{
						format: 'h:i',
						formatTime:	'h:i',
						datepicker: false,
						hours12: true,
						step: 30,
					}
				);

				var options = {
					placeholder: 'hh:mm',
					translation: {
						'P': {
							pattern: /0|1/, optional: false
						},
						'Q': {
							pattern: /0|1|2/, optional: false
						},
						'X': {
							pattern: /0|[1-9]/, optional: false
						},
						'Y': {
							pattern: /[0-5]/, optional: false
						},
						'Z': {
							pattern: /[0-9]/, optional: false
						},
					},
					onKeyPress: function(cep, e, field, options) {
						var masks = [ 'PX:YZ', 'PQ:YZ' ];
						var mask  = ( cep.length > 1 && cep.substr( 0,1 ) > 0 ) ? masks[1] : masks[0];
						meeting_wrapper.find( '#bp-zoom-meeting-start-time' ).mask( mask, options );
					}
				};

				meeting_wrapper.find( '#bp-zoom-meeting-start-time' ).mask( 'PX:YZ', options );
			}

			if (typeof jQuery.fn.select2 !== 'undefined') {
				meeting_wrapper.find( '#bp-zoom-meeting-timezone' ).select2(
					{
						minimumInputLength: 0,
						closeOnSelect: true,
						language: this.select2_laguage_text,
						dropdownCssClass: 'bb-select-dropdown',
						containerCssClass: 'bb-select-container',
					}
				);
			}

			var webinar_wrapper = $( '#bp-zoom-single-webinar-wrapper' );
			if (typeof jQuery.fn.datetimepicker !== 'undefined') {

				webinar_wrapper.find( '#bp-zoom-webinar-start-date' ).datetimepicker(
					{
						format: 'Y-m-d',
						timepicker: false,
						mask: true,
						minDate: 0,
						yearStart: new Date().getFullYear(),
						defaultDate: new Date(),
						scrollMonth: false,
						scrollTime: false,
						scrollInput: false,
						onSelectDate: function (date,element) {
							webinar_wrapper.find( '#bp-zoom-webinar-end-date-time' ).datetimepicker(
								{
									minDate: element.val(),
								}
							);
						}
					}
				);

				webinar_wrapper.find( '#bp-zoom-webinar-end-date-time' ).datetimepicker(
					{
						format: 'Y-m-d',
						timepicker: false,
						mask: true,
						minDate: 0,
						defaultDate: new Date().setDate( new Date().getDate() + 6 ),
						scrollMonth: false,
						scrollTime: false,
						scrollInput: false,
					}
				);

				webinar_wrapper.find( '#bp-zoom-webinar-start-time' ).datetimepicker(
					{
						format: 'h:i',
						formatTime:	'h:i',
						datepicker: false,
						hours12: true,
						step: 30,
					}
				);

				var webinar_options = {
					placeholder: 'hh:mm',
					translation: {
						'P': {
							pattern: /0|1/, optional: false
						},
						'Q': {
							pattern: /0|1|2/, optional: false
						},
						'X': {
							pattern: /0|[1-9]/, optional: false
						},
						'Y': {
							pattern: /[0-5]/, optional: false
						},
						'Z': {
							pattern: /[0-9]/, optional: false
						},
					},
					onKeyPress: function(cep, e, field, options) {
						var masks = [ 'PX:YZ', 'PQ:YZ' ];
						var mask  = ( cep.length > 1 && cep.substr( 0,1 ) > 0 ) ? masks[1] : masks[0];
						webinar_wrapper.find( '#bp-zoom-webinar-start-time' ).mask( mask, options );
					}
				};

				webinar_wrapper.find( '#bp-zoom-webinar-start-time' ).mask( 'PX:YZ', webinar_options );
			}

			if (typeof jQuery.fn.select2 !== 'undefined') {
				webinar_wrapper.find( '#bp-zoom-webinar-timezone' ).select2(
					{
						minimumInputLength: 0,
						closeOnSelect: true,
						language: this.select2_laguage_text,
						dropdownCssClass: 'bb-select-dropdown',
						containerCssClass: 'bb-select-container',
					}
				);
			}
		},

		autoScrollToDateInRecordingPopup: function() {
			var mf_content      = $( '.mfp-content' );
			var meeting_date    = $( '#bp-zoom-single-meeting' ).data( 'meeting-start-date' );
			var scrollToElement = mf_content.find( '.bp-zoom-block-show-recordings' ).find( '[data-recorded-date="' + meeting_date + '"]' );
			if ( ! scrollToElement.length ) {
				meeting_date = new Date( meeting_date );
				mf_content.find( '[data-recorded-date]' ).each(
					function () {
						var row_recording_date     = $( this ).data( 'recorded-date' );
						var row_recording_date_obj = new Date( row_recording_date );
						if ( row_recording_date_obj.getTime() === meeting_date.getTime() ) {
							scrollToElement = mf_content.find( '.bp-zoom-block-show-recordings' ).find( '[data-recorded-date="' + row_recording_date + '"]' );
								return false;
						}
					}
				);
				if ( ! scrollToElement.length ) {
					mf_content.find( '[data-recorded-date]' ).each(
						function () {
							var row_recording_date     = $( this ).data( 'recorded-date' );
							var row_recording_date_obj = new Date( row_recording_date );
							if ( row_recording_date_obj.getTime() >= meeting_date.getTime() ) {
								scrollToElement = mf_content.find( '.bp-zoom-block-show-recordings' ).find( '[data-recorded-date="' + row_recording_date + '"]' );
								return false;
							}
						}
					);
				}
			}
			if ( scrollToElement.length ) {
				var total_scroll = scrollToElement.outerHeight();
				$( scrollToElement.nextAll() ).each(
					function () {
						total_scroll += $( this ).outerHeight();
					}
				);
				mf_content.find( '.recording-list-row-wrap' ).animate(
					{
						scrollTop: mf_content.find( '.recording-list-row-wrap' )[0].scrollHeight - total_scroll
					},
					100
				);
				$( '.bp-zoom-recordings-dates' ).val( scrollToElement.data( 'recorded-date' ) );
			}
		},

		toggleAutoRecording: function(e) {
			var target = $( e.target ), form_recording_options = target.closest( 'form' ).find( '.bp-zoom-meeting-auto-recording-options' );
			if (target.is( ':checked' )) {
				form_recording_options.removeClass( 'bp-hide' );
			} else {
				form_recording_options.addClass( 'bp-hide' );
			}
		},

		toggleRecurring: function ( e ) {
			var target                 = $( e.target ),
				form_recurring_options = target.closest( 'form' ).find( '.bp-zoom-meeting-recurring-options' ),
				registration_options   = target.closest( 'form' ).find( '.bp-zoom-meeting-registration-options' );
			if ( target.is( ':checked' ) ) {
				form_recurring_options.removeClass( 'bp-hide' );
				if ( target.closest( 'form' ).find( '#bp-zoom-meeting-registration' ).is( ':checked' ) && target.closest( 'form' ).find( '#bp-zoom-meeting-recurring' ).is( ':checked' ) && [ '1', '2', '3' ].includes( target.closest( 'form' ).find( '#bp-zoom-meeting-recurrence' ).val() ) ) {
					registration_options.removeClass( 'bp-hide' );
				}
			} else {
				form_recurring_options.addClass( 'bp-hide' );
				registration_options.addClass( 'bp-hide' );
			}
		},

		formatDate: function (date) {
			var d     = new Date( date ),
				month = '' + (d.getMonth() + 1),
				day   = '' + d.getDate(),
				year  = d.getFullYear();

			if (month.length < 2) {
				month = '0' + month;
			}
			if (day.length < 2) {
				day = '0' + day;
			}

			return [year, month, day].join( '-' );
		},

		toggleDates: function(e) {
			var target             = $( e.target ),
				form               = target.closest( 'form' ),
				recurrence         = form.find( '#bp-zoom-meeting-recurrence' ),
				repeat_interval    = form.find( '#bp-zoom-meeting-repeat-interval' ),
				start_date_time    = form.find( '#bp-zoom-meeting-start-date' ),
				end_date_time      = form.find( '#bp-zoom-meeting-end-date-time' ),
				start_date         = new Date( start_date_time.val() ),
				end_date_time_date = new Date( end_date_time.val() );
			e.preventDefault();

			if ( start_date.getTime() >= end_date_time_date.getTime() ) {
				if ( recurrence.val() == '1' ) {
					start_date.setDate( start_date.getDate() + ( 6 * repeat_interval.val() ) );
					end_date_time.val( this.formatDate( start_date ) );
				}
				if ( recurrence.val() == '2' ) {
					start_date.setDate( start_date.getDate() + ( 6 * ( 7 * repeat_interval.val() ) ) );
					end_date_time.val( this.formatDate( start_date ) );
				}
				if ( recurrence.val() == '3' ) {
					start_date.setMonth( start_date.getMonth() + ( 6 * repeat_interval.val() ) );
					end_date_time.val( this.formatDate( start_date ) );
				}
			}
		},

		toggleRegistration: function ( e ) {
			var target               = $( e.target ),
				form                 = target.closest( 'form#bp_zoom_meeting_form' ),
				registration_options = form.find( '.bp-zoom-meeting-registration-options' );

			if ( target.is( ':checked' ) && form.find( '#bp-zoom-meeting-recurring' ).is( ':checked' ) && [ '1', '2', '3' ].includes( form.find( '#bp-zoom-meeting-recurrence' ).val() ) ) {
				registration_options.removeClass( 'bp-hide' );
			} else {
				registration_options.addClass( 'bp-hide' );
			}
		},

		toggleRepeatInterval: function (e) {
			var target          = $( e.target ),
				recurrence      = target.closest( 'form' ).find( '#bp-zoom-meeting-recurrence' ),
				start_date_time = target.closest( 'form' ).find( '#bp-zoom-meeting-start-date' ),
				end_date_time   = target.closest( 'form' ).find( '#bp-zoom-meeting-end-date-time' ),
				start_date      = new Date( start_date_time.val() ),
				end_date        = new Date();
			e.preventDefault();

			if (recurrence.val() == '1') {
				end_date.setDate( start_date.getDate() + (6 * target.val()) );
				end_date_time.val( this.formatDate( end_date ) );
			}
			if (recurrence.val() == '2') {
				end_date.setDate( start_date.getDate() + (6 * (7 * target.val())) );
				end_date_time.val( this.formatDate( end_date ) );
			}
			if (recurrence.val() == '3') {
				end_date.setMonth( start_date.getMonth() + (6 * target.val()) );
				end_date_time.val( this.formatDate( end_date ) );
			}
		},

		toggleRecurrence: function (e) {
			var target                  = $( e.target ),
				form                    = target.closest( 'form' ),
				form_recurrence_options = form.find( '.bp-zoom-meeting-recurring-sub-options' ),
				registration_options    = form.find( '.bp-zoom-meeting-registration-options' ),
				registration_wrapper    = form.find( '.bp-zoom-meeting-registration-wrapper' ),
				form_occurs_on_options  = form.find( '.bp-zoom-meeting-occurs-on' ),
				form_occurs_on_monthly  = form.find( '#bp-zoom-meeting-occurs-on-month' ),
				form_occurs_on_weekly   = form.find( '#bp-zoom-meeting-occurs-on-week' ),
				interval_type_label     = form.find( '#bp-zoom-meeting-repeat-interval-type' ),
				repeat_interval         = form.find( '#bp-zoom-meeting-repeat-interval' ),
				start_date_time         = form.find( '#bp-zoom-meeting-start-date' ),
				end_date_time           = form.find( '#bp-zoom-meeting-end-date-time' ),
				i                       = 1, repeat_interval_html = '',
				start_date              = new Date( start_date_time.val() ),
				occurs_on_label = form.find('.bp-zoom-meeting-occurs-on > label');
			e.preventDefault();

			if (target.val() == '-1') {
				form_recurrence_options.addClass( 'bp-hide' );
				registration_options.addClass( 'bp-hide' );
				registration_wrapper.addClass( 'bp-hide' );
			} else {
				if ( target.closest( 'form' ).find( '#bp-zoom-meeting-registration' ).is( ':checked' ) && target.closest( 'form' ).find( '#bp-zoom-meeting-recurring' ).is( ':checked' ) ) {
					registration_options.removeClass( 'bp-hide' );
				}
				registration_wrapper.removeClass( 'bp-hide' );

				if (target.val() == '1') {
					form_occurs_on_options.addClass( 'bp-hide' );
					interval_type_label.text( bp_zoom_vars.strings.day );
					repeat_interval_html = '';
					occurs_on_label.text( function(index, text) {
						return text.replace( ' *', '' );
					});
					for (i = 1; i <= 15; i++) {
						repeat_interval_html += '<option value="' + i + '">' + i + '</option>';
					}
					repeat_interval.html( repeat_interval_html );

					start_date.setDate( start_date.getDate() + ( 6 * repeat_interval.val() ) );
					end_date_time.val( this.formatDate( start_date ) );
				}
				if (target.val() == '2') {
					form_occurs_on_options.removeClass( 'bp-hide' );
					form_occurs_on_weekly.removeClass( 'bp-hide' );
					form_occurs_on_monthly.addClass( 'bp-hide' );
					interval_type_label.text( bp_zoom_vars.strings.week );
					repeat_interval_html = '';
					occurs_on_label.text( function(index, text) {
						return text + ' *';
					});
					for (i = 1; i <= 12; i++) {
						repeat_interval_html += '<option value="' + i + '">' + i + '</option>';
					}
					repeat_interval.html( repeat_interval_html );

					start_date.setDate( start_date.getDate() + ( 6 * ( 7 * repeat_interval.val() ) ) );
					end_date_time.val( this.formatDate( start_date ) );
				}
				if (target.val() == '3') {
					form_occurs_on_options.removeClass( 'bp-hide' );
					form_occurs_on_weekly.addClass( 'bp-hide' );
					form_occurs_on_monthly.removeClass( 'bp-hide' );
					interval_type_label.text( bp_zoom_vars.strings.month );
					repeat_interval_html = '';
					occurs_on_label.text( function(index, text) {
						return text.replace( ' *', '' );
					});
					for (i = 1; i <= 3; i++) {
						repeat_interval_html += '<option value="' + i + '">' + i + '</option>';
					}
					repeat_interval.html( repeat_interval_html );

					start_date.setMonth( start_date.getMonth() + ( 6 * repeat_interval.val() ) );
					end_date_time.val( this.formatDate( start_date ) );
				}

				form_recurrence_options.removeClass( 'bp-hide' );
			}
		},

		toggleNotification: function ( e ) {
			var target              = $( e.target ),
				field_wrap          = target.closest( '.bb-field-wrap' ),
				notification_select = field_wrap.find( '#bp-zoom-meeting-alert' );

			e.preventDefault();

			if ( notification_select.attr( 'disabled' ) && target.prop( 'checked' ) ) {
				notification_select.removeAttr( 'disabled' );
			} else {
				notification_select.attr( 'disabled', 'disabled' );
			}
		},

		loadCreateMeeting: function( e ) {
			e.preventDefault();
			var target   = $( e.currentTarget ),
				group_id = target.data( 'group-id' );

			$( '#bp-zoom-single-meeting-wrapper' ).empty();
			$( '#meetings-list .meeting-item' ).removeClass( 'current' );

			if ( $( this.bp_zoom_meeting_container_elem ).length ) {
				$( this.bp_zoom_meeting_container_elem ).addClass( 'bp-create-meeting' );
				$( this.bp_zoom_meeting_container_elem ).removeClass( 'bp-past-meeting' );
				$( this.bp_zoom_meeting_container_elem ).removeClass( 'bp-future-meeting' );
				$( this.bp_zoom_meeting_container_elem ).removeClass( 'bp-edit-meeting' );
				$( this.bp_zoom_meeting_container_elem ).removeClass( 'bp-single-meeting' );
			}

			this.abort_zoom_ajax.bind( this );

			this.bp_zoom_ajax = $.ajax(
				{
					type: 'GET',
					url: bp_zoom_vars.ajax_url,
					data: {action: 'zoom_meeting_create_meeting', group_id: group_id },
					success: function (response) {
						if (typeof response.data !== 'undefined' && response.data.contents) {
							$( '#bp-zoom-single-meeting-wrapper' ).html( response.data.contents );

							$( '#bp-zoom-single-meeting-wrapper' ).find( '#bp-zoom-meeting-start-date' ).datetimepicker(
								{
									format: 'Y-m-d',
									timepicker: false,
									mask: true,
									minDate: 0,
									yearStart: new Date().getFullYear(),
									defaultDate: new Date(),
									scrollMonth: false,
									scrollTime: false,
									scrollInput: false,
									onSelectDate: function (date,element) {
										$( '#bp-zoom-single-meeting-wrapper' ).find( '#bp-zoom-meeting-end-date-time' ).datetimepicker(
											{
												minDate: element.val(),
											}
										);
									}
								}
							);

							$( '#bp-zoom-single-meeting-wrapper' ).find( '#bp-zoom-meeting-end-date-time' ).datetimepicker(
								{
									format: 'Y-m-d',
									timepicker: false,
									mask: true,
									minDate: 0,
									defaultDate: new Date().setDate( new Date().getDate() + 6 ),
									scrollMonth: false,
									scrollTime: false,
									scrollInput: false,
								}
							);

							$( '#bp-zoom-single-meeting-wrapper' ).find( '#bp-zoom-meeting-start-time' ).datetimepicker(
								{
									format: 'h:i',
									formatTime:	'h:i',
									datepicker: false,
									hours12: true,
									step: 30,
								}
							);

							var options = {
								placeholder: 'hh:mm',
								translation: {
									'P': {
										pattern: /0|1/, optional: false
									},
									'Q': {
										pattern: /0|1|2/, optional: false
									},
									'X': {
										pattern: /0|[1-9]/, optional: false
									},
									'Y': {
										pattern: /[0-5]/, optional: false
									},
									'Z': {
										pattern: /[0-9]/, optional: false
									},
								},
								onKeyPress: function(cep, e, field, options) {
									var masks = [ 'PX:YZ', 'PQ:YZ' ];
									var mask  = ( cep.length > 1 && cep.substr( 0,1 ) > 0 ) ? masks[1] : masks[0];
									$( '#bp-zoom-single-meeting-wrapper' ).find( '#bp-zoom-meeting-start-time' ).mask( mask, options );
								}
							};

							$( '#bp-zoom-single-meeting-wrapper' ).find( '#bp-zoom-meeting-start-time' ).mask( 'PX:YZ', options );

							$( '#bp-zoom-single-meeting-wrapper' ).find( '#bp-zoom-meeting-timezone' ).select2(
								{
									minimumInputLength: 0,
									closeOnSelect: true,
									language: this.select2_laguage_text,
									dropdownCssClass: 'bb-select-dropdown',
									containerCssClass: 'bb-select-container',
								}
							);

							$( '.bp-zoom-meeting-left-inner .bp-zoom-meeting-members-listing #meetings-list' ).animate( {scrollTop: $( document ).height() * 50 } );
							$( '.bp-zoom-meeting-left-inner .bp-zoom-meeting-members-listing #meetings-list' ).css( 'max-height', Number( $( '#bp_zoom_meeting_form .bp-zoom-meeting-right-top' ).height() ) + 'px' );

							if ( bp_zoom_vars.group_meetings_url !== '') {
								var create_meeting_url = bp_zoom_vars.group_meetings_url + 'create-meeting';
								window.history.pushState( null, null, create_meeting_url );
							}
						}
					}
				}
			);
		},

		openOccurrenceDeletePopup: function( e ) {
			var target        = $( e.currentTarget ),
				meeting_item  = target.closest( '.meeting-item-container' ),
				occurrence_id = meeting_item.data( 'occurrence-id' );
			e.preventDefault();

			if ( typeof occurrence_id !== 'undefined' && occurrence_id != '' ) {
				$.magnificPopup.open(
					{
						items: {
							src: '#bp-zoom-delete-occurrence-popup-' + occurrence_id,
							type: 'inline'
						}
					}
				);
			}
		},

		openOccurrenceEditPopup: function( e ) {
			var target        = $( e.currentTarget ),
				meeting_item  = target.closest( '.meeting-item-container' ),
				occurrence_id = meeting_item.data( 'occurrence-id' );
			e.preventDefault();

			if ( typeof occurrence_id !== 'undefined' && occurrence_id != '' ) {
				$.magnificPopup.open(
					{
						items: {
							src: '#bp-zoom-edit-occurrence-popup-' + occurrence_id,
							type: 'inline'
						}
					}
				);
			}
		},

		loadEditOccurrence: function( e ) {
			var target        = $( e.currentTarget ),
				id            = target.data( 'id' ),
				meeting_id    = target.data( 'meeting-id' ),
				occurrence_id = target.data( 'occurrence-id' );
			e.preventDefault();

			$.magnificPopup.close();

			this.ajaxEditMeetingLoader( id, meeting_id, occurrence_id );
		},

		loadEditMeeting: function( e ) {
			var target     = $( e.currentTarget ),
				id         = target.data( 'id' ),
				meeting_id = target.data( 'meeting-id' );
			e.preventDefault();

			$.magnificPopup.close();

			this.ajaxEditMeetingLoader( id, meeting_id, '' );
		},

		ajaxEditMeetingLoader: function( id, meeting_id, occurrence_id ) {
			var self = this;

			$( '#bp-zoom-single-meeting-wrapper' ).empty();

			var data = { action: 'zoom_meeting_edit_meeting', 'id': id };
			if ( typeof occurrence_id !== 'undefined' && occurrence_id !== '' ) {
				data.occurrence_id = occurrence_id;
			}
			if ( typeof meeting_id !== 'undefined' && meeting_id !== '' ) {
				data.meeting_id = meeting_id;
			}

			if ( $( self.bp_zoom_meeting_container_elem ).length ) {
				$( self.bp_zoom_meeting_container_elem )
					.addClass( 'bp-create-meeting' )
					.removeClass( 'bp-past-meeting' )
					.removeClass( 'bp-future-meeting' )
					.removeClass( 'bp-edit-meeting' )
					.removeClass( 'bp-single-meeting' );
			}

			self.abort_zoom_ajax();

			self.bp_zoom_ajax = $.ajax(
				{
					type: 'GET',
					url: bp_zoom_vars.ajax_url,
					data: data,
					success: function (response) {
						if (typeof response.data !== 'undefined' && response.data.contents) {
							$( '#bp-zoom-single-meeting-wrapper' ).html( response.data.contents );

							$( '#bp-zoom-single-meeting-wrapper' ).find( '#bp-zoom-meeting-start-date' ).datetimepicker(
								{
									format: 'Y-m-d',
									timepicker: false,
									mask: true,
									minDate: 0,
									yearStart: new Date().getFullYear(),
									defaultDate: new Date(),
									scrollMonth: false,
									scrollTime: false,
									scrollInput: false,
									onSelectDate: function (date,element) {
										$( '#bp-zoom-single-meeting-wrapper' ).find( '#bp-zoom-meeting-end-date-time' ).datetimepicker(
											{
												minDate: element.val(),
											}
										);
									}
								}
							);

							$( '#bp-zoom-single-meeting-wrapper' ).find( '#bp-zoom-meeting-end-date-time' ).datetimepicker(
								{
									format: 'Y-m-d',
									timepicker: false,
									mask: true,
									minDate: 0,
									defaultDate: new Date().setDate( new Date().getDate() + 6 ),
									scrollMonth: false,
									scrollTime: false,
									scrollInput: false,
								}
							);

							$( '#bp-zoom-single-meeting-wrapper' ).find( '#bp-zoom-meeting-start-time' ).datetimepicker(
								{
									format: 'h:i',
									formatTime:	'h:i',
									datepicker: false,
									hours12: true,
									step: 30,
								}
							);

							var options = {
								placeholder: 'hh:mm',
								translation: {
									'P': {
										pattern: /0|1/, optional: false
									},
									'Q': {
										pattern: /0|1|2/, optional: false
									},
									'X': {
										pattern: /0|[1-9]/, optional: false
									},
									'Y': {
										pattern: /[0-5]/, optional: false
									},
									'Z': {
										pattern: /[0-9]/, optional: false
									},
								},
								onKeyPress: function(cep, e, field, options) {
									var masks = [ 'PX:YZ', 'PQ:YZ' ];
									var mask  = ( cep.length > 1 && cep.substr( 0,1 ) > 0 ) ? masks[1] : masks[0];
									$( '#bp-zoom-single-meeting-wrapper' ).find( '#bp-zoom-meeting-start-time' ).mask( mask, options );
								}
							};

							$( '#bp-zoom-single-meeting-wrapper' ).find( '#bp-zoom-meeting-start-time' ).mask( 'PX:YZ', options );

							$( '#bp-zoom-single-meeting-wrapper' ).find( '#bp-zoom-meeting-timezone' ).select2(
								{
									minimumInputLength: 0,
									closeOnSelect: true,
									language: this.select2_laguage_text,
									dropdownCssClass: 'bb-select-dropdown',
									containerCssClass: 'bb-select-container',
								}
							);

							$( '.bp-zoom-meeting-left-inner .bp-zoom-meeting-members-listing #meetings-list' ).css( 'max-height', Number( $( '#bp_zoom_meeting_form .bp-zoom-meeting-right-top' ).height() ) + 'px' );
						}
					}
				}
			);
		},

		backToMeetingList: function(e) {
			e.preventDefault();

			if ( $( this.bp_zoom_meeting_container_elem ).length ) {
				$( this.bp_zoom_meeting_container_elem ).removeClass( 'bp-create-meeting' );
				$( this.bp_zoom_meeting_container_elem ).removeClass( 'bp-past-meeting' );
				$( this.bp_zoom_meeting_container_elem ).removeClass( 'bp-future-meeting' );
				$( this.bp_zoom_meeting_container_elem ).removeClass( 'bp-edit-meeting' );
				$( this.bp_zoom_meeting_container_elem ).removeClass( 'bp-single-meeting' );
			}
		},

		loadSingleMeeting: function(e){
			var target            = $( e.target ),
				meeting_item      = target.closest( '.meeting-item' ),
				meeting_action    = meeting_item.data( 'action' ),
				meeting_zoom_type = meeting_item.data( 'zoom-type' ),
				id                = meeting_item.data( 'id' );
			e.preventDefault();
			var self = this;

			// when cancelling paren meeting editing for recurring meeting, reload the page.
			if ( 'edit-cancel' === meeting_action && 'meeting' === meeting_zoom_type ) {
				window.location.reload();
				return false;
			}

			if ( $( this.bp_zoom_meeting_container_elem ).length ) {
				$( this.bp_zoom_meeting_container_elem ).removeClass( 'bp-create-meeting' );
				$( this.bp_zoom_meeting_container_elem ).removeClass( 'bp-past-meeting' );
				$( this.bp_zoom_meeting_container_elem ).removeClass( 'bp-future-meeting' );
				$( this.bp_zoom_meeting_container_elem ).removeClass( 'bp-edit-meeting' );
				$( this.bp_zoom_meeting_container_elem ).addClass( 'bp-single-meeting' );

				if ( $( '.bp-navs.group-subnav' ).find( 'li.bp-groups-tab.current.selected' ).hasClass( 'meetings' ) ) {
					$( this.bp_zoom_meeting_container_elem ).addClass( 'bp-future-meeting' );
				} else if ( $( '.bp-navs.group-subnav' ).find( 'li.bp-groups-tab.current.selected' ).hasClass( 'past-meetings' ) ) {
					$( this.bp_zoom_meeting_container_elem ).addClass( 'bp-past-meeting' );
				}
			}

			if ( target.hasClass( 'view-recordings' ) || target.hasClass( 'dashicons' ) || meeting_item.hasClass( 'current' ) ) {
				return false;
			}

			$( '#meetings-list .meeting-item' ).removeClass( 'current' );
			$( '#meetings-list .meeting-item[data-id=' + id + ']' ).addClass( 'current' );

			$( '#bp-zoom-single-meeting-wrapper' ).empty();

			this.abort_zoom_ajax.bind( this );

			this.bp_zoom_ajax = $.ajax(
				{
					type: 'GET',
					url: bp_zoom_vars.ajax_url,
					data: {action: 'zoom_meeting_get_single_meeting', 'id': id},
					success: function (response) {
						if (typeof response.data !== 'undefined' && response.data.contents) {
							$( '#bp-zoom-single-meeting-wrapper' ).html( response.data.contents );

							if ( bp_zoom_vars.group_meetings_url !== '') {
								var meeting_url = bp_zoom_vars.group_meetings_url + 'meetings/' + id;
								window.history.pushState( null, null, meeting_url );
							}
						}

						$( '#bp-zoom-single-meeting-wrapper' ).find( '#copy-invitation-link' ).magnificPopup(
							{
								type:'inline',
								midClick: true // Allow opening popup on middle mouse click. Always set it to true if you don't provide alternative source in href.
							}
						);

						$( '#bp-zoom-single-meeting-wrapper' ).find( '.show-recordings' ).magnificPopup(
							{
								type:'inline',
								midClick: true,
								callbacks: {
									open: function () {
										bp.Zoom.autoScrollToDateInRecordingPopup();
									},
								}
							}
						);

						self.mask_meeting_id();
						self.triggerCountdowns();

						$( '.bp-zoom-meeting-left-inner .bp-zoom-meeting-members-listing #meetings-list' ).css( 'max-height', Number( $( '#bp_zoom_meeting_form .bp-zoom-meeting-right-top' ).height() ) + 'px' );
					}
				}
			);
		},

		scrollMeetings: function(event) {
			if ( event.target.id === 'meetings-list' ) { // or any other filtering condition.
				var el = event.target;
				if ( el.scrollTop + el.offsetHeight >= el.scrollHeight && ! el.classList.contains( 'loading' ) ) {
					var load_more = $( el ).find( '.load-more' );
					if ( load_more.length ) {
						el.classList.add( 'loading' );
						load_more.find( 'a' ).trigger( 'click' );
					}
				}
			}
		},

		loadMoreMeetings: function (e) {
			var _this = $( e.currentTarget );
			e.preventDefault();
			var self = this;

			if (_this.hasClass( 'loading' )) {
				return false;
			}

			_this.addClass( 'loading' );

			var recorded = false;
			if ( $( '#bp-zoom-meeting-recorded-switch-checkbox' ).is( ':checked' ) ) {
				recorded = true;
			}

			$.ajax(
				{
					type: 'GET',
					url: bp_zoom_vars.ajax_url,
					data: {
						action: 'zoom_meeting_load_more',
						'page': this.getLinkParams( _this.prop( 'href' ), 'acpage' ),
						'search_terms': $( '#bp-zoom-meeting-container #bp_zoom_meeting_search' ).val(),
						'recorded'    : recorded,
						'past'        : $( '#bp-zoom-meeting-container #bp-zoom-meeting-recorded-switch-checkbox' ).length,
					},
					success: function (response) {
						if (typeof response.data !== 'undefined' && response.data.contents) {
							_this.closest( '.load-more' ).replaceWith( response.data.contents );
						}
						_this.removeClass( 'loading' );
						$( '#meetings-list' ).removeClass( 'loading' );
						self.mask_meeting_id();
						$( '.bp-zoom-meeting-left-inner .bp-zoom-meeting-members-listing #meetings-list' ).css( 'max-height', Number( $( '#bp_zoom_meeting_form' ).height() ) - 140 + 'px' );
					}
				}
			);
		},

		updateMeeting: function (e) {
			var _this = $( e.currentTarget );
			e.preventDefault();

			if (_this.hasClass( 'loading' ) || _this.hasClass( 'disabled' )) {
				return false;
			}

			var self = this;

			_this.addClass( 'loading' );

			_this.parents( '.bp-meeting-fields-wrap' ).find( '.bp-feedback.error' ).remove();

			var form_data = _this.closest( 'form' ).serialize();

			this.abort_zoom_ajax.bind( this );

			this.bp_zoom_ajax = $.ajax(
				{
					type: 'POST',
					url: bp_zoom_vars.ajax_url,
					data: form_data,
					success: function (response) {
						var error_html = '';
						if (response.success) {
							if (typeof response.data !== 'undefined') {
								if ( response.data.redirect_url !== '' ) {
									window.location.href = response.data.redirect_url;
									return false;
								} else {
									window.location.reload();
								}
							}
						} else {
							if ( response.data.errors ) {
								for ( var er in response.data.errors ) {
									error_html = '<aside class="bp-feedback error">' +
									'<span class="bp-icon" aria-hidden="true"></span>' +
									'<p>' + response.data.errors[er].message + '</p>' +
									'</aside>';
									_this.parents( '.bp-meeting-fields-wrap' ).prepend( error_html );
								}
							} else if ( response.data.error ) {
								error_html = '<aside class="bp-feedback error">' +
									'<span class="bp-icon" aria-hidden="true"></span>' +
									'<p>' + response.data.error + '</p>' +
								'</aside>';
								_this.parents( '.bp-meeting-fields-wrap' ).prepend( error_html );
							}
							_this.removeClass( 'loading' );

							$( 'html, body' ).animate( { scrollTop: $( '#bp-zoom-single-meeting-wrapper' ).offset().top - 100 }, 500 );
						}
						self.mask_meeting_id();
					}
				}
			);
		},

		deleteOnlyThisMeeting: function( e ) {
			var target        = $( e.target ),
				id            = target.data( 'id' ),
				meeting_id    = target.data( 'meeting-id' ),
				occurrence_id = target.data( 'occurrence-id' );
			target.addClass( 'loading' );
			e.preventDefault();

			this.ajaxDeleteMeeting( id, meeting_id, occurrence_id );
		},

		deleteAllMeetingOccurrences: function( e ) {
			var target     = $( e.target ),
				id         = target.data( 'id' ),
				meeting_id = target.data( 'meeting-id' );
			target.addClass( 'loading' );
			e.preventDefault();

			target.addClass( 'loading' );

			this.ajaxDeleteMeeting( id, meeting_id );
		},

		ajaxDeleteMeeting: function( id, meeting_id, occurrence_id ) {
			var self = this;

			var data = {
				'action': 'zoom_meeting_delete',
				'meeting_id': meeting_id,
				'id': id,
				'_wpnonce': bp_zoom_vars.meeting_delete_nonce,
			};

			if ( typeof occurrence_id !== 'undefined' && occurrence_id !== '' ) {
				data.occurrence_id = occurrence_id;
			}

			self.abort_zoom_ajax.bind( self );

			self.bp_zoom_ajax = $.ajax(
				{
					type: 'POST',
					url: bp_zoom_vars.ajax_url,
					data: data,
					success: function (response) {
						if (true === response.data.deleted && '1' === bp_zoom_vars.is_single_meeting) {
							if ( true === response.data.is_past && bp_zoom_vars.group_meetings_past_url !== '') {
								window.location.href = bp_zoom_vars.group_meetings_past_url;
							} else if (bp_zoom_vars.group_meetings_url !== '') {
								window.location.href = bp_zoom_vars.group_meetings_url;
							}
							return false;
						}
					}
				}
			);
		},

		deleteMeeting: function (e) {
			var target       = $( e.target ),
				meeting_item = target.parents( '.meeting-item-container' ),
				meeting_id   = meeting_item.data( 'meeting-id' ),
				id           = meeting_item.data( 'id' );
			e.preventDefault();

			if ( ! confirm( bp_zoom_vars.meeting_confirm_msg ) ) {
				return false;
			}

			this.ajaxDeleteMeeting( id, meeting_id );
		},

		togglePassword: function (e) {
			var _this = $( e.currentTarget ), meeting_row = _this.closest( '.single-meeting-item' );
			e.preventDefault();

			if (_this.hasClass( 'show-pass' )) {
				_this.removeClass( 'on' );
				meeting_row.find( '.toggle-password.hide-pass' ).addClass( 'on' );
				meeting_row.find( '.hide-password' ).removeClass( 'on' );
				meeting_row.find( '.show-password' ).addClass( 'on' );
			} else {
				_this.removeClass( 'on' );
				meeting_row.find( '.toggle-password.show-pass' ).addClass( 'on' );
				meeting_row.find( '.show-password' ).removeClass( 'on' );
				meeting_row.find( '.hide-password' ).addClass( 'on' );
			}
		},

		toggleRecordingPassword: function(e) {
			var _this = $( e.currentTarget ), recording_row = _this.closest( '.recording-list-row' );
			e.preventDefault();

			if (_this.hasClass( 'show-pass' )) {
				recording_row.find( '.toggle-password.show-pass' ).addClass( 'bp-hide' );
				recording_row.find( '.show-password' ).removeClass( 'bp-hide' );
			} else {
				recording_row.find( '.toggle-password.show-pass' ).removeClass( 'bp-hide' );
				recording_row.find( '.show-password' ).addClass( 'bp-hide' );
			}
		},

		copyInvitationDetails: function (e) {
			var target = $( e.currentTarget );
			e.preventDefault();

			if ( target.hasClass( 'copied' ) ) {
				return false;
			}

			var meeting_invitation = $( '#meeting-invitation' ), button_text = target.text();
			meeting_invitation.select();
			try {
				var successful = document.execCommand( 'copy' );
				// var msg = successful ? 'successful' : 'unsuccessful';
				if (successful) {
					target.addClass( 'copied' );
					target.html( target.data( 'copied' ) );

					setTimeout(
						function () {
							target.removeClass( 'copied' );
							target.html( button_text );
						},
						3000
					);
				}
			} catch (err) {
				console.log( 'Oops, unable to copy' );
			}
		},

		copyDownloadLink: function (e) {
			var _this       = $( e.currentTarget ),
				button_text = _this.html();
			e.preventDefault();

			if ( _this.hasClass( 'copied' ) ) {
				return false;
			}

			var textArea   = document.createElement( 'textarea' );
			textArea.value = _this.data( 'download-link' );
			if ( _this.closest( '.bp-zoom-block-show-recordings' ).length ) {
				_this.closest( '.bp-zoom-block-show-recordings' )[0].appendChild( textArea );
			} else {
				document.body.appendChild( textArea );
			}
			textArea.select();
			try {
				var successful = document.execCommand( 'copy' );
				// var msg = successful ? 'successful' : 'unsuccessful';
				if (successful) {
					_this.addClass( 'copied' );
					_this.html( _this.data( 'copied' ) );

					setTimeout(
						function () {
							_this.removeClass( 'copied' );
							_this.html( button_text );
						},
						3000
					);
				}
			} catch (err) {
				console.log( 'Oops, unable to copy' );
			}
			if ( _this.closest( '.bp-zoom-block-show-recordings' ).length ) {
				_this.closest( '.bp-zoom-block-show-recordings' )[0].removeChild( textArea );
			} else {
				document.body.removeChild( textArea );
			}
		},

		getLinkParams: function (url, param) {
			var qs;
			if (url) {
				qs = (-1 !== url.indexOf( '?' )) ? '?' + url.split( '?' )[1] : '';
			} else {
				qs = document.location.search;
			}

			if ( ! qs) {
				return null;
			}

			var params = qs.replace( /(^\?)/, '' ).split( '&' ).map(
				function (n) {
					return n = n.split( '=' ), this[n[0]] = n[1], this;
				}.bind( {} )
			)[0];

			if (param) {
				return params[param];
			}

			return params;
		},

		closeCreateMeetingModal: function (event) {
			event.preventDefault();

			$( '#bp-meeting-create' ).hide();
		},

		openRecordingModal: function(e) {
			var _this = $( e.currentTarget );
			e.preventDefault();

			_this.closest( '.recording-list-row' ).find( '.bb-media-model-wrapper' ).show();
		},

		closeRecordingModal: function(e) {
			e.preventDefault();

			if ( $( '.bb-media-model-wrapper' ).find( 'video' ).length > 0 ) {
				$( '.bb-media-model-wrapper' ).find( 'video' ).get( 0 ).pause();
			}

			if ( $( '.bb-media-model-wrapper' ).find( 'audio' ).length > 0 ) {
				$( '.bb-media-model-wrapper' ).find( 'audio' ).get( 0 ).pause();
			}

			$( '.bb-media-model-wrapper' ).hide();
		},

		checkPressedKey: function( e ) {
			var self = this;
			e        = e || window.event;
			switch ( e.keyCode ) {
				case 27: // escape key.
					self.closeRecordingModal( e );
					break;
			}
		},

		openMeetingActions: function(e) {
			var _this = $( e.currentTarget );
			e.preventDefault();

			_this.next( '.meeting-actions-list' ).toggleClass( 'open' );
		},

		searchMeetingActions: function(e) {
			var _this   = $( e.currentTarget ),
				self_id = _this.attr( 'id' ),
				self    = this;

			if ('bp-zoom-meeting-recorded-switch-checkbox' !== self_id) {
				e.preventDefault();
			}

			var recorded = false;
			if ( $( '#bp-zoom-meeting-recorded-switch-checkbox' ).is( ':checked' ) ) {
				recorded = true;
			}

			$( '#bp-zoom-meeting-container #bp-zoom-dropdown-options-loader' ).show();

			var page  = 1;
			var param = {
				'action'      : 'zoom_meeting_search',
				'recorded'    : recorded,
				'page'        : page,
				'search_terms': $( '#bp-zoom-meeting-container #bp_zoom_meeting_search' ).val(),
				'past'        : $( '#bp-zoom-meeting-container #bp-zoom-meeting-recorded-switch-checkbox' ).length,
			};

			$.ajax(
				{
					type: 'GET',
					url: bp_zoom_vars.ajax_url,
					async: true,
					data: param,
					success: function (response) {
						if ( typeof response.data !== 'undefined' && response.data.contents) {
							$( '#bp-zoom-meeting-container .bp-zoom-meeting-left-inner #meetings-list' ).html( response.data.contents );

							var id = $( '#bp-zoom-single-meeting-wrapper' ).find( '.meeting-item-container' ).data( 'id' );
							if ( id == 0 || id == null || typeof id === 'undefined' ) {
								if ( $( '#bp_zoom_meeting_form' ).length && $( '#bp_zoom_meeting_form' ).find( '#bp-zoom-meeting-id' ).length ) {
									id = $( '#bp_zoom_meeting_form' ).find( '#bp-zoom-meeting-id' ).val();
								}
							}
							if ( id ) {
								$( '#meetings-list' ).find( '.meeting-item[data-id="' + id + '"]' ).addClass( 'current' );
							}
						}

						$( '#bp-zoom-meeting-container #bp-zoom-dropdown-options-loader' ).hide();
						self.mask_meeting_id();
					}
				}
			);

		},

		changeTimezone: function ( e ) {
			var _this        = $( e.target );
			var currentDate  = new Date( new Date().toLocaleDateString( 'en-US', { timeZone: _this.val() } ) );
			var args         = {
				minDate: this.formatDate( currentDate )
			};
			var selectedDate = new Date( jQuery( '#bp-zoom-meeting-start-date' ).val() );
			if ( selectedDate < currentDate ) {
				args.value = this.formatDate( currentDate );
			}
			jQuery( '#bp-zoom-meeting-start-date' ).datetimepicker( args );
		},

		abort_zoom_ajax: function () {
			if (this.bp_zoom_ajax !== false) {
				this.bp_zoom_ajax.abort();
				this.bp_zoom_ajax = false;
			}
		},

		mask_meeting_id: function() {
			if ( typeof jQuery.fn.mask !== 'undefined' ) {
				$( '#meetings-list' ).find( '.meeting-id' ).mask( 'AA: 000 0000 0000' );
				$( '#bp-zoom-single-meeting' ).find( '.meeting-id' ).mask( '000 0000 0000' );
				$( '.zoom-meeting-id' ).mask( '000 0000 0000' );
				$( document ).find( '.bb-meeting-id' ).mask( 'AA: 000 0000 0000' );
			}
		},

		syncGroupMeetings: function (e) {
			var _this    = $( e.currentTarget ),
				group_id = _this.data( 'group-id' ),
				offset   = 0;
			e.preventDefault();

			_this.addClass( 'loading' );

			this.bp_zoom_sync_function( offset, group_id );
		},

		bp_zoom_sync_function: function (offset, group_id) {
			var self = this;
			$.ajax(
				{
					type: 'POST',
					url: bp_zoom_vars.ajax_url,
					data: {
						'action': 'zoom_meetings_sync',
						'group_id': group_id,
						'offset': offset,
					},
					success: function (response) {
						if (typeof response.success !== 'undefined') {
							if (response.success && typeof response.data !== 'undefined') {
								if ('running' === response.data.status) {
									self.bp_zoom_sync_function( response.data.offset, group_id );
								} else {
									$( '#meetings-sync' ).removeClass( 'loading' );
									if ( response.data.redirect_url ) {
										window.location.href = response.data.redirect_url;
									} else {
										window.location.reload();
									}
									return false;
								}
							} else {
								$( '#meetings-sync' ).removeClass( 'loading' );
								if ( response.data.redirect_url ) {
									window.location.href = response.data.redirect_url;
								} else {
									window.location.reload();
								}
								return false;
							}
						}
					},
					error: function () {
						$( '#meetings-sync' ).removeClass( 'loading' );
						return false;
					}
				}
			);
		},

		joinMeetingInBrowser: function (e) {
			var _this       = $( e.currentTarget ), scripts_loaded = 0;
			var stylesArray = $( 'style, link[rel="stylesheet"]' ),i = 0;

			e.preventDefault();

			if (
				(
					typeof bp_zoom_vars.scripts === 'undefined' &&
					0 === bp_zoom_vars.scripts.length
				) ||
				! bp_zoom_vars.is_zoom_sdk
			) {
				return false;
			}

			var dummydiv   = document.createElement( 'div' );
			dummydiv.style = 'position:absolute;z-index:9999;top: 0;background-color: black;width: 99999999px;height: 999999999999px;';
			document.body.appendChild( dummydiv );

			// Add Zoom Style dependencies.
			for (i in bp_zoom_vars.styles ) {
				$(
					'<link/>',
					{
						rel: 'stylesheet',
						type: 'text/css',
						href: bp_zoom_vars.styles[i]
					}
				).appendTo( 'head' );
			}

			for (i in bp_zoom_vars.scripts) {
				var script  = document.createElement( 'script' );
				script.type = 'text/javascript';
				script.src  = bp_zoom_vars.scripts[i];

				// ie.
				if ( script.readyState ) {
					/* jshint ignore:start */
					script.onreadystatechange = function(){
						if ( script.readyState == 'loaded' || script.readyState == 'complete' ) {
							script.onreadystatechange = null;
							scripts_loaded++;
						}
					};
					/* jshint ignore:end */
					// normal browsers.
				} else {
					/* jshint ignore:start */
					script.onload = function(){
						scripts_loaded++;
					};
					/* jshint ignore:end */
				}
				document.head.appendChild( script );
			}

			var loadscripts = setInterval(
				function(){
					if ( scripts_loaded >= bp_zoom_vars.scripts.length && typeof ZoomMtg !== 'undefined' ) {
						clearInterval( loadscripts );

						// Add needed fixes
						// var bp_zoom_in_browser_style = $('<style>#wc-footer .btn-default { color: #333; background-color: transparent; border-color: transparent;}.security-option-menu__pop-menu > li.selected > a:before, .popmenu > li.selected > a:before{left: 5px;top: 6px;}</style>');
						// bp_zoom_in_browser_style.appendTo("head");

						document.body.removeChild( dummydiv );
						// Remove Theme styles.
						stylesArray.remove();

						// Added special support for Japanese language.
					if ( bp_zoom_vars.lang === 'ja' ) {
						bp_zoom_vars.lang = 'jp-JP';

						// Added special support for Korean language.
					} else if ( bp_zoom_vars.lang === 'ko-KR' ) {
						bp_zoom_vars.lang = 'ko-KO';

						// Added special support for Vietnamese language.
					} else if ( bp_zoom_vars.lang === 'vi' ) {
						bp_zoom_vars.lang = 'vi-VN';
					}

					if (
						typeof bp_zoom_vars.lang !== 'undefined' &&
						-1 !== $.inArray( bp_zoom_vars.lang, bp.Zoom.zoom_languages )
					) {
						ZoomMtg.i18n.load( bp_zoom_vars.lang );
					}ZoomMtg.preLoadWasm();
						ZoomMtg.prepareJssdk();

						// var testTool = window.BpZoomTestTool;
						// var meetingId = $(this).data('meeting-id');
						// var meetingPwd = $(this).data('meeting-pwd');
						// var stmUserName = 'Local' + ZoomMtg.getJSSDKVersion()[0] + testTool.detectOS() + '#' + testTool.getBrowserInfo();
						$( '.zoom-theatre' ).show();

						$( '#zmmtg-root' ).addClass( 'active' );
						var meetConfig = {
							meetingNumber: _this.data( 'meeting-id' ),
							userName: bp_zoom_vars.user.name,
							passWord: _this.data( 'meeting-pwd' ),
							leaveUrl: bp_zoom_vars.home_url,
							role: _this.data( 'is-host' ) == '1' ? 1 : 0,
						};

						var signature = _this.data( 'meeting-sign' ),
							sdkKey    = _this.data( 'meeting-sdk' );

						ZoomMtg.init(
							{
								leaveUrl: meetConfig.leaveUrl,
								isSupportAV: true,
								success: function () {
									ZoomMtg.join(
										{
											sdkKey: sdkKey,
											signature: signature,
											meetingNumber: meetConfig.meetingNumber,
											passWord: meetConfig.passWord,
											userName: meetConfig.userName,
											success: function (res) {
												console.log( res );
												console.log( 'join meeting success' );
											},
											error: function (res) {
												console.log( res );
											}
										}
									);
								},
								error: function (res) {
									console.log( res );
								}
							}
						);
					} else {
						console.log( 'ZoomMtg is not defined yet and scripts not loaded!' );
					}
				},
				500
			);

			setTimeout(
				function(){
					clearInterval( loadscripts );
				},
				20000
			);
		},

		settingTab: function(e) {
			e.preventDefault();
			var $this = $( e.target );
			if ( ! $this.hasClass( 'active-tab' ) ) {
				$this.closest( '.bb-zoom-setting-tabs' ).find( '.active-tab' ).removeClass( 'active-tab' ).removeAttr( 'aria-selected' );
				$this.addClass( 'active-tab' ).attr( 'aria-selected', 'true' );
				$this.closest( '.bb-zoom-setting-tab' ).find( '.bb-zoom-setting-content-tab' ).removeClass( 'active-tab' );
				$( $this.attr( 'href' ) ).addClass( 'active-tab' );
			}
		},

		copyText: function(e) {
			var $this = $( e.currentTarget );
			if( $this.hasClass( 'copied' ) ) {
				return;
			}
			$this.addClass( 'copied' );
			var attrValue = $this.attr( 'data-balloon' );
			$this.attr( 'data-balloon', $this.attr( 'data-copied-text' ) + '!' );
			$this.closest( '.copy-toggle' ).find( 'input').trigger( 'select' );
			document.execCommand( 'copy' );
			$this.closest( '.copy-toggle' ).find( 'input').blur();
			setTimeout( function() {
				$this.attr( 'data-balloon', attrValue );
				$this.removeClass( 'copied' );
			}, 1000 );
		},

		switchZoomType: function (e) {
			var toggleSwitch   = jQuery( e.currentTarget ).closest( '.bb-toggle-switch' );
			var meetingTooltip = toggleSwitch.data( 'meeting-tooltip' );
			var webinarTooltip = toggleSwitch.data( 'webinar-tooltip' );

			if ( jQuery( e.currentTarget ).is( ':checked' ) ) {
				toggleSwitch.attr( 'data-bp-tooltip',webinarTooltip );
			} else {
				toggleSwitch.attr( 'data-bp-tooltip',meetingTooltip );
			}
		},

		scrollWebinars: function(event) {
			if ( event.target.id === 'webinars-list' ) { // or any other filtering condition.
				var el = event.target;
				if ( el.scrollTop + el.offsetHeight >= el.scrollHeight && ! el.classList.contains( 'loading' ) ) {
					var load_more = $( el ).find( '.load-more' );
					if ( load_more.length ) {
						el.classList.add( 'loading' );
						load_more.find( 'a' ).trigger( 'click' );
					}
				}
			}
		},

		loadMoreWebinars: function (e) {
			var _this = $( e.currentTarget );
			e.preventDefault();
			var self = this;

			if (_this.hasClass( 'loading' )) {
				return false;
			}

			_this.addClass( 'loading' );

			var recorded = false;
			if ( $( '#bp-zoom-webinar-recorded-switch-checkbox' ).is( ':checked' ) ) {
				recorded = true;
			}

			$.ajax(
				{
					type: 'GET',
					url: bp_zoom_vars.ajax_url,
					data: {
						action: 'zoom_webinar_load_more',
						'page': this.getLinkParams( _this.prop( 'href' ), 'acpage' ),
						'search_terms': $( '#bp-zoom-webinar-container #bp_zoom_webinar_search' ).val(),
						'recorded'    : recorded,
						'past'        : $( '#bp-zoom-webinar-container #bp-zoom-webinar-recorded-switch-checkbox' ).length,
					},
					success: function (response) {
						if (typeof response.data !== 'undefined' && response.data.contents) {
							_this.closest( '.load-more' ).replaceWith( response.data.contents );
						}
						_this.removeClass( 'loading' );
						$( '#webinars-list' ).removeClass( 'loading' );
						self.mask_webinar_id();
					}
				}
			);
		},

		backToWebinarsList: function(e) {
			e.preventDefault();

			if ( $( this.bp_zoom_webinar_container_elem ).length ) {
				$( this.bp_zoom_webinar_container_elem ).removeClass( 'bp-create-webinar' );
				$( this.bp_zoom_webinar_container_elem ).removeClass( 'bp-past-webinar' );
				$( this.bp_zoom_webinar_container_elem ).removeClass( 'bp-future-webinar' );
				$( this.bp_zoom_webinar_container_elem ).removeClass( 'bp-edit-webinar' );
				$( this.bp_zoom_webinar_container_elem ).removeClass( 'bp-single-webinar' );
			}
		},

		loadCreateWebinar: function( e ) {
			e.preventDefault();
			var target   = $( e.currentTarget ),
				group_id = target.data( 'group-id' );

			$( '#bp-zoom-single-webinar-wrapper' ).empty();
			$( '#webinars-list .webinar-item' ).removeClass( 'current' );

			if ( $( this.bp_zoom_webinar_container_elem ).length ) {
				$( this.bp_zoom_webinar_container_elem ).addClass( 'bp-create-webinar' );
				$( this.bp_zoom_webinar_container_elem ).removeClass( 'bp-past-webinar' );
				$( this.bp_zoom_webinar_container_elem ).removeClass( 'bp-future-webinar' );
				$( this.bp_zoom_webinar_container_elem ).removeClass( 'bp-edit-webinar' );
				$( this.bp_zoom_webinar_container_elem ).removeClass( 'bp-single-webinar' );
			}

			this.abort_zoom_ajax.bind( this );

			this.bp_zoom_ajax = $.ajax(
				{
					type: 'GET',
					url: bp_zoom_vars.ajax_url,
					data: {action: 'zoom_webinar_create_webinar', group_id: group_id },
					success: function (response) {
						if (typeof response.data !== 'undefined' && response.data.contents) {
							$( '#bp-zoom-single-webinar-wrapper' ).html( response.data.contents );

							$( '#bp-zoom-single-webinar-wrapper' ).find( '#bp-zoom-webinar-start-date' ).datetimepicker(
								{
									format: 'Y-m-d',
									timepicker: false,
									mask: true,
									minDate: 0,
									yearStart: new Date().getFullYear(),
									defaultDate: new Date(),
									scrollMonth: false,
									scrollTime: false,
									scrollInput: false,
									onSelectDate: function (date,element) {
										$( '#bp-zoom-single-webinar-wrapper' ).find( '#bp-zoom-webinar-end-date-time' ).datetimepicker(
											{
												minDate: element.val(),
											}
										);
									}
								}
							);

							$( '#bp-zoom-single-webinar-wrapper' ).find( '#bp-zoom-webinar-end-date-time' ).datetimepicker(
								{
									format: 'Y-m-d',
									timepicker: false,
									mask: true,
									minDate: 0,
									defaultDate: new Date().setDate( new Date().getDate() + 6 ),
									scrollMonth: false,
									scrollTime: false,
									scrollInput: false,
								}
							);

							$( '#bp-zoom-single-webinar-wrapper' ).find( '#bp-zoom-webinar-start-time' ).datetimepicker(
								{
									format: 'h:i',
									formatTime:	'h:i',
									datepicker: false,
									hours12: true,
									step: 30,
								}
							);

							var options = {
								placeholder: 'hh:mm',
								translation: {
									'P': {
										pattern: /0|1/, optional: false
									},
									'Q': {
										pattern: /0|1|2/, optional: false
									},
									'X': {
										pattern: /0|[1-9]/, optional: false
									},
									'Y': {
										pattern: /[0-5]/, optional: false
									},
									'Z': {
										pattern: /[0-9]/, optional: false
									},
								},
								onKeyPress: function(cep, e, field, options) {
									var masks = [ 'PX:YZ', 'PQ:YZ' ];
									var mask  = ( cep.length > 1 && cep.substr( 0,1 ) > 0 ) ? masks[1] : masks[0];
									$( '#bp-zoom-single-webinar-wrapper' ).find( '#bp-zoom-webinar-start-time' ).mask( mask, options );
								}
							};

							$( '#bp-zoom-single-webinar-wrapper' ).find( '#bp-zoom-webinar-start-time' ).mask( 'PX:YZ', options );

							$( '#bp-zoom-single-webinar-wrapper' ).find( '#bp-zoom-webinar-timezone' ).select2(
								{
									minimumInputLength: 0,
									closeOnSelect: true,
									language: this.select2_laguage_text,
									dropdownCssClass: 'bb-select-dropdown',
									containerCssClass: 'bb-select-container',
								}
							);

							$( '.bp-zoom-webinar-left-inner .bp-zoom-webinar-members-listing #webinars-list' ).css( 'max-height', Number( $( '#bp_zoom_webinar_form .bp-zoom-webinar-right-top' ).height() ) + 'px' );

							if ( bp_zoom_vars.group_webinars_url !== '') {
								var create_webinar_url = bp_zoom_vars.group_webinars_url + 'create-webinar';
								window.history.pushState( null, null, create_webinar_url );
							}
						}
					}
				}
			);
		},

		loadSingleWebinar: function(e){
			var target            = $( e.target ),
				webinar_item      = target.closest( '.webinar-item' ),
				webinar_action    = webinar_item.data( 'action' ),
				webinar_zoom_type = webinar_item.data( 'zoom-type' ),
				id                = webinar_item.data( 'id' );
			e.preventDefault();
			var self = this;

			// when cancelling paren webinar editing for recurring webinar, reload the page.
			if ( 'edit-cancel' === webinar_action && 'webinar' === webinar_zoom_type ) {
				window.location.reload();
				return false;
			}

			if ( $( this.bp_zoom_webinar_container_elem ).length ) {
				$( this.bp_zoom_webinar_container_elem ).removeClass( 'bp-create-webinar' );
				$( this.bp_zoom_webinar_container_elem ).removeClass( 'bp-past-webinar' );
				$( this.bp_zoom_webinar_container_elem ).removeClass( 'bp-future-webinar' );
				$( this.bp_zoom_webinar_container_elem ).removeClass( 'bp-edit-webinar' );
				$( this.bp_zoom_webinar_container_elem ).addClass( 'bp-single-webinar' );

				if ( $( '.bp-navs.group-subnav' ).find( 'li.bp-groups-tab.current.selected' ).hasClass( 'webinars' ) ) {
					$( this.bp_zoom_webinar_container_elem ).addClass( 'bp-future-webinar' );
				} else if ( $( '.bp-navs.group-subnav' ).find( 'li.bp-groups-tab.current.selected' ).hasClass( 'past-webinar' ) ) {
					$( this.bp_zoom_webinar_container_elem ).addClass( 'bp-past-webinar' );
				}
			}

			if ( target.hasClass( 'view-recordings' ) || target.hasClass( 'dashicons' ) || webinar_item.hasClass( 'current' ) ) {
				return false;
			}

			$( '#webinars-list .webinar-item' ).removeClass( 'current' );
			$( '#webinars-list .webinar-item[data-id=' + id + ']' ).addClass( 'current' );

			$( '#bp-zoom-single-webinar-wrapper' ).empty();

			this.abort_zoom_ajax.bind( this );

			this.bp_zoom_ajax = $.ajax(
				{
					type: 'GET',
					url: bp_zoom_vars.ajax_url,
					data: {action: 'zoom_webinar_get_single_webinar', 'id': id},
					success: function (response) {
						if (typeof response.data !== 'undefined' && response.data.contents) {
							$( '#bp-zoom-single-webinar-wrapper' ).html( response.data.contents );

							if ( bp_zoom_vars.group_webinars_url !== '') {
								var webinar_url = bp_zoom_vars.group_webinars_url + 'webinars/' + id;
								window.history.pushState( null, null, webinar_url );
							}
						}

						// $('#bp-zoom-single-webinar-wrapper').find('.show-recordings').magnificPopup({
						// type:'inline',
						// midClick: true,
						// callbacks: {
						// open: function () {
						// bp.Zoom.autoScrollToDateInRecordingPopup();
						// },
						// }
						// });

						self.mask_webinar_id();
						self.triggerCountdowns();
					}
				}
			);
		},

		openWebinarOccurrenceDeletePopup: function( e ) {
			var target        = $( e.currentTarget ),
				webinar_item  = target.closest( '.webinar-item-container' ),
				occurrence_id = webinar_item.data( 'occurrence-id' );
			e.preventDefault();

			if ( typeof occurrence_id !== 'undefined' && occurrence_id != '' ) {
				$.magnificPopup.open(
					{
						items: {
							src: '#bp-zoom-delete-occurrence-popup-' + occurrence_id,
							type: 'inline'
						}
					}
				);
			}
		},

		openWebinarOccurrenceEditPopup: function( e ) {
			var target        = $( e.currentTarget ),
				webinar_item  = target.closest( '.webinar-item-container' ),
				occurrence_id = webinar_item.data( 'occurrence-id' );
			e.preventDefault();

			if ( typeof occurrence_id !== 'undefined' && occurrence_id != '' ) {
				$.magnificPopup.open(
					{
						items: {
							src: '#bp-zoom-edit-occurrence-popup-' + occurrence_id,
							type: 'inline'
						}
					}
				);
			}
		},

		loadEditWebinarOccurrence: function( e ) {
			var target        = $( e.currentTarget ),
				id            = target.data( 'id' ),
				webinar_id    = target.data( 'webinar-id' ),
				occurrence_id = target.data( 'occurrence-id' );
			e.preventDefault();

			$.magnificPopup.close();

			this.ajaxEditWebinarLoader( id, webinar_id, occurrence_id );
		},

		loadEditWebinar: function( e ) {
			var target     = $( e.currentTarget ),
				id         = target.data( 'id' ),
				webinar_id = target.data( 'webinar-id' );
			e.preventDefault();

			$.magnificPopup.close();

			this.ajaxEditWebinarLoader( id, webinar_id, '' );
		},

		ajaxEditWebinarLoader: function( id, webinar_id, occurrence_id ) {
			var self = this;

			$( '#bp-zoom-single-webinar-wrapper' ).empty();

			var data = { action: 'zoom_webinar_edit_webinar', 'id': id };
			if ( typeof occurrence_id !== 'undefined' && occurrence_id !== '' ) {
				data.occurrence_id = occurrence_id;
			}
			if ( typeof webinar_id !== 'undefined' && webinar_id !== '' ) {
				data.webinar_id = webinar_id;
			}

			if ( $( self.bp_zoom_webinar_container_elem ).length ) {
				$( self.bp_zoom_webinar_container_elem )
					.addClass( 'bp-create-webinar' )
					.removeClass( 'bp-past-webinar' )
					.removeClass( 'bp-future-webinar' )
					.removeClass( 'bp-edit-webinar' )
					.removeClass( 'bp-single-webinar' );
			}

			self.abort_zoom_ajax();

			self.bp_zoom_ajax = $.ajax(
				{
					type: 'GET',
					url: bp_zoom_vars.ajax_url,
					data: data,
					success: function (response) {
						if (typeof response.data !== 'undefined' && response.data.contents) {
							$( '#bp-zoom-single-webinar-wrapper' ).html( response.data.contents );

							$( '#bp-zoom-single-webinar-wrapper' ).find( '#bp-zoom-webinar-start-date' ).datetimepicker(
								{
									format: 'Y-m-d',
									timepicker: false,
									mask: true,
									minDate: 0,
									yearStart: new Date().getFullYear(),
									defaultDate: new Date(),
									scrollMonth: false,
									scrollTime: false,
									scrollInput: false,
									onSelectDate: function (date,element) {
										$( '#bp-zoom-single-webinar-wrapper' ).find( '#bp-zoom-webinar-end-date-time' ).datetimepicker(
											{
												minDate: element.val(),
											}
										);
									}
								}
							);

							$( '#bp-zoom-single-webinar-wrapper' ).find( '#bp-zoom-webinar-end-date-time' ).datetimepicker(
								{
									format: 'Y-m-d',
									timepicker: false,
									mask: true,
									minDate: 0,
									defaultDate: new Date().setDate( new Date().getDate() + 6 ),
									scrollMonth: false,
									scrollTime: false,
									scrollInput: false,
								}
							);

							$( '#bp-zoom-single-webinar-wrapper' ).find( '#bp-zoom-webinar-start-time' ).datetimepicker(
								{
									format: 'h:i',
									formatTime:	'h:i',
									datepicker: false,
									hours12: true,
									step: 30,
								}
							);

							var options = {
								placeholder: 'hh:mm',
								translation: {
									'P': {
										pattern: /0|1/, optional: false
									},
									'Q': {
										pattern: /0|1|2/, optional: false
									},
									'X': {
										pattern: /0|[1-9]/, optional: false
									},
									'Y': {
										pattern: /[0-5]/, optional: false
									},
									'Z': {
										pattern: /[0-9]/, optional: false
									},
								},
								onKeyPress: function(cep, e, field, options) {
									var masks = [ 'PX:YZ', 'PQ:YZ' ];
									var mask  = ( cep.length > 1 && cep.substr( 0,1 ) > 0 ) ? masks[1] : masks[0];
									$( '#bp-zoom-single-webinar-wrapper' ).find( '#bp-zoom-webinar-start-time' ).mask( mask, options );
								}
							};

							$( '#bp-zoom-single-webinar-wrapper' ).find( '#bp-zoom-webinar-start-time' ).mask( 'PX:YZ', options );

							$( '#bp-zoom-single-webinar-wrapper' ).find( '#bp-zoom-webinar-timezone' ).select2(
								{
									minimumInputLength: 0,
									closeOnSelect: true,
									language: this.select2_laguage_text,
									dropdownCssClass: 'bb-select-dropdown',
									containerCssClass: 'bb-select-container',
								}
							);

							$( '.bp-zoom-webinar-left-inner .bp-zoom-webinar-members-listing #webinars-list' ).css( 'max-height', Number( $( '#bp_zoom_webinar_form .bp-zoom-webinar-right-top' ).height() ) + 'px' );
						}
					}
				}
			);
		},

		updateWebinar: function (e) {
			var _this = $( e.currentTarget );
			e.preventDefault();

			if (_this.hasClass( 'loading' ) || _this.hasClass( 'disabled' )) {
				return false;
			}

			var self = this;

			_this.addClass( 'loading' );

			_this.parents( '.bp-webinar-fields-wrap' ).find( '.bp-feedback.error' ).remove();

			var form_data = _this.closest( 'form' ).serialize();

			this.abort_zoom_ajax.bind( this );

			this.bp_zoom_ajax = $.ajax(
				{
					type: 'POST',
					url: bp_zoom_vars.ajax_url,
					data: form_data,
					success: function (response) {
						var error_html = '';
						if (response.success) {
							if (typeof response.data !== 'undefined') {
								if ( response.data.redirect_url !== '' ) {
									window.location.href = response.data.redirect_url;
									return false;
								} else {
									window.location.reload();
								}
							}
						} else {
							if ( response.data.errors ) {
								for ( var er in response.data.errors ) {
									error_html = '<aside class="bp-feedback error">' +
									'<span class="bp-icon" aria-hidden="true"></span>' +
									'<p>' + response.data.errors[er].message + '</p>' +
									'</aside>';
									_this.parents( '.bp-webinar-fields-wrap' ).prepend( error_html );
								}
							} else if ( response.data.error ) {
								error_html = '<aside class="bp-feedback error">' +
								'<span class="bp-icon" aria-hidden="true"></span>' +
								'<p>' + response.data.error + '</p>' +
								'</aside>';
								_this.parents( '.bp-webinar-fields-wrap' ).prepend( error_html );
							}
							_this.removeClass( 'loading' );

							$( 'html, body' ).animate( { scrollTop: $( '#bp-zoom-single-webinar-wrapper' ).offset().top - 100 }, 500 );
						}
						self.mask_webinar_id();
					}
				}
			);
		},

		deleteOnlyThisWebinar: function( e ) {
			var target        = $( e.target ),
				id            = target.data( 'id' ),
				webinar_id    = target.data( 'webinar-id' ),
				occurrence_id = target.data( 'occurrence-id' );
				target.addClass( 'loading' );
			e.preventDefault();

			this.ajaxDeleteWebinar( id, webinar_id, occurrence_id );
		},

		deleteAllWebinarOccurrences: function( e ) {
			var target     = $( e.target ),
				id         = target.data( 'id' ),
				webinar_id = target.data( 'webinar-id' );
				target.addClass( 'loading' );
			e.preventDefault();

			this.ajaxDeleteWebinar( id, webinar_id );
		},

		ajaxDeleteWebinar: function( id, webinar_id, occurrence_id ) {
			var self = this;

			var data = {
				'action': 'zoom_webinar_delete',
				'webinar_id': webinar_id,
				'id': id,
				'_wpnonce': bp_zoom_vars.webinar_delete_nonce,
			};

			if ( typeof occurrence_id !== 'undefined' && occurrence_id !== '' ) {
				data.occurrence_id = occurrence_id;
			}

			self.abort_zoom_ajax.bind( self );

			self.bp_zoom_ajax = $.ajax(
				{
					type: 'POST',
					url: bp_zoom_vars.ajax_url,
					data: data,
					success: function (response) {
						if (true === response.data.deleted && '1' === bp_zoom_vars.is_single_webinar) {
							if ( true === response.data.is_past && bp_zoom_vars.group_webinars_past_url !== '') {
								window.location.href = bp_zoom_vars.group_webinars_past_url;
							} else if (bp_zoom_vars.group_webinars_url !== '') {
								window.location.href = bp_zoom_vars.group_webinars_url;
							}
							return false;
						}
					}
				}
			);
		},

		deleteWebinar: function (e) {
			var target       = $( e.target ),
				webinar_item = target.parents( '.webinar-item-container' ),
				webinar_id   = webinar_item.data( 'webinar-id' ),
				id           = webinar_item.data( 'id' );
			e.preventDefault();

			if ( ! confirm( bp_zoom_vars.webinar_confirm_msg ) ) {
				return false;
			}

			this.ajaxDeleteWebinar( id, webinar_id );
		},

		openWebinarActions: function(e) {
			var _this = $( e.currentTarget );
			e.preventDefault();

			_this.next( '.webinar-actions-list' ).toggleClass( 'open' );
		},

		searchWebinarActions: function(e) {
			var _this   = $( e.currentTarget ),
				self_id = _this.attr( 'id' ),
				self    = this;

			if ('bp-zoom-webinar-recorded-switch-checkbox' !== self_id) {
				e.preventDefault();
			}

			var recorded = false;
			if ( $( '#bp-zoom-webinar-recorded-switch-checkbox' ).is( ':checked' ) ) {
				recorded = true;
			}

			$( '#bp-zoom-webinar-container #bp-zoom-dropdown-options-loader' ).show();

			var page  = 1;
			var param = {
				'action'      : 'zoom_webinar_search',
				'recorded'    : recorded,
				'page'        : page,
				'search_terms': $( '#bp-zoom-webinar-container #bp_zoom_webinar_search' ).val(),
				'past'        : $( '#bp-zoom-webinar-container #bp-zoom-webinar-recorded-switch-checkbox' ).length,
			};

			$.ajax(
				{
					type: 'GET',
					url: bp_zoom_vars.ajax_url,
					async: true,
					data: param,
					success: function (response) {
						if ( typeof response.data !== 'undefined' && response.data.contents) {
							$( '#bp-zoom-webinar-container .bp-zoom-webinar-left-inner #webinars-list' ).html( response.data.contents );

							var id = $( '#bp-zoom-single-webinar-wrapper' ).find( '.webinar-item-container' ).data( 'id' );
							if ( id == 0 || id == null || typeof id === 'undefined' ) {
								if ( $( '#bp_zoom_webinar_form' ).length && $( '#bp_zoom_webinar_form' ).find( '#bp-zoom-webinar-id' ).length ) {
									id = $( '#bp_zoom_webinar_form' ).find( '#bp-zoom-webinar-id' ).val();
								}
							}
							if ( id ) {
								$( '#webinars-list' ).find( '.webinar-item[data-id="' + id + '"]' ).addClass( 'current' );
							}
						}

						$( '#bp-zoom-webinar-container #bp-zoom-dropdown-options-loader' ).hide();
						self.mask_webinar_id();
					}
				}
			);

		},

		joinWebinarInBrowser: function (e) {
			var _this       = $( e.currentTarget ), scripts_loaded = 0;
			var stylesArray = $( 'style, link[rel="stylesheet"]' ),i = 0;

			e.preventDefault();

			if (
				(
					typeof bp_zoom_vars.scripts === 'undefined' &&
					0 === bp_zoom_vars.scripts.length
				) ||
				! bp_zoom_vars.is_zoom_sdk
			) {
				return false;
			}

			var dummydiv   = document.createElement( 'div' );
			dummydiv.style = 'position:absolute;z-index:9999;top: 0;background-color: black;width: 99999999px;height: 999999999999px;';
			document.body.appendChild( dummydiv );

			// Add Zoom Style dependencies.
			for (i in bp_zoom_vars.styles ) {
				$(
					'<link/>',
					{
						rel: 'stylesheet',
						type: 'text/css',
						href: bp_zoom_vars.styles[i]
					}
				).appendTo( 'head' );
			}

			for (i in bp_zoom_vars.scripts) {
				var script  = document.createElement( 'script' );
				script.type = 'text/javascript';
				script.src  = bp_zoom_vars.scripts[i];

				// ie.
				if ( script.readyState ) {
					/* jshint ignore:start */
					script.onreadystatechange = function(){
						if ( script.readyState == 'loaded' || script.readyState == 'complete' ) {
							script.onreadystatechange = null;
							scripts_loaded++;
						}
					};
					/* jshint ignore:end */
					// normal browsers.
				} else {
					/* jshint ignore:start */
					script.onload = function(){
						scripts_loaded++;
					};
					/* jshint ignore:end */
				}
				document.head.appendChild( script );
			}

			var loadscripts = setInterval(
				function(){
					if ( scripts_loaded >= bp_zoom_vars.scripts.length && typeof ZoomMtg !== 'undefined' ) {
						clearInterval( loadscripts );

						// Add needed fixes
						// var bp_zoom_in_browser_style = $('<style>#wc-footer .btn-default { color: #333; background-color: transparent; border-color: transparent;}.security-option-menu__pop-menu > li.selected > a:before, .popmenu > li.selected > a:before{left: 5px;top: 6px;}</style>');
						// bp_zoom_in_browser_style.appendTo("head");

						document.body.removeChild( dummydiv );
						// Remove Theme styles.
						stylesArray.remove();

						// Added special support for Japanese language.
					if ( bp_zoom_vars.lang === 'ja' ) {
						bp_zoom_vars.lang = 'jp-JP';

						// Added special support for Korean language.
					} else if ( bp_zoom_vars.lang === 'ko-KR' ) {
						bp_zoom_vars.lang = 'ko-KO';

						// Added special support for Vietnamese language.
					} else if ( bp_zoom_vars.lang === 'vi' ) {
						bp_zoom_vars.lang = 'vi-VN';
					}

					if (
						typeof bp_zoom_vars.lang !== 'undefined' &&
						-1 !== $.inArray( bp_zoom_vars.lang, bp.Zoom.zoom_languages )
					) {
						ZoomMtg.i18n.load( bp_zoom_vars.lang );
					}ZoomMtg.preLoadWasm();
						ZoomMtg.prepareJssdk();

						// var testTool = window.BpZoomTestTool;
						// var webinarId = $(this).data('webinar-id');
						// var webinarPwd = $(this).data('webinar-pwd');
						// var stmUserName = 'Local' + ZoomMtg.getJSSDKVersion()[0] + testTool.detectOS() + '#' + testTool.getBrowserInfo();
						$( '.zoom-theatre' ).show();

						$( '#zmmtg-root' ).addClass( 'active' );
						var meetConfig = {
							meetingNumber: _this.data( 'webinar-id' ),
							userName: bp_zoom_vars.user.name,
							passWord: _this.data( 'webinar-pwd' ),
							userEmail: bp_zoom_vars.user.email, // required.
							leaveUrl: bp_zoom_vars.home_url,
							role: 0,
						};

						var signature = _this.data( 'meeting-sign' ),
							sdkKey    = _this.data( 'meeting-sdk' );

						ZoomMtg.init(
							{
								leaveUrl: meetConfig.leaveUrl,
								isSupportAV: true,
								success: function () {
									ZoomMtg.join(
										{
											meetingNumber: meetConfig.meetingNumber,
											userName: meetConfig.userName,
											signature: signature,
											sdkKey: sdkKey,
											passWord: meetConfig.passWord,
											userEmail: bp_zoom_vars.user.email, // required.
											success: function (res) {
												console.log( res );
												console.log( 'join webinar success' );
											},
											error: function (res) {
												console.log( res );
											}
										}
									);
								},
								error: function (res) {
									console.log( res );
								}
							}
						);
					} else {
						console.log( 'ZoomMtg is not defined yet and scripts not loaded!' );
					}
				},
				500
			);

			setTimeout(
				function(){
					clearInterval( loadscripts );
				},
				20000
			);
		},

		toggleWebinarPassword: function (e) {
			var _this = $( e.currentTarget ), webinar_row = _this.closest( '.single-webinar-item' );
			e.preventDefault();

			if (_this.hasClass( 'show-pass' )) {
				_this.removeClass( 'on' );
				webinar_row.find( '.toggle-password.hide-pass' ).addClass( 'on' );
				webinar_row.find( '.hide-password' ).removeClass( 'on' );
				webinar_row.find( '.show-password' ).addClass( 'on' );
			} else {
				_this.removeClass( 'on' );
				webinar_row.find( '.toggle-password.show-pass' ).addClass( 'on' );
				webinar_row.find( '.show-password' ).removeClass( 'on' );
				webinar_row.find( '.hide-password' ).addClass( 'on' );
			}
		},

		toggleWebinarAutoRecording: function(e) {
			var target = $( e.target ), form_recording_options = target.closest( 'form' ).find( '.bp-zoom-webinar-auto-recording-options' );
			if (target.is( ':checked' )) {
				form_recording_options.removeClass( 'bp-hide' );
			} else {
				form_recording_options.addClass( 'bp-hide' );
			}
		},

		toggleWebinarRecurring: function ( e ) {
			var target                 = $( e.target ),
				form_recurring_options = target.closest( 'form' ).find( '.bp-zoom-webinar-recurring-options' ),
				registration_options   = target.closest( 'form' ).find( '.bp-zoom-webinar-registration-options' );
			if ( target.is( ':checked' ) ) {
				form_recurring_options.removeClass( 'bp-hide' );
				if ( target.closest( 'form' ).find( '#bp-zoom-webinar-registration' ).is( ':checked' ) && target.closest( 'form' ).find( '#bp-zoom-webinar-recurring' ).is( ':checked' ) && [ '1', '2', '3' ].includes( target.closest( 'form' ).find( '#bp-zoom-webinar-recurrence' ).val() ) ) {
					registration_options.removeClass( 'bp-hide' );
				}
			} else {
				form_recurring_options.addClass( 'bp-hide' );
				registration_options.addClass( 'bp-hide' );
			}
		},

		toggleWebinarDates: function(e) {
			var target             = $( e.target ),
				form               = target.closest( 'form' ),
				recurrence         = form.find( '#bp-zoom-webinar-recurrence' ),
				repeat_interval    = form.find( '#bp-zoom-webinar-repeat-interval' ),
				start_date_time    = form.find( '#bp-zoom-webinar-start-date' ),
				end_date_time      = form.find( '#bp-zoom-webinar-end-date-time' ),
				start_date         = new Date( start_date_time.val() ),
				end_date_time_date = new Date( end_date_time.val() );
			e.preventDefault();

			if ( start_date.getTime() >= end_date_time_date.getTime() ) {
				if ( recurrence.val() == '1' ) {
					start_date.setDate( start_date.getDate() + ( 6 * repeat_interval.val() ) );
					end_date_time.val( this.formatDate( start_date ) );
				}
				if ( recurrence.val() == '2' ) {
					start_date.setDate( start_date.getDate() + ( 6 * ( 7 * repeat_interval.val() ) ) );
					end_date_time.val( this.formatDate( start_date ) );
				}
				if ( recurrence.val() == '3' ) {
					start_date.setMonth( start_date.getMonth() + ( 6 * repeat_interval.val() ) );
					end_date_time.val( this.formatDate( start_date ) );
				}
			}
		},

		toggleWebinarNotification: function ( e ) {
			var target              = $( e.target ),
				field_wrap          = target.closest( '.bb-field-wrap' ),
				notification_select = field_wrap.find( '#bp-zoom-webinar-alert' );

			e.preventDefault();

			if ( notification_select.attr( 'disabled' ) && target.prop( 'checked' ) ) {
				notification_select.removeAttr( 'disabled' );
			} else {
				notification_select.attr( 'disabled', 'disabled' );
			}
		},

		toggleWebinarRegistration: function ( e ) {
			var target               = $( e.target ),
				form                 = target.closest( 'form#bp_zoom_webinar_form' ),
				registration_options = form.find( '.bp-zoom-webinar-registration-options' );

			if ( target.is( ':checked' ) && form.find( '#bp-zoom-webinar-recurring' ).is( ':checked' ) && [ '1', '2', '3' ].includes( form.find( '#bp-zoom-webinar-recurrence' ).val() ) ) {
				registration_options.removeClass( 'bp-hide' );
			} else {
				registration_options.addClass( 'bp-hide' );
			}
		},

		toggleWebinarRepeatInterval: function (e) {
			var target          = $( e.target ),
				recurrence      = target.closest( 'form' ).find( '#bp-zoom-webinar-recurrence' ),
				start_date_time = target.closest( 'form' ).find( '#bp-zoom-webinar-start-date' ),
				end_date_time   = target.closest( 'form' ).find( '#bp-zoom-webinar-end-date-time' ),
				start_date      = new Date( start_date_time.val() ),
				end_date        = new Date();
			e.preventDefault();

			if (recurrence.val() == '1') {
				end_date.setDate( start_date.getDate() + (6 * target.val()) );
				end_date_time.val( this.formatDate( end_date ) );
			}
			if (recurrence.val() == '2') {
				end_date.setDate( start_date.getDate() + (6 * (7 * target.val())) );
				end_date_time.val( this.formatDate( end_date ) );
			}
			if (recurrence.val() == '3') {
				end_date.setMonth( start_date.getMonth() + (6 * target.val()) );
				end_date_time.val( this.formatDate( end_date ) );
			}
		},

		toggleWebinarRecurrence: function (e) {
			var target                  = $( e.target ),
				form                    = target.closest( 'form' ),
				form_recurrence_options = form.find( '.bp-zoom-webinar-recurring-sub-options' ),
				registration_options    = form.find( '.bp-zoom-webinar-registration-options' ),
				registration_wrapper    = form.find( '.bp-zoom-webinar-registration-wrapper' ),
				form_occurs_on_options  = form.find( '.bp-zoom-webinar-occurs-on' ),
				form_occurs_on_monthly  = form.find( '#bp-zoom-webinar-occurs-on-month' ),
				form_occurs_on_weekly   = form.find( '#bp-zoom-webinar-occurs-on-week' ),
				interval_type_label     = form.find( '#bp-zoom-webinar-repeat-interval-type' ),
				repeat_interval         = form.find( '#bp-zoom-webinar-repeat-interval' ),
				start_date_time         = form.find( '#bp-zoom-webinar-start-date' ),
				end_date_time           = form.find( '#bp-zoom-webinar-end-date-time' ),
				i                       = 1, repeat_interval_html = '',
				start_date              = new Date( start_date_time.val() ),
				occurs_on_label = form.find('.bp-zoom-webinar-occurs-on > label');
			e.preventDefault();

			if (target.val() == '-1') {
				form_recurrence_options.addClass( 'bp-hide' );
				registration_options.addClass( 'bp-hide' );
				registration_wrapper.addClass( 'bp-hide' );
			} else {
				if ( target.closest( 'form' ).find( '#bp-zoom-webinar-registration' ).is( ':checked' ) && target.closest( 'form' ).find( '#bp-zoom-webinar-recurring' ).is( ':checked' ) ) {
					registration_options.removeClass( 'bp-hide' );
				}
				registration_wrapper.removeClass( 'bp-hide' );

				if (target.val() == '1') {
					form_occurs_on_options.addClass( 'bp-hide' );
					interval_type_label.text( bp_zoom_vars.strings.day );
					repeat_interval_html = '';
					occurs_on_label.text( function(index, text) {
						return text.replace( ' *', '' );
					});
					for (i = 1; i <= 15; i++) {
						repeat_interval_html += '<option value="' + i + '">' + i + '</option>';
					}
					repeat_interval.html( repeat_interval_html );

					start_date.setDate( start_date.getDate() + ( 6 * repeat_interval.val() ) );
					end_date_time.val( this.formatDate( start_date ) );
				}
				if (target.val() == '2') {
					form_occurs_on_options.removeClass( 'bp-hide' );
					form_occurs_on_weekly.removeClass( 'bp-hide' );
					form_occurs_on_monthly.addClass( 'bp-hide' );
					interval_type_label.text( bp_zoom_vars.strings.week );
					repeat_interval_html = '';
					occurs_on_label.text( function(index, text) {
						return text + ' *';
					});
					for (i = 1; i <= 12; i++) {
						repeat_interval_html += '<option value="' + i + '">' + i + '</option>';
					}
					repeat_interval.html( repeat_interval_html );

					start_date.setDate( start_date.getDate() + ( 6 * ( 7 * repeat_interval.val() ) ) );
					end_date_time.val( this.formatDate( start_date ) );
				}
				if (target.val() == '3') {
					form_occurs_on_options.removeClass( 'bp-hide' );
					form_occurs_on_weekly.addClass( 'bp-hide' );
					form_occurs_on_monthly.removeClass( 'bp-hide' );
					interval_type_label.text( bp_zoom_vars.strings.month );
					repeat_interval_html = '';
					occurs_on_label.text( function(index, text) {
						return text.replace( ' *', '' );
					});
					for (i = 1; i <= 3; i++) {
						repeat_interval_html += '<option value="' + i + '">' + i + '</option>';
					}
					repeat_interval.html( repeat_interval_html );

					start_date.setMonth( start_date.getMonth() + ( 6 * repeat_interval.val() ) );
					end_date_time.val( this.formatDate( start_date ) );
				}

				form_recurrence_options.removeClass( 'bp-hide' );
			}
		},

		changeWebinarTimezone: function ( e ) {
			var _this        = $( e.target );
			var currentDate  = new Date( new Date().toLocaleDateString( 'en-US', { timeZone: _this.val() } ) );
			var args         = {
				minDate: this.formatDate( currentDate )
			};
			var selectedDate = new Date( jQuery( '#bp-zoom-webinar-start-date' ).val() );
			if ( selectedDate < currentDate ) {
				args.value = this.formatDate( currentDate );
			}
			jQuery( '#bp-zoom-webinar-start-date' ).datetimepicker( args );
		},

		mask_webinar_id: function() {
			if ( typeof jQuery.fn.mask !== 'undefined' ) {
				$( '#webinars-list' ).find( '.webinar-id' ).mask( 'AA: 000 0000 0000' );
				$( '#bp-zoom-single-webinar' ).find( '.webinar-id' ).mask( '000 0000 0000' );
				$( '.zoom-webinar-id' ).mask( '000 0000 0000' );
				$( document ).find( '.bb-webinar-id' ).mask( 'AA: 000 0000 0000' );
			}
		},

		syncGroupWebinars: function (e) {
			var _this    = $( e.currentTarget ),
				group_id = _this.data( 'group-id' ),
				offset   = 0;
			e.preventDefault();

			_this.addClass( 'loading' );

			this.bp_zoom_webinars_sync_function( offset, group_id );
		},

		bp_zoom_webinars_sync_function: function (offset, group_id) {
			var self = this;
			$.ajax(
				{
					type: 'POST',
					url: bp_zoom_vars.ajax_url,
					data: {
						'action': 'zoom_webinars_sync',
						'group_id': group_id,
						'offset': offset,
					},
					success: function (response) {
						if (typeof response.success !== 'undefined') {
							if (response.success && typeof response.data !== 'undefined') {
								if ('running' === response.data.status) {
									self.bp_zoom_webinars_sync_function( response.data.offset, group_id );
								} else {
									$( '#webinars-sync' ).removeClass( 'loading' );
									if ( response.data.redirect_url ) {
										window.location.href = response.data.redirect_url;
									} else {
										window.location.reload();
									}
									return false;
								}
							} else {
								$( '#webinars-sync' ).removeClass( 'loading' );
								if ( response.data.redirect_url ) {
									window.location.href = response.data.redirect_url;
								} else {
									window.location.reload();
								}
								return false;
							}
						}
					},
					error: function () {
						$( '#webinars-sync' ).removeClass( 'loading' );
						return false;
					}
				}
			);
		},

		togglePasswordTooltip: function(e) {
			var $this = $( e.currentTarget );
			var $tooltip = $this.attr( 'data-balloon' );

			if( $tooltip !== undefined ) {
				if ( $this.hasClass( 'bb-show-pass' ) ) {
					$this.attr( 'data-balloon', $this.attr( 'data-balloon-toggle' ) );
					$this.attr( 'data-balloon-toggle', $tooltip );
				} else {
					$this.attr( 'data-balloon', $this.attr( 'data-balloon-toggle' ) );
					$this.attr( 'data-balloon-toggle', $tooltip );
				}
			}
		}

	};

	// Launch BP Zoom.
	bp.Zoom.start();

} )( bp, jQuery );
