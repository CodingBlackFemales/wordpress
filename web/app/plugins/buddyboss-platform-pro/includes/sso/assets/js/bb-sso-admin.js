/* jshint browser: true */
/* global bb, bbSSOAdminVars */
/* @version 1.0.0 */
window.bb = window.bb || {};

(
	function ( exports, $ ) {

		/**
		 * Admin side of the SSO.
		 *
		 * @type {Object}
		 */
		bb.SSO_Admin = {

			/**
			 * Start the SSO admin.
			 *
			 * @return {[type]} [description]
			 */
			start: function () {
				this.setupGlobals();
				this.ssoSortable();

				// Listen to events ("Add hooks!").
				this.addListeners();
			},

			/**
			 * Setup global variables.
			 *
			 * @return {[type]} [description]
			 */
			setupGlobals: function () {
			},

			/**
			 * Add event listeners.
			 */
			addListeners: function () {
				$( document ).on( 'click', '.bb-box-item-edit--sso', this.bbSSOConnectionStep );
				$( document ).on( 'click', '.bb-hello-sso .close-modal, .bb-hello-sso #sso_cancel', this.bbSSOConnectionStepClose );
				$( document ).on( 'keyup', '.bb-hello-sso input[type="text"], .bb-hello-sso input[type="radio"]', this.enableSaveChangesButton );
				$( document ).on( 'click', '#sso_submit', this.bbSSOSubmit );
				$( document ).on( 'change', '.sso-enable, .sso-disable', this.bbSSOEnable );
				$( document ).on( 'change', 'input#bb_enable_sso', this.ssoBlockState );
				$( document ).on( 'change', 'input#bp-enable-site-registration', this.ssoRegistrationOptions );
			},

			/**
			 * Sortable SSO providers.
			 */
			ssoSortable: function () {
				$( window ).on(
					'load',
					function () {
						var bbBoxPanel = $( '.bb-box-panel--sortable' );
						if ( bbBoxPanel.length > 0 ) {
							$( bbBoxPanel ).sortable(
								{
									cursor     : 'move',
									items      : '> div.bb-box-item',
									placeholder: 'bb-box-item bb-sortable-placeholder',
									stop       : function ( event, ui ) {
										var $providers = $( '.bb-sso-list > .bb-sso-item' ),
										providerList   = [];
										for ( var i = 0; i < $providers.length; i++ ) {
											providerList.push( $providers.eq( i ).data( 'provider' ) );
										}

										ui.item.closest( '.bb-box-panel' ).next( '.bb-sso-provider-notice' ).remove();

										var $notice = $( '<div class="bb-sso-provider-notice"></div>' );

										ui.item.closest( '.bb-box-panel' ).after( $notice );

										$.ajax(
											{
												type    : 'post',
												dataType: 'json',
												url     : bbSSOAdminVars.ajax_url,
												data    : {
													'nonce'   : bbSSOAdminVars.nonce,
													'action'  : 'bb-social-login',
													'view'    : 'orderProviders',
													'ordering': providerList
												},
												success : function ( response ) {
													$notice.html( '<div class="notice-container notice-container--success">' + response.data.message + '</div>' );
													setTimeout(
														function () {
															$notice.fadeOut(
																300,
																function () {
																		$notice.remove();
																}
															);
														},
														2000
													);
												},
												error   : function ( response ) {
													$notice.html( '<div class="notice-container notice-container--error">' + response.data.message + '</div>' );
													setTimeout(
														function () {
															$notice.fadeOut(
																300,
																function () {
																		$notice.remove();
																}
															);
														},
														3000
													);
												}
											}
										);
									},
								}
							);
						}
					}
				);
			},

			/**
			 * Open the SSO connection step modal.
			 *
			 * @return {boolean}       [description]
			 * @param event
			 */
			bbSSOConnectionStep: function ( event ) {
				event.preventDefault();

				if ( $( document ).find( '#bb-hello-backdrop' ).length ) {
				} else {
					var finder = $( document ).find( '.bb-hello-sso' );
					$( '<div id="bb-hello-backdrop" style="display: none;"></div>' ).insertBefore( finder );
				}
				var backdrop = document.getElementById( 'bb-hello-backdrop' ),
					modal    = document.getElementById( 'bb-hello-container' );

				if ( null === backdrop ) {
					return false;
				}
				document.body.classList.add( 'bp-disable-scroll' );

				// Show modal and overlay.
				backdrop.style.display = '';
				modal.style.display    = '';

				var $this           = $( this );
				var ssoItem         = $this.closest( '.bb-sso-item' );
				var dataId          = ssoItem.data( 'provider' );
				var ssoPopup        = $this.closest( '.bb-sso-list' );
				var allProvider     = bbSSOAdminVars.sso_fields;
				var currentProvider = allProvider[dataId];
				var hiddenAttr      = ssoPopup.find( '#sso_validate_popup_' + dataId + '_data' ).attr( 'data-hidden-attr' );
				hiddenAttr          = 'undefined' === typeof hiddenAttr ? {} : JSON.parse( hiddenAttr );
				var templateSource  = _.template( $( '#sso-fields-template' ).html() );
				var htmlContent     = templateSource(
					{
						provider  : currentProvider,
						providerId: dataId,
						hiddenAttr: hiddenAttr,
					}
				);

				$( ssoPopup ).find( '.bb-hello-sso' ).html( htmlContent );

				if ( 'not-configured' !== hiddenAttr.state ) {
					var buttonHtmlElem = bb.SSO_Admin.verifySettingButton( hiddenAttr );
					if ( $( ssoPopup ).find( '.bb-hello-sso #sso_submit' ).length > 0 ) {
						$( ssoPopup ).find( '.bb-hello-sso #sso_submit' ).before( buttonHtmlElem );
					} else {
						$( ssoPopup ).find( '.bb-hello-sso #sso_cancel' ).before( buttonHtmlElem );
					}
				}

				if ( 'not-configured' === hiddenAttr.state ) {
					var submitButton = bb.SSO_Admin.submitButtonRender( hiddenAttr );
					$( ssoPopup ).find( '.bb-hello-sso #sso_cancel' ).before( submitButton );
				}
			},

			/**
			 * Redirect to the SSO settings page after closing the modal.
			 * Reload the page to display the saved data.
			 *
			 * @return {[type]}       [description]
			 * @param event
			 */
			bbSSOConnectionStepClose: function ( event ) {
				event.preventDefault();

				// Reload modal because saved data will not display if the page does not load.
				window.location.reload();
			},

			/**
			 * Enable the save changes button based on the input fields.
			 *
			 * @return {[type]}       [description]
			 * @param event
			 */
			enableSaveChangesButton: function ( event ) {
				event.preventDefault();

				var allowSubmit    = [];
				var textInputs     = $( '.bb-hello-sso input[type="text"]:visible' );
				var radioInputs    = $( '.bb-hello-sso input[type="radio"]:visible' );
				var textareaInputs = $( '.bb-hello-sso textarea:visible' );

				if ( textInputs.length > 0 ) {
					textInputs.each(
						function () {
							var inputVal    = $( this ).val().trim();
							var oldInputVal = $( this ).attr( 'data-old-value' ).trim();
							if ( inputVal === '' ) {
									allowSubmit.push( false );
							}
							if ( '' !== inputVal && inputVal === oldInputVal ) {
								allowSubmit.push( true );
							}
							if ( '' !== inputVal && inputVal !== oldInputVal ) {
								allowSubmit.push( false );
							}
						}
					);
				}

				if ( radioInputs.length > 0 ) {
					var checkedRadioGroups = {};

					radioInputs.each(
						function () {
							var name = $( this ).attr( 'name' );

							// Ensure we check each group only once.
							if ( ! checkedRadioGroups[name] ) {
									checkedRadioGroups[name] = true;

								if ( $( 'input[name="' + name + '"]:checked' ).length === 0 ) {
									allowSubmit.push( false );
								}
							}
						}
					);
				}

				if ( textareaInputs.length > 0 ) {
					textareaInputs.each(
						function () {
							var inputVal    = $( this ).val().trim();
							var oldInputVal = $( this ).attr( 'data-old-value' ).trim();
							if ( inputVal === '' ) {
								allowSubmit.push( false );
							}
							if ( '' !== inputVal && inputVal === oldInputVal ) {
								allowSubmit.push( true );
							}
							if ( '' !== inputVal && inputVal !== oldInputVal ) {
								allowSubmit.push( false );
							}
						}
					);
				}

				var ssoSubmitElem    = $( '.bb-hello-sso #sso_submit' );
				var verifyButtonElem = $( '.bb-hello-sso #bb-sso-test-button' );
				var hiddenAttrElem   = $( '#sso_validate_popup_hidden_data' );
				var cancelElem       = $( '.bb-hello-sso #sso_cancel' );
				if ( - 1 !== jQuery.inArray( false, allowSubmit ) ) {
					if ( ! ssoSubmitElem.length ) {
						var hiddenAtrr   = hiddenAttrElem.data( 'hidden-attr' );
						var submitButton = bb.SSO_Admin.submitButtonRender( hiddenAtrr );
						cancelElem.before( submitButton );
					}
					if ( $( ssoSubmitElem ).is( ':hidden' ) ) {
						ssoSubmitElem.show();
						ssoSubmitElem.removeAttr( 'disabled' );
					}
					verifyButtonElem.remove();
				} else {
					if ( ! verifyButtonElem.length ) {
						var hiddenAtr      = hiddenAttrElem.data( 'hidden-attr' );
						var buttonHtmlElem = bb.SSO_Admin.verifySettingButton( hiddenAtr );
						if ( ssoSubmitElem.length > 0 ) {
							ssoSubmitElem.before( buttonHtmlElem );
						} else {
							cancelElem.before( buttonHtmlElem );
						}
					}
					ssoSubmitElem.remove();
				}
			},

			/**
			 * Save the SSO settings.
			 *
			 * @return {[type]}       [description]
			 * @param event
			 */
			bbSSOSubmit: function ( event ) {
				event.preventDefault();

				var $this               = $( this );
				var bbSSOTestButtonElem = $( '#bb-sso-test-button' );

				// Disable the submitted button to prevent multiple requests.
				$this.attr( 'disabled', 'disabled' );

				var helloErrorElm = $( '.bb-hello-content .bb-hello-error' );
				if ( helloErrorElm.length > 0 ) {
					helloErrorElm.remove();
				}

				var formDataArray = $( '.bb-hello-content' ).find( ':input' ).serializeArray();

				// Create a data object for the AJAX request.
				var ajaxData = {
					action: 'bb_sso_save_settings',
					nonce : bbSSOAdminVars.nonce
				};

				// Convert the formDataArray into key-value pairs and merge them into the ajaxData object.
				$.each(
					formDataArray,
					function ( i, field ) {
						ajaxData[field.name] = field.value;
					}
				);

				// Store the form data array in the global object.
				bb.SSO_Admin.formDataArray = ajaxData;

				$.ajax(
					{
						url    : bbSSOAdminVars.ajax_url,
						type   : 'POST',
						data   : ajaxData, // Send serialized data as individual fields.
						success: function ( response ) {
							if ( response.success ) {
								if ( response.data.redirect ) {
									// Update the form data array with the new data.
									bb.SSO_Admin.updateFormData( bb.SSO_Admin.formDataArray );
									// Trigger the auth button click event.
									var buttonHtmlElem = bb.SSO_Admin.verifySettingButton( $( '#sso_validate_popup_hidden_data' ).data( 'hidden-attr' ) );
									if ( ! $( '#bb-sso-test-button' ).length ) {
										$this.before( buttonHtmlElem );
									}

									var bbSSOTestButtonElem = $( '#bb-sso-test-button' );
									$this.hide();
									$this.attr( 'disabled', 'disabled' );
									bbSSOTestButtonElem.trigger( 'click' );
								} else {
									window.location.reload();
								}
							} else {
								if ( response.data ) {
									var responseData = response.data;
									if ( $.isArray( responseData ) && responseData.length > 1 ) {
										// Reverse the array to display the first error on top.
										var responseDataReverse = responseData.reverse();
										$.each(
											responseDataReverse,
											function ( error, message ) {
												$( '.bb-hello-content' ).prepend( '<div class="bb-hello-error"><i class="bb-icon-rf bb-icon-exclamation"></i>' + message + '</div>' );
											}
										);
									} else {
										$( '.bb-hello-content' ).prepend( '<div class="bb-hello-error"><i class="bb-icon-rf bb-icon-exclamation"></i>' + response.data + '</div>' );
									}
								}
								$this.removeAttr( 'disabled' );
							}
						},
						error  : function () {
							bbSSOTestButtonElem.hide();
							$this.show();
							$this.removeAttr( 'disabled' );
						}
					}
				);
			},

			/**
			 * Render the verified button.
			 *
			 * @param hiddenAttr
			 *
			 * @returns {string}
			 */
			verifySettingButton: function ( hiddenAttr ) {
				var buttonHtml = _.template( $( '#sso-verify-settings-template' ).html() );
				return buttonHtml(
					{
						hiddenAttr: hiddenAttr
					}
				);
			},

			/**
			 * Render the submitted button.
			 *
			 * @param hiddenAttr
			 *
			 * @returns {string}
			 */
			submitButtonRender: function ( hiddenAttr ) {
				var buttonHtml = _.template( $( '#sso-submit-template' ).html() );
				return buttonHtml(
					{
						hiddenAttr: hiddenAttr
					}
				);
			},

			/**
			 * Enable the SSO provider.
			 *
			 * @return {[type]}       [description]
			 * @param event
			 */
			bbSSOEnable: function ( event ) {
				event.preventDefault();

				var $this = $( this );
				$this.attr( 'disabled', 'disabled' );
				if ( 'not-configured' === $this.data( 'state' ) ) {
					return;
				}
				var provider = $this.data( 'provider' );
				var state    = $this.data( 'state' );
				var checked  = $this.is( ':checked' );

				$.ajax(
					{
						url    : bbSSOAdminVars.ajax_url,
						type   : 'POST',
						data   : {
							'action'  : 'bb_sso_enable_provider',
							'nonce'   : bbSSOAdminVars.nonce,
							'provider': provider,
							'state'   : state
						},
						success: function ( response ) {
							if ( response.success ) {
								if ( checked ) {
									$this.prop( 'checked', 'checked' );
									$this.closest( '.bb-box-item' ).attr( 'data-state', 'enabled' );
								} else {
									$this.removeAttr( 'checked' );
									$this.closest( '.bb-box-item' ).attr( 'data-state', 'disabled' );
								}
								bb.SSO_Admin.listOfEnableProvider();
								$this.removeAttr( 'disabled' );
							} else {

							}
						},
						error  : function () {

						}
					}
				);
			},

			/**
			 * Disable the checkboxes when SSO is disabled.
			 *
			 * @return {[type]}       [description]
			 */
			ssoBlockRestrict: function () {
				$( '.bb-box-item-actions-disable input[type="checkbox"]' ).prop( 'disabled', true );

				if ( $( '#bb_enable_sso' ).is( ':checked' ) ) {
					$( '.bb-box-item-actions-label:not(.bb-box-item-actions-disable) input[type="checkbox"]' ).prop( 'disabled', false );
					$( '.sso-additional-fields input[type="checkbox"]' ).prop( 'disabled', false );
				} else {
					$( '.bb-box-item-actions-label input[type="checkbox"]' ).prop( 'disabled', true );
					$( '.sso-additional-fields input[type="checkbox"]' ).prop( 'disabled', true );
				}

				bb.SSO_Admin.listOfEnableProvider();
			},

			/**
			 * SSO block state.
			 *
			 * @return {[type]}       [description]
			 * @param event
			 */
			ssoBlockState: function ( event ) {
				event.preventDefault();

				bb.SSO_Admin.ssoBlockRestrict();
			},

			/**
			 * Update the form data array with the new data.
			 *
			 * @return {[type]}       [description]
			 * @param data
			 */
			updateFormData: function ( data ) {
				$.each(
					data,
					function ( key, value ) {
						var inputField = $( '.bb-hello-content [name="' + key + '"]' );
						if ( inputField.length ) {
							inputField.attr( 'value', value );
							inputField.attr( 'data-old-value', value );
						}
					}
				);
			},

			/**
			 * List of enabled providers.
			 *
			 * @return {[type]} [description]
			 */
			listOfEnableProvider: function () {
				var enabledProviers = [];
				$( '.bb-sso-list .bb-box-item[data-state="enabled"]' ).each(
					function () {
						enabledProviers.push( $( this ).data( 'provider' ) );
					}
				);

				if ( 1 === enabledProviers.length && 'twitter' === enabledProviers[0] ) {
					$( '.sso-additional-fields input' ).each(
						function () {
							$( this ).attr( 'disabled', 'disabled' );
						}
					);
				} else {
					$( '.sso-additional-fields input' ).each(
						function () {
							$( this ).removeAttr( 'disabled' );
						}
					);
				}
			},

			/**
			 * SSO registration options.
			 *
			 * @return {[type]} [description]
			 * @param event
			 */
			ssoRegistrationOptions: function ( event ) {
				event.preventDefault();

				var $this                  = $( this );
				var isChecked              = $this.is( ':checked' );
				var ssoRegistrationOptions = $( 'input[name="bb-sso-reg-options"]' );

				ssoRegistrationOptions.prop( 'checked', false ); // Reset all options first

				ssoRegistrationOptions.each( function () {
					var $option = $( this );
					if ( $option.val() === ( isChecked ? '1' : '0' ) ) {
						$option.prop( 'checked', true );
					}
				} );

				// Disable the registration enable checkbox if the SSO is disabled.
				if ( ! isChecked ) {
					$( '#bb-sso-registration-enable' ).attr( 'disabled', 'disabled' );
				} else {
					$( '#bb-sso-registration-enable' ).removeAttr( 'disabled' );
				}
			},
		};

		$( document ).on(
			'ready',
			function () {
				bb.SSO_Admin.start();
			}
		);

	}
)( bb, jQuery );
