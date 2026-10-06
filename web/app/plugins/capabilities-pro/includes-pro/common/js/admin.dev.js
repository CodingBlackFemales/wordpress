jQuery(document).ready(function ($) {

  // -------------------------------------------------------------
  //   Prevent ENTER on input from submitting whole Editor feature settings
  // -------------------------------------------------------------
  $(document).on("keydown", ".ppc-add-custom-row-body input[type='text']", function (event) {
    return event.keyCode !== 13;
  });

  // -------------------------------------------------------------
  //   Edit features custom item
  // -------------------------------------------------------------
  $(document).on("click", ".edit-features-custom-item", function (event) {
    event.preventDefault();

    var item = $(this);
    var item_section = item.attr('data-section');
    var item_id = item.attr('data-id');
    var item_label = item.attr('data-label');
    var item_element = item.attr('data-element');
    var item_wildcard = item.attr('data-wildcard');
    var item_target_urls = item.attr('data-target-urls');
    var item_url_wildcard = item.attr('data-url-wildcard');
    var item_form = $('.ppc-custom-features-table.' + item_section + '');

    if (item_id == '') {
      return;
    }

    $('.' + item_section + '.editing-custom-item').show();
    $('.' + item_section + '.editing-custom-item .title').html(item_label);
    item_form.find('.cancel-custom-features-item-edit').attr('style', 'visibility: visible');
    item_form.find('.submit-button').html(item_form.find('.submit-button').attr('data-edit'));

    item_form.find('.custom-edit-id').val(item_id);
    item_form.find('.form-label').val(item_label);
    item_form.find('.form-element').val(item_element);

    if (item_section === 'blockedbyurl') {
      item_form.find('.ppc-feature-block-url-wildcard-enabled').prop('checked', item_wildcard === '1');
    } else if (item_section === 'hidecsselement') {
      item_form.find('.ppc-feature-css-hide-target-urls').val(item_target_urls || '');
      item_form.find('.ppc-feature-css-hide-url-wildcard-enabled').prop('checked', item_url_wildcard === '1');
    }

    item_form.find('.form-label').trigger('change');

    //scroll to the form
    $([document.documentElement, document.body]).animate({
      scrollTop: item_form.offset().top - 150
    }, 'fast');

  });

  // -------------------------------------------------------------
  //   Cancel custom features item edit
  // -------------------------------------------------------------
  $(document).on("click", ".cancel-custom-features-item-edit", function (event) {
    event.preventDefault();
    var item = $(this);
    var item_section = item.attr('data-section');
    var item_form = $('.ppc-custom-features-table.' + item_section + '');

    $('.' + item_section + '.editing-custom-item').hide();

    $('.' + item_section + '.editing-custom-item .title').html('');
    item_form.find('.cancel-custom-features-item-edit').attr('style', '');
    item_form.find('.submit-button').html(item_form.find('.submit-button').attr('data-add'));

    item_form.find('.custom-edit-id').val('');
    item_form.find('.form-label').val('');
    item_form.find('.form-element').val('');

    if (item_section === 'blockedbyurl') {
      item_form.find('.ppc-feature-block-url-wildcard-enabled').prop('checked', false);
    } else if (item_section === 'hidecsselement') {
      item_form.find('.ppc-feature-css-hide-target-urls').val('');
      item_form.find('.ppc-feature-css-hide-url-wildcard-enabled').prop('checked', false);
    }

    item_form.find('.form-label').trigger('change');
  });

  // -------------------------------------------------------------
  //   Submit new item for editor feature
  // -------------------------------------------------------------
  $(document).on("click", ".ppc-feature-gutenberg-new-submit, .ppc-feature-classic-new-submit", function (event) {
    event.preventDefault();
    var ajax_action,
      security,
      custom_label,
      custom_element,
      item_id,
      button = $(this);

    $('.ppc-feature-submit-form-error').remove();
    button.attr('disabled', true);
    $(".ppc-feature-post-loader").addClass("is-active");

    if (button.hasClass('ppc-feature-gutenberg-new-submit')) {
      ajax_action = 'ppc_submit_feature_gutenberg_by_ajax';
      custom_label = button.closest('.ppc-add-custom-row-body').find('.ppc-feature-gutenberg-new-name').val();
      custom_element = button.closest('.ppc-add-custom-row-body').find('.ppc-feature-gutenberg-new-ids').val();
    } else {
      ajax_action = 'ppc_submit_feature_classic_by_ajax';
      custom_label = button.closest('.ppc-add-custom-row-body').find('.ppc-feature-classic-new-name').val();
      custom_element = button.closest('.ppc-add-custom-row-body').find('.ppc-feature-classic-new-ids').val();
    }


    item_id = button.closest('tr').find('.custom-edit-id').val();

    security = button.closest('.ppc-add-custom-row-body').find('.ppc-feature-submit-form-nonce').val();

    var data = {
      'action': ajax_action,
      'security': security,
      'custom_label': custom_label,
      'custom_element': custom_element,
      'item_id': item_id,
    };

    $.post(ajaxurl, data, function (response) {

      if (response.status == 'error') {
        button.closest('tr').find('.ppc-post-features-note').html('<div class="ppc-feature-submit-form-error" style="color:red;">' + response.message + '</div>');
        $(".ppc-feature-submit-form-error").delay(2000).fadeOut('slow');
      } else {
        var currentTable, tableContent, PostType, checkedElement, activeSelector;

        $('.ppc-editor-features-submit').attr('disabled', false);
        $('.ppc-save-button-warning').remove();

        if (button.hasClass('ppc-feature-gutenberg-new-submit')) {
          activeSelector = '.editor-features-gutenberg';
          $('.ppc-feature-gutenberg-new-name').val('');
          $('.ppc-feature-gutenberg-new-ids').val('');
        } else {
          activeSelector = '.editor-features-classic';
          $('.ppc-feature-classic-new-name').val('');
          $('.ppc-feature-classic-new-ids').val('');
        }

        button.closest('tr').find('.ppc-post-features-note').html('<div class="ppc-feature-submit-form-error" style="color:green;">' + response.message + '</div>');

        $(".ppc-feature-submit-form-error").delay(5000).fadeOut('slow');

        setTimeout(function () {
          $('.ppc-menu-overlay-item').removeClass('ppc-menu-overlay-item');
        }, 5000);

        //add element to all post type
        $(activeSelector).each(function () {
          currentTable = $(this);
          PostType = currentTable.attr('data-post_type');
          checkedElement = ($('.ppc-capabilities-tab-active').attr('data-slug') === PostType) ? 'checked' : '';
          tableContent = '<tr class="ppc-menu-row parent-menu ppc-menu-overlay-item custom-item-' + response.content.element_id + '">' +
            '<td class="restrict-column ppc-menu-checkbox">' +
            '<input id="check-item-' + PostType + '-' + response.content.element_id + '" class="check-item" type="checkbox" name="' + response.content.data_name_prefix + PostType + '[]" value="' + response.content.element_id + '" ' + checkedElement + ' />' +
            '</td>' +

            '<td class="menu-column ppc-menu-item custom-item-row ppc-flex">' +
            '<div class="ppc-flex-item">' +
            '<div>' +
            '<span class="gutenberg menu-item-link">' +
            '<strong><i class="dashicons dashicons-arrow-right"></i> ' + response.content.custom_label + '</span>' +
            '</div>' +
            '<div class="custom-item-output">' +
            '<div class="custom-item-display">' +
            response.content.custom_element +
            '</div>' +
            '</div>' +
            '</div>' +
            '<div class="ppc-flex-item">' +
            '<div class="button view-custom-item">' + response.content.view_text + '</div>' +

            '<div class="button edit-features-custom-item" data-section="' + response.content.section + '" data-label="' + response.content.custom_label + '" data-element="' + response.content.custom_element + '" data-id="' + response.content.element_id + '">' + response.content.edit_text + '</div>' +

            '<div class="button ppc-custom-features-delete feature-red" data-parent="' + response.content.data_parent + '" data-id="' + response.content.element_id + '">' + response.content.delete_text + '</div>' +
            '</div>' +
            '</div>' +
            '</td>' +

            '</tr>';


          if (item_id !== '') {
            button.closest('tr').find('.cancel-custom-features-item-edit').trigger("click");
            currentTable.find('.custom-item-' + item_id).replaceWith(tableContent);
          } else {
            currentTable.find('tr:last').after(tableContent);
          }
        });

      }

      $(".ppc-feature-post-loader").removeClass("is-active");
      button.attr('disabled', false);

    });


  });

  // -------------------------------------------------------------
  //   Delete custom added post features item
  // -------------------------------------------------------------
  $(document).on("click", ".ppc-custom-features-delete", function (event) {
    if (confirm(cmeAdmin.deleteWarning)) {
      var item = $(this);
      var delete_id = item.attr('data-id');
      var delete_parent = item.attr('data-parent');
      var security = $('.ppc-feature-submit-form-nonce').val();

      $("div[data-id='" + delete_id + "']").each(function () {
        $(this).closest('.ppc-menu-row').fadeOut(300);
      });

      var data = {
        'action': 'ppc_delete_custom_post_features_by_ajax',
        'security': security,
        'delete_id': delete_id,
        'delete_parent': delete_parent,
      };

      $.post(ajaxurl, data, function (response) {
        if (response.status == 'error') {
          $("div[data-id='" + delete_id + "']").each(function () {
            $(this).closest('.ppc-menu-row').show();
          });
          alert(response.message);
        }
      });

    }
  });

  // -------------------------------------------------------------
  //   Lock Editor Features 'Save changes' button if unsaved custom items exist
  // -------------------------------------------------------------
  $(document).on("keyup paste", ".ppc-add-custom-row-body input, .ppc-add-custom-row-body textarea", function (event) {
    var lock_button = false;
    $('.ppc-save-button-warning').remove();

    $('.ppc-add-custom-row-body .left input, .ppc-add-custom-row-body .right textarea').each(function () {
      if ($(this).val() !== '' && $(this).val().replace(/\s/g, '').length) {
        lock_button = true;
      }
    });

    if (lock_button) {
      $(this).closest('form').find('input[type=submit]').attr('disabled', true).after('<span class="ppc-save-button-warning">' + cmeAdmin.saveWarning + '</span>');
    } else {
      $(this).closest('form').find('input[type=submit]').attr('disabled', false);
    }
  });

  // -------------------------------------------------------------
  //   Submit css hide new form entry for admin feature
  // -------------------------------------------------------------
  $(document).on("click", ".ppc-feature-css-hide-new-submit", function (event) {
    event.preventDefault();
    var ajax_action = 'ppc_submit_feature_css_hide_by_ajax';
    custom_label = $('.ppc-feature-css-hide-new-name').val(),
      custom_element = $('.ppc-feature-css-hide-new-element').val(),
      target_urls = $('.ppc-feature-css-hide-target-urls').val(),
      url_wildcard = $('.ppc-feature-css-hide-url-wildcard-enabled').is(':checked') ? 1 : 0,
      item_id = '',
      security = $('.ppc-feature-submit-form-nonce').val(),
      button = $(this);

    item_id = button.closest('tr').find('.custom-edit-id').val();

    $('.ppc-feature-submit-form-error').remove();
    button.attr('disabled', true);
    $(".ppc-feature-post-loader").addClass("is-active");

    var data = {
      'action': ajax_action,
      'security': security,
      'custom_label': custom_label,
      'custom_element': custom_element,
      'target_urls': target_urls,
      'url_wildcard': url_wildcard,
      'item_id': item_id,
    };

    $.post(ajaxurl, data, function (response) {

      if (response.status == 'error') {
        ppcTimerStatus('error', response.message);
      } else {
        var parent_table = $('.parent-menu.hidecsselement');
        var parent_child = $('.child-menu.hidecsselement');
        var addedItem;

        $('.ppc-save-button-warning').remove();
        $('.ppc-feature-css-hide-new-name').val('');
        $('.ppc-feature-css-hide-new-element').val('');
        $('.ppc-feature-css-hide-target-urls').val('');
        $('.ppc-feature-css-hide-url-wildcard-enabled').prop('checked', false);
        ppcTimerStatus('success', response.message);
        setTimeout(function () {
          $('.ppc-menu-overlay-item').removeClass('ppc-menu-overlay-item');
        }, 5000);

        if (parent_child.length > 0) {
          if (item_id !== '') {
            button.closest('tr').find('.cancel-custom-features-item-edit').trigger("click");
            $('.child-menu.hidecsselement.custom-item-' + item_id).replaceWith(response.content);
            addedItem = $('.child-menu.hidecsselement.custom-item-' + item_id);
          } else {
            $('.child-menu.hidecsselement:last').after(response.content);
            addedItem = $('.child-menu.hidecsselement').last();
          }
        } else {
          parent_table.after(response.content);
          addedItem = parent_table.next();
        }

        if (addedItem && addedItem.length) {
          $([document.documentElement, document.body]).animate({
            scrollTop: addedItem.offset().top - 150
          }, 'fast');
        }
      }

      button.closest('form').find('input[type=submit]').attr('disabled', false);
      $(".ppc-feature-post-loader").removeClass("is-active");
      button.attr('disabled', false);

    });
  });

  // -------------------------------------------------------------
  //   Delete css hide item for admin feature
  // -------------------------------------------------------------
  $(document).on("click", ".ppc-custom-features-css-delete", function (event) {
    if (confirm(cmeAdmin.deleteWarning)) {
      var item = $(this);
      var delete_id = item.attr('data-id');
      var security = $('.ppc-feature-submit-form-nonce').val();

      item.closest('.ppc-menu-row').fadeOut(300);

      var data = {
        'action': 'ppc_delete_feature_css_hide_item_by_ajax',
        'security': security,
        'delete_id': delete_id,
      };

      $.post(ajaxurl, data, function (response) {
        if (response.status == 'error') {
          item.closest('.ppc-menu-row').show();
          ppcTimerStatus('error', response.message);
        } else {
          item.closest('.ppc-menu-row').remove();
          ppcTimerStatus('success', response.message);
        }
      });

    }
  });


  // -------------------------------------------------------------
  //   Submit block url new form entry for admin feature
  // -------------------------------------------------------------
  $(document).on("click", ".ppc-feature-block-url-new-submit", function (event) {
    event.preventDefault();
    var ajax_action = 'ppc_submit_feature_blocked_url_by_ajax';
    custom_label = $('.ppc-feature-block-url-new-name').val(),
      custom_link = $('.ppc-feature-block-url-new-link').val(),
      custom_wildcard = $('.ppc-feature-block-url-wildcard-enabled').is(':checked') ? 1 : 0,
      security = $('.ppc-feature-submit-form-nonce').val(),
      item_id = '',
      button = $(this);

    item_id = button.closest('tr').find('.custom-edit-id').val();

    $('.ppc-feature-submit-form-error').remove();
    button.attr('disabled', true);
    $(".ppc-feature-post-loader").addClass("is-active");

    var data = {
      'action': ajax_action,
      'security': security,
      'custom_label': custom_label,
      'custom_link': custom_link,
      'custom_wildcard': custom_wildcard,
      'item_id': item_id,
    };

    $.post(ajaxurl, data, function (response) {

      if (response.status == 'error') {
        ppcTimerStatus('error', response.message);
      } else {
        var parent_table = $('.parent-menu.blockedbyurl');
        var parent_child = $('.child-menu.blockedbyurl');
        var addedItem;

        $('.ppc-save-button-warning').remove();
        $('.ppc-feature-block-url-new-name').val('');
        $('.ppc-feature-block-url-new-link').val('');
        $('.ppc-feature-block-url-wildcard-enabled').prop('checked', false);

        setTimeout(function () {
          $('.ppc-menu-overlay-item').removeClass('ppc-menu-overlay-item');
        }, 5000);
        if (parent_child.length > 0) {
          if (item_id !== '') {
            button.closest('tr').find('.cancel-custom-features-item-edit').trigger("click");
            $('.child-menu.blockedbyurl.custom-item-' + item_id).replaceWith(response.content);
            addedItem = $('.child-menu.blockedbyurl.custom-item-' + item_id);
          } else {
            $('.child-menu.blockedbyurl:last').after(response.content);
            addedItem = $('.child-menu.blockedbyurl').last();
          }
        } else {
          parent_table.after(response.content);
          addedItem = parent_table.next();
        }

        if (addedItem && addedItem.length) {
          $([document.documentElement, document.body]).animate({
            scrollTop: addedItem.offset().top - 150
          }, 'fast');
        }
      }

      button.closest('form').find('input[type=submit]').attr('disabled', false);
      $(".ppc-feature-post-loader").removeClass("is-active");
      button.attr('disabled', false);

    });
  });



  // -------------------------------------------------------------
  //   Delete blocked url item for admin feature
  // -------------------------------------------------------------
  $(document).on("click", ".ppc-custom-features-url-delete", function (event) {
    if (confirm(cmeAdmin.deleteWarning)) {
      var item = $(this);
      var delete_id = item.attr('data-id');
      var security = $('.ppc-feature-submit-form-nonce').val();

      item.closest('.ppc-menu-row').fadeOut(300);

      var data = {
        'action': 'ppc_delete_feature_blocked_url_item_by_ajax',
        'security': security,
        'delete_id': delete_id,
      };

      $.post(ajaxurl, data, function (response) {
        if (response.status == 'error') {
          item.closest('.ppc-menu-row').show();
          ppcTimerStatus('error', response.message);
        } else {
          item.closest('.ppc-menu-row').remove();
          ppcTimerStatus('success', response.message);
        }
      });

    }
  });


   function ppcTimerStatus(type = "success", message = "") {
    setTimeout(function () {
      var uniqueClass = "ppc-floating-msg-" + Math.round(new Date().getTime() + Math.random() * 100);

      if (message == '') {
        message = type === "success" ? __("Changes saved!", "capabilities-pro") : __(" Error: changes can't be saved.", "capabilities-pro");
      }

      var instances = $(".ppc-floating-status").length;
      $("#wpbody-content").after('<span class="ppc-floating-status ppc-floating-status--' + type + " " + uniqueClass + '">' + message + "</span>");
      $("." + uniqueClass)
        .css("bottom", instances * 45)
        .fadeIn(1e3)
        .delay(1e4)
        .fadeOut(1e3, function () {
          $(this).remove();
        });
    }, 500);
  }


  function highlightItem($item) {
    $item.addClass('ppc-item-highlight');
    setTimeout(function () {
      $item.removeClass('ppc-item-highlight');
    }, 2000);
  }

});
