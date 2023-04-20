jQuery(window).on('load', function () {
	initPageLoad();
});

jQuery(function () {
	/**
	 * Template
	 */

	jQuery(document).on(
		'mouseover',
		'.templates .template figure',
		function (e) {
			e.preventDefault();
			jQuery(this).addClass('hover');
		}
	);

	jQuery(document).on(
		'mouseleave',
		'.templates .template figure',
		function (e) {
			e.preventDefault();
			jQuery(this).removeClass('hover');
		}
	);

	jQuery(document).on(
		'click',
		'.templates .template figure .actions .select',
		function (e) {
			e.preventDefault();

			const $templates = jQuery(this).closest('.templates'),
				templateId = jQuery(this).closest('.template').data('id'),
				themeTemplateId = jQuery(this)
					.closest('.template')
					.data('theme_template_id');

			$templates.find('.template').removeClass('selected');
			jQuery(this).closest('.template').addClass('selected');

			Cookies.set('ldDwTemplateId', templateId);
			Cookies.set('ldDwThemeTemplateId', themeTemplateId);

			// Reset template font and palette selection.
			Cookies.set('ldDwPalette', 'default');
			Cookies.set('ldDwFont', 'default');
		}
	);

	/**
	 * Preview
	 */

	jQuery(document).on(
		'click',
		'.templates .template figure .actions .preview',
		function (e) {
			e.preventDefault();

			const $preview = jQuery('.preview-wrapper'),
				$iframeWrapper = $preview.find('.iframe-wrapper'),
				$template = jQuery(this).closest('.template'),
				templateId = $template.data('id'),
				theme = templateId.includes('kadence_') ? 'kadence' : 'astra',
				previewUrl = $template.data('preview_url');

			if (previewUrl && previewUrl.length > 0) {
				$preview.find('iframe').attr('src', previewUrl);
				$preview.show();
				$iframeWrapper.hide();

				setTimeout(() => {
					updatePreview(theme, 'site-colors', 'default');
					updatePreview(theme, 'site-typography', 'default');
					$iframeWrapper.show();
				}, 500);
			}
		}
	);

	jQuery(document).on('click', '.preview-wrapper .close', function (e) {
		e.preventDefault();

		const $wrapper = jQuery(this).closest('.preview-wrapper');

		$wrapper.find('.preview iframe').removeAttr('src');
		$wrapper.hide();
	});

	/**
	 * Font
	 */

	jQuery(document).on('click', '.design-wizard .fonts .font', function (e) {
		e.preventDefault();

		const $fonts = jQuery(this).closest('.fonts'),
			id = jQuery(this).data('id'),
			template = Cookies.get('ldDwTemplateId'),
			theme = template.includes('kadence_') ? 'kadence' : 'astra';

		$fonts.find('.font').removeClass('selected');
		jQuery(this).addClass('selected');

		Cookies.set('ldDwFont', id);

		updatePreview(theme, 'site-typography', id);
	});

	jQuery(document).on(
		'click',
		'.design-wizard .reset-font-button',
		function (e) {
			e.preventDefault();

			const $fonts = jQuery(this).closest('.header').find('.fonts'),
				template = Cookies.get('ldDwTemplateId'),
				theme = template.includes('kadence_') ? 'kadence' : 'astra';

			$fonts.find('.font').removeClass('selected');

			Cookies.remove('ldDwFont');

			updatePreview(theme, 'site-typography', 'default');
		}
	);

	/**
	 * Palette
	 */

	jQuery(document).on(
		'click',
		'.design-wizard .palettes .palette',
		function (e) {
			e.preventDefault();

			const $palettes = jQuery(this).closest('.palettes'),
				id = jQuery(this).data('id'),
				template = Cookies.get('ldDwTemplateId'),
				theme = template.includes('kadence_') ? 'kadence' : 'astra';

			$palettes.find('.palette').removeClass('selected');
			jQuery(this).addClass('selected');

			Cookies.set('ldDwPalette', id);

			updatePreview(theme, 'site-colors', id);
		}
	);

	jQuery(document).on(
		'click',
		'.design-wizard .reset-palette-button',
		function (e) {
			e.preventDefault();

			const $palettes = jQuery(this).closest('.header').find('.palettes'),
				template = Cookies.get('ldDwTemplateId'),
				theme = template.includes('kadence_') ? 'kadence' : 'astra';

			$palettes.find('.palette').removeClass('selected');

			Cookies.remove('ldDwPalette');

			updatePreview(theme, 'site-colors', 'default');
		}
	);

	/**
	 * Pagination
	 */

	jQuery(document).on('click', '.next-button', function (e) {
		e.preventDefault();

		const url = window.location.href,
			urlParams = new URLSearchParams(window.location.search),
			template = Cookies.get('ldDwTemplateId');

		let nonce = false;

		if (!template || template === 'undefined') {
			alert('Please select a template first');
			return false;
		}

		let step = urlParams.get('step');
		step = step ? parseInt(step) : 1;

		if (step === 1) {
			// Get Astra theme data
			if (template.includes('astra_')) {
				Cookies.set('astra-site-color-scheme', 'light');
			}

			redirectPage(url, step, template, nonce);
		} else if (step === 4) {
			nonce = LearnDashDesignWizard.ajax_init_nonce;
			jQuery('#ld_dw_confirm').dialog({
				dialogClass: 'wp-dialog',
				modal: true,
				title: 'Ready to Import?',
				width: 400,
				buttons: [
					{
						text: 'I understand, continue',
						click() {
							redirectPage(url, step, template, nonce);
						},
					},
					{
						text: 'Exit Setup',
						click() {
							window.location.href =
								LearnDashDesignWizard.learndash_setup_url;
						},
					},
				],
			});
		} else {
			redirectPage(url, step, template, nonce);
		}
	});

	jQuery(document).on('click', '.back', function (e) {
		e.preventDefault();

		history.back();
	});

	jQuery(document).on('click', '.exit', function (e) {
		e.preventDefault();

		window.location.href = LearnDashDesignWizard.learndash_setup_url;
	});

	jQuery(document).on('click', '.visit-site', function (e) {
		e.preventDefault();

		jQuery(
			'<a href="' +
				LearnDashDesignWizard.site_url +
				'" target="_blank"Visit Site</a>'
		)[0].click();
	});
});

