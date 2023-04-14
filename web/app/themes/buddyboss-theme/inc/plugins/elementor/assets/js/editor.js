( function( $ ) {
	"use strict";

	$(document).ready(function() {
	
		elementor.hooks.addAction( 'panel/open_editor/widget/header-bar', function( panel, model, view ) {
			
			/*var $element = view.$el.find( '.header-aside' );
			if ( $element.length ) {
				$element.click( function() {
					console.log( 'Header Bar Element' );
				} );
			}*/

		} );

	} );

	
} )( jQuery );