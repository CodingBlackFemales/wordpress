/* eslint-disable -- TODO: Fix linting issues */

var learnDashProPanel = jQuery( function ( $ ) {

	var widgetObjects = {};
	var currentFilters = {
		type:  'course',
		id: null,
		courseStatus: null,
		search: null,
		groups: '',
		courses: '',
		users: '',
		time_start: '',
		time_end: '',
		dateStart: '',
		dateEnd: '',
		reporting_pager: {},
		activity_pager: {},
	};
	var selectedUserIds = [];
	var allUserIds = [];
	var proPanelTable;
	var containerType;

	initialize();

	function getCurrentFilters() {
		return currentFilters;
	}

	/**
	 * Initialize ProPanel
	 */
	function initialize() {
		var is_debug = getQSParameterByName('LD_DEBUG');
		if ( ( typeof is_debug !== 'undefined' ) && ( is_debug != '' ) ) {
			ld_propanel_settings.is_debug = is_debug;
		}

		onScreenResize();

		$( document ).trigger( 'proPanel.loadWidgets' );

		$( document ).on( 'proPanel.templateLoaded', function ( event, template ) {

			if ( template == 'filtering' ) {
				propanelToggles();
				loadSelect2s();
				initDateFilters();
				requireEmailFields();
				maybeHideEmailBox( );

				widgetObjects['filtering'].on( 'click', 'button.filter', function() {
					for ( const key in widgetObjects ) {
						if ( key === 'filtering' ) {
							continue;
						}

						let data = widgetObjects[ key ].data( 'filters' ) || { filters: {} };

						// Override any configured filters since the Filtering widget should take priority if the form was submitted.
						data.filters = {};

						widgetObjects[ key ].data(
							'filters',
							data
						);
					}

					filterReporting();
				} );
				widgetObjects['filtering'].on( 'click tap', '#propanel-send-email', sendEmailUsers );
				widgetObjects['filtering'].on( 'click tap', '#propanel-reset-email', resetEmailUsers );

				widgetObjects['filtering'].on( 'click', 'button.reset', resetReporting );
			}

			if ( template == 'reporting' ) {

				$( document ).on( 'proPanel.setSelectedUsers', updateSelectedCount );
				$( document ).on( 'proPanel.setSelectedUsers', updateSelectedAll );
				$( document ).on( 'click', '.ld-propanel-widget-reporting .ld-propanel-reporting-pager-info button', pagerReporting );
				$( document ).on( 'click', '.ld-propanel-widget-reporting button.reporting-download', downloadReporting );

				if ( typeof widgetObjects['reporting'] !== 'undefined' ) {
					var reportingContainer = $( '.propanel-reporting', widgetObjects['reporting'] );

					if ( currentFilters.type == 'group' ) {
						loadTemplate( reportingContainer, 'group-reporting' );
					} else if ( currentFilters.type == 'course' ) {
						loadTemplate( reportingContainer, 'course-reporting' );
					} else if ( currentFilters.type == 'user' ) {
						loadTemplate( reportingContainer, 'user-reporting' );
					}
				}
				loadReportingTable();
			}

			if ( template == 'course-reporting' ) {
				loadReportingTable();
				setSelectedUsers();
				showReportingButton();

				// change selected users when checkbox is checked or searched
				proPanelTable.on( 'change', 'tbody input.ld-propanel-report-checkbox:checkbox', handleSelectedUsers );
				proPanelTable.on( 'change', 'thead input.ld-propanel-report-checkbox:checkbox', handleSelectedAllUsers );
				$( document ).on( 'proPanel.reportingTableUpdated', setSelectedUsers );
				$( document ).on( 'proPanel.reportingTableUpdated', updateSelectedAll );
				$( document ).on( 'proPanel.reportingTableUpdated', reporting_init_search );

			} else if ( template == 'user-reporting' ) {
				loadReportingTable();
				setSelectedUsers();
				showReportingButton();

				$( document ).on( 'proPanel.reportingTableUpdated', setSelectedUsers );
				$( document ).on( 'proPanel.reportingTableUpdated', reporting_init_search );

			} else if ( template == 'group-reporting' ) {
				loadReportingTable();
				setSelectedUsers();
				showReportingButton();

				// change selected users when checkbox is checked or searched
				proPanelTable.on( 'change', 'tbody input.ld-propanel-report-checkbox:checkbox', handleSelectedUsers );
				proPanelTable.on( 'change', 'thead input.ld-propanel-report-checkbox:checkbox', handleSelectedAllUsers );
				$( document ).on( 'proPanel.reportingTableUpdated', setSelectedUsers );
				$( document ).on( 'proPanel.reportingTableUpdated', updateSelectedAll );
				$( document ).on( 'proPanel.reportingTableUpdated', reporting_init_search );

			}

			if ( template == 'activity' ) {
				$( document ).on( 'proPanel.filterChanged', loadActivity );
			}

			if ( ( template == 'activity_rows' ) || ( template == 'activity' ) ) {
				$( '.report-pagination .ld-propanel-reporting-pager-info > button', widgetObjects['activity'] ).on( 'click', processActivityPagination );

				$( document ).on( 'click', 'button.download-activity', downloadActivity );
			}

			if ( template == 'progress-chart' ) {
				$( document ).on( 'proPanel.filterChanged', getProgressChartsData );
				getProgressChartsData();
			}
		});

		loadWidgets();
		setContainerType();
		if ( typeof widgetObjects['filtering'] === 'undefined' ) {
			if ( typeof currentFilters !== 'undefined' ) {
				if ( typeof widgetObjects['reporting'] !== 'undefined' ) {
					if (( currentFilters.id == '') || ( currentFilters.type == '' )) {
						delete widgetObjects['reporting'];
					}
				}

				if ( typeof widgetObjects['progress-chart'] !== 'undefined' ) {
					if (( currentFilters.id == '') || ( currentFilters.type == '' )) {
						delete widgetObjects['progress-chart'];
					}
					$( document ).on( 'proPanel.filterChanged', getProgressChartsData );
				}
			}
		}
	}

	/**
	 * Initialize all widgets.
	 */
	function loadWidgets() {
		var widgetElements = $( '.ld-propanel-widget' );

		// We load all the widget elements first.
		$.each( widgetElements, function () {
			var widget_id = $( this ).data( 'ld-widget-type' );
			widgetObjects[ widget_id ] = $( this );
		} );

		// Then in this next loop we load in the filters.

		$.each( widgetObjects, function () {
			var widget_id = $( this ).data( 'ld-widget-type' ),
				filters = $( this ).data( 'filters' );

			if ( typeof widgetObjects['filtering'] === 'undefined' ) {
				if ( ( typeof filters !== 'undefined' ) && ( filters != '' ) ) {

					for (var filter_key in currentFilters ) {
						if ( ( typeof filters[filter_key] !== 'undefined' ) && ( filters[filter_key] != '' ) ) {
							if ( filter_key == 'reporting_pager' ) {
								if ( typeof widgetObjects['filtering'] === 'undefined' ) {
									currentFilters[filter_key] = filters[filter_key];
								}
							} else {
								currentFilters[filter_key] = filters[filter_key];
							}
						}
					}
				}
			}

			loadTemplate( widgetObjects[ widget_id ], widget_id, filters );
		} );

		if ( Object.keys(widgetObjects).length ) {
			if ( typeof currentFilters.reporting_pager['per_page'] === 'undefined' ) {
				currentFilters.reporting_pager['per_page'] = ld_propanel_settings.default_per_page;
				currentFilters.reporting_pager['current_page'] = 1;
			}
		}
	}

	function setContainerType() {
		if (( containerType == '' ) || ( containerType == null )) {

			if ( $( '#learndash-propanel-reporting' ).length ) {
				if ( $( '#learndash-propanel-reporting' ).hasClass( 'single-view' ) ) {
					containerType = 'full';
				} else {
					containerType = 'widget';
				}
			} else {
				containerType = 'widget';
			}
		}
	}

	/**
	 * Load a template via AJAX
	 *
	 * If data comes along with the response that other areas of propanel need to use, add it
	 * Add/remove a spinner while loading
	 *
	 * @param element
	 * @param template
	 * @param args
	 */
	function loadTemplate( element, template, args ) {
		showSpinner( element );

		if ( typeof args === 'undefined' ) {
			args = {};
		}

		// For Activity and Activity_rows we want to pass the per_page size to the server.
		if ( ( template == 'activity' ) || ( template == 'activity_rows' ) ) {

			if ( jQuery('#dashboard-widgets').length ) {

				var per_page = jQuery('select#ld-propanel-pagesize').val();
				if ( typeof per_page !== 'undefined' ) {
					args['per_page'] = per_page;
				}
			}
		}

		const filters = Object.assign(
			{},
			currentFilters,
			args.filters || {}
		);

		return $.when(
			$.ajax( {
				url: ld_propanel_settings.ajaxurl,
				method: 'get',
				dataType: 'json',
				data: {
					'action': 'learndash_propanel_template',
					'template': template,
					'filters': filters,
					'container_type': containerType,
					'args' : args,
					'nonce': ld_propanel_settings.nonce,
					'lang': ld_propanel_settings.lang,
				}
			})
		).done(function (response) {
			hideSpinner(element);

			if (response.hasOwnProperty('success')) {
				if (typeof response.data.output.rows_html !== 'undefined') {
					element.html(response.data.output.rows_html);
				} else if (typeof response.data.output !== 'undefined') {
					element.html(response.data.output);
				}

				$(document).trigger('proPanel.templateLoaded', [template]);

				$(window).trigger('resize');
			}
		});
	}

	function showSpinner( element ) {
		if ((typeof ld_propanel_settings.is_dashboard !== 'undefined') && (ld_propanel_settings.is_dashboard == '1' ))  {
			// Dashboard widgets...
			var spinnerExists = element.parents('.postbox').find( '.loading' );
			if (spinnerExists.length) {
				return;
			}
			var widgetTitle = element.parents('.postbox').find('h2.hndle');
			if (typeof widgetTitle !== 'undefined') {
				widgetTitle.append('<img src="' + ld_propanel_settings.spinner_admin_img + '" class="loading">');
			}
		} else if (jQuery('body').hasClass('.ld-propanel-full-page')) {
			// Front-end full page template...
			var spinnerExists = element.prev('h2').find('.loading');
			if (spinnerExists.length) {
				return;
			}
			var widgetTitle = element.prev('h2');
			if (typeof widgetTitle !== 'undefined') {
				widgetTitle.append('<img src="' + ld_propanel_settings.spinner_admin_img + '" class="loading">');
			}
		} else {
			// Shortcodes...
			element.prepend('<img src="' + ld_propanel_settings.spinner_admin_img + '" class="loading">');
		}
	}

	function hideSpinner( element ) {
		setTimeout( function() {
			if ((typeof ld_propanel_settings.is_dashboard !== 'undefined') && (ld_propanel_settings.is_dashboard == '1')) {
				// Dashboard widgets...
				element.parents('.postbox').find('.loading').remove();
			} else if(jQuery('body').hasClass('.ld-propanel-full-page')) {
				// Front-end full page template...
				element.prev('h2').find('.loading').remove();
			} else {
				// Shortcodes...
				element.find('.loading').remove();
			}
		}, 500 );
	}

	/**
	 * Initialize Tablesorter Reporting Tables
	 */
	function loadReportingTable() {
		if ( typeof widgetObjects['reporting'] !== 'undefined' ) {
			proPanelTable = widgetObjects['reporting'].find( '.tablesorter' );
			var page_size =jQuery('select#ld-propanel-pagesize').val();
			var search =jQuery('select#ld-propanel-pagesize').val();

			const args = widgetObjects['reporting'].data( 'filters' ) || {};

			let filters = Object.assign(
				{},
				currentFilters,
				args.filters || {}
			);

			filters = Object.assign(
				{
					type: 'course', // Fallback if nothing is set.
				},
				filters
			);

			/**
			 * Ensure that these secondary keys are set.
			 * This is important for the Frontend widgets. This does not impact the Dashboard widgets or Blocks within the Block Editor.
			 */
			if ( filters.type === 'group' ) {
				filters.groups = filters.id;
			} else if ( filters.type === 'course' ) {
				filters.courses = filters.id;
			} else {
				filters.users = filters.id;
			}

			$.when(
				$.ajax( {
					url: ld_propanel_settings.ajaxurl,
					method: 'get',
					dataType: 'json',
					data: {
						'action': 'learndash_propanel_reporting_get_result_rows',
						'nonce' : ld_propanel_settings.nonce,
						'filters' : filters,
						'container_type' : containerType,
						'lang': ld_propanel_settings.lang,
					}
				})
			).done(function (response) {
				if (typeof response['rows_html'] !== 'undefined') {
					proPanelTable.find('tbody').html(response['rows_html']);

					// Logic here is if there is no filtering widget then we can't send emails so hide the checkbox column
					if (typeof widgetObjects['filtering'] === 'undefined') {
						proPanelTable.find('thead th.ld-propanel-reporting-col-checkbox').hide();
						proPanelTable.find('tbody td.ld-propanel-reporting-col-checkbox').hide();
					}
				}

				$(window).trigger('resize');


				if (typeof response['pager'] !== 'undefined') {

					// save the reporting pager details.
					currentFilters.reporting_pager = response['pager'];

					$('.ld-propanel-widget-reporting .pager .ld-propanel-reporting-pager-info').show();
					$('.ld-propanel-widget-reporting .pager .ld-propanel-reporting-pager-info span.pagedisplay span.current_page').html(response['pager']['current_page']);
					$('.ld-propanel-widget-reporting .pager .ld-propanel-reporting-pager-info span.pagedisplay span.total_pages').html(response['pager']['total_pages']);
					$('.ld-propanel-widget-reporting .pager .ld-propanel-reporting-pager-info span.pagedisplay span.total_items').html(response['pager']['total_items']);

					if (parseInt(response['pager']['current_page']) == 1) {
						$('.ld-propanel-widget-reporting .pager .ld-propanel-reporting-pager-info button.first').attr('disabled', true);
						$('.ld-propanel-widget-reporting .pager .ld-propanel-reporting-pager-info button.prev').attr('disabled', true);
					} else {
						$('.ld-propanel-widget-reporting .pager .ld-propanel-reporting-pager-info button.first').attr('disabled', false);
						$('.ld-propanel-widget-reporting .pager .ld-propanel-reporting-pager-info button.prev').attr('disabled', false);
					}

					if (parseInt(response['pager']['current_page']) == parseInt(response['pager']['total_pages'])) {
						$('.ld-propanel-widget-reporting .pager .ld-propanel-reporting-pager-info button.last').attr('disabled', true);
						$('.ld-propanel-widget-reporting .pager .ld-propanel-reporting-pager-info button.next').attr('disabled', true);
					} else {
						$('.ld-propanel-widget-reporting .pager .ld-propanel-reporting-pager-info button.last').attr('disabled', false);
						$('.ld-propanel-widget-reporting .pager .ld-propanel-reporting-pager-info button.next').attr('disabled', false);
					}
				} else {
					currentFilters.reporting_pager = {};
					$('.ld-propanel-widget-reporting .pager .ld-propanel-reporting-pager-info span.pagedisplay span.current_page').html('0');
					$('.ld-propanel-widget-reporting .pager .ld-propanel-reporting-pager-info span.pagedisplay span.total_pages').html('0');
					$('.ld-propanel-widget-reporting .pager .ld-propanel-reporting-pager-info span.pagedisplay span.total_items').html('0');
					$('.ld-propanel-widget-reporting .pager .ld-propanel-reporting-pager-info').hide();
				}

				updateSelectedCount();
				maybeHideEmailBox();

				$(document).trigger('proPanel.reportingTableUpdated');
			});
		}
	}

	/**
	 * Initialize Select2 dropdowns
	 */
	function loadSelect2s() {
		//show_hide_FilterActionButton();
		init_reporting_per_page();

		$('.filter-selection select', widgetObjects['filtering'] ).each(function () {
			var filter_select = $( this );
			var filter_section = $( filter_select ).parent('.filter-selection');
			var filter_key = $( filter_section ).data('filter-key');

			if ( ( typeof filter_key !== 'undefined' ) && ( filter_key != '' ) ) {
				$(filter_select).select2( {
					theme: 'ld_propanel',
					dir: (window.isRtl) ? 'rtl' : '',
					//dropdownAutoWidth: true,
					//width: 'resolve',
					ajax: ajaxGetSelect2Data( filter_key ),
					containerCssClass : "filter-"+filter_key+"-select2"
				} );
			}

			$( filter_select ).change( function() {

				var filter_select_changed = $( this );
				var filter_val = $( this ).val();
				if ( filter_val == null ) {
					$( this ).val('');
					filter_val = '';
				}

				var filter_section = $( this ).parent('.filter-selection');
				var filter_key = $( filter_section ).data('filter-key');
				if ( ( typeof filter_key !== 'undefined' ) && ( filter_key != '' ) ) {
					currentFilters[filter_key] = filter_val;

					//show_hide_FilterActionButton();
				}
			});
		});
	}

	function initDateFilters() {

		if (typeof ld_propanel_settings.flatpickr_locale.months !== 'undefined') {
			if (typeof ld_propanel_settings.flatpickr_locale.months.longhand !== 'undefined') {
				flatpickr.l10ns.default.months.longhand = ld_propanel_settings.flatpickr_locale.months.longhand;
			}

			if (typeof ld_propanel_settings.flatpickr_locale.months.shorthand !== 'undefined') {
				flatpickr.l10ns.default.months.shorthand = ld_propanel_settings.flatpickr_locale.months.shorthand;
			}
		}

		if (typeof ld_propanel_settings.flatpickr_locale.weekdays !== 'undefined') {
			if (typeof ld_propanel_settings.flatpickr_locale.weekdays.longhand !== 'undefined') {
				flatpickr.l10ns.default.weekdays.longhand = ld_propanel_settings.flatpickr_locale.weekdays.longhand;
			}

			if (typeof ld_propanel_settings.flatpickr_locale.weekdays.shorthand !== 'undefined') {
				flatpickr.l10ns.default.weekdays.shorthand = ld_propanel_settings.flatpickr_locale.weekdays.shorthand;
			}
		}

		if (typeof ld_propanel_settings.flatpickr_locale.time_24hr !== 'undefined') {
			if (ld_propanel_settings.flatpickr_locale.time_24hr) {
				flatpickr.l10ns.default.time_24hr = true;
			} else {
				flatpickr.l10ns.default.time_24hr = false;
			}
		}

		if (typeof ld_propanel_settings.flatpickr_locale.hourAriaLabel !== 'undefined') {
			flatpickr.l10ns.default.hourAriaLabel = ld_propanel_settings.flatpickr_locale.hourAriaLabel;
		}
		if (typeof ld_propanel_settings.flatpickr_locale.minuteAriaLabel !== 'undefined') {
			flatpickr.l10ns.default.minuteAriaLabel = ld_propanel_settings.flatpickr_locale.minuteAriaLabel;
		}
		if (typeof ld_propanel_settings.flatpickr_locale.monthAriaLabel !== 'undefined') {
			flatpickr.l10ns.default.monthAriaLabel = ld_propanel_settings.flatpickr_locale.monthAriaLabel;
		}
		if (typeof ld_propanel_settings.flatpickr_locale.rangeSeparator !== 'undefined') {
			flatpickr.l10ns.default.rangeSeparator = ld_propanel_settings.flatpickr_locale.rangeSeparator;
		}
		if (typeof ld_propanel_settings.flatpickr_locale.scrollTitle !== 'undefined') {
			flatpickr.l10ns.default.scrollTitle = ld_propanel_settings.flatpickr_locale.scrollTitle;
		}
		if (typeof ld_propanel_settings.flatpickr_locale.toggleTitle !== 'undefined') {
			flatpickr.l10ns.default.toggleTitle = ld_propanel_settings.flatpickr_locale.toggleTitle;
		}
		if (typeof ld_propanel_settings.flatpickr_locale.weekAbbreviation !== 'undefined') {
			flatpickr.l10ns.default.weekAbbreviation = ld_propanel_settings.flatpickr_locale.weekAbbreviation;
		}
		if (typeof ld_propanel_settings.flatpickr_locale.yearAriaLabel !== 'undefined') {
			flatpickr.l10ns.default.yearAriaLabel = ld_propanel_settings.flatpickr_locale.yearAriaLabel;
		}

		var fp = flatpickr(".filter-selection.filter-section-date .ld_filter_section_date", {
			allowInput: false,
			//mode: "range",
			enableTime: true,
			dateFormat: "Y-m-d H:i:s",
			altInput: true,
			altFormat: ld_propanel_settings.flatpickr_date_time_format,

			maxDate: new Date().fp_incr(1) // 1 days from now
		})
	}
	function show_hide_FilterActionButton() {
		if ( ( currentFilters.groups == '' ) && ( currentFilters.courses == '' ) && ( currentFilters.users == '' )) {
			jQuery('button.filter', widgetObjects['filtering'] ).attr('disabled', true);
		} else {
			jQuery('button.filter', widgetObjects['filtering'] ).attr('disabled', false);
		}
	}

	function init_reporting_per_page() {
		var reporting_pagesize_selector = 'select#ld-propanel-pagesize';
		if ( $(reporting_pagesize_selector).length ) {

			if ( typeof currentFilters.reporting_pager === 'undefined' )
				currentFilters.reporting_pager = [];

			currentFilters.reporting_pager['per_page'] = jQuery(reporting_pagesize_selector+' option:checked').val();
			if ( typeof currentFilters.reporting_pager['per_page'] === 'undefined' ) {
				currentFilters.reporting_pager['per_page'] = $(reporting_pagesize_selector).find('option:first-child').val();
			}

			if ( typeof currentFilters.reporting_pager['current_page'] === 'undefined' ) {
				currentFilters.reporting_pager['current_page'] = 1
			}

			$( document ).on( 'change', reporting_pagesize_selector, function() {
				currentFilters.reporting_pager['per_page'] = $(this).val();
			} );
		}
	}


	/**
	 * Populate Select2 dropdowns with data
	 *
	 * @param action
	 * @returns {{url, dataType: string, method: string, delay: number, data: data, processResults: processResults}}
	 */
	function ajaxGetSelect2Data( filter_key ) {
		return {
			url: ld_propanel_settings.ajaxurl,
			dataType: 'json',
			method: 'get',
			delay: 1000,
			cache: true,
			data: function ( params ) {
				return {
					'action': 'learndash_propanel_filters_search',
					'filter_key': filter_key,
					'filters': currentFilters,
					'search': params.term || '',
					'page': params.page || 1,
					'nonce': ld_propanel_settings.nonce,
					'lang': ld_propanel_settings.lang,
				};
			},
			processResults: function ( response, params ) {
				params.page = params.page || 1;

				return {
					results: response.data.items,
					pagination: {
						more: ( params.page * 10 ) < response.data.total
					}
				};
			},
		}
	}

	/**
	 * Toggles
	 */
	function propanelToggles() {
		widgetObjects['filtering'].on( 'click tap', '.section-toggle', function () {
			var $showThis = $( this ).attr( 'href' );
			$( this ).toggleClass( 'active' ).siblings().removeClass( 'active' );
			$( '' + $showThis + '' ).toggleClass( 'display' ).siblings().removeClass( 'display' );
			return false;
		} );

		//widgetObjects['reporting'].on( 'click tap', '.close', function () {
		//	$( '.section-toggle' ).removeClass( 'active' );
		//	$( '.toggle-section' ).removeClass( 'display' );
		//	return false;
		//} );
	}

	/**
	 * Set Selected Users
	 *
	 * If User, set the single user
	 * If Courses, set all the filtered users.  If users are checked, set those as long
	 * as they are filtered.
	 */
	function handleSelectedUsers( e ) {
		if ( typeof e !== 'undefined' ) {
			if ( currentFilters.type == 'user' ) {
				selectedUserIds = [];
				selectedUserIds.push( $( '.user.select2' ).val() );
			}

			if (( currentFilters.type == 'course' ) || ( currentFilters.type == 'group' )) {

				var current_checkbox = e.currentTarget;
				var user_id = $(current_checkbox).attr('data-user-id');
				var user_id_checked = false;
				if ( $(e.currentTarget).is(':checked') ) {
					var user_id_checked = true;
					proPanelTable.find( 'input.ld-propanel-report-checkbox[data-user-id="'+user_id+'"]' ).attr('checked', user_id_checked );
					selectedUserIds.push(user_id);
				} else {
					var user_id_checked = false;
					proPanelTable.find( 'input.ld-propanel-report-checkbox[data-user-id="'+user_id+'"]' ).attr('checked', user_id_checked );
					selectedUserIds.splice( $.inArray(user_id, selectedUserIds), 1 );
				}

				if ( selectedUserIds.length ) {
					var selectedUserIds_unique = [];
					$.each( selectedUserIds, function(i, el){
						if ( $.inArray(el, selectedUserIds_unique ) === -1 ) selectedUserIds_unique.push( el );
					});
					selectedUserIds = selectedUserIds_unique;
				}
			}

			// Let everyone know that we've set selected user(s)
			$( document ).trigger( 'proPanel.setSelectedUsers' );
		}
	}

	function handleSelectedAllUsers( e ) {
		if ( typeof e !== 'undefined' ) {
			var allChecked = jQuery( e.currentTarget ).prop('checked');
			proPanelTable.find( 'tbody input.ld-propanel-report-checkbox' ).each( function( idx, cb ) {
				$( cb ).attr('checked', allChecked ).trigger( 'change' );
			});
		}
	}

	// Called after the Reporting table rows are update.
	function updateSelectedAll( ) {

		if ( proPanelTable.find( 'tbody input.ld-propanel-report-checkbox' ).length ) {
			proPanelTable.find( 'thead input.ld-propanel-report-checkbox').show();
			if ( proPanelTable.find( 'tbody input.ld-propanel-report-checkbox:checkbox:not(:checked)' ).length ) {
				proPanelTable.find( 'thead input.ld-propanel-report-checkbox').attr('checked', false );
			} else {
				proPanelTable.find( 'thead input.ld-propanel-report-checkbox').attr('checked', true );
			}
		} else {
			proPanelTable.find( 'thead input.ld-propanel-report-checkbox').hide();
		}
	}

	// Here we just filtered or paged the reporting rows and need to update the state of the checkboxes of the rows.
	function setSelectedUsers() {

		if ( currentFilters.type == 'user' ) {
			selectedUserIds = [];
			selectedUserIds.push( $( '.user.select2' ).val() );
		}

		if (( currentFilters.type == 'course' ) || ( currentFilters.type == 'group' )) {
			if ( selectedUserIds.length ) {
				$.each( selectedUserIds, function( user_idx, user_id ) {
					proPanelTable.find( 'input.ld-propanel-report-checkbox[data-user-id="'+user_id+'"]' ).attr('checked', true );
				});
			}
		}
	}


	function showReportingButton() {
		if ( typeof widgetObjects['reporting'] !== 'undefined' ) {
			if ( typeof widgetObjects['activity'] !== 'undefined' ) {
				// If we have both the reporting and activity we don't need the reporting download button. So hide it.
				jQuery('p.download-button-wrap', widgetObjects['reporting']).hide();
			} else {
				// Else we show it.
				jQuery('p.download-button-wrap', widgetObjects['reporting']).show();
			}
		}
	}

	/**
	 * Email Box only shows when we have users selected
	 */
	function maybeHideEmailBox( ) {
		if ( ( typeof currentFilters.reporting_pager['total_items'] !== 'undefined' ) && ( parseInt( currentFilters.reporting_pager['total_items'] ) > 0 ) ) {
			$( '.email .no-results' ).hide();
			$( '.email .results' ).show();
		} else {
			$( '.email .no-results' ).show();
			$( '.email .results' ).hide();
		}
	}

	/**
	 * Update selected user count in button
	 */
	function updateSelectedCount() {
		if ( currentFilters.type == 'user' ) {
			//$( '#propanel-send-email' ).find( 'span.count' ).html( '' );
			//$( '#propanel-send-email' ).find( 'span.selected' ).hide();
			$( 'a.email-toggle').find( 'span.count' ).html( '' );
		} else {
			var selected_user_count = 0;
			if ( selectedUserIds.length )
				selected_user_count = selectedUserIds.length;
			//else
			//	selected_user_count = currentFilters.total_users;

			//$( '#propanel-send-email' ).find( 'span.selected' ).show();
			//$( '#propanel-send-email' ).find( 'span.count' ).html( selected_user_count );

			if ( selected_user_count > 0 ) {
				$( 'a.email-toggle').find( 'span.count' ).html( ' ('+selected_user_count+')' );
			} else {
				$( 'a.email-toggle').find( 'span.count' ).html( '' );
			}
		}
	}

	/**
	 * Disable Send button unless Subject/Message is not empty
	 */
	function requireEmailFields() {
		if ( typeof widgetObjects['filtering'] !== 'undefined' ) {

			$( '#email', widgetObjects['filtering'] ).on( 'keyup', '.subject, .message', function () {
				var subject = $( '#email .subject', widgetObjects['filtering'] ).val();
				var message = $( '#email .message', widgetObjects['filtering'] ).val();

				var sendButton = $( '#propanel-send-email' );
				var resetButton = $( '#propanel-reset-email' );

				if ( subject == '' || message == '' ) {
					sendButton.prop( 'disabled', true );
					resetButton.prop( 'disabled', true );
				} else {
					sendButton.prop( 'disabled', false );
					resetButton.prop( 'disabled', false );
				}
			} );
		}
	}

	/**
	 * Email Users
	 *
	 * If rows are checked, grab only those User ID's for rows that are checked and not filtered
	 * If no rows are checked, grab all User ID's for rows that are not filtered
	 */
	function sendEmailUsers() {
		var emailContainer, subject, message, sending, sent, sendButton;

		if ( typeof widgetObjects['filtering'] !== 'undefined' ) {

			emailContainer = $( '#email', widgetObjects['filtering'] );
			subject = emailContainer.find( '.subject' ).val();
			message = emailContainer.find( '.message' ).val();

			if ( ! selectedUserIds ) {
				return;
			}

			sending = emailContainer.find( '.sending' );
			sent = emailContainer.find( '.sent' );
			sendButton = emailContainer.find( '#propanel-send-email' );

			sending.show();
			sendButton.prop( 'disabled', true );

			$.when(
				$.ajax( {
					url: ld_propanel_settings.ajaxurl,
					method: 'post',
					dataType: 'json',
					data: {
						'action': 'learndash_propanel_email_users',
						'user_ids': selectedUserIds.join(),
						'subject': subject,
						'message': message,
						'filters': currentFilters,
						'nonce': ld_propanel_settings.nonce,
						'is_debug': ld_propanel_settings.is_debug,
						'lang': ld_propanel_settings.lang,
					}
				})
			).done(function (response) {
				if (response.success) {
					if ((typeof response.data.message !== 'undefined') && (response.data.message != '')) {
						sent.html(response.data.message);
					}
					sent.fadeIn();

					sending.hide();

					if ((typeof response.data.debug !== 'undefined') && (response.data.debug !== '') && (ld_propanel_settings.is_debug)) {
						alert(response.data.debug);
					}

					setTimeout(function () {
						sent.fadeOut();
						sent.html('');
						sendButton.prop('disabled', false);
					}, 3000);
				} else {
					alert(response.data.message);
				}
			});
		}
	}

	function resetEmailUsers() {
		if ( typeof widgetObjects['filtering'] !== 'undefined' ) {

			emailContainer = $( '#email', widgetObjects['filtering'] );
			emailContainer.find( '.subject' ).val('');
			emailContainer.find( '.message' ).val('');

			var sendButton = $( '#propanel-send-email' );
			sendButton.prop( 'disabled', true );

			var resetButton = $( '#propanel-reset-email' );
			resetButton.prop( 'disabled', true );
		}
	}

	/**
	 * Load Activity based on current filters
	 */
	function loadActivity() {
		if ( $( document.activeElement ).hasClass( 'course-status' ) ) {
			return;
		}

		if ( typeof widgetObjects['activity'] !== 'undefined' ) {
			loadTemplate( widgetObjects['activity'], 'activity_rows' );
		}
	}

	/**
	 * Process Activity Pagination
	 */
	function processActivityPagination( event ) {
		event.preventDefault();

		template_args = {};

		var thisPagination = $(this);
		template_args.paged = thisPagination.attr( 'data-page' );

		if ( typeof widgetObjects['activity'] !== 'undefined' ) {
			loadTemplate( widgetObjects['activity'], 'activity_rows', template_args );
		}
	}

	/**
	 * Load Trends Chart
	 */
	function trendsBarChart() {
		var ctxProPanelTrends = document.getElementById( "proPanelTrends" ).getContext( "2d" );
		var data = {
			labels: [ "1", "2", "3", "4", "5", "6", "7", ],
			datasets: [
				{
					label: "Week",
					backgroundColor: "#2D97C5",
					borderWidth: 1,
					hoverBackgroundColor: "#2D97C5",
					data: [ 65, 59, 80, 81, 56, 55, 40 ],
				},
				{
					label: "Month",
					backgroundColor: "#5BAED2",
					borderWidth: 1,
					hoverBackgroundColor: "#5BAED2",
					data: [ 40, 34, 65, 66, 36, 21, 10 ],
				},
				{
					label: "6 Months",
					backgroundColor: "#8AC5DF",
					borderWidth: 1,
					hoverBackgroundColor: "#8AC5DF",
					data: [ 25, 27, 55, 44, 25, 10, 8 ],
				}
			]
		};
		var options = {
			scales: {
				yAxes: [
					{
						position: "left",
						scaleLabel: {
							display: true,
							labelString: "# of Enrollments",
							fontColor: "#D3D6D7"
						},
						ticks: {
							beginAtZero: true,
						},
						gridLines: {
							zeroLineColor: "#eeeeee",
							color: "#eeeeee"
						}
					}
				],
				xAxes: [
					{
						position: "bottom",
						scaleLabel: {
							display: true,
							labelString: "Courses",
							fontColor: "#D3D6D7"
						},
						gridLines: {
							display: false,
							zeroLineColor: "#eeeeee",
							color: "#eeeeee"
						}
					}
				]
			},
			tooltips: {
				mode: 'label',
				backgroundColor: "#3B3E44",
				fontFamily: "'Open Sans',sans-serif",
				titleMarginBottom: 15,
				titleFontSize: 18,
				cornerRadius: 4,
				bodyFontSize: 14,
				xPadding: 10,
				yPadding: 15,
				bodySpacing: 10
			},
			legend: {
				display: true,
				labels: {
					boxWidth: 14,
					fontFamily: "'Open Sans',sans-serif"
				}
			}
		};

		new Chart( ctxProPanelTrends, {
			type: 'bar',
			data: data,
			options: options
		} );
	}

	/**
	 * Get data to display progress donut charts based on current filters
	 *
	 * Don't run when the course-status dropdown changes or if current filter type is user
	 */
	function getProgressChartsData( event ) {
		if ( $( document.activeElement ).hasClass( 'course-status' ) ) {
			return;
		}

		const args = widgetObjects['progress-chart'].data( 'filters' ) || {};

		const filters = Object.assign(
			{},
			currentFilters,
			args.filters || {}
		);

		// Our request details.
		const ajaxData = {
			url: ld_propanel_settings.ajaxurl,
			method: 'get',
			dataType: 'json',
			data: {
				'action': 'learndash_propanel_get_progress_charts_data',
				'filters': filters,
				'nonce': ld_propanel_settings.nonce,
				'lang': ld_propanel_settings.lang,
			}
		};

		// On success, build the charts.
		const ajaxCallback = function (response) {
			if (response && response.hasOwnProperty('success')) {
				buildProgressCharts(response.data);
			}
		};

		if ( typeof widgetObjects['progress-chart'] !== 'undefined' ) {
			return loadTemplate(
				widgetObjects['progress-chart'],
				'progress-chart-data',
				Object.assign(
					{},
					args,
					{
						filters: filters
					}
				)
			).then(function (response) {
				$.when(
					$.ajax( ajaxData )
				).done( ajaxCallback );

				return response;
			});
		} else {
			return $.when(
				$.ajax( ajaxData )
			).done( ajaxCallback );
		}
	}

	/**
	 * Build progress donut charts based on returned ajax data
	 * @param data
     */
	function buildProgressCharts( data ) {
		if ( typeof data.all_progress !== 'undefined' ) {
			drawProgressAllChart( data.all_progress );
		}

		if ( typeof data.all_percentages !== 'undefined' ) {
			drawProgressAllPercentagesChart( data.all_percentages );
		}
	}

	function drawProgressAllChart( chart_data ) {
		if (typeof proPanelProgressAllChart !== 'undefined')
		{
			proPanelProgressAllChart.destroy();
		}

		if ( ( typeof chart_data.data !== 'undefined' ) && ( typeof chart_data.data.datasets !== 'undefined' ) && ( chart_data.data.datasets.length > 0 )) {
			jQuery('#proPanelProgressAllDefaultMessage').hide();

			var ctxProPanelProgressAll = document.getElementById( "proPanelProgressAll" ).getContext( "2d" );
			if ( typeof ctxProPanelProgressAll !== 'undefined' ) {
				var progressAllData = {
					labels: [],
					datasets: []
				};

				if ( typeof chart_data.data.labels !== 'undefined' ) {
					progressAllData.labels = chart_data.data.labels;
				}

				if ( typeof chart_data.data.datasets !== 'undefined' ) {
					progressAllData.datasets = chart_data.data.datasets;
				}

				var progressAllOptions = {};
				if ( typeof chart_data['options'] !== 'undefined' ) {
					progressAllOptions = chart_data['options'];
				}

				window.proPanelProgressAllChart = new Chart( ctxProPanelProgressAll, {
					type: 'doughnut',
					data: progressAllData,
					options: progressAllOptions
				} );
			}
		} else {
			jQuery('#proPanelProgressAllDefaultMessage').show();
			jQuery('#proPanelProgressAll').hide();
			jQuery('#proPanelProgressAll').css('height', '0');
			jQuery('#proPanelProgressAll').css('width', '0');
		}
	}

	function drawProgressAllPercentagesChart( chart_data ) {

		if (typeof proPanelProgressAllPercentagesChart !== 'undefined')
		{
			proPanelProgressAllPercentagesChart.destroy();
		}

		if (( typeof chart_data.data.datasets !== 'undefined' ) && ( chart_data.data.datasets.length > 0 )) {
			jQuery('#proPanelProgressInMotionDefaultMessage').hide();

			var ctxProPanelProgressInMotion = document.getElementById( "proPanelProgressInMotion" ).getContext( "2d" );
			if ( typeof ctxProPanelProgressInMotion !== 'undefined' ) {

				var progressInMotionData = {
					labels: [],
					datasets: []
				};

				if ( typeof chart_data.data.labels !== 'undefined' ) {
					progressInMotionData.labels = chart_data.data.labels;
				}

				if ( typeof chart_data.data.datasets !== 'undefined' ) {
					progressInMotionData.datasets = chart_data.data.datasets;
				}

				var progressInMotionOptions = {};
				if ( typeof chart_data['options'] !== 'undefined' ) {
					progressInMotionOptions = chart_data['options'];
				}

				window.proPanelProgressAllPercentagesChart = new Chart( ctxProPanelProgressInMotion, {
					type: 'doughnut',
					data: progressInMotionData,
					options: progressInMotionOptions
				} );
			}
		} else {
			jQuery('#proPanelProgressInMotionDefaultMessage').show();
			jQuery('#proPanelProgressInMotion').hide();
			jQuery('#proPanelProgressInMotion').css('height', '0');
			jQuery('#proPanelProgressInMotion').css('width', '0');
		}
	}

	function downloadReporting(e) {
		e.stopImmediatePropagation();
		var data_template 	= $(e.currentTarget).attr('data-template');
		var data_slug 		= $(e.currentTarget).attr('data-slug');
		var data_nonce 		= $(e.currentTarget).attr('data-nonce');
		var updateElement 	= e.currentTarget;

		if ( typeof data_template !== 'undefined' ) {

			jQuery(e.currentTarget).prop('disabled', true);

			var post_data = {
				'init': 1,
				'nonce': data_nonce,
				'slug': data_slug,
				'filters': currentFilters
			}

			loadActivityTemplate( data_template, post_data, updateElement );
		}
	}

	function filterReporting(e) {
		if ( typeof e !== 'undefined' ) {
			e.stopImmediatePropagation();
		}

		selectedUserIds = [];
		updateSelectedCount();
		updateDateFilters();

		if ( currentFilters.groups !== '' ) {
			currentFilters.type = 'group';
			currentFilters.id = currentFilters.groups;

		} else if ( currentFilters.users !== '' ) {
			currentFilters.type = 'user';
			currentFilters.id = currentFilters.users;
		} else if ( currentFilters.course !== '' ) {
			currentFilters.type = 'course';
			currentFilters.id = currentFilters.courses;
		}

		if ( typeof widgetObjects['reporting'] !== 'undefined' ) {
			var reportingContainer = $( '.propanel-reporting', widgetObjects['reporting'] );

			if ( currentFilters.type == 'group' ) {
				loadTemplate( reportingContainer, 'group-reporting' );
			} else if ( currentFilters.type == 'course' ) {
				loadTemplate( reportingContainer, 'course-reporting' );
			} else if ( currentFilters.type == 'user' ) {
				loadTemplate( reportingContainer, 'user-reporting' );
			}
		}

		// Reset the pager to 1.
		currentFilters.reporting_pager['current_page'] = 1;

		$( document ).trigger( 'proPanel.filterChanged' );
	}

	function updateDateFilters() {
		if (typeof widgetObjects['reporting'] !== 'undefined') {
			if (jQuery('.filter-selection.filter-section-date input[name="filter-date-start"]').length) {
				currentFilters.time_start = jQuery('.filter-selection.filter-section-date input[name="filter-date-start"]').val();
			} else {
				currentFilters.time_start = '';
			}
			if (jQuery('.filter-selection.filter-section-date input[name="filter-date-end"]').length) {
				currentFilters.time_end = jQuery('.filter-selection.filter-section-date input[name="filter-date-end"]').val();
			} else {
				currentFilters.time_end = '';
			}
		}
	}

	/**
	 * Handles pagination of the Reporting Widget.
	 *
	 * @since 4.17.0
	 *
	 * @param  {Event} e Event object.
	 *
	 * @return {void}
	 */
	function pagerReporting(e) {
		e.preventDefault();

		currentFilters.reporting_pager['current_page'] = parseInt( currentFilters.reporting_pager['current_page'] );
		currentFilters.reporting_pager['total_pages'] = parseInt( currentFilters.reporting_pager['total_pages'] );

		var pager_el = (e.currentTarget );
		var pager_change = false;

		if ( $(pager_el).hasClass('next' ) ) {
			if ( currentFilters.reporting_pager['current_page'] < currentFilters.reporting_pager['total_pages'] ) {
				pager_change = true;
				currentFilters.reporting_pager['current_page'] = currentFilters.reporting_pager['current_page'] + 1;
			}
		} else if ( $(pager_el).hasClass('prev' ) ) {
			if ( currentFilters.reporting_pager['current_page'] > 1 ) {
				pager_change = true;
				currentFilters.reporting_pager['current_page'] = currentFilters.reporting_pager['current_page'] - 1;
			}
		} else if ( $(pager_el).hasClass('first' ) ) {
			if ( currentFilters.reporting_pager['current_page'] > 1 ) {
				pager_change = true;
				currentFilters.reporting_pager['current_page'] = 1;
			}
		} else if ( $(pager_el).hasClass('last' ) ) {
			if ( currentFilters.reporting_pager['current_page'] < currentFilters.reporting_pager['total_pages'] ) {
				pager_change = true;
				currentFilters.reporting_pager['current_page'] = currentFilters.reporting_pager['total_pages'];
			}
		}

		if ( pager_change == true ) {
			loadReportingTable();
		}
	}


	function resetReporting(e) {
		e.stopImmediatePropagation();
		window.location.reload(false);
	}

	function downloadActivity(e) {
		e.stopImmediatePropagation();
		var data_template 	= $(e.currentTarget).attr('data-template');
		var data_slug 		= $(e.currentTarget).attr('data-slug');
		var data_nonce 		= $(e.currentTarget).attr('data-nonce');

		var updateElement 	= e.currentTarget;

		// If we are NOT running under the Dashboard we need to get the filters data from the parent element in order to properly run the AJAX
		if ( !jQuery('#dashboard-widgets').length ) {
			var activityContainer = $( e.currentTarget ).parents( 'div.learndash-propanel-activity' );

			if ( typeof activityContainer !== 'undefined' ) {
				const args = $(activityContainer).data('filters') || {};

				const filters = Object.assign(
					{},
					currentFilters,
					args.filters || {}
				);

				if (( typeof filters !== 'undefined' ) && ( filters != '')) {
					currentFilters = filters;
				}
			}
		}

		if ( typeof data_template !== 'undefined' ) {

			jQuery(updateElement).prop('disabled', true);

			var post_data = {
				'init': 1,
				'nonce': data_nonce,
				'slug': data_slug,
				'filters': currentFilters
			}

			loadActivityTemplate( data_template, post_data, updateElement );

		}
	}

	function loadActivityTemplate( template, args, updateElement ) {
		$.when(
			$.ajax( {
				url: ld_propanel_settings.ajaxurl,
				method: 'get',
				dataType: 'json',
				data: {
					'action': 'learndash_propanel_template',
					'template': template,
					'args' : args,
					'nonce': ld_propanel_settings.nonce,
					'lang': ld_propanel_settings.lang,
					//'filters': filters,
				},
			})
		).done(function (response) {
			if (typeof response !== 'undefined') {
				if (typeof response['data']['output']['rows_html'] !== 'undefined') {
					var reply_data = response['data']['output']['rows_html'];

					$(window).trigger('resize');

					var total_count = 0;
					if (typeof reply_data['data']['total_count'] !== 'undefined')
						total_count = parseInt(reply_data['data']['total_count']);

					var result_count = 0;
					if (typeof reply_data['data']['result_count'] !== 'undefined')
						result_count = parseInt(reply_data['data']['result_count']);

					if (result_count < total_count) {

						// Update the progress meter
						if (typeof updateElement !== 'undefined') {
							if (jQuery(updateElement).length) {

								if (typeof reply_data['data']['progress_percent'] !== 'undefined') {
									var progress_percent = parseInt(reply_data['data']['progress_percent']);
									jQuery('span.status', updateElement).html(' ' + progress_percent + '%');
								}
							}
						}

						loadActivityTemplate(template, reply_data['data'], updateElement);
					} else {
						// Re-enable the buttons
						jQuery(updateElement).prop('disabled', false);

						jQuery('span.status', updateElement).html('');

						if ((typeof reply_data['data']['report_download_link'] !== 'undefined') && (reply_data['data']['report_download_link'] != '')) {
							window.location.href = reply_data['data']['report_download_link'];
						}
					}

				}
			}
		});
	}

	function reporting_init_search() {

		if ( typeof widgetObjects['reporting'] !== 'undefined' ) {

			if ( jQuery( 'input.tablesorter-search', widgetObjects['reporting'] ).length) {

				// Hold reference to our interval loop for key press
				var search_interval_ref;

				// Set time for .20 seconds. 1/5 of a second.
				var search_timeout = 200;

				var search_value = '';

				// Activate logic on fucus.
				jQuery( 'input.tablesorter-search', widgetObjects['reporting'] ).focus(function() {
					var search_el = this;

					// Grab the current value of the search input and store it as part of our data.
					search_value = jQuery(search_el).val();

					if ( search_interval_ref != '' ) {
						clearInterval( search_interval_ref );
					}

					search_interval_ref = setInterval( function() {
						search_value = jQuery(search_el).val();

						// If search was cleared we need to reset the display to show the regular non-search items
						if ( ( search_value == '' ) && ( search_value != currentFilters.search ) ) {
							currentFilters.search = search_value;
							currentFilters.current_page = 1;

							loadReportingTable();
						} else {

							if ( ( search_value.length >= 3 ) && ( search_value != currentFilters.search ) ) {
								currentFilters.search = search_value;
								currentFilters.current_page = 1;

								loadReportingTable();
							}

							if ( !jQuery( 'input.tablesorter-search', widgetObjects['reporting'] ).is(':focus')) {
								clearInterval( search_interval_ref );
							}
						}
					}, search_timeout);
				});
			}
		}
	}

	function getQSParameterByName( name ){
		var vars = {}, hash;
		var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');

		for (var i = 0; i < hashes.length; i++) {
			hash = hashes[i].split('=');
			vars[hash[0]] = hash[1];
		}

		if ( typeof vars[name] !== 'undefined' ) {
			return vars[name];
		}
	}

	function onScreenResize() {

		$( window ).resize(function() {
			if ( typeof widgetObjects['reporting'] !== 'undefined' ) {
				var widget_width = widgetObjects['reporting'].width();
				if ( widget_width < 350 ) {
					widgetObjects['reporting'].find('.ld-propanel-reporting-pager-info').addClass('ld-propanel-full-width');
					widgetObjects['reporting'].find('.search-wrap').addClass('ld-propanel-full-width');
				} else {
					widgetObjects['reporting'].find('.ld-propanel-reporting-pager-info').removeClass('ld-propanel-full-width');
					widgetObjects['reporting'].find('.search-wrap').removeClass('ld-propanel-full-width');
				}

				if ( widgetObjects['reporting'].find( 'table.ld-propanel-reporting-table-groups-widget').length ) {
					if ( widget_width < 350 ) {
						if ( ( widgetObjects['reporting'].find( 'table tbody td.ld-propanel-reporting-col-course').length ) && ( widgetObjects['reporting'].find( 'td.ld-propanel-reporting-col-progress').length ) ) {

							widgetObjects['reporting'].find('table thead th.ld-propanel-reporting-col-progress').hide();
							widgetObjects['reporting'].find('table tbody td.ld-propanel-reporting-col-progress').hide();

							if ( widgetObjects['reporting'].find('table tbody td.ld-propanel-reporting-col-course div.ld-propanel-reporting-col-progress').length ) {
								widgetObjects['reporting'].find('table tbody td.ld-propanel-reporting-col-course div.ld-propanel-reporting-col-progress').show();
							} else {
								widgetObjects['reporting'].find('table tbody tr').each(function( ) {
									var tr = $(this);

									if ( !$(tr).find( 'td.ld-propanel-reporting-col-course div.ld-propanel-reporting-col-progress').length ) {
										if ( $(tr).find( 'td.ld-propanel-reporting-col-progress').length ) {
											var td_progress_html = $(tr).find( 'td.ld-propanel-reporting-col-progress').html();
											if ( typeof td_progress_html !== 'undefined' ) {
												$(tr).find( 'td.ld-propanel-reporting-col-course').append('<div class="ld-propanel-reporting-col-progress" style="display:none; margin-top: 20px;">'+td_progress_html+'</div>');
												$(tr).find( 'td.ld-propanel-reporting-col-course div.ld-propanel-reporting-col-progress').show();
											}
										}
									}
								});
							}
						}
					} else {
						widgetObjects['reporting'].find('table thead th.ld-propanel-reporting-col-progress').show();
						widgetObjects['reporting'].find('table tbody td.ld-propanel-reporting-col-progress').show();

						widgetObjects['reporting'].find('table tbody td.ld-propanel-reporting-col-course div.ld-propanel-reporting-col-progress').hide();
					}
				}

				if ( widgetObjects['reporting'].find( 'table.ld-propanel-reporting-table-courses-widget').length ) {
					if ( widget_width < 350 ) {
						if ( ( widgetObjects['reporting'].find( 'table tbody td.ld-propanel-reporting-col-user').length ) && ( widgetObjects['reporting'].find( 'td.ld-propanel-reporting-col-progress').length ) ) {

							widgetObjects['reporting'].find('table thead th.ld-propanel-reporting-col-progress').hide();
							widgetObjects['reporting'].find('table tbody td.ld-propanel-reporting-col-progress').hide();

							if ( widgetObjects['reporting'].find('table tbody td.ld-propanel-reporting-col-user div.ld-propanel-reporting-col-progress').length ) {
								widgetObjects['reporting'].find('table tbody td.ld-propanel-reporting-col-user div.ld-propanel-reporting-col-progress').show();
							} else {
								widgetObjects['reporting'].find('table tbody tr').each(function( ) {
									var tr = $(this);

									if ( !$(tr).find( 'td.ld-propanel-reporting-col-course div.ld-propanel-reporting-col-progress').length ) {
										if ( $(tr).find( 'td.ld-propanel-reporting-col-progress').length ) {
											var td_progress_html = $(tr).find( 'td.ld-propanel-reporting-col-progress').html();
											if ( typeof td_progress_html !== 'undefined' ) {
												$(tr).find( 'td.ld-propanel-reporting-col-user').append('<div class="ld-propanel-reporting-col-progress" style="display:none; margin-top: 20px;">'+td_progress_html+'</div>');
												$(tr).find( 'td.ld-propanel-reporting-col-user div.ld-propanel-reporting-col-progress').show();
											}
										}
									}
								});
							}
						}
					} else {
						widgetObjects['reporting'].find('table thead th.ld-propanel-reporting-col-progress').show();
						widgetObjects['reporting'].find('table tbody td.ld-propanel-reporting-col-progress').show();

						widgetObjects['reporting'].find('table tbody td.ld-propanel-reporting-col-user div.ld-propanel-reporting-col-progress').hide();
					}
				}

				if ( widgetObjects['reporting'].find( 'table.ld-propanel-reporting-table-users-widget').length ) {
					if ( widget_width < 350 ) {
						if ( ( widgetObjects['reporting'].find( 'table tbody td.ld-propanel-reporting-col-course').length ) && ( widgetObjects['reporting'].find( 'td.ld-propanel-reporting-col-progress').length ) ) {

							widgetObjects['reporting'].find('table thead th.ld-propanel-reporting-col-progress').hide();
							widgetObjects['reporting'].find('table tbody td.ld-propanel-reporting-col-progress').hide();

							if ( widgetObjects['reporting'].find('table tbody td.ld-propanel-reporting-col-course div.ld-propanel-reporting-col-progress').length ) {
								widgetObjects['reporting'].find('table tbody td.ld-propanel-reporting-col-course div.ld-propanel-reporting-col-progress').show();
							} else {
								widgetObjects['reporting'].find('table tbody tr').each(function( ) {
									var tr = $(this);

									if ( !$(tr).find( 'td.ld-propanel-reporting-col-course div.ld-propanel-reporting-col-progress').length ) {
										if ( $(tr).find( 'td.ld-propanel-reporting-col-progress').length ) {
											var td_progress_html = $(tr).find( 'td.ld-propanel-reporting-col-progress').html();
											if ( typeof td_progress_html !== 'undefined' ) {
												$(tr).find( 'td.ld-propanel-reporting-col-course').append('<div class="ld-propanel-reporting-col-progress" style="display:none; margin-top: 20px;">'+td_progress_html+'</div>');
												$(tr).find( 'td.ld-propanel-reporting-col-course div.ld-propanel-reporting-col-progress').show();
											}
										}
									}
								});
							}
						}
					} else {
						widgetObjects['reporting'].find('table thead th.ld-propanel-reporting-col-progress').show();
						widgetObjects['reporting'].find('table tbody td.ld-propanel-reporting-col-progress').show();

						widgetObjects['reporting'].find('table tbody td.ld-propanel-reporting-col-course div.ld-propanel-reporting-col-progress').hide();
					}
				}
			}


			if ( typeof widgetObjects['activity'] !== 'undefined' ) {
				var widget_width = widgetObjects['activity'].width();
				if ( widget_width < 350 ) {
					widgetObjects['activity'].find('.report-pagination').addClass('ld-propanel-full-width');
					widgetObjects['activity'].find('.report-exports').addClass('ld-propanel-full-width');
				} else {
					widgetObjects['activity'].find('.report-pagination').removeClass('ld-propanel-full-width');
					widgetObjects['activity'].find('.report-exports').removeClass('ld-propanel-full-width');
				}
			}
		});

		$( document ).trigger( 'resize' );

	}

} );

