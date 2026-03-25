/* global wp, bp, BP_Nouveau, _, Backbone, BBTopicsManager */
/* jshint devel: true */
/* @version 3.1.0 */
window.wp = window.wp || {};
window.bp = window.bp || {};

( function ( exports, $ ) {

	// Bail if not set.
	if ( 'undefined' === typeof BP_Nouveau ) {
		return;
	}

	_.extend( bp, _.pick( wp, 'Backbone', 'ajax', 'template' ) );

	bp.Models      = bp.Models || {};
	bp.Collections = bp.Collections || {};
	bp.Views       = bp.Views || {};

	bp.Nouveau = bp.Nouveau || {};

	/**
	 * [Nouveau description]
	 *
	 * @type {Object}
	 */
	bp.Nouveau.SchedulePost = {
		/**
		 * [start description]
		 *
		 * @return {[type]} [description]
		 */
		start: function () {
			this.setupGlobals();

			// When click on view posts from activity dir after created scheduled posts for group.
			// then open schedule posts modal.
			this.viewGroupSchedulePostModal();

			// Listen to events ("Add hooks!").
			this.addListeners();
		},

		setupGlobals: function () {
			// Page number for scheduled activities.
			this.scheduled_current_page = 1;
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {
			$( document ).on( 'click', '.bb-view-scheduled-posts', this.openSchedulePostModal );

			$( '#buddypress' ).on( 'click', '.bb-view-schedule-posts, .bb-view-all-scheduled-posts', this, this.showSchedulePosts );

			// Loadmore for schedule posts.
			$( '#buddypress' ).on( 'click', '#bb-schedule-posts_modal li.load-more', this.loadMoreScheduledActivities.bind( this ) );

			$( document ).on( 'click', '.bb-schedule-posts_modal .activity-item', this.activityActions.bind( this ) );
		},

		activityActions: function ( event ) {
			var activity_item = $( event.currentTarget ), target = $( event.target ),
				activity_id   = activity_item.data( 'bp-activity-id' ),
				item_id;

			// Reading more.
			if ( target.closest( 'span' ).hasClass( 'activity-read-more' ) ) {
				var content = target.closest( 'div' ), readMore = target.closest( 'span' );

				item_id = null;

				if ( $( content ).hasClass( 'activity-inner' ) ) {
					item_id = activity_id;
				} else if ( $( content ).hasClass( 'acomment-content' ) ) {
					item_id = target.closest( 'li' ).data( 'bp-activity-comment-id' );
				}

				if ( ! item_id ) {
					return event;
				}

				// Stop event propagation.
				event.preventDefault();

				$( readMore ).addClass( 'loading' );

				bp.Nouveau.ajax(
					{
						action : 'get_single_activity_content',
						id     : item_id,
						status : 'scheduled',
					},
					'activity'
				).done(
					function ( response ) {

						// check for JSON output.
						if ( typeof response !== 'object' && target.closest( 'div' ).find( '.bb-activity-media-wrap' ).length > 0 ) {
							response = JSON.parse( response );
						}

						$( readMore ).removeClass( 'loading' );

						if ( content.parent().find( '.bp-feedback' ).length ) {
							content.parent().find( '.bp-feedback' ).remove();
						}

						if ( false === response.success ) {
							content.after( response.data.feedback );
							content.parent().find( '.bp-feedback' ).hide().fadeIn( 300 );
						} else {
							$( content ).html( response.data.contents ).slideDown( 300 );

							// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad.
							jQuery( window ).scroll();

							if ( activity_item.hasClass( 'wp-link-embed' ) ) {
								if ( typeof window.instgrm !== 'undefined' ) {
									window.instgrm.Embeds.process();
								}
								if ( typeof window.FB !== 'undefined' && typeof window.FB.XFBML !== 'undefined' ) {
									window.FB.XFBML.parse( document.getElementById( 'activity-' + item_id ) );
								}
							}
						}
					}
				);
			}
		},

		openSchedulePostModal: function () {
			// Let closing modal know that we are opening view scheduled posts modal from outside.
			bp.Nouveau.SchedulePostView = true;

			var activity_form = $( '.activity-update-form').length ? '.activity-update-form .activity-form' : '.bb-rl-activity-update-form .bb-rl-activity-form';
			var whats_new     = $( activity_form ).find( '#whats-new' ).length ? '#whats-new' : '#bb-rl-whats-new';

			// Show post form modal.
			jQuery( activity_form + ':not(.focus-in) ' + whats_new ).trigger( 'focus' );

			// Open view scheduled posts modal.
			setTimeout(
				function () {
					// Check if schedule Post button is not visible.
					if ( $( '.bb-schedule-post_dropdown_section' ).hasClass( 'bp-hide' ) ) {
						bp.Nouveau.SchedulePostViewHidden = true;
						$( '.bb-schedule-post_dropdown_section' ).removeClass( 'bp-hide' );
					}

					$( activity_form + ' .bb-schedule-post_dropdown_button' ).trigger( 'click' );

					setTimeout(
						function () {
							$( activity_form + ' .bb-view-schedule-posts' ).trigger( 'click' );
						},
						0
					);
				},
				100
			);
		},

		/**
		 * Show scheduled activities.
		 */
		showSchedulePosts: function () {
			var object = 'activity';
			var scope  = null;

			if ( $( bp.Nouveau.objectNavParent + ' #bb-subnav-filter-show [data-bp-scope].selected' ).length ) {
				scope = $( bp.Nouveau.objectNavParent + ' #bb-subnav-filter-show [data-bp-scope].selected' ).data( 'bp-scope' );
			}

			bp.Nouveau.SchedulePost.scheduled_current_page = 1;

			if ( $( '#buddypress .bb-action-popup-content .schedule-posts-content' ).length ) {
				var queryData = {
					object: object,
					status: 'scheduled',
					target: '#buddypress .bb-action-popup-content .schedule-posts-content',
					template: 'activity_schedule',
					scope: scope,
				};

				if ( $( '#buddypress [data-bp-member-type-filter="' + object + '"]' ).length ) {
					queryData.member_type_id = $( '#buddypress [data-bp-member-type-filter="' + object + '"]' ).val();
				} else if ( $( '#buddypress [data-bp-group-type-filter="' + object + '"]' ).length ) {
					queryData.group_type = $( '#buddypress [data-bp-group-type-filter="' + object + '"]' ).val();
				}

				// Populate the object list.
				bp.Nouveau.objectRequest( queryData );
			}
		},

		/**
		 * Load more scheduled activities.
		 */
		loadMoreScheduledActivities: function ( event ) {
			event.preventDefault();
			var object   = 'activity',
				targetEl = $( event.currentTarget ),
				self     = this,
				page     = ( Number( self.scheduled_current_page ) * 1 ) + 1;

			if ( page > 0 && $( '#buddypress .bb-action-popup-content .schedule-posts-content' ).length ) {
				if ( targetEl.hasClass( 'bb-page-item-deleted' ) ) {
					targetEl.removeClass( 'bb-page-item-deleted' );
					page = page - 1;
				}
				targetEl.find( 'a' ).first().addClass( 'loading' );
				$( '#buddypress #bb-schedule-posts_modal ul.bp-list li.activity-item' ).addClass( 'bb-pre-listed-page-item' );
				var queryData = {
					object: object,
					status: 'scheduled',
					target: '#buddypress #bb-schedule-posts_modal .schedule-posts-content ul.bp-list',
					method: 'append',
					template: 'activity_schedule',
					page: page,
				};

				if ( page === 1 ) {
					queryData.method = 'reset';
					queryData.target = '#buddypress #bb-schedule-posts_modal .schedule-posts-content';
				}

				if ( $( '#buddypress [data-bp-member-type-filter="' + object + '"]' ).length ) {
					queryData.member_type_id = $( '#buddypress [data-bp-member-type-filter="' + object + '"]' ).val();
				} else if ( $( '#buddypress [data-bp-group-type-filter="' + object + '"]' ).length ) {
					queryData.group_type = $( '#buddypress [data-bp-group-type-filter="' + object + '"]' ).val();
				}

				// Populate the object list.
				bp.Nouveau.objectRequest( queryData ).done(
					function ( response ) {
						if ( true === response.success ) {
							if ( 'undefined' !== typeof response.data.contents && '' !== response.data.contents ) {
								var activities = $.parseHTML( response.data.contents );
								$.each(
									activities,
									function ( a, activity ) {
										if ( 'LI' === activity.nodeName && $( activity ).hasClass( 'activity-item' ) ) {
											if ( $( '#' + $( activity ).prop( 'id' ) + '.bb-pre-listed-page-item' ).length ) {
												$( '#' + $( activity ).prop( 'id' ) + '.bb-pre-listed-page-item' ).remove();
											}
										}

									}
								);
							}
							targetEl.remove();

							// Update the current page.
							self.scheduled_current_page = page;

							// Replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad.
							jQuery( window ).scroll();
						}
					}
				);
			}
		},

		viewGroupSchedulePostModal: function () {
			var paramValue = this.bbGetUrlParameter( 'action' );
			if ( 'scheduled_posts' === paramValue ) {
				var self     = this;
				var attempts = 0;
				this.bbRemoveUrlParameter( 'action' );
				var checkPostForm = setInterval(
					function () {
						if ( $( '#whats-new-form' ).hasClass( 'focus-in--empty' ) ) {
							self.openSchedulePostModal();
							clearInterval( checkPostForm );
							return;
						}
						// Make sure not to run this check more than 10 times.
						attempts++;
						if ( attempts > 10 ) {
							clearInterval( checkPostForm );
						}
					},
					200
				);
			}
		},

		bbGetUrlParameter: function ( name ) {
			name        = name.replace( /[\[]/, '\\[' ).replace( /[\]]/, '\\]' );
			var regex   = new RegExp( '[\\?&]' + name + '=([^&#]*)' );
			var results = regex.exec( location.search );
			return results === null ? '' : decodeURIComponent( results[1].replace( /\+/g, ' ' ) );
		},

		bbRemoveUrlParameter: function ( param ) {
			var url      = window.location.href;
			var urlparts = url.split( '?' );

			if ( urlparts.length >= 2 ) {
				var prefix = encodeURIComponent( param ) + '=';
				var pars   = urlparts[1].split( /[&;]/g );

				// Filter out the parameter.
				for ( var i = pars.length; i-- > 0; ) {
					if ( pars[i].lastIndexOf( prefix, 0 ) !== -1 ) {
						pars.splice( i, 1 );
					}
				}

				url = urlparts[0] + ( pars.length > 0 ? '?' + pars.join( '&' ) : '' );
				window.history.replaceState( null, null, url );
			}
		},

	};

	bp.Views.activitySchedulePost = Backbone.View.extend(
		{
			tagName: 'div',
			id: 'bb-schedule-posts',
			className: 'bb-schedule-posts',
			template: bp.template( 'activity-schedule-post' ),

			events: {
				'click .bb-schedule-post_dropdown_button': 'displayOptions',
				'click .bb-schedule-post_action': 'displayScheduleForm',
				'click .bb-view-schedule-posts': 'displaySchedulePosts',
				'click .bb-view-all-scheduled-posts': 'displaySchedulePosts',
				'click #bb-schedule-posts_modal .bb-close-action-popup': 'closeSchedulePosts',
				'click .bb-schedule-activity-cancel': 'cancelSchedulePost',
				'click .bb-model-close-button': 'cancelSchedulePost',
				'click .bb-schedule-activity': 'displayScheduleButton',
				'change .bb-schedule-activity-meridian-wrap input': 'validateScheduleTime',
				'change .bb-schedule-activity-date-field': 'validateScheduleTime',
				'change .bb-schedule-activity-time-field': 'validateScheduleTime',
				'click .bb-activity-schedule_edit': 'editScheduledPost',
				'click .bb-activity-schedule_delete': 'deleteScheduledPost',
				'click .bb-schedule-activity-clear': 'clearScheduledPost',
			},

			initialize: function () {
				this.model.on(
					'change:activity_action_type change:activity_schedule_date_raw change:activity_schedule_date change:activity_schedule_time change:activity_schedule_meridiem',
					this.render,
					this
				);
			},

			render: function () {
				this.$el.html( this.template( this.model.attributes ) );
				return this;
			},

			displayOptions: function ( event ) {
				event.preventDefault();
				var target = $( event.target );
				var schedulePostDropdown = target.closest( '.bb-schedule-post_dropdown_section' ).find( '.bb-schedule-post_dropdown_list' );
				if ( target.hasClass( 'is_scheduled' ) && target.closest( '.activity-form' ).hasClass( 'bp-activity-edit' ) ) {
					target.closest( '.bb-schedule-posts' ).find( '.bb-schedule-post_action' ).trigger( 'click' );
				} else {
					schedulePostDropdown.toggleClass( 'is_open' );
				}

				if( schedulePostDropdown.height() + schedulePostDropdown.offset().top + 20 > $( window ).height() ){
					schedulePostDropdown.addClass( 'is_bottom' );
				} else {
					schedulePostDropdown.removeClass( 'is_bottom' );
				}
			},

			cancelSchedulePost: function ( event ) {
				event.preventDefault();
				var schedulePost = $( event.target ).closest( '#bb-schedule-post_form_modal' );
				schedulePost.hide();
			},

			displayScheduleForm: function ( event ) {
				event.preventDefault();
				var schedulePost = $( event.target ).closest( '.bb-schedule-posts' );
				schedulePost.find( '.bb-schedule-post_dropdown_list' ).removeClass( 'is_open' );
				schedulePost.find( '.bb-schedule-post_modal #bb-schedule-post_form_modal' ).show();
				if ( 'undefined' !== typeof jQuery.fn.datetimepicker ) {
					var currentDate       = new Date();
					var datepickerOptions = {
						format      : 'Y-m-d',
						timepicker  : false,
						mask        : false,
						minDate     : 0,
						maxDate     : new Date( currentDate.getFullYear(), currentDate.getMonth(), currentDate.getDate() + 90 ),
						yearStart   : currentDate.getFullYear(),
						defaultDate : currentDate,
						scrollMonth : false,
						scrollTime  : false,
						scrollInput : false,
						className   : 'bb-schedule-activity-date-wrap',
					};
					var lastSelectedDate  = null;
					if ( $( '.bb-schedule-post_modal .bb-rl-modal-container' ).length ) {
						// Convert date to d/m/Y format for datepicker when editing.
						var dateField    = $( '.bb-schedule-post_dropdown_section .bb-schedule-activity-date-field' );
						var dateRawField = $( '.bb-schedule-activity-date-raw' );

						// If we have a raw date, convert it to d/m/Y format for the datepicker.
						if ( dateRawField.val() ) {
							var rawDate = dateRawField.val();
							if ( rawDate && rawDate.match( /^\d{4}-\d{2}-\d{2}$/ ) ) {
								// Convert Y-m-d to d/m/Y format.
								var dateParts     = rawDate.split( '-' );
								var formattedDate = dateParts[ 2 ] + '/' + dateParts[ 1 ] + '/' + dateParts[ 0 ];
								dateField.val( formattedDate );
							}
						}
						datepickerOptions.format           = 'd/m/Y';
						datepickerOptions.onSelectDate     = function ( $currentDate ) {
							var year         = $currentDate.getFullYear();
							var month        = String( $currentDate.getMonth() + 1 ).padStart( 2, '0' );
							var day          = String( $currentDate.getDate() ).padStart( 2, '0' );
							var formatted    = year + '-' + month + '-' + day;
							lastSelectedDate = formatted;
							$( '.bb-schedule-activity-date-raw' ).val( formatted );
						};
						datepickerOptions.onChangeDateTime = function ( $currentDate ) {
							var year      = $currentDate.getFullYear();
							var month     = String( $currentDate.getMonth() + 1 ).padStart( 2, '0' );
							var day       = String( $currentDate.getDate() ).padStart( 2, '0' );
							var formatted = year + '-' + month + '-' + day;
							var existDate = $( '.bb-schedule-activity-date-raw' ).val();
							if ( lastSelectedDate !== existDate ) {
								$( '.bb-schedule-activity-date-raw' ).val( formatted );
							}
							/**
							 * If we select date format as m Y ( i.e - 07 2025 as default date ), then
							 * Select date from datepicker ( i.e - 31 date ) will be saved as Y-m-d as 31-07-2025
							 * But if click outside of datepicker, then
							 * Select date from datepicker will be changed ( i.e - 01-08-2025 ) - Its default datepicker behavior.
							 * So we need to save the selected date to validate for custom date input.
							 */
							lastSelectedDate = formatted;
						};
					}
					$( '.bb-schedule-post_dropdown_section .bb-schedule-activity-date-field' ).datetimepicker( datepickerOptions );

					$( '.bb-schedule-post_dropdown_section .bb-schedule-activity-time-field' ).datetimepicker(
						{
							datepicker: false,
							format: 'h:i',
							formatTime: 'h:i',
							hours12: true,
							step: 5,
							className: 'bb-schedule-activity-time-picker',
						}
					);
				}

				if ( $( '.bb-server-date' ).length ) {
					$( '.bb-server-date' ).text( bp.Nouveau.bbServerTime().date );
				}

				if ( $( '.bb-server-year' ).length ) {
					$( '.bb-server-year' ).text( bp.Nouveau.bbServerTime().year );
				}

				if ( $( '.bb-server-time' ).length ) {
					$( '.bb-server-time' ).text( bp.Nouveau.bbServerTime().time );
				}
			},

			displaySchedulePosts: function ( event ) {
				event.preventDefault();
				var schedulePost = $( event.target ).closest( '.bb-schedule-posts' );
				schedulePost.find( '.bb-schedule-post_dropdown_list' ).removeClass( 'is_open' );
				schedulePost.find( '#bb-schedule-post_form_modal' ).hide();
				schedulePost.find( '#bb-schedule-posts_modal' ).show();
				schedulePost.find( '#bb-schedule-posts_modal .bb-action-popup-content:not(.bb-scrolling)' ).on(
					'scroll',
					function () {
						// replace dummy image with original image by faking scroll event.
						$( window ).scroll();
						$( this ).addClass( 'bb-scrolling' );
					}
				);

			},

			closeSchedulePosts: function ( event ) {
				event.preventDefault();
				var schedulePostModal = $( event.target ).closest( '#bb-schedule-posts_modal' );
				schedulePostModal.find( '.bb-action-popup-content' ).removeClass( 'has-content has-no-content' );
				schedulePostModal.find( '.bb-action-popup-content .schedule-posts-content' ).removeAttr( 'style' ).html( '' );
				schedulePostModal.hide();

				// Hide post form if user came to only view schedules posts from outside.
				if ( bp.Nouveau.SchedulePostView ) {
					$( '#activity-header .bb-model-close-button' ).trigger( 'click' );
					bp.Nouveau.SchedulePostView = false;
				}

				// Hide schedule post button if it was hidden before opening schedule posts.
				if ( bp.Nouveau.SchedulePostViewHidden ) {
					$( '.bb-schedule-post_dropdown_section' ).addClass( 'bp-hide' );
					bp.Nouveau.SchedulePostViewHidden = false;
				}
			},

			displayScheduleButton: function ( event ) {
				event.preventDefault();
				var schedulePost          = $( event.target ).closest( '.bb-schedule-posts' );
				var schedulePost_time     = schedulePost.find( '.bb-schedule-activity-time-field' ).val();
				var schedulePost_date_raw = schedulePost.find( '.bb-schedule-activity-date-field' ).val();
				var schedulePost_meridian = schedulePost.find( 'input[name="bb-schedule-activity-meridian"]:checked' ).val();

				var rlEdit = $( '.bb-schedule-post_modal .bb-rl-modal-container' );
				if ( rlEdit.length ) {
					schedulePost_date_raw = rlEdit.find( '.bb-schedule-activity-date-raw' ).val();
				}
				var UserDate          = new Date( schedulePost_date_raw + 'T00:00:00' ); // Include time to ensure no shift.
				var monthName         = UserDate.toLocaleString( 'en-us', { month: 'short' } );
				var dateNumber        = UserDate.getDate();
				var schedulePost_date = monthName + ' ' + dateNumber;

				if ( rlEdit.length ) {
					var dateYear      = UserDate.getFullYear();
					schedulePost_date = monthName + ' ' + dateNumber + ',' + dateYear;
				}

				// Check if time has passed and trigger warning and revert to normal post button.
				var activity_schedule_datetime = schedulePost_date_raw + ' ' + schedulePost_time + ' ' + schedulePost_meridian;
				var activity_schedule_date     = new Date( activity_schedule_datetime );
				var current_date               = new Date( bp.Nouveau.bbServerTime().currentServerTime );

				var threeMonthsAgo = new Date( current_date );
				threeMonthsAgo.setMonth( current_date.getMonth() + 3 );

				var whats_new_form = $( '#whats-new-form' );

				if ( current_date > activity_schedule_date ) {
					Backbone.trigger( 'onError', BP_Nouveau.activity_schedule.strings.scheduleWarning, 'warning' );
					// Clear Feedback after 3 sec.
					setTimeout(
						function () {
							Backbone.trigger( 'cleanFeedBack' );
						},
						3000
					);

					// If date is invalid then icon display and post button will be disabled.
					whats_new_form.find( '.bb-schedule-post_dropdown_section' ).removeClass( 'bp-hide' );
					whats_new_form.addClass( 'focus-in--empty' );
				} else if ( UserDate > threeMonthsAgo.getTime() ) {

					// Date validation based on current date.
					// If entered date is more than 3 months from current date, disable schedule button.
					if ( current_date > threeMonthsAgo.getTime() ) {
						$( '.bb-schedule-activity' ).attr( 'disabled', 'disabled' );
					}

					Backbone.trigger( 'onError', BP_Nouveau.activity_schedule.strings.scheduleWarning, 'warning' );
					// Clear Feedback after 3 sec.
					setTimeout(
						function () {
							Backbone.trigger( 'cleanFeedBack' );
						},
						3000
					);

					// If date is invalid then icon display and post button will be disabled.
					whats_new_form.find( '.bb-schedule-post_dropdown_section' ).removeClass( 'bp-hide' );
					whats_new_form.addClass( 'focus-in--empty' );
				} else {
					// Validate topic content with schedule post.
					if (
						! _.isUndefined( BP_Nouveau.activity.params.topics ) &&
						! _.isUndefined( BP_Nouveau.activity.params.topics.bb_is_enabled_activity_topics ) &&
						BP_Nouveau.activity.params.topics.bb_is_enabled_activity_topics &&
						! _.isUndefined( BP_Nouveau.activity.params.topics.bb_is_activity_topic_required ) &&
						BP_Nouveau.activity.params.topics.bb_is_activity_topic_required &&
						'undefined' !== typeof BBTopicsManager &&
						'undefined' !== typeof BBTopicsManager.bbTopicValidateContent
					) {
						BBTopicsManager.bbTopicValidateContent( {
							self         : this,
							selector     : whats_new_form,
							validContent : true,
							class        : 'focus-in--empty',
							data         : this.model.attributes,
							action       : 'schedule_post_form_submitted'
						} );
					} else {
						whats_new_form.removeClass( 'focus-in--empty' );
					}
					this.model.set( 'activity_action_type', 'scheduled' );
					this.model.set( 'activity_schedule_date_raw', schedulePost_date_raw );
					this.model.set( 'activity_schedule_date', schedulePost_date );
					this.model.set( 'activity_schedule_time', schedulePost_time );
					this.model.set( 'activity_schedule_meridiem', schedulePost_meridian );
					Backbone.trigger( 'cleanFeedBack' );

					// Check if user can schedule in feed.
					var whatsNewForm     = $( '#whats-new-form' ).length ? $( '#whats-new-form' ) : $( '#bb-rl-whats-new-form' );
					var schedule_allowed = whatsNewForm.find( '#bp-item-opt-' + this.model.attributes.item_id ).data( 'allow-schedule-post' );
					if ( _.isUndefined( schedule_allowed ) ) {
						// When change group from news feed.
						if (
							// On group page.
							! _.isUndefined( BP_Nouveau.activity_schedule.params.can_schedule_in_feed ) &&
							true === BP_Nouveau.activity_schedule.params.can_schedule_in_feed
						) {
							schedule_allowed = 'enabled';
						}
					}

					if ( ! _.isUndefined( schedule_allowed ) && 'enabled' === schedule_allowed ) {
						whatsNewForm.find( '.bb-schedule-post_dropdown_section' ).removeClass( 'bp-hide' );
					} else {

						// If schedule post is not allowed then reset schedule post data.
						this.model.set( 'activity_action_type', null );
						this.model.set( 'activity_schedule_date_raw', null );
						this.model.set( 'activity_schedule_date', null );
						this.model.set( 'activity_schedule_time', null );
						this.model.set( 'activity_schedule_meridiem', null );
						this.model.set( 'schedule_allowed', null );
						whatsNewForm.find( '.bb-schedule-post_dropdown_section' ).addClass( 'bp-hide' );
					}
				}
				$( event.target ).closest( '#bb-schedule-post_form_modal' ).hide();
			},

			validateScheduleTime: function () {
				var currentSelectedDate = $( '.bb-schedule-activity-date-field' ).val();
				if ( '' !== currentSelectedDate && '' !== $( '.bb-schedule-activity-time-field' ).val() ) {
					$( '.bb-schedule-activity' ).removeAttr( 'disabled' );
				} else {
					$( '.bb-schedule-activity' ).attr( 'disabled', 'disabled' );
				}
			},

			editScheduledPost: function ( event ) {
				event.preventDefault();

				var activity = $( event.target ).closest( 'li.activity-item' );
				var target   = $( event.target );

				// Edit the activity.
				if ( target.parent().hasClass( 'bb-activity-schedule_edit' ) ) {
					// Stop event propagation.
					event.preventDefault();
					var activity_data        = activity.data( 'bp-activity' );
					var activity_URL_preview = activity.data( 'link-url' ) !== '' ? activity.data( 'link-url' ) : null;

					// Set the activity schedule data.
					if ( 'scheduled' === activity_data.status ) {
						var activity_schedule_data               = activity.data( 'bb-scheduled-time' );
						activity_data.activity_schedule_date_raw = activity_schedule_data.date_raw;
						activity_data.activity_schedule_date     = activity_schedule_data.date;
						activity_data.activity_schedule_time     = activity_schedule_data.time;
						activity_data.activity_schedule_meridiem = activity_schedule_data.meridiem;
					}

					if ( 'undefined' !== typeof activity_data ) {
						bp.Nouveau.Activity.postForm.displayEditActivityForm( activity_data, activity_URL_preview );

						// Check if it's a Group activity.
						if ( target.closest( 'li' ).hasClass( 'groups' ) ) {
							$( '#bp-nouveau-activity-form' ).addClass( 'group-activity' );
						} else {
							$( '#bp-nouveau-activity-form' ).removeClass( 'group-activity' );
						}

						// Close the Media/Document popup if someone click on Edit while on Media/Document popup.
						if ( 'undefined' !== typeof bp.Nouveau.Media && 'undefined' !== typeof bp.Nouveau.Media.Theatre && ( bp.Nouveau.Media.Theatre.is_open_media || bp.Nouveau.Media.Theatre.is_open_document ) ) {
							$( document ).find( '.bb-close-media-theatre' ).trigger( 'click' );
							$( document ).find( '.bb-close-document-theatre' ).trigger( 'click' );
						}
					}
				}
			},

			deleteScheduledPost: function ( event ) {
				event.preventDefault();
				var confirm_deletion = confirm( BP_Nouveau.activity_schedule.strings.confirmDeletePost );
				var target           = $( event.target ).parent();
				var activity         = $( event.target ).closest( 'li.activity-item' );
				var activity_id      = activity.data( 'bp-activity-id' );
				var schedule_posts   = $( event.target ).closest( '.schedule-posts-content' );

				// Deleting or spamming.
				if ( confirm_deletion && target.hasClass( 'bb-activity-schedule_delete' ) ) {
					var li_parent;

					target.addClass( 'loading' );

					var ajaxData = {
						'action'    : 'delete_scheduled_activity',
						'id'        : activity_id,
						'_wpnonce'  : BP_Nouveau.activity_schedule.params.scheduled_post_nonce,
						'is_single' : target.closest( '[data-bp-single]' ).length
					};

					// Set defaults parent li to activity container.
					li_parent = activity;
					bp.ajax.post( ajaxData ).done(
						function () {
							target.removeClass( 'loading' );
							$( li_parent ).remove();
							
							// Update schedule post count.
							var $bb_rl_schedule_post_count = $( '.bb-rl-schedule-post-count' );
							if ( $bb_rl_schedule_post_count.length ) {
								var currentText = $bb_rl_schedule_post_count.text().trim();
								var count       = parseInt( currentText, 10 );
								
								// Only update if we have a valid number.
								if ( ! isNaN( count ) && count > 0 ) {
									var newCount = count - 1;
									// Hide count element if it reaches zero.
									if ( 0 === newCount ) {
										$bb_rl_schedule_post_count.text( '' );
										$bb_rl_schedule_post_count.addClass( 'bb-count-zero' ).removeClass( 'bb-rl-heading-count' );
									} else {
										$bb_rl_schedule_post_count.text( newCount );
										$bb_rl_schedule_post_count.removeClass( 'bb-count-zero' ).addClass( 'bb-rl-heading-count' );
									}
								} else if ( '' === currentText || isNaN( count ) ) {
									$bb_rl_schedule_post_count.text( '' ).addClass( 'bb-count-zero' );
								}
							}

							if ( 0 === schedule_posts.find( 'li' ).length ) {
								schedule_posts.closest( '.bb-action-popup-content' ).addClass( 'has-no-content' ).removeClass( 'has-content' );
							}

							$( document ).trigger(
								'bb_trigger_toast_message',
								[
									BP_Nouveau.activity_schedule.strings.successDeletionTitle,
									'<div>' + BP_Nouveau.activity_schedule.strings.successDeletionDesc + '</div>',
									'delete',
									null,
									true
								]
							);

							$( '#buddypress #bb-schedule-posts_modal .load-more' ).addClass( 'bb-page-item-deleted' );
						}
					).fail(
						function ( response ) {
							target.removeClass( 'loading' );
							var $error_block = '<aside class="bp-feedback bp-messages bp-template-notice error"><span class="bp-icon" aria-hidden="true"></span><p>' + response.feedback + '</p></aside>';
							li_parent.closest( '.schedule-posts-content' ).prepend( $error_block );
							li_parent.closest( '.schedule-posts-content' ).find( '.bp-feedback' ).hide().fadeIn( 500 );
						}
					);
				}

			},

			clearScheduledPost: function ( event ) {
				event.preventDefault();

				// Reset the schedule data and close form.
				this.model.set( 'edit_activity', false );
				this.model.set( 'activity_action_type', null );
				this.model.set( 'activity_schedule_date_raw', null );
				this.model.set( 'activity_schedule_date', null );
				this.model.set( 'activity_schedule_time', null );
				this.model.set( 'activity_schedule_meridiem', null );
				$( event.target ).closest( '#bb-schedule-post_form_modal' ).hide();

				// Check if user can schedule in feed.
				var whatsNewForm     = $( '#whats-new-form' ).length ? $( '#whats-new-form' ) : $( '#bb-rl-whats-new-form' );
				var schedule_allowed = whatsNewForm.find( '#bp-item-opt-' + this.model.attributes.item_id ).data( 'allow-schedule-post' );

				if ( _.isUndefined( schedule_allowed ) ) {
					// When change group from news feed.
					if (
						// On group page.
						! _.isUndefined( BP_Nouveau.activity_schedule.params.can_schedule_in_feed ) &&
						true === BP_Nouveau.activity_schedule.params.can_schedule_in_feed
					) {
						schedule_allowed = 'enabled';
					}
				}

				if ( ! _.isUndefined( schedule_allowed ) && 'enabled' === schedule_allowed ) {
					whatsNewForm.find( '.bb-schedule-post_dropdown_section' ).removeClass( 'bp-hide' );
				}
			}
		}
	);

	bp.Views.PostScheduleTime = Backbone.View.extend(
		{
			tagName: 'div',
			id: 'activity-schedule-section',
			template: bp.template( 'activity-schedule-details' ),

			initialize: function () {
				this.model.on( 'change', this.render, this );
			},

			render: function () {
				this.$el.html( this.template( this.model.attributes ) );
				return this;
			},
		}
	);

	// Launch BP Nouveau Subscriptions.
	bp.Nouveau.SchedulePost.start();

} )( bp, jQuery );
