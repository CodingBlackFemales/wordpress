/* eslint-disable no-var */
jQuery(function ($) {
	var hash = window.location.hash;

	learndashFocusModeSidebarAutoScroll();

	initLoginModal();
	if ('#login' == hash) {
		openLoginModal();
	}

	if ('undefined' !== typeof ldGetUrlVars().login) {
		var loginStatus = ldGetUrlVars().login;

		if ('failed' == loginStatus) {
			openLoginModal();
		}
	}

	if ('undefined' !== typeof ldGetUrlVars()['ld-topic-page']) {
		var topicPage = ldGetUrlVars()['ld-topic-page'];
		var topicIds = topicPage.split('-');
		var topicId = Object.values(topicIds)[0];

		var lesson = $('#ld-expand-' + topicId);
		var button = $(lesson).find('.ld-expand-button');

		ld_expand_element(button);

		$('html, body').animate(
			{
				scrollTop: $(lesson).offset().top,
			},
			500
		);
	}

	$('body').on('click', 'a[href="#login"]', function (e) {
		e.preventDefault();
		openLoginModal();
	});

	$('body').on('click', '.ld-modal-closer', function (e) {
		e.preventDefault();
		closeLoginModal();
	});

	$('body').on('click', '#ld-comments-post-button', function (e) {
		$(this).addClass('ld-open');
		$('#ld-comments-form').removeClass('ld-collapsed');
		$('textarea#comment').focus();
	});

	// Close modal if clicking away
	/*
	$('body').on('click', function(e) {
		if ($('.learndash-wrapper').hasClass('ld-modal-open')) {
			if ( ! $(e.target).parents('.ld-modal').length && (! $(e.target).is('a'))) {
				closeLoginModal();
			}
		}
	});
	*/

	// Close modal on Esc key
	$(document).on('keyup', function (e) {
		if (27 === e.keyCode) {
			closeLoginModal();
		}
	});

	$('.learndash-wrapper').on(
		'click',
		'a.user_statistic',
		learndash_ld30_show_user_statistic
	);

	focusMobileCheck();

	$('body').on('click', '.ld-focus-sidebar-trigger', function (e) {
		if ($('.ld-focus').hasClass('ld-focus-sidebar-collapsed')) {
			openFocusSidebar();
		} else {
			closeFocusSidebar();
		}
	});

	$('body').on('click', '.ld-mobile-nav a', function (e) {
		e.preventDefault();
		if ($('.ld-focus').hasClass('ld-focus-sidebar-collapsed')) {
			openFocusSidebar();
		} else {
			closeFocusSidebar();
		}
	});

	$('.ld-js-register-account').on('click', function (e) {
		e.preventDefault();

		$('.ld-login-modal-register .ld-modal-text').slideUp('slow');
		$('.ld-login-modal-register .ld-alert').slideUp('slow');
		$(this).slideUp('slow', function () {
			$('#ld-user-register').slideDown('slow');
		});
	});

	// If registration login link filter not defined, allow to replace the register form with login form
	if ('' == $('.registration-login-link').attr('href')) {
		$('.registration-login-link').on('click', function (e) {
			e.preventDefault();
			$('#learndash_registerform, .registration-login').hide();
			$(
				'.registration-login-form, .show-register-form, .show-password-reset-link'
			).show();
		});
		$('.show-register-form').on('click', function (e) {
			e.preventDefault();
			$(
				'.registration-login-form, .show-register-form, .show-password-reset-link'
			).hide();
			$('#learndash_registerform, .registration-login').show();
		});
	}

	var windowWidth = $(window).width();

	$(window).on('orientationchange', function () {
		windowWidth = $(window).width();
	});

	$(window).on('resize', function () {
		if ($(this).width() !== windowWidth && 1024 >= $(this).width()) {
			setTimeout(function () {
				focusMobileResizeCheck();
			}, 50);
		}
	});

	if ($('.ld-course-status-content').length) {
		var tallest = 0;

		$('.ld-course-status-content').each(function () {
			if ($(this).height() > tallest) {
				tallest = $(this).height();
			}
		});

		$('.ld-course-status-content').height(tallest);
	}

	function focusMobileCheck() {
		if (1024 > $(window).width()) {
			closeFocusSidebarPageLoad();
		}
	}

	function focusMobileResizeCheck() {
		if (
			1024 > $(window).width() &&
			!$('.ld-focus').hasClass('ld-focus-sidebar-collapsed')
		) {
			closeFocusSidebar();
		} else if (
			1024 <= $(window).width() &&
			$('.ld-focus').hasClass('ld-focus-sidebar-filtered')
		) {
			closeFocusSidebar();
		} else if (
			1024 <= $(window).width() &&
			!$('.ld-focus').hasClass('ld-focus-sidebar-filtered') &&
			$('.ld-focus').hasClass('ld-focus-sidebar-collapsed')
		) {
			openFocusSidebar();
		}
	}

	function focusMobileHandleOrientationChange(e) {
		if (e.matches) {
			if (
				1024 <= $(window).width() &&
				!$('.ld-focus').hasClass('ld-focus-sidebar-filtered') &&
				$('.ld-focus').hasClass('ld-focus-sidebar-collapsed')
			) {
				openFocusSidebar();
			}
		}
	}
	window
		.matchMedia('(orientation: landscape)')
		.addListener(focusMobileHandleOrientationChange);

	function closeFocusSidebarPageLoad() {
		$('.ld-focus').addClass('ld-focus-sidebar-collapsed');
		$('.ld-focus').removeClass('ld-focus-initial-transition');
		$('.ld-mobile-nav').removeClass('expanded');
		positionTooltips();
	}

	function closeFocusSidebar() {
		$('.ld-focus').addClass('ld-focus-sidebar-collapsed');
		$('.ld-mobile-nav').removeClass('expanded');

		if (
			$('.ld-focus-sidebar-trigger .ld-icon').hasClass(
				'ld-icon-arrow-left'
			)
		) {
			$('.ld-focus-sidebar-trigger .ld-icon').removeClass(
				'ld-icon-arrow-left'
			);
			$('.ld-focus-sidebar-trigger .ld-icon').addClass(
				'ld-icon-arrow-right'
			);
		} else if (
			$('.ld-focus-sidebar-trigger .ld-icon').hasClass(
				'ld-icon-arrow-right'
			)
		) {
			$('.ld-focus-sidebar-trigger .ld-icon').removeClass(
				'ld-icon-arrow-right'
			);
			$('.ld-focus-sidebar-trigger .ld-icon').addClass(
				'ld-icon-arrow-left'
			);
		}

		positionTooltips();
	}

	function openFocusSidebar() {
		focusMobileCheck();
		$('.ld-focus').removeClass('ld-focus-sidebar-collapsed');
		$('.ld-mobile-nav').addClass('expanded');

		if (
			$('.ld-focus-sidebar-trigger .ld-icon').hasClass(
				'ld-icon-arrow-left'
			)
		) {
			$('.ld-focus-sidebar-trigger .ld-icon').removeClass(
				'ld-icon-arrow-left'
			);
			$('.ld-focus-sidebar-trigger .ld-icon').addClass(
				'ld-icon-arrow-right'
			);
		} else if (
			$('.ld-focus-sidebar-trigger .ld-icon').hasClass(
				'ld-icon-arrow-right'
			)
		) {
			$('.ld-focus-sidebar-trigger .ld-icon').removeClass(
				'ld-icon-arrow-right'
			);
			$('.ld-focus-sidebar-trigger .ld-icon').addClass(
				'ld-icon-arrow-left'
			);
		}

		positionTooltips();
	}

	$('.ld-file-input').each(function () {
		var $input = $(this),
			$label = $input.next('label'),
			labelVal = $label.html();

		$input.on('change', function (e) {
			var fileName = '';
			if (this.files && 1 < this.files.length) {
				fileName = (
					this.getAttribute('data-multiple-caption') || ''
				).replace('{count}', this.files.length);
			} else if (e.target.value) {
				fileName = e.target.value.split('\\').pop();
			}
			if (fileName) {
				$label.find('span').html(fileName);
				$label.addClass('ld-file-selected');
				$('#uploadfile_btn').attr('disabled', false);
			} else {
				$label.html(labelVal);
				$label.removeClass('ld-file-selected');
				$('#uploadfile_btn').attr('disabled', true);
			}
		});

		$('#uploadfile_form').on('submit', function () {
			$label.removeClass('ld-file-selected');
			$('#uploadfile_btn').attr('disabled', true);
		});

		// Firefox bug fix
		$input
			.on('focus', function () {
				$input.addClass('has-focus');
			})
			.on('blur', function () {
				$input.removeClass('has-focus');
			});
	});

	$('body').on('click', '.ld-expand-button', function (e) {
		e.preventDefault();

		ld_expand_element($(this));

		positionTooltips();
	});

	$('body').on('click', '.ld-search-prompt', function (e) {
		e.preventDefault();

		$('#course_name_field').focus();

		ld_expand_element($(this));
	});

	function ld_expand_button_state(state, elm) {
		var $expandText = $(elm)[0].hasAttribute('data-ld-expand-text')
			? $(elm).attr('data-ld-expand-text')
			: 'Expand';
		var $collapseText = $(elm)[0].hasAttribute('data-ld-collapse-text')
			? $(elm).attr('data-ld-collapse-text')
			: 'Collapse';

		if ('collapse' == state) {
			$(elm).removeClass('ld-expanded');
			if ('false' !== $collapseText) {
				$(elm).find('.ld-text').text($expandText);
			}
		} else {
			$(elm).addClass('ld-expanded');
			if ('false' !== $collapseText) {
				$(elm).find('.ld-text').text($collapseText);
			}
		}
	}

	function ld_expand_element(elm, collapse) {
		if (collapse === undefined) {
			collapse = false;
		}

		var elmParentWrapper = elm.parents('.ld-focus-sidebar');
		if (
			'undefined' === typeof elmParentWrapper ||
			!elmParentWrapper.length
		) {
			var elmParentWrapper = elm.parents('.learndash-wrapper');
		}
		if (
			'undefined' === typeof elmParentWrapper ||
			!elmParentWrapper.length
		) {
			return;
		}

		// Get the button's state
		var $expanded = $(elm).hasClass('ld-expanded');

		// Get the element to expand
		if ($(elm)[0] && $(elm)[0].hasAttribute('data-ld-expands')) {
			var $expands = $(elm).attr('data-ld-expands');
			if ('undefined' === typeof $expands || !$expands.length) {
				return;
			}

			var $expandElm = $(elmParentWrapper).find(
				'[data-ld-expand-id="' + $expands + '"]'
			);
			if ('undefined' === typeof $expandElm || !$expandElm.length) {
				return;
			}

			var $expandsChild = $($expandElm).find(
				'.ld-item-list-item-expanded'
			);

			if ($expandsChild.length) {
				$expandElm = $expandsChild;
			}

			var totalHeight = 0;

			$expandElm.find('> *').each(function () {
				totalHeight += $(this).outerHeight();
			});

			$expandElm.attr('data-height', '' + (totalHeight + 50) + '');

			// If the element expands a list

			if (
				$(elmParentWrapper)
					.find('[data-ld-expand-id="' + $expands + '"]')[0]
					.hasAttribute('data-ld-expand-list')
			) {
				var $container = $(elmParentWrapper).find(
					'[data-ld-expand-id="' + $expands + '"]'
				);
				var innerButtons = $container.find('.ld-expand-button');
				if ($expanded) {
					ld_expand_button_state('collapse', elm);
					innerButtons.each(function () {
						ld_expand_element($(this), true);
					});
				} else {
					ld_expand_button_state('expand', elm);
					innerButtons.each(function () {
						ld_expand_element($(this));
					});
				}

				// If the element expands an item
			} else if (
				$(elmParentWrapper).find(
					'[data-ld-expand-id="' + $expands + '"]'
				).length
			) {
				if ($expanded || true == collapse) {
					ld_expand_singular_item(
						elm,
						$(elmParentWrapper).find(
							'[data-ld-expand-id="' + $expands + '"]'
						),
						$expandElm
					);
				} else {
					ld_collapse_singular_item(
						elm,
						$(elmParentWrapper).find(
							'[data-ld-expand-id="' + $expands + '"]'
						),
						$expandElm
					);
				}
			} else {
				console.log('LearnDash: No expandable content was found');
			}
			positionTooltips();
		}
	}

	function ld_expand_singular_item(elm, $containerElm, $expandElm) {
		$containerElm.removeClass('ld-expanded');
		ld_expand_button_state('collapse', elm);

		$expandElm.css({
			'max-height': 0,
		});
	}

	function ld_collapse_singular_item(elm, $containerElm, $expandElm) {
		$containerElm.addClass('ld-expanded');

		ld_expand_button_state('expand', elm);

		$expandElm.css({
			'max-height': $expandElm.data('height'),
		});
	}

	$('body').on('click', '.ld-closer', function (e) {
		ld_expand_element($('.ld-search-prompt'), true);
	});

	$('body').on('click', '.ld-tabs-navigation .ld-tab', function () {
		var $tab = $('#' + $(this).attr('data-ld-tab'));
		if ($tab.length) {
			$('.ld-tabs-navigation .ld-tab.ld-active').removeClass('ld-active');
			$('.ld-tabs-navigation .ld-tab').removeAttr('aria-selected');
			$(this).addClass('ld-active');
			$(this).attr('aria-selected', 'true');
			$('.ld-tabs-content .ld-tab-content.ld-visible').removeClass(
				'ld-visible'
			);
			$tab.addClass('ld-visible');
		}
		positionTooltips();
	});

	var $tooltips = $('*[data-ld-tooltip]');

	initTooltips();

	function initTooltips() {
		// Clear out old tooltips

		if ($('#learndash-tooltips').length) {
			$('#learndash-tooltips').remove();
			$tooltips = $('*[data-ld-tooltip]');
		}

		if ($tooltips.length) {
			$('body').prepend('<div id="learndash-tooltips"></div>');
			var $ctr = 1;
			$tooltips.each(function () {
				var anchor = $(this);
				if (anchor.hasClass('ld-item-list-item')) {
					anchor = anchor.find('.ld-item-title');
				}

				/**
				 * Prevent calendar icon from being clickable.
				 */
				if (
					'undefined' !== typeof anchor &&
					$(anchor).hasClass('ld-status-waiting')
				) {
					$(anchor).on('click', function (e) {
						e.preventDefault();
						return false;
					});

					// Also prevent parent <a> from being clickable.
					var parent_anchor = $(anchor).parents('a');
					if ('undefined' !== typeof parent_anchor) {
						$(parent_anchor).on('click', function (e) {
							e.preventDefault();
							return false;
						});
					}
				}

				var elementOffsets = {
					top: anchor.offset().top,
					left: anchor.offset().left + anchor.outerWidth() / 2,
				};
				var $content = $(this).attr('data-ld-tooltip');
				var $rel_id = Math.floor(Math.random() * 99999);

				//var $tooltip = '<span id="ld-tooltip-' + $rel_id + '" class="ld-tooltip" style="top:' + elementOffsets.top + 'px; left:' + elementOffsets.left + 'px;">' + $content + '</span>';
				var $tooltip =
					'<span id="ld-tooltip-' +
					$rel_id +
					'" class="ld-tooltip">' +
					$content +
					'</span>';
				$(this).attr('data-ld-tooltip-id', $rel_id);
				$('#learndash-tooltips').append($tooltip);
				$ctr++;
				var $tooltip = $('#ld-tooltip-' + $rel_id);
				$(this)
					.on('mouseenter', function () {
						$tooltip.addClass('ld-visible');
					})
					.on('mouseleave', function () {
						$tooltip.removeClass('ld-visible');
					});
			});

			$(window).on('resize', function () {
				// Reposition tooltips after resizing
				positionTooltips();
			});

			$(window)
				.add('.ld-focus-sidebar-wrapper')
				.on('scroll', function () {
					// Hide tooltips so they don't persist while scrolling
					$('.ld-visible.ld-tooltip').removeClass('ld-visible');

					// Reposition tooltips after scrolling
					positionTooltips();
				});

			positionTooltips();
		}
	}

	function initLoginModal() {
		var modal_wrapper = $('.learndash-wrapper-login-modal');
		if ('undefined' !== typeof modal_wrapper && modal_wrapper.length) {
			// Move the model to be first element of the body. See LEARNDASH-3503
			$(modal_wrapper).prependTo('body');
		}
	}

	function openLoginModal() {
		var modal_wrapper = $('.learndash-wrapper-login-modal');
		if ('undefined' !== typeof modal_wrapper && modal_wrapper.length) {
			$(modal_wrapper).addClass('ld-modal-open');
			$(modal_wrapper).removeClass('ld-modal-closed');

			// Removed LEARNDASH-3867 #4
			$('html, body').animate(
				{
					scrollTop: $('.ld-modal', modal_wrapper).offset().top,
				},
				50
			);
		}
	}

	function closeLoginModal() {
		var modal_wrapper = $('.learndash-wrapper-login-modal');
		if ('undefined' !== typeof modal_wrapper && modal_wrapper.length) {
			$(modal_wrapper).removeClass('ld-modal-open');
			$(modal_wrapper).addClass('ld-modal-closed');
		}
	}

	function positionTooltips() {
		if ('undefined' !== typeof $tooltips) {
			setTimeout(function () {
				$tooltips.each(function () {
					var anchor = $(this);
					var $rel_id = anchor.attr('data-ld-tooltip-id');
					$tooltip = $('#ld-tooltip-' + $rel_id);

					if (anchor.hasClass('ld-item-list-item')) {
						//anchor = anchor.find('.ld-item-title');
						anchor = anchor.find('.ld-status-icon');
					}

					var parent_focus =
						jQuery(anchor).parents('.ld-focus-sidebar');
					var left_post =
						anchor.offset().left + (anchor.outerWidth() + 10);
					if (parent_focus.length) {
						left_post =
							anchor.offset().left + (anchor.outerWidth() - 18);
					}

					// Get the main content height
					var focusModeMainContentHeight =
						$('.ld-focus-main').height();

					// Current tooltip height
					var focusModeCurrentTooltipHeight =
						anchor.offset().top + -3;

					// Position tooltip depending on focus mode or not
					if (!focusModeMainContentHeight) {
						var anchorTop = anchor.offset().top + -3;
						var anchorLeft = anchor.offset().left;
					} else {
						anchorTop =
							focusModeCurrentTooltipHeight <
							focusModeMainContentHeight
								? focusModeCurrentTooltipHeight
								: focusModeMainContentHeight;
						anchorLeft = left_post;
					}

					$tooltip
						.css({
							top: anchorTop,

							//'left' : anchor.offset().left + (anchor.outerWidth() / 2),
							//'left': left_post, //anchor.offset().left + (anchor.outerWidth() +10),
							left: anchorLeft, //anchor.offset().left + (anchor.outerWidth() +10),
							'margin-left': 0,
							'margin-right': 0,
						})
						.removeClass('ld-shifted-left ld-shifted-right');
					if ($tooltip.offset().left <= 0) {
						$tooltip
							.css({
								'margin-left': Math.abs($tooltip.offset().left),
							})
							.addClass('ld-shifted-left');
					}
					var $tooltipRight =
						$(window).width() -
						($tooltip.offset().left + $tooltip.outerWidth());
					if (0 >= $tooltipRight && 360 < $(window).width()) {
						$tooltip
							.css({ 'margin-right': Math.abs($tooltipRight) })
							.addClass('ld-shifted-right');
					}
				});
			}, 500);
		}
	}

	$('body').on('click', '#ld-profile .ld-reset-button', function (e) {
		e.preventDefault();

		$('#ld-profile #course_name_field').val('');

		var searchVars = {
			shortcode_instance: $('#ld-profile').data('shortcode_instance'),
		};

		searchVars['ld-profile-search'] = $(this)
			.parents('.ld-item-search-wrapper')
			.find('#course_name_field')
			.val();
		searchVars['ld-profile-search-nonce'] = $(this)
			.parents('.ld-item-search-wrapper')
			.find('form.ld-item-search-fields')
			.data('nonce');

		$('#ld-profile #ld-main-course-list').addClass('ld-loading');

		$.ajax({
			type: 'GET',
			url: ldVars.ajaxurl + '?action=ld30_ajax_profile_search',
			data: searchVars,
			success(response) {
				if ('undefined' !== typeof response.data.markup) {
					$('#ld-profile').html(response.data.markup);
					ld_expand_element('#ld-profile .ld-search-prompt', false);
				}
			},
		});
	});

	$('body').on('submit', '.ld-item-search-fields', function (e) {
		e.preventDefault();

		var searchVars = {
			shortcode_instance: $('#ld-profile').data('shortcode_instance'),
		};

		searchVars['ld-profile-search'] = $(this)
			.parents('.ld-item-search-wrapper')
			.find('#course_name_field')
			.val();
		searchVars['ld-profile-search-nonce'] = $(this)
			.parents('.ld-item-search-wrapper')
			.find('form.ld-item-search-fields')
			.data('nonce');

		$('#ld-profile #ld-main-course-list').addClass('ld-loading');

		$.ajax({
			type: 'GET',
			url: ldVars.ajaxurl + '?action=ld30_ajax_profile_search',
			data: searchVars,
			success(response) {
				if ('undefined' !== typeof response.data.markup) {
					$('#ld-profile').html(response.data.markup);
					ld_expand_element('#ld-profile .ld-search-prompt', false);
				}
			},
		});
	});

	$('body').on('click', '.ld-pagination a', function (e) {
		e.preventDefault();

		var linkVars = {};
		var parentVars = {};

		$(this)
			.attr('href')
			.replace(/[?&]+([^=&]+)=([^&]*)/gi, function (m, key, value) {
				linkVars[key] = value;
			});

		linkVars.pager_nonce = $(this)
			.parents('.ld-pagination')
			.data('pager-nonce');

		linkVars.pager_results = $(this)
			.parents('.ld-pagination')
			.data('pager-results');

		linkVars.context = $(this).data('context');
		console.log('linkVars[%o]', linkVars);

		parentVars.currentTarget = e.currentTarget;

		if ('profile' != linkVars.context) {
			linkVars.lesson_id = $(this).data('lesson_id');
			linkVars.course_id = $(this).data('course_id');

			if ($('.ld-course-nav-' + linkVars.course_id).length) {
				linkVars.widget_instance = $(
					'.ld-course-nav-' + linkVars.course_id
				).data('widget_instance');
			}
		}

		if ('course_topics' == linkVars.context) {
			$('#ld-topic-list-' + linkVars.lesson_id).addClass('ld-loading');
			$('#ld-nav-content-list-' + linkVars.lesson_id).addClass(
				'ld-loading'
			);
		}

		if ('course_content_shortcode' == linkVars.context) {
			parentVars.parent_container = $(parentVars.currentTarget).closest(
				'.ld-course-content-' + linkVars.course_id
			);
			if (
				'undefined' !== typeof parentVars.parent_container &&
				parentVars.parent_container.length
			) {
				$(parentVars.parent_container).addClass('ld-loading');
				linkVars.shortcode_instance = $(
					parentVars.parent_container
				).data('shortcode_instance');
			} else {
				$('.ld-course-content-' + linkVars.course_id).addClass(
					'ld-loading'
				);
				linkVars.shortcode_instance = $(
					'.ld-course-content-' + linkVars.course_id
				).data('shortcode_instance');
			}
		} else if ('course_lessons' == linkVars.context) {
			var parent_container;

			// Check if we are within the Course Navigation Widget.
			if (
				'undefined' === typeof parentVars.parent_container ||
				!parentVars.parent_container.length
			) {
				parent_container = $(parentVars.currentTarget).parents(
					'.ld-lesson-navigation'
				);
				if (
					'undefined' !== typeof parent_container &&
					parent_container.length
				) {
					parentVars.context_sub = 'course_navigation_widget';
					parentVars.parent_container = $(
						parentVars.currentTarget
					).parents('#ld-lesson-list-' + linkVars.course_id);
				}
			}

			// Check if we are within the Focus Mode Sidebar.
			if (
				'undefined' === typeof parentVars.parent_container ||
				!parentVars.parent_container.length
			) {
				parent_container = $(parentVars.currentTarget).parents(
					'.ld-focus-sidebar-wrapper'
				);
				if (
					'undefined' !== typeof parent_container &&
					parent_container.length
				) {
					parentVars.context_sub = 'focus_mode_sidebar';
					parentVars.parent_container = $(
						parentVars.currentTarget
					).parents('#ld-lesson-list-' + linkVars.course_id);
				}
			}

			if (
				'undefined' === typeof parentVars.parent_container ||
				!parentVars.parent_container.length
			) {
				parentVars.parent_container = $(
					parentVars.currentTarget
				).closest(
					'#ld-item-list-' + linkVars.course_id,
					'#ld-lesson-list-' + linkVars.course_id
				);
			}
			if (
				'undefined' !== typeof parentVars.parent_container &&
				parentVars.parent_container.length
			) {
				$(parentVars.parent_container).addClass('ld-loading');
			} else {
				// Fallback solution.
				$('#ld-item-list-' + linkVars.course_id).addClass('ld-loading');
				$('#ld-lesson-list-' + linkVars.course_id).addClass(
					'ld-loading'
				);
			}
		}

		if ('profile' == linkVars.context) {
			$('#ld-profile #ld-main-course-list').addClass('ld-loading');
			linkVars.shortcode_instance =
				$('#ld-profile').data('shortcode_instance');
		}

		if ('profile_quizzes' == linkVars.context) {
			$(
				'#ld-course-list-item-' +
					linkVars.pager_results.quiz_course_id +
					' .ld-item-contents'
			).addClass('ld-loading');
		}

		if ('course_info_courses' == linkVars.context) {
			$('.ld-user-status').addClass('ld-loading');
			linkVars.shortcode_instance =
				$('.ld-user-status').data('shortcode-atts');
		}

		if ('group_courses' == linkVars.context) {
			linkVars.group_id = $(this).data('group_id');
			if ('undefined' !== typeof linkVars.group_id) {
				parent_container = $(parentVars.currentTarget).parents(
					'.ld-group-courses-' + linkVars.group_id
				);
				if (
					'undefined' !== typeof parent_container &&
					parent_container.length
				) {
					$(parent_container).addClass('ld-loading');
					parentVars.parent_container = parent_container;
				}
			}
		}

		$.ajax({
			type: 'GET',
			url: ldVars.ajaxurl + '?action=ld30_ajax_pager',
			data: linkVars,
			success(response) {
				// If we have a course listing, update

				if ('course_topics' == linkVars.context) {
					if ($('#ld-topic-list-' + linkVars.lesson_id).length) {
						if ('undefined' !== typeof response.data.topics) {
							$('#ld-topic-list-' + linkVars.lesson_id).html(
								response.data.topics
							);
						}

						if ('undefined' !== typeof response.data.pager) {
							$('#ld-expand-' + linkVars.lesson_id)
								.find('.ld-table-list-footer')
								.html(response.data.pager);
						}

						learndashSetMaxHeight(
							$('.ld-lesson-item-' + linkVars.lesson_id).find(
								'.ld-item-list-item-expanded'
							)
						);

						$('#ld-topic-list-' + linkVars.lesson_id).removeClass(
							'ld-loading'
						);
					}

					if (
						$('#ld-nav-content-list-' + linkVars.lesson_id).length
					) {
						if ('undefined' !== typeof response.data.nav_topics) {
							$('#ld-nav-content-list-' + linkVars.lesson_id)
								.find('.ld-table-list-items')
								.html(response.data.topics);
						}

						if ('undefined' !== typeof response.data.pager) {
							$('#ld-nav-content-list-' + linkVars.lesson_id)
								.find('.ld-table-list-footer')
								.html(response.data.pager);
						}

						$(
							'#ld-nav-content-list-' + linkVars.lesson_id
						).removeClass('ld-loading');
					}
				}

				if ('course_content_shortcode' == linkVars.context) {
					if ('undefined' !== typeof response.data.markup) {
						if (
							'undefined' !==
								typeof parentVars.parent_container &&
							parentVars.parent_container.length
						) {
							$(parentVars.parent_container).replaceWith(
								response.data.markup
							);
						} else {
							$(
								'#learndash_post_' + linkVars.course_id
							).replaceWith(response.data.markup);
						}
					}
				} else if ('course_lessons' == linkVars.context) {
					if (
						'undefined' !== typeof parentVars.parent_container &&
						parentVars.parent_container.length
					) {
						if (
							'course_navigation_widget' == parentVars.context_sub
						) {
							if (
								'undefined' !== typeof response.data.nav_lessons
							) {
								$(parentVars.parent_container)
									.html(response.data.nav_lessons)
									.removeClass('ld-loading');
							}
						} else if (
							'focus_mode_sidebar' == parentVars.context_sub
						) {
							if (
								'undefined' !== typeof response.data.nav_lessons
							) {
								$(parentVars.parent_container)
									.html(response.data.nav_lessons)
									.removeClass('ld-loading');
							}
						} else if (
							'undefined' !== typeof response.data.lessons
						) {
							$(parentVars.parent_container)
								.html(response.data.lessons)
								.removeClass('ld-loading');
						}
					} else {
						if ($('#ld-item-list-' + linkVars.course_id).length) {
							if ('undefined' !== typeof response.data.lessons) {
								$('#ld-item-list-' + linkVars.course_id)
									.html(response.data.lessons)
									.removeClass('ld-loading');
							}
						}

						if ($('#ld-lesson-list-' + linkVars.course_id).length) {
							if (
								'undefined' !== typeof response.data.nav_lessons
							) {
								$('#ld-lesson-list-' + linkVars.course_id)
									.html(response.data.nav_lessons)
									.removeClass('ld-loading');
							}
						}
					}
				}

				if ('group_courses' == linkVars.context) {
					if (
						'undefined' !== typeof parentVars.parent_container &&
						parentVars.parent_container.length
					) {
						if ('undefined' !== typeof response.data.markup) {
							$(parentVars.parent_container)
								.html(response.data.markup)
								.removeClass('ld-loading');
						}
					}
				}

				if ('profile' == linkVars.context) {
					if ('undefined' !== typeof response.data.markup) {
						$('#ld-profile').html(response.data.markup);
					}
				}

				if ('profile_quizzes' == linkVars.context) {
					if ('undefined' !== typeof response.data.markup) {
						$(
							'#ld-course-list-item-' +
								linkVars.pager_results.quiz_course_id +
								' .ld-item-list-item-expanded .ld-item-contents'
						).replaceWith(response.data.markup);
						$(
							'#ld-course-list-item-' +
								linkVars.pager_results.quiz_course_id
						)
							.get(0)
							.scrollIntoView({ behavior: 'smooth' });
					}
				}

				if ('course_info_courses' == linkVars.context) {
					if ('undefined' !== typeof response.data.markup) {
						$('.ld-user-status').replaceWith(response.data.markup);
					}
				}

				$('body').trigger('ld_has_paginated');

				initTooltips();
			},
		});
	});

	if ($('#learndash_timer').length) {
		var timer_el = jQuery('#learndash_timer');
		var timer_seconds = timer_el.attr('data-timer-seconds');
		var timer_button_el = jQuery(timer_el.attr('data-button'));

		var cookie_key = timer_el.attr('data-cookie-key');

		if ('undefined' !== typeof cookie_key) {
			var cookie_name = 'learndash_timer_cookie_' + cookie_key;
		} else {
			var cookie_name = 'learndash_timer_cookie';
		}

		cookie_timer_seconds = jQuery.cookie(cookie_name);

		if ('undefined' !== typeof cookie_timer_seconds) {
			timer_seconds = parseInt(cookie_timer_seconds);
		}

		if (0 == timer_seconds) {
			$(timer_el).hide();
		}

		$(timer_button_el).on('learndash-time-finished', function () {
			$(timer_el).hide();
		});
	}

	$(document).on('learndash_video_disable_assets', function (event, status) {
		if ('undefined' === typeof learndash_video_data) {
			return false;
		}

		if ('BEFORE' == learndash_video_data.videos_shown) {
			if (true == status) {
				$('.ld-lesson-topic-list').hide();
				$('.ld-lesson-navigation')
					.find('#ld-nav-content-list-' + ldVars.postID)
					.addClass('user_has_no_access');
				$('.ld-quiz-list').hide();
			} else {
				$('.ld-lesson-topic-list').slideDown();
				$('.ld-quiz-list').slideDown();
				$('.ld-lesson-navigation')
					.find('#ld-nav-content-list-' + ldVars.postID)
					.removeClass('user_has_no_access');
			}
		}
	});

	$('.learndash-wrapper').on(
		'click',
		'.wpProQuiz_questionListItem input[type="radio"]',
		function (e) {
			$(this)
				.parents('.wpProQuiz_questionList')
				.find('label')
				.removeClass('is-selected');
			$(this).parents('label').addClass('is-selected');
		}
	);

	$('.learndash-wrapper').on(
		'click',
		'.wpProQuiz_questionListItem input[type="checkbox"]',
		function (e) {
			if (jQuery(e.currentTarget).is(':checked')) {
				$(this).parents('label').addClass('is-selected');
			} else {
				$(this).parents('label').removeClass('is-selected');
			}
		}
	);

	function learndash_ld30_show_user_statistic(e) {
		e.preventDefault();

		var refId = jQuery(this).data('ref-id');
		var quizId = jQuery(this).data('quiz-id');
		var userId = jQuery(this).data('user-id');
		var statistic_nonce = jQuery(this).data('statistic-nonce');
		var post_data = {
			action: 'wp_pro_quiz_admin_ajax_statistic_load_user',
			func: 'statisticLoadUser',
			data: {
				quizId,
				userId,
				refId,
				statistic_nonce,
				avg: 0,
			},
		};

		jQuery('#wpProQuiz_user_overlay, #wpProQuiz_loadUserData').show();
		var content = jQuery('#wpProQuiz_user_content').hide();

		//console.log('- learndash.js');

		jQuery.ajax({
			type: 'POST',
			url: ldVars.ajaxurl,
			dataType: 'json',
			cache: false,
			data: post_data,
			error(jqXHR, textStatus, errorThrown) {},
			success(reply_data) {
				if ('undefined' !== typeof reply_data.html) {
					content.html(reply_data.html);
					jQuery('#wpProQuiz_user_content').show();

					//console.log('trigger event change - learndash.js');
					jQuery('body').trigger(
						'learndash-statistics-contentchanged'
					);

					jQuery('#wpProQuiz_loadUserData').hide();

					content.find('.statistic_data').on('click', function () {
						jQuery(this).parents('tr').next().toggle('fast');

						return false;
					});
				}
			},
		});

		jQuery('#wpProQuiz_overlay_close').on('click', function () {
			jQuery('#wpProQuiz_user_overlay').hide();
		});
	}

	function learndashSetMaxHeight(elm) {
		var totalHeight = 0;

		elm.find('> *').each(function () {
			totalHeight += $(this).outerHeight();
		});

		elm.attr('data-height', '' + (totalHeight + 50) + '');

		elm.css({
			'max-height': totalHeight + 50,
		});
	}

	/**
	 * Will scroll the position of the Focus Mode sidebar
	 * to the active step.
	 */
	function learndashFocusModeSidebarAutoScroll() {
		if (jQuery('.learndash-wrapper .ld-focus').length) {
			var sidebar_wrapper = jQuery(
				'.learndash-wrapper .ld-focus .ld-focus-sidebar-wrapper'
			);

			var sidebar_current_topic = jQuery(
				'.learndash-wrapper .ld-focus .ld-focus-sidebar-wrapper .ld-is-current-item'
			);
			if (
				'undefined' !== typeof sidebar_current_topic &&
				sidebar_current_topic.length
			) {
				var sidebar_scrollTo = sidebar_current_topic;
			} else {
				var sidebar_current_lesson = jQuery(
					'.learndash-wrapper .ld-focus .ld-focus-sidebar-wrapper .ld-is-current-lesson'
				);
				if (
					'undefined' !== typeof sidebar_current_lesson &&
					sidebar_current_lesson.length
				) {
					var sidebar_scrollTo = sidebar_current_lesson;
				}
			}

			if (
				'undefined' !== typeof sidebar_scrollTo &&
				sidebar_scrollTo.length
			) {
				var offset_top = 0;
				if (
					jQuery('.learndash-wrapper .ld-focus .ld-focus-header')
						.length
				) {
					var logo_height = jQuery(
						'.learndash-wrapper .ld-focus .ld-focus-header'
					).height();
					offset_top += logo_height;
				}
				if (
					jQuery(
						'.learndash-wrapper .ld-focus .ld-focus-sidebar .ld-course-navigation-heading'
					).length
				) {
					var heading_height = jQuery(
						'.learndash-wrapper .ld-focus .ld-focus-sidebar .ld-course-navigation-heading'
					).height();
					offset_top += heading_height;
				}
				if (
					jQuery(
						'.learndash-wrapper .ld-focus .ld-focus-sidebar .ld-focus-sidebar-wrapper'
					).length
				) {
					var container_height = jQuery(
						'.learndash-wrapper .ld-focus .ld-focus-sidebar .ld-focus-sidebar-wrapper'
					).height();
					offset_top += container_height;
				}

				var current_item_height = jQuery(sidebar_scrollTo).height();
				offset_top -= current_item_height;

				sidebar_wrapper.animate(
					{
						scrollTop: sidebar_scrollTo.offset().top - offset_top,
					},
					1000
				);
			}
		}
	}

	// Coupon processing.

	function update_payment_forms(data) {
		$('#total-row').attr('data-total', data.total.value);

		// Update PayPal form amount.
		$('form[name="buynow"] input[name="amount"]').val(data.total.value);

		// Update Stripe form amount.
		$('form.learndash-stripe-checkout input[name="stripe_price"]').val(
			data.total.stripe_value
		);

		// Remove Stripe Connect session to respect the new amount.
		const stripe_course_id = $(
			'.learndash-stripe-checkout input[name="stripe_course_id"]'
		).val();

		if (stripe_course_id) {
			LD_Cookies.remove('ld_stripe_session_id_' + stripe_course_id); // Stripe Plugin (Checkout).
			LD_Cookies.remove(
				'ld_stripe_connect_session_id_' + stripe_course_id
			); // Stripe Connect in core.
		}

		// Re-init Stripe Plugin (Legacy) to respect the new amount.
		if (typeof ld_init_stripe_legacy === 'function') {
			ld_init_stripe_legacy();
		}
	}

	$('.btn-join').on('click', function (e) {
		if ($(this).hasClass('btn-disabled')) {
			e.preventDefault();
			return false;
		}

		const total = parseFloat($('#total-row').attr('data-total'));

		if (0 === total) {
			$.ajax({
				type: 'POST',
				url: ldVars.ajaxurl,
				dataType: 'json',
				cache: false,
				data: {
					action: 'learndash_enroll_with_zero_price',
					nonce: $('#apply-coupon-form').data('nonce'),
					post_id: $('#apply-coupon-form').data('post-id'),
				},
				success(response) {
					if (response.success) {
						window.location.replace(response.data.redirect_url);
					} else {
						alert(response.data.message);
					}
				},
			});

			e.preventDefault();
			return false;
		}
	});

	$('#apply-coupon-form').on('submit', function (e) {
		e.preventDefault();

		$.ajax({
			type: 'POST',
			url: ldVars.ajaxurl,
			dataType: 'json',
			cache: false,
			data: {
				action: 'learndash_apply_coupon',
				nonce: $(this).data('nonce'),
				coupon_code: $(this).find('#coupon-field').val(),
				post_id: $(this).data('post-id'),
			},
			success(response) {
				$('#coupon-alerts .coupon-alert').hide();

				const $alert = $('#coupon-alerts').find(
					response.success
						? '.coupon-alert-success'
						: '.coupon-alert-warning'
				);

				const $coupon_row = $('#coupon-row');

				if (response.success) {
					$coupon_row
						.find('.purchase-label > span')
						.html(response.data.coupon_code); // Set coupon code in totals.
					$coupon_row
						.find('.purchase-value span')
						.html(response.data.discount); // Set discount value in totals.
					$coupon_row.css('display', 'flex').hide().fadeIn(); // Show a coupon row in totals.
					$('#total-row .purchase-value').html(
						response.data.total.formatted
					); // Update Total.
					$('#totals').show();

					update_payment_forms(response.data);
				}

				$alert.find('.ld-alert-messages').html(response.data.message);
				$alert.fadeIn();
			},
		});
	});

	$('#remove-coupon-form').on('submit', function (e) {
		e.preventDefault();

		$.ajax({
			type: 'POST',
			url: ldVars.ajaxurl,
			dataType: 'json',
			cache: false,
			data: {
				action: 'learndash_remove_coupon',
				nonce: $(this).data('nonce'),
				post_id: $(this).data('post-id'),
			},
			success(response) {
				$('#coupon-alerts .coupon-alert').hide();

				const $alert = $('#coupon-alerts').find(
					response.success
						? '.coupon-alert-success'
						: '.coupon-alert-warning'
				);

				if (response.success) {
					$('#coupon-row').hide(); // Hide a coupon row in totals.
					$('#coupon-field').val(''); // Set coupon field empty.
					$('#price-row .purchase-value').html(
						response.data.total.formatted
					); // Update Price.
					$('#subtotal-row .purchase-value').html(
						response.data.total.formatted
					); // Update Subtotal.
					$('#total-row .purchase-value').html(
						response.data.total.formatted
					); // Update Total.
					$('#totals').hide();

					update_payment_forms(response.data);
				}

				$alert.find('.ld-alert-messages').html(response.data.message);
				$alert.fadeIn();
			},
		});
	});
});

function ldGetUrlVars() {
	var vars = {};
	var parts = window.location.href.replace(
		/[?&]+([^=&]+)=([^&]*)/gi,
		function (m, key, value) {
			vars[key] = value;
		}
	);

	return vars;
}