jQuery(document).ready(function(){
	jQuery( '.ld-propanel-widget-activity' ).on( 'click', 'a.user_statistic', show_user_statistic );

	function show_user_statistic( e ) {
		e.preventDefault();

		var refId 				= 	jQuery(this).data('ref_id');
		var quizId 				= 	jQuery(this).data('quiz_id');
		var userId 				= 	jQuery(this).data('user_id');
		var statistic_nonce 	= 	jQuery(this).data('statistic_nonce');
		var post_data = {
			'action': 'wp_pro_quiz_admin_ajax_statistic_load_user',
			'func': 'statisticLoadUser',
			'data': {
				'quizId': quizId,
            	'userId': userId,
            	'refId': refId,
				'statistic_nonce': statistic_nonce,
				'avg': 0,
				'lang': ld_propanel_settings.lang,
			}
		}

		jQuery('#wpProQuiz_user_overlay, #wpProQuiz_loadUserData').show();
		var content = jQuery('#wpProQuiz_user_content').hide();

		jQuery.when(
			jQuery.ajax({
				type: "POST",
				url: ld_propanel_settings.ajaxurl,
				dataType: "json",
				cache: false,
				data: post_data,
			})
		).done(function (reply_data) {
			if (typeof reply_data.html !== 'undefined') {
				content.html(reply_data.html);
				jQuery('a.wpProQuiz_update', content).remove();
				jQuery('a#wpProQuiz_resetUserStatistic', content).remove();


				jQuery('#wpProQuiz_user_content').show();

				jQuery('body').trigger('learndash-statistics-contentchanged');

				jQuery('#wpProQuiz_loadUserData').hide();

				content.find('.statistic_data').click(function () {
					jQuery(this).parents('tr').next().toggle('fast');

					return false;
				});
			}
		});
		jQuery('#wpProQuiz_overlay_close').click(function() {
			jQuery('#wpProQuiz_user_overlay').hide();
		});
	}
});
