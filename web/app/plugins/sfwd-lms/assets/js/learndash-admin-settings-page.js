if (
	typeof learndash_admin_settings_data !== 'undefined' &&
	typeof learndash_admin_settings_data.json !== 'undefined'
) {
	learndash_admin_settings_data = learndash_admin_settings_data.json.replace(
		/&quot;/g,
		'"'
	);
	learndash_admin_settings_data = jQuery.parseJSON(
		learndash_admin_settings_data
	);
} else {
	learndash_admin_settings_data = {};
}

jQuery(function () {
	learndash_course_edit_page_billing_cycle_javascript();

	if (
		jQuery('.sfwd_options .sfwd_option_input select[data-ld-select2="1"]')
			.length
	) {
		jQuery(
			'.sfwd_options .sfwd_option_input select[data-ld-select2="1"]'
		).each(function (idx, item) {
			const parent_ld_select = jQuery(item).parent('span.ld-select');
			if (typeof parent_ld_select !== 'undefined') {
				jQuery(parent_ld_select).addClass('ld-select2');
			}

			const select2_args = learndash_get_base_select2_args();

			let placeholder = jQuery(item).attr('placeholder');
			if (typeof placeholder === 'undefined' || placeholder === '') {
				placeholder = jQuery("option[value='']", item).text();
			}
			if (typeof placeholder === 'undefined' || placeholder === '') {
				placeholder = 'Select an option';
			}
			select2_args.placeholder = placeholder;

			select2_args.ajax = learndash_settings_select2_ajax(item);
			jQuery(item).select2(select2_args);
		});
	}

	if (
		jQuery(
			'body.edit-php .tablenav.top select[data-ld-select2="1"], body.users-php .tablenav.top select[data-ld-select2="1"]'
		).length
	) {
		jQuery(
			'body.edit-php .tablenav.top select[data-ld-select2="1"], body.users-php .tablenav.top select[data-ld-select2="1"]'
		).each(function (idx, item) {
			const select2_args = learndash_get_base_select2_args();

			let placeholder = jQuery(item).attr('placeholder');
			if (typeof placeholder === 'undefined' || placeholder === '') {
				placeholder = jQuery("option[value='']", item).text();
			}
			if (typeof placeholder === 'undefined' || placeholder === '') {
				placeholder = 'Select an option';
			}
			select2_args.placeholder = placeholder;

			select2_args.ajax = learndash_listing_select2_ajax(item);
			jQuery(item).select2(select2_args);
		});
	}

	/**
	 * Populate Select2 dropdowns with data
	 *
	 * @param  action
	 * @param  el
	 * @return {{url, dataType: string, method: string, delay: number, data: data, processResults: processResults}}
	 */
	function learndash_listing_select2_ajax(el) {
		const listing_nonce =
			jQuery('#ld-listing-nonce').data('ld-listing-nonce');
		if (typeof listing_nonce === 'undefined' || listing_nonce === '') {
			return null;
		}

		const selector_query_data = jQuery(el).data('ld-selector-query-data');
		if (
			typeof selector_query_data === 'undefined' ||
			selector_query_data === ''
		) {
			return null;
		}

		if (
			typeof selector_query_data.selector_key === 'undefined' ||
			selector_query_data.selector_key === ''
		) {
			return null;
		}

		// Trigger change when the selector is cleared.
		jQuery(el).on('select2:unselect', function (e) {
			jQuery(el).trigger('change');
		});

		return {
			url: learndash_admin_settings_data.ajaxurl,
			dataType: 'json',
			method: 'post',
			delay: 1500,
			cache: true,
			data(params) {
				const query_data = {};
				query_data.selector_key = selector_query_data.selector_key;
				query_data.selector_filters = {};

				// We need to get the values from our related filters.
				if (
					typeof selector_query_data.selector_filters !== 'undefined'
				) {
					jQuery.each(
						selector_query_data.selector_filters,
						function (idx, selector_filter) {
							if (selector_filter !== '') {
								if (
									jQuery(
										'body.edit-php .tablenav.top select[data-ld-selector-nonce="' +
											selector_filter +
											'"], body.users-php .tablenav.top select[data-ld-selector-nonce="' +
											selector_filter +
											'"]'
									).length
								) {
									const selector_filter_value = jQuery(
										'body.edit-php .tablenav.top select[data-ld-selector-nonce="' +
											selector_filter +
											'"], body.users-php .tablenav.top select[data-ld-selector-nonce="' +
											selector_filter +
											'"]'
									).val();
									query_data.selector_filters[
										selector_filter
									] = selector_filter_value;
								}
							}
						}
					);
				}

				return {
					action: 'learndash_listing_select2_query',
					listing_nonce,
					query_data: query_data || '',
					search: params.term || '',
					page: params.page || 1,
				};
			},
			processResults(response, params) {
				params.page = params.page || 1;

				return {
					results: response.items,
					pagination: {
						more: params.page < response.total_pages,
					},
				};
			},
		};
	}

	/**
	 * Handle color picker settings fields.
	 */
	if (
		jQuery(
			'.sfwd_input_type_colorpicker .learndash-section-field-colorpicker'
		).length
	) {
		jQuery(
			'.sfwd_input_type_colorpicker .learndash-section-field-colorpicker'
		).wpColorPicker();
	}

	/**
	 * Handle the combination Select + Button field type actions.
	 */
	if (jQuery('.sfwd_options .sfwd_input_type_select-edit-delete').length) {
		jQuery('.sfwd_options .sfwd_input_type_select-edit-delete').each(
			function (idx, item) {
				const item_spinner = jQuery(item).find('.spinner');
				item_spinner.css('float', 'none');

				jQuery(item)
					.find('select')
					.on('change', function (e) {
						const select_val = jQuery(item).find('select').val();

						// Hide any previous update message.
						jQuery(item).find('.message').hide();

						if (select_val.length) {
							const select_text = jQuery(item)
								.find('select option:selected')
								.text();
							jQuery(item)
								.find('input[type="text"]')
								.val(select_text);
							jQuery(item)
								.find('input[type="text"]')
								.attr('disabled', false);
							jQuery(item)
								.find('input[type="button"]')
								.attr('disabled', false);
						} else {
							jQuery(item).find('input[type="text"]').val('');
							jQuery(item)
								.find('input[type="button"]')
								.attr('disabled', true);
							jQuery(item)
								.find('input[type="text"]')
								.attr('disabled', true);
						}
					});

				jQuery(item)
					.find('input[type="button"]')
					.on('click', function (e) {
						const field_action = jQuery(e.currentTarget).data(
							'action'
						);
						const field_value = jQuery(item).find('select').val();
						const updated_text = jQuery(item)
							.find('input[type="text"]')
							.val();

						const post_data = jQuery(item)
							.find('.ajax_data')
							.data('ajax');
						if (typeof post_data !== 'undefined') {
							post_data.field_action = field_action;
							post_data.field_value = field_value;
							post_data.field_text = updated_text;

							item_spinner.css('visibility', 'visible');

							jQuery.ajax({
								type: 'POST',
								url: ajaxurl,
								dataType: 'json',
								cache: false,
								data: post_data,
								error(jqXHR, textStatus, errorThrown) {},
								success(reply_data) {
									item_spinner.css('visibility', 'hidden');

									if (
										typeof reply_data.status !==
											'undefined' &&
										reply_data.status === true
									) {
										if (field_action == 'update') {
											jQuery(item)
												.find(
													'select option[value="' +
														field_value +
														'"]'
												)
												.text(updated_text);
										} else if (field_action == 'delete') {
											jQuery(item)
												.find(
													'select option[value="' +
														field_value +
														'"]'
												)
												.remove();
										}

										jQuery(item).find('select').val('');
										jQuery(item)
											.find('input[type="text"]')
											.val('');
									}

									if (
										typeof reply_data.message !==
										'undefined'
									) {
										jQuery(item)
											.find('.message')
											.html(reply_data.message);
										jQuery(item)
											.find('.message')
											.show()
											.fadeOut(3000);
									}
								},
							});
						}
					});
			}
		);
	}

	/**
	 * Handle Media Upload setting fields.
	 */
	if (
		jQuery('.sfwd_options .learndash-section-field-media-upload_wrapper')
			.length
	) {
		jQuery(
			'.sfwd_options .learndash-section-field-media-upload_wrapper'
		).each(function (idx, item) {
			const media_upload_field = jQuery(item).find(
				'.learndash-section-field-media-upload'
			);
			const media_preview_field = jQuery(item).find('img.image-preview');

			jQuery(item)
				.find('input[type="button"].image-upload-button')
				.on('click', function (e) {
					e.preventDefault();
					let file_frame;

					// If the media frame already exists, reopen it.
					if (file_frame) {
						// Open frame
						file_frame.open();
						return;
					}

					// Create the media frame.
					file_frame = wp.media.frames.file_frame = wp.media({
						title: 'Select a image to upload',
						button: {
							text: 'Use this image',
						},
						multiple: false, // Set to true to allow multiple files to be selected
					});

					// When an image is selected, run a callback.
					file_frame.on('select', function () {
						// We set multiple to false so only get one image from the uploader
						attachment = file_frame
							.state()
							.get('selection')
							.first()
							.toJSON();

						// Do something with attachment.id and/or attachment.url here
						jQuery(media_preview_field)
							.attr('src', attachment.url)
							.css('width', 'auto');
						jQuery(media_upload_field).val(attachment.id);
					});

					// Finally, open the modal
					file_frame.open();
				});

			jQuery(item)
				.find('input[type="button"].image-remove-button')
				.on('click', function (e) {
					e.preventDefault();
					jQuery(media_upload_field).val('');
					const default_image_url =
						jQuery(media_preview_field).data('default');
					if (typeof default_image_url !== 'undefined') {
						jQuery(media_preview_field).attr(
							'src',
							default_image_url
						);
					} else {
						jQuery(media_preview_field).attr('src', '');
					}
				});
		});
	}

	/**
	 * On the Course Settings metabox. If it contains no items we remove the metabox.
	 */
	if (
		!jQuery(
			'body.post-type-sfwd-courses #sfwd-courses.postbox .inside .sfwd_input'
		).length
	) {
		jQuery('body.post-type-sfwd-courses #sfwd-courses.postbox').remove();
	}

	/**
	 * On the Lesson Settings metabox. If it contains no items we remove the metabox.
	 */
	if (
		!jQuery(
			'body.post-type-sfwd-lessons #sfwd-lessons.postbox .inside .sfwd_input'
		).length
	) {
		jQuery('body.post-type-sfwd-lessons #sfwd-lessons.postbox').remove();
	}

	/**
	 * On the Lesson Settings metabox. If it contains no items we remove the metabox.
	 */
	if (
		!jQuery(
			'body.post-type-sfwd-topic #sfwd-topic.postbox .inside .sfwd_input'
		).length
	) {
		jQuery('body.post-type-sfwd-topic #sfwd-topic.postbox').remove();
	}

	/**
	 * On the Quiz Settings metabox. If it contains no items we remove the metabox.
	 */
	if (
		!jQuery(
			'body.post-type-sfwd-quiz #sfwd-quiz.postbox .inside .sfwd_input'
		).length
	) {
		jQuery('body.post-type-sfwd-quiz #sfwd-quiz.postbox').remove();
	}

	/**
	 * On the Question Settings metabox. If it contains no items we remove the metabox.
	 */
	if (
		!jQuery(
			'body.post-type-sfwd-question #sfwd-question.postbox .inside .sfwd_input'
		).length
	) {
		jQuery('body.post-type-sfwd-question #sfwd-question.postbox').remove();
	}

	/**
	 * For the checkbox-switch can have dual labels. One for the 'on' state and one for the
	 * 'off' state. This piece of code hooks into the change early event and swaps the labels.
	 */
	jQuery('.sfwd_options').on(
		'ld_setting_switch_changed_early',
		function (event) {
			if (
				jQuery(event.ld_trigger_data.element).hasClass(
					'learndash-section-field-checkbox-switch'
				)
			) {
				const ld_switch_wrapper = jQuery(
					event.ld_trigger_data.element
				).parents('.ld-switch-wrapper');
				if (
					typeof ld_switch_wrapper !== 'undefined' &&
					jQuery('span.label-text-multiple', ld_switch_wrapper).length
				) {
					// cspell:disable-line -- too many CSS related references.
					jQuery('span.label-text-multiple', ld_switch_wrapper)
						.find('.ld-label-text')
						.hide(); // cspell:disable-line -- too many CSS related references.
					if ('checked' === event.ld_trigger_data.state) {
						const switch_val = jQuery(
							event.ld_trigger_data.element
						).val();
						jQuery('span.label-text-multiple', ld_switch_wrapper)
							.find('.ld-label-text-' + switch_val)
							.show(); // cspell:disable-line -- too many CSS related references.
					} else {
						jQuery('span.label-text-multiple', ld_switch_wrapper)
							.find('span.ld-label-text-')
							.show(); // cspell:disable-line -- too many CSS related references.
					}
				}
			}
		}
	);

	/**
	 * Handle Settings fields switch open/close state change logic.
	 */
	if (
		jQuery('.sfwd_options input.learndash-section-field-checkbox-switch')
			.length
	) {
		jQuery(
			'.sfwd_options input.learndash-section-field-checkbox-switch'
		).each(function (idx, item) {
			jQuery(item).on('click', function (e) {
				const is_inverted =
					jQuery(e.currentTarget).data('inverted') === 1;

				checked_state = is_inverted ? 'checked' : 'unchecked';
				if (jQuery(e.currentTarget).is(':checked')) {
					checked_state = is_inverted ? 'unchecked' : 'checked';
				}

				const trigger_data = {
					type: 'ld_setting_switch_changed_early',
					element: e.currentTarget,
					class: settings_sub_trigger_class,
					state: checked_state,
				};

				jQuery('.sfwd_options').trigger({
					type: 'ld_setting_switch_changed_early',
					ld_trigger_data: trigger_data,
				});

				var settings_sub_trigger_class = jQuery(e.currentTarget).data(
					'settings-sub-trigger'
				);
				if (
					typeof settings_sub_trigger_class !== 'undefined' &&
					jQuery('.sfwd_options .' + settings_sub_trigger_class)
						.length
				) {
					trigger_data.type = 'ld_setting_switch_sub_changed_late';

					if ('checked' === checked_state) {
						jQuery(
							'.sfwd_options .' + settings_sub_trigger_class
						).slideDown(500, function () {
							jQuery(
								'.sfwd_options .' + settings_sub_trigger_class
							).removeClass('ld-settings-sub-state-closed');
							jQuery(
								'.sfwd_options .' + settings_sub_trigger_class
							).addClass('ld-settings-sub-state-open');

							jQuery('.sfwd_options').trigger({
								type: trigger_data.type,
								ld_trigger_data: trigger_data,
							});
						});
					} else {
						jQuery(
							'.sfwd_options .' + settings_sub_trigger_class
						).slideUp(400, function () {
							jQuery(
								'.sfwd_options .' + settings_sub_trigger_class
							).addClass('ld-settings-sub-state-closed');
							jQuery(
								'.sfwd_options .' + settings_sub_trigger_class
							).removeClass('ld-settings-sub-state-open');

							jQuery('.sfwd_options').trigger({
								type: trigger_data.type,
								ld_trigger_data: trigger_data,
							});
						});
					}
				}
				const settings_inner_trigger_class = jQuery(
					e.currentTarget
				).data('settings-inner-trigger');
				if (
					typeof settings_inner_trigger_class !== 'undefined' &&
					jQuery('.sfwd_options .' + settings_inner_trigger_class)
						.length
				) {
					trigger_data.type = 'ld_setting_switch_inner_changed_late';

					if ('checked' === checked_state) {
						jQuery(
							'.sfwd_options .' + settings_inner_trigger_class
						).slideDown(500, function () {
							jQuery(
								'.sfwd_options .' + settings_inner_trigger_class
							).removeClass('ld-settings-inner-state-closed');
							jQuery(
								'.sfwd_options .' + settings_inner_trigger_class
							).addClass('ld-settings-inner-state-open');

							jQuery('.sfwd_options').trigger({
								type: trigger_data.type,
								ld_trigger_data: trigger_data,
							});
						});
					} else {
						jQuery(
							'.sfwd_options .' + settings_inner_trigger_class
						).slideUp(400, function () {
							jQuery(
								'.sfwd_options .' + settings_inner_trigger_class
							).addClass('ld-settings-inner-state-closed');
							jQuery(
								'.sfwd_options .' + settings_inner_trigger_class
							).removeClass('ld-settings-inner-state-open');

							jQuery('.sfwd_options').trigger({
								type: trigger_data.type,
								ld_trigger_data: trigger_data,
							});
						});
					}
				}
				//e.preventDefault();
				e.stopPropagation();
			});
		});
	}

	/**
	 * Handle Settings fields select state change logic.
	 */
	if (jQuery('.sfwd_options select.learndash-section-field-select').length) {
		jQuery('.sfwd_options select.learndash-section-field-select').each(
			function (idx, item) {
				jQuery(item).on('change', function (e) {
					let select_val = jQuery(e.currentTarget).val();
					if (
						typeof select_val === 'undefined' ||
						'-1' == select_val
					) {
						select_val = '';
					}

					const trigger_data = {
						element: e.currentTarget,
						state: 'open',
						value: select_val,
					};

					const settings_sub_trigger_class = jQuery(
						e.currentTarget
					).data('settings-sub-trigger');
					if (
						typeof settings_sub_trigger_class !== 'undefined' &&
						'' !== settings_sub_trigger_class
					) {
						trigger_data.class = settings_inner_trigger_class;
						trigger_data.type =
							'ld_setting_select_sub_changed_early';

						if (
							jQuery(
								'.sfwd_options .' + settings_sub_trigger_class
							).length
						) {
							trigger_data.state = 'closed';

							// First we need to close any open items
							jQuery(
								'.sfwd_options .' + settings_sub_trigger_class
							).each(function (idx, item) {
								jQuery(item).slideUp('fast', function () {
									jQuery(item).removeClass(
										'ld-settings-sub-state-open'
									);
									jQuery(item).addClass(
										'ld-settings-sub-state-closed'
									);

									jQuery('.sfwd_options').trigger({
										type: trigger_data.type,
										ld_trigger_data: trigger_data,
									});
								});
							});
						}

						if ('' !== select_val) {
							//settings_sub_trigger_class = settings_sub_trigger_class + '_'+select_val;
							if (
								jQuery(
									'.sfwd_options .' +
										settings_sub_trigger_class
								).length
							) {
								trigger_data.class = settings_sub_trigger_class;
								jQuery('.sfwd_options').trigger({
									type: trigger_data.type,
									ld_trigger_data: trigger_data,
								});

								trigger_data.type =
									'ld_setting_switch_sub_changed_late';
								trigger_data.state = 'open';

								jQuery(
									'.sfwd_options .' +
										settings_sub_trigger_class
								).slideDown(500, function () {
									jQuery(
										'.sfwd_options .' +
											settings_sub_trigger_class
									).removeClass(
										'ld-settings-sub-state-closed'
									);
									jQuery(
										'.sfwd_options .' +
											settings_sub_trigger_class
									).addClass('ld-settings-sub-state-open');

									jQuery('.sfwd_options').trigger({
										type: trigger_data.type,
										ld_trigger_data: trigger_data,
									});
								});
							}
						}
					}

					var settings_inner_trigger_class = jQuery(
						e.currentTarget
					).data('settings-inner-trigger');
					if (
						typeof settings_inner_trigger_class !== 'undefined' &&
						'' !== settings_inner_trigger_class
					) {
						trigger_data.class = settings_inner_trigger_class;

						// First we need to close any open items
						const parent_fieldset = jQuery(e.currentTarget).parents(
							'.sfwd_option_div'
						)[0];
						if (typeof parent_fieldset !== 'undefined') {
							jQuery(
								'.ld-settings-inner-state-open',
								parent_fieldset
							).each(function (idx, item) {
								jQuery(item).slideUp('fast', function () {
									jQuery(item).removeClass(
										'ld-settings-inner-state-open'
									);
									jQuery(item).addClass(
										'ld-settings-inner-state-closed'
									);

									trigger_data.type =
										'ld_setting_switch_sub_changed_late';
									trigger_data.state = 'closed';

									jQuery('.sfwd_options').trigger({
										type: 'ld_setting_select_changed_early',
										ld_trigger_data: trigger_data,
									});
								});
							});
						}

						settings_inner_trigger_class =
							settings_inner_trigger_class + '_' + select_val;

						if (
							jQuery(
								'.sfwd_options .' + settings_inner_trigger_class
							).length
						) {
							trigger_data.type =
								'ld_setting_switch_inner_changed_early';
							trigger_data.state = 'open';

							jQuery('.sfwd_options').trigger({
								type: trigger_data.type,
								ld_trigger_data: trigger_data,
							});

							trigger_data.type =
								'ld_setting_switch_inner_changed_late';
							jQuery(
								'.sfwd_options .' + settings_inner_trigger_class
							).slideDown(500, function () {
								jQuery(
									'.sfwd_options .' +
										settings_inner_trigger_class
								).removeClass('ld-settings-inner-state-closed');
								jQuery(
									'.sfwd_options .' +
										settings_inner_trigger_class
								).addClass('ld-settings-inner-state-open');

								jQuery('.sfwd_options').trigger({
									type: trigger_data.type,
									ld_trigger_data: trigger_data,
								});
							});
						}
					}

					//e.preventDefault();
					e.stopPropagation();
				});
			}
		);
	}

	/**
	 * Handle Settings fields radio open/close state change logic.
	 */
	if (jQuery('.sfwd_options input.learndash-section-field-radio').length) {
		jQuery('.sfwd_options input.learndash-section-field-radio').each(
			function (idx, item) {
				jQuery(item).on('click', function (e) {
					// First we need to close any open items
					const parent_fieldset = jQuery(e.currentTarget).parents(
						'fieldset'
					)[0];
					if (typeof parent_fieldset !== 'undefined') {
						jQuery(
							'.ld-settings-inner-state-open',
							parent_fieldset
						).each(function (idx, item) {
							jQuery(item).slideUp('fast', function () {
								jQuery(item).removeClass(
									'ld-settings-inner-state-open'
								);
								jQuery(item).addClass(
									'ld-settings-inner-state-closed'
								);

								jQuery('.sfwd_options').trigger({
									type: 'ld_setting_radio_changed_early',
									ld_trigger_data: {
										type: 'ld_setting_changed',
										element: item,
										class: '',
										state: 'unchecked',
									},
								});
							});
						});
					}

					const settings_sub_trigger_class = jQuery(item).data(
						'settings-inner-trigger'
					);
					if (
						typeof settings_sub_trigger_class !== 'undefined' &&
						jQuery('.sfwd_options .' + settings_sub_trigger_class)
							.length
					) {
						checked_state = 'unchecked';
						if (jQuery(e.currentTarget).is(':checked')) {
							checked_state = 'checked';
						}

						const trigger_data = {
							type: 'ld_setting_radio_inner_changed_early',
							element: e.currentTarget,
							class: settings_sub_trigger_class,
							state: checked_state,
						};
						jQuery('.sfwd_options').trigger({
							type: 'ld_setting_changed_early',
							ld_trigger_data: trigger_data,
						});

						trigger_data.type =
							'ld_setting_radio_inner_changed_later';
						if ('checked' === checked_state) {
							jQuery(
								'.sfwd_options .' + settings_sub_trigger_class
							).slideDown(500, function () {
								jQuery(
									'.sfwd_options .' +
										settings_sub_trigger_class
								).removeClass('ld-settings-inner-state-closed');
								jQuery(
									'.sfwd_options .' +
										settings_sub_trigger_class
								).addClass('ld-settings-inner-state-open');

								jQuery('.sfwd_options').trigger({
									type: trigger_data.type,
									ld_trigger_data: trigger_data,
								});
							});
						} else {
							jQuery(
								'.sfwd_options .' + settings_sub_trigger_class
							).slideUp(400, function () {
								jQuery(
									'.sfwd_options .' +
										settings_sub_trigger_class
								).removeClass('ld-settings-inner-state-open');
								jQuery(
									'.sfwd_options .' +
										settings_sub_trigger_class
								).addClass('ld-settings-inner-state-closed');

								jQuery('.sfwd_options').trigger({
									type: trigger_data.type,
									ld_trigger_data: trigger_data,
								});
							});
						}
					}
					//e.preventDefault();
					e.stopPropagation();
				});
			}
		);
	}

	/**
	 * Advanced Settings inline fields toggle.
	 */
	/*
	if (jQuery('.sfwd_options .ld-settings-sub-advanced a.ld-settings-sub-advanced-trigger').length) {
		jQuery('.sfwd_options .ld-settings-sub-advanced a.ld-settings-sub-advanced-trigger').each(function (idx, item) {
			jQuery(item).on( 'click', function (e) {
				var parent_div = jQuery(e.currentTarget).parent('div.ld-settings-sub-advanced');
				if (parent_div !== undefined) {
					var advanced_inner = jQuery('div.ld-settings-sub-advanced-inner', parent_div);
					if (advanced_inner !== undefined) {
						jQuery(advanced_inner).slideToggle(500, function () {
						});
					}
				}
			});
		});
	}
	*/

	/**
	 * Used on the Quiz Result Messages expand/collapse.
	 */
	if (jQuery('.sfwd_options #resultList li .expand-arrow').length) {
		jQuery('.sfwd_options #resultList li .expand-arrow').each(function (
			idx,
			item
		) {
			jQuery(item).on('click', function (e) {
				const parent_li = jQuery(e.currentTarget).parents('li');
				if (parent_li !== undefined) {
					const div_resultEditor = jQuery('.resultEditor', parent_li);
					if (div_resultEditor !== undefined) {
						if (
							jQuery(e.currentTarget).hasClass(
								'expand-arrow-down'
							)
						) {
							jQuery(e.currentTarget).addClass('expand-arrow-up');
							jQuery(e.currentTarget).removeClass(
								'expand-arrow-down'
							);
							jQuery(div_resultEditor).slideDown(400);
						} else {
							jQuery(e.currentTarget).addClass(
								'expand-arrow-down'
							);
							jQuery(e.currentTarget).removeClass(
								'expand-arrow-up'
							);

							jQuery(div_resultEditor).slideUp(500);
						}
					}
				}
			});
		});
	}

	/**
	 * Handle coordination between three checkbox-switch elements. Only one can
	 * be active. When one is active the other two are disabled and show tooltip
	 * messages.
	 */
	var learndash_settings_track_items = {};
	if (jQuery('body.post-type-sfwd-lessons .sfwd_options').length) {
		learndash_settings_track_items = {
			'learndash-lesson-display-content-settings_lesson_video_enabled':
				'',
			'learndash-lesson-display-content-settings_lesson_assignment_upload':
				'',
			'learndash-lesson-display-content-settings_forced_lesson_time_enabled':
				'',
		};
		learndash_update_radio_tracked_items();
	}

	if (jQuery('body.post-type-sfwd-topic .sfwd_options').length) {
		var learndash_settings_track_items = {
			'learndash-topic-display-content-settings_lesson_video_enabled': '',
			'learndash-topic-display-content-settings_lesson_assignment_upload':
				'',
			'learndash-topic-display-content-settings_forced_lesson_time_enabled':
				'',
		};
		learndash_update_radio_tracked_items();
	}

	jQuery('.sfwd_options').on(
		'ld_setting_switch_changed_early',
		function (event) {
			learndash_update_radio_tracked_items();
		}
	);

	function learndash_update_radio_tracked_items() {
		let checked_count = 0;
		if (!jQuery.isEmptyObject(learndash_settings_track_items)) {
			jQuery.each(
				learndash_settings_track_items,
				function (item_id, value) {
					if (jQuery('.sfwd_options input#' + item_id).length) {
						if (
							jQuery('.sfwd_options input#' + item_id).is(
								':checked'
							)
						) {
							learndash_settings_track_items[item_id] = true;
							checked_count += 1;
						} else {
							learndash_settings_track_items[item_id] = false;
						}
					}
				}
			);

			if (checked_count > 0) {
				jQuery.each(
					learndash_settings_track_items,
					function (item_id, value) {
						if (value !== true) {
							jQuery('#' + item_id).attr('disabled', 'disabled');
							jQuery('#' + item_id)
								.parent('.ld-switch')
								.addClass('-disabled');
						}
					}
				);
			} else {
				jQuery.each(
					learndash_settings_track_items,
					function (item_id, value) {
						if (value !== true) {
							jQuery('#' + item_id).attr('disabled', false);
							jQuery('#' + item_id)
								.parent('.ld-switch')
								.removeClass('-disabled');
						}
					}
				);
			}
		}
	}

	/**
	 * Handle the Lessons selector on the Topic edit screen when the Course selector is changed.
	 */

	jQuery(
		'body.post-type-sfwd-topic .sfwd_options select#learndash-topic-access-settings_course'
	).on('change', function () {
		if (window.sfwd_topic_lesson == undefined) {
			window.sfwd_topic_lesson = jQuery(
				'body.post-type-sfwd-topic .sfwd_options select#learndash-topic-access-settings_lesson'
			).val();
		}

		const course_val = jQuery(
			'body.post-type-sfwd-topic .sfwd_options select#learndash-topic-access-settings_course'
		).val();
		if (
			typeof course_val === 'undefined' ||
			course_val === null ||
			course_val === ''
		) {
			jQuery(
				'body.post-type-sfwd-topic .sfwd_options select#learndash-topic-access-settings_lesson'
			).html('');
			return null;
		}

		const data = {
			action: 'select_a_lesson',
			course_id: jQuery(this).val(),
		};

		if (
			jQuery(
				'body.post-type-sfwd-topic .sfwd_options select#learndash-topic-access-settings_lesson'
			).length
		) {
			if (window.sfwd_topic_lesson != '') {
				const lesson_selector_nonce = jQuery(
					'body.post-type-sfwd-topic .sfwd_options select#learndash-topic-access-settings_lesson'
				).data('ld_selector_nonce');
				if (typeof lesson_selector_nonce !== 'undefined') {
					data.ld_selector_nonce = lesson_selector_nonce;
				}
				const lesson_selector_default = jQuery(
					'body.post-type-sfwd-topic .sfwd_options select#learndash-topic-access-settings_lesson'
				).data('ld_selector_default');
				if (typeof lesson_selector_default !== 'undefined') {
					data.ld_selector_default = lesson_selector_default;
				}
				jQuery.post(
					ajaxurl,
					data,
					function (json) {
						window.response = json;
						html = ''; //'<option value="0">' + sfwd_data.select_a_lesson_lang + '</option>';
						jQuery.each(json.opt, function (i, opt) {
							if (opt.key != '' && opt.key != '0') {
								selected =
									opt.key == window.sfwd_topic_lesson
										? 'selected=selected'
										: '';
								html +=
									"<option value='" +
									opt.key +
									"' " +
									selected +
									'>' +
									opt.value +
									'</option>';
							}
						});
						jQuery(
							'body.post-type-sfwd-topic .sfwd_options select#learndash-topic-access-settings_lesson'
						).html(html);
					},
					'json'
				);
			}
		}
	});

	/**
	 * Handle the Lessons selector on the Topic edit screen when the Course selector is changed.
	 */
	jQuery(
		'body.post-type-sfwd-quiz .sfwd_options select#learndash-quiz-access-settings_course'
	).on('change', function () {
		if (window.sfwd_quiz_lesson == undefined) {
			window.sfwd_quiz_lesson = jQuery(
				'body.post-type-sfwd-quiz .sfwd_options select#learndash-quiz-access-settings_lesson'
			).val();
		}

		const course_val = jQuery(
			'body.post-type-sfwd-quiz .sfwd_options select#learndash-quiz-access-settings_course'
		).val();
		if (
			typeof course_val === 'undefined' ||
			course_val === null ||
			course_val === ''
		) {
			jQuery(
				'body.post-type-sfwd-quiz .sfwd_options select#learndash-quiz-access-settings_lesson'
			).html('');
			return null;
		}
		const data = {
			action: 'select_a_lesson_or_topic',
			course_id: jQuery(this).val(),
		};

		if (
			jQuery(
				'body.post-type-sfwd-quiz .sfwd_options select#learndash-quiz-access-settings_lesson'
			).length
		) {
			const lesson_selector_nonce = jQuery(
				'body.post-type-sfwd-quiz .sfwd_options select#learndash-quiz-access-settings_lesson'
			).data('ld_selector_nonce');
			if (typeof lesson_selector_nonce !== 'undefined') {
				data.ld_selector_nonce = lesson_selector_nonce;
			}

			const lesson_selector_default = jQuery(
				'body.post-type-sfwd-quiz .sfwd_options select#learndash-quiz-access-settings_lesson'
			).data('ld_selector_default');
			if (typeof lesson_selector_default !== 'undefined') {
				data.ld_selector_default = lesson_selector_default;
			}
			jQuery.post(
				ajaxurl,
				data,
				function (json) {
					window.response = json;
					html = ''; //'<option value="0">' + sfwd_data.select_a_lesson_lang + '</option>';
					jQuery.each(json.opt, function (i, opt) {
						if (opt.key != '' && opt.key != '0') {
							selected =
								opt.key == window.sfwd_quiz_lesson
									? 'selected=selected'
									: '';
							html +=
								"<option value='" +
								opt.key +
								"' " +
								selected +
								'>' +
								opt.value +
								'</option>';
						}
					});
					jQuery(
						'body.post-type-sfwd-quiz .sfwd_options select#learndash-quiz-access-settings_lesson'
					).html(html);
				},
				'json'
			);
		}
	});

	/**
	 * Handle the Quiz Run Once Cookie Selector.
	 */
	if (
		jQuery('select#learndash-quiz-progress-settings_quizRunOnceType').length
	) {
		jQuery('select#learndash-quiz-progress-settings_quizRunOnceType').on(
			'change',
			function () {
				const select_val = jQuery(this).val();

				// Always hide this.
				jQuery(
					'#learndash-quiz-progress-settings_quizRunOnceCookie_field'
				).hide();

				// If value is '2' for Logged in users only then no cookie is used.
				// So hide the cookie field.
				if (select_val == 2) {
					jQuery(
						'#learndash-quiz-progress-settings_quiz_reset_cookies_field'
					).hide();
				} else {
					jQuery(
						'#learndash-quiz-progress-settings_quiz_reset_cookies_field'
					).show();
				}
			}
		);
		jQuery(
			'select#learndash-quiz-progress-settings_quizRunOnceType'
		).change();
	}

	/**
	 * Handle Settings Themes select state change logic.
	 */
	if (
		jQuery(
			'.sfwd_options select#learndash_settings_courses_themes_active_theme'
		).length
	) {
		jQuery(
			'.sfwd_options select#learndash_settings_courses_themes_active_theme'
		).each(function (idx, item) {
			jQuery(item).on('change', function (e) {
				let select_theme_val = jQuery(e.currentTarget).val();

				// If a theme inherits settings from another theme then we need to change the select value to the parent theme.
				if ( learndash_admin_settings_data.themes_inheriting_settings[ select_theme_val ] !== undefined ) {
					select_theme_val = learndash_admin_settings_data.themes_inheriting_settings[ select_theme_val ];
				}

				jQuery(
					'.sfwd_options .ld-theme-settings-section-state-open'
				).slideUp(500, function () {
					jQuery(this).removeClass(
						'ld-theme-settings-section-state-open'
					);
					jQuery(this).addClass(
						'ld-theme-settings-section-state-closed'
					);
				});

				if (select_theme_val !== '') {
					jQuery(
						'.sfwd_options .ld-theme-settings-section-' +
							select_theme_val
					).slideDown(500, function () {
						jQuery(this).removeClass(
							'ld-theme-settings-section-state-closed'
						);
						jQuery(this).addClass(
							'ld-theme-settings-section-state-open'
						);
					});
				}

				e.stopPropagation();
			});
		});
	}

	/**
	 * Handle the Template load button on the Quiz / Questions edit metabox.
	 */
	if (jQuery('.sfwd_options input[name="templateLoad"]').length) {
		// Hide the Load Template section if there are no options. Other than the default message.
		if (
			jQuery('.sfwd_options select[name="templateLoadId"] option')
				.length < 2
		) {
			const template_load_settings_row = jQuery(
				'.sfwd_options input[name="templateLoad"]'
			).parents('.sfwd_input');
			if (typeof template_load_settings_row !== 'undefined') {
				jQuery(template_load_settings_row).hide();
			}
		}

		jQuery('.sfwd_options input[name="templateLoad"]').on(
			'click',
			function () {
				if (
					jQuery('.sfwd_options select[name="templateLoadId"]').length
				) {
					let template_load_url = jQuery(
						'.sfwd_options select[name="templateLoadId"]'
					).val();
					if (template_load_url != '' && template_load_url != '-1') {
						if (
							jQuery(
								'.sfwd_options input[name="templateLoadReplaceCourse"]'
							).length
						) {
							if (
								jQuery(
									'.sfwd_options input[name="templateLoadReplaceCourse"]'
								).checked
							) {
								const template_course = jQuery(
									'.sfwd_options input[name="templateLoadReplaceCourse"]'
								).val();
								if (
									typeof template_course !== 'undefined' &&
									'' !== template_course
								) {
									template_load_url =
										template_load_url +
										'&templateLoadReplaceCourse=' +
										template_course;
								}
							}
						}
						window.location.href = template_load_url;
					}
				}

				return false;
			}
		);
	}

	/**
	 * Handle number fields with limits and filtering.
	 *
	 * @param  input_field
	 */
	function learndash_get_input_config(input_field) {
		let input_config = input_field.data('input-config');
		if (typeof input_config === 'undefined') {
			input_config = {};

			input_config.input_min = input_field.attr('min');
			if (typeof input_config.input_min === 'undefined') {
				input_config.input_min = '';
			}

			input_config.input_max = input_field.attr('max');
			if (typeof input_config.input_max === 'undefined') {
				input_config.input_max = '';
			}

			input_config.input_step = input_field.attr('step');
			if (typeof input_config.input_step === 'undefined') {
				input_config.input_step = '';
			}

			input_config.can_decimal = input_field.attr('can_decimal');
			if (
				typeof input_config.can_decimal !== 'undefined' &&
				'' !== input_config.can_decimal
			) {
				if ('true' === input_config.can_decimal) {
					input_config.can_decimal = 2;
				} else {
					input_config.can_decimal = parseInt(
						input_config.can_decimal.toString()
					);
				}

				if ('' === input_config.can_decimal) {
					input_config.can_decimal = false;
				}
			} else {
				input_config.can_decimal = false;
			}

			input_config.can_empty = input_field.attr('can_empty');
			if (
				typeof input_config.can_empty !== 'undefined' &&
				('true' === input_config.can_empty ||
					'1' === input_config.can_empty)
			) {
				input_config.can_empty = true;
			} else {
				input_config.can_empty = false;
			}

			if (
				'' === input_config.input_step &&
				'' === input_config.input_min &&
				'' === input_config.input_max
			) {
				return false;
			}

			if (input_config.can_decimals > 0) {
				if ('' !== input_config.input_min) {
					input_config.input_min = parseFloat(input_config.input_min);
				}

				if ('' !== input_config.input_max) {
					input_config.input_max = parseFloat(input_config.input_max);
				}

				if ('' !== input_config.input_step) {
					input_config.input_step = parseFloat(
						input_config.input_step
					);
				}
			} else {
				if ('' !== input_config.input_min) {
					input_config.input_min = parseInt(input_config.input_min);
				}

				if ('' !== input_config.input_max) {
					input_config.input_max = parseInt(input_config.input_max);
				}

				if ('' !== input_config.input_step) {
					input_config.input_step = parseInt(input_config.input_step);
				}
			}

			input_field.data('input-config', input_config);
		}

		return input_config;
	}

	const learndash_settings_fields_errors = {};

	function learndash_set_input_error(target, invalid, error_key) {
		if (jQuery(target).length && 'undefined' !== typeof target.type) {
			const target_type = target.type;
			const input_wrapper = jQuery(target).parents('.sfwd_input');
			if ('undefined' !== typeof input_wrapper) {
				const input_id = jQuery(input_wrapper).attr('id');

				let error_message = '';
				if (invalid === true) {
					if ('undefined' !== typeof error_key) {
						if (
							'undefined' !==
							typeof learndash_admin_settings_data.settings_fields_errors
						) {
							if (
								'undefined' !==
								typeof learndash_admin_settings_data
									.settings_fields_errors[target_type]
							) {
								if (
									'undefined' !==
									typeof learndash_admin_settings_data
										.settings_fields_errors[target_type][
										error_key
									]
								) {
									error_message =
										learndash_admin_settings_data
											.settings_fields_errors[
											target_type
										][error_key];
								}
							}
						}
					}

					if (
						'undefined' ===
						typeof learndash_settings_fields_errors[input_id]
					) {
						const input_label = jQuery(
							'.sfwd_option_label label',
							input_wrapper
						).html();
						if ('undefined' !== typeof input_label) {
							learndash_settings_fields_errors[input_id] =
								input_label.trim();

							const input_error = jQuery(target)
								.next('.learndash-section-field-error')
								.html();
							if ('undefined' !== typeof input_error) {
								learndash_settings_fields_errors[input_id] +=
									' - ' + input_error;
							}

							if (error_message) {
								learndash_settings_fields_errors[input_id] +=
									' : ' + error_message;
							}
						}
					}

					jQuery(input_wrapper).addClass(
						'learndash_settings_field_invalid'
					);
					jQuery(input_wrapper)
						.find('.learndash-section-field-error')
						.show();
				} else {
					if (
						'undefined' !==
						typeof learndash_settings_fields_errors[input_id]
					) {
						delete learndash_settings_fields_errors[input_id];
					}
					jQuery(input_wrapper).removeClass(
						'learndash_settings_field_invalid'
					);
					jQuery(input_wrapper)
						.find('.learndash-section-field-error')
						.hide();
				}
			}
		}

		learndash_update_header_notice();
	}

	function learndash_update_header_notice() {
		if (!jQuery('#learndash-settings-fields-notice-errors').length) {
			if (
				'undefined' ===
				typeof learndash_admin_settings_data.admin_notice_settings_fields_errors_container
			) {
				return;
			}

			jQuery(
				learndash_admin_settings_data.admin_notice_settings_fields_errors_container
			).insertAfter('hr.wp-header-end');
		}

		if (jQuery('#learndash-settings-fields-notice-errors').length) {
			const notice_el = jQuery(
				'#learndash-settings-fields-notice-errors'
			);
			const object_keys = Object.keys(learndash_settings_fields_errors);

			let error_field_list = '';
			for (let i = 0; i < object_keys.length; i++) {
				const object_key = object_keys[i];
				const error_field_label =
					learndash_settings_fields_errors[object_key];
				if ('' !== error_field_label) {
					error_field_list += '<li>' + error_field_label + '</li>';
				}
			}

			//if ( '' !== error_field_list ) {
			//	error_field_list = "<p><ul>" + error_field_list + '</ul></p>';
			//}
			jQuery('ul.errors-list', notice_el).html(error_field_list);
			if (object_keys.length > 0) {
				notice_el.show();
			} else {
				notice_el.hide();
			}
		}
	}

	if (jQuery('.sfwd_options input[type="number"]').length) {
		jQuery('.sfwd_options input[type="number"]').each(function (idx, item) {
			jQuery(item).on('invalid', function (e) {
				if (jQuery(e.currentTarget).length) {
					learndash_set_input_error(e.currentTarget, true);

					// prevent showing the default display
					e.preventDefault();
				}
			});

			jQuery(item).on('input', function (e) {
				if (jQuery(e.currentTarget).length) {
					const input_config = learndash_get_input_config(
						jQuery(e.currentTarget)
					);
					if (false === input_config) {
						return;
					}

					const input_value_current = e.currentTarget.valueAsNumber;

					if ('undefined' === typeof input_value_current) {
						learndash_set_input_error(
							e.currentTarget,
							true,
							'invalid'
						);
						return false;
					}

					if (input_config.can_empty && isNaN(input_value_current)) {
						learndash_set_input_error(
							e.currentTarget,
							false,
							'invalid'
						);
						return true;
					} else if (isNaN(input_value_current)) {
						learndash_set_input_error(
							e.currentTarget,
							true,
							'empty'
						);
						return false;
					}

					if (
						'undefined' !== typeof input_config.input_max &&
						'' !== input_config.input_max &&
						input_value_current > input_config.input_max
					) {
						learndash_set_input_error(
							e.currentTarget,
							true,
							'maximum'
						);
						return false;
					}

					if (
						'undefined' !== typeof input_config.input_min &&
						'' !== input_config.input_min &&
						input_value_current < input_config.input_min
					) {
						learndash_set_input_error(
							e.currentTarget,
							true,
							'minimum'
						);
						return false;
					}

					input_value_current_split = input_value_current
						.toString()
						.split('.');

					if (input_value_current_split.length > 1) {
						if (!input_config.can_decimal) {
							learndash_set_input_error(
								e.currentTarget,
								true,
								'decimal'
							);
							return false;
						} else if (
							input_value_current_split[1].length >
							input_config.can_decimal
						) {
							const input_value_current_fixed =
								input_value_current.toFixed(
									input_config.can_decimal
								);
							if (
								input_value_current_fixed !==
								input_value_current
							) {
								jQuery(e.currentTarget).val(
									input_value_current_fixed
								);
							}
						}
					}

					learndash_set_input_error(e.currentTarget, false);
				}
			});
		});
	}

	jQuery('form#ld_data_remove_form').on('submit', function (event) {
		let ld_data_remove_verify = jQuery('input#ld_data_remove_verify').val();
		const ld_data_remove_confirm = jQuery(
			'input#ld_data_remove_verify'
		).data('confirm');

		if (
			typeof ld_data_remove_verify === 'undefined' ||
			typeof ld_data_remove_confirm === 'undefined'
		) {
			event.preventDefault();
			return;
		}

		ld_data_remove_verify = ld_data_remove_verify.trim();
		if (ld_data_remove_verify === '' || ld_data_remove_confirm === '') {
			event.preventDefault();
			return;
		}

		if (!confirm(ld_data_remove_confirm)) {
			event.preventDefault();
		}
		// If we get to here the form will submit.
	});

	/**
	 * Update hidden 'country' input field on Payments settings page when selecting a country from the currency selector.
	 */
	if (jQuery('select#learndash_settings_payments_defaults_currency').length) {
		console.log(
			jQuery('select#learndash_settings_payments_defaults_currency')
				.length
		);
		jQuery('select#learndash_settings_payments_defaults_currency').on(
			'change',
			function () {
				const select_val = jQuery(this)
					.find(':selected')
					.attr('data-country');
				jQuery('#learndash_settings_payments_defaults_country').val(
					select_val
				);
			}
		);
	}
});

