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


	/* Change Stats
	------------------------------------------ */
	$( 'body' ).on( 'change', 'input[name="stats-options"]', function() {
		var wrap = $( this ).parents( 'li.wpjms-stats-option' );
		if( $( this ).is( ':checked' ) ){
			wrap.siblings( 'li' ).data( 'checked', 'unchecked' );
			wrap.siblings( 'li' ).attr( 'data-checked', 'unchecked' );
			wrap.data( 'checked', 'checked' );
			wrap.attr( 'data-checked', 'checked' );
		}
		else{
			wrap.siblings( 'li' ).data( 'checked', 'checked' );
			wrap.siblings( 'li' ).attr( 'data-checked', 'checked' );
			wrap.data( 'checked', 'unchecked' );
			wrap.attr( 'data-checked', 'unchecked' );
		}
		refresh_chart();
	});


	/* Change Legend (Items)
	------------------------------------------ */
	$( 'body' ).on( 'change', 'input[name="chart-item[]"]', function() {
		var wrap = $( this ).parents( 'li.chart-item' );
		if( $( this ).is( ':checked' ) ){
			wrap.data( 'checked', 'checked' );
			wrap.attr( 'data-checked', 'checked' );
		}
		else{
			wrap.data( 'checked', 'unchecked' );
			wrap.attr( 'data-checked', 'unchecked' );
		}
		refresh_chart();
	});


	/* Search Legend
	------------------------------------------ */
	var LegendSearch = $( "#wpjms-legend-search" ).autocomplete( {
		source: function ( request, response ){

			/* Bail if less than 3 character */
			if( request.term.length < 3 ){
				response( { value: wpjms.min_char } );
				return;
			}

			/* Exclude */
			var post_ids = [];
			$( "#wpjms-chart-legend-list" ).find( "li" ).each( function(){
				post_ids.push( $( this ).data( 'id' ) ); 
			} );

			/* Ajax */
			wp.ajax.post( 'wpjms_legend_search', {
				nonce      : wpjms.ajax_nonce,
				exclude    : post_ids,
				keyword    : request.term,
			} )
			.done( function( data ) {
				response( data );
				return;
			} );

		},
		minLength : 0,
		select: function (e, ui) {
			if( undefined !== ui.item.html ){
				$( '#wpjms-chart-legend-list' ).append( ui.item.html );
				refresh_chart();
			}
			$( this ).val( '' );
			return false;
		},
	} )
	LegendSearch.bind( 'focus', function(){
		$(this).autocomplete( "search" );
	} );


	/* Remove Legend Items
	------------------------------------------ */
	$( 'body' ).on( 'click', '.remove-chart-legend-item', function(e){
		e.preventDefault();
		$( this ).parents( 'li.chart-item' ).remove();
		refresh_chart();
	})

	/* Ajax: Refresh Chart
	------------------------------------------ */
	function refresh_chart(){

		/* Date */
		var date_from = $( 'input[name="chart-date_from"]' ).val();
		var date_to = $( 'input[name="chart-date_to"]' ).val();

		/* Items */
		var items = [];
		$( 'input[name="chart-item[]"]:checked' ).each( function(){
			var id    = $( this ).attr( 'value' );
			var color = $( this ).attr( 'data-color' );
			items.push( {id,color} );
		} );

		/* Stats */
		var stat_id = $( 'input[name="stats-options"]:checked' ).val();

		/* Wrap */
		var wrap = $( '#wpjms-job-dashboard' );
		wrap.block({ message: null, full: false, overlayCSS: { background: '#fff', opacity: 0.6 } });

		/* AJAX */
		wp.ajax.post( 'wpjms_job_dashboard_chart', {
			nonce     : wpjms.ajax_nonce,
			date_from : date_from,
			date_to   : date_to,
			stat_id   : stat_id,
			items     : items,
		} )
		.done( function( data ) {
			wrap.height( wrap.height() + 'px' );
			$( '#wpjms_job_dashboard_chart' ).html( data );
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