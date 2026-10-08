// Course Registered
jQuery( function() {
	if (jQuery( '.ld_course_info .ld_course_info_mycourses_list .ld-course-registered-pager-container a' ).length ) {
		jQuery( '.ld_course_info .ld_course_info_mycourses_list' ).on( 'click', '.ld-course-registered-pager-container a', ld_course_registered_pager_handler );

		function ld_course_registered_pager_handler( e ) {
			e.preventDefault();

			var pager_div = jQuery(e.currentTarget).parents('.learndash-pager');

			jQuery(pager_div).addClass('ld-loading');

			if (typeof pager_div === 'undefined')
				return;
			var pager_nonce = jQuery(pager_div).data('nonce');

			var paged = jQuery( e.currentTarget ).data('paged');

			var parent_div = jQuery( e.currentTarget ).parents('.ld_course_info' );
			if ( typeof parent_div === 'undefined')
				return;

			var shortcode_atts = jQuery( parent_div ).data( 'shortcode-atts' );
			if ( typeof parent_div === 'undefined')
				return;

			var post_data = {
				'action': 'ld_course_registered_pager',
				'nonce' : pager_nonce,
				'paged': paged,
				'shortcode_atts': shortcode_atts
			};

			jQuery.ajax({
				type: "POST",
				url: sfwd_data.ajaxurl,
				dataType: "json",
				cache: false,
				data: post_data,
				error: function(jqXHR, textStatus, errorThrown ) {
					//console.log('init: error HTTP Status['+jqXHR.status+'] '+errorThrown);
				},
				success: function( reply_data ) {
					if ( typeof reply_data !== 'undefined') {
						if ( typeof reply_data['content'] !== 'undefined' ) {
							jQuery('.ld_course_info_mycourses_list .ld-courseregistered-content-container', parent_div ).html( reply_data['content'] );
							jQuery(pager_div).removeClass('ld-loading');
						}

						if ( typeof reply_data['pager'] !== 'undefined' ) {
							jQuery('.ld_course_info_mycourses_list .ld-course-registered-pager-container', parent_div ).html( reply_data['pager'] );

							jQuery(pager_div).removeClass('ld-loading');

							learndash_scroll_to_parent(jQuery('.ld_course_info_mycourses_list', parent_div));

							/**
							 * Send out a triggered event for externals to process.
							 * @since 2.5.9
							 */
							jQuery(window).trigger('learndash_pager_content_changed', { parent_div: parent_div });
						}
					}
				}
			});
		}
	}
});

// Course Progress
jQuery( function() {
	if (jQuery( '.ld_course_info .course_progress_details .ld-course-progress-pager-container a' ).length ) {
		jQuery( '.ld_course_info .course_progress_details' ).on( 'click', '.ld-course-progress-pager-container a', ld_course_content_pager_handler );

		function ld_course_content_pager_handler( e ) {
			e.preventDefault();

			var pager_div = jQuery(e.currentTarget).parents('.learndash-pager');

			jQuery(pager_div).addClass('ld-loading');

			if (typeof pager_div === 'undefined')
				return;
			var pager_nonce = jQuery(pager_div).data('nonce');

			var paged = jQuery( e.currentTarget ).data('paged');

			var parent_div = jQuery( e.currentTarget ).parents('.ld_course_info' );
			if ( typeof parent_div === 'undefined')
				return;

			var shortcode_atts = jQuery( parent_div ).data( 'shortcode-atts' );
			if ( typeof parent_div === 'undefined')
				return;

			var post_data = {
				'action': 'ld_course_progress_pager',
				'nonce': pager_nonce,
				'paged': paged,
				'shortcode_atts': shortcode_atts
			};

			jQuery.ajax({
				type: "POST",
				url: sfwd_data.ajaxurl,
				dataType: "json",
				cache: false,
				data: post_data,
				error: function(jqXHR, textStatus, errorThrown ) {
					//console.log('init: error HTTP Status['+jqXHR.status+'] '+errorThrown);
				},
				success: function( reply_data ) {
					if ( typeof reply_data !== 'undefined') {
						if ( typeof reply_data['content'] !== 'undefined' ) {
							jQuery('.course_progress_details .ld-course-progress-content-container', parent_div).html( reply_data['content'] );
							jQuery(pager_div).removeClass('ld-loading');
						}

						if ( typeof reply_data['pager'] !== 'undefined' ) {
							jQuery('.course_progress_details .ld-course-progress-pager-container', parent_div).html( reply_data['pager'] );
							jQuery(pager_div).removeClass('ld-loading');

							learndash_scroll_to_parent(jQuery('.course_progress_details', parent_div));

							/**
							 * Send out a triggered event for externals to process.
							 * @since 2.5.9
							 */
							jQuery(window).trigger('learndash_pager_content_changed', { parent_div: parent_div });
						}
					}
				}
			});
		}
	}
});

