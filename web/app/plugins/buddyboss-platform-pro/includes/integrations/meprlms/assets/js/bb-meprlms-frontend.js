( function ( $ ) {
	var BB_MeprLMS = {

		init: function () {
			this.setupGlobals();
		},

		setupGlobals: function () {

			setTimeout(
				function () {
					if ( $( '.mpcs-sidebar-wrapper .progress-bar .user-progress' ).length ) {
						$( '.mpcs-sidebar-wrapper .progress-bar .user-progress' ).html( '' );
					}
				},
				100
			);
		}
	};

	$(
		function () {
			BB_MeprLMS.init();
		}
	);
} )( jQuery );
