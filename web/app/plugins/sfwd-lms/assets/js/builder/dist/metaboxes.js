/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
/*!**********************!*\
  !*** ./metaboxes.js ***!
  \**********************/
if ('block' === window.learndash_builder_metaboxes.editor) {
  // If Gutenberg is used, make sure some metaboxes can't be toggled off
  wp.data.subscribe(() => {
    // "Always On" panels.
    const alwaysOn = ['meta-box-learndash-course-access-settings', 'meta-box-learndash-course-display-content-settings', 'meta-box-learndash-course-navigation-settings', 'meta-box-learndash_course_builder', 'meta-box-learndash_course_groups', 'meta-box-learndash_quiz_builder', 'meta-box-sfwd-course-lessons', 'meta-box-sfwd-course-quizzes', 'meta-box-sfwd-course-topics', 'meta-box-sfwd-quiz']; // WordPress Data Store information.

    const store = wp.data.select('core/edit-post');
    const panels = store.getPreference('panels'); // Loop over the panels object, but only those panels listed as "Always ON".

    for (const id in panels) {
      if (panels.hasOwnProperty(id) && alwaysOn.includes(id)) {
        const panel = panels[id]; // Only perform the actions with panels with enabled property.

        if (panel.hasOwnProperty('enabled')) {
          if (!panel.enabled) {
            wp.data.dispatch('core/edit-post').toggleEditorPanelEnabled(id);
          }
        }
      }
    }
  });
} else {
  // Metaboxes IDs
  const alwaysOn = ['learndash-course-access-settings', 'learndash-course-display-content-settings', 'learndash-course-navigation-settings', 'learndash_course_builder', 'learndash_course_groups', 'learndash_quiz_builder', 'sfwd-course-lessons', 'sfwd-course-quizzes', 'sfwd-course-topics', 'sfwd-quiz']; // We need to follow the core postbox.js to bind the events

  jQuery('.hide-postbox-tog').on('click.postboxes', function (e) {
    const $el = jQuery(this),
          boxId = $el.val(),
          $postbox = jQuery('#' + boxId); // Check if the metabox is in "always on"

    if (-1 < alwaysOn.indexOf(boxId)) {
      if (!$el.prop('checked')) {
        // Prevent unchecking and force visibility
        e.preventDefault();
        $postbox.show();
        $el.prop('checked', true);
      }
    }
  });
}
/******/ })()
;
//# sourceMappingURL=metaboxes.js.map