// Quiz Progress
jQuery( function() {

	if (jQuery( '.ld_course_info .ld-quiz-progress-pager-container a' ).length ) {
		jQuery( '.ld_course_info .quiz_progress_details' ).on( 'click', '.ld-quiz-progress-pager-container a', ld_quiz_content_pager_handler );

		function ld_quiz_content_pager_handler( e ) {
			e.preventDefault();

			var pager_div = jQuery(e.currentTarget).parents('.learndash-pager');

			jQuery(pager_div).addClass('ld-loading');

			if (typeof pager_div === 'undefined')
				return;
			var pager_nonce = jQuery(pager_div).data('nonce');

			var paged = jQuery( e.currentTarget ).data('paged');

			var parent_div = jQuery( e.currentTarget ).parents('.ld_course_info' );
			if ( typeof parent_div === 'undefined')
				return;

			var shortcode_atts = jQuery( parent_div ).data( 'shortcode-atts' );
			if ( typeof parent_div === 'undefined')
				return;

			var post_data = {
				'action': 'ld_quiz_progress_pager',
				'nonce': pager_nonce,
				'paged': paged,
				'shortcode_atts': shortcode_atts
			};

			jQuery.ajax({
				type: "POST",
				url: sfwd_data.ajaxurl,
				dataType: "json",
				cache: false,
				data: post_data,
				error: function(jqXHR, textStatus, errorThrown ) {
					//console.log('init: error HTTP Status['+jqXHR.status+'] '+errorThrown);
				},
				success: function( reply_data ) {
					if ( typeof reply_data !== 'undefined') {
						if ( typeof reply_data['content'] !== 'undefined' ) {
							jQuery('#quiz_progress_details .ld-quiz-progress-content-container', parent_div).html( reply_data['content'] );
						}

						if ( typeof reply_data['pager'] !== 'undefined' ) {
							jQuery('#quiz_progress_details .ld-quiz-progress-pager-container', parent_div).html( reply_data['pager'] );

							learndash_scroll_to_parent(jQuery('#quiz_progress_details', parent_div));

							/**
							 * Send out a triggered event for externals to process.
							 * @since 2.5.9
							 */
							jQuery(window).trigger('learndash_pager_content_changed', { parent_div: parent_div });
						}
					}
				}
			});
		}
	}

});

// Course List Shortcode
jQuery( function() {

	if ( jQuery( '.ld-course-list-content .learndash-pager-course_list a' ).length ) {
		jQuery( '.ld-course-list-content' ).on( 'click', '.learndash-pager-course_list a', ld_course_list_content_pager_handler );

		function ld_course_list_content_pager_handler( e ) {
			e.preventDefault();

			var pager_div = jQuery(e.currentTarget).parents('.learndash-pager');

			jQuery(pager_div).addClass('ld-loading');

			if (typeof pager_div === 'undefined')
				return;
			var pager_nonce = jQuery(pager_div).data('nonce');

			var parent_div = jQuery( e.currentTarget ).parents('.ld-course-list-content' );
			if ( typeof parent_div === 'undefined')
				return;

			var shortcode_atts = jQuery( parent_div ).data( 'shortcode-atts' );
			if (typeof shortcode_atts === 'undefined')
				return;

			var paged = jQuery( e.currentTarget ).data('paged');

			var post_data = {
				'action': 'ld_course_list_shortcode_pager',
				'nonce': pager_nonce,
				'paged': paged,
				'shortcode_atts': shortcode_atts
			};

			jQuery.ajax({
				type: "POST",
				url: sfwd_data.ajaxurl,
				dataType: "json",
				cache: false,
				data: post_data,
				error: function(jqXHR, textStatus, errorThrown ) {
					//console.log('init: error HTTP Status['+jqXHR.status+'] '+errorThrown);
				},
				success: function( reply_data ) {
					if ( typeof reply_data !== 'undefined') {
						if ( typeof reply_data['content'] !== 'undefined' ) {
							jQuery( parent_div ).html( reply_data['content'] );

							jQuery(pager_div).removeClass('ld-loading');

							learndash_scroll_to_parent(parent_div);

							/**
							 * Send out a triggered event for externals to process.
							 * @since 2.5.9
							 */
							jQuery(window).trigger('learndash_pager_content_changed', { parent_div: parent_div } );
						}
					}
				}
			});
		}
	}

});

