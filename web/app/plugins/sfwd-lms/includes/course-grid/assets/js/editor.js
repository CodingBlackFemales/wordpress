/******/ (() => { // webpackBootstrap
/*!*************************************************!*\
  !*** ./src/assets/js/admin/gutenberg/editor.js ***!
  \*************************************************/
(function () {
  // eslint-disable-next-line camelcase
  function init_masonry() {
    // Masonry
    const wrappers = document.querySelectorAll('.learndash-course-grid[data-skin="masonry"]');
    wrappers.forEach(function (wrapper) {
      // eslint-disable-next-line camelcase
      const items_wrapper = wrapper.querySelector('.items-wrapper.masonry');

      // eslint-disable-next-line camelcase
      if (items_wrapper) {
        // eslint-disable-next-line camelcase
        const first_item = items_wrapper.querySelector('.item');
        // eslint-disable-next-line camelcase
        if (!first_item) {
          return;
        }
      }

      // eslint-disable-next-line no-undef
      learndash_course_grid_init_masonry(items_wrapper);
    });
  }
  document.addEventListener('click', function (e) {
    const el = e.target;
    if (el.closest('.learndash-block-inner > .learndash-course-grid') || el.closest('.learndash-block-inner > .learndash-course-grid-filter')) {
      e.preventDefault();
    }
  });
  setInterval(function () {
    // eslint-disable-next-line no-undef
    learndash_course_grid_init_grid_responsive_design();

    // eslint-disable-next-line camelcase
    const temp_css = document.querySelectorAll('.learndash-course-grid-temp-css');

    // eslint-disable-next-line camelcase
    if (temp_css) {
      // eslint-disable-next-line camelcase
      const css_wrapper = document.getElementById('learndash-course-grid-custom-css');

      // eslint-disable-next-line camelcase
      if (css_wrapper) {
        let style = '';

        // eslint-disable-next-line camelcase
        temp_css.forEach(function (element) {
          style += element.innerText;
        });

        // eslint-disable-next-line camelcase
        css_wrapper.innerHTML = style;
      }
    }
  }, 500);
  setInterval(function () {
    init_masonry();
  }, 2000);
})();
/******/ })()
;
//# sourceMappingURL=editor.js.map