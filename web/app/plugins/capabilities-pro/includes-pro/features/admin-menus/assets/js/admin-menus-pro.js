jQuery(document).ready(function ($) {
  var tools = window.ppCapabilitiesAdminMenusTools;
  var getMenuOrder = tools.getMenuOrder;
  var generateRandomCharacters = tools.generateRandomCharacters;
  var isSeparatorMenuType = tools.isSeparatorMenuType;

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

});
