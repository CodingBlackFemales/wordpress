/* jshint browser: true */
/* global wp, bp, BP_Nouveau, _, Backbone, bbPollsVars, BBTopicsManager */
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
	 * [Poll description]
	 *
	 * @type {Object}
	 */
	bp.Nouveau.Poll = {
		/**
		 * [start description]
		 *
		 * @return {[type]} [description]
		 */
		start: function () {
			this.setupGlobals();
			this.addListeners();
		},

		/**
		 * [setupGlobals description]
		 *
		 * @return {[type]} [description]
		 */
		setupGlobals: function () {
			this.pollFormInitialized = false;
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {
			$( '#bp-nouveau-activity-form, #bb-rl-activity-form' ).on( 'click', '.post-elements-buttons-item.post-poll', this.showPollForm.bind( this ) );
			$( '#bp-nouveau-activity-form, #bb-rl-activity-form' ).on( 'click', '.bb-activity-poll-cancel, .bb-activity-poll_modal .bb-model-close-button', this.hidePollForm.bind( this ) );
			$( document ).on( 'keyup', '.bb-activity-poll-new-option-input', this.validateNewOption.bind( this ) );
			$( document ).on( 'click', '.bb-activity-option-submit', this.submitNewOption.bind( this ) );
			$( document ).on( 'click', '.bb-activity-poll-see-more-link', this.seeMoreOptions.bind( this ) );
			$( document ).on( 'click', '.bb-poll-option-view-state', this.showPollState.bind( this ) );
			$( document ).on( 'click', '.bb-poll-option_remove', this.removePollOption.bind( this ) );
			$( document ).on( 'click', '.bb-activity-poll-option input[type="radio"]', this.addPollVote.bind( this ) );
			$( document ).on( 'change', '.bb-activity-poll-option input[type="checkbox"]', this.addPollVote.bind( this ) );
			$( document ).on( 'click', '.bb-close-action-popup, .bb-activity-poll-state_overlay', this.closeVoteState.bind( this ) );

		},

		showPollForm: function () {
			if ( $.fn.sortable && ! $( '.bb-poll-question_options' ).hasClass( 'ui-sortable' ) ) {
				$( '.bb-poll-question_options' ).sortable(
					{
						placeholder: 'sortable-placeholder',
						update     : function () {
							reIndexOptions();
						},
						cancel     : 'input, .bb-poll-edit-option_remove' // Prevent dragging when interacting with inputs or the "x" icon.
					}
				);
			}

			if ( ! this.pollFormInitialized ) {
				// Handle adding new option.
				$( document ).on(
					'click',
					'.bb-poll-option_add',
					function ( e ) {
						e.preventDefault();
						var $this = $( this );
						$this.attr( 'disabled', 'disabled' );

						var $popupContent = $this.closest( '.bb-action-popup-content' );
						var customIndex   = $popupContent.find( '.bb-poll_option_draggable:not(.custom)' ).length;
						var optLength     = customIndex;

						// Max 10 options are allowed.
						if ( customIndex >= 10 ) {
							return;
						}

						// Clone the first existing option element.
						var $newOptionClone = $popupContent.find( '.bb-poll_option_draggable:first' ).clone();

						// Clear the value of the cloned input field.
						$newOptionClone.find( '.bb-poll-question_option' ).val( '' ).removeAttr( 'disabled' );

						// Update the name attribute with the correct index.
						$newOptionClone.find( '.bb-poll-question_option' ).attr( 'name', 'bb-poll-question-option[' + customIndex + ']' );

						$newOptionClone.find( '.bb-poll-question_option' ).attr( 'data-opt_id', ++optLength );

						if ( 2 <= customIndex ) {
							$newOptionClone.append( '<a href="#" class="bb-poll-edit-option_remove"><span class="bb-icon-l bb-icon-times"></span></a>' );
						}

						// Insert the new option element before the add button.
						$newOptionClone.insertAfter( $popupContent.find( '.bb-poll-question_options .bb-poll_option_draggable' ).last() );

						$this.removeAttr( 'disabled' );

						if ( customIndex === 9 ) {
							$popupContent.find( '.bb-poll-option_add' ).attr( 'disabled', 'disabled' ).parent().addClass( 'bp-hide' );
						}

						// Re-validate form.
						validateForm();
					}
				);

				// Handle removing a new option.
				$( document ).on(
					'click',
					'.bb-poll-edit-option_remove',
					function ( e ) {
						e.preventDefault();
						var $this         = $( this );
						var $popupContent = $this.closest( '.bb-action-popup-content' );
						var customIndex   = $popupContent.find( '.bb-poll_option_draggable:not(.custom)' ).length;

						// Min 2 options are required.
						if ( customIndex <= 2 ) {
							return;
						}
						$this.closest( '.bb-poll_option_draggable' ).remove();
						$popupContent.find( '.bb-poll-option_add' ).removeAttr( 'disabled' ).parent().removeClass( 'bp-hide' );
						reIndexOptions();
						validateForm();
					}
				);

				$( document ).on(
					'keyup',
					'.bb-poll-question-field, .bb-poll-question_option',
					function () {
						validateForm();
					}
				);

				$( document ).on(
					'change',
					'.bb-poll_duration, #bb-poll-allow-multiple-answer, #bb-poll-allow-new-option',
					function () {
						validateForm();
					}
				);

				this.pollFormInitialized = true;
			}

			// Re-index the options.
			function reIndexOptions() {
				$( '#bb-activity-poll-form_modal .bb-action-popup-content' ).find( '.bb-poll_option_draggable:not(.custom)' ).each(
					function ( index ) {
						$( this ).find( '.bb-poll-question_option' ).attr( 'name', 'bb-poll-question-option[' + index + ']' );
					}
				);
				$( '#bb-activity-poll-form_modal .bb-activity-poll-submit' ).removeAttr( 'disabled' );
			}

			function validateForm() {
				var isValid    = true;
				var $PollPopup = $( '#bb-activity-poll-form_modal' );

				// Check options.
				$PollPopup.find( '.bb-poll-question_option' ).each(
					function () {
						if ( $( this ).val() === '' && ! $( this ).parent().hasClass( 'custom' ) ) {
							isValid = false;
						}
					}
				);

				// Check question.
				if ( $PollPopup.find( '.bb-poll-question-field' ).val() === '' ) {
					isValid = false;
				}

				if ( isValid ) {
					$PollPopup.find( '.bb-activity-poll-submit' ).removeAttr( 'disabled' );
				} else {
					$PollPopup.find( '.bb-activity-poll-submit' ).attr( 'disabled', true );
				}

			}

			$( '[class*="whats-new-form-footer"]' ).find( '.bb-poll-form .bb-action-popup' ).show();
		},

		hidePollForm: function ( e ) {
			e.preventDefault();
			var pollFormPopup = $( e.currentTarget ).closest( '.bb-action-popup' );
			var pollID        = pollFormPopup.data( 'poll_id' );
			if ( ! pollID ) {
				// Check if any form field has data.
				var hasData = pollFormPopup.find( 'input[type="text"]' ).filter( function () {
					return '' !== $( this ).val();
				} ).length > 0 || 1 !== pollFormPopup.find( 'select' ).prop( 'selectedIndex' ) || pollFormPopup.find( 'input[type="checkbox"]' ).is( ':checked' );

				// Trigger confirmation only if any field has data.
				if ( hasData && confirm( BP_Nouveau.activity_polls.strings.closePopupConfirm ) ) {
					pollFormPopup.find( 'input[type="text"]' ).val( '' );
					pollFormPopup.find( 'select' ).prop( 'selectedIndex', 1 );
					pollFormPopup.find( 'input[type="checkbox"]' ).prop( 'checked', false );
					pollFormPopup.find( '.bp-messages.bp-feedback' ).hide();
					pollFormPopup.find( '.bb-poll_option_draggable' ).not( ':lt(2)' ).remove();
					pollFormPopup.find( '.bb-activity-poll-submit' ).attr( 'disabled', true );
				}
			}
			$( e.currentTarget ).closest( '.bb-action-popup' ).hide();
			var form_footer =  $( e.currentTarget ).closest( '.whats-new-form-footer' ).length ? $( e.currentTarget ).closest( '.whats-new-form-footer' ) : $( e.currentTarget ).closest( '.bb-rl-whats-new-form-footer' );
			form_footer.find( '.post-poll.active' ).removeClass( 'active' );
			// Reset Activity Form Media action.
			window.activityMediaAction = null;
		},

		validateNewOption: function ( e ) {
			var $newOptionField = $( e.currentTarget );
			var $newOption      = $newOptionField.closest( '.bb-activity-poll-new-option' );
			var value = $newOptionField.val().trim();
			if ( '' !== value && value.length <= 50 ) {
				$newOption.addClass( 'is-valid' );
			} else {
				$newOption.removeClass( 'is-valid' );
			}

			// If enter key is pressed, submit the new option.
			if ( e.key === 'Enter' || e.keyCode === 13 ) {
				if ( $newOption.hasClass( 'is-valid' ) ) {
					this.submitNewOption( e );
				}
			}
		},

		submitNewOption: function( e )  {
			e.preventDefault();
			// Make a call to submit a new option here.
			var eventTarget = $( e.currentTarget );
			if ( eventTarget.hasClass( 'adding' ) ) {
				return;
			}
			eventTarget.addClass( 'adding' );
			var activity    = eventTarget.parents( '.activity-item' ).data( 'bp-activity' );
			if (
				! _.isUndefined( activity ) &&
				! _.isUndefined( activity.poll ) &&
				! _.isUndefined( activity.poll.id ) &&
				! _.isUndefined( activity.poll.allow_new_option )
			) {
				var pollID              = activity.poll.id;
				var existingpollOptions = activity.poll.options ?  Object.values( activity.poll.options ) : [];
				var newPollOption       = eventTarget.parent().find( '.bb-activity-poll-new-option-input' ).val();
				var optionExists        = existingpollOptions.some(
					function ( option ) {
						return jQuery.trim( option.option_title ) === jQuery.trim( newPollOption );
					}
				);
				var activtyId           = activity.id;
				if ( ! optionExists ) {
					$.ajax(
						{
							url: bbPollsVars.ajax_url,
							data: {
								'action': 'bb_pro_add_poll_option',
								'nonce': bbPollsVars.nonce.add_poll_option_nonce,
								'poll_id': pollID,
								'activity_id': activtyId,
								'poll_option': newPollOption,
							},
							method: 'POST'
						}
					).done(
						function ( response ) {
							if (
								response.success &&
								! _.isUndefined( response.data ) &&
								! _.isUndefined( response.data.option_data )
							) {
								activity.poll.options = response.data.all_options;

								var activityPollEntry = new bp.Views.ActivityPollEntry( response.data );
								$( '#activity-' + activtyId + ' .bb-activity-poll_block' ).replaceWith( activityPollEntry.render().el );

								eventTarget.parents( '.activity-item' ).attr( 'data-bp-activity', JSON.stringify( activity ) );
								eventTarget.parent().removeClass( 'is-invalid' );
								eventTarget.parent().siblings( '.bb-poll-error' ).removeClass( 'is-visible' );
								eventTarget.removeClass( 'adding' );
							} else {
								// error.
								eventTarget.parent().addClass( 'is-invalid' ).removeClass( 'is-valid' );
								eventTarget.closest( '.bb-activity-poll-option' ).siblings( '.bb-poll-error.limit-error' ).addClass( 'is-visible' ).text( response.data );
								eventTarget.removeClass( 'adding' );
							}
						}
					);
				} else {
					// error.
					eventTarget.parent().addClass( 'is-invalid' ).removeClass( 'is-valid' );
					eventTarget.closest( '.bb-activity-poll-option' ).siblings( '.bb-poll-error.duplicate-error' ).addClass( 'is-visible' );
					eventTarget.removeClass( 'adding' );
				}
			}
		},

		seeMoreOptions: function ( e ) {
			e.preventDefault();
			var $target = $( e.currentTarget );
			if ( $target.hasClass( 'see-less' ) ) {
				$target.removeClass( 'see-less' );
				$target.parents( '.bb-activity-poll-options' ).find( '.bb-activity-poll-option' ).not( ':has(.bb-activity-poll-new-option-input)' ).slice( 5, 10 ).addClass( 'bb-activity-poll-option-hide' ).removeClass( 'is-visible' );
			} else {
				$target.parents( '.bb-activity-poll-options' ).find( '.bb-activity-poll-option' ).not( ':has(.bb-activity-poll-new-option-input)' ).slice( 5, 10 ).addClass( 'is-visible' );
				$target.addClass( 'see-less' );
			}
		},

		showPollState: function ( e ) {
			e.preventDefault();
			var $target              = $( e.currentTarget );
			var self                 = this;
			var $statePopup          = $target.closest( '#bb-poll-view' ).find( '#bb-activity-poll-state_modal' );
			var statePopupHeading    = $target.closest( '.bb-activity-poll-option' ).find( 'label' ).text();
			var bbActionPopupContent = $target.closest( '#bb-poll-view' ).find( '.bb-action-popup-content' );
			$statePopup.find( '.bb-model-header h4' ).text( statePopupHeading );
			$statePopup.show();
			var activity = $target.parents( '.activity-item' ).data( 'bp-activity' );
			if (
				! _.isUndefined( activity ) &&
				! _.isUndefined( activity.poll ) &&
				! _.isUndefined( activity.poll.id ) &&
				! _.isUndefined( activity.poll.options )
			) {
				var optionId = $target.data( 'opt_id' );
				if ( optionId ) {
					var args = {
						'element': bbActionPopupContent,
						'poll_id': activity.poll.id,
						'activity_id': activity.id,
						'option_id': optionId,
						'paged': 1
					};
					self.loadPollState( args );
				}
			}
		},

		loadPollState: function ( args ) {
			var self           = this;
			var contentElement = args.element;
			var pollID         = args.poll_id;
			var activityId     = args.activity_id;
			var optionId       = args.option_id;
			var paged          = args.paged;
			$.ajax(
				{
					url: bbPollsVars.ajax_url,
					data: {
						'action': 'bb_pro_poll_vote_state',
						'nonce': bbPollsVars.nonce.poll_vote_state_nonce,
						'poll_id': pollID,
						'activity_id': activityId,
						'option_id': optionId,
						'paged': paged
					},
					method: 'POST'
				}
			).done(
				function ( response ) {
					if ( response.success && ! _.isUndefined( response.data ) && ! _.isUndefined( response.data.members ) ) {
						var args = {
							vote_state: ( response.success ) ? response.data : {},
						};

						var pollState = new bp.Views.ActivityPollState( args );
						if ( 1 === paged ) {
							contentElement.html( pollState.render().el ); // Initial load.
						} else {
							contentElement.find( '.has-more-vote-state li:last' ).after( pollState.render().el ); // Load more.
						}

						contentElement.find( '.bb-poll-state-loader' ).remove();

						var $hasMoreVoteState = contentElement.find( '.has-more-vote-state' );
						$hasMoreVoteState.attr( 'data-paged', response.data.others.paged ).attr( 'data-total_pages', response.data.others.total_pages );

						if ( response.data.others.paged >= response.data.others.total_pages ) {
							$hasMoreVoteState.removeClass( 'has-more-vote-state' );
						} else {
							// Add scroll event listener for infinite scroll.
							contentElement.off( 'scroll' ).on(
								'scroll',
								function () {
									if ( contentElement.scrollTop() + contentElement.innerHeight() >= contentElement[0].scrollHeight ) {
										if ( $hasMoreVoteState.hasClass( 'has-more-vote-state' ) && contentElement.find( '.bb-poll-state-loader' ).length === 0 ) {
											contentElement.find( 'ul.activity-state_users' ).append( '<li class="bb-poll-state-loader"><span class="bb-icon-spinner animate-spin"></span></li>' );
											var args = {
												'element': contentElement,
												'poll_id': pollID,
												'activity_id': activityId,
												'option_id': optionId,
												'paged': paged + 1
											};
											self.loadPollState( args );
										}
									}
								}
							);
						}
					}
				}
			);
		},

		closeVoteState: function ( e ) {
			e.preventDefault();
			var $target     = $( e.currentTarget );
			var $statePopup = $target.closest( '#bb-poll-view' ).find( '#bb-activity-poll-state_modal' );
			$statePopup.find( '.bb-action-popup-content .bb-action-popup-content-dynamic' ).html( '' );
			$statePopup.find( '.bb-activity-poll-loader' ).show();
			$statePopup.hide();
		},

		removePollOption: function ( e ) {
			e.preventDefault();

			var $target  = $( e.currentTarget );
			var activity = $target.parents( '.activity-item' ).data( 'bp-activity' );
			if (
				! _.isUndefined( activity ) &&
				! _.isUndefined( activity.poll ) &&
				! _.isUndefined( activity.poll.id )
			) {
				var pollID    = activity.poll.id;
				var activtyId = activity.id;
				var optionId  = 0;
				if ( $target.parent( '.bb-activity-poll-option' ).find( '.bp-radio-wrap' ).length ) {
					optionId = $target.parent( '.bb-activity-poll-option' ).find( '.bs-styled-radio' ).data( 'opt_id' );
				} else {
					optionId = $target.parent( '.bb-activity-poll-option' ).find( '.bs-styled-checkbox' ).data( 'opt_id' );
				}
				if ( optionId ) {
					if ( confirm( BP_Nouveau.activity_polls.strings.areYouSure ) ) {
						$.ajax(
							{
								url: bbPollsVars.ajax_url,
								data: {
									'action': 'bb_pro_remove_poll_option',
									'nonce': bbPollsVars.nonce.remove_poll_option_nonce,
									'poll_id': pollID,
									'activity_id': activtyId,
									'option_id': optionId,
								},
								method: 'POST'
							}
						).done(
							function ( response ) {
								if (
									response.success &&
									! _.isUndefined( response.data ) &&
									! _.isUndefined( response.data.option_data )
								) {
									activity.poll.options = response.data.all_options;

									var activityPollEntry = new bp.Views.ActivityPollEntry( response.data );
									$( '#activity-' + activtyId + ' .bb-activity-poll_block' ).html( activityPollEntry.render().el );

									// Remove option and update new data.
									$target.parents( '.activity-item' ).attr( 'data-bp-activity', JSON.stringify( activity ) );

									$target.parent( '.bb-activity-poll-option' ).remove();
								}
							}
						);
					}
				}
			}
		},

		addPollVote: function ( e ) {
			e.preventDefault();

			var $target  = $( e.currentTarget );
			var activity = $target.parents( '.activity-item' ).data( 'bp-activity' );
			if (
				! _.isUndefined( activity ) &&
				! _.isUndefined( activity.poll ) &&
				! _.isUndefined( activity.poll.id ) &&
				! _.isUndefined( activity.poll.options )
			) {
				var pollID    = activity.poll.id;
				var activtyId = activity.id;
				var optionId  = $target.data( 'opt_id' );
				$target.addClass( 'is-checked' );
				if ( optionId ) {
					$.ajax(
						{
							url: bbPollsVars.ajax_url,
							data: {
								'action': 'bb_pro_add_poll_vote',
								'nonce': bbPollsVars.nonce.add_poll_vote_nonce,
								'poll_id': pollID,
								'activity_id': activtyId,
								'option_id': optionId
							},
							method: 'POST'
						}
					).done(
						function ( response ) {
							if (
								response.success &&
								! _.isUndefined( response.data ) &&
								! _.isUndefined( response.data.vote_data )
							) {
								// Replace the updated total votes.
								activity.poll.total_votes = response.data.total_votes;
								activity.poll.options     = response.data.all_options;

								var activityPollEntry = new bp.Views.ActivityPollEntry( response.data );
								var activityItem = $( '#activity-' + activtyId ).length ? $( '#activity-' + activtyId + ' .bb-activity-poll_block' ) : $( '#bb-rl-activity-' + activtyId + ' .bb-activity-poll_block' );

								if ( activityItem.length ) {
									activityItem.replaceWith( activityPollEntry.render().el );
								}

								$target.parents( '.activity-item' ).attr( 'data-bp-activity', JSON.stringify( activity ) );
							}
						}
					);
				}
			}
		}

	};

	bp.Views.activityPollForm = Backbone.View.extend(
		{
			tagName: 'div',
			id: 'bb-poll-form',
			className: 'bb-poll-form',
			template: bp.template( 'bb-activity-poll-form' ),

			events: {
				'click .bb-activity-poll-submit': 'submitPoll',
			},

			initialize: function () {
				this.model.on( 'change:poll change:poll_id', this.render, this );
			},

			render: function () {
				this.$el.html( this.template( this.model.attributes ) );
				return this;
			},

			submitPoll: function ( event ) {
				event.preventDefault();

				var pollForm                   = $( event.target ).closest( '#bb-activity-poll-form_modal' );
				var pollID                     = pollForm.data( 'poll_id' );
				var poll_que                   = pollForm.find( '.bb-poll-question-field' ).val();
				var poll_options               = pollForm.find( '.bb-poll-question_option' ).map(
					function () {
						if ( ! $( this ).parent().hasClass( 'custom' ) ) {
							var obj  = {};
							var key  = $( this ).attr( 'data-opt_id' );
							obj[key] = $( this ).val();
							return obj;
						}
					}
				).get();
				var poll_allow_multiple_answer = pollForm.find( '#bb-poll-allow-multiple-answer' ).is( ':checked' );
				var poll_allow_new_option      = pollForm.find( '#bb-poll-allow-new-option' ).is( ':checked' );
				var poll_duration              = pollForm.find( '.bb-poll_duration' ).val();
				var activityID                 = pollForm.data( 'activity_id' );
				var model                      = this.model;

				var groupID = 0;
				if ( $( '#item-header' ).length && 'groups' === $( '#item-header' ).data( 'bp-item-component' ) ) {
					groupID = $( '#item-header' ).data( 'bp-item-id' );
				} else if ( ! _.isUndefined( model.get( 'object' ) ) && 'group' === model.get( 'object' ) && ! _.isUndefined( model.get( 'item_id' ) ) ) {
					groupID = model.get( 'item_id' );
				}

				// Duration validate.
				if (
					! _.isUndefined( model.get( 'edit_activity' ) ) &&
					true === model.get( 'edit_activity' ) &&
					! _.isUndefined( model.get( 'poll' ) ) &&
					! _.isUndefined( model.get( 'poll' ).duration ) &&
					model.get( 'poll' ).duration !== poll_duration
				) {
					poll_duration = model.get( 'poll' ).duration;
				}

				$( event.target ).addClass( 'loading' );

				$.ajax(
					{
						url: bbPollsVars.ajax_url,
						data: {
							'action': 'bb_pro_add_poll',
							'nonce': bbPollsVars.nonce.add_poll_nonce,
							'poll_id': pollID,
							'activity_id': activityID,
							'group_id': groupID,
							'questions': poll_que,
							'options': poll_options,
							'allow_multiple_answer': poll_allow_multiple_answer,
							'allow_new_option': poll_allow_new_option,
							'duration': poll_duration
						},
						method: 'POST'
					}
				).done(
					function ( response ) {
						if ( response.success && typeof response.data !== 'undefined' ) {
							var pollObject = {
								id: response.data.id,
								item_id: response.data.item_id,
								user_id: parseInt( response.data.user_id ),
								vote_disabled_date: response.data.vote_disabled_date,
								question: response.data.question,
								options: response.data.options,
								total_votes: response.data.total_votes
							};

							if ( response.data.settings ) {
								pollObject.allow_multiple_options = response.data.settings.allow_multiple_options || false;
								pollObject.allow_new_option       = response.data.settings.allow_new_option || false;
								pollObject.duration               = response.data.settings.duration || 3;
							}
							model.set( 'poll', pollObject );
							model.set( 'poll_id', response.data.id );

							// Validate topic content with poll.
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
									selector     : $( '#whats-new-form' ),
									validContent : true,
									class        : 'focus-in--empty',
									data         : model.attributes,
									action       : 'poll_form_submitted'
								} );
							} else {
								$( '#whats-new-form' ).removeClass( 'focus-in--empty' );
							}

							$( event.target ).closest( '#bb-activity-poll-form_modal' ).hide();
							window.activityMediaAction = null;
							pollForm.find( '#bb-poll-allow-multiple-answer .bb-action-popup-content > .bp-feedback' ).removeClass( 'active' );
						}
						if ( false === response.success ) {
							pollForm.find( '.bb-action-popup-content > .bp-feedback' ).addClass( 'active' ).find( 'p' ).text( response.data );
						}
						$( event.target ).removeClass( 'loading' );
					}
				);
			}
		}
	);

	bp.Views.activityPollView = Backbone.View.extend(
		{
			tagName: 'div',
			id: 'bb-poll-view',
			className: 'bb-poll-view',
			template: bp.template( 'bb-activity-poll-view' ),

			events: {
				'click .bb-activity-poll-options-action': 'showOptions',
				'click .bb-activity-poll-action-edit': 'editPoll',
				'click .bb-activity-poll-action-delete': 'deletePoll'
			},

			initialize: function () {
				this.model.on( 'change:activity_poll_title change:activity_poll_options change:activity_poll_allow_multiple_answer change:activity_poll_allow_new_option change:activity_poll_duration', this.render, this );
				this.model.on( 'change', this.render, this );

				$( document ).on(
					'click',
					function ( event ) {
						if ( ! $( event.target ).closest( '.bb-activity-poll-options-wrap' ).length ) {
							$( '.bb-activity-poll-options-wrap' ).removeClass( 'active' );
						}
					}
				);
			},

			render: function () {
				this.$el.html( this.template( this.model.attributes ) );
				return this;
			},

			showOptions: function ( event ) {
				event.preventDefault();
				$( event.target ).closest( '.bb-activity-poll-options-wrap' ).toggleClass( 'active' );
			},

			editPoll: function ( e ) {
				e.preventDefault();
				bp.Nouveau.Poll.showPollForm();
			},

			deletePoll: function ( e ) {
				e.preventDefault();

				var $target        = $( e.currentTarget );
				var $this          = this;
				var confirm_delete = confirm( BP_Nouveau.activity_polls.strings.DeletePollConfirm );
				if ( confirm_delete ) {
					var pollID     = $target.parents( '.poll_view' ).data( 'poll_id' );
					var activityID = $target.parents( '.poll_view' ).data( 'activity_id' );
					$.ajax(
						{
							url: bbPollsVars.ajax_url,
							data: {
								'action': 'bb_pro_remove_poll',
								'nonce': bbPollsVars.nonce.remove_poll_nonce,
								'poll_id': pollID,
								'activity_id': activityID
							},
							method: 'POST'
						}
					).done(
						function ( response ) {
							if ( response.success ) {
								$this.resetPollData();
								$( '.post-poll.active' ).removeClass( 'active' );
								if ( bp.draft_activity.data.poll ) {
									if ( '' === bp.draft_activity.data.content ) {
										$( '#discard-draft-activity' ).trigger( 'click' );
									} else {
										bp.Nouveau.Activity.postForm.collectDraftActivity();
									}
								} else {
									bp.Nouveau.refreshActivities();
								}
							}
						}
					);
				} else {
					$( '.bb-activity-poll-options-wrap.active' ).removeClass( 'active' );
				}
			},

			resetPollData: function () {
				this.model.set( 'poll', {} );
				this.model.set( 'poll_id', '' );
			}
		}
	);

	bp.Views.ActivityPollState = Backbone.View.extend(
		{
			tagName: 'div',
			className: 'bb-action-popup-content-dynamic',
			template: bp.template( 'bb-activity-poll-state' ),

			initialize: function ( options ) {
				this.data = options;
			},
			render: function () {
				this.$el.html( this.template( this.data ) );
				return this;
			},
		}
	);

	bp.Views.ActivityPollEntry = Backbone.View.extend(
		{
			tagName: 'div',
			className: 'bb-activity-poll_block',
			template: bp.template( 'bb-activity-poll-entry' ),

			initialize: function ( poll_data ) {
				this.data = poll_data;
			},
			render: function () {
				this.$el.html( this.template( this.data ) );
				return this;
			},
		}
	);

	// Launch Poll.
	bp.Nouveau.Poll.start();

} )( bp, jQuery );
