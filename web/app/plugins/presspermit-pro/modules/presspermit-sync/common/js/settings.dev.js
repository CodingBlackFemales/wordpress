jQuery(document).ready(function ($) {
  // Initialize Select2 with error handling
  if (typeof $.fn.select2 !== "undefined") {
    initializeSelect2();
    // Clear validation errors when role selection changes
    $(".pp-role-select2").on("change", function () {
      var $td = $(this).closest("td");
      if ($td.hasClass("pp-field-error")) {
        $td.removeClass("pp-field-error");
        $td.find(".pp-validation-error").remove();
      }
    });

    // Check for server-side validation errors on page load
    function checkServerValidationErrors() {
      var urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get("presspermit_validation_error") === "1") {
        // Run validation to highlight fields with issues
        var validation = validateRoleSelection();
        if (!validation.isValid) {
          displayValidationErrors(validation.errors);
        }
      }
    }

    // Run server validation check after Select2 is initialized
    setTimeout(checkServerValidationErrors, 300);
  } else {
    console.warn("Select2 library not loaded. Please check wp_enqueue_script.");
  }

  function initializeSelect2() {
    // Initialize role Select2 with multiple selection and icons
    $(".pp-role-select2").select2({
      placeholder: "Select roles...",
      allowClear: true,
      width: "100%",
      closeOnSelect: false,
    });

    // Handle exclusive "All Roles" selection
    $(".pp-role-select2").on("select2:select", function (e) {
      var selectedValues = $(this).val() || [];

      if (e.params.data.id === "(any)") {
        // If "All Roles" selected, clear other selections
        $(this).val(["(any)"]).trigger("change");
      } else if (selectedValues.includes("(any)")) {
        // If other role selected while "All Roles" was selected, remove "All Roles"
        var newValues = selectedValues.filter(function (val) {
          return val !== "(any)";
        });
        $(this).val(newValues).trigger("change");
      }
    });
  }

  $("#sync_posts_to_users").on("change", function () {
    $(
      "#sync_posts_to_users_settings, #sync_posts_to_users_settings_container, #sync_posts_to_users_apply_permissions, #sync_posts_to_users_apply_permissions_container"
    ).toggle($(this).is(":checked"));
    $("div.pp-sync-permissions-hint").toggle($(this).is(":checked"));
  });
  $('td.pp-sync-now input[type="checkbox"]').on("change", function () {
    var checked = $(this).is(":checked");
    $("input.pp-sync-now-button, .pp-sync-submit-row").toggle(checked);
  });

  // Function to check if row should be active (either new users OR existing users enabled)
  function updateRowState($row) {
    var $newUsersCheckbox = $row.find(".sync-enable-new-users");
    var $existingUsersCheckbox = $row.find(".sync-enable-existing-users");
    var postType = $row.data("post-type");
    var $detailRow = $(".pp-detail-" + postType);
    var $expandIcon = $row.find(".pp-expand-icon");
    var $toggleCells = $row.find(".pp-toggle");
    
    var newUsersEnabled = $newUsersCheckbox.is(":checked");
    var existingUsersEnabled = $existingUsersCheckbox.is(":checked");
    var rowActive = newUsersEnabled || existingUsersEnabled;
    
    // Update row active state
    $row.attr("data-row-active", rowActive ? "1" : "0");
    
    if (rowActive) {
      // Show expand icon and enable advanced controls
      $expandIcon.show();
      
      // Handle hierarchical type parent fields
      if ($row.find("td").first().hasClass("pp-hierarchical-type")) {
        $("th.pp-sync-parent").show();
        $row.find("td.pp-sync-parent").show();
        $("#sync_posts_to_users_settings tr td:visible span.pp-sync-parent").show();
      }
      
      // Enable form controls (except the main checkboxes which should always be enabled)
      $row.find("select, input:not(.sync-enable-new-users):not(.sync-enable-existing-users)").prop("disabled", false);
      $detailRow.find("select, input").prop("disabled", false);
    } else {
      // Hide expand icon and collapse detail row
      $expandIcon.hide();
      $detailRow.slideUp(200);
      $row.removeClass("pp-expanded");
      
      // Disable form controls (except the main checkboxes which should always be enabled)
      $row.find("select, input:not(.sync-enable-new-users):not(.sync-enable-existing-users)").prop("disabled", true);
      $detailRow.find("select, input").prop("disabled", true);
    }
  }

  // Handle new users checkbox change
  $("#sync_posts_to_users_settings input.sync-enable-new-users").on("change", function () {
    var $row = $(this).closest("tr.pp-main-row");
    updateRowState($row);
  });
  
  // Handle existing users checkbox change  
  $("#sync_posts_to_users_settings input.sync-enable-existing-users").on("change", function () {
    var $row = $(this).closest("tr.pp-main-row");
    updateRowState($row);
  });
  // Updated handlers for the new expandable structure
  $("a.ppp-suggest").on("click", function () {
    var $container = $(this).closest(".pp-field-container");
    if ($container.length) {
      // Store current text field value in buffer
      $container
        .find("input.ppp-field-buffer")
        .val($container.find("input.ppp-text-field").val());

      // Hide text field and show select
      $container.find("input.ppp-text-field").hide();

      // Set select value to first option initially
      var $select = $container.find("select.ppp-suggestion");
      $select.val($select.find("option:first").val());

      // If current text field value exists as an option, select it
      var currentVal = $container.find("input.ppp-text-field").val();
      if ($select.find("option[value='" + currentVal + "']").length > 0) {
        $select.val(currentVal);
      }

      // Show select and update text field
      $select.show();
      $container.find("input.ppp-text-field").attr("value", $select.val());

      // Hide suggest link and show cancel
      $(this).hide();
      $container.find("a.ppp-cancel").show();
      
      if ($container.find('input[name^="sync_posts_to_users_user_field"]').length) {
        // Hide template info box when switching to dropdown
        $container.closest(".pp-detail-content").find(".pp-template-info-box").hide();
      }
    } else {
      $(this)
        .closest("td")
        .find("input.ppp-text-field-buffer")
        .val($(this).closest("td").find("input.ppp-text-field").val());
      $(this).closest("td").find("input.ppp-text-field").hide();
      $(this)
        .closest("td")
        .find("select.ppp-suggestion")
        .val(
          $(this).closest("td").find("select.ppp-suggestion option:first").val()
        );
      if (
        $(this)
          .closest("td")
          .find(
            "select.ppp-suggestion option[value='" +
              $(this).closest("td").find("input.ppp-text-field").val() +
              "']"
          ).length > 0
      ) {
        $(this)
          .closest("td")
          .find("select.ppp-suggestion")
          .val($(this).closest("td").find("input.ppp-text-field").val())
          .show();
      }
      $(this).closest("td").find("select.ppp-suggestion").show();
      $(this)
        .closest("td")
        .find("input.ppp-text-field")
        .attr(
          "value",
          $(this).closest("td").find("select.ppp-suggestion").val()
        );
      $(this).hide();
    }
  });

  $("a.ppp-cancel").on("click", function () {
    var $container = $(this).closest(".pp-field-container");

    if ($container.length) {
      // Hide select and show text field
      $container.find("select.ppp-suggestion").hide();

      // Restore original value from buffer
      var originalVal = $container.find("input.ppp-field-buffer").val();
      $container.find("input.ppp-text-field").attr("value", originalVal).show();

      // Hide cancel and show suggest link
      $(this).hide();
      $container.find("a.ppp-suggest").show();

      if ($container.find('input[name^="sync_posts_to_users_user_field"]').length) {
        // Show template info box when switching to text field mode (for custom templates)
        $container.closest(".pp-detail-content").find(".pp-template-info-box").slideDown(200);
      }
    } else {
      $(this).closest("td").find("select.ppp-suggestion").hide();
      $(this)
        .closest("td")
        .find("input.ppp-text-field")
        .attr(
          "value",
          $(this).closest("td").find("input.ppp-field-buffer").val()
        );
      $(this).closest("td").find("input.ppp-text-field").show();
      $(this).hide();
      $(this).closest("td").find("a.ppp-suggest").show();
    }
  });

  $("select.ppp-suggestion").on("click", function () {
    var $container = $(this).closest(".pp-field-container");

    if ($(this).val() == "(other)") {
      if ($container.length) {
        // Switch to text input mode
        $container.find("a.ppp-cancel").trigger("click");
      } else {
        $(this).closest("td").find("a.ppp-cancel").trigger("click");
      }
    } else {
      if ($container.length) {
        // Update text field with selected value
        $container.find("input.ppp-text-field").attr("value", $(this).val());
      } else {
        $(this).siblings("input.ppp-text-field").attr("value", $(this).val());
      }
    }
  });

  // Form validation for role selection
  function validateRoleSelection() {
    var validationErrors = [];
    var hasEnabledTypes = false;

    // Check each enabled post type for role selection
    $("#sync_posts_to_users_settings tr").each(function () {
      var $row = $(this);
      var $newUsersCheckbox = $row.find("input.sync-enable-new-users");
      var $existingUsersCheckbox = $row.find("input.sync-enable-existing-users");

      // Skip header row and check if either checkbox is enabled
      if ((!$newUsersCheckbox.length && !$existingUsersCheckbox.length) || 
          (!$newUsersCheckbox.is(":checked") && !$existingUsersCheckbox.is(":checked"))) {
        return;
      }

      hasEnabledTypes = true;
      var $roleSelect = $row.find(".pp-role-select2");
      var selectedRoles = $roleSelect.val() || [];
      var postTypeName = $row.find("td.pp-posttype label").text().trim();

      // Check if no roles are selected
      if (selectedRoles.length === 0) {
        validationErrors.push({
          postType: postTypeName,
          element: $roleSelect,
          message: "Please select at least one role for " + postTypeName,
        });
      }
    });

    return {
      isValid: validationErrors.length === 0,
      errors: validationErrors,
      hasEnabledTypes: hasEnabledTypes,
    };
  }

  // Display validation errors
  function displayValidationErrors(errors) {
    // Clear previous error messages
    $(".pp-validation-error").remove();
    $(".pp-role-select2").closest("td").removeClass("pp-field-error");

    // Add error styling and messages
    errors.forEach(function (error) {
      error.element.closest("td").addClass("pp-field-error");

      // Add error message below the select element
      var errorMsg = $(
        '<div class="pp-validation-error">' + error.message + "</div>"
      );
      error.element.closest("td").append(errorMsg);
    });

    // Scroll to first error
    if (errors.length > 0) {
      $("html, body").animate(
        {
          scrollTop: errors[0].element.closest("tr").offset().top - 100,
        },
        500
      );
    }
  }

  // Clear validation errors
  function clearValidationErrors() {
    $(".pp-validation-error").remove();
    $(".pp-role-select2").closest("td").removeClass("pp-field-error");
  }

  // Form submission validation
  $("form").on("submit", function (e) {
    // Only validate if sync is enabled and we're submitting settings
    if (!$("#sync_posts_to_users").is(":checked")) {
      return true;
    }

    var validation = validateRoleSelection();

    if (!validation.isValid) {
      e.preventDefault();
      displayValidationErrors(validation.errors);

      return false;
    }
  });

  // Clear validation errors when role selection changes
  $(".pp-role-select2").on("change", function () {
    var $td = $(this).closest("td");
    if ($td.hasClass("pp-field-error")) {
      $td.removeClass("pp-field-error");
      $td.find(".pp-validation-error").remove();
    }
  });

  // Expand/collapse functionality for User Posts sync settings
  if ($("#sync_posts_to_users_settings").length) {
    // Handle expand/collapse icon click
    $(document).on("click", ".pp-expand-icon", function (e) {
      e.preventDefault();
      e.stopPropagation();

      var $icon = $(this);
      var $mainRow = $icon.closest("tr.pp-main-row");
      var postType = $mainRow.data("post-type");
      var $detailRow = $(".pp-detail-" + postType);

      if ($detailRow.is(":visible")) {
        // Collapse
        $icon.removeClass("pp-expanded");
        $mainRow.removeClass("pp-expanded");
        setTimeout(function() {
          $detailRow.toggle();
          $detailRow.removeClass("expanded");
        }, 150);
      } else {
        // Expand
        $icon.addClass("pp-expanded");
        $mainRow.addClass("pp-expanded");
        setTimeout(function() {
          $detailRow.toggle();
          $detailRow.addClass("expanded");
        }, 150);
      }
    });

    // Handle clicking on the main row (except on form controls)
    $(document).on("click", "tr.pp-main-row", function (e) {
      // Don't trigger on form controls and Select2 elements
      if (
        $(e.target).is(
          "input, select, label, .pp-sync-toggle-switch, .pp-sync-slider, .pp-expand-icon", ".pp-toggle"
        ) ||
        $(e.target).closest(".select2-container").length > 0 ||
        $(e.target).closest(".pp-role-select2").length > 0 ||
        $(e.target).closest(".pp-field-select2").length > 0 ||
        $(e.target).closest(".pp-status-select2").length > 0
      ) {
        return;
      }

      var $mainRow = $(this);
      var $expandIcon = $mainRow.find(".pp-expand-icon");

      // Only trigger if the row is enabled (has an expand icon visible)
      if ($expandIcon.is(":visible")) {
        $expandIcon.trigger("click");
      }
    });

    // Initialize the state on page load
    $("#sync_posts_to_users_settings .pp-main-row").each(function () {
      updateRowState($(this));
    });
  }
});
