const { _n } = wp.i18n;

/**
 * Shows the payment form submitted successfully message and executes the afterAlertHook after the countdown.
 *
 * If it's impossible to show the message, the afterAlertHook will be executed immediately.
 *
 * @since 4.21.3
 *
 * @param {string} afterAlertHook - The hook to call after the message is shown.
 *
 * @return {void}
 */
function learnDashPaymentsShowFormSubmittedSuccessfullyMessage(afterAlertHook) {
	const modernRegistrationAfterElement = jQuery(
		'.ld-registration-order__heading'
	);
	const modernRegistrationParentElement = jQuery('.ld-registration-order');
	const classicRegistrationAfterElement = jQuery('.order-heading');
	const classicRegistrationParentElement = jQuery(
		'#learndash-registration-wrapper'
	);

	let countdown = learndash_payments.payment_form_redirect_alert_countdown;
	let afterElement = null;
	let parentElement = null;

	if (modernRegistrationAfterElement.length > 0) {
		afterElement = modernRegistrationAfterElement;
		parentElement = modernRegistrationParentElement;
	} else if (classicRegistrationAfterElement.length > 0) {
		afterElement = classicRegistrationAfterElement;
		parentElement = classicRegistrationParentElement;
	} else {
		// If it's impossible to find the after element to insert the alert, execute the afterAlertHook immediately.
		afterAlertHook();

		return;
	}

	afterElement.after(learndash_payments.payment_form_submitted_alert).focus();

	const countdownValueElement = jQuery(
		'#ld-payments-redirect-alert-countdown-value'
	);
	const countdownUnitLabelElement = jQuery(
		'#ld-payments-redirect-alert-countdown-unit-label'
	);

	countdownValueElement.text(countdown);
	countdownUnitLabelElement.text(
		_n('second', 'seconds', countdown, 'learndash')
	);

	const countdownInterval = setInterval(() => {
		countdown--;

		countdownValueElement.text(countdown);
		countdownUnitLabelElement.text(
			_n('second', 'seconds', countdown, 'learndash')
		);

		if (countdown <= 0) {
			clearInterval(countdownInterval);

			// Remove the alert.
			parentElement.find('.ld-alert').remove();

			// Execute the afterAlertHook.
			afterAlertHook();
		}
	}, 1000);
}

jQuery(document).ready(function ($) {
	'use strict';

	// PayPal.

	$('.learndash-payment-gateway-form-paypal').on(
		'submit.paypal',
		function (e) {
			e.preventDefault();

			// Remove the event handler after first submission to avoid infinite loop.
			$(this).off('submit.paypal');

			learnDashPaymentsShowFormSubmittedSuccessfullyMessage(function () {
				$('#btn-join').click();
			});
		}
	);

	// Razorpay.

	$('.learndash-payment-gateway-form-razorpay').on(
		'submit.razorpay',
		function (e) {
			e.preventDefault();

			const $form = $(this);
			const $button = $(this).find('button[type="submit"]');

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
					const afterAlertHook = function () {
						const options = response.data.options;

						options.handler = function () {
							alert(
								learndash_payments.messages
									.successful_transaction
							);

							window.location.replace(response.data.redirect_url);
						};

						const razorpay = new Razorpay(options);

						razorpay.on('payment.failed', function (response) {
							alert(response.error.description);
						});

						razorpay.open();
					};

					learnDashPaymentsShowFormSubmittedSuccessfullyMessage(
						afterAlertHook
					);
				} else {
					alert(response.data.message);
				}
			});
		}
	);
});
