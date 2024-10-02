(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);throw new Error("Cannot find module '"+o+"'")}var f=n[o]={exports:{}};t[o][0].call(f.exports,function(e){var n=t[o][1][e];return s(n?n:e)},f,f.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
/* global WPFormsUtils */
'use strict';

function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
(function ($) {
  /**
   * All connections are slow by default.
   *
   * @since 1.6.2
   *
   * @type {boolean|null}
   */
  var isSlow = null;

  /**
   * Previously submitted data.
   *
   * @since 1.7.1
   *
   * @type {Array}
   */
  var submittedValues = [];

  /**
   * Default settings for our speed test.
   *
   * @since 1.6.2
   *
   * @type {{maxTime: number, payloadSize: number}}
   */
  var speedTestSettings = {
    maxTime: 3000,
    // Max time (ms) it should take to be considered a 'fast connection'.
    payloadSize: 100 * 1024 // Payload size.
  };

  /**
   * Create a random payload for the speed test.
   *
   * @since 1.6.2
   *
   * @returns {string} Random payload.
   */
  function getPayload() {
    var data = '';
    for (var i = 0; i < speedTestSettings.payloadSize; ++i) {
      data += String.fromCharCode(Math.round(Math.random() * 36 + 64));
    }
    return data;
  }

  /**
   * Run speed tests and flag the clients as slow or not. If a connection
   * is slow it would let the backend know and the backend most likely
   * would disable parallel uploads and would set smaller chunk sizes.
   *
   * @since 1.6.2
   *
   * @param {Function} next Function to call when the speed detection is done.
   */
  function speedTest(next) {
    if (null !== isSlow) {
      setTimeout(next);
      return;
    }
    var data = getPayload();
    var start = new Date();
    wp.ajax.post({
      action: 'wpforms_file_upload_speed_test',
      data: data
    }).then(function () {
      var delta = new Date() - start;
      isSlow = delta >= speedTestSettings.maxTime;
      next();
    }).fail(function () {
      isSlow = true;
      next();
    });
  }

  /**
   * Toggle loading message above submit button.
   *
   * @since 1.5.6
   *
   * @param {object} $form jQuery form element.
   *
   * @returns {Function} event handler function.
   */
  function toggleLoadingMessage($form) {
    return function () {
      if ($form.find('.wpforms-uploading-in-progress-alert').length) {
        return;
      }
      $form.find('.wpforms-submit-container').before("<div class=\"wpforms-error-alert wpforms-uploading-in-progress-alert\">\n\t\t\t\t\t\t".concat(window.wpforms_file_upload.loading_message, "\n\t\t\t\t\t</div>"));
    };
  }

  /**
   * Is a field loading?
   *
   * @since 1.7.6
   *
   * @param {object} dz Dropzone object.
   *
   * @returns {boolean} true if the field is loading.
   */
  function uploadInProgress(dz) {
    return dz.loading > 0 || dz.getFilesWithStatus('error').length > 0;
  }

  /**
   * Is at least one field loading?
   *
   * @since 1.7.6
   *
   * @returns {boolean} true if at least one field is loading.
   */
  function anyUploadsInProgress() {
    var anyUploadsInProgress = false;
    window.wpforms.dropzones.some(function (dz) {
      if (uploadInProgress(dz)) {
        anyUploadsInProgress = true;
        return true;
      }
    });
    return anyUploadsInProgress;
  }

  /**
   * Disable submit button and add overlay.
   *
   * @param {object} $form jQuery form element.
   */
  function disableSubmitButton($form) {
    // Find the primary submit button and the "Next" button for multi-page forms.
    var $btn = $form.find('.wpforms-submit');
    var $btnNext = $form.find('.wpforms-page-next:visible');
    var handler = toggleLoadingMessage($form); // Get the handler function for loading message toggle.

    // For multi-pages layout, use the "Next" button instead of the primary submit button.
    if ($form.find('.wpforms-page-indicator').length !== 0 && $btnNext.length !== 0) {
      $btn = $btnNext;
    }

    // Disable the submit button.
    $btn.prop('disabled', true);
    WPFormsUtils.triggerEvent($form, 'wpformsFormSubmitButtonDisable', [$form, $btn]);

    // If the overlay is not already added and the button is of type "submit", add an overlay.
    if (!$form.find('.wpforms-submit-overlay').length && $btn.attr('type') === 'submit') {
      // Add a container for the overlay and append the overlay element to it.
      $btn.parent().addClass('wpforms-submit-overlay-container');
      $btn.parent().append('<div class="wpforms-submit-overlay"></div>');

      // Set the overlay dimensions to match the submit button's size.
      $form.find('.wpforms-submit-overlay').css({
        width: "".concat($btn.outerWidth(), "px"),
        height: "".concat($btn.parent().outerHeight(), "px")
      });

      // Attach the click event to the overlay so that it triggers the handler function.
      $form.find('.wpforms-submit-overlay').on('click', handler);
    }
  }

  /**
   * Disable submit button when we are sending files to the server.
   *
   * @since 1.5.6
   *
   * @param {object} dz Dropzone object.
   */
  function toggleSubmit(dz) {
    // eslint-disable-line complexity

    var $form = jQuery(dz.element).closest('form'),
      $btn = $form.find('.wpforms-submit'),
      $btnNext = $form.find('.wpforms-page-next:visible'),
      handler = toggleLoadingMessage($form),
      disabled = uploadInProgress(dz);

    // For multi-pages layout.
    if ($form.find('.wpforms-page-indicator').length !== 0 && $btnNext.length !== 0) {
      $btn = $btnNext;
    }
    var isButtonDisabled = Boolean($btn.prop('disabled')) || $btn.hasClass('wpforms-disabled');
    if (disabled === isButtonDisabled) {
      return;
    }
    if (disabled) {
      disableSubmitButton($form);
      return;
    }
    if (anyUploadsInProgress()) {
      return;
    }
    $btn.prop('disabled', false);
    WPFormsUtils.triggerEvent($form, 'wpformsFormSubmitButtonRestore', [$form, $btn]);
    $form.find('.wpforms-submit-overlay').off('click', handler);
    $form.find('.wpforms-submit-overlay').remove();
    $btn.parent().removeClass('wpforms-submit-overlay-container');
    if ($form.find('.wpforms-uploading-in-progress-alert').length) {
      $form.find('.wpforms-uploading-in-progress-alert').remove();
    }
  }

  /**
   * Try to parse JSON or return false.
   *
   * @since 1.5.6
   *
   * @param {string} str JSON string candidate.
   *
   * @returns {*} Parse object or false.
   */
  function parseJSON(str) {
    try {
      return JSON.parse(str);
    } catch (e) {
      return false;
    }
  }

  /**
   * Leave only objects with length.
   *
   * @since 1.5.6
   *
   * @param {object} el Any array.
   *
   * @returns {bool} Has length more than 0 or no.
   */
  function onlyWithLength(el) {
    return el.length > 0;
  }

  /**
   * Leave only positive elements.
   *
   * @since 1.5.6
   *
   * @param {*} el Any element.
   *
   * @returns {*} Filter only positive.
   */
  function onlyPositive(el) {
    return el;
  }

  /**
   * Get xhr.
   *
   * @since 1.5.6
   *
   * @param {object} el Object with xhr property.
   *
   * @returns {*} Get XHR.
   */
  function getXHR(el) {
    return el.chunkResponse || el.xhr;
  }

  /**
   * Get response text.
   *
   * @since 1.5.6
   *
   * @param {object} el Xhr object.
   *
   * @returns {object} Response text.
   */
  function getResponseText(el) {
    return typeof el === 'string' ? el : el.responseText;
  }

  /**
   * Get data.
   *
   * @since 1.5.6
   *
   * @param {object} el Object with data property.
   *
   * @returns {object} Data.
   */
  function getData(el) {
    return el.data;
  }

  /**
   * Get value from files.
   *
   * @since 1.5.6
   *
   * @param {object} files Dropzone files.
   *
   * @returns {object} Prepared value.
   */
  function getValue(files) {
    return files.map(getXHR).filter(onlyPositive).map(getResponseText).filter(onlyWithLength).map(parseJSON).filter(onlyPositive).map(getData);
  }

  /**
   * Sending event higher order function.
   *
   * @since 1.5.6
   * @since 1.5.6.1 Added special processing of a file that is larger than server's post_max_size.
   *
   * @param {object} dz Dropzone object.
   * @param {object} data Adding data to request.
   *
   * @returns {Function} Handler function.
   */
  function sending(dz, data) {
    return function (file, xhr, formData) {
      /*
       * We should not allow sending a file, that exceeds server post_max_size.
       * With this "hack" we redefine the default send functionality
       * to prevent only this object from sending a request at all.
       * The file that generated that error should be marked as rejected,
       * so Dropzone will silently ignore it.
       *
       * If Chunks are enabled the file size will never exceed (by a PHP constraint) the
       * postMaxSize. This block shouldn't be removed nonetheless until the "modern" upload is completely
       * deprecated and removed.
       */
      if (file.size > this.dataTransfer.postMaxSize) {
        xhr.send = function () {};
        file.accepted = false;
        file.processing = false;
        file.status = 'rejected';
        file.previewElement.classList.add('dz-error');
        file.previewElement.classList.add('dz-complete');
        return;
      }
      Object.keys(data).forEach(function (key) {
        formData.append(key, data[key]);
      });
    };
  }

  /**
   * Convert files to input value.
   *
   * @since 1.5.6
   * @since 1.7.1 Added the dz argument.
   *
   * @param {object} files Files list.
   * @param {object} dz Dropzone object.
   *
   * @returns {string} Converted value.
   */
  function convertFilesToValue(files, dz) {
    if (!submittedValues[dz.dataTransfer.formId] || !submittedValues[dz.dataTransfer.formId][dz.dataTransfer.fieldId]) {
      return files.length ? JSON.stringify(files) : '';
    }
    files.push.apply(files, submittedValues[dz.dataTransfer.formId][dz.dataTransfer.fieldId]);
    return JSON.stringify(files);
  }

  /**
   * Get input element.
   *
   * @since 1.7.1
   *
   * @param {object} dz Dropzone object.
   *
   * @returns {jQuery} Hidden input element.
   */
  function getInput(dz) {
    return jQuery(dz.element).parents('.wpforms-field-file-upload').find('input[name=' + dz.dataTransfer.name + ']');
  }

  /**
   * Update value in input.
   *
   * @since 1.5.6
   *
   * @param {object} dz Dropzone object.
   */
  function updateInputValue(dz) {
    var $input = getInput(dz);
    $input.val(convertFilesToValue(getValue(dz.files), dz)).trigger('input');
    if (typeof jQuery.fn.valid !== 'undefined') {
      $input.valid();
    }
  }

  /**
   * Complete event higher order function.
   *
   * @deprecated 1.6.2
   *
   * @since 1.5.6
   *
   * @param {object} dz Dropzone object.
   *
   * @returns {Function} Handler function.
   */
  function complete(dz) {
    return function () {
      dz.loading = dz.loading || 0;
      dz.loading--;
      dz.loading = Math.max(dz.loading - 1, 0);
      toggleSubmit(dz);
      updateInputValue(dz);
    };
  }

  /**
   * Add an error message to the current file.
   *
   * @since 1.6.2
   *
   * @param {object} file         File object.
   * @param {string} errorMessage Error message
   */
  function addErrorMessage(file, errorMessage) {
    if (file.isErrorNotUploadedDisplayed) {
      return;
    }
    var span = document.createElement('span');
    span.innerText = errorMessage.toString();
    span.setAttribute('data-dz-errormessage', '');
    file.previewElement.querySelector('.dz-error-message').appendChild(span);
  }

  /**
   * Confirm the upload to the server.
   *
   * The confirmation is needed in order to let PHP know
   * that all the chunks have been uploaded.
   *
   * @since 1.6.2
   *
   * @param {object} dz Dropzone object.
   *
   * @returns {Function} Handler function.
   */
  function confirmChunksFinishUpload(dz) {
    return function confirm(file) {
      if (!file.retries) {
        file.retries = 0;
      }
      if ('error' === file.status) {
        return;
      }

      /**
       * Retry finalize function.
       *
       * @since 1.6.2
       */
      function retry() {
        file.retries++;
        if (file.retries === 3) {
          addErrorMessage(file, window.wpforms_file_upload.errors.file_not_uploaded);
          return;
        }
        setTimeout(function () {
          confirm(file);
        }, 5000 * file.retries);
      }

      /**
       * Fail handler for ajax request.
       *
       * @since 1.6.2
       *
       * @param {object} response Response from the server
       */
      function fail(response) {
        var hasSpecificError = response.responseJSON && response.responseJSON.success === false && response.responseJSON.data;
        if (hasSpecificError) {
          addErrorMessage(file, response.responseJSON.data);
        } else {
          retry();
        }
      }

      /**
       * Handler for ajax request.
       *
       * @since 1.6.2
       *
       * @param {object} response Response from the server
       */
      function complete(response) {
        file.chunkResponse = JSON.stringify({
          data: response
        });
        dz.loading = dz.loading || 0;
        dz.loading--;
        dz.loading = Math.max(dz.loading, 0);
        toggleSubmit(dz);
        updateInputValue(dz);
      }
      wp.ajax.post(jQuery.extend({
        action: 'wpforms_file_chunks_uploaded',
        form_id: dz.dataTransfer.formId,
        field_id: dz.dataTransfer.fieldId,
        name: file.name
      }, dz.options.params.call(dz, null, null, {
        file: file,
        index: 0
      }))).then(complete).fail(fail);

      // Move to upload the next file, if any.
      dz.processQueue();
    };
  }

  /**
   * Toggle showing empty message.
   *
   * @since 1.5.6
   *
   * @param {object} dz Dropzone object.
   */
  function toggleMessage(dz) {
    setTimeout(function () {
      var validFiles = dz.files.filter(function (file) {
        return file.accepted;
      });
      if (validFiles.length >= dz.options.maxFiles) {
        dz.element.querySelector('.dz-message').classList.add('hide');
      } else {
        dz.element.querySelector('.dz-message').classList.remove('hide');
      }
    }, 0);
  }

  /**
   * Toggle error message if total size more than limit.
   * Runs for each file.
   *
   * @since 1.5.6
   *
   * @param {object} file Current file.
   * @param {object} dz   Dropzone object.
   */
  function validatePostMaxSizeError(file, dz) {
    setTimeout(function () {
      if (file.size >= dz.dataTransfer.postMaxSize) {
        var errorMessage = window.wpforms_file_upload.errors.post_max_size;
        if (!file.isErrorNotUploadedDisplayed) {
          file.isErrorNotUploadedDisplayed = true;
          errorMessage = window.wpforms_file_upload.errors.file_not_uploaded + ' ' + errorMessage;
          addErrorMessage(file, errorMessage);
        }
      }
    }, 1);
  }

  /**
   * Start File Upload.
   *
   * This would do the initial request to start a file upload. No chunk
   * is uploaded at this stage, instead all the information related to the
   * file are send to the server waiting for an authorization.
   *
   * If the server authorizes the client would start uploading the chunks.
   *
   * @since 1.6.2
   *
   * @param {object} dz   Dropzone object.
   * @param {object} file Current file.
   */
  function initFileUpload(dz, file) {
    wp.ajax.post(jQuery.extend({
      action: 'wpforms_upload_chunk_init',
      form_id: dz.dataTransfer.formId,
      field_id: dz.dataTransfer.fieldId,
      name: file.name,
      slow: isSlow
    }, dz.options.params.call(dz, null, null, {
      file: file,
      index: 0
    }))).then(function (response) {
      // File upload has been authorized.

      for (var key in response) {
        dz.options[key] = response[key];
      }
      if (response.dzchunksize) {
        dz.options.chunkSize = parseInt(response.dzchunksize, 10);
        file.upload.totalChunkCount = Math.ceil(file.size / dz.options.chunkSize);
      }
      dz.processQueue();
    }).fail(function (response) {
      file.status = 'error';
      if (!file.xhr) {
        var field = dz.element.closest('.wpforms-field');
        var hiddenInput = field.querySelector('.dropzone-input');
        var errorMessage = window.wpforms_file_upload.errors.file_not_uploaded + ' ' + window.wpforms_file_upload.errors.default_error;
        file.previewElement.classList.add('dz-processing', 'dz-error', 'dz-complete');
        hiddenInput.classList.add('wpforms-error');
        field.classList.add('wpforms-has-error');
        addErrorMessage(file, errorMessage);
      }
      dz.processQueue();
    });
  }

  /**
   * Validate the file when it was added in the dropzone.
   *
   * @since 1.5.6
   *
   * @param {object} dz Dropzone object.
   *
   * @returns {Function} Handler function.
   */
  function addedFile(dz) {
    return function (file) {
      if (file.size >= dz.dataTransfer.postMaxSize) {
        validatePostMaxSizeError(file, dz);
      } else {
        speedTest(function () {
          initFileUpload(dz, file);
        });
      }
      dz.loading = dz.loading || 0;
      dz.loading++;
      toggleSubmit(dz);
      toggleMessage(dz);
    };
  }

  /**
   * Send an AJAX request to remove file from the server.
   *
   * @since 1.5.6
   *
   * @param {string} file File name.
   * @param {object} dz Dropzone object.
   */
  function removeFromServer(file, dz) {
    wp.ajax.post({
      action: 'wpforms_remove_file',
      file: file,
      form_id: dz.dataTransfer.formId,
      field_id: dz.dataTransfer.fieldId
    });
  }

  /**
   * Init the file removal on server when user removed it on front-end.
   *
   * @since 1.5.6
   *
   * @param {object} dz Dropzone object.
   *
   * @returns {Function} Handler function.
   */
  function removedFile(dz) {
    return function (file) {
      toggleMessage(dz);
      var json = file.chunkResponse || (file.xhr || {}).responseText;
      if (json) {
        var object = parseJSON(json);
        if (object && object.data && object.data.file) {
          removeFromServer(object.data.file, dz);
        }
      }

      // Remove submitted value.
      if (Object.prototype.hasOwnProperty.call(file, 'isDefault') && file.isDefault) {
        submittedValues[dz.dataTransfer.formId][dz.dataTransfer.fieldId].splice(file.index, 1);
        dz.options.maxFiles++;
        removeFromServer(file.file, dz);
      }
      updateInputValue(dz);
      dz.loading = dz.loading || 0;
      dz.loading--;
      dz.loading = Math.max(dz.loading, 0);
      toggleSubmit(dz);
      var numErrors = dz.element.querySelectorAll('.dz-preview.dz-error').length;
      if (numErrors === 0) {
        dz.element.classList.remove('wpforms-error');
        dz.element.closest('.wpforms-field').classList.remove('wpforms-has-error');
      }
    };
  }

  /**
   * Process any error that was fired per each file.
   * There might be several errors per file, in that case - display "not uploaded" text only once.
   *
   * @since 1.5.6.1
   *
   * @param {object} dz Dropzone object.
   *
   * @returns {Function} Handler function.
   */
  function error(dz) {
    return function (file, errorMessage) {
      if (file.isErrorNotUploadedDisplayed) {
        return;
      }
      if (_typeof(errorMessage) === 'object') {
        errorMessage = Object.prototype.hasOwnProperty.call(errorMessage, 'data') && typeof errorMessage.data === 'string' ? errorMessage.data : '';
      }
      errorMessage = errorMessage !== '0' ? errorMessage : '';
      file.isErrorNotUploadedDisplayed = true;
      file.previewElement.querySelectorAll('[data-dz-errormessage]')[0].textContent = window.wpforms_file_upload.errors.file_not_uploaded + ' ' + errorMessage;
      dz.element.classList.add('wpforms-error');
      dz.element.closest('.wpforms-field').classList.add('wpforms-has-error');
    };
  }

  /**
   * Preset previously submitted files to the dropzone.
   *
   * @since 1.7.1
   *
   * @param {object} dz Dropzone object.
   */
  function presetSubmittedData(dz) {
    var files = parseJSON(getInput(dz).val());
    if (!files || !files.length) {
      return;
    }
    submittedValues[dz.dataTransfer.formId] = [];

    // We do deep cloning an object to be sure that data is passed without links.
    submittedValues[dz.dataTransfer.formId][dz.dataTransfer.fieldId] = JSON.parse(JSON.stringify(files));
    files.forEach(function (file, index) {
      file.isDefault = true;
      file.index = index;
      if (file.type.match(/image.*/)) {
        dz.displayExistingFile(file, file.url);
        return;
      }
      dz.emit('addedfile', file);
      dz.emit('complete', file);
    });
    dz.options.maxFiles = dz.options.maxFiles - files.length;
  }

  /**
   * Dropzone.js init for each field.
   *
   * @since 1.5.6
   *
   * @param {object} $el WPForms uploader DOM element.
   *
   * @returns {object} Dropzone object.
   */
  function dropZoneInit($el) {
    if ($el.dropzone) {
      return $el.dropzone;
    }
    var formId = parseInt($el.dataset.formId, 10);
    var fieldId = parseInt($el.dataset.fieldId, 10) || 0;
    var maxFiles = parseInt($el.dataset.maxFileNumber, 10);
    var acceptedFiles = $el.dataset.extensions.split(',').map(function (el) {
      return '.' + el;
    }).join(',');

    // Configure and modify Dropzone library.
    var dz = new window.Dropzone($el, {
      url: window.wpforms_file_upload.url,
      addRemoveLinks: true,
      chunking: true,
      forceChunking: true,
      retryChunks: true,
      chunkSize: parseInt($el.dataset.fileChunkSize, 10),
      paramName: $el.dataset.inputName,
      parallelChunkUploads: !!($el.dataset.parallelUploads || '').match(/^true$/i),
      parallelUploads: parseInt($el.dataset.maxParallelUploads, 10),
      autoProcessQueue: false,
      maxFilesize: (parseInt($el.dataset.maxSize, 10) / (1024 * 1024)).toFixed(2),
      maxFiles: maxFiles,
      acceptedFiles: acceptedFiles,
      dictMaxFilesExceeded: window.wpforms_file_upload.errors.file_limit.replace('{fileLimit}', maxFiles),
      dictInvalidFileType: window.wpforms_file_upload.errors.file_extension,
      dictFileTooBig: window.wpforms_file_upload.errors.file_size
    });

    // Custom variables.
    dz.dataTransfer = {
      postMaxSize: $el.dataset.maxSize,
      name: $el.dataset.inputName,
      formId: formId,
      fieldId: fieldId
    };
    presetSubmittedData(dz);

    // Process events.
    dz.on('sending', sending(dz, {
      action: 'wpforms_upload_chunk',
      form_id: formId,
      field_id: fieldId
    }));
    dz.on('addedfile', addedFile(dz));
    dz.on('removedfile', removedFile(dz));
    dz.on('complete', confirmChunksFinishUpload(dz));
    dz.on('error', error(dz));
    return dz;
  }

  /**
   * Hidden Dropzone input focus event handler.
   *
   * @since 1.8.1
   */
  function dropzoneInputFocus() {
    $(this).prev('.wpforms-uploader').addClass('wpforms-focus');
  }

  /**
   * Hidden Dropzone input blur event handler.
   *
   * @since 1.8.1
   */
  function dropzoneInputBlur() {
    $(this).prev('.wpforms-uploader').removeClass('wpforms-focus');
  }

  /**
   * Hidden Dropzone input blur event handler.
   *
   * @since 1.8.1
   *
   * @param {object} e Event object.
   */
  function dropzoneInputKeypress(e) {
    e.preventDefault();
    if (e.keyCode !== 13) {
      return;
    }
    $(this).prev('.wpforms-uploader').trigger('click');
  }

  /**
   * Hidden Dropzone input blur event handler.
   *
   * @since 1.8.1
   */
  function dropzoneClick() {
    $(this).next('.dropzone-input').trigger('focus');
  }

  /**
   * Classic File upload success callback to determine if all files are uploaded.
   *
   * @since 1.8.3
   *
   * @param {Event} e Event.
   * @param {jQuery} $form Form.
   */
  function combinedUploadsSizeOk(e, $form) {
    if (anyUploadsInProgress()) {
      disableSubmitButton($form);
    }
  }

  /**
   * Events.
   *
   * @since 1.8.1
   */
  function events() {
    $('.dropzone-input').on('focus', dropzoneInputFocus).on('blur', dropzoneInputBlur).on('keypress', dropzoneInputKeypress);
    $('.wpforms-uploader').on('click', dropzoneClick);
    $('form.wpforms-form').on('wpformsCombinedUploadsSizeOk', combinedUploadsSizeOk);
  }

  /**
   * DOMContentLoaded handler.
   *
   * @since 1.5.6
   */
  function ready() {
    window.wpforms = window.wpforms || {};
    window.wpforms.dropzones = [].slice.call(document.querySelectorAll('.wpforms-uploader')).map(dropZoneInit);
    events();
  }

  /**
   * Modern File Upload engine.
   *
   * @since 1.6.0
   */
  var wpformsModernFileUpload = {
    /**
     * Start the initialization.
     *
     * @since 1.6.0
     */
    init: function init() {
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ready);
      } else {
        ready();
      }
    }
  };

  // Call init and save in global variable.
  wpformsModernFileUpload.init();
  window.wpformsModernFileUpload = wpformsModernFileUpload;
})(jQuery);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJuYW1lcyI6WyJfdHlwZW9mIiwibyIsIlN5bWJvbCIsIml0ZXJhdG9yIiwiY29uc3RydWN0b3IiLCJwcm90b3R5cGUiLCIkIiwiaXNTbG93Iiwic3VibWl0dGVkVmFsdWVzIiwic3BlZWRUZXN0U2V0dGluZ3MiLCJtYXhUaW1lIiwicGF5bG9hZFNpemUiLCJnZXRQYXlsb2FkIiwiZGF0YSIsImkiLCJTdHJpbmciLCJmcm9tQ2hhckNvZGUiLCJNYXRoIiwicm91bmQiLCJyYW5kb20iLCJzcGVlZFRlc3QiLCJuZXh0Iiwic2V0VGltZW91dCIsInN0YXJ0IiwiRGF0ZSIsIndwIiwiYWpheCIsInBvc3QiLCJhY3Rpb24iLCJ0aGVuIiwiZGVsdGEiLCJmYWlsIiwidG9nZ2xlTG9hZGluZ01lc3NhZ2UiLCIkZm9ybSIsImZpbmQiLCJsZW5ndGgiLCJiZWZvcmUiLCJjb25jYXQiLCJ3aW5kb3ciLCJ3cGZvcm1zX2ZpbGVfdXBsb2FkIiwibG9hZGluZ19tZXNzYWdlIiwidXBsb2FkSW5Qcm9ncmVzcyIsImR6IiwibG9hZGluZyIsImdldEZpbGVzV2l0aFN0YXR1cyIsImFueVVwbG9hZHNJblByb2dyZXNzIiwid3Bmb3JtcyIsImRyb3B6b25lcyIsInNvbWUiLCJkaXNhYmxlU3VibWl0QnV0dG9uIiwiJGJ0biIsIiRidG5OZXh0IiwiaGFuZGxlciIsInByb3AiLCJXUEZvcm1zVXRpbHMiLCJ0cmlnZ2VyRXZlbnQiLCJhdHRyIiwicGFyZW50IiwiYWRkQ2xhc3MiLCJhcHBlbmQiLCJjc3MiLCJ3aWR0aCIsIm91dGVyV2lkdGgiLCJoZWlnaHQiLCJvdXRlckhlaWdodCIsIm9uIiwidG9nZ2xlU3VibWl0IiwialF1ZXJ5IiwiZWxlbWVudCIsImNsb3Nlc3QiLCJkaXNhYmxlZCIsImlzQnV0dG9uRGlzYWJsZWQiLCJCb29sZWFuIiwiaGFzQ2xhc3MiLCJvZmYiLCJyZW1vdmUiLCJyZW1vdmVDbGFzcyIsInBhcnNlSlNPTiIsInN0ciIsIkpTT04iLCJwYXJzZSIsImUiLCJvbmx5V2l0aExlbmd0aCIsImVsIiwib25seVBvc2l0aXZlIiwiZ2V0WEhSIiwiY2h1bmtSZXNwb25zZSIsInhociIsImdldFJlc3BvbnNlVGV4dCIsInJlc3BvbnNlVGV4dCIsImdldERhdGEiLCJnZXRWYWx1ZSIsImZpbGVzIiwibWFwIiwiZmlsdGVyIiwic2VuZGluZyIsImZpbGUiLCJmb3JtRGF0YSIsInNpemUiLCJkYXRhVHJhbnNmZXIiLCJwb3N0TWF4U2l6ZSIsInNlbmQiLCJhY2NlcHRlZCIsInByb2Nlc3NpbmciLCJzdGF0dXMiLCJwcmV2aWV3RWxlbWVudCIsImNsYXNzTGlzdCIsImFkZCIsIk9iamVjdCIsImtleXMiLCJmb3JFYWNoIiwia2V5IiwiY29udmVydEZpbGVzVG9WYWx1ZSIsImZvcm1JZCIsImZpZWxkSWQiLCJzdHJpbmdpZnkiLCJwdXNoIiwiYXBwbHkiLCJnZXRJbnB1dCIsInBhcmVudHMiLCJuYW1lIiwidXBkYXRlSW5wdXRWYWx1ZSIsIiRpbnB1dCIsInZhbCIsInRyaWdnZXIiLCJmbiIsInZhbGlkIiwiY29tcGxldGUiLCJtYXgiLCJhZGRFcnJvck1lc3NhZ2UiLCJlcnJvck1lc3NhZ2UiLCJpc0Vycm9yTm90VXBsb2FkZWREaXNwbGF5ZWQiLCJzcGFuIiwiZG9jdW1lbnQiLCJjcmVhdGVFbGVtZW50IiwiaW5uZXJUZXh0IiwidG9TdHJpbmciLCJzZXRBdHRyaWJ1dGUiLCJxdWVyeVNlbGVjdG9yIiwiYXBwZW5kQ2hpbGQiLCJjb25maXJtQ2h1bmtzRmluaXNoVXBsb2FkIiwiY29uZmlybSIsInJldHJpZXMiLCJyZXRyeSIsImVycm9ycyIsImZpbGVfbm90X3VwbG9hZGVkIiwicmVzcG9uc2UiLCJoYXNTcGVjaWZpY0Vycm9yIiwicmVzcG9uc2VKU09OIiwic3VjY2VzcyIsImV4dGVuZCIsImZvcm1faWQiLCJmaWVsZF9pZCIsIm9wdGlvbnMiLCJwYXJhbXMiLCJjYWxsIiwiaW5kZXgiLCJwcm9jZXNzUXVldWUiLCJ0b2dnbGVNZXNzYWdlIiwidmFsaWRGaWxlcyIsIm1heEZpbGVzIiwidmFsaWRhdGVQb3N0TWF4U2l6ZUVycm9yIiwicG9zdF9tYXhfc2l6ZSIsImluaXRGaWxlVXBsb2FkIiwic2xvdyIsImR6Y2h1bmtzaXplIiwiY2h1bmtTaXplIiwicGFyc2VJbnQiLCJ1cGxvYWQiLCJ0b3RhbENodW5rQ291bnQiLCJjZWlsIiwiZmllbGQiLCJoaWRkZW5JbnB1dCIsImRlZmF1bHRfZXJyb3IiLCJhZGRlZEZpbGUiLCJyZW1vdmVGcm9tU2VydmVyIiwicmVtb3ZlZEZpbGUiLCJqc29uIiwib2JqZWN0IiwiaGFzT3duUHJvcGVydHkiLCJpc0RlZmF1bHQiLCJzcGxpY2UiLCJudW1FcnJvcnMiLCJxdWVyeVNlbGVjdG9yQWxsIiwiZXJyb3IiLCJ0ZXh0Q29udGVudCIsInByZXNldFN1Ym1pdHRlZERhdGEiLCJ0eXBlIiwibWF0Y2giLCJkaXNwbGF5RXhpc3RpbmdGaWxlIiwidXJsIiwiZW1pdCIsImRyb3Bab25lSW5pdCIsIiRlbCIsImRyb3B6b25lIiwiZGF0YXNldCIsIm1heEZpbGVOdW1iZXIiLCJhY2NlcHRlZEZpbGVzIiwiZXh0ZW5zaW9ucyIsInNwbGl0Iiwiam9pbiIsIkRyb3B6b25lIiwiYWRkUmVtb3ZlTGlua3MiLCJjaHVua2luZyIsImZvcmNlQ2h1bmtpbmciLCJyZXRyeUNodW5rcyIsImZpbGVDaHVua1NpemUiLCJwYXJhbU5hbWUiLCJpbnB1dE5hbWUiLCJwYXJhbGxlbENodW5rVXBsb2FkcyIsInBhcmFsbGVsVXBsb2FkcyIsIm1heFBhcmFsbGVsVXBsb2FkcyIsImF1dG9Qcm9jZXNzUXVldWUiLCJtYXhGaWxlc2l6ZSIsIm1heFNpemUiLCJ0b0ZpeGVkIiwiZGljdE1heEZpbGVzRXhjZWVkZWQiLCJmaWxlX2xpbWl0IiwicmVwbGFjZSIsImRpY3RJbnZhbGlkRmlsZVR5cGUiLCJmaWxlX2V4dGVuc2lvbiIsImRpY3RGaWxlVG9vQmlnIiwiZmlsZV9zaXplIiwiZHJvcHpvbmVJbnB1dEZvY3VzIiwicHJldiIsImRyb3B6b25lSW5wdXRCbHVyIiwiZHJvcHpvbmVJbnB1dEtleXByZXNzIiwicHJldmVudERlZmF1bHQiLCJrZXlDb2RlIiwiZHJvcHpvbmVDbGljayIsImNvbWJpbmVkVXBsb2Fkc1NpemVPayIsImV2ZW50cyIsInJlYWR5Iiwic2xpY2UiLCJ3cGZvcm1zTW9kZXJuRmlsZVVwbG9hZCIsImluaXQiLCJyZWFkeVN0YXRlIiwiYWRkRXZlbnRMaXN0ZW5lciJdLCJzb3VyY2VzIjpbImZha2VfYzc2MWRlNDUuanMiXSwic291cmNlc0NvbnRlbnQiOlsiLyogZ2xvYmFsIFdQRm9ybXNVdGlscyAqL1xuJ3VzZSBzdHJpY3QnO1xuXG4oIGZ1bmN0aW9uKCAkICkge1xuXG5cdC8qKlxuXHQgKiBBbGwgY29ubmVjdGlvbnMgYXJlIHNsb3cgYnkgZGVmYXVsdC5cblx0ICpcblx0ICogQHNpbmNlIDEuNi4yXG5cdCAqXG5cdCAqIEB0eXBlIHtib29sZWFufG51bGx9XG5cdCAqL1xuXHR2YXIgaXNTbG93ID0gbnVsbDtcblxuXHQvKipcblx0ICogUHJldmlvdXNseSBzdWJtaXR0ZWQgZGF0YS5cblx0ICpcblx0ICogQHNpbmNlIDEuNy4xXG5cdCAqXG5cdCAqIEB0eXBlIHtBcnJheX1cblx0ICovXG5cdHZhciBzdWJtaXR0ZWRWYWx1ZXMgPSBbXTtcblxuXHQvKipcblx0ICogRGVmYXVsdCBzZXR0aW5ncyBmb3Igb3VyIHNwZWVkIHRlc3QuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjYuMlxuXHQgKlxuXHQgKiBAdHlwZSB7e21heFRpbWU6IG51bWJlciwgcGF5bG9hZFNpemU6IG51bWJlcn19XG5cdCAqL1xuXHR2YXIgc3BlZWRUZXN0U2V0dGluZ3MgPSB7XG5cdFx0bWF4VGltZTogMzAwMCwgLy8gTWF4IHRpbWUgKG1zKSBpdCBzaG91bGQgdGFrZSB0byBiZSBjb25zaWRlcmVkIGEgJ2Zhc3QgY29ubmVjdGlvbicuXG5cdFx0cGF5bG9hZFNpemU6IDEwMCAqIDEwMjQsIC8vIFBheWxvYWQgc2l6ZS5cblx0fTtcblxuXHQvKipcblx0ICogQ3JlYXRlIGEgcmFuZG9tIHBheWxvYWQgZm9yIHRoZSBzcGVlZCB0ZXN0LlxuXHQgKlxuXHQgKiBAc2luY2UgMS42LjJcblx0ICpcblx0ICogQHJldHVybnMge3N0cmluZ30gUmFuZG9tIHBheWxvYWQuXG5cdCAqL1xuXHRmdW5jdGlvbiBnZXRQYXlsb2FkKCkge1xuXG5cdFx0dmFyIGRhdGEgPSAnJztcblxuXHRcdGZvciAoIHZhciBpID0gMDsgaSA8IHNwZWVkVGVzdFNldHRpbmdzLnBheWxvYWRTaXplOyArK2kgKSB7XG5cdFx0XHRkYXRhICs9IFN0cmluZy5mcm9tQ2hhckNvZGUoIE1hdGgucm91bmQoIE1hdGgucmFuZG9tKCkgKiAzNiArIDY0ICkgKTtcblx0XHR9XG5cblx0XHRyZXR1cm4gZGF0YTtcblx0fVxuXG5cdC8qKlxuXHQgKiBSdW4gc3BlZWQgdGVzdHMgYW5kIGZsYWcgdGhlIGNsaWVudHMgYXMgc2xvdyBvciBub3QuIElmIGEgY29ubmVjdGlvblxuXHQgKiBpcyBzbG93IGl0IHdvdWxkIGxldCB0aGUgYmFja2VuZCBrbm93IGFuZCB0aGUgYmFja2VuZCBtb3N0IGxpa2VseVxuXHQgKiB3b3VsZCBkaXNhYmxlIHBhcmFsbGVsIHVwbG9hZHMgYW5kIHdvdWxkIHNldCBzbWFsbGVyIGNodW5rIHNpemVzLlxuXHQgKlxuXHQgKiBAc2luY2UgMS42LjJcblx0ICpcblx0ICogQHBhcmFtIHtGdW5jdGlvbn0gbmV4dCBGdW5jdGlvbiB0byBjYWxsIHdoZW4gdGhlIHNwZWVkIGRldGVjdGlvbiBpcyBkb25lLlxuXHQgKi9cblx0ZnVuY3Rpb24gc3BlZWRUZXN0KCBuZXh0ICkge1xuXG5cdFx0aWYgKCBudWxsICE9PSBpc1Nsb3cgKSB7XG5cdFx0XHRzZXRUaW1lb3V0KCBuZXh0ICk7XG5cdFx0XHRyZXR1cm47XG5cdFx0fVxuXG5cdFx0dmFyIGRhdGEgID0gZ2V0UGF5bG9hZCgpO1xuXHRcdHZhciBzdGFydCA9IG5ldyBEYXRlO1xuXG5cdFx0d3AuYWpheC5wb3N0KCB7XG5cdFx0XHRhY3Rpb246ICd3cGZvcm1zX2ZpbGVfdXBsb2FkX3NwZWVkX3Rlc3QnLFxuXHRcdFx0ZGF0YTogZGF0YSxcblx0XHR9ICkudGhlbiggZnVuY3Rpb24oKSB7XG5cblx0XHRcdHZhciBkZWx0YSA9IG5ldyBEYXRlIC0gc3RhcnQ7XG5cblx0XHRcdGlzU2xvdyA9IGRlbHRhID49IHNwZWVkVGVzdFNldHRpbmdzLm1heFRpbWU7XG5cblx0XHRcdG5leHQoKTtcblx0XHR9ICkuZmFpbCggZnVuY3Rpb24oKSB7XG5cblx0XHRcdGlzU2xvdyA9IHRydWU7XG5cblx0XHRcdG5leHQoKTtcblx0XHR9ICk7XG5cdH1cblxuXHQvKipcblx0ICogVG9nZ2xlIGxvYWRpbmcgbWVzc2FnZSBhYm92ZSBzdWJtaXQgYnV0dG9uLlxuXHQgKlxuXHQgKiBAc2luY2UgMS41LjZcblx0ICpcblx0ICogQHBhcmFtIHtvYmplY3R9ICRmb3JtIGpRdWVyeSBmb3JtIGVsZW1lbnQuXG5cdCAqXG5cdCAqIEByZXR1cm5zIHtGdW5jdGlvbn0gZXZlbnQgaGFuZGxlciBmdW5jdGlvbi5cblx0ICovXG5cdGZ1bmN0aW9uIHRvZ2dsZUxvYWRpbmdNZXNzYWdlKCAkZm9ybSApIHtcblxuXHRcdHJldHVybiBmdW5jdGlvbigpIHtcblxuXHRcdFx0aWYgKCAkZm9ybS5maW5kKCAnLndwZm9ybXMtdXBsb2FkaW5nLWluLXByb2dyZXNzLWFsZXJ0JyApLmxlbmd0aCApIHtcblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXG5cdFx0XHQkZm9ybS5maW5kKCAnLndwZm9ybXMtc3VibWl0LWNvbnRhaW5lcicgKVxuXHRcdFx0XHQuYmVmb3JlKFxuXHRcdFx0XHRcdGA8ZGl2IGNsYXNzPVwid3Bmb3Jtcy1lcnJvci1hbGVydCB3cGZvcm1zLXVwbG9hZGluZy1pbi1wcm9ncmVzcy1hbGVydFwiPlxuXHRcdFx0XHRcdFx0JHt3aW5kb3cud3Bmb3Jtc19maWxlX3VwbG9hZC5sb2FkaW5nX21lc3NhZ2V9XG5cdFx0XHRcdFx0PC9kaXY+YFxuXHRcdFx0XHQpO1xuXHRcdH07XG5cdH1cblxuXHQvKipcblx0ICogSXMgYSBmaWVsZCBsb2FkaW5nP1xuXHQgKlxuXHQgKiBAc2luY2UgMS43LjZcblx0ICpcblx0ICogQHBhcmFtIHtvYmplY3R9IGR6IERyb3B6b25lIG9iamVjdC5cblx0ICpcblx0ICogQHJldHVybnMge2Jvb2xlYW59IHRydWUgaWYgdGhlIGZpZWxkIGlzIGxvYWRpbmcuXG5cdCAqL1xuXHRmdW5jdGlvbiB1cGxvYWRJblByb2dyZXNzKCBkeiApIHtcblxuXHRcdHJldHVybiBkei5sb2FkaW5nID4gMCB8fCBkei5nZXRGaWxlc1dpdGhTdGF0dXMoICdlcnJvcicgKS5sZW5ndGggPiAwO1xuXHR9XG5cblx0LyoqXG5cdCAqIElzIGF0IGxlYXN0IG9uZSBmaWVsZCBsb2FkaW5nP1xuXHQgKlxuXHQgKiBAc2luY2UgMS43LjZcblx0ICpcblx0ICogQHJldHVybnMge2Jvb2xlYW59IHRydWUgaWYgYXQgbGVhc3Qgb25lIGZpZWxkIGlzIGxvYWRpbmcuXG5cdCAqL1xuXHRmdW5jdGlvbiBhbnlVcGxvYWRzSW5Qcm9ncmVzcygpIHtcblxuXHRcdHZhciBhbnlVcGxvYWRzSW5Qcm9ncmVzcyA9IGZhbHNlO1xuXG5cdFx0d2luZG93LndwZm9ybXMuZHJvcHpvbmVzLnNvbWUoIGZ1bmN0aW9uKCBkeiApIHtcblxuXHRcdFx0aWYgKCB1cGxvYWRJblByb2dyZXNzKCBkeiApICkge1xuXHRcdFx0XHRhbnlVcGxvYWRzSW5Qcm9ncmVzcyA9IHRydWU7XG5cblx0XHRcdFx0cmV0dXJuIHRydWU7XG5cdFx0XHR9XG5cdFx0fSApO1xuXG5cdFx0cmV0dXJuIGFueVVwbG9hZHNJblByb2dyZXNzO1xuXHR9XG5cblx0LyoqXG5cdCAqIERpc2FibGUgc3VibWl0IGJ1dHRvbiBhbmQgYWRkIG92ZXJsYXkuXG5cdCAqXG5cdCAqIEBwYXJhbSB7b2JqZWN0fSAkZm9ybSBqUXVlcnkgZm9ybSBlbGVtZW50LlxuXHQgKi9cblx0ZnVuY3Rpb24gZGlzYWJsZVN1Ym1pdEJ1dHRvbiggJGZvcm0gKSB7XG5cblx0XHQvLyBGaW5kIHRoZSBwcmltYXJ5IHN1Ym1pdCBidXR0b24gYW5kIHRoZSBcIk5leHRcIiBidXR0b24gZm9yIG11bHRpLXBhZ2UgZm9ybXMuXG5cdFx0bGV0ICRidG4gPSAkZm9ybS5maW5kKCAnLndwZm9ybXMtc3VibWl0JyApO1xuXHRcdGNvbnN0ICRidG5OZXh0ID0gJGZvcm0uZmluZCggJy53cGZvcm1zLXBhZ2UtbmV4dDp2aXNpYmxlJyApO1xuXHRcdGNvbnN0IGhhbmRsZXIgPSB0b2dnbGVMb2FkaW5nTWVzc2FnZSggJGZvcm0gKTsgLy8gR2V0IHRoZSBoYW5kbGVyIGZ1bmN0aW9uIGZvciBsb2FkaW5nIG1lc3NhZ2UgdG9nZ2xlLlxuXG5cdFx0Ly8gRm9yIG11bHRpLXBhZ2VzIGxheW91dCwgdXNlIHRoZSBcIk5leHRcIiBidXR0b24gaW5zdGVhZCBvZiB0aGUgcHJpbWFyeSBzdWJtaXQgYnV0dG9uLlxuXHRcdGlmICggJGZvcm0uZmluZCggJy53cGZvcm1zLXBhZ2UtaW5kaWNhdG9yJyApLmxlbmd0aCAhPT0gMCAmJiAkYnRuTmV4dC5sZW5ndGggIT09IDAgKSB7XG5cdFx0XHQkYnRuID0gJGJ0bk5leHQ7XG5cdFx0fVxuXG5cdFx0Ly8gRGlzYWJsZSB0aGUgc3VibWl0IGJ1dHRvbi5cblx0XHQkYnRuLnByb3AoICdkaXNhYmxlZCcsIHRydWUgKTtcblx0XHRXUEZvcm1zVXRpbHMudHJpZ2dlckV2ZW50KCAkZm9ybSwgJ3dwZm9ybXNGb3JtU3VibWl0QnV0dG9uRGlzYWJsZScsIFsgJGZvcm0sICRidG4gXSApO1xuXG5cdFx0Ly8gSWYgdGhlIG92ZXJsYXkgaXMgbm90IGFscmVhZHkgYWRkZWQgYW5kIHRoZSBidXR0b24gaXMgb2YgdHlwZSBcInN1Ym1pdFwiLCBhZGQgYW4gb3ZlcmxheS5cblx0XHRpZiAoICEgJGZvcm0uZmluZCggJy53cGZvcm1zLXN1Ym1pdC1vdmVybGF5JyApLmxlbmd0aCAmJiAkYnRuLmF0dHIoICd0eXBlJyApID09PSAnc3VibWl0JyApIHtcblxuXHRcdFx0Ly8gQWRkIGEgY29udGFpbmVyIGZvciB0aGUgb3ZlcmxheSBhbmQgYXBwZW5kIHRoZSBvdmVybGF5IGVsZW1lbnQgdG8gaXQuXG5cdFx0XHQkYnRuLnBhcmVudCgpLmFkZENsYXNzKCAnd3Bmb3Jtcy1zdWJtaXQtb3ZlcmxheS1jb250YWluZXInICk7XG5cdFx0XHQkYnRuLnBhcmVudCgpLmFwcGVuZCggJzxkaXYgY2xhc3M9XCJ3cGZvcm1zLXN1Ym1pdC1vdmVybGF5XCI+PC9kaXY+JyApO1xuXG5cdFx0XHQvLyBTZXQgdGhlIG92ZXJsYXkgZGltZW5zaW9ucyB0byBtYXRjaCB0aGUgc3VibWl0IGJ1dHRvbidzIHNpemUuXG5cdFx0XHQkZm9ybS5maW5kKCAnLndwZm9ybXMtc3VibWl0LW92ZXJsYXknICkuY3NzKCB7XG5cdFx0XHRcdHdpZHRoOiBgJHskYnRuLm91dGVyV2lkdGgoKX1weGAsXG5cdFx0XHRcdGhlaWdodDogYCR7JGJ0bi5wYXJlbnQoKS5vdXRlckhlaWdodCgpfXB4YCxcblx0XHRcdH0gKTtcblxuXHRcdFx0Ly8gQXR0YWNoIHRoZSBjbGljayBldmVudCB0byB0aGUgb3ZlcmxheSBzbyB0aGF0IGl0IHRyaWdnZXJzIHRoZSBoYW5kbGVyIGZ1bmN0aW9uLlxuXHRcdFx0JGZvcm0uZmluZCggJy53cGZvcm1zLXN1Ym1pdC1vdmVybGF5JyApLm9uKCAnY2xpY2snLCBoYW5kbGVyICk7XG5cdFx0fVxuXHR9XG5cblx0LyoqXG5cdCAqIERpc2FibGUgc3VibWl0IGJ1dHRvbiB3aGVuIHdlIGFyZSBzZW5kaW5nIGZpbGVzIHRvIHRoZSBzZXJ2ZXIuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjUuNlxuXHQgKlxuXHQgKiBAcGFyYW0ge29iamVjdH0gZHogRHJvcHpvbmUgb2JqZWN0LlxuXHQgKi9cblx0ZnVuY3Rpb24gdG9nZ2xlU3VibWl0KCBkeiApIHsgLy8gZXNsaW50LWRpc2FibGUtbGluZSBjb21wbGV4aXR5XG5cblx0XHR2YXIgJGZvcm0gPSBqUXVlcnkoIGR6LmVsZW1lbnQgKS5jbG9zZXN0KCAnZm9ybScgKSxcblx0XHRcdCRidG4gPSAkZm9ybS5maW5kKCAnLndwZm9ybXMtc3VibWl0JyApLFxuXHRcdFx0JGJ0bk5leHQgPSAkZm9ybS5maW5kKCAnLndwZm9ybXMtcGFnZS1uZXh0OnZpc2libGUnICksXG5cdFx0XHRoYW5kbGVyID0gdG9nZ2xlTG9hZGluZ01lc3NhZ2UoICRmb3JtICksXG5cdFx0XHRkaXNhYmxlZCA9IHVwbG9hZEluUHJvZ3Jlc3MoIGR6ICk7XG5cblx0XHQvLyBGb3IgbXVsdGktcGFnZXMgbGF5b3V0LlxuXHRcdGlmICggJGZvcm0uZmluZCggJy53cGZvcm1zLXBhZ2UtaW5kaWNhdG9yJyApLmxlbmd0aCAhPT0gMCAmJiAkYnRuTmV4dC5sZW5ndGggIT09IDAgKSB7XG5cdFx0XHQkYnRuID0gJGJ0bk5leHQ7XG5cdFx0fVxuXG5cdFx0Y29uc3QgaXNCdXR0b25EaXNhYmxlZCA9IEJvb2xlYW4oICRidG4ucHJvcCggJ2Rpc2FibGVkJyApICkgfHwgJGJ0bi5oYXNDbGFzcyggJ3dwZm9ybXMtZGlzYWJsZWQnICk7XG5cblx0XHRpZiAoIGRpc2FibGVkID09PSBpc0J1dHRvbkRpc2FibGVkICkge1xuXHRcdFx0cmV0dXJuO1xuXHRcdH1cblxuXHRcdGlmICggZGlzYWJsZWQgKSB7XG5cdFx0XHRkaXNhYmxlU3VibWl0QnV0dG9uKCAkZm9ybSApO1xuXHRcdFx0cmV0dXJuO1xuXHRcdH1cblxuXHRcdGlmICggYW55VXBsb2Fkc0luUHJvZ3Jlc3MoKSApIHtcblx0XHRcdHJldHVybjtcblx0XHR9XG5cblx0XHQkYnRuLnByb3AoICdkaXNhYmxlZCcsIGZhbHNlICk7XG5cdFx0V1BGb3Jtc1V0aWxzLnRyaWdnZXJFdmVudCggJGZvcm0sICd3cGZvcm1zRm9ybVN1Ym1pdEJ1dHRvblJlc3RvcmUnLCBbICRmb3JtLCAkYnRuIF0gKTtcblx0XHQkZm9ybS5maW5kKCAnLndwZm9ybXMtc3VibWl0LW92ZXJsYXknICkub2ZmKCAnY2xpY2snLCBoYW5kbGVyICk7XG5cdFx0JGZvcm0uZmluZCggJy53cGZvcm1zLXN1Ym1pdC1vdmVybGF5JyApLnJlbW92ZSgpO1xuXHRcdCRidG4ucGFyZW50KCkucmVtb3ZlQ2xhc3MoICd3cGZvcm1zLXN1Ym1pdC1vdmVybGF5LWNvbnRhaW5lcicgKTtcblx0XHRpZiAoICRmb3JtLmZpbmQoICcud3Bmb3Jtcy11cGxvYWRpbmctaW4tcHJvZ3Jlc3MtYWxlcnQnICkubGVuZ3RoICkge1xuXHRcdFx0JGZvcm0uZmluZCggJy53cGZvcm1zLXVwbG9hZGluZy1pbi1wcm9ncmVzcy1hbGVydCcgKS5yZW1vdmUoKTtcblx0XHR9XG5cdH1cblxuXHQvKipcblx0ICogVHJ5IHRvIHBhcnNlIEpTT04gb3IgcmV0dXJuIGZhbHNlLlxuXHQgKlxuXHQgKiBAc2luY2UgMS41LjZcblx0ICpcblx0ICogQHBhcmFtIHtzdHJpbmd9IHN0ciBKU09OIHN0cmluZyBjYW5kaWRhdGUuXG5cdCAqXG5cdCAqIEByZXR1cm5zIHsqfSBQYXJzZSBvYmplY3Qgb3IgZmFsc2UuXG5cdCAqL1xuXHRmdW5jdGlvbiBwYXJzZUpTT04oIHN0ciApIHtcblx0XHR0cnkge1xuXHRcdFx0cmV0dXJuIEpTT04ucGFyc2UoIHN0ciApO1xuXHRcdH0gY2F0Y2ggKCBlICkge1xuXHRcdFx0cmV0dXJuIGZhbHNlO1xuXHRcdH1cblx0fVxuXG5cdC8qKlxuXHQgKiBMZWF2ZSBvbmx5IG9iamVjdHMgd2l0aCBsZW5ndGguXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjUuNlxuXHQgKlxuXHQgKiBAcGFyYW0ge29iamVjdH0gZWwgQW55IGFycmF5LlxuXHQgKlxuXHQgKiBAcmV0dXJucyB7Ym9vbH0gSGFzIGxlbmd0aCBtb3JlIHRoYW4gMCBvciBuby5cblx0ICovXG5cdGZ1bmN0aW9uIG9ubHlXaXRoTGVuZ3RoKCBlbCApIHtcblx0XHRyZXR1cm4gZWwubGVuZ3RoID4gMDtcblx0fVxuXG5cdC8qKlxuXHQgKiBMZWF2ZSBvbmx5IHBvc2l0aXZlIGVsZW1lbnRzLlxuXHQgKlxuXHQgKiBAc2luY2UgMS41LjZcblx0ICpcblx0ICogQHBhcmFtIHsqfSBlbCBBbnkgZWxlbWVudC5cblx0ICpcblx0ICogQHJldHVybnMgeyp9IEZpbHRlciBvbmx5IHBvc2l0aXZlLlxuXHQgKi9cblx0ZnVuY3Rpb24gb25seVBvc2l0aXZlKCBlbCApIHtcblx0XHRyZXR1cm4gZWw7XG5cdH1cblxuXHQvKipcblx0ICogR2V0IHhoci5cblx0ICpcblx0ICogQHNpbmNlIDEuNS42XG5cdCAqXG5cdCAqIEBwYXJhbSB7b2JqZWN0fSBlbCBPYmplY3Qgd2l0aCB4aHIgcHJvcGVydHkuXG5cdCAqXG5cdCAqIEByZXR1cm5zIHsqfSBHZXQgWEhSLlxuXHQgKi9cblx0ZnVuY3Rpb24gZ2V0WEhSKCBlbCApIHtcblx0XHRyZXR1cm4gZWwuY2h1bmtSZXNwb25zZSB8fCBlbC54aHI7XG5cdH1cblxuXHQvKipcblx0ICogR2V0IHJlc3BvbnNlIHRleHQuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjUuNlxuXHQgKlxuXHQgKiBAcGFyYW0ge29iamVjdH0gZWwgWGhyIG9iamVjdC5cblx0ICpcblx0ICogQHJldHVybnMge29iamVjdH0gUmVzcG9uc2UgdGV4dC5cblx0ICovXG5cdGZ1bmN0aW9uIGdldFJlc3BvbnNlVGV4dCggZWwgKSB7XG5cdFx0cmV0dXJuIHR5cGVvZiBlbCA9PT0gJ3N0cmluZycgPyBlbCA6IGVsLnJlc3BvbnNlVGV4dDtcblx0fVxuXG5cdC8qKlxuXHQgKiBHZXQgZGF0YS5cblx0ICpcblx0ICogQHNpbmNlIDEuNS42XG5cdCAqXG5cdCAqIEBwYXJhbSB7b2JqZWN0fSBlbCBPYmplY3Qgd2l0aCBkYXRhIHByb3BlcnR5LlxuXHQgKlxuXHQgKiBAcmV0dXJucyB7b2JqZWN0fSBEYXRhLlxuXHQgKi9cblx0ZnVuY3Rpb24gZ2V0RGF0YSggZWwgKSB7XG5cdFx0cmV0dXJuIGVsLmRhdGE7XG5cdH1cblxuXHQvKipcblx0ICogR2V0IHZhbHVlIGZyb20gZmlsZXMuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjUuNlxuXHQgKlxuXHQgKiBAcGFyYW0ge29iamVjdH0gZmlsZXMgRHJvcHpvbmUgZmlsZXMuXG5cdCAqXG5cdCAqIEByZXR1cm5zIHtvYmplY3R9IFByZXBhcmVkIHZhbHVlLlxuXHQgKi9cblx0ZnVuY3Rpb24gZ2V0VmFsdWUoIGZpbGVzICkge1xuXHRcdHJldHVybiBmaWxlc1xuXHRcdFx0Lm1hcCggZ2V0WEhSIClcblx0XHRcdC5maWx0ZXIoIG9ubHlQb3NpdGl2ZSApXG5cdFx0XHQubWFwKCBnZXRSZXNwb25zZVRleHQgKVxuXHRcdFx0LmZpbHRlciggb25seVdpdGhMZW5ndGggKVxuXHRcdFx0Lm1hcCggcGFyc2VKU09OIClcblx0XHRcdC5maWx0ZXIoIG9ubHlQb3NpdGl2ZSApXG5cdFx0XHQubWFwKCBnZXREYXRhICk7XG5cdH1cblxuXHQvKipcblx0ICogU2VuZGluZyBldmVudCBoaWdoZXIgb3JkZXIgZnVuY3Rpb24uXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjUuNlxuXHQgKiBAc2luY2UgMS41LjYuMSBBZGRlZCBzcGVjaWFsIHByb2Nlc3Npbmcgb2YgYSBmaWxlIHRoYXQgaXMgbGFyZ2VyIHRoYW4gc2VydmVyJ3MgcG9zdF9tYXhfc2l6ZS5cblx0ICpcblx0ICogQHBhcmFtIHtvYmplY3R9IGR6IERyb3B6b25lIG9iamVjdC5cblx0ICogQHBhcmFtIHtvYmplY3R9IGRhdGEgQWRkaW5nIGRhdGEgdG8gcmVxdWVzdC5cblx0ICpcblx0ICogQHJldHVybnMge0Z1bmN0aW9ufSBIYW5kbGVyIGZ1bmN0aW9uLlxuXHQgKi9cblx0ZnVuY3Rpb24gc2VuZGluZyggZHosIGRhdGEgKSB7XG5cblx0XHRyZXR1cm4gZnVuY3Rpb24oIGZpbGUsIHhociwgZm9ybURhdGEgKSB7XG5cblx0XHRcdC8qXG5cdFx0XHQgKiBXZSBzaG91bGQgbm90IGFsbG93IHNlbmRpbmcgYSBmaWxlLCB0aGF0IGV4Y2VlZHMgc2VydmVyIHBvc3RfbWF4X3NpemUuXG5cdFx0XHQgKiBXaXRoIHRoaXMgXCJoYWNrXCIgd2UgcmVkZWZpbmUgdGhlIGRlZmF1bHQgc2VuZCBmdW5jdGlvbmFsaXR5XG5cdFx0XHQgKiB0byBwcmV2ZW50IG9ubHkgdGhpcyBvYmplY3QgZnJvbSBzZW5kaW5nIGEgcmVxdWVzdCBhdCBhbGwuXG5cdFx0XHQgKiBUaGUgZmlsZSB0aGF0IGdlbmVyYXRlZCB0aGF0IGVycm9yIHNob3VsZCBiZSBtYXJrZWQgYXMgcmVqZWN0ZWQsXG5cdFx0XHQgKiBzbyBEcm9wem9uZSB3aWxsIHNpbGVudGx5IGlnbm9yZSBpdC5cblx0XHRcdCAqXG5cdFx0XHQgKiBJZiBDaHVua3MgYXJlIGVuYWJsZWQgdGhlIGZpbGUgc2l6ZSB3aWxsIG5ldmVyIGV4Y2VlZCAoYnkgYSBQSFAgY29uc3RyYWludCkgdGhlXG5cdFx0XHQgKiBwb3N0TWF4U2l6ZS4gVGhpcyBibG9jayBzaG91bGRuJ3QgYmUgcmVtb3ZlZCBub25ldGhlbGVzcyB1bnRpbCB0aGUgXCJtb2Rlcm5cIiB1cGxvYWQgaXMgY29tcGxldGVseVxuXHRcdFx0ICogZGVwcmVjYXRlZCBhbmQgcmVtb3ZlZC5cblx0XHRcdCAqL1xuXHRcdFx0aWYgKCBmaWxlLnNpemUgPiB0aGlzLmRhdGFUcmFuc2Zlci5wb3N0TWF4U2l6ZSApIHtcblx0XHRcdFx0eGhyLnNlbmQgPSBmdW5jdGlvbigpIHt9O1xuXG5cdFx0XHRcdGZpbGUuYWNjZXB0ZWQgPSBmYWxzZTtcblx0XHRcdFx0ZmlsZS5wcm9jZXNzaW5nID0gZmFsc2U7XG5cdFx0XHRcdGZpbGUuc3RhdHVzID0gJ3JlamVjdGVkJztcblx0XHRcdFx0ZmlsZS5wcmV2aWV3RWxlbWVudC5jbGFzc0xpc3QuYWRkKCAnZHotZXJyb3InICk7XG5cdFx0XHRcdGZpbGUucHJldmlld0VsZW1lbnQuY2xhc3NMaXN0LmFkZCggJ2R6LWNvbXBsZXRlJyApO1xuXG5cdFx0XHRcdHJldHVybjtcblx0XHRcdH1cblxuXHRcdFx0T2JqZWN0LmtleXMoIGRhdGEgKS5mb3JFYWNoKCBmdW5jdGlvbigga2V5ICkge1xuXHRcdFx0XHRmb3JtRGF0YS5hcHBlbmQoIGtleSwgZGF0YVtrZXldICk7XG5cdFx0XHR9ICk7XG5cdFx0fTtcblx0fVxuXG5cdC8qKlxuXHQgKiBDb252ZXJ0IGZpbGVzIHRvIGlucHV0IHZhbHVlLlxuXHQgKlxuXHQgKiBAc2luY2UgMS41LjZcblx0ICogQHNpbmNlIDEuNy4xIEFkZGVkIHRoZSBkeiBhcmd1bWVudC5cblx0ICpcblx0ICogQHBhcmFtIHtvYmplY3R9IGZpbGVzIEZpbGVzIGxpc3QuXG5cdCAqIEBwYXJhbSB7b2JqZWN0fSBkeiBEcm9wem9uZSBvYmplY3QuXG5cdCAqXG5cdCAqIEByZXR1cm5zIHtzdHJpbmd9IENvbnZlcnRlZCB2YWx1ZS5cblx0ICovXG5cdGZ1bmN0aW9uIGNvbnZlcnRGaWxlc1RvVmFsdWUoIGZpbGVzLCBkeiApIHtcblxuXHRcdGlmICggISBzdWJtaXR0ZWRWYWx1ZXNbIGR6LmRhdGFUcmFuc2Zlci5mb3JtSWQgXSB8fCAhIHN1Ym1pdHRlZFZhbHVlc1sgZHouZGF0YVRyYW5zZmVyLmZvcm1JZCBdWyBkei5kYXRhVHJhbnNmZXIuZmllbGRJZCBdICkge1xuXHRcdFx0cmV0dXJuIGZpbGVzLmxlbmd0aCA/IEpTT04uc3RyaW5naWZ5KCBmaWxlcyApIDogJyc7XG5cdFx0fVxuXG5cdFx0ZmlsZXMucHVzaC5hcHBseSggZmlsZXMsIHN1Ym1pdHRlZFZhbHVlc1sgZHouZGF0YVRyYW5zZmVyLmZvcm1JZCBdWyBkei5kYXRhVHJhbnNmZXIuZmllbGRJZCBdICk7XG5cblx0XHRyZXR1cm4gSlNPTi5zdHJpbmdpZnkoIGZpbGVzICk7XG5cdH1cblxuXHQvKipcblx0ICogR2V0IGlucHV0IGVsZW1lbnQuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjcuMVxuXHQgKlxuXHQgKiBAcGFyYW0ge29iamVjdH0gZHogRHJvcHpvbmUgb2JqZWN0LlxuXHQgKlxuXHQgKiBAcmV0dXJucyB7alF1ZXJ5fSBIaWRkZW4gaW5wdXQgZWxlbWVudC5cblx0ICovXG5cdGZ1bmN0aW9uIGdldElucHV0KCBkeiApIHtcblxuXHRcdHJldHVybiBqUXVlcnkoIGR6LmVsZW1lbnQgKS5wYXJlbnRzKCAnLndwZm9ybXMtZmllbGQtZmlsZS11cGxvYWQnICkuZmluZCggJ2lucHV0W25hbWU9JyArIGR6LmRhdGFUcmFuc2Zlci5uYW1lICsgJ10nICk7XG5cdH1cblxuXHQvKipcblx0ICogVXBkYXRlIHZhbHVlIGluIGlucHV0LlxuXHQgKlxuXHQgKiBAc2luY2UgMS41LjZcblx0ICpcblx0ICogQHBhcmFtIHtvYmplY3R9IGR6IERyb3B6b25lIG9iamVjdC5cblx0ICovXG5cdGZ1bmN0aW9uIHVwZGF0ZUlucHV0VmFsdWUoIGR6ICkge1xuXG5cdFx0dmFyICRpbnB1dCA9IGdldElucHV0KCBkeiApO1xuXG5cdFx0JGlucHV0LnZhbCggY29udmVydEZpbGVzVG9WYWx1ZSggZ2V0VmFsdWUoIGR6LmZpbGVzICksIGR6ICkgKS50cmlnZ2VyKCAnaW5wdXQnICk7XG5cblx0XHRpZiAoIHR5cGVvZiBqUXVlcnkuZm4udmFsaWQgIT09ICd1bmRlZmluZWQnICkge1xuXHRcdFx0JGlucHV0LnZhbGlkKCk7XG5cdFx0fVxuXHR9XG5cblx0LyoqXG5cdCAqIENvbXBsZXRlIGV2ZW50IGhpZ2hlciBvcmRlciBmdW5jdGlvbi5cblx0ICpcblx0ICogQGRlcHJlY2F0ZWQgMS42LjJcblx0ICpcblx0ICogQHNpbmNlIDEuNS42XG5cdCAqXG5cdCAqIEBwYXJhbSB7b2JqZWN0fSBkeiBEcm9wem9uZSBvYmplY3QuXG5cdCAqXG5cdCAqIEByZXR1cm5zIHtGdW5jdGlvbn0gSGFuZGxlciBmdW5jdGlvbi5cblx0ICovXG5cdGZ1bmN0aW9uIGNvbXBsZXRlKCBkeiApIHtcblxuXHRcdHJldHVybiBmdW5jdGlvbigpIHtcblx0XHRcdGR6LmxvYWRpbmcgPSBkei5sb2FkaW5nIHx8IDA7XG5cdFx0XHRkei5sb2FkaW5nLS07XG5cdFx0XHRkei5sb2FkaW5nID0gTWF0aC5tYXgoIGR6LmxvYWRpbmcgLSAxLCAwICk7XG5cdFx0XHR0b2dnbGVTdWJtaXQoIGR6ICk7XG5cdFx0XHR1cGRhdGVJbnB1dFZhbHVlKCBkeiApO1xuXHRcdH07XG5cdH1cblxuXHQvKipcblx0ICogQWRkIGFuIGVycm9yIG1lc3NhZ2UgdG8gdGhlIGN1cnJlbnQgZmlsZS5cblx0ICpcblx0ICogQHNpbmNlIDEuNi4yXG5cdCAqXG5cdCAqIEBwYXJhbSB7b2JqZWN0fSBmaWxlICAgICAgICAgRmlsZSBvYmplY3QuXG5cdCAqIEBwYXJhbSB7c3RyaW5nfSBlcnJvck1lc3NhZ2UgRXJyb3IgbWVzc2FnZVxuXHQgKi9cblx0ZnVuY3Rpb24gYWRkRXJyb3JNZXNzYWdlKCBmaWxlLCBlcnJvck1lc3NhZ2UgKSB7XG5cblx0XHRpZiAoIGZpbGUuaXNFcnJvck5vdFVwbG9hZGVkRGlzcGxheWVkICkge1xuXHRcdFx0cmV0dXJuO1xuXHRcdH1cblxuXHRcdHZhciBzcGFuID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCggJ3NwYW4nICk7XG5cdFx0c3Bhbi5pbm5lclRleHQgPSBlcnJvck1lc3NhZ2UudG9TdHJpbmcoKTtcblx0XHRzcGFuLnNldEF0dHJpYnV0ZSggJ2RhdGEtZHotZXJyb3JtZXNzYWdlJywgJycgKTtcblxuXHRcdGZpbGUucHJldmlld0VsZW1lbnQucXVlcnlTZWxlY3RvciggJy5kei1lcnJvci1tZXNzYWdlJyApLmFwcGVuZENoaWxkKCBzcGFuICk7XG5cdH1cblxuXHQvKipcblx0ICogQ29uZmlybSB0aGUgdXBsb2FkIHRvIHRoZSBzZXJ2ZXIuXG5cdCAqXG5cdCAqIFRoZSBjb25maXJtYXRpb24gaXMgbmVlZGVkIGluIG9yZGVyIHRvIGxldCBQSFAga25vd1xuXHQgKiB0aGF0IGFsbCB0aGUgY2h1bmtzIGhhdmUgYmVlbiB1cGxvYWRlZC5cblx0ICpcblx0ICogQHNpbmNlIDEuNi4yXG5cdCAqXG5cdCAqIEBwYXJhbSB7b2JqZWN0fSBkeiBEcm9wem9uZSBvYmplY3QuXG5cdCAqXG5cdCAqIEByZXR1cm5zIHtGdW5jdGlvbn0gSGFuZGxlciBmdW5jdGlvbi5cblx0ICovXG5cdGZ1bmN0aW9uIGNvbmZpcm1DaHVua3NGaW5pc2hVcGxvYWQoIGR6ICkge1xuXG5cdFx0cmV0dXJuIGZ1bmN0aW9uIGNvbmZpcm0oIGZpbGUgKSB7XG5cblx0XHRcdGlmICggISBmaWxlLnJldHJpZXMgKSB7XG5cdFx0XHRcdGZpbGUucmV0cmllcyA9IDA7XG5cdFx0XHR9XG5cblx0XHRcdGlmICggJ2Vycm9yJyA9PT0gZmlsZS5zdGF0dXMgKSB7XG5cdFx0XHRcdHJldHVybjtcblx0XHRcdH1cblxuXHRcdFx0LyoqXG5cdFx0XHQgKiBSZXRyeSBmaW5hbGl6ZSBmdW5jdGlvbi5cblx0XHRcdCAqXG5cdFx0XHQgKiBAc2luY2UgMS42LjJcblx0XHRcdCAqL1xuXHRcdFx0ZnVuY3Rpb24gcmV0cnkoKSB7XG5cdFx0XHRcdGZpbGUucmV0cmllcysrO1xuXG5cdFx0XHRcdGlmICggZmlsZS5yZXRyaWVzID09PSAzICkge1xuXHRcdFx0XHRcdGFkZEVycm9yTWVzc2FnZSggZmlsZSwgd2luZG93LndwZm9ybXNfZmlsZV91cGxvYWQuZXJyb3JzLmZpbGVfbm90X3VwbG9hZGVkICk7XG5cdFx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0XHR9XG5cblx0XHRcdFx0c2V0VGltZW91dCggZnVuY3Rpb24oKSB7XG5cdFx0XHRcdFx0Y29uZmlybSggZmlsZSApO1xuXHRcdFx0XHR9LCA1MDAwICogZmlsZS5yZXRyaWVzICk7XG5cdFx0XHR9XG5cblx0XHRcdC8qKlxuXHRcdFx0ICogRmFpbCBoYW5kbGVyIGZvciBhamF4IHJlcXVlc3QuXG5cdFx0XHQgKlxuXHRcdFx0ICogQHNpbmNlIDEuNi4yXG5cdFx0XHQgKlxuXHRcdFx0ICogQHBhcmFtIHtvYmplY3R9IHJlc3BvbnNlIFJlc3BvbnNlIGZyb20gdGhlIHNlcnZlclxuXHRcdFx0ICovXG5cdFx0XHRmdW5jdGlvbiBmYWlsKCByZXNwb25zZSApIHtcblxuXHRcdFx0XHR2YXIgaGFzU3BlY2lmaWNFcnJvciA9XHRyZXNwb25zZS5yZXNwb25zZUpTT04gJiZcblx0XHRcdFx0XHRcdFx0XHRcdFx0cmVzcG9uc2UucmVzcG9uc2VKU09OLnN1Y2Nlc3MgPT09IGZhbHNlICYmXG5cdFx0XHRcdFx0XHRcdFx0XHRcdHJlc3BvbnNlLnJlc3BvbnNlSlNPTi5kYXRhO1xuXG5cdFx0XHRcdGlmICggaGFzU3BlY2lmaWNFcnJvciApIHtcblx0XHRcdFx0XHRhZGRFcnJvck1lc3NhZ2UoIGZpbGUsIHJlc3BvbnNlLnJlc3BvbnNlSlNPTi5kYXRhICk7XG5cdFx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdFx0cmV0cnkoKTtcblx0XHRcdFx0fVxuXHRcdFx0fVxuXG5cdFx0XHQvKipcblx0XHRcdCAqIEhhbmRsZXIgZm9yIGFqYXggcmVxdWVzdC5cblx0XHRcdCAqXG5cdFx0XHQgKiBAc2luY2UgMS42LjJcblx0XHRcdCAqXG5cdFx0XHQgKiBAcGFyYW0ge29iamVjdH0gcmVzcG9uc2UgUmVzcG9uc2UgZnJvbSB0aGUgc2VydmVyXG5cdFx0XHQgKi9cblx0XHRcdGZ1bmN0aW9uIGNvbXBsZXRlKCByZXNwb25zZSApIHtcblxuXHRcdFx0XHRmaWxlLmNodW5rUmVzcG9uc2UgPSBKU09OLnN0cmluZ2lmeSggeyBkYXRhOiByZXNwb25zZSB9ICk7XG5cdFx0XHRcdGR6LmxvYWRpbmcgPSBkei5sb2FkaW5nIHx8IDA7XG5cdFx0XHRcdGR6LmxvYWRpbmctLTtcblx0XHRcdFx0ZHoubG9hZGluZyA9IE1hdGgubWF4KCBkei5sb2FkaW5nLCAwICk7XG5cblx0XHRcdFx0dG9nZ2xlU3VibWl0KCBkeiApO1xuXHRcdFx0XHR1cGRhdGVJbnB1dFZhbHVlKCBkeiApO1xuXHRcdFx0fVxuXG5cdFx0XHR3cC5hamF4LnBvc3QoIGpRdWVyeS5leHRlbmQoXG5cdFx0XHRcdHtcblx0XHRcdFx0XHRhY3Rpb246ICd3cGZvcm1zX2ZpbGVfY2h1bmtzX3VwbG9hZGVkJyxcblx0XHRcdFx0XHRmb3JtX2lkOiBkei5kYXRhVHJhbnNmZXIuZm9ybUlkLFxuXHRcdFx0XHRcdGZpZWxkX2lkOiBkei5kYXRhVHJhbnNmZXIuZmllbGRJZCxcblx0XHRcdFx0XHRuYW1lOiBmaWxlLm5hbWUsXG5cdFx0XHRcdH0sXG5cdFx0XHRcdGR6Lm9wdGlvbnMucGFyYW1zLmNhbGwoIGR6LCBudWxsLCBudWxsLCB7ZmlsZTogZmlsZSwgaW5kZXg6IDB9IClcblx0XHRcdCkgKS50aGVuKCBjb21wbGV0ZSApLmZhaWwoIGZhaWwgKTtcblxuXHRcdFx0Ly8gTW92ZSB0byB1cGxvYWQgdGhlIG5leHQgZmlsZSwgaWYgYW55LlxuXHRcdFx0ZHoucHJvY2Vzc1F1ZXVlKCk7XG5cdFx0fTtcblx0fVxuXG5cdC8qKlxuXHQgKiBUb2dnbGUgc2hvd2luZyBlbXB0eSBtZXNzYWdlLlxuXHQgKlxuXHQgKiBAc2luY2UgMS41LjZcblx0ICpcblx0ICogQHBhcmFtIHtvYmplY3R9IGR6IERyb3B6b25lIG9iamVjdC5cblx0ICovXG5cdGZ1bmN0aW9uIHRvZ2dsZU1lc3NhZ2UoIGR6ICkge1xuXG5cdFx0c2V0VGltZW91dCggZnVuY3Rpb24oKSB7XG5cdFx0XHR2YXIgdmFsaWRGaWxlcyA9IGR6LmZpbGVzLmZpbHRlciggZnVuY3Rpb24oIGZpbGUgKSB7XG5cdFx0XHRcdHJldHVybiBmaWxlLmFjY2VwdGVkO1xuXHRcdFx0fSApO1xuXG5cdFx0XHRpZiAoIHZhbGlkRmlsZXMubGVuZ3RoID49IGR6Lm9wdGlvbnMubWF4RmlsZXMgKSB7XG5cdFx0XHRcdGR6LmVsZW1lbnQucXVlcnlTZWxlY3RvciggJy5kei1tZXNzYWdlJyApLmNsYXNzTGlzdC5hZGQoICdoaWRlJyApO1xuXHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0ZHouZWxlbWVudC5xdWVyeVNlbGVjdG9yKCAnLmR6LW1lc3NhZ2UnICkuY2xhc3NMaXN0LnJlbW92ZSggJ2hpZGUnICk7XG5cdFx0XHR9XG5cdFx0fSwgMCApO1xuXHR9XG5cblx0LyoqXG5cdCAqIFRvZ2dsZSBlcnJvciBtZXNzYWdlIGlmIHRvdGFsIHNpemUgbW9yZSB0aGFuIGxpbWl0LlxuXHQgKiBSdW5zIGZvciBlYWNoIGZpbGUuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjUuNlxuXHQgKlxuXHQgKiBAcGFyYW0ge29iamVjdH0gZmlsZSBDdXJyZW50IGZpbGUuXG5cdCAqIEBwYXJhbSB7b2JqZWN0fSBkeiAgIERyb3B6b25lIG9iamVjdC5cblx0ICovXG5cdGZ1bmN0aW9uIHZhbGlkYXRlUG9zdE1heFNpemVFcnJvciggZmlsZSwgZHogKSB7XG5cblx0XHRzZXRUaW1lb3V0KCBmdW5jdGlvbigpIHtcblx0XHRcdGlmICggZmlsZS5zaXplID49IGR6LmRhdGFUcmFuc2Zlci5wb3N0TWF4U2l6ZSApIHtcblx0XHRcdFx0dmFyIGVycm9yTWVzc2FnZSA9IHdpbmRvdy53cGZvcm1zX2ZpbGVfdXBsb2FkLmVycm9ycy5wb3N0X21heF9zaXplO1xuXHRcdFx0XHRpZiAoICEgZmlsZS5pc0Vycm9yTm90VXBsb2FkZWREaXNwbGF5ZWQgKSB7XG5cdFx0XHRcdFx0ZmlsZS5pc0Vycm9yTm90VXBsb2FkZWREaXNwbGF5ZWQgPSB0cnVlO1xuXHRcdFx0XHRcdGVycm9yTWVzc2FnZSA9IHdpbmRvdy53cGZvcm1zX2ZpbGVfdXBsb2FkLmVycm9ycy5maWxlX25vdF91cGxvYWRlZCArICcgJyArIGVycm9yTWVzc2FnZTtcblx0XHRcdFx0XHRhZGRFcnJvck1lc3NhZ2UoIGZpbGUsIGVycm9yTWVzc2FnZSApO1xuXHRcdFx0XHR9XG5cdFx0XHR9XG5cdFx0fSwgMSApO1xuXHR9XG5cblx0LyoqXG5cdCAqIFN0YXJ0IEZpbGUgVXBsb2FkLlxuXHQgKlxuXHQgKiBUaGlzIHdvdWxkIGRvIHRoZSBpbml0aWFsIHJlcXVlc3QgdG8gc3RhcnQgYSBmaWxlIHVwbG9hZC4gTm8gY2h1bmtcblx0ICogaXMgdXBsb2FkZWQgYXQgdGhpcyBzdGFnZSwgaW5zdGVhZCBhbGwgdGhlIGluZm9ybWF0aW9uIHJlbGF0ZWQgdG8gdGhlXG5cdCAqIGZpbGUgYXJlIHNlbmQgdG8gdGhlIHNlcnZlciB3YWl0aW5nIGZvciBhbiBhdXRob3JpemF0aW9uLlxuXHQgKlxuXHQgKiBJZiB0aGUgc2VydmVyIGF1dGhvcml6ZXMgdGhlIGNsaWVudCB3b3VsZCBzdGFydCB1cGxvYWRpbmcgdGhlIGNodW5rcy5cblx0ICpcblx0ICogQHNpbmNlIDEuNi4yXG5cdCAqXG5cdCAqIEBwYXJhbSB7b2JqZWN0fSBkeiAgIERyb3B6b25lIG9iamVjdC5cblx0ICogQHBhcmFtIHtvYmplY3R9IGZpbGUgQ3VycmVudCBmaWxlLlxuXHQgKi9cblx0ZnVuY3Rpb24gaW5pdEZpbGVVcGxvYWQoIGR6LCBmaWxlICkge1xuXG5cdFx0d3AuYWpheC5wb3N0KCBqUXVlcnkuZXh0ZW5kKFxuXHRcdFx0e1xuXHRcdFx0XHRhY3Rpb24gOiAnd3Bmb3Jtc191cGxvYWRfY2h1bmtfaW5pdCcsXG5cdFx0XHRcdGZvcm1faWQ6IGR6LmRhdGFUcmFuc2Zlci5mb3JtSWQsXG5cdFx0XHRcdGZpZWxkX2lkOiBkei5kYXRhVHJhbnNmZXIuZmllbGRJZCxcblx0XHRcdFx0bmFtZTogZmlsZS5uYW1lLFxuXHRcdFx0XHRzbG93OiBpc1Nsb3csXG5cdFx0XHR9LFxuXHRcdFx0ZHoub3B0aW9ucy5wYXJhbXMuY2FsbCggZHosIG51bGwsIG51bGwsIHtmaWxlOiBmaWxlLCBpbmRleDogMH0gKVxuXHRcdCkgKS50aGVuKCBmdW5jdGlvbiggcmVzcG9uc2UgKSB7XG5cblx0XHRcdC8vIEZpbGUgdXBsb2FkIGhhcyBiZWVuIGF1dGhvcml6ZWQuXG5cblx0XHRcdGZvciAoIHZhciBrZXkgaW4gcmVzcG9uc2UgKSB7XG5cdFx0XHRcdGR6Lm9wdGlvbnNbIGtleSBdID0gcmVzcG9uc2VbIGtleSBdO1xuXHRcdFx0fVxuXG5cdFx0XHRpZiAoIHJlc3BvbnNlLmR6Y2h1bmtzaXplICkge1xuXHRcdFx0XHRkei5vcHRpb25zLmNodW5rU2l6ZSA9IHBhcnNlSW50KCByZXNwb25zZS5kemNodW5rc2l6ZSwgMTAgKTtcblx0XHRcdFx0ZmlsZS51cGxvYWQudG90YWxDaHVua0NvdW50ID0gTWF0aC5jZWlsKCBmaWxlLnNpemUgLyBkei5vcHRpb25zLmNodW5rU2l6ZSApO1xuXHRcdFx0fVxuXG5cdFx0XHRkei5wcm9jZXNzUXVldWUoKTtcblx0XHR9ICkuZmFpbCggZnVuY3Rpb24oIHJlc3BvbnNlICkge1xuXG5cdFx0XHRmaWxlLnN0YXR1cyA9ICdlcnJvcic7XG5cblx0XHRcdGlmICggISBmaWxlLnhociApIHtcblx0XHRcdFx0Y29uc3QgZmllbGQgPSBkei5lbGVtZW50LmNsb3Nlc3QoICcud3Bmb3Jtcy1maWVsZCcgKTtcblx0XHRcdFx0Y29uc3QgaGlkZGVuSW5wdXQgPSBmaWVsZC5xdWVyeVNlbGVjdG9yKCAnLmRyb3B6b25lLWlucHV0JyApO1xuXHRcdFx0XHRjb25zdCBlcnJvck1lc3NhZ2UgPSB3aW5kb3cud3Bmb3Jtc19maWxlX3VwbG9hZC5lcnJvcnMuZmlsZV9ub3RfdXBsb2FkZWQgKyAnICcgKyB3aW5kb3cud3Bmb3Jtc19maWxlX3VwbG9hZC5lcnJvcnMuZGVmYXVsdF9lcnJvcjtcblxuXHRcdFx0XHRmaWxlLnByZXZpZXdFbGVtZW50LmNsYXNzTGlzdC5hZGQoICdkei1wcm9jZXNzaW5nJywgJ2R6LWVycm9yJywgJ2R6LWNvbXBsZXRlJyApO1xuXHRcdFx0XHRoaWRkZW5JbnB1dC5jbGFzc0xpc3QuYWRkKCAnd3Bmb3Jtcy1lcnJvcicgKTtcblx0XHRcdFx0ZmllbGQuY2xhc3NMaXN0LmFkZCggJ3dwZm9ybXMtaGFzLWVycm9yJyApO1xuXHRcdFx0XHRhZGRFcnJvck1lc3NhZ2UoIGZpbGUsIGVycm9yTWVzc2FnZSApO1xuXHRcdFx0fVxuXG5cdFx0XHRkei5wcm9jZXNzUXVldWUoKTtcblx0XHR9ICk7XG5cdH1cblxuXHQvKipcblx0ICogVmFsaWRhdGUgdGhlIGZpbGUgd2hlbiBpdCB3YXMgYWRkZWQgaW4gdGhlIGRyb3B6b25lLlxuXHQgKlxuXHQgKiBAc2luY2UgMS41LjZcblx0ICpcblx0ICogQHBhcmFtIHtvYmplY3R9IGR6IERyb3B6b25lIG9iamVjdC5cblx0ICpcblx0ICogQHJldHVybnMge0Z1bmN0aW9ufSBIYW5kbGVyIGZ1bmN0aW9uLlxuXHQgKi9cblx0ZnVuY3Rpb24gYWRkZWRGaWxlKCBkeiApIHtcblxuXHRcdHJldHVybiBmdW5jdGlvbiggZmlsZSApIHtcblxuXHRcdFx0aWYgKCBmaWxlLnNpemUgPj0gZHouZGF0YVRyYW5zZmVyLnBvc3RNYXhTaXplICkge1xuXHRcdFx0XHR2YWxpZGF0ZVBvc3RNYXhTaXplRXJyb3IoIGZpbGUsIGR6ICk7XG5cdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRzcGVlZFRlc3QoIGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRcdGluaXRGaWxlVXBsb2FkKCBkeiwgZmlsZSApO1xuXHRcdFx0XHR9ICk7XG5cdFx0XHR9XG5cblx0XHRcdGR6LmxvYWRpbmcgPSBkei5sb2FkaW5nIHx8IDA7XG5cdFx0XHRkei5sb2FkaW5nKys7XG5cdFx0XHR0b2dnbGVTdWJtaXQoIGR6ICk7XG5cblx0XHRcdHRvZ2dsZU1lc3NhZ2UoIGR6ICk7XG5cdFx0fTtcblx0fVxuXG5cdC8qKlxuXHQgKiBTZW5kIGFuIEFKQVggcmVxdWVzdCB0byByZW1vdmUgZmlsZSBmcm9tIHRoZSBzZXJ2ZXIuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjUuNlxuXHQgKlxuXHQgKiBAcGFyYW0ge3N0cmluZ30gZmlsZSBGaWxlIG5hbWUuXG5cdCAqIEBwYXJhbSB7b2JqZWN0fSBkeiBEcm9wem9uZSBvYmplY3QuXG5cdCAqL1xuXHRmdW5jdGlvbiByZW1vdmVGcm9tU2VydmVyKCBmaWxlLCBkeiApIHtcblxuXHRcdHdwLmFqYXgucG9zdCgge1xuXHRcdFx0YWN0aW9uOiAnd3Bmb3Jtc19yZW1vdmVfZmlsZScsXG5cdFx0XHRmaWxlOiBmaWxlLFxuXHRcdFx0Zm9ybV9pZDogZHouZGF0YVRyYW5zZmVyLmZvcm1JZCxcblx0XHRcdGZpZWxkX2lkOiBkei5kYXRhVHJhbnNmZXIuZmllbGRJZCxcblx0XHR9ICk7XG5cdH1cblxuXHQvKipcblx0ICogSW5pdCB0aGUgZmlsZSByZW1vdmFsIG9uIHNlcnZlciB3aGVuIHVzZXIgcmVtb3ZlZCBpdCBvbiBmcm9udC1lbmQuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjUuNlxuXHQgKlxuXHQgKiBAcGFyYW0ge29iamVjdH0gZHogRHJvcHpvbmUgb2JqZWN0LlxuXHQgKlxuXHQgKiBAcmV0dXJucyB7RnVuY3Rpb259IEhhbmRsZXIgZnVuY3Rpb24uXG5cdCAqL1xuXHRmdW5jdGlvbiByZW1vdmVkRmlsZSggZHogKSB7XG5cblx0XHRyZXR1cm4gZnVuY3Rpb24oIGZpbGUgKSB7XG5cdFx0XHR0b2dnbGVNZXNzYWdlKCBkeiApO1xuXG5cdFx0XHR2YXIganNvbiA9IGZpbGUuY2h1bmtSZXNwb25zZSB8fCAoIGZpbGUueGhyIHx8IHt9ICkucmVzcG9uc2VUZXh0O1xuXG5cdFx0XHRpZiAoIGpzb24gKSB7XG5cdFx0XHRcdHZhciBvYmplY3QgPSBwYXJzZUpTT04oIGpzb24gKTtcblxuXHRcdFx0XHRpZiAoIG9iamVjdCAmJiBvYmplY3QuZGF0YSAmJiBvYmplY3QuZGF0YS5maWxlICkge1xuXHRcdFx0XHRcdHJlbW92ZUZyb21TZXJ2ZXIoIG9iamVjdC5kYXRhLmZpbGUsIGR6ICk7XG5cdFx0XHRcdH1cblx0XHRcdH1cblxuXHRcdFx0Ly8gUmVtb3ZlIHN1Ym1pdHRlZCB2YWx1ZS5cblx0XHRcdGlmICggT2JqZWN0LnByb3RvdHlwZS5oYXNPd25Qcm9wZXJ0eS5jYWxsKCBmaWxlLCAnaXNEZWZhdWx0JyApICYmIGZpbGUuaXNEZWZhdWx0ICkge1xuXHRcdFx0XHRzdWJtaXR0ZWRWYWx1ZXNbIGR6LmRhdGFUcmFuc2Zlci5mb3JtSWQgXVsgZHouZGF0YVRyYW5zZmVyLmZpZWxkSWQgXS5zcGxpY2UoIGZpbGUuaW5kZXgsIDEgKTtcblx0XHRcdFx0ZHoub3B0aW9ucy5tYXhGaWxlcysrO1xuXHRcdFx0XHRyZW1vdmVGcm9tU2VydmVyKCBmaWxlLmZpbGUsIGR6ICk7XG5cdFx0XHR9XG5cblx0XHRcdHVwZGF0ZUlucHV0VmFsdWUoIGR6ICk7XG5cblx0XHRcdGR6LmxvYWRpbmcgPSBkei5sb2FkaW5nIHx8IDA7XG5cdFx0XHRkei5sb2FkaW5nLS07XG5cdFx0XHRkei5sb2FkaW5nID0gTWF0aC5tYXgoIGR6LmxvYWRpbmcsIDAgKTtcblxuXHRcdFx0dG9nZ2xlU3VibWl0KCBkeiApO1xuXG5cdFx0XHRjb25zdCBudW1FcnJvcnMgPSBkei5lbGVtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoICcuZHotcHJldmlldy5kei1lcnJvcicgKS5sZW5ndGg7XG5cblx0XHRcdGlmICggbnVtRXJyb3JzID09PSAwICkge1xuXHRcdFx0XHRkei5lbGVtZW50LmNsYXNzTGlzdC5yZW1vdmUoICd3cGZvcm1zLWVycm9yJyApO1xuXHRcdFx0XHRkei5lbGVtZW50LmNsb3Nlc3QoICcud3Bmb3Jtcy1maWVsZCcgKS5jbGFzc0xpc3QucmVtb3ZlKCAnd3Bmb3Jtcy1oYXMtZXJyb3InICk7XG5cdFx0XHR9XG5cdFx0fTtcblx0fVxuXG5cdC8qKlxuXHQgKiBQcm9jZXNzIGFueSBlcnJvciB0aGF0IHdhcyBmaXJlZCBwZXIgZWFjaCBmaWxlLlxuXHQgKiBUaGVyZSBtaWdodCBiZSBzZXZlcmFsIGVycm9ycyBwZXIgZmlsZSwgaW4gdGhhdCBjYXNlIC0gZGlzcGxheSBcIm5vdCB1cGxvYWRlZFwiIHRleHQgb25seSBvbmNlLlxuXHQgKlxuXHQgKiBAc2luY2UgMS41LjYuMVxuXHQgKlxuXHQgKiBAcGFyYW0ge29iamVjdH0gZHogRHJvcHpvbmUgb2JqZWN0LlxuXHQgKlxuXHQgKiBAcmV0dXJucyB7RnVuY3Rpb259IEhhbmRsZXIgZnVuY3Rpb24uXG5cdCAqL1xuXHRmdW5jdGlvbiBlcnJvciggZHogKSB7XG5cblx0XHRyZXR1cm4gZnVuY3Rpb24oIGZpbGUsIGVycm9yTWVzc2FnZSApIHtcblxuXHRcdFx0aWYgKCBmaWxlLmlzRXJyb3JOb3RVcGxvYWRlZERpc3BsYXllZCApIHtcblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXG5cdFx0XHRpZiAoIHR5cGVvZiBlcnJvck1lc3NhZ2UgPT09ICdvYmplY3QnICkge1xuXHRcdFx0XHRlcnJvck1lc3NhZ2UgPSBPYmplY3QucHJvdG90eXBlLmhhc093blByb3BlcnR5LmNhbGwoIGVycm9yTWVzc2FnZSwgJ2RhdGEnICkgJiYgdHlwZW9mIGVycm9yTWVzc2FnZS5kYXRhID09PSAnc3RyaW5nJyA/IGVycm9yTWVzc2FnZS5kYXRhIDogJyc7XG5cdFx0XHR9XG5cblx0XHRcdGVycm9yTWVzc2FnZSA9IGVycm9yTWVzc2FnZSAhPT0gJzAnID8gZXJyb3JNZXNzYWdlIDogJyc7XG5cblx0XHRcdGZpbGUuaXNFcnJvck5vdFVwbG9hZGVkRGlzcGxheWVkID0gdHJ1ZTtcblx0XHRcdGZpbGUucHJldmlld0VsZW1lbnQucXVlcnlTZWxlY3RvckFsbCggJ1tkYXRhLWR6LWVycm9ybWVzc2FnZV0nIClbMF0udGV4dENvbnRlbnQgPSB3aW5kb3cud3Bmb3Jtc19maWxlX3VwbG9hZC5lcnJvcnMuZmlsZV9ub3RfdXBsb2FkZWQgKyAnICcgKyBlcnJvck1lc3NhZ2U7XG5cdFx0XHRkei5lbGVtZW50LmNsYXNzTGlzdC5hZGQoICd3cGZvcm1zLWVycm9yJyApO1xuXHRcdFx0ZHouZWxlbWVudC5jbG9zZXN0KCAnLndwZm9ybXMtZmllbGQnICkuY2xhc3NMaXN0LmFkZCggJ3dwZm9ybXMtaGFzLWVycm9yJyApO1xuXHRcdH07XG5cdH1cblxuXHQvKipcblx0ICogUHJlc2V0IHByZXZpb3VzbHkgc3VibWl0dGVkIGZpbGVzIHRvIHRoZSBkcm9wem9uZS5cblx0ICpcblx0ICogQHNpbmNlIDEuNy4xXG5cdCAqXG5cdCAqIEBwYXJhbSB7b2JqZWN0fSBkeiBEcm9wem9uZSBvYmplY3QuXG5cdCAqL1xuXHRmdW5jdGlvbiBwcmVzZXRTdWJtaXR0ZWREYXRhKCBkeiApIHtcblxuXHRcdHZhciBmaWxlcyA9IHBhcnNlSlNPTiggZ2V0SW5wdXQoIGR6ICkudmFsKCkgKTtcblxuXHRcdGlmICggISBmaWxlcyB8fCAhIGZpbGVzLmxlbmd0aCApIHtcblx0XHRcdHJldHVybjtcblx0XHR9XG5cblx0XHRzdWJtaXR0ZWRWYWx1ZXNbZHouZGF0YVRyYW5zZmVyLmZvcm1JZF0gPSBbXTtcblxuXHRcdC8vIFdlIGRvIGRlZXAgY2xvbmluZyBhbiBvYmplY3QgdG8gYmUgc3VyZSB0aGF0IGRhdGEgaXMgcGFzc2VkIHdpdGhvdXQgbGlua3MuXG5cdFx0c3VibWl0dGVkVmFsdWVzW2R6LmRhdGFUcmFuc2Zlci5mb3JtSWRdW2R6LmRhdGFUcmFuc2Zlci5maWVsZElkXSA9IEpTT04ucGFyc2UoIEpTT04uc3RyaW5naWZ5KCBmaWxlcyApICk7XG5cblx0XHRmaWxlcy5mb3JFYWNoKCBmdW5jdGlvbiggZmlsZSwgaW5kZXggKSB7XG5cblx0XHRcdGZpbGUuaXNEZWZhdWx0ID0gdHJ1ZTtcblx0XHRcdGZpbGUuaW5kZXggPSBpbmRleDtcblxuXHRcdFx0aWYgKCBmaWxlLnR5cGUubWF0Y2goIC9pbWFnZS4qLyApICkge1xuXHRcdFx0XHRkei5kaXNwbGF5RXhpc3RpbmdGaWxlKCBmaWxlLCBmaWxlLnVybCApO1xuXG5cdFx0XHRcdHJldHVybjtcblx0XHRcdH1cblxuXHRcdFx0ZHouZW1pdCggJ2FkZGVkZmlsZScsIGZpbGUgKTtcblx0XHRcdGR6LmVtaXQoICdjb21wbGV0ZScsIGZpbGUgKTtcblx0XHR9ICk7XG5cblx0XHRkei5vcHRpb25zLm1heEZpbGVzID0gZHoub3B0aW9ucy5tYXhGaWxlcyAtIGZpbGVzLmxlbmd0aDtcblx0fVxuXG5cdC8qKlxuXHQgKiBEcm9wem9uZS5qcyBpbml0IGZvciBlYWNoIGZpZWxkLlxuXHQgKlxuXHQgKiBAc2luY2UgMS41LjZcblx0ICpcblx0ICogQHBhcmFtIHtvYmplY3R9ICRlbCBXUEZvcm1zIHVwbG9hZGVyIERPTSBlbGVtZW50LlxuXHQgKlxuXHQgKiBAcmV0dXJucyB7b2JqZWN0fSBEcm9wem9uZSBvYmplY3QuXG5cdCAqL1xuXHRmdW5jdGlvbiBkcm9wWm9uZUluaXQoICRlbCApIHtcblxuXHRcdGlmICggJGVsLmRyb3B6b25lICkge1xuXHRcdFx0cmV0dXJuICRlbC5kcm9wem9uZTtcblx0XHR9XG5cblx0XHR2YXIgZm9ybUlkID0gcGFyc2VJbnQoICRlbC5kYXRhc2V0LmZvcm1JZCwgMTAgKTtcblx0XHR2YXIgZmllbGRJZCA9IHBhcnNlSW50KCAkZWwuZGF0YXNldC5maWVsZElkLCAxMCApIHx8IDA7XG5cdFx0dmFyIG1heEZpbGVzID0gcGFyc2VJbnQoICRlbC5kYXRhc2V0Lm1heEZpbGVOdW1iZXIsIDEwICk7XG5cblx0XHR2YXIgYWNjZXB0ZWRGaWxlcyA9ICRlbC5kYXRhc2V0LmV4dGVuc2lvbnMuc3BsaXQoICcsJyApLm1hcCggZnVuY3Rpb24oIGVsICkge1xuXHRcdFx0cmV0dXJuICcuJyArIGVsO1xuXHRcdH0gKS5qb2luKCAnLCcgKTtcblxuXHRcdC8vIENvbmZpZ3VyZSBhbmQgbW9kaWZ5IERyb3B6b25lIGxpYnJhcnkuXG5cdFx0dmFyIGR6ID0gbmV3IHdpbmRvdy5Ecm9wem9uZSggJGVsLCB7XG5cdFx0XHR1cmw6IHdpbmRvdy53cGZvcm1zX2ZpbGVfdXBsb2FkLnVybCxcblx0XHRcdGFkZFJlbW92ZUxpbmtzOiB0cnVlLFxuXHRcdFx0Y2h1bmtpbmc6IHRydWUsXG5cdFx0XHRmb3JjZUNodW5raW5nOiB0cnVlLFxuXHRcdFx0cmV0cnlDaHVua3M6IHRydWUsXG5cdFx0XHRjaHVua1NpemU6IHBhcnNlSW50KCAkZWwuZGF0YXNldC5maWxlQ2h1bmtTaXplLCAxMCApLFxuXHRcdFx0cGFyYW1OYW1lOiAkZWwuZGF0YXNldC5pbnB1dE5hbWUsXG5cdFx0XHRwYXJhbGxlbENodW5rVXBsb2FkczogISEgKCAkZWwuZGF0YXNldC5wYXJhbGxlbFVwbG9hZHMgfHwgJycgKS5tYXRjaCggL150cnVlJC9pICksXG5cdFx0XHRwYXJhbGxlbFVwbG9hZHM6IHBhcnNlSW50KCAkZWwuZGF0YXNldC5tYXhQYXJhbGxlbFVwbG9hZHMsIDEwICksXG5cdFx0XHRhdXRvUHJvY2Vzc1F1ZXVlOiBmYWxzZSxcblx0XHRcdG1heEZpbGVzaXplOiAoIHBhcnNlSW50KCAkZWwuZGF0YXNldC5tYXhTaXplLCAxMCApIC8gKCAxMDI0ICogMTAyNCApICkudG9GaXhlZCggMiApLFxuXHRcdFx0bWF4RmlsZXM6IG1heEZpbGVzLFxuXHRcdFx0YWNjZXB0ZWRGaWxlczogYWNjZXB0ZWRGaWxlcyxcblx0XHRcdGRpY3RNYXhGaWxlc0V4Y2VlZGVkOiB3aW5kb3cud3Bmb3Jtc19maWxlX3VwbG9hZC5lcnJvcnMuZmlsZV9saW1pdC5yZXBsYWNlKCAne2ZpbGVMaW1pdH0nLCBtYXhGaWxlcyApLFxuXHRcdFx0ZGljdEludmFsaWRGaWxlVHlwZTogd2luZG93LndwZm9ybXNfZmlsZV91cGxvYWQuZXJyb3JzLmZpbGVfZXh0ZW5zaW9uLFxuXHRcdFx0ZGljdEZpbGVUb29CaWc6IHdpbmRvdy53cGZvcm1zX2ZpbGVfdXBsb2FkLmVycm9ycy5maWxlX3NpemUsXG5cdFx0fSApO1xuXG5cdFx0Ly8gQ3VzdG9tIHZhcmlhYmxlcy5cblx0XHRkei5kYXRhVHJhbnNmZXIgPSB7XG5cdFx0XHRwb3N0TWF4U2l6ZTogJGVsLmRhdGFzZXQubWF4U2l6ZSxcblx0XHRcdG5hbWU6ICRlbC5kYXRhc2V0LmlucHV0TmFtZSxcblx0XHRcdGZvcm1JZDogZm9ybUlkLFxuXHRcdFx0ZmllbGRJZDogZmllbGRJZCxcblx0XHR9O1xuXG5cdFx0cHJlc2V0U3VibWl0dGVkRGF0YSggZHogKTtcblxuXHRcdC8vIFByb2Nlc3MgZXZlbnRzLlxuXHRcdGR6Lm9uKCAnc2VuZGluZycsIHNlbmRpbmcoIGR6LCB7XG5cdFx0XHRhY3Rpb246ICd3cGZvcm1zX3VwbG9hZF9jaHVuaycsXG5cdFx0XHRmb3JtX2lkOiBmb3JtSWQsXG5cdFx0XHRmaWVsZF9pZDogZmllbGRJZCxcblx0XHR9ICkgKTtcblx0XHRkei5vbiggJ2FkZGVkZmlsZScsIGFkZGVkRmlsZSggZHogKSApO1xuXHRcdGR6Lm9uKCAncmVtb3ZlZGZpbGUnLCByZW1vdmVkRmlsZSggZHogKSApO1xuXHRcdGR6Lm9uKCAnY29tcGxldGUnLCBjb25maXJtQ2h1bmtzRmluaXNoVXBsb2FkKCBkeiApICk7XG5cdFx0ZHoub24oICdlcnJvcicsIGVycm9yKCBkeiApICk7XG5cblx0XHRyZXR1cm4gZHo7XG5cdH1cblxuXHQvKipcblx0ICogSGlkZGVuIERyb3B6b25lIGlucHV0IGZvY3VzIGV2ZW50IGhhbmRsZXIuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjguMVxuXHQgKi9cblx0ZnVuY3Rpb24gZHJvcHpvbmVJbnB1dEZvY3VzKCkge1xuXG5cdFx0JCggdGhpcyApLnByZXYoICcud3Bmb3Jtcy11cGxvYWRlcicgKS5hZGRDbGFzcyggJ3dwZm9ybXMtZm9jdXMnICk7XG5cdH1cblxuXHQvKipcblx0ICogSGlkZGVuIERyb3B6b25lIGlucHV0IGJsdXIgZXZlbnQgaGFuZGxlci5cblx0ICpcblx0ICogQHNpbmNlIDEuOC4xXG5cdCAqL1xuXHRmdW5jdGlvbiBkcm9wem9uZUlucHV0Qmx1cigpIHtcblxuXHRcdCQoIHRoaXMgKS5wcmV2KCAnLndwZm9ybXMtdXBsb2FkZXInICkucmVtb3ZlQ2xhc3MoICd3cGZvcm1zLWZvY3VzJyApO1xuXHR9XG5cblx0LyoqXG5cdCAqIEhpZGRlbiBEcm9wem9uZSBpbnB1dCBibHVyIGV2ZW50IGhhbmRsZXIuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjguMVxuXHQgKlxuXHQgKiBAcGFyYW0ge29iamVjdH0gZSBFdmVudCBvYmplY3QuXG5cdCAqL1xuXHRmdW5jdGlvbiBkcm9wem9uZUlucHV0S2V5cHJlc3MoIGUgKSB7XG5cblx0XHRlLnByZXZlbnREZWZhdWx0KCk7XG5cblx0XHRpZiAoIGUua2V5Q29kZSAhPT0gMTMgKSB7XG5cdFx0XHRyZXR1cm47XG5cdFx0fVxuXG5cdFx0JCggdGhpcyApLnByZXYoICcud3Bmb3Jtcy11cGxvYWRlcicgKS50cmlnZ2VyKCAnY2xpY2snICk7XG5cdH1cblxuXHQvKipcblx0ICogSGlkZGVuIERyb3B6b25lIGlucHV0IGJsdXIgZXZlbnQgaGFuZGxlci5cblx0ICpcblx0ICogQHNpbmNlIDEuOC4xXG5cdCAqL1xuXHRmdW5jdGlvbiBkcm9wem9uZUNsaWNrKCkge1xuXG5cdFx0JCggdGhpcyApLm5leHQoICcuZHJvcHpvbmUtaW5wdXQnICkudHJpZ2dlciggJ2ZvY3VzJyApO1xuXHR9XG5cblx0LyoqXG5cdCAqIENsYXNzaWMgRmlsZSB1cGxvYWQgc3VjY2VzcyBjYWxsYmFjayB0byBkZXRlcm1pbmUgaWYgYWxsIGZpbGVzIGFyZSB1cGxvYWRlZC5cblx0ICpcblx0ICogQHNpbmNlIDEuOC4zXG5cdCAqXG5cdCAqIEBwYXJhbSB7RXZlbnR9IGUgRXZlbnQuXG5cdCAqIEBwYXJhbSB7alF1ZXJ5fSAkZm9ybSBGb3JtLlxuXHQgKi9cblx0ZnVuY3Rpb24gY29tYmluZWRVcGxvYWRzU2l6ZU9rKCBlLCAkZm9ybSApIHtcblxuXHRcdGlmICggYW55VXBsb2Fkc0luUHJvZ3Jlc3MoKSApIHtcblx0XHRcdGRpc2FibGVTdWJtaXRCdXR0b24oICRmb3JtICk7XG5cdFx0fVxuXHR9XG5cblx0LyoqXG5cdCAqIEV2ZW50cy5cblx0ICpcblx0ICogQHNpbmNlIDEuOC4xXG5cdCAqL1xuXHRmdW5jdGlvbiBldmVudHMoKSB7XG5cblx0XHQkKCAnLmRyb3B6b25lLWlucHV0JyApXG5cdFx0XHQub24oICdmb2N1cycsIGRyb3B6b25lSW5wdXRGb2N1cyApXG5cdFx0XHQub24oICdibHVyJywgZHJvcHpvbmVJbnB1dEJsdXIgKVxuXHRcdFx0Lm9uKCAna2V5cHJlc3MnLCBkcm9wem9uZUlucHV0S2V5cHJlc3MgKTtcblxuXHRcdCQoICcud3Bmb3Jtcy11cGxvYWRlcicgKVxuXHRcdFx0Lm9uKCAnY2xpY2snLCBkcm9wem9uZUNsaWNrICk7XG5cblx0XHQkKCAnZm9ybS53cGZvcm1zLWZvcm0nIClcblx0XHRcdC5vbiggJ3dwZm9ybXNDb21iaW5lZFVwbG9hZHNTaXplT2snLCBjb21iaW5lZFVwbG9hZHNTaXplT2sgKTtcblx0fVxuXG5cdC8qKlxuXHQgKiBET01Db250ZW50TG9hZGVkIGhhbmRsZXIuXG5cdCAqXG5cdCAqIEBzaW5jZSAxLjUuNlxuXHQgKi9cblx0ZnVuY3Rpb24gcmVhZHkoKSB7XG5cblx0XHR3aW5kb3cud3Bmb3JtcyA9IHdpbmRvdy53cGZvcm1zIHx8IHt9O1xuXHRcdHdpbmRvdy53cGZvcm1zLmRyb3B6b25lcyA9IFtdLnNsaWNlLmNhbGwoIGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoICcud3Bmb3Jtcy11cGxvYWRlcicgKSApLm1hcCggZHJvcFpvbmVJbml0ICk7XG5cblx0XHRldmVudHMoKTtcblx0fVxuXG5cdC8qKlxuXHQgKiBNb2Rlcm4gRmlsZSBVcGxvYWQgZW5naW5lLlxuXHQgKlxuXHQgKiBAc2luY2UgMS42LjBcblx0ICovXG5cdHZhciB3cGZvcm1zTW9kZXJuRmlsZVVwbG9hZCA9IHtcblxuXHRcdC8qKlxuXHRcdCAqIFN0YXJ0IHRoZSBpbml0aWFsaXphdGlvbi5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjYuMFxuXHRcdCAqL1xuXHRcdGluaXQ6IGZ1bmN0aW9uKCkge1xuXG5cdFx0XHRpZiAoIGRvY3VtZW50LnJlYWR5U3RhdGUgPT09ICdsb2FkaW5nJyApIHtcblx0XHRcdFx0ZG9jdW1lbnQuYWRkRXZlbnRMaXN0ZW5lciggJ0RPTUNvbnRlbnRMb2FkZWQnLCByZWFkeSApO1xuXHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0cmVhZHkoKTtcblx0XHRcdH1cblx0XHR9LFxuXHR9O1xuXG5cdC8vIENhbGwgaW5pdCBhbmQgc2F2ZSBpbiBnbG9iYWwgdmFyaWFibGUuXG5cdHdwZm9ybXNNb2Rlcm5GaWxlVXBsb2FkLmluaXQoKTtcblx0d2luZG93LndwZm9ybXNNb2Rlcm5GaWxlVXBsb2FkID0gd3Bmb3Jtc01vZGVybkZpbGVVcGxvYWQ7XG5cbn0oIGpRdWVyeSApICk7XG4iXSwibWFwcGluZ3MiOiJBQUFBO0FBQ0EsWUFBWTs7QUFBQyxTQUFBQSxRQUFBQyxDQUFBLHNDQUFBRCxPQUFBLHdCQUFBRSxNQUFBLHVCQUFBQSxNQUFBLENBQUFDLFFBQUEsYUFBQUYsQ0FBQSxrQkFBQUEsQ0FBQSxnQkFBQUEsQ0FBQSxXQUFBQSxDQUFBLHlCQUFBQyxNQUFBLElBQUFELENBQUEsQ0FBQUcsV0FBQSxLQUFBRixNQUFBLElBQUFELENBQUEsS0FBQUMsTUFBQSxDQUFBRyxTQUFBLHFCQUFBSixDQUFBLEtBQUFELE9BQUEsQ0FBQUMsQ0FBQTtBQUVYLFdBQVVLLENBQUMsRUFBRztFQUVmO0FBQ0Q7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0VBQ0MsSUFBSUMsTUFBTSxHQUFHLElBQUk7O0VBRWpCO0FBQ0Q7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0VBQ0MsSUFBSUMsZUFBZSxHQUFHLEVBQUU7O0VBRXhCO0FBQ0Q7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0VBQ0MsSUFBSUMsaUJBQWlCLEdBQUc7SUFDdkJDLE9BQU8sRUFBRSxJQUFJO0lBQUU7SUFDZkMsV0FBVyxFQUFFLEdBQUcsR0FBRyxJQUFJLENBQUU7RUFDMUIsQ0FBQzs7RUFFRDtBQUNEO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtFQUNDLFNBQVNDLFVBQVVBLENBQUEsRUFBRztJQUVyQixJQUFJQyxJQUFJLEdBQUcsRUFBRTtJQUViLEtBQU0sSUFBSUMsQ0FBQyxHQUFHLENBQUMsRUFBRUEsQ0FBQyxHQUFHTCxpQkFBaUIsQ0FBQ0UsV0FBVyxFQUFFLEVBQUVHLENBQUMsRUFBRztNQUN6REQsSUFBSSxJQUFJRSxNQUFNLENBQUNDLFlBQVksQ0FBRUMsSUFBSSxDQUFDQyxLQUFLLENBQUVELElBQUksQ0FBQ0UsTUFBTSxDQUFDLENBQUMsR0FBRyxFQUFFLEdBQUcsRUFBRyxDQUFFLENBQUM7SUFDckU7SUFFQSxPQUFPTixJQUFJO0VBQ1o7O0VBRUE7QUFDRDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0VBQ0MsU0FBU08sU0FBU0EsQ0FBRUMsSUFBSSxFQUFHO0lBRTFCLElBQUssSUFBSSxLQUFLZCxNQUFNLEVBQUc7TUFDdEJlLFVBQVUsQ0FBRUQsSUFBSyxDQUFDO01BQ2xCO0lBQ0Q7SUFFQSxJQUFJUixJQUFJLEdBQUlELFVBQVUsQ0FBQyxDQUFDO0lBQ3hCLElBQUlXLEtBQUssR0FBRyxJQUFJQyxJQUFJLENBQUQsQ0FBQztJQUVwQkMsRUFBRSxDQUFDQyxJQUFJLENBQUNDLElBQUksQ0FBRTtNQUNiQyxNQUFNLEVBQUUsZ0NBQWdDO01BQ3hDZixJQUFJLEVBQUVBO0lBQ1AsQ0FBRSxDQUFDLENBQUNnQixJQUFJLENBQUUsWUFBVztNQUVwQixJQUFJQyxLQUFLLEdBQUcsSUFBSU4sSUFBSSxDQUFELENBQUMsR0FBR0QsS0FBSztNQUU1QmhCLE1BQU0sR0FBR3VCLEtBQUssSUFBSXJCLGlCQUFpQixDQUFDQyxPQUFPO01BRTNDVyxJQUFJLENBQUMsQ0FBQztJQUNQLENBQUUsQ0FBQyxDQUFDVSxJQUFJLENBQUUsWUFBVztNQUVwQnhCLE1BQU0sR0FBRyxJQUFJO01BRWJjLElBQUksQ0FBQyxDQUFDO0lBQ1AsQ0FBRSxDQUFDO0VBQ0o7O0VBRUE7QUFDRDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0VBQ0MsU0FBU1csb0JBQW9CQSxDQUFFQyxLQUFLLEVBQUc7SUFFdEMsT0FBTyxZQUFXO01BRWpCLElBQUtBLEtBQUssQ0FBQ0MsSUFBSSxDQUFFLHNDQUF1QyxDQUFDLENBQUNDLE1BQU0sRUFBRztRQUNsRTtNQUNEO01BRUFGLEtBQUssQ0FBQ0MsSUFBSSxDQUFFLDJCQUE0QixDQUFDLENBQ3ZDRSxNQUFNLHlGQUFBQyxNQUFBLENBRUhDLE1BQU0sQ0FBQ0MsbUJBQW1CLENBQUNDLGVBQWUsdUJBRTlDLENBQUM7SUFDSCxDQUFDO0VBQ0Y7O0VBRUE7QUFDRDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0VBQ0MsU0FBU0MsZ0JBQWdCQSxDQUFFQyxFQUFFLEVBQUc7SUFFL0IsT0FBT0EsRUFBRSxDQUFDQyxPQUFPLEdBQUcsQ0FBQyxJQUFJRCxFQUFFLENBQUNFLGtCQUFrQixDQUFFLE9BQVEsQ0FBQyxDQUFDVCxNQUFNLEdBQUcsQ0FBQztFQUNyRTs7RUFFQTtBQUNEO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtFQUNDLFNBQVNVLG9CQUFvQkEsQ0FBQSxFQUFHO0lBRS9CLElBQUlBLG9CQUFvQixHQUFHLEtBQUs7SUFFaENQLE1BQU0sQ0FBQ1EsT0FBTyxDQUFDQyxTQUFTLENBQUNDLElBQUksQ0FBRSxVQUFVTixFQUFFLEVBQUc7TUFFN0MsSUFBS0QsZ0JBQWdCLENBQUVDLEVBQUcsQ0FBQyxFQUFHO1FBQzdCRyxvQkFBb0IsR0FBRyxJQUFJO1FBRTNCLE9BQU8sSUFBSTtNQUNaO0lBQ0QsQ0FBRSxDQUFDO0lBRUgsT0FBT0Esb0JBQW9CO0VBQzVCOztFQUVBO0FBQ0Q7QUFDQTtBQUNBO0FBQ0E7RUFDQyxTQUFTSSxtQkFBbUJBLENBQUVoQixLQUFLLEVBQUc7SUFFckM7SUFDQSxJQUFJaUIsSUFBSSxHQUFHakIsS0FBSyxDQUFDQyxJQUFJLENBQUUsaUJBQWtCLENBQUM7SUFDMUMsSUFBTWlCLFFBQVEsR0FBR2xCLEtBQUssQ0FBQ0MsSUFBSSxDQUFFLDRCQUE2QixDQUFDO0lBQzNELElBQU1rQixPQUFPLEdBQUdwQixvQkFBb0IsQ0FBRUMsS0FBTSxDQUFDLENBQUMsQ0FBQzs7SUFFL0M7SUFDQSxJQUFLQSxLQUFLLENBQUNDLElBQUksQ0FBRSx5QkFBMEIsQ0FBQyxDQUFDQyxNQUFNLEtBQUssQ0FBQyxJQUFJZ0IsUUFBUSxDQUFDaEIsTUFBTSxLQUFLLENBQUMsRUFBRztNQUNwRmUsSUFBSSxHQUFHQyxRQUFRO0lBQ2hCOztJQUVBO0lBQ0FELElBQUksQ0FBQ0csSUFBSSxDQUFFLFVBQVUsRUFBRSxJQUFLLENBQUM7SUFDN0JDLFlBQVksQ0FBQ0MsWUFBWSxDQUFFdEIsS0FBSyxFQUFFLGdDQUFnQyxFQUFFLENBQUVBLEtBQUssRUFBRWlCLElBQUksQ0FBRyxDQUFDOztJQUVyRjtJQUNBLElBQUssQ0FBRWpCLEtBQUssQ0FBQ0MsSUFBSSxDQUFFLHlCQUEwQixDQUFDLENBQUNDLE1BQU0sSUFBSWUsSUFBSSxDQUFDTSxJQUFJLENBQUUsTUFBTyxDQUFDLEtBQUssUUFBUSxFQUFHO01BRTNGO01BQ0FOLElBQUksQ0FBQ08sTUFBTSxDQUFDLENBQUMsQ0FBQ0MsUUFBUSxDQUFFLGtDQUFtQyxDQUFDO01BQzVEUixJQUFJLENBQUNPLE1BQU0sQ0FBQyxDQUFDLENBQUNFLE1BQU0sQ0FBRSw0Q0FBNkMsQ0FBQzs7TUFFcEU7TUFDQTFCLEtBQUssQ0FBQ0MsSUFBSSxDQUFFLHlCQUEwQixDQUFDLENBQUMwQixHQUFHLENBQUU7UUFDNUNDLEtBQUssS0FBQXhCLE1BQUEsQ0FBS2EsSUFBSSxDQUFDWSxVQUFVLENBQUMsQ0FBQyxPQUFJO1FBQy9CQyxNQUFNLEtBQUExQixNQUFBLENBQUthLElBQUksQ0FBQ08sTUFBTSxDQUFDLENBQUMsQ0FBQ08sV0FBVyxDQUFDLENBQUM7TUFDdkMsQ0FBRSxDQUFDOztNQUVIO01BQ0EvQixLQUFLLENBQUNDLElBQUksQ0FBRSx5QkFBMEIsQ0FBQyxDQUFDK0IsRUFBRSxDQUFFLE9BQU8sRUFBRWIsT0FBUSxDQUFDO0lBQy9EO0VBQ0Q7O0VBRUE7QUFDRDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7RUFDQyxTQUFTYyxZQUFZQSxDQUFFeEIsRUFBRSxFQUFHO0lBQUU7O0lBRTdCLElBQUlULEtBQUssR0FBR2tDLE1BQU0sQ0FBRXpCLEVBQUUsQ0FBQzBCLE9BQVEsQ0FBQyxDQUFDQyxPQUFPLENBQUUsTUFBTyxDQUFDO01BQ2pEbkIsSUFBSSxHQUFHakIsS0FBSyxDQUFDQyxJQUFJLENBQUUsaUJBQWtCLENBQUM7TUFDdENpQixRQUFRLEdBQUdsQixLQUFLLENBQUNDLElBQUksQ0FBRSw0QkFBNkIsQ0FBQztNQUNyRGtCLE9BQU8sR0FBR3BCLG9CQUFvQixDQUFFQyxLQUFNLENBQUM7TUFDdkNxQyxRQUFRLEdBQUc3QixnQkFBZ0IsQ0FBRUMsRUFBRyxDQUFDOztJQUVsQztJQUNBLElBQUtULEtBQUssQ0FBQ0MsSUFBSSxDQUFFLHlCQUEwQixDQUFDLENBQUNDLE1BQU0sS0FBSyxDQUFDLElBQUlnQixRQUFRLENBQUNoQixNQUFNLEtBQUssQ0FBQyxFQUFHO01BQ3BGZSxJQUFJLEdBQUdDLFFBQVE7SUFDaEI7SUFFQSxJQUFNb0IsZ0JBQWdCLEdBQUdDLE9BQU8sQ0FBRXRCLElBQUksQ0FBQ0csSUFBSSxDQUFFLFVBQVcsQ0FBRSxDQUFDLElBQUlILElBQUksQ0FBQ3VCLFFBQVEsQ0FBRSxrQkFBbUIsQ0FBQztJQUVsRyxJQUFLSCxRQUFRLEtBQUtDLGdCQUFnQixFQUFHO01BQ3BDO0lBQ0Q7SUFFQSxJQUFLRCxRQUFRLEVBQUc7TUFDZnJCLG1CQUFtQixDQUFFaEIsS0FBTSxDQUFDO01BQzVCO0lBQ0Q7SUFFQSxJQUFLWSxvQkFBb0IsQ0FBQyxDQUFDLEVBQUc7TUFDN0I7SUFDRDtJQUVBSyxJQUFJLENBQUNHLElBQUksQ0FBRSxVQUFVLEVBQUUsS0FBTSxDQUFDO0lBQzlCQyxZQUFZLENBQUNDLFlBQVksQ0FBRXRCLEtBQUssRUFBRSxnQ0FBZ0MsRUFBRSxDQUFFQSxLQUFLLEVBQUVpQixJQUFJLENBQUcsQ0FBQztJQUNyRmpCLEtBQUssQ0FBQ0MsSUFBSSxDQUFFLHlCQUEwQixDQUFDLENBQUN3QyxHQUFHLENBQUUsT0FBTyxFQUFFdEIsT0FBUSxDQUFDO0lBQy9EbkIsS0FBSyxDQUFDQyxJQUFJLENBQUUseUJBQTBCLENBQUMsQ0FBQ3lDLE1BQU0sQ0FBQyxDQUFDO0lBQ2hEekIsSUFBSSxDQUFDTyxNQUFNLENBQUMsQ0FBQyxDQUFDbUIsV0FBVyxDQUFFLGtDQUFtQyxDQUFDO0lBQy9ELElBQUszQyxLQUFLLENBQUNDLElBQUksQ0FBRSxzQ0FBdUMsQ0FBQyxDQUFDQyxNQUFNLEVBQUc7TUFDbEVGLEtBQUssQ0FBQ0MsSUFBSSxDQUFFLHNDQUF1QyxDQUFDLENBQUN5QyxNQUFNLENBQUMsQ0FBQztJQUM5RDtFQUNEOztFQUVBO0FBQ0Q7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtFQUNDLFNBQVNFLFNBQVNBLENBQUVDLEdBQUcsRUFBRztJQUN6QixJQUFJO01BQ0gsT0FBT0MsSUFBSSxDQUFDQyxLQUFLLENBQUVGLEdBQUksQ0FBQztJQUN6QixDQUFDLENBQUMsT0FBUUcsQ0FBQyxFQUFHO01BQ2IsT0FBTyxLQUFLO0lBQ2I7RUFDRDs7RUFFQTtBQUNEO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7RUFDQyxTQUFTQyxjQUFjQSxDQUFFQyxFQUFFLEVBQUc7SUFDN0IsT0FBT0EsRUFBRSxDQUFDaEQsTUFBTSxHQUFHLENBQUM7RUFDckI7O0VBRUE7QUFDRDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0VBQ0MsU0FBU2lELFlBQVlBLENBQUVELEVBQUUsRUFBRztJQUMzQixPQUFPQSxFQUFFO0VBQ1Y7O0VBRUE7QUFDRDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0VBQ0MsU0FBU0UsTUFBTUEsQ0FBRUYsRUFBRSxFQUFHO0lBQ3JCLE9BQU9BLEVBQUUsQ0FBQ0csYUFBYSxJQUFJSCxFQUFFLENBQUNJLEdBQUc7RUFDbEM7O0VBRUE7QUFDRDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0VBQ0MsU0FBU0MsZUFBZUEsQ0FBRUwsRUFBRSxFQUFHO0lBQzlCLE9BQU8sT0FBT0EsRUFBRSxLQUFLLFFBQVEsR0FBR0EsRUFBRSxHQUFHQSxFQUFFLENBQUNNLFlBQVk7RUFDckQ7O0VBRUE7QUFDRDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0VBQ0MsU0FBU0MsT0FBT0EsQ0FBRVAsRUFBRSxFQUFHO0lBQ3RCLE9BQU9BLEVBQUUsQ0FBQ3RFLElBQUk7RUFDZjs7RUFFQTtBQUNEO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7RUFDQyxTQUFTOEUsUUFBUUEsQ0FBRUMsS0FBSyxFQUFHO0lBQzFCLE9BQU9BLEtBQUssQ0FDVkMsR0FBRyxDQUFFUixNQUFPLENBQUMsQ0FDYlMsTUFBTSxDQUFFVixZQUFhLENBQUMsQ0FDdEJTLEdBQUcsQ0FBRUwsZUFBZ0IsQ0FBQyxDQUN0Qk0sTUFBTSxDQUFFWixjQUFlLENBQUMsQ0FDeEJXLEdBQUcsQ0FBRWhCLFNBQVUsQ0FBQyxDQUNoQmlCLE1BQU0sQ0FBRVYsWUFBYSxDQUFDLENBQ3RCUyxHQUFHLENBQUVILE9BQVEsQ0FBQztFQUNqQjs7RUFFQTtBQUNEO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0VBQ0MsU0FBU0ssT0FBT0EsQ0FBRXJELEVBQUUsRUFBRTdCLElBQUksRUFBRztJQUU1QixPQUFPLFVBQVVtRixJQUFJLEVBQUVULEdBQUcsRUFBRVUsUUFBUSxFQUFHO01BRXRDO0FBQ0g7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7TUFDRyxJQUFLRCxJQUFJLENBQUNFLElBQUksR0FBRyxJQUFJLENBQUNDLFlBQVksQ0FBQ0MsV0FBVyxFQUFHO1FBQ2hEYixHQUFHLENBQUNjLElBQUksR0FBRyxZQUFXLENBQUMsQ0FBQztRQUV4QkwsSUFBSSxDQUFDTSxRQUFRLEdBQUcsS0FBSztRQUNyQk4sSUFBSSxDQUFDTyxVQUFVLEdBQUcsS0FBSztRQUN2QlAsSUFBSSxDQUFDUSxNQUFNLEdBQUcsVUFBVTtRQUN4QlIsSUFBSSxDQUFDUyxjQUFjLENBQUNDLFNBQVMsQ0FBQ0MsR0FBRyxDQUFFLFVBQVcsQ0FBQztRQUMvQ1gsSUFBSSxDQUFDUyxjQUFjLENBQUNDLFNBQVMsQ0FBQ0MsR0FBRyxDQUFFLGFBQWMsQ0FBQztRQUVsRDtNQUNEO01BRUFDLE1BQU0sQ0FBQ0MsSUFBSSxDQUFFaEcsSUFBSyxDQUFDLENBQUNpRyxPQUFPLENBQUUsVUFBVUMsR0FBRyxFQUFHO1FBQzVDZCxRQUFRLENBQUN0QyxNQUFNLENBQUVvRCxHQUFHLEVBQUVsRyxJQUFJLENBQUNrRyxHQUFHLENBQUUsQ0FBQztNQUNsQyxDQUFFLENBQUM7SUFDSixDQUFDO0VBQ0Y7O0VBRUE7QUFDRDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtFQUNDLFNBQVNDLG1CQUFtQkEsQ0FBRXBCLEtBQUssRUFBRWxELEVBQUUsRUFBRztJQUV6QyxJQUFLLENBQUVsQyxlQUFlLENBQUVrQyxFQUFFLENBQUN5RCxZQUFZLENBQUNjLE1BQU0sQ0FBRSxJQUFJLENBQUV6RyxlQUFlLENBQUVrQyxFQUFFLENBQUN5RCxZQUFZLENBQUNjLE1BQU0sQ0FBRSxDQUFFdkUsRUFBRSxDQUFDeUQsWUFBWSxDQUFDZSxPQUFPLENBQUUsRUFBRztNQUM1SCxPQUFPdEIsS0FBSyxDQUFDekQsTUFBTSxHQUFHNEMsSUFBSSxDQUFDb0MsU0FBUyxDQUFFdkIsS0FBTSxDQUFDLEdBQUcsRUFBRTtJQUNuRDtJQUVBQSxLQUFLLENBQUN3QixJQUFJLENBQUNDLEtBQUssQ0FBRXpCLEtBQUssRUFBRXBGLGVBQWUsQ0FBRWtDLEVBQUUsQ0FBQ3lELFlBQVksQ0FBQ2MsTUFBTSxDQUFFLENBQUV2RSxFQUFFLENBQUN5RCxZQUFZLENBQUNlLE9BQU8sQ0FBRyxDQUFDO0lBRS9GLE9BQU9uQyxJQUFJLENBQUNvQyxTQUFTLENBQUV2QixLQUFNLENBQUM7RUFDL0I7O0VBRUE7QUFDRDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0VBQ0MsU0FBUzBCLFFBQVFBLENBQUU1RSxFQUFFLEVBQUc7SUFFdkIsT0FBT3lCLE1BQU0sQ0FBRXpCLEVBQUUsQ0FBQzBCLE9BQVEsQ0FBQyxDQUFDbUQsT0FBTyxDQUFFLDRCQUE2QixDQUFDLENBQUNyRixJQUFJLENBQUUsYUFBYSxHQUFHUSxFQUFFLENBQUN5RCxZQUFZLENBQUNxQixJQUFJLEdBQUcsR0FBSSxDQUFDO0VBQ3ZIOztFQUVBO0FBQ0Q7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0VBQ0MsU0FBU0MsZ0JBQWdCQSxDQUFFL0UsRUFBRSxFQUFHO0lBRS9CLElBQUlnRixNQUFNLEdBQUdKLFFBQVEsQ0FBRTVFLEVBQUcsQ0FBQztJQUUzQmdGLE1BQU0sQ0FBQ0MsR0FBRyxDQUFFWCxtQkFBbUIsQ0FBRXJCLFFBQVEsQ0FBRWpELEVBQUUsQ0FBQ2tELEtBQU0sQ0FBQyxFQUFFbEQsRUFBRyxDQUFFLENBQUMsQ0FBQ2tGLE9BQU8sQ0FBRSxPQUFRLENBQUM7SUFFaEYsSUFBSyxPQUFPekQsTUFBTSxDQUFDMEQsRUFBRSxDQUFDQyxLQUFLLEtBQUssV0FBVyxFQUFHO01BQzdDSixNQUFNLENBQUNJLEtBQUssQ0FBQyxDQUFDO0lBQ2Y7RUFDRDs7RUFFQTtBQUNEO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0VBQ0MsU0FBU0MsUUFBUUEsQ0FBRXJGLEVBQUUsRUFBRztJQUV2QixPQUFPLFlBQVc7TUFDakJBLEVBQUUsQ0FBQ0MsT0FBTyxHQUFHRCxFQUFFLENBQUNDLE9BQU8sSUFBSSxDQUFDO01BQzVCRCxFQUFFLENBQUNDLE9BQU8sRUFBRTtNQUNaRCxFQUFFLENBQUNDLE9BQU8sR0FBRzFCLElBQUksQ0FBQytHLEdBQUcsQ0FBRXRGLEVBQUUsQ0FBQ0MsT0FBTyxHQUFHLENBQUMsRUFBRSxDQUFFLENBQUM7TUFDMUN1QixZQUFZLENBQUV4QixFQUFHLENBQUM7TUFDbEIrRSxnQkFBZ0IsQ0FBRS9FLEVBQUcsQ0FBQztJQUN2QixDQUFDO0VBQ0Y7O0VBRUE7QUFDRDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtFQUNDLFNBQVN1RixlQUFlQSxDQUFFakMsSUFBSSxFQUFFa0MsWUFBWSxFQUFHO0lBRTlDLElBQUtsQyxJQUFJLENBQUNtQywyQkFBMkIsRUFBRztNQUN2QztJQUNEO0lBRUEsSUFBSUMsSUFBSSxHQUFHQyxRQUFRLENBQUNDLGFBQWEsQ0FBRSxNQUFPLENBQUM7SUFDM0NGLElBQUksQ0FBQ0csU0FBUyxHQUFHTCxZQUFZLENBQUNNLFFBQVEsQ0FBQyxDQUFDO0lBQ3hDSixJQUFJLENBQUNLLFlBQVksQ0FBRSxzQkFBc0IsRUFBRSxFQUFHLENBQUM7SUFFL0N6QyxJQUFJLENBQUNTLGNBQWMsQ0FBQ2lDLGFBQWEsQ0FBRSxtQkFBb0IsQ0FBQyxDQUFDQyxXQUFXLENBQUVQLElBQUssQ0FBQztFQUM3RTs7RUFFQTtBQUNEO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7RUFDQyxTQUFTUSx5QkFBeUJBLENBQUVsRyxFQUFFLEVBQUc7SUFFeEMsT0FBTyxTQUFTbUcsT0FBT0EsQ0FBRTdDLElBQUksRUFBRztNQUUvQixJQUFLLENBQUVBLElBQUksQ0FBQzhDLE9BQU8sRUFBRztRQUNyQjlDLElBQUksQ0FBQzhDLE9BQU8sR0FBRyxDQUFDO01BQ2pCO01BRUEsSUFBSyxPQUFPLEtBQUs5QyxJQUFJLENBQUNRLE1BQU0sRUFBRztRQUM5QjtNQUNEOztNQUVBO0FBQ0g7QUFDQTtBQUNBO0FBQ0E7TUFDRyxTQUFTdUMsS0FBS0EsQ0FBQSxFQUFHO1FBQ2hCL0MsSUFBSSxDQUFDOEMsT0FBTyxFQUFFO1FBRWQsSUFBSzlDLElBQUksQ0FBQzhDLE9BQU8sS0FBSyxDQUFDLEVBQUc7VUFDekJiLGVBQWUsQ0FBRWpDLElBQUksRUFBRTFELE1BQU0sQ0FBQ0MsbUJBQW1CLENBQUN5RyxNQUFNLENBQUNDLGlCQUFrQixDQUFDO1VBQzVFO1FBQ0Q7UUFFQTNILFVBQVUsQ0FBRSxZQUFXO1VBQ3RCdUgsT0FBTyxDQUFFN0MsSUFBSyxDQUFDO1FBQ2hCLENBQUMsRUFBRSxJQUFJLEdBQUdBLElBQUksQ0FBQzhDLE9BQVEsQ0FBQztNQUN6Qjs7TUFFQTtBQUNIO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtNQUNHLFNBQVMvRyxJQUFJQSxDQUFFbUgsUUFBUSxFQUFHO1FBRXpCLElBQUlDLGdCQUFnQixHQUFHRCxRQUFRLENBQUNFLFlBQVksSUFDdENGLFFBQVEsQ0FBQ0UsWUFBWSxDQUFDQyxPQUFPLEtBQUssS0FBSyxJQUN2Q0gsUUFBUSxDQUFDRSxZQUFZLENBQUN2SSxJQUFJO1FBRWhDLElBQUtzSSxnQkFBZ0IsRUFBRztVQUN2QmxCLGVBQWUsQ0FBRWpDLElBQUksRUFBRWtELFFBQVEsQ0FBQ0UsWUFBWSxDQUFDdkksSUFBSyxDQUFDO1FBQ3BELENBQUMsTUFBTTtVQUNOa0ksS0FBSyxDQUFDLENBQUM7UUFDUjtNQUNEOztNQUVBO0FBQ0g7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO01BQ0csU0FBU2hCLFFBQVFBLENBQUVtQixRQUFRLEVBQUc7UUFFN0JsRCxJQUFJLENBQUNWLGFBQWEsR0FBR1AsSUFBSSxDQUFDb0MsU0FBUyxDQUFFO1VBQUV0RyxJQUFJLEVBQUVxSTtRQUFTLENBQUUsQ0FBQztRQUN6RHhHLEVBQUUsQ0FBQ0MsT0FBTyxHQUFHRCxFQUFFLENBQUNDLE9BQU8sSUFBSSxDQUFDO1FBQzVCRCxFQUFFLENBQUNDLE9BQU8sRUFBRTtRQUNaRCxFQUFFLENBQUNDLE9BQU8sR0FBRzFCLElBQUksQ0FBQytHLEdBQUcsQ0FBRXRGLEVBQUUsQ0FBQ0MsT0FBTyxFQUFFLENBQUUsQ0FBQztRQUV0Q3VCLFlBQVksQ0FBRXhCLEVBQUcsQ0FBQztRQUNsQitFLGdCQUFnQixDQUFFL0UsRUFBRyxDQUFDO01BQ3ZCO01BRUFqQixFQUFFLENBQUNDLElBQUksQ0FBQ0MsSUFBSSxDQUFFd0MsTUFBTSxDQUFDbUYsTUFBTSxDQUMxQjtRQUNDMUgsTUFBTSxFQUFFLDhCQUE4QjtRQUN0QzJILE9BQU8sRUFBRTdHLEVBQUUsQ0FBQ3lELFlBQVksQ0FBQ2MsTUFBTTtRQUMvQnVDLFFBQVEsRUFBRTlHLEVBQUUsQ0FBQ3lELFlBQVksQ0FBQ2UsT0FBTztRQUNqQ00sSUFBSSxFQUFFeEIsSUFBSSxDQUFDd0I7TUFDWixDQUFDLEVBQ0Q5RSxFQUFFLENBQUMrRyxPQUFPLENBQUNDLE1BQU0sQ0FBQ0MsSUFBSSxDQUFFakgsRUFBRSxFQUFFLElBQUksRUFBRSxJQUFJLEVBQUU7UUFBQ3NELElBQUksRUFBRUEsSUFBSTtRQUFFNEQsS0FBSyxFQUFFO01BQUMsQ0FBRSxDQUNoRSxDQUFFLENBQUMsQ0FBQy9ILElBQUksQ0FBRWtHLFFBQVMsQ0FBQyxDQUFDaEcsSUFBSSxDQUFFQSxJQUFLLENBQUM7O01BRWpDO01BQ0FXLEVBQUUsQ0FBQ21ILFlBQVksQ0FBQyxDQUFDO0lBQ2xCLENBQUM7RUFDRjs7RUFFQTtBQUNEO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtFQUNDLFNBQVNDLGFBQWFBLENBQUVwSCxFQUFFLEVBQUc7SUFFNUJwQixVQUFVLENBQUUsWUFBVztNQUN0QixJQUFJeUksVUFBVSxHQUFHckgsRUFBRSxDQUFDa0QsS0FBSyxDQUFDRSxNQUFNLENBQUUsVUFBVUUsSUFBSSxFQUFHO1FBQ2xELE9BQU9BLElBQUksQ0FBQ00sUUFBUTtNQUNyQixDQUFFLENBQUM7TUFFSCxJQUFLeUQsVUFBVSxDQUFDNUgsTUFBTSxJQUFJTyxFQUFFLENBQUMrRyxPQUFPLENBQUNPLFFBQVEsRUFBRztRQUMvQ3RILEVBQUUsQ0FBQzBCLE9BQU8sQ0FBQ3NFLGFBQWEsQ0FBRSxhQUFjLENBQUMsQ0FBQ2hDLFNBQVMsQ0FBQ0MsR0FBRyxDQUFFLE1BQU8sQ0FBQztNQUNsRSxDQUFDLE1BQU07UUFDTmpFLEVBQUUsQ0FBQzBCLE9BQU8sQ0FBQ3NFLGFBQWEsQ0FBRSxhQUFjLENBQUMsQ0FBQ2hDLFNBQVMsQ0FBQy9CLE1BQU0sQ0FBRSxNQUFPLENBQUM7TUFDckU7SUFDRCxDQUFDLEVBQUUsQ0FBRSxDQUFDO0VBQ1A7O0VBRUE7QUFDRDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0VBQ0MsU0FBU3NGLHdCQUF3QkEsQ0FBRWpFLElBQUksRUFBRXRELEVBQUUsRUFBRztJQUU3Q3BCLFVBQVUsQ0FBRSxZQUFXO01BQ3RCLElBQUswRSxJQUFJLENBQUNFLElBQUksSUFBSXhELEVBQUUsQ0FBQ3lELFlBQVksQ0FBQ0MsV0FBVyxFQUFHO1FBQy9DLElBQUk4QixZQUFZLEdBQUc1RixNQUFNLENBQUNDLG1CQUFtQixDQUFDeUcsTUFBTSxDQUFDa0IsYUFBYTtRQUNsRSxJQUFLLENBQUVsRSxJQUFJLENBQUNtQywyQkFBMkIsRUFBRztVQUN6Q25DLElBQUksQ0FBQ21DLDJCQUEyQixHQUFHLElBQUk7VUFDdkNELFlBQVksR0FBRzVGLE1BQU0sQ0FBQ0MsbUJBQW1CLENBQUN5RyxNQUFNLENBQUNDLGlCQUFpQixHQUFHLEdBQUcsR0FBR2YsWUFBWTtVQUN2RkQsZUFBZSxDQUFFakMsSUFBSSxFQUFFa0MsWUFBYSxDQUFDO1FBQ3RDO01BQ0Q7SUFDRCxDQUFDLEVBQUUsQ0FBRSxDQUFDO0VBQ1A7O0VBRUE7QUFDRDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtFQUNDLFNBQVNpQyxjQUFjQSxDQUFFekgsRUFBRSxFQUFFc0QsSUFBSSxFQUFHO0lBRW5DdkUsRUFBRSxDQUFDQyxJQUFJLENBQUNDLElBQUksQ0FBRXdDLE1BQU0sQ0FBQ21GLE1BQU0sQ0FDMUI7TUFDQzFILE1BQU0sRUFBRywyQkFBMkI7TUFDcEMySCxPQUFPLEVBQUU3RyxFQUFFLENBQUN5RCxZQUFZLENBQUNjLE1BQU07TUFDL0J1QyxRQUFRLEVBQUU5RyxFQUFFLENBQUN5RCxZQUFZLENBQUNlLE9BQU87TUFDakNNLElBQUksRUFBRXhCLElBQUksQ0FBQ3dCLElBQUk7TUFDZjRDLElBQUksRUFBRTdKO0lBQ1AsQ0FBQyxFQUNEbUMsRUFBRSxDQUFDK0csT0FBTyxDQUFDQyxNQUFNLENBQUNDLElBQUksQ0FBRWpILEVBQUUsRUFBRSxJQUFJLEVBQUUsSUFBSSxFQUFFO01BQUNzRCxJQUFJLEVBQUVBLElBQUk7TUFBRTRELEtBQUssRUFBRTtJQUFDLENBQUUsQ0FDaEUsQ0FBRSxDQUFDLENBQUMvSCxJQUFJLENBQUUsVUFBVXFILFFBQVEsRUFBRztNQUU5Qjs7TUFFQSxLQUFNLElBQUluQyxHQUFHLElBQUltQyxRQUFRLEVBQUc7UUFDM0J4RyxFQUFFLENBQUMrRyxPQUFPLENBQUUxQyxHQUFHLENBQUUsR0FBR21DLFFBQVEsQ0FBRW5DLEdBQUcsQ0FBRTtNQUNwQztNQUVBLElBQUttQyxRQUFRLENBQUNtQixXQUFXLEVBQUc7UUFDM0IzSCxFQUFFLENBQUMrRyxPQUFPLENBQUNhLFNBQVMsR0FBR0MsUUFBUSxDQUFFckIsUUFBUSxDQUFDbUIsV0FBVyxFQUFFLEVBQUcsQ0FBQztRQUMzRHJFLElBQUksQ0FBQ3dFLE1BQU0sQ0FBQ0MsZUFBZSxHQUFHeEosSUFBSSxDQUFDeUosSUFBSSxDQUFFMUUsSUFBSSxDQUFDRSxJQUFJLEdBQUd4RCxFQUFFLENBQUMrRyxPQUFPLENBQUNhLFNBQVUsQ0FBQztNQUM1RTtNQUVBNUgsRUFBRSxDQUFDbUgsWUFBWSxDQUFDLENBQUM7SUFDbEIsQ0FBRSxDQUFDLENBQUM5SCxJQUFJLENBQUUsVUFBVW1ILFFBQVEsRUFBRztNQUU5QmxELElBQUksQ0FBQ1EsTUFBTSxHQUFHLE9BQU87TUFFckIsSUFBSyxDQUFFUixJQUFJLENBQUNULEdBQUcsRUFBRztRQUNqQixJQUFNb0YsS0FBSyxHQUFHakksRUFBRSxDQUFDMEIsT0FBTyxDQUFDQyxPQUFPLENBQUUsZ0JBQWlCLENBQUM7UUFDcEQsSUFBTXVHLFdBQVcsR0FBR0QsS0FBSyxDQUFDakMsYUFBYSxDQUFFLGlCQUFrQixDQUFDO1FBQzVELElBQU1SLFlBQVksR0FBRzVGLE1BQU0sQ0FBQ0MsbUJBQW1CLENBQUN5RyxNQUFNLENBQUNDLGlCQUFpQixHQUFHLEdBQUcsR0FBRzNHLE1BQU0sQ0FBQ0MsbUJBQW1CLENBQUN5RyxNQUFNLENBQUM2QixhQUFhO1FBRWhJN0UsSUFBSSxDQUFDUyxjQUFjLENBQUNDLFNBQVMsQ0FBQ0MsR0FBRyxDQUFFLGVBQWUsRUFBRSxVQUFVLEVBQUUsYUFBYyxDQUFDO1FBQy9FaUUsV0FBVyxDQUFDbEUsU0FBUyxDQUFDQyxHQUFHLENBQUUsZUFBZ0IsQ0FBQztRQUM1Q2dFLEtBQUssQ0FBQ2pFLFNBQVMsQ0FBQ0MsR0FBRyxDQUFFLG1CQUFvQixDQUFDO1FBQzFDc0IsZUFBZSxDQUFFakMsSUFBSSxFQUFFa0MsWUFBYSxDQUFDO01BQ3RDO01BRUF4RixFQUFFLENBQUNtSCxZQUFZLENBQUMsQ0FBQztJQUNsQixDQUFFLENBQUM7RUFDSjs7RUFFQTtBQUNEO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7RUFDQyxTQUFTaUIsU0FBU0EsQ0FBRXBJLEVBQUUsRUFBRztJQUV4QixPQUFPLFVBQVVzRCxJQUFJLEVBQUc7TUFFdkIsSUFBS0EsSUFBSSxDQUFDRSxJQUFJLElBQUl4RCxFQUFFLENBQUN5RCxZQUFZLENBQUNDLFdBQVcsRUFBRztRQUMvQzZELHdCQUF3QixDQUFFakUsSUFBSSxFQUFFdEQsRUFBRyxDQUFDO01BQ3JDLENBQUMsTUFBTTtRQUNOdEIsU0FBUyxDQUFFLFlBQVc7VUFDckIrSSxjQUFjLENBQUV6SCxFQUFFLEVBQUVzRCxJQUFLLENBQUM7UUFDM0IsQ0FBRSxDQUFDO01BQ0o7TUFFQXRELEVBQUUsQ0FBQ0MsT0FBTyxHQUFHRCxFQUFFLENBQUNDLE9BQU8sSUFBSSxDQUFDO01BQzVCRCxFQUFFLENBQUNDLE9BQU8sRUFBRTtNQUNadUIsWUFBWSxDQUFFeEIsRUFBRyxDQUFDO01BRWxCb0gsYUFBYSxDQUFFcEgsRUFBRyxDQUFDO0lBQ3BCLENBQUM7RUFDRjs7RUFFQTtBQUNEO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0VBQ0MsU0FBU3FJLGdCQUFnQkEsQ0FBRS9FLElBQUksRUFBRXRELEVBQUUsRUFBRztJQUVyQ2pCLEVBQUUsQ0FBQ0MsSUFBSSxDQUFDQyxJQUFJLENBQUU7TUFDYkMsTUFBTSxFQUFFLHFCQUFxQjtNQUM3Qm9FLElBQUksRUFBRUEsSUFBSTtNQUNWdUQsT0FBTyxFQUFFN0csRUFBRSxDQUFDeUQsWUFBWSxDQUFDYyxNQUFNO01BQy9CdUMsUUFBUSxFQUFFOUcsRUFBRSxDQUFDeUQsWUFBWSxDQUFDZTtJQUMzQixDQUFFLENBQUM7RUFDSjs7RUFFQTtBQUNEO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7RUFDQyxTQUFTOEQsV0FBV0EsQ0FBRXRJLEVBQUUsRUFBRztJQUUxQixPQUFPLFVBQVVzRCxJQUFJLEVBQUc7TUFDdkI4RCxhQUFhLENBQUVwSCxFQUFHLENBQUM7TUFFbkIsSUFBSXVJLElBQUksR0FBR2pGLElBQUksQ0FBQ1YsYUFBYSxJQUFJLENBQUVVLElBQUksQ0FBQ1QsR0FBRyxJQUFJLENBQUMsQ0FBQyxFQUFHRSxZQUFZO01BRWhFLElBQUt3RixJQUFJLEVBQUc7UUFDWCxJQUFJQyxNQUFNLEdBQUdyRyxTQUFTLENBQUVvRyxJQUFLLENBQUM7UUFFOUIsSUFBS0MsTUFBTSxJQUFJQSxNQUFNLENBQUNySyxJQUFJLElBQUlxSyxNQUFNLENBQUNySyxJQUFJLENBQUNtRixJQUFJLEVBQUc7VUFDaEQrRSxnQkFBZ0IsQ0FBRUcsTUFBTSxDQUFDckssSUFBSSxDQUFDbUYsSUFBSSxFQUFFdEQsRUFBRyxDQUFDO1FBQ3pDO01BQ0Q7O01BRUE7TUFDQSxJQUFLa0UsTUFBTSxDQUFDdkcsU0FBUyxDQUFDOEssY0FBYyxDQUFDeEIsSUFBSSxDQUFFM0QsSUFBSSxFQUFFLFdBQVksQ0FBQyxJQUFJQSxJQUFJLENBQUNvRixTQUFTLEVBQUc7UUFDbEY1SyxlQUFlLENBQUVrQyxFQUFFLENBQUN5RCxZQUFZLENBQUNjLE1BQU0sQ0FBRSxDQUFFdkUsRUFBRSxDQUFDeUQsWUFBWSxDQUFDZSxPQUFPLENBQUUsQ0FBQ21FLE1BQU0sQ0FBRXJGLElBQUksQ0FBQzRELEtBQUssRUFBRSxDQUFFLENBQUM7UUFDNUZsSCxFQUFFLENBQUMrRyxPQUFPLENBQUNPLFFBQVEsRUFBRTtRQUNyQmUsZ0JBQWdCLENBQUUvRSxJQUFJLENBQUNBLElBQUksRUFBRXRELEVBQUcsQ0FBQztNQUNsQztNQUVBK0UsZ0JBQWdCLENBQUUvRSxFQUFHLENBQUM7TUFFdEJBLEVBQUUsQ0FBQ0MsT0FBTyxHQUFHRCxFQUFFLENBQUNDLE9BQU8sSUFBSSxDQUFDO01BQzVCRCxFQUFFLENBQUNDLE9BQU8sRUFBRTtNQUNaRCxFQUFFLENBQUNDLE9BQU8sR0FBRzFCLElBQUksQ0FBQytHLEdBQUcsQ0FBRXRGLEVBQUUsQ0FBQ0MsT0FBTyxFQUFFLENBQUUsQ0FBQztNQUV0Q3VCLFlBQVksQ0FBRXhCLEVBQUcsQ0FBQztNQUVsQixJQUFNNEksU0FBUyxHQUFHNUksRUFBRSxDQUFDMEIsT0FBTyxDQUFDbUgsZ0JBQWdCLENBQUUsc0JBQXVCLENBQUMsQ0FBQ3BKLE1BQU07TUFFOUUsSUFBS21KLFNBQVMsS0FBSyxDQUFDLEVBQUc7UUFDdEI1SSxFQUFFLENBQUMwQixPQUFPLENBQUNzQyxTQUFTLENBQUMvQixNQUFNLENBQUUsZUFBZ0IsQ0FBQztRQUM5Q2pDLEVBQUUsQ0FBQzBCLE9BQU8sQ0FBQ0MsT0FBTyxDQUFFLGdCQUFpQixDQUFDLENBQUNxQyxTQUFTLENBQUMvQixNQUFNLENBQUUsbUJBQW9CLENBQUM7TUFDL0U7SUFDRCxDQUFDO0VBQ0Y7O0VBRUE7QUFDRDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7RUFDQyxTQUFTNkcsS0FBS0EsQ0FBRTlJLEVBQUUsRUFBRztJQUVwQixPQUFPLFVBQVVzRCxJQUFJLEVBQUVrQyxZQUFZLEVBQUc7TUFFckMsSUFBS2xDLElBQUksQ0FBQ21DLDJCQUEyQixFQUFHO1FBQ3ZDO01BQ0Q7TUFFQSxJQUFLbkksT0FBQSxDQUFPa0ksWUFBWSxNQUFLLFFBQVEsRUFBRztRQUN2Q0EsWUFBWSxHQUFHdEIsTUFBTSxDQUFDdkcsU0FBUyxDQUFDOEssY0FBYyxDQUFDeEIsSUFBSSxDQUFFekIsWUFBWSxFQUFFLE1BQU8sQ0FBQyxJQUFJLE9BQU9BLFlBQVksQ0FBQ3JILElBQUksS0FBSyxRQUFRLEdBQUdxSCxZQUFZLENBQUNySCxJQUFJLEdBQUcsRUFBRTtNQUM5STtNQUVBcUgsWUFBWSxHQUFHQSxZQUFZLEtBQUssR0FBRyxHQUFHQSxZQUFZLEdBQUcsRUFBRTtNQUV2RGxDLElBQUksQ0FBQ21DLDJCQUEyQixHQUFHLElBQUk7TUFDdkNuQyxJQUFJLENBQUNTLGNBQWMsQ0FBQzhFLGdCQUFnQixDQUFFLHdCQUF5QixDQUFDLENBQUMsQ0FBQyxDQUFDLENBQUNFLFdBQVcsR0FBR25KLE1BQU0sQ0FBQ0MsbUJBQW1CLENBQUN5RyxNQUFNLENBQUNDLGlCQUFpQixHQUFHLEdBQUcsR0FBR2YsWUFBWTtNQUMxSnhGLEVBQUUsQ0FBQzBCLE9BQU8sQ0FBQ3NDLFNBQVMsQ0FBQ0MsR0FBRyxDQUFFLGVBQWdCLENBQUM7TUFDM0NqRSxFQUFFLENBQUMwQixPQUFPLENBQUNDLE9BQU8sQ0FBRSxnQkFBaUIsQ0FBQyxDQUFDcUMsU0FBUyxDQUFDQyxHQUFHLENBQUUsbUJBQW9CLENBQUM7SUFDNUUsQ0FBQztFQUNGOztFQUVBO0FBQ0Q7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0VBQ0MsU0FBUytFLG1CQUFtQkEsQ0FBRWhKLEVBQUUsRUFBRztJQUVsQyxJQUFJa0QsS0FBSyxHQUFHZixTQUFTLENBQUV5QyxRQUFRLENBQUU1RSxFQUFHLENBQUMsQ0FBQ2lGLEdBQUcsQ0FBQyxDQUFFLENBQUM7SUFFN0MsSUFBSyxDQUFFL0IsS0FBSyxJQUFJLENBQUVBLEtBQUssQ0FBQ3pELE1BQU0sRUFBRztNQUNoQztJQUNEO0lBRUEzQixlQUFlLENBQUNrQyxFQUFFLENBQUN5RCxZQUFZLENBQUNjLE1BQU0sQ0FBQyxHQUFHLEVBQUU7O0lBRTVDO0lBQ0F6RyxlQUFlLENBQUNrQyxFQUFFLENBQUN5RCxZQUFZLENBQUNjLE1BQU0sQ0FBQyxDQUFDdkUsRUFBRSxDQUFDeUQsWUFBWSxDQUFDZSxPQUFPLENBQUMsR0FBR25DLElBQUksQ0FBQ0MsS0FBSyxDQUFFRCxJQUFJLENBQUNvQyxTQUFTLENBQUV2QixLQUFNLENBQUUsQ0FBQztJQUV4R0EsS0FBSyxDQUFDa0IsT0FBTyxDQUFFLFVBQVVkLElBQUksRUFBRTRELEtBQUssRUFBRztNQUV0QzVELElBQUksQ0FBQ29GLFNBQVMsR0FBRyxJQUFJO01BQ3JCcEYsSUFBSSxDQUFDNEQsS0FBSyxHQUFHQSxLQUFLO01BRWxCLElBQUs1RCxJQUFJLENBQUMyRixJQUFJLENBQUNDLEtBQUssQ0FBRSxTQUFVLENBQUMsRUFBRztRQUNuQ2xKLEVBQUUsQ0FBQ21KLG1CQUFtQixDQUFFN0YsSUFBSSxFQUFFQSxJQUFJLENBQUM4RixHQUFJLENBQUM7UUFFeEM7TUFDRDtNQUVBcEosRUFBRSxDQUFDcUosSUFBSSxDQUFFLFdBQVcsRUFBRS9GLElBQUssQ0FBQztNQUM1QnRELEVBQUUsQ0FBQ3FKLElBQUksQ0FBRSxVQUFVLEVBQUUvRixJQUFLLENBQUM7SUFDNUIsQ0FBRSxDQUFDO0lBRUh0RCxFQUFFLENBQUMrRyxPQUFPLENBQUNPLFFBQVEsR0FBR3RILEVBQUUsQ0FBQytHLE9BQU8sQ0FBQ08sUUFBUSxHQUFHcEUsS0FBSyxDQUFDekQsTUFBTTtFQUN6RDs7RUFFQTtBQUNEO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7RUFDQyxTQUFTNkosWUFBWUEsQ0FBRUMsR0FBRyxFQUFHO0lBRTVCLElBQUtBLEdBQUcsQ0FBQ0MsUUFBUSxFQUFHO01BQ25CLE9BQU9ELEdBQUcsQ0FBQ0MsUUFBUTtJQUNwQjtJQUVBLElBQUlqRixNQUFNLEdBQUdzRCxRQUFRLENBQUUwQixHQUFHLENBQUNFLE9BQU8sQ0FBQ2xGLE1BQU0sRUFBRSxFQUFHLENBQUM7SUFDL0MsSUFBSUMsT0FBTyxHQUFHcUQsUUFBUSxDQUFFMEIsR0FBRyxDQUFDRSxPQUFPLENBQUNqRixPQUFPLEVBQUUsRUFBRyxDQUFDLElBQUksQ0FBQztJQUN0RCxJQUFJOEMsUUFBUSxHQUFHTyxRQUFRLENBQUUwQixHQUFHLENBQUNFLE9BQU8sQ0FBQ0MsYUFBYSxFQUFFLEVBQUcsQ0FBQztJQUV4RCxJQUFJQyxhQUFhLEdBQUdKLEdBQUcsQ0FBQ0UsT0FBTyxDQUFDRyxVQUFVLENBQUNDLEtBQUssQ0FBRSxHQUFJLENBQUMsQ0FBQzFHLEdBQUcsQ0FBRSxVQUFVVixFQUFFLEVBQUc7TUFDM0UsT0FBTyxHQUFHLEdBQUdBLEVBQUU7SUFDaEIsQ0FBRSxDQUFDLENBQUNxSCxJQUFJLENBQUUsR0FBSSxDQUFDOztJQUVmO0lBQ0EsSUFBSTlKLEVBQUUsR0FBRyxJQUFJSixNQUFNLENBQUNtSyxRQUFRLENBQUVSLEdBQUcsRUFBRTtNQUNsQ0gsR0FBRyxFQUFFeEosTUFBTSxDQUFDQyxtQkFBbUIsQ0FBQ3VKLEdBQUc7TUFDbkNZLGNBQWMsRUFBRSxJQUFJO01BQ3BCQyxRQUFRLEVBQUUsSUFBSTtNQUNkQyxhQUFhLEVBQUUsSUFBSTtNQUNuQkMsV0FBVyxFQUFFLElBQUk7TUFDakJ2QyxTQUFTLEVBQUVDLFFBQVEsQ0FBRTBCLEdBQUcsQ0FBQ0UsT0FBTyxDQUFDVyxhQUFhLEVBQUUsRUFBRyxDQUFDO01BQ3BEQyxTQUFTLEVBQUVkLEdBQUcsQ0FBQ0UsT0FBTyxDQUFDYSxTQUFTO01BQ2hDQyxvQkFBb0IsRUFBRSxDQUFDLENBQUUsQ0FBRWhCLEdBQUcsQ0FBQ0UsT0FBTyxDQUFDZSxlQUFlLElBQUksRUFBRSxFQUFHdEIsS0FBSyxDQUFFLFNBQVUsQ0FBQztNQUNqRnNCLGVBQWUsRUFBRTNDLFFBQVEsQ0FBRTBCLEdBQUcsQ0FBQ0UsT0FBTyxDQUFDZ0Isa0JBQWtCLEVBQUUsRUFBRyxDQUFDO01BQy9EQyxnQkFBZ0IsRUFBRSxLQUFLO01BQ3ZCQyxXQUFXLEVBQUUsQ0FBRTlDLFFBQVEsQ0FBRTBCLEdBQUcsQ0FBQ0UsT0FBTyxDQUFDbUIsT0FBTyxFQUFFLEVBQUcsQ0FBQyxJQUFLLElBQUksR0FBRyxJQUFJLENBQUUsRUFBR0MsT0FBTyxDQUFFLENBQUUsQ0FBQztNQUNuRnZELFFBQVEsRUFBRUEsUUFBUTtNQUNsQnFDLGFBQWEsRUFBRUEsYUFBYTtNQUM1Qm1CLG9CQUFvQixFQUFFbEwsTUFBTSxDQUFDQyxtQkFBbUIsQ0FBQ3lHLE1BQU0sQ0FBQ3lFLFVBQVUsQ0FBQ0MsT0FBTyxDQUFFLGFBQWEsRUFBRTFELFFBQVMsQ0FBQztNQUNyRzJELG1CQUFtQixFQUFFckwsTUFBTSxDQUFDQyxtQkFBbUIsQ0FBQ3lHLE1BQU0sQ0FBQzRFLGNBQWM7TUFDckVDLGNBQWMsRUFBRXZMLE1BQU0sQ0FBQ0MsbUJBQW1CLENBQUN5RyxNQUFNLENBQUM4RTtJQUNuRCxDQUFFLENBQUM7O0lBRUg7SUFDQXBMLEVBQUUsQ0FBQ3lELFlBQVksR0FBRztNQUNqQkMsV0FBVyxFQUFFNkYsR0FBRyxDQUFDRSxPQUFPLENBQUNtQixPQUFPO01BQ2hDOUYsSUFBSSxFQUFFeUUsR0FBRyxDQUFDRSxPQUFPLENBQUNhLFNBQVM7TUFDM0IvRixNQUFNLEVBQUVBLE1BQU07TUFDZEMsT0FBTyxFQUFFQTtJQUNWLENBQUM7SUFFRHdFLG1CQUFtQixDQUFFaEosRUFBRyxDQUFDOztJQUV6QjtJQUNBQSxFQUFFLENBQUN1QixFQUFFLENBQUUsU0FBUyxFQUFFOEIsT0FBTyxDQUFFckQsRUFBRSxFQUFFO01BQzlCZCxNQUFNLEVBQUUsc0JBQXNCO01BQzlCMkgsT0FBTyxFQUFFdEMsTUFBTTtNQUNmdUMsUUFBUSxFQUFFdEM7SUFDWCxDQUFFLENBQUUsQ0FBQztJQUNMeEUsRUFBRSxDQUFDdUIsRUFBRSxDQUFFLFdBQVcsRUFBRTZHLFNBQVMsQ0FBRXBJLEVBQUcsQ0FBRSxDQUFDO0lBQ3JDQSxFQUFFLENBQUN1QixFQUFFLENBQUUsYUFBYSxFQUFFK0csV0FBVyxDQUFFdEksRUFBRyxDQUFFLENBQUM7SUFDekNBLEVBQUUsQ0FBQ3VCLEVBQUUsQ0FBRSxVQUFVLEVBQUUyRSx5QkFBeUIsQ0FBRWxHLEVBQUcsQ0FBRSxDQUFDO0lBQ3BEQSxFQUFFLENBQUN1QixFQUFFLENBQUUsT0FBTyxFQUFFdUgsS0FBSyxDQUFFOUksRUFBRyxDQUFFLENBQUM7SUFFN0IsT0FBT0EsRUFBRTtFQUNWOztFQUVBO0FBQ0Q7QUFDQTtBQUNBO0FBQ0E7RUFDQyxTQUFTcUwsa0JBQWtCQSxDQUFBLEVBQUc7SUFFN0J6TixDQUFDLENBQUUsSUFBSyxDQUFDLENBQUMwTixJQUFJLENBQUUsbUJBQW9CLENBQUMsQ0FBQ3RLLFFBQVEsQ0FBRSxlQUFnQixDQUFDO0VBQ2xFOztFQUVBO0FBQ0Q7QUFDQTtBQUNBO0FBQ0E7RUFDQyxTQUFTdUssaUJBQWlCQSxDQUFBLEVBQUc7SUFFNUIzTixDQUFDLENBQUUsSUFBSyxDQUFDLENBQUMwTixJQUFJLENBQUUsbUJBQW9CLENBQUMsQ0FBQ3BKLFdBQVcsQ0FBRSxlQUFnQixDQUFDO0VBQ3JFOztFQUVBO0FBQ0Q7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0VBQ0MsU0FBU3NKLHFCQUFxQkEsQ0FBRWpKLENBQUMsRUFBRztJQUVuQ0EsQ0FBQyxDQUFDa0osY0FBYyxDQUFDLENBQUM7SUFFbEIsSUFBS2xKLENBQUMsQ0FBQ21KLE9BQU8sS0FBSyxFQUFFLEVBQUc7TUFDdkI7SUFDRDtJQUVBOU4sQ0FBQyxDQUFFLElBQUssQ0FBQyxDQUFDME4sSUFBSSxDQUFFLG1CQUFvQixDQUFDLENBQUNwRyxPQUFPLENBQUUsT0FBUSxDQUFDO0VBQ3pEOztFQUVBO0FBQ0Q7QUFDQTtBQUNBO0FBQ0E7RUFDQyxTQUFTeUcsYUFBYUEsQ0FBQSxFQUFHO0lBRXhCL04sQ0FBQyxDQUFFLElBQUssQ0FBQyxDQUFDZSxJQUFJLENBQUUsaUJBQWtCLENBQUMsQ0FBQ3VHLE9BQU8sQ0FBRSxPQUFRLENBQUM7RUFDdkQ7O0VBRUE7QUFDRDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtFQUNDLFNBQVMwRyxxQkFBcUJBLENBQUVySixDQUFDLEVBQUVoRCxLQUFLLEVBQUc7SUFFMUMsSUFBS1ksb0JBQW9CLENBQUMsQ0FBQyxFQUFHO01BQzdCSSxtQkFBbUIsQ0FBRWhCLEtBQU0sQ0FBQztJQUM3QjtFQUNEOztFQUVBO0FBQ0Q7QUFDQTtBQUNBO0FBQ0E7RUFDQyxTQUFTc00sTUFBTUEsQ0FBQSxFQUFHO0lBRWpCak8sQ0FBQyxDQUFFLGlCQUFrQixDQUFDLENBQ3BCMkQsRUFBRSxDQUFFLE9BQU8sRUFBRThKLGtCQUFtQixDQUFDLENBQ2pDOUosRUFBRSxDQUFFLE1BQU0sRUFBRWdLLGlCQUFrQixDQUFDLENBQy9CaEssRUFBRSxDQUFFLFVBQVUsRUFBRWlLLHFCQUFzQixDQUFDO0lBRXpDNU4sQ0FBQyxDQUFFLG1CQUFvQixDQUFDLENBQ3RCMkQsRUFBRSxDQUFFLE9BQU8sRUFBRW9LLGFBQWMsQ0FBQztJQUU5Qi9OLENBQUMsQ0FBRSxtQkFBb0IsQ0FBQyxDQUN0QjJELEVBQUUsQ0FBRSw4QkFBOEIsRUFBRXFLLHFCQUFzQixDQUFDO0VBQzlEOztFQUVBO0FBQ0Q7QUFDQTtBQUNBO0FBQ0E7RUFDQyxTQUFTRSxLQUFLQSxDQUFBLEVBQUc7SUFFaEJsTSxNQUFNLENBQUNRLE9BQU8sR0FBR1IsTUFBTSxDQUFDUSxPQUFPLElBQUksQ0FBQyxDQUFDO0lBQ3JDUixNQUFNLENBQUNRLE9BQU8sQ0FBQ0MsU0FBUyxHQUFHLEVBQUUsQ0FBQzBMLEtBQUssQ0FBQzlFLElBQUksQ0FBRXRCLFFBQVEsQ0FBQ2tELGdCQUFnQixDQUFFLG1CQUFvQixDQUFFLENBQUMsQ0FBQzFGLEdBQUcsQ0FBRW1HLFlBQWEsQ0FBQztJQUVoSHVDLE1BQU0sQ0FBQyxDQUFDO0VBQ1Q7O0VBRUE7QUFDRDtBQUNBO0FBQ0E7QUFDQTtFQUNDLElBQUlHLHVCQUF1QixHQUFHO0lBRTdCO0FBQ0Y7QUFDQTtBQUNBO0FBQ0E7SUFDRUMsSUFBSSxFQUFFLFNBQUFBLEtBQUEsRUFBVztNQUVoQixJQUFLdEcsUUFBUSxDQUFDdUcsVUFBVSxLQUFLLFNBQVMsRUFBRztRQUN4Q3ZHLFFBQVEsQ0FBQ3dHLGdCQUFnQixDQUFFLGtCQUFrQixFQUFFTCxLQUFNLENBQUM7TUFDdkQsQ0FBQyxNQUFNO1FBQ05BLEtBQUssQ0FBQyxDQUFDO01BQ1I7SUFDRDtFQUNELENBQUM7O0VBRUQ7RUFDQUUsdUJBQXVCLENBQUNDLElBQUksQ0FBQyxDQUFDO0VBQzlCck0sTUFBTSxDQUFDb00sdUJBQXVCLEdBQUdBLHVCQUF1QjtBQUV6RCxDQUFDLEVBQUV2SyxNQUFPLENBQUMifQ==
},{}]},{},[1])