// Course Navigation Widget
jQuery( function() {

	if ( jQuery( '.widget_ldcoursenavigation .learndash-pager-course_navigation_widget a' ).length ) {
		jQuery( '.widget_ldcoursenavigation' ).on( 'click', '.learndash-pager-course_navigation_widget a', ld_course_navigation_widget_pager_handler );

		function ld_course_navigation_widget_pager_handler( e ) {
			e.preventDefault();

			var pager_div = jQuery(e.currentTarget).parents('.learndash-pager');
			if (typeof pager_div === 'undefined')
				return;
			var pager_nonce = jQuery(pager_div).data('nonce');

			var parent_div = jQuery( e.currentTarget ).parents('.course_navigation' );
			if ( typeof parent_div === 'undefined')
				return;

			var widget_data = jQuery( parent_div ).data( 'widget_instance' );
			if ( typeof widget_data === 'undefined')
				return;

			var paged = jQuery( e.currentTarget ).data('paged');

			var post_data = {
				'action': 'ld_course_navigation_pager',
				'nonce': pager_nonce,
				'paged': paged,
				'widget_data': widget_data
			};

			jQuery.ajax({
				type: "POST",
				url: sfwd_data.ajaxurl,
				dataType: "json",
				cache: false,
				data: post_data,
				error: function(jqXHR, textStatus, errorThrown ) {
					//console.log('init: error HTTP Status['+jqXHR.status+'] '+errorThrown);
				},
				success: function( reply_data ) {
					if ( typeof reply_data !== 'undefined') {
						if ( ( typeof reply_data['content'] !== 'undefined' ) && ( reply_data['content'].length ) ) {
							jQuery( parent_div ).html( reply_data['content'] );

							learndash_scroll_to_parent(parent_div);

							/**
							 * Send out a triggered event for externals to process.
							 * @since 2.5.9
							 */
							jQuery(window).trigger('learndash_pager_content_changed', { parent_div: parent_div });
						}
					}
				}
			});
		}
	}
});


// Course Navigation Admin Widget
jQuery( function() {

	if ( jQuery( '#learndash_course_navigation_admin_meta .course_navigation .learndash-pager a' ).length ) {
		jQuery( '#learndash_course_navigation_admin_meta' ).on( 'click', '.course_navigation .learndash-pager a', ld_course_navigation_widget_pager_handler );

		function ld_course_navigation_widget_pager_handler( e ) {
			e.preventDefault();

			var pager_div = jQuery(e.currentTarget).parents('.learndash-pager');
			if (typeof pager_div === 'undefined')
				return;
			var pager_nonce = jQuery(pager_div).data('nonce');

			var parent_div = jQuery( e.currentTarget ).parents('.course_navigation' );
			if ( typeof parent_div === 'undefined')
				return;

			var widget_data = jQuery( parent_div ).data( 'widget_instance' );
			if ( typeof widget_data === 'undefined')
				return;

			var paged = jQuery( e.currentTarget ).data('paged');

			var post_data = {
				'action': 'ld_course_navigation_admin_pager',
				'nonce': pager_nonce,
				'paged': paged,
				'widget_data': widget_data
			};

			jQuery.ajax({
				type: "POST",
				url: sfwd_data.ajaxurl,
				dataType: "json",
				cache: false,
				data: post_data,
				error: function(jqXHR, textStatus, errorThrown ) {
					//console.log('init: error HTTP Status['+jqXHR.status+'] '+errorThrown);
				},
				success: function( reply_data ) {
					if ( typeof reply_data !== 'undefined') {
						if ( ( typeof reply_data['content'] !== 'undefined' ) && ( reply_data['content'].length ) ) {
							jQuery( parent_div ).html( reply_data['content'] );

							learndash_scroll_to_parent(parent_div);

							/**
							 * Send out a triggered event for externals to process.
							 * @since 2.5.9
							 */
							jQuery(window).trigger('learndash_pager_content_changed', { parent_div: parent_div });
						}
					}
				}
			});
		}
	}
});

