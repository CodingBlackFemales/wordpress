jQuery(function ($) {
	const accountSwitch = $('input#createaccount');
	if (accountSwitch.length) {
		accountSwitch.attr('checked', true).parent().hide();

		$('.create-account').show();
		accountSwitch.parent().hide();
	}
});
