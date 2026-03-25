/* global BBTopicsManager, bbTopics, bbTopicsManagerVars */

( function ( $ ) {

	/**
	 * [Activity Topic description]
	 *
	 * @type {Object}
	 */
	var BBTopics = {
		/**
		 * [start description]
		 *
		 * @return {[type]} [description]
		 */
		start: function () {
			if ( 'undefined' !== typeof BBTopicsManager ) {
				BBTopicsManager.config.modalSelector      = '#bb-rl-activity-topic-form_modal';
				BBTopicsManager.config.modalOpenClass     = 'bb-topic-modal-open';
				BBTopicsManager.config.closeModalSelector = '.bb-model-close-button, .bb-topic-cancel';

				// Migrate Topic Modal.
				BBTopicsManager.config.migrateTopicContainerModal = '#bb-rl-activity-migrate-topic_modal';
			}

			this.addListeners();
		},

		addListeners: function () {
			// Listen for modal open.
			$( document ).on( 'bb_modal_opened', this.initializeSelect2.bind( this ) );

			// Listen for modal close to destroy select2.
			$( document ).on( 'bb_modal_closed', this.destroySelect2.bind( this ) );
		},

		initializeSelect2: function () {
			var $topicName = $( '.bb_topic_name_select' );
			if ( $topicName.length ) {

				var existingSlugs = $topicName.data( 'existing-slugs' ) || [];

				// Destroy existing instance if any.
				if ( $topicName.data( 'select2' ) ) {
					$topicName.select2( 'destroy' );
				}

				// Initialize Select2.
				$topicName.select2(
					{
						theme         : 'bb-activity-topic',
						dropdownParent: $( '#bb-rl-activity-topic-form_modal' ).length ? $( '#bb-rl-activity-topic-form_modal' ) : $( '.bb-modal-panel--activity-topic' ),
						width         : '100%',
						allowClear    : true,
						tags          : 'allow_both' === bbTopics.group_topic_options || 'create_own_topics' === bbTopics.group_topic_options,
						createTag     : function ( params ) {
							// Only allow creating new tags if group_topic_options allows it.
							if ( 'only_from_activity_topics' === bbTopics.group_topic_options ) {
								return null;
							}

							var term = $.trim( params.term );

							if ( term === '' ) {
								return null;
							}

							// Sanitize the input to prevent XSS.
							term = term.replace( /[<>]/g, '' ); // Remove < and > characters.

							// Don't allow script-like content.
							var disallowedTerms   = ['script', 'javascript', 'vbscript', 'data:', 'alert'];
							var termLower         = term.toLowerCase();
							var hasDisallowedTerm = disallowedTerms.some( function ( disallowed ) {
								return termLower.indexOf( disallowed ) !== -1;
							} );

							var $error = $( '#bb-rl-activity-topic-form_modal .bb-hello-error' );
							if ( $error.length ) {
								$error.remove();
							}
							if ( hasDisallowedTerm ) {
								$( '#bb-rl-activity-topic-form_modal' ).find( '.bb-action-popup-content' ).prepend( '<div class="bb-hello-error"><i class="bb-icon-rf bb-icon-exclamation"></i>' + bbTopicsManagerVars.error_message + '</div>' );
								return null;
							}
							

							// Check if term exists in current options.
							var exists = false;
							$topicName.find( 'option' ).each(
								function () {
									if ( $( this ).text().toLowerCase() === term.toLowerCase() ) {
											exists = true;
											return false; // break the loop.
									}
								}
							);

							// Only create new tag if it doesn't exist.
							if ( ! exists ) {
								var uniqueSlug = BBTopics.generateUniqueSlug( existingSlugs, term );

								return {
									id:    uniqueSlug,
									text:  term,
									isNew: true,
									slug:  uniqueSlug,
								};
							}

							return null;
						},
						insertTag: function ( data, tag ) {
							data.push( tag );
						},
						templateResult: function ( data ) {
							if ( data.isNew ) {
								return $( '<span class="bb-topic-create-new"><i class="bb-icon-plus"></i> Create "<b>' + data.text + '</b>" ' + bbTopicsManagerVars.create_new_topic_text + '</span>' );
							}
							return $( '<span>' + data.text + '</span>' );
						},
						templateSelection: function ( data ) {
							var isGlobal = false;
							if (
								data.element &&
								$( data.element ).data( 'is-global-activity' )
							) {
								isGlobal = true === $( data.element ).data( 'is-global-activity' );
							}
							if ( data.isNew ) {
								return data.text;
							}
							if ( isGlobal ) {
								return $( '<span>' + data.text + ' <i class="bb-icon-globe"></i></span>' );
							}
							return data.text;
						}
					}
				).on(
					'select2:open',
					function () {
						// Ensure dropdown is visible.
						$( '.select2-dropdown' ).css( 'z-index', 999999 );
					}
				).on(
					'select2:select',
					function ( e ) {
						var data = e.params.data, selectedData;
						if ( data.isNew ) {
							// For new topics.
							selectedData = {
								name              : data.text,
								slug              : data.slug,
								isNew             : true,
								is_global_activity: data.is_global_activity
							};
							$topicName.data( 'selected', selectedData );
							$topicName.val( data.text );
						} else {
							// For existing topics.
							selectedData = {
								name              : data.text,
								id                : data.id,
								isNew             : false,
								is_global_activity: data.is_global_activity
							};
							$topicName.data( 'selected', selectedData );
							$topicName.val( data.text );
						}

						$( this ).next( '.select2-container' ).find( '.select2-selection--single' ).addClass( 'select2-selection--single--with-selection' );
					}
				).on(
					'select2:unselect',
					function () {
						$( this ).next( '.select2-container' ).find( '.select2-selection--single' ).removeClass( 'select2-selection--single--with-selection' );
					}
				);
			}
		},

		destroySelect2: function () {
			var $topicName = $( '.bb_topic_name_select' );
			if ( $topicName.data( 'select2' ) ) {
				$topicName.select2( 'destroy' );
			}
		},

		generateUniqueSlug: function ( existingSlugs, text ) {
			// Basic slug generation.
			var slug = text.toLowerCase().replace( /[^a-z0-9]+/g, '-' ).replace( /^-+|-+$/g, '' );

			// If slug doesn't exist, return it.
			if ( ! existingSlugs.includes( slug ) ) {
				return slug;
			}

			// If slug exists, add incremental number.
			var baseSlug = slug;
			var counter  = 1;
			while ( existingSlugs.includes( slug ) ) {
				slug = baseSlug + '-' + counter;
				counter++;
			}

			return slug;
		},
	};

	// Launch Activity Topic.
	BBTopics.start();

	// Make the Topics object available globally.
	window.BBTopics = BBTopics;

} )( jQuery );