/**
 * Change the notice type for LD plugins in the listing that have the '.ld-plugin-update-notice' class.
 *
 * The readme.txt section 'Update Notice' if provided will contain critical update information and so
 * we change the notice type from warning to error.
 *
 * @since 3.1.4.
 */
if (
	jQuery(
		'body.plugins-php table.wp-list-table.plugins .ld-plugin-update-notice'
	).length
) {
	jQuery(
		'body.plugins-php table.wp-list-table.plugins .ld-plugin-update-notice'
	).each(function (idx, item) {
		const parent_notice_wrapper = jQuery(item).parents(
			'.update-message.notice-warning'
		);
		if ('undefined' !== typeof parent_notice_wrapper) {
			jQuery(parent_notice_wrapper).removeClass('notice-warning');
			jQuery(parent_notice_wrapper).addClass('notice-error');
		}
	});
}

/**
 * Handle the dismissible license notice. Sends trigger to server to store for 24 hours.
 */
if (jQuery('.learndash-updates-disabled-dismissible').length) {
	jQuery('.learndash-updates-disabled-dismissible').on(
		'click',
		'.notice-dismiss',
		function (event, el) {
			const nonce = jQuery(event.currentTarget)
				.parent('.learndash-updates-disabled-dismissible')
				.data('notice-dismiss-nonce');
			post_data = {
				action: 'learndash_license_notice_dismissed',
				learndash_license_notice_dismissed_nonce: nonce,
			};

			jQuery.ajax({
				type: 'POST',
				url: ajaxurl,
				dataType: 'json',
				cache: false,
				data: post_data,
				error(jqXHR, textStatus, errorThrown) {},
				success(reply_data) {},
			});
		}
	);
}

