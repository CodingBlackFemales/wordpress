/* jshint browser: true */
/* global bp */
/* @version 1.0.0 */
window.bp = window.bp || {};

(function ( exports, $ ) {

	/**
	 * [Pusher description]
	 *
	 * @type {Object}
	 */
	bp.Pusher_Settings = {
		/**
		 * [start description]
		 *
		 * @return {[type]} [description]
		 */
		start: function () {
			this.setupGlobals();

			// Listen to events ("Add hooks!").
			this.addListeners();
		},

		/**
		 * [setupGlobals description]
		 *
		 * @return {[type]} [description]
		 */
		setupGlobals: function() {},

		/**
		 * [addListeners descriptiong]
		 */
		addListeners: function () {
			$( document ).on( 'change', '#bb-pusher-app-cluster', this.ValidateClusterUpdate.bind( this ) );
			$( document ).on( 'click', '.bb-hide-pw', this.TogglePasswordField.bind( this ) );
		},

		ValidateClusterUpdate: function ( event ) {
			var current_val = $( event.currentTarget ).val(),
				$parent     = $( event.currentTarget ).parents( 'td' );
			if ( 'custom' === current_val ) {
				$parent.find( '.custom-cluster' )
					.removeClass( 'bp-hide' )
					.find( '#bb-pusher-app-custom-cluster' )
					.prop( 'required', true );
			} else {
				$parent.find( '.custom-cluster' )
					.addClass( 'bp-hide' )
					.find( '#bb-pusher-app-custom-cluster' )
					.removeAttr( 'required' );
			}
		},

		TogglePasswordField: function( event ) {
			var current_item = $( event.currentTarget ),
				pass_field   = current_item.parent( '.password-toggle' ).find( 'input' );

			if ( 'password' === pass_field.attr( 'type' ) ) {
				pass_field.attr( 'type', 'text' );
			} else {
				pass_field.attr( 'type', 'password' );
			}
		},

	};

	// Launch Pusher Settings.
	bp.Pusher_Settings.start();

})( bp, jQuery );
