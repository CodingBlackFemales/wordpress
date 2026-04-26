if ( typeof flip_expand_collapse === 'undefined' ) {
	function flip_expand_collapse( what, id ) {
		//console.log(id + ':' + document.getElementById( 'list_arrow.flippable-'+id).className);
		if ( jQuery( what + '-' + id + ' .list_arrow.flippable' ).hasClass( 'expand' ) ) {
			jQuery( what + '-' + id + ' .list_arrow.flippable' ).removeClass( 'expand' );
			jQuery( what + '-' + id + ' .list_arrow.flippable' ).addClass( 'collapse' );
			jQuery( what + '-' + id + ' .flip' ).slideUp();
		} else {
			jQuery( what + '-' + id + ' .list_arrow.flippable' ).removeClass( 'collapse' );
			jQuery( what + '-' + id + ' .list_arrow.flippable' ).addClass( 'expand' );
			jQuery( what + '-' + id + ' .flip' ).slideDown();
		}
		return false;
	}
}

if ( typeof flip_expand_all === 'undefined' ) {
	function flip_expand_all( what ) {
		jQuery( what + ' .list_arrow.flippable' ).removeClass( 'collapse' );
		jQuery( what + ' .list_arrow.flippable' ).addClass( 'expand' );
		jQuery( what + ' .flip' ).slideDown();
		return false;
	}
}

if ( typeof flip_collapse_all === 'undefined' ) {
	function flip_collapse_all( what ) {
		jQuery( what + ' .list_arrow.flippable' ).removeClass( 'expand' );
		jQuery( what + ' .list_arrow.flippable' ).addClass( 'collapse' );
		jQuery( what + ' .flip' ).slideUp();
		return false;
	}
}

Object.defineProperty( String.prototype, 'toHHMMSS', {
	value() {
		const secNumb = parseInt( this, 10 );
		let hours = Math.floor( secNumb / 3600 );
		let minutes = Math.floor( ( secNumb - hours * 3600 ) / 60 );
		let seconds = secNumb - ( hours * 3600 ) - ( minutes * 60 );

		if ( hours < 10 ) {
			hours = '0' + hours;
		}

		if ( minutes < 10 ) {
			minutes = '0' + minutes;
		}

		if ( seconds < 10 ) {
			seconds = '0' + seconds;
		}

		return hours + ':' + minutes + ':' + seconds;
	},
	enumerable: false,
} );

jQuery( function() {
	if (jQuery('.learndash_timer').length) {
		/**
		 * Formats the timer seconds into a string.
		 *
		 * @param {number} timerSeconds The number of seconds to format.
		 *
		 * @return {string} The formatted string.
		 */
		function learndashTimerToString(timerSeconds) {
			const timerLabel = jQuery('.ld-navigation__progress-timer-label');

			// If the timer label is not present, use the old format.

			if (timerLabel.length <= 0) {
				return timerSeconds.toString().toHHMMSS();
			}

			// Less than 60 seconds.

			if (timerSeconds <= 60) {
				return `${timerSeconds}s`;
			}

			// Less than 1 hour.

			if (timerSeconds < 3600) {
				const minutes = Math.floor(timerSeconds / 60);
				const seconds = timerSeconds % 60;

				return `${minutes}m ${seconds}s`;
			}

			// More than 1 hour.

			return timerSeconds.toString().toHHMMSS();
		}

		jQuery('.learndash_timer').each(function (idx, item) {
			var timer_el = jQuery(item);
			const $tooltip = timer_el.closest('.ld-tooltip');

			var timer_seconds = timer_el.data('timer-seconds');
			var button_ref = timer_el.data('button');

			if (
				typeof button_ref !== 'undefined' &&
				jQuery(button_ref).length
			) {
				var timer_button_el = jQuery(button_ref);

				if (
					typeof timer_seconds !== 'undefined' &&
					typeof timer_button_el !== 'undefined'
				) {
					timer_button_el.attr('disabled', true);

					timer_seconds = parseInt(timer_seconds);

					var cookie_key = timer_el.attr('data-cookie-key');

					if (typeof cookie_key !== 'undefined') {
						var cookie_name =
							'learndash_timer_cookie_' + cookie_key;
					} else {
						var cookie_name = 'learndash_timer_cookie';
					}

					var cookie_timer_seconds = jQuery.cookie(cookie_name);

					if (typeof cookie_timer_seconds !== 'undefined') {
						timer_seconds = parseInt(cookie_timer_seconds);
					}
					//jQuery.removeCookie( cookie_name );

					const timerLabel = jQuery(
						'.ld-navigation__progress-timer-label'
					);

					if (timer_seconds >= 1) {
						// Show the first second.
						timer_el.html(learndashTimerToString(timer_seconds));

						var learndash_timer_var = setInterval(function () {
							timer_seconds = timer_seconds - 1;

							var time_display =
								learndashTimerToString(timer_seconds);
							timer_el.html(time_display);
							if (timer_seconds <= 0) {
								clearInterval(learndash_timer_var);
								timer_button_el.attr('disabled', false);
								timer_el.html('');
								timer_el.hide();
								jQuery.cookie(cookie_name, 0);

								timer_button_el.trigger(
									'learndash-time-finished'
								);

								if (timerLabel.length) {
									timerLabel.text(
										timerLabel.data('timer-complete-label')
									);
								}

								if ($tooltip.length) {
									$tooltip.find('[role="tooltip"]').hide();
								}
							}
							// Store the timer state (value) into a cookie. This is done if the page reloads the student can resume
							// the time instead of restarting.
							jQuery.cookie(cookie_name, timer_seconds);
						}, 1000);
					} else {
						timer_button_el.attr('disabled', false);
						timer_el.html('');
						jQuery.cookie(cookie_name, 0);
						//jQuery.removeCookie( cookie_name );

						if (timerLabel.length) {
							timerLabel.text(
								timerLabel.data('timer-complete-label')
							);
						}

						if ($tooltip.length) {
							$tooltip.find('[role="tooltip"]').hide();
						}
					}
				}
			}
		});
	}
} );