/**
 * Build template
 */

function initPageLoad() {
	const urlParams = new URLSearchParams(window.location.search),
		template = urlParams.get('template'),
		nonce = urlParams.get('nonce');

	let step = urlParams.get('step'),
		theme = '';

	step = step ? parseInt(step) : 1;

	if (template) {
		theme = template.includes('kadence_') ? 'kadence' : 'astra';
	}

	if (step === 1) {
		Cookies.remove('ldDwTemplateId');
	} else if (step === 2 || step === 3 || step === 4) {
		const font = Cookies.get('ldDwFont'),
			palette = Cookies.get('ldDwPalette');

		setTimeout(function () {
			updatePreview(theme, 'site-colors', palette);
			updatePreview(theme, 'site-typography', font);

			setTimeout(function () {
				jQuery('#ld-site-preview').show();
			}, 1000);
		}, 500);
	} else if (step === 5 && template.length > 0 && nonce.length > 0) {
		Cookies.remove('ldDwLastBuildAstraStep');
		Cookies.remove('ldDwLastBuildKadenceStep');

		ajaxBuildTemplate(true);
	}
}

function ajaxBuildTemplate(init = false) {
	const urlParams = new URLSearchParams(window.location.search),
		template = urlParams.get('template'),
		nonce = urlParams.get('nonce');

	let currentStepN = 0,
		totalSteps = 0;

	if (init) {
		currentStepN = 1;
		// Remove wizard cookies set by the previous wizard process.
		flushCookies();

		totalSteps = 6;

		if (template.includes('astra')) {
			totalSteps = parseInt(totalSteps) + 35;
		} else if (template.includes('kadence')) {
			totalSteps = parseInt(totalSteps) + 9;
		}

		Cookies.set('ldDwTotalSteps', totalSteps);
	} else {
		currentStepN = Cookies.get('ldDwCurrentStepN');
		currentStepN = parseInt(currentStepN) + 1;

		totalSteps = Cookies.get('ldDwTotalSteps');
	}

	Cookies.set('ldDwCurrentStepN', currentStepN);

	jQuery.post(
		LearnDashDesignWizard.ajaxurl,
		{
			action: 'ld_dw_build_template',
			nonce,
			template,
			init,
		},
		function (response) {
			if (response.success) {
				if (!response.data.complete) {
					if (response.data.step !== 'build_template') {
						ajaxBuildTemplate();
					} else if (response.data.step === 'build_template') {
						if (response.data.theme === 'astra') {
							ajaxBuildAstra();
						} else if (response.data.theme === 'kadence') {
							ajaxBuildKadence();
						}
					}
				} else {
					currentStepN = 1;
					totalSteps = 1;

					flushCookies(true);

					// Add process complete handler.
					const actionsTemplate =
						LearnDashDesignWizard.templates.actions_success;

					jQuery('.design-wizard > .content > .text').replaceWith(
						actionsTemplate
					);
				}

				updateProgress({
					currentStepN,
					totalSteps,
					message: response.data.message,
				});
			}
		}
	);
}

