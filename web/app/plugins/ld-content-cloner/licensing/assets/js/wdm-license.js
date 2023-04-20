jQuery( document ).ready(
	function () {
		jQuery( "#send_data" ).change(
			function () {
				var checkStatus = jQuery( "#send_data" ).is( ':checked' );
				if ( checkStatus ) {
					checkStatus = 'yes';
				} else {
					checkStatus = 'no';
				}
				var data = {
					'action': 'save_send_data', // Action to store quotation in database
					'checkStatus': checkStatus,
				};

				jQuery.post(
					license_data.ajax_url,
					data,
					function ( response ) {

					}
				);
			}
		);
	}
)