/**
 * Handle the dismissible hub upgrade notice.
 */
if (jQuery('.learndash_hub_upgrade_dismiss').length) {
	jQuery('.learndash_hub_upgrade_dismiss').on(
		'click',
		'.notice-dismiss',
		function (event, el) {
			const nonce = jQuery(event.currentTarget)
				.parent('.learndash_hub_upgrade_dismiss')
				.data('notice-dismiss-nonce');
			post_data = {
				action: 'learndash_hub_upgrade_dismissed',
				learndash_hub_upgrade_dismissed_nonce: nonce,
			};

			jQuery.ajax({
				type: 'POST',
				url: ajaxurl,
				dataType: 'json',
				cache: false,
				data: post_data,
				error(jqXHR, textStatus, errorThrown) {},
				success(reply_data) {},
			});
		}
	);
}

/**
 * Handle the dismissible license notice. Sends trigger to server to store for 24 hours.
 */
if (jQuery('.ld-plugin-other-plugins-notice').length) {
	jQuery('.ld-plugin-other-plugins-notice').on(
		'click',
		'.notice-dismiss',
		function (event, el) {
			const nonce = jQuery(event.currentTarget)
				.parent('.ld-plugin-other-plugins-notice')
				.data('notice-dismiss-nonce');
			post_data = {
				action: 'learndash_other_plugins_notice_dismissed',
				learndash_other_plugins_notice_dismissed_nonce: nonce,
			};
			jQuery.ajax({
				type: 'POST',
				url: ajaxurl,
				dataType: 'json',
				cache: false,
				data: post_data,
				error(jqXHR, textStatus, errorThrown) {},
				success(reply_data) {},
			});
		}
	);
}

