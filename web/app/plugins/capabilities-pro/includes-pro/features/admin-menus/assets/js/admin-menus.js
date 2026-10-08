jQuery(document).ready(function ($) {

  var { __: __, _x: _x, _n: _n, _nx: _nx } = wp.i18n;

  function isSeparatorMenuType(menuType) {
    return menuType == 'menu-separator' || menuType == 'submenu-separator';
  }

  function updateNewMenuFormFields(formTable) {
    var selectedType = formTable.find('.menu-type').val();
    var separatorStyle = formTable.find('.separator-style').val();
    var isSeparator = isSeparatorMenuType(selectedType);
    var isSubmenu = selectedType == 'submenu' || selectedType == 'submenu-separator';
    var showTitleField = !isSeparator || separatorStyle == 'text';

    formTable.find('label[for="separator-style"], .separator-style').toggleClass('hidden-element', !isSeparator);
    formTable.find('label[for="menu-title"], .menu-title').toggleClass('hidden-element', !showTitleField);
    formTable.find('label[for="menu-url"], .menu-url').toggleClass('hidden-element', isSeparator);
    formTable.find('label[for="separator-color"], .separator-color').toggleClass('hidden-element', !isSeparator || separatorStyle == 'empty');
    formTable.find('.select-icon-button').toggleClass('hidden-element', isSeparator || isSubmenu);

    if (selectedType == 'menu' || selectedType == 'menu-separator') {
      formTable.find('.add-menu-after option[data-option_type="submenu"]').prop('disabled', true);
      formTable.find('.add-menu-after option[data-option_type="menu"]').prop('disabled', false);

      if (formTable.find('.add-menu-after option:selected').attr('data-option_type') == 'submenu') {
        formTable.find('.add-menu-after option[data-option_type="menu"]:enabled').first().prop('selected', true);
      }
    } else {
      formTable.find('.add-menu-after option[data-option_type="submenu"]').prop('disabled', false);
      formTable.find('.add-menu-after option[data-option_type="menu"]').prop('disabled', false);
    }

    if (!isSeparator && !isSubmenu) {
      formTable.find('.select-icon-button').css('visibility', 'visible');
    }
  }

  /**
   * Toggle admin menu form
   */
  $(document).on('click', '.ppc-menu-action .add-new-menu', function (e) {
    $('.ppc-new-menu-form .new-menu-fields').slideToggle();

    var addMenuAfter = $('.add-menu-after');

    // Clear existing options
    addMenuAfter.empty();

    // Populate options from parent menu items
    var parent_position = 0;
    var child_position = 0;
    var menu_position = '';
    $('.menu-table-body tr.ppc-menu-row').each(function() {
        var menuItem = $(this);
        if (menuItem.hasClass('parent-menu')) {
          parent_position++;
          // start child position count for this parent
          child_position = 0;

          menu_position = parent_position;
        } else {
          child_position++;

          menu_position = child_position;
        }
        var menuSlug = menuItem.data('main-slug');
        var menuType = menuItem.hasClass('parent-menu') ? 'menu' : 'submenu';

        var menuTitle = menuItem.hasClass('parent-menu') ? '' : '&nbsp;&nbsp;&nbsp;&nbsp;';
         menuTitle += menuItem.find('.menu-title').text();

        if (menuSlug) {
          // Append to both select elements
          if (menuType == 'menu') {
            addMenuAfter.append('<option value="' + menuSlug + '" data-option_type="' + menuType + '" data-option_position="' + menu_position + '">' + menuTitle + '</option>');
          } else {
            addMenuAfter.append('<option value="' + menuSlug + '" data-option_type="' + menuType + '" data-option_position="' + menu_position + '" disabled>' + menuTitle + '</option>');
          }
          // add position to the menu tr if it doesn't have one
          if (!menuItem.attr('data-position') || menuItem.attr('data-position') == '') {
            menuItem.attr('data-position', menu_position);
          }
        }
    });

    // set default fields
    $('.menu-form-table .menu-url').val('admin.php?page=pp-capabilities-admin-menus&menu_id=' + generateRandomCharacters(4));
    $('.menu-form-table .separator-color').val('#ffffff');
    updateNewMenuFormFields($('.menu-form-table'));

  });

  /**
   * Admin menu type change
   */
  $(document).on('change', '.menu-form-table .menu-type', function (e) {

    updateNewMenuFormFields($(this).closest('table.menu-form-table'));
  });

  $(document).on('change', '.menu-form-table .separator-style', function (e) {
    updateNewMenuFormFields($(this).closest('table.menu-form-table'));
  });

  /**
   * Hide or show menu icon if it's a main menu or sub menu
   */
  $(document).on('change', '.menu-form-table .add-menu-after', function (e) {

    var selected_type = $(this).find('option:selected').attr('data-option_type');

    if (selected_type == 'submenu') {
      // set icon button visibility to hidden
      $('.menu-form-table .select-icon-button').css('visibility', 'hidden');
    } else {
      // set icon button visibility to visible
      $('.menu-form-table .select-icon-button').css('visibility', 'visible');
    }
  });

  /**
   * Save custom menu
   */
  $(document).on('click', '.save-new-menu', function (e) {
    e.preventDefault();

    $('.required-message').addClass('hidden-element');
    $('.admin-menu-response-message').html(' ');

    var error_form  = false;
    var button      = $(this);
    var formTable   = button.closest('table.menu-form-table');
    // get form fields
    var menuTypeField           = formTable.find('.menu-type');
    var menuPositionField       = formTable.find('.menu-position');
    var menuPositionSlugField   = formTable.find('.add-menu-after');
    var menuTitleField          = formTable.find('.menu-title');
    var menuSeparatorStyleField = formTable.find('.separator-style');
    var menuSeparatorColorField = formTable.find('.separator-color');
    var menuUrlField            = formTable.find('.menu-url');
    var menuCapabilityField     = formTable.find('.menu-capability');

    // get form field values
    var menuType            = menuTypeField.val();
    var menuPosition        = menuPositionField.val();
    var menuPositionSlug    = menuPositionSlugField.val();
    var menuTitle           = $.trim(menuTitleField.val());
    var menuSeparatorStyle  = menuSeparatorStyleField.val();
    var menuSeparatorColor  = menuSeparatorColorField.val();
    var menuUrl             = menuUrlField.val();
    var menuCapability      = menuCapabilityField.val();

    var menuPositionSlugType = formTable.find('.add-menu-after').find('option:selected').attr('data-option_type');

    var selected_menu_parent = menuPositionSlug;
    if (menuPositionSlugType == 'submenu') {
      selected_menu_parent = $('.menu-table-body tr.ppc-menu-row.child-menu[data-main-slug="' + menuPositionSlug + '"]').attr('data-parent-slug');
    }

    if (menuUrl == '') {
      menuUrl = '#ppc_custom_menu_' + generateRandomCharacters(8);
    }

    if (menuType == 'menu-separator' || menuType == 'submenu-separator') {
      // Keep separator styling in menu_data[4]; the menu slug only needs a unique ID.
      menuUrl = 'separator-' + generateRandomCharacters(8).toLowerCase();
    }

    var menuIcon = '';
    var iconButton = formTable.find('.ppc-icon-selector-button');
    if (iconButton.length) {
      var dashiconMatch = iconButton.find('.dashicons').attr('class') ? iconButton.find('.dashicons').attr('class').match(/dashicons-[\w-]+/) : null;
      if (dashiconMatch) {
        menuIcon = dashiconMatch[0];
      }
    }

    //determine if this is a menu or sub menu and also where it should be added
    var new_menu_type = '', new_menu_position = '', new_menu_position_after_menu = '', new_menu_position_after_menu_selector = '';
    if (menuType == 'menu' || menuType == 'menu-separator') {
      new_menu_type = 'menu';
    } else if (menuType == 'submenu' || menuType == 'submenu-separator') {
      new_menu_type = 'submenu';
    }

    if (new_menu_type == 'menu') {
      new_menu_position_after_menu_selector = $('.menu-table-body tr.ppc-menu-row.parent-menu[data-main-slug="' + menuPositionSlug + '"]');
      new_menu_position = Number(new_menu_position_after_menu_selector.attr('data-position'));
    } else {
      new_menu_position_after_menu_selector = $('.menu-table-body tr.ppc-menu-row.child-menu[data-main-slug="' + menuPositionSlug + '"]');
      new_menu_position = Number(new_menu_position_after_menu_selector.attr('data-position')) + 1;
      if (menuPositionSlugType == 'menu') {
        // simply set it as first submenu if main menu is selected when adding submenu.
        new_menu_position = 1;
      }
    }
    menu_position_after = new_menu_position_after_menu_selector.attr('data-main-slug');

    if (menuType == 'menu-separator' || menuType == 'submenu-separator') {
      // separator
      var separatorItemClass = 'ppc-admin-menu-item-' + menuUrl.toLowerCase().replace(/[^a-z0-9_-]/g, '-');
      var separatorClasses = 'ppc-admin-menu-separator ' + menuSeparatorStyle + '-separator';
      var separatorTitle = menuSeparatorStyle == 'text' ? menuTitle : '';
      separatorClasses += ' ' + separatorItemClass;
      var menu_data = [
        separatorTitle,
        menuCapability,
        menuUrl,
        separatorTitle,
        separatorClasses,
        '',
        'none'
      ];
    } else {
      if (new_menu_type == 'menu') {
        // main menu
        var menu_data = [
          menuTitle,
          menuCapability,
          menuUrl,
          menuTitle,
          'menu-top pp-capability-custom-menu',
          'menu-' + generateRandomCharacters(8),
          menuIcon
        ];

        if ($('.menu-table-body tr.ppc-menu-row.parent-menu[data-main-slug="' + menuUrl + '"]').length > 0) {
          error_form = true;
          $('.admin-menu-response-message').append('<div class="notice notice-error"><p>' + ppCapabilitiesProAdminMenus.duplicateMenuError + '</p></div>');
        }
      } else {
        //submenu
        var menu_data = [
          menuTitle,
          menuCapability,
          menuUrl
        ];

        if ($('.menu-table-body tr.ppc-menu-row.child-menu[data-main-slug="' + menuUrl + '"]').length > 0) {
          error_form = true;
          $('.admin-menu-response-message').append('<div class="notice notice-error"><p>' + ppCapabilitiesProAdminMenus.duplicateSubMenuError + '</p></div>');
        }
      }

    }

    if ((menuType == 'menu' || menuType == 'submenu' || (isSeparatorMenuType(menuType) && menuSeparatorStyle == 'text')) && menuTitle == '') {
      error_form = true;
      menuTitleField.closest('td').find('.required-message').removeClass('hidden-element');
    }
    if (menuCapability == '') {
      error_form = true;
      menuCapabilityField.closest('td').find('.required-message').removeClass('hidden-element');
    }

    if (error_form) {
      return;
    }

    // we need to update our custom menu reoder too and include current new menu at the right place
    var menu_group = getMenuOrder();

    if (new_menu_type == 'menu') {
      // Adding new menu after the selected menu
      let entries = Object.entries(menu_group);
      let positionIndex = entries.findIndex(([key, value]) => key === menuPositionSlug);
      if (positionIndex !== -1) {
        // Insert a new menu after selected menu
        entries.splice(positionIndex + 1, 0, [menuUrl, [menuUrl]]);
        menu_group = Object.fromEntries(entries);
      }
    } else {
      // Adding new menu after the selected menu
      let positionParent = menu_group[selected_menu_parent];
      let positionIndex = positionParent.indexOf(menuPositionSlug);
      if (positionIndex !== -1) {
        positionParent.splice(positionIndex + 1, 0, menuUrl);
      }

    }

    var form_data = {
      action: 'ppc_add_new_admin_menu',
      nonce: ppCapabilitiesProAdminMenus.nonce,
      form_menu_type: menuType,
      menu_type: new_menu_type,
      menu_position: new_menu_position,
      menu_position_after: new_menu_position_after_menu,
      menu_parent: selected_menu_parent,
      current_role: $('.ppc-admin-menu-role').val(),
      menu_data: menu_data,
      menu_order: menu_group,
      separator_color: (menuType == 'menu-separator' || menuType == 'submenu-separator') && menuSeparatorStyle != 'empty' ? menuSeparatorColor : ''
    };

    $('.admin-menu-spinner').addClass('is-active');
    $('.save-new-menu').prop('disabled', true);

    $.ajax({
      url: ajaxurl,
      method: 'POST',
      data: form_data,
      success: function (response) {
        if (response.status == 'success') {
          $('.admin-menu-response-message').append('<div class="updated notice notice-success"><p>' + response.message + '</p></div>');
          // Append a random query string to ensure a fresh reload
          location.href = response.redirect;
        } else {
          $('.admin-menu-response-message').append('<div class="notice notice-error"><p>' + response.message + '</p></div>');
          $('.admin-menu-spinner').removeClass('is-active');
          $('.save-new-menu').prop('disabled', false);
        }
      }
    });
  });

  /**
   * Delete custom menu
   */
  $(document).on('click', '.pp-admin-menu-buttons .delete-admin-menu', function (event) {
    event.preventDefault();
    var menu_row  = $(this).closest('tr.ppc-menu-row');
    var menu_type = menu_row.hasClass('parent-menu') ? 'menu' : 'submenu';
    var menu_slug = menu_row.attr('data-main-slug');

    menu_row.addClass('admin-menu-highlight disabled');

    var form_data = {
      action: 'ppc_delete_admin_menu',
      nonce: ppCapabilitiesProAdminMenus.nonce,
      menu_type: menu_type,
      menu_slug: menu_slug,
      current_role: $('.ppc-admin-menu-role').val()
    };

    ppcTimerStatus('info', __("Deleting menu...", "capabilities-pro"));

    $.ajax({
      url: ajaxurl,
      method: 'POST',
      data: form_data,
      success: function (response) {
        ppcTimerStatus(response.status, response.message);
        if (response.status == 'success') {
          menu_row.remove();
        } else {
          menu_row.show();
        }
      }
    });
 });

 /**
  * Update admin menu settings
  */
 $(document).on('change', '.admin-menu-setting-field', function (event) {
  var show_menu_slug  = $('.admin-menu-setting-field.show-menu-slug').is(':checked');
  var hide_submenu    = $('.admin-menu-setting-field.hide-submenu').is(':checked');

   if (show_menu_slug) {
    $('.pp-capability-menus .menu-item-link .admin-menu-slug').show();
   } else {
    $('.pp-capability-menus .menu-item-link .admin-menu-slug').hide();
   }

   if (hide_submenu) {
    $('.menu-table-body tr.ppc-menu-row.child-menu').hide();
   } else {
    $('.menu-table-body tr.ppc-menu-row.child-menu').show();
   }

   var form_data = {
     action: 'ppc_update_admin_menu_settings',
     nonce: ppCapabilitiesProAdminMenus.nonce,
     show_menu_slug: show_menu_slug ? 1 : 0,
     hide_submenu: hide_submenu ? 1 : 0
   };

   ppcTimerStatus('info', __("Updating Settings...", "capabilities-pro"));

   $.ajax({
     url: ajaxurl,
     method: 'POST',
     data: form_data,
     success: function (response) {
       ppcTimerStatus(response.status, response.message);
     }
   });
});

/**
 * Delete custom menu
 */
$(document).on('click', '.ppc-reset-admin-menu-options', function (event) {
  event.preventDefault();

  var button       = $(this);
  var reset_order  = $('.reset-admin-menu-order').is(':checked');
  var reset_names  = $('.reset-admin-menu-names').is(':checked');
  var reset_menu   = $('.reset-admin-menu-cache').is(':checked');

  if (!reset_order && !reset_names && !reset_menu) {
   ppcTimerStatus('error', __("You must check at least one option to reset.", "capabilities-pro"));
   return;
  }

  var form_data = {
    action: 'ppc_reset_admin_menu',
    nonce: ppCapabilitiesProAdminMenus.nonce,
    reset_order: reset_order ? 1 : 0,
    reset_names: reset_names ? 1 : 0,
    reset_menu: reset_menu ? 1 : 0
  };

  ppcTimerStatus('info', __("Resetting option...", "capabilities-pro"));

  button.addClass('ppc-disabled-button');

  $.ajax({
    url: ajaxurl,
    method: 'POST',
    data: form_data,
    success: function (response) {
      ppcTimerStatus(response.status, response.message);
      button.removeClass('ppc-disabled-button');
      if (response.status == 'success') {
         location.href = response.redirect;
      }
    }
  });
});

  /**
   * Admin menu icon selector button click
   */
  $(document).on('click', '.ppc-icon-selector-button', function (e) {
    e.preventDefault();
    e.stopPropagation();

    var button = $(this);
    var menuRow = button.closest('tr');
    var originalIcon = menuRow.find('.menu-item-link .dashicons').first();

    // Store original icon class
    if (!button.data('original-icon')) {
      var dashiconMatch = originalIcon.attr('class') ? originalIcon.attr('class').match(/dashicons-[\w-]+/) : null;
      if (dashiconMatch) {
        button.data('original-icon', dashiconMatch[0].replace('dashicons-', ''));
      }
    }

    if ($('#ppc-icon-picker-popup').length) {
      $('#ppc-icon-picker-popup').remove();
      return;
    }

    var popup = $('<div id="ppc-icon-picker-popup" class="ppc-icon-picker-popup">' +
      '<div class="ppc-icon-picker-container">' +
      '<input type="text" class="ppc-icon-search" placeholder="' + ppCapabilitiesProAdminMenus.searchIcons + '">' +
      '<div class="ppc-icon-list">' + getIconList() + '</div>' +
      '</div></div>');

    $('body').append(popup);

    var buttonPos = button.offset();
    popup.css({
      top: buttonPos.top + button.outerHeight() + 5,
      left: buttonPos.left
    });
  });

  /**
   * Admin menu icon picker icon select
   */
  $(document).on('click', '.ppc-icon-picker-popup .dashicons', function () {
    var selectedIcon = '';
    var dashiconMatch = $(this).attr('class') ? $(this).attr('class').match(/dashicons-[\w-]+/) : null;
    if (dashiconMatch) {
      selectedIcon = dashiconMatch[0].replace('dashicons-', '');
    }
    var button = $('.ppc-icon-selector-button:visible');
    var menuRow = button.closest('tr');

    // Update button and original menu icon
    button.find('.dashicons').attr('class', 'dashicons dashicons-' + selectedIcon);
    menuRow.find('.menu-item-link .dashicons').first().attr('class', 'dashicons dashicons-' + selectedIcon);

    $('#ppc-icon-picker-popup').remove();
  });

  if ($('.pp-capability-menus-content').length > 0) {
    /**
     * Close icon picker when clicking outside
     */
    $(document).on('click', function (e) {
      if ($('#ppc-icon-picker-popup').length > 0 && !$(e.target).closest('.ppc-icon-picker-popup, .ppc-icon-selector-button').length) {
        var button = $('.ppc-icon-selector-button:visible');
        if (button.length) {
          var menuRow = button.closest('tr');
          var originalIcon = button.data('original-icon');
          // Revert icon changes
          if (originalIcon) {
            menuRow.find('.menu-item-link .dashicons').first().attr('class', 'dashicons dashicons-' + originalIcon);
            button.find('.dashicons').attr('class', 'dashicons dashicons-' + originalIcon);
          }
        }
        $('#ppc-icon-picker-popup').remove();
      }
    });
  }

  /**
   * Add admin menu icon search functionality
   */
  $(document).on('input', '.ppc-icon-search', function () {
    var search = $(this).val().toLowerCase();
    $('.ppc-icon-picker-popup .dashicons').each(function () {
      var icon = $(this).attr('class');
      $(this).toggle(icon.toLowerCase().indexOf(search) > -1);
    });
  });

  /**
   * Helper function to get icon list
   */
  function getIconList() {

    var icons = ppCapabilitiesProAdminMenus.dashicons || [];

    return icons.map(function (icon) {
      return '<i class="dashicons ' + icon.class + '" data-icon="' + icon.class.replace('dashicons-', '') + '" title="' + icon.name + '"></i>';
    }).join('');
  }


  /**
   * Admin menu rename button click
   */
  $(document).on('click', '.rename-admin-menu', function (e) {
    e.preventDefault();
    var menuRow = $(this).closest('tr');
    var titleSpan = menuRow.find('.menu-title');
    var otherElement = menuRow.find('.ppc-other-menu-element');
    var editForm = menuRow.find('.ppc-admin-menu-rename-form');
    var iconButton = menuRow.find('.ppc-icon-selector-button');
    var menuIcon = menuRow.find('.menu-item-link .dashicons').first();

    // Toggle the current form
    if (editForm.is(':visible')) {
      editForm.find('.menu-title-input').val($.trim(titleSpan.text()));
      editForm.hide();
      titleSpan.show();
      otherElement.show();
      // Revert icon if changed and ensure it's visible
      if (iconButton.length && iconButton.data('original-icon')) {
        menuIcon.attr('class', 'dashicons dashicons-' + iconButton.data('original-icon'));
      }
      menuIcon.show();

      iconButton.remove();
      $('#ppc-icon-picker-popup').remove();
    } else {
      // Only show icon selector for parent menus
      if (menuRow.hasClass('parent-menu') && !menuRow.hasClass('separator')) {
        var iconClass = '';
        var dashiconMatch = menuIcon.attr('class') ? menuIcon.attr('class').match(/dashicons-[\w-]+/) : null;
        if (dashiconMatch) {
          iconClass = dashiconMatch[0].replace('dashicons-', '');
        }

        if (!menuRow.find('.ppc-icon-selector-button').length) {
          menuIcon.after(
            '<button type="button" class="button button-small ppc-icon-selector-button" data-current="' + iconClass + '">' +
            '<i class="dashicons dashicons-' + iconClass + '"></i> <span>' +
            ppCapabilitiesProAdminMenus.changeIcon +
            '</span></button>'
          );
        }
        menuIcon.hide();
      }

      // Show this form
      titleSpan.hide();
      otherElement.hide();
      editForm.show();

      // Focus on input
      var input = editForm.find('.menu-title-input');
      input.focus();
    }
  });

  // Add handler for cancel button
  $(document).on('click', '.cancel-menu-title', function (e) {
    e.preventDefault();
    var menuRow = $(this).closest('tr');
    var titleSpan = menuRow.find('.menu-title');
    var otherElement = menuRow.find('.ppc-other-menu-element');
    var editForm = menuRow.find('.ppc-admin-menu-rename-form');
    var input = editForm.find('.menu-title-input');
    var iconButton = menuRow.find('.ppc-icon-selector-button');
    var menuIcon = menuRow.find('.menu-item-link .dashicons').first();

    // Reset input value
    input.val($.trim(titleSpan.text()));

    // Revert icon if changed and ensure it's visible
    if (iconButton.length && iconButton.data('original-icon')) {
      menuIcon.attr('class', 'dashicons dashicons-' + iconButton.data('original-icon'));
    }
    menuIcon.show();

    // Hide edit form and cleanup
    editForm.hide();
    titleSpan.show();
    otherElement.show();
    iconButton.remove();
    $('#ppc-icon-picker-popup').remove();
  });

  /**
   * Make admin menu sortable
   */
  if ($(".pp-capability-menus-wrapper.admin-menus table.pp-capability-menus-select tbody").length > 0) {
    $('.pp-capability-menus-wrapper.admin-menus table.pp-capability-menus-select tbody').sortable({
      items: 'tr.ppc-menu-row',
      cursor: 'move',
      placeholder: 'ppc-menu-row-placeholder',
      helper: function (e, item) {
        // If dragging parent menu, include children in visual helper
        if (item.hasClass('parent-menu')) {

          var hide_submenu = $('.admin-menu-setting-field.hide-submenu').is(':checked');
          var custom_display = hide_submenu ? 'display: none;' : '';
          var children = item.nextUntil('.parent-menu', '.child-menu');
          var clone = item.clone().wrap('<div class="menu-helper-item"></div>').parent();
          children.each(function () {
            clone = clone.add(
              $(this).clone()
                .wrap('<div class="menu-helper-item" style="' + custom_display + '"></div>')
                .parent()
            );
          });
          // Add styles to the helper wrapper
          return $('<div>')
            .append(clone)
            .css({
              'background-color': '#fff',
              'border': '1px solid #ccc',
              'box-shadow': '0 2px 5px rgba(0,0,0,0.15)'
            })
            .find('.menu-helper-item')
            .css({
              'background-color': '#fff',
              'margin-bottom': '2px',
              'padding': '5px'
            })
            .end();
        }
        // Single item helper
        return item.clone()
          .wrap('<div class="menu-helper-item"></div>')
          .parent()
          .css({
            'background-color': '#fff',
            'border': '1px solid #ccc',
            'box-shadow': '0 2px 5px rgba(0,0,0,0.15)',
            'padding': '5px'
          });
      },
      start: function (e, ui) {
        var item = ui.item;

        if (item.hasClass('parent-menu')) {
          // Store children with parent
          var children = item.nextUntil('.parent-menu', '.child-menu').detach();
          item.data('child-menus', children);
        }
      },
      stop: function (e, ui) {
        var item = ui.item;

        if (item.hasClass('parent-menu')) {
          var children = item.data('child-menus');
          var prev_item = item.prev();
          var next_item = item.next();

          if (prev_item.hasClass('child-menu') || next_item.hasClass('child-menu')) {
            $(this).sortable('cancel');
            var targetParent = prev_item.hasClass('child-menu') ? prev_item.prevAll('.parent-menu:first') : next_item.prevAll('.parent-menu:first');
            var lastChild = targetParent.nextUntil('.parent-menu', '.child-menu').last();
            var itemToMove = item.detach();
            lastChild.after(itemToMove);

            if (children && children.length) {
              itemToMove.after(children);
            }
            highlightMovedItems(itemToMove.add(children));
          } else {
            if (children && children.length) {
              children.insertAfter(item);
            }
            highlightMovedItems(item.add(children));
          }
        } else if (item.hasClass('child-menu')) {
          var prevParent = item.prevAll('.parent-menu:first');

          if (!prevParent.length || prevParent.hasClass('separator')) {
            $(this).sortable('cancel');
            return;
          }
          highlightMovedItems(item);
        }

        saveMenuOrder();
      }
    });
  }

  // Add handlers for up/down menu movement
  $(document).on("click", ".move-admin-menu", function (e) {
    e.preventDefault();
    var button = $(this);
    var currentRow = button.closest('tr.ppc-menu-row');
    var isParent = currentRow.hasClass('parent-menu');
    var direction = button.hasClass('up') ? 'up' : 'down';

    if (isParent) {
      // Parent menu movement logic
      var children = currentRow.nextUntil('.parent-menu', '.child-menu');
      var itemsToMove = children.add(currentRow);

      if (direction === 'up') {
        var prevParent = currentRow.prevAll('.parent-menu:first');
        if (prevParent.length) {
          prevParent.before(itemsToMove);
          highlightMovedItems(itemsToMove);
        }
      } else {
        var nextParent = currentRow.nextAll('.parent-menu:first');
        if (nextParent.length) {
          var nextParentChildren = nextParent.nextUntil('.parent-menu', '.child-menu');
          if (nextParentChildren.length) {
            nextParentChildren.last().after(itemsToMove);
          } else {
            nextParent.after(itemsToMove);
          }
          highlightMovedItems(itemsToMove);
        }
      }
    } else {
      if (direction === 'up') {
        var currentParent = currentRow.prevAll('.parent-menu:first');
        var prevItem = currentRow.prev('.ppc-menu-row');

        if (prevItem.hasClass('child-menu')) {
          // Simple move before previous child menu in same parent
          prevItem.before(currentRow);
          highlightMovedItems(currentRow);
        } else {
          // Find the previous non-separator parent
          var targetParent = currentParent.prevAll('.parent-menu:not(.separator)').first();
          if (targetParent.length) {
            // Find all children of target parent
            var lastChild = targetParent.nextUntil('.parent-menu', '.child-menu').last();
            if (lastChild.length) {
              // Add after the last child
              lastChild.after(currentRow);
            } else {
              // No children, add directly after parent
              targetParent.after(currentRow);
            }
            highlightMovedItems(currentRow);
          }
        }
      } else {
        var nextItem = currentRow.next('.ppc-menu-row');

        if (nextItem.hasClass('child-menu')) {
          // Simple move after next child menu
          nextItem.after(currentRow);
          highlightMovedItems(currentRow);
        } else {
          // Find the next non-separator parent
          var nextParent = currentRow.nextAll('.parent-menu:not(.separator)').first();
          if (nextParent.length) {
            nextParent.after(currentRow);
            highlightMovedItems(currentRow);
          }
        }
      }
    }

    saveMenuOrder();
  });

  /**
   * Save admin menu title
   */
  $(document).on('click', '.save-menu-title', function (e) {
    e.preventDefault();
    var button = $(this);
    var menuRow = button.closest('tr');
    var titleSpan = menuRow.find('.menu-title');
    var otherElement = menuRow.find('.ppc-other-menu-element');
    var editForm = menuRow.find('.ppc-admin-menu-rename-form');
    var input = editForm.find('.menu-title-input');
    var scope = editForm.find('.rename-menu-scope');
    var menuId = menuRow.find('input[type="checkbox"]').val();
    var menuType = menuRow.hasClass('child-menu') ? 'submenu' : 'menu';
    var menuMainSlug = menuRow.data('main-slug');
    // Get selected icon if icon selector was used
    var iconButton = menuRow.find('.ppc-icon-selector-button');
    var selectedIcon = '';
    if (iconButton.length) {
      var dashiconMatch = iconButton.find('.dashicons').attr('class') ? iconButton.find('.dashicons').attr('class').match(/dashicons-[\w-]+/) : null;
      if (dashiconMatch) {
        menuRow.find('.dashicons').attr('style', '');
        selectedIcon = dashiconMatch[0];
      }
    }

    if (input.val() == '') {
      ppcTimerStatus('error', ppCapabilitiesProAdminMenus.menu_title_empty);
      return;
    }

    ppcTimerStatus('info', ppCapabilitiesProAdminMenus.saving_menu_title);

    // Hide form, show updated title and original icon
    titleSpan.text(input.val());
    editForm.hide();
    titleSpan.show();
    otherElement.show();
    menuRow.find('.menu-item-link .dashicons').first().show();
    menuRow.find('.ppc-icon-selector-button').remove();
    $('#ppc-icon-picker-popup').remove();

    $.ajax({
      url: ajaxurl,
      method: 'POST',
      data: {
        action: 'ppc_update_admin_menu_title',
        menu_id: menuMainSlug,
        menu_title: input.val(),
        scope: scope.val(),
        menu_icon: selectedIcon,
        current_role: $('.ppc-admin-menu-role').val(),
        menu_type: menuType,
        nonce: ppCapabilitiesProAdminMenus.nonce
      },
      success: function (response) {
        ppcTimerStatus(response.status, response.message);
        if (response.menu_title) {
          titleSpan.text(response.menu_title);
          input.val(response.menu_title);
        }
      }
    });
  });

  function getMenuOrder() {
    var menuGroups = {};

    // First pass: organize menus into groups
    $('.pp-capability-menus-select tbody tr.ppc-menu-row').each(function () {
      var item = $(this);
      var menuId = item.data('main-slug');

      if (item.hasClass('parent-menu')) {
        // Initialize parent array
        // Check if this menu has any child items
        var hasChildren = $(this).next('tr').hasClass('child-menu');
        menuGroups[menuId] = hasChildren ? [] : [menuId];
      } else {
        // Get parent ID and add child to its group
        var parentId = item.prevAll('.parent-menu:first').data('main-slug');
        if (parentId) {
          if (!menuGroups[parentId]) {
            menuGroups[parentId] = [parentId];
          }
          menuGroups[parentId].push(menuId);
        }
      }
    });

    return menuGroups;
  }

  function saveMenuOrder() {
    var menuGroups = getMenuOrder();

    ppcTimerStatus('info', ppCapabilitiesProAdminMenus.saving_menu_order);

    // AJAX call to save menu order
    $.ajax({
      url: ajaxurl,
      method: 'POST',
      data: {
        action: 'ppc_update_admin_menu_order_by_ajax',
        menu_groups: menuGroups,
        current_role: $('.ppc-admin-menu-role').val(),
        nonce: ppCapabilitiesProAdminMenus.nonce
      },
      success: function (response) {
        ppcTimerStatus(response.status, response.message);
      }
    });
  }



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


  function highlightMovedItems($items) {
    $items.addClass('ppc-menu-highlight');
    setTimeout(function () {
      $items.removeClass('ppc-menu-highlight');
    }, 2000);
  }

  function generateRandomCharacters(length) {
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_';
    let result = '';
    for (let i = 0; i < length; i++) {
        const randomIndex = Math.floor(Math.random() * characters.length);
        result += characters[randomIndex];
    }
    return result;
}


});
