/**
 * This file is used to show ads carousal and Bulk Rename.
 *
 * @package Content Cloner.
 */

(function( $ ) {
	'use strict';
	/**
	 * All of the code for your admin-specific JavaScript source
	 * should reside in this file.
	 *
	 * Note that this assume you're going to use jQuery, so it prepares
	 * the $ function reference to be used within the scope of this
	 * function.
	 *
	 * From here, you're able to define handlers for when the DOM is
	 * ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * Or when the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and so on.
	 *
	 * Remember that ideally, we should not attach any more than a single DOM-ready or window-load handler
	 * for any particular page. Though other scripts in WordPress core, other plugins, and other themes may
	 * be doing this, we should try to minimize doing that in our own work.
	 */
	$( document ).ready(
		function(){
			$( '.carousel' ).carousel(
				{
					interval: 1000 * 10
				}
			);
			$( "#save_post_titles" ).click(
				function(){
					var this_btn = $( this );
					if ( this_btn.data( "lock" ) == 0 ) {
							this_btn.data( "lock", 1 );
							this_btn.after( "<img src='" + ldbr_js_data.image_base_url + "loader.gif'>" );
							var array = {};
						$( ".ldbr-table .ldbr-row" ).each(
							function( index, value ){
								var input_new_title                        = $( value ).find( ".ldbr-post-new-title" );
								array[ input_new_title.data( 'post-id' ) ] = input_new_title.val();
							}
						);

						$.ajax(
							{
								method: "POST",
								url: ldbr_js_data.adm_ajax_url,
								data: {
									action: "ldbr_bulk_rename",
									security: $( "#ldbr_security" ).val(),
									course_data : JSON.stringify( array ),
								},
							}
						).
						success(
							function( result ){
								this_btn.data( "lock", 0 );
								var res = JSON.parse( result );
								if ( res.success ) {
									this_btn.parents( "td" ).find( "img" ).remove();
									this_btn.after( "<img src='" + ldbr_js_data.image_base_url + "tick.png'>" );
									$( ".ldbr-table" ).after( "<div class='ldbr-success'>" + res.success + "</div>" );
								} else {
									$( '.ldbr-table' ).after( "<div class='ldbr-error'>" + res.error + "</div>" );
								}
							}
						);
						window.setTimeout(
							function(){
								$( '.ldbr-success, .ldbr-error, .ldbr-foot-row img' ).hide( "slow" );
							},
							3500
						);
					} else {
						alert( "Please wait till the renaming process is completed." );
					}
				}
			);

		}
	);

})( jQuery );
