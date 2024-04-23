/* jshint browser: true */
/* global bp, window */
window.bp = window.bp || {};

( function( exports, $ ) {

	/**
	 * [Reaction description]
	 *
	 * @type {Object}
	 */
	bp.Reaction = {
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
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {
			var self = this;

			$( window ).ready(
				function() {

					if ( $( 'body' ).hasClass( 'bb-is-mobile' ) && $( 'body' ).hasClass( 'bb-reactions-mode' ) ) {

						$( document ).on(
							'contextmenu',
							'.button.bp-like-button',
							function( event ) {
								event.preventDefault();
								return false;
							}
						);

						$( document ).on(
							'touchstart',
							'a.button.fav, .button.reaction, a.button.has-like, a.button.has-emotion, .button.bp-like-button',
							self.showReactionsOnTouch.bind( self )
						).on(
							'touchend',
							'a.button.fav, .button.reaction, a.button.has-like, a.button.has-emotion, .button.bp-like-button',
							function() {
								clearTimeout( window.reactionTouchTimeout );
							}
						);

						$( document ).on( 'touchend', '.activity-item .ac-emotions_list, .button.fav, .button.unfav, .button.has-like, .button.has-emotion, .button.bp-like-button', self.ReactionsOnTouchEnd.bind( self ) );

						$( document ).on(
							'click',
							function( event ) {
								const element = $( '.button.fav, .button.unfav, .button.has-like, .button.has-emotion, .button.bp-like-button' );
								if ( ! element.is( event.target ) && ! element.has( event.target ).length ) {
									$( '.activity-item .bp-generic-meta' ).find( '.ac-emotions_list.active' ).removeClass( 'active' );
								}
							}
						);
					} else {
						$( document ).on(
							'mouseover',
							'a.button.fav, .button.reaction, a.button.has-like, a.button.has-emotion',
							self.showReactions.bind( self )
						).on(
							'mouseout',
							'a.button.fav, .button.reaction, a.button.has-like, a.button.has-emotion',
							function() {
								clearTimeout( window.reactionHoverTimeout );
							}
						);

						$( document ).on( 'mouseleave', '.activity-item .ac-emotions_list, .button.fav, .button.unfav, .button.has-like, .button.has-emotion', self.hideReactions.bind( self ) );
					}
				}
			);
		},

		/**
		 * [showReactions description]
		 *
		 * @return {[type]}       [description]
		 */
		showReactionsOnTouch: function( event ) {
			var $this = $( event.currentTarget );
			if ( $this.hasClass( 'bb-reaction-migration-inprogress' ) ) {
				return;
			}
			window.reactionTouchTimeout = setTimeout(
				function() {
					$this.closest( '.bp-generic-meta' ).find( '.ac-emotions_list' ).addClass( 'active' );
				},
				500
			);
		},

		/**
		 * [hideReactions description]
		 *
		 * @return {[type]}       [description]
		 */
		ReactionsOnTouchEnd: function( event ) {
			var $this = $( event.currentTarget );

			if ( ! $this.closest( '.bp-generic-meta' ).find( '.ac-emotions_list' ).hasClass( 'active' ) ) {
				$this.trigger( 'click' );
				$this.trigger( $.Event( 'click', { customTriggered: true } ) );
			}
		},

		/**
		 * [showReactions description]
		 *
		 * @return {[type]}       [description]
		 */
		showReactions: function( event ) {
			var $this = $( event.currentTarget );
			if ( $this.hasClass( 'bb-reaction-migration-inprogress' ) ) {
				return;
			}
			window.reactionHoverTimeout = setTimeout(
				function() {
					$this.closest( '.bp-generic-meta' ).find( '.ac-emotions_list' ).addClass( 'active' );
				},
				500
			);
		},

		/**
		 * [hideReactions description]
		 *
		 * @return {[type]}       [description]
		 */
		hideReactions: function( event ) {
			$( event.currentTarget ).closest( '.bp-generic-meta' ).find( '.ac-emotions_list' ).removeClass( 'active' );
		},

	};

	// Launch Reaction.
	bp.Reaction.start();

} )( bp, jQuery );