jQuery( function() {
	if ( typeof sfwd_data !== 'undefined' ) {
		if ( typeof sfwd_data.json !== 'undefined' ) {
			sfwd_data = sfwd_data.json.replace( /&quot;/g, '"' );
			sfwd_data = JSON.parse( sfwd_data );
		}
	}

	jQuery( '#ld_course_info' ).on( 'click', 'a.user_statistic', learndash_show_user_statistic );
	jQuery( '#learndash_profile' ).on( 'click', 'a.user_statistic', learndash_show_user_statistic );

	function learndash_show_user_statistic( e ) {
		e.preventDefault();

		var refId = jQuery( this ).data( 'ref_id' );
		var quizId = jQuery( this ).data( 'quiz_id' );
		var userId = jQuery( this ).data( 'user_id' );
		var statistic_nonce	= jQuery( this ).data( 'statistic_nonce' );
		var post_data = {
			action: 'wp_pro_quiz_admin_ajax_statistic_load_user',
			func: 'statisticLoadUser',
			data: {
				quizId: quizId,
				userId: userId,
				refId: refId,
				statistic_nonce: statistic_nonce,
				avg: 0,
			},
		};

		jQuery( '#wpProQuiz_user_overlay, #wpProQuiz_loadUserData' ).show();
		var content = jQuery( '#wpProQuiz_user_content' ).hide();

		jQuery.ajax( {
			type: 'POST',
			url: sfwd_data.ajaxurl,
			dataType: 'json',
			cache: false,
			data: post_data,
			error: function( jqXHR, textStatus, errorThrown ) {
			},
			success: function( reply_data ) {
				if ( typeof reply_data.html !== 'undefined' ) {
					content.html( reply_data.html );
					jQuery( 'a.wpProQuiz_update', content ).remove();
					jQuery( 'a#wpProQuiz_resetUserStatistic', content ).remove();
					jQuery( 'body' ).trigger( 'learndash-statistics-contentchanged' );
					jQuery( '#wpProQuiz_user_content' ).show();
					jQuery( '#wpProQuiz_loadUserData' ).hide();
					content.find( '.statistic_data' ).on( 'click', function() {
						jQuery( this ).parents( 'tr' ).next().toggle( 'fast' );
						return false;
					} );
				}
			},
		} );

		jQuery( '#wpProQuiz_overlay_close' ).on( 'click', function() {
			jQuery( '#wpProQuiz_user_overlay' ).hide();
		} );
	}
} );

