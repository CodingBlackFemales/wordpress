/* global bbTutorLMSVars */
( function ( $ ) {
	var BB_TutorLMS = {

		init: function () {

			this.setupGlobals();

			// Listen to events ("Add hooks!").
			this.addListeners();
		},

		setupGlobals: function () {
			if ( jQuery( '.buddyboss_page_bp-integrations .section-bb_tutorlms_posts_activity_settings_section' ).length ) {
				jQuery( '.bp-feed-post-type-checkbox' ).each(
					function () {
						var post_type = $( this ).data( 'post_type' );

						if ( true === this.checked ) {
							$( '.bp-feed-post-type-comment-' + post_type ).closest( 'tr' ).show();
						}
					}
				);
			}

			if ( typeof jQuery.fn.select2 !== 'undefined' ) {
				jQuery( '.bb_tutorlms_select2' ).select2(
					{
						ajax: {
							url: bbTutorLMSVars.ajax_url,
							dataType: 'json',
							delay: 250,
							data: function ( params ) {
								return {
									action: 'bb_tutorlms_group_course',
									q: params.term,
									page: params.page || 1,
								};
							},
							processResults: function ( data ) {
								var options = [];
								if ( data && data.matches ) {
									$.each(
										data.matches,
										function ( index, text ) {
											options.push( { id: text.value, text: text.label } );
										}
									);
								}
								return {
									results: options,
									pagination: {
										more: data.more,
									}
								};
							},
							cache: true,
						},
						placeholder: bbTutorLMSVars.select_course_placeholder,
						minimumInputLength: 2,
						dropdownParent: jQuery( '.bb_tutorlms_select2' ).parent(),
					}
				);
			}

			/* jshint ignore:start */
			var bbTutrolmsgroupCourse = this.bbgetUrlParameter( 'scrollto' );
			if ( 'bpmigratetutorgroupcourse' === bbTutrolmsgroupCourse ) {
				$( 'html, body' ).animate(
					{
						scrollTop: $( '#bp-migrate-tutorlms-buddypress-group-course' ).offset().top,
					},
					1500
				);
				$( '.label-bp-migrate-tutorlms-buddypress-group-course' ).css( 'background-color', '#faafaa' );
				setTimeout(
					function () {
						$( '.label-bp-migrate-tutorlms-buddypress-group-course' ).css( 'background-color', 'transparent' );
					},
					1500
				);
			}
			/* jshint ignore:end */
		},

		addListeners: function () {
			$( '.buddyboss_page_bp-integrations .section-bb_tutorlms_posts_activity_settings_section' ).on( 'click', '.bp-feed-post-type-checkbox', this.openPostCommentCheckbox.bind( this ) );
			$( '.bb-group-tutorlms-settings-container .bb-tutorlms-group-option-enable' ).on( 'click', '#bb-tutorlms-group-course-is-enable', this.openDependencySettings.bind( this ) );
		},

		openPostCommentCheckbox: function ( event ) {
			var target       = $( event.currentTarget );
			var post_type    = target.data( 'post_type' ),
				commentField = $( '.bp-feed-post-type-comment-' + post_type );

			if ( target.is( ':checked' ) ) {
				commentField.closest( 'tr' ).show();
			} else {
				commentField.prop( 'checked', false ).closest( 'tr' ).hide();
			}
		},

		openDependencySettings: function ( event ) {
			var target = event.currentTarget;
			if ( $( target ).is( ':checked' ) ) {
				jQuery( '.bb-course-activity-selection' ).removeClass( 'bb-hide' );
			} else {
				jQuery( '.bb-course-activity-selection' ).addClass( 'bb-hide' );
			}
		},

		bbgetUrlParameter: function ( name ) {
			name        = name.replace( /[\[]/, '\\[' ).replace( /[\]]/, '\\]' );
			var regex   = new RegExp( '[\\?&]' + name + '=([^&#]*)' ),
				results = regex.exec( location.search );
			return results === null ? '' : decodeURIComponent( results[ 1 ].replace( /\+/g, ' ' ) );
		},
	};

	$(
		function () {
			BB_TutorLMS.init();
		}
	);
} )( jQuery );
