/* global bp, BP_Nouveau, _, Backbone, jQuery, bp_media_dropzone, Cropper */

/**
 * Activity Post Feature Image JavaScript.
 *
 * @since [BBVERSION]
 */

( function ( $ ) {
	'use strict';

	/**
	 * Activity Post Feature Image functionality.
	 */
	var BBActivityPostFeatureImage = {

		_eventsBound           : false,
		bbActivityFeatureImage : ! _.isUndefined( BP_Nouveau.activity.params.post_feature_image ) ? BP_Nouveau.activity.params.post_feature_image : {},
		cropperInstance        : null,
		currentFile            : null,
		fileID                 : null,
		canEditFeatureImage    : true,

		init : function () {
			this.initFeatureImageView();
			this.bindEvents();
			this.initDropzone();
		},

		/**
		 * Initialize dropzone for featured image.
		 */
		initDropzone: function () {
			// Set up dropzones auto discover to false so it does not automatically set dropzones.
			if ( 'undefined' !== typeof window.Dropzone ) {
				window.Dropzone.autoDiscover = false;
			}
		},

		/**
		 * Bind events.
		 */
		bindEvents: function () {
			// Prevent multiple bindings.
			if ( this._eventsBound ) {
				return;
			}

			// Set up other event listeners (updatePostFormInitial is already set up at script load).
			var $body                   = $( 'body' );
			var $whatsNewForm           = $( '#whats-new-form' );
			var $awWhatsNewResetName    = '#aw-whats-new-reset';
			var activityHeaderCloseName = '.activity-header .close';
			if ( ! $whatsNewForm.length ) {
				$whatsNewForm           = $( '#bb-rl-whats-new-form' );
				$awWhatsNewResetName    = '#bb-rl-aw-whats-new-reset';
				activityHeaderCloseName = '.bb-rl-activity-header .close';
			}

			// Remove any existing event handlers to prevent duplicates.
			$( document ).ready( function () {

				$body.on( 'bb_activity_event', function ( event, data ) {
					switch ( data.type ) {
						case 'bb_activity_attachments_destroy':
							BBActivityPostFeatureImage.handleAttachmentsDestroyEvent( event, data );
							break;
						case 'bb_activity_form_data':
							BBActivityPostFeatureImage.handleFormDataEvent( event, data, $whatsNewForm );
							break;
						case 'bb_activity_reset_draft':
							BBActivityPostFeatureImage.handleResetDraftEvent( event, data, $whatsNewForm );
							break;
						case 'bb_activity_post_success':
							BBActivityPostFeatureImage.handlePostSuccessEvent( event, data );
							break;
						case 'bb_activity_edit_loaded_at_end':
							BBActivityPostFeatureImage.handlePostEditEvent( event, data );
							break;
						case 'bb_activity_draft_loaded':
							BBActivityPostFeatureImage.handleDraftLoadedEvent( event, data );
							break;
						case 'bb_activity_draft_data_keys':
							BBActivityPostFeatureImage.handleDraftDataKeysEvent( event, data );
							break;
						case 'bb_activity_draft_collect_activity':
							BBActivityPostFeatureImage.handleDraftCollectActivityEvent( event, data );
							break;
						case 'bb_activity_privacy_changed':
							BBActivityPostFeatureImage.handlePrivacyChangedEvent( event, data );
							break;
						case 'bb_activity_form_prep':
							BBActivityPostFeatureImage.handleFormPrepEvent( event, data );
							break;
					}
				} );

				// Hide feature image button when privacy header is updated.
				Backbone.on( 'privacy:headerupdate', function () {
					$whatsNewForm.find( '.bb-activity-post-feature-image-button' ).addClass( 'bp-hide' );
				} );

				// Hide feature image button when privacy selector is updated.
				Backbone.on( 'privacySelector', function () {
					if (
						'user' === BBActivityPostFeatureImage.getCurrentModel().get( 'object' ) &&
						! _.isUndefined( BBActivityPostFeatureImage.bbActivityFeatureImage ) &&
						! _.isUndefined( BBActivityPostFeatureImage.bbActivityFeatureImage.can_upload_post_feature_image ) &&
						true === Boolean( BBActivityPostFeatureImage.bbActivityFeatureImage.can_upload_post_feature_image )
					) {
						$whatsNewForm.find( '.bb-activity-post-feature-image-button' ).removeClass( 'bp-hide' );
					} else {
						$whatsNewForm.find( '.bb-activity-post-feature-image-button' ).addClass( 'bp-hide' );
					}
				} );

				// Listen for form close/reset events to handle draft preservation at the right time
				$( document ).on( 'click', $awWhatsNewResetName + ', ' + activityHeaderCloseName, function ( event ) {
					if ( typeof BBActivityPostFeatureImage !== 'undefined' && BBActivityPostFeatureImage.handleFormClose ) {
						BBActivityPostFeatureImage.handleFormClose( event );
					}
					// Clean up cropper when form is closed.
					if ( typeof BBActivityPostFeatureImage !== 'undefined' ) {
						BBActivityPostFeatureImage.closeCropper();
					}
				} );

				var observer    = null;
				var initialized = false;

				// Check if already available.
				if ( BBActivityPostFeatureImage.isReadyToInitialize() ) {
					BBActivityPostFeatureImage.init();
					return;
				}

				// Function to handle form found event.
				var onFormFound = function () {
					initialized = BBActivityPostFeatureImage.initializeFeatureImage( observer, initialized );
				};

				// Set up mutation observer for DOM changes.
				observer = BBActivityPostFeatureImage.setupMutationObserver( onFormFound );

				// Fallback: initialize after 5 seconds if an activity form is not found.
				setTimeout( function () {
					if ( observer ) {
						observer.disconnect();
					}
					if ( ! initialized ) {
						BBActivityPostFeatureImage.init();
					}
				}, 5000 );

			} );
			$( document ).off( 'click', '#bb-activity-post-feature-image-control' );
			$( document ).on( 'click', '#bb-activity-post-feature-image-control', this.handleButtonClick );

			if ( typeof Cropper !== 'undefined' ) {
				$( document ).on( 'click', '.bb-feature-image-crop-btn', this.handleCropButtonClick );
				$( document ).on( 'click', '.bb-feature-image-crop-save', this.handleCropSave );
				$( document ).on( 'click', '.bb-feature-image-crop-cancel', this.handleCropCancel );
			}

			// Mark events as bound.
			this._eventsBound = true;
		},

		/**
		 * Handle attachments destroy event.
		 *
		 * @param {Object} data - The draft activity.
		 */
		handleAttachmentsDestroyEvent : function ( event, data ) {
			var draft_activity = ( data && data.draft_activity ) || bp.draft_activity || {};

			if ( typeof draft_activity.is_discard_draft_activity === 'undefined' ||
			     draft_activity.is_discard_draft_activity === false ) {
				bp.draft_activity.allow_delete_post_feature_image = false;
			}

			BBActivityPostFeatureImage.destroyFeatureImageDropzone();
		},

		/**
		 * Handle form data event.
		 *
		 * @param {Object} data - The draft activity.
		 */
		handleFormDataEvent : function ( event, data, $whatsNewForm ) {
			if (
				! _.isUndefined( BBActivityPostFeatureImage.bbActivityFeatureImage ) &&
				! _.isUndefined( BBActivityPostFeatureImage.bbActivityFeatureImage.can_upload_post_feature_image ) &&
				true === Boolean( BBActivityPostFeatureImage.bbActivityFeatureImage.can_upload_post_feature_image )
			) {
				$whatsNewForm.find( '.bb-activity-post-feature-image-button' ).removeClass( 'bp-hide' );
			}

			var feature_image_id = ! _.isUndefined( data.data.bb_activity_post_feature_image ) && ! _.isUndefined( data.data.bb_activity_post_feature_image.id ) ? data.data.bb_activity_post_feature_image.id : '';
			if ( ! _.isUndefined( data.data.can_upload_post_feature_image ) ) {
				delete data.data.can_upload_post_feature_image;
			}
			if ( ! _.isUndefined( data.data.bb_activity_post_feature_image ) ) {
				delete data.data.bb_activity_post_feature_image;
			}
			if ( data.model && data.model.unset && ! _.isUndefined( data.model.attributes.can_upload_post_feature_image ) ) {
				data.model.unset( 'can_upload_post_feature_image' );
			}
			if ( data.model && data.model.attributes && ! _.isUndefined( data.model.attributes.can_upload_post_feature_image ) ) {
				delete data.model.attributes.can_upload_post_feature_image;
			}
			if (
				'undefined' !== typeof BBActivityPostFeatureImage &&
				! _.isUndefined( data.data ) &&
				! _.isUndefined( feature_image_id ) &&
				'' !== feature_image_id
			) {
				data.data = _.extend(
					data.data,
					{
						bb_activity_post_feature_image_id    : feature_image_id,
						bb_activity_post_feature_image_nonce : BBActivityPostFeatureImage.bbActivityFeatureImage.nonce.save
					}
				);
			}
		},

		handleResetDraftEvent : function ( event, data, $whatsNewForm ) {
			if (
				! _.isUndefined( BBActivityPostFeatureImage.bbActivityFeatureImage ) &&
				! _.isUndefined( BBActivityPostFeatureImage.bbActivityFeatureImage.can_upload_post_feature_image ) &&
				true === Boolean( BBActivityPostFeatureImage.bbActivityFeatureImage.can_upload_post_feature_image )
			) {
				$whatsNewForm.find( '.bb-activity-post-feature-image-button' ).removeClass( 'bp-hide' );
			}
		},

		handlePostSuccessEvent : function ( event, data ) {
			if (
				typeof BBActivityPostFeatureImage !== 'undefined' &&
				BBActivityPostFeatureImage.handleActivityPostSuccess
			) {
				BBActivityPostFeatureImage.handleActivityPostSuccess( data );
			}
		},

		handlePostEditEvent : function ( event, data ) {
			if (
				typeof BBActivityPostFeatureImage !== 'undefined' &&
				BBActivityPostFeatureImage.handleActivityEditLoaded
			) {
				bp.draft_activity.allow_delete_post_feature_image = true;
				BBActivityPostFeatureImage.handleActivityEditLoaded( data );
			}
		},

		handleDraftLoadedEvent : function ( event, data ) {
			if (
				typeof BBActivityPostFeatureImage !== 'undefined' &&
				BBActivityPostFeatureImage.handleActivityDraftLoaded
			) {
				bp.draft_activity.allow_delete_post_feature_image = true;
				BBActivityPostFeatureImage.handleActivityDraftLoaded( data );
			}
		},

		handleDraftDataKeysEvent : function ( event, data ) {
			data.draft_data_keys.push( 'feature_image_allowed' );
			data.draft_data_keys.push( 'bb_activity_post_feature_image' );
		},

		handleDraftCollectActivityEvent : function ( event, data ) {
			if (
				typeof BBActivityPostFeatureImage !== 'undefined' &&
				BBActivityPostFeatureImage.handleActivityDraftCollectActivity
			) {
				BBActivityPostFeatureImage.handleActivityDraftCollectActivity( data );
			}
		},

		handlePrivacyChangedEvent : function ( event, data ) {
			if ( typeof BBActivityPostFeatureImage !== 'undefined' && BBActivityPostFeatureImage.handleActivityPrivacyChanged ) {
				BBActivityPostFeatureImage.handleActivityPrivacyChanged( data );
			}
		},

		handleFormPrepEvent : function ( event, data ) {
			if ( typeof BBActivityPostFeatureImage !== 'undefined' && BBActivityPostFeatureImage.handleActivityFormPrep ) {
				BBActivityPostFeatureImage.handleActivityFormPrep( data );
			}
		},

		/**
		 * Handle button click
		 *
		 * @param {Event} e - The click event
		 */
		handleButtonClick: function ( e ) {
			e.preventDefault();
			e.stopPropagation();

			// Get a featured image section.
			var $featuredImageSection = $( '#bb-activity-post-feature-image' );
			var $whatsNewForm         = $( '#whats-new-form' );
			if ( ! $whatsNewForm.length ) {
				$whatsNewForm = $( '#bb-rl-whats-new-form' );
			}

			if ( $featuredImageSection.length ) {
				// Check if a featured image section is currently visible.
				if ( $featuredImageSection.is( ':visible' ) ) {
					// Hide the section.
					$featuredImageSection.hide();
					$( this ).removeClass( 'active' );
				} else {
					// Show the section and position it.
					$featuredImageSection.show();
					$( this ).addClass( 'active' );

					var $userStatusHuddle = $whatsNewForm.find( '#user-status-huddle' );
					if ( ! $userStatusHuddle.length ) {
						$userStatusHuddle = $whatsNewForm.find( '#bb-rl-user-status-huddle' );
					}

					if ( $userStatusHuddle.length ) {
						// Reposition featured image section.
						$featuredImageSection.insertAfter( $userStatusHuddle );
					}

					// Initialize dropzone if not already initialized.
					if ( 'undefined' !== typeof window.Dropzone && ! $featuredImageSection.data( 'dropzone-initialized' ) ) {
						BBActivityPostFeatureImage.initializeDropzone( $featuredImageSection );
					}
				}
			}
		},

		/**
		 * Handle feature image position button click - REMOVED (guillotine functionality removed)
		 */

		/**
		 * Handle feature image save button click - REMOVED (guillotine functionality removed)
		 */

		/**
		 * Handle crop button click.
		 *
		 * @param {Event} e - The click event.
		 */
		handleCropButtonClick: function( e ) {
			e.preventDefault();
			e.stopPropagation();

			// Check if Cropper is available
			if ( typeof Cropper === 'undefined' ) {
				return;
			}

			var $preview           = $( e.target ).closest( '.dz-preview' );
			var $image             = $preview.find( '.dz-image img' );
			var $whatsNewFormField = $( '#whats-new-form' );
			if ( ! $whatsNewFormField.length ) {
				$whatsNewFormField = $( '#bb-rl-whats-new-form' );
			}

			if ( $image.length ) {
				$( $whatsNewFormField ).addClass( BBActivityPostFeatureImage.getFocusClass() );
				BBActivityPostFeatureImage.openCropper( $image[ 0 ] );
			}
		},

		/**
		 * Handle crop save button click.
		 *
		 * @param {Event} e - The click event.
		 */
		handleCropSave: function( e ) {
			e.preventDefault();
			e.stopPropagation();

			BBActivityPostFeatureImage.saveCrop();
		},

		/**
		 * Handle crop cancel button click.
		 *
		 * @param {Event} e - The click event.
		 */
		handleCropCancel: function( e ) {
			e.preventDefault();
			e.stopPropagation();

			BBActivityPostFeatureImage.closeCropper();
		},

		/**
		 * Open the cropper section.
		 *
		 * @param {HTMLElement} imageElement - The image element to crop.
		 */
		openCropper : function ( imageElement ) {
			var self            = this;
			var $container      = $( '#bb-activity-post-feature-image' );
			var $cropperSection = $container.find( '.bb-feature-image-cropper-section' );
			var $dropzone       = $container.find( '#activity-post-feature-image-uploader' );

			// Start with cropper section invisible for fade-in effect.
			$cropperSection.addClass( 'bb-cropper-entering' );

			// Show the cropper section (still invisible due to opacity: 0).
			$cropperSection.show();

			// Hide dropzone.
			$dropzone.hide();

			// Initialize cropper.
			self.initCropper( imageElement );

			// Trigger fade-in after a brief delay to allow CSS transition.
			setTimeout( function() {
				$cropperSection.removeClass( 'bb-cropper-entering' );
			}, 50 );
		},

		/**
		 * Initialize the cropper.
		 *
		 * @param {HTMLElement} imageElement - The image element to crop.
		 */
		initCropper: function( imageElement ) {
			// Check if Cropper is available
			if ( typeof Cropper === 'undefined' ) {
				return;
			}

			var self       = this;
			var $cropImage = $( '#bb-feature-image-to-crop' );

			// Clear the image source first to prevent old image flash
			$cropImage.attr( 'src', '' );

			// Set the image source.
			$cropImage.attr( 'src', imageElement.src );

			// Destroy existing cropper instance.
			if ( this.cropperInstance ) {
				try {
					this.cropperInstance.destroy();
				} catch ( e ) {
					console.warn( BBActivityPostFeatureImage.bbActivityFeatureImage.strings.error_dc, e );
				}
				this.cropperInstance = null;
			}

			// Initialize new cropper instance.
			this.cropperInstance = new Cropper( $cropImage[ 0 ], {
				aspectRatio              : 3.70,
				viewMode                 : 1,
				dragMode                 : 'move',
				autoCropArea             : 1,
				restore                  : false,
				guides                   : false,
				center                   : false,
				highlight                : false,
				cropBoxMovable           : false,
				cropBoxResizable         : false,
				toggleDragModeOnDblclick : false,
				ready                    : function () {
					// Cropper is ready.
				},
				crop                     : function ( event ) {
					// Store crop data.
					self.cropData = {
						x      : Math.round( event.detail.x ),
						y      : Math.round( event.detail.y ),
						width  : Math.round( event.detail.width ),
						height : Math.round( event.detail.height )
					};
				}
			} );
		},

		/**
		 * Save the cropped image.
		 */
		saveCrop: function() {
			if ( ! this.cropperInstance || ! this.cropData ) {
				this.closeCropper();
				return;
			}

			var self = this;
			var $whatsNewFormField = $( '#whats-new-form' );
			if ( ! $whatsNewFormField.length ) {
				$whatsNewFormField = $( '#bb-rl-whats-new-form' );
			}

			// Get cropped canvas.
			var canvas = this.cropperInstance.getCroppedCanvas( {
				width                 : 1200, // Max width for feature image.
				height                : 436, // Max height for feature image (1200 / 2.75).
				fillColor             : '#fff',
				imageSmoothingEnabled : true,
				imageSmoothingQuality : 'high'
			} );

			if ( canvas ) {
				// Convert canvas to blob.
				canvas.toBlob( function ( blob ) {
					// Create FormData to send the cropped image blob.
					var formData = new FormData();
					var groupID  = 0;
					var $groupSingleHeader = '.bb-rl-groups-single-wrapper';
					if ( ! $( $groupSingleHeader ).length ) {
						$groupSingleHeader = '#item-header';
					}
					if ( $( $groupSingleHeader ).length && 'groups' === $( $groupSingleHeader ).data( 'bp-item-component' ) ) {
						groupID = $( $groupSingleHeader ).data( 'bp-item-id' );
					} else if ( ! _.isUndefined( BBActivityPostFeatureImage.getCurrentModel().get( 'object' ) ) && 'group' === BBActivityPostFeatureImage.getCurrentModel().get( 'object' ) && ! _.isUndefined( BBActivityPostFeatureImage.getCurrentModel().get( 'item_id' ) ) ) {
						groupID = BBActivityPostFeatureImage.getCurrentModel().get( 'item_id' );
					}
					formData.append( 'action', 'activity_post_feature_image_crop_replace' );
					formData.append( '_wpnonce', BBActivityPostFeatureImage.bbActivityFeatureImage.nonce.crop_replace );
					formData.append( 'postid', BBActivityPostFeatureImage.fileID );
					formData.append( 'group_id', groupID );
					formData.append( 'file', blob, 'cropped-image.jpg' );

					// Update the dropzone file.
					var $dropzone = $( '#activity-post-feature-image-uploader' );
					if ( $dropzone.length && $dropzone[ 0 ].dropzone ) {
						var dropzone = $dropzone[ 0 ].dropzone;

						if ( dropzone.files.length > 0 ) {
							// Get the original file.
							var originalFile = dropzone.files[ 0 ];

							// Update the original file with the cropped version.
							originalFile.dataURL  = canvas.toDataURL( 'image/jpeg', 0.9 );
							originalFile.cropped  = true;
							originalFile.cropData = self.cropData;

							// Update the preview image.
							var $preview = dropzone.element.querySelector( '.dz-preview' );
							if ( $preview ) {
								var $img = $preview.querySelector( '.dz-image img' );
								if ( $img ) {
									$img.src = canvas.toDataURL( 'image/jpeg', 0.9 );
								}

								// Hide the crop button immediately to prevent re-clicking.
								var $cropOverlay = $preview.querySelector( '.bb-feature-image-crop-overlay' );
								if ( $cropOverlay ) {
									$cropOverlay.style.display = 'none';
								}
							}
						}
					}

					$.ajax( {
						url         : BP_Nouveau.ajaxurl,
						type        : 'POST',
						data        : formData,
						processData : false,
						contentType : false,
						success     : function ( response ) {
							if ( response.data && response.data.id ) {
								// Store the server response for later use when posting.
								var $dropzone = $( '#activity-post-feature-image-uploader' );
								if ( $dropzone.length && $dropzone[ 0 ].dropzone ) {
									var dropzone = $dropzone[ 0 ].dropzone;
									if ( dropzone.files.length > 0 ) {
										var originalFile                = dropzone.files[ 0 ];
										originalFile.serverCropResponse = response.data;
										originalFile.cropped            = true;

										// Hide the crop button overlay since the image is now cropped.
										var $preview = $( originalFile.previewElement );
										if ( $preview.length ) {
											$preview.find( '.bb-feature-image-crop-overlay' ).remove();
										}

										// Update the model with cropped status.
										var featureImage = BBActivityPostFeatureImage.getCurrentModel().get( 'bb_activity_post_feature_image' );
										if ( featureImage ) {
											featureImage.cropped = true;
											BBActivityPostFeatureImage.getCurrentModel().set( 'bb_activity_post_feature_image', featureImage );
										}
									}
								}
								$( $whatsNewFormField ).removeClass( BBActivityPostFeatureImage.getFocusClass() );
							}
						},
						error       : function ( xhr, status, error ) {
							console.error( BBActivityPostFeatureImage.bbActivityFeatureImage.strings.crop_operation_failed, error );
							console.error( BBActivityPostFeatureImage.bbActivityFeatureImage.strings.failed_to_save_cropped_image );
							$( $whatsNewFormField ).removeClass( BBActivityPostFeatureImage.getFocusClass() );
						}
					} );

					// Close cropper and show dropzone.
					self.closeCropper();
				}, 'image/jpeg', 0.9 );
			}
		},

		/**
		 * Close the cropper section.
		 */
		closeCropper: function() {
			var self       = this;
			var $container = $( '#bb-activity-post-feature-image' );
			var $cropperSection = $container.find( '.bb-feature-image-cropper-section' );
			var $dropzone       = $container.find( '#activity-post-feature-image-uploader' );

			// Add fade-out class to trigger transition.
			$cropperSection.addClass( 'bb-cropper-leaving' );

			// Wait for transition to complete before hiding.
			setTimeout( function() {
				// Destroy cropper instance.
				if ( self.cropperInstance ) {
					try {
						self.cropperInstance.destroy();
					} catch ( e ) {
						console.warn( BBActivityPostFeatureImage.bbActivityFeatureImage.strings.error_dc, e );
					}
					self.cropperInstance = null;
				}

				// Hide cropper section and remove transition classes.
				$cropperSection.hide().removeClass( 'bb-cropper-entering bb-cropper-leaving' );

				// Show dropzone.
				$dropzone.show();

				var $whatsNewFormField = $( '#whats-new-form' );
				if ( ! $whatsNewFormField.length ) {
					$whatsNewFormField = $( '#bb-rl-whats-new-form' );
				}
				$( $whatsNewFormField ).removeClass( BBActivityPostFeatureImage.getFocusClass() );

				// Clear crop data.
				self.cropData = null;
			}, 200 ); // Match CSS transition duration.
		},

		/**
		 * Handle form close event.
		 */
		handleFormClose: function( ) {
			// Clean up any resources when the form is closed.
			this.closeCropper();
		},

		/**
		 * Cleanup method to prevent memory leaks.
		 * Call this when the component is being destroyed.
		 */
		cleanup : function () {
			// Clean up cropper.
			if ( this.cropperInstance ) {
				try {
					this.cropperInstance.destroy();
				} catch ( e ) {
					console.warn( BBActivityPostFeatureImage.bbActivityFeatureImage.strings.error_dc_during_cleanup, e );
				}
				this.cropperInstance = null;
			}

			// Clean up dropzone.
			var $dropzoneElement = $( '#activity-post-feature-image-uploader' );
			if ( $dropzoneElement.length && $dropzoneElement[ 0 ].dropzone ) {
				try {
					$dropzoneElement[ 0 ].dropzone.destroy();
					$dropzoneElement[ 0 ].dropzone = null;
				} catch ( e ) {
					console.warn( BBActivityPostFeatureImage.bbActivityFeatureImage.strings.error_dd_during_cleanup, e );
				}
			}

			// Reset state.
			this.currentFile = null;
			this.fileID      = null;
			this.cropData    = null;
		},

		/**
		 * Handle feature image cancel button click - REMOVED (guillotine functionality removed)
		 */

		/**
		 * Initialize dropzone for featured image.
		 *
		 * @param {jQuery} $container - The container element.
		 */
		initializeDropzone: function ( $container ) {
			var $dropzone = $container.find( '#activity-post-feature-image-uploader' );

			if ( ! $dropzone.length ) {
				return;
			}

			// Check if dropzone is already initialized.
			if ( $container.data( 'dropzone-initialized' ) ) {
				return;
			}

			var featureImage       = null;
			var $whatsNewFormField = '#whats-new-form';
			if ( ! $( $whatsNewFormField ).length ) {
				$whatsNewFormField = '#bb-rl-whats-new-form';
			}

			// Dropzone options.
			var dropzoneOptions = {
				url                  : BP_Nouveau.ajaxurl,
				timeout              : 3 * 60 * 60 * 1000,
				dictFileTooBig       : ! _.isUndefined( bp_media_dropzone ) ? bp_media_dropzone.dictFileTooBig : '',
				acceptedFiles        : 'image/*',
				autoProcessQueue     : true,
				addRemoveLinks       : true,
				uploadMultiple       : false,
				maxFiles             : BBActivityPostFeatureImage.bbActivityFeatureImage.config.max_file,
				maxFilesize          : BBActivityPostFeatureImage.bbActivityFeatureImage.config.max_upload_size,
				thumbnailWidth       : null,
				thumbnailHeight      : null,
				previewTemplate      : document.getElementsByClassName( 'activity-post-feature-image-default-template' )[ 0 ] ? document.getElementsByClassName( 'activity-post-feature-image-default-template' )[ 0 ].innerHTML : '',
				maxThumbnailFilesize : BBActivityPostFeatureImage.bbActivityFeatureImage.config.max_file ? BBActivityPostFeatureImage.bbActivityFeatureImage.config.max_file : 2,
				preventDuplicates    : true,
			};

			var activityPostFeatureImageDropzone = new window.Dropzone( '#activity-post-feature-image-uploader', dropzoneOptions );

			activityPostFeatureImageDropzone.on(
				'addedfile',
				function ( file ) {
					$( $whatsNewFormField ).addClass( BBActivityPostFeatureImage.getFocusClass() );

					// Since we only allow one file, clear any existing files.
					if ( activityPostFeatureImageDropzone.files.length > 1 ) {
						activityPostFeatureImageDropzone.removeFile( activityPostFeatureImageDropzone.files[ 0 ] );
					}

					if ( file.media_edit_data ) {
						featureImage = file.media_edit_data; // Set as a single object.
						BBActivityPostFeatureImage.getCurrentModel().set( 'bb_activity_post_feature_image', featureImage );
					}

					$container.addClass( 'has-file' );
				}
			);

			activityPostFeatureImageDropzone.on(
				'uploadprogress',
				function ( element ) {

					$container.closest( $whatsNewFormField ).addClass( 'feature-image-uploading' );

					var circle        = $( element.previewElement ).find( '.dz-progress-ring circle' )[ 0 ];
					var radius        = circle.r.baseVal.value;
					var circumference = radius * 2 * Math.PI;

					circle.style.strokeDasharray  = circumference + ' ' + circumference;
					circle.style.strokeDashoffset = circumference;
					circle.style.strokeDashoffset = circumference - element.upload.progress.toFixed( 0 ) / 100 * circumference;
				}
			);

			activityPostFeatureImageDropzone.on(
				'sending',
				function ( file, xhr, formData ) {
					var groupID = 0;
					var $groupSingleHeader = '.bb-rl-groups-single-wrapper';
					if ( ! $( $groupSingleHeader ).length ) {
						$groupSingleHeader = '#item-header';
					}
					if ( $( $groupSingleHeader ).length && 'groups' === $( $groupSingleHeader ).data( 'bp-item-component' ) ) {
						groupID = $( $groupSingleHeader ).data( 'bp-item-id' );
					} else if ( ! _.isUndefined( BBActivityPostFeatureImage.getCurrentModel().get( 'object' ) ) && 'group' === BBActivityPostFeatureImage.getCurrentModel().get( 'object' ) && ! _.isUndefined( BBActivityPostFeatureImage.getCurrentModel().get( 'item_id' ) ) ) {
						groupID = BBActivityPostFeatureImage.getCurrentModel().get( 'item_id' );
					}
					formData.append( 'action', 'activity_post_feature_image_upload' );
					formData.append( 'group_id', groupID );
					formData.append( '_wpnonce', BBActivityPostFeatureImage.bbActivityFeatureImage.nonce.upload );
				}
			);

			activityPostFeatureImageDropzone.on(
				'success',
				function ( file, response ) {
					if ( response.data.id ) {
						file.id                = response.data.id;
						response.data.uuid     = file.upload.uuid;
						response.data.group_id = ! _.isUndefined( BBActivityPostFeatureImage.bbActivityFeatureImage.group_id ) ? BBActivityPostFeatureImage.bbActivityFeatureImage.group_id : false;
						response.data.saved    = false;
						// Since we only allow one file, set as a single object.
						featureImage                        = response.data;
						featureImage.can_edit_feature_image = true;
						featureImage.cropped                = false; // Fresh uploads are not cropped.
						BBActivityPostFeatureImage.getCurrentModel().set( 'bb_activity_post_feature_image', featureImage );

						// Replace the base64 thumbnail with the server-generated URL.
						// But preserve cropped preview if the file has been cropped.
						if ( response.data.url ) {
							var $thumbnail = $( file.previewElement ).find( '.dz-image > img' );
							if ( $thumbnail.length ) {
								// Only update the src if the file hasn't been cropped
								if ( ! file.cropped ) {
									// Add load event handler before changing src.
									$thumbnail.off( 'load error' ).on( 'load', function () {
										// Remove any error classes that might have been added.
										$( file.previewElement ).removeClass( 'dz-error' );
										$( file.previewElement ).find( '.dz-error-message' ).hide();
										$( file.previewElement ).removeClass( 'bb-processing' );
									} ).on( 'error', function () {
										var message = BBActivityPostFeatureImage.bbActivityFeatureImage.invalid_media_type;
										file.previewElement.classList.add( 'dz-error' );
										var node, _i, _len, _ref, _results;
										_ref     = file.previewElement.querySelectorAll( '[data-dz-errormessage]' );
										_results = [];
										for ( _i = 0, _len = _ref.length; _i < _len; _i++ ) {
											node = _ref[ _i ];
											_results.push( node.textContent = message );
										}
										BBActivityPostFeatureImage.clearFeatureImage();
									} );

									// Change the src after setting up event handlers.
									$thumbnail.attr( 'src', response.data.url );
									$thumbnail.attr( 'data-dz-thumbnail', response.data.url );
									$( file.previewElement ).addClass( 'bb-processing' );
								} else {
									// For cropped files, store the server URL but keep the cropped preview
									file.serverUrl       = response.data.url;
									file.serverThumbnail = response.data.url;
								}
							}
						}
						BBActivityPostFeatureImage.fileID = response.data.id;

					} else {
						Backbone.trigger( 'onError', (
							'<div>' + BBActivityPostFeatureImage.bbActivityFeatureImage.invalid_media_type + '. ' + response.data.feedback + '</div>'
						) );
						this.removeFile( file );
					}

					bp.draft_content_changed = true;
				}
			);

			activityPostFeatureImageDropzone.on(
				'error',
				function ( file, response ) {
					var errorMessage;
					if ( file.accepted ) {
						errorMessage = BBActivityPostFeatureImage.getErrorMessage( file.xhr, response );
						
						if ( ! _.isUndefined( response ) && ! _.isUndefined( response.data ) && ! _.isUndefined( response.data.feedback ) ) {
							$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
						} else if ( 'error' === file.status && (
						            file.xhr && 0 === file.xhr.status
						) ) { // update server error text to user friendly.
							$( file.previewElement ).find( '.dz-error-message span' ).text( BBActivityPostFeatureImage.bbActivityFeatureImage.connection_lost_error );
						} else {
							// Use the standardized error message for any other errors
							$( file.previewElement ).find( '.dz-error-message span' ).text( errorMessage );
						}
					} else {
						// For non-accepted files, use a proper error message
						errorMessage = BBActivityPostFeatureImage.bbActivityFeatureImage.invalid_media_type;
						if ( response && typeof response === 'object' ) {
							// Try to extract meaningful error message from response object
							if ( response.data && response.data.feedback ) {
								errorMessage += '. ' + response.data.feedback;
							} else if ( response.message ) {
								errorMessage += '. ' + response.message;
							} else {
								errorMessage += '. ' + BBActivityPostFeatureImage.bbActivityFeatureImage.strings.upload_failed;
							}
						} else if ( response && typeof response === 'string' ) {
							errorMessage += '. ' + response;
						}
						
						Backbone.trigger( 'onError', '<div>' + errorMessage + '</div>' );
						this.removeFile( file );
						$container.closest( $whatsNewFormField ).removeClass( 'feature-image-uploading' );
					}
				}
			);

			activityPostFeatureImageDropzone.on(
				'removedfile',
				function ( file ) {
					var featureImage = BBActivityPostFeatureImage.getCurrentModel().get( 'bb_activity_post_feature_image' );
					if ( true === bp.draft_activity.allow_delete_post_feature_image ) {
						// Since we only have one file, simply clear the feature image.
						if (
							file.id &&
							featureImage &&
							(
								(
									! _.isUndefined( featureImage.saved ) &&
									! featureImage.saved
								) ||
								(
									! _.isUndefined( featureImage.can_edit_feature_image ) &&
									! featureImage.can_edit_feature_image
								)
							)
						) {
							BBActivityPostFeatureImage.removeAttachment( file.id );
						}
						BBActivityPostFeatureImage.getCurrentModel().set( 'bb_activity_post_feature_image', null );
						// Check if bp.draft_activity.data is an object before setting properties
						if ( bp.draft_activity.data && typeof bp.draft_activity.data === 'object' ) {
							bp.draft_activity.data.bb_activity_post_feature_image = null;
						}

						// Clear the feature image.
						featureImage = null;
						BBActivityPostFeatureImage.clearFeatureImage();

						// Remove container styling.
						$container.removeClass( 'has-file' );
						$container.closest( $whatsNewFormField ).removeClass( 'feature-image-uploading' );

						// Remove dz-started class to show dz-message again when no files remain
						if ( this.files.length === 0 ) {
							$container.removeClass( 'dz-started' );
						}

						if ( $( '#message-feedabck' ).hasClass( 'noMediaError' ) ) {
							BBActivityPostFeatureImage.getCurrentModel().unset( 'errors' );
						}

						bp.draft_content_changed = true;
					}
				}
			);

			// Enable submit button when upload is complete.
			activityPostFeatureImageDropzone.on(
				'complete',
				function ( file ) {
					// Add position controls to the preview (simplified - no positioning functionality).
					// Check if the file has already been cropped (from server response or client-side crop).
					var isCropped = file.cropped || ( file.media_edit_data && file.media_edit_data.cropped );
					if ( ! _.isUndefined( BBActivityPostFeatureImage.canEditFeatureImage ) && true === Boolean( BBActivityPostFeatureImage.canEditFeatureImage ) ) {
						BBActivityPostFeatureImage.addPositionControls( file.previewElement, isCropped );
					}
					var $whatsNewElem = $( '#whats-new' );
					if ( ! $whatsNewElem.length ) {
						$whatsNewElem = $( '#bb-rl-whats-new' );
					}
					$whatsNewElem.trigger( 'input' );
					if ( 0 === this.getUploadingFiles().length && 0 === this.getQueuedFiles().length ) {
						$container.closest( $whatsNewFormField ).removeClass( 'feature-image-uploading' );
					}
				}
			);

			$container.find( '#activity-post-feature-image-uploader' ).addClass( 'open' ).removeClass( 'closed' );
			$( '#whats-new-attachments' ).removeClass( 'empty' ).closest( $whatsNewFormField ).addClass( 'focus-in--attm' );

			// Mark as initialized.
			$container.data( 'dropzone-initialized', true );
		},

		/**
		 * Check if ReadyLaunch is active.
		 *
		 * @returns {boolean} True if ReadyLaunch is active.
		 */
		isReadyLaunch: function() {
			return $( '#bb-rl-whats-new-form' ).length > 0 || $( '#bb-rl-user-status-huddle' ).length > 0;
		},

		/**
		 * Get the correct focus class based on ReadyLaunch usage.
		 *
		 * @returns {string} The appropriate focus class name.
		 */
		getFocusClass: function() {
			return this.isReadyLaunch() ? 'bb-rl-focus-in--empty' : 'focus-in--empty';
		},

		/**
		 * Add position controls to the dropzone preview.
		 *
		 * @param {HTMLElement} previewElement - The preview element.
		 * @param {boolean}     isCropped      - Whether the image has already been cropped.
		 */
		addPositionControls: function( previewElement, isCropped ) {
			var $preview = $( previewElement );

			// Set initial position data to center.
			$preview.attr( 'data-position', 'center' );

			// Skip adding the crop button if the image is already cropped.
			if ( isCropped ) {
				return;
			}

			// Add crop button overlay only if Cropper.js is available.
			if ( typeof Cropper !== 'undefined' ) {
				// Determine the appropriate crop icon class based on ReadyLaunch.
				var cropIconClass = this.isReadyLaunch() ? 'bb-icons-rl-crop' : 'bb-icon-l bb-icon-crop';

				var $cropButton = $( '<div class="bb-feature-image-crop-overlay">' +
				                     '<button type="button" class="bb-feature-image-crop-btn" title="' + BBActivityPostFeatureImage.bbActivityFeatureImage.strings.reposition_crop_image + '">' +
				                     '<i class="' + cropIconClass + '"></i>' +
				                     '<span>' + BBActivityPostFeatureImage.bbActivityFeatureImage.strings.reposition_crop + '</span>' +
				                     '</button>' +
				                     '</div>' );

				$preview.append( $cropButton );
			}
		},

		/**
		 * Remove an attachment.
		 *
		 * @param {string} id - The attachment ID.
		 */
		removeAttachment: function ( id ) {
			var data = {
				'action'   : 'activity_post_feature_image_delete',
				'_wpnonce' : BBActivityPostFeatureImage.bbActivityFeatureImage.nonce.delete,
				'id'       : id
			};
			var activity_id = $( '#bp-activity-id' ).val();
			if ( activity_id ) {
				data.activity_id = activity_id;
			}

			$.ajax(
				{
					type : 'POST',
					url  : BP_Nouveau.ajaxurl,
					data : data
				}
			);

			if ( ! _.isUndefined( BBActivityPostFeatureImage.canEditFeatureImage ) && false === Boolean( BBActivityPostFeatureImage.canEditFeatureImage ) ) {
				BBActivityPostFeatureImage.destroyFeatureImageDropzone();
				$( '#bb-activity-post-feature-image' ).hide();
			}
		},

		/**
		 * Get the current activity post form model.
		 *
		 * @returns {Object} The current model instance.
		 */
		getCurrentModel : function () {
			return bp.Nouveau.Activity.postForm.postForm ? bp.Nouveau.Activity.postForm.postForm.model : bp.Nouveau.Activity.postForm.model;
		},

		/**
		 * Clear the feature image from the model.
		 */
		clearFeatureImage : function () {
			BBActivityPostFeatureImage.getCurrentModel().unset( 'bb_activity_post_feature_image' );
		},

		/**
		 * Destroy the feature image dropzone.
		 */
		destroyFeatureImageDropzone : function () {
			var $dropzoneElement = $( '#activity-post-feature-image-uploader' );
			if ( $dropzoneElement.length && $dropzoneElement[ 0 ].dropzone ) {
				try {
					$dropzoneElement[ 0 ].dropzone.destroy();
					$dropzoneElement[ 0 ].dropzone = null;
				} catch ( e ) {
					console.warn( BBActivityPostFeatureImage.bbActivityFeatureImage.strings.error_destroying_dropzone, e );
				}
			}

			// Remove the dropzone-initialized data attribute
			$( '#bb-activity-post-feature-image' ).removeData( 'dropzone-initialized' );

			// Clean up cropper.
			this.closeCropper();
		},

		/**
		 * Standardized error message extraction.
		 *
		 * @param {Object} xhr - XHR response object.
		 * @param {string} errorMessage - Error message from dropzone.
		 * @returns {string} Standardized error message.
		 */
		getErrorMessage : function ( xhr, errorMessage ) {
			var defaultMessage = BBActivityPostFeatureImage.bbActivityFeatureImage.strings.upload_failed;

			// Standard error response format: { data: { message: 'error text' } }.
			if ( xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ) {
				return xhr.responseJSON.data.message;
			}

			// Fallback to errorMessage if it's a string.
			if ( typeof errorMessage === 'string' && errorMessage.trim() ) {
				return errorMessage;
			}

			// Return a default message if no specific error found.
			return defaultMessage;
		},

		/**
		 * Handle activity post success event.
		 *
		 * @param {Object} data - Event data containing model and response.
		 */
		handleActivityPostSuccess : function ( data ) {
			if ( ! data || ! data.model ) {
				return;
			}

			var feature_image = data.model.get( 'bb_activity_post_feature_image' );

			if ( ! _.isUndefined( feature_image ) && feature_image ) {
				feature_image.saved = true;
				data.model.set( 'bb_activity_post_feature_image', feature_image );
			}
		},

		handleActivityPrivacyChanged : function ( data ) {
			if ( ! data || ! data.model || ! data.whats_new_form || ! data.draft_activity ) {
				return;
			}
			var group_item_id               = data.model.attributes.item_id;
			var $bpActivityPrivacyInput     = '.bp-activity-privacy__input';
			var $whatsNewFormHeaderElemName = '.whats-new-form-header';
			var $whatsNewFormHeaderElem     = $( $whatsNewFormHeaderElemName );
			var bpItemOptElemName           = '#bp-item-opt-' + group_item_id;
			if ( ! $whatsNewFormHeaderElem.length ) {
				$whatsNewFormHeaderElemName = '.bb-rl-activity-privacy__input';
				$bpActivityPrivacyInput     = '.bb-rl-activity-privacy__input';
				bpItemOptElemName           = '#bb-rl-item-opt-' + group_item_id;
			}

			var selected_privacy = data.element.find( $bpActivityPrivacyInput + ':checked' ).val();

			if ( 'group' === selected_privacy ) {
				// Check feature image is allowed in this group or not.
				var allow_feature_image = data.whats_new_form.find( bpItemOptElemName ).data( 'allow-feature-image' );
				if ( ! _.isUndefined( allow_feature_image ) && 'enabled' === allow_feature_image ) {
					BBActivityPostFeatureImage.getCurrentModel().set( 'feature_image_allowed', allow_feature_image );
					data.whats_new_form.find( '.bb-activity-post-feature-image-button' ).removeClass( 'bp-hide' );
					data.whats_new_form.find( $whatsNewFormHeaderElemName + ' #bb-activity-post-feature-image' ).show();
				} else {
					BBActivityPostFeatureImage.getCurrentModel().set( 'feature_image_allowed', 'disabled' );
					BBActivityPostFeatureImage.getCurrentModel().set( 'bb_activity_post_feature_image', null );
					data.whats_new_form.find( '.bb-activity-post-feature-image-button' ).addClass( 'bp-hide' );
					data.whats_new_form.find( $whatsNewFormHeaderElemName + ' #bb-activity-post-feature-image' ).hide();
					BBActivityPostFeatureImage.destroyFeatureImageDropzone();
				}
			} else {
				// Clear feature image data when change privacy.
				if (
					! _.isUndefined( BBActivityPostFeatureImage.bbActivityFeatureImage ) &&
					! _.isUndefined( BBActivityPostFeatureImage.bbActivityFeatureImage.can_upload_post_feature_image ) &&
					true === Boolean( BBActivityPostFeatureImage.bbActivityFeatureImage.can_upload_post_feature_image )
				) {
					data.whats_new_form.find( '.bb-activity-post-feature-image-button' ).removeClass( 'bp-hide' );
				} else {
					BBActivityPostFeatureImage.getCurrentModel().set( 'feature_image_allowed', 'disabled' );
					BBActivityPostFeatureImage.getCurrentModel().set( 'bb_activity_post_feature_image', null );
					data.whats_new_form.find( '.bb-activity-post-feature-image-button' ).addClass( 'bp-hide' );
					BBActivityPostFeatureImage.destroyFeatureImageDropzone();
				}
			}
		},

		/**
		 * Handle activity form data preparation (before submit).
		 *
		 * @param {Object} data - Event data containing model.
		 */
		handleActivityFormPrep : function ( data ) {
			if ( ! data || ! data.model ) {
				return;
			}

			var feature_image = data.model.get( 'bb_activity_post_feature_image' );

			// Set group_id for feature images when posting to a group.
			if ( 'group' === data.model.get( 'object' ) && ! _.isUndefined( feature_image ) && feature_image ) {
				feature_image.group_id = data.model.get( 'item_id' );
				data.model.set( 'bb_activity_post_feature_image', feature_image );
			}
		},

		/**
		 * Handle activity edit loaded event.
		 *
		 * @param {Object} data - Event data containing model and activity data.
		 */
		handleActivityEditLoaded : function ( data ) {
			if ( ! data || ! data.model || ! data.activity_data ) {
				return;
			}

			var activity_data     = data.activity_data;
			var $whatsNewFormElem = $( '#whats-new-form' );
			var bpItemOptElemName = '#bp-item-opt-' + activity_data.item_id;
			if ( ! $whatsNewFormElem.length ) {
				$whatsNewFormElem = $( '#bb-rl-whats-new-form' );
				bpItemOptElemName = '#bb-rl-item-opt-' + activity_data.item_id;
			}
			var editActivity = !_.isUndefined( data.activity_data ) && true === Boolean( data.activity_data );
			// Display button icon when privacy is not group for admin.
			if (
				! _.isUndefined( data ) &&
				'group' !== activity_data.privacy &&
				! _.isUndefined( BBActivityPostFeatureImage.bbActivityFeatureImage ) &&
				! _.isUndefined( BBActivityPostFeatureImage.bbActivityFeatureImage.can_upload_post_feature_image ) &&
				true === Boolean( BBActivityPostFeatureImage.bbActivityFeatureImage.can_upload_post_feature_image )
			) {
				$whatsNewFormElem.find( '.bb-activity-post-feature-image-button' ).removeClass( 'bp-hide' );
			}

			if ( 'group' === activity_data.privacy ) {
				var allow_feature_image = $whatsNewFormElem.find( bpItemOptElemName ).data( 'allow-feature-image' );
				if ( _.isUndefined( allow_feature_image ) ) {
					// When change group from news feed.
					if ( ! _.isUndefined( activity_data.feature_image_allowed ) && 'enabled' === activity_data.feature_image_allowed ) {
						allow_feature_image = activity_data.feature_image_allowed;
						BBActivityPostFeatureImage.getCurrentModel().set( 'feature_image_allowed', activity_data.allow_feature_image );
					} else if ( ! _.isUndefined( activity_data.feature_image_allowed ) && 'disabled' === activity_data.feature_image_allowed ) {
						allow_feature_image = 'disabled';
						BBActivityPostFeatureImage.getCurrentModel().set( 'feature_image_allowed', activity_data.feature_image_allowed );
					} else if (
						// On group page.
						! _.isUndefined( BBActivityPostFeatureImage.bbActivityFeatureImage ) &&
						! _.isUndefined( BBActivityPostFeatureImage.bbActivityFeatureImage.can_upload_post_feature_image ) &&
						true === Boolean( BBActivityPostFeatureImage.bbActivityFeatureImage.can_upload_post_feature_image )
					) {
						allow_feature_image = 'enabled';
					}
				}

				if ( ! _.isUndefined( allow_feature_image ) && 'enabled' === allow_feature_image ) {
					$whatsNewFormElem.find( '.bb-activity-post-feature-image-button' ).removeClass( 'bp-hide' );
				} else {
					$whatsNewFormElem.find( '.bb-activity-post-feature-image-button' ).addClass( 'bp-hide' );
				}
			}

			// Check if feature image data exists (could be object or array).
			var featureImageData = data.activity_data.bb_activity_post_feature_image;
			if ( ! _.isUndefined( featureImageData ) ) {
				// Convert an array to a single object if needed (backward compatibility).
				if ( _.isArray( featureImageData ) && featureImageData.length > 0 ) {
					featureImageData = featureImageData[ 0 ];
				}

				if ( featureImageData ) {
					// Update the activity_data to use the single object format.
					data.activity_data.bb_activity_post_feature_image = featureImageData;
					
					if ( editActivity ) {
						BBActivityPostFeatureImage.canEditFeatureImage = featureImageData.can_edit_feature_image;
						if ( true === Boolean( featureImageData.can_edit_feature_image ) ) {
							$whatsNewFormElem.find( '.bb-activity-post-feature-image-button' ).removeClass( 'bp-hide' ).addClass( 'no-click' );
						} else {
							$whatsNewFormElem.find( '.bb-activity-post-feature-image-button' ).addClass( 'bp-hide' );
						}
					}
					if ( featureImageData.id ) {
						BBActivityPostFeatureImage.injectFeatureImages( data.activity_data, data.model );
					}
				}
			}
		},

		/**
		 * Handle activity draft loaded event.
		 *
		 * @param {Object} data - Event data containing model and draft data.
		 */
		handleActivityDraftLoaded : function ( data ) {
			if ( ! data || data.$whatsNewForm.hasClass( 'bp-activity-edit' ) || ! data.model || ! data.activity_data ) {
				return;
			}

			// Check if feature image data exists (could be object or array).
			var featureImageData = data.activity_data.bb_activity_post_feature_image;
			if ( ! _.isUndefined( featureImageData ) ) {
				// Convert an array to a single object if needed (backward compatibility).
				if ( _.isArray( featureImageData ) && featureImageData.length > 0 ) {
					featureImageData = featureImageData[ 0 ];
				}

				if ( featureImageData ) {
					// Update the activity_data to use the single object format.
					data.activity_data.bb_activity_post_feature_image = featureImageData;
					BBActivityPostFeatureImage.injectFeatureImages( data.activity_data, data.model );
				}
			}
		},

		/**
		 * Inject feature images into the form.
		 *
		 * @param {Object} activity_data - Activity data containing feature images.
		 * @param {Object} model - Backbone model.
		 */
		injectFeatureImages : function ( activity_data, model ) {

			// Prevent duplicate calls.
			if ( BBActivityPostFeatureImage._injecting ) {
				return;
			}
			BBActivityPostFeatureImage._injecting = true;

			// Find the feature image section and button.
			var $featuredImageSection = $( '#bb-activity-post-feature-image' );
			var $featureImageButton   = $( '#bb-activity-post-feature-image-control' );
			var $whatsNewForm         = $( '#whats-new-form' );
			if ( ! $whatsNewForm.length ) {
				$whatsNewForm = $( '#bb-rl-whats-new-form' );
			}

			if ( $featuredImageSection.length ) {
				// Manually show the section (don't rely on button click).
				$featuredImageSection.show();

				// Add active class to button.
				if ( $featureImageButton.length ) {
					$featureImageButton.addClass( 'active no-click' );
				}

				// Position the section correctly.
				var $userStatusHuddle = $whatsNewForm.find( '#user-status-huddle' );
				if ( ! $userStatusHuddle.length ) {
					$userStatusHuddle = $whatsNewForm.find( '#bb-rl-user-status-huddle' );
				}
				if ( $userStatusHuddle.length ) {
					$featuredImageSection.insertAfter( $userStatusHuddle );
				}

				// Initialize dropzone if needed.
				if ( 'undefined' !== typeof window.Dropzone && ! $featuredImageSection.data( 'dropzone-initialized' ) ) {
					BBActivityPostFeatureImage.initializeDropzone( $featuredImageSection );
				}
			}

			// Wait for the section to be visible and dropzone ready, then inject files.
			setTimeout( function () {
				BBActivityPostFeatureImage.injectFeatureImageFiles( activity_data, model );
				// Reset an injection flag.
				BBActivityPostFeatureImage._injecting = false;
			}, 300 );
		},

		/**
		 * Inject feature image files into the dropzone.
		 *
		 * @param {Object} activity_data - Activity data containing feature images
		 * @param {Object} model - Backbone model
		 */
		injectFeatureImageFiles : function ( activity_data, model ) {
			// Check if a feature image section is visible.
			var $featuredImageSection = $( '#bb-activity-post-feature-image' );

			// Check if dropzone element exists.
			var dropzoneElement = $( '#activity-post-feature-image-uploader' )[ 0 ];

			// Check if files already injected to prevent duplicates
			if ( dropzoneElement && dropzoneElement.dropzone && dropzoneElement.dropzone.files.length > 0 ) {
				return;
			}

			// Create mock file for existing feature image.
			var feature_image_data = activity_data.bb_activity_post_feature_image;
			if ( ! feature_image_data ) {
				return;
			}
			BBActivityPostFeatureImage.fileID = feature_image_data.id;

			var feature_image_edit_data = {
				'id'                       : feature_image_data.id,
				'name'                     : feature_image_data.name || feature_image_data.alt || 'Feature Image',
				'thumb'                    : feature_image_data.thumb || feature_image_data.url,
				'medium'                   : feature_image_data.medium || feature_image_data.url,
				'url'                      : feature_image_data.url,
				'uuid'                     : feature_image_data.uuid || feature_image_data.id,
				'group_id'                 : feature_image_data.group_id || 0,
				'saved'                    : feature_image_data.saved || true,
				'width'                    : feature_image_data.width,
				'height'                   : feature_image_data.height,
				'alt'                      : feature_image_data.alt || '',
				'can_edit_feature_image'   : feature_image_data.can_edit_feature_image || false,
				'can_delete_feature_image' : feature_image_data.can_delete_feature_image || false,
				'cropped'                  : feature_image_data.cropped || false,
			};

			var mock_file = {
				name            : feature_image_data.name || feature_image_data.alt || 'Feature Image',
				accepted        : true,
				kind            : 'image',
				upload          : {
					filename : feature_image_data.name || feature_image_data.alt || 'Feature Image',
					uuid     : feature_image_data.uuid || feature_image_data.id
				},
				dataURL         : feature_image_data.url,
				id              : feature_image_data.id,
				media_edit_data : feature_image_edit_data,
				width           : feature_image_data.width,
				height          : feature_image_data.height,
				cropped         : feature_image_data.cropped || false
			};

			// Get the dropzone instance.
			if ( dropzoneElement && dropzoneElement.dropzone ) {
				var dropzone = dropzoneElement.dropzone;

				// Add a file to dropzone.
				dropzone.files.push( mock_file );

				dropzone.emit( 'addedfile', mock_file );

				// Create thumbnail from URL.
				BBActivityPostFeatureImage.createThumbnailFromUrl( mock_file, dropzone );

				// Note: complete event is emitted in the thumbnail callback.
				dropzone.emit( 'dz-success' );
				dropzone.emit( 'dz-complete' );

				// Set position data to center (no positioning functionality).
				setTimeout( function () {
					var $preview = $( dropzoneElement ).find( '.dz-preview' );
					if ( $preview.length ) {
						$preview.attr( 'data-position', 'center' );
					}
				}, 100 );

			} else {
				// Try to initialize dropzone if it doesn't exist.
				if ( $featuredImageSection.length && 'undefined' !== typeof window.Dropzone ) {
					BBActivityPostFeatureImage.initializeDropzone( $featuredImageSection );

					// Retry after initialization using closure to avoid linting issues.
					(
						function ( activityData, activityModel ) {
							setTimeout( function () {
								var retryDropzoneElement = $( '#activity-post-feature-image-uploader' )[ 0 ];
								if ( retryDropzoneElement && retryDropzoneElement.dropzone ) {
									BBActivityPostFeatureImage.injectFeatureImageFiles( activityData, activityModel );
								}
							}, 50 );
						}
					)( activity_data, model );
					return;
				}
			}

			model.set( 'bb_activity_post_feature_image', feature_image_edit_data );
		},

		/**
		 * Create a thumbnail from a URL.
		 *
		 * @param {Object} mock_file - The mock file object.
		 * @param {Object} dropzone - The dropzone instance.
		 */
		createThumbnailFromUrl : function ( mock_file, dropzone ) {
			if ( ! dropzone || ! mock_file ) {
				return;
			}

			// Ensure we have a valid URL for thumbnail creation
			var thumbnailUrl = mock_file.dataURL || mock_file.url;
			
			// If we don't have a valid URL, skip thumbnail creation
			if ( ! thumbnailUrl || typeof thumbnailUrl !== 'string' || thumbnailUrl === '[object Event]' ) {
				console.warn( BBActivityPostFeatureImage.bbActivityFeatureImage.strings.invalid_thumbnail_url, thumbnailUrl );
				return;
			}

			dropzone.createThumbnailFromUrl(
				mock_file,
				dropzone.options.thumbnailWidth,
				dropzone.options.thumbnailHeight,
				dropzone.options.thumbnailMethod,
				true,
				function ( thumbnail ) {
					dropzone.emit( 'thumbnail', mock_file, thumbnail );
					dropzone.emit( 'complete', mock_file );
				}
			);
		},

		handleActivityDraftCollectActivity : function ( data ) {
			var feature_image = data.model.get( 'bb_activity_post_feature_image' );

			if ( 'group' === data.model.get( 'object' ) && ! _.isUndefined( feature_image ) && feature_image ) {
				feature_image.group_id = data.model.get( 'item_id' );
				data.model.set( 'bb_activity_post_feature_image', feature_image );
			} else if ( ! _.isUndefined( feature_image ) && feature_image ) {
				delete feature_image.group_id;
				data.model.set( 'bb_activity_post_feature_image', feature_image );
			}
		},

		/**
		 * Check if the current activity form has feature images.
		 * This function can be called from core BuddyPress JS to prevent draft deletion.
		 *
		 * @returns {boolean} True if feature images exist, false otherwise
		 */
		hasFeatureImages : function () {
			var model = BBActivityPostFeatureImage.getCurrentModel();
			if ( model ) {
				var feature_image = model.get( 'bb_activity_post_feature_image' );
				return ! _.isUndefined( feature_image ) && feature_image !== null;
			}

			return false;
		},

		/**
		 * Initialize Backbone View for a feature image form.
		 * Similar to initTopicsManagerFrontend pattern.
		 */
		initFeatureImageView : function () {
			if ( typeof bp !== 'undefined' && bp.Views ) {
				bp.Views.activityPostFeatureImageForm = Backbone.View.extend( {
					tagName   : 'div',
					id        : 'bb-activity-post-feature-image',
					className : 'bb-activity-post-feature-image-container bb-activity-post-feature-image',
					template  : bp.template( 'bb-activity-post-feature-image-form' ),

					initialize : function () {
						this.model.on( 'change:activity_post_featured_image', this.render, this );
					},

					render : function () {
						this.$el.html( this.template( this.model.toJSON() ) );

						// Hide the container initially.
						this.$el.hide();

						// Position the container.
						this.positionContainer();

						return this;
					},

					positionContainer : function () {
						var $whatsNewFormField = '#whats-new-form';
						if ( ! $( $whatsNewFormField ).length ) {
							$whatsNewFormField = '#bb-rl-whats-new-form';
						}
						var $userStatusHuddleField = '#user-status-huddle';
						if ( ! $( $userStatusHuddleField ).length ) {
							$userStatusHuddleField = '#bb-rl-user-status-huddle';
						}
						var $userStatusHuddle = $( $whatsNewFormField + ' ' + $userStatusHuddleField );
						if ( ! $userStatusHuddle.length ) {
							$userStatusHuddle = $( $whatsNewFormField + ' ' + $userStatusHuddleField );
						}
						if ( $userStatusHuddle.length ) {
							// Only position if not already positioned correctly.
							if ( 0 === this.$el.prev( $userStatusHuddleField ).length ) {
								this.$el.insertAfter( $userStatusHuddle );
							}
						}
					}
				} );
			}
		},


		/**
		 * Check if initialization conditions are met.
		 *
		 * @returns {boolean} True if ready to initialize.
		 */
		isReadyToInitialize : function () {
			var $whatsNewFormField = '#whats-new-form';
			if ( ! $( $whatsNewFormField ).length ) {
				$whatsNewFormField = '#bb-rl-whats-new-form';
			}
			return $( $whatsNewFormField ).length && typeof bp !== 'undefined' && bp.Nouveau && bp.Nouveau.Activity;
		},

		/**
		 * Initialize the feature image functionality.
		 *
		 * @param {MutationObserver} observer - The mutation observer instance.
		 * @param {boolean} initialized - Flag to track initialization status.
		 */
		initializeFeatureImage : function ( observer, initialized ) {
			if ( ! initialized && this.isReadyToInitialize() ) {
				BBActivityPostFeatureImage.init();

				// Disconnect observer to prevent multiple initializations.
				if ( observer ) {
					observer.disconnect();
				}

				return true; // Mark as initialized.
			}
			return false;
		},

		/**
		 * Set up mutation observer for DOM changes.
		 *
		 * @param {Function} onFormFound - Callback when form is found.
		 */
		setupMutationObserver : function ( onFormFound ) {
			if ( typeof MutationObserver === 'undefined' ) {
				return null;
			}

			var observer = new MutationObserver( function ( mutations ) {
				mutations.forEach( function ( mutation ) {
					// Check for added nodes.
					if ( mutation.type === 'childList' && mutation.addedNodes.length > 0 ) {
						for ( var i = 0; i < mutation.addedNodes.length; i++ ) {
							var node = mutation.addedNodes[ i ];

							// Check if the added node is the activity form or contains it.
							if ( node.nodeType === Node.ELEMENT_NODE ) {
								var $whatsNewFormFieldName = 'whats-new-form';
								var $whatsNewFormField     = '#' + $whatsNewFormFieldName;
								if ( ! $( $whatsNewFormField ).length ) {
									$whatsNewFormFieldName = 'bb-rl-whats-new-form';
									$whatsNewFormField     = '#bb-rl-' + $whatsNewFormFieldName;
								}
								if ( node.id === $whatsNewFormFieldName || $( node ).find( $whatsNewFormField ).length ) {
									// Activity form found, trigger callback after a short delay.
									setTimeout( onFormFound, 100 );
									return;
								}
							}
						}
					}
				} );
			} );

			// Start observing.
			observer.observe( document.body, {
				childList : true,
				subtree   : true
			} );

			return observer;
		},
	};

	$(
		function () {
			BBActivityPostFeatureImage.init();
		}
	);

	// Make the manager available globally.
	window.BBActivityPostFeatureImage = BBActivityPostFeatureImage;

} )( jQuery ); 