function learndash_course_edit_page_billing_cycle_javascript() {
	if (jQuery('.sfwd_options select[name=course_price_billing_t3]').length) {
		var selector = jQuery(
			'.sfwd_options select[name=course_price_billing_t3]'
		);
	} else if (
		jQuery('.sfwd_options select[name=group_price_billing_t3]').length
	) {
		var selector = jQuery(
			'.sfwd_options select[name=group_price_billing_t3]'
		);
	}

	if ('undefined' !== typeof selector) {
		const parent = selector.parent();
		let billing_cycle = selector.val();

		function build_notice(message) {
			return (
				'<div id="learndash_price_billing_cycle_instructions"><label class="sfwd_help_text">' +
				message +
				'</label></div>'
			);
		}

		function output_message() {
			switch (billing_cycle) {
				case 'D':
					message = sfwd_data.valid_recurring_paypal_day_range;
					max_value = sfwd_data.valid_recurring_paypal_day_max;
					break;

				case 'W':
					message = sfwd_data.valid_recurring_paypal_week_range;
					max_value = sfwd_data.valid_recurring_paypal_week_max;
					break;

				case 'M':
					message = sfwd_data.valid_recurring_paypal_month_range;
					max_value = sfwd_data.valid_recurring_paypal_month_max;
					break;

				case 'Y':
					message = sfwd_data.valid_recurring_paypal_year_range;
					max_value = sfwd_data.valid_recurring_paypal_year_max;
					break;

				default:
					message = '';
					max_value = 0;
					break;
			}
			parent.append(build_notice(message));

			const billing_input = parent.find(
				'input[name="course_price_billing_p3"'
			);
			if (billing_input.val() > max_value) {
				billing_input.val(max_value);
			}
			billing_input.prop('max', max_value);
		}

		output_message();

		selector.on('change', function (e) {
			billing_cycle = selector.val();
			jQuery('#learndash_price_billing_cycle_instructions').remove();
			output_message(billing_cycle);
		});
	}

	if (jQuery('table.learndash-settings-table-sortable').length) {
		jQuery('table.learndash-settings-table-sortable tbody').sortable();
	}
}

