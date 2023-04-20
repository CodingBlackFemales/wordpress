jQuery(document).ready(function ($) {
	'use strict';

	// Razorpay.

	$('.learndash-payment-gateway-form-razorpay').on(
		'submit.razorpay',
		function (e) {
			const $form = $(this);
			const $button = $(this).find('input[type="submit"]');

			$form.addClass('ld-loading');
			$button.attr('disabled', true);

			$.ajax({
				type: 'POST',
				url: learndash_payments.ajaxurl,
				dataType: 'json',
				data: $(this).data(),
			}).done(function (response) {
				$form.removeClass('ld-loading');
				$button.removeAttr('disabled');

				if (response.success) {
					const options = response.data.options;

					options.handler = function () {
						alert(
							learndash_payments.messages.successful_transaction
						);

						window.location.replace(response.data.redirect_url);
					};

					const razorpay = new Razorpay(options);

					razorpay.on('payment.failed', function (response) {
						alert(response.error.description);
					});

					razorpay.open();
				} else {
					alert(response.data.message);
				}
			});

			e.preventDefault();
		}
	);
});
