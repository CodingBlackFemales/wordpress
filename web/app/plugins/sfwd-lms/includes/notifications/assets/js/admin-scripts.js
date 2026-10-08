jQuery( function( $ ) {
	var LD_Notifications = {
		init: function() {
			this.toggle_child_input();
			this.toggle_shortcode_instruction();
			this.submit_disabled_fields();
			this.init_child_field();
			this.init_select2_fields();
			this.init_filter_child_field();
			this.init_filter_select2_fields();
			this.add_conditions_field();
			this.remove_conditions_field();
			this.toggle_conditions_child_field();
			this.init_accordions();
			this.toggle_field_help_text();
		},

		build_select2_args: function( args ) {
			return {
				dropdownAutoWidth: true,
				minimumInputLength: args.hasOwnProperty( 'minimumInputLength' ) ? args.minimumInputLength : null,
				theme: 'learndash',
				width: '100%',
				data: args.hasOwnProperty( 'data' ) && typeof args.data === 'object' && args.data.constructor === Array ? args.data : null,
				disabled: args.hasOwnProperty( 'disabled' ) ? args.disabled : false,
				placeholder: args.hasOwnProperty( 'placeholder' ) ? args.placeholder : null,
				ajax: args.hasOwnProperty( 'ajax' ) ? args.ajax : {
					url: LearnDash_Notifications_Vars.ajaxurl,
					dataType: 'json',
					delay: 250,
					type: 'POST',
					data: function( params ) {
						return {
							action: 'ld_notifications_get_posts_list',
							nonce: LearnDash_Notifications_Vars.nonce,
							post_type: args.post_type,
							keyword: params.term,
							page: params.page || 1,
							course_id: args.hasOwnProperty( 'course_id' ) ? args.course_id : null,
							lesson_id: args.hasOwnProperty( 'lesson_id' ) ? args.lesson_id : null,
							topic_id: args.hasOwnProperty( 'topic_id' ) ? args.topic_id : null,
							quiz_id: args.hasOwnProperty( 'quiz_id' ) ? args.quiz_id : null,
							parent_type: args.hasOwnProperty( 'parent_type' ) ? args.parent_type : null,
							parent_id: args.hasOwnProperty( 'parent_id' ) ? args.parent_id : null,
						}
					},
					processResults: function( data ) {
						const results =  {
							results: data.results,
							pagination: {
								more: data.pagination,
							},
						}

						return results;
					}
				}
			};
		},

		toggle_child_input: function() {
			if ( $( '.ld_notifications_metabox_settings' ).length > 0 ) {
				$( 'select[name="_ld_notifications_trigger"]' ).on( 'change', function( e ) {
					LD_Notifications.update_select_values();

					var option_class = $( this ).val();

					$( '.sfwd_input.' + option_class ).show();
					$( '.sfwd_input.child-input' ).not( '.' + option_class ).hide();
					$( '.sfwd_input.hide_on' ).show();
					$( '.sfwd_input.hide_on_' + option_class ).hide();
				});

				$( window ).on( 'load', function( e ) {
					LD_Notifications.update_select_values_onload();

					var option_class = $( 'select[name="_ld_notifications_trigger"]' ).val();

					$( '.sfwd_input.' + option_class ).show();
					$( '.sfwd_input.child-input' ).not( '.' + option_class ).hide();
					$( '.sfwd_input.hide-empty-select' ).hide();
					$( '.sfwd_input.hide_on' ).show();
					$( '.sfwd_input.hide_on_' + option_class ).hide();
				});
			}
		},

		toggle_shortcode_instruction: function() {
			if ( $( '.shortcodes-instruction' ).length > 0 ) {
				$( 'select[name="_ld_notifications_trigger"]' ).on( 'change', function( e ) {
					var option_class = $( this ).val();

					$( '.shortcodes-instruction.' + option_class ).show();
					$( '.shortcodes-instruction' ).not( '.' + option_class ).hide();

					if ( option_class == 'complete_course' ) {
						$( '.additional-help-text.complete_course' ).show();
					}
				});

				$( window ).load( function( e ) {
					var option_class = $( 'select[name="_ld_notifications_trigger"]' ).val();
					$( '.shortcodes-instruction.' + option_class ).show();

					if ( option_class == 'complete_course' ) {
						$( '.additional-help-text.complete_course' ).show();
					}
				});
			}
		},

		submit_disabled_fields: function() {
			$( 'form' ).on( 'submit', function() {
				$( this ).find( ':input' ).prop( 'disabled', false );
			});
		},

		init_child_field: function() {
			$( '.parent_field select' ).on( 'change', function( e ) {
				var parent_type     = '',
					child_post_type = '';

				const el           = $( this ),
					name           = $( this ).attr( 'name' ),
					inputs_wrapper = el.closest( '.inputs-wrapper' );

				let parent_id      = $( this ).val();

				const course_field = inputs_wrapper.find( '> .sfwd_input:not(#conditions) select[name*="course_id"]' ),
					lesson_field   = inputs_wrapper.find( '> .sfwd_input:not(#conditions) select[name*="lesson_id"]' ),
					topic_field    = inputs_wrapper.find( '> .sfwd_input:not(#conditions) select[name*="topic_id"]' ),
					quiz_field     = inputs_wrapper.find( '> .sfwd_input:not(#conditions) select[name*="quiz_id"]' );

				const course_id = course_field.val(),
					lesson_id   = lesson_field.val(),
					topic_id    = topic_field.val(),
					quiz_id     = quiz_field.val();

				if ( topic_id.length > 0 ) {
					parent_type = 'topic';
					parent_id   = topic_id;
				} else if ( lesson_id.length > 0 ) {
					parent_type = 'lesson';
					parent_id   = lesson_id;
				} else {
					parent_type = 'course';
					parent_id   = course_id;
				}

				let child_field_select2_args = {
					course_id          : course_id,
					lesson_id          : lesson_id,
					topic_id           : topic_id,
					quiz_id            : quiz_id,
					parent_type        : parent_type,
					parent_id          : parent_id,
					minimumInputLength : 0,
					disabled           : false,
				};

				let child_fields,
					lesson_field_select2_args = $.extend( true, {}, child_field_select2_args ),
					topic_field_select2_args  = $.extend( true, {}, child_field_select2_args ),
					quiz_field_select2_args   = $.extend( true, {}, child_field_select2_args );

				if ( el.attr( 'name' ).indexOf( 'course' ) != '-1' ) {
					lesson_field_select2_args.post_type = 'sfwd-lessons';
					lesson_field.select2( 'destroy' ).select2( LD_Notifications.build_select2_args( lesson_field_select2_args ) );

					quiz_field_select2_args.post_type = 'sfwd-quiz';
					quiz_field.select2( 'destroy' ).select2( LD_Notifications.build_select2_args( quiz_field_select2_args ) );

					if ( course_id.length < 1 ) {
						child_fields = [ lesson_field, topic_field, quiz_field ];
						LD_Notifications.reset_child_fields( child_fields );
					}
				}

				if ( el.attr( 'name' ).indexOf( 'lesson' ) != '-1' ) {
					topic_field_select2_args.post_type = 'sfwd-topic';
					topic_field.select2( 'destroy' ).select2( LD_Notifications.build_select2_args( topic_field_select2_args ) );

					quiz_field_select2_args.post_type = 'sfwd-quiz';
					quiz_field.select2( 'destroy' ).select2( LD_Notifications.build_select2_args( quiz_field_select2_args ) );

					if ( lesson_id.length < 1 ) {
						child_fields = [ topic_field ];
						LD_Notifications.reset_child_fields( child_fields );
					}
				}

				if ( el.attr( 'name' ).indexOf( 'topic' ) != '-1' ) {
					quiz_field_select2_args.post_type = 'sfwd-quiz';

					quiz_field.select2( 'destroy' ).select2( LD_Notifications.build_select2_args( quiz_field_select2_args ) );
				}
			});
		},

		reset_child_fields: function( fields = [] ) {
			fields.map( function( field ) {
				let placeholder;
				if ( field.attr( 'name' ).indexOf( 'lesson_id' ) > -1 ) {
					placeholder = LearnDash_Notifications_Vars.select_course_first;
				} else if ( field.attr( 'name' ).indexOf( 'topic_id' ) > -1 ) {
					placeholder = LearnDash_Notifications_Vars.select_lesson_first;
				} else if ( field.attr( 'name' ).indexOf( 'quiz_id' ) > -1 ) {
					placeholder = LearnDash_Notifications_Vars.select_course_lesson_topic_first;
				}

				field.val( '' ).select2( 'destroy' ).select2( LD_Notifications.build_select2_args( {
					disabled    : true,
					placeholder : placeholder,
				} ) );
			} );
		},

		init_select2_fields: function( parent = null ) {
			const selects = typeof( parent ) === 'object' && parent !== null ? parent.find( '.sfwd_input.dynamic-options select' ) : $( '.sfwd_input.dynamic-options select' );

			selects.each( function( $el, index ) {
				const wrapper      = $( this ).closest( '.sfwd_input' ),
					value		   = $( this ).val(),
					id             = wrapper.attr( 'id' ).trim(),
					el             = $( this ),
					name           = $( this ).attr( 'name' ),
					inputs_wrapper = el.closest( '.inputs-wrapper' );

				let post_type       = false,
					placeholder     = '',
					parent_id		= '',
					parent_type     = '',
					child_post_type = '';

				const course_field = inputs_wrapper.find( '> .sfwd_input:not(#conditions) select[name*="course_id"]' ),
					lesson_field   = inputs_wrapper.find( '> .sfwd_input:not(#conditions) select[name*="lesson_id"]' ),
					topic_field    = inputs_wrapper.find( '> .sfwd_input:not(#conditions) select[name*="topic_id"]' ),
					quiz_field     = inputs_wrapper.find( '> .sfwd_input:not(#conditions) select[name*="quiz_id"]' );

				const course_id = course_field.val(),
					lesson_id   = lesson_field.val(),
					topic_id    = topic_field.val(),
					quiz_id     = quiz_field.val();

				if ( name.indexOf( 'lesson_id' ) > -1 ) {
					parent_type     = 'course';
				} else if ( name.indexOf( 'topic_id' ) > -1 ) {
					parent_type     = 'lesson';
				} else if ( name.indexOf( 'quiz_id' ) > -1 ) {
					if ( topic_id.length > 0 ) {
						parent_type = 'topic';
					} else if ( lesson_id.length > 0 ) {
						parent_type = 'lesson';
					} else if ( course_id.length > 0 ) {
						parent_type = 'course';
					}
				}

				switch ( id ) {
					case 'group_id':
						post_type = 'groups';
						break;

					case 'course_id':
						post_type = 'sfwd-courses';
						break;

					case 'lesson_id':
						post_type   = 'sfwd-lessons';
						parent_id   = course_id;
						placeholder = parent_id.length < 1 ? LearnDash_Notifications_Vars.select_course_first : '';
						break;

					case 'topic_id':
						post_type   = 'sfwd-topic';
						parent_id   = lesson_id;
						placeholder = parent_id.length < 1 ? LearnDash_Notifications_Vars.select_lesson_first : '';
						break;

					case 'quiz_id':
						post_type   = 'sfwd-quiz';

						if ( topic_id.length > 0 ) {
							parent_id = topic_id;
						} else if ( lesson_id.length > 0 ) {
							parent_id = lesson_id;
						} else if ( course_id.length > 0 ) {
							parent_id = course_id;
						}

						placeholder = parent_id.length < 1 ? LearnDash_Notifications_Vars.select_course_lesson_topic_first : '';
						break;
				}

				const disabled_child = wrapper.hasClass( 'disabled-child' ) && value.length < 1 && parent_id.length < 1 ? true : false;

				$( this ).select2( LD_Notifications.build_select2_args( {
					post_type          : post_type,
					course_id          : course_id,
					lesson_id          : lesson_id,
					topic_id           : topic_id,
					quiz_id            : quiz_id,
					parent_type        : parent_type,
					parent_id          : parent_id,
					minimumInputLength : 0,
					disabled           : disabled_child,
					placeholder        : placeholder,
				} ) );
			} );
		},

		init_filter_child_field: function() {
			$( '#posts-filter select.select2' ).on( 'change', function( e ) {
				var parent_type     = '',
					child_post_type = '';

				const el       = $( this ),
					parent_id  = $( this ).val(),
					name       = $( this ).attr( 'name' );

				const course_id = $( 'select[name="course_id"]' ).val(),
					lesson_id   = $( 'select[name="lesson_id"]' ).val(),
					topic_id    = $( 'select[name="topic_id"]' ).val(),
					quiz_id     = $( 'select[name="quiz_id"]' ).val();

				switch ( name ) {
					case 'course_id':
						parent_type     = 'course';
						child_post_type = 'sfwd-lessons'
						break;
					case 'lesson_id':
						parent_type     = 'lesson';
						child_post_type = 'sfwd-topic'
						break;
					case 'topic_id':
						parent_type     = 'topic';
						child_post_type = 'sfwd-quiz'
						break;
				}

				const child_field_select2_args = {
					post_type          : child_post_type,
					course_id          : course_id,
					lesson_id          : lesson_id,
					topic_id           : topic_id,
					quiz_id            : quiz_id,
					parent_type        : parent_type,
					parent_id          : parent_id,
					minimumInputLength : 0,
					disabled           : false,
				};

				if ( el.attr( 'name' ).indexOf( 'course' ) != '-1' ) {
					$( '#posts-filter select[name="topic_id"]' ).html(
						'<option value="">' + LearnDash_Notifications_Vars.select_lesson_first + '</option>'
					).attr( 'disabled', true );

					$( '#posts-filter select[name="quiz_id"]' ).html(
						'<option value="">' + LearnDash_Notifications_Vars.select_course_lesson_topic_first + '</option>'
					).attr( 'disabled', true );

					$( '#posts-filter select[name="lesson_id"]' ).html(
						'<option value="">' + LearnDash_Notifications_Vars.select_lesson + '</option>'
						+ '<option value="all">' + LearnDash_Notifications_Vars.all_lessons + '</option>'
					);

					$( '#posts-filter select[name="lesson_id"]' ).select2( 'destroy' ).select2( LD_Notifications.build_select2_args( child_field_select2_args ) );
				}

				if ( el.attr( 'name' ).indexOf( 'lesson' ) != '-1' ) {
					$( '#posts-filter select[name="quiz_id"]' ).html(
						'<option value="">' + LearnDash_Notifications_Vars.select_course_lesson_topic_first + '</option>'
					).attr( 'disabled', true );

					$( '#posts-filter select[name="topic_id"]' ).html(
						'<option value="">' + LearnDash_Notifications_Vars.select_topic + '</option>' +
						'<option value="all">' + LearnDash_Notifications_Vars.all_topics + '</option>'
					);

					$( '#posts-filter select[name="topic_id"]' ).select2( 'destroy' ).select2( LD_Notifications.build_select2_args( child_field_select2_args ) );
				}

				if ( el.attr( 'name' ).indexOf( 'topic' ) != '-1' ) {
					$( '#posts-filter select[name="quiz_id"]' ).html(
						'<option value="">' + LearnDash_Notifications_Vars.select_quiz + '</option>' +
						'<option value="all">' + LearnDash_Notifications_Vars.all_quizzes + '</option>'
					);

					$( '#posts-filter select[name="quiz_id"]' ).select2( 'destroy' ).select2( LD_Notifications.build_select2_args( child_field_select2_args ) );
				}
			});
		},

		init_filter_select2_fields: function() {
			$( '#posts-filter select.select2' ).each( function( element, index ) {
				const id = $( this ).attr( 'id' ).trim(),
					el             = $( this ),
					name           = $( this ).attr( 'name' ),
					inputs_wrapper = el.closest( '#posts-filter' );

				let post_type = false,
					disabled_child,
					placeholder;

				switch ( id ) {
					case 'group_id':
						post_type = 'groups';
						placeholder = LearnDash_Notifications_Vars.select_group;
						break;

					case 'course_id':
						post_type = 'sfwd-courses';
						placeholder = LearnDash_Notifications_Vars.select_course;
						break;

					case 'lesson_id':
						post_type = 'sfwd-lessons';
						placeholder = LearnDash_Notifications_Vars.select_course_first;
						break;

					case 'topic_id':
						post_type = 'sfwd-topic';
						placeholder = LearnDash_Notifications_Vars.select_lesson_first;
						break;

					case 'quiz_id':
						post_type = 'sfwd-quiz';
						placeholder = LearnDash_Notifications_Vars.select_course_lesson_topic_first;
						break;
				}

				let parent_id		= '',
					parent_type     = '',
					child_post_type = '';

				const course_field = inputs_wrapper.find( '#course_id' ),
					lesson_field   = inputs_wrapper.find( '#lesson_id' ),
					topic_field    = inputs_wrapper.find( '#topic_id' ),
					quiz_field     = inputs_wrapper.find( '#quiz_id' );

				const course_id = course_field.val(),
					lesson_id   = lesson_field.val(),
					topic_id    = topic_field.val(),
					quiz_id     = quiz_field.val();

				if ( name.indexOf( 'course_id' ) > -1 ) {
					child_post_type = 'sfwd-lessons';
				} else if ( name.indexOf( 'lesson_id' ) > -1 ) {
					parent_type     = 'quiz';
					child_post_type = 'sfwd-topic';
				} else if ( name.indexOf( 'topic_id' ) > -1 ) {
					parent_type     = 'lesson';
					child_post_type = 'sfwd-quiz';
				} else if ( name.indexOf( 'quiz_id' ) > -1 ) {
					parent_type     = 'topic';
				}

				switch ( id ) {
					case 'group_id':
						post_type = 'groups';
						break;

					case 'course_id':
						post_type = 'sfwd-courses';
						break;

					case 'lesson_id':
						post_type   = 'sfwd-lessons';
						parent_id   = course_id;
						if ( parent_id === '' ) {
							placeholder = LearnDash_Notifications_Vars.select_course_first;
						} else {
							placeholder = LearnDash_Notifications_Vars.select_lesson;
						}
						break;

					case 'topic_id':
						post_type   = 'sfwd-topic';
						parent_id   = lesson_id;
						if ( parent_id === '' ) {
							placeholder = LearnDash_Notifications_Vars.select_lesson_first;
						} else {
							placeholder = LearnDash_Notifications_Vars.select_topic;
						}
						break;

					case 'quiz_id':
						post_type   = 'sfwd-quiz';
						parent_id   = topic_id;
						if ( parent_id === '' ) {
							placeholder = LearnDash_Notifications_Vars.select_course_lesson_topic_first;
						} else {
							placeholder = LearnDash_Notifications_Vars.select_quiz;
						}
						break;
				}

				disabled_child = $( this ).hasClass( 'disabled-child' ) && $( this ).val() === '' && parent_id === '' ? true : false

				$( this ).select2( LD_Notifications.build_select2_args( {
					post_type          : post_type,
					course_id          : course_id,
					lesson_id          : lesson_id,
					topic_id           : topic_id,
					quiz_id            : quiz_id,
					parent_type        : parent_type,
					parent_id          : parent_id,
					post_type          : post_type,
					minimumInputLength : 0,
					disabled           : disabled_child,
					placeholder        : placeholder,
				} ) );
			});
		},

		add_conditions_field: function() {
			$( document ).on( 'click', '.add-condition', function() {
				$( this ).prev( '.conditions-wrapper' ).append( LearnDash_Notifications_Vars.templates.condition_field );

				const last_condition_field = $( '.conditions-wrapper .condition' ).last();

				LD_Notifications.init_select2_fields( last_condition_field );
				LD_Notifications.init_child_field();
				LD_Notifications.init_accordions();
				LD_Notifications.recalculate_condition_fields();
			} );
		},

		remove_conditions_field: function() {
			$( document ).on( 'click', '.remove-condition', function( e ) {
				e.preventDefault();

				// Temporarily disable the collapse, so we can avoid removed nodes from being called in animation callback.
				$( '.accordion .accordion-wrapper' ).accordion( "option", "collapsible", false );

				const $title = $( this ).closest( '.title' ),
					$condition = $title.next( '.condition' );

				$title.remove();
				$condition.remove();

				LD_Notifications.init_accordions();
				LD_Notifications.recalculate_condition_fields();
			} );
		},

		toggle_conditions_child_field: function() {
			if ( $( '.ld_notifications_metabox_settings' ).length > 0 ) {
				$( document ).on( 'change', '.condition .condition-input-wrapper select', function( e ) {
					e.preventDefault();

					const $wrapper = $( this ).closest( '.condition' ),
						type       = $( this ).val();

					// Trigger.
					$( $wrapper ).find( '.sfwd_input.' + type ).show();
					$( $wrapper ).find( '.sfwd_input.child-input' ).not( '.' + type ).hide();
					$( $wrapper ).find( '.sfwd_input.hide_on' ).show();
					$( $wrapper ).find( '.sfwd_input.hide_on_' + type ).hide();
				} );

				$( window ).on( 'load', function( e ) {
					const conditions = $( '.conditions-wrapper .condition .condition-input-wrapper select' );
					$.each( conditions, function( condition ) {
						const $wrapper = $( this ).closest( '.condition' ),
							type       = $( this ).val();

						// Trigger.
						$( $wrapper ).find( '.sfwd_input.' + type ).show();
						$( $wrapper ).find( '.sfwd_input.child-input' ).not( '.' + type ).hide();
						$( $wrapper ).find( '.sfwd_input.hide_on' ).show();
						$( $wrapper ).find( '.sfwd_input.hide_on_' + type ).hide();
					} );
				});
			}
		},

		recalculate_condition_fields: function() {
			const conditions = $( '.conditions-wrapper .condition' );

			$.each( conditions, function( index, condition ) {
				const sequence = index + 1;

				$( this )
					.data( 'id', sequence )
					.attr( 'data-sequence', sequence );

				$( this ).prev( '.title' )
					.data( 'title-sequence', sequence )
					.attr( 'data-title-sequence', sequence )
						.find( '.title-sequence-number')
							.text( '#' + sequence );

				const inputs = $( this ).find( 'input, select' );

				$.each( inputs, function( index, input ) {
					let name = $( this ).attr( 'name' );

					if ( name !== undefined && name.indexOf( '_ld_notifications_conditions' ) > -1 ) {
						name = name.replace( /(_ld_notifications_conditions\[).*?(\].*)/, '$1' + sequence + '$2' );

						$( this ).attr( 'name', name );
					}
				} );
			} );
		},

		init_accordions: function() {
			const $wrapper = $( '.accordion .accordion-wrapper' ),
				instance = $wrapper.accordion( 'instance' )
				$items = $wrapper.find( '.accordion-item' );

			if ( instance !== undefined ) {
				$( '.accordion .accordion-wrapper' ).accordion( 'destroy' );
			}

			if ( $items.length ) {
				$( '.accordion .accordion-wrapper' ).accordion({
					active: -1,
					collapsible: true,
					heightStyle: 'auto',
				});
			}
		},

		destroy_accordions: function() {
			$( '.accordion .accordion-wrapper' ).accordion( 'destroy' );
		},

		update_select_values: function() {
			$( 'select[name="_ld_notifications_lesson_id"]' ).html(
				'<option value="">' + LearnDash_Notifications_Vars.select_course_first + '</option>'
			);

			$( 'select[name="_ld_notifications_topic_id"]' ).html(
				'<option value="">' + LearnDash_Notifications_Vars.select_lesson_first + '</option>'
			);

			$( 'select[name="_ld_notifications_quiz_id"]' ).html(
				'<option value="">' + LearnDash_Notifications_Vars.select_course_lesson_topic_first + '</option>'
			);
		},

		update_select_values_onload: function() {
			if ( $( 'select[name="_ld_notifications_course_id"]' ).val() === '' ) {
				$( 'select[name="_ld_notifications_course_id"]' ).prop( 'selectedIndex', 0 );
			}

			if ( $( 'select[name="_ld_notifications_lesson_id"]' ).val() === '' ) {
				$( 'select[name="_ld_notifications_lesson_id"]' ).html(
					'<option value="">' + LearnDash_Notifications_Vars.select_course_first + '</option>'
				);
			}

			if ( $( 'select[name="_ld_notifications_topic_id"]' ).val() === '' ) {
				$( 'select[name="_ld_notifications_topic_id"]' ).html(
					'<option value="">' + LearnDash_Notifications_Vars.select_lesson_first + '</option>'
				);
			}

			if ( $( 'select[name="_ld_notifications_quiz_id"]' ).val() === '' ) {
				$( 'select[name="_ld_notifications_quiz_id"]' ).html(
					'<option value="">' + LearnDash_Notifications_Vars.select_course_lesson_topic_first + '</option>'
				);
			}
		},

		toggle_field_help_text: function() {
			$( document ).on( 'click', '.sfwd_help_text_link', function( e ) {
				const $wrapper = $( this ).closest( '.sfwd_input' );

				$wrapper.find( '.sfwd_help_text_div' ).toggle();
			} );
		}
	};

	LD_Notifications.init();

} );
