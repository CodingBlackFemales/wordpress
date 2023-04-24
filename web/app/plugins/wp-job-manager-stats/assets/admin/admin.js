jQuery( document ).ready( function( $ ){


	/* Bulk Action
	------------------------------------------ */

	/* Top Bulk Action */
	$( 'body' ).on( 'click', '#doaction', function(e){
		var action = $( '#bulk-action-selector-top' ).val();
		if( 'wpjms_stats' == action ){
			e.preventDefault();
			refresh_chart();
		}
	});

	/* Bottom Bulk Action */
	$( 'body' ).on( 'click', '#doaction2', function(e){
		var action = $( '#bulk-action-selector-bottom' ).val();
		if( 'wpjms_stats' == action ){
			e.preventDefault();
			refresh_chart();
		}
	});


	/* Date Range Picker
	------------------------------------------ */
	$.dateRangePickerLanguages = { 'wpjms': drp_lang };
	$( 'input[name="chart-date-range"]' ).dateRangePicker({
		language:'wpjms',
		setValue: function(s) {
			this.value = s;
		},
		showShortcuts: true,
		showTopbar: true,
		singleMonth: false,
		shortcuts : {
			'prev-days': [3,7,14],
			'prev': ['week','month'],
			'next-days': null,
			'next': null,
		},
		customShortcuts: [
			{
				name: wpjms.this_week,
				dates : function()
				{
					var start = moment().day(0).toDate();
					var end = moment().day(6).toDate();
					return [start,end];
				}
			},
			{
				name: wpjms.this_month,
				dates : function()
				{
					var start = moment().startOf('month').toDate();
					var end = moment().endOf('month').toDate();
					return [start,end];
				}
			},
		]
	});
	$( 'body' ).on( 'datepicker-change', 'input[name="chart-date-range"]', function( e, obj ) {

		/* Get date from + to */
		var date1 = new Date( obj.date1 );
		var date2 = new Date( obj.date2 );

		/* Format it in YYYY-MM-DD for consistency */
		var date_from = date1.getFullYear() + '-' + ("0" + (date1.getMonth() + 1)).slice(-2) + '-' + ("0" + date1.getDate()).slice(-2);
		var date_to = date2.getFullYear() + '-' + ("0" + (date2.getMonth() + 1)).slice(-2) + '-' +("0" + date2.getDate()).slice(-2);

		/* Add it in hidden input */
		$( 'input[name="chart-date_from"]' ).val( date_from );
		$( 'input[name="chart-date_to"]' ).val( date_to );

		/* Update chart */
		refresh_chart();
	});

	/* Change Stats
	------------------------------------------ */
	$( 'body' ).on( 'change', 'select[name="stats-options"]', function() {
		refresh_chart();
	});


	/* Close Modal
	------------------------------------------ */
	$( 'body' ).on( 'click', '.wpjms-box-close,#wpjms-box-overlay', function(e){
		e.preventDefault();
		$( "#wpjms-box,#wpjms-box-overlay" ).hide();
	});

	/* Load Stats
	------------------------------------------ */
	function refresh_chart(){

		/* Items */
		var items = [];
		$( 'input[name="post[]"]:checked' ).each( function(){
			var id    = $( this ).attr( 'value' );
			items.push( id );
		} );

		/* Only if items selected */
		if( items.length ){

			/* Date */
			var date_from = $( 'input[name="chart-date_from"]' ).val();
			var date_to = $( 'input[name="chart-date_to"]' ).val();

			/* Stat */
			var stat_id = $( 'select[name="stats-options"]' ).val();

			/* Open Modal + Resize Properly */
			$( "#wpjms-box-overlay, #wpjms-box" ).show();
			$( "#wpjms-box-content" ).css( "height", ( $( '#wpjms-box' ).height() - $( '.wpjms-box-close' ).height() ) + "px" );
			$( window ).resize( function(){
				$( "#wpjms-box-content" ).css( "height", ( $( '#wpjms-box' ).height() - $( '.wpjms-box-close' ).height() ) + "px" );
			});

			/* Block */
			var wrap = $( '#wpjms-box-content' );
			wrap.block({ message: null, full: false, overlayCSS: { background: '#fff', opacity: 0.6 } });


			/* Load Chart via Ajax */
			wp.ajax.post( 'wpjms_admin_chart', {
				nonce     : wpjms.ajax_nonce,
				date_from : date_from,
				date_to   : date_to,
				stat_id   : stat_id,
				items     : items,
			} )
			.done( function( data ) {
				$( '#wpjms_admin_chart' ).html( data );
				wrap.unblock();
			} )
			.fail( function( data ) {
				wrap.unblock();
			} );

		}
	}

});