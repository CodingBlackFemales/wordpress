/* jshint browser: true */
/* global bp, bpZoomMeetingCommonVars */
/* @version 1.0.0 */
window.bp = window.bp || {};

( function( exports, $ ) {

	/**
	 * [Zoom description]
     *
	 * @type {Object}
	 */
	bp.Zoom_Common = {
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
			$(document).on( 'click', '#bp-zoom-check-connection, #bp-zoom-group-check-connection', this.checkConnection.bind(this));
			$(document).on( 'change', '#bp-zoom-api-key, #bp-zoom-api-secret, #bp-zoom-api-email, #bp-group-zoom-api-key, #bp-group-zoom-api-secret, #bp-group-zoom-api-email', this.checkApiFields.bind(this));
			$(document).on( 'change', '#bp-edit-group-zoom', this.toggleGroupSettings.bind(this));
			$(document).on( 'click', '.copy-webhook-link', this.copyWebhookLink.bind(this));
			$(document).on( 'click', '.bp-step-nav li > a, .bp-step-actions > span', this.bpStepsNavigate.bind(this));
			$(document).on( 'keyup', '.zoom-group-instructions-cloned-input', this.copyInputData.bind(this));
			$(document).on( 'keyup', '.zoom-group-instructions-main-input', this.copyMainInputData.bind(this));
			$(document).on( 'click', '.bp-zoom-group-show-instructions .save-settings', this.zoomInstructionSave.bind(this));
			$(window).on( 'keyup', this.zoomInstructionNavigate.bind(this));
			if ( $(document).find('.bb-group-zoom-settings-container').length ) {
				$(document).on( 'click', 'form#group-settings-form button.bb-save-settings', this.zoomSettingsSave.bind(this));
			}

			if (typeof jQuery.fn.magnificPopup !== 'undefined') {
				jQuery('.show-zoom-instructions').magnificPopup({
					type: 'inline',
					midClick: true
				});
			}
		},

		copyWebhookLink: function (e) {
			var target = $(e.currentTarget),
				button_text = target.attr('data-balloon');
			if ( target.closest('.bp-zoom-group-show-instructions').length ) {
				button_text = target.attr('data-text');
			}

			if ( target.hasClass('copied') ) {
				return false;
			}

			e.preventDefault();

			var textArea = document.createElement('textarea');
			textArea.value = target.data('webhook-link');
			if ( target.closest('.bp-zoom-group-show-instructions').length ) {
				target.closest('.bp-zoom-group-show-instructions')[0].appendChild(textArea);
			} else {
				document.body.appendChild(textArea);
			}
			textArea.select();
			try {
				var successful = document.execCommand('copy');
				if (successful) {
					target.addClass('copied');
					if ( target.closest('.bp-zoom-group-show-instructions').length ) {
						target.html('<span class="bb-icon-l bb-icon-check"></span> '+target.data('copied'));
					} else {
						target.attr('data-balloon', target.data('copied'));
					}

                                        setTimeout(function () {
                                            target.removeClass('copied');
                                        }, 2000);

					setTimeout(function () {
						if ( target.closest('.bp-zoom-group-show-instructions').length ) {
							target.html('<span class="bb-icon-l bb-icon-duplicate"></span> ' +button_text);
						} else {
							target.attr('data-balloon', button_text);
						}
					}, 3000);
				}
			} catch (err) {
				console.log('Oops, unable to copy');
			}
			if ( target.closest('.bp-zoom-group-show-instructions').length ) {
				target.closest('.bp-zoom-group-show-instructions')[0].removeChild(textArea);
			} else {
				document.body.removeChild(textArea);
			}
		},

        checkApiFields: function(e) {
            var api_key = $('#bp-zoom-api-key').length ? $('#bp-zoom-api-key').val() : ( $('#bp-group-zoom-api-key').length ? $('#bp-group-zoom-api-key').val() : '' ),
                api_secret = $('#bp-zoom-api-secret').length ? $('#bp-zoom-api-secret').val() : ( $('#bp-group-zoom-api-secret').length ? $('#bp-group-zoom-api-secret').val() : '' ),
                api_email = $('#bp-zoom-api-email').length ? $('#bp-zoom-api-email').val() : ( $('#bp-group-zoom-api-email').length ? $('#bp-group-zoom-api-email').val() : '' );

            e.preventDefault();

            if ( api_key === '' || api_secret === '' || api_email === '' ) {
                jQuery( '#bp-zoom-check-connection, #bp-zoom-group-check-connection' ).hide();
            } else {
                jQuery( '#bp-zoom-check-connection, #bp-zoom-group-check-connection' ).show();
            }
        },

        checkConnection: function(e) {
			var api_key = $('#bp-zoom-api-key').length ? $('#bp-zoom-api-key').val() : ( $('#bp-group-zoom-api-key').length ? $('#bp-group-zoom-api-key').val() : '' ),
				api_secret = $('#bp-zoom-api-secret').length ? $('#bp-zoom-api-secret').val() : ( $('#bp-group-zoom-api-secret').length ? $('#bp-group-zoom-api-secret').val() : '' ),
				api_email = $('#bp-zoom-api-email').length ? $('#bp-zoom-api-email').val() : ( $('#bp-group-zoom-api-email').length ? $('#bp-group-zoom-api-email').val() : '' );
			e.preventDefault();

			if ( api_key === '' || api_secret === '' || api_email === '' ) {
				return false;
			}

			$( document ).find( '#bp-zoom-group-check-connection' ).addClass( 'loading' );

			$.ajax({
				type: 'GET',
				url: bpZoomMeetingCommonVars.ajax_url,
				data: { action: 'zoom_api_check_connection', key: api_key, secret: api_secret, email: api_email },
				success: function ( response ) {
					if ( typeof response.data !== 'undefined' && response.data.message ) {
						alert(response.data.message);
						$( document ).find( '#bp-zoom-group-check-connection' ).removeClass( 'loading' );
					}
				}
			});
		},

		toggleGroupSettings: function(e) {
			var target = $(e.target), group_zoom_settings = target.closest('form').find('#bp-group-zoom-settings');
			e.preventDefault();

			if (target.is(':checked')) {
				$('#bp-group-zoom-settings, #bp-zoom-group-show-instructions').removeClass('bp-hide');
				$('#bp-group-zoom-settings-additional').removeClass('bp-hide');
				$('#bp-zoom-check-connection, #bp-zoom-group-check-connection').show();
				group_zoom_settings.find('#bp-group-zoom-api-key, #bp-group-zoom-api-secret, #bp-group-zoom-api-email').attr('required',true);
			} else {
				$('#bp-group-zoom-settings, #bp-zoom-group-show-instructions').addClass('bp-hide');
				$('#bp-group-zoom-settings-additional').addClass('bp-hide');
				$('#bp-zoom-check-connection, #bp-zoom-group-check-connection').hide();
				group_zoom_settings.find('#bp-group-zoom-api-key, #bp-group-zoom-api-secret, #bp-group-zoom-api-email').removeAttr('required');
			}
		},

		bpStepsNavigate: function(e) {

			e.preventDefault();

			var target = $(e.currentTarget);

			if( target.closest('.bp-step-nav').length ){
				target.closest('li').addClass('selected').siblings().removeClass('selected');
				target.closest('.bp-step-nav-main').find( '.bp-step-block' + target.attr('href') ).addClass('selected').siblings().removeClass('selected');
			} else if( target.closest('.bp-step-actions').length ){
				var activeBlock = target.closest('.bp-step-nav-main').find('.bp-step-block.selected');
				var activeTab = target.closest('.bp-step-nav-main').find('.bp-step-nav li.selected');
				if( target.hasClass('bp-step-prev') ) {
					activeBlock.removeClass('selected').prev().addClass('selected');
					activeTab.removeClass('selected').prev().addClass('selected');
				} else if( target.hasClass('bp-step-next') ) {
					activeBlock.removeClass('selected').next().addClass('selected');
					activeTab.removeClass('selected').next().addClass('selected');
				}
			}

			// Hide Next/Prev Buttons if first or last tab is active
			var bpStepsLength = target.closest('.bp-step-nav-main').find('.bp-step-nav li').length;
			if( target.closest('.bp-step-nav-main').find('.bp-step-nav li.selected').index() == 0 ) {
				target.closest('.bp-step-nav-main').find('.bp-step-actions .bp-step-prev').hide();
			} else {
				target.closest('.bp-step-nav-main').find('.bp-step-actions .bp-step-prev').show();
			}

			if( target.closest('.bp-step-nav-main').find('.bp-step-nav li.selected').index() == bpStepsLength - 1 ) {
				target.closest('.bp-step-nav-main').addClass('last-tab').find('.bp-step-actions .bp-step-next').hide();
			} else {
				target.closest('.bp-step-nav-main').removeClass('last-tab').find('.bp-step-actions .bp-step-next').show();
			}
		},

		copyInputData: function(e) {
			$(document).find( 'input[name=' + $(e.currentTarget).attr('name').replace('-popup','') + ']' ).val( $(e.currentTarget).val() );
		},

		copyMainInputData: function(e) {
			$(document).find( 'input[name=' + $(e.currentTarget).attr('name') + '-popup]' ).val( $(e.currentTarget).val() );
		},

		zoomSettingsSave: function(e) {
			if ( $(e.target).closest('form').find('#bp-group-zoom-api-key').val().trim() !== '' &&
				$(e.target).closest('form').find('#bp-group-zoom-api-secret').val().trim() !== '' &&
				$(e.target).closest('form').find('#bp-group-zoom-api-email').val().trim() !== '' )
			{
				$(e.target).addClass('loading');
			}
		},

		zoomInstructionSave: function() {
			if (typeof jQuery.fn.magnificPopup !== 'undefined') {
				$.magnificPopup.close();
			}
			$(document).find('#group-settings-form button.bb-save-settings').trigger('click');
		},

		zoomInstructionNavigate: function(e) {

			if( $('.bp-zoom-group-show-instructions').length ) {

				if( e.keyCode == 39 ) {
					$( '.bp-zoom-group-show-instructions .bp-step-actions .bp-step-next:visible' ).trigger( 'click' );
				} else if( e.keyCode == 37 ) {
					$( '.bp-zoom-group-show-instructions .bp-step-actions .bp-step-prev:visible' ).trigger( 'click' );
				}
			}

		}
	};

	// Launch BP Zoom
	bp.Zoom_Common.start();

} )( bp, jQuery );