function ajaxBuildAstra(url = '', data = {}, type = 'POST') {
	const urlParams = new URLSearchParams(window.location.search),
		font = Cookies.get('ldDwFont'),
		palette = Cookies.get('ldDwPalette'),
		fontDetails = JSON.stringify(getFontDetails('astra', font)),
		colorScheme = Cookies.get('astra-site-color-scheme');

	let paletteDetails,
		step = urlParams.get('step');

	if (colorScheme) {
		paletteDetails = JSON.stringify(
			getPaletteDetails('astra', palette, colorScheme)
		);
	}

	if (url.length < 1) {
		url = LearnDashDesignWizard.ajaxurl;
	}

	const actions = [
		'astra-sites-api-request',
		'astra-required-plugins',
		'astra-sites-filesystem-permission',
		'astra-sites-set-start-flag',
		'astra-sites-reset-customizer-data',
		'astra-sites-reset-site-options',
		'astra-sites-reset-widgets-data',
		'astra-sites-reset-terms-and-forms',
		'astra-sites-get-deleted-post-ids',
		'astra-sites-reset-posts', // 10 posts per batch
		'astra-sites-import-wpforms',
		'astra-sites-import-cartflows',
		'astra-sites-import-customizer-settings',
		'astra-sites-import-prepare-xml',
		'astra-wxr-import',
		'astra-sites-import-options',
		'astra-sites-import-widgets',
		'astra_sites_set_site_data-site_colors', // param: site-colors
		'astra_sites_set_site_data-site_typography', // param: site-typography
		'astra-sites-import-end',
	];

	const templateId = Cookies.get('ldDwThemeTemplateId'),
		lastStep = Cookies.get('ldDwLastBuildAstraStep'),
		totalSteps = Cookies.get('ldDwTotalSteps');

	let lastStepKey, currentStepKey;

	let currentStepN = Cookies.get('ldDwCurrentStepN'),
		message = '';

	currentStepN = parseInt(currentStepN) + 1;
	Cookies.set('ldDwCurrentStepN', currentStepN);

	if (!lastStep) {
		currentStepKey = 0;
	} else {
		lastStepKey = actions.indexOf(lastStep);
		currentStepKey = lastStepKey + 1;
	}

	const currentStep = actions[currentStepKey];

	if (currentStep) {
		let postIds, deletedPostIds, astraSiteResetPosts;

		step = currentStep;

		switch (currentStep) {
			case 'astra-sites-api-request':
				data.url = 'astra-sites/' + templateId;

				message = 'Get template data';
				break;

			case 'astra-sites-reset-customizer-data':
				message = 'Reset site data';
				break;

			case 'astra-sites-reset-posts':
				postIds = Cookies.get('astra-site-deleted-post-ids');

				if (postIds !== 'undefined') {
					postIds = postIds.split(',');

					deletedPostIds = postIds.splice(0, 10);
				} else {
					deletedPostIds = [];
				}

				data.ids = Object.assign({}, deletedPostIds);
				data.ids = JSON.stringify(data.ids);

				if (postIds !== 'undefined' && postIds.length > 0) {
					astraSiteResetPosts = true;
				} else {
					astraSiteResetPosts = false;
				}

				break;

			case 'astra-sites-import-wpforms':
				data.wpforms_url = Cookies.get('astra-site-wpforms-path');
				Cookies.remove('astra-site-wpforms-path');

				message = 'Import wpforms data if any';
				break;

			case 'astra-sites-import-cartflows':
				data.cartflows_url = Cookies.get('astra-site-cartflows-path');
				Cookies.remove('astra-site-cartflows-path');

				message = 'Import cartflows data if any';
				break;

			case 'astra-sites-import-widgets':
				data.widgets_data = Cookies.get('astra-site-widgets-data');
				Cookies.remove('astra-site-widgets-data');

				message = 'Import widgets data';
				break;

			case 'astra-sites-import-prepare-xml':
				data.wxr_url = Cookies.get('astra-site-wxr-path');
				Cookies.remove('astra-site-wxr-path');

				message = 'Import XML data';
				break;

			case 'astra_sites_set_site_data-site_colors':
				step = 'astra_sites_set_site_data';
				data.param = 'site-colors';
				data.palette = paletteDetails;
				data.security = LearnDashDesignWizard.ajax_set_data_nonce;

				Cookies.remove('astra-site-color-scheme');
				message = 'Apply site color options';
				break;

			case 'astra_sites_set_site_data-site_typography':
				step = 'astra_sites_set_site_data';
				data.param = 'site-typography';
				data.typography = fontDetails;
				data.security = LearnDashDesignWizard.ajax_set_data_nonce;

				message = 'Apply site typography options';
				break;
		}

		updateProgress({
			currentStepN,
			totalSteps,
			message,
		});

		jQuery
			.ajax({
				url,
				type,
				data: {
					action: step,
					_ajax_nonce: LearnDashDesignWizard.ajax_nonce,
					...data,
				},
				success(response) {
					if (currentStep === 'astra-wxr-import') {
						if (response.length > 0) {
							ajaxBuildAstra();
						}
					} else if ('astra-wxr-import' !== currentStep) {
						if (response.success) {
							const ajaxData = {};

							let ajaxUrl = '',
								ajaxType = 'POST',
								storedDeletedPostIds;

							switch (currentStep) {
								case 'astra-sites-api-request':
									Cookies.set(
										'astra-site-wpforms-path',
										response.data['astra-site-wpforms-path']
									);

									Cookies.set(
										'astra-site-cartflows-path',
										response.data[
											'astra-site-cartflows-path'
										]
									);

									Cookies.set(
										'astra-site-wxr-path',
										response.data['astra-site-wxr-path']
									);

									Cookies.set(
										'astra-site-widgets-data',
										response.data['astra-site-widgets-data']
									);
									break;

								case 'astra-required-plugins':
									Cookies.set(
										'astra-site-required-plugins',
										response.data.required_plugins
									);

									response.data.required_plugins.notinstalled.forEach(
										function (plugin) {
											wp.updates.queue.push({
												action: 'install-plugin',
												data: {
													slug: plugin.slug,
													init: plugin.init,
													name: plugin.name,
													clear_destination: true,
													success() {
														activatePlugin(plugin);
													},
													error() {},
												},
											});
										}
									);

									// Required to set queue.
									wp.updates.queueChecker();

									response.data.required_plugins.inactive.forEach(
										function (plugin) {
											activatePlugin(plugin);
										}
									);
									break;

								case 'astra-sites-get-deleted-post-ids':
									storedDeletedPostIds = response.data.splice(
										0,
										100
									);

									Cookies.set(
										'astra-site-deleted-post-ids',
										storedDeletedPostIds
									);
									break;

								case 'astra-sites-import-prepare-xml':
									ajaxUrl = response.data.url;
									ajaxType = 'GET';
									break;

								case 'astra-sites-import-end':
									Cookies.remove('ldDwLastBuildAstraStep');
									ajaxBuildTemplate();
									return;
							}

							ajaxBuildAstra(ajaxUrl, ajaxData, ajaxType);
						} else {
							// Add error handler.
							if (
								Object.hasOwnProperty.call(response, 'data') &&
								Object.hasOwnProperty.call(
									response.data,
									'message'
								)
							) {
								message = response.data.message;
							} else {
								message =
									LearnDashDesignWizard.messages
										.dw_error_default;
							}

							message =
								LearnDashDesignWizard.messages.dw_error_prefix +
								': ' +
								message;

							updateProgress({
								currentStepN: currentStepN - 1,
								totalSteps,
								message,
							});

							const actionsTemplate =
								LearnDashDesignWizard.templates.actions_error;

							jQuery(
								'.design-wizard > .content > .text'
							).replaceWith(actionsTemplate);

							Cookies.remove('ldDwLastBuildAstraStep');
						}
					}

					currentStepN = parseInt(currentStepN) + 1;
					Cookies.set('ldDwCurrentStepN', currentStepN);
				},
			})
			.fail(function () {
				Cookies.remove('ldDwLastBuildAstraStep');
				Cookies.remove('ldDwCurrentStepN');
			});

		if (astraSiteResetPosts) {
			// Step before get-deleted-post-ids
			Cookies.set(
				'ldDwLastBuildAstraStep',
				'astra-sites-reset-terms-and-forms'
			);
		} else {
			Cookies.set('ldDwLastBuildAstraStep', currentStep);
		}
	} else {
		Cookies.remove('ldDwLastBuildAstraStep');
	}
}

