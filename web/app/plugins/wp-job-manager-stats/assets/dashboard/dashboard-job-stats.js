jQuery( document ).ready( function( $ ){

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
			}
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

	/* Ajax: Refresh Chart
	------------------------------------------ */
	function refresh_chart(){

		/* Wrap */
		var wrap = $( '#wpjms-job-stats' );
		var chart_div = $( '#wpjms_job_stats_chart' );
		wrap.block({ message: null, full: false, overlayCSS: { background: '#fff', opacity: 0.6 } });

		/* AJAX */
		wp.ajax.post( 'wpjms_job_stats_chart', {
			nonce     : wpjms.ajax_nonce,
			date_from : $( 'input[name="chart-date_from"]' ).val(),
			date_to   : $( 'input[name="chart-date_to"]' ).val(),
			post_id   : chart_div.data( 'post_id' ),
		} )
		.done( function( data ) {
			wrap.height( wrap.height() + 'px' );
			chart_div.html( data );
			wrap.height( 'auto' ).unblock();
		} )
		.fail( function( data ) {
			wrap.height( wrap.height() + 'px' );
			wrap.html( data );
			wrap.height( 'auto' ).unblock();
		} );

	}

	/* Load it, on initial page load */
	refresh_chart();

});

