(function ($) {
  "use strict";

  /**
   * Populate data-line on .code-annotations list items.
   *
   * Items that already carry a data-line value are left untouched.
   * Items without one receive the 1-based position within their own list,
   * so each list independently starts at 1.
   */
  $(function () {
    $(".code-annotations").each(function () {
      $(this)
        .children("li")
        .each(function () {
          if (!$(this).attr("data-line") && $(this).val()) {
            $(this).attr("data-line", $(this).val());
          }
        });
    });
  });
})(jQuery); // eslint-disable-line