function ajaxBuildKadence(url = '', data = {}, type = 'POST') {
	const urlParams = new URLSearchParams(window.location.search);

	let step = urlParams.get('step');

	if (url.length < 1) {
		url = LearnDashDesignWizard.ajaxurl;
	}

	const actions = [
		'kadence_import_get_template_data',
		'kadence_check_plugin_data',
		'kadence_remove_past_import_data',
		'kadence_import_install_plugins',
		'kadence_import_demo_data',
		'kadence_import_customizer_data',
		'kadence_after_import_data',
	];

	const templateId = Cookies.get('ldDwThemeTemplateId'),
		lastStep = Cookies.get('ldDwLastBuildKadenceStep'),
		totalSteps = Cookies.get('ldDwTotalSteps');

	let lastStepKey, currentStepKey, kadenceImportDemo;

	let currentStepN = Cookies.get('ldDwCurrentStepN'),
		message = '';

	currentStepN = parseInt(currentStepN) + 1;
	Cookies.set('ldDwCurrentStepN', currentStepN);

	if (!lastStep) {
		currentStepKey = 0;
	} else {
		lastStepKey = actions.indexOf(lastStep);
		currentStepKey = lastStepKey + 1;
	}

	const currentStep = actions[currentStepKey];

	if (currentStep) {
		step = currentStep;

		data.selected = templateId;
		data.builder = 'blocks';

		switch (currentStep) {
			case 'kadence_import_get_template_data':
				data.template_type = 'blocks';

				message = 'Get template data';
				break;

			case 'check_plugin_data':
				data.selected = '';
				data.builder = 'blocks';

				message = 'Check plugin data';
				break;

			case 'kadence_import_demo_data':
				data.palette = Cookies.get('ldDwPalette');
				data.font = Cookies.get('ldDwFont');

				message = 'Import demo data';
				break;

			case 'kadence_import_customizer_data':
				data.wp_customize = 'on';
				delete data.selected;
				delete data.builder;

				message = 'Import customizer data';
				break;
		}

		updateProgress({
			currentStepN,
			totalSteps,
			message,
		});

		if (currentStep) {
			Cookies.set('ldDwLastBuildKadenceStep', currentStep);
		}

		jQuery
			.ajax({
				url,
				type,
				data: {
					action: step,
					security: LearnDashDesignWizard.ajax_kadence_security_nonce,
					...data,
				},
				success(response) {
					const ajaxUrl = '',
						ajaxData = {},
						ajaxType = 'POST';

					let templateData;

					switch (currentStep) {
						case 'kadence_import_get_template_data':
							templateData = JSON.parse(response);
							Cookies.set(
								'ldDwKadenceTemplateData',
								templateData
							);
							break;

						case 'kadence_import_demo_data':
							kadenceImportDemo = response.status === 'newAJAX';

							if (kadenceImportDemo) {
								Cookies.set(
									'ldDwLastBuildKadenceStep',
									'kadence_import_install_plugins'
								);
							}
							break;

						case 'kadence_after_import_data':
							resetKadenceCookies();
							ajaxBuildTemplate();
							return;
					}

					ajaxBuildKadence(ajaxUrl, ajaxData, ajaxType);
				},
			})
			.fail(function () {
				resetKadenceCookies();
			});
	} else {
		resetKadenceCookies();
	}
}

