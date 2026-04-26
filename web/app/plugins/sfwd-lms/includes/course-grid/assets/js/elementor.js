/******/ (() => { // webpackBootstrap
/*!************************************!*\
  !*** ./src/assets/js/elementor.js ***!
  \************************************/
/**
 * Elementor compatibility script
 */

(function () {
  let gridFound, masonryFound;
  // eslint-disable-next-line no-undef
  const gridObserver = new MutationObserver(function (mutations) {
    mutations.forEach(function (mutationRecord) {
      const grid = mutationRecord.target.querySelector('.learndash-course-grid'),
        skin = grid.dataset.skin,
        display = mutationRecord.target.style.display;
      if ('none' !== display) {
        if ('grid' === skin) {
          gridFound = true;
        } else if ('masonry' === skin) {
          masonryFound = true;
        }
      }
    });
    if (gridFound && 'function' ===
    // eslint-disable-next-line camelcase
    typeof learndash_course_grid_init_grid_responsive_design) {
      // eslint-disable-next-line no-undef
      learndash_course_grid_init_grid_responsive_design();
    }
    if (masonryFound && 'function' ===
    // eslint-disable-next-line camelcase
    typeof learndash_course_grid_init_masonry_responsive_design) {
      // eslint-disable-next-line no-undef
      learndash_course_grid_init_masonry_responsive_design();
    }
  });
  const grids = document.querySelectorAll('.learndash-course-grid');
  grids.forEach(function (grid) {
    const target = grid.closest('.elementor-tab-content');
    if (target) {
      gridObserver.observe(target, {
        attributes: true,
        attributeFilter: ['style']
      });
    }
  });
})();
/******/ })()
;
//# sourceMappingURL=elementor.js.map