function learndash_get_base_select2_args() {
	return {
		theme: 'learndash',
		ajax: null,
		allowClear: true,
		width: 'resolve',
		dir: window.isRtl ? 'rtl' : '',
		dropdownAutoWidth: true,
		language: {
			loadingMore() {
				if (
					typeof learndash_admin_settings_data.selec2_language
						.loadingMore !== 'undefined'
				) {
					return learndash_admin_settings_data.selec2_language
						.loadingMore;
				}
				return 'Loading more results…';
			},
			noResults() {
				if (
					typeof learndash_admin_settings_data.selec2_language
						.noResults !== 'undefined'
				) {
					return learndash_admin_settings_data.selec2_language
						.noResults;
				}
				return 'No results found';
			},
			searching() {
				if (
					typeof learndash_admin_settings_data.selec2_language
						.searching !== 'undefined'
				) {
					return learndash_admin_settings_data.selec2_language
						.searching;
				}
				return 'Searching…';
			},
			removeAllItems() {
				if (
					typeof learndash_admin_settings_data.selec2_language
						.removeAllItems !== 'undefined'
				) {
					return learndash_admin_settings_data.selec2_language
						.removeAllItems;
				}
				return 'Remove all items';
			},
			removeItem() {
				if (
					typeof learndash_admin_settings_data.selec2_language
						.removeItem !== 'undefined'
				) {
					return learndash_admin_settings_data.selec2_language
						.removeItem;
				}
				return 'Remove item';
			},
		},
		//escapeMarkup: function (markup) { return markup; }
	};
}

