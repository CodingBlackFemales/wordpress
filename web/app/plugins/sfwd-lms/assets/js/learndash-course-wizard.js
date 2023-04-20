window.onload = function() {
	var createHiddenField = function( name, value ) {
		var input = document.createElement( 'input' );
		input.type = 'hidden';
		input.name = name;
		input.value = value;
		return input;
	};
	var removeChildren = function( element ) {
		while ( element.firstChild ) {
			element.removeChild( element.firstChild );
		}
	};

	var showExtraPriceTypeFields = function( priceType ) {
		document.getElementById( 'ld_cw_paynow_div' ).style.display = 'none';
		document.getElementById( 'ld_cw_subscribe_div' ).style.display = 'none';

		if ( priceType === 'paynow' ) {
			document.getElementById( 'ld_cw_paynow_div' ).style.display = 'block';
		} else if ( priceType === 'subscribe' ) {
			document.getElementById( 'ld_cw_subscribe_div' ).style.display = 'block';
		}
	};
	var coursePriceSelected = document.querySelector( 'input[name="ld_cw_course_price_type"]:checked' );
	if ( coursePriceSelected ) {
		showExtraPriceTypeFields( coursePriceSelected.value );
	}

	// show extra fields when the user selects a course price type
	var coursePriceTypeRadios = document.getElementsByName( 'ld_cw_course_price_type' );
	if ( coursePriceTypeRadios.length > 0 ) {
		for ( var i = 0; i < coursePriceTypeRadios.length; i++ ) {
			coursePriceTypeRadios[i].addEventListener( 'change', function( event ) {
				showExtraPriceTypeFields( event.target.value );
			} );
		}
	}

	// show services branding when the user fills down the URL field
	var showButtonBranding = function( url ) {
		var buttonLabel = ldCourseWizard.buttons.default.label;
		var buttonImageSrc,
			buttonImageAlt,
			buttonImageClass = null;
		var button = document.getElementById( 'ld_cw_load_data_button' );
		removeChildren( button );

		if ( url.includes( 'youtube.com' ) ) {
			buttonLabel = ldCourseWizard.buttons.youtube.label;
			buttonImageSrc = ldCourseWizard.buttons.youtube.img_src;
			buttonImageAlt = ldCourseWizard.buttons.youtube.img_alt;
			buttonImageClass = ldCourseWizard.buttons.youtube.img_class;
		} else if ( url.includes( 'vimeo.com' ) ) {
			buttonLabel = ldCourseWizard.buttons.vimeo.label;
			buttonImageSrc = ldCourseWizard.buttons.vimeo.img_src;
			buttonImageAlt = ldCourseWizard.buttons.vimeo.img_alt;
			buttonImageClass = ldCourseWizard.buttons.vimeo.img_class;
		} else if ( url.includes( 'wistia.com' ) ) {
			buttonLabel = ldCourseWizard.buttons.wistia.label;
			buttonImageSrc = ldCourseWizard.buttons.wistia.img_src;
			buttonImageAlt = ldCourseWizard.buttons.wistia.img_alt;
			buttonImageClass = ldCourseWizard.buttons.wistia.img_class;
		}

		// add the image if it exists
		if ( buttonImageSrc ) {
			var img = document.createElement( 'img' );
			img.src = buttonImageSrc;
			img.alt = buttonImageAlt;
			img.className = buttonImageClass;
			button.appendChild( img );
		}
		// add the button label
		var label = document.createElement( 'span' );
		label.textContent = buttonLabel;
		button.appendChild( label );
	};
	var playlistUrl = document.getElementById( 'ld_cw_playlist_url' );
	if ( playlistUrl ) {
		playlistUrl.addEventListener( 'keyup',
			function( event ) {
				showButtonBranding( event.target.value );
			}
		);
		playlistUrl.addEventListener( 'change',
			function( event ) {
				showButtonBranding( event.target.value );
			}
		);
		showButtonBranding( playlistUrl.value );
	}

	// billing cycle control
	var billingCycle = document.getElementById( 'ld_cw_course_price_billing_interval' );
	var billingCycleNumber = document.getElementById( 'ld_cw_course_price_billing_number' );
	var maxValue = 0;
	if ( billingCycle && billingCycleNumber ) {
		billingCycle.addEventListener( 'change', function( event ) {
			switch ( event.target.value ) {
				case 'D':
					maxValue = ldCourseWizard.valid_recurring_paypal_day_max;
					break;

				case 'W':
					maxValue = ldCourseWizard.valid_recurring_paypal_week_max;
					break;

				case 'M':
					maxValue = ldCourseWizard.valid_recurring_paypal_month_max;
					break;

				case 'Y':
					maxValue = ldCourseWizard.valid_recurring_paypal_year_max;
					break;

				default:
					maxValue = 0;
					break;
			}
			if ( billingCycleNumber.value > maxValue ) {
				billingCycleNumber.value = maxValue;
			}
			billingCycleNumber.setAttribute( 'max', maxValue );
		} );
	}

	// add event listener for the submit button
	var createCourseButton = document.getElementById( 'ld_cw_create_course_btn' );
	if ( createCourseButton ) {
		createCourseButton.addEventListener( 'click', function() {
			var form = document.getElementById( 'ld_cw_create_course_form' );
			var courseType = document.querySelector( 'input[name="ld_cw_course_price_type"]:checked' ).value;
			if ( form ) {
				form.appendChild(
					createHiddenField( 'course_price_type'
						, courseType )
				);
				form.appendChild(
					createHiddenField( 'course_disable_lesson_progression'
						, document.querySelector( 'input[name="ld_cw_course_progression"]:checked' ).value )
				);

				if ( courseType === 'paynow' ) {
					form.appendChild(
						createHiddenField( 'course_price'
							, document.getElementById( 'ld_cw_course_price_type_paynow_price' ).value )
					);
				} else if ( courseType === 'subscribe' ) {
					form.appendChild(
						createHiddenField( 'course_price'
							, document.getElementById( 'ld_cw_course_price_type_subscribe_price' ).value )
					);
					form.appendChild(
						createHiddenField( 'course_price_billing_number'
							, document.getElementById( 'ld_cw_course_price_billing_number' ).value )
					);
					form.appendChild(
						createHiddenField( 'course_price_billing_interval'
							, document.getElementById( 'ld_cw_course_price_billing_interval' ).value )
					);
				}
				form.submit();
			}
		} );
	}
};
