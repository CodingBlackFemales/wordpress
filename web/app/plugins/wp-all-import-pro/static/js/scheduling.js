/**
 * plugin javascript
 */
(function($){$(function () {

	if ( ! $('body.wpallimport-plugin').length) return; // do not execute any code if we are not on plugin page

	var hasActiveLicense = false;

		// Main accordion logic
	$(document).on('change', 'input[name="scheduling_enable"]', function () {
		var schedulingEnable = $('input[name="scheduling_enable"]:checked').val();
		if (schedulingEnable == 1) {
			$('#automatic-scheduling').slideDown();
			$('.manual-scheduling').slideUp();
			setTimeout(function () {
				$('.timezone-select').slideDown(275);
			}, 200);
		}
		else if (schedulingEnable == 2) {
			$('.timezone-select').slideUp(275);
			$('#automatic-scheduling').slideUp();
			$('.manual-scheduling').slideDown();
		} else {
			$('.timezone-select').hide();
			$('#automatic-scheduling').slideUp();
			$('.manual-scheduling').slideUp();
		}
		if(!window.pmxiHasSchedulingSubscription && parseInt(schedulingEnable) == 1) {
			$('.save-changes').addClass('disabled');
		} else {
			$('.save-changes').removeClass('disabled');
		}
	});

	// help scheduling template
	$('.help_scheduling').on('click', function(){

		$('.wp-all-import-scheduling-help').css('left', ($( document ).width()/2) - 255 ).show();
		$('#wp-all-import-scheduling-help-inner').css('max-height', $( window ).height()-150).show();
		$('.wpallimport-overlay').show();
		return false;
	});

	var saveSubscription = false;

	$('#add-subscription').on('click', function(){
		$('#add-subscription-field').show();
		$('#add-subscription-field').animate({width:'400px'}, 225);
		$('#add-subscription-field').animate({left:'0'}, 225);
		$('#subscribe-button .button-subscribe').css('background-color','#46ba69');
		$('.text-container p').fadeOut();

		setTimeout(function () {
			$('#find-subscription-link').show();
			$('#find-subscription-link').animate({left: '410px'}, 300, 'swing');
		}, 225);
		$('.subscribe-button-text').html('Activate');
		saveSubscription = true;
		return false;
	});

	$('.wp_all_import_scheduling_help').find('h3').on('click', function(){
		var $action = $(this).find('span').html();
		$('.wp_all_import_scheduling_help').find('h3').each(function(){
			$(this).find('span').html("+");
		});
		if ( $action == "+" ) {
			$('.wp_all_import_help_tab').slideUp();
			$('.wp_all_import_help_tab[rel=' + $(this).attr('id') + ']').slideDown();
			$(this).find('span').html("-");
		}
		else{
			$('.wp_all_import_help_tab[rel=' + $(this).attr('id') + ']').slideUp();
			$(this).find('span').html("+");
		}
	});

	function openSchedulingAccordeonIfClosed() {
		if ($('.wpallimport-file-options').hasClass('closed')) {
			// Open accordion
			$('#scheduling-title').trigger('click');
		}
	}

    window.openSchedulingDialog = function(itemId, element, preloaderSrc) {
        $('.wpallimport-overlay').show();
        $('.wpallimport-loader').show();

        var $self = element;
        $.ajax({
            type: "POST",
            url: ajaxurl,
            context: element,
            data: {
                'action': 'wpai_scheduling_dialog_content',
                'id': itemId,
                'security' : wp_all_import_security
            },
            success: function (data) {

                $('.wpallimport-loader').hide();
                $(this).pointer({
                    content: '<div id="scheduling-popup">' + data + '</div>',
                    position: {
                        edge: 'right',
                        align: 'center'
                    },
                    pointerWidth: 815,
                    show: function (event, t) {

                        $('.timepicker').timepicker();

                        var $leftOffset = ($(window).width() - 715) / 2;
						var $topOffset = $(document).scrollTop() + 100;

                        var $pointer = $('.wp-pointer').last();
                        $pointer.css({'position': 'absolute', 'top': $topOffset + 'px', 'left': $leftOffset + 'px'});

                        $pointer.find('a.close').remove();
                        $pointer.find('.wp-pointer-buttons').append('<button class="save-changes button button-primary button-hero wpallimport-large-button scheduling-save-button" style="float: right; background-image: none;">Save</button>');
                        $pointer.find('.wp-pointer-buttons').append('<button class="close-pointer button button-primary button-hero wpallimport-large-button scheduling-cancel-button" style="float: right; background: #F1F1F1 none;text-shadow: 0 0 black; color: #777; margin-right: 10px;">Cancel</button>');

                        $(".close-pointer, .wpallimport-overlay").unbind('click').on('click', function () {
                            $self.pointer('close');
                            $self.pointer('destroy');
                        });

                        if(!window.pmxiHasSchedulingSubscription && $('input[name="scheduling_enable"]:checked').val() == 1) {
                            $('.save-changes').addClass('disabled');
                        }

                        $(".save-changes").unbind('click').on('click', function () {
							var schedulingEnable = $('input[name="scheduling_enable"]:checked').val();

                            if($(this).hasClass('disabled')) {
                                return false;
                            }

                            var formValid = pmxeValidateSchedulingForm();

                            if (formValid.isValid) {

                                var formData = $('#scheduling-form').serializeArray();
                                formData.push({name: 'security', value: wp_all_import_security});
                                formData.push({name: 'action', value: 'save_import_scheduling'});
                                formData.push({name: 'element_id', value: itemId});
                                formData.push({name: 'scheduling_enable', value: schedulingEnable});

                                $('.close-pointer').hide();
                                $('.save-changes').hide();

                                $('.wp-pointer-buttons').append('<img id="pmxe_button_preloader" style="float:right" src="' + preloaderSrc + '" /> ');
                                $.ajax({
                                    type: "POST",
                                    url: ajaxurl,
                                    data: formData,
                                    dataType: "json",
                                    success: function (data) {
                                        $('#pmxe_button_preloader').remove();
                                        $('.close-pointer').show();
                                        $(".wpallimport-overlay").trigger('click');
                                    },
                                    error: function () {
                                        alert('There was a problem saving the schedule');
                                        $('#pmxe_button_preloader').remove();
                                        $('.close-pointer').show();
                                        $(".wpallimport-overlay").trigger('click');
                                    }
                                });

                            } else {
                                alert(formValid.message);
                            }
                            return false;
                        });
                    },
                    close: function () {
                        jQuery('.wpallimport-overlay').hide();
                    }
                }).pointer('open');
            },
            error: function () {
                alert('There was a problem saving the schedule');
                $('#pmxe_button_preloader').remove();
                $('.close-pointer').show();
                $(".wpallimport-overlay").trigger('click');
                $('.wpallimport-loader').hide();
            }
        });
	};

	window.pmxiValidateSchedulingForm = function () {

		var schedulingEnabled = $('input[name="scheduling_enable"]:checked').val() == 1;

		if (!schedulingEnabled) {
			return {
				isValid: true
			};
		}

		var runOn = $('input[name="scheduling_run_on"]:checked').val();

		// Validate weekdays
		if (runOn == 'weekly') {
			var weeklyDays = $('#weekly_days').val();

			if (weeklyDays == '') {
				$('#weekly li').addClass('error');
				return {
					isValid: false,
					message: 'Please select at least a day on which the import should run'
				}
			}
		} else if (runOn == 'monthly') {
			var monthlyDays = $('#monthly_days').val();

			if (monthlyDays == '') {
				$('#monthly li').addClass('error');
				return {
					isValid: false,
					message: 'Please select at least a day on which the import should run'
				}
			}
		}

		// Validate times
		var timeValid = true;
		var timeMessage = 'Please select at least a time for the import to run';
		var timeInputs = $('.timepicker');
		var timesHasValues = false;

		timeInputs.each(function (key, $elem) {

			if($(this).val() !== ''){
				timesHasValues = true;
			}

			if (!$(this).val().match(/^(0?[1-9]|1[012])(:[0-5]\d)[APap][mM]$/) && $(this).val() != '') {
				$(this).addClass('error');
				timeValid = false;
			} else {
				$(this).removeClass('error');
			}
		});

		if(!timesHasValues) {
			timeValid = false;
			$('.timepicker').addClass('error');
		}

		if (!timeValid) {
			return {
				isValid: false,
				message: timeMessage
			};
		}

		return {
			isValid: true
		};
	};

	$('#weekly li').on('click', function () {

		$('#weekly li').removeClass('error');

		if ($(this).hasClass('selected')) {
			$(this).removeClass('selected');
		} else {
			$(this).addClass('selected');
		}

		$('#weekly_days').val('');

		$('#weekly li.selected').each(function () {
			var val = $(this).data('day');
			$('#weekly_days').val($('#weekly_days').val() + val + ',');
		});

		$('#weekly_days').val($('#weekly_days').val().slice(0, -1));

	});

	$('#monthly li').on('click', function () {

		$('#monthly li').removeClass('error');
		$(this).parent().parent().find('.days-of-week li').removeClass('selected');
		$(this).addClass('selected');

		$('#monthly_days').val($(this).data('day'));
	});

	$('input[name="scheduling_run_on"]').on('change', function () {
		var val = $('input[name="scheduling_run_on"]:checked').val();
		if (val == "weekly") {

			$('#weekly').slideDown({
				queue: false
			});
			$('#monthly').slideUp({
				queue: false
			});

		} else if (val == "monthly") {

			$('#weekly').slideUp({
				queue: false
			});
			$('#monthly').slideDown({
				queue: false
			});
		}
	});

	$('.timepicker').timepicker();

	var selectedTimes = [];

	var onTimeSelected = function () {

		selectedTimes.push([$(this).val(), $(this).val() + 1]);

		var isLastChild = $(this).is(':last-child');
		if (isLastChild) {
			$(this).parent().append('<input class="timepicker" name="scheduling_times[]" style="display: none;" type="text" />');
			$('.timepicker:last-child').timepicker({
				'disableTimeRanges': selectedTimes
			});
			$('.timepicker:last-child').fadeIn('fast');
			$('.timepicker').on('changeTime', onTimeSelected);
		}
	};

	$('.timepicker').on('changeTime', onTimeSelected);

	$('#timezone').chosen({width: '320px'});

	$('.wpai-import-complete-save-button').on('click', function (e) {

		if($('.wpai-save-button').hasClass('disabled')) {
			return false;
		}

		var initialValue = $(this).find('.save-text').html();
		var schedulingEnable = $('input[name="scheduling_enable"]:checked').val() == 1;

		var validationResponse = pmxiValidateSchedulingForm();
		if (!validationResponse.isValid) {

			openSchedulingAccordeonIfClosed();
			e.preventDefault();
			return false;
		}

		var formData = $('#scheduling-form :input').serializeArray();

		formData.push({name: 'security', value: wp_all_import_security});
		formData.push({name: 'action', value: 'save_import_scheduling'});
		formData.push({name: 'element_id', value: import_id});
		formData.push({name: 'scheduling_enable', value: $('input[name="scheduling_enable"]:checked').val()});

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: formData,
			success: function (response) {
				var submitEvent = $.Event('wpae-scheduling-options-form:submit');
				$(document).trigger(submitEvent);
			},
			error: function () {
			}
		});
	});

	$('#subscribe-button').on('click', function(){

		if(saveSubscription) {
			$('#subscribe-button .easing-spinner').show();
			var license = $('#add-subscription-field').val();
			$.ajax({
				url:ajaxurl+'?action=wp_all_import_api&q=schedulingLicense/saveSchedulingLicense&security=' + wp_all_import_security,
				type:"POST",
				data: {
					license: license
				},
				dataType:"json",
				success: function(response){

					$('#subscribe-button .button-subscribe').css('background-color','#425f9a');
					if(response.success) {
						hasActiveLicense = true;
						$('.wpai-save-button').removeClass('disabled');
						$('#subscribe-button .easing-spinner').hide();
						$('#subscribe-button svg.success').show();
						$('#subscribe-button svg.success').fadeOut(3000, function () {
							$('.subscribe').hide({queue: false});
							$('#subscribe-filler').show({queue: false});
						});

						$('.wpai-no-license').hide();
						$('.wpai-license').show();
                        $('#scheduling_has_license').val('1');
					} else {
						$('#subscribe-button .easing-spinner').hide();
						$('#subscribe-button svg.error').show();
						$('.subscribe-button-text').html('Subscribe');
						$('#subscribe-button svg.error').fadeOut(3000, function () {
							$('#subscribe-button svg.error').hide({queue: false});

						});

						$('#add-subscription').html('Invalid license, try again?');
						$('.text-container p').fadeIn();

						$('#find-subscription-link').animate({width: 'toggle'}, 300, 'swing');

						setTimeout(function () {
							$('#add-subscription-field').animate({width:'140px'}, 225);
							$('#add-subscription-field').animate({left:'-161px'}, 225);
						}, 300);

						$('#add-subscription-field').val('');

						$('#subscribe-button-text').html('Subscribe');
						saveSubscription = false;
					}
				}
			});

			return false;
		}
	});

	function get_delete_missing_notice_type() {
		if (!$('input[name="is_delete_missing"]').is(':checked')) {
			return 0;
		}
		if ($('input[name="delete_missing_logic"]:checked').val() == 'import' && $('input[name="delete_missing_action"]:checked').val() == 'keep' && $('input[name="is_send_removed_to_trash"]').is(':checked')) {
			return 1;
		}
		if ($('input[name="delete_missing_logic"]:checked').val() == 'import' && $('input[name="delete_missing_action"]:checked').val() == 'keep' && $('input[name="is_change_post_status_of_removed"]').is(':checked')) {
			return 2;
		}
		if ($('input[name="delete_missing_logic"]:checked').val() == 'import' && $('input[name="delete_missing_action"]:checked').val() == 'remove') {
			return 3;
		}
		if ($('input[name="delete_missing_logic"]:checked').val() == 'all' && $('input[name="delete_missing_action"]:checked').val() == 'keep' && $('input[name="is_send_removed_to_trash"]').is(':checked')) {
			return 4;
		}
		if ($('input[name="delete_missing_logic"]:checked').val() == 'all' && $('input[name="delete_missing_action"]:checked').val() == 'keep' && $('input[name="is_change_post_status_of_removed"]').is(':checked')) {
			return 5;
		}
		if ($('input[name="delete_missing_logic"]:checked').val() == 'all' && $('input[name="delete_missing_action"]:checked').val() == 'remove') {
			return 6;
		}
		return 0;
	}

	function is_valid_delete_missing_options() {
		let is_valid = true;
		if ( $('input[name="is_delete_missing"]').is(':checked') && $('input[name="delete_missing_action"]:checked').val() == 'keep' ) {
			if ( ! $('input[name="is_send_removed_to_trash"]').is(':checked')
				&& ! $('input[name="is_change_post_status_of_removed"]').is(':checked')
				&& ! $('input[name="is_update_missing_cf"]').is(':checked')
				&& ! $('input[name="missing_records_stock_status"]').is(':checked')
			) {
				is_valid = false;
			}
		}
		return is_valid;
	}

	let submit_import_settings = function($button) {

		var saveOnly = $button.hasClass('save_only');

		var hasActiveLicense = $('#scheduling_has_license').val();

		if(hasActiveLicense === '1') {
			hasActiveLicense = true;
		} else {
			hasActiveLicense = false;
		}

		var initialValue = $button.find('.save-text').html();
		var schedulingEnable = $('input[name="scheduling_enable"]:checked').val() == 1;
		if(!hasActiveLicense) {
			if (!$button.data('iunderstand') && schedulingEnable) {
				$('#no-subscription').slideDown();
				$button.find('.save-text').html('I Understand');
				$button.find('.save-text').addClass('wpai-iunderstand');
				$button.find('.save-text').css('left', '100px');
				$button.data('iunderstand', 1);

				openSchedulingAccordeonIfClosed();
				e.preventDefault();
				return;
			} else {
				if(saveOnly) {
					$('#save_only_field').prop('disabled', false);
				}
				$('#wpai-submit-confirm-form').submit();
				return;
			}
		}

		// Don't process scheduling
		if (!hasActiveLicense) {
			if(saveOnly) {
				$('#save_only_field').prop('disabled', false);
			}
			$('#wpai-submit-confirm-form').submit();

			return;
		}

		var validationResponse = pmxiValidateSchedulingForm();
		if (!validationResponse.isValid) {

			openSchedulingAccordeonIfClosed();
			$('html, body').animate({
				scrollTop: $("#scheduling-title").offset().top-100
			}, 500);
			e.preventDefault();
			return false;
		}

		var formData = $('#scheduling-form :input').serializeArray();

		formData.push({name: 'security', value: wp_all_import_security});
		formData.push({name: 'action', value: 'save_import_scheduling'});
		formData.push({name: 'element_id', value: $('#scheduling_import_id').val()});
		formData.push({name: 'scheduling_enable', value: $('input[name="scheduling_enable"]:checked').val()});

		$button.find('.easing-spinner').toggle();

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: formData,
			success: function (response) {
				$button.find('.easing-spinner').toggle();
				$button.find('.save-text').html(initialValue);
				$button.find('.save-text').removeClass('wpai-iunderstand');
				$button.find('svg').show();

				setTimeout(function(){
					if(saveOnly) {
						$('#save_only_field').prop('disabled', false);
					}
					$('#wpai-submit-confirm-form').submit();
				}, 1000);

			},
			error: function () {
				$button.find('.easing-spinner').toggle();
				$button.find('.save-text').html(initialValue);
				$button.find('.save-text').removeClass('wpai-iunderstand');
			}
		});
	}

    $('.wpai-save-scheduling-button, .wpai-save-scheduling-button-blue').on('click', function (e) {
		// Validate delete missing options.
		let notice_type = get_delete_missing_notice_type();

		if ( ! is_valid_delete_missing_options() ) {
			$('.delete-missing-error').removeClass('hidden');
			$('.switcher-target-delete_missing_action_keep').addClass('delete-missing-error-wrapper');
			return;
		}

		let $this = $(this);
		// Show notice if any.
		if (notice_type) {
			$('.confirmation-modal-' + notice_type).find('.status_of_removed').html($('select[name="status_of_removed"]').val());
			$('.confirmation-modal-' + notice_type).dialog({
				resizable: false,
				height: "auto",
				width: 550,
				modal: true,
				draggable: false,
				closeText: '',
				classes: {
					"ui-dialog": "wpai-warning-check"
				},
				buttons: {
					"Confirm": {
						click: function() {

							let confirm_field = $('#confirm-settings-' + notice_type);

							let confirm_text = confirm_field.val();

							if (confirm_text !== 'I HAVE BACKUPS') {

								if (confirm_text.length === 0) {
									alert('Please type the confirmation message.');
								} else {
									alert('Please double-check that the confirmation message has been typed as required.');
								}

								confirm_field.addClass('confirm-error');

								return false;
							}

							$( this ).dialog( "close" );

							submit_import_settings($this);
						},
						text: 'Confirm',
						class: 'wpai-warning-confirm-button'
					},
					"Cancel": {
						click: function() {
							$( this ).dialog( "close" );
						},
						text: 'Cancel',
						class: 'wpai-warning-cancel-button'
					}
				}
			});
		} else {
			submit_import_settings($this);
		}
    });

});})(jQuery);