/**
 * Populate Select2 dropdowns with data
 *
 * @param  action
 * @param  el
 * @return {{url, dataType: string, method: string, delay: number, data: data, processResults: processResults}}
 */
function learndash_settings_select2_ajax(el) {
	const query_data = jQuery(el).data('select2-query-data');

	if (typeof query_data === 'undefined' || query_data === '') {
		return null;
	}

	// Trigger change when the selector is cleared.
	jQuery(el).on('select2:unselect', function (e) {
		jQuery(el).trigger('change');
	});

	return {
		url: learndash_admin_settings_data.ajaxurl,
		dataType: 'json',
		method: 'post',
		delay: 1500,
		cache: true,
		data(params) {
			return {
				action: 'learndash_settings_select2_query',
				query_data: query_data || '',
				search: params.term || '',
				page: params.page || 1,
			};
		},
		processResults(response, params) {
			params.page = params.page || 1;

			return {
				results: response.items,
				pagination: {
					more: params.page < response.total_pages,
				},
			};
		},
	};
}

/**
 * Trigger resize on load to trigger the resizing of
 * .sfwd_options .ld-settings-sub elements.
 */
/*
jQuery(window).load(function () {
	setTimeout(function () {
		jQuery(window).trigger('resize');
	}, 1000);
});
*/