function updateProgress(args) {
	let percentage = (args.currentStepN / args.totalSteps) * 100;
	percentage = percentage > 100 ? 100 : percentage;
	percentage = percentage.toFixed(0);

	jQuery('.progress .percentage .number').text(percentage + '%');
	jQuery('.progress .bar progress').attr('value', percentage);

	if (args.message !== '') {
		jQuery('.progress .status .message').html(args.message);
	}
}

/**
 * Helpers
 */

function getFontDetails(theme, key) {
	let details = {};

	if (
		Object.prototype.hasOwnProperty.call(
			LearnDashDesignWizard.fonts[theme][key],
			'details'
		)
	) {
		details = LearnDashDesignWizard.fonts[theme][key].details;
	}

	return details;
}

function getPaletteDetails(theme, key, colorScheme = '') {
	let palettes;

	if (colorScheme.length > 0) {
		palettes = LearnDashDesignWizard.palettes[theme][colorScheme][key];
	} else {
		palettes = LearnDashDesignWizard.palettes[theme][key];
	}

	return palettes;
}

function redirectPage(url, step, template, nonce) {
	const args = {
		step: step + 1,
		template,
		nonce,
	};

	if (!nonce) {
		delete args.nonce;
	}

	const nextUrlParams = new URLSearchParams(args),
		nextUrl = encodeURI(url + '&' + nextUrlParams.toString());

	window.location.href = nextUrl;
}

