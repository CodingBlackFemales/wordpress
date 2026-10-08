jQuery( document ).ready( function( $ ) {

var LD_Notifications_Tools = {};

LD_Notifications_Tools.init = function() {
	LD_Notifications_Tools.run_fix_recipient_tool();
	LD_Notifications_Tools.confirm_empty_db_table();
};

LD_Notifications_Tools.run_fix_recipient_tool = function() {
	$( '#ld-fix-recipient-button' ).on( 'click', function(e) {
		e.preventDefault();

		if ( $( this ).attr( 'disabled' ) ) {
			return false;
		}
		
		$( this ).parent( 'td' ).append( '<p class="description" id="ld-notifications-fix-recipient-status"></p>');
		$( '#ld-notifications-fix-recipient-status' ).text( LD_Notifications_Tools_Params.text.keep_page_open + ' ' + LD_Notifications_Tools_Params.text.status + ': 0%' );
		$( 'a.button#ld-fix-recipient-button' ).text( '' ).addClass( 'ld-notifications-spinner' ).attr( 'disabled', 'disabled' );
		LD_Notifications_Tools.process_fix_recipient();
	});
};

LD_Notifications_Tools.confirm_empty_db_table = function() {
	$( document ).on( 'click', '.empty-db-table', function( e ) {
		var empty_db_table = confirm( LD_Notifications_Tools_Params.text.confirm.empty_db_table );
		if ( ! empty_db_table ) {
			return false;
		}
	});
};

LD_Notifications_Tools.process_fix_recipient = function( step = 1, total = null, checked_emails = null ) {
	$.ajax({
		url: ajaxurl,
		type: 'POST',
		dataType: 'json',
		data: {
			action: 'ld_notifications_fix_recipients',
			nonce: LD_Notifications_Tools_Params.nonce.fix_recipient,
			step: step,
			total: total,
			checked_emails: checked_emails
		},
	}).done( function( data ) {
		console.log( data );
		if ( typeof( data ) != 'undefined' ) {
			if ( data.step !== 'complete' ) {
				$( '#ld-notifications-fix-recipient-status' ).text( LD_Notifications_Tools_Params.text.keep_page_open + ' ' + LD_Notifications_Tools_Params.text.status + ': ' + data.percentage + '%' );
				LD_Notifications_Tools.process_fix_recipient( data.step, data.total, data.checked_emails );
			} else {
				// done
				$( 'a.button#ld-fix-recipient-button' ).text( LD_Notifications_Tools_Params.text.button ).removeClass( 'ld-notifications-spinner' ).removeAttr( 'disabled' );
				$( '#ld-notifications-fix-recipient-status' ).text( LD_Notifications_Tools_Params.text.status + ': ' + LD_Notifications_Tools_Params.text.complete );
			}
		}
	});
};

LD_Notifications_Tools.init();

});