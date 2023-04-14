/* jshint browser: true */
/* global bp, bbAccessControlAdminVars */
/* @version 1.1.0 */
window.bp = window.bp || {};

( function( exports, $ ) {

	/**
	 * [Access Control description]
	 *
	 * @type {Object}
	 */
	bp.Access_Control_Admin = {
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
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {

			$( document ).on( 'change', '.access-control-select-box', this.showAccessControlSelect.bind( this ) );
			$( document ).on( 'change', '.access-control-plugin-select-box', this.showPluginAccessControlSelect.bind( this ) );
			$( document ).on( 'change', '.access-control-gamipress-select-box', this.showGamiPressAccessControlSelect.bind( this ) );
			$( document ).on( 'change', '.access-control-checkbox-list .parent input[type="checkbox"]', this.enableDisableOptions.bind( this ) );
			$( document ).on( 'change', '.chb', this.enableDisableOptionsAllOrSpecific.bind( this ) );
			$( document ).on( 'change', '.access-control-threaded-input', this.showHideChildDiv.bind( this ) );
			$( window ).on( 'load', this.changeNotices.bind( this ) );
			$( document ).on( 'change', 'input#bp_restrict_group_creation', this.group_creation_restrict.bind( this ) );
			$( document ).on( 'change', 'input#bp_media_profile_media_support, input#bp_media_messages_media_support, input#bp_media_forums_media_support', this.upload_photo_restrict.bind( this ) );
			$( document ).on( 'change', 'input#bp_video_profile_video_support, input#bp_video_messages_video_support, input#bp_video_forums_video_support', this.upload_video_restrict.bind( this ) );
			$( document ).on( 'change', 'input#bp_media_profile_document_support, input#bp_media_messages_document_support, input#bp_media_forums_document_support', this.upload_document_restrict.bind( this ) );
		},

		showHideChildDiv : function ( event ) {
			var $this  = event.currentTarget;
			var dataId = $( $this ).data( 'id' );

			var allSpecificInputs = $( $this ).closest( '.parent.' + dataId ).next( '.access-control-checkbox-list.child-' + dataId ).find( '.multiple_options' );
			var allInputs         = $( $this ).closest( '.parent.' + dataId ).next( '.access-control-checkbox-list' ).find( 'input[type="checkbox"]' );
			var nextDiv           = $( $this ).closest( '.parent.' + dataId ).next( '.access-control-checkbox-list.child-' + dataId );
			var nextSubDiv        = $( $this ).closest( '.parent.' + dataId ).next( '.access-control-checkbox-list.child-' + dataId ).find( '.sub-child-' + dataId );
			if ( $( $this ).prop( 'checked' ) ) {
				allSpecificInputs.children( 'input[data-value="all"]' ).trigger( 'click' );
				allInputs.removeProp( 'disabled' );
				nextDiv.removeClass( 'access-control-hide-div' );
				nextDiv.children( 'input[data-value="all"]' ).trigger( 'click' );
			} else {
				allSpecificInputs.children( 'input[data-value="all"]' ).trigger( 'click' );
				allInputs.prop( 'disabled','disabled' );
				allInputs.removeProp( 'checked' );
				nextSubDiv.addClass( 'access-control-hide-div' );
				nextDiv.addClass( 'access-control-hide-div' );
			}
			bp.Access_Control_Admin.changeNotices();

		},
		enableDisableOptionsAllOrSpecific : function ( event ) {
			var $this     = event.currentTarget;
			var dataValue = $( $this ).data( 'value' );
			var dataId    = $( $this ).data( 'id' );
			var nextDiv   = $( $this ).closest( '.access-control-checkbox-list.child-' + dataId ).find( '.sub-child-' + dataId );
			var allInputs = $( $this ).closest( '.access-control-checkbox-list.child-' + dataId ).find( '.sub-child-' + dataId + ' input[type="checkbox"]' );
			if ( 'specific' === dataValue ) {
				if ( nextDiv.length ) {
					allInputs.attr( 'disabled', false );
					nextDiv.parent().removeClass( 'access-control-hide-div' );
					nextDiv.removeClass( 'access-control-hide-div' );
				}
			} else {
				allInputs.prop( 'checked', false );
				if ( nextDiv.length ) {
					nextDiv.parent().addClass( 'access-control-hide-div' );
					nextDiv.addClass( 'access-control-hide-div' );
				}
			}
			$( $this ).siblings( '.chb' ).prop( 'checked', false );
			$( $this ).prop( 'checked', true );
		},
		showPluginAccessControlSelect : function ( event ) {
			var $this             = event.currentTarget;
			var value             = $( $this ).val();
			var name              = $( $this ).data( 'id' );
			var threaded          = $( $this ).hasClass( 'display-threaded' );
			var label             = $( $this ).data( 'label' );
			var subLabel          = $( $this ).data( 'sub-label' );
			var componentSettings = $( $this ).data( 'component-settings' );

			if ( '' === value ) {
				$( $this ).siblings( '.access-control-checkbox-list' ).html( '' );
			} else {
				if ( $( $this ).hasClass( 'loading' ) ) {
					return; // Do not go further if AJAX is already running
				} else {
					$( $this ).addClass( 'loading' );
				}

				$( '<div class="loader-repair-tools" style="vertical-align: middle"></div>' ).insertAfter( $this );
				$.ajax(
					{
						type: 'POST',
						url: bbAccessControlAdminVars.ajax_url,
						data: { action: 'plugin_get_access_control_level_options', value: value, key: name, threaded: threaded, label: label, sub_label: subLabel, component_settings: componentSettings },
						success: function ( response ) {
							$( $this ).siblings( '.loader-repair-tools' ).remove();
							$( $this ).removeClass( 'loading' );
							if ( typeof response.data !== 'undefined' && response.data.message ) {
								$( $this ).siblings( '.access-control-checkbox-list' ).html( '' );
								$( $this ).siblings( '.access-control-checkbox-list' ).html( response.data.message );
								$( $this ).siblings( '.access-control-checkbox-list' ).find( '.access-control-checkbox-list input[type="checkbox"]' ).prop( 'disabled','disabled' );
								bp.Access_Control_Admin.changeNotices();
							} else {
								$( $this ).siblings( '.access-control-checkbox-list' ).html( '' );
							}
						},
						error: function () {
							$( $this ).removeClass( 'loading' );
							$( $this ).siblings( '.loader-repair-tools' ).remove();
						}
					}
				);
			}
		},
		showGamiPressAccessControlSelect : function ( event ) {
			var $this             = event.currentTarget;
			var value             = $( $this ).val();
			var name              = $( $this ).data( 'id' );
			var threaded          = $( $this ).hasClass( 'display-threaded' );
			var label             = $( $this ).data( 'label' );
			var subLabel          = $( $this ).data( 'sub-label' );
			var componentSettings = $( $this ).data( 'component-settings' );

			if ( '' === value ) {
				$( $this ).siblings( '.access-control-checkbox-list' ).html( '' );
			} else {
				if ( $( $this ).hasClass( 'loading' ) ) {
					return; // Do not go further if AJAX is already running
				} else {
					$( $this ).addClass( 'loading' );
				}

				$( '<div class="loader-repair-tools" style="vertical-align: middle"></div>' ).insertAfter( $this );
				$.ajax(
					{
						type: 'POST',
						url: bbAccessControlAdminVars.ajax_url,
						data: { action: 'gamipress_get_access_control_level_options', value: value, key: name, threaded: threaded, label:label, sub_label : subLabel, component_settings: componentSettings },
						success: function ( response ) {
							$( $this ).siblings( '.loader-repair-tools' ).remove();
							$( $this ).removeClass( 'loading' );
							if ( typeof response.data !== 'undefined' && response.data.message ) {
								$( $this ).siblings( '.access-control-checkbox-list' ).html( '' );
								$( $this ).siblings( '.access-control-checkbox-list' ).html( response.data.message );
								$( $this ).siblings( '.access-control-checkbox-list' ).find( '.access-control-checkbox-list input[type="checkbox"]' ).prop( 'disabled','disabled' );
								bp.Access_Control_Admin.changeNotices();
							} else {
								$( $this ).siblings( '.access-control-checkbox-list' ).html( '' );
							}
						},
						error: function () {
							$( $this ).removeClass( 'loading' );
							$( $this ).siblings( '.loader-repair-tools' ).remove();
						}
					}
				);
			}
		},
		showAccessControlSelect : function ( event ) {
			var $this             = event.currentTarget;
			var value             = $( $this ).val();
			var threaded          = $( $this ).hasClass( 'display-threaded' );
			var label             = $( $this ).data( 'label' );
			var subLabel          = $( $this ).data( 'sub-label' );
			var componentSettings = $( $this ).data( 'component-settings' );

			if ( '' === value ) {
				$( $this ).siblings( '.access-control-plugin-select-box' ).addClass( 'hidden' );
				$( $this ).siblings( '.access-control-gamipress-select-box' ).addClass( 'hidden' );
				$( $this ).siblings( '.access-control-checkbox-list' ).html( '' );
			} else {
				if ( $( $this ).hasClass( 'loading' ) ) {
					return; // Do not go further if AJAX is already running
				} else {
					$( $this ).addClass( 'loading' );
				}

				// Reset both select box.
				$( $this ).siblings( '.access-control-gamipress-select-box' ).prop( 'selectedIndex', 0 );
				$( $this ).siblings( '.access-control-plugin-select-box' ).prop( 'selectedIndex', 0 );

				var name = $( $this ).attr( 'id' );
				if ( value === 'membership' ) {
					$( $this ).siblings( '.access-control-gamipress-select-box' ).addClass( 'hidden' );
					$( $this ).removeClass( 'loading' ).siblings( '.access-control-plugin-select-box' ).removeClass( 'hidden' );
					$( $this ).siblings( '.select-before-label' ).removeClass( 'hidden' );
					$( $this ).siblings( '.select-after-label' ).removeClass( 'hidden' );
					$( $this ).siblings( '.access-control-checkbox-list' ).html( '' );
				} else if ( value === 'gamipress' ) {
					$( $this ).siblings( '.access-control-plugin-select-box' ).addClass( 'hidden' );
					$( $this ).siblings( '.select-before-label' ).addClass( 'hidden' );
					$( $this ).siblings( '.select-after-label' ).addClass( 'hidden' );
					$( $this ).removeClass( 'loading' ).siblings( '.access-control-gamipress-select-box' ).removeClass( 'hidden' );
					$( $this ).siblings( '.access-control-checkbox-list' ).html( '' );
				} else {
					$( $this ).siblings( '.access-control-plugin-select-box' ).addClass( 'hidden' );
					$( $this ).siblings( '.select-before-label' ).addClass( 'hidden' );
					$( $this ).siblings( '.select-after-label' ).addClass( 'hidden' );
					$( $this ).siblings( '.access-control-gamipress-select-box' ).addClass( 'hidden' );
					$( '<div class="loader-repair-tools" style="vertical-align: middle"></div>' ).insertAfter( $this );
					$.ajax(
						{
							type: 'POST',
							url: bbAccessControlAdminVars.ajax_url,
							data: { action: 'get_access_control_level_options', value: value, key: name, threaded: threaded, label:label, sub_label: subLabel, component_settings: componentSettings },
							success: function ( response ) {
								$( $this ).siblings( '.loader-repair-tools' ).remove();
								if ( typeof response.data !== 'undefined' && response.data.message ) {
									$( $this ).siblings( '.access-control-checkbox-list' ).html( '' );
									$( $this ).siblings( '.access-control-checkbox-list' ).html( response.data.message );
									$( $this ).siblings( '.access-control-checkbox-list' ).find( '.access-control-checkbox-list input[type="checkbox"]' ).prop( 'disabled','disabled' );
									bp.Access_Control_Admin.changeNotices();
								} else {
									$( $this ).siblings( '.access-control-checkbox-list' ).html( '' );
								}
								$( $this ).removeClass( 'loading' );
							},
							error: function () {
								$( $this ).removeClass( 'loading' );
								$( $this ).siblings( '.loader-repair-tools' ).remove();
							}
						}
					);
				}
			}
		},
		enableDisableOptions : function ( event ) {
			var $this     = event.currentTarget;
			var allInputs = $( $this ).closest( '.parent' ).next( '.access-control-checkbox-list' ).find( 'input[type="checkbox"]' );
			if ( $( $this ).prop( 'checked' ) ) {
				allInputs.removeProp( 'disabled' );
			} else {
				allInputs.prop( 'disabled','disabled' );
				allInputs.removeProp( 'checked' );
			}

		},

		changeNotices : function () {
			if( $('.access-control-checkbox-list .access-control-checkbox-list .description').length ) {
				var value = $( '.access-control-select-box' ).val();
				var select_value = '';
				if ( value === 'membership' ) {
					var access_control_text = $( '.access-control-select-box option:selected' ).text();
					select_value = $('.access-control-plugin-select-box option[value='+ $('.access-control-plugin-select-box' ).val() +']').text() + ' ' + access_control_text;
				} else if ( value === 'gamipress' ) {
					select_value = $('.access-control-gamipress-select-box option[value='+ $('.access-control-gamipress-select-box' ).val() +']').text();
				} else {
					select_value = $('.access-control-select-box option[value='+ $('.access-control-select-box' ).val() +']').text();
				}

				$('.access-control-checkbox-list .access-control-checkbox-list .description').each( function() {
					var recentSelectValue = $( this ).text();
					var editedSelectValue = bp.Access_Control_Admin.replaceAll( recentSelectValue, '{{select_value}}', select_value );
					$( this ).text( editedSelectValue );
					var recentOptionValue = $( this ).parent().prev('.parent').children( 'label' ).text();
					var editedOptionValue = $( this ).text().replace( '{{option_value}}', '<strong>'+recentOptionValue+'</strong>' );
					$( this ).html( editedOptionValue );
				});

			}
		},

		replaceAll : function ( str, find, replace ) {
			var escapedFind=find.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, '\\$1');
			return str.replace(new RegExp(escapedFind, 'g'), replace);
		},

		group_creation_restrict : function ( event ) {
			if( $( event.currentTarget ).prop( 'checked' ) ) {
				$( '#group_access_control_block tr:first-child td .bp-messages-feedback' ).hide();
				$( '#group_access_control_block tr:first-child td select:disabled' ).prop( 'disabled', false );
				$( '#group_access_control_block tr:first-child td .access-control-checkbox-list input' ).prop( 'disabled', false );
			} else {
				$( '#group_access_control_block tr:first-child td .bp-messages-feedback' ).show();
				$( '#group_access_control_block tr:first-child td select:visible' ).prop( 'disabled', true );
				$( '#group_access_control_block tr:first-child td .access-control-checkbox-list input' ).prop( 'disabled', true );
			}
		},

		upload_photo_restrict : function () {
			if( $( 'input#bp_media_profile_media_support:checked, input#bp_media_messages_media_support:checked, input#bp_media_forums_media_support:checked' ).length >= 1 ) {
				$( '#media_access_control_block tr:first-child td .bp-messages-feedback' ).hide();
				$( '#media_access_control_block tr:first-child td select:visible' ).prop( 'disabled', false );
				$( '#media_access_control_block tr:first-child td .access-control-checkbox-list input' ).prop( 'disabled', false );
			} else if( $( 'input#bp_media_profile_media_support:checked, input#bp_media_messages_media_support:checked, input#bp_media_forums_media_support:checked' ).length === 0 ) {
				$( '#media_access_control_block tr:first-child td .bp-messages-feedback' ).show();
				$( '#media_access_control_block tr:first-child td select:visible' ).prop( 'disabled', true );
				$( '#media_access_control_block tr:first-child td .access-control-checkbox-list input' ).prop( 'disabled', true );
			}
		},

		upload_document_restrict : function () {
			if( $( 'input#bp_media_profile_document_support:checked, input#bp_media_messages_document_support:checked, input#bp_media_forums_document_support:checked' ).length >= 1 ) {
				$( '#media_access_control_block tr:nth-child(2) td .bp-messages-feedback' ).hide();
				$( '#media_access_control_block tr:nth-child(2) td select:visible' ).prop( 'disabled', false );
				$( '#media_access_control_block tr:nth-child(2) td .access-control-checkbox-list input' ).prop( 'disabled', false );
			} else if( $( 'input#bp_media_profile_document_support:checked, input#bp_media_messages_document_support:checked, input#bp_media_forums_document_support:checked' ).length === 0 ) {
				$( '#media_access_control_block tr:nth-child(2) td .bp-messages-feedback' ).show();
				$( '#media_access_control_block tr:nth-child(2) td select:visible' ).prop( 'disabled', true );
				$( '#media_access_control_block tr:nth-child(2) td .access-control-checkbox-list input' ).prop( 'disabled', true );
			}
		},

		upload_video_restrict : function () {
			if( $( 'input#bp_video_profile_video_support:checked, input#bp_video_messages_video_support:checked, input#bp_video_forums_video_support:checked' ).length >= 1 ) {
				$( '#media_access_control_block tr:nth-child(3) td .bp-messages-feedback' ).hide();
				$( '#media_access_control_block tr:nth-child(3) td select:visible' ).prop( 'disabled', false );
				$( '#media_access_control_block tr:nth-child(3) td .access-control-checkbox-list input' ).prop( 'disabled', false );
			} else if( $( 'input#bp_video_profile_video_support:checked, input#bp_video_messages_video_support:checked, input#bp_video_forums_video_support:checked' ).length === 0 ) {
				$( '#media_access_control_block tr:nth-child(3) td .bp-messages-feedback' ).show();
				$( '#media_access_control_block tr:nth-child(3) td select:visible' ).prop( 'disabled', true );
				$( '#media_access_control_block tr:nth-child(3) td .access-control-checkbox-list input' ).prop( 'disabled', true );
			}
		}

	};

	// Launch Access Control.
	bp.Access_Control_Admin.start();

} )( bp, jQuery );