/**
 * Handle moving metabox description into title area.
 */
(function () {
	document.addEventListener('DOMContentLoaded', function () {
		const LDdescriptions = document.querySelectorAll(
			'.ld-metabox-description'
		);
		Array.prototype.forEach.call(LDdescriptions, (description) => {
			// Find the metabox h2 and append the description inside it
			// description.parentNode is <div class="inside"></div>
			// description.parentNode.previousElementSibling is the <h2 class="hndle"></h2>
			description.parentNode.previousElementSibling.appendChild(
				description
			);
		});
	});
})();

//if ('undefined' === typeof window.learndash) {

/**
 * @namespace learndash
 */
//	window.learndash = {};
//}

//if ('undefined' === typeof window.learndash.admin) {

/**
 * @namespace learndash.admin
 */
//	window.learndash.admin = {};
//}

/**
 * @namespace learndash.admin.settings
 */
/*
window.learndash.admin.settings = {
	toggleClassName: 'learndash-section-field-checkbox-switch',
	toggleSettingsVisibility: function (event) {
		if (event.target.classList.contains(this.toggleClassName)) {
			var dataSet = event.target.dataset.settingsSubTrigger;
			if (dataSet) {
				var divToToggle = document.querySelector('.' + dataSet);
				if (divToToggle) {
					divToToggle.style.display = divToToggle.style.display === 'none' ? 'block' : 'none';
				}
			}
		}
	},
	eventListeners: function () {
		document.querySelector('body').addEventListener('change', learndash.admin.settings.toggleSettingsVisibility.bind(learndash.admin.settings));
	}
};
window.onload = learndash.admin.settings.eventListeners;
*/