function updatePreview(theme, type, key = '') {
	const frame = document.getElementById('ld-site-preview');

	if (!frame) {
		return;
	}

	let astraParam, astraData, kadenceParam, kadenceData, colorScheme;

	const requestData = {};

	switch (type) {
		case 'site-colors':
			if (theme === 'astra') {
				astraParam = 'colorPalette';
				colorScheme = Cookies.get('astra-site-color-scheme');
				colorScheme = colorScheme ? colorScheme : 'light';

				astraData = getPaletteDetails('astra', key, colorScheme);
			} else if (theme === 'kadence') {
				kadenceParam = 'color';
				if (key !== 'default') {
					kadenceData = Cookies.get('ldDwPalette');
				} else {
					kadenceData = '';
				}
			}
			break;

		case 'site-typography':
			if (theme === 'astra') {
				astraParam = 'siteTypography';

				astraData = getFontDetails('astra', key);
				astraData = filterAstraFontDetails(key, astraData);
			} else if (theme === 'kadence') {
				kadenceParam = 'font';
				if (key !== 'default') {
					kadenceData = Cookies.get('ldDwFont');
				} else {
					kadenceData = '';
				}
			}
			break;
	}

	if (theme === 'astra') {
		if (astraParam && astraData) {
			frame.contentWindow.postMessage(
				{
					call: 'starterTemplatePreviewDispatch',
					value: {
						param: astraParam,
						data: astraData,
					},
				},
				'*'
			);
		}
	} else if (theme === 'kadence') {
		requestData[kadenceParam] = kadenceData;

		frame.contentWindow.postMessage(requestData, '*');
	}
}

function filterAstraFontDetails(key, data) {
	const id = key;

	// ID
	data.id = id;

	// Headings
	const inheritArgs = [
		'font-family-h1',
		'font-family-h2',
		'font-family-h3',
		'font-family-h4',
		'font-family-h5',
		'font-family-h6',
		'font-weight-h1',
		'font-weight-h2',
		'font-weight-h3',
		'font-weight-h4',
		'font-weight-h5',
		'font-weight-h6',
	];

	const emptyArgs = [
		'line-height-h1',
		'line-height-h2',
		'line-height-h3',
		'line-height-h4',
		'line-height-h5',
		'line-height-h6',
		'text-transform-h1',
		'text-transform-h2',
		'text-transform-h3',
		'text-transform-h4',
		'text-transform-h5',
		'text-transform-h6',
	];

	inheritArgs.forEach(function (arg) {
		data[arg] = 'inherit';
	});

	emptyArgs.forEach(function (arg) {
		data[arg] = '';
	});

	return data;
}

function activatePlugin(plugin) {
	jQuery.post(
		LearnDashDesignWizard.ajaxurl,
		{
			action: 'astra-required-plugin-activate',
			_ajax_nonce: LearnDashDesignWizard.ajax_nonce,
			init: plugin.init,
		},
		function () {}
	);
}

function flushCookies(end = false) {
	const cookies = [
		'ldDwCurrentStepN',
		'ldDwTotalSteps',
		'ldDwLastBuildAstraStep',
		'ldDwLastBuildKadenceStep',
		'astra-site-wpforms-path',
		'astra-site-cartflows-path',
		'astra-site-wxr-path',
		'astra-site-widgets-data',
		'astra-site-required-plugins',
	];

	if (end) {
		cookies.push(
			...[
				'astra-site-color-scheme',
				'ldDwTemplateId',
				'ldDwThemeTemplateId',
				'ldDwFont',
				'ldDwPalette',
			]
		);
	}

	cookies.forEach(function (cookie) {
		Cookies.remove(cookie);
	});
}

function resetKadenceCookies() {
	const cookies = ['ldDwLastBuildKadenceStep', 'ldDwPalette', 'ldDwFont'];

	cookies.forEach(function (cookie) {
		Cookies.remove(cookie);
	});
}
