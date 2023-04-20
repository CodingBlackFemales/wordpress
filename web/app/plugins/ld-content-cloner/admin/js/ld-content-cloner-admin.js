/**
 * This file is used to implement the successive AJAX functionality for the content cloner plugin.
 *
 * @package Content Cloner.
 */

(function($) {
	'use strict';

	$( document ).ready(
		function() {

			$( '.carousel' ).carousel(
				{
					interval: 1000 * 10
				}
			);
			var old_course_id = 0;
			var new_course_id = 0;
			var og_course_id  = 0;
			var action        = "";

			var og_group_id  = 0;
			var new_group_id = 0;

			var curriculum_data = "";

			var curr_lesson_ind = 0;
			var next_lesson     = 0;

			var curr_quiz_ind = 0;
			var next_quiz     = 0;
			function setCookie(cname, cvalue, exdays) {
				var d = new Date();
				d.setTime( d.getTime() + (exdays * 24 * 60 * 60 * 1000) );
				var expires     = "expires=" + d.toUTCString();
				document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
			}
			function getCookie(cname) {
				var name          = cname + "=";
				var decodedCookie = decodeURIComponent( document.cookie );
				var ca            = decodedCookie.split( ';' );
				var cookie_length = ca.length;
				for (var i = 0; i < cookie_length; i++) {
					var c = ca[i];
					while (c.charAt( 0 ) == ' ') {
						c = c.substring( 1 );
					}
					if (c.indexOf( name ) == 0) {
						return c.substring( name.length, c.length );
					}
				}
				return "";
			}
			$( '.ldcc-clone-course' ).click(
				function(e) {
					$( '.wdm_leftwrap' ).removeAttr( 'style' );
					e.preventDefault();
					if ("" === getCookie( 'show_data_upgrade_notice' )) {
						alert( "If you face any issues after cloning the course. \n(1) Go to LearnDash LMS > Settings > Data Upgrades \n(2) Uncheck the Process Mismatched Questions only? checkbox. \n(3) Now run all of the upgrades on the page.\n(4) Try Cloning the course again." );
						setCookie( 'show_data_upgrade_notice', 'yes' );
					}
					var originalContent;
					$( '#ldcc-dialog' ).dialog(
						{
							modal: true,
							closeOnEscape: false,
							draggable: false,
							resizable: false,
							minWidth: 500,
							// minHeight: 400,//.
							open: function(event, ui) {
								originalContent = $( "#ldcc-dialog" ).html();
								$( "#ldcc-dialog" ).removeClass( 'hidden' );
								$( ".ui-dialog-titlebar-close", ui.dialog | ui ).addClass( 'hide_extra_close' );
								// var win = $(window);
							    // $(this).parent().css({
							    //     position: 'fixed',
							    //     left: (win.width() - $(this).parent().outerWidth()) / 2,
							    //     top: (win.height() - $(this).parent().outerHeight()) / 2
							    // });
							},
							close: function(event, ui) {
								$( "#ldcc-dialog" ).html( originalContent );
								$( "#ldcc-dialog" ).addClass( 'hidden' );
								// window.location.reload();//.
							}
						}
					);
					var course_id = og_course_id = $( this ).data( 'course-id' );
					var course    = $( this ).data( 'course' );

					var course_title = $( this ).parents( 'td.title.column-title' ).find( 'strong a.row-title' ).text();

					$( '#ldcc-dialog #ldcc_clone_status' ).append( "<div class='ldcc-course-progress'> <span>" + ldcc_js_data.course_label + " - " + course_title + "</span> <img src='" + ldcc_js_data.image_base_url + "loader.gif' /></div>" );
					action = "duplicate_course_new";
					$.ajax(
						{
							method: "POST",
							url: ldcc_js_data.adm_ajax_url,
							data: {
								course: course,
								course_id: course_id,
								action: action,
							},
							success: function(result) {
								var res = JSON.parse( result );
								if (res.success) {
									new_course_id   = res.success.new_course_id;
									old_course_id   = res.success.old_course_id;
									curriculum_data = res.success.c_data;
									$( '#ldcc-dialog .ldcc-course-progress img' ).attr( "src", ldcc_js_data.image_base_url + "tick.png" );
									$( '#ldcc-dialog' ).trigger( "course_post_created" );
								} else {
									$( '#ldcc-dialog #ldcc_clone_status' ).append( "<div class='ldcc-error''>" + res.error + "</div>" );
								}
							}
						}
					);
				}
			);

			$( '.ldcc-clone-group' ).click(
				function(e) {
					$( '.wdm_leftwrap' ).removeAttr( 'style' );
					e.preventDefault();
					$( '#ldcc-group-dialog' ).dialog(
						{
							modal: true,
							closeOnEscape: false,
							draggable: false,
							resizable: false,
							minWidth: 500,
							// minHeight: 400,//.
							open: function(event, ui) {
								// Commented this as it was hiding the close button for group cloning.
								// $(".ui-dialog-titlebar-close", ui.dialog | ui).hide();//.
								$( "#ldcc-group-dialog" ).removeClass( 'hidden' );
								$( ".ui-dialog-titlebar-close", ui.dialog | ui ).addClass( 'hide_extra_close' );
								// var win = $(window);
							 //    $(this).parent().css({
							 //        position: 'fixed',
							 //        left: (win.width() - $(this).parent().outerWidth()) / 2,
							 //        top: (win.height() - $(this).parent().outerHeight()) / 2
							 //    });
							},
							close: function(event, ui) {
								$( "#ldcc-group-dialog" ).addClass( 'hidden' );
								// window.location.reload();//.
							}
						}
					);
					var group_id = og_group_id = $( this ).data( 'group-id' );
					var group    = $( this ).data( 'group' );

					var group_title = $( this ).parents( 'td.title.column-title' ).find( 'strong a.row-title' ).text();

					$( '#ldcc-group-dialog #ldcc_clone_status' ).append( "<div class='ldcc-course-progress'> <span>" + group_title + "</span> <img src='" + ldcc_js_data.image_base_url + "loader.gif' /></div>" );
					$.ajax(
						{
							method: "POST",
							url: ldcc_js_data.adm_ajax_url,
							data: {
								group: group,
								group_id: group_id,
								action: "duplicate_group",
							},
							success: function(result) {
								var res = JSON.parse( result );
								if (res.success) {
									new_group_id    = res.success.new_group_id;
									curriculum_data = res.success.c_data;
									$( '#ldcc-group-dialog .ldcc-course-progress img' ).attr( "src", ldcc_js_data.image_base_url + "tick.png" );
									$( '#ldcc-group-dialog' ).trigger( "group_clone_completed" );
								} else {
									$( '#ldcc-group-dialog #ldcc_clone_status' ).append( "<div class='ldcc-error''>" + res.error + "</div>" );
								}
							}
						}
					);
				}
			);

			$( '#ldcc-dialog' ).on(
				"course_post_created",
				function() {

					if ( ! $.isEmptyObject( curriculum_data )) {
						if (curriculum_data.lesson.length || curriculum_data.quiz.length) {
							curr_lesson_ind = 0;
							next_lesson     = 0;
							if (curriculum_data.lesson.length) {
								next_lesson = curriculum_data.lesson[0][0];
							}
							$( '#ldcc-dialog' ).trigger( "create_lesson" );
						} else {
							$( '#ldcc-dialog #ldcc_clone_status' ).append( "<div>" + ldcc_js_data.no_content_text + "</div>" );
							$( '#ldcc-dialog' ).trigger( "course_clone_completed" );
						}
					} else {
						$( '#ldcc-dialog #ldcc_clone_status' ).append( "<div>" + ldcc_js_data.no_content_text + "</div>" );
						$( '#ldcc-dialog' ).trigger( "course_clone_completed" );
					}

				}
			);

			$( '#ldcc-dialog' ).on(
				"create_lesson",
				function() {
					if (curr_lesson_ind <= (curriculum_data.lesson.length - 1)) {
						$( '#ldcc-dialog #ldcc_clone_status' ).append( "<div class='ldcc-lesson-" + curriculum_data.lesson[curr_lesson_ind][0] + "'> <span>" + curriculum_data.lesson[curr_lesson_ind][1] + "</span> <img src='" + ldcc_js_data.image_base_url + "loader.gif' /> </div>" );

						action = "duplicate_lesson_new";
						$.ajax(
							{
								method: "POST",
								url: ldcc_js_data.adm_ajax_url,
								data: {
									lesson_id: curriculum_data.lesson[curr_lesson_ind][0],
									new_lesson_id: curriculum_data.lesson[curr_lesson_ind][2],
									topic_lesson_id: curriculum_data.lesson[curr_lesson_ind][3],
									old_course_id: old_course_id,
									course_id: new_course_id,
									ld_builder_settings: ldcc_js_data.ld_builder_settings,
									action: action,
								},
								success: function(result) {
									var res = JSON.parse( result );
									if (res.success) {
										$( '#ldcc-dialog .ldcc-lesson-' + curriculum_data.lesson[curr_lesson_ind][0] + ' img' ).attr( "src", ldcc_js_data.image_base_url + "tick.png" );
										curr_lesson_ind += 1;
										$( '#ldcc-dialog' ).trigger( "create_lesson" );
									} else {
										$( '#ldcc-dialog #ldcc_clone_status' ).append( "<div class='ldcc-error''>" + res.error + "</div>" );
									}
								}
							}
						);
					} else {
						curr_quiz_ind = 0;
						next_quiz     = 0;
						if (curriculum_data.quiz.length) {
							next_quiz = curriculum_data.quiz[0][0];
						}
						if (next_quiz !== 0) {
							$( '#ldcc-dialog' ).trigger( "create_quiz" );
						} else {
							$( '#ldcc-dialog' ).trigger( "course_clone_completed" );
						}
					}
				}
			);

			$( '#ldcc-dialog' ).on(
				"create_quiz",
				function() {
					if (curr_quiz_ind <= (curriculum_data.quiz.length - 1)) {
						$( '#ldcc-dialog #ldcc_clone_status' ).append( "<div class='ldcc-quiz-" + curriculum_data.quiz[curr_quiz_ind][0] + "'> <span>" + curriculum_data.quiz[curr_quiz_ind][1] + " </span> <img src='" + ldcc_js_data.image_base_url + "loader.gif' /> </div>" );
						action = "duplicate_quiz_new";
						$.ajax(
							{
								method: "POST",
								url: ldcc_js_data.adm_ajax_url,
								data: {
									course_id: new_course_id,
									old_course_id: old_course_id,
									quiz_id: curriculum_data.quiz[curr_quiz_ind][0],
									new_quiz_id: curriculum_data.quiz[curr_quiz_ind][2],
									lesson_id: curriculum_data.quiz[curr_quiz_ind][3],
									ld_builder_settings: ldcc_js_data.ld_builder_settings,
									action: action,
								},
								success: function(result) {
									var res = JSON.parse( result );
									if (res.success) {
										$( '#ldcc-dialog .ldcc-quiz-' + curriculum_data.quiz[curr_quiz_ind][0] + ' img' ).attr( "src", ldcc_js_data.image_base_url + "tick.png" );
										curr_quiz_ind += 1;
										$( '#ldcc-dialog' ).trigger( "create_quiz" );
									} else {
										$( '#ldcc-dialog #ldcc_clone_status' ).append( "<div class='ldcc-error''>" + res.error + "</div>" );
									}
								}
							}
						);
					} else {
						$( '#ldcc-dialog' ).trigger( "course_clone_completed" );
					}
				}
			);

			$( '#ldcc-dialog' ).on(
				"course_clone_completed",
				function() {
					$( '#ldcc-dialog .ldcc-success .ldcc-course-link' ).attr( "href", ldcc_js_data.adm_post_url + "?action=edit&post=" + new_course_id );
					$( '#ldcc-dialog .ldcc-success .ldcc-course-rename-link' ).attr( "href", ldcc_js_data.adm_ldbr_url + "&ldbr-select-course=" + new_course_id );
					$( '#ldcc-dialog .ldcc-success' ).show();
					$( '#ldcc-dialog .ldcc-notice' ).show();
				}
			);

			$( '#ldcc-group-dialog' ).on(
				"group_clone_completed",
				function() {
					$( '#ldcc-group-dialog .ldcc-success .ldcc-group-link' ).attr( "href", ldcc_js_data.adm_post_url + "?action=edit&post=" + new_group_id );
					$( '#ldcc-group-dialog .ldcc-success' ).show();
					$( '#ldcc-group-dialog .ldcc-notice' ).show();
				}
			);

		}
	);

})( jQuery );