jQuery(function ($) {
	/**
	 * Handle assignments modal view.
	 */
	$('.view-learndash-assignment').on('click', function (e) {
		e.preventDefault();

		const content = '<img src="' + $(this).attr('href') + '" />';

		$('#learndash-admin-table-modal')
			.html(content)
			.dialog({
				modal: true,
				draggable: false,
				resizable: false,
				width: '70%',
				title: $(this).data('title'),
			});
	});

	/**
	 * Handle essays modal view.
	 */
	$('.view-learndash-essay').on('click', function (e) {
		e.preventDefault();

		$.ajax({
			url: $(this).attr('href'),
			method: 'POST',
			success(response) {
				if (response.success) {
					$('#learndash-admin-table-modal')
						.html(response.data.content)
						.dialog({
							modal: true,
							draggable: false,
							resizable: false,
							width: '70%',
							title: response.data.title,
						});
				}
			},
		});
	});

	/**
	 * Common.
	 */

	// Tabs.
	$('.sfwd_options .tabs .tab').on('click', function () {
		$(this).addClass('selected').siblings().removeClass('selected');

		$($(this).data('target-id')).show().siblings().hide();
	});

	/**
	 * Logs.
	 */

	// Log selection.

	function learndash_load_log_content() {
		const selected_log_name = $('#learndash-options-logs-select').val();

		$('.learndash-options-logs-list-item').fadeOut();

		if (!selected_log_name.length) {
			return;
		}

		$.post({
			url: learndash_admin_settings_data.ajaxurl,
			data: {
				action: learndash_admin_settings_data.logs.actions
					.get_log_content.name,
				nonce: learndash_admin_settings_data.logs.actions
					.get_log_content.nonce,
				name: selected_log_name,
			},
			success(response) {
				if (response.success) {
					const container = $(
						'.learndash-options-logs-list-item[data-name="' +
							selected_log_name +
							'"]'
					);

					container.fadeIn();

					container
						.find('.learndash-options-logs-list-item-content')
						.val(response.data.content);

					container
						.find('.learndash-options-logs-download')
						.toggle(response.data.content.length > 0);
				} else {
					alert(response.data.message);
				}
			},
		});

		return false;
	}

	$('#learndash-options-logs-select').on(
		'change',
		learndash_load_log_content
	);
	$('.learndash-options-logs-refresh').on(
		'click',
		learndash_load_log_content
	);

	// Log deletion.

	$('.learndash-options-logs-delete').on('click', function () {
		const selected_log_name = $('#learndash-options-logs-select').val();

		if (!selected_log_name.length) {
			return;
		}

		$.post({
			url: learndash_admin_settings_data.ajaxurl,
			data: {
				action: learndash_admin_settings_data.logs.actions
					.delete_log_content.name,
				nonce: learndash_admin_settings_data.logs.actions
					.delete_log_content.nonce,
				name: selected_log_name,
			},
			success(response) {
				if (response.success) {
					const container = $(
						'.learndash-options-logs-list-item[data-name="' +
							selected_log_name +
							'"]'
					);

					container
						.find('.learndash-options-logs-list-item-content')
						.val('');

					container.find('.learndash-options-logs-download').hide();
				}

				alert(response.data.message);
			},
		});

		return false;
	});

	/**
	 * Bulk Edit.
	 */

	function map_bulk_edit_filters(container) {
		const filters = [];

		container.find('.bulk-edit-filter').each(function () {
			const $filter = $(this).find('.learndash-filter');

			const value = ['radio', 'checkbox'].includes($filter.attr('type'))
				? $(this).find('.learndash-filter:checked').val()
				: $filter.val();

			filters.push({
				enabled: $(this)
					.find('.bulk-edit-display-switcher')
					.is(':checked'),
				name: $filter.attr('name'),
				value,
			});
		});

		return filters;
	}

	function map_bulk_edit_fields(container) {
		const fields = [];

		container.find('.bulk-edit-field').each(function () {
			// Map the parent field.

			const $parent_switcher = $(this).find(
				'.bulk-edit-display-switcher'
			);
			const field_name = $parent_switcher.data('field-name');
			const $field = $('[name="' + field_name + '"]');

			const value = ['radio', 'checkbox'].includes($field.attr('type'))
				? $('[name="' + field_name + '"]:checked').val() || ''
				: $field.val();

			fields.push({
				name: field_name,
				value,
				enabled: $parent_switcher.is(':checked'),
			});

			// Map child fields.
			$(this)
				.find('.bulk-edit-child-display-switcher-container')
				.each(function () {
					const enabled = $(this)
						.find('.bulk-edit-child-display-switcher')
						.is(':checked');

					$(this)
						.next()
						.find('.sfwd_option_div input, .sfwd_option_div select')
						.each(function () {
							const value = ['radio', 'checkbox'].includes(
								$(this).attr('type')
							)
								? $(
										'[name="' +
											$(this).attr('name') +
											'"]:checked'
								  ).val() || ''
								: $(this).val();

							fields.push({
								name: $(this).attr('name'),
								value,
								enabled,
							});
						});
				});
		});

		return fields;
	}

	function update_bulk_edit_button_state(container) {
		const button = container.find('.bulk-edit-button');

		const enabled_fields_count = container.find(
			'.bulk-edit-fields .bulk-edit-display-switcher:checked'
		).length;
		const affected_posts_number =
			parseInt(button.find('.posts-number').html()) || 0;

		button.attr(
			'disabled',
			0 === enabled_fields_count || 0 === affected_posts_number
		);
	}

	function update_affected_posts_number(container) {
		$.post({
			url: learndash_admin_settings_data.ajaxurl,
			data: {
				action: container.data('action-get-affected-posts-number'),
				nonce: container.data('action-get-affected-posts-number-nonce'),
				filters: map_bulk_edit_filters(container),
			},
			success(response) {
				if (response.success) {
					container
						.find('.posts-number')
						.html(response.data.posts_number);

					update_bulk_edit_button_state(container);
				} else {
					alert(response.data.message);
				}
			},
		});
	}

	// Add checkboxes on document ready to support enabling of child fields.

	$('#learndash-bulk-edit .sfwd_input').each(function () {
		const $label = $(this).find('label');
		const label_text = $label.html();
		const id = $label.attr('for') + '-switcher';

		let input = '';
		input +=
			'<div class="sfwd_input bulk-edit-child-display-switcher-container">';
		input +=
			'<input type="checkbox" id="' +
			id +
			'" class="bulk-edit-child-display-switcher" autocomplete="off">';
		input += '<label for="' + id + '">' + label_text + '</label>';
		input += '</div>';

		$(this).before(input).hide();
	});

	// Show a field/filter input when enabled.

	$('#learndash-bulk-edit .bulk-edit-display-switcher').on(
		'change',
		function () {
			$($(this).data('target-id')).toggle();

			update_bulk_edit_button_state($(this).closest('.tab-content'));
		}
	);

	// Show a child field input when enabled.

	$('#learndash-bulk-edit').on(
		'change',
		'.bulk-edit-child-display-switcher',
		function () {
			$(this).parent().next().toggle();
		}
	);

	// Send update request.

	$('#learndash-bulk-edit .bulk-edit-button').on('click', function (e) {
		e.preventDefault();

		const container = $(this).closest('.tab-content');

		$.post({
			url: learndash_admin_settings_data.ajaxurl,
			data: {
				action: container.data('action-update-posts'),
				nonce: container.data('action-update-posts-nonce'),
				filters: map_bulk_edit_filters(container),
				fields: map_bulk_edit_fields(container),
			},
			success(response) {
				if (response.success) {
					alert(response.data.message);
				}
			},
		});
	});

	// Update posts number when a filter is changed.

	$(
		'#learndash-bulk-edit .bulk-edit-filters .learndash-filter, #learndash-bulk-edit .bulk-edit-filters .bulk-edit-display-switcher'
	).on('change', function () {
		update_affected_posts_number($(this).closest('.tab-content'));
	});

	// Update posts number on document ready.

	$('#learndash-bulk-edit .tab-content').each(function () {
		update_affected_posts_number($(this));
	});

	/**
	 * Import / Export.
	 */

	// Toggle the export form.

	$('#learndash-export-all').on('click', function () {
		$('.learndash-export-items').hide();
	});

	$('#learndash-export-selected').on('click', function () {
		$('.learndash-export-items').fadeIn();
	});

	// Send export request.

	$('#learndash-export-button').on('click', function (e) {
		e.preventDefault();

		const container = $('#learndash-export-container');

		const options = {};

		container
			.find('.learndash-export-input')
			.serializeArray()
			.map(function (option) {
				if (options[option.name] === undefined) {
					options[option.name] = [];
				}
				options[option.name].push(option.value);
			});

		$('.notice').hide();

		$.post({
			url: learndash_admin_settings_data.ajaxurl,
			data: {
				action: container.data('action-name'),
				nonce: container.data('action-nonce'),
				type: container.find('.learndash-export-type:checked').val(),
				options,
			},
			success(response) {
				if (response.success) {
					$('#learndash-export-info').fadeIn();

					$('#learndash-export-button')
						.text(
							$('#learndash-export-button').text() +
								learndash_admin_settings_data.in_progress_label
						)
						.attr('disabled', true);
				} else {
					const alert = $('#learndash-export-error');
					alert.find('p').html(response.data.message);
					alert.fadeIn();
				}
			},
		});
	});

	// Send import request.

	$('#learndash-import-button').on('click', function (e) {
		e.preventDefault();

		const button = $(this);
		const container = $('#learndash-import-container');
		const importFile = $('#learndash-import-file')[0];

		if (importFile.files.length === 0 || importFile.files[0].size === 0) {
			alert(learndash_admin_settings_data.import_file_empty);
			button.attr('disabled', false).text(defaultButtonLabel);

			return;
		}

		if (
			importFile.files[0].size >
			learndash_admin_settings_data.import_file_size_limit
		) {
			alert(
				learndash_admin_settings_data.import_file_size_limit_exceeded
			);
			button.attr('disabled', false).text(defaultButtonLabel);

			return;
		}

		const formData = new FormData();

		formData.append('action', container.data('action-name'));
		formData.append('nonce', container.data('action-nonce'));
		formData.append('file', importFile.files[0]);

		$('.notice').hide();

		const defaultButtonLabel = button.text();

		button
			.attr('disabled', true)
			.text(learndash_admin_settings_data.uploading_label);

		$.post({
			url: learndash_admin_settings_data.ajaxurl,
			data: formData,
			processData: false,
			contentType: false,
			success(response) {
				if (response.success) {
					$('#learndash-import-info').fadeIn();

					$('#learndash-import-button').text(
						defaultButtonLabel +
							learndash_admin_settings_data.in_progress_label
					);
				} else {
					const alert = $('#learndash-import-error');
					alert.find('p').html(response.data.message);
					alert.fadeIn();

					button.attr('disabled', false).text(defaultButtonLabel);
				}
			},
		}).fail(function (response) {
			const alert = $('#learndash-import-error');
			alert
				.find('p')
				.html(
					'Upload failed: ' +
						response.status +
						' - ' +
						response.statusText
				);
			alert.fadeIn();

			button.attr('disabled', false).text(defaultButtonLabel);
		});
	});

	function learndashValidateStripeWebhook(callIndex = 1) {
		const $button = $('#learndash-validate-stripe-webhook');

		$button.attr('disabled', true);
		$button.find('.learndash-validate-stripe-webhook-text-default').hide();
		$button.find('.learndash-validate-stripe-webhook-text-loading').show();

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			dataType: 'json',
			data: {
				action: 'learndash_validate_stripe_webhook',
				nonce: $button.data('nonce'),
				call_index: callIndex,
			},
		}).done(function (response) {
			if (response.success) {
				if (response.data.success) {
					$button.attr('disabled', false);
					$button
						.find('.learndash-validate-stripe-webhook-text-loading')
						.hide();
					$button
						.find('.learndash-validate-stripe-webhook-text-default')
						.show();

					$('#learndash-stripe-webhook-validation-success').show();
				} else if (response.data.fail) {
					$button.attr('disabled', false);
					$button
						.find('.learndash-validate-stripe-webhook-text-loading')
						.hide();
					$button
						.find('.learndash-validate-stripe-webhook-text-default')
						.show();

					$('#learndash-stripe-webhook-validation-error').show();
				} else if (response.data.progress) {
					callIndex++;

					setTimeout(function () {
						learndashValidateStripeWebhook(callIndex);
					}, 5000);
				}
			} else {
				alert(response.data.message);
			}
		});
	}

	$('#learndash-validate-stripe-webhook').on('click', function (e) {
		e.preventDefault();

		$('#learndash-stripe-webhook-validation-success').hide();
		$('#learndash-stripe-webhook-validation-error').hide();

		learndashValidateStripeWebhook();
	});
});
