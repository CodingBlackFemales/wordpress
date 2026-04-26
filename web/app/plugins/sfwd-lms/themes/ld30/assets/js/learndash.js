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

		ldToggleExpandableElement(button, true);

		$('html, body').animate(
			{
				scrollTop: $(lesson).offset().top,
			},
			500
		);
	}

	// a[href="#login"] is for backwards compatibility with potentially outdated templates.
	$('body').on(
		'click',
		'a[href="#login"], button[data-ld-login-modal-trigger]',
		function (e) {
			e.preventDefault();
			openLoginModal();
		}
	);

	// By default, the element is not interactable with keyboard. We make the keypress event for space and enter key to close the login modal for accessibility.
	$('body').on('keypress', '.ld-modal-closer', function (e) {
		if (13 === e.keyCode || 32 === e.keyCode) {
			e.preventDefault();
			closeLoginModal();
		}
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
	focusMobileResizeCheck();

	disableFocusTrap();

	$('body').on('click', '.ld-focus-sidebar-trigger', function (e) {
		if ($('.ld-focus').hasClass('ld-focus-sidebar-collapsed')) {
			openFocusSidebar();
		} else {
			closeFocusSidebar();
		}
	});

	$('body').on('click', '.ld-trigger-mobile-nav', function (e) {
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
	ldRegistrationLinkInit();
	ldRegistrationLinkInitModern();

	var windowWidth = $(window).width();

	// Ensure that tooltips are positioned properly after screen size changes.
	$(window).on('resize orientationchange', function () {
		const resizeTimer = setTimeout(() => {
			const newWidth = $(window).width();

			if (newWidth === windowWidth) {
				return;
			}

			windowWidth = newWidth;

			// Wait one more animation frame after debounce to let layout settle.
			window.requestAnimationFrame(() => {
				initTooltips();
				focusMobileResizeCheck();
				clearTimeout(resizeTimer);
			});
		}, 150);
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

	/**
	 * Toggles the focus sidebar based on the window width.
	 *
	 * @since 3.2.0
	 * @since 4.21.5 Now also toggles aria-modal attribute.
	 *
	 * @return {void}
	 */
	function focusMobileResizeCheck() {
		if ($(window).width() < 1024) {
			$('#ld-focus-sidebar').attr('aria-modal', 'true');

			if (!$('.ld-focus').hasClass('ld-focus-sidebar-collapsed')) {
				closeFocusSidebar();
			}
		} else {
			$('#ld-focus-sidebar').attr('aria-modal', 'false');

			if ($('.ld-focus').hasClass('ld-focus-sidebar-filtered')) {
				closeFocusSidebar();
			} else if (
				!$('.ld-focus').hasClass('ld-focus-sidebar-filtered') &&
				$('.ld-focus').hasClass('ld-focus-sidebar-collapsed')
			) {
				openFocusSidebar();
			}
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
		$('[aria-controls="ld-focus-sidebar"]').attr('aria-expanded', 'false');
		positionTooltips();

		const sidebar = document.querySelector('.ld-focus-sidebar');

		if (sidebar) {
			sidebar.dispatchEvent(new CustomEvent('ld-focus-sidebar-closed'));
		}
		dispatchSidebarEvent(false);
	}

	/**
	 * Dispatches a custom event on to notify other scripts that the focus sidebar has been opened or closed.
	 *
	 * @since 4.23.2
	 *
	 * @param {boolean} isOpen Whether the sidebar is open.
	 *
	 * @return {void}
	 */
	function dispatchSidebarEvent(isOpen) {
		const sidebar = document.querySelector('.ld-focus-sidebar');

		if (!sidebar) {
			return;
		}

		let eventName = 'ld-focus-sidebar-closed';

		if (isOpen) {
			eventName = 'ld-focus-sidebar-opened';
		}

		sidebar.dispatchEvent(new CustomEvent(eventName));
	}

	/**
	 * Closes the focus sidebar.
	 *
	 * @since 3.0.0
	 *
	 * @return {void}
	 */
	function closeFocusSidebar() {
		// Hide the wrapper to avoid issues with focus trap.
		$('.ld-focus-sidebar-wrapper').hide();

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

		$('[aria-controls="ld-focus-sidebar"]').attr('aria-expanded', 'false');

		disableFocusTrap();

		// If the mobile trigger is visible, move focus to it.
		const mobileTrigger = $('.ld-trigger-mobile-nav');

		if (mobileTrigger.is(':visible')) {
			mobileTrigger.focus();
		}

		positionTooltips();

		const sidebar = document.querySelector('.ld-focus-sidebar');

		if (sidebar) {
			sidebar.dispatchEvent(new CustomEvent('ld-focus-sidebar-closed'));
		}
		dispatchSidebarEvent(false);
	}
	/**
	 * Handles tab key press in the focus sidebar to trap focus within the sidebar.
	 * When the last focusable element is reached, focus is redirected to the first element.
	 *
	 * @since 4.21.3
	 * @param {Event} e The keyboard event object.
	 */
	function handleTabTrap(e) {
		if (e.key === 'Tab') {
			e.preventDefault();
			$('#ld-focus-sidebar-toggle').focus();
		}
	}

	/**
	 * Enables focus trap for the sidebar to improve accessibility.
	 * Makes sidebar elements tabbable, focuses the sidebar toggle button,
	 * and adds event listener to trap focus within the sidebar.
	 *
	 * @since 4.21.3
	 */
	function enableFocusTrap() {
		// Focus sidebar for accessibility when opened, allowing keyboard navigation easier.
		$('#ld-focus-sidebar-toggle').focus();

		// Make the course heading tabbable.
		$('#ld-focus-mode-course-heading').attr('tabindex', '0');

		// Get list of focusable elements, and when last one is focused, focus on the first element to trap focus.
		const focusableElements = $('.ld-lesson-items a');
		const lastFocusableElement =
			focusableElements[focusableElements.length - 1];

		lastFocusableElement.addEventListener('keydown', handleTabTrap);
	}

	/**
	 * Disables focus trap for the sidebar when it's closed.
	 * Makes sidebar elements non-tabbable and removes the event listener.
	 *
	 * @since 4.21.3
	 */
	function disableFocusTrap() {
		// Make the course heading non-tabbable when the sidebar is closed.
		$('#ld-focus-mode-course-heading').attr('tabindex', '-1');

		if ($('.ld-focus-sidebar-trigger').attr('aria-expanded') === 'true') {
			return;
		}

		// Remove focus trap when sidebar is closed.
		const focusableElements = $('.ld-lesson-items a');
		const lastFocusableElement =
			focusableElements[focusableElements.length - 1];

		if (lastFocusableElement) {
			lastFocusableElement.removeEventListener('keydown', handleTabTrap);
		}
	}

	/**
	 * Opens the focus sidebar and enables focus trap for accessibility.
	 * Handles mobile checks, class toggling, and icon changes.
	 *
	 * @since 3.0.0
	 */
	function openFocusSidebar() {
		focusMobileCheck();

		// Show the wrapper
		$('.ld-focus-sidebar-wrapper').show();

		// Flip classes to open the focus sidebar.
		$('.ld-focus').removeClass('ld-focus-sidebar-collapsed');
		$('.ld-mobile-nav').addClass('expanded');

		// We need to wait for the sidebar to be opened before we can enable the focus trap.
		enableFocusTrap();

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

		$('[aria-controls="ld-focus-sidebar"]').attr('aria-expanded', 'true');

		const sidebar = document.querySelector('.ld-focus-sidebar');

		if (sidebar) {
			sidebar.dispatchEvent(new CustomEvent('ld-focus-sidebar-opened'));
		}
		dispatchSidebarEvent(true);

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

	$('body').on(
		'click',
		'.ld-expand-button, [data-ld-expand-button]',
		function (e) {
			e.preventDefault();

			ldToggleExpandableElement($(this));

			positionTooltips();
		}
	);

	/**
	 * Initialize expanded items to be expanded and collapsed elements to have [hidden="hidden"].
	 * Expanded elements allow "Expand All" items to expand all their children by default.
	 * Collapsed elements need [hidden="hidden"] to be set via JavaScript, so we cannot pre-load this on the server.
	 *
	 * @see https://designsystem.digital.gov/components/accordion/
	 *
	 * @since 4.21.0
	 */
	function initializeExpandableElements() {
		$('.ld-expand-button, [data-ld-expand-button], .ld-search-prompt').each(
			function (index, buttonElement) {
				ldToggleExpandableElement(
					$(buttonElement),
					$(buttonElement).attr('aria-expanded') === 'true'
				);
			}
		);
	}

	/**
	 * Focuses on the first alert on the page with a role "alert".
	 * This is required for accessibility.
	 *
	 * @since 4.21.3
	 * @since 4.21.5 Switched to a focus-based approach for better compatibility with different screen readers.
	 *
	 * @return {void}
	 */
	function initializeAlerts() {
		/**
		 * We need to give it a tabindex of -1 to allow it to be programmatically focused so it will be
		 * read out by screen readers. Unfortunately, we cannot remove it after focusing.
		 * If we do, it won't be read at all.
		 *
		 * By using -1 rather than 0, it won't show up in the tab order when navigating by keyboard.
		 *
		 * We can only target the first alert because we have no way to know when the screen reader has finished
		 * reading each alert to focus on the next one.
		 *
		 * The setTimeout() is used to force this to happen at the end of the event queue and improve compatibility.
		 */
		setTimeout(function () {
			$('.ld-alert[role="alert"]:visible')
				.first()
				.attr('tabindex', '-1')
				.focus();
		}, 500);
	}

	$(document).on(
		'ldAccordionPaginationComplete',
		'.ld-accordion',
		initializeExpandableElements
	);

	// On page load.
	initializeExpandableElements();
	initializeAlerts();

	$('body').on('click', '.ld-search-prompt', function (e) {
		e.preventDefault();

		$('#course_name_field').focus();

		ldToggleExpandableElement($(this));

		const $controls = $('#' + $(this).attr('aria-controls'));

		if ($controls.find('.ld-closer').length > 0) {
			$controls
				.find('.ld-closer')
				.attr('aria-expanded', $(this).attr('aria-expanded'));
		}
	});

	/**
	 * Handles expanding and collapsing elements on button click.
	 *
	 * @since 4.20.2
	 *
	 * @param {jQuery}  $button jQuery Element for the button that was clicked.
	 * @param {boolean} expand  Whether to expand the associated element or not. Defaults to a value based on the button's aria-expanded attribute.
	 *
	 * @return {void}
	 */
	function ldToggleExpandableElement($button, expand) {
		if ('undefined' === typeof expand) {
			// Checking !== true to handle undefined and false.
			expand = $button.attr('aria-expanded') !== 'true';
		}

		const containerID = $button.attr('aria-controls');

		if (
			typeof containerID !== 'undefined' &&
			containerID.indexOf(' ') > -1 &&
			!$button.data('ld-expanding-all')
		) {
			// We're toggling multiple elements at once via an "Expand All"-type button.

			containerID.split(' ').forEach(function (id) {
				const $element = $('[aria-controls="' + id + '"]');

				ldToggleExpandableElement($element, expand);
			});

			// Temporarily set the "ld-expanding-all" data to allow us to toggle the state of this specific button.

			$button.data('ld-expanding-all', true);

			ldToggleExpandableElement($button, expand);

			$button.data('ld-expanding-all', false);
		} else {
			// Toggle a specific button.

			// Account for edge cases where an Expand All button controls only one expandable area.
			$button = $('[aria-controls="' + containerID + '"]');

			const $container = $('#' + containerID);

			if (expand && $container.length > 0) {
				// Unhide right away.
				$container.attr('hidden', false);
			}

			$button.each(function (index, element) {
				/**
				 * Pull the initial text from a cached data attribute,
				 * that way as the button text changes we always know what it was initially.
				 */
				const dataInitialText =
					$(element).data('ld-initial-text') ||
					$(element)
						.find('.ld-text, [data-ld-expand-button-text-element]')
						.html();

				$(element).data('ld-initial-text', dataInitialText);

				const dataExpandText =
					$(element).data('ld-expand-text') || dataInitialText;
				const dataCollapseText =
					$(element).data('ld-collapse-text') || dataInitialText;

				$(element)
					.attr('aria-expanded', expand)
					.toggleClass('ld-expanded', expand);

				if (expand && dataCollapseText) {
					$(element)
						.find('.ld-text, [data-ld-expand-button-text-element]')
						.html(dataCollapseText);
				} else if (!expand && dataExpandText) {
					$(element)
						.find('.ld-text, [data-ld-expand-button-text-element]')
						.html(dataExpandText);
				}
			});

			if ($container.length <= 0) {
				/**
				 * Expand All buttons with more than one controlled element won't have a valid Container,
				 * so we shouldn't proceed.
				 */
				return;
			}

			let totalHeight = 0;
			$container.find('> *').each(function () {
				totalHeight += $(this).outerHeight();
			});

			// Writing to the attribute to make debugging easier.
			$container.attr('data-height', totalHeight + 50);

			$container.css({
				'max-height': expand ? $container.data('height') : 0,
			});

			if (!expand) {
				// If we're collapsing, we should remove this class immediately.
				$container.toggleClass('ld-expanded', expand);

				const waitForCollapsed = setInterval(function () {
					if ($container.outerHeight() === 0) {
						clearInterval(waitForCollapsed);
						$container.attr('hidden', true);
					}
				});
			} else {
				// If we're expanding, we only want to add the .ld-expanded class once fully expanded.
				const waitForExpanded = setInterval(function () {
					if ($container.outerHeight() === totalHeight) {
						clearInterval(waitForExpanded);
						$container.toggleClass('ld-expanded', expand);
					}
				});
			}
		}

		positionTooltips();
	}

	/**
	 * Initialize registration link in the classic registration page.
	 *
	 * @since 4.16.0
	 *
	 * @return {void}
	 */
	function ldRegistrationLinkInit() {
		const $loginLink = $('.registration-login-link');

		if ($loginLink.length === 0) {
			return;
		}

		if ('' !== $loginLink.attr('href')) {
			return;
		}

		$loginLink.on('click', function (e) {
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

	/**
	 * Initialize registration link in the modern registration page.
	 *
	 * @since 4.16.0
	 *
	 * @return {void}
	 */
	function ldRegistrationLinkInitModern() {
		const $loginLink = $('.ld-registration__login-link');

		if ($loginLink.length === 0) {
			return;
		}

		if ('' !== $loginLink.attr('href')) {
			return;
		}

		$(document).on('click', '.ld-registration__login-link', function (e) {
			e.preventDefault();
			let $wrapper = $(this).closest('.ld-registration__wrapper');
			$wrapper.addClass('ld-registration__wrapper--login');
			$wrapper.removeClass('ld-registration__wrapper--register');
		});

		$(document).on(
			'click',
			'.ld-registration__register-link',
			function (e) {
				e.preventDefault();
				let $wrapper = $(this).closest('.ld-registration__wrapper');
				$wrapper.removeClass('ld-registration__wrapper--login');
				$wrapper.addClass('ld-registration__wrapper--register');
			}
		);
	}

	$('body').on('click', '.ld-closer', function (e) {
		ldToggleExpandableElement($('.ld-search-prompt'), false);
		$(this).attr('aria-expanded', false);
	});

	$('body').on('touch click', '.ld-tabs-navigation .ld-tab', function () {
		const $tabContent = $('#' + $(this).attr('aria-controls'));
		if (!$tabContent.length) {
			return;
		}

		// Set other Tabs as inactive.
		$('.ld-tabs-navigation .ld-tab.ld-active')
			.removeClass('ld-active')
			.attr('aria-selected', 'false')
			.attr('tabindex', '-1');

		// Set current Tab as active.
		$(this)
			.addClass('ld-active')
			.attr('aria-selected', 'true')
			.removeAttr('tabindex');

		// Make other Tab Content panels invisible.
		$('.ld-tabs-content .ld-tab-content.ld-visible').removeClass(
			'ld-visible'
		);

		$tabContent.addClass('ld-visible');

		positionTooltips();
	});

	$('body').on('keydown', '.ld-tabs-navigation .ld-tab', function (event) {
		// If the key is not a Left/Right arrow key, Home, or End, do nothing.
		if (
			['ArrowLeft', 'ArrowRight', 'Home', 'End'].indexOf(event.key) === -1
		) {
			return;
		}

		const target = event.currentTarget;
		const $firstTab = $(target)
			.closest('[role="tablist"]')
			.find('[role="tab"]')
			.first();
		const $lastTab = $(target)
			.closest('[role="tablist"]')
			.find('[role="tab"]')
			.last();

		event.stopPropagation();
		event.preventDefault();

		switch (event.key) {
			case 'ArrowLeft':
				if (target === $firstTab[0]) {
					$lastTab.focus();
				} else {
					$(this).prev().focus();
				}
				break;
			case 'ArrowRight':
				if (target === $lastTab[0]) {
					$firstTab.focus();
				} else {
					$(target).next().focus();
				}
				break;
			case 'Home':
				$firstTab.focus();
				break;
			case 'End':
				$lastTab.focus();
				break;
			default:
				break;
		}
	});

	/**
	 * Initialize tooltips.
	 * - If JS isn't enabled, the tooltips will be visible at all times.
	 * - This adds the `ld-tooltip--initialized` class to all tooltips to signify that JS is enabled.
	 * - The `ld-tooltip--hidden` class is removed when the mouse is over a tooltip, the tooltip is focused,
	 * or the tooltip is focused-within.
	 * - The `ld-tooltip--hidden` class is added if a tooltip is visible and the Escape key is pressed.
	 *
	 * @since 4.21.3
	 *
	 * @return {void}
	 */
	function initTooltips() {
		$('.ld-tooltip').each(function () {
			const $tooltip = $(this).find('[role="tooltip"]');

			if (!$tooltip.length || typeof $tooltip[0] === 'undefined') {
				return;
			}

			if (!$(this).hasClass('ld-tooltip--initialized')) {
				$(this)
					.addClass('ld-tooltip--initialized')
					.addClass('ld-tooltip--hidden');
			}

			/**
			 * We need to temporarily remove the `ld-tooltip--hidden` class
			 * as hidden tooltips are hidden off to the left edge of the screen.
			 *
			 * We need to know where it would normally be positioned when visible
			 * to know if we need to move it to the right.
			 */
			const isHidden = $(this).hasClass('ld-tooltip--hidden');

			$(this).removeClass('ld-tooltip--hidden');
			$(this).removeClass('ld-tooltip--position-right');

			const tooltipRect = $tooltip[0].getBoundingClientRect();

			let containerWidth = windowWidth;

			/**
			 * If the tooltip is within the focus sidebar, use the sidebar width.
			 *
			 * We have to do this because when an element is vertically scrollable, a browser will simply not
			 * allow you to have items spill out the left or right sides even when the appropriate CSS for
			 * overflow-x is set to visible as it will be forced to auto implicitly.
			 *
			 * See https://developer.mozilla.org/en-US/docs/Web/CSS/overflow-x#syntax
			 */
			if ($(this).closest('.ld-focus-sidebar').length) {
				containerWidth = $(this).closest('.ld-focus-sidebar').width();
			}

			/**
			 * If the tooltip would overflow the right edge of the window,
			 * add the `ld-tooltip--position-right` class.
			 */
			$(this).toggleClass(
				'ld-tooltip--position-right',
				tooltipRect.right > containerWidth
			);

			// Add the .ld-tooltip--hidden class back if it was hidden.
			$(this).toggleClass('ld-tooltip--hidden', isHidden);
		});

		$(document).on('keydown', function (event) {
			/**
			 * We need to check if the Escape key is pressed and if any tooltips
			 * are currently visible before dismissing them.
			 */
			if (
				event.key === 'Escape' &&
				($('.ld-tooltip:hover').length ||
					$('.ld-tooltip:focus').length ||
					$('.ld-tooltip:focus-within').length)
			) {
				$(
					'.ld-tooltip:hover, .ld-tooltip:focus, .ld-tooltip:focus-within'
				).addClass('ld-tooltip--hidden');

				// Remove focus to prevent automatically scrolling the page.
				$('.ld-tooltip:focus, .ld-tooltip :focus').blur();
			}
		});

		/**
		 * For the next two event listeners:
		 *
		 * - mouseenter/mouseleave are used for hover on desktop.
		 *
		 * - focusin/focusout are used for keyboard focus on desktop.
		 * 	- These are used instead of focus/blur because the focused element is likely _within_ the tooltip container.
		 * 	- If the focused element is the tooltip container, these event names still work.
		 *
		 * - touchstart is used for touch devices.
		 */

		$(document).on(
			'mouseenter focusin touchstart',
			'.ld-tooltip--hidden',
			function () {
				// Show the tooltip.
				$(this).removeClass('ld-tooltip--hidden');
			}
		);

		$(document).on(
			'mouseleave focusout touchstart',
			'.ld-tooltip:not(.ld-tooltip--hidden)',
			function () {
				// Hide the tooltip when the mouse when the user moves away from the tooltip.
				$(this).addClass('ld-tooltip--hidden');
			}
		);
	}

	/**
	 * In order to account for container breakpoints modifying the layout,
	 * we need to initialize tooltips twice on page load.
	 *
	 * TODO: Find a better way to do this.
	 */
	initTooltips();
	initTooltips();

	var $tooltips = $('*[data-ld-tooltip]');

	initLegacyTooltips();

	/**
	 * Initialize legacy LD30 Classic tooltips.
	 * Replaced with a new style of tooltips in 4.21.3 for accessibility reasons.
	 * Kept for backwards compatibility.
	 *
	 * @since 3.0.0
	 * @since 4.21.3 Renamed from initTooltips to initLegacyTooltips.
	 *
	 * @return {void}
	 */
	function initLegacyTooltips() {
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

			$('.ld-modal', modal_wrapper).focus();
		}
	}

	function closeLoginModal() {
		var modal_wrapper = $('.learndash-wrapper-login-modal');
		if ('undefined' !== typeof modal_wrapper && modal_wrapper.length) {
			$(modal_wrapper).removeClass('ld-modal-open');
			$(modal_wrapper).addClass('ld-modal-closed');

			// Return the focus to the login link that triggers the modal.
			$('[data-ld-login-modal-trigger]').focus();
		}
	}

	/**
	 * Position legacy LD30 Classic tooltips.
	 * This is not used by the new tooltips added in 4.21.3.
	 * Kept for backwards compatibility.
	 *
	 * @since 3.0.0
	 *
	 * @return {void}
	 */
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
					ldToggleExpandableElement(
						'#ld-profile .ld-search-prompt',
						true
					);
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
					ldToggleExpandableElement(
						'#ld-profile .ld-search-prompt',
						true
					);
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

		const supportsCoupon = $('#total-row').attr('data-supports-coupon');

		if (!supportsCoupon) {
			return;
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
		const $el = $(this);
		const $wrapper = $el.closest('.ld-registration-order__items');

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
				const isModernRegistration = $(
					'.ld-form__field-coupon_field'
				).length;

				if (isModernRegistration) {
					$('#coupon-alerts .coupon-alert').hide();

					const $alert = $('#coupon-alerts').find(
						response.success
							? '.coupon-alert-success'
							: '.coupon-alert-warning'
					);

					if (response.success) {
						$wrapper
							.find('.ld-coupon__label-text')
							.html(response.data.coupon_code); // Set coupon code in totals.

						$wrapper
							.find('.ld-coupon__value')
							.html('(' + response.data.discount + ')'); // Set discount value in totals.

						$wrapper
							.find('.ld-registration-order__item-price-value')
							.html(response.data.total.formatted); // Update course/group price.

						$wrapper
							.find('.ld-registration-order__total-price')
							.html(response.data.total.formatted); // Update Total.

						$wrapper.addClass(
							'ld-registration-order__items--with-coupon'
						);

						update_payment_forms(response.data);
					}

					$alert
						.find('.ld-alert-messages')
						.html(response.data.message);
					$alert.fadeIn();
				} else {
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

					$alert
						.find('.ld-alert-messages')
						.html(response.data.message);
					$alert.fadeIn();
				}
			},
		});
	});

	$('#remove-coupon-form').on('submit', function (e) {
		e.preventDefault();
		const $el = $(this);
		const $wrapper = $el.closest('.ld-registration-order__items');

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
				const isModernRegistration = $(
					'.ld-form__field-coupon_field'
				).length;

				if (isModernRegistration) {
					$('#coupon-alerts .coupon-alert').hide();

					const $alert = $('#coupon-alerts').find(
						response.success
							? '.coupon-alert-success'
							: '.coupon-alert-warning'
					);

					if (response.success) {
						$wrapper.removeClass(
							'ld-registration-order__items--with-coupon'
						);
						$wrapper.find('.ld-form__field-coupon_field').val(''); // Set coupon field empty.
						$wrapper
							.find('.ld-registration-order__item-price-value')
							.html(response.data.total.formatted); // Update course/group price.
						$wrapper
							.find('.ld-registration-order__total-price')
							.html(response.data.total.formatted); // Update Total.

						update_payment_forms(response.data);
					}

					$alert
						.find('.ld-alert-messages')
						.html(response.data.message);
					$alert.fadeIn();
				} else {
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

					$alert
						.find('.ld-alert-messages')
						.html(response.data.message);
					$alert.fadeIn();
				}
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