// Quiz Questions Navigation Admin Widget
jQuery( function () {

	if (jQuery('#learndash_admin_quiz_navigation .quiz_navigation .learndash-pager a').length) {
		jQuery('#learndash_admin_quiz_navigation').on('click', '.quiz_navigation .learndash-pager a', ld_quiz_navigation_widget_pager_handler);

		function ld_quiz_navigation_widget_pager_handler(e) {
			e.preventDefault();

			var pager_div = jQuery(e.currentTarget).parents('.learndash-pager');
			if (typeof pager_div === 'undefined')
				return;
			var pager_nonce = jQuery(pager_div).data('nonce');

			var parent_div = jQuery(e.currentTarget).parents('.quiz_navigation');
			if (typeof parent_div === 'undefined')
				return;

			var widget_data = jQuery(parent_div).data('widget_instance');
			if (typeof widget_data === 'undefined')
				return;

			var paged = jQuery(e.currentTarget).data('paged');

			var post_data = {
				'action': 'ld_quiz_navigation_admin_pager',
				'nonce': pager_nonce,
				'paged': paged,
				'widget_data': widget_data
			};

			jQuery.ajax({
				type: "POST",
				url: sfwd_data.ajaxurl,
				dataType: "json",
				cache: false,
				data: post_data,
				error: function (jqXHR, textStatus, errorThrown) {
					//console.log('init: error HTTP Status['+jqXHR.status+'] '+errorThrown);
				},
				success: function (reply_data) {
					if (typeof reply_data !== 'undefined') {
						if ((typeof reply_data['content'] !== 'undefined') && (reply_data['content'].length)) {
							jQuery(parent_div).html(reply_data['content']);

							learndash_scroll_to_parent(parent_div);

							/**
							 * Send out a triggered event for externals to process.
							 * @since 2.5.9
							 */
							jQuery(window).trigger('learndash_pager_content_changed', { parent_div: parent_div });
						}
					}
				}
			});
		}
	}
});

function learndash_scroll_to_parent( parent_div ) {
	if ( ( parent_div != '' ) && ( jQuery( parent_div).length ) ) {

		var elementTop = jQuery(parent_div).offset().top;
		var viewportTop = jQuery(window).scrollTop();

		if (elementTop < viewportTop ) {
			jQuery('html,body').animate({
				scrollTop: elementTop
			}, 750 );
		}
	}
}
/**
 * Example event trigger handler when the page AJAX finishes and the
 * new content is move in place.
 *
 * Within the args object is an element 'parent_div' to reference the
 * outer parent div of the paged element. The args object may contain
 * other elements in the future.
 *
 * The folowing code is an example if scolling to the top of the parent
 * div IF it ia above the top of the current viewport.
 */
/*
jQuery(window).on('learndash_pager_content_changed', function (e, args) {
	if ( typeof args['parent_div'] !== 'undefined') {

		var win = jQuery(window);
		var winScrollPosition = win.scrollTop();
		var objOffsetTop = jQuery(args['parent_div']).offset().top;

		if (winScrollPosition > objOffsetTop) {
			jQuery('html,body').animate({
				scrollTop: objOffsetTop
			}, 750);
		}

		jQuery(window).trigger('resize');
	}
});